<?php
function orderNumberValue($value, string $label, float $min, float $max, bool $integer = false): float {
    if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < $min || (float)$value > $max || ($integer && floor((float)$value) !== (float)$value)) {
        throw new DomainException($label . ' geçersiz.');
    }
    return (float)$value;
}
/** Prices and quantities are always recalculated from authoritative products. */
function createOrderRecord(PDO $pdo, array $user, array $data): array {
    $customerId = (int)orderNumberValue($data['customer_id'] ?? 0, 'Cari', 1, 2147483647, true);
    $items = $data['items'] ?? null;
    if (!is_array($items) || !array_is_list($items) || count($items) < 1 || count($items) > 200) throw new DomainException('Sipariş 1–200 kalem içermelidir.');
    $payment = $data['odeme_tipi'] ?? 'NAKIT';
    if (!in_array($payment, ['NAKIT', 'VADELI'], true)) throw new DomainException('Geçersiz ödeme tipi.');
    $term = $payment === 'VADELI' ? (int)orderNumberValue($data['vade_gun'] ?? 0, 'Vade günü', 1, 365, true) : null;
    $delivery = $data['teslim_tarihi'] ?? null;
    if ($delivery !== null && $delivery !== '') {
        $date = is_string($delivery) ? DateTimeImmutable::createFromFormat('!Y-m-d', $delivery) : false;
        if (!$date || $date->format('Y-m-d') !== $delivery || $delivery < date('Y-m-d')) throw new DomainException('Teslim tarihi geçerli olmalı ve geçmişte olmamalıdır.');
    } else { $delivery = null; }
    foreach (['evrak_aciklamasi'=>500, 'ambar_bilgisi'=>255, 'note'=>5000] as $field=>$limit) {
        if (isset($data[$field]) && (!is_string($data[$field]) || mb_strlen($data[$field]) > $limit)) throw new DomainException($field . ' çok uzun veya geçersiz.');
    }
    $requestId = $data['request_id'] ?? null;
    if ($requestId !== null && (!is_string($requestId) || !preg_match('/^[A-Za-z0-9_-]{16,80}$/', $requestId))) throw new DomainException('Geçersiz istek kimliği.');
    $requestHash = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    $pdo->beginTransaction();
    try {
        // Serialize retries by this employee. This lock also guards duplicate submits.
        $employee = $pdo->prepare('SELECT id FROM employees WHERE id = ? FOR UPDATE');
        $employee->execute([$user['employee_id']]);
        if ($requestId !== null) {
            $existing = $pdo->prepare('SELECT id AS order_id, siparis_no, genel_toplam, request_hash FROM orders WHERE employee_id = ? AND request_id = ?');
            $existing->execute([$user['employee_id'], $requestId]);
            if ($order = $existing->fetch()) {
                if (!hash_equals($order['request_hash'], $requestHash)) throw new DomainException('Aynı istek kimliği farklı sipariş için kullanılamaz.');
                unset($order['request_hash']); $pdo->commit(); return $order + ['success'=>true, 'replayed'=>true];
            }
        }
        $customer = $pdo->prepare('SELECT durum FROM customers WHERE id = ? LOCK IN SHARE MODE');
        $customer->execute([$customerId]);
        if ($customer->fetchColumn() !== 'AKTIF') throw new DomainException('Cari bulunamadı veya pasif.');
        if (!employeeCanAccessCustomer((int)$user['employee_id'], $customerId, $user['rol'])) throw new DomainException('Bu cariye sipariş oluşturma yetkiniz yok.');
        $seen = []; $lines = []; $subtotal = 0; $taxTotal = 0;
        foreach ($items as $item) if (!is_array($item)) throw new DomainException('Geçersiz sipariş kalemi.');
        usort($items, fn($a, $b) => (int)($a['product_id'] ?? 0) <=> (int)($b['product_id'] ?? 0));
        foreach ($items as $item) {
            if (!is_array($item)) throw new DomainException('Geçersiz sipariş kalemi.');
            $pid = (int)orderNumberValue($item['product_id'] ?? 0, 'Ürün', 1, 2147483647, true);
            if (isset($seen[$pid])) throw new DomainException('Aynı ürün tek kalemde gönderilmelidir.');
            $seen[$pid] = true;
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND aktif = 1 FOR UPDATE'); $stmt->execute([$pid]);
            $p = $stmt->fetch();
            if (!$p) throw new DomainException('Ürün bulunamadı.');
            $pack = (int)$p['koli_ici_adet'];
            if ($pack < 1) throw new DomainException('Ürün koli içi adedi geçersiz.');
            if (isset($item['adet'])) {
                $units = (int)orderNumberValue($item['adet'], 'Adet', 1, 2147483647, true);
                $boxes = $units / $pack;
                if (isset($item['koli_adedi']) && abs(orderNumberValue($item['koli_adedi'], 'Koli adedi', 0, 2147483647) - $boxes) > 0.00001) throw new DomainException('Adet ve koli miktarı tutarsız.');
            } else {
                $boxes = orderNumberValue($item['koli_adedi'] ?? 0, 'Koli adedi', 1, 2147483647, true);
                $units = $boxes * $pack;
                if ($units > 2147483647) throw new DomainException('Miktar çok büyük.');
            }
            if ($units > (int)$p['stok']) throw new DomainException('Yetersiz stok: ' . $p['urun_adi']);
            $discounts = [];
            for ($i=1; $i<=3; $i++) $discounts[] = round(orderNumberValue($item['iskonto_'.$i] ?? 0, 'İskonto', 0, 100), 2);
            $unitPrice = (float)$p['liste_fiyati'];
            $price = round($unitPrice * $pack, 2);
            $netUnitPrice = round($unitPrice * (1-$discounts[0]/100) * (1-$discounts[1]/100) * (1-$discounts[2]/100), 2);
            $netPrice = round($netUnitPrice * $pack, 2);
            if ($unitPrice < 0 || $netUnitPrice < round((float)$p['dip_fiyat'],2)) throw new DomainException('Net adet fiyatı dip adet fiyatının altında: ' . $p['urun_adi']);
            $net = round($netUnitPrice * $units, 2);
            $taxRate = (float)$p['kdv_orani'];
            if ($taxRate < 0 || $taxRate > 100) throw new DomainException('Ürün KDV oranı geçersiz.');
            $tax = round($net * $taxRate / 100, 2);
            $subtotal += $net; $taxTotal += $tax;
            $lines[] = [$pid, $boxes, $units, $price, ...$discounts, $netPrice, $net, $taxRate, $tax];
        }
        $total = round($subtotal + $taxTotal, 2);
        if ($total > 999999999999.99) throw new DomainException('Sipariş toplamı çok büyük.');
        if (isset($data['expected_total']) && abs(orderNumberValue($data['expected_total'], 'Beklenen toplam', 0, 999999999999.99) - $total) > 0.009) throw new DomainException('Ürün fiyatı veya KDV değişti. Sepeti güncelleyip toplamı tekrar kontrol edin.');
        $number = generateOrderNumber();
        $stmt = $pdo->prepare('INSERT INTO orders (siparis_no, customer_id, employee_id, evrak_aciklamasi, teslim_tarihi, ambar_bilgisi, odeme_tipi, vade_gun, durum, ara_toplam, kdv_toplam, genel_toplam, note, request_id, request_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "BEKLEMEDE", ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$number, $customerId, $user['employee_id'], $data['evrak_aciklamasi'] ?? null, $delivery, $data['ambar_bilgisi'] ?? null, $payment, $term, round($subtotal,2), round($taxTotal,2), $total, $data['note'] ?? null, $requestId, $requestHash]);
        $id = (int)$pdo->lastInsertId();
        $insert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, koli_adedi, adet, birim_fiyat, iskonto_1, iskonto_2, iskonto_3, net_fiyat, net_tutar, kdv_orani, kdv_tutari) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($lines as $line) $insert->execute([$id, ...$line]);
        $pdo->commit();
        return ['success'=>true, 'order_id'=>$id, 'siparis_no'=>$number, 'genel_toplam'=>$total];
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
