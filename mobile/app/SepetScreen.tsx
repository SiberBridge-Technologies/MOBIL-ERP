import React, { useState, useCallback } from 'react';
import { View, Text, TextInput, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { searchProducts, Product } from '../services/productService';
import { useCart } from '../services/CartContext';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "sepet-bos 5" (boş) ve "Sepet-Sayfasi-Screen 7" (ürünlü) — birleştirilmiş tek ekran
export default function SepetScreen({ navigation }: any) {
  const { customerName, items, removeItem, netTutar, genelToplam } = useCart();
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);

  const handleSearch = useCallback(async (text: string) => {
    setQuery(text);
    if (text.trim().length < 2) {
      setResults([]);
      return;
    }
    setLoading(true);
    try {
      const data = await searchProducts(text);
      setResults(data);
    } finally {
      setLoading(false);
    }
  }, []);

  return (
    <View style={styles.container}>
      <HeaderBar title="Sepet Sayfası" subtitle={`Cari İsmi: ${customerName ?? ''}`} />

      <View style={styles.body}>
        <View style={styles.leftColumn}>
          <Text style={styles.sectionTitle}>Ürün Arama</Text>
          <TextInput
            style={styles.searchInput}
            placeholder="Aramak istediğiniz ürün kodunu veya ismini girin..."
            value={query}
            onChangeText={handleSearch}
          />
          {loading && <ActivityIndicator style={{ marginTop: spacing.md }} color={colors.primary} />}
          <FlatList
            style={{ marginTop: spacing.sm }}
            data={results}
            keyExtractor={(item) => String(item.id)}
            renderItem={({ item }) => (
              <TouchableOpacity
                style={styles.resultCard}
                onPress={() => navigation.navigate('UrunDetay', { productId: item.id })}
              >
                <Text style={styles.resultCode}>Ürün Kodu: {item.urun_kodu}</Text>
                <Text style={styles.resultName}>{item.urun_adi}</Text>
                <View style={styles.resultBottom}>
                  <Text style={styles.resultPrice}>B. Fiyat: {item.koli_fiyati} ₺</Text>
                  <View style={styles.stockPill}>
                    <Text style={styles.stockText}>ENV. MİK: {item.stok}</Text>
                  </View>
                </View>
              </TouchableOpacity>
            )}
          />
        </View>

        <View style={styles.rightColumn}>
          <View style={styles.cartHeader}>
            <Text style={styles.sectionTitle}>Alışveriş Sepeti ({items.length} Ürün)</Text>
            {items.length > 0 && (
              <TouchableOpacity
                style={styles.continueBtn}
                onPress={() => navigation.navigate('SiparisFormu')}
              >
                <Text style={styles.continueBtnText}>Devam Et</Text>
              </TouchableOpacity>
            )}
          </View>

          {items.length === 0 ? (
            <View style={styles.emptyState}>
              <Text style={styles.emptyTitle}>Alışveriş Sepeti Boş</Text>
              <Text style={styles.emptyText}>
                Sepetinizde ürün bulunmamaktadır. Sol taraftaki arama alanından sepetinize hızlıca
                ürün ekleyin.
              </Text>
            </View>
          ) : (
            <>
              <FlatList
                data={items}
                keyExtractor={(item) => String(item.product_id)}
                renderItem={({ item }) => (
                  <View style={styles.cartItemCard}>
                    <View style={styles.cartItemInfo}>
                      <Text style={styles.cartItemName}>{item.urun_adi}</Text>
                      <Text style={styles.cartItemMeta}>
                        {item.koli_fiyati} ₺ ({item.koli_adedi} Koli Sipariş)
                      </Text>
                      <Text style={styles.cartItemMeta}>
                        Net Tutar: {netTutar(item).toFixed(2)} ₺ (isk1+isk2+isk3 uygulanmıştır)
                      </Text>
                      <Text style={styles.cartItemCode}>Ürün Kodu: {item.urun_kodu}</Text>
                    </View>
                    <TouchableOpacity onPress={() => removeItem(item.product_id)}>
                      <Text style={styles.deleteText}>Sil</Text>
                    </TouchableOpacity>
                  </View>
                )}
              />
              <View style={styles.summaryFooter}>
                <Text style={styles.summaryLabel}>Genel Toplam (KDV Dahil)</Text>
                <Text style={styles.summaryValue}>{genelToplam().toFixed(2)} ₺</Text>
              </View>
            </>
          )}
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  body: { flex: 1, flexDirection: 'row', padding: spacing.md, gap: spacing.md },
  leftColumn: { flex: 1 },
  rightColumn: { flex: 1.6 },
  sectionTitle: { fontSize: 15, fontWeight: '700', color: colors.text },
  searchInput: {
    marginTop: spacing.sm,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    backgroundColor: colors.card,
  },
  resultCard: {
    backgroundColor: colors.card,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.sm,
    marginBottom: spacing.sm,
  },
  resultCode: { fontSize: 11, color: colors.textMuted },
  resultName: { fontSize: 14, fontWeight: '600', color: colors.text, marginVertical: 4 },
  resultBottom: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 },
  resultPrice: { fontSize: 12, color: colors.text },
  stockPill: { backgroundColor: colors.background, borderRadius: 8, paddingHorizontal: 8, paddingVertical: 2 },
  stockText: { fontSize: 10, color: colors.textMuted },
  cartHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.sm },
  continueBtn: { backgroundColor: colors.primary, borderRadius: 8, paddingHorizontal: spacing.md, paddingVertical: 8 },
  continueBtnText: { color: '#fff', fontWeight: '600', fontSize: 13 },
  emptyState: { alignItems: 'center', marginTop: spacing.xl * 2, paddingHorizontal: spacing.lg },
  emptyTitle: { fontSize: 17, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
  emptyText: { fontSize: 13, color: colors.textMuted, textAlign: 'center' },
  cartItemCard: {
    backgroundColor: colors.card,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    marginBottom: spacing.sm,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  cartItemInfo: { flex: 1 },
  cartItemName: { fontSize: 14, fontWeight: '600', color: colors.text },
  cartItemMeta: { fontSize: 12, color: colors.textMuted, marginTop: 2 },
  cartItemCode: { fontSize: 11, color: colors.textMuted, marginTop: 4 },
  deleteText: { color: colors.danger, fontWeight: '600', fontSize: 13 },
  summaryFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  summaryLabel: { fontSize: 14, color: colors.text, fontWeight: '600' },
  summaryValue: { fontSize: 18, color: colors.primary, fontWeight: '700' },
});
