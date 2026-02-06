import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { ChevronLeft, MoreVertical } from 'lucide-react-native';
import { Avatar } from './Avatar';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

interface ChatHeaderProps {
    name: string;
    avatarUri: string;
    isOnline?: boolean;
    onBack?: () => void;
    onMore?: () => void;
}

export const ChatHeader: React.FC<ChatHeaderProps> = ({
    name,
    avatarUri,
    isOnline = false,
    onBack,
    onMore,
}) => {
    return (
        <View style={styles.container}>
            <View style={styles.leftSection}>
                <TouchableOpacity style={styles.backButton} onPress={onBack}>
                    <ChevronLeft size={28} color={Colors.light.textSecondary} />
                </TouchableOpacity>

                <Avatar uri={avatarUri} size={40} isOnline={isOnline} />

                <View style={styles.userInfo}>
                    <Text style={styles.name}>{name}</Text>
                    {isOnline && <Text style={styles.status}>online</Text>}
                </View>
            </View>

            <View style={styles.actions}>
                <TouchableOpacity style={styles.actionButton} onPress={onMore}>
                    <MoreVertical size={20} color={Colors.light.textSecondary} />
                </TouchableOpacity>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        backgroundColor: Colors.light.surface,
        paddingHorizontal: Spacing.md,
        paddingTop: 48,
        paddingBottom: 12,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.border,
    },
    leftSection: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        flex: 1,
    },
    backButton: {
        padding: 4,
        marginLeft: -8,
    },
    userInfo: {
        flexDirection: 'column',
        marginLeft: 4,
    },
    name: {
        fontSize: FontSize.base,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
    },
    status: {
        fontSize: FontSize.xs,
        fontWeight: FontWeight.medium,
        color: Colors.primary,
        marginTop: 2,
    },
    actions: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 16,
    },
    actionButton: {
        padding: 4,
    },
});
