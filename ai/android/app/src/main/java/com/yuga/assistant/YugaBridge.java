package com.yuga.assistant;

import android.app.AlarmManager;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.ContentResolver;
import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.hardware.camera2.CameraManager;
import android.net.Uri;
import android.os.Build;
import android.os.VibrationEffect;
import android.os.Vibrator;
import android.provider.ContactsContract;
import android.provider.Settings;
import android.telephony.SmsManager;
import android.webkit.JavascriptInterface;
import android.webkit.WebView;
import androidx.core.app.NotificationCompat;
import org.json.JSONObject;
import org.json.JSONArray;

/**
 * YugaBridge — JavaScript ↔ Android native bridge.
 *
 * Called from assistant/index.php via:
 *   window.YugaBridge.execute(JSON.stringify(action))
 *
 * Each action type maps to a real Android API call.
 */
public class YugaBridge {

    private final Context context;
    private final WebView webView;
    private static final String NOTIF_CHANNEL = "yuga_channel";
    private boolean torchOn = false;

    public YugaBridge(Context ctx, WebView wv) {
        this.context = ctx;
        this.webView = wv;
        createNotificationChannel();
    }

    // ── Main dispatcher — called from JS ────────────────────────────────
    @JavascriptInterface
    public void execute(String actionJson) {
        try {
            JSONObject action = new JSONObject(actionJson);
            String type = action.optString("type", "");

            switch (type) {
                case "open":
                case "search":
                case "weather":
                case "maps":
                case "youtube":
                case "translate":
                    openUrl(action.optString("url", ""));
                    break;

                case "call":
                    makeCall(action.optString("url", "").replace("tel:", ""));
                    break;

                case "email":
                    openUrl(action.optString("url", ""));
                    break;

                case "sms":
                    sendSMS(action.optString("to", ""), action.optString("text", ""));
                    break;

                case "timer":
                case "reminder":
                    long ms = action.optLong("ms", 60000);
                    String label = action.optString("label", "Yuga reminder");
                    setAlarm(ms, label);
                    break;

                case "note":
                    saveNote(action.optString("text", ""));
                    break;

                case "clipboard":
                    copyToClipboard(action.optString("text", ""));
                    break;

                case "torch":
                    toggleTorch();
                    break;

                case "vibrate":
                    vibrate(action.optLong("ms", 300));
                    break;

                case "stop":
                    // Nothing needed — JS already handles TTS cancel
                    break;

                case "time":
                case "date":
                    // Handled in JS — no native action needed
                    break;

                case "volume_hint":
                    openSystemSettings(Settings.ACTION_SOUND_SETTINGS);
                    break;
            }
        } catch (Exception e) {
            postToJs("console.error('YugaBridge error: " + e.getMessage() + "')");
        }
    }

    // ── Open any URL (browser / intent) ─────────────────────────────────
    @JavascriptInterface
    public void openUrl(String url) {
        if (url == null || url.isEmpty()) return;
        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        context.startActivity(intent);
    }

    // ── Make a phone call ────────────────────────────────────────────────
    @JavascriptInterface
    public void makeCall(String number) {
        if (number == null || number.isEmpty()) return;
        // Strip anything not a digit or +
        number = number.replaceAll("[^0-9+]", "");
        Intent intent = new Intent(Intent.ACTION_CALL, Uri.parse("tel:" + number));
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        context.startActivity(intent);
    }

    // ── Send SMS ─────────────────────────────────────────────────────────
    @JavascriptInterface
    public void sendSMS(String to, String message) {
        try {
            SmsManager sms = SmsManager.getDefault();
            sms.sendTextMessage(to, null, message, null, null);
            showNotification("SMS Sent", "Message sent to " + to);
        } catch (Exception e) {
            postToJs("alert('SMS failed: " + e.getMessage() + "')");
        }
    }

    // ── Set alarm / timer ────────────────────────────────────────────────
    @JavascriptInterface
    public void setAlarm(long delayMs, String label) {
        AlarmManager am = (AlarmManager) context.getSystemService(Context.ALARM_SERVICE);
        Intent intent   = new Intent(context, AlarmReceiver.class);
        intent.putExtra("label", label);

        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) flags |= PendingIntent.FLAG_IMMUTABLE;

        PendingIntent pi = PendingIntent.getBroadcast(context, (int)System.currentTimeMillis(), intent, flags);
        long triggerAt   = System.currentTimeMillis() + delayMs;

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            am.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerAt, pi);
        } else {
            am.setExact(AlarmManager.RTC_WAKEUP, triggerAt, pi);
        }
    }

    // ── Show a notification ──────────────────────────────────────────────
    @JavascriptInterface
    public void showNotification(String title, String body) {
        NotificationManager nm = (NotificationManager) context.getSystemService(Context.NOTIFICATION_SERVICE);
        NotificationCompat.Builder builder = new NotificationCompat.Builder(context, NOTIF_CHANNEL)
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .setContentTitle(title)
            .setContentText(body)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true);
        nm.notify((int)System.currentTimeMillis(), builder.build());
    }

    // ── Copy to clipboard ────────────────────────────────────────────────
    @JavascriptInterface
    public void copyToClipboard(String text) {
        ClipboardManager cm = (ClipboardManager) context.getSystemService(Context.CLIPBOARD_SERVICE);
        cm.setPrimaryClip(ClipData.newPlainText("Yuga", text));
    }

    // ── Save note (shared preferences) ──────────────────────────────────
    @JavascriptInterface
    public void saveNote(String text) {
        String existing = context.getSharedPreferences("yuga_notes", Context.MODE_PRIVATE)
            .getString("notes", "[]");
        try {
            JSONArray notes = new JSONArray(existing);
            JSONObject note = new JSONObject();
            note.put("text", text);
            note.put("time", System.currentTimeMillis());
            notes.put(note);
            context.getSharedPreferences("yuga_notes", Context.MODE_PRIVATE)
                .edit().putString("notes", notes.toString()).apply();
        } catch (Exception ignored) {}
    }

    // ── Get notes (returns JSON to JS) ───────────────────────────────────
    @JavascriptInterface
    public String getNotes() {
        return context.getSharedPreferences("yuga_notes", Context.MODE_PRIVATE)
            .getString("notes", "[]");
    }

    // ── Read contacts (returns JSON to JS) ──────────────────────────────
    @JavascriptInterface
    public String getContacts() {
        JSONArray contacts = new JSONArray();
        try {
            ContentResolver cr = context.getContentResolver();
            Cursor c = cr.query(ContactsContract.CommonDataKinds.Phone.CONTENT_URI,
                new String[]{ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME,
                             ContactsContract.CommonDataKinds.Phone.NUMBER},
                null, null, ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME + " ASC");
            if (c != null) {
                while (c.moveToNext()) {
                    JSONObject contact = new JSONObject();
                    contact.put("name",   c.getString(0));
                    contact.put("number", c.getString(1));
                    contacts.put(contact);
                }
                c.close();
            }
        } catch (Exception ignored) {}
        return contacts.toString();
    }

    // ── Vibrate device ───────────────────────────────────────────────────
    @JavascriptInterface
    public void vibrate(long ms) {
        Vibrator v = (Vibrator) context.getSystemService(Context.VIBRATOR_SERVICE);
        if (v == null) return;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            v.vibrate(VibrationEffect.createOneShot(ms, VibrationEffect.DEFAULT_AMPLITUDE));
        } else {
            v.vibrate(ms);
        }
    }

    // ── Toggle flashlight ────────────────────────────────────────────────
    @JavascriptInterface
    public void toggleTorch() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) return;
        try {
            CameraManager cm = (CameraManager) context.getSystemService(Context.CAMERA_SERVICE);
            String cameraId   = cm.getCameraIdList()[0];
            torchOn = !torchOn;
            cm.setTorchMode(cameraId, torchOn);
        } catch (Exception ignored) {}
    }

    // ── Get device info ──────────────────────────────────────────────────
    @JavascriptInterface
    public String getDeviceInfo() {
        try {
            JSONObject info = new JSONObject();
            info.put("model",   Build.MODEL);
            info.put("brand",   Build.BRAND);
            info.put("android", Build.VERSION.RELEASE);
            info.put("sdk",     Build.VERSION.SDK_INT);
            return info.toString();
        } catch (Exception e) { return "{}"; }
    }

    // ── Open system settings screen ──────────────────────────────────────
    @JavascriptInterface
    public void openSystemSettings(String action) {
        Intent intent = new Intent(action);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        context.startActivity(intent);
    }

    // ── Share text via Android share sheet ───────────────────────────────
    @JavascriptInterface
    public void shareText(String text) {
        Intent share = new Intent(Intent.ACTION_SEND);
        share.setType("text/plain");
        share.putExtra(Intent.EXTRA_TEXT, text);
        share.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        context.startActivity(Intent.createChooser(share, "Share via").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK));
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel ch = new NotificationChannel(
                NOTIF_CHANNEL, "Yuga Assistant", NotificationManager.IMPORTANCE_HIGH);
            ch.setDescription("Timers, reminders, and alerts from Yuga");
            NotificationManager nm = (NotificationManager) context.getSystemService(Context.NOTIFICATION_SERVICE);
            nm.createNotificationChannel(ch);
        }
    }

    private void postToJs(final String js) {
        webView.post(() -> webView.evaluateJavascript(js, null));
    }
}
