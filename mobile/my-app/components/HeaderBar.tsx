import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, spacing, fontSize, isNarrowScreen } from '../constants/theme';
import { useAuth } from '../services/AuthContext';

interface Props {
  title: string;
  subtitle?: string;
}

// Figma'daki tüm iç ekranlarda tekrarlanan header (avatar - başlık - Siberbridge logosu).
// - Kullanıcı bilgisi gerçek oturum verisinden gelir (AuthContext).
// - Durum çubuğu/çentik boşluğu (safe area) otomatik hesaplanır.
// - Dar ekranlarda (küçük telefonlar) marka metni gizlenip yer başlığa bırakılır.
export default function HeaderBar({ title, subtitle }: Props) {
  const { user } = useAuth();
  const insets = useSafeAreaInsets();
  const narrow = isNarrowScreen();

  const displayName = user ? `${user.ad} ${user.soyad}` : 'Kullanıcı';
  const displayRole = user ? roleLabel(user.rol) : '';

  const initials = displayName
    .split(' ')
    .filter(Boolean)
    .map((n) => n[0])
    .join('')
    .toUpperCase();

  return (
    <View style={[styles.header, { paddingTop: insets.top + spacing.sm }]}>
      <View style={styles.left}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials}</Text>
        </View>
        {!narrow && (
          <View style={{ flexShrink: 1 }}>
            <Text style={styles.userName} numberOfLines={1}>{displayName}</Text>
            <Text style={styles.userRole}>{displayRole}</Text>
          </View>
        )}
      </View>

      <View style={styles.center}>
        <Text style={styles.title} numberOfLines={1}>{title}</Text>
        {subtitle ? <Text style={styles.subtitle} numberOfLines={1}>{subtitle}</Text> : null}
      </View>

      {!narrow && (
        <View style={styles.right}>
          <Text style={styles.brand}>Siberbridge</Text>
        </View>
      )}
    </View>
  );
}

function roleLabel(rol: string): string {
  switch (rol) {
    case 'ADMIN': return 'Yönetici';
    case 'YONETICI': return 'Yönetici';
    case 'CALISAN': return 'Temsilci';
    default: return rol;
  }
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingBottom: spacing.sm,
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  left: { flexDirection: 'row', alignItems: 'center', flex: 1 },
  avatar: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: colors.buttonPrimary,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.sm,
  },
  avatarText: { color: '#fff', fontWeight: '700', fontSize: fontSize.sm },
  userName: { fontSize: fontSize.sm, fontWeight: '600', color: colors.text },
  userRole: { fontSize: fontSize.xs, color: colors.textMuted },
  center: { flex: 1.4, alignItems: 'center' },
  title: { fontSize: fontSize.base, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  right: { flex: 0.8, alignItems: 'flex-end' },
  brand: { fontSize: fontSize.sm, fontWeight: '700', color: colors.text },
});
