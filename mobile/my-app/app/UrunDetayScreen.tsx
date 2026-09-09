import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';

import {
  getProduct,
  Product,
} from '../services/productService';

import { useCart } from '../services/CartContext';

import {
  cardStyle,
  colors,
  fontSize,
  radius,
  spacing,
} from '../constants/theme';

import HeaderBar from '../components/HeaderBar';

type SiparisBirimi = 'ADET' | 'KOLI' | 'STAND';

const VADE_SECENEKLERI = [
  { label: 'PEŞİN', value: '0' },
  { label: '30 Gün', value: '30' },
  { label: '60 Gün', value: '60' },
  { label: '90 Gün', value: '90' },
  { label: '120 Gün', value: '120' },
  { label: '150 Gün', value: '150' },
  { label: '180 Gün', value: '180' },
] as const;

const BIRIM_SECENEKLERI: {
  label: string;
  value: SiparisBirimi;
}[] = [
  { label: 'Adet', value: 'ADET' },
  { label: 'Koli', value: 'KOLI' },
];

const MIN_LOADING_MS = 0;

export default function UrunDetayScreen({
  route,
  navigation,
}: any) {
  const { productId } = route.params;
  const { addItem, items, vadeGun: cartTerm, setVadeGun: setCartTerm } = useCart();
  const existing = items.find(i => i.product_id === productId);

  const [product, setProduct] =
    useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [miktar, setMiktar] = useState(existing ? String(existing.birim_miktari) : '');
  const [siparisBirimi, setSiparisBirimi] =
    useState<SiparisBirimi>(existing?.siparis_birimi ?? 'KOLI');

  const [iskonto1, setIskonto1] = useState(existing ? String(existing.iskonto_1) : '');
  const [iskonto2, setIskonto2] = useState(existing ? String(existing.iskonto_2) : '');
  const [iskonto3, setIskonto3] = useState(existing ? String(existing.iskonto_3) : '');

  const [vadeGun, setVadeGun] = useState(String(cartTerm));
  const [vadeMenuAcik, setVadeMenuAcik] =
    useState(false);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      setLoading(true);
      const startedAt = Date.now();

      try {
        const p = await getProduct(productId);
        if (!cancelled) setProduct(p);
      } catch (error: any) {
        if (!cancelled) setLoadError(error.message || "Ürün yüklenemedi.");
        console.error(
          'Ürün detay yükleme hatası:',
          error
        );
      } finally {
        const elapsed = Date.now() - startedAt;
        const remaining = MIN_LOADING_MS - elapsed;
        if (remaining > 0) {
          await new Promise((r) =>
            setTimeout(r, remaining)
          );
        }
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [productId]);

  const hesap = useMemo(() => {
    if (!product) return null;

    const qty = Math.max(
      0,
      Number(miktar.replace(',', '.') || '0') || 0
    );

    const isk1 = Math.max(
      0,
      Math.min(100, Number(iskonto1.replace(',', '.') || '0') || 0)
    );
    const isk2 = Math.max(
      0,
      Math.min(100, Number(iskonto2.replace(',', '.') || '0') || 0)
    );
    const isk3 = Math.max(
      0,
      Math.min(100, Number(iskonto3.replace(',', '.') || '0') || 0)
    );

    const koliIciAdet =
      Number(product.koli_ici_adet) || 1;
    const standIciAdet =
      Number(product.stand_ici_adet) ||
      koliIciAdet;

    const stok = Number(product.stok) || 0;
    const adetFiyati = Number(product.liste_fiyati) || 0;
    const dipFiyat =
      Number(product.dip_fiyat) || 0;

    let adet = 0;
    let koliAdedi = 0;

    if (siparisBirimi === 'ADET') {
      adet = qty;
      koliAdedi =
        koliIciAdet > 0 ? qty / koliIciAdet : 0;
    } else if (siparisBirimi === 'KOLI') {
      koliAdedi = qty;
      adet = qty * koliIciAdet;
    } else {
      adet = qty * standIciAdet;
      koliAdedi =
        koliIciAdet > 0 ? adet / koliIciAdet : 0;
    }

    const netAdetFiyati =
      Math.round(
        (adetFiyati *
          (1 - isk1 / 100) *
          (1 - isk2 / 100) *
          (1 - isk3 / 100) +
          Number.EPSILON) *
          100
      ) / 100;

    const netKoliFiyati = Math.round((netAdetFiyati * koliIciAdet + Number.EPSILON) * 100) / 100;

    const netTutar =
      Math.round(
        (netAdetFiyati * adet + Number.EPSILON) *
          100
      ) / 100;

    const yuvarlanmisDipFiyat =
      Math.round(
        (dipFiyat + Number.EPSILON) * 100
      ) / 100;

    const dipFiyatUygun =
      netAdetFiyati >= yuvarlanmisDipFiyat;
    const stokUygun = adet <= stok;
    const miktarUygun = qty > 0 && Number.isInteger(qty) && Number.isInteger(adet);

    const girilebilir =
      dipFiyatUygun && stokUygun && miktarUygun;

    return {
      qty,
      adet,
      koliAdedi,
      isk1,
      isk2,
      isk3,
      netKoliFiyati,
      netAdetFiyati,
      netTutar,
      dipFiyat: yuvarlanmisDipFiyat,
      dipFiyatUygun,
      stokUygun,
      miktarUygun,
      girilebilir,
    };
  }, [
    product,
    miktar,
    siparisBirimi,
    iskonto1,
    iskonto2,
    iskonto3,
  ]);

  if (!loading && !product) return <View style={styles.center}><Text>{loadError || "Ürün bulunamadı."}</Text><TouchableOpacity onPress={() => navigation.goBack()}><Text>Geri dön</Text></TouchableOpacity></View>;

  if (loading || !product) {
    return (
      <View style={styles.center}>
        <View style={styles.loadingCard}>
          <ActivityIndicator
            color={colors.primary}
            size="large"
          />
          <Text style={styles.loadingTitle}>
            Ürün yükleniyor
          </Text>
          <Text style={styles.loadingText}>
            Bilgiler hazırlanıyor...
          </Text>
        </View>
      </View>
    );
  }

  const girilebilir = hesap?.girilebilir ?? false;

  const vadeLabel =
    VADE_SECENEKLERI.find((v) => v.value === vadeGun)
      ?.label ?? 'PEŞİN';

  const miktarLabel =
    siparisBirimi === 'ADET'
      ? 'Miktar (Adet)'
      : siparisBirimi === 'KOLI'
        ? 'Miktar (Koli)'
        : 'Miktar (Stand)';

  const handleSepeteEkle = () => {
    if (!girilebilir || !hesap) return;

    const added = addItem({
      product_id: product.id,
      urun_kodu: product.urun_kodu,
      urun_adi: product.urun_adi,
      koli_adedi: hesap.koliAdedi,
      adet: hesap.adet,
      koli_ici_adet: Number(product.koli_ici_adet),
      adet_fiyati: Number(product.liste_fiyati),
      stand_aktif: Number(product.stand_aktif),
      stand_ici_adet: product.stand_ici_adet === null ? null : Number(product.stand_ici_adet),
      siparis_birimi: siparisBirimi,
      birim_miktari: hesap.qty,
      stok: Number(product.stok),
      kdv_orani: Number(product.kdv_orani),
      koli_fiyati: Number(product.koli_fiyati) || 0,
      dip_fiyat: Number(product.dip_fiyat) || 0,
      iskonto_1: hesap.isk1,
      iskonto_2: hesap.isk2,
      iskonto_3: hesap.isk3,
    });

    if (added) { setCartTerm(Number(vadeGun)); navigation.goBack(); }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={
        Platform.OS === 'ios' ? 'padding' : undefined
      }
    >
      <HeaderBar
        title="Ürün Detay"
        subtitle={product.urun_adi}
      />

      {/* Üst bilgi şeridi */}
      <View style={styles.heroStrip}>
        <View style={styles.heroBlock}>
          <Text style={styles.heroLabel}>
            Ürün Kodu
          </Text>
          <Text
            style={styles.heroValue}
            numberOfLines={1}
          >
            {product.urun_kodu}
          </Text>
        </View>

        <View style={styles.heroDivider} />

        <View style={styles.heroBlock}>
          <Text style={styles.heroLabel}>
            Stok
          </Text>
          <Text style={styles.heroValue}>
            {product.stok}{' '}
            <Text style={styles.heroUnit}>adet</Text>
          </Text>
        </View>

        <View style={styles.heroDivider} />

        <View style={styles.heroBlock}>
          <Text style={styles.heroLabel}>
            Durum
          </Text>
          <View
            style={[
              styles.statusPill,
              girilebilir
                ? styles.statusPillOk
                : styles.statusPillNo,
            ]}
          >
            <Text
              style={[
                styles.statusPillText,
                girilebilir
                  ? styles.statusPillTextOk
                  : styles.statusPillTextNo,
              ]}
            >
              {girilebilir ? 'GİRİLİR' : 'GİRELEMEZ'}
            </Text>
          </View>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        {/* Ürün bilgileri */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>
            Ürün Bilgileri
          </Text>

          <View style={styles.infoGrid}>
            <InfoTile
              label="Adet Fiyatı"
              value={`${Number(product.liste_fiyati ?? 0).toFixed(2)} ₺`}
            />
            <InfoTile
              label="Koli Fiyatı"
              value={`${Number(product.koli_fiyati ?? 0).toFixed(2)} ₺`}
            />
            <InfoTile
              label="Koli İçi"
              value={`${Number(product.koli_ici_adet ?? 0)} adet`}
            />
            {Number(product.stand_aktif) === 1 && (
              <InfoTile
                label="Stand"
                value={`${Number(product.stand_ici_adet ?? 0)} adet · ${Number(product.stand_fiyati ?? 0).toFixed(2)} ₺`}
              />
            )}
            <InfoTile
              label="KDV"
              value={`%${product.kdv_orani ?? 0}`}
            />
          </View>

          {!!product.hacim_m3 && (
            <View style={styles.infoFooter}>
              <Text style={styles.infoFooterLabel}>
                Hacim
              </Text>
              <Text style={styles.infoFooterValue}>
                {product.hacim_m3} m³
              </Text>
            </View>
          )}
        </View>

        {/* Sipariş parametreleri */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>
            Sipariş Parametreleri
          </Text>

          <Text style={styles.fieldLabel}>
            Sipariş Birimi
          </Text>
          <View style={styles.segmentRow}>
            {[...BIRIM_SECENEKLERI, ...(Number(product.stand_aktif) === 1 ? [{ label: 'Stand', value: 'STAND' as SiparisBirimi }] : [])].map((b) => {
              const selected =
                siparisBirimi === b.value;
              return (
                <TouchableOpacity
                  key={b.value}
                  style={[
                    styles.segmentBtn,
                    selected && styles.segmentBtnActive,
                  ]}
                  onPress={() =>
                    setSiparisBirimi(b.value)
                  }
                  activeOpacity={0.8}
                >
                  <Text
                    style={[
                      styles.segmentText,
                      selected &&
                        styles.segmentTextActive,
                    ]}
                  >
                    {b.label}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>

          <Text style={styles.fieldLabel}>
            {miktarLabel}
          </Text>
          <TextInput
            style={styles.input}
            value={miktar}
            onChangeText={setMiktar}
            placeholder="0"
            placeholderTextColor={colors.textMuted}
            keyboardType="numeric"
          />

          <Text style={styles.fieldLabel}>
            Vade
          </Text>
          <Pressable
            style={styles.selectBox}
            onPress={() => setVadeMenuAcik(true)}
          >
            <Text style={styles.selectText}>
              {vadeLabel}
            </Text>
            <Text style={styles.selectChevron}>
              ▾
            </Text>
          </Pressable>

          <Text
            style={[
              styles.fieldLabel,
              { marginTop: spacing.sm },
            ]}
          >
            İskontolar (%)
          </Text>
          <View style={styles.discountRow}>
            <View style={styles.discountField}>
              <Text style={styles.discountHint}>
                1. İsk
              </Text>
              <TextInput
                style={styles.discountInput}
                value={iskonto1}
                onChangeText={setIskonto1}
                placeholder="0"
                placeholderTextColor={colors.textMuted}
                keyboardType="numeric"
              />
            </View>
            <View style={styles.discountField}>
              <Text style={styles.discountHint}>
                2. İsk
              </Text>
              <TextInput
                style={styles.discountInput}
                value={iskonto2}
                onChangeText={setIskonto2}
                placeholder="0"
                placeholderTextColor={colors.textMuted}
                keyboardType="numeric"
              />
            </View>
            <View style={styles.discountField}>
              <Text style={styles.discountHint}>
                3. İsk
              </Text>
              <TextInput
                style={styles.discountInput}
                value={iskonto3}
                onChangeText={setIskonto3}
                placeholder="0"
                placeholderTextColor={colors.textMuted}
                keyboardType="numeric"
              />
            </View>
          </View>

          {!hesap?.dipFiyatUygun && (
            <View style={styles.alertBox}>
              <Text style={styles.alertTitle}>
                Dip fiyat kontrolü
              </Text>
              <Text style={styles.alertText}>
                Net adet fiyatı{' '}
                {Number(
                  hesap?.netAdetFiyati ?? 0
                ).toFixed(2)}{' '}
                ₺ minimumun altında. Bu iskonto ile
                girilemez.
              </Text>
            </View>
          )}

          {!hesap?.stokUygun && (
            <View style={styles.alertBox}>
              <Text style={styles.alertTitle}>
                Stok yetersiz
              </Text>
              <Text style={styles.alertText}>
                İstenen: {hesap?.adet ?? 0} adet
                {'  ·  '}
                Mevcut: {product.stok} adet
              </Text>
            </View>
          )}
        </View>

        {/* Özet */}
        <View style={[styles.card, styles.summaryCard]}>
          <Text style={styles.sectionTitle}>
            Özet Hesap
          </Text>

          <SummaryLine
            label="Birim / Miktar"
            value={`${hesap?.qty ?? 0} ${siparisBirimi}`}
          />
          <SummaryLine
            label="Toplam Adet"
            value={`${hesap?.adet ?? 0} adet`}
          />
          <SummaryLine
            label="Vade"
            value={vadeLabel}
          />
          <SummaryLine
            label="Net Koli Fiyatı"
            value={`${Number(hesap?.netKoliFiyati ?? 0).toFixed(2)} ₺`}
          />
          <SummaryLine
            label="Net Adet Fiyatı"
            value={`${Number(hesap?.netAdetFiyati ?? 0).toFixed(2)} ₺`}
          />

          <View style={styles.totalBox}>
            <Text style={styles.totalLabel}>
              Net Tutar
            </Text>
            <Text style={styles.totalValue}>
              {Number(hesap?.netTutar ?? 0).toFixed(2)} ₺
            </Text>
          </View>
        </View>

        {/* Alt butonlar */}
        <View style={styles.footerActions}>
          <TouchableOpacity
            style={styles.btnSecondary}
            onPress={() => navigation.goBack()}
            activeOpacity={0.85}
          >
            <Text style={styles.btnSecondaryText}>
              Vazgeç
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[
              styles.btnPrimary,
              !girilebilir && styles.btnDisabled,
            ]}
            onPress={handleSepeteEkle}
            disabled={!girilebilir}
            activeOpacity={0.85}
          >
            <Text style={styles.btnPrimaryText}>
              Sepete Ekle
            </Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      {/* Vade modal */}
      <Modal
        visible={vadeMenuAcik}
        transparent
        animationType="fade"
        onRequestClose={() => setVadeMenuAcik(false)}
      >
        <Pressable
          style={styles.modalOverlay}
          onPress={() => setVadeMenuAcik(false)}
        >
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>
              Vade seçin
            </Text>
            {VADE_SECENEKLERI.map((secenek) => {
              const selected =
                vadeGun === secenek.value;
              return (
                <TouchableOpacity
                  key={secenek.value}
                  style={[
                    styles.modalRow,
                    selected && styles.modalRowActive,
                  ]}
                  onPress={() => {
                    setVadeGun(secenek.value);
                    setVadeMenuAcik(false);
                  }}
                >
                  <Text
                    style={[
                      styles.modalRowText,
                      selected &&
                        styles.modalRowTextActive,
                    ]}
                  >
                    {secenek.label}
                  </Text>
                  {selected && (
                    <Text style={styles.modalCheck}>
                      ✓
                    </Text>
                  )}
                </TouchableOpacity>
              );
            })}
          </View>
        </Pressable>
      </Modal>
    </KeyboardAvoidingView>
  );
}

function InfoTile({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <View style={styles.infoTile}>
      <Text style={styles.infoTileLabel}>
        {label}
      </Text>
      <Text style={styles.infoTileValue}>
        {value}
      </Text>
    </View>
  );
}

function SummaryLine({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <View style={styles.summaryLine}>
      <Text style={styles.summaryLabel}>
        {label}
      </Text>
      <Text style={styles.summaryValue}>
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },

  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.background,
    padding: spacing.lg,
  },

  loadingCard: {
    ...cardStyle,
    paddingVertical: spacing.xl,
    paddingHorizontal: spacing.lg,
    alignItems: 'center',
    minWidth: 220,
  },

  loadingTitle: {
    marginTop: spacing.md,
    fontSize: fontSize.md,
    fontWeight: '700',
    color: colors.text,
  },

  loadingText: {
    marginTop: 4,
    fontSize: fontSize.sm,
    color: colors.textMuted,
  },

  heroStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },

  heroBlock: {
    flex: 1,
    alignItems: 'center',
  },

  heroDivider: {
    width: 1,
    height: 36,
    backgroundColor: colors.border,
  },

  heroLabel: {
    fontSize: 10,
    color: colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    marginBottom: 4,
  },

  heroValue: {
    fontSize: fontSize.sm,
    fontWeight: '700',
    color: colors.text,
  },

  heroUnit: {
    fontWeight: '500',
    color: colors.textMuted,
    fontSize: fontSize.xs,
  },

  statusPill: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: radius.full,
  },

  statusPillOk: {
    backgroundColor: colors.successSoft,
  },

  statusPillNo: {
    backgroundColor: colors.dangerSoft,
  },

  statusPillText: {
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.3,
  },

  statusPillTextOk: {
    color: colors.success,
  },

  statusPillTextNo: {
    color: colors.danger,
  },

  scrollContent: {
    padding: spacing.md,
    paddingBottom: spacing.xl + 8,
  },

  card: {
    ...cardStyle,
    padding: spacing.md,
    marginBottom: spacing.md,
  },

  summaryCard: {
    borderColor: colors.border,
  },

  sectionTitle: {
    fontSize: fontSize.md,
    fontWeight: '700',
    color: colors.text,
    marginBottom: spacing.md,
  },

  infoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },

  infoTile: {
    width: '47%',
    flexGrow: 1,
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },

  infoTileLabel: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginBottom: 4,
  },

  infoTileValue: {
    fontSize: fontSize.base,
    fontWeight: '700',
    color: colors.text,
  },

  infoFooter: {
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },

  infoFooterLabel: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
  },

  infoFooterValue: {
    fontSize: fontSize.sm,
    fontWeight: '600',
    color: colors.text,
  },

  fieldLabel: {
    fontSize: fontSize.xs,
    fontWeight: '600',
    color: colors.textMuted,
    marginBottom: 6,
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },

  segmentRow: {
    flexDirection: 'row',
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 4,
    marginBottom: spacing.md,
    gap: 4,
  },

  segmentBtn: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 6,
    alignItems: 'center',
  },

  segmentBtnActive: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.primary,
  },

  segmentText: {
    fontSize: fontSize.sm,
    fontWeight: '600',
    color: colors.textMuted,
  },

  segmentTextActive: {
    color: colors.primary,
    fontWeight: '700',
  },

  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    fontSize: fontSize.base,
    color: colors.text,
    backgroundColor: colors.background,
    marginBottom: spacing.md,
  },

  selectBox: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    backgroundColor: colors.background,
    marginBottom: spacing.sm,
  },

  selectText: {
    fontSize: fontSize.base,
    fontWeight: '600',
    color: colors.text,
  },

  selectChevron: {
    fontSize: 12,
    color: colors.textMuted,
  },

  discountRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: spacing.sm,
  },

  discountField: {
    flex: 1,
  },

  discountHint: {
    fontSize: 10,
    color: colors.textMuted,
    marginBottom: 4,
  },

  discountInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.sm,
    paddingHorizontal: 10,
    paddingVertical: 10,
    fontSize: fontSize.base,
    color: colors.text,
    backgroundColor: colors.background,
    textAlign: 'center',
  },

  alertBox: {
    marginTop: spacing.sm,
    padding: spacing.sm + 2,
    borderRadius: radius.sm,
    backgroundColor: colors.dangerSoft,
    borderWidth: 1,
    borderColor: '#fecaca',
  },

  alertTitle: {
    fontSize: fontSize.sm,
    fontWeight: '700',
    color: colors.danger,
    marginBottom: 4,
  },

  alertText: {
    fontSize: fontSize.xs,
    color: colors.text,
    lineHeight: 18,
  },

  summaryLine: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },

  summaryLabel: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
  },

  summaryValue: {
    fontSize: fontSize.sm,
    fontWeight: '600',
    color: colors.text,
  },

  totalBox: {
    marginTop: spacing.sm,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },

  totalLabel: {
    fontSize: fontSize.base,
    fontWeight: '700',
    color: colors.text,
  },

  totalValue: {
    fontSize: fontSize.lg,
    fontWeight: '800',
    color: colors.primary,
  },

  footerActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },

  btnSecondary: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 14,
    alignItems: 'center',
    backgroundColor: colors.card,
  },

  btnSecondaryText: {
    color: colors.text,
    fontWeight: '600',
    fontSize: fontSize.base,
  },

  btnPrimary: {
    flex: 1.4,
    backgroundColor: colors.buttonPrimary,
    borderRadius: radius.md,
    paddingVertical: 14,
    alignItems: 'center',
  },

  btnDisabled: {
    backgroundColor: '#d4d4d8',
  },

  btnPrimaryText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: fontSize.base,
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(24,24,27,0.45)',
    justifyContent: 'center',
    padding: spacing.lg,
  },

  modalCard: {
    backgroundColor: colors.card,
    borderRadius: radius.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },

  modalTitle: {
    fontSize: fontSize.md,
    fontWeight: '700',
    color: colors.text,
    marginBottom: spacing.sm,
    paddingHorizontal: 4,
  },

  modalRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
    paddingHorizontal: 12,
    borderRadius: radius.sm,
  },

  modalRowActive: {
    backgroundColor: colors.primarySoft,
  },

  modalRowText: {
    fontSize: fontSize.base,
    color: colors.text,
  },

  modalRowTextActive: {
    color: colors.primary,
    fontWeight: '700',
  },

  modalCheck: {
    color: colors.primary,
    fontWeight: '700',
    fontSize: fontSize.base,
  },
});
