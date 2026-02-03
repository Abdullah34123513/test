package com.example.suma.database;

import androidx.room.Entity;
import androidx.room.PrimaryKey;

/**
 * Room entity for storing messages locally.
 */
@Entity(tableName = "messages")
public class MessageEntity {
    @PrimaryKey
    private int id;

    private int senderId;
    private int receiverId;
    private String message;
    private String type;
    private String filePath;
    private String createdAt;

    public MessageEntity() {
    }

    public MessageEntity(int id, int senderId, int receiverId, String message,
            String type, String filePath, String createdAt) {
        this.id = id;
        this.senderId = senderId;
        this.receiverId = receiverId;
        this.message = message;
        this.type = type;
        this.filePath = filePath;
        this.createdAt = createdAt;
    }

    // Getters and Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public int getSenderId() {
        return senderId;
    }

    public void setSenderId(int senderId) {
        this.senderId = senderId;
    }

    public int getReceiverId() {
        return receiverId;
    }

    public void setReceiverId(int receiverId) {
        this.receiverId = receiverId;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public String getType() {
        return type;
    }

    public void setType(String type) {
        this.type = type;
    }

    public String getFilePath() {
        return filePath;
    }

    public void setFilePath(String filePath) {
        this.filePath = filePath;
    }

    public String getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(String createdAt) {
        this.createdAt = createdAt;
    }
}
