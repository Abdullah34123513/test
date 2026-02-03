package com.example.suma.api;

import android.content.Context;
import android.content.SharedPreferences;

import com.example.suma.AuthManager;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class RetrofitClient {

    private static Retrofit retrofit = null;

    public static Retrofit getClient(Context context) {
        if (retrofit == null) {
            HttpLoggingInterceptor interceptor = new HttpLoggingInterceptor();
            interceptor.setLevel(HttpLoggingInterceptor.Level.BODY);

            OkHttpClient client = new OkHttpClient.Builder()
                    .addInterceptor(interceptor)
                    .addInterceptor(chain -> {
                        Request original = chain.request();
                        String token = AuthManager.getToken(context);
                        
                        Request.Builder requestBuilder = original.newBuilder();
                        if (token != null) {
                            requestBuilder.header("Authorization", "Bearer " + token);
                        }
                        requestBuilder.method(original.method(), original.body());
                        
                        return chain.proceed(requestBuilder.build());
                    })
                    .build();

            retrofit = new Retrofit.Builder()
                    .baseUrl(AuthManager.getBaseUrl() + "/") // Ensure trailing slash if needed, usually handy
                    .client(client)
                    .addConverterFactory(GsonConverterFactory.create())
                    .build();
        }
        return retrofit;
    }
}
