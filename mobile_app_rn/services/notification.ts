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

import * as Notifications from 'expo-notifications';
import { messageStore } from './messageStore';

// Configure expo-notifications
Notifications.setNotificationHandler({
    handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: true,
        shouldShowBanner: true,
        shouldShowList: true,
    }),
});

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
        console.log('A new FCM message arrived (Foreground)!', JSON.stringify(remoteMessage, null, 2));

        if (remoteMessage.data) {
            const messageData = {
                id: parseInt(remoteMessage.data.message_id as string) || Date.now(),
                sender_id: parseInt(remoteMessage.data.sender_id as string),
                receiver_id: 0,
                message: (remoteMessage.data.body as string) || (remoteMessage.notification?.body) || '',
                type: (remoteMessage.data.chat_type as any) || 'text',
                created_at: new Date().toISOString(),
                is_read: false,
            };

            // Save to local store
            await messageStore.saveMessage(messageData as any);
        }

        // Show foreground notification if desired
        const title = (remoteMessage.notification?.title || remoteMessage.data?.title || 'New Message') as string;
        const body = (remoteMessage.notification?.body || remoteMessage.data?.body || '') as string;

        await Notifications.scheduleNotificationAsync({
            content: {
                title,
                body,
                data: remoteMessage.data,
            },
            trigger: null,
        });
    });

    return () => {
        unsubscribeOpenedApp();
        unsubscribeMessage();
    };
};
