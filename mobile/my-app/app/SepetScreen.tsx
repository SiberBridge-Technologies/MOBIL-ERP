import {
  useCallback,
  useEffect,
  useState,
} from 'react';

import {
  ActivityIndicator,
  FlatList,
  Keyboard,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  useWindowDimensions,
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
  getProducts,
  Product,
  searchProducts,
} from '../services/productService';


export default function SepetScreen({
  navigation,
}: any) {

  const {
    customerName,
    items,
    addItem,
    removeItem,
    netTutar,
    genelToplam,
  } = useCart();

  const { width } = useWindowDimensions();

  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);


  /*
   * Responsive yapı
   *
   * Telefon:
   * Ürünler %46
   * Sepet %54
   *
   * Tablet:
   * Ürünler %50
   * Sepet %50
   */

  const isTablet = width >= 600;

  const productWidth = isTablet
    ? '50%'
    : '46%';

  const cartWidth = isTablet
    ? '50%'
    : '54%';


  /*
   * EKRAN AÇILDIĞINDA ÜRÜNLERİ GETİR
   */

  const loadProducts = useCallback(
    async () => {

      setLoading(true);

      try {

        const products =
          await getProducts();

        setResults(
          Array.isArray(products)
            ? products
            : []
        );

      } catch (error) {

        console.error(
          'Ürünler yüklenemedi:',
          error
        );

        setResults([]);

      } finally {

        setLoading(false);

      }

    },
    []
  );


  useEffect(() => {

    loadProducts();

  }, [loadProducts]);


  /*
   * ÜRÜN ARAMA
   *
   * Arama kutusu boşsa tüm ürünler tekrar yüklenir.
   */

  const handleSearch = useCallback(
    async (text: string) => {

      setQuery(text);

      const searchText =
        text.trim();


      /*
       * Arama temizlendiyse
       * tüm ürünleri göster.
       */

      if (searchText.length === 0) {

        loadProducts();

        return;

      }


      setLoading(true);

      try {

        const products =
          await searchProducts(
            searchText
          );

        setResults(
          Array.isArray(products)
            ? products
            : []
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
    [loadProducts]
  );


  /*
   * ÜRÜNÜ SEPETE EKLE
   *
   * Ürün zaten sepetteyse
   * koli adedini 1 artırır.
   */

  const handleAddProduct = (
    product: Product
  ) => {

    const existingItem =
      items.find(
        (item) =>
          item.product_id ===
          product.id
      );


    const currentQuantity =
      existingItem?.koli_adedi ?? 0;


    addItem({

      product_id:
        product.id,

      urun_kodu:
        product.urun_kodu,

      urun_adi:
        product.urun_adi,

      koli_adedi:
        currentQuantity + 1,

      koli_fiyati:
        Number(
          product.koli_fiyati
        ),
        
dip_fiyat: Number(product.dip_fiyat),

      iskonto_1:
        existingItem?.iskonto_1 ?? 0,

      iskonto_2:
        existingItem?.iskonto_2 ?? 0,

      iskonto_3:
        existingItem?.iskonto_3 ?? 0,

    });

  };


  /*
   * SEPET MİKTAR ARTIR
   */

  const increaseQuantity = (
    productId: number
  ) => {

    const item =
      items.find(
        (i) =>
          i.product_id ===
          productId
      );

    if (!item) return;


    addItem({

      ...item,

      koli_adedi:
        item.koli_adedi + 1,

    });

  };


  /*
   * SEPET MİKTAR AZALT
   */

  const decreaseQuantity = (
    productId: number
  ) => {

    const item =
      items.find(
        (i) =>
          i.product_id ===
          productId
      );

    if (!item) return;


    /*
     * 1 kolinin altına düşerse
     * ürün tamamen silinir.
     */

    if (item.koli_adedi <= 1) {

      removeItem(productId);

      return;

    }


    addItem({

      ...item,

      koli_adedi:
        item.koli_adedi - 1,

    });

  };


  /*
   * ÜRÜN DETAY
   */

  const handleProductDetail = (
    productId: number
  ) => {

    Keyboard.dismiss();

    navigation.navigate(
      'UrunDetay',
      {
        productId,
      }
    );

  };


  /*
   * TOPLAM KOLİ
   */

  const totalQuantity =
    items.reduce(
      (total, item) =>
        total + item.koli_adedi,
      0
    );


  return (

    <View style={styles.container}>

      {/* HEADER */}

      <HeaderBar
        title="Sipariş Sepeti"
        subtitle={
          'Cari İsmi: ' +
          (customerName || '')
        }
      />


      {/* =====================================================
          ANA İKİ PANEL
          ===================================================== */}

      <View style={styles.mainContent}>


        {/* =================================================
            SOL PANEL - ÜRÜNLER
            ================================================= */}

        <View
          style={[
            styles.productsPanel,
            {
              width:
                productWidth,
            },
          ]}
        >


          {/* ÜRÜN BAŞLIK */}

          <View
            style={
              styles.panelHeader
            }
          >

            <View
              style={
                styles.panelTitleRow
              }
            >

              <Text
                style={
                  styles.panelTitle
                }
              >
                📦 Ürünler
              </Text>

              <View
                style={
                  styles.productCountBadge
                }
              >

                <Text
                  style={
                    styles.productCountText
                  }
                >
                  {results.length}
                </Text>

              </View>

            </View>


            {/* ARAMA */}

            <TextInput
              style={
                styles.searchInput
              }
              placeholder="Ürün kodu veya ismi ara..."
              placeholderTextColor={
                colors.textMuted
              }
              value={query}
              onChangeText={
                handleSearch
              }
              autoCorrect={false}
              autoCapitalize="none"
            />

          </View>


          {/* ÜRÜN LİSTESİ */}

          <View
            style={
              styles.productListContainer
            }
          >

            {loading ? (

              <View
                style={
                  styles.centerState
                }
              >

                <ActivityIndicator
                  size="small"
                  color={
                    colors.primary
                  }
                />

                <Text
                  style={
                    styles.stateText
                  }
                >
                  Ürünler yükleniyor...
                </Text>

              </View>

            ) : results.length === 0 ? (

              <View
                style={
                  styles.centerState
                }
              >

                <Text
                  style={
                    styles.stateIcon
                  }
                >
                  📦
                </Text>

                <Text
                  style={
                    styles.stateTitle
                  }
                >
                  Ürün Bulunamadı
                </Text>

                <Text
                  style={
                    styles.stateText
                  }
                >
                  {query.trim().length > 0
                    ? 'Aramanızla eşleşen ürün bulunamadı.'
                    : 'Listelenecek ürün bulunamadı.'}
                </Text>

              </View>

            ) : (

              <FlatList
                data={results}
                keyExtractor={(
                  item
                ) =>
                  String(item.id)
                }
                keyboardShouldPersistTaps="handled"
                showsVerticalScrollIndicator={
                  false
                }
                contentContainerStyle={
                  styles.productList
                }

                renderItem={({
                  item,
                }) => {

                  const cartItem =
                    items.find(
                      (cartProduct) =>
                        cartProduct.product_id ===
                        item.id
                    );


                  const quantity =
                    cartItem?.koli_adedi ??
                    0;


                  return (

                    <View
                      style={
                        styles.productCard
                      }
                    >

                      {/* ÜRÜN BİLGİSİ */}

                      <TouchableOpacity
                        style={
                          styles.productInfo
                        }
                        activeOpacity={
                          0.7
                        }
                        onPress={() =>
                          handleProductDetail(
                            item.id
                          )
                        }
                      >

                        <Text
                          style={
                            styles.productName
                          }
                          numberOfLines={
                            2
                          }
                        >
                          {item.urun_adi}
                        </Text>


                        <Text
                          style={
                            styles.productCode
                          }
                          numberOfLines={
                            1
                          }
                        >
                          {item.urun_kodu}
                        </Text>


                        <Text
                          style={
                            styles.productPrice
                          }
                        >
                          {Number(
                            item.koli_fiyati
                          ).toFixed(2)}{' '}
                          ₺ / koli
                        </Text>


                        <Text
                          style={
                            styles.productStock
                          }
                        >
                          Stok: {item.stok}
                        </Text>

                      </TouchableOpacity>


                      {/* ÜRÜN EKLE */}

                      <View
                        style={
                          styles.productAction
                        }
                      >

                        {quantity > 0 && (

                          <View
                            style={
                              styles.addedBadge
                            }
                          >

                            <Text
                              style={
                                styles.addedBadgeText
                              }
                            >
                              {quantity}
                            </Text>

                          </View>

                        )}


                        <TouchableOpacity
                          style={
                            styles.addButton
                          }
                          activeOpacity={
                            0.8
                          }
                          onPress={() =>
                            handleAddProduct(
                              item
                            )
                          }
                        >

                          <Text
                            style={
                              styles.addButtonText
                            }
                          >
                            +
                          </Text>

                        </TouchableOpacity>

                      </View>

                    </View>

                  );

                }}
              />

            )}

          </View>

        </View>


        {/* =================================================
            SAĞ PANEL - SEPET
            ================================================= */}

        <View
          style={[
            styles.cartPanel,
            {
              width:
                cartWidth,
            },
          ]}
        >


          {/* SEPET BAŞLIK */}

          <View
            style={
              styles.cartHeader
            }
          >

            <View>

              <Text
                style={
                  styles.panelTitle
                }
              >
                🛒 Sepet
              </Text>

              <Text
                style={
                  styles.cartSubtitle
                }
              >
                {items.length}{' '}
                farklı ürün
              </Text>

            </View>


            {items.length > 0 && (

              <View
                style={
                  styles.cartCountBadge
                }
              >

                <Text
                  style={
                    styles.cartCountText
                  }
                >
                  {totalQuantity}{' '}
                  Koli
                </Text>

              </View>

            )}

          </View>


          {/* SEPET */}

          {items.length === 0 ? (

            <View
              style={
                styles.emptyCart
              }
            >

              <Text
                style={
                  styles.emptyCartIcon
                }
              >
                🛒
              </Text>

              <Text
                style={
                  styles.emptyCartTitle
                }
              >
                Sepet Boş
              </Text>

              <Text
                style={
                  styles.emptyCartText
                }
              >
                Sol taraftan ürün
                seçerek sepete
                ekleyebilirsiniz.
              </Text>

            </View>

          ) : (

            <FlatList
              data={items}
              keyExtractor={(
                item
              ) =>
                String(
                  item.product_id
                )
              }
              showsVerticalScrollIndicator={
                false
              }
              contentContainerStyle={
                styles.cartList
              }

              renderItem={({
                item,
              }) => (

                /*
                 * SEPET ÜRÜN KARTI
                 *
                 * Kartın ana alanına basıldığında
                 * Ürün Detay sayfasına gider.
                 */

                <TouchableOpacity
                  style={
                    styles.cartItemCard
                  }
                  activeOpacity={
                    0.75
                  }
                  onPress={() =>
                    handleProductDetail(
                      item.product_id
                    )
                  }
                >

                  {/* ÜRÜN */}

                  <View
                    style={
                      styles.cartItemInfo
                    }
                  >

                    <Text
                      style={
                        styles.cartItemName
                      }
                      numberOfLines={
                        2
                      }
                    >
                      {item.urun_adi}
                    </Text>


                    <Text
                      style={
                        styles.cartItemCode
                      }
                    >
                      {item.urun_kodu}
                    </Text>


                    <Text
                      style={
                        styles.cartItemPrice
                      }
                    >
                      {Number(
                        item.koli_fiyati
                      ).toFixed(2)}{' '}
                      ₺ / koli
                    </Text>


                    {(item.iskonto_1 > 0 ||
                      item.iskonto_2 > 0 ||
                      item.iskonto_3 > 0) && (

                      <Text
                        style={
                          styles.discountText
                        }
                      >
                        İskonto:{' '}
                        {item.iskonto_1}%

                        {item.iskonto_2 >
                        0
                          ? ` + ${item.iskonto_2}%`
                          : ''}

                        {item.iskonto_3 >
                        0
                          ? ` + ${item.iskonto_3}%`
                          : ''}

                      </Text>

                    )}

                  </View>


                  {/* SAĞ KONTROLLER */}

                  <View
                    style={
                      styles.cartItemRight
                    }
                  >


                    {/* ADET */}

                    <View
                      style={
                        styles.quantityControl
                      }
                    >

                      <TouchableOpacity
                        style={
                          styles.quantityButton
                        }
                        activeOpacity={
                          0.7
                        }
                        onPress={() =>
                          decreaseQuantity(
                            item.product_id
                          )
                        }
                      >

                        <Text
                          style={
                            styles.quantityButtonText
                          }
                        >
                          −
                        </Text>

                      </TouchableOpacity>


                      <Text
                        style={
                          styles.quantityValue
                        }
                      >
                        {item.koli_adedi}
                      </Text>


                      <TouchableOpacity
                        style={
                          styles.quantityButton
                        }
                        activeOpacity={
                          0.7
                        }
                        onPress={() =>
                          increaseQuantity(
                            item.product_id
                          )
                        }
                      >

                        <Text
                          style={
                            styles.quantityButtonText
                          }
                        >
                          +
                        </Text>

                      </TouchableOpacity>

                    </View>


                    {/* NET TUTAR */}

                    <Text
                      style={
                        styles.cartItemTotal
                      }
                    >
                      {netTutar(
                        item
                      ).toFixed(2)}{' '}
                      ₺
                    </Text>


                    {/* SİL */}

                    <TouchableOpacity
                      activeOpacity={
                        0.7
                      }
                      onPress={() =>
                        removeItem(
                          item.product_id
                        )
                      }
                    >

                      <Text
                        style={
                          styles.deleteText
                        }
                      >
                        Sil
                      </Text>

                    </TouchableOpacity>

                  </View>

                </TouchableOpacity>

              )}
            />

          )}


          {/* =================================================
              TOPLAM
              ================================================= */}

          {items.length > 0 && (

            <View
              style={
                styles.summaryFooter
              }
            >

              <View
                style={
                  styles.summaryRow
                }
              >

                <View>

                  <Text
                    style={
                      styles.summaryLabel
                    }
                  >
                    Genel Toplam
                  </Text>

                  <Text
                    style={
                      styles.summaryItemCount
                    }
                  >
                    {totalQuantity}{' '}
                    koli
                  </Text>

                </View>


                <Text
                  style={
                    styles.summaryValue
                  }
                >
                  {genelToplam().toFixed(
                    2
                  )}{' '}
                  ₺
                </Text>

              </View>


              <TouchableOpacity
                style={
                  styles.continueButton
                }
                activeOpacity={
                  0.8
                }
                onPress={() =>
                  navigation.navigate(
                    'SiparisFormu'
                  )
                }
              >

                <Text
                  style={
                    styles.continueButtonText
                  }
                >
                  Devam Et →
                </Text>

              </TouchableOpacity>

            </View>

          )}

        </View>

      </View>

    </View>

  );
}


/* =========================================================
   STYLES
   ========================================================= */

const styles = StyleSheet.create({

  container: {
    flex: 1,
    backgroundColor:
      colors.background,
  },


  /* ANA PANEL */

  mainContent: {
    flex: 1,
    flexDirection: 'row',
    minHeight: 0,
  },


  /* =====================================================
     ÜRÜNLER
     ===================================================== */

  productsPanel: {
    borderRightWidth: 1,
    borderRightColor:
      colors.border,
    backgroundColor:
      colors.background,
    minWidth: 0,
  },


  panelHeader: {
    paddingHorizontal:
      spacing.sm,
    paddingTop:
      spacing.md,
    paddingBottom:
      spacing.sm,
    backgroundColor:
      colors.card,
    borderBottomWidth: 1,
    borderBottomColor:
      colors.border,
  },


  panelTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom:
      spacing.sm,
  },


  panelTitle: {
    fontSize:
      fontSize.md,
    fontWeight: '800',
    color:
      colors.text,
  },


  productCountBadge: {
    marginLeft: 7,
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor:
      colors.buttonPrimary,
  },


  productCountText: {
    color: '#fff',
    fontSize: 9,
    fontWeight: '800',
  },


  searchInput: {
    height: 40,
    borderWidth: 1,
    borderColor:
      colors.border,
    borderRadius:
      radius.md,
    paddingHorizontal:
      spacing.sm,
    backgroundColor:
      colors.background,
    color:
      colors.text,
    fontSize:
      fontSize.xs,
  },


  productListContainer: {
    flex: 1,
    minHeight: 0,
  },


  productList: {
    padding:
      spacing.sm,
    paddingBottom:
      spacing.lg,
  },


  productCard: {
    ...cardStyle,
    flexDirection: 'row',
    alignItems: 'center',
    padding:
      spacing.sm,
    marginBottom:
      spacing.sm,
  },


  productInfo: {
    flex: 1,
    minWidth: 0,
    marginRight: 5,
  },


  productName: {
    fontSize:
      fontSize.sm,
    fontWeight: '700',
    color:
      colors.text,
    lineHeight: 17,
  },


  productCode: {
    fontSize: 9,
    color:
      colors.textMuted,
    marginTop: 3,
  },


  productPrice: {
    fontSize: 10,
    fontWeight: '700',
    color:
      colors.text,
    marginTop: 5,
  },


  productStock: {
    fontSize: 9,
    color:
      colors.textMuted,
    marginTop: 2,
  },


  productAction: {
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 3,
  },


  addButton: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor:
      colors.buttonPrimary,
    alignItems: 'center',
    justifyContent: 'center',
  },


  addButtonText: {
    color: '#fff',
    fontSize: 22,
    lineHeight: 25,
    fontWeight: '500',
  },


  addedBadge: {
    position: 'absolute',
    zIndex: 2,
    top: -7,
    right: -5,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    paddingHorizontal: 4,
    backgroundColor:
      colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },


  addedBadgeText: {
    color: '#fff',
    fontSize: 9,
    fontWeight: '800',
  },


  /* =====================================================
     DURUM
     ===================================================== */

  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal:
      spacing.md,
  },


  stateIcon: {
    fontSize: 30,
    marginBottom:
      spacing.sm,
  },


  stateTitle: {
    fontSize:
      fontSize.sm,
    fontWeight: '700',
    color:
      colors.text,
    textAlign: 'center',
    marginBottom: 4,
  },


  stateText: {
    fontSize:
      fontSize.xs,
    color:
      colors.textMuted,
    textAlign: 'center',
    lineHeight: 17,
  },


  /* =====================================================
     SEPET
     ===================================================== */

  cartPanel: {
    backgroundColor:
      colors.card,
    minWidth: 0,
  },


  cartHeader: {
    minHeight: 69,
    paddingHorizontal:
      spacing.sm,
    paddingVertical:
      spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor:
      colors.border,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent:
      'space-between',
  },


  cartSubtitle: {
    fontSize: 9,
    color:
      colors.textMuted,
    marginTop: 2,
  },


  cartCountBadge: {
    backgroundColor:
      colors.background,
    paddingHorizontal: 7,
    paddingVertical: 5,
    borderRadius: 8,
  },


  cartCountText: {
    fontSize: 9,
    fontWeight: '700',
    color:
      colors.textMuted,
  },


  cartList: {
    padding:
      spacing.sm,
    paddingBottom:
      spacing.md,
  },


  cartItemCard: {
    borderWidth: 1,
    borderColor:
      colors.border,
    borderRadius:
      radius.md,
    backgroundColor:
      colors.card,
    padding:
      spacing.sm,
    marginBottom:
      spacing.sm,
    flexDirection: 'row',
  },


  cartItemInfo: {
    flex: 1,
    minWidth: 0,
    paddingRight: 5,
  },


  cartItemName: {
    fontSize:
      fontSize.sm,
    fontWeight: '700',
    color:
      colors.text,
    lineHeight: 17,
  },


  cartItemCode: {
    fontSize: 9,
    color:
      colors.textMuted,
    marginTop: 3,
  },


  cartItemPrice: {
    fontSize: 10,
    color:
      colors.text,
    fontWeight: '600',
    marginTop: 5,
  },


  discountText: {
    fontSize: 8,
    color:
      colors.primary,
    fontWeight: '600',
    marginTop: 3,
  },


  cartItemRight: {
    alignItems: 'flex-end',
    justifyContent: 'center',
    minWidth: 66,
  },


  quantityControl: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 5,
  },


  quantityButton: {
    width: 24,
    height: 24,
    borderWidth: 1,
    borderColor:
      colors.border,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor:
      colors.background,
  },


  quantityButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color:
      colors.text,
    lineHeight: 18,
  },


  quantityValue: {
    minWidth: 25,
    textAlign: 'center',
    fontSize: 11,
    fontWeight: '800',
    color:
      colors.text,
  },


  cartItemTotal: {
    fontSize: 10,
    fontWeight: '800',
    color:
      colors.text,
    marginBottom: 4,
  },


  deleteText: {
    fontSize: 9,
    color:
      colors.danger,
    fontWeight: '700',
  },


  /* =====================================================
     BOŞ SEPET
     ===================================================== */

  emptyCart: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal:
      spacing.md,
  },


  emptyCartIcon: {
    fontSize: 38,
    marginBottom:
      spacing.sm,
  },


  emptyCartTitle: {
    fontSize:
      fontSize.md,
    fontWeight: '800',
    color:
      colors.text,
    marginBottom: 5,
  },


  emptyCartText: {
    fontSize:
      fontSize.xs,
    color:
      colors.textMuted,
    textAlign: 'center',
    lineHeight: 17,
  },


  /* =====================================================
     ALT TOPLAM
     ===================================================== */

  summaryFooter: {
    borderTopWidth: 1,
    borderTopColor:
      colors.border,
    backgroundColor:
      colors.card,
    padding:
      spacing.sm,
  },


  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent:
      'space-between',
    marginBottom:
      spacing.sm,
  },


  summaryLabel: {
    fontSize:
      fontSize.xs,
    color:
      colors.textMuted,
    fontWeight: '700',
  },


  summaryItemCount: {
    fontSize: 9,
    color:
      colors.textMuted,
    marginTop: 2,
  },


  summaryValue: {
    fontSize:
      fontSize.lg,
    fontWeight: '900',
    color:
      colors.text,
  },


  continueButton: {
    width: '100%',
    height: 42,
    borderRadius:
      radius.md,
    backgroundColor:
      colors.buttonPrimary,
    alignItems: 'center',
    justifyContent: 'center',
  },


  continueButtonText: {
    color: '#fff',
    fontSize:
      fontSize.sm,
    fontWeight: '800',
  },

});