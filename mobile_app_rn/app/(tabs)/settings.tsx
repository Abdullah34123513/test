import React from 'react';
import { View, Text, StyleSheet, SafeAreaView } from 'react-native';
import { Colors, FontSize, FontWeight } from '../../constants/theme';

export default function SettingsScreen() {
    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Settings</Text>
            </View>
            <View style={styles.content}>
                <Text style={styles.placeholder}>Settings options will appear here</Text>
            </View>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: Colors.light.surface,
    },
    header: {
        padding: 16,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.border,
    },
    title: {
        fontSize: FontSize.xl,
        fontWeight: FontWeight.bold,
        color: Colors.light.text,
        textAlign: 'center',
    },
    content: {
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
    },
    placeholder: {
        fontSize: FontSize.base,
        color: Colors.light.textMuted,
    },
});
