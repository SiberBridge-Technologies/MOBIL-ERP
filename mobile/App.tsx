import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StatusBar } from 'expo-status-bar';

import { CartProvider } from './services/CartContext';
import LoginScreen from './app/LoginScreen';
import MainTabs from './app/MainTabs';
import CariSayfasiScreen from './app/CariSayfasiScreen';
import UrunDetayScreen from './app/UrunDetayScreen';
import SepetScreen from './app/SepetScreen';
import SiparisFormuScreen from './app/SiparisFormuScreen';

// Figma akışı: Login -> [Cariler/Siparişlerim/Profil sekmeleri] -> Cari Sayfası -> (Sepet <-> Ürün Detay) -> Sipariş Formu
export type RootStackParamList = {
  Login: undefined;
  MainTabs: undefined;
  CariSayfasi: { customerId: number };
  Sepet: undefined;
  UrunDetay: { productId: number };
  SiparisFormu: undefined;
};

const Stack = createNativeStackNavigator<RootStackParamList>();

export default function App() {
  return (
    <CartProvider>
      <StatusBar style="dark" />
      <NavigationContainer>
        <Stack.Navigator
          initialRouteName="Login"
          screenOptions={{ headerShown: false }}
        >
          <Stack.Screen name="Login" component={LoginScreen} />
          <Stack.Screen name="MainTabs" component={MainTabs} />
          <Stack.Screen name="CariSayfasi" component={CariSayfasiScreen} />
          <Stack.Screen name="Sepet" component={SepetScreen} />
          <Stack.Screen name="UrunDetay" component={UrunDetayScreen} />
          <Stack.Screen name="SiparisFormu" component={SiparisFormuScreen} />
        </Stack.Navigator>
      </NavigationContainer>
    </CartProvider>
  );
}
