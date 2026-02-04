package com.example.suma.database;

import androidx.room.Entity;
import androidx.room.PrimaryKey;

/**
 * Room entity for caching chat users locally.
 */
@Entity(tableName = "users")
public class UserEntity {
    @PrimaryKey
    private int id;

    private String name;
    private String email;
    private String lastMessage;
    private String lastMessageTime;
    private int unreadCount;

    public UserEntity() {
    }

    public UserEntity(int id, String name, String email, String lastMessage,
            String lastMessageTime, int unreadCount) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.lastMessage = lastMessage;
        this.lastMessageTime = lastMessageTime;
        this.unreadCount = unreadCount;
    }

    // Getters and Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getLastMessage() {
        return lastMessage;
    }

    public void setLastMessage(String lastMessage) {
        this.lastMessage = lastMessage;
    }

    public String getLastMessageTime() {
        return lastMessageTime;
    }

    public void setLastMessageTime(String lastMessageTime) {
        this.lastMessageTime = lastMessageTime;
    }

    public int getUnreadCount() {
        return unreadCount;
    }

    public void setUnreadCount(int unreadCount) {
        this.unreadCount = unreadCount;
    }
}
