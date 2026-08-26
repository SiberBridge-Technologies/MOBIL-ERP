import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  TextInput,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { searchCustomers, Customer } from '../services/customerService';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Figma: "cari-arama-bos 2" (boş durum) ve "cari-arama-sonuc 3" (sonuç listesi)
export default function CariAramaScreen({ navigation }: any) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);

  const handleSearch = useCallback(async (text: string) => {
    setQuery(text);
    if (text.trim().length < 2) {
      setResults([]);
      setSearched(false);
      return;
    }
    setLoading(true);
    setSearched(true);
    try {
      const data = await searchCustomers(text);
      setResults(data);
    } finally {
      setLoading(false);
    }
  }, []);

  return (
    <View style={styles.container}>
      <HeaderBar title="Cari Arama Sayfası" />

      <View style={styles.searchBox}>
        <TextInput
          style={styles.searchInput}
          placeholder="Cari Ara"
          value={query}
          onChangeText={handleSearch}
        />
      </View>

      {loading && <ActivityIndicator style={{ marginTop: spacing.lg }} color={colors.primary} />}

      {!loading && searched && results.length > 0 && (
        <Text style={styles.resultCount}>ARAMA SONUÇLARI ({results.length})</Text>
      )}

      {!loading && (
        <FlatList
          contentContainerStyle={{ padding: spacing.md }}
          data={results}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.resultCard}
              onPress={() => navigation.navigate('CariSayfasi', { customerId: item.id })}
            >
              <View style={styles.cardLeft}>
                <Text style={styles.firmaAdi}>{item.firma_adi}</Text>
                {(item.sehir || item.ilce) && (
                  <Text style={styles.location}>
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
            !loading && searched ? (
              <View style={styles.emptyState}>
                <Text style={styles.emptyTitle}>Cari Kayıt Bulunmuyor</Text>
                <Text style={styles.emptyText}>
                  Farklı bir arama terimi deneyin veya cari kodunu kontrol edin.
                </Text>
              </View>
            ) : !loading ? (
              <View style={styles.emptyState}>
                <Text style={styles.emptyTitle}>Cari Kayıt Bulunmuyor</Text>
                <Text style={styles.emptyText}>
                  Sipariş veya fatura işlemleri gerçekleştirmek üzere yukarıdaki arama kutusunu
                  kullanarak bir Cari kaydı arayın.
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
  cardLeft: { flex: 1 },
  firmaAdi: { fontSize: 15, fontWeight: '600', color: colors.text },
  location: { fontSize: 12, color: colors.textMuted, marginTop: 4 },
  statusPill: {
    backgroundColor: '#DCFCE7',
    borderRadius: 20,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  statusText: { color: colors.success, fontSize: 12, fontWeight: '600' },
  emptyState: { alignItems: 'center', marginTop: spacing.xl * 2, paddingHorizontal: spacing.lg },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: colors.text, marginBottom: spacing.sm },
  emptyText: { fontSize: 13, color: colors.textMuted, textAlign: 'center' },
});
