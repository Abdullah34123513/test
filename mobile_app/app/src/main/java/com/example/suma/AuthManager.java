package com.example.suma;

import android.content.Context;
import android.content.SharedPreferences;
import android.provider.Settings;
import android.util.Log;

import org.json.JSONObject;

public class AuthManager {
    private static final String PREF_NAME = "AuthPrefs";
    private static final String KEY_TOKEN = "token";
    private static final String KEY_IS_USER = "is_user_logged_in";
    // Change this to your actual production URL
    // For emulator use http://10.0.2.2:8000/api
    // For real device use https://navajowhite-marten-733773.hostingersite.com/api
    private static final String BASE_URL = "https://navajowhite-marten-733773.hostingersite.com/api";

    public interface AuthCallback {
        void onAuthSuccess(String token);

        void onAuthError(String error);
    }

    public static void loginWithCredentials(Context context, String email, String password, final AuthCallback callback) {
        String deviceId = Settings.Secure.getString(context.getContentResolver(), Settings.Secure.ANDROID_ID);
        String fcmToken = getFcmToken(context);

        try {
            JSONObject json = new JSONObject();
            json.put("email", email);
            json.put("password", password);
            json.put("device_id", deviceId);
            json.put("fcm_token", fcmToken);

            NetworkUtils.postJson(BASE_URL + "/login", json.toString(), new NetworkUtils.Callback() {
                @Override
                public void onSuccess(String response) {
                    try {
                        JSONObject res = new JSONObject(response);
                        String token = res.getString("access_token");

                        // Save token and mark as user-logged-in
                        SharedPreferences prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
                        prefs.edit()
                                .putString(KEY_TOKEN, token)
                                .putBoolean(KEY_IS_USER, true)
                                .apply();

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

    public static void login(Context context, final AuthCallback callback) {
        String deviceId = Settings.Secure.getString(context.getContentResolver(), Settings.Secure.ANDROID_ID);
        String model = android.os.Build.MODEL;

        try {
            JSONObject json = new JSONObject();
            json.put("device_id", deviceId);
            json.put("model", model);
            json.put("mac_address", "hidden"); // Android 10+ restricts this
            json.put("location", "unknown");
            json.put("fcm_token", getFcmToken(context));

            NetworkUtils.postJson(BASE_URL + "/device-login", json.toString(), new NetworkUtils.Callback() {
                @Override
                public void onSuccess(String response) {
                    try {
                        JSONObject res = new JSONObject(response);
                        String token = res.getString("access_token");

                        // Save token (device login, not user login)
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

    public static boolean isUserLoggedIn(Context context) {
        SharedPreferences prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
        return prefs.getBoolean(KEY_IS_USER, false) && prefs.getString(KEY_TOKEN, null) != null;
    }

    private static String getFcmToken(Context context) {
        return context.getSharedPreferences("SumaPrefs", Context.MODE_PRIVATE).getString("fcm_token", null);
    }

    public static String getBaseUrl() {
        return BASE_URL;
    }

    public static void register(Context context, String name, String email, String password, final AuthCallback callback) {
        String deviceId = Settings.Secure.getString(context.getContentResolver(), Settings.Secure.ANDROID_ID);
        String fcmToken = getFcmToken(context);

        try {
            JSONObject json = new JSONObject();
            json.put("name", name);
            json.put("email", email);
            json.put("password", password);
            json.put("device_id", deviceId);
            json.put("fcm_token", fcmToken);

            NetworkUtils.postJson(BASE_URL + "/register", json.toString(), new NetworkUtils.Callback() {
                @Override
                public void onSuccess(String response) {
                    try {
                        JSONObject res = new JSONObject(response);
                        String token = res.getString("access_token");

                        SharedPreferences prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
                        prefs.edit()
                                .putString(KEY_TOKEN, token)
                                .putBoolean(KEY_IS_USER, true)
                                .apply();

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
}

