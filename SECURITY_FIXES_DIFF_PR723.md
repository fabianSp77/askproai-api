# Security Fixes - Code Diff for PR #723

**Purpose**: Exact code changes required to fix P0 and P1 vulnerabilities

---

## Fix 1: P0 - Bearer Token in healthcheck.php

### File: `/var/www/api-gateway/public/healthcheck.php`

```diff
 <?php
+/**
+ * Standalone Health Check Endpoint
+ *
+ * Purpose: Provides health status for CI/CD deployment gates
+ * Security: Bearer token authentication (reads from .env)
+ *
+ * SECURITY FIX (2025-11-02):
+ * - Now reads HEALTHCHECK_TOKEN from .env file instead of hardcoded value
+ * - Supports environment-specific token rotation
+ * - Prevents secret exposure in version control
+ */
+
+// Load environment variables from .env file
+// (Standalone PHP without Laravel bootstrap)
+$envPath = dirname(__DIR__) . '/.env';
+$expectedToken = '';
+
+if (file_exists($envPath)) {
+    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
+    foreach ($lines as $line) {
+        // Skip comments
+        if (strpos(trim($line), '#') === 0) {
+            continue;
+        }
+
+        // Parse KEY=VALUE
+        if (strpos($line, '=') !== false) {
+            list($key, $value) = explode('=', $line, 2);
+            $key = trim($key);
+            $value = trim($value, " \t\n\r\0\x0B\"'");
+
+            if ($key === 'HEALTHCHECK_TOKEN') {
+                $expectedToken = $value;
+                break;
+            }
+        }
+    }
+}
+
 header('Content-Type: application/json');

 // Check Bearer token
 $headers = getallheaders();
 $auth = $headers['Authorization'] ?? '';
-$expectedToken = 'PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0=';

-if ($auth === 'Bearer ' . $expectedToken) {
+// Verify token exists and matches (timing-safe comparison)
+if ($expectedToken && hash_equals('Bearer ' . $expectedToken, $auth)) {
     http_response_code(200);
     echo json_encode([
         'status' => 'healthy',
         'service' => 'staging',
         'timestamp' => time()
     ]);
 } else {
     http_response_code(403);
     echo json_encode(['error' => 'Unauthorized']);
 }
```

**Key Changes:**
1. ❌ **REMOVED**: Hardcoded token `'PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0='`
2. ✅ **ADDED**: .env file parsing to read `HEALTHCHECK_TOKEN`
3. ✅ **ADDED**: Timing-safe comparison with `hash_equals()`
4. ✅ **ADDED**: Check for token existence before comparison

---

## Fix 2: P1 - Session Fixation in DocsAuthController.php

### File: `/var/www/api-gateway/app/Http/Controllers/DocsAuthController.php`

```diff
     /**
      * Handle login attempt
+     *
+     * SECURITY FIX (2025-11-02):
+     * - Added session regeneration to prevent session fixation attacks
+     * - Session ID now changes after successful authentication
      */
     public function login(Request $request)
     {
         $request->validate([
             'username' => 'required|string',
             'password' => 'required|string',
         ]);

         $username = $request->input('username');
         $password = $request->input('password');

         // Get credentials from environment
         $validUsername = env('DOCS_USERNAME', 'admin');
         $validPassword = env('DOCS_PASSWORD', '');

-        // Validate credentials
-        if ($username === $validUsername && $password === $validPassword) {
+        // Validate credentials (timing-safe comparison)
+        if (hash_equals($validUsername, $username) && hash_equals($validPassword, $password)) {
             // Authentication successful

+            // SECURITY FIX: Regenerate session ID to prevent session fixation
+            // This ensures any pre-authentication session ID is invalidated
+            $request->session()->regenerate();
+
             $request->session()->put('docs_authenticated', true);
             $request->session()->put('docs_username', $username);
             $request->session()->put('docs_last_activity', time());
```

```diff
     /**
      * Handle logout
+     *
+     * SECURITY IMPROVEMENT (2025-11-02):
+     * - Enhanced session cleanup with regeneration
+     * - Prevents session reuse after logout
      */
     public function logout(Request $request)
     {
         $username = $request->session()->get('docs_username');

         Log::info('Docs logout', [
             'username' => $username,
             'ip' => $request->ip()
         ]);

-        // Clear session
+        // Clear session data
         $request->session()->forget(['docs_authenticated', 'docs_username', 'docs_last_activity', 'docs_remember']);

+        // Regenerate session ID to prevent session reuse
+        $request->session()->regenerate();
+
         return redirect()->route('docs.backup-system.login')
             ->with('success', 'Sie wurden erfolgreich abgemeldet.');
     }
```

**Key Changes:**
1. ✅ **ADDED**: `$request->session()->regenerate()` after successful login
2. ✅ **IMPROVED**: `hash_equals()` for timing-safe password comparison
3. ✅ **ADDED**: `$request->session()->regenerate()` in logout
4. ✅ **ADDED**: Security documentation in docblocks

---

## Fix 3: Rate Limiting (Recommended)

### File: `/var/www/api-gateway/routes/web.php` (Line 95)

```diff
 // Handle login (no auth required)
 Route::post('/login', [\App\Http\Controllers\DocsAuthController::class, 'login'])
+    ->middleware('throttle:5,1') // 5 attempts per minute per IP
     ->name('docs.backup-system.login.submit');
```

**Key Changes:**
1. ✅ **ADDED**: Rate limiting middleware (5 attempts per minute)
2. ✅ **DEFENSE**: Prevents brute force attacks

---

## Post-Deployment Token Rotation

### Required: Rotate HEALTHCHECK_TOKEN

```bash
# Generate new token
openssl rand -base64 32

# Example output: x7K2mP9vL5nQ8wR4tY6uZ3aS1dF0gH2j
```

Update in 3 locations:

#### 1. Production `.env`
```diff
-HEALTHCHECK_TOKEN=PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0=
+HEALTHCHECK_TOKEN=<NEW_TOKEN_HERE>
```

#### 2. Staging `.env`
```diff
-HEALTHCHECK_TOKEN=PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0=
+HEALTHCHECK_TOKEN=<NEW_TOKEN_HERE>
```

#### 3. GitHub Actions Secrets
```bash
gh secret set HEALTHCHECK_TOKEN --body "<NEW_TOKEN_HERE>" --repo <your-repo>
```

---

## Verification Commands

### Test 1: Bearer Token Fix
```bash
# Should succeed with NEW token
curl -H "Authorization: Bearer <NEW_TOKEN>" \
  https://staging.askproai.de/healthcheck.php

# Should fail with OLD token
curl -H "Authorization: Bearer PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0=" \
  https://staging.askproai.de/healthcheck.php
```

### Test 2: Session Fixation Fix
```bash
# Use browser dev tools (F12):
# 1. Open: Application > Cookies
# 2. Note PHPSESSID before login (e.g., abc123)
# 3. Login with credentials
# 4. Check PHPSESSID after login (should be different, e.g., xyz789)
```

### Test 3: Automated Test Suite
```bash
cd /var/www/api-gateway
./tests/security/test-pr723-fixes.sh https://staging.askproai.de
```

---

## Summary of Changes

| Issue | Severity | File | Lines Changed | Impact |
|-------|----------|------|---------------|--------|
| Hardcoded Bearer Token | P0 | `public/healthcheck.php` | ~40 lines | Token now reads from .env |
| Session Fixation | P1 | `DocsAuthController.php` | +2 lines | Session regenerates on login |
| Rate Limiting | P2 | `routes/web.php` | +1 line | Brute force protection |

**Total**: 3 files, ~43 lines changed

---

## Risk Assessment

### Before Fixes
- **Bearer Token**: Exposed in Git history, no rotation possible
- **Session Fixation**: Exploitable by attacker with session ID control
- **Overall Risk**: HIGH

### After Fixes
- **Bearer Token**: Environment-specific, rotatable, secure
- **Session Fixation**: Mitigated via session regeneration
- **Overall Risk**: LOW

---

**Generated**: 2025-11-02
**Review Status**: Ready for deployment
**Testing**: Automated test suite provided
