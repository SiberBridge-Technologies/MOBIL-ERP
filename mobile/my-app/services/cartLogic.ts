import type { CartItem } from './orderService';
export const roundMoney = (n: number) => Math.round((n + Number.EPSILON) * 100) / 100;
export function netUnitPrice(item: CartItem): number {
  return roundMoney(item.adet_fiyati * (1-item.iskonto_1/100) * (1-item.iskonto_2/100) * (1-item.iskonto_3/100));
}
export function lineNet(item: CartItem): number { return roundMoney(netUnitPrice(item) * item.adet); }
export function lineTax(item: CartItem): number { return roundMoney(lineNet(item) * item.kdv_orani / 100); }
export function total(items: CartItem[]): number { return roundMoney(items.reduce((sum, item) => sum + lineNet(item) + lineTax(item), 0)); }
export function validateCartItem(item: CartItem): string | null {
  if (!Number.isInteger(item.adet) || item.adet <= 0 || !Number.isInteger(item.koli_ici_adet) || item.koli_ici_adet < 1) return 'Adet pozitif tam sayı olmalıdır.';
  if (!Number.isFinite(item.stok) || item.adet > item.stok) return 'Yeterli stok bulunmuyor.';
  if (![item.adet_fiyati, item.koli_fiyati, item.dip_fiyat, item.kdv_orani, item.iskonto_1, item.iskonto_2, item.iskonto_3].every(Number.isFinite)) return 'Ürün fiyat bilgileri geçersiz.';
  if ([item.iskonto_1,item.iskonto_2,item.iskonto_3,item.kdv_orani].some(n => n < 0 || n > 100)) return 'İskonto ve KDV 0–100 arasında olmalıdır.';
  if (item.adet_fiyati < 0 || netUnitPrice(item) < roundMoney(item.dip_fiyat)) return 'Net adet fiyatı dip adet fiyatının altında.';
  if (item.siparis_birimi === 'STAND' && (!item.stand_aktif || !Number.isInteger(item.stand_ici_adet) || Number(item.stand_ici_adet) < 1)) return 'Bu ürün stand ile satılamaz.';
  return null;
}
export interface CartState { customerId: number | null; customerName: string | null; items: CartItem[]; vadeGun: number; }
export const emptyCart: CartState = {customerId:null, customerName:null, items:[], vadeGun:0};
export type CartAction = {type:'customer';id:number;name:string} | {type:'item';item:CartItem} | {type:'remove';id:number} | {type:'clear'} | {type:'term';days:number};
export function cartReducer(state: CartState, action: CartAction): CartState {
  switch (action.type) {
    case 'customer': return {...(state.customerId === action.id ? state : emptyCart), customerId:action.id, customerName:action.name};
    case 'item': return {...state, items:[...state.items.filter(i=>i.product_id!==action.item.product_id), action.item]};
    case 'remove': return {...state, items:state.items.filter(i=>i.product_id!==action.id)};
    case 'clear': return {...emptyCart};
    case 'term': return {...state,vadeGun:action.days};
  }
}
