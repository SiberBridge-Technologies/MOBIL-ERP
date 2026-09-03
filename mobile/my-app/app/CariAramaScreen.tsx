import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  TextInput,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { searchCustomers, Customer } from '../services/customerService';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "cari-arama-bos 2" (boş durum) ve "cari-arama-sonuc 3" (sonuç listesi)
export default function CariAramaScreen({ navigation }: any) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [loadedOnce, setLoadedOnce] = useState(false);

  const load = useCallback(async (text: string, isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    try {
      // Boş arama metni de geçerlidir: bu durumda backend, kullanıcının
      // yetkili olduğu TÜM carileri döner (arama yapmaya gerek kalmadan).
      const data = await searchCustomers(text);
      setResults(data);
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadedOnce(true);
    }
  }, []);

  // Ekran her odaklandığında (örn. bir cari eklendikten/atandıktan sonra bu
  // sekmeye geri dönüldüğünde) liste otomatik olarak yeniden çekilir —
  // böylece veriler her zaman güncel kalır.
  useFocusEffect(
    useCallback(() => {
      load(query);
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])
  );

  const handleSearch = (text: string) => {
    setQuery(text);
    load(text);
  };

  const handleRefresh = () => {
    load(query, true);
  };

  return (
    <View style={styles.container}>
      <HeaderBar title="Cari Arama Sayfası" />

      <View style={styles.searchBox}>
        <TextInput
          style={styles.searchInput}
          placeholder="Cari Ara (boş bırakırsan tüm carilerin listelenir)"
          value={query}
          onChangeText={handleSearch}
        />
      </View>

      {loading && !refreshing && (
        <ActivityIndicator style={{ marginTop: spacing.lg }} color={colors.primary} />
      )}

      {!loading && results.length > 0 && (
        <Text style={styles.resultCount}>
          {query ? `ARAMA SONUÇLARI (${results.length})` : `CARİLERİM (${results.length})`}
        </Text>
      )}

      {!loading && (
        <FlatList
          contentContainerStyle={{ padding: spacing.md, flexGrow: 1 }}
          data={results}
          keyExtractor={(item) => String(item.id)}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
          }
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.resultCard}
              onPress={() => navigation.navigate('CariSayfasi', { customerId: item.id })}
            >
              <View style={styles.cardLeft}>
                <Text style={styles.firmaAdi} numberOfLines={1}>{item.firma_adi}</Text>
                {(item.sehir || item.ilce) && (
                  <Text style={styles.location} numberOfLines={1}>
                    {[item.sehir, item.ilce].filter(Boolean).join('/')}
                  </Text>
                )}
              </View>
              <View style={styles.statusPill}>
                <Text style={styles.statusText}>Uygun</Text>
              </View>
            </TouchableOpacity>
          )}
          ListEmptyComponent={
            loadedOnce ? (
              <View style={styles.emptyState}>
                <Text style={styles.emptyTitle}>Cari Kayıt Bulunmuyor</Text>
                <Text style={styles.emptyText}>
                  {query
                    ? 'Farklı bir arama terimi deneyin veya cari kodunu kontrol edin.'
                    : 'Henüz size atanmış bir cari yok. Yöneticinizden sizi bir cariye atamasını isteyin, sonra bu sayfayı aşağı çekerek yenileyin.'}
                </Text>
              </View>
            ) : null
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  searchBox: {
    margin: spacing.md,
    backgroundColor: colors.card,
    borderRadius: 12,
    paddingHorizontal: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  searchInput: { paddingVertical: 14, fontSize: 15 },
  resultCount: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textMuted,
    marginLeft: spacing.md,
  },
  resultCard: {
    backgroundColor: colors.card,
    borderRadius: 12,
    padding: spacing.md,
    marginBottom: spacing.sm,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  cardLeft: { flex: 1, marginRight: spacing.sm },
  firmaAdi: { fontSize: 15, fontWeight: '600', color: colors.text },
  location: { fontSize: 12, color: colors.textMuted, marginTop: 4 },
  statusPill: {
    backgroundColor: colors.successSoft,
    borderRadius: 20,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  statusText: { color: colors.success, fontSize: 12, fontWeight: '600' },
  emptyState: { alignItems: 'center', marginTop: spacing.xl * 2, paddingHorizontal: spacing.lg },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
  emptyText: { fontSize: 13, color: colors.textMuted, textAlign: 'center' },
});
