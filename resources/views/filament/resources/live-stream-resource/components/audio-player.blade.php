@php
    $streamId = $getRecord()->id;
    $status = $getRecord()->status;
    $firebaseConfig = [
        'apiKey' => env('FIREBASE_API_KEY', ''),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN', ''),
        'databaseURL' => env('FIREBASE_DATABASE_URL', ''),
        'projectId' => env('FIREBASE_PROJECT_ID', ''),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET', ''),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID', ''),
        'appId' => env('FIREBASE_APP_ID', ''),
    ];
@endphp

<div 
    x-data="webRtcPlayer_{{ $streamId }}({{ $streamId }}, {{ json_encode($firebaseConfig) }})"
    class="relative w-full"
>
    <!-- Controls -->
    <div class="flex items-center justify-between bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-4">
            <!-- Icon Status -->
            <div class="relative">
                <div x-show="connectionState === 'connected'" class="absolute -inset-1 bg-green-400 rounded-full opacity-50 animate-pulse"></div>
                <div class="relative bg-white p-2 rounded-full border" 
                     :class="{'border-green-500 text-green-600': connectionState === 'connected', 'border-gray-300 text-gray-400': connectionState !== 'connected'}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
            </div>
            
            <div class="flex flex-col">
                <span class="text-xs font-bold uppercase text-gray-500 tracking-wider">Real-time Audio</span>
                <span class="text-sm font-semibold" 
                      :class="{
                          'text-green-600': connectionState === 'connected',
                          'text-yellow-600': connectionState === 'connecting',
                          'text-red-500': connectionState === 'failed' || connectionState === 'disconnected'
                      }"
                      x-text="statusMessage">
                </span>
            </div>
        </div>

        <button 
            @click="startcall()"
            x-show="connectionState === 'new' || connectionState === 'disconnected' || connectionState === 'failed'"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow transition flex items-center gap-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
            </svg>
            Start Listening
        </button>
        
        <audio x-ref="remoteAudio" autoplay controls class="hidden"></audio>
    </div>
    
    <!-- Debug Info -->
    <div class="mt-3 p-2 bg-gray-900 text-green-400 rounded text-xs font-mono">
        <div class="flex justify-between">
            <span class="font-bold">WEBRTC DEBUG</span>
            <span x-text="connectionState" class="uppercase"></span>
        </div>
        <div>ICE State: <span x-text="iceState"></span></div>
        <div>Signaling: <span x-text="signalingState"></span></div>
        <div x-show="lastError" class="text-red-400 mt-1" x-text="'Error: ' + lastError"></div>
    </div>
</div>

<!-- Firebase SDKs -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-database-compat.js"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('webRtcPlayer_{{ $streamId }}', (streamId, firebaseConfig) => ({
        streamId: streamId,
        firebaseConfig: firebaseConfig,
        
        // WebRTC Vars
        peerConnection: null,
        database: null,
        streamRef: null,
        
        // UI State
        connectionState: 'new', // new, connecting, connected, disconnected, failed
        iceState: 'new',
        signalingState: 'stable',
        statusMessage: 'Ready to Connect',
        lastError: null,
        
        init() {
            console.log('🚀 WebRTC Player Initialized for Stream:', this.streamId);
            
            // Check Config
            if (!this.firebaseConfig.apiKey) {
                this.lastError = "Missing Firebase Config in .env";
                this.statusMessage = "Config Error";
                return;
            }

            // Initialize Firebase if not already
            if (!firebase.apps.length) {
                try {
                    firebase.initializeApp(this.firebaseConfig);
                } catch (e) {
                    console.error("Firebase Init Error", e);
                    this.lastError = "Firebase Config Invalid";
                    return;
                }
            }
            this.database = firebase.database();
            this.streamRef = this.database.ref('streams/' + this.streamId);
            
            // Auto-start if status is active? Optional.
             this.startcall();
        },

        async startcall() {
            this.connectionState = 'connecting';
            this.statusMessage = 'Initializing Peer...';
            this.lastError = null;
            
            try {
                // 1. Create Peer Connection
                const config = {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' }
                    ]
                };
                this.peerConnection = new RTCPeerConnection(config);
                
                // 2. Handle ICE Candidates (Allow TCP)
                this.peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        console.log('generated ice candidate');
                        this.streamRef.child('ice_candidates_admin').push({
                            sdp: event.candidate.candidate,
                            sdpMid: event.candidate.sdpMid,
                            sdpMLineIndex: event.candidate.sdpMLineIndex
                        });
                    }
                };

                // 3. Handle Connection State Changes
                this.peerConnection.onconnectionstatechange = () => {
                    this.connectionState = this.peerConnection.connectionState;
                    this.statusMessage = 'State: ' + this.connectionState;
                    console.log('Connection State:', this.connectionState);
                };
                
                this.peerConnection.oniceconnectionstatechange = () => {
                    this.iceState = this.peerConnection.iceConnectionState;
                    if (this.iceState === 'disconnected') {
                        this.statusMessage = 'Peer Disconnected';
                    }
                };

                this.peerConnection.onsignalingstatechange = () => {
                   this.signalingState = this.peerConnection.signalingState;
                };

                // 4. Handle Remote Stream (Audio)
                this.peerConnection.ontrack = (event) => {
                    console.log('🎤 Received Remote Track');
                    this.$refs.remoteAudio.srcObject = event.streams[0];
                    this.$refs.remoteAudio.play().catch(e => console.error("Auto-play blocked", e));
                };
                
                // 5. Add Transceiver (Audio Receive Only)
                this.peerConnection.addTransceiver('audio', { direction: 'recvonly' });

                // 6. Create Offer
                const offer = await this.peerConnection.createOffer();
                await this.peerConnection.setLocalDescription(offer);
                
                console.log('📝 Created Offer, writing to Firebase...');
                
                // 7. Write Offer to Firebase
                await this.streamRef.child('offer').set({
                    type: 'offer',
                    sdp: offer.sdp
                });
                
                // 8. Listen for Answer
                this.streamRef.child('answer').on('value', (snapshot) => {
                    const data = snapshot.val();
                    if (data && data.type === 'answer' && this.peerConnection.signalingState !== 'stable') {
                        console.log('📩 Received Answer');
                        const answer = new RTCSessionDescription(data);
                        this.peerConnection.setRemoteDescription(answer);
                    }
                });

                // 9. Listen for ICE Candidates from Device
                this.streamRef.child('ice_candidates_device').on('child_added', (snapshot) => {
                    const data = snapshot.val();
                    if (data) {
                        console.log('❄️ Received ICE Candidate');
                        this.peerConnection.addIceCandidate(new RTCIceCandidate({
                            candidate: data.sdp,
                            sdpMid: data.sdpMid,
                            sdpMLineIndex: data.sdpMLineIndex
                        })).catch(e => console.error("Add ICE Error", e));
                    }
                });
                
                this.statusMessage = 'Waiting for Device...';

            } catch (e) {
                console.error("Start Call Error", e);
                this.lastError = e.message;
                this.connectionState = 'failed';
            }
        }
    }));
});
</script>
