import { Platform } from 'react-native';

// Shadcn UI'ın nötr "zinc" paletine ve backend'deki style.css ile BİREBİR
// aynı tokenlara göre kurgulandı — mobil ve web admin panel aynı görsel
// dili konuşsun diye. Marka/aksan rengi (mavi) sadece logo, aktif sekme,
// bağlantı ve odak durumlarında kullanılır; buton arka planları backend'deki
// gibi nötr siyah (zinc-900) tutulur.
export const colors = {
  // Marka aksanı (backend --primary ile aynı mavi)
  primary: '#1d4ed8',
  primarySoft: '#eff6ff',

  // Buton arka planı (backend .btn-primary ile aynı zinc-900 siyah)
  buttonPrimary: '#18181b',
  buttonPrimaryText: '#ffffff',

  // Nötr zinc skalası (backend ile birebir aynı hex değerleri)
  background: '#fafafa',
  card: '#ffffff',
  border: '#e4e4e7',
  text: '#18181b',
  textMuted: '#71717a',

  success: '#16a34a',
  successSoft: '#f0fdf4',
  danger: '#dc2626',
  dangerSoft: '#fef2f2',
  warning: '#d97706',
  warningSoft: '#fff7ed',
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
};

export const radius = {
  sm: 8,
  md: 10,
  lg: 14,
  full: 999,
};

export const fontSize = {
  xs: 11,
  sm: 12,
  base: 14,
  md: 15,
  lg: 18,
  xl: 22,
  xxl: 26,
};

// Tüm kartlarda kullanılan tek, hafif gölge stili — cihazlar arası tutarlı
// görünsün diye Android'de elevation, iOS'ta shadow* kullanılır.
export const cardShadow = Platform.select({
  ios: {
    shadowColor: '#18181b',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  android: {
    elevation: 1,
  },
  default: {},
});

// Yaygın kart görünümü — kopyala-yapıştırı azaltmak için her ekran bunu
// styles.card, {...} şeklinde genişletebilir.
export const cardStyle = {
  backgroundColor: colors.card,
  borderRadius: radius.md,
  borderWidth: 1,
  borderColor: colors.border,
  ...cardShadow,
};

// Telefon genişliğine göre basit responsive yardımcıları. RN'de "breakpoint"
// kavramı yok; küçük/dar cihazlarda (örn. eski/küçük Android telefonlar)
// boşlukları biraz sıkılaştırmak için Dimensions ile birlikte kullanılabilir.
import { Dimensions } from 'react-native';
export function isNarrowScreen(): boolean {
  return Dimensions.get('window').width < 360;
}
