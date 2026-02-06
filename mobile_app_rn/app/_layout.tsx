import '@react-native-firebase/app';
import { DarkTheme, DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { Stack, useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { View, ActivityIndicator, Platform } from 'react-native';
import { Colors } from '../constants/theme';
import { requestUserPermission, getFCMToken, notificationListener } from '../services/notification';
import { getMessaging, setBackgroundMessageHandler } from '@react-native-firebase/messaging';

// Register background handler
setBackgroundMessageHandler(getMessaging(), async remoteMessage => {
  console.log('Message handled in the background!', remoteMessage);
});

// Settings
export const unstable_settings = {
  initialRouteName: 'auth/login',
};

function RootLayoutNav() {
  const { user, isLoading } = useAuth();
  const segments = useSegments();
  const router = useRouter();
  const colorScheme = useColorScheme();

  useEffect(() => {
    if (isLoading) return;

    if (user) {
      const setupNotifications = async () => {
        const hasPermission = await requestUserPermission();
        if (hasPermission) {
          await getFCMToken();
        }
      };
      setupNotifications();
    }
  }, [isLoading, user]);

  useEffect(() => {
    if (isLoading || !user) return;

    const handleNotificationOpen = (remoteMessage: any) => {
      const senderId = remoteMessage.data?.sender_id;
      if (senderId) {
        console.log('Navigating to chat with:', senderId);
        // Delay slightly to ensure layout is ready
        setTimeout(() => {
          router.push(`/chat/${senderId}`);
        }, 500);
      }
    };

    const unsubscribe = notificationListener(handleNotificationOpen);
    return () => {
      unsubscribe();
    };
  }, [isLoading, user]);

  useEffect(() => {
    if (isLoading) return;

    const inAuthGroup = segments[0] === 'auth';

    if (!user && !inAuthGroup) {
      // Redirect to login if not authenticated and not in auth group
      router.replace('/auth/login');
    } else if (user && inAuthGroup) {
      // Redirect to tabs if authenticated and in auth group
      router.replace('/(tabs)');
    }
  }, [user, isLoading, segments]);

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.light.background }}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  return (
    <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
      <Stack>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="chat/[id]" options={{ headerShown: false }} />
        <Stack.Screen name="auth/login" options={{ headerShown: false, animation: 'fade' }} />
        <Stack.Screen name="auth/register" options={{ headerShown: false, animation: 'fade' }} />
        <Stack.Screen name="modal" options={{ presentation: 'modal', title: 'Modal' }} />
      </Stack>
      <StatusBar style="auto" />
    </ThemeProvider>
  );
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <RootLayoutNav />
    </AuthProvider>
  );
}
