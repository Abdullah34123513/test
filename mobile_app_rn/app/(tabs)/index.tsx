import React, { useState, useEffect, useCallback } from 'react';
import { View, ScrollView, StyleSheet, SafeAreaView, StatusBar, RefreshControl, ActivityIndicator, Text } from 'react-native';
import { useRouter } from 'expo-router';
import { TopAppBar } from '../../components/ui/TopAppBar';
import { TabBar } from '../../components/ui/TabBar';
import { ChatListItem } from '../../components/ui/ChatListItem';
import { FloatingActionButton } from '../../components/ui/FloatingActionButton';
import { Colors, FontSize } from '../../constants/theme';
import api from '../../services/api';
import { useAuth } from '../../context/AuthContext';

const TABS = [
  { key: 'chats', label: 'Chats' },
  { key: 'status', label: 'Status' },
];

export default function ChatsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('chats');
  const [chats, setChats] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const fetchChats = useCallback(async (showLoading = true) => {
    if (showLoading) setIsLoading(true);
    try {
      const response = await api.get('/users');
      setChats(response.data);
    } catch (error) {
      console.error('Error fetching chats:', error);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    if (user) {
      fetchChats();
    }
  }, [fetchChats, user]);

  const onRefresh = () => {
    setIsRefreshing(true);
    fetchChats(false);
  };

  const handleTabPress = (key: string) => {
    setActiveTab(key);
    if (key === 'status') {
      router.push('/updates');
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor={Colors.light.surface} />

      {/* Header */}
      <View style={styles.header}>
        <TopAppBar
          title="Connect"
          onCameraPress={() => console.log('Camera')}
          onSearchPress={() => console.log('Search')}
        />
        <TabBar
          tabs={TABS}
          activeTab={activeTab}
          onTabPress={handleTabPress}
        />
      </View>

      {/* Chat List */}
      <ScrollView
        style={styles.chatList}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} colors={[Colors.primary]} />
        }
      >
        {isLoading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color={Colors.primary} />
          </View>
        ) : chats.length > 0 ? (
          chats.map((chat: any) => (
            <ChatListItem
              key={chat.id}
              id={chat.id.toString()}
              name={chat.name}
              avatarUri={chat.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(chat.name)}&background=random`}
              lastMessage={chat.last_message || "No messages yet"}
              timestamp={chat.last_message_time || ""}
              unreadCount={chat.unread_count}
              isHighlighted={chat.unread_count > 0}
              onPress={() => router.push({ pathname: '/chat/[id]', params: { id: chat.id.toString() } })}
            />
          ))
        ) : (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>No conversations yet</Text>
          </View>
        )}
      </ScrollView>

      {/* FAB */}
      <FloatingActionButton onPress={() => console.log('New Chat')} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.light.surface,
  },
  header: {
    backgroundColor: Colors.light.surface,
  },
  chatList: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    paddingTop: 50,
    alignItems: 'center',
  },
  emptyContainer: {
    flex: 1,
    paddingTop: 50,
    alignItems: 'center',
  },
  emptyText: {
    color: Colors.light.textMuted,
    fontSize: FontSize.base,
  }
});
