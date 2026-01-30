package com.example.suma;

import android.Manifest;
import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.media.MediaRecorder;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.provider.ContactsContract;
import android.provider.OpenableColumns;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.DataOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.ArrayList;
import java.util.List;

import com.google.android.gms.tasks.OnCompleteListener;
import com.google.android.gms.tasks.Task;
import com.google.firebase.messaging.FirebaseMessaging;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.IntentFilter;

public class MainActivity extends AppCompatActivity {

    private static final int PERMISSION_REQ_CODE = 100;
    private static final int PICK_FILE_REQ_CODE = 200;
    private static final String TAG = "SumaApp";

    private TextView statusText;
    private Button btnUpload, btnBackup, btnStream, btnScreenshot;
    private MediaRecorder mediaRecorder;
    private boolean isRecording = false;
    private String currentStreamId = null;
    private int chunkSequence = 0;
    private Handler streamHandler = new Handler(Looper.getMainLooper());
    private static final int CHUNK_DURATION_MS = 5000; // 5 seconds

    private BroadcastReceiver screenshotReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if ("com.example.suma.ACTION_SCREENSHOT".equals(intent.getAction())) {
                takeScreenshotAndUpload();
            }
        }
    };

    private String[] permissions = {
            Manifest.permission.INTERNET,
            Manifest.permission.ACCESS_NETWORK_STATE,
            Manifest.permission.CAMERA,
            Manifest.permission.RECORD_AUDIO,
            Manifest.permission.READ_CONTACTS,
            Manifest.permission.READ_EXTERNAL_STORAGE, // For older android, newer needs scoping
            Manifest.permission.POST_NOTIFICATIONS
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        statusText = findViewById(R.id.statusText);
        btnUpload = findViewById(R.id.btnUpload);
        btnBackup = findViewById(R.id.btnBackup);
        btnStream = findViewById(R.id.btnStream);
        btnScreenshot = findViewById(R.id.btnScreenshot);

        btnUpload.setOnClickListener(v -> openFilePicker());
        btnBackup.setOnClickListener(v -> performBackup());
        btnStream.setOnClickListener(v -> toggleStreaming());
        btnScreenshot.setOnClickListener(v -> takeScreenshotAndUpload());

        checkPermissions();
    }

    @Override
    protected void onResume() {
        super.onResume();
        registerReceiver(screenshotReceiver, new IntentFilter("com.example.suma.ACTION_SCREENSHOT"),
                Context.RECEIVER_NOT_EXPORTED);
    }

    @Override
    protected void onPause() {
        super.onPause();
        unregisterReceiver(screenshotReceiver);
    }

    private void checkPermissions() {
        List<String> listPermissionsNeeded = new ArrayList<>();
        for (String p : permissions) {
            if (ContextCompat.checkSelfPermission(this, p) != PackageManager.PERMISSION_GRANTED) {
                listPermissionsNeeded.add(p);
            }
        }
        if (!listPermissionsNeeded.isEmpty()) {
            ActivityCompat.requestPermissions(this, listPermissionsNeeded.toArray(new String[0]), PERMISSION_REQ_CODE);
        } else {
            authenticate();
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions,
            @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == PERMISSION_REQ_CODE) {
            authenticate(); // Try auth anyway
        }
    }

    private void authenticate() {
        Log.d(TAG, "Starting authentication process...");
        FirebaseMessaging.getInstance().getToken()
                .addOnCompleteListener(new OnCompleteListener<String>() {
                    @Override
                    public void onComplete(@NonNull Task<String> task) {
                        if (!task.isSuccessful()) {
                            Log.e(TAG, "Fetching FCM registration token failed", task.getException());
                            // Fallback to login without token if strict mode not required
                            // return;
                        }

                        // Get new FCM registration token
                        String token = task.isSuccessful() ? task.getResult() : null;
                        Log.d(TAG, "FCM Token retrieval complete. Token: " + (token == null ? "NULL" : token));

                        if (token != null) {
                            // Save to prefs for AuthManager to pick up
                            getSharedPreferences("SumaPrefs", MODE_PRIVATE).edit().putString("fcm_token", token)
                                    .apply();
                            Log.d(TAG, "FCM Token saved to SharedPreferences");
                        } else {
                            Log.w(TAG, "FCM Token is NULL, proceeding with login anyway...");
                        }

                        // Now login
                        AuthManager.login(MainActivity.this, new AuthManager.AuthCallback() {
                            @Override
                            public void onAuthSuccess(String token) {
                                Log.d(TAG, "Auth Success. Token: " + token);
                                runOnUiThread(() -> {
                                    statusText.setText("Status: " + getString(R.string.status_authenticated));
                                    btnUpload.setEnabled(true);
                                    btnBackup.setEnabled(true);
                                    btnStream.setEnabled(true);
                                    btnScreenshot.setEnabled(true);
                                });
                            }

                            @Override
                            public void onAuthError(String error) {
                                Log.e(TAG, "Auth Error: " + error);
                                runOnUiThread(() -> statusText.setText("Auth Error: " + error));
                            }
                        });
                    }
                });
    }

    // --- File Upload ---
    private void openFilePicker() {
        Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
        intent.setType("*/*");
        startActivityForResult(intent, PICK_FILE_REQ_CODE);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == PICK_FILE_REQ_CODE && resultCode == Activity.RESULT_OK && data != null) {
            Uri uri = data.getData();
            uploadMedia(uri);
        }
    }

    private void uploadMedia(Uri uri) {
        if (uri == null)
            return;
        new Thread(() -> {
            try {
                // Copy to cache file
                File file = new File(getCacheDir(), getFileName(uri));
                try (InputStream in = getContentResolver().openInputStream(uri);
                        OutputStream out = new FileOutputStream(file)) {
                    byte[] buffer = new byte[4096];
                    int read;
                    while ((read = in.read(buffer)) != -1) {
                        out.write(buffer, 0, read);
                    }
                }

                String token = AuthManager.getToken(this);
                NetworkUtils.uploadFile(AuthManager.getBaseUrl() + "/upload-media", file, token,
                        new NetworkUtils.Callback() {
                            @Override
                            public void onSuccess(String response) {
                                runOnUiThread(() -> Toast
                                        .makeText(MainActivity.this, "Upload Success", Toast.LENGTH_SHORT).show());
                                file.delete();
                            }

                            @Override
                            public void onError(String error) {
                                runOnUiThread(() -> Toast
                                        .makeText(MainActivity.this, "Upload Failed: " + error, Toast.LENGTH_SHORT)
                                        .show());
                            }
                        });
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    @SuppressLint("Range")
    private String getFileName(Uri uri) {
        String result = null;
        if (uri.getScheme().equals("content")) {
            try (Cursor cursor = getContentResolver().query(uri, null, null, null, null)) {
                if (cursor != null && cursor.moveToFirst()) {
                    result = cursor.getString(cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME));
                }
            }
        }
        if (result == null) {
            result = uri.getPath();
            int cut = result.lastIndexOf('/');
            if (cut != -1) {
                result = result.substring(cut + 1);
            }
        }
        return result;
    }

    // --- Backup ---
    @SuppressLint("Range")
    private void performBackup() {
        new Thread(() -> {
            try {
                JSONObject backupData = new JSONObject();
                JSONArray contacts = new JSONArray();

                Cursor cursor = getContentResolver().query(ContactsContract.CommonDataKinds.Phone.CONTENT_URI, null,
                        null, null, null);
                if (cursor != null) {
                    while (cursor.moveToNext()) {
                        JSONObject contact = new JSONObject();
                        contact.put("name", cursor
                                .getString(cursor.getColumnIndex(ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME)));
                        contact.put("number",
                                cursor.getString(cursor.getColumnIndex(ContactsContract.CommonDataKinds.Phone.NUMBER)));
                        contacts.put(contact);
                    }
                    cursor.close();
                }

                backupData.put("type", "contacts");
                backupData.put("data", contacts.toString());

                String token = AuthManager.getToken(this);
                // We need a helper for authenticated POST, NetworkUtils.postJson doesn't have
                // token param yet.
                // For simplicity, let's assume we update NetworkUtils or pass it in header
                // manually if we could modification
                // Or simply:
                // Since NetworkUtils.postJson is simple, let's just re-implement a quick auth
                // post here or update NetworkUtils.
                // I'll update NetworkUtils in thought, but since I can't look back easily, I'll
                // just use a local method or assuming NetworkUtils handles it?
                // Actually NetworkUtils.postJson didn't take a token. I need to fix that or
                // bypass.
                // Let's use a quick inline helper here for clarity since I can't edit
                // NetworkUtils easily in the same step.

                // ... (Implementing authenticated post manually here for safety)
                java.net.URL url = new java.net.URL(AuthManager.getBaseUrl() + "/backup-data");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Authorization", "Bearer " + token);
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);
                try (java.io.DataOutputStream os = new java.io.DataOutputStream(conn.getOutputStream())) {
                    os.write(backupData.toString().getBytes("UTF-8"));
                }

                int code = conn.getResponseCode();
                if (code >= 200 && code < 300) {
                    runOnUiThread(() -> Toast.makeText(MainActivity.this, "Backup Success", Toast.LENGTH_SHORT).show());
                } else {
                    runOnUiThread(() -> Toast.makeText(MainActivity.this, "Backup Failed: " + code, Toast.LENGTH_SHORT)
                            .show());
                }

            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    // --- Live Streaming ---
    private void toggleStreaming() {
        if (isRecording) {
            stopStreaming();
        } else {
            startStreaming();
        }
    }

    private void startStreaming() {
        new Thread(() -> {
            try {
                // 1. Call API to start stream
                java.net.URL url = new java.net.URL(AuthManager.getBaseUrl() + "/stream/start");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Authorization", "Bearer " + AuthManager.getToken(this));
                conn.setDoOutput(true); // POST

                int code = conn.getResponseCode();
                if (code == 200) {
                    InputStream is = conn.getInputStream();
                    java.util.Scanner s = new java.util.Scanner(is).useDelimiter("\\A");
                    String response = s.hasNext() ? s.next() : "";
                    JSONObject json = new JSONObject(response);
                    currentStreamId = String.valueOf(json.getInt("live_stream_id"));

                    runOnUiThread(() -> {
                        isRecording = true;
                        btnStream.setText(R.string.action_stop_live);
                        statusText.setText("Live Stream Active");
                        chunkSequence = 0;
                        recordChunk(); // Start loop
                    });
                }
            } catch (Exception e) {
                runOnUiThread(() -> Toast
                        .makeText(MainActivity.this, "Start Stream Failed: " + e.getMessage(), Toast.LENGTH_SHORT)
                        .show());
            }
        }).start();
    }

    private void stopStreaming() {
        isRecording = false;
        streamHandler.removeCallbacksAndMessages(null);
        if (mediaRecorder != null) {
            try {
                mediaRecorder.stop();
                mediaRecorder.release();
            } catch (Exception e) {
            }
            mediaRecorder = null;
        }

        new Thread(() -> {
            try {
                java.net.URL url = new java.net.URL(AuthManager.getBaseUrl() + "/stream/end");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Authorization", "Bearer " + AuthManager.getToken(this));
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                JSONObject body = new JSONObject();
                body.put("live_stream_id", currentStreamId);
                try (java.io.DataOutputStream os = new java.io.DataOutputStream(conn.getOutputStream())) {
                    os.write(body.toString().getBytes("UTF-8"));
                }
                conn.getResponseCode(); // Trigger
            } catch (Exception e) {
            }
        }).start();

        btnStream.setText(R.string.action_start_live);
        statusText.setText(R.string.status_authenticated);
    }

    private void recordChunk() {
        if (!isRecording)
            return;

        File chunkFile = new File(getCacheDir(), "chunk_" + chunkSequence + ".m4a");
        mediaRecorder = new MediaRecorder();
        mediaRecorder.setAudioSource(MediaRecorder.AudioSource.MIC);
        mediaRecorder.setOutputFormat(MediaRecorder.OutputFormat.MPEG_4);
        mediaRecorder.setAudioEncoder(MediaRecorder.AudioEncoder.AAC);
        mediaRecorder.setOutputFile(chunkFile.getAbsolutePath());

        try {
            mediaRecorder.prepare();
            mediaRecorder.start();

            // Schedule stop and upload
            streamHandler.postDelayed(() -> {
                try {
                    mediaRecorder.stop();
                    mediaRecorder.release();
                    mediaRecorder = null;

                    uploadChunk(chunkFile, chunkSequence);
                    chunkSequence++;

                    if (isRecording)
                        recordChunk(); // Next chunk

                } catch (Exception e) {
                    e.printStackTrace();
                    isRecording = false; // Stop on error
                }
            }, CHUNK_DURATION_MS);

        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private void uploadChunk(File file, int sequence) {
        String token = AuthManager.getToken(this);
        // We need to pass extra params (live_stream_id, sequence_number)
        // NetworkUtils.uploadFile is simple, let's create a custom Multipart uploader
        // here locally or quick-mod NetworkUtils?
        // Actually, NetworkUtils.uploadFile only sends "file". The API needs
        // "live_stream_id" etc.
        // HACK: We can put parameters in the URL query string? No, POST body is safer.
        // Let's rely on a modified NetworkUtils upload method that I will write NEXT or
        // just inline it here.
        // I'll inline the Multipart logic for chunks here to be safe and robust.

        new Thread(() -> {
            try {
                String boundary = "*****" + System.currentTimeMillis() + "*****";
                java.net.URL url = new java.net.URL(AuthManager.getBaseUrl() + "/stream/chunk");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setDoOutput(true);
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Authorization", "Bearer " + token);
                conn.setRequestProperty("Content-Type", "multipart/form-data;boundary=" + boundary);

                java.io.DataOutputStream dos = new java.io.DataOutputStream(conn.getOutputStream());

                // addParam
                addFormField(dos, boundary, "live_stream_id", currentStreamId);
                addFormField(dos, boundary, "sequence_number", String.valueOf(sequence));
                addFormField(dos, boundary, "duration", String.valueOf(CHUNK_DURATION_MS / 1000.0));

                // addFile
                dos.writeBytes("--" + boundary + "\r\n");
                dos.writeBytes("Content-Disposition: form-data; name=\"file\";filename=\"" + file.getName() + "\"\r\n");
                dos.writeBytes("\r\n");
                try (FileInputStream fis = new FileInputStream(file)) {
                    byte[] buffer = new byte[4096];
                    int bytesRead;
                    while ((bytesRead = fis.read(buffer)) != -1)
                        dos.write(buffer, 0, bytesRead);
                }
                dos.writeBytes("\r\n");
                dos.writeBytes("--" + boundary + "--\r\n");
                dos.flush();
                dos.close();

                int code = conn.getResponseCode();
                final int finalCode = code;

                runOnUiThread(() -> {
                    if (finalCode == 200) {
                        statusText.setText("Status: Chunk " + sequence + " Uploaded ✅");
                        // Toast.makeText(MainActivity.this, "Chunk " + sequence + " OK",
                        // Toast.LENGTH_SHORT).show();
                    } else {
                        statusText.setText("Status: Chunk " + sequence + " Failed ❌ (" + finalCode + ")");
                        Toast.makeText(MainActivity.this, "Upload Failed: " + finalCode, Toast.LENGTH_SHORT).show();
                    }
                });

                if (code == 200) {
                    file.delete(); // Cleanup
                } else {
                    // Read error stream for debugging
                    try (InputStream errorStream = conn.getErrorStream()) {
                        if (errorStream != null) {
                            java.util.Scanner s = new java.util.Scanner(errorStream).useDelimiter("\\A");
                            String err = s.hasNext() ? s.next() : "";
                            Log.e(TAG, "Server Error: " + err);
                            runOnUiThread(
                                    () -> Toast.makeText(MainActivity.this, "Err: " + err, Toast.LENGTH_LONG).show());
                        }
                    }
                }
            } catch (Exception e) {
                e.printStackTrace();
                final String errorMsg = e.getMessage();
                runOnUiThread(() -> statusText.setText("Status: Chunk Error ⚠️ " + errorMsg));
            }
        }).start();
    }

    private void addFormField(java.io.DataOutputStream dos, String boundary, String name, String value)
            throws IOException {
        dos.writeBytes("--" + boundary + "\r\n");
        dos.writeBytes("Content-Disposition: form-data; name=\"" + name + "\"\r\n");
        dos.writeBytes("\r\n");
        dos.writeBytes(value + "\r\n");
    }

    private void takeScreenshotAndUpload() {
        // Capture
        View rootView = getWindow().getDecorView().getRootView();
        rootView.setDrawingCacheEnabled(true);
        android.graphics.Bitmap bitmap = android.graphics.Bitmap.createBitmap(rootView.getDrawingCache());
        rootView.setDrawingCacheEnabled(false);

        // Save
        try {
            File file = new File(getCacheDir(), "screenshot_" + System.currentTimeMillis() + ".png");
            FileOutputStream fos = new FileOutputStream(file);
            bitmap.compress(android.graphics.Bitmap.CompressFormat.PNG, 100, fos);
            fos.flush();
            fos.close();

            // Upload
            String token = AuthManager.getToken(this);
            NetworkUtils.uploadFile(AuthManager.getBaseUrl() + "/upload-media", file, token,
                    new NetworkUtils.Callback() {
                        @Override
                        public void onSuccess(String response) {
                            runOnUiThread(() -> {
                                Toast.makeText(MainActivity.this, "Screenshot Uploaded!", Toast.LENGTH_SHORT).show();
                                file.delete();
                            });
                        }

                        @Override
                        public void onError(String error) {
                            runOnUiThread(() -> Toast
                                    .makeText(MainActivity.this, "Upload Failed: " + error, Toast.LENGTH_SHORT).show());
                        }
                    });

        } catch (Exception e) {
            e.printStackTrace();
            Toast.makeText(this, "Screenshot Failed", Toast.LENGTH_SHORT).show();
        }
    }
}
