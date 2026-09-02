import React, { useEffect, useState, useMemo } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ScrollView, ActivityIndicator, KeyboardAvoidingView, Platform } from 'react-native';
import { getProduct, Product } from '../services/productService';
import { useCart } from '../services/CartContext';
import { colors, spacing, radius, fontSize, cardStyle } from '../constants/theme';
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
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <HeaderBar title="Ürün Detay Sayfası" subtitle={product.urun_adi} />

      <View style={styles.productStrip}>
        <View style={styles.stripItem}>
          <Text style={styles.stripLabel}>Ürün Kodu</Text>
          <Text style={styles.stripValue} numberOfLines={1}>{product.urun_kodu}</Text>
        </View>
        <View style={styles.stripDivider} />
        <View style={styles.stripItem}>
          <Text style={styles.stripLabel}>Stok Adedi</Text>
          <Text style={styles.stripValue}>{product.stok}</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.columns} keyboardShouldPersistTaps="handled">
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
          <View style={styles.inputRow}>
            <LabeledInput style={{ flex: 1 }} label="Sipariş Koli" value={koliAdedi} onChangeText={setKoliAdedi} placeholder="0" keyboardType="numeric" />
            <LabeledInput style={{ flex: 1 }} label="Vade / Gün" value={vadeGun} onChangeText={setVadeGun} placeholder="0" keyboardType="numeric" />
          </View>
          <View style={styles.inputRow}>
            <LabeledInput style={{ flex: 1 }} label="İskonto 1 (%)" value={iskonto1} onChangeText={setIskonto1} placeholder="0" keyboardType="numeric" />
            <LabeledInput style={{ flex: 1 }} label="İskonto 2 (%)" value={iskonto2} onChangeText={setIskonto2} placeholder="0" keyboardType="numeric" />
            <LabeledInput style={{ flex: 1 }} label="İskonto 3 (%)" value={iskonto3} onChangeText={setIskonto3} placeholder="0" keyboardType="numeric" />
          </View>

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
          <Row label="Net Tutar" value={`${(hesap?.netTutar ?? 0).toFixed(2)} TL`} last />

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
    </KeyboardAvoidingView>
  );
}

function Row({ label, value, last }: { label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.row, last && { borderBottomWidth: 0 }]}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

function LabeledInput({ style, label, ...rest }: any) {
  return (
    <View style={[styles.inputField, style]}>
      <Text style={styles.inputLabel}>{label}</Text>
      <TextInput style={styles.inputBox} placeholderTextColor={colors.textMuted} {...rest} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  productStrip: {
    flexDirection: 'row',
    backgroundColor: colors.card,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  stripItem: { flex: 1 },
  stripDivider: { width: 1, backgroundColor: colors.border, marginHorizontal: spacing.md },
  stripLabel: { fontSize: fontSize.xs, color: colors.textMuted },
  stripValue: { fontSize: fontSize.base, color: colors.text, fontWeight: '700', marginTop: 2 },
  columns: { padding: spacing.md, paddingBottom: spacing.xl },
  card: { ...cardStyle, padding: spacing.md, marginBottom: spacing.md },
  cardTitle: { fontSize: fontSize.base, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  rowLabel: { fontSize: fontSize.sm, color: colors.textMuted },
  rowValue: { fontSize: fontSize.sm, color: colors.text, fontWeight: '600' },
  inputRow: { flexDirection: 'row', gap: spacing.sm },
  inputField: { marginBottom: spacing.sm },
  inputLabel: { fontSize: fontSize.xs, color: colors.text, marginBottom: 4, fontWeight: '500' },
  inputBox: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 10,
    fontSize: fontSize.base,
    color: colors.text,
    backgroundColor: colors.background,
  },
  statusRow: { flexDirection: 'row', marginTop: spacing.xs, gap: spacing.sm },
  statusBox: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.sm,
    paddingVertical: 10,
    alignItems: 'center',
  },
  statusBoxActive: { backgroundColor: colors.successSoft, borderColor: colors.success },
  statusBoxDenyActive: { backgroundColor: colors.dangerSoft, borderColor: colors.danger },
  statusText: { fontSize: fontSize.sm, color: colors.textMuted, fontWeight: '600' },
  statusTextActive: { color: colors.success },
  statusTextDeny: { color: colors.danger },
  actionRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  cancelBtn: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 13,
    alignItems: 'center',
  },
  cancelBtnText: { color: colors.text, fontWeight: '600', fontSize: fontSize.base },
  addBtn: {
    flex: 1,
    backgroundColor: colors.buttonPrimary,
    borderRadius: radius.md,
    paddingVertical: 13,
    alignItems: 'center',
  },
  addBtnDisabled: { backgroundColor: colors.border },
  addBtnText: { color: '#fff', fontWeight: '700', fontSize: fontSize.base },
});
