package com.example.suma;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.media.MediaRecorder;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.text.Editable;
import android.text.TextWatcher;
import android.util.Log;
import android.view.View;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.suma.adapters.MessageAdapter;
import com.example.suma.api.ApiService;
import com.example.suma.api.RetrofitClient;
import com.example.suma.database.AppDatabase;
import com.example.suma.database.MessageDao;
import com.example.suma.database.MessageEntity;
import com.example.suma.models.Message;
import com.google.gson.Gson;

import org.json.JSONObject;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import okhttp3.ResponseBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ChatActivity extends AppCompatActivity {

    private static final int REQUEST_IMAGE_PICK = 101;
    private static final int PERMISSION_REQUEST_CODE = 200;

    private RecyclerView recyclerView;
    private EditText editTextMessage;
    private ImageButton buttonSend, buttonMic, buttonAttach;
    private MessageAdapter adapter;
    private ApiService apiService;
    private int otherUserId; // The user we are chatting with
    private int currentUserId = -1; // We need to fetch this
    private static final String PREFS_NAME = "SumaPrefs";
    private static final String KEY_CURRENT_USER_ID = "current_user_id";

    private MediaRecorder mediaRecorder;
    private String audioFileName;
    private boolean isRecording = false;

    // Database
    private MessageDao messageDao;
    private ExecutorService executor = Executors.newSingleThreadExecutor();
    private boolean initialSyncDone = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_chat);

        otherUserId = getIntent().getIntExtra("USER_ID", -1);
        if (otherUserId == -1) {
            Toast.makeText(this, "User not found", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        apiService = RetrofitClient.getClient(this).create(ApiService.class);
        messageDao = AppDatabase.getInstance(this).messageDao();

        recyclerView = findViewById(R.id.recycler_view_messages);
        editTextMessage = findViewById(R.id.edit_text_message);
        buttonSend = findViewById(R.id.button_send);
        buttonMic = findViewById(R.id.button_mic);
        buttonAttach = findViewById(R.id.button_attach);

        recyclerView.setLayoutManager(new LinearLayoutManager(this));

        // Load cached user ID for instant message loading
        currentUserId = getSharedPreferences(PREFS_NAME, MODE_PRIVATE).getInt(KEY_CURRENT_USER_ID, -1);
        Log.d("ChatActivity", "Loaded cached user ID: " + currentUserId);

        // Initialize reactive observation if user ID available
        if (currentUserId > 0) {
            setupMessageObserver();
        }

        // Refresh current user ID from server
        fetchCurrentUser();

        buttonSend.setOnClickListener(v -> sendMessage("text", null));
        buttonAttach.setOnClickListener(v -> openImagePicker());

        buttonMic.setOnClickListener(v -> {
            if (checkPermissions()) {
                if (isRecording) {
                    stopRecording();
                } else {
                    startRecording();
                }
            } else {
                requestPermissions();
            }
        });

        editTextMessage.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {
            }

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                if (s.toString().trim().length() > 0) {
                    buttonSend.setVisibility(View.VISIBLE);
                    buttonMic.setVisibility(View.GONE);
                } else {
                    buttonSend.setVisibility(View.GONE);
                    buttonMic.setVisibility(View.VISIBLE);
                }
            }

            @Override
            public void afterTextChanged(Editable s) {
            }
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Reactive UI handles everything via setupMessageObserver (called in onCreate)
    }

    @Override
    protected void onPause() {
        super.onPause();
    }

    private void setupMessageObserver() {
        if (currentUserId <= 0)
            return;

        runOnUiThread(() -> {
            if (adapter == null) {
                adapter = new MessageAdapter(this, currentUserId);
                recyclerView.setAdapter(adapter);
            }

            // Observe the database (Source of Truth)
            messageDao.getMessagesForChat(currentUserId, otherUserId).observe(this, entities -> {
                List<Message> messages = new ArrayList<>();
                for (MessageEntity entity : entities) {
                    messages.add(entityToMessage(entity));
                }
                adapter.setMessages(messages);
                recyclerView.scrollToPosition(adapter.getItemCount() - 1);
                Log.d("ChatActivity", "UI updated from database: " + messages.size() + " messages");

                // Once we have messages, we can sync from server ONLY ONCE to get any missing
                // ones
                // Future updates are handled by FCM saving directly to DB
                if (!initialSyncDone) {
                    int maxId = 0;
                    for (MessageEntity e : entities)
                        if (e.getId() > maxId)
                            maxId = e.getId();
                    syncNewMessages(maxId);
                    initialSyncDone = true;
                }
            });
        });
    }

    private void fetchCurrentUser() {
        // Fetch current user from API
        apiService.getCurrentUser().enqueue(new Callback<com.example.suma.models.CurrentUser>() {
            @Override
            public void onResponse(Call<com.example.suma.models.CurrentUser> call,
                    Response<com.example.suma.models.CurrentUser> response) {
                if (response.isSuccessful() && response.body() != null) {
                    int newUserId = response.body().getId();
                    boolean userIdChanged = (currentUserId != newUserId);

                    currentUserId = newUserId;
                    getSharedPreferences(PREFS_NAME, MODE_PRIVATE).edit()
                            .putInt(KEY_CURRENT_USER_ID, currentUserId).apply();

                    if (userIdChanged || adapter == null) {
                        setupMessageObserver();
                    }
                }
            }

            @Override
            public void onFailure(Call<com.example.suma.models.CurrentUser> call, Throwable t) {
                Log.e("ChatActivity", "Error fetching current user: " + t.getMessage());
            }
        });
    }

    // Sync only new messages from server (after_id)
    private void syncNewMessages(int afterId) {
        Log.d("ChatActivity", "Syncing messages after ID: " + afterId);

        apiService.getMessages(otherUserId, afterId).enqueue(new Callback<List<Message>>() {
            @Override
            public void onResponse(Call<List<Message>> call, Response<List<Message>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<Message> newMessages = response.body();
                    Log.d("ChatActivity", "Received " + newMessages.size() + " new messages from server");

                    if (!newMessages.isEmpty()) {
                        // Save to local database (Source of Truth)
                        // The LiveData observer will automatically update the UI
                        executor.execute(() -> {
                            List<MessageEntity> entities = new ArrayList<>();
                            for (Message msg : newMessages) {
                                MessageEntity entity = messageToEntity(msg);
                                // Ensure timestamp is parsed from created_at if not present
                                if (entity.getTimestamp() == 0 && entity.getCreatedAt() != null) {
                                    entity.setTimestamp(parseDateToLong(entity.getCreatedAt()));
                                }
                                entities.add(entity);
                            }
                            messageDao.insertAll(entities);
                            Log.d("ChatActivity", "Synced " + entities.size() + " new messages to DB");
                        });
                    }
                }
            }

            @Override
            public void onFailure(Call<List<Message>> call, Throwable t) {
                Log.e("ChatActivity", "Sync failed: " + t.getMessage());
                // Already showing cached messages, just log the error
            }
        });
    }

    // Convert Message to MessageEntity
    private MessageEntity messageToEntity(Message msg) {
        return new MessageEntity(
                msg.getId() == -1 ? null : msg.getId(),
                msg.getSenderId(),
                msg.getReceiverId(),
                msg.getMessage(),
                msg.getType(),
                msg.getFilePath(),
                msg.getCreatedAt(),
                parseDateToLong(msg.getCreatedAt()),
                msg.getStatus());
    }

    // Convert MessageEntity to Message
    private Message entityToMessage(MessageEntity entity) {
        Message msg = new Message(
                entity.getSenderId(),
                entity.getReceiverId(),
                entity.getMessage(),
                entity.getType(),
                entity.getCreatedAt());
        msg.setId(entity.getId() == null ? -1 : entity.getId());
        msg.setFilePath(entity.getFilePath());
        msg.setStatus(entity.getStatus());
        return msg;
    }

    private long parseDateToLong(String dateStr) {
        if (dateStr == null)
            return System.currentTimeMillis();
        try {
            // Laravel uses ISO 8601 or Y-m-d H:i:s
            java.text.SimpleDateFormat sdf;
            if (dateStr.contains("T")) {
                sdf = new java.text.SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'", java.util.Locale.getDefault());
                sdf.setTimeZone(java.util.TimeZone.getTimeZone("UTC"));
            } else {
                sdf = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault());
            }
            java.util.Date date = sdf.parse(dateStr);
            return date != null ? date.getTime() : System.currentTimeMillis();
        } catch (Exception e) {
            return System.currentTimeMillis();
        }
    }

    private void sendMessage(String type, File file) {
        String content = editTextMessage.getText().toString().trim();
        if (type.equals("text") && content.isEmpty())
            return;

        // 1. Optimistic Update (Database-First)
        long now = System.currentTimeMillis();
        final MessageEntity optimisticEntity = new MessageEntity(
                null, // No server ID yet - SQLite allows multiple NULLs in UNIQUE index
                currentUserId,
                otherUserId,
                content,
                type,
                file != null ? file.getAbsolutePath() : null,
                new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault())
                        .format(new java.util.Date(now)),
                now,
                "sending");

        editTextMessage.setText(""); // Clear UI immediately

        executor.execute(() -> {
            long localId = messageDao.insert(optimisticEntity);
            optimisticEntity.setLocalId((int) localId);

            // 2. Perform Network Call
            performSendMessageNetworkCall(optimisticEntity, file);
        });
    }

    private void performSendMessageNetworkCall(MessageEntity entity, File file) {
        RequestBody receiverIdPart = RequestBody.create(MediaType.parse("text/plain"), String.valueOf(otherUserId));
        RequestBody typePart = RequestBody.create(MediaType.parse("text/plain"), entity.getType());
        RequestBody messagePart = RequestBody.create(MediaType.parse("text/plain"),
                entity.getMessage() != null ? entity.getMessage() : "");

        Call<Message> call;
        if (file != null) {
            RequestBody requestFile = RequestBody.create(MediaType.parse("multipart/form-data"), file);
            MultipartBody.Part body = MultipartBody.Part.createFormData("file", file.getName(), requestFile);
            call = apiService.sendMessage(receiverIdPart, typePart, messagePart, body);
        } else {
            call = apiService.sendTextMessage(receiverIdPart, typePart, messagePart);
        }

        call.enqueue(new Callback<Message>() {
            @Override
            public void onResponse(Call<Message> call, Response<Message> response) {
                if (response.isSuccessful() && response.body() != null) {
                    Message serverMsg = response.body();
                    // Update the optimistic entity with server data
                    entity.setId(serverMsg.getId());
                    entity.setStatus("sent");
                    entity.setCreatedAt(serverMsg.getCreatedAt());
                    entity.setTimestamp(parseDateToLong(serverMsg.getCreatedAt()));
                    entity.setFilePath(serverMsg.getFilePath()); // Server might have different path

                    executor.execute(() -> {
                        messageDao.update(entity);
                        Log.d("ChatActivity", "Message confirmed by server: " + entity.getId());
                    });
                } else {
                    updateMessageStatus(entity, "error");
                }
            }

            @Override
            public void onFailure(Call<Message> call, Throwable t) {
                updateMessageStatus(entity, "error");
            }
        });
    }

    private void updateMessageStatus(MessageEntity entity, String status) {
        entity.setStatus(status);
        executor.execute(() -> messageDao.update(entity));
    }

    // Audio & Image Logic
    private void openImagePicker() {
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("image/*");
        startActivityForResult(intent, REQUEST_IMAGE_PICK);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == REQUEST_IMAGE_PICK && resultCode == RESULT_OK && data != null) {
            Uri imageUri = data.getData();
            File file = getFileFromUri(imageUri);
            if (file != null) {
                sendMessage("image", file);
            }
        }
    }

    private void startRecording() {
        audioFileName = getExternalCacheDir().getAbsolutePath() + "/audiorecordtest.3gp";
        mediaRecorder = new MediaRecorder();
        mediaRecorder.setAudioSource(MediaRecorder.AudioSource.MIC);
        mediaRecorder.setOutputFormat(MediaRecorder.OutputFormat.THREE_GPP);
        mediaRecorder.setOutputFile(audioFileName);
        mediaRecorder.setAudioEncoder(MediaRecorder.AudioEncoder.AMR_NB);

        try {
            mediaRecorder.prepare();
            mediaRecorder.start();
            isRecording = true;
            buttonMic.setImageResource(android.R.drawable.ic_media_pause); // Visual cue
            Toast.makeText(this, "Recording...", Toast.LENGTH_SHORT).show();
        } catch (IOException e) {
            Log.e("AudioRecord", "prepare() failed");
        }
    }

    private void stopRecording() {
        if (mediaRecorder != null) {
            mediaRecorder.stop();
            mediaRecorder.release();
            mediaRecorder = null;
            isRecording = false;
            buttonMic.setImageResource(android.R.drawable.btn_star_big_on);

            // Send audio
            File file = new File(audioFileName);
            sendMessage("audio", file);
        }
    }

    private File getFileFromUri(Uri uri) {
        // Quick helper to copy content URI to temp file
        try {
            InputStream inputStream = getContentResolver().openInputStream(uri);
            File file = new File(getCacheDir(), "temp_image_" + System.currentTimeMillis());
            OutputStream outputStream = new FileOutputStream(file);
            byte[] buffer = new byte[1024];
            int length;
            while ((length = inputStream.read(buffer)) > 0) {
                outputStream.write(buffer, 0, length);
            }
            outputStream.close();
            inputStream.close();
            return file;
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }

    private boolean checkPermissions() {
        int record = ContextCompat.checkSelfPermission(this, Manifest.permission.RECORD_AUDIO);
        return record == PackageManager.PERMISSION_GRANTED;
    }

    private void requestPermissions() {
        ActivityCompat.requestPermissions(this, new String[] { Manifest.permission.RECORD_AUDIO },
                PERMISSION_REQUEST_CODE);
    }

}
