import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

import { getToken } from './api';

const API_BASE_URL =
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  'http://192.168.18.56/B/api';

export async function downloadAndShareOrderPdf(
  orderId: number,
  siparisNo: string
): Promise<void> {
  const token = await getToken();

  if (!token) {
    throw new Error(
      'Oturum bulunamadı. Lütfen tekrar giriş yapın.'
    );
  }

  const safeOrderNo = siparisNo.replace(
    /[^a-zA-Z0-9_-]/g,
    '_'
  );

  const cacheDirectory =
    FileSystem.cacheDirectory;

  if (!cacheDirectory) {
    throw new Error(
      'Cihazın geçici dosya alanına erişilemedi.'
    );
  }

  const fileUri =
    `${cacheDirectory}Siparis-${safeOrderNo}.pdf`;

  const url =
    `${API_BASE_URL}/orders/pdf.php?id=${orderId}`;

  console.log('📄 PDF isteniyor:', url);

  const result =
    await FileSystem.downloadAsync(
      url,
      fileUri,
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    );

  console.log(
    '📥 PDF cevabı:',
    result.status,
    result.uri
  );

  if (result.status !== 200) {
    throw new Error(
      `PDF alınamadı. Sunucu HTTP ${result.status} döndürdü.`
    );
  }

  const info =
    await FileSystem.getInfoAsync(result.uri);

  if (!info.exists) {
    throw new Error(
      'PDF cihazda oluşturulamadı.'
    );
  }

  const sharingAvailable =
    await Sharing.isAvailableAsync();

  if (!sharingAvailable) {
    throw new Error(
      'Bu cihazda PDF paylaşma/açma özelliği kullanılamıyor.'
    );
  }

  await Sharing.shareAsync(
    result.uri,
    {
      mimeType: 'application/pdf',
      dialogTitle:
        `Sipariş ${siparisNo}`,
      UTI: 'com.adobe.pdf',
    }
  );
}