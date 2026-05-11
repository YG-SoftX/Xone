package com.yuga.assistant;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.os.VibrationEffect;
import android.os.Vibrator;
import androidx.core.app.NotificationCompat;

/**
 * Fires when a Yuga timer or reminder goes off.
 * Works even if the app is closed (BroadcastReceiver).
 */
public class AlarmReceiver extends BroadcastReceiver {

    private static final String CHANNEL = "yuga_channel";

    @Override
    public void onReceive(Context context, Intent intent) {
        String label = intent.getStringExtra("label");
        if (label == null) label = "Yuga Reminder";

        // Show notification
        createChannel(context);
        NotificationManager nm = (NotificationManager) context.getSystemService(Context.NOTIFICATION_SERVICE);
        NotificationCompat.Builder b = new NotificationCompat.Builder(context, CHANNEL)
            .setSmallIcon(android.R.drawable.ic_dialog_alert)
            .setContentTitle("⏰ " + label)
            .setContentText("Your Yuga timer is done!")
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setAutoCancel(true)
            .setDefaults(NotificationCompat.DEFAULT_ALL);
        nm.notify((int) System.currentTimeMillis(), b.build());

        // Vibrate
        Vibrator v = (Vibrator) context.getSystemService(Context.VIBRATOR_SERVICE);
        if (v != null) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                v.vibrate(VibrationEffect.createWaveform(new long[]{0, 400, 200, 400}, -1));
            } else {
                v.vibrate(new long[]{0, 400, 200, 400}, -1);
            }
        }
    }

    private void createChannel(Context ctx) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel ch = new NotificationChannel(CHANNEL, "Yuga Assistant", NotificationManager.IMPORTANCE_HIGH);
            ((NotificationManager) ctx.getSystemService(Context.NOTIFICATION_SERVICE)).createNotificationChannel(ch);
        }
    }
}
