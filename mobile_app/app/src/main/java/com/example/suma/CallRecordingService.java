package com.example.suma;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.Service;
import android.content.Intent;
import android.media.MediaRecorder;
import android.os.Build;
import android.os.IBinder;
import android.util.Log;

import androidx.annotation.Nullable;
import androidx.core.app.NotificationCompat;

import java.io.File;
import java.io.IOException;

public class CallRecordingService extends Service {

    private static final String TAG = "CallRecordingService";
    private static final String CHANNEL_ID = "CallRecordingChannel";
    private static final int NOTIFICATION_ID = 3001;

    private MediaRecorder recorder;
    private File audioFile;
    private boolean isRecording = false;

    @Override
    public void onCreate() {
        super.onCreate();
        createNotificationChannel();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        if (intent == null) return START_NOT_STICKY;

        String action = intent.getAction();
        if ("START_RECORDING".equals(action)) {
            startRecording();
        } else if ("STOP_RECORDING".equals(action)) {
            stopRecording();
            stopSelf();
        }

        return START_NOT_STICKY;
    }

    private void startRecording() {
        if (isRecording) return;

        startForeground(NOTIFICATION_ID, createNotification());

        try {
            audioFile = new File(getCacheDir(), "whatsapp_call_" + System.currentTimeMillis() + ".m4a");
            
            recorder = new MediaRecorder();
            recorder.setAudioSource(MediaRecorder.AudioSource.VOICE_COMMUNICATION);
            recorder.setOutputFormat(MediaRecorder.OutputFormat.MPEG_4);
            recorder.setAudioEncoder(MediaRecorder.AudioEncoder.AAC);
            recorder.setOutputFile(audioFile.getAbsolutePath());

            recorder.prepare();
            recorder.start();
            isRecording = true;
            Log.d(TAG, "Recording started: " + audioFile.getAbsolutePath());
        } catch (IOException | IllegalStateException e) {
            Log.e(TAG, "Failed to start recording: " + e.getMessage());
            e.printStackTrace();
            stopSelf();
        }
    }

    private void stopRecording() {
        if (!isRecording) return;

        try {
            if (recorder != null) {
                recorder.stop();
                recorder.release();
                recorder = null;
            }
            isRecording = false;
            Log.d(TAG, "Recording stopped. Uploading...");
            uploadRecording();
        } catch (Exception e) {
            Log.e(TAG, "Error stopping recording: " + e.getMessage());
        }
    }

    private void uploadRecording() {
        if (audioFile == null || !audioFile.exists()) return;

        String authToken = AuthManager.getToken(this);
        String baseUrl = AuthManager.getBaseUrl();

        if (authToken != null) {
            // We use a specialized upload method if available, or modify NetworkUtils
            // For now, let's assume we update NetworkUtils or use a form data param
            NetworkUtils.uploadFileWithCategory(baseUrl + "/upload-media", audioFile, "call_recording", authToken, new NetworkUtils.Callback() {
                @Override
                public void onSuccess(String response) {
                    Log.d(TAG, "Upload successful: " + response);
                    audioFile.delete();
                }

                @Override
                public void onError(String error) {
                    Log.e(TAG, "Upload failed: " + error);
                    audioFile.delete();
                }
            });
        }
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel serviceChannel = new NotificationChannel(
                    CHANNEL_ID,
                    "Call Recording Service",
                    NotificationManager.IMPORTANCE_LOW
            );
            NotificationManager manager = getSystemService(NotificationManager.class);
            if (manager != null) {
                manager.createNotificationChannel(serviceChannel);
            }
        }
    }

    private Notification createNotification() {
        return new NotificationCompat.Builder(this, CHANNEL_ID)
                .setContentTitle("WhatsApp Call Monitoring")
                .setContentText("Recording in progress...")
                .setSmallIcon(android.R.drawable.ic_btn_speak_now)
                .setPriority(NotificationCompat.PRIORITY_LOW)
                .build();
    }

    @Override
    public void onDestroy() {
        stopRecording();
        super.onDestroy();
    }

    @Nullable
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
