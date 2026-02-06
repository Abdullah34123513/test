import React from 'react';
import { TouchableOpacity, StyleSheet, Animated } from 'react-native';
import { MessageSquarePlus } from 'lucide-react-native';
import { Colors } from '../../constants/theme';

interface FloatingActionButtonProps {
    onPress?: () => void;
}

export const FloatingActionButton: React.FC<FloatingActionButtonProps> = ({ onPress }) => {
    return (
        <TouchableOpacity
            style={styles.button}
            onPress={onPress}
            activeOpacity={0.8}
        >
            <MessageSquarePlus size={24} color={Colors.dark.background} />
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    button: {
        position: 'absolute',
        bottom: 100,
        right: 20,
        width: 56,
        height: 56,
        borderRadius: 28,
        backgroundColor: Colors.primary,
        alignItems: 'center',
        justifyContent: 'center',
        shadowColor: Colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3,
        shadowRadius: 10,
        elevation: 8,
    },
});
