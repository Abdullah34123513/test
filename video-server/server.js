require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*", // Adjust this in production for security
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.PORT || 3000;

// Simple health check endpoint
app.get('/', (req, res) => {
    res.send('Video Signaling Server is running.');
});

// Room management
const rooms = new Map();

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    socket.on('join-room', (roomId) => {
        console.log(`User ${socket.id} joining room: ${roomId}`);

        socket.join(roomId);

        // Notify others in the room
        socket.to(roomId).emit('user-joined', socket.id);

        // Check how many users are in the room
        const clients = io.sockets.adapter.rooms.get(roomId);
        const numClients = clients ? clients.size : 0;

        console.log(`Room ${roomId} has ${numClients} clients`);
    });

    // Handle WebRTC Offer
    socket.on('offer', (data) => {
        // data should contain { roomId, sdp, senderUserId, etc. }
        console.log(`Forwarding offer from ${socket.id} in room ${data.roomId}`);
        socket.to(data.roomId).emit('offer', {
            ...data,
            senderId: socket.id
        });
    });

    // Handle WebRTC Answer
    socket.on('answer', (data) => {
        console.log(`Forwarding answer from ${socket.id} in room ${data.roomId}`);
        socket.to(data.roomId).emit('answer', {
            ...data,
            senderId: socket.id
        });
    });

    // Handle ICE Candidates
    socket.on('ice-candidate', (data) => {
        console.log(`Forwarding ICE candidate from ${socket.id} in room ${data.roomId}`);
        socket.to(data.roomId).emit('ice-candidate', {
            ...data,
            senderId: socket.id
        });
    });

    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`Signaling server listening on port ${PORT}`);
});
