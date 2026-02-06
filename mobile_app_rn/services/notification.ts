import messaging from '@react-native-firebase/messaging';
import { Alert, Platform } from 'react-native';
import api from './api';

export async function requestUserPermission() {
    const authStatus = await messaging().requestPermission();
    const enabled =
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

    if (enabled) {
        console.log('Authorization status:', authStatus);
        return true;
    }
    return false;
}

export async function getFCMToken() {
    try {
        const token = await messaging().getToken();
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

export const notificationListener = () => {
    messaging().onNotificationOpenedApp(remoteMessage => {
        console.log(
            'Notification caused app to open from background state:',
            remoteMessage.notification,
        );
    });

    messaging()
        .getInitialNotification()
        .then(remoteMessage => {
            if (remoteMessage) {
                console.log(
                    'Notification caused app to open from quit state:',
                    remoteMessage.notification,
                );
            }
        });

    const unsubscribe = messaging().onMessage(async remoteMessage => {
        console.log('A new FCM message arrived!', JSON.stringify(remoteMessage));
        // You can show a local notification here if needed
        // Alert.alert('New Message', remoteMessage.notification?.body);
    });

    return unsubscribe;
};
