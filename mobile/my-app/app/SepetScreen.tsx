import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Keyboard,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';

import HeaderBar from '../components/HeaderBar';
import {
  cardStyle,
  colors,
  fontSize,
  radius,
  spacing,
} from '../constants/theme';
import { useCart } from '../services/CartContext';
import {
  Product,
  searchProducts,
} from '../services/productService';

export default function SepetScreen({
  navigation,
}: any) {
  const {
    customerName,
    items,
    removeItem,
    netTutar,
    genelToplam,
  } = useCart();

  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const [dropdownOpen, setDropdownOpen] = useState(false);

  const handleSearch = useCallback(
    async (text: string) => {
      setQuery(text);

      const searchText = text.trim();

      if (searchText.length < 2) {
        setResults([]);
        setDropdownOpen(false);
        return;
      }

      setLoading(true);
      setDropdownOpen(true);

      try {
        const products = await searchProducts(searchText);

        setResults(
          Array.isArray(products) ? products : []
        );
      } catch (error) {
        console.error(
          'Ürün arama hatası:',
          error
        );

        setResults([]);
      } finally {
        setLoading(false);
      }
    },
    []
  );

  const closeDropdown = () => {
    setDropdownOpen(false);
    Keyboard.dismiss();
  };

  const handleProductPress = (productId: number) => {
    closeDropdown();

    navigation.navigate('UrunDetay', {
      productId,
    });
  };

  return (
    <View style={styles.container}>
      <HeaderBar
        title="Sepet Sayfası"
        subtitle={
          'Cari İsmi: ' +
          (customerName || '')
        }
      />

      <View style={styles.searchWrap}>
        <TextInput
          style={styles.searchInput}
          placeholder="Ürün kodu veya ismi ara..."
          placeholderTextColor={colors.textMuted}
          value={query}
          onChangeText={handleSearch}
          onFocus={() => {
            if (query.trim().length >= 2) {
              setDropdownOpen(true);
            }
          }}
        />

        {dropdownOpen && (
          <View style={styles.dropdown}>
            {loading ? (
              <View style={styles.loadingContainer}>
                <ActivityIndicator
                  color={colors.primary}
                />

                <Text style={styles.loadingText}>
                  Ürünler aranıyor...
                </Text>
              </View>
            ) : (
              <FlatList
                data={results}
                keyExtractor={(item) =>
                  String(item.id)
                }
                keyboardShouldPersistTaps="handled"
                style={styles.resultsList}
                renderItem={({ item }) => (
                  <TouchableOpacity
                    style={styles.dropdownItem}
                    activeOpacity={0.7}
                    onPress={() =>
                      handleProductPress(item.id)
                    }
                  >
                    <View style={styles.resultInfo}>
                      <Text
                        style={styles.resultName}
                        numberOfLines={1}
                      >
                        {item.urun_adi}
                      </Text>

                      <Text style={styles.resultCode}>
                        Kod: {item.urun_kodu} • Fiyat:{' '}
                        {item.koli_fiyati} ₺
                      </Text>
                    </View>

                    <View style={styles.stockPill}>
                      <Text style={styles.stockText}>
                        {item.stok} adet
                      </Text>
                    </View>
                  </TouchableOpacity>
                )}
                ListEmptyComponent={
                  <Text style={styles.dropdownEmpty}>
                    Sonuç bulunamadı.
                  </Text>
                }
              />
            )}
          </View>
        )}
      </View>

      {dropdownOpen && (
        <Pressable
          style={styles.overlay}
          onPress={closeDropdown}
        />
      )}

      <View style={styles.cartHeader}>
        <Text style={styles.sectionTitle}>
          Alışveriş Sepeti ({items.length} Ürün)
        </Text>
      </View>

      {items.length === 0 ? (
        <View style={styles.emptyState}>
          <Text style={styles.emptyIcon}>
            🛒
          </Text>

          <Text style={styles.emptyTitle}>
            Alışveriş Sepeti Boş
          </Text>

          <Text style={styles.emptyText}>
            Sepetinizde ürün bulunmamaktadır.
            {'\n'}
            Yukarıdaki arama kutusundan
            sepetinize hızlıca ürün ekleyin.
          </Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) =>
            String(item.product_id)
          }
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.cartList}
          renderItem={({ item }) => (
            <View style={styles.cartItemCard}>
              <View style={styles.cartItemInfo}>
                <Text
                  style={styles.cartItemName}
                  numberOfLines={1}
                >
                  {item.urun_adi}
                </Text>

                <Text style={styles.cartItemMeta}>
                  {item.koli_fiyati} ₺ •{' '}
                  {item.koli_adedi} koli
                </Text>

                <Text style={styles.cartItemMeta}>
                  Net Tutar:{' '}
                  {netTutar(item).toFixed(2)} ₺
                </Text>

                <Text style={styles.cartItemCode}>
                  Ürün Kodu: {item.urun_kodu}
                </Text>
              </View>

              <TouchableOpacity
                onPress={() =>
                  removeItem(item.product_id)
                }
                hitSlop={8}
                activeOpacity={0.7}
              >
                <Text style={styles.deleteText}>
                  Sil
                </Text>
              </TouchableOpacity>
            </View>
          )}
        />
      )}

      {items.length > 0 && (
        <View style={styles.summaryFooter}>
          <View style={styles.summaryInfo}>
            <Text style={styles.summaryLabel}>
              Genel Toplam
            </Text>

            <Text style={styles.summaryValue}>
              {genelToplam().toFixed(2)} ₺
            </Text>
          </View>

          <TouchableOpacity
            style={styles.continueBtn}
            activeOpacity={0.8}
            onPress={() =>
              navigation.navigate(
                'SiparisFormu'
              )
            }
          >
            <Text style={styles.continueBtnText}>
              Devam Et
            </Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },

  searchWrap: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
    zIndex: 20,
  },

  searchInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    backgroundColor: colors.card,
    fontSize: fontSize.base,
    color: colors.text,
  },

  dropdown: {
    ...cardStyle,
    position: 'absolute',
    top: 56,
    left: spacing.md,
    right: spacing.md,
    zIndex: 30,
    overflow: 'hidden',
  },

  resultsList: {
    maxHeight: 320,
  },

  loadingContainer: {
    padding: spacing.lg,
    alignItems: 'center',
    gap: spacing.sm,
  },

  loadingText: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
  },

  dropdownItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },

  resultInfo: {
    flex: 1,
    marginRight: spacing.sm,
  },

  resultName: {
    fontSize: fontSize.base,
    fontWeight: '600',
    color: colors.text,
  },

  resultCode: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 3,
  },

  dropdownEmpty: {
    padding: spacing.md,
    color: colors.textMuted,
    fontSize: fontSize.base,
    textAlign: 'center',
  },

  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    zIndex: 10,
  },

  stockPill: {
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    paddingHorizontal: 8,
    paddingVertical: 4,
  },

  stockText: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
  },

  cartHeader: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.lg,
    paddingBottom: spacing.xs,
  },

  sectionTitle: {
    fontSize: fontSize.md,
    fontWeight: '700',
    color: colors.text,
  },

  emptyState: {
    alignItems: 'center',
    marginTop: spacing.xl * 1.5,
    paddingHorizontal: spacing.lg,
  },

  emptyIcon: {
    fontSize: 42,
    marginBottom: spacing.sm,
  },

  emptyTitle: {
    fontSize: fontSize.lg,
    fontWeight: '700',
    color: colors.text,
    marginBottom: spacing.sm,
  },

  emptyText: {
    fontSize: fontSize.base,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 22,
  },

  cartList: {
    padding: spacing.md,
    paddingBottom: 120,
  },

  cartItemCard: {
    ...cardStyle,
    padding: spacing.md,
    marginBottom: spacing.sm,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },

  cartItemInfo: {
    flex: 1,
    marginRight: spacing.sm,
  },

  cartItemName: {
    fontSize: fontSize.base,
    fontWeight: '600',
    color: colors.text,
  },

  cartItemMeta: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    marginTop: 3,
  },

  cartItemCode: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 5,
  },

  deleteText: {
    color: colors.danger,
    fontWeight: '600',
    fontSize: fontSize.base,
  },

  summaryFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.card,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    padding: spacing.md,
    paddingBottom: spacing.lg,
  },

  summaryInfo: {
    flex: 1,
  },

  summaryLabel: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    fontWeight: '600',
  },

  summaryValue: {
    fontSize: fontSize.xl,
    color: colors.text,
    fontWeight: '800',
    marginTop: 2,
  },

  continueBtn: {
    backgroundColor: colors.buttonPrimary,
    borderRadius: radius.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
    marginLeft: spacing.md,
  },

  continueBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: fontSize.base,
  },
});