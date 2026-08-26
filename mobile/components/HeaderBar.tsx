import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { colors, spacing } from '../constants/theme';

interface Props {
  title: string;
  subtitle?: string;
  userName?: string;
  userRole?: string;
}

// Figma'daki tüm iç ekranlarda tekrarlanan header (avatar - başlık - Siberbridge logosu)
export default function HeaderBar({ title, subtitle, userName = 'Ersan Çelebi', userRole = 'Temsilci' }: Props) {
  const initials = userName
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase();

  return (
    <View style={styles.header}>
      <View style={styles.left}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials}</Text>
        </View>
        <View>
          <Text style={styles.userName}>{userName}</Text>
          <Text style={styles.userRole}>{userRole}</Text>
        </View>
      </View>

      <View style={styles.center}>
        <Text style={styles.title}>{title}</Text>
        {subtitle && <Text style={styles.subtitle}>{subtitle}</Text>}
      </View>

      <View style={styles.right}>
        <Text style={styles.brand}>Siberbridge</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  left: { flexDirection: 'row', alignItems: 'center', flex: 1 },
  avatar: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.sm,
  },
  avatarText: { color: '#fff', fontWeight: '700', fontSize: 13 },
  userName: { fontSize: 13, fontWeight: '600', color: colors.text },
  userRole: { fontSize: 11, color: colors.textMuted },
  center: { flex: 1.5, alignItems: 'center' },
  title: { fontSize: 15, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 11, color: colors.textMuted, marginTop: 2 },
  right: { flex: 1, alignItems: 'flex-end' },
  brand: { fontSize: 13, fontWeight: '700', color: colors.primary },
});
