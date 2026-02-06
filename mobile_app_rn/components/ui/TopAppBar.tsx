import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Camera, Search } from 'lucide-react-native';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

interface TopAppBarProps {
    title: string;
    onCameraPress?: () => void;
    onSearchPress?: () => void;
}

export const TopAppBar: React.FC<TopAppBarProps> = ({
    title,
    onCameraPress,
    onSearchPress,
}) => {
    return (
        <View style={styles.container}>
            <Text style={styles.title}>{title}</Text>
            <View style={styles.actions}>
                <TouchableOpacity style={styles.iconButton} onPress={onCameraPress}>
                    <Camera size={24} color={Colors.light.text} />
                </TouchableOpacity>
                <TouchableOpacity style={styles.searchButton} onPress={onSearchPress}>
                    <Search size={20} color={Colors.light.text} />
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
        paddingHorizontal: Spacing.md,
        paddingTop: 12,
        paddingBottom: 8,
        backgroundColor: Colors.light.surface,
    },
    title: {
        fontSize: FontSize.xxl,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
    },
    actions: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 16,
    },
    iconButton: {
        padding: 4,
    },
    searchButton: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: Colors.light.border,
        alignItems: 'center',
        justifyContent: 'center',
    },
});
