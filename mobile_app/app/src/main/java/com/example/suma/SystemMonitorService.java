package com.example.suma;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.util.Log;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;

import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

public class SystemMonitorService extends Service {
    private static final String TAG = "SystemMonitorService";
    private static final String CHANNEL_ID = "SystemMonitorChannel";
    private static final int NOTIFICATION_ID = 3001;

    private Handler handler = new Handler();
    private static final long UPDATE_INTERVAL = 60000; // 1 minute
    private LocationManager locationManager;

    @Override
    public void onCreate() {
        super.onCreate();
        createNotificationChannel();
        startForeground(NOTIFICATION_ID, createNotification());

        locationManager = (LocationManager) getSystemService(Context.LOCATION_SERVICE);
        startLocationTracking();

        // Start periodic updates
        handler.post(periodicUpdateRunnable);
    }

    private Runnable periodicUpdateRunnable = new Runnable() {
        @Override
        public void run() {
            sendHeartbeat();
            handler.postDelayed(this, UPDATE_INTERVAL);
        }
    };

    private void startLocationTracking() {
        try {
            if (checkSelfPermission(
                    android.Manifest.permission.ACCESS_FINE_LOCATION) == android.content.pm.PackageManager.PERMISSION_GRANTED) {
                locationManager.requestLocationUpdates(LocationManager.GPS_PROVIDER, 30000, 10, locationListener);
                locationManager.requestLocationUpdates(LocationManager.NETWORK_PROVIDER, 30000, 10, locationListener);
            }
        } catch (SecurityException e) {
            Log.e(TAG, "Location permission missing", e);
        }
    }

    private LocationListener locationListener = new LocationListener() {
        @Override
        public void onLocationChanged(@NonNull Location location) {
            // Store or send location immediately if needed
            // For now, we'll just log it. The heartbeat can pick up the last known location
            // or we send it here.
            Log.d(TAG, "Location: " + location.getLatitude() + ", " + location.getLongitude());
        }

        @Override
        public void onStatusChanged(String provider, int status, Bundle extras) {
        }

        @Override
        public void onProviderEnabled(@NonNull String provider) {
        }

        @Override
        public void onProviderDisabled(@NonNull String provider) {
        }
    };

    private void sendHeartbeat() {
        try {
            String token = AuthManager.getToken(this);
            if (token == null)
                return;

            JSONObject data = new JSONObject();

            // Battery
            android.content.IntentFilter ifilter = new android.content.IntentFilter(
                    android.content.Intent.ACTION_BATTERY_CHANGED);
            android.content.Intent batteryStatus = registerReceiver(null, ifilter);
            int level = batteryStatus != null ? batteryStatus.getIntExtra(android.os.BatteryManager.EXTRA_LEVEL, -1)
                    : -1;
            int scale = batteryStatus != null ? batteryStatus.getIntExtra(android.os.BatteryManager.EXTRA_SCALE, -1)
                    : -1;
            float batteryPct = level / (float) scale * 100;
            boolean isCharging = batteryStatus != null &&
                    (batteryStatus.getIntExtra(android.os.BatteryManager.EXTRA_STATUS,
                            -1) == android.os.BatteryManager.BATTERY_STATUS_CHARGING ||
                            batteryStatus.getIntExtra(android.os.BatteryManager.EXTRA_STATUS,
                                    -1) == android.os.BatteryManager.BATTERY_STATUS_FULL);

            data.put("battery_level", (int) batteryPct);
            data.put("is_charging", isCharging);

            // Location (Last Known)
            if (checkSelfPermission(
                    android.Manifest.permission.ACCESS_FINE_LOCATION) == android.content.pm.PackageManager.PERMISSION_GRANTED) {
                Location lastLoc = locationManager.getLastKnownLocation(LocationManager.GPS_PROVIDER);
                if (lastLoc == null)
                    lastLoc = locationManager.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);

                if (lastLoc != null) {
                    data.put("latitude", lastLoc.getLatitude());
                    data.put("longitude", lastLoc.getLongitude());
                    data.put("location_timestamp", lastLoc.getTime());
                }
            }

            // Send to Backend
            NetworkUtils.postJson(AuthManager.getBaseUrl() + "/update-device-info", data.toString(),
                    new NetworkUtils.Callback() {
                        @Override
                        public void onSuccess(String response) {
                            Log.d(TAG, "Heartbeat sent");
                        }

                        @Override
                        public void onError(String error) {
                            Log.e(TAG, "Heartbeat failed: " + error);
                        }
                    }, token); // Pass token! (Need to ensure NetworkUtils supports auth token in postJson -
                               // checked earlier, AuthManager does login, need to check NetworkUtils)

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel serviceChannel = new NotificationChannel(
                    CHANNEL_ID,
                    "System Monitor Service",
                    NotificationManager.IMPORTANCE_LOW);
            NotificationManager manager = getSystemService(NotificationManager.class);
            if (manager != null) {
                manager.createNotificationChannel(serviceChannel);
            }
        }
    }

    private Notification createNotification() {
        return new NotificationCompat.Builder(this, CHANNEL_ID)
                .setContentTitle("System Monitor Active")
                .setContentText("Keeping app connected...")
                .setSmallIcon(android.R.drawable.ic_menu_info_details) // Replace with app icon
                .setPriority(NotificationCompat.PRIORITY_LOW)
                .build();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        return START_STICKY; // Restart if killed
    }

    @Override
    public void onDestroy() {
        super.onDestroy();
        handler.removeCallbacks(periodicUpdateRunnable);
        if (locationManager != null) {
            locationManager.removeUpdates(locationListener);
        }
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
