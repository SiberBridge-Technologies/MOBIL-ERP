import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { getCustomer, Customer, CustomerOrder } from '../services/customerService';
import { useCart } from '../services/CartContext';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "cari-sayfasi 4" — Eski Siparişler (Son 15) + Yeni Sipariş Oluştur CTA
// Not: Figma'daki iki sütunlu (yan yana) tasarım geniş ekranlar içindi;
// telefon ekranında CTA kartı en üste alınıp liste altına tek sütun halinde
// yerleştirildi (responsive).
export default function CariSayfasiScreen({ route, navigation }: any) {
  const { customerId } = route.params;
  const { setCustomer } = useCart();

  const [customer, setLocalCustomer] = useState<Customer | null>(null);
  const [orders, setOrders] = useState<CustomerOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    try {
      const res = await getCustomer(customerId);
      setLocalCustomer(res.data);
      setOrders(res.son_siparisler);
    } catch (e: any) {
      Alert.alert('Cari yüklenemedi', e.message);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [customerId]);

  // Sipariş oluşturulup bu sayfaya geri dönüldüğünde liste otomatik
  // güncellensin diye, ekran her odaklandığında veri yeniden çekilir.
  useFocusEffect(
    useCallback(() => {
      load();
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [customerId])
  );

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  const handleYeniSiparis = () => {
    if (customer) {
      setCustomer(customer.id, customer.firma_adi);
      navigation.navigate('Sepet');
    }
  };

  return (
    <View style={styles.container}>
      <HeaderBar title="Cari Sayfası" subtitle={`Cari İsmi: ${customer?.firma_adi ?? ''}`} />

      <FlatList
        contentContainerStyle={styles.body}
        data={orders}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} colors={[colors.primary]} />
        }
        ListHeaderComponent={
          <>
            <TouchableOpacity style={styles.ctaCard} onPress={handleYeniSiparis}>
              <View style={styles.ctaIcon} />
              <View style={{ flex: 1 }}>
                <Text style={styles.ctaTitle}>Yeni Sipariş Oluştur</Text>
                <Text style={styles.ctaSubtitle}>Bu cari için sepete ürün eklemeye başla</Text>
              </View>
              <Text style={styles.ctaArrow}>›</Text>
            </TouchableOpacity>

            <Text style={styles.sectionTitle}>Eski Siparişler (Son 15 Sipariş)</Text>
          </>
        }
        renderItem={({ item }) => (
          <View style={styles.orderCard}>
            <View style={styles.orderTop}>
              <View style={{ flex: 1 }}>
                <Text style={styles.orderDesc} numberOfLines={1}>
                  Açıklama: {item.evrak_aciklamasi ?? '-'}
                </Text>
                <Text style={styles.orderMeta}>
                  Oluşturulma Tarihi: {formatDate(item.olusturulma_tarihi)}
                </Text>
              </View>
            </View>
            <View style={styles.orderBottom}>
              <Text style={styles.orderMeta}>
                Teslim: {item.teslim_tarihi ? formatDate(item.teslim_tarihi) : '-'}
              </Text>
              <Text style={styles.orderTotal}>
                {Number(item.genel_toplam).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
              </Text>
            </View>
          </View>
        )}
        ListEmptyComponent={
          <Text style={styles.emptyText}>Bu cariye ait sipariş bulunmuyor.</Text>
        }
      />
    </View>
  );
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('tr-TR');
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  body: { padding: spacing.md, flexGrow: 1 },
  sectionTitle: { fontSize: 15, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
  ctaCard: {
    backgroundColor: colors.card,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    flexDirection: 'row',
    alignItems: 'center',
    padding: spacing.md,
    marginBottom: spacing.lg,
  },
  ctaIcon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: colors.primarySoft,
    marginRight: spacing.md,
  },
  ctaTitle: { fontSize: 15, fontWeight: '700', color: colors.text },
  ctaSubtitle: { fontSize: 12, color: colors.textMuted, marginTop: 2 },
  ctaArrow: { fontSize: 24, color: colors.primary, marginLeft: spacing.sm },
  orderCard: {
    backgroundColor: colors.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  orderTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  orderDesc: { fontSize: 14, fontWeight: '600', color: colors.text },
  orderMeta: { fontSize: 12, color: colors.textMuted, marginTop: 4 },
  orderBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  orderTotal: { fontSize: 15, fontWeight: '700', color: colors.text },
  emptyText: { color: colors.textMuted, textAlign: 'center', marginTop: spacing.lg },
});

import { Alert } from '../services/dialogs';
