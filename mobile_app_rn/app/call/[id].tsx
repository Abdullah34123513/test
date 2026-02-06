import React, { useEffect, useState, useRef } from 'react';
import { View, StyleSheet, TouchableOpacity, Text, SafeAreaView, Dimensions, Alert } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import {
    RTCPeerConnection,
    RTCIceCandidate,
    RTCSessionDescription,
    RTCView,
    mediaDevices
} from 'react-native-webrtc';
import { Camera, CameraOff, Mic, MicOff, PhoneOff, Video, VideoOff } from 'lucide-react-native';
import { socket } from '../../services/socket';
import { useAuth } from '../../context/AuthContext';
import { Colors } from '../../constants/theme';

const { width, height } = Dimensions.get('window');

const configuration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
    ],
};

export default function CallScreen() {
    const params = useLocalSearchParams();
    const recipientId = Array.isArray(params.id) ? params.id[0] : params.id;
    const isIncoming = params.isIncoming;
    const incomingSdpStr = Array.isArray(params.sdp) ? params.sdp[0] : params.sdp;

    const { user } = useAuth();
    const router = useRouter();

    const [localStream, setLocalStream] = useState<any>(null);
    const [remoteStream, setRemoteStream] = useState<any>(null);
    const [isMuted, setIsMuted] = useState(false);
    const [isVideoOff, setIsVideoOff] = useState(false);
    const [callStatus, setCallStatus] = useState('Connecting...');

    const pc = useRef<RTCPeerConnection | null>(null);
    const localStreamRef = useRef<any>(null);
    const iceCandidateQueue = useRef<any[]>([]);
    const isRemoteDescriptionSet = useRef(false);

    const roomId = [user?.id, recipientId].sort((a, b) => Number(a) - Number(b)).join('-');

    useEffect(() => {
        console.log('CallScreen mounted, roomId:', roomId);
        startCall();

        return () => {
            console.log('CallScreen unmounting, cleaning up...');
            cleanup();
        };
    }, []);

    const cleanup = () => {
        if (localStreamRef.current) {
            localStreamRef.current.getTracks().forEach((t: any) => {
                t.stop();
                console.log('Local track stopped:', t.kind);
            });
        }
        if (pc.current) {
            pc.current.close();
            pc.current = null;
            console.log('PeerConnection closed');
        }
        socket.off('answer');
        socket.off('ice-candidate');
        socket.emit('leave-room', roomId);
    };

    const startCall = async () => {
        try {
            console.log('Initializing media devices...');
            const stream: any = await mediaDevices.getUserMedia({
                audio: true,
                video: {
                    facingMode: 'user',
                },
            });
            setLocalStream(stream);
            localStreamRef.current = stream;

            console.log('Creating PeerConnection...');
            pc.current = new RTCPeerConnection(configuration);

            stream.getTracks().forEach((track: any) => {
                console.log('Adding local track to PC:', track.kind);
                pc.current?.addTrack(track, stream);
            });

            (pc.current as any).ontrack = (event: any) => {
                console.log('Remote track received:', event.track?.kind);
                if (event.streams && event.streams[0]) {
                    setRemoteStream(event.streams[0]);
                    setCallStatus('Active');
                }
            };

            (pc.current as any).onicecandidate = (event: any) => {
                if (event.candidate) {
                    console.log('Generated local ICE candidate');
                    socket.emit('ice-candidate', {
                        roomId,
                        candidate: event.candidate,
                        senderUserId: user?.id, // Added
                    });
                }
            };

            socket.emit('join-room', roomId);

            if (isIncoming === 'true' && incomingSdpStr) {
                console.log('Handling incoming call...');
                const sdp = JSON.parse(incomingSdpStr);
                await pc.current.setRemoteDescription(new RTCSessionDescription(sdp));
                isRemoteDescriptionSet.current = true;
                processQueuedCandidates();

                const answer = await pc.current.createAnswer();
                await pc.current.setLocalDescription(answer);
                socket.emit('answer', {
                    roomId,
                    sdp: answer,
                    senderUserId: user?.id, // Added
                });
            } else {
                console.log('Initiating outgoing call...');
                const offer = await pc.current.createOffer();
                await pc.current.setLocalDescription(offer);
                socket.emit('offer', {
                    roomId,
                    sdp: offer,
                    senderUserId: user?.id, // Added
                });
            }

            socket.on('answer', async (data: any) => {
                console.log('Signaling answer received');
                if (pc.current && pc.current.signalingState !== 'stable') {
                    await pc.current.setRemoteDescription(new RTCSessionDescription(data.sdp));
                    isRemoteDescriptionSet.current = true;
                    processQueuedCandidates();
                }
            });

            socket.on('ice-candidate', async (data: any) => {
                if (!isRemoteDescriptionSet.current) {
                    console.log('Queuing remote ICE candidate (remote description not set)');
                    iceCandidateQueue.current.push(data.candidate);
                } else {
                    addRemoteCandidate(data.candidate);
                }
            });

        } catch (error) {
            console.error('Call initialization failed:', error);
            setCallStatus('Failed');
            Alert.alert('Call Error', 'Could not initialize video call.');
        }
    };

    const addRemoteCandidate = async (candidate: any) => {
        try {
            if (pc.current && pc.current.remoteDescription) {
                console.log('Adding remote ICE candidate');
                await pc.current.addIceCandidate(new RTCIceCandidate(candidate));
            }
        } catch (e) {
            console.warn('Error adding ICE candidate:', e);
        }
    };

    const processQueuedCandidates = () => {
        console.log(`Processing ${iceCandidateQueue.current.length} queued candidates`);
        while (iceCandidateQueue.current.length > 0) {
            const candidate = iceCandidateQueue.current.shift();
            addRemoteCandidate(candidate);
        }
    };

    const endCall = () => {
        cleanup();
        router.back();
    };

    const toggleMute = () => {
        if (localStreamRef.current) {
            localStreamRef.current.getAudioTracks().forEach((track: any) => {
                track.enabled = isMuted;
            });
            setIsMuted(!isMuted);
        }
    };

    const toggleVideo = () => {
        if (localStreamRef.current) {
            localStreamRef.current.getVideoTracks().forEach((track: any) => {
                track.enabled = isVideoOff;
            });
            setIsVideoOff(!isVideoOff);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.videoContainer}>
                {remoteStream ? (
                    <RTCView
                        streamURL={remoteStream.toURL()}
                        style={styles.remoteVideo}
                        objectFit="cover"
                    />
                ) : (
                    <View style={styles.placeholderContainer}>
                        <Text style={styles.statusText}>{callStatus}</Text>
                        <Text style={styles.nameText}>User {recipientId}</Text>
                    </View>
                )}

                {localStream && !isVideoOff && (
                    <RTCView
                        streamURL={localStream.toURL()}
                        style={styles.localVideo}
                        objectFit="cover"
                    />
                )}
            </View>

            <View style={styles.controls}>
                <TouchableOpacity style={[styles.controlButton, isMuted && styles.activeButton]} onPress={toggleMute}>
                    {isMuted ? <MicOff color="#fff" /> : <Mic color="#fff" />}
                </TouchableOpacity>

                <TouchableOpacity style={[styles.controlButton, isVideoOff && styles.activeButton]} onPress={toggleVideo}>
                    {isVideoOff ? <VideoOff color="#fff" /> : <Video color="#fff" />}
                </TouchableOpacity>

                <TouchableOpacity style={[styles.controlButton, styles.endCallButton]} onPress={endCall}>
                    <PhoneOff color="#fff" />
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#000',
    },
    videoContainer: {
        flex: 1,
        position: 'relative',
    },
    remoteVideo: {
        width: '100%',
        height: '100%',
    },
    localVideo: {
        position: 'absolute',
        top: 40,
        right: 20,
        width: 120,
        height: 180,
        borderRadius: 16,
        overflow: 'hidden',
        backgroundColor: '#333',
    },
    placeholderContainer: {
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
    },
    statusText: {
        color: '#fff',
        fontSize: 18,
        opacity: 0.7,
        marginBottom: 8,
    },
    nameText: {
        color: '#fff',
        fontSize: 24,
        fontWeight: 'bold',
    },
    controls: {
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        paddingVertical: 40,
        gap: 20,
    },
    controlButton: {
        width: 60,
        height: 60,
        borderRadius: 30,
        backgroundColor: 'rgba(255, 255, 255, 0.2)',
        alignItems: 'center',
        justifyContent: 'center',
    },
    activeButton: {
        backgroundColor: '#ef4444',
    },
    endCallButton: {
        backgroundColor: '#ef4444',
        width: 70,
        height: 70,
        borderRadius: 35,
    },
});
