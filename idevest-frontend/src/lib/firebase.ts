import { initializeApp, type FirebaseApp } from "firebase/app";
import { getAuth, type Auth } from "firebase/auth";

/**
 * Firebase web config.
 *
 * These values are NOT secrets — Firebase web API keys are public identifiers.
 * Security is enforced via Firebase Console → Authentication → Settings → Authorized domains,
 * and via Firestore/Storage rules if/when those are used.
 *
 * To override per-environment, set VITE_FIREBASE_* in .env.
 */
const firebaseConfig = {
  apiKey:
    import.meta.env.VITE_FIREBASE_API_KEY ??
    "AIzaSyAe5BdkEvg7_M_1hdPKZXOxXEcpkoJ8mZ8",
  authDomain:
    import.meta.env.VITE_FIREBASE_AUTH_DOMAIN ??
    "ideavest-otp.firebaseapp.com",
  projectId:
    import.meta.env.VITE_FIREBASE_PROJECT_ID ?? "ideavest-otp",
  storageBucket:
    import.meta.env.VITE_FIREBASE_STORAGE_BUCKET ??
    "ideavest-otp.firebasestorage.app",
  messagingSenderId:
    import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID ??
    "722827466404",
  appId:
    import.meta.env.VITE_FIREBASE_APP_ID ??
    "1:722827466404:web:ade788be3e95e293b3f831",
  measurementId:
    import.meta.env.VITE_FIREBASE_MEASUREMENT_ID ??
    "G-9TYNVMBM6F",
};

let _app: FirebaseApp | null = null;
let _auth: Auth | null = null;

export function getFirebaseApp(): FirebaseApp {
  if (!_app) _app = initializeApp(firebaseConfig);
  return _app;
}

export function getFirebaseAuth(): Auth {
  if (!_auth) _auth = getAuth(getFirebaseApp());
  return _auth;
}
