import React, { useEffect, useState, useMemo } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ScrollView, ActivityIndicator } from 'react-native';
import { getProduct, Product } from '../services/productService';
import { useCart } from '../services/CartContext';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "Urun-Detay-Screen 6" — Ürün Bilgileri, Bilgilendirme, Sipariş Parametreleri, Özet Hesap
export default function UrunDetayScreen({ route, navigation }: any) {
  const { productId } = route.params;
  const { addItem } = useCart();

  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);

  const [koliAdedi, setKoliAdedi] = useState('');
  const [iskonto1, setIskonto1] = useState('');
  const [iskonto2, setIskonto2] = useState('');
  const [iskonto3, setIskonto3] = useState('');
  const [vadeGun, setVadeGun] = useState('');

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const p = await getProduct(productId);
        setProduct(p);
      } finally {
        setLoading(false);
      }
    })();
  }, [productId]);

  const hesap = useMemo(() => {
    if (!product) return null;
    const koli = parseInt(koliAdedi || '0', 10);
    const isk1 = parseFloat(iskonto1 || '0');
    const isk2 = parseFloat(iskonto2 || '0');
    const isk3 = parseFloat(iskonto3 || '0');

    const adet = koli * product.koli_ici_adet;
    const netFiyat =
      product.koli_fiyati * (1 - isk1 / 100) * (1 - isk2 / 100) * (1 - isk3 / 100);
    const netTutar = netFiyat * koli;

    return { koli, adet, netFiyat, netTutar };
  }, [product, koliAdedi, iskonto1, iskonto2, iskonto3]);

  if (loading || !product) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  const girilebilir = hesap ? hesap.adet <= product.stok && hesap.koli > 0 : false;

  const handleSepeteEkle = () => {
    if (!girilebilir || !hesap) return;
    addItem({
      product_id: product.id,
      urun_kodu: product.urun_kodu,
      urun_adi: product.urun_adi,
      koli_adedi: hesap.koli,
      koli_fiyati: product.koli_fiyati,
      iskonto_1: parseFloat(iskonto1 || '0'),
      iskonto_2: parseFloat(iskonto2 || '0'),
      iskonto_3: parseFloat(iskonto3 || '0'),
    });
    navigation.goBack();
  };

  return (
    <View style={styles.container}>
      <HeaderBar title="Ürün Detay Sayfası" />

      <View style={styles.productStrip}>
        <Text style={styles.stripItem}>Ürün Kodu: {product.urun_kodu}</Text>
        <Text style={styles.stripItem}>Stok Adedi: {product.stok}</Text>
        <Text style={styles.stripItem}>Ürün İsmi: {product.urun_adi}</Text>
      </View>

      <ScrollView contentContainerStyle={styles.columns}>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Ürün Bilgileri</Text>
          <Row label="Liste Fiyatı" value={`${product.liste_fiyati} TL`} />
          <Row label="Koli Fiyatı" value={`${product.koli_fiyati} TL`} />
          <Row label="Koli İçi" value={`${product.koli_ici_adet} Adet`} />
          <Row label="Kdv" value={`%${product.kdv_orani}`} />
          <Row label="Hacim (m3)" value={`${product.hacim_m3} m3`} />
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Sipariş Parametreleri</Text>
          <LabeledInput label="Sipariş Koli" value={koliAdedi} onChangeText={setKoliAdedi} placeholder="Koli adedi giriniz..." keyboardType="numeric" />
          <LabeledInput label="İskonto 1 (%)" value={iskonto1} onChangeText={setIskonto1} placeholder="-%" keyboardType="numeric" />
          <LabeledInput label="İskonto 2 (%)" value={iskonto2} onChangeText={setIskonto2} placeholder="-%" keyboardType="numeric" />
          <LabeledInput label="İskonto 3 (%)" value={iskonto3} onChangeText={setIskonto3} placeholder="-%" keyboardType="numeric" />
          <LabeledInput label="Vade / Gün" value={vadeGun} onChangeText={setVadeGun} placeholder="Gün giriniz..." keyboardType="numeric" />

          <View style={styles.statusRow}>
            <View style={[styles.statusBox, girilebilir && styles.statusBoxActive]}>
              <Text style={[styles.statusText, girilebilir && styles.statusTextActive]}>Girilebilir</Text>
            </View>
            <View style={[styles.statusBox, !girilebilir && styles.statusBoxDenyActive]}>
              <Text style={[styles.statusText, !girilebilir && styles.statusTextDeny]}>Girilemez</Text>
            </View>
          </View>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Bilgilendirme (Özet Hesap)</Text>
          <Row label="Sipariş Adet" value={`${hesap?.adet ?? 0} Adet`} />
          <Row label="Koli / Koli İçi" value={`${hesap?.koli ?? 0} Koli / ${product.koli_ici_adet}`} />
          <Row label="Net Fiyat" value={`${(hesap?.netFiyat ?? 0).toFixed(2)} TL`} />
          <Row label="Net Tutar" value={`${(hesap?.netTutar ?? 0).toFixed(2)} TL`} />

          <View style={styles.actionRow}>
            <TouchableOpacity style={styles.cancelBtn} onPress={() => navigation.goBack()}>
              <Text style={styles.cancelBtnText}>Vazgeç</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.addBtn, !girilebilir && styles.addBtnDisabled]}
              onPress={handleSepeteEkle}
              disabled={!girilebilir}
            >
              <Text style={styles.addBtnText}>Sepete Ekle</Text>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

function LabeledInput(props: any) {
  return (
    <View style={styles.inputField}>
      <Text style={styles.inputLabel}>{props.label}</Text>
      <TextInput style={styles.inputBox} {...props} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  productStrip: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    backgroundColor: colors.card,
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  stripItem: { fontSize: 12, color: colors.text, fontWeight: '600' },
  columns: { padding: spacing.md, gap: spacing.md },
  card: {
    backgroundColor: colors.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  cardTitle: { fontSize: 14, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  rowLabel: { fontSize: 13, color: colors.textMuted },
  rowValue: { fontSize: 13, color: colors.text, fontWeight: '600' },
  inputField: { marginBottom: spacing.sm },
  inputLabel: { fontSize: 12, color: colors.text, marginBottom: 4, fontWeight: '500' },
  inputBox: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    paddingHorizontal: spacing.sm,
    paddingVertical: 10,
    fontSize: 14,
    backgroundColor: colors.background,
  },
  statusRow: { flexDirection: 'row', marginTop: spacing.sm, gap: spacing.sm },
  statusBox: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    paddingVertical: 10,
    alignItems: 'center',
  },
  statusBoxActive: { backgroundColor: '#DCFCE7', borderColor: colors.success },
  statusBoxDenyActive: { backgroundColor: '#FEE2E2', borderColor: colors.danger },
  statusText: { fontSize: 12, color: colors.textMuted, fontWeight: '600' },
  statusTextActive: { color: colors.success },
  statusTextDeny: { color: colors.danger },
  actionRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  cancelBtn: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  cancelBtnText: { color: colors.text, fontWeight: '600' },
  addBtn: {
    flex: 1,
    backgroundColor: colors.primary,
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  addBtnDisabled: { backgroundColor: colors.border },
  addBtnText: { color: '#fff', fontWeight: '600' },
});
