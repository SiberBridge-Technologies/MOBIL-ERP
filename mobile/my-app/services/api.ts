import { readStorage, writeStorage, deleteStorage } from './storage';
export const API_BASE_URL = (process.env.EXPO_PUBLIC_API_BASE_URL || '').replace(/\/+$/, '');
const TOKEN_KEY = 'erp_auth_token';
let expired: (() => void) | undefined;
export function onSessionExpired(callback?: () => void) { expired = callback; }
export async function saveToken(token: string) { await writeStorage(TOKEN_KEY, token); }
export async function getToken() { return readStorage(TOKEN_KEY); }
export async function clearToken() { await deleteStorage(TOKEN_KEY); }
export async function invalidateSession() {
  await Promise.all([clearToken(), deleteStorage('erp_current_user'), deleteStorage('erp_token_expiry')]);
  expired?.();
}
export class ApiError extends Error {
  constructor(message: string, public status: number) { super(message); this.name = 'ApiError'; }
}
interface ApiOptions { method?: 'GET' | 'POST' | 'PUT' | 'DELETE'; body?: unknown; requiresAuth?: boolean; }
export async function apiRequest<T = any>(endpoint: string, options: ApiOptions = {}): Promise<T> {
  if (!API_BASE_URL) throw new ApiError('API adresi ayarlanmamış. EXPO_PUBLIC_API_BASE_URL değerini belirtin.', 0);
  const { method = 'GET', body, requiresAuth = true } = options;
  const headers: Record<string,string> = { 'Content-Type':'application/json', Accept:'application/json' };
  if (requiresAuth) {
    const token = await getToken();
    if (!token) { await invalidateSession(); throw new ApiError('Lütfen tekrar giriş yapın.', 401); }
    headers.Authorization = 'Bearer ' + token;
  }
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);
  try {
    const response = await fetch(API_BASE_URL + endpoint, { method, headers, signal: controller.signal, body: body === undefined ? undefined : JSON.stringify(body) });
    if (requiresAuth && response.status === 401) await invalidateSession();
    let data: any;
    try { data = await response.json(); } catch { throw new ApiError('Sunucudan geçersiz yanıt alındı.', response.status); }
    if (!response.ok || data?.success === false) throw new ApiError(data?.message || 'İşlem tamamlanamadı.', response.status);
    return data as T;
  } catch (e) {
    if (e instanceof ApiError) throw e;
    throw new ApiError(controller.signal.aborted ? 'İstek zaman aşımına uğradı. Tekrar deneyebilirsiniz.' : 'Sunucuya bağlanılamadı.', 0);
  } finally { clearTimeout(timeout); }
}
