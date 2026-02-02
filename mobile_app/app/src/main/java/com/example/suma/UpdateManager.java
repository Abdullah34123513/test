package com.example.suma;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.DownloadManager;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.util.Log;
import android.widget.Toast;

import androidx.core.content.FileProvider;

import org.json.JSONObject;

import java.io.File;

public class UpdateManager {

    private static final String TAG = "UpdateManager";
    private Context context;
    private Activity activity;
    private long downloadId = -1;
    private String downloadUrl;

    public UpdateManager(Activity activity) {
        this.activity = activity;
        this.context = activity;
    }

    public void checkForUpdates() {
        new Thread(() -> {
            try {
                int currentVersionCode = 0;
                try {
                    PackageInfo pInfo = context.getPackageManager().getPackageInfo(context.getPackageName(), 0);
                    currentVersionCode = pInfo.versionCode;
                } catch (PackageManager.NameNotFoundException e) {
                    e.printStackTrace();
                }

                String url = AuthManager.getBaseUrl() + "/app/check-update?current_version_code=" + currentVersionCode;
                // Simple GET request using NetworkUtils (assuming it supports GET or we use
                // java.net)
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) new java.net.URL(url).openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("Authorization", "Bearer " + AuthManager.getToken(context));

                int responseCode = conn.getResponseCode();
                if (responseCode == 200) {
                    java.util.Scanner s = new java.util.Scanner(conn.getInputStream()).useDelimiter("\\A");
                    String result = s.hasNext() ? s.next() : "";
                    JSONObject json = new JSONObject(result);

                    boolean updateAvailable = json.optBoolean("update_available", false);
                    if (updateAvailable) {
                        downloadUrl = json.getString("apk_url");
                        String releaseNotes = json.optString("release_notes", "A new version is available.");
                        boolean forceUpdate = json.optBoolean("force_update", false);

                        activity.runOnUiThread(() -> showUpdateDialog(releaseNotes, forceUpdate));
                    }
                }
            } catch (Exception e) {
                Log.e(TAG, "Check update failed", e);
            }
        }).start();
    }

    private void showUpdateDialog(String notes, boolean force) {
        AlertDialog.Builder builder = new AlertDialog.Builder(context);
        builder.setTitle("Update Available")
                .setMessage(notes)
                .setPositiveButton("Update", (dialog, which) -> downloadApk(downloadUrl))
                .setCancelable(!force);

        if (!force) {
            builder.setNegativeButton("Later", null);
        }

        builder.show();
    }

    private void downloadApk(String url) {
        Toast.makeText(context, "Downloading update...", Toast.LENGTH_SHORT).show();

        DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
        request.setTitle("App Update");
        request.setDescription("Downloading new version...");
        request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE);
        request.setDestinationInExternalFilesDir(context, Environment.DIRECTORY_DOWNLOADS, "update.apk");
        request.setMimeType("application/vnd.android.package-archive");

        DownloadManager manager = (DownloadManager) context.getSystemService(Context.DOWNLOAD_SERVICE);
        downloadId = manager.enqueue(request);

        // Register receiver for download complete
        context.registerReceiver(onDownloadComplete, new IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE));
    }

    private BroadcastReceiver onDownloadComplete = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            long id = intent.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1);
            if (downloadId == id) {
                installApk();
                context.unregisterReceiver(this);
            }
        }
    };

    private void installApk() {
        try {
            File file = new File(context.getExternalFilesDir(Environment.DIRECTORY_DOWNLOADS), "update.apk");
            if (!file.exists()) {
                Log.e(TAG, "Update file not found");
                return;
            }

            Uri uri = FileProvider.getUriForFile(context, context.getPackageName() + ".provider", file);

            Intent intent = new Intent(Intent.ACTION_VIEW);
            intent.setDataAndType(uri, "application/vnd.android.package-archive");
            intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            context.startActivity(intent);

        } catch (Exception e) {
            Log.e(TAG, "Install failed", e);
            Toast.makeText(context, "Install failed: " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }
}
