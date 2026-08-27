import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import CariAramaScreen from './CariAramaScreen';
import SiparislerimScreen from './SiparislerimScreen';
import ProfilScreen from './ProfilScreen';
import { colors } from '../constants/theme';

// Dokümandaki ana çalışan sekmeleri: Cariler (Ürünler alt akışta), Sipariş, Profil
const Tab = createBottomTabNavigator();

export default function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarIcon: ({ color, size }) => {
          const iconMap: Record<string, keyof typeof Ionicons.glyphMap> = {
            CariArama: 'business-outline',
            Siparislerim: 'receipt-outline',
            Profil: 'person-outline',
          };
          return <Ionicons name={iconMap[route.name]} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="CariArama" component={CariAramaScreen} options={{ title: 'Cariler' }} />
      <Tab.Screen name="Siparislerim" component={SiparislerimScreen} options={{ title: 'Siparişlerim' }} />
      <Tab.Screen name="Profil" component={ProfilScreen} options={{ title: 'Profil' }} />
    </Tab.Navigator>
  );
}
