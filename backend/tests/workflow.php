<?php
putenv('DB_NAME=mobilerp2_test_20260909');
require __DIR__.'/../config/config.php';
require __DIR__.'/../includes/functions.php';
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/order_creation.php';
require __DIR__.'/../includes/order_workflow.php';
$pdo = getDbConnection();
if (($argv[1] ?? '') === '--approve') { changeOrderStatus($pdo,(int)$argv[2],'ONAYLANDI'); exit; }
$passed=0;
function check($ok,$name) { global $passed; if (!$ok) throw new RuntimeException($name); $passed++; echo "PASS $name\n"; }
function reject($fn,$name) { try { $fn(); } catch (DomainException $e) { check(true,$name); return; } throw new RuntimeException('Expected rejection: '.$name); }
$user=['employee_id'=>3,'rol'=>'CALISAN'];
$payload=['customer_id'=>1,'items'=>[['product_id'=>1,'koli_adedi'=>1,'iskonto_1'=>10]],'request_id'=>'test_'.bin2hex(random_bytes(12))];
$before=(int)$pdo->query('SELECT stok FROM products WHERE id=1')->fetchColumn();
$o=createOrderRecord($pdo,$user,$payload); check((float)$o['genel_toplam']===108.0,'discount plus tax');
$retry=createOrderRecord($pdo,$user,$payload);check($retry['order_id']===$o['order_id'],'retry returns same order');
reject(fn()=>createOrderRecord($pdo,$user,array_replace($payload,['note'=>'changed'])),'reject idempotency key reuse with changed data');
$base=['customer_id'=>1,'items'=>[['product_id'=>1,'adet'=>3,'koli_adedi'=>0.3]]];
$units=createOrderRecord($pdo,$user,$base);check((float)$units['genel_toplam']===36.0,'three units remains three units');
$q=$pdo->prepare('SELECT adet,koli_adedi FROM order_items WHERE order_id=?');$q->execute([$units['order_id']]);$line=$q->fetch();check((int)$line['adet']===3 && (float)$line['koli_adedi']===0.3,'fractional boxes stored without truncation');
reject(fn()=>createOrderRecord($pdo,$user,['customer_id'=>1,'items'=>[['product_id'=>1,'koli_adedi'=>0.3]]]),'legacy fractional boxes rejected instead of truncated');
reject(fn()=>createOrderRecord($pdo,$user,['customer_id'=>1,'items'=>[['product_id'=>1,'koli_adedi'=>1,'iskonto_1'=>50]]]),'dip price enforced');
reject(fn()=>createOrderRecord($pdo,$user,['customer_id'=>1,'items'=>[['product_id'=>1,'koli_adedi'=>1,'iskonto_1'=>101]]]),'discount out of range');
reject(fn()=>createOrderRecord($pdo,$user,['customer_id'=>1,'items'=>[['product_id'=>1,'koli_adedi'=>1],['product_id'=>1,'koli_adedi'=>1]]]),'duplicate products rejected');
reject(fn()=>createOrderRecord($pdo,$user,array_replace($base,['teslim_tarihi'=>'2027-02-31'])),'invalid calendar day rejected');
reject(fn()=>createOrderRecord($pdo,$user,array_replace($base,['teslim_tarihi'=>'2020-01-01'])),'past delivery rejected');
reject(fn()=>createOrderRecord($pdo,$user,array_replace($base,['odeme_tipi'=>'VADELI'])),'credit requires term');
reject(fn()=>createOrderRecord($pdo,$user,array_replace($base,['expected_total'=>1])),'stale displayed price rejected');
$pdo->exec('UPDATE customers SET durum="PASIF" WHERE id=1');
reject(fn()=>createOrderRecord($pdo,$user,$base),'inactive customer rejected');$pdo->exec('UPDATE customers SET durum="AKTIF" WHERE id=1');
reject(fn()=>createOrderRecord($pdo,['employee_id'=>4,'rol'=>'CALISAN'],$base),'unassigned customer rejected');
reject(fn()=>changeOrderStatus($pdo,$o['order_id'],'TAMAMLANDI'),'cannot complete without approval');
// Spawn actual parallel processes, each with its own database connection.
$workers=[];
for($i=0;$i<4;$i++) {
  $pipes=[];$process=proc_open([PHP_BINARY,__FILE__,'--approve',(string)$o['order_id']],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  fclose($pipes[0]);$workers[]=[$process,$pipes];
}
foreach($workers as [$process,$pipes]) { $out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($process)===0,'parallel approval worker: '.trim($out)); }
check((int)$pdo->query('SELECT stok FROM products WHERE id=1')->fetchColumn()===$before-10,'parallel approvals deduct only once');
reject(fn()=>changeOrderStatus($pdo,$o['order_id'],'BEKLEMEDE'),'approved order cannot reopen');
changeOrderStatus($pdo,$o['order_id'],'IPTAL');changeOrderStatus($pdo,$o['order_id'],'IPTAL');
check((int)$pdo->query('SELECT stok FROM products WHERE id=1')->fetchColumn()===$before,'cancellation restores stock once');
reject(fn()=>changeOrderStatus($pdo,$o['order_id'],'ONAYLANDI'),'cancelled order cannot reapprove');
changeOrderStatus($pdo,$units['order_id'],'ONAYLANDI');changeOrderStatus($pdo,$units['order_id'],'TAMAMLANDI');
$q=$pdo->prepare('SELECT teslim_edilme_tarihi FROM orders WHERE id=?');$q->execute([$units['order_id']]);check((bool)$q->fetchColumn(),'completion date saved');
$rollback=createOrderRecord($pdo,$user,['customer_id'=>1,'items'=>[['product_id'=>1,'koli_adedi'=>1],['product_id'=>2,'koli_adedi'=>1]]]);
$stock1=(int)$pdo->query('SELECT stok FROM products WHERE id=1')->fetchColumn();$stock2=(int)$pdo->query('SELECT stok FROM products WHERE id=2')->fetchColumn();
$pdo->exec('UPDATE products SET stok=0 WHERE id=2');reject(fn()=>changeOrderStatus($pdo,$rollback['order_id'],'ONAYLANDI'),'insufficient later line rolls back whole approval');
check((int)$pdo->query('SELECT stok FROM products WHERE id=1')->fetchColumn()===$stock1,'earlier line stock restored on rollback');$pdo->exec('UPDATE products SET stok='.$stock2.' WHERE id=2');
echo "Passed: $passed workflow assertions\n";
