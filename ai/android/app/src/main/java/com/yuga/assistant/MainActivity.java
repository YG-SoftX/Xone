package com.yuga.assistant;

import android.Manifest;
import android.annotation.SuppressLint;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.view.Window;
import android.view.WindowManager;
import android.webkit.PermissionRequest;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import java.util.ArrayList;
import java.util.List;

public class MainActivity extends AppCompatActivity {

    // ── CHANGE THIS to your cPanel domain ───────────────────────────────
    public static final String SERVER_URL = "https://yourdomain.com/yuga/assistant/index.php";
    // Optional: append ?key=YOUR_API_KEY if you require auth
    // public static final String SERVER_URL = "https://yourdomain.com/yuga/assistant/index.php?key=yuga_live_xxx";

    private static final int PERM_REQUEST = 100;
    private WebView webView;

    private static final String[] PERMISSIONS = {
        Manifest.permission.CALL_PHONE,
        Manifest.permission.SEND_SMS,
        Manifest.permission.READ_CONTACTS,
        Manifest.permission.RECORD_AUDIO,
        Manifest.permission.ACCESS_FINE_LOCATION,
        Manifest.permission.CAMERA,
        Manifest.permission.VIBRATE,
    };

    @SuppressLint({"SetJavaScriptEnabled", "AddJavascriptInterface"})
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Full screen — immersive, no status bar
        requestWindowFeature(Window.FEATURE_NO_TITLE);
        getWindow().setFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN, WindowManager.LayoutParams.FLAG_FULLSCREEN);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            getWindow().setStatusBarColor(Color.parseColor("#080d1a"));
        }
        getWindow().getDecorView().setSystemUiVisibility(
            View.SYSTEM_UI_FLAG_HIDE_NAVIGATION |
            View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY |
            View.SYSTEM_UI_FLAG_FULLSCREEN
        );

        // WebView fills whole screen
        webView = new WebView(this);
        setContentView(webView);

        // WebView settings
        WebSettings ws = webView.getSettings();
        ws.setJavaScriptEnabled(true);
        ws.setDomStorageEnabled(true);
        ws.setDatabaseEnabled(true);
        ws.setAllowFileAccessFromFileURLs(true);
        ws.setMediaPlaybackRequiresUserGesture(false);
        ws.setCacheMode(WebSettings.LOAD_DEFAULT);
        ws.setUserAgentString(ws.getUserAgentString() + " YugaNativeApp/1.0");

        // Allow mic/camera from WebView (for Web Speech API)
        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onPermissionRequest(PermissionRequest request) {
                request.grant(request.getResources());
            }
        });

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                // Let the bridge handle tel:/mailto: — don't navigate away
                return url.startsWith("tel:") || url.startsWith("mailto:");
            }
        });

        // ── Inject the native bridge ──────────────────────────────────────
        YugaBridge bridge = new YugaBridge(this, webView);
        webView.addJavascriptInterface(bridge, "YugaBridge");

        // Request dangerous permissions
        requestPermissionsIfNeeded();

        // Load the assistant
        webView.loadUrl(SERVER_URL);
    }

    private void requestPermissionsIfNeeded() {
        List<String> needed = new ArrayList<>();
        for (String p : PERMISSIONS) {
            if (ContextCompat.checkSelfPermission(this, p) != PackageManager.PERMISSION_GRANTED) {
                needed.add(p);
            }
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            needed.add(Manifest.permission.POST_NOTIFICATIONS);
        }
        if (!needed.isEmpty()) {
            ActivityCompat.requestPermissions(this, needed.toArray(new String[0]), PERM_REQUEST);
        }
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) webView.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }

    @Override
    protected void onPause() {
        super.onPause();
        webView.onPause();
    }
}
