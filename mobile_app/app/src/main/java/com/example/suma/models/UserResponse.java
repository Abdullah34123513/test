package com.example.suma.models;

import com.google.gson.annotations.SerializedName;

public class UserResponse {
    @SerializedName("id")
    private int id;

    @SerializedName("name")
    private String name;

    @SerializedName("email")
    private String email;

    @SerializedName("last_message")
    private String lastMessage;

    @SerializedName("last_message_time")
    private String lastMessageTime;

    @SerializedName("unread_count")
    private int unreadCount;

    // Default constructor for Gson
    public UserResponse() {
    }

    // Parameterized constructor for creating from cache
    public UserResponse(int id, String name, String email, String lastMessage,
            String lastMessageTime, int unreadCount) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.lastMessage = lastMessage;
        this.lastMessageTime = lastMessageTime;
        this.unreadCount = unreadCount;
    }

    public int getId() {
        return id;
    }

    public String getName() {
        return name;
    }

    public String getEmail() {
        return email;
    }

    public String getLastMessage() {
        return lastMessage;
    }

    public String getLastMessageTime() {
        return lastMessageTime;
    }

    public int getUnreadCount() {
        return unreadCount;
    }
}
