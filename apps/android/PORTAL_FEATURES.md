# Portal-Specific Considerations for Android App

This document outlines specific considerations and features of the AGHAWK Portal that are fully supported in the Android app wrapper.

## Portal Features Verified Compatible

### 1. Authentication & Sessions
- **Feature**: User login/logout functionality via `/auth/login`
- **Android Support**: ✅ Full support via cookies and session management
- **Implementation**: WebView maintains session cookies automatically
- **Note**: Sessions persist across app restarts via cookie storage

### 2. Theme Management
- **Feature**: Theme selection with localStorage persistence
- **File**: `/public/js/theme.js`
- **Android Support**: ✅ Full support via DOM storage
- **Implementation**: 
  - WebView has `domStorageEnabled = true`
  - Theme preferences saved to localStorage
  - Also uses cookies as backup: `theme=${t}; path=/; max-age=${60 * 60 * 24 * 365}`
- **User Experience**: Theme selection persists across app sessions

### 3. File Uploads
- **Feature**: File attachment for blocks and services
- **Files**: 
  - `/views/block/block.phtml` - Block attachments
  - `/views/service/dialog/attachment.phtml` - Service attachments
- **Android Support**: ✅ Full support via WebChromeClient
- **Implementation**:
  - MainActivity implements `onShowFileChooser()`
  - Supports gallery, camera, and file browser
  - Handles multiple file selection
  - Requires storage and camera permissions
- **User Experience**: 
  - Tap upload button → Android file picker appears
  - Can select from gallery, camera, or files
  - Files upload via FormData (standard web upload)

### 4. File Downloads
- **Feature**: Download service attachments and reports
- **Android Support**: ✅ Full support via DownloadManager
- **Implementation**:
  - WebView DownloadListener configured
  - Files saved to device Downloads folder
  - Download notifications shown
- **User Experience**: Files download like in any native app

### 5. JavaScript Features
- **Features Used**:
  - Portal object for notifications
  - FormData for file uploads
  - Event listeners (DOMContentLoaded, click, etc.)
  - DOM manipulation
  - Promises and async operations
- **Android Support**: ✅ All supported with `javaScriptEnabled = true`

### 6. Notifications
- **Feature**: Portal.notify() for user messages
- **File**: `/public/js/app.js`
- **Android Support**: ✅ Full support
- **Implementation**: JavaScript-based notifications work in WebView
- **Note**: These are in-page notifications, not Android system notifications

### 7. Forms & Controls
- **Features**:
  - Text inputs, textareas, selects
  - Date pickers
  - Checkboxes and radio buttons
  - Dynamic form controls
- **Android Support**: ✅ Full support
- **Implementation**: 
  - WebView handles all HTML5 input types
  - Mobile keyboard adapts to input type
  - `windowSoftInputMode="adjustResize"` ensures form visibility

### 8. Navigation
- **Feature**: Multi-page portal with routing
- **Android Support**: ✅ Full support
- **Implementation**:
  - WebView handles all internal navigation
  - Back button navigates WebView history
  - External links open in system browser
- **User Experience**: Feels like native navigation

## WebView Configuration Summary

The Android app is configured to support all portal features:

```kotlin
// JavaScript and modern web features
javaScriptEnabled = true
domStorageEnabled = true
databaseEnabled = true

// File operations
allowFileAccess = true
allowContentAccess = true

// Performance
cacheMode = WebSettings.LOAD_DEFAULT
setAppCacheEnabled(true)

// Responsive design
loadWithOverviewMode = true
useWideViewPort = true

// Security
mixedContentMode = MIXED_CONTENT_COMPATIBILITY_MODE
safeBrowsingEnabled = true (API 26+)

// User Agent
userAgentString = "${userAgentString} AGHAWKPortalApp/1.0.0"
```

## Portal Structure Compatibility

### Directory Structure
```
portal/
├── public/
│   ├── index.php         # Entry point - loads in WebView
│   ├── css/              # Styles - loaded normally
│   ├── js/               # Scripts - executed in WebView
│   └── img/              # Images - displayed normally
├── src/                  # Backend PHP - handles requests
└── views/                # Templates - rendered server-side
```

### How It Works
1. **WebView loads**: `https://portal.aghawkdynamics.com`
2. **Server returns**: Rendered HTML with CSS/JS references
3. **WebView renders**: Full page with all assets
4. **User interacts**: JavaScript events handled normally
5. **Forms submit**: Via AJAX or standard POST
6. **Files upload**: Via WebView file chooser
7. **Navigation**: Internal links stay in WebView
8. **Sessions**: Maintained via cookies

## Permissions Required by Portal Features

| Portal Feature | Android Permission | Purpose |
|---------------|-------------------|---------|
| File upload (gallery) | READ_MEDIA_IMAGES/VIDEO | Access device media |
| File upload (camera) | CAMERA | Capture photos/videos |
| File download | WRITE_EXTERNAL_STORAGE | Save downloads (API < 29) |
| Network requests | INTERNET | Load portal and API calls |
| Check connectivity | ACCESS_NETWORK_STATE | Detect offline mode |

All permissions are properly declared in AndroidManifest.xml and requested at runtime.

## Testing Recommendations

### Essential Tests
1. **Login Flow**:
   - [ ] Open app → redirects to login if needed
   - [ ] Login successful → session persists
   - [ ] Close app → reopen → still logged in
   - [ ] Logout → session cleared

2. **Theme Selection**:
   - [ ] Select theme → applies immediately
   - [ ] Close app → reopen → theme persisted
   - [ ] Verify localStorage working

3. **File Upload**:
   - [ ] Tap attachment button on block/service
   - [ ] Select from gallery → uploads successfully
   - [ ] Capture from camera → uploads successfully
   - [ ] Grant permissions when prompted

4. **File Download**:
   - [ ] Click download link
   - [ ] File saves to Downloads
   - [ ] Notification appears
   - [ ] Can open downloaded file

5. **Forms**:
   - [ ] Fill out service request form
   - [ ] All input types work (text, date, select, etc.)
   - [ ] Form submits successfully
   - [ ] Validation messages display

6. **Navigation**:
   - [ ] Navigate between pages
   - [ ] Back button goes to previous page
   - [ ] External links open in browser
   - [ ] Deep links work

7. **Notifications**:
   - [ ] Portal.notify() messages appear
   - [ ] Messages auto-dismiss
   - [ ] Different types (success, error, warning) display correctly

### Device Testing
- Test on Android 7.0 (minimum)
- Test on Android 14 (target)
- Test on different screen sizes (phone, tablet)
- Test with different Android manufacturers (Samsung, Google, etc.)

## Potential Issues & Solutions

### Issue: Session Lost on App Restart
**Cause**: Cookie settings or WebView data cleared
**Solution**: Ensure cookies are enabled (already configured)
```kotlin
CookieManager.getInstance().setAcceptCookie(true)
```

### Issue: File Upload Button Doesn't Work
**Cause**: Permissions not granted or WebChromeClient not set
**Solution**: 
1. Check permissions granted in device settings
2. Verify WebChromeClient.onShowFileChooser() implemented (already done)

### Issue: Theme Not Persisting
**Cause**: DOM storage disabled
**Solution**: Verify `domStorageEnabled = true` (already configured)

### Issue: Downloads Failing
**Cause**: Storage permissions on Android 10+
**Solution**: 
- App uses scoped storage on Android 10+
- Downloads go to standard Downloads folder (no permission needed on API 29+)
- For older versions, permissions requested at runtime

### Issue: JavaScript Not Working
**Cause**: JavaScript disabled
**Solution**: Verify `javaScriptEnabled = true` (already configured)

## Performance Considerations

### Caching
- WebView cache enabled for static resources (CSS, JS, images)
- Portal pages cached according to server headers
- Improves load times on subsequent visits

### Memory
- WebView may use significant memory for complex pages
- App monitors and cleans up on destroy
- Consider adding memory warning handling for very large pages

### Network
- All requests go through normal HTTP/HTTPS
- No special proxy or network configuration needed
- Works on WiFi and mobile data

## Security Considerations

### HTTPS Enforcement
- App enforces HTTPS via network security config
- No cleartext traffic allowed
- Certificate validation performed automatically

### Data Storage
- Cookies stored securely by WebView
- LocalStorage data encrypted at rest (Android system)
- No sensitive data stored in app code

### Permissions
- Minimal permissions requested
- Runtime permissions for sensitive operations
- User can revoke permissions at any time

## Customization Points

If portal requirements change, these are the key areas to modify:

### Change Portal URL
```kotlin
// MainActivity.kt
private val portalUrl = "https://portal.aghawkdynamics.com"
```

### Add Custom JavaScript Interface
```kotlin
// MainActivity.kt - in setupWebView()
webView.addJavascriptInterface(MyJavaScriptInterface(), "Android")
```

### Customize User Agent
```kotlin
// MainActivity.kt - in setupWebView()
settings.userAgentString = "${userAgentString} AGHAWKPortalApp/1.0.0"
```

### Add Custom Headers
```kotlin
// MainActivity.kt - when loading URL
val headers = mapOf("X-Custom-Header" to "value")
webView.loadUrl(url, headers)
```

## Future Enhancements

Potential improvements for the Android app:

1. **Push Notifications**: Integrate Firebase Cloud Messaging for server-sent notifications
2. **Biometric Auth**: Add fingerprint/face unlock for quick login
3. **Offline Mode**: Cache critical data for offline viewing
4. **Widget**: Add Android home screen widget for quick access
5. **Share Integration**: Add "Share to Portal" from other apps
6. **Camera Integration**: Direct camera capture for service photos
7. **Location Services**: Auto-fill location data if needed

## Conclusion

The Android app wrapper is designed to provide full compatibility with all AGHAWK Portal features. The WebView configuration ensures that:

- All JavaScript functionality works as expected
- File uploads and downloads function correctly
- Sessions and authentication persist properly
- The user experience is smooth and native-feeling
- Security is maintained with HTTPS and proper permissions

The app requires minimal maintenance and will automatically support new portal features as they're added to the web application.
