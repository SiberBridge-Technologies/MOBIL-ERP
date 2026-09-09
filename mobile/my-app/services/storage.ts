import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';
export async function readStorage(key: string): Promise<string | null> {
  return Platform.OS === 'web' ? window.sessionStorage.getItem(key) : SecureStore.getItemAsync(key);
}
export async function writeStorage(key: string, value: string): Promise<void> {
  if (Platform.OS === 'web') window.sessionStorage.setItem(key, value);
  else await SecureStore.setItemAsync(key, value);
}
export async function deleteStorage(key: string): Promise<void> {
  if (Platform.OS === 'web') window.sessionStorage.removeItem(key);
  else await SecureStore.deleteItemAsync(key);
}
