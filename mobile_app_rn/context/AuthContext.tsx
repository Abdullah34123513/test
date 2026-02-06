import React, { createContext, useContext, useState, useEffect } from 'react';
import * as SecureStore from 'expo-secure-store';
import { useRouter } from 'expo-router';
import api from '../services/api';
import { connectSocket, disconnectSocket, socket } from '../services/socket';

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface AuthContextType {
    user: User | null;
    token: string | null;
    isLoading: boolean;
    login: (token: string, user: User) => Promise<void>;
    logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [user, setUser] = useState<User | null>(null);
    const [token, setToken] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const router = useRouter();

    useEffect(() => {
        if (user && token) {
            connectSocket(token);

            // Listen for incoming calls (Disabled for now)
            /*
            socket.on('offer', (data) => {
                const actualSenderId = data.senderUserId || data.senderId;
                console.log('Incoming call from:', actualSenderId);
                // Navigate to the call screen
                router.push({
                    pathname: `/call/${actualSenderId}`,
                    params: { isIncoming: 'true', sdp: JSON.stringify(data.sdp) }
                } as any);
            });
            */

            return () => {
                socket.off('offer');
            };
        }
    }, [user, token]);

    useEffect(() => {
        loadStoredAuth();
    }, []);

    const loadStoredAuth = async () => {
        try {
            const storedToken = await SecureStore.getItemAsync('userToken');
            const storedUser = await SecureStore.getItemAsync('userData');

            if (storedToken && storedUser) {
                setToken(storedToken);
                setUser(JSON.parse(storedUser));
                // Configure API client with token
                api.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`;
            }
        } catch (e) {
            console.error('Failed to load auth data', e);
        } finally {
            setIsLoading(false);
        }
    };

    const login = async (newToken: string, newUser: User) => {
        if (typeof newToken !== 'string' || !newToken) {
            console.error('Login failed: Invalid or missing token');
            return;
        }

        try {
            await SecureStore.setItemAsync('userToken', newToken);
            await SecureStore.setItemAsync('userData', JSON.stringify(newUser));

            setToken(newToken);
            setUser(newUser);

            api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
        } catch (e) {
            console.error('Failed to save auth data', e);
        }
    };

    const logout = async () => {
        try {
            await SecureStore.deleteItemAsync('userToken');
            await SecureStore.deleteItemAsync('userData');

            setToken(null);
            setUser(null);

            delete api.defaults.headers.common['Authorization'];
            disconnectSocket();
        } catch (e) {
            console.error('Failed to clear auth data', e);
        }
    };

    return (
        <AuthContext.Provider value={{ user, token, isLoading, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        console.warn('useAuth was called outside of an AuthProvider. Returning a fallback state.');
        return {
            user: null,
            token: null,
            isLoading: true,
            login: async () => { },
            logout: async () => { },
        };
    }
    return context;
};
