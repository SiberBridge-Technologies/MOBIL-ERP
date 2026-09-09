import { apiRequest, saveToken, invalidateSession, getToken } from './api';
import { readStorage, writeStorage } from './storage';

export interface LoginResponse {
  success: boolean;
  token: string;
  expires_at: string;
  user: {
    id: number;
    ad: string;
    soyad: string;
    kullanici_adi: string;
    rol: string;
  };
}

export async function login(kullaniciAdi: string, sifre: string): Promise<LoginResponse> {
  const data = await apiRequest<LoginResponse>('/auth/login.php', {
    method: 'POST',
    body: { kullanici_adi: kullaniciAdi, sifre },
    requiresAuth: false,
  });

  // SecureStore yalnızca string kabul eder. Sunucudan beklenmedik bir yanıt
  // gelirse (örn. token eksikse) burada anlaşılır bir hata fırlatıp
  // uygulamanın çökmesini engelliyoruz.
  if (!data || typeof data.token !== 'string' || data.token.length === 0) {
    throw new Error(
      'Sunucudan geçerli bir oturum anahtarı alınamadı.'
    );
  }

  await saveToken(data.token);
  await writeStorage('erp_token_expiry', data.expires_at);

  if (data.user) {
    await saveUser(data.user);
  }

  return data;
}

export async function logout(): Promise<void> {
  try {
    await apiRequest('/auth/logout.php', { method: 'POST' });
  } catch {
    // Local logout must also succeed while offline.
  } finally {
    await invalidateSession();
  }
}

const USER_KEY = 'erp_current_user';

export async function saveUser(user: LoginResponse['user']): Promise<void> {
  await writeStorage(USER_KEY, JSON.stringify(user));
}

export async function getSavedUser(): Promise<LoginResponse['user'] | null> {
  try {
    const [raw, token, expiry] = await Promise.all([readStorage(USER_KEY), getToken(), readStorage('erp_token_expiry')]);
    if (!raw || !token || !expiry || !Number.isFinite(Date.parse(expiry)) || Date.parse(expiry) <= Date.now()) {
      await invalidateSession(); return null;
    }
    return JSON.parse(raw);
  } catch { await invalidateSession(); return null; }
}
