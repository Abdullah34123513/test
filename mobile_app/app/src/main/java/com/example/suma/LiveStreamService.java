package com.example.suma;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.Service;
import android.content.Intent;
import android.os.Build;
import android.os.IBinder;
import android.util.Log;

import androidx.annotation.Nullable;
import androidx.core.app.NotificationCompat;

import com.google.firebase.firestore.DocumentReference;
import com.google.firebase.firestore.FirebaseFirestore;
import com.google.firebase.firestore.SetOptions;

import org.webrtc.AudioSource;
import org.webrtc.AudioTrack;
import org.webrtc.DataChannel;
import org.webrtc.DefaultVideoDecoderFactory;
import org.webrtc.DefaultVideoEncoderFactory;
import org.webrtc.EglBase;
import org.webrtc.IceCandidate;
import org.webrtc.MediaConstraints;
import org.webrtc.MediaStream;
import org.webrtc.PeerConnection;
import org.webrtc.PeerConnectionFactory;
import org.webrtc.RtpReceiver;
import org.webrtc.SdpObserver;
import org.webrtc.SessionDescription;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class LiveStreamService extends Service {

    private static final String TAG = "LiveStreamService";
    private static final String CHANNEL_ID = "LiveStreamChannel";
    private static final int NOTIFICATION_ID = 2001;

    private PeerConnectionFactory peerConnectionFactory;
    private PeerConnection peerConnection;
    private AudioSource audioSource;
    private AudioTrack audioTrack;
    private FirebaseFirestore db;
    private String liveStreamId;
    private DocumentReference streamRef;

    @Override
    public void onCreate() {
        super.onCreate();
        createNotificationChannel();
        db = FirebaseFirestore.getInstance();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        if (intent == null)
            return START_NOT_STICKY;

        String action = intent.getAction();
        if ("start_stream".equals(action)) {
            liveStreamId = intent.getStringExtra("live_stream_id");
            Log.d(TAG, "Starting WebRTC Stream: " + liveStreamId);
            startForeground(NOTIFICATION_ID, createNotification());
            initializeWebRTC();
        } else if ("stop_stream".equals(action)) {
            Log.d(TAG, "Stopping WebRTC Stream");
            stopStream();
            stopSelf();
        }

        return START_NOT_STICKY;
    }

    private void initializeWebRTC() {
        PeerConnectionFactory.InitializationOptions initializationOptions = PeerConnectionFactory.InitializationOptions
                .builder(this)
                .createInitializationOptions();
        PeerConnectionFactory.initialize(initializationOptions);

        PeerConnectionFactory.Options options = new PeerConnectionFactory.Options();
        peerConnectionFactory = PeerConnectionFactory.builder()
                .setOptions(options)
                .setVideoEncoderFactory(new DefaultVideoEncoderFactory(null, true, true))
                .setVideoDecoderFactory(new DefaultVideoDecoderFactory(null))
                .createPeerConnectionFactory();

        // Audio Source & Track
        MediaConstraints audioConstraints = new MediaConstraints();
        audioSource = peerConnectionFactory.createAudioSource(audioConstraints);
        audioTrack = peerConnectionFactory.createAudioTrack("ARDAMSa0", audioSource);

        createPeerConnection();
    }

    private void createPeerConnection() {
        List<PeerConnection.IceServer> iceServers = new ArrayList<>();
        iceServers.add(PeerConnection.IceServer.builder("stun:stun.l.google.com:19302").createIceServer());

        PeerConnection.RTCConfiguration rtcConfig = new PeerConnection.RTCConfiguration(iceServers);
        rtcConfig.tcpCandidatePolicy = PeerConnection.TcpCandidatePolicy.ENABLED; // As requested

        peerConnection = peerConnectionFactory.createPeerConnection(rtcConfig, new PeerConnection.Observer() {
            @Override
            public void onIceCandidate(IceCandidate iceCandidate) {
                sendIceCandidate(iceCandidate);
            }

            @Override
            public void onSignalingChange(PeerConnection.SignalingState signalingState) {
            }

            @Override
            public void onIceConnectionChange(PeerConnection.IceConnectionState iceConnectionState) {
                Log.d(TAG, "ICE Connection: " + iceConnectionState);
                if (iceConnectionState == PeerConnection.IceConnectionState.DISCONNECTED) {
                    stopSelf();
                }
            }

            @Override
            public void onIceConnectionReceivingChange(boolean b) {
            }

            @Override
            public void onIceGatheringChange(PeerConnection.IceGatheringState iceGatheringState) {
            }

            @Override
            public void onAddStream(MediaStream mediaStream) {
            }

            @Override
            public void onRemoveStream(MediaStream mediaStream) {
            }

            @Override
            public void onDataChannel(DataChannel dataChannel) {
            }

            @Override
            public void onRenegotiationNeeded() {
            }

            @Override
            public void onAddTrack(RtpReceiver rtpReceiver, MediaStream[] mediaStreams) {
            }

            @Override
            public void onIceCandidatesRemoved(IceCandidate[] iceCandidates) {
            }
        });

        // Add Audio Track
        MediaStream stream = peerConnectionFactory.createLocalMediaStream("ARDAMS");
        stream.addTrack(audioTrack);
        peerConnection.addStream(stream);

        // CREATE OFFER
        createOffer();
    }

    private void createOffer() {
        MediaConstraints constraints = new MediaConstraints();
        constraints.mandatory.add(new MediaConstraints.KeyValuePair("OfferToReceiveAudio", "false"));
        constraints.mandatory.add(new MediaConstraints.KeyValuePair("OfferToReceiveVideo", "false"));

        peerConnection.createOffer(new SdpObserver() {
            @Override
            public void onCreateSuccess(SessionDescription sessionDescription) {
                peerConnection.setLocalDescription(new SdpObserver() {
                    @Override
                    public void onCreateSuccess(SessionDescription sessionDescription) {
                    }

                    @Override
                    public void onSetSuccess() {
                        sendOffer(sessionDescription);
                    }

                    @Override
                    public void onCreateFailure(String s) {
                    }

                    @Override
                    public void onSetFailure(String s) {
                    }
                }, sessionDescription);
            }

            @Override
            public void onSetSuccess() {
            }

            @Override
            public void onCreateFailure(String s) {
            }

            @Override
            public void onSetFailure(String s) {
            }
        }, constraints);
    }

    // --- Signaling (Firestore) ---

    private void sendOffer(SessionDescription sdp) {
        streamRef = db.collection("live_streams").document(liveStreamId);

        Map<String, Object> offerMap = new HashMap<>();
        offerMap.put("type", "offer");
        offerMap.put("sdp", sdp.description);

        Map<String, Object> data = new HashMap<>();
        data.put("offer", offerMap);

        streamRef.set(data, SetOptions.merge())
                .addOnSuccessListener(aVoid -> listenForAnswer())
                .addOnFailureListener(e -> Log.e(TAG, "Error writing offer", e));
    }

    private void listenForAnswer() {
        streamRef.addSnapshotListener((snapshot, e) -> {
            if (e != null || snapshot == null || !snapshot.exists())
                return;

            if (snapshot.contains("answer")) {
                Map<String, Object> answerMap = (Map<String, Object>) snapshot.get("answer");
                if (answerMap != null) {
                    String type = (String) answerMap.get("type");
                    String sdp = (String) answerMap.get("sdp");

                    if (peerConnection.getRemoteDescription() == null) {
                        SessionDescription answer = new SessionDescription(SessionDescription.Type.ANSWER, sdp);
                        peerConnection.setRemoteDescription(new SdpObserver() {
                            @Override
                            public void onCreateSuccess(SessionDescription sessionDescription) {
                            }

                            @Override
                            public void onSetSuccess() {
                                Log.d(TAG, "Remote Description Set");
                            }

                            @Override
                            public void onCreateFailure(String s) {
                            }

                            @Override
                            public void onSetFailure(String s) {
                            }
                        }, answer);
                    }
                }
            }

            // Handle Remote ICE Candidates (if needed, usually viewer sends them)
        });
    }

    private void sendIceCandidate(IceCandidate candidate) {
        if (streamRef == null)
            return;

        Map<String, Object> candidateMap = new HashMap<>();
        candidateMap.put("candidate", candidate.sdp);
        candidateMap.put("sdpMid", candidate.sdpMid);
        candidateMap.put("sdpMLineIndex", candidate.sdpMLineIndex);

        // Add to array 'ice_caller'
        streamRef.update("ice_caller", com.google.firebase.firestore.FieldValue.arrayUnion(candidateMap));
    }

    private void stopStream() {
        if (peerConnection != null) {
            peerConnection.close();
            peerConnection = null;
        }
        if (audioSource != null) {
            audioSource.dispose();
            audioSource = null;
        }
        if (peerConnectionFactory != null) {
            peerConnectionFactory.dispose();
            peerConnectionFactory = null;
        }
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel serviceChannel = new NotificationChannel(
                    CHANNEL_ID,
                    "Live Stream Service",
                    NotificationManager.IMPORTANCE_LOW);
            NotificationManager manager = getSystemService(NotificationManager.class);
            if (manager != null) {
                manager.createNotificationChannel(serviceChannel);
            }
        }
    }

    private Notification createNotification() {
        return new NotificationCompat.Builder(this, CHANNEL_ID)
                .setContentTitle("Live Audio Active")
                .setContentText("Streaming via WebRTC...")
                .setSmallIcon(android.R.drawable.ic_btn_speak_now)
                .setPriority(NotificationCompat.PRIORITY_LOW)
                .build();
    }

    @Override
    public void onDestroy() {
        stopStream();
        super.onDestroy();
    }

    @Nullable
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
