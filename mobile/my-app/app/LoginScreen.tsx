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
  ScrollView,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { login } from '../services/authService';
import { useAuth } from '../services/AuthContext';
import { colors, spacing, radius, fontSize } from '../constants/theme';

// Figma: "login 1" ekranı — Kullanıcı Adı / Şifre / Giriş Yap
export default function LoginScreen({ navigation }: any) {
  const { setUser } = useAuth();
  const insets = useSafeAreaInsets();
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
      const data = await login(kullaniciAdi, sifre);
      setUser(data.user);
      navigation.replace('MainTabs');
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
      <ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          { paddingTop: insets.top + spacing.xl, paddingBottom: insets.bottom + spacing.lg },
        ]}
        keyboardShouldPersistTaps="handled"
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
              placeholderTextColor={colors.textMuted}
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
              placeholderTextColor={colors.textMuted}
              secureTextEntry
              value={sifre}
              onChangeText={setSifre}
            />
          </View>

          {error ? <Text style={styles.errorText}>{error}</Text> : null}

          <TouchableOpacity style={styles.submitBtn} onPress={handleLogin} disabled={loading}>
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.submitText}>Giriş Yap</Text>
            )}
          </TouchableOpacity>
        </View>

        <Text style={styles.footer}>© 2026 Siberbridge. Tüm hakları saklıdır.</Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.card },
  scrollContent: { flexGrow: 1, paddingHorizontal: spacing.lg, justifyContent: 'center' },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: spacing.xl,
  },
  logoBox: {
    width: 28,
    height: 28,
    borderRadius: radius.sm,
    backgroundColor: colors.primary,
    marginRight: spacing.sm,
  },
  brandText: { fontSize: fontSize.lg, fontWeight: '700', color: colors.text },
  formContainer: { flexGrow: 0 },
  title: { fontSize: fontSize.xxl, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: fontSize.base, color: colors.textMuted, marginTop: spacing.xs, marginBottom: spacing.lg },
  inputGroup: { marginBottom: spacing.md },
  label: { fontSize: fontSize.sm, color: colors.text, marginBottom: spacing.xs, fontWeight: '500' },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 14,
    fontSize: fontSize.md,
    color: colors.text,
    backgroundColor: colors.background,
  },
  errorText: { color: colors.danger, marginBottom: spacing.sm, fontSize: fontSize.sm },
  submitBtn: {
    backgroundColor: colors.buttonPrimary,
    borderRadius: radius.md,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: spacing.md,
  },
  submitText: { color: '#fff', fontSize: fontSize.md, fontWeight: '600' },
  footer: {
    textAlign: 'center',
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: spacing.xl,
  },
});
