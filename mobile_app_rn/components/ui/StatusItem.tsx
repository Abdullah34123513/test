import React from 'react';
import { View, Text, Image, TouchableOpacity, StyleSheet } from 'react-native';
import { Plus, Camera, Edit2 } from 'lucide-react-native';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

interface StatusItemProps {
    id: string;
    name: string;
    avatarUri: string;
    timestamp: string;
    preview?: string;
    isViewed?: boolean;
    onPress?: () => void;
}

export const StatusItem: React.FC<StatusItemProps> = ({
    name,
    avatarUri,
    timestamp,
    preview,
    isViewed = false,
    onPress,
}) => {
    return (
        <TouchableOpacity
            style={[styles.container, isViewed && styles.viewedContainer]}
            onPress={onPress}
            activeOpacity={0.7}
        >
            <View style={[styles.avatarRing, isViewed ? styles.viewedRing : styles.unviewedRing]}>
                <Image source={{ uri: avatarUri }} style={styles.avatar} />
            </View>

            <View style={styles.content}>
                <View style={styles.topRow}>
                    <Text style={styles.name}>{name}</Text>
                    <Text style={styles.timestamp}>{timestamp}</Text>
                </View>
                {preview && (
                    <Text style={styles.preview} numberOfLines={1}>{preview}</Text>
                )}
            </View>
        </TouchableOpacity>
    );
};

interface MyStatusProps {
    avatarUri: string;
    onAddStatus?: () => void;
    onCamera?: () => void;
    onEdit?: () => void;
}

export const MyStatus: React.FC<MyStatusProps> = ({
    avatarUri,
    onAddStatus,
    onCamera,
    onEdit,
}) => {
    return (
        <TouchableOpacity style={styles.myStatusContainer} onPress={onAddStatus} activeOpacity={0.8}>
            <View style={styles.myAvatarContainer}>
                <Image source={{ uri: avatarUri }} style={styles.myAvatar} />
                <View style={styles.addBadge}>
                    <Plus size={12} color={Colors.light.surface} strokeWidth={4} />
                </View>
            </View>

            <View style={styles.myStatusInfo}>
                <Text style={styles.myStatusTitle}>My Status</Text>
                <Text style={styles.myStatusSubtitle}>Tap to add status update</Text>
            </View>

            <View style={styles.myStatusActions}>
                <TouchableOpacity style={styles.actionButton} onPress={onCamera}>
                    <Camera size={18} color={Colors.primary} />
                </TouchableOpacity>
                <TouchableOpacity style={styles.actionButton} onPress={onEdit}>
                    <Edit2 size={18} color={Colors.primary} />
                </TouchableOpacity>
            </View>
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    container: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 12,
        paddingVertical: 12,
        gap: 16,
        borderRadius: 12,
    },
    viewedContainer: {
        opacity: 0.6,
    },
    avatarRing: {
        width: 64,
        height: 64,
        borderRadius: 32,
        padding: 3,
        alignItems: 'center',
        justifyContent: 'center',
    },
    unviewedRing: {
        borderWidth: 3,
        borderColor: Colors.primary,
    },
    viewedRing: {
        borderWidth: 3,
        borderColor: Colors.light.border,
    },
    avatar: {
        width: '100%',
        height: '100%',
        borderRadius: 28,
    },
    content: {
        flex: 1,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.border,
        paddingBottom: 12,
    },
    topRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 4,
    },
    name: {
        fontSize: FontSize.lg,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
    },
    timestamp: {
        fontSize: FontSize.xs,
        color: Colors.light.textMuted,
    },
    preview: {
        fontSize: FontSize.sm,
        color: Colors.light.textSecondary,
    },
    myStatusContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: Colors.light.surface,
        padding: 16,
        borderRadius: 16,
        gap: 16,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.05,
        shadowRadius: 4,
        elevation: 2,
    },
    myAvatarContainer: {
        position: 'relative',
    },
    myAvatar: {
        width: 56,
        height: 56,
        borderRadius: 28,
        borderWidth: 2,
        borderColor: Colors.light.border,
    },
    addBadge: {
        position: 'absolute',
        bottom: 0,
        right: 0,
        width: 20,
        height: 20,
        borderRadius: 10,
        backgroundColor: Colors.primary,
        alignItems: 'center',
        justifyContent: 'center',
        borderWidth: 2,
        borderColor: Colors.light.surface,
    },
    myStatusInfo: {
        flex: 1,
    },
    myStatusTitle: {
        fontSize: FontSize.lg,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
    },
    myStatusSubtitle: {
        fontSize: FontSize.sm,
        color: Colors.light.textSecondary,
        marginTop: 2,
    },
    myStatusActions: {
        flexDirection: 'row',
        gap: 8,
    },
    actionButton: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: `${Colors.primary}15`,
        alignItems: 'center',
        justifyContent: 'center',
    },
});
