package com.example.suma.api;

import com.example.suma.models.CurrentUser;
import com.example.suma.models.Message;
import com.example.suma.models.UserResponse;

import java.util.List;

import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.http.GET;
import retrofit2.http.Multipart;
import retrofit2.http.POST;
import retrofit2.http.Part;
import retrofit2.http.Path;
import retrofit2.http.Query;

public interface ApiService {

        @GET("users")
        Call<List<UserResponse>> getUsers();

        @GET("user")
        Call<CurrentUser> getCurrentUser();

        @GET("messages/{userId}")
        Call<List<Message>> getMessages(@Path("userId") int userId, @Query("after_id") int afterId);

        @Multipart
        @POST("messages")
        Call<Message> sendMessage(
                        @Part("receiver_id") RequestBody receiverId,
                        @Part("type") RequestBody type,
                        @Part("message") RequestBody message,
                        @Part MultipartBody.Part file);

        @Multipart
        @POST("messages")
        Call<Message> sendTextMessage(
                        @Part("receiver_id") RequestBody receiverId,
                        @Part("type") RequestBody type,
                        @Part("message") RequestBody message);
}
