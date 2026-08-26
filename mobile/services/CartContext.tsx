import React, { createContext, useContext, useState, ReactNode } from 'react';
import { CartItem } from './orderService';

interface CartContextValue {
  customerId: number | null;
  customerName: string | null;
  items: CartItem[];
  setCustomer: (id: number, name: string) => void;
  addItem: (item: CartItem) => void;
  removeItem: (productId: number) => void;
  clearCart: () => void;
  netTutar: (item: CartItem) => number;
  genelToplam: () => number;
}

const CartContext = createContext<CartContextValue | undefined>(undefined);

export function CartProvider({ children }: { children: ReactNode }) {
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customerName, setCustomerName] = useState<string | null>(null);
  const [items, setItems] = useState<CartItem[]>([]);

  const setCustomer = (id: number, name: string) => {
    setCustomerId(id);
    setCustomerName(name);
  };

  const addItem = (item: CartItem) => {
    setItems((prev) => [...prev.filter((i) => i.product_id !== item.product_id), item]);
  };

  const removeItem = (productId: number) => {
    setItems((prev) => prev.filter((i) => i.product_id !== productId));
  };

  const clearCart = () => {
    setItems([]);
  };

  // Not: bu istemci taraflı hesap yalnızca ÖNİZLEME amaçlıdır.
  // Kesin tutar sipariş oluşturulurken sunucu tarafında yeniden hesaplanır.
  const netTutar = (item: CartItem) => {
    const netFiyat =
      item.koli_fiyati *
      (1 - item.iskonto_1 / 100) *
      (1 - item.iskonto_2 / 100) *
      (1 - item.iskonto_3 / 100);
    return netFiyat * item.koli_adedi;
  };

  const genelToplam = () => items.reduce((sum, item) => sum + netTutar(item), 0);

  return (
    <CartContext.Provider
      value={{ customerId, customerName, items, setCustomer, addItem, removeItem, clearCart, netTutar, genelToplam }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart(): CartContextValue {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart, CartProvider içinde kullanılmalıdır.');
  return ctx;
}
