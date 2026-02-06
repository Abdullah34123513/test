import React from 'react';
import { View, Image, StyleSheet } from 'react-native';
import { Colors } from '../../constants/theme';

interface AvatarProps {
    uri: string;
    size?: number;
    isOnline?: boolean;
}

export const Avatar: React.FC<AvatarProps> = ({ uri, size = 56, isOnline = false }) => {
    return (
        <View style={[styles.container, { width: size, height: size }]}>
            <Image
                source={{ uri }}
                style={[styles.image, { width: size, height: size, borderRadius: size / 2 }]}
            />
            {isOnline && (
                <View style={[styles.onlineIndicator, {
                    width: size * 0.25,
                    height: size * 0.25,
                    borderRadius: size * 0.125
                }]} />
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        position: 'relative',
    },
    image: {
        borderWidth: 1,
        borderColor: Colors.light.border,
    },
    onlineIndicator: {
        position: 'absolute',
        bottom: 0,
        right: 0,
        backgroundColor: Colors.primary,
        borderWidth: 2,
        borderColor: '#ffffff',
    },
});
