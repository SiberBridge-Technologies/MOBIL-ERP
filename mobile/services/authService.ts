import { apiRequest, saveToken, clearToken } from './api';

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
  await saveToken(data.token);
  await saveUser(data.user);
  return data;
}

export async function logout(): Promise<void> {
  try {
    await apiRequest('/auth/logout.php', { method: 'POST' });
  } finally {
    await clearToken();
  }
}

const USER_KEY = 'erp_current_user';

export async function saveUser(user: LoginResponse['user']): Promise<void> {
  const SecureStore = await import('expo-secure-store');
  await SecureStore.setItemAsync(USER_KEY, JSON.stringify(user));
}

export async function getSavedUser(): Promise<LoginResponse['user'] | null> {
  const SecureStore = await import('expo-secure-store');
  const raw = await SecureStore.getItemAsync(USER_KEY);
  return raw ? JSON.parse(raw) : null;
}
