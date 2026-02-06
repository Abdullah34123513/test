import React, { useState } from 'react';
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, SafeAreaView, StatusBar } from 'react-native';
import { Settings, SquarePen, PhoneCall } from 'lucide-react-native';
import { CallItem } from '../../components/ui/CallItem';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

const MOCK_CALLS = [
    {
        id: '1',
        name: 'John Doe',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBnKS1c8i8ag7mNr_vf_AQ0hpmo4AlR0PRK45wymAmswgZ5vcTL6n_ciYKUauFSGRBfoh9A9N_ZRVcjCc9wT924n9GB_KiOdcWtbpiy81rQnrvOe-KrxY9o7QMX9HluUjISX7l28Gj_em0YRVKltRcGVO5cMNqmgNr9-Zax7wUd0aG7FDOPEYTMht7dyVOHqo2R7RQJUfzRN5DpjQ2MyQasCxhy3ujqV9037NuYsE2GbKe5M7gGDgfblVqK3IQAfpsoq1tUMAQTXt4',
        timestamp: '10:30 AM',
        type: 'incoming' as const,
        callType: 'voice' as const,
    },
    {
        id: '2',
        name: 'Jane Smith',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDZPoITv6Sk2xNDw9j1m9ebk140NWnq1OFcijwh8380XDHLifsiu5rnPLk8wE_zd-9tE7GAMN3FebWF_l0vfKuaKgrFLWO1xVbEii6UX_u1y94-TivOsy2g_iI0D5GDcUMYeQhV7ESI6RNTbjiEpjRzPSXpMGPswseNNG6hfeY0nqiO_1sxUQvUmM0VfBFI0cNEV_yaQ4_4uatJAFLjnl1tY6haZrc6sYIsYSNMB2yzHd_plz-z1yP4laNdf_OSMCGS8B3Ty-OtIz8',
        timestamp: '09:15 AM',
        type: 'missed' as const,
        callType: 'video' as const,
    },
    {
        id: '3',
        name: 'Alex Rivera',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDdG7GkzXXiFN0vFb9MD50gVSGueCDuJlZw0hpf-L2aLWfuCIO_YG67Oxn5WP6qsu0dNAdt1XLX79FmOCoUwe62pVW4pEki10eGHDE61A6V6czZm0uDsp6noht8DjC0e_oWdg97om2CK9FMRTLNxPrc7-IxeYmbzn3C_Z2CQdN9fXJRXTsF66hqcr_f7YeaFic1fYyPdOzwjv2bwhkw2lx9xQXcamhApSlRF2pZSIz3isNBTjsRRXBnOTOu8eo5mFA2-Je9yob_liI',
        timestamp: 'Yesterday',
        type: 'outgoing' as const,
        callType: 'voice' as const,
    },
    {
        id: '4',
        name: 'Elena Costa',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBRUfSSELMBDXYJCYmlYuYHFDt5fj4e2Yk6CqqKwIRbvKgrjJZq_yXlgfjUY9aK9qTXSX4YOWIq2uujCjmingM7U1-E4DKNzMf8mKEn7xNKhmuIXBE2Q81729wzQxFaMqC_9vGFZTZ8vXhggAIK1Ml48LQmsiEdaYsaWCkSwl2W-gicQnA1ftQUIoAO3HcHkQ6S10FHxrPHBPKN3_hQi1yoL7siCS7lz3tuTaPHuO6G7H0QFXEFdxdAzn6spehRycA8AwgSmjgGf0',
        timestamp: 'Sunday (12 min)',
        type: 'incoming' as const,
        callType: 'video' as const,
    },
    {
        id: '5',
        name: 'Marcus Thorne',
        avatarUri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBJeHX77MuE4qmcLhchu0wT3QoONyN8azoBVvVZzZv8iROicRwQAYED0jMTz6_8o3mh64M9BKeICbIwDnCQea_ySw2elVcgYHK8e97m1R6lxPpU4a0MTJ95cHLid3BjOBB63qq595UaLmgfGI_b_fYBnkTNHcflt-Amls8eZMG1dHsUxp_6-X36KI3jRLbfkVgotbOU9yeQNWLIyPs6I-r8RjIsbVopjndsbKbonNMU4Hqywv_MsMcJpTpTo7IBxPy4ioALTlLsEms',
        timestamp: 'Oct 24',
        type: 'outgoing' as const,
        callType: 'voice' as const,
    },
];

export default function CallsScreen() {
    const [filter, setFilter] = useState<'all' | 'missed'>('all');

    const filteredCalls = filter === 'all'
        ? MOCK_CALLS
        : MOCK_CALLS.filter(call => call.type === 'missed');

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor={Colors.light.background} />

            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity style={styles.settingsButton}>
                    <Settings size={26} color={Colors.primary} />
                </TouchableOpacity>
                <Text style={styles.title}>Calls</Text>
                <TouchableOpacity style={styles.editButton}>
                    <SquarePen size={20} color={Colors.primary} />
                </TouchableOpacity>
            </View>

            {/* Filter Tabs */}
            <View style={styles.filterTabs}>
                <TouchableOpacity
                    style={[styles.tab, filter === 'all' && styles.activeTab]}
                    onPress={() => setFilter('all')}
                >
                    <Text style={[styles.tabText, filter === 'all' && styles.activeTabText]}>All</Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={[styles.tab, filter === 'missed' && styles.activeTab]}
                    onPress={() => setFilter('missed')}
                >
                    <Text style={[styles.tabText, filter === 'missed' && styles.activeTabText]}>Missed</Text>
                </TouchableOpacity>
            </View>

            <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
                <View style={styles.callList}>
                    {filteredCalls.map((call) => (
                        <CallItem
                            key={call.id}
                            {...call}
                            onPress={() => console.log('View call details:', call.id)}
                            onActionPress={() => console.log('Action press:', call.id)}
                        />
                    ))}
                </View>

                <View style={{ height: 120 }} />
            </ScrollView>

            {/* FAB */}
            <TouchableOpacity style={styles.fab} activeOpacity={0.8}>
                <PhoneCall size={26} color="#fff" />
            </TouchableOpacity>
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
    settingsButton: {
        width: 48,
        height: 48,
        justifyContent: 'center',
    },
    title: {
        fontSize: 20,
        fontWeight: '700',
        color: Colors.light.text,
    },
    editButton: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: `${Colors.primary}10`,
        alignItems: 'center',
        justifyContent: 'center',
    },
    filterTabs: {
        flexDirection: 'row',
        paddingHorizontal: 16,
        gap: 32,
        borderBottomWidth: 1,
        borderBottomColor: Colors.light.border,
    },
    tab: {
        paddingVertical: 12,
        borderBottomWidth: 3,
        borderBottomColor: 'transparent',
    },
    activeTab: {
        borderBottomColor: Colors.primary,
    },
    tabText: {
        fontSize: 14,
        fontWeight: '700',
        color: Colors.light.textSecondary,
        letterSpacing: 0.5,
    },
    activeTabText: {
        color: Colors.light.text,
    },
    content: {
        flex: 1,
    },
    callList: {
        paddingVertical: 4,
    },
    fab: {
        position: 'absolute',
        bottom: 100,
        right: 24,
        width: 60,
        height: 60,
        borderRadius: 30,
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
