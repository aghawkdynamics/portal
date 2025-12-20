# AGHAWK Portal - Android App

This is a native Android application wrapper for the AGHAWK Portal web application. The app provides a seamless mobile experience by wrapping the portal website (https://portal.aghawkdynamics.com) in a WebView with full functionality support.

## Features

- **Full Web Application Support**: Loads the complete AGHAWK Portal website with all features intact
- **JavaScript Enabled**: Full support for modern web applications with JavaScript, DOM storage, and databases
- **File Upload/Download**: Complete support for file uploads (including camera access) and downloads
- **Offline Storage**: Utilizes WebView cache and DOM storage for improved performance
- **Pull to Refresh**: Swipe down to refresh the page content
- **Deep Linking**: Opens portal.aghawkdynamics.com URLs directly in the app
- **Security**: 
  - HTTPS-only connections
  - Secure network configuration
  - Safe browsing enabled
- **Responsive**: Handles orientation changes and screen size adjustments
- **Navigation**: Hardware back button support for navigating through page history
- **External Links**: Opens non-portal URLs in the system browser

## Requirements

- **Minimum SDK**: API 24 (Android 7.0 Nougat)
- **Target SDK**: API 34 (Android 14)
- **Compile SDK**: API 34

## Permissions

The app requests the following permissions:

- **INTERNET**: Required to load the web portal
- **ACCESS_NETWORK_STATE**: Check network connectivity
- **READ_EXTERNAL_STORAGE**: Read files for uploads (API < 33)
- **WRITE_EXTERNAL_STORAGE**: Save downloaded files (API < 33)
- **READ_MEDIA_IMAGES/VIDEO/AUDIO**: Access media files for uploads (API 33+)
- **CAMERA**: Capture photos/videos for uploads

## Building the App

### Prerequisites

1. Install [Android Studio](https://developer.android.com/studio) (Arctic Fox or later recommended)
2. Install Android SDK with API level 34
3. Set up JDK 8 or higher

### Build Steps

1. Open the project in Android Studio:
   ```bash
   cd apps/android
   # Open this directory in Android Studio
   ```

2. Sync Gradle files:
   - Android Studio should automatically prompt to sync
   - Android Studio will download the Gradle wrapper automatically
   - Or manually: `File > Sync Project with Gradle Files`

3. Build the project:
   ```bash
   ./gradlew build
   ```
   **Note**: If you get an error about missing gradle-wrapper.jar, open the project in Android Studio first, which will generate the wrapper files.

4. Build APK:
   ```bash
   ./gradlew assembleDebug
   ```
   The APK will be generated at: `app/build/outputs/apk/debug/app-debug.apk`

5. Build release APK (requires signing configuration):
   ```bash
   ./gradlew assembleRelease
   ```

### Command Line Build (without Android Studio)

You can also build from the command line if you have Android SDK installed:

```bash
# Ensure ANDROID_HOME is set
export ANDROID_HOME=/path/to/android/sdk

# First-time setup: Generate Gradle wrapper (requires Gradle 8.0+ installed)
gradle wrapper

# Or open in Android Studio once to generate wrapper files

# Grant execute permission to gradlew
chmod +x gradlew

# Build debug APK
./gradlew assembleDebug

# Build release APK
./gradlew assembleRelease
```

## Installation

### Installing Debug APK

```bash
# Using adb
adb install app/build/outputs/apk/debug/app-debug.apk

# Or transfer the APK to your device and install manually
```

### Release Build

For production release:

1. Create a keystore:
   ```bash
   keytool -genkey -v -keystore aghawk-portal.keystore -alias aghawk-portal -keyalg RSA -keysize 2048 -validity 10000
   ```

2. Configure signing in `app/build.gradle`:
   ```gradle
   android {
       signingConfigs {
           release {
               storeFile file("path/to/aghawk-portal.keystore")
               storePassword "your-password"
               keyAlias "aghawk-portal"
               keyPassword "your-password"
           }
       }
       buildTypes {
           release {
               signingConfig signingConfigs.release
               minifyEnabled true
               proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
           }
       }
   }
   ```

3. Build signed APK:
   ```bash
   ./gradlew assembleRelease
   ```

## Configuration

### Changing the Portal URL

To point the app to a different URL, edit `MainActivity.kt`:

```kotlin
private val portalUrl = "https://portal.aghawkdynamics.com"  // Change this URL
```

### Customizing App Name and Theme

1. **App Name**: Edit `app/src/main/res/values/strings.xml`
2. **Colors**: Edit `app/src/main/res/values/colors.xml`
3. **Theme**: Edit `app/src/main/res/values/themes.xml`
4. **Icon**: Replace files in `app/src/main/res/mipmap-*` directories

### WebView Settings

WebView configuration can be adjusted in `MainActivity.kt` in the `setupWebView()` method. Key settings include:

- JavaScript: `javaScriptEnabled = true`
- DOM Storage: `domStorageEnabled = true`
- File Access: `allowFileAccess = true`
- Cache: `cacheMode = WebSettings.LOAD_DEFAULT`
- User Agent: Can be customized for server-side detection

## Testing

### Portal Feature Compatibility

The app is designed to support all portal features, including:

1. **User Authentication**: Login/logout functionality
2. **Forms**: All form inputs, selects, and file uploads
3. **JavaScript**: Full JS execution including AJAX requests
4. **LocalStorage/SessionStorage**: For maintaining state
5. **Cookies**: Session management
6. **File Operations**: Upload and download files
7. **Responsive Design**: Adapts to different screen sizes
8. **Notifications**: JavaScript alerts and confirms

### Testing Checklist

- [ ] Load portal homepage
- [ ] Test login functionality
- [ ] Test form submissions
- [ ] Test file uploads (from gallery and camera)
- [ ] Test file downloads
- [ ] Test navigation (back button)
- [ ] Test pull-to-refresh
- [ ] Test external link handling
- [ ] Test orientation changes
- [ ] Test offline behavior (cache)
- [ ] Test deep links

## Troubleshooting

### Common Issues

1. **Build fails with SDK version error**:
   - Install the required SDK version (API 34) via Android Studio SDK Manager

2. **App crashes on file upload**:
   - Ensure storage permissions are granted in device settings

3. **WebView shows blank page**:
   - Check internet connection
   - Verify the portal URL is accessible
   - Check logcat for JavaScript errors

4. **JavaScript not working**:
   - Ensure `javaScriptEnabled = true` in WebView settings

5. **Downloads not working**:
   - Check storage permissions
   - Verify download manager is enabled on device

### Debug Logging

To enable WebView debugging for Chrome DevTools:

```kotlin
// Add to onCreate() in MainActivity
if (BuildConfig.DEBUG) {
    WebView.setWebContentsDebuggingEnabled(true)
}
```

Then access `chrome://inspect` in Chrome on your development machine.

## Architecture

The app follows a simple single-activity architecture:

```
MainActivity
├── WebView (portal content)
├── SwipeRefreshLayout (pull-to-refresh)
├── WebViewClient (navigation handling)
├── WebChromeClient (file uploads, dialogs, progress)
└── DownloadListener (file downloads)
```

## Security Considerations

- HTTPS-only connections enforced via network security config
- No cleartext traffic allowed
- File Provider for secure file sharing
- Permission checks before accessing sensitive features
- ProGuard rules to protect WebView JavaScript interfaces

## Publishing

To publish to Google Play Store:

1. Create a signed release APK/AAB
2. Create a Google Play Developer account
3. Prepare store listing (screenshots, description, etc.)
4. Upload APK/AAB to Play Console
5. Complete all required information
6. Submit for review

### App Bundle (Recommended)

Google Play recommends using Android App Bundle:

```bash
./gradlew bundleRelease
```

The AAB will be at: `app/build/outputs/bundle/release/app-release.aab`

## License

This Android wrapper application follows the same license as the AGHAWK Portal project.

## Support

For issues related to:
- **App wrapper**: File issues in the portal repository
- **Portal functionality**: Check the main portal documentation
- **Android build issues**: Refer to Android Studio documentation

## Version History

- **1.0.0**: Initial release
  - WebView wrapper for portal.aghawkdynamics.com
  - Full web app feature support
  - File upload/download support
  - Deep linking support
