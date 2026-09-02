import * as SecureStore from 'expo-secure-store';

const API_BASE_URL =
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  'http://192.168.1.110/api';

const TOKEN_KEY = 'erp_auth_token';

export async function saveToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  console.log('🔐 Token kaydedildi:', token ? `VAR (${token.length} karakter)` : 'YOK');
}

export async function getToken(): Promise<string | null> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);

  console.log(
    '🔐 Token okundu:',
    token ? `VAR (${token.length} karakter)` : 'YOK'
  );

  return token;
}

export async function clearToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  console.log('🔐 Token silindi.');
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
    this.name = 'ApiError';
    this.status = status;
  }
}

export async function apiRequest<T = any>(
  endpoint: string,
  options: ApiOptions = {}
): Promise<T> {
  const {
    method = 'GET',
    body,
    requiresAuth = true,
  } = options;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (requiresAuth) {
    const token = await getToken();

    if (token) {
      headers.Authorization = `Bearer ${token}`;

      console.log(
        '📡 Authorization header gönderiliyor:',
        `Bearer ${token.substring(0, 8)}...`
      );
    } else {
      console.warn(
        '⚠️ API isteği token olmadan gönderiliyor!'
      );
    }
  }

  const url = API_BASE_URL + endpoint;

  console.log('🌐 API İsteği:', method, url);

  let response: Response;

  try {
    console.log('📤 İstek Headerları:', JSON.stringify(headers));
    response = await fetch(url, {
      method,
      headers,
      body:
        body !== undefined
          ? JSON.stringify(body)
          : undefined,
    });
  } catch (error) {
    console.error('❌ API bağlantı hatası:', error);

    throw new ApiError(
      'Sunucuya bağlanılamadı. API adresini kontrol edin.',
      0
    );
  }

  const rawText = await response.text();

  console.log(
    '📥 API Cevabı:',
    response.status,
    rawText
  );

  let data: any = {};

  if (rawText.trim().length > 0) {
    try {
      data = JSON.parse(rawText);
    } catch (error) {
      console.error(
        '❌ JSON parse hatası:',
        rawText
      );

      throw new ApiError(
        'Sunucudan geçersiz JSON yanıtı alındı.',
        response.status
      );
    }
  }

  if (
    !response.ok ||
    data?.success === false
  ) {
    throw new ApiError(
      data?.message || 'Bir hata oluştu.',
      response.status
    );
  }

  return data as T;
}