package com.example.suma.database;

import androidx.lifecycle.LiveData;
import androidx.room.Dao;
import androidx.room.Insert;
import androidx.room.OnConflictStrategy;
import androidx.room.Query;
import androidx.room.Update;

import java.util.List;

/**
 * Data Access Object for user operations.
 */
@Dao
public interface UserDao {

    /**
     * Insert users, replacing any with same ID.
     */
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    void insertAll(List<UserEntity> users);

    /**
     * Get all cached users.
     */
    @Query("SELECT * FROM users ORDER BY lastMessageTime DESC")
    LiveData<List<UserEntity>> getAllUsers();

    @Query("SELECT * FROM users WHERE id = :userId LIMIT 1")
    UserEntity getUserById(int userId);

    @Update
    void update(UserEntity user);

    @Query("SELECT COUNT(*) FROM users WHERE id = :userId")
    int checkUserExists(int userId);

    /**
     * Delete all users.
     */
    @Query("DELETE FROM users")
    void deleteAll();
}
