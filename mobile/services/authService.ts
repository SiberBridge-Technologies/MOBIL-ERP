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
  return data;
}

export async function logout(): Promise<void> {
  try {
    await apiRequest('/auth/logout.php', { method: 'POST' });
  } finally {
    await clearToken();
  }
}
