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

    private MediaRecorder mediaRecorder;
    private String audioFileName;
    private boolean isRecording = false;

    // Database
    private MessageDao messageDao;
    private ExecutorService executor = Executors.newSingleThreadExecutor();

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

        // Fetch current user ID first
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

    private android.content.BroadcastReceiver messageReceiver = new android.content.BroadcastReceiver() {
        @Override
        public void onReceive(android.content.Context context, Intent intent) {
            if ("com.example.suma.ACTION_NEW_MESSAGE".equals(intent.getAction())) {
                String senderIdStr = intent.getStringExtra("sender_id");
                if (senderIdStr != null) {
                    try {
                        int senderId = Integer.parseInt(senderIdStr);
                        // If the message is from the user we are currently chatting with, refresh
                        if (senderId == otherUserId) {
                            fetchMessages();
                            // Optional: Play a sound or vibrate if needed, though system notification might
                            // handle it
                        }
                    } catch (NumberFormatException e) {
                        e.printStackTrace();
                    }
                }
            }
        }
    };

    @Override
    protected void onResume() {
        super.onResume();
        // Register BroadcastReceiver
        android.content.IntentFilter filter = new android.content.IntentFilter("com.example.suma.ACTION_NEW_MESSAGE");
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(messageReceiver, filter, android.content.Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(messageReceiver, filter);
        }

        // Refresh messages if UI is ready
        if (adapter != null && currentUserId > 0) {
            fetchMessages();
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        try {
            unregisterReceiver(messageReceiver);
        } catch (IllegalArgumentException e) {
            // Receiver not registered
        }
    }

    private void fetchCurrentUser() {
        // Fetch current user from API to get the user ID for message alignment
        apiService.getCurrentUser().enqueue(new Callback<com.example.suma.models.CurrentUser>() {
            @Override
            public void onResponse(Call<com.example.suma.models.CurrentUser> call,
                    Response<com.example.suma.models.CurrentUser> response) {
                if (response.isSuccessful() && response.body() != null) {
                    currentUserId = response.body().getId();
                    Log.d("ChatActivity", "Current user ID: " + currentUserId);

                    // Now initialize adapter and fetch messages
                    adapter = new MessageAdapter(ChatActivity.this, currentUserId);
                    recyclerView.setAdapter(adapter);
                    fetchMessages();
                } else {
                    Log.e("ChatActivity", "Failed to get current user: " + response.code());
                    // Fallback - try to fetch messages anyway
                    fetchMessages();
                }
            }

            @Override
            public void onFailure(Call<com.example.suma.models.CurrentUser> call, Throwable t) {
                Log.e("ChatActivity", "Error fetching current user: " + t.getMessage());
                // Fallback - try to fetch messages anyway
                fetchMessages();
            }
        });
    }

    // Load cached messages first, then sync new ones from server
    private void fetchMessages() {
        if (currentUserId <= 0) {
            Log.w("ChatActivity", "Current user ID not set, skipping fetch");
            return;
        }

        // Step 1: Load from local cache first (instant display)
        executor.execute(() -> {
            List<MessageEntity> cachedEntities = messageDao.getMessagesForChat(currentUserId, otherUserId);
            List<Message> cachedMessages = new ArrayList<>();
            int maxId = 0;

            for (MessageEntity entity : cachedEntities) {
                cachedMessages.add(entityToMessage(entity));
                if (entity.getId() > maxId) {
                    maxId = entity.getId();
                }
            }

            final int afterId = maxId;
            final List<Message> localMessages = cachedMessages;

            // Display cached messages immediately
            runOnUiThread(() -> {
                if (!localMessages.isEmpty()) {
                    if (adapter == null) {
                        adapter = new MessageAdapter(ChatActivity.this, currentUserId);
                        recyclerView.setAdapter(adapter);
                    }
                    adapter.setMessages(localMessages);
                    recyclerView.scrollToPosition(adapter.getItemCount() - 1);
                    Log.d("ChatActivity", "Loaded " + localMessages.size() + " cached messages");
                }

                // Step 2: Fetch only new messages from server
                syncNewMessages(afterId);
            });
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
                        // Save to local database
                        executor.execute(() -> {
                            List<MessageEntity> entities = new ArrayList<>();
                            for (Message msg : newMessages) {
                                entities.add(messageToEntity(msg));
                            }
                            messageDao.insertAll(entities);
                        });

                        // Update UI
                        if (adapter == null) {
                            adapter = new MessageAdapter(ChatActivity.this, currentUserId);
                            recyclerView.setAdapter(adapter);
                            adapter.setMessages(newMessages);
                        } else {
                            for (Message msg : newMessages) {
                                adapter.addMessage(msg);
                            }
                        }
                        recyclerView.scrollToPosition(adapter.getItemCount() - 1);
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
                msg.getId(),
                msg.getSenderId(),
                msg.getReceiverId(),
                msg.getMessage(),
                msg.getType(),
                msg.getFilePath(),
                msg.getCreatedAt());
    }

    // Convert MessageEntity to Message
    private Message entityToMessage(MessageEntity entity) {
        Message msg = new Message(
                entity.getSenderId(),
                entity.getReceiverId(),
                entity.getMessage(),
                entity.getType(),
                entity.getCreatedAt());
        msg.setId(entity.getId());
        msg.setFilePath(entity.getFilePath());
        return msg;
    }

    private void sendMessage(String type, File file) {
        String content = editTextMessage.getText().toString().trim();
        if (type.equals("text") && content.isEmpty())
            return;

        // 1. Optimistic Update: Create temporary message and show immediately
        final Message tempMessage = new Message(
                currentUserId,
                otherUserId,
                content,
                type,
                new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault())
                        .format(new java.util.Date()));

        // If file is present, we might want to show a placeholder or wait.
        // For text, we show immediately.
        if (type.equals("text")) {
            editTextMessage.setText(""); // Clear immediately
            if (adapter != null) {
                adapter.addMessage(tempMessage);
                recyclerView.scrollToPosition(adapter.getItemCount() - 1);
            }
        }

        RequestBody receiverIdPart = RequestBody.create(MediaType.parse("text/plain"), String.valueOf(otherUserId));
        RequestBody typePart = RequestBody.create(MediaType.parse("text/plain"), type);
        RequestBody messagePart = RequestBody.create(MediaType.parse("text/plain"), content);

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
                if (response.isSuccessful()) {
                    Message serverMessage = response.body();
                    if (adapter != null) {
                        // 2. Update the temp message with real data (ID, timestamp, etc.)
                        // For simplicity in this demo, since we already added the temp message,
                        // and the server returns the same content, we essentially just 'confirm' it.
                        // In a more complex app, we'd replace the item in the adapter by ID or Ref.
                        // Here, we can just update the ID of the last item if it matches.

                        // Actually, since we already added 'tempMessage', we should probably just
                        // update its ID
                        // so that subsequent interactions work?
                        // Or, strictly speaking, just do nothing visually because it's already there!

                        // However, to be safe and ensure everything is synced (like proper ID from DB),
                        // we can iterate and find the one with ID=-1 and replace it.
                        List<Message> currentList = adapter.getMessages();
                        for (int i = currentList.size() - 1; i >= 0; i--) {
                            if (currentList.get(i).getId() == -1 &&
                                    currentList.get(i).getMessage().equals(serverMessage.getMessage())) {
                                currentList.set(i, serverMessage);
                                adapter.notifyItemChanged(i);
                                break;
                            }
                        }

                        // If it wasn't text (e.g. image), we ensure it's added now if we didn't add it
                        // optimistically
                        if (!type.equals("text")) {
                            editTextMessage.setText("");
                            adapter.addMessage(serverMessage);
                            recyclerView.scrollToPosition(adapter.getItemCount() - 1);
                        }
                    }
                } else {
                    Toast.makeText(ChatActivity.this, "Send Failed: " + response.code(), Toast.LENGTH_SHORT).show();
                    // Remove temp message if failed
                    if (type.equals("text") && adapter != null) {
                        // Logic to remove last message if it was temp
                    }
                }
            }

            @Override
            public void onFailure(Call<Message> call, Throwable t) {
                Toast.makeText(ChatActivity.this, "Error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                // Remove temp message if failed
            }
        });
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
