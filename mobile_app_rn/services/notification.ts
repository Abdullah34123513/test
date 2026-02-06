import '@react-native-firebase/app';
import { getMessaging, getToken, onMessage, onNotificationOpenedApp, getInitialNotification, setBackgroundMessageHandler } from '@react-native-firebase/messaging';
import { Alert, Platform } from 'react-native';
import api from './api';

export async function requestUserPermission() {
    const messaging = getMessaging();
    const authStatus = await messaging.requestPermission();
    const enabled =
        authStatus === 1 || // AUTHORIZED
        authStatus === 2;   // PROVISIONAL

    if (enabled) {
        console.log('Authorization status:', authStatus);
        return true;
    }
    return false;
}

export async function getFCMToken() {
    try {
        const messaging = getMessaging();
        const token = await getToken(messaging);
        if (token) {
            console.log('FCM Token:', token);
            // Send token to backend
            await updateFCMTokenOnBackend(token);
            return token;
        }
    } catch (error) {
        console.error('Error getting FCM token:', error);
    }
    return null;
}

async function updateFCMTokenOnBackend(token: string) {
    try {
        await api.post('/update-fcm-token', { fcm_token: token });
        console.log('FCM Token updated successfully on backend');
    } catch (error) {
        console.error('Failed to update FCM token on backend:', error);
    }
}

export const notificationListener = (onNotificationOpen?: (remoteMessage: any) => void) => {
    const messaging = getMessaging();

    const unsubscribeOpenedApp = onNotificationOpenedApp(messaging, remoteMessage => {
        console.log(
            'Notification caused app to open from background state:',
            remoteMessage.data,
        );
        if (onNotificationOpen) {
            onNotificationOpen(remoteMessage);
        }
    });

    getInitialNotification(messaging)
        .then(remoteMessage => {
            if (remoteMessage) {
                console.log(
                    'Notification caused app to open from quit state:',
                    remoteMessage.data,
                );
                if (onNotificationOpen) {
                    onNotificationOpen(remoteMessage);
                }
            }
        });

    const unsubscribeMessage = onMessage(messaging, async remoteMessage => {
        console.log('A new FCM message arrived!', JSON.stringify(remoteMessage));
        // For foreground, we could show a toast or just let the chat screen handle it via listeners
    });

    return () => {
        unsubscribeOpenedApp();
        unsubscribeMessage();
    };
};
