import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { login } from '../services/authService';
import { colors, spacing } from '../constants/theme';

// Figma: "login 1" ekranı — Kullanıcı Adı / Şifre / Giriş Yap
export default function LoginScreen({ navigation }: any) {
  const [kullaniciAdi, setKullaniciAdi] = useState('');
  const [sifre, setSifre] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleLogin = async () => {
    setError(null);
    if (!kullaniciAdi || !sifre) {
      setError('Kullanıcı adı ve şifre zorunludur.');
      return;
    }
    setLoading(true);
    try {
      await login(kullaniciAdi, sifre);
      navigation.replace('CariArama');
    } catch (e: any) {
      setError(e.message || 'Giriş başarısız.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={styles.brandRow}>
        <View style={styles.logoBox} />
        <Text style={styles.brandText}>Siberbridge</Text>
      </View>

      <View style={styles.formContainer}>
        <Text style={styles.title}>Giriş Yap</Text>
        <Text style={styles.subtitle}>B2B sipariş panelinize güvenle erişim sağlayın.</Text>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Kullanıcı Adı</Text>
          <TextInput
            style={styles.input}
            placeholder="Kullanıcı Adı"
            autoCapitalize="none"
            value={kullaniciAdi}
            onChangeText={setKullaniciAdi}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Şifre</Text>
          <TextInput
            style={styles.input}
            placeholder="Şifre"
            secureTextEntry
            value={sifre}
            onChangeText={setSifre}
          />
        </View>

        {error && <Text style={styles.errorText}>{error}</Text>}

        <TouchableOpacity style={styles.submitBtn} onPress={handleLogin} disabled={loading}>
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.submitText}>Giriş Yap</Text>
          )}
        </TouchableOpacity>
      </View>

      <Text style={styles.footer}>© 2026 Siberbridge. Tüm hakları saklıdır.</Text>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.card,
    padding: spacing.lg,
    justifyContent: 'center',
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    position: 'absolute',
    top: 64,
    left: spacing.lg,
  },
  logoBox: {
    width: 28,
    height: 28,
    borderRadius: 6,
    backgroundColor: colors.primary,
    marginRight: spacing.sm,
  },
  brandText: { fontSize: 18, fontWeight: '700', color: colors.text },
  formContainer: { marginTop: 40 },
  title: { fontSize: 26, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textMuted, marginTop: spacing.xs, marginBottom: spacing.lg },
  inputGroup: { marginBottom: spacing.md },
  label: { fontSize: 13, color: colors.text, marginBottom: spacing.xs, fontWeight: '500' },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: spacing.md,
    paddingVertical: 14,
    fontSize: 15,
    backgroundColor: colors.background,
  },
  errorText: { color: colors.danger, marginBottom: spacing.sm, fontSize: 13 },
  submitBtn: {
    backgroundColor: colors.primary,
    borderRadius: 10,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: spacing.md,
  },
  submitText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  footer: {
    position: 'absolute',
    bottom: 32,
    left: spacing.lg,
    fontSize: 12,
    color: colors.textMuted,
  },
});
