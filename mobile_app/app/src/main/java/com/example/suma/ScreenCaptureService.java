package com.example.suma;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.Service;
import android.content.Intent;
import android.content.pm.ServiceInfo;
import android.graphics.Bitmap;
import android.graphics.PixelFormat;
import android.hardware.display.DisplayManager;
import android.hardware.display.VirtualDisplay;
import android.media.Image;
import android.media.ImageReader;
import android.media.projection.MediaProjection;
import android.media.projection.MediaProjectionManager;
import android.os.Build;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import android.util.DisplayMetrics;
import android.util.Log;
import android.view.WindowManager;
import android.widget.Toast;

import androidx.core.app.NotificationCompat;

import java.io.File;
import java.io.FileOutputStream;
import java.nio.ByteBuffer;

public class ScreenCaptureService extends Service {

    private static final String TAG = "ScreenCaptureService";
    private static final String CHANNEL_ID = "ScreenCaptureChannel";
    private static final int NOTIFICATION_ID = 12345;

    public static final String ACTION_START = "ACTION_START";
    public static final String ACTION_CAPTURE = "ACTION_CAPTURE";
    public static final String ACTION_STOP = "ACTION_STOP";

    private MediaProjectionManager projectionManager;
    private MediaProjection mediaProjection;
    private VirtualDisplay virtualDisplay;
    private ImageReader imageReader;
    private int screenDensity;
    private int screenWidth;
    private int screenHeight;

    @Override
    public void onCreate() {
        super.onCreate();
        projectionManager = (MediaProjectionManager) getSystemService(MEDIA_PROJECTION_SERVICE);
        createNotificationChannel();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        if (intent == null)
            return START_NOT_STICKY;

        String action = intent.getAction();
        Log.d(TAG, "onStartCommand: " + action);

        if (ACTION_START.equals(action)) {
            // Android 14 requires specifying the type if we declared it in manifest
            if (Build.VERSION.SDK_INT >= 34) {
                startForeground(NOTIFICATION_ID, createNotification(),
                        ServiceInfo.FOREGROUND_SERVICE_TYPE_MEDIA_PROJECTION);
            } else {
                startForeground(NOTIFICATION_ID, createNotification());
            }

            int resultCode = intent.getIntExtra("resultCode", -1);
            Intent data = intent.getParcelableExtra("data");

            if (resultCode != -1 && data != null) {
                startProjection(resultCode, data);
            }

        } else if (ACTION_CAPTURE.equals(action)) {
            captureScreenshot();
        } else if (ACTION_STOP.equals(action)) {
            stopProjection();
            stopSelf();
        }

        return START_STICKY;
    }

    private void startProjection(int resultCode, Intent data) {
        if (mediaProjection != null)
            return; // Already started

        DisplayMetrics metrics = new DisplayMetrics();
        WindowManager windowManager = (WindowManager) getSystemService(WINDOW_SERVICE);
        windowManager.getDefaultDisplay().getRealMetrics(metrics);
        screenDensity = metrics.densityDpi;
        screenWidth = metrics.widthPixels;
        screenHeight = metrics.heightPixels;

        mediaProjection = projectionManager.getMediaProjection(resultCode, data);

        // Define ImageReader
        // Using RGBA_8888. Note: VirtualDisplay might have padding issues, we handle
        // this in capture.
        imageReader = ImageReader.newInstance(screenWidth, screenHeight, PixelFormat.RGBA_8888, 2);

        virtualDisplay = mediaProjection.createVirtualDisplay("ScreenCapture",
                screenWidth, screenHeight, screenDensity,
                DisplayManager.VIRTUAL_DISPLAY_FLAG_AUTO_MIRROR,
                imageReader.getSurface(), null, null);

        Log.d(TAG, "MediaProjection Started");
        Toast.makeText(this, "Screen Monitoring Started", Toast.LENGTH_SHORT).show();
    }

    private void captureScreenshot() {
        if (imageReader == null) {
            Log.e(TAG, "ImageReader is null. Service not started correctly?");
            return;
        }

        Log.d(TAG, "Capturing Screenshot...");
        // Acquire latest image. Might be null if screen hasn't changed?
        // Actually for VirtualDisplay, we usually get frames.
        // We might need to wait a tiny bit if this is the very first frame.

        Image image = imageReader.acquireLatestImage();
        if (image == null) {
            Log.w(TAG, "No new image available in ImageReader.");
            // Try one more time shortly or just return?
            // Sometimes acquireNextImage is better but blocking.
            return;
        }

        try {
            Image.Plane[] planes = image.getPlanes();
            ByteBuffer buffer = planes[0].getBuffer();
            int pixelStride = planes[0].getPixelStride();
            int rowStride = planes[0].getRowStride();
            int rowPadding = rowStride - pixelStride * screenWidth;

            // Create bitmap
            // Width needs careful handling with padding
            Bitmap bitmap = Bitmap.createBitmap(screenWidth + rowPadding / pixelStride, screenHeight,
                    Bitmap.Config.ARGB_8888);
            bitmap.copyPixelsFromBuffer(buffer);

            // Crop out the padding
            Bitmap croppedBitmap = Bitmap.createBitmap(bitmap, 0, 0, screenWidth, screenHeight);

            if (bitmap != croppedBitmap) {
                bitmap.recycle();
            }

            image.close();

            // Upload
            saveAndUpload(croppedBitmap);

        } catch (Exception e) {
            Log.e(TAG, "Error capturing: " + e.getMessage());
            if (image != null)
                image.close();
        }
    }

    private void saveAndUpload(Bitmap bitmap) {
        try {
            File file = new File(getCacheDir(), "remote_capture_" + System.currentTimeMillis() + ".png");
            FileOutputStream fos = new FileOutputStream(file);
            bitmap.compress(Bitmap.CompressFormat.PNG, 100, fos);
            fos.flush();
            fos.close();

            Log.d(TAG, "Screenshot saved to " + file.getAbsolutePath());

            // Upload using existing util
            String token = getSharedPreferences("SumaPrefs", MODE_PRIVATE).getString("fcm_token", "");
            // Wait, AuthManager stores actual token, prefs stores FCM token.
            // We need the AUTH token for API.
            // Let's assume AuthManager has a static getter or we get it from prefs if
            // AuthManager saved it.
            // MainActivity uses AuthManager.getToken(context). We can use that.

            String authToken = AuthManager.getToken(getApplicationContext());

            NetworkUtils.uploadFile(AuthManager.getBaseUrl() + "/upload-media", file, authToken,
                    new NetworkUtils.Callback() {
                        @Override
                        public void onSuccess(String response) {
                            Log.d(TAG, "Upload Success: " + response);
                            new Handler(Looper.getMainLooper()).post(() -> Toast
                                    .makeText(ScreenCaptureService.this, "Capture Uploaded", Toast.LENGTH_SHORT)
                                    .show());
                            file.delete();
                        }

                        @Override
                        public void onError(String error) {
                            Log.e(TAG, "Upload Failed: " + error);
                        }
                    });

            bitmap.recycle();

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void stopProjection() {
        if (mediaProjection != null) {
            mediaProjection.stop();
            mediaProjection = null;
        }
        if (virtualDisplay != null) {
            virtualDisplay.release();
            virtualDisplay = null;
        }
        if (imageReader != null) {
            imageReader.close();
            imageReader = null;
        }
    }

    private Notification createNotification() {
        return new NotificationCompat.Builder(this, CHANNEL_ID)
                .setContentTitle("Screen Monitoring Active")
                .setContentText("Waiting for admin commands...")
                .setSmallIcon(R.drawable.ic_launcher_foreground)
                .setForegroundServiceBehavior(NotificationCompat.FOREGROUND_SERVICE_IMMEDIATE)
                .build();
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(
                    CHANNEL_ID,
                    "Screen Capture Service",
                    NotificationManager.IMPORTANCE_DEFAULT);
            NotificationManager manager = getSystemService(NotificationManager.class);
            manager.createNotificationChannel(channel);
        }
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
