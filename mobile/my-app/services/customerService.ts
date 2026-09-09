import { apiRequest } from './api';
import { fetchAllPages } from './pagination';

export interface Customer {
  id: number;
  cari_kodu: string;
  firma_adi: string;
  yetkili_kisi: string | null;
  telefon: string | null;
  email: string | null;
  adres: string | null;
  sehir: string | null;
  ilce: string | null;
  durum: string;
}

export interface CustomerOrder {
  id: number;
  siparis_no: string;
  evrak_aciklamasi: string | null;
  teslim_tarihi: string | null;
  genel_toplam: number;
  durum: string;
  olusturulma_tarihi: string;
}

export async function searchCustomers(query: string): Promise<Customer[]> {
  return fetchAllPages<Customer>('/customers/list.php?q='+encodeURIComponent(query));
}

export async function getCustomer(
  id: number
): Promise<{ data: Customer; son_siparisler: CustomerOrder[] }> {
  return apiRequest(`/customers/get.php?id=${id}`);
}
