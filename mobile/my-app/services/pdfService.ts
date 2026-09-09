import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

import { getToken, API_BASE_URL, invalidateSession } from './api';
import { Platform } from 'react-native';


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

  if (Platform.OS === 'web') {
    const response = await fetch(API_BASE_URL + '/orders/pdf.php?id=' + orderId, {headers:{Authorization:'Bearer '+token}});
    if (response.status === 401) await invalidateSession();
    if (!response.ok || !response.headers.get('content-type')?.includes('application/pdf')) throw new Error('PDF alınamadı.');
    const url = URL.createObjectURL(await response.blob());
    const link = document.createElement('a'); link.href=url; link.download='Siparis-'+safeOrderNo+'.pdf'; link.click();
    setTimeout(()=>URL.revokeObjectURL(url), 60000); return;
  }
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

  if (result.status === 401) await invalidateSession();
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