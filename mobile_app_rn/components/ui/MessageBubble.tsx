import React from 'react';
import { View, Text, Image, StyleSheet } from 'react-native';
import { Play, CheckCheck } from 'lucide-react-native';
import { Colors, FontSize, Spacing } from '../../constants/theme';

interface MessageBubbleProps {
    type: 'sent' | 'received';
    content: string;
    timestamp: string;
    isRead?: boolean;
    messageType?: 'text' | 'image' | 'voice';
    imageUri?: string;
    voiceDuration?: string;
}

export const MessageBubble: React.FC<MessageBubbleProps> = ({
    type,
    content,
    timestamp,
    isRead = false,
    messageType = 'text',
    imageUri,
    voiceDuration,
}) => {
    const isSent = type === 'sent';

    const renderVoiceWaveform = () => (
        <View style={styles.waveformContainer}>
            <Play size={24} color={Colors.dark.background} fill={Colors.dark.background} />
            <View style={styles.waveform}>
                {[2, 4, 6, 3, 5, 4, 7, 3, 5, 2].map((height, index) => (
                    <View
                        key={index}
                        style={[
                            styles.waveformBar,
                            { height: height * 4, opacity: index === 2 ? 1 : 0.4 },
                        ]}
                    />
                ))}
            </View>
            <Text style={styles.voiceDuration}>{voiceDuration || '0:15'}</Text>
        </View>
    );

    const renderContent = () => {
        if (messageType === 'image' && imageUri) {
            return (
                <View style={[styles.imageBubble, !isSent && styles.receivedImageBubble]}>
                    <Image source={{ uri: imageUri }} style={styles.messageImage} />
                </View>
            );
        }

        if (messageType === 'voice') {
            return (
                <View style={[styles.bubble, isSent ? styles.sentBubble : styles.receivedBubble, styles.voiceBubble]}>
                    {renderVoiceWaveform()}
                </View>
            );
        }

        return (
            <View style={[styles.bubble, isSent ? styles.sentBubble : styles.receivedBubble]}>
                <Text style={[styles.messageText, isSent && styles.sentMessageText]}>
                    {content}
                </Text>
            </View>
        );
    };

    return (
        <View style={[styles.container, isSent && styles.sentContainer]}>
            <View style={styles.messageWrapper}>
                {renderContent()}
                <View style={[styles.timestampRow, isSent && styles.sentTimestampRow]}>
                    <Text style={styles.timestamp}>{timestamp}</Text>
                    {isSent && isRead && <CheckCheck size={14} color={Colors.primary} />}
                </View>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        maxWidth: '85%',
        marginBottom: Spacing.md,
    },
    sentContainer: {
        alignSelf: 'flex-end',
    },
    messageWrapper: {
        alignItems: 'flex-start',
    },
    bubble: {
        paddingHorizontal: Spacing.md,
        paddingVertical: 12,
        borderRadius: 20,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.05,
        shadowRadius: 2,
        elevation: 1,
    },
    sentBubble: {
        backgroundColor: Colors.primary,
        borderBottomRightRadius: 4,
    },
    receivedBubble: {
        backgroundColor: Colors.light.surface,
        borderBottomLeftRadius: 4,
    },
    messageText: {
        fontSize: FontSize.sm,
        lineHeight: 20,
        color: Colors.light.text,
    },
    sentMessageText: {
        color: Colors.dark.background,
    },
    timestampRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        marginTop: 4,
        marginLeft: 4,
    },
    sentTimestampRow: {
        justifyContent: 'flex-end',
        marginRight: 4,
        marginLeft: 0,
    },
    timestamp: {
        fontSize: 10,
        color: Colors.light.textMuted,
    },
    imageBubble: {
        borderRadius: 20,
        borderBottomRightRadius: 4,
        overflow: 'hidden',
        backgroundColor: Colors.light.surface,
        padding: 4,
    },
    receivedImageBubble: {
        borderBottomRightRadius: 20,
        borderBottomLeftRadius: 4,
    },
    messageImage: {
        width: 240,
        height: 180,
        borderRadius: 16,
    },
    voiceBubble: {
        minWidth: 200,
    },
    waveformContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    waveform: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 2,
        flex: 1,
    },
    waveformBar: {
        width: 3,
        backgroundColor: Colors.dark.background,
        borderRadius: 2,
    },
    voiceDuration: {
        fontSize: FontSize.xs,
        fontWeight: '700',
        color: Colors.dark.background,
    },
});
