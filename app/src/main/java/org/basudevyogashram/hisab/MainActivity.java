package org.basudevyogashram.hisab;

import android.Manifest;
import android.app.KeyguardManager;
import android.content.SharedPreferences;
import android.hardware.biometrics.BiometricManager;
import android.hardware.biometrics.BiometricPrompt;
import android.hardware.fingerprint.FingerprintManager;
import android.os.CancellationSignal;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyPermanentlyInvalidatedException;
import android.security.keystore.KeyProperties;
import android.util.Base64;
import java.nio.charset.StandardCharsets;
import java.security.KeyStore;
import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;
import org.json.JSONObject;
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
import android.print.PrintAttributes;
import android.print.PrintManager;
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

    /* ---------- login with fingerprint ----------
       The server gives this phone a long random device key (never the password). It is encrypted with an
       Android Keystore key that can only be used after a fingerprint check, so the phone alone cannot read it. */
    private static final String KEY_ALIAS = "bya_login";
    private static final String PREFS = "bya_bio";

    private SharedPreferences prefs() { return getSharedPreferences(PREFS, MODE_PRIVATE); }

    @SuppressWarnings("deprecation")
    private boolean bioAvailable() {
        if (Build.VERSION.SDK_INT < 28) return false;
        KeyguardManager km = (KeyguardManager) getSystemService(Context.KEYGUARD_SERVICE);
        if (km == null || !km.isDeviceSecure()) return false;
        if (Build.VERSION.SDK_INT >= 30) {
            BiometricManager bm = getSystemService(BiometricManager.class);
            return bm != null && bm.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG) == BiometricManager.BIOMETRIC_SUCCESS;
        }
        if (Build.VERSION.SDK_INT == 29) {
            BiometricManager bm = getSystemService(BiometricManager.class);
            return bm != null && bm.canAuthenticate() == BiometricManager.BIOMETRIC_SUCCESS;
        }
        FingerprintManager fm = (FingerprintManager) getSystemService(Context.FINGERPRINT_SERVICE);
        return fm != null && fm.isHardwareDetected() && fm.hasEnrolledFingerprints();
    }

    private void bioClearAll() {
        prefs().edit().clear().apply();
        try { KeyStore ks = KeyStore.getInstance("AndroidKeyStore"); ks.load(null); ks.deleteEntry(KEY_ALIAS); } catch (Exception ignored) { }
    }

    private SecretKey newKey() throws Exception {
        KeyStore ks = KeyStore.getInstance("AndroidKeyStore"); ks.load(null); ks.deleteEntry(KEY_ALIAS);
        KeyGenerator kg = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        KeyGenParameterSpec.Builder b = new KeyGenParameterSpec.Builder(KEY_ALIAS, KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setUserAuthenticationRequired(true)
                .setInvalidatedByBiometricEnrollment(true);
        kg.init(b.build());
        return kg.generateKey();
    }

    private SecretKey oldKey() throws Exception {
        KeyStore ks = KeyStore.getInstance("AndroidKeyStore"); ks.load(null);
        return (SecretKey) ks.getKey(KEY_ALIAS, null);
    }

    /* tell the page what happened: window.byaBio(event, data) */
    private void toPage(String ev, String data) {
        runOnUiThread(() -> web.evaluateJavascript("window.byaBio && window.byaBio(" + JSONObject.quote(ev) + "," + JSONObject.quote(data == null ? "" : data) + ")", null));
    }

    private interface Done { void accept(Cipher c) throws Exception; }

    private void prompt(String title, Cipher cipher, Done ok) {
        if (Build.VERSION.SDK_INT < 28) { toPage("error", "এই ফোনে চলবে না।"); return; }
        BiometricPrompt.Builder pb = new BiometricPrompt.Builder(this)
                .setTitle(title)
                .setSubtitle("ফোনে সেভ করা আঙুলের ছাপ দিন")
                .setNegativeButton("বাতিল", getMainExecutor(), (d, w) -> toPage("cancel", ""));
        if (Build.VERSION.SDK_INT >= 30) pb.setAllowedAuthenticators(BiometricManager.Authenticators.BIOMETRIC_STRONG);
        pb.build().authenticate(new BiometricPrompt.CryptoObject(cipher), new CancellationSignal(), getMainExecutor(),
                new BiometricPrompt.AuthenticationCallback() {
                    @Override
                    public void onAuthenticationSucceeded(BiometricPrompt.AuthenticationResult r) {
                        try { ok.accept(r.getCryptoObject().getCipher()); } catch (Exception e) { toPage("error", "সমস্যা হয়েছে: " + e.getMessage()); }
                    }

                    @Override
                    public void onAuthenticationError(int code, CharSequence msg) {
                        if (code == BiometricPrompt.BIOMETRIC_ERROR_USER_CANCELED || code == BiometricPrompt.BIOMETRIC_ERROR_CANCELED
                                || code == BiometricPrompt.BIOMETRIC_ERROR_NEGATIVE_BUTTON) toPage("cancel", "");
                        else toPage("error", String.valueOf(msg));
                    }
                });
    }

    private class Shell {
        @JavascriptInterface
        public String bioState() {
            try {
                JSONObject o = new JSONObject();
                o.put("avail", bioAvailable());
                o.put("user", prefs().getString("user", ""));
                return o.toString();
            } catch (Exception e) { return "{\"avail\":false,\"user\":\"\"}"; }
        }

        @JavascriptInterface
        public void bioSave(final String user, final String secret) {
            runOnUiThread(() -> {
                try {
                    Cipher c = Cipher.getInstance("AES/GCM/NoPadding");
                    c.init(Cipher.ENCRYPT_MODE, newKey());
                    prompt("আঙুলের ছাপে লগইন চালু", c, done -> {
                        try {
                            byte[] ct = done.doFinal(secret.getBytes(StandardCharsets.UTF_8));
                            prefs().edit().putString("user", user)
                                    .putString("iv", Base64.encodeToString(done.getIV(), Base64.NO_WRAP))
                                    .putString("ct", Base64.encodeToString(ct, Base64.NO_WRAP)).apply();
                            toPage("saved", user);
                        } catch (Exception e) { toPage("error", "সেভ করা যায়নি: " + e.getMessage()); }
                    });
                } catch (Exception e) { toPage("error", "এই ফোনে চালু করা গেল না: " + e.getMessage()); }
            });
        }

        @JavascriptInterface
        public void bioLogin() {
            runOnUiThread(() -> {
                final SharedPreferences p = prefs();
                final String user = p.getString("user", ""), iv = p.getString("iv", ""), ct = p.getString("ct", "");
                if (user.isEmpty() || ct.isEmpty()) { toPage("invalid", ""); return; }
                try {
                    Cipher c = Cipher.getInstance("AES/GCM/NoPadding");
                    SecretKey k = oldKey();
                    if (k == null) { bioClearAll(); toPage("invalid", ""); return; }
                    c.init(Cipher.DECRYPT_MODE, k, new GCMParameterSpec(128, Base64.decode(iv, Base64.NO_WRAP)));
                    prompt("আঙুলের ছাপ দিয়ে লগইন", c, done -> {
                        try {
                            String secret = new String(done.doFinal(Base64.decode(ct, Base64.NO_WRAP)), StandardCharsets.UTF_8);
                            JSONObject o = new JSONObject(); o.put("user", user); o.put("secret", secret);
                            toPage("login", o.toString());
                        } catch (Exception e) { toPage("error", "পড়া যায়নি: " + e.getMessage()); }
                    });
                } catch (KeyPermanentlyInvalidatedException e) {
                    bioClearAll(); toPage("invalid", "");
                } catch (Exception e) { toPage("error", "সমস্যা হয়েছে: " + e.getMessage()); }
            });
        }

        @JavascriptInterface
        public void bioClear() { bioClearAll(); }

        /* the page's print view: Android print screen, where "Save as PDF" is one of the printers */
        @JavascriptInterface
        public void print(final String title) {
            runOnUiThread(() -> {
                String name = (title == null || title.trim().isEmpty()) ? "Basudev-Hisab" : title.trim();
                PrintManager pm = (PrintManager) getSystemService(Context.PRINT_SERVICE);
                pm.print(name, web.createPrintDocumentAdapter(name),
                        new PrintAttributes.Builder().setMediaSize(PrintAttributes.MediaSize.ISO_A4).build());
            });
        }

        @JavascriptInterface
        public void retry() {
            runOnUiThread(() -> web.loadUrl(START_URL));
        }
    }
}
