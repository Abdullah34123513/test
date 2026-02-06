import React, { useState } from 'react';
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, SafeAreaView, StatusBar } from 'react-native';
import { Camera, Edit2, Menu, Search } from 'lucide-react-native';
import { StatusItem, MyStatus } from '../../components/ui/StatusItem';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

const MY_AVATAR = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAMlv8uI_7peX7qGSc4ZvNVN-pZ4vGEjl9Rl4cuOR9PRy88D1h_7gt5wnqrB4JSSAwnlMbxKJVqN4FlFyjEHui-lSMf0XB9MLgqdb4FzznTSTi5i6o6i5ymwZRX1S2p8gUvEIRJYnyOhPsv4Hv1www2NU7vaOjNK1GWmWPL7oVeph_LpfgLh0pVYg8lo6HDcgO6uuklrMVET6Vt0aqKQgWWPUUiAbrapMU1eLmkiBRzjei9Ji9BHH8vzydwIsZNvLsizDpDT7dgWFY';

const RECENT_UPDATES = [
    {
        id: '1',
        name: 'Alex Rivera',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBAFR675gEIKTB2_C4D5UjG9LLcpVpX8CaPq17hhEOJLNPnwSySEIw_eDsRjNOGv_Zfj_YtB6pscYPR3JKThW5fpOrWrsNpBBFVP9VrSFa8M2Cn4ytM5-6MMm4YIsYhBGTHLutqUMqntoYPdqtrUmpRUIA2TRUrHSAPY9EbSxwwuFwqb4_Ettkiftpdq8nylAX2Sanb1yKGqJuIvhWJnj2NY6MWjZA0CJapxVSzn-VRXzUmkuct-lGVudKFgb3guyCgDnsiHeC2Zzw',
        timestamp: '10m ago',
        preview: 'Just landed in Tokyo! ✈️',
    },
    {
        id: '2',
        name: 'Marcus Wright',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDZC6qncGVQ3kMqvwKqZrGMh7_EfTHNxEi7_Wgasg8zWBaT0NUwvXR-RklIpGaUkZC4Th13o1_jXww1Zt0TwBDPzBS5ImRoAhq_H8UU7W0Xl2I8MxgdnwZSik_0VXiuS0slSTzDo1U5VoifSNemHxl5-_rl52-wXatJFqhMNEnVHJlBiUnpspQRxtVI3OueMDrBYCjTA202nq6Xn5LFZG1AS57YE-f3BeuGA1zos2QdtLk9kx8yEsrEqYDqUlPb0aDGWHoVOBmuNdI',
        timestamp: '45m ago',
        preview: 'Morning coffee run ☕️',
    },
    {
        id: '3',
        name: 'Sarah Jenkins',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDQRcTkFSCxkTq0nnj02AaSKwNAy6ft-IUoACmAoQsri6_Gap0jeCj5_IVbwAbs4JF1J5Zxt7Yp3iY6FKVzU685QrnLww6AMkM5YHsoqvMEx2qfAlGILL9VS6xKHFD1K03o__KvxvYkcysjfmJbj3hKHEH23tMhiXrfw7mCrGHRgH59mgYUJhSvoTT3qCAtWi-kFNMg0dTvf6r-ga8vaQYDe3Nn2YLx4_uQLM0mM1WSziWEb_D2Vi_Dqsc_u9U5ZZjuXcciSJT_jcA',
        timestamp: '2h ago',
        preview: 'New design project preview!',
    },
];

const VIEWED_UPDATES = [
    {
        id: '4',
        name: 'David Chen',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA7k5d9u6KQQl5vntlYUGmXMGxoMo3CX4UIiq15_NpVY4UdDuNy6E7ee8NI4mqaXyETNvmhhmwZoJFRimItAP-7bCf5PExwqeG6oobIdQk9SwPPvyxhIIgrHyxT2MOnJC9cyEknaA8XBZbMvTabzudUEr1prVmoZBff8Arx4YUii7gO4xD3CTTD1_Iebz-xhukt5OVelFK8h-lq0e_us7KcqxCWvDdY5TgGGj7RvnP9z1IvneqzfXl6p0ncUCJfq3JGF71O5W7nWFA',
        timestamp: '5h ago',
        preview: 'Check out this view',
        isViewed: true,
    },
];

export default function UpdatesScreen() {
    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor={Colors.light.background} />

            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity style={styles.menuButton}>
                    <Menu size={28} color={Colors.light.text} />
                </TouchableOpacity>
                <Text style={styles.title}>Updates</Text>
                <TouchableOpacity style={styles.searchButton}>
                    <Search size={22} color={Colors.light.text} />
                </TouchableOpacity>
            </View>

            <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
                {/* My Status */}
                <View style={styles.myStatusSection}>
                    <MyStatus
                        avatarUri={MY_AVATAR}
                        onAddStatus={() => console.log('Add status')}
                        onCamera={() => console.log('Camera')}
                        onEdit={() => console.log('Edit')}
                    />
                </View>

                {/* Recent Updates */}
                <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>Recent updates</Text>
                    <TouchableOpacity>
                        <Text style={styles.viewAllButton}>View all</Text>
                    </TouchableOpacity>
                </View>

                <View style={styles.statusList}>
                    {RECENT_UPDATES.map((status) => (
                        <StatusItem
                            key={status.id}
                            {...status}
                            onPress={() => console.log('View status:', status.id)}
                        />
                    ))}
                </View>

                {/* Viewed Updates */}
                <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>Viewed updates</Text>
                </View>

                <View style={styles.statusList}>
                    {VIEWED_UPDATES.map((status) => (
                        <StatusItem
                            key={status.id}
                            {...status}
                            onPress={() => console.log('View status:', status.id)}
                        />
                    ))}
                </View>

                <View style={{ height: 120 }} />
            </ScrollView>

            {/* FABs */}
            <View style={styles.fabContainer}>
                <TouchableOpacity style={styles.fabSecondary}>
                    <Edit2 size={24} color={Colors.light.textSecondary} />
                </TouchableOpacity>
                <TouchableOpacity style={styles.fabPrimary}>
                    <Camera size={32} color="#fff" />
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: Colors.light.background,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingTop: 12,
        paddingBottom: 12,
    },
    menuButton: {
        width: 48,
        height: 48,
        justifyContent: 'center',
    },
    title: {
        fontSize: 20,
        fontWeight: '700',
        color: Colors.light.text,
    },
    searchButton: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: Colors.light.border,
        alignItems: 'center',
        justifyContent: 'center',
    },
    content: {
        flex: 1,
    },
    myStatusSection: {
        paddingHorizontal: 16,
        marginVertical: 12,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: 20,
        paddingTop: 24,
        paddingBottom: 8,
    },
    sectionTitle: {
        fontSize: 12,
        fontWeight: '700',
        color: Colors.light.textMuted,
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    viewAllButton: {
        fontSize: 14,
        fontWeight: '600',
        color: Colors.primary,
    },
    statusList: {
        paddingHorizontal: 8,
    },
    fabContainer: {
        position: 'absolute',
        bottom: 100,
        right: 24,
        alignItems: 'center',
        gap: 16,
    },
    fabSecondary: {
        width: 48,
        height: 48,
        borderRadius: 24,
        backgroundColor: Colors.light.surface,
        alignItems: 'center',
        justifyContent: 'center',
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        elevation: 4,
    },
    fabPrimary: {
        width: 64,
        height: 64,
        borderRadius: 32,
        backgroundColor: Colors.primary,
        alignItems: 'center',
        justifyContent: 'center',
        shadowColor: Colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3,
        shadowRadius: 12,
        elevation: 8,
    },
});
