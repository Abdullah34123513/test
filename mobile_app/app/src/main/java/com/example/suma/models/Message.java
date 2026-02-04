package com.example.suma.models;

import com.google.gson.annotations.SerializedName;

public class Message {
    @SerializedName("id")
    private int id;

    @SerializedName("sender_id")
    private int senderId;

    @SerializedName("receiver_id")
    private int receiverId;

    @SerializedName("message")
    private String message;

    @SerializedName("type")
    private String type; // text, image, audio

    @SerializedName("file_path")
    private String filePath;

    @SerializedName("created_at")
    private String createdAt;

    private String status; // "sending", "sent", "error" - local only

    public Message() {
    }

    public Message(int senderId, int receiverId, String message, String type, String createdAt) {
        this.senderId = senderId;
        this.receiverId = receiverId;
        this.message = message;
        this.type = type;
        this.createdAt = createdAt;
        this.id = -1; // Temp ID
        this.status = "sent"; // Default for messages from server
    }

    public int getId() {
        return id;
    }

    public int getSenderId() {
        return senderId;
    }

    public int getReceiverId() {
        return receiverId;
    }

    public String getMessage() {
        return message;
    }

    public String getType() {
        return type;
    }

    public String getFilePath() {
        return filePath;
    }

    public String getCreatedAt() {
        return createdAt;
    }

    public String getStatus() {
        return status;
    }

    public void setId(int id) {
        this.id = id;
    }

    public void setFilePath(String filePath) {
        this.filePath = filePath;
    }

    public void setStatus(String status) {
        this.status = status;
    }
}
