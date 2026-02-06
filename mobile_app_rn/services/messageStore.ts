import AsyncStorage from '@react-native-async-storage/async-storage';

export interface Message {
    id: number;
    sender_id: number;
    receiver_id: number;
    message: string;
    type: 'text' | 'image' | 'audio';
    file_path?: string;
    is_read: boolean;
    created_at: string;
}

const STORAGE_KEY = '@chat_messages';

type Listener = (message: Message) => void;
const listeners: Set<Listener> = new Set();

export const messageStore = {
    async saveMessage(message: Message) {
        try {
            const stored = await AsyncStorage.getItem(STORAGE_KEY);
            const messages: Message[] = stored ? JSON.parse(stored) : [];

            // Avoid duplicates
            if (messages.find(m => m.id === message.id)) return;

            messages.push(message);
            await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(messages));

            // Notify listeners
            listeners.forEach(listener => listener(message));
        } catch (error) {
            console.error('Error saving message to store:', error);
        }
    },

    async getMessages(contactId: string): Promise<Message[]> {
        try {
            const stored = await AsyncStorage.getItem(STORAGE_KEY);
            if (!stored) return [];
            const messages: Message[] = JSON.parse(stored);
            return messages.filter(m =>
                m.sender_id.toString() === contactId ||
                m.receiver_id.toString() === contactId
            );
        } catch (error) {
            console.error('Error getting messages from store:', error);
            return [];
        }
    },

    addListener(listener: Listener) {
        listeners.add(listener);
        return () => listeners.delete(listener);
    },

    async clearAll() {
        await AsyncStorage.removeItem(STORAGE_KEY);
    }
};
