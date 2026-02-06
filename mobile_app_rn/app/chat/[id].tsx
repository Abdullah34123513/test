import React, { useState, useEffect, useRef } from 'react';
import { View, ScrollView, StyleSheet, KeyboardAvoidingView, Platform, ActivityIndicator, Text } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { ChatHeader } from '../../components/ui/ChatHeader';
import { MessageBubble } from '../../components/ui/MessageBubble';
import { ChatInput } from '../../components/ui/ChatInput';
import { Colors, FontSize } from '../../constants/theme';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { socket } from '../../services/socket';

interface Message {
    id: number;
    sender_id: number;
    receiver_id: number;
    message: string;
    type: 'text' | 'image' | 'audio';
    file_path?: string;
    is_read: boolean;
    created_at: string;
}

export default function ChatScreen() {
    const router = useRouter();
    const params = useLocalSearchParams();
    const { user } = useAuth();
    const [messages, setMessages] = useState<Message[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const scrollViewRef = useRef<ScrollView>(null);

    const fetchMessages = async () => {
        try {
            const response = await api.get<Message[]>(`/messages/${params.id}`);
            setMessages(response.data);
        } catch (error) {
            console.error('Error fetching messages:', error);
        } finally {
            setIsLoading(false);
            setTimeout(() => scrollViewRef.current?.scrollToEnd({ animated: true }), 100);
        }
    };

    useEffect(() => {
        if (!user) return;

        fetchMessages();

        socket.connect();
        socket.on('message', (newMessage: Message) => {
            const isRelevant =
                (newMessage.sender_id.toString() === params.id && newMessage.receiver_id === user?.id) ||
                (newMessage.sender_id === user?.id && newMessage.receiver_id.toString() === params.id);

            if (isRelevant) {
                setMessages((prev) => [...prev, newMessage]);
                setTimeout(() => scrollViewRef.current?.scrollToEnd({ animated: true }), 100);
            }
        });

        return () => {
            socket.off('message');
            socket.disconnect();
        };
    }, [params.id, user]);

    const handleSend = async (content: string) => {
        try {
            const response = await api.post<Message>('/messages', {
                receiver_id: params.id,
                type: 'text',
                message: content,
            });

            setMessages((prev) => [...prev, response.data]);
            setTimeout(() => scrollViewRef.current?.scrollToEnd({ animated: true }), 100);

            socket.emit('send_message', response.data);
        } catch (error) {
            console.error('Error sending message:', error);
        }
    };

    return (
        <KeyboardAvoidingView
            style={styles.container}
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}
        >
            <ChatHeader
                name={`User ${params.id}`}
                avatarUri={`https://ui-avatars.com/api/?name=User+${params.id}&background=random`}
                isOnline={true}
                onBack={() => router.back()}
                onMore={() => console.log('More options')}
            />

            <ScrollView
                ref={scrollViewRef}
                style={styles.messagesContainer}
                contentContainerStyle={styles.messagesContent}
                showsVerticalScrollIndicator={false}
                onContentSizeChange={() => scrollViewRef.current?.scrollToEnd({ animated: true })}
            >
                {isLoading ? (
                    <ActivityIndicator size="small" color={Colors.primary} style={{ marginTop: 20 }} />
                ) : messages.length > 0 ? (
                    messages.map((message) => (
                        <MessageBubble
                            key={message.id}
                            type={message.sender_id === user?.id ? 'sent' : 'received'}
                            content={message.message || ''}
                            timestamp={new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                            messageType={message.type === 'text' ? 'text' : message.type === 'image' ? 'image' : 'voice'}
                            imageUri={message.file_path ? `${api.defaults.baseURL?.replace('/api', '')}/storage/${message.file_path}` : undefined}
                            isRead={message.is_read}
                        />
                    ))
                ) : (
                    <View style={styles.emptyContainer}>
                        <Text style={styles.emptyText}>No messages yet. Say hi!</Text>
                    </View>
                )}
            </ScrollView>

            <ChatInput
                onSend={handleSend}
                onAttach={() => console.log('Attach')}
                onCamera={() => console.log('Camera')}
                onVoice={() => console.log('Voice')}
                onEmoji={() => console.log('Emoji')}
                onAdd={() => console.log('Add')}
            />
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f0f2f0',
    },
    messagesContainer: {
        flex: 1,
    },
    messagesContent: {
        padding: 16,
        paddingBottom: 8,
    },
    emptyContainer: {
        flex: 1,
        alignItems: 'center',
        paddingTop: 50,
    },
    emptyText: {
        color: Colors.light.textMuted,
        fontSize: FontSize.base,
    },
});
