import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useCart } from '../services/CartContext';
import { createOrder } from '../services/orderService';
import { colors, spacing, radius, fontSize, cardStyle } from '../constants/theme';
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
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <HeaderBar title="Sipariş Formu" subtitle={`Cari İsmi: ${customerName ?? ''}`} />

      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }} keyboardShouldPersistTaps="handled">
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
            placeholder="gg.aa.yyyy"
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
            <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
              {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Siparişi Kaydet</Text>}
            </TouchableOpacity>
            <TouchableOpacity style={styles.cancelBtn} onPress={() => navigation.goBack()}>
              <Text style={styles.cancelBtnText}>Vazgeç ve Sepete Dön</Text>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function normalizeDate(input: string): string | null {
  // gg.aa.yyyy -> yyyy-mm-dd
  const match = input.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
  if (!match) return null;
  return `${match[3]}-${match[2]}-${match[1]}`;
}

function LabeledInput({ label, ...rest }: any) {
  return (
    <View style={styles.inputField}>
      <Text style={styles.inputLabel}>{label}</Text>
      <TextInput style={styles.inputBox} placeholderTextColor={colors.textMuted} {...rest} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  formCard: { ...cardStyle, padding: spacing.lg },
  formTitle: { fontSize: fontSize.xl, fontWeight: '700', color: colors.text },
  formSubtitle: { fontSize: fontSize.sm, color: colors.textMuted, marginTop: 4, marginBottom: spacing.lg },
  inputField: { marginBottom: spacing.md },
  inputLabel: { fontSize: fontSize.sm, color: colors.text, marginBottom: spacing.xs, fontWeight: '500' },
  inputBox: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    fontSize: fontSize.base,
    color: colors.text,
    backgroundColor: colors.background,
  },
  paymentRow: { flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.lg },
  paymentOption: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 12,
    alignItems: 'center',
  },
  paymentOptionActive: { backgroundColor: colors.buttonPrimary, borderColor: colors.buttonPrimary },
  paymentText: { color: colors.text, fontWeight: '600' },
  paymentTextActive: { color: '#fff', fontWeight: '600' },
  footer: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.md,
    gap: spacing.sm,
  },
  saveBtn: {
    backgroundColor: colors.buttonPrimary,
    borderRadius: radius.md,
    paddingVertical: 14,
    alignItems: 'center',
  },
  saveBtnText: { color: '#fff', fontWeight: '700', fontSize: fontSize.base },
  cancelBtn: { paddingVertical: 10, alignItems: 'center' },
  cancelBtnText: { color: colors.textMuted, fontWeight: '600', fontSize: fontSize.base },
});
