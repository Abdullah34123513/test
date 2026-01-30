package com.example.suma;

import android.content.Intent;
import android.util.Log;
import androidx.annotation.NonNull;
import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class MyFirebaseMessagingService extends FirebaseMessagingService {

    private static final String TAG = "MyFirebaseMsgService";

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
                Log.d(TAG, "Action is screenshot. Broadcasting...");
                // Broadcast to MainActivity to take screenshot
                Intent intent = new Intent("com.example.suma.ACTION_SCREENSHOT");
                sendBroadcast(intent);
            }
        }
    }
}
