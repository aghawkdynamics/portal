# AGHAWK Portal - Android App Wrapper

## Overview

This directory contains the Android application wrapper for the AGHAWK Portal. The app is a WebView-based wrapper that loads the portal website (https://portal.aghawkdynamics.com) and provides a native Android experience.

## Quick Start

1. **Open in Android Studio**:
   ```bash
   # Open the apps/android directory in Android Studio
   ```

2. **Build the app**:
   - File > Sync Project with Gradle Files
   - Build > Make Project
   - Build > Build Bundle(s) / APK(s) > Build APK(s)

3. **Install on device**:
   - Connect Android device via USB
   - Run > Run 'app'
   - Or manually install the APK from `app/build/outputs/apk/debug/`

## What This App Does

The Android wrapper provides:

✅ **Full Portal Access**: Complete access to all portal features in a native Android app
✅ **Web Compatibility**: JavaScript, cookies, storage, and all modern web features enabled
✅ **File Operations**: Upload files from device (camera/gallery) and download files
✅ **Offline Support**: Caching for improved performance and partial offline functionality  
✅ **Native Features**: Pull-to-refresh, back button navigation, deep linking
✅ **Security**: HTTPS-only, secure file handling, proper permissions

## Project Structure

```
apps/android/
├── app/
│   ├── src/main/
│   │   ├── java/com/aghawkdynamics/portal/
│   │   │   └── MainActivity.kt          # Main WebView activity
│   │   ├── res/
│   │   │   ├── layout/
│   │   │   │   └── activity_main.xml    # Main layout with WebView
│   │   │   ├── values/
│   │   │   │   ├── strings.xml          # App name and strings
│   │   │   │   ├── colors.xml           # Color definitions
│   │   │   │   └── themes.xml           # App theme
│   │   │   ├── xml/
│   │   │   │   ├── network_security_config.xml  # HTTPS security
│   │   │   │   └── file_paths.xml       # File provider paths
│   │   │   ├── drawable/                # App icons and graphics
│   │   │   └── mipmap-*/                # Launcher icons
│   │   └── AndroidManifest.xml          # App configuration
│   ├── build.gradle                     # App-level build config
│   └── proguard-rules.pro              # ProGuard rules
├── gradle/                              # Gradle wrapper files
├── build.gradle                         # Project-level build config
├── settings.gradle                      # Project settings
├── gradle.properties                    # Gradle properties
├── .gitignore                          # Git ignore rules
└── README.md                           # Detailed documentation
```

## Key Features Implementation

### WebView Configuration
The MainActivity configures WebView with:
- JavaScript enabled
- DOM storage enabled
- File access for uploads/downloads
- Cache for performance
- Custom user agent
- Safe browsing

### File Upload Support
Handles file selection via:
- Device gallery
- Camera capture
- Multiple file selection
- Permission management

### Download Support
Implements download functionality:
- Uses Android DownloadManager
- Saves to device Downloads folder
- Shows download notifications

### Security
- HTTPS-only connections via network security config
- Permissions requested at runtime
- Secure file provider for file sharing
- ProGuard rules for release builds

## Web Portal Compatibility

The wrapper is designed to work seamlessly with web applications that use:

- **JavaScript frameworks**: React, Vue, Angular, vanilla JS
- **AJAX/Fetch**: All async requests work normally
- **Local/Session Storage**: Full support for web storage APIs
- **Cookies**: Session and persistent cookies
- **File APIs**: FileReader, Blob, File upload
- **Responsive Design**: Viewport and media queries
- **Forms**: All HTML5 input types
- **WebSockets**: Real-time communication (if portal uses it)

## Testing the App

### Testing on Emulator
1. Create an AVD in Android Studio (API 24+)
2. Run the app on the emulator
3. Test all portal features

### Testing on Physical Device
1. Enable USB debugging on device
2. Connect via USB
3. Run the app from Android Studio
4. Grant permissions when prompted

### Test Checklist
- [ ] Portal loads correctly
- [ ] Login/authentication works
- [ ] Forms can be submitted
- [ ] Files can be uploaded
- [ ] Files can be downloaded
- [ ] Navigation works (back button)
- [ ] Pull-to-refresh works
- [ ] External links open in browser
- [ ] App survives rotation
- [ ] Deep links work

## Configuration Options

### Change Portal URL
Edit `MainActivity.kt`:
```kotlin
private val portalUrl = "https://your-portal-url.com"
```

### Change App Name
Edit `app/src/main/res/values/strings.xml`:
```xml
<string name="app_name">Your App Name</string>
```

### Change App Icon
Replace icon files in:
- `app/src/main/res/drawable/ic_launcher_foreground.xml`
- `app/src/main/res/mipmap-*/` directories

### Change Theme Colors
Edit `app/src/main/res/values/colors.xml`

## Deployment

### Debug Build
```bash
# In Android Studio: Build > Build Bundle(s) / APK(s) > Build APK(s)
# Output: app/build/outputs/apk/debug/app-debug.apk
```

### Release Build
1. Create keystore for signing
2. Configure signing in `app/build.gradle`
3. Build release APK or AAB
4. Upload to Google Play Store

See the main [README.md](./README.md) for detailed build and deployment instructions.

## Requirements

- Android Studio Arctic Fox or later
- Android SDK API 34
- JDK 8 or higher
- Minimum device: Android 7.0 (API 24)
- Target device: Android 14 (API 34)

## Troubleshooting

See the main [README.md](./README.md) for common issues and solutions.

## Documentation

For comprehensive documentation, see [README.md](./README.md) in this directory.

## Support

For questions or issues:
1. Check the [README.md](./README.md) troubleshooting section
2. Review Android Studio build logs
3. Check device logcat for runtime errors
4. File an issue in the main portal repository
