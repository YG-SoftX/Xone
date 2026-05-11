# Yuga Android App — Build Guide

## What this is
A native Android WebView wrapper that loads your Yuga assistant and gives it
**full OS-level device control** via a Java bridge (YugaBridge.java).

## Step 1 — Set your server URL

Open `app/src/main/java/com/yuga/assistant/MainActivity.java` and change:

```java
public static final String SERVER_URL = "https://yourdomain.com/yuga/assistant/index.php";
```

Replace `yourdomain.com` with your actual cPanel domain.

## Step 2 — Install Android Studio (free)

Download from: https://developer.android.com/studio
Install with defaults. Takes ~15 minutes.

## Step 3 — Open the project

1. Open Android Studio
2. Click "Open" → select this `android/` folder
3. Wait for Gradle sync (downloads dependencies, 2-5 min first time)

## Step 4 — Build the APK

### For testing on your old phone (debug APK — no signing needed):
```
Build → Build Bundle(s) / APK(s) → Build APK(s)
```
APK appears in: `app/build/outputs/apk/debug/app-debug.apk`

### For release (signed APK):
```
Build → Generate Signed Bundle / APK → APK
```

## Step 5 — Install on your phone

**Option A — USB:**
1. On your phone: Settings → Developer Options → Enable USB Debugging
2. Connect USB → Android Studio detects it
3. Click the green ▶ Run button

**Option B — File transfer:**
1. Copy `app-debug.apk` to your phone
2. Open Files app → tap the APK
3. Allow "Install unknown apps" when prompted
4. Install

## What the app can do (vs browser)

| Feature | Browser | App |
|---------|---------|-----|
| Voice input/output | ✅ | ✅ |
| Open websites | ✅ | ✅ |
| Set timers | ✅ (tab must be open) | ✅ (works when app closed) |
| Make phone calls | ⚠️ (depends on phone) | ✅ direct |
| Send SMS | ❌ | ✅ |
| Read contacts | ❌ | ✅ |
| Flashlight | ❌ | ✅ |
| Vibrate | ❌ | ✅ |
| Works offline (cached) | ⚠️ | ✅ |
| Reminders when app closed | ❌ | ✅ |

## Minimum requirements

- Android 5.0 (Lollipop) or newer — covers phones from 2014+
- Internet connection to reach your cPanel server
- Microphone for voice commands

## Permissions the app requests

On first launch, your phone will ask:
- Microphone — for voice commands
- Phone — for "call X" commands
- SMS — for "send message to X"
- Contacts — for looking up names
- Location — for "weather near me" / maps
- Notifications — for timers and reminders
- Camera — for flashlight

You can deny any you don't want.
