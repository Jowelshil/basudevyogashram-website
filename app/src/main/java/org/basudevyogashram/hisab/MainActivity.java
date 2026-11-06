package org.basudevyogashram.hisab;

import android.Manifest;
import android.app.Activity;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.view.View;
import android.webkit.JavascriptInterface;
import android.webkit.URLUtil;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;

/**
 * App shell for the ashram accounts app. The app itself lives at
 * https://basudevyogashram.org/app/ so every improvement reaches all phones
 * without reinstalling; this shell adds the icon, back button, downloads and
 * an offline screen.
 */
public class MainActivity extends Activity {

    private static final String HOST = "basudevyogashram.org";
    private static final String START_URL = "https://" + HOST + "/app/";
    private WebView web;
    private String pendingDownload;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        web = new WebView(this);
        setContentView(web);

        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setAllowFileAccess(false);
        s.setSupportZoom(false);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        s.setUserAgentString(s.getUserAgentString() + " BasudevHisabApp/1");
        if (Build.VERSION.SDK_INT >= 21) android.webkit.CookieManager.getInstance().setAcceptThirdPartyCookies(web, false);

        web.addJavascriptInterface(new Shell(), "Shell");
        web.setWebChromeClient(new WebChromeClient());
        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest req) {
                return handleUrl(req.getUrl());
            }

            @SuppressWarnings("deprecation")
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                return handleUrl(Uri.parse(url));
            }

            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                web.setVisibility(View.VISIBLE);
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest req, WebResourceError err) {
                if (Build.VERSION.SDK_INT >= 21 && req.isForMainFrame()) showOffline();
            }

            @SuppressWarnings("deprecation")
            @Override
            public void onReceivedError(WebView view, int code, String desc, String url) {
                if (url != null && url.startsWith(START_URL)) showOffline();
            }
        });

        web.setDownloadListener((url, userAgent, contentDisposition, mimeType, length) -> download(url, contentDisposition, mimeType));

        if (savedInstanceState != null) web.restoreState(savedInstanceState);
        else web.loadUrl(START_URL);
    }

    private boolean handleUrl(Uri uri) {
        String scheme = uri.getScheme() == null ? "" : uri.getScheme();
        if ("https".equals(scheme) && HOST.equals(uri.getHost())) return false;
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, uri));
        } catch (ActivityNotFoundException e) {
            Toast.makeText(this, "এই লিংক খোলার মতো অ্যাপ নেই।", Toast.LENGTH_SHORT).show();
        }
        return true;
    }

    private void showOffline() {
        web.loadUrl("file:///android_asset/offline.html");
    }

    private void download(String url, String contentDisposition, String mimeType) {
        if (Build.VERSION.SDK_INT < 29 && Build.VERSION.SDK_INT >= 23
                && checkSelfPermission(Manifest.permission.WRITE_EXTERNAL_STORAGE) != PackageManager.PERMISSION_GRANTED) {
            pendingDownload = url + "\n" + (contentDisposition == null ? "" : contentDisposition) + "\n" + (mimeType == null ? "" : mimeType);
            requestPermissions(new String[]{Manifest.permission.WRITE_EXTERNAL_STORAGE}, 7);
            return;
        }
        try {
            String name = URLUtil.guessFileName(url, contentDisposition, mimeType);
            DownloadManager.Request r = new DownloadManager.Request(Uri.parse(url));
            r.setMimeType(mimeType);
            r.setTitle(name);
            r.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
            r.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, name);
            ((DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE)).enqueue(r);
            Toast.makeText(this, "ডাউনলোড শুরু হয়েছে। ফাইলটি Download ফোল্ডারে পাবেন।", Toast.LENGTH_LONG).show();
        } catch (Exception e) {
            Toast.makeText(this, "ডাউনলোড হয়নি: " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }

    @Override
    public void onRequestPermissionsResult(int code, String[] perms, int[] results) {
        if (code == 7 && pendingDownload != null) {
            String[] p = pendingDownload.split("\n", -1);
            pendingDownload = null;
            if (results.length > 0 && results[0] == PackageManager.PERMISSION_GRANTED) download(p[0], p[1], p[2]);
            else Toast.makeText(this, "ফাইল সংরক্ষণের অনুমতি দেওয়া হয়নি।", Toast.LENGTH_LONG).show();
        }
    }

    @Override
    public void onBackPressed() {
        String url = web.getUrl();
        if (url != null && url.startsWith("file:")) { super.onBackPressed(); return; }
        // Let the page close an open form, menu or sub-page first
        web.evaluateJavascript("(window.byaBack && window.byaBack()) ? 'y' : 'n'", v -> {
            if (v != null && v.contains("y")) return;
            if (web.canGoBack()) web.goBack();
            else finish();
        });
    }

    @Override
    protected void onSaveInstanceState(Bundle out) {
        super.onSaveInstanceState(out);
        web.saveState(out);
    }

    private class Shell {
        @JavascriptInterface
        public void retry() {
            runOnUiThread(() -> web.loadUrl(START_URL));
        }
    }
}
