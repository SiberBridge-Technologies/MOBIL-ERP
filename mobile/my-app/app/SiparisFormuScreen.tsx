import React, { useState, useRef } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useCart } from '../services/CartContext';
import { createOrder } from '../services/orderService';
import { colors, spacing, radius, fontSize, cardStyle } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';
import { normalizeDate } from '../services/orderDate';

// Figma: "Siparis-Formu-Screen 8" — Evrak Açıklaması, Teslim Tarihi, Ambar Bilgisi, Ödeme Tipi
export default function SiparisFormuScreen({ navigation }: any) {
  const { customerId, customerName, items, clearCart, vadeGun, genelToplam, refreshPrices } = useCart();

  const [evrakAciklamasi, setEvrakAciklamasi] = useState('');
  const [teslimTarihi, setTeslimTarihi] = useState('');
  const [ambarBilgisi, setAmbarBilgisi] = useState('');
  const [odemeTipi, setOdemeTipi] = useState<'NAKIT' | 'VADELI'>(vadeGun > 0 ? 'VADELI' : 'NAKIT');
  const [saving, setSaving] = useState(false);
  const [termDays, setTermDays] = useState(String(vadeGun || 30));
  const inFlight = useRef(false);
  const retry = useRef({signature:'', key:''});

  const handleSave = async () => {
    if (inFlight.current) return;
    if (!customerId || !items.length) { Alert.alert('Eksik bilgi', 'Cari seçip sepete ürün ekleyin.'); return; }
    const delivery = teslimTarihi.trim() ? normalizeDate(teslimTarihi) : undefined;
    const now = new Date(); const today = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
    if (teslimTarihi.trim() && (!delivery || delivery < today)) { Alert.alert('Geçersiz tarih', 'Geçerli ve geçmişte olmayan bir teslim tarihi girin.'); return; }
    if (odemeTipi === 'VADELI' && (!Number.isInteger(Number(termDays)) || Number(termDays) < 1 || Number(termDays) > 365)) { Alert.alert('Geçersiz vade', 'Vade 1–365 gün olmalıdır.'); return; }
    inFlight.current = true;
    setSaving(true);
    try {
      const payload = {
        customer_id: customerId,
        evrak_aciklamasi: evrakAciklamasi || undefined,
        teslim_tarihi: delivery || undefined,
        ambar_bilgisi: ambarBilgisi || undefined,
        odeme_tipi: odemeTipi,
        vade_gun: odemeTipi === "VADELI" ? Number(termDays) : undefined,
        expected_total: genelToplam(),
        items: items.map((i) => ({
          product_id: i.product_id,
          koli_adedi: i.koli_adedi,
          adet: i.adet,
          siparis_birimi: i.siparis_birimi,
          birim_miktari: i.birim_miktari,
          iskonto_1: i.iskonto_1,
          iskonto_2: i.iskonto_2,
          iskonto_3: i.iskonto_3,
        })),
      };
      const signature = JSON.stringify(payload);
      if (retry.current.signature !== signature) retry.current = {signature, key: Date.now().toString(36)+'_'+Math.random().toString(36).slice(2)+'_'+Math.random().toString(36).slice(2)};
      const result = await createOrder({...payload, request_id:retry.current.key});
      clearCart();
      Alert.alert('Sipariş Kaydedildi', `Sipariş No: ${result.siparis_no}`, [
        { text: 'Tamam', onPress: () => navigation.popToTop() },
      ]);
    } catch (e: any) {
      Alert.alert('Hata', e.message || 'Sipariş kaydedilemedi.');
    } finally {
      inFlight.current = false;
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

          {odemeTipi === 'VADELI' && <LabeledInput label="Vade (gün)" value={termDays} onChangeText={setTermDays} keyboardType="number-pad" />}
          <TouchableOpacity disabled={saving} onPress={async () => {try {await refreshPrices(); Alert.alert('Sepet güncellendi','Güncel toplamı kontrol edip siparişi kaydedin.');} catch(e:any){Alert.alert('Güncellenemedi',e.message);}}}><Text>Fiyatları ve Stoğu Güncelle</Text></TouchableOpacity>
          <Text>Genel Toplam (KDV dahil): {genelToplam().toFixed(2)} TL</Text>
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

import { Alert } from '../services/dialogs';
