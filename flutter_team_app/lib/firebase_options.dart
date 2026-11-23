// File generated for Firebase initialization
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        return android;
    }
  }

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'AIzaSyCtIFBvJI7D3Ynl8-1dLkM4M5ri6tR1p8Q',
    appId: '1:519365799103:web:23bb6ef57f267f8d8baa0a',
    messagingSenderId: '519365799103',
    projectId: 'fbibd-team',
    authDomain: 'fbibd-team.firebaseapp.com',
    storageBucket: 'fbibd-team.firebasestorage.app',
  );

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyCtIFBvJI7D3Ynl8-1dLkM4M5ri6tR1p8Q',
    appId: '1:519365799103:android:23bb6ef57f267f8d8baa0a',
    messagingSenderId: '519365799103',
    projectId: 'fbibd-team',
    storageBucket: 'fbibd-team.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyCtIFBvJI7D3Ynl8-1dLkM4M5ri6tR1p8Q',
    appId: '1:519365799103:ios:23bb6ef57f267f8d8baa0a',
    messagingSenderId: '519365799103',
    projectId: 'fbibd-team',
    storageBucket: 'fbibd-team.firebasestorage.app',
  );
}
