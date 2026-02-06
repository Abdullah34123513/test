import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

interface Tab {
    key: string;
    label: string;
}

interface TabBarProps {
    tabs: Tab[];
    activeTab: string;
    onTabPress: (key: string) => void;
}

export const TabBar: React.FC<TabBarProps> = ({ tabs, activeTab, onTabPress }) => {
    return (
        <View style={styles.container}>
            {tabs.map((tab) => {
                const isActive = tab.key === activeTab;
                return (
                    <TouchableOpacity
                        key={tab.key}
                        style={[styles.tab, isActive && styles.activeTab]}
                        onPress={() => onTabPress(tab.key)}
                    >
                        <Text style={[styles.tabText, isActive && styles.activeTabText]}>
                            {tab.label}
                        </Text>
                    </TouchableOpacity>
                );
            })}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flexDirection: 'row',
        paddingHorizontal: Spacing.md,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.border,
        gap: Spacing.xl,
    },
    tab: {
        paddingVertical: Spacing.md,
        paddingHorizontal: Spacing.sm,
        borderBottomWidth: 2,
        borderBottomColor: 'transparent',
    },
    activeTab: {
        borderBottomColor: Colors.primary,
    },
    tabText: {
        fontSize: FontSize.sm,
        fontWeight: FontWeight.bold,
        color: Colors.light.textMuted,
        letterSpacing: 0.5,
    },
    activeTabText: {
        color: Colors.light.text,
    },
});
