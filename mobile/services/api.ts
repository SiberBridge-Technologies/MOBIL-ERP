import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';

const API_BASE_URL: string =
  (Constants.expoConfig?.extra?.apiBaseUrl as string) || 'https://sbrsystem.gt.tc/api';

const TOKEN_KEY = 'erp_auth_token';

export async function saveToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function clearToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

interface ApiOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
  requiresAuth?: boolean;
}

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

/**
 * Tüm PHP REST API çağrıları bu fonksiyon üzerinden yapılır.
 * MySQL'e ASLA doğrudan bağlanılmaz — sadece bu HTTPS katmanı kullanılır.
 */
export async function apiRequest<T = any>(
  endpoint: string,
  options: ApiOptions = {}
): Promise<T> {
  const { method = 'GET', body, requiresAuth = true } = options;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
  };

  if (requiresAuth) {
    const token = await getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok || data.success === false) {
    throw new ApiError(data.message || 'Bir hata oluştu.', response.status);
  }

  return data;
}