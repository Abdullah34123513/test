@php
    $streamId = $getRecord()->id;
    $status = $getRecord()->status;
@endphp

<div 
    x-data="liveAudioPlayer_{{ $streamId }}({{ $streamId }}, '{{ $status }}')"
    class="relative w-full"
>
    <!-- Controls -->
    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200">
        <div class="flex items-center gap-3">
            <button 
                @click="forceStart()"
                x-show="!isPlaying"
                class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-2 shadow transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            
            <button 
                x-show="isPlaying"
                class="bg-green-100 text-green-700 rounded-full p-2 animate-pulse cursor-default"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                </svg>
            </button>
            
            <div class="flex flex-col">
                <span class="text-xs font-bold uppercase text-gray-400 tracking-wider">Status</span>
                <span class="text-sm font-medium" x-text="isPlaying ? 'Listening Live' : (bufferHealth > 0 ? 'Ready to Play' : 'Buffering...')"></span>
            </div>
        </div>

        <!-- Buffer Indicator -->
        <div class="flex flex-col items-end">
            <span class="text-xs text-gray-400">Buffer</span>
            <div class="flex gap-1">
                <template x-for="i in 5">
                   <div class="w-1 h-3 rounded-full transition-all duration-300" 
                        :class="i <= bufferHealth ? 'bg-green-500' : 'bg-gray-200'"></div>
                </template>
            </div>
        </div>
    </div>
    
    <!-- Debug Info (Optional) -->
    <div class="mt-2 text-xs text-gray-400 flex justify-between px-1">
        <span x-text="'Seq: ' + currentSequence"></span>
        <span x-text="'Queue: ' + audioQueue.length"></span>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('liveAudioPlayer_{{ $streamId }}', (streamId, initialStatus) => ({
        audioQueue: [],
        playedChunkIds: new Set(),
        isPlaying: false,
        isLoading: true,
        bufferHealth: 0,
        currentSequence: -1,
        players: [new Audio(), new Audio()],
        activePlayerIndex: 0,
        streamId: streamId,
        pollingInterval: null,
        status: initialStatus,
        
        init() {
            console.log('🚀 Live Player V2 Initialized');
            
            // Player Setup
            this.players.forEach((p, idx) => {
                p.addEventListener('ended', () => this.handleTrackEnd(idx));
                p.addEventListener('error', (e) => this.handleTrackError(idx, e));
                p.addEventListener('playing', () => { this.isLoading = false; });
            });
            
            // Initial Fetch
            this.fetchData();
            
            // Smart Polling (3s)
            if (this.status === 'active') {
                this.pollingInterval = setInterval(() => this.fetchData(), 3000);
            }
        },

        fetchData() {
            fetch('/driver/audio-chunks/' + this.streamId)
                .then(r => r.json())
                .then(data => {
                    // Filter: Only new chunks based on ID
                    const newChunks = data
                        .sort((a, b) => a.sequence_number - b.sequence_number)
                        .filter(c => !this.playedChunkIds.has(c.id) && !this.audioQueue.some(q => q.id === c.id));

                    if (newChunks.length > 0) {
                        console.log(`📥 Received ${newChunks.length} new chunks`);
                        this.audioQueue.push(...newChunks);
                        this.updateBufferHealth();
                        
                        // Auto-Start if stalled
                        if (!this.isPlaying && this.bufferHealth > 0) {
                            this.playNext();
                        }
                    }
                })
                .catch(err => console.error('Polling Error:', err));
        },

        updateBufferHealth() {
            this.bufferHealth = this.audioQueue.length;
        },

        playNext() {
            if (this.audioQueue.length === 0) {
                console.log('⚠️ Buffer Underrun: Waiting for data...');
                this.isPlaying = false;
                this.isLoading = true;
                return;
            }

            const chunk = this.audioQueue.shift(); // Dequeue
            this.playedChunkIds.add(chunk.id); // Mark as played
            
            // Sequence Check
            if (this.currentSequence !== -1 && chunk.sequence_number !== this.currentSequence + 1) {
                console.warn(`⏩ Sequence Skip: ${this.currentSequence} -> ${chunk.sequence_number}`);
            }
            this.currentSequence = chunk.sequence_number;

            // Load and Play
            const player = this.players[this.activePlayerIndex];
            player.src = '/storage/' + chunk.file_path;
            
            console.log(`▶️ Playing Seq #${chunk.sequence_number} (Player ${this.activePlayerIndex})`);
            
            player.play()
                .then(() => {
                    this.isPlaying = true;
                    this.isLoading = false;
                    this.preloadNext(); // Prepare the OTHER player
                })
                .catch(e => {
                    console.error('Playback Start Failed:', e);
                    if (e.name === 'NotAllowedError') alert('Tap "Start Listening" to enable audio!');
                });
        },

        preloadNext() {
            if (this.audioQueue.length > 0) {
                const nextChunk = this.audioQueue[0];
                const nextPlayerIdx = (this.activePlayerIndex + 1) % 2;
                console.log(`💾 Preloading Seq #${nextChunk.sequence_number} on Player ${nextPlayerIdx}`);
                this.players[nextPlayerIdx].src = '/storage/' + nextChunk.file_path;
                this.players[nextPlayerIdx].load();
            }
        },

        handleTrackEnd(playerIdx) {
            console.log(`🏁 Track Finished (Player ${playerIdx})`);
            if (playerIdx === this.activePlayerIndex) {
                // Switch Players
                this.activePlayerIndex = (this.activePlayerIndex + 1) % 2;
                this.playNext(); // This will trigger the preloaded player
            }
        },

        handleTrackError(playerIdx, error) {
            console.error(`❌ Player ${playerIdx} Error:`, error);
            // Skip broken chunk and try next
            if (playerIdx === this.activePlayerIndex) {
                this.activePlayerIndex = (this.activePlayerIndex + 1) % 2;
                this.playNext();
            }
        },
        
        forceStart() {
            // Unlocks audio context
            this.players[0].play().catch(() => {}); 
            this.players[1].play().catch(() => {});
            this.playNext();
        }
    }));
});
</script>
