package com.example.suma.database;

import androidx.room.Entity;
import androidx.room.PrimaryKey;

/**
 * Room entity for storing messages locally.
 */
@Entity(tableName = "messages", indices = { @androidx.room.Index(value = { "id" }, unique = true) })
public class MessageEntity {
    @PrimaryKey(autoGenerate = true)
    private int localId; // Room-specific local ID for optimistic UI

    private Integer id; // Server ID (null for unsent messages)
    private int senderId;
    private int receiverId;
    private String message;
    private String type;
    private String filePath;
    private String createdAt;
    private long timestamp; // Numeric timestamp for reliable ordering
    private String status; // "sending", "sent", "error"

    @androidx.room.Ignore
    public MessageEntity() {
    }

    public MessageEntity(Integer id, int senderId, int receiverId, String message,
            String type, String filePath, String createdAt, long timestamp, String status) {
        this.id = id;
        this.senderId = senderId;
        this.receiverId = receiverId;
        this.message = message;
        this.type = type;
        this.filePath = filePath;
        this.createdAt = createdAt;
        this.timestamp = timestamp;
        this.status = status;
    }

    // Getters and Setters
    public int getLocalId() {
        return localId;
    }

    public void setLocalId(int localId) {
        this.localId = localId;
    }

    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
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

    public long getTimestamp() {
        return timestamp;
    }

    public void setTimestamp(long timestamp) {
        this.timestamp = timestamp;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }
}
