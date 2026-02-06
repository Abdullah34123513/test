import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { Avatar } from './Avatar';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

interface ChatListItemProps {
    id: string;
    name: string;
    avatarUri: string;
    lastMessage: string;
    timestamp: string;
    unreadCount?: number;
    isOnline?: boolean;
    isHighlighted?: boolean;
    messageType?: 'text' | 'image' | 'voice';
    voiceDuration?: string;
    senderName?: string;
    onPress?: () => void;
}

export const ChatListItem: React.FC<ChatListItemProps> = ({
    id,
    name,
    avatarUri,
    lastMessage,
    timestamp,
    unreadCount = 0,
    isOnline = false,
    isHighlighted = false,
    messageType = 'text',
    voiceDuration,
    senderName,
    onPress,
}) => {
    const router = useRouter();

    const handlePress = () => {
        if (onPress) {
            onPress();
        } else {
            router.push(`/chat/${id}`);
        }
    };

    const renderLastMessage = () => {
        if (messageType === 'image') {
            return (
                <View style={styles.mediaMessage}>
                    <Text style={styles.mediaIcon}>🖼️</Text>
                    <Text style={styles.lastMessage}>Photo</Text>
                </View>
            );
        }
        if (messageType === 'voice') {
            return (
                <View style={styles.mediaMessage}>
                    <Text style={styles.mediaIcon}>🎤</Text>
                    <Text style={styles.lastMessage}>{voiceDuration || '0:00'}</Text>
                </View>
            );
        }
        if (senderName) {
            return (
                <Text style={styles.lastMessage} numberOfLines={1}>
                    <Text style={styles.senderName}>{senderName}: </Text>
                    {lastMessage}
                </Text>
            );
        }
        return (
            <Text style={styles.lastMessage} numberOfLines={1}>
                {lastMessage}
            </Text>
        );
    };

    return (
        <TouchableOpacity style={styles.container} onPress={handlePress} activeOpacity={0.7}>
            <Avatar uri={avatarUri} size={56} isOnline={isOnline} />

            <View style={styles.content}>
                <View style={styles.topRow}>
                    <Text style={styles.name} numberOfLines={1}>{name}</Text>
                    <Text style={[styles.timestamp, isHighlighted && styles.timestampHighlighted]}>
                        {timestamp}
                    </Text>
                </View>

                <View style={styles.bottomRow}>
                    <View style={styles.messageContainer}>
                        {renderLastMessage()}
                    </View>
                    {unreadCount > 0 && (
                        <View style={styles.badge}>
                            <Text style={styles.badgeText}>{unreadCount}</Text>
                        </View>
                    )}
                </View>
            </View>
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    container: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: Spacing.md,
        paddingVertical: Spacing.sm,
        minHeight: 80,
        gap: Spacing.md,
    },
    content: {
        flex: 1,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.borderLight,
        paddingBottom: Spacing.sm,
    },
    topRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'baseline',
        marginBottom: 4,
    },
    bottomRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        gap: Spacing.sm,
    },
    name: {
        fontSize: FontSize.lg,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
        flex: 1,
    },
    timestamp: {
        fontSize: FontSize.xs,
        color: Colors.light.textMuted,
    },
    timestampHighlighted: {
        color: Colors.primary,
        fontWeight: FontWeight.semibold,
    },
    messageContainer: {
        flex: 1,
    },
    lastMessage: {
        fontSize: FontSize.sm,
        color: Colors.light.textSecondary,
    },
    senderName: {
        fontWeight: FontWeight.medium,
        color: Colors.light.text,
    },
    mediaMessage: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    mediaIcon: {
        fontSize: FontSize.sm,
    },
    badge: {
        minWidth: 20,
        height: 20,
        borderRadius: 10,
        backgroundColor: Colors.primary,
        alignItems: 'center',
        justifyContent: 'center',
        paddingHorizontal: 6,
    },
    badgeText: {
        fontSize: FontSize.xs,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
    },
});
