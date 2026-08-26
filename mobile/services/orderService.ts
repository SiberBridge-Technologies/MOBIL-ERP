import { apiRequest } from './api';

export interface CartItem {
  product_id: number;
  urun_kodu: string;
  urun_adi: string;
  koli_adedi: number;
  koli_fiyati: number;
  iskonto_1: number;
  iskonto_2: number;
  iskonto_3: number;
}

export interface CreateOrderPayload {
  customer_id: number;
  evrak_aciklamasi?: string;
  teslim_tarihi?: string; // yyyy-mm-dd
  ambar_bilgisi?: string;
  odeme_tipi?: 'NAKIT' | 'VADELI';
  vade_gun?: number;
  note?: string;
  items: Array<{
    product_id: number;
    koli_adedi: number;
    iskonto_1?: number;
    iskonto_2?: number;
    iskonto_3?: number;
  }>;
}

export interface CreateOrderResponse {
  success: boolean;
  order_id: number;
  siparis_no: string;
  genel_toplam: number;
}

/**
 * NOT: Burada gönderilen fiyat/iskonto bilgileri sadece "önerilen" değerlerdir.
 * Sunucu tarafı (orders/create.php) fiyatı ve stoğu MySQL'den tekrar okuyup
 * doğrular; istemci taraflı bir fiyat manipülasyonu siparişi etkilemez.
 */
export async function createOrder(payload: CreateOrderPayload): Promise<CreateOrderResponse> {
  return apiRequest<CreateOrderResponse>('/orders/create.php', {
    method: 'POST',
    body: payload,
  });
}
