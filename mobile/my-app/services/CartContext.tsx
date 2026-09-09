import { createContext, ReactNode, useContext, useReducer } from 'react';
import type { CartItem } from './orderService';
import { cartReducer, emptyCart, lineNet, total, validateCartItem } from './cartLogic';
import { getProduct } from './productService';
import { Alert } from './dialogs';
interface CartContextValue {
  customerId: number | null; customerName: string | null; items: CartItem[]; vadeGun: number;
  setCustomer: (id:number,name:string)=>void; setVadeGun:(days:number)=>void;
  addItem:(item:CartItem)=>boolean; removeItem:(id:number)=>void; clearCart:()=>void;
  refreshPrices:()=>Promise<void>;
  netTutar:(item:CartItem)=>number; genelToplam:()=>number;
}
const CartContext = createContext<CartContextValue | undefined>(undefined);
export function CartProvider({children}:{children:ReactNode}) {
  const [state, dispatch] = useReducer(cartReducer, emptyCart);
  const addItem = (item:CartItem) => {
    const error = validateCartItem(item);
    if (error) { Alert.alert('Sepete eklenemedi', error); return false; }
    dispatch({type:'item',item:{...item,koli_adedi:item.adet/item.koli_ici_adet}}); return true;
  };
  const refreshPrices = async () => {
    const updated = await Promise.all(state.items.map(async item => {
      const p = await getProduct(item.product_id);
      return {...item, adet_fiyati:Number(p.liste_fiyati), koli_fiyati:Number(p.koli_fiyati), dip_fiyat:Number(p.dip_fiyat), koli_ici_adet:Number(p.koli_ici_adet), stand_aktif:Number(p.stand_aktif), stand_ici_adet:p.stand_ici_adet === null ? null : Number(p.stand_ici_adet), koli_adedi:item.adet/Number(p.koli_ici_adet), kdv_orani:Number(p.kdv_orani), stok:Number(p.stok)};
    }));
    for (const item of updated) {
      const error = validateCartItem(item);
      if (error) throw new Error(`${item.urun_adi}: ${error}`);
    }
    for (const item of updated) dispatch({type:'item',item});
  };
  return <CartContext.Provider value={{...state, addItem, refreshPrices,
    setCustomer:(id,name)=>dispatch({type:'customer',id,name}),
    setVadeGun:days=>dispatch({type:'term',days}),
    removeItem:id=>dispatch({type:'remove',id}), clearCart:()=>dispatch({type:'clear'}),
    netTutar:lineNet, genelToplam:()=>total(state.items)
  }}>{children}</CartContext.Provider>;
}
export function useCart() { const ctx = useContext(CartContext); if (!ctx) throw new Error('CartProvider eksik.'); return ctx; }
