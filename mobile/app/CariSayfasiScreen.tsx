import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { getCustomer, Customer, CustomerOrder } from '../services/customerService';
import { useCart } from '../services/CartContext';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "cari-sayfasi 4" — Eski Siparişler (Son 15) + Yeni Sipariş Oluştur CTA
export default function CariSayfasiScreen({ route, navigation }: any) {
  const { customerId } = route.params;
  const { setCustomer } = useCart();

  const [customer, setLocalCustomer] = useState<Customer | null>(null);
  const [orders, setOrders] = useState<CustomerOrder[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const res = await getCustomer(customerId);
        setLocalCustomer(res.data);
        setOrders(res.son_siparisler);
      } finally {
        setLoading(false);
      }
    })();
  }, [customerId]);

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

      <View style={styles.body}>
        <View style={styles.leftColumn}>
          <Text style={styles.sectionTitle}>Eski Siparişler (Son 15 Sipariş)</Text>
          <FlatList
            data={orders}
            keyExtractor={(item) => String(item.id)}
            renderItem={({ item }) => (
              <View style={styles.orderCard}>
                <View style={styles.orderTop}>
                  <View>
                    <Text style={styles.orderDesc}>
                      Açıklama: {item.evrak_aciklamasi ?? '-'}
                    </Text>
                    <Text style={styles.orderMeta}>
                      Oluşturulma Tarihi: {formatDate(item.olusturulma_tarihi)}
                    </Text>
                  </View>
                  <TouchableOpacity style={styles.summaryBtn}>
                    <Text style={styles.summaryBtnText}>Özet Çıkar</Text>
                  </TouchableOpacity>
                </View>
                <View style={styles.orderBottom}>
                  <Text style={styles.orderMeta}>
                    Teslim Tarihi: {item.teslim_tarihi ? formatDate(item.teslim_tarihi) : '-'}
                  </Text>
                  <Text style={styles.orderTotal}>
                    {Number(item.genel_toplam).toLocaleString('tr-TR', {
                      minimumFractionDigits: 2,
                    })}{' '}
                    ₺
                  </Text>
                </View>
              </View>
            )}
            ListEmptyComponent={
              <Text style={styles.emptyText}>Bu cariye ait sipariş bulunmuyor.</Text>
            }
          />
        </View>

        <View style={styles.rightColumn}>
          <View style={styles.ctaCard}>
            <View style={styles.ctaIcon} />
            <Text style={styles.ctaTitle}>Yeni Sipariş Oluştur</Text>
            <TouchableOpacity style={styles.ctaButton} onPress={handleYeniSiparis}>
              <Text style={styles.ctaButtonText}>Yeni Sipariş Başlat</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
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
  body: { flex: 1, padding: spacing.md },
  leftColumn: { flex: 1 },
  sectionTitle: { fontSize: 15, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
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
  summaryBtn: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    paddingHorizontal: spacing.sm,
    paddingVertical: 6,
  },
  summaryBtnText: { fontSize: 12, color: colors.primary, fontWeight: '600' },
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
  rightColumn: { width: 200, marginLeft: spacing.md },
  ctaCard: {
    backgroundColor: colors.card,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    padding: spacing.lg,
  },
  ctaIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#E8F0FE',
    marginBottom: spacing.md,
  },
  ctaTitle: { fontSize: 15, fontWeight: '700', color: colors.text, marginBottom: spacing.md, textAlign: 'center' },
  ctaButton: {
    backgroundColor: colors.primary,
    borderRadius: 10,
    paddingVertical: 12,
    paddingHorizontal: spacing.md,
    width: '100%',
    alignItems: 'center',
  },
  ctaButtonText: { color: '#fff', fontWeight: '600', fontSize: 13 },
});
