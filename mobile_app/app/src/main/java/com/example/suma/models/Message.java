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

    public int getId() { return id; }
    public int getSenderId() { return senderId; }
    public int getReceiverId() { return receiverId; }
    public String getMessage() { return message; }
    public String getType() { return type; }
    public String getFilePath() { return filePath; }
    public String getCreatedAt() { return createdAt; }
}
