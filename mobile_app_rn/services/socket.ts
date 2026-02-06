import { io, Socket } from 'socket.io-client';
import { CONFIG } from '../constants/Config';

export const socket: Socket = io(CONFIG.SIGNALING_SERVER, {
    autoConnect: false,
    transports: ['websocket'],
});

export const connectSocket = (token: string) => {
    if (!socket.connected) {
        socket.auth = { token };
        socket.connect();
    }
};

export const disconnectSocket = () => {
    if (socket.connected) {
        socket.disconnect();
    }
};
