import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { onSessionExpired } from './api';
import { getSavedUser } from './authService';
import type { LoginResponse } from './authService';

type User = LoginResponse['user'];

interface AuthContextValue {
  user: User | null;
  setUser: (user: User | null) => void;
  loading: boolean;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Uygulama açılışında (örn. telefon kapatılıp açıldığında) daha önce
  // kaydedilmiş kullanıcı bilgisini SecureStore'dan geri yükle.
  useEffect(() => {
    onSessionExpired(() => setUser(null));
    (async () => {
      try {
        const saved = await getSavedUser();
        setUser(saved);
      } finally {
        setLoading(false);
      }
    })();
    return () => onSessionExpired(undefined);
  }, []);

  return (
    <AuthContext.Provider value={{ user, setUser, loading }}>{children}</AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth, AuthProvider içinde kullanılmalıdır.');
  return ctx;
}
