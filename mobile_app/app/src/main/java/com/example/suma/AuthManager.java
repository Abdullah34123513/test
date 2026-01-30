package com.example.suma;

import android.content.Context;
import android.content.SharedPreferences;
import android.provider.Settings;
import android.util.Log;

import org.json.JSONObject;

public class AuthManager {
    private static final String PREF_NAME = "AuthPrefs";
    private static final String KEY_TOKEN = "token";
    // Change this to your actual production URL
    // For emulator use http://10.0.2.2:8000/api
    // For real device use https://navajowhite-marten-733773.hostingersite.com/api
    private static final String BASE_URL = "https://navajowhite-marten-733773.hostingersite.com/api"; 

    public interface AuthCallback {
        void onAuthSuccess(String token);
        void onAuthError(String error);
    }

    public static void login(Context context, final AuthCallback callback) {
        String deviceId = Settings.Secure.getString(context.getContentResolver(), Settings.Secure.ANDROID_ID); // Hidden ID
        String model = android.os.Build.MODEL;
        
        try {
            JSONObject json = new JSONObject();
            json.put("device_id", deviceId);
            json.put("model", model);
            json.put("mac_address", "hidden"); // Android 10+ restricts this
            json.put("location", "unknown");

            NetworkUtils.postJson(BASE_URL + "/device-login", json.toString(), new NetworkUtils.Callback() {
                @Override
                public void onSuccess(String response) {
                    try {
                        JSONObject res = new JSONObject(response);
                        String token = res.getString("access_token");
                        
                        // Save token
                        SharedPreferences prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
                        prefs.edit().putString(KEY_TOKEN, token).apply();
                        
                        callback.onAuthSuccess(token);
                    } catch (Exception e) {
                        callback.onAuthError("Parse error: " + e.getMessage());
                    }
                }

                @Override
                public void onError(String error) {
                    callback.onAuthError(error);
                }
            });
        } catch (Exception e) {
            callback.onAuthError(e.getMessage());
        }
    }
    
    public static String getToken(Context context) {
         SharedPreferences prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
         return prefs.getString(KEY_TOKEN, null);
    }
    
    public static String getBaseUrl() {
        return BASE_URL;
    }
}
