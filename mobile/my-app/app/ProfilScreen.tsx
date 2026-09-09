import React, { useState } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { logout } from '../services/authService';
import { useAuth } from '../services/AuthContext';
import { colors, spacing } from '../constants/theme';
import HeaderBar from '../components/HeaderBar';

// Dokümandaki 4. ana sekme: Ürünler / Cariler / Sipariş / Profil
export default function ProfilScreen({ navigation }: any) {
  const { user, setUser } = useAuth();
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = () => {
    Alert.alert('Çıkış Yap', 'Oturumunuzu kapatmak istediğinize emin misiniz?', [
      { text: 'Vazgeç', style: 'cancel' },
      {
        text: 'Çıkış Yap',
        style: 'destructive',
        onPress: async () => {
          setLoggingOut(true);
          try {
            await logout();
          } finally {
            setUser(null);

          }
        },
      },
    ]);
  };

  const initials = user ? `${user.ad[0] ?? ''}${user.soyad[0] ?? ''}`.toUpperCase() : '?';

  return (
    <View style={styles.container}>
      <HeaderBar title="Profil" />

      <View style={styles.body}>
        <View style={styles.avatarLarge}>
          <Text style={styles.avatarLargeText}>{initials}</Text>
        </View>
        <Text style={styles.name}>{user ? `${user.ad} ${user.soyad}` : '-'}</Text>
        <Text style={styles.role}>{user?.rol ?? ''}</Text>

        <View style={styles.infoCard}>
          <InfoRow label="Kullanıcı Adı" value={user?.kullanici_adi ?? '-'} />
          <InfoRow label="Rol" value={user?.rol ?? '-'} />
        </View>

        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout} disabled={loggingOut}>
          {loggingOut ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.logoutText}>Çıkış Yap</Text>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  body: { flex: 1, alignItems: 'center', padding: spacing.lg },
  avatarLarge: {
    width: 84,
    height: 84,
    borderRadius: 42,
    backgroundColor: colors.buttonPrimary,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: spacing.lg,
  },
  avatarLargeText: { color: '#fff', fontSize: 28, fontWeight: '700' },
  name: { fontSize: 18, fontWeight: '700', color: colors.text, marginTop: spacing.md },
  role: { fontSize: 13, color: colors.textMuted, marginTop: 2 },
  infoCard: {
    width: '100%',
    backgroundColor: colors.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    marginTop: spacing.lg,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  infoLabel: { color: colors.textMuted, fontSize: 13 },
  infoValue: { color: colors.text, fontSize: 13, fontWeight: '600' },
  logoutBtn: {
    width: '100%',
    backgroundColor: colors.danger,
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: spacing.xl,
  },
  logoutText: { color: '#fff', fontWeight: '700', fontSize: 15 },
});

import { Alert } from '../services/dialogs';
