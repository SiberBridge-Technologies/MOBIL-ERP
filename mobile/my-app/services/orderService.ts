import { apiRequest } from './api';
import { fetchAllPages } from './pagination';

export interface CartItem {
  product_id: number;

  urun_kodu: string;
  urun_adi: string;

  koli_adedi: number;
  adet: number;
  koli_ici_adet: number;
  adet_fiyati: number;
  stand_aktif: number;
  stand_ici_adet: number | null;
  siparis_birimi: 'ADET' | 'KOLI' | 'STAND';
  birim_miktari: number;
  stok: number;
  kdv_orani: number;

  koli_fiyati: number;
  dip_fiyat: number;

  iskonto_1: number;
  iskonto_2: number;
  iskonto_3: number;
}

export interface CreateOrderPayload {
  customer_id: number;
  request_id?: string;
  expected_total?: number;

  evrak_aciklamasi?: string;

  teslim_tarihi?: string;

  ambar_bilgisi?: string;

  odeme_tipi?: 'NAKIT' | 'VADELI';

  vade_gun?: number;

  note?: string;

  items: Array<{
    product_id: number;
    koli_adedi: number;
    adet?: number;
    siparis_birimi?: 'ADET' | 'KOLI' | 'STAND';
    birim_miktari?: number;
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

export async function createOrder(
  payload: CreateOrderPayload
): Promise<CreateOrderResponse> {
  return apiRequest<CreateOrderResponse>(
    '/orders/create.php',
    {
      method: 'POST',
      body: payload,
    }
  );
}

export interface MyOrder {
  id: number;
  siparis_no: string;
  firma_adi: string;
  genel_toplam: number;
  durum: string;
  olusturulma_tarihi: string;
}

export async function getMyOrders(): Promise<MyOrder[]> {
  return fetchAllPages<MyOrder>('/orders/list.php');
}
