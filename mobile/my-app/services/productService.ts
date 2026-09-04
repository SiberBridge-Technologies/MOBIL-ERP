import { apiRequest } from './api';

export interface Product {
  id: number;
  urun_kodu: string;
  urun_adi: string;
  aciklama: string | null;
  barkod: string | null;

  liste_fiyati: number;
  koli_fiyati: number;
  dip_fiyat: number;

  koli_ici_adet: number;
  kdv_orani: number;
  hacim_m3: number;
  stok: number;
  birim: string;
  gorsel_url: string | null;
}

export async function getProducts(): Promise<Product[]> {
  const data = await apiRequest<{ data: Product[] }>(
    '/products/list.php'
  );

  return Array.isArray(data.data)
    ? data.data
    : [];
}

export async function searchProducts(
  query: string
): Promise<Product[]> {
  const data = await apiRequest<{ data: Product[] }>(
    `/products/list.php?q=${encodeURIComponent(query)}`
  );

  return Array.isArray(data.data)
    ? data.data
    : [];
}

export async function getProduct(
  id: number
): Promise<Product> {
  const data = await apiRequest<{ data: Product }>(
    `/products/get.php?id=${id}`
  );

  return data.data;
}