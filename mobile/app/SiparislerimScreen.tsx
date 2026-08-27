import React, { useCallback, useState } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator, RefreshControl } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { getMyOrders, MyOrder } from '../services/orderService';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

const durumRenk: Record<string, string> = {
  TASLAK: colors.textMuted,
  BEKLEMEDE: colors.warning,
  ONAYLANDI: colors.success,
  TAMAMLANDI: colors.success,
  IPTAL: colors.danger,
};

export default function SiparislerimScreen() {
  const [orders, setOrders] = useState<MyOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const data = await getMyOrders();
      setOrders(data);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load])
  );

  return (
    <View style={styles.container}>
      <HeaderBar title="Siparişlerim" />

      {loading ? (
        <ActivityIndicator style={{ marginTop: spacing.xl }} color={colors.primary} />
      ) : (
        <FlatList
          contentContainerStyle={{ padding: spacing.md }}
          data={orders}
          keyExtractor={(item) => String(item.id)}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => {
                setRefreshing(true);
                load();
              }}
            />
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardTop}>
                <Text style={styles.siparisNo}>{item.siparis_no}</Text>
                <View style={[styles.pill, { backgroundColor: `${durumRenk[item.durum]}22` }]}>
                  <Text style={[styles.pillText, { color: durumRenk[item.durum] }]}>{item.durum}</Text>
                </View>
              </View>
              <Text style={styles.firma}>{item.firma_adi}</Text>
              <View style={styles.cardBottom}>
                <Text style={styles.tarih}>{new Date(item.olusturulma_tarihi).toLocaleDateString('tr-TR')}</Text>
                <Text style={styles.tutar}>
                  {Number(item.genel_toplam).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </Text>
              </View>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>Henüz oluşturduğunuz bir sipariş yok.</Text>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  card: {
    backgroundColor: colors.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  siparisNo: { fontSize: 13, fontWeight: '700', color: colors.text },
  pill: { borderRadius: 20, paddingHorizontal: spacing.sm, paddingVertical: 3 },
  pillText: { fontSize: 11, fontWeight: '700' },
  firma: { fontSize: 15, fontWeight: '600', color: colors.text, marginTop: 6 },
  cardBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  tarih: { fontSize: 12, color: colors.textMuted },
  tutar: { fontSize: 14, fontWeight: '700', color: colors.primary },
  emptyText: { textAlign: 'center', color: colors.textMuted, marginTop: spacing.xl },
});
