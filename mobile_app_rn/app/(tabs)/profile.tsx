import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, Image, TouchableOpacity, ScrollView } from 'react-native';
import { User, MessageSquare, Bell, HardDrive, HelpCircle, ChevronRight, LogOut } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { useAuth } from '../../context/AuthContext';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

export default function ProfileScreen() {
    const { user, logout } = useAuth();
    const router = useRouter();

    const handleLogout = async () => {
        await logout();
        router.replace('/auth/login');
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView>
                <View style={styles.header}>
                    <Text style={styles.title}>Profile</Text>
                </View>

                <View style={styles.profileSection}>
                    <View style={styles.avatarContainer}>
                        <Image
                            source={{ uri: user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=random` }}
                            style={styles.avatar}
                        />
                    </View>
                    <Text style={styles.name}>{user?.name || 'Abdullah'}</Text>
                    <Text style={styles.status}>Available</Text>
                </View>

                <View style={styles.menuSection}>
                    <MenuItem
                        icon={<User size={22} color={Colors.light.textSecondary} />}
                        title="Account"
                        subtitle="Privacy, security, change number"
                    />
                    <MenuItem
                        icon={<MessageSquare size={22} color={Colors.light.textSecondary} />}
                        title="Chats"
                        subtitle="Theme, wallpapers, chat history"
                    />
                    <MenuItem
                        icon={<Bell size={22} color={Colors.light.textSecondary} />}
                        title="Notifications"
                        subtitle="Message, group & call tones"
                    />
                    <MenuItem
                        icon={<HardDrive size={22} color={Colors.light.textSecondary} />}
                        title="Storage and Data"
                        subtitle="Network usage, auto-download"
                    />
                    <MenuItem
                        icon={<HelpCircle size={22} color={Colors.light.textSecondary} />}
                        title="Help"
                        subtitle="Help center, contact us, privacy policy"
                    />

                    <TouchableOpacity
                        style={[styles.menuItem, { marginTop: 20, borderBottomWidth: 0 }]}
                        onPress={handleLogout}
                    >
                        <View style={styles.menuIconContainer}>
                            <LogOut size={22} color="#ef4444" />
                        </View>
                        <View style={styles.menuTextContainer}>
                            <Text style={[styles.menuTitle, { color: '#ef4444' }]}>Log Out</Text>
                        </View>
                    </TouchableOpacity>
                </View>

                <View style={{ height: 100 }} />
            </ScrollView>
        </SafeAreaView>
    );
}

const MenuItem = ({ icon, title, subtitle }: { icon: React.ReactNode, title: string, subtitle: string }) => (
    <TouchableOpacity style={styles.menuItem}>
        <View style={styles.menuIconContainer}>
            {icon}
        </View>
        <View style={styles.menuTextContainer}>
            <Text style={styles.menuTitle}>{title}</Text>
            <Text style={styles.menuSubtitle}>{subtitle}</Text>
        </View>
        <ChevronRight size={18} color={Colors.light.textMuted} />
    </TouchableOpacity>
);

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: Colors.light.background,
    },
    header: {
        padding: 24,
        alignItems: 'center',
    },
    title: {
        fontSize: 24,
        fontWeight: 'bold',
        color: Colors.light.text,
    },
    profileSection: {
        alignItems: 'center',
        paddingVertical: 10,
        marginBottom: 20,
    },
    avatarContainer: {
        width: 100,
        height: 100,
        borderRadius: 50,
        padding: 2,
        borderWidth: 2,
        borderColor: Colors.primary,
        marginBottom: 16,
    },
    avatar: {
        width: '100%',
        height: '100%',
        borderRadius: 48,
    },
    name: {
        fontSize: 20,
        fontWeight: 'bold',
        color: Colors.light.text,
    },
    status: {
        fontSize: 14,
        color: Colors.light.textSecondary,
        marginTop: 4,
    },
    menuSection: {
        paddingHorizontal: 20,
    },
    menuItem: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 16,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.border,
    },
    menuIconContainer: {
        width: 40,
        height: 40,
        alignItems: 'center',
        justifyContent: 'center',
    },
    menuTextContainer: {
        marginLeft: 12,
        flex: 1,
    },
    menuTitle: {
        fontSize: 16,
        fontWeight: '600',
        color: Colors.light.text,
    },
    menuSubtitle: {
        fontSize: 13,
        color: Colors.light.textMuted,
        marginTop: 2,
    },
});
