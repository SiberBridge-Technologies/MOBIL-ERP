import { Alert as NativeAlert, Platform, type AlertButton } from 'react-native';
export const Alert = {
  alert(title: string, message?: string, buttons?: AlertButton[]) {
    if (Platform.OS !== 'web') { NativeAlert.alert(title, message, buttons); return; }
    const text = title + (message ? '\n' + message : '');
    if (buttons && buttons.length > 1) {
      const ok = window.confirm(text);
      const button = ok ? buttons.find(b => b.style !== 'cancel') : buttons.find(b => b.style === 'cancel');
      button?.onPress?.();
    } else { window.alert(text); buttons?.[0]?.onPress?.(); }
  }
};
