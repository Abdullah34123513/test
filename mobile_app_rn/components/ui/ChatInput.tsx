import React, { useState } from 'react';
import { View, TextInput, TouchableOpacity, StyleSheet } from 'react-native';
import { Plus, Smile, Paperclip, Camera, Mic, Send } from 'lucide-react-native';
import { Colors, FontSize, Spacing } from '../../constants/theme';

interface ChatInputProps {
    onSend?: (message: string) => void;
    onAttach?: () => void;
    onCamera?: () => void;
    onVoice?: () => void;
    onEmoji?: () => void;
    onAdd?: () => void;
}

export const ChatInput: React.FC<ChatInputProps> = ({
    onSend,
    onAttach,
    onCamera,
    onVoice,
    onEmoji,
    onAdd,
}) => {
    const [message, setMessage] = useState('');

    const handleSend = () => {
        if (message.trim() && onSend) {
            onSend(message.trim());
            setMessage('');
        }
    };

    return (
        <View style={styles.container}>
            <TouchableOpacity style={styles.addButton} onPress={onAdd}>
                <Plus size={24} color={Colors.light.textMuted} />
            </TouchableOpacity>

            <View style={styles.inputContainer}>
                <TouchableOpacity style={styles.iconButton} onPress={onEmoji}>
                    <Smile size={20} color={Colors.light.textMuted} />
                </TouchableOpacity>

                <TextInput
                    style={styles.input}
                    placeholder="Message"
                    placeholderTextColor={Colors.light.textMuted}
                    value={message}
                    onChangeText={setMessage}
                    onSubmitEditing={handleSend}
                />

                <TouchableOpacity style={styles.iconButton} onPress={onAttach}>
                    <Paperclip size={20} color={Colors.light.textMuted} />
                </TouchableOpacity>

                {!message.trim() && (
                    <TouchableOpacity style={styles.iconButton} onPress={onCamera}>
                        <Camera size={20} color={Colors.light.textMuted} />
                    </TouchableOpacity>
                )}
            </View>

            <TouchableOpacity
                style={styles.actionButton}
                onPress={message.trim() ? handleSend : onVoice}
            >
                {message.trim() ? (
                    <Send size={20} color={Colors.dark.background} />
                ) : (
                    <Mic size={20} color={Colors.dark.background} />
                )}
            </TouchableOpacity>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        backgroundColor: Colors.light.surface,
        paddingHorizontal: Spacing.md,
        paddingTop: Spacing.sm,
        paddingBottom: 32,
        borderTopWidth: 1,
        borderTopColor: Colors.light.border,
    },
    addButton: {
        padding: 4,
    },
    inputContainer: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: Colors.light.border,
        borderRadius: 24,
        paddingHorizontal: 12,
        paddingVertical: 6,
        gap: 8,
    },
    iconButton: {
        padding: 4,
    },
    input: {
        flex: 1,
        fontSize: FontSize.sm,
        color: Colors.light.text,
        paddingVertical: 4,
    },
    actionButton: {
        width: 44,
        height: 44,
        borderRadius: 22,
        backgroundColor: Colors.primary,
        alignItems: 'center',
        justifyContent: 'center',
        shadowColor: Colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 8,
        elevation: 4,
    },
});
