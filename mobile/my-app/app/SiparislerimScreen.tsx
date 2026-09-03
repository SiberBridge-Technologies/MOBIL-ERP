import {
  useCallback,
  useState,
} from 'react';

import {
  ActivityIndicator,
  Alert,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';

import { useFocusEffect } from '@react-navigation/native';

import {
  getMyOrders,
  MyOrder,
} from '../services/orderService';

import {
  downloadAndShareOrderPdf,
} from '../services/pdfService';

export default function SiparislerimScreen() {
  const [orders, setOrders] =
    useState<MyOrder[]>([]);

  const [loading, setLoading] =
    useState(true);

  const [refreshing, setRefreshing] =
    useState(false);

  const [pdfLoadingId, setPdfLoadingId] =
    useState<number | null>(null);

  const loadOrders = async () => {
    try {
      const data =
        await getMyOrders();

      setOrders(data);
    } catch (error: any) {
      console.error(
        '❌ Siparişler alınamadı:',
        error
      );

      Alert.alert(
        'Hata',
        error?.message ||
          'Siparişler yüklenirken bir hata oluştu.'
      );
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      loadOrders();
    }, [])
  );

  const handleRefresh = () => {
    setRefreshing(true);
    loadOrders();
  };

  const handlePdf = async (
    orderId: number,
    siparisNo: string
  ) => {
    if (pdfLoadingId !== null) {
      return;
    }

    try {
      setPdfLoadingId(orderId);

      await downloadAndShareOrderPdf(
        orderId,
        siparisNo
      );
    } catch (error: any) {
      console.error(
        '❌ PDF hatası:',
        error
      );

      Alert.alert(
        'PDF Hatası',
        error?.message ||
          'PDF alınırken bir hata oluştu.'
      );
    } finally {
      setPdfLoadingId(null);
    }
  };

  const formatMoney = (
    value: number
  ) => {
    return Number(value).toLocaleString(
      'tr-TR',
      {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }
    );
  };

  const formatDate = (
    value: string
  ) => {
    if (!value) {
      return '-';
    }

    const date =
      new Date(value);

    if (Number.isNaN(
      date.getTime()
    )) {
      return value;
    }

    return date.toLocaleDateString(
      'tr-TR'
    );
  };

  const renderOrder = ({
    item,
  }: {
    item: MyOrder;
  }) => {
    const isPdfLoading =
      pdfLoadingId === item.id;

    return (
      <View style={styles.card}>

        <View style={styles.cardTop}>

          <View
            style={styles.orderInfo}
          >
            <Text
              style={styles.orderNo}
            >
              {item.siparis_no}
            </Text>

            <Text
              style={styles.company}
              numberOfLines={2}
            >
              {item.firma_adi}
            </Text>
          </View>

          <View
            style={styles.statusBadge}
          >
            <Text
              style={styles.statusText}
            >
              {item.durum}
            </Text>
          </View>

        </View>

        <View
          style={styles.divider}
        />

        <View
          style={styles.infoRow}
        >

          <View>
            <Text
              style={styles.label}
            >
              Sisteme Giriş
            </Text>

            <Text
              style={styles.value}
            >
              {formatDate(
                item.olusturulma_tarihi
              )}
            </Text>
          </View>

          <View
            style={styles.totalContainer}
          >
            <Text
              style={styles.label}
            >
              Genel Toplam
            </Text>

            <Text
              style={styles.total}
            >
              {formatMoney(
                item.genel_toplam
              )}{' '}
              ₺
            </Text>
          </View>

        </View>

        <TouchableOpacity
          style={[
            styles.pdfButton,
            isPdfLoading &&
              styles.pdfButtonDisabled,
          ]}
          activeOpacity={0.8}
          disabled={isPdfLoading}
          onPress={() =>
            handlePdf(
              item.id,
              item.siparis_no
            )
          }
        >
          {isPdfLoading ? (
            <>
              <ActivityIndicator
                size="small"
                color="#FFFFFF"
              />

              <Text
                style={styles.pdfButtonText}
              >
                PDF Alınıyor...
              </Text>
            </>
          ) : (
            <>
              <Text
                style={styles.pdfIcon}
              >
                📄
              </Text>

              <Text
                style={styles.pdfButtonText}
              >
                PDF Çıktısı
              </Text>
            </>
          )}
        </TouchableOpacity>

      </View>
    );
  };

  if (loading) {
    return (
      <View style={styles.center}>

        <ActivityIndicator
          size="large"
        />

        <Text
          style={styles.loadingText}
        >
          Siparişler yükleniyor...
        </Text>

      </View>
    );
  }

  return (
    <View
      style={styles.container}
    >

      <View
        style={styles.header}
      >

        <View>
          <Text
            style={styles.title}
          >
            Siparişlerim
          </Text>

          <Text
            style={styles.subtitle}
          >
            Oluşturduğunuz siparişler
          </Text>
        </View>

        <View
          style={styles.countBadge}
        >
          <Text
            style={styles.countText}
          >
            {orders.length}
          </Text>
        </View>

      </View>

      <FlatList
        data={orders}
        keyExtractor={(item) =>
          String(item.id)
        }
        renderItem={renderOrder}
        contentContainerStyle={
          orders.length === 0
            ? styles.emptyContainer
            : styles.list
        }
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
          />
        }
        ListEmptyComponent={
          <View
            style={styles.empty}
          >
            <Text
              style={styles.emptyIcon}
            >
              📦
            </Text>

            <Text
              style={styles.emptyTitle}
            >
              Henüz sipariş yok
            </Text>

            <Text
              style={styles.emptyText}
            >
              Oluşturduğunuz siparişler
              burada görünecek.
            </Text>
          </View>
        }
      />

    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F6F7F9',
  },

  header: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 16,
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    justifyContent:
      'space-between',
    alignItems: 'center',
  },

  title: {
    fontSize: 25,
    fontWeight: '800',
    color: '#111827',
  },

  subtitle: {
    marginTop: 4,
    fontSize: 13,
    color: '#6B7280',
  },

  countBadge: {
    minWidth: 38,
    height: 38,
    paddingHorizontal: 10,
    borderRadius: 19,
    backgroundColor: '#111827',
    justifyContent: 'center',
    alignItems: 'center',
  },

  countText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },

  list: {
    padding: 16,
    paddingBottom: 30,
  },

  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 17,
    marginBottom: 14,

    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 3,
    },
    shadowOpacity: 0.06,
    shadowRadius: 10,

    elevation: 3,
  },

  cardTop: {
    flexDirection: 'row',
    justifyContent:
      'space-between',
    alignItems: 'flex-start',
  },

  orderInfo: {
    flex: 1,
    paddingRight: 10,
  },

  orderNo: {
    fontSize: 17,
    fontWeight: '800',
    color: '#111827',
  },

  company: {
    marginTop: 5,
    fontSize: 14,
    color: '#6B7280',
    fontWeight: '500',
  },

  statusBadge: {
    backgroundColor: '#F3F4F6',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 9,
  },

  statusText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#374151',
  },

  divider: {
    height: 1,
    backgroundColor: '#E5E7EB',
    marginVertical: 15,
  },

  infoRow: {
    flexDirection: 'row',
    justifyContent:
      'space-between',
    alignItems: 'flex-end',
  },

  label: {
    fontSize: 11,
    color: '#9CA3AF',
    marginBottom: 4,
    fontWeight: '600',
  },

  value: {
    fontSize: 13,
    color: '#374151',
    fontWeight: '600',
  },

  totalContainer: {
    alignItems: 'flex-end',
  },

  total: {
    fontSize: 18,
    color: '#111827',
    fontWeight: '800',
  },

  pdfButton: {
    marginTop: 16,
    height: 48,
    borderRadius: 13,
    backgroundColor: '#111827',
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 8,
  },

  pdfButtonDisabled: {
    opacity: 0.65,
  },

  pdfIcon: {
    fontSize: 17,
  },

  pdfButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },

  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F6F7F9',
  },

  loadingText: {
    marginTop: 12,
    color: '#6B7280',
    fontSize: 14,
  },

  emptyContainer: {
    flexGrow: 1,
    justifyContent: 'center',
  },

  empty: {
    alignItems: 'center',
    paddingHorizontal: 30,
  },

  emptyIcon: {
    fontSize: 45,
    marginBottom: 15,
  },

  emptyTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111827',
  },

  emptyText: {
    marginTop: 7,
    fontSize: 13,
    textAlign: 'center',
    color: '#6B7280',
    lineHeight: 20,
  },
});