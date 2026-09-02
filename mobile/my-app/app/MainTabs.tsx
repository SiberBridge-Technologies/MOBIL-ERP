import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import CariAramaScreen from './CariAramaScreen';
import SiparislerimScreen from './SiparislerimScreen';
import ProfilScreen from './ProfilScreen';
import { colors, fontSize } from '../constants/theme';

// Dokümandaki ana çalışan sekmeleri: Cariler (Ürünler alt akışta), Sipariş, Profil
const Tab = createBottomTabNavigator();

export default function MainTabs() {
  const insets = useSafeAreaInsets();

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarLabelStyle: { fontSize: fontSize.xs, fontWeight: '600', marginBottom: 2 },
        tabBarStyle: {
          backgroundColor: colors.card,
          borderTopColor: colors.border,
          borderTopWidth: 1,
          height: 58 + insets.bottom,
          paddingBottom: Math.max(insets.bottom, 8),
          paddingTop: 8,
        },
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
