import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { useCart } from '../services/CartContext';
import { createOrder } from '../services/orderService';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "Siparis-Formu-Screen 8" — Evrak Açıklaması, Teslim Tarihi, Ambar Bilgisi, Ödeme Tipi
export default function SiparisFormuScreen({ navigation }: any) {
  const { customerId, customerName, items, clearCart } = useCart();

  const [evrakAciklamasi, setEvrakAciklamasi] = useState('');
  const [teslimTarihi, setTeslimTarihi] = useState('');
  const [ambarBilgisi, setAmbarBilgisi] = useState('');
  const [odemeTipi, setOdemeTipi] = useState<'NAKIT' | 'VADELI'>('NAKIT');
  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    if (!customerId) return;
    setSaving(true);
    try {
      const result = await createOrder({
        customer_id: customerId,
        evrak_aciklamasi: evrakAciklamasi || undefined,
        teslim_tarihi: normalizeDate(teslimTarihi) || undefined,
        ambar_bilgisi: ambarBilgisi || undefined,
        odeme_tipi: odemeTipi,
        items: items.map((i) => ({
          product_id: i.product_id,
          koli_adedi: i.koli_adedi,
          iskonto_1: i.iskonto_1,
          iskonto_2: i.iskonto_2,
          iskonto_3: i.iskonto_3,
        })),
      });
      clearCart();
      Alert.alert('Sipariş Kaydedildi', `Sipariş No: ${result.siparis_no}`, [
        { text: 'Tamam', onPress: () => navigation.navigate('MainTabs') },
      ]);
    } catch (e: any) {
      Alert.alert('Hata', e.message || 'Sipariş kaydedilemedi.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <HeaderBar title="Sipariş Formu" subtitle={`Cari İsmi: ${customerName ?? ''}`} />

      <View style={styles.formCard}>
        <Text style={styles.formTitle}>Siparişi Tamamla</Text>
        <Text style={styles.formSubtitle}>İşlemi sonlandırmak için teslimat ve ödeme bilgilerini girin.</Text>

        <LabeledInput
          label="Evrak Açıklaması"
          value={evrakAciklamasi}
          onChangeText={setEvrakAciklamasi}
          placeholder="Açıklama giriniz..."
        />
        <LabeledInput
          label="Teslim Tarihi"
          value={teslimTarihi}
          onChangeText={setTeslimTarihi}
          placeholder="Tarih Seçiniz... (gg.aa.yyyy)"
        />
        <LabeledInput
          label="Ambar Bilgisi"
          value={ambarBilgisi}
          onChangeText={setAmbarBilgisi}
          placeholder="Ambar bilgisi giriniz..."
        />

        <Text style={styles.inputLabel}>Ödeme Tipi Seçimi</Text>
        <View style={styles.paymentRow}>
          <TouchableOpacity
            style={[styles.paymentOption, odemeTipi === 'NAKIT' && styles.paymentOptionActive]}
            onPress={() => setOdemeTipi('NAKIT')}
          >
            <Text style={odemeTipi === 'NAKIT' ? styles.paymentTextActive : styles.paymentText}>Nakit</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.paymentOption, odemeTipi === 'VADELI' && styles.paymentOptionActive]}
            onPress={() => setOdemeTipi('VADELI')}
          >
            <Text style={odemeTipi === 'VADELI' ? styles.paymentTextActive : styles.paymentText}>Vadeli</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.footer}>
          <TouchableOpacity style={styles.cancelBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.cancelBtnText}>Vazgeç ve Sepete Dön</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
            {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Siparişi Kaydet</Text>}
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

function normalizeDate(input: string): string | null {
  // gg.aa.yyyy -> yyyy-mm-dd
  const match = input.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
  if (!match) return null;
  return `${match[3]}-${match[2]}-${match[1]}`;
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
  formCard: {
    backgroundColor: colors.card,
    margin: spacing.md,
    borderRadius: 14,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  formTitle: { fontSize: 20, fontWeight: '700', color: colors.text },
  formSubtitle: { fontSize: 13, color: colors.textMuted, marginTop: 4, marginBottom: spacing.lg },
  inputField: { marginBottom: spacing.md },
  inputLabel: { fontSize: 13, color: colors.text, marginBottom: spacing.xs, fontWeight: '500' },
  inputBox: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    fontSize: 14,
    backgroundColor: colors.background,
  },
  paymentRow: { flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.lg },
  paymentOption: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  paymentOptionActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  paymentText: { color: colors.text, fontWeight: '600' },
  paymentTextActive: { color: '#fff', fontWeight: '600' },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.md,
    gap: spacing.sm,
  },
  cancelBtn: { paddingVertical: 12, paddingHorizontal: spacing.md },
  cancelBtnText: { color: colors.textMuted, fontWeight: '600' },
  saveBtn: {
    backgroundColor: colors.primary,
    borderRadius: 10,
    paddingVertical: 12,
    paddingHorizontal: spacing.lg,
    minWidth: 160,
    alignItems: 'center',
  },
  saveBtnText: { color: '#fff', fontWeight: '700' },
});
