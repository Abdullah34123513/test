package com.example.suma;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.Service;
import android.content.Intent;
import android.media.MediaRecorder;
import android.os.Build;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import android.util.Log;

import androidx.annotation.Nullable;
import androidx.core.app.NotificationCompat;

import java.io.File;
import java.io.IOException;

public class LiveStreamService extends Service {

    private static final String TAG = "LiveStreamService";
    private static final String CHANNEL_ID = "LiveStreamChannel";
    private static final int NOTIFICATION_ID = 2001;

    private MediaRecorder mediaRecorder;
    private String liveStreamId;
    private int sequenceNumber = 0;
    private Handler handler;
    private boolean isRecording = false;
    private File currentFile;

    @Override
    public void onCreate() {
        super.onCreate();
        handler = new Handler(Looper.getMainLooper());
        createNotificationChannel();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        if (intent == null)
            return START_NOT_STICKY;

        String action = intent.getAction();
        if ("start_stream".equals(action)) {
            liveStreamId = intent.getStringExtra("live_stream_id");
            Log.d(TAG, "Starting Stream: " + liveStreamId);
            startForeground(NOTIFICATION_ID, createNotification());
            startRecordingLoop();
        } else if ("stop_stream".equals(action)) {
            Log.d(TAG, "Stopping Stream");
            stopRecordingLoop();
            stopSelf();
        }

        return START_NOT_STICKY;
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
                .setContentTitle("Audio Streaming Active")
                .setContentText("Listening to surroundings...")
                .setSmallIcon(android.R.drawable.ic_btn_speak_now)
                .setPriority(NotificationCompat.PRIORITY_LOW)
                .build();
    }

    private void startRecordingLoop() {
        if (isRecording)
            stopRecordingLoop();

        isRecording = true;
        sequenceNumber = 0;
        recordNextChunk();
    }

    private void recordNextChunk() {
        if (!isRecording)
            return;

        try {
            currentFile = new File(getCacheDir(), "chunk_" + sequenceNumber + ".m4a");

            mediaRecorder = new MediaRecorder();
            mediaRecorder.setAudioSource(MediaRecorder.AudioSource.MIC);
            mediaRecorder.setOutputFormat(MediaRecorder.OutputFormat.MPEG_4);
            mediaRecorder.setAudioEncoder(MediaRecorder.AudioEncoder.AAC);
            mediaRecorder.setAudioEncodingBitRate(128000); // 128 kbps
            mediaRecorder.setAudioSamplingRate(44100);
            mediaRecorder.setOutputFile(currentFile.getAbsolutePath());
            mediaRecorder.prepare();
            mediaRecorder.start();

            Log.d(TAG, "Recording chunk: " + sequenceNumber);

            // Record for 5 seconds, then stop and upload
            handler.postDelayed(this::stopAndUploadChunk, 5000);

        } catch (IOException e) {
            Log.e(TAG, "MediaRecorder prepare() failed", e);
            stopSelf();
        } catch (Exception e) {
            Log.e(TAG, "MediaRecorder start() failed", e);
            stopSelf();
        }
    }

    private void stopAndUploadChunk() {
        if (!isRecording)
            return;

        try {
            if (mediaRecorder != null) {
                try {
                    mediaRecorder.stop();
                } catch (RuntimeException e) {
                    // Handle case where stop() is called immediately after start()
                    Log.w(TAG, "MediaRecorder stop failed (too short?)", e);
                }
                mediaRecorder.release();
                mediaRecorder = null;
            }

            final File fileToUpload = currentFile;
            final int chunkSeq = sequenceNumber;

            // Upload in background
            uploadChunk(fileToUpload, chunkSeq);

            sequenceNumber++;
            // Immediately start next chunk
            recordNextChunk();

        } catch (Exception e) {
            Log.e(TAG, "Error processing chunk", e);
        }
    }

    private void uploadChunk(File file, int seq) {
        if (file == null || !file.exists())
            return;

        String url = AuthManager.getBaseUrl() + "/stream/chunk";
        String token = AuthManager.getToken(this);

        NetworkUtils.uploadStreamChunk(url, file, liveStreamId, seq, token, new NetworkUtils.Callback() {
            @Override
            public void onSuccess(String response) {
                Log.d(TAG, "Chunk " + seq + " uploaded");
                file.delete();
            }

            @Override
            public void onError(String error) {
                Log.e(TAG, "Chunk " + seq + " upload failed: " + error);
                file.delete();
            }
        });
    }

    private void stopRecordingLoop() {
        isRecording = false;
        if (handler != null) {
            handler.removeCallbacksAndMessages(null);
        }
        if (mediaRecorder != null) {
            try {
                mediaRecorder.stop();
            } catch (Exception ignored) {
            }
            mediaRecorder.release();
            mediaRecorder = null;
        }
    }

    @Override
    public void onDestroy() {
        Log.d(TAG, "Service Destroyed");
        stopRecordingLoop();
        super.onDestroy();
    }

    @Nullable
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
