import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Image } from 'react-native';
import { Phone, Video, PhoneIncoming, PhoneOutgoing, PhoneMissed } from 'lucide-react-native';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

interface CallItemProps {
    id: string;
    name: string;
    avatarUri: string;
    timestamp: string;
    type: 'incoming' | 'outgoing' | 'missed';
    callType: 'voice' | 'video';
    duration?: string;
    onPress?: () => void;
    onActionPress?: () => void;
}

export const CallItem: React.FC<CallItemProps> = ({
    name,
    avatarUri,
    timestamp,
    type,
    callType,
    duration,
    onPress,
    onActionPress,
}) => {
    const isMissed = type === 'missed';

    const getStatusIcon = () => {
        switch (type) {
            case 'incoming': return <PhoneIncoming size={14} color={Colors.primary} />;
            case 'outgoing': return <PhoneOutgoing size={14} color={Colors.light.textMuted} />;
            case 'missed': return <PhoneMissed size={14} color="#ef4444" />;
        }
    };

    const getStatusColor = () => {
        if (isMissed) return '#ef4444';
        return Colors.light.textSecondary;
    };

    return (
        <TouchableOpacity style={styles.container} onPress={onPress} activeOpacity={0.7}>
            <View style={styles.leftSection}>
                <View style={styles.avatarContainer}>
                    <Image source={{ uri: avatarUri }} style={styles.avatar} />
                </View>
                <View style={styles.info}>
                    <Text style={[styles.name, isMissed && styles.missedName]} numberOfLines={1}>
                        {name}
                    </Text>
                    <View style={styles.statusRow}>
                        {getStatusIcon()}
                        <Text style={[styles.statusText, { color: getStatusColor() }]}>
                            {type.charAt(0).toUpperCase() + type.slice(1)}, {timestamp}
                            {duration ? ` (${duration})` : ''}
                        </Text>
                    </View>
                </View>
            </View>

            <TouchableOpacity style={styles.actionButton} onPress={onActionPress}>
                {callType === 'video' ? (
                    <Video size={20} color={Colors.light.textMuted} />
                ) : (
                    <Phone size={20} color={Colors.light.textMuted} />
                )}
            </TouchableOpacity>
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    container: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingVertical: 12,
        minHeight: 76,
    },
    leftSection: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 16,
        flex: 1,
    },
    avatarContainer: {
        width: 56,
        height: 56,
        borderRadius: 28,
        borderWidth: 1,
        borderColor: Colors.light.border,
        padding: 1,
    },
    avatar: {
        width: '100%',
        height: '100%',
        borderRadius: 27,
    },
    info: {
        justifyContent: 'center',
        flex: 1,
    },
    name: {
        fontSize: 16,
        fontWeight: '600',
        color: Colors.light.text,
    },
    missedName: {
        color: '#ef4444',
    },
    statusRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        marginTop: 2,
    },
    statusText: {
        fontSize: 12,
        fontWeight: '400',
    },
    actionButton: {
        width: 44,
        height: 44,
        borderRadius: 22,
        alignItems: 'center',
        justifyContent: 'center',
    },
});
