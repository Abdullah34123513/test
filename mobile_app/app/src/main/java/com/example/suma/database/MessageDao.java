package com.example.suma.database;

import androidx.room.Dao;
import androidx.room.Insert;
import androidx.room.OnConflictStrategy;
import androidx.room.Query;

import java.util.List;

/**
 * Data Access Object for message operations.
 */
@Dao
public interface MessageDao {

    /**
     * Insert messages, replacing any with same ID.
     */
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    void insertAll(List<MessageEntity> messages);

    /**
     * Insert a single message.
     */
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    void insert(MessageEntity message);

    /**
     * Get all messages for a chat between two users, ordered by creation time.
     */
    @Query("SELECT * FROM messages WHERE " +
            "(senderId = :currentUserId AND receiverId = :otherUserId) OR " +
            "(senderId = :otherUserId AND receiverId = :currentUserId) " +
            "ORDER BY createdAt ASC")
    List<MessageEntity> getMessagesForChat(int currentUserId, int otherUserId);

    /**
     * Get the maximum message ID for a chat (for sync).
     */
    @Query("SELECT MAX(id) FROM messages WHERE " +
            "(senderId = :currentUserId AND receiverId = :otherUserId) OR " +
            "(senderId = :otherUserId AND receiverId = :currentUserId)")
    Integer getMaxMessageId(int currentUserId, int otherUserId);

    /**
     * Delete all messages for a specific chat.
     */
    @Query("DELETE FROM messages WHERE " +
            "(senderId = :currentUserId AND receiverId = :otherUserId) OR " +
            "(senderId = :otherUserId AND receiverId = :currentUserId)")
    void deleteMessagesForChat(int currentUserId, int otherUserId);

    /**
     * Delete all messages.
     */
    @Query("DELETE FROM messages")
    void deleteAll();
}
