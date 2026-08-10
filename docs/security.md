# Security Specifications Guide - Barangay Sinalhan Health Center

This document outlines the security architecture, data protection controls, and vulnerability defense mechanisms implemented in the **Patient Management System** of the Barangay Sinalhan Health Center.

---

## 1. Authentication & Session Management

### 1.1 Secure Password Hashing
Password credentials are never stored in plain text.
* The system utilizes PHP's native `password_hash()` with the **bcrypt algorithm** (`PASSWORD_BCRYPT`) during user registration and password updates.
* Validations during login are verified strictly using `password_verify()`.

### 1.2 Session Protection
User sessions are configured in [`public/index.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/public/index.php) and [`config/app.php`](file:///c:/xampp/htdocs/sinalhan-hc-system/config/app.php) with strong parameters to prevent session hijacking and session fixation attacks:
* **Session Cookie Lifetime**: 2 hours (7200 seconds) of inactivity before auto-timeout.
* **HttpOnly**: Session cookies are configured as `httponly => true` to block JavaScript access and prevent Cross-Site Scripting (XSS) cookie extraction.
* **Secure Cookie Flags**: Session cookies utilize `samesite => 'Lax'` configuration to mitigate Cross-Site Request Forgery (CSRF).
* **ID Regeneration**: The session ID is regenerated using `session_regenerate_id(true)` immediately upon login to invalidate old session identifiers.

### 1.3 Inactivity Auto-Logout & Session Expiration
To prevent unauthorized access on unattended clinic workstations, the system enforces strict client-side and server-side inactivity monitoring:
* **Idle Threshold**: 15 minutes (900 seconds) of inactivity.
* **Client-Side Event Listener**: JavaScript monitors user interaction events (`mousemove`, `mousedown`, `keydown`, `scroll`, `click`, `touchstart`). If no events occur for 15 minutes, the system smoothly redirects the browser to the login page (`/login?timeout=1`).
* **Server-Side Enforcement**: `AuthMiddleware` verifies `$_SESSION['last_activity']` on every HTTP request. If the threshold exceeds 15 minutes, the session is invalidated, a `SESSION_TIMEOUT` audit log is recorded, and the user is redirected to `/login`.
* **SweetAlert Notification**: Upon redirection to the login screen, a SweetAlert2 warning modal alerts the user: *"Session Expired: You've been logged out due to inactivity. Please sign in again."*

---

## 2. Role-Based Access Control (RBAC)

System access is divided into two distinct levels to enforce the **Principle of Least Privilege**:

| Feature / Directory | Admin Role | Staff Role |
|---|---|---|
| Patient Registration & Directories | Access allowed | Access allowed |
| Vitals & SOAP Consultations | Access allowed | Access allowed |
| Appointments & Daily Queue Board | Access allowed | Access allowed |
| User Accounts Management | Access allowed | **Access Blocked** (Redirects to Dashboard) |
| Audit Logs Explorer | Access allowed | **Access Blocked** (Redirects to Dashboard) |
| Database Backup Tool | Access allowed | **Access Blocked** (Redirects to Dashboard) |
| Archive Registry & Restore | Access allowed | **Access Blocked** (Redirects to Dashboard) |

Route guards are validated at the controller constructor level. If a staff user manually types the URL path of an administrator route (such as `/users` or `/backup`), they are blocked and redirected back to `/dashboard` with an authorization warning message.

### 2.1 Administrative Privilege Isolation
To prevent privilege escalation and account takeover vulnerabilities:
* **Primary Administrator (Main Admin) Authority**: User ID 1 (`admin`) is designated as the Main System Administrator and holds master authority over all administrator and staff accounts.
* **Co-Administrator Privilege Restrictions**: Co-Administrators (User ID > 1 with role `admin`) can manage standard `staff` accounts, but are strictly blocked from promoting staff to `admin`, creating admin accounts (automatically overridden to `staff`), or modifying, demoting, deactivating, or resetting the password of **any peer administrator account**. Any unauthorized attempt is blocked and logged as a `SECURITY_VIOLATION` event.
* **Self-Exclusion Prevention**: Administrators are blocked from demoting their own role or deactivating their own active accounts.
* **Account Deletion Safeguard**: Physical and soft deletion of user accounts is completely disabled to preserve clinical audit trails and log integrity. Access control is managed exclusively via `Active` and `Inactive` status toggles.
* **Re-authentication & Password Validation Requirement**: Resetting a user's password requires the logged-in administrator to enter their current password to authorize the change. The system validates that the new temporary password is not identical to the user's current password hash.

---

## 3. Vulnerability Defenses

### 3.1 SQL Injection Prevention
All data retrieval and state modification queries use parameterized SQL commands via **PDO Prepared Statements**:
* Emulated prepares are disabled (`PDO::ATTR_EMULATE_PREPARES => false`), forcing native prepared statements in MySQL.
* User inputs are never concatenated directly into SQL query strings.
* Search inputs utilize distinct, unique named placeholders (e.g., `:search_first`, `:search_last`) instead of repeated variables to prevent PDO parameter binding errors.

### 3.2 Cross-Site Scripting (XSS) Prevention
To prevent stored XSS attacks, output data rendered in views is passed through a global helper function `h()`:
```php
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
```
This sanitizes HTML tags and special characters (like `<`, `>`, `"`, `'`) before outputting them to the client browser.

### 3.3 Cross-Site Request Forgery (CSRF) Prevention
All data-modifying HTTP POST operations (forms and AJAX calls) are validated against CSRF:
* A cryptographically secure CSRF token is generated using `bin2hex(random_bytes(32))` and stored in the user's session when initiated.
* All forms render a hidden CSRF token input field using `<?= csrf_field() ?>`.
* The Front Controller (`public/index.php`) intercepts all incoming `POST` requests and validates that the submitted `csrf_token` exactly matches the session-stored token. If the token is invalid, missing, or mismatched, the request is immediately terminated with a `403 Forbidden` response.

### 3.4 Dual-Layer Input Validation & Data Sanitization
To prevent invalid data injection, malformed records, and data corruption:
* **Name Protection**: All name inputs (`first_name`, `middle_name`, `last_name`, `emergency_name`) are sanitized via `trim()` and `strip_tags()`, and validated against regex `/^[a-zA-ZñÑ\s\-\'\.]{2,50}$/u`. Client-side JavaScript blocks numeric keypresses in real-time.
* **Philippine Mobile Phone Standard**: Phone inputs (`contact_no`, `emergency_no`) strictly enforce the **11-digit `09XXXXXXXXX` format** (`/^09\d{9}$/`). JavaScript strips non-numeric characters and enforces an 11-digit maximum length.
* **Date Bounds Enforcement**: Date of birth (`dob`) is parsed as a valid `Y-m-d` calendar date, strictly enforcing `1900-01-01 <= dob <= Today`. Future dates or impossible birth years are rejected.
* **PhilHealth Auto-Formatting Mask**: PhilHealth IDs strictly enforce the 12-digit format `XX-XXXXXXXXX-X` (`/^\d{2}-\d{9}-\d{1}$/`). Client-side JavaScript auto-formats hyphens as the user types.
* **Strict Whitelist Verification**: Dropdown fields (`sex`, `civil_status`, `blood_type`) use strict `in_array()` whitelist verification in PHP controllers before database interaction.

---

## 4. Brute Force & Temporary Lockout Safeguards

To prevent brute force dictionary attacks on login credentials without creating account lockout vulnerabilities:
1. The `users` table tracks failed login attempts using `failed_attempts` and `last_failed_login_at`.
2. Each failed login attempt increments `failed_attempts` and updates `last_failed_login_at = NOW()`.
3. **Automatic Window Reset**: If an active user attempts a login more than **15 minutes** (900 seconds) after their previous failure, the counter automatically resets back to `1`.
4. **15-Minute Temporary Lockout**: Upon reaching **5 consecutive failed attempts**, the user is placed in a **15-minute temporary lockout window**. Account status remains `active`, but authentication is strictly blocked during the 15 minutes, displaying remaining minutes and seconds.
5. **Automatic Expiration**: Once the 15-minute cooldown expires, the user is automatically permitted to attempt logging in again.
6. **Administrative Override Button**: An administrator can instantly override a staff member's 15-minute lockout by clicking the **Clear Lockout** action button (`bi-unlock-fill`) in the User Accounts directory (`/users`). This resets `failed_attempts` to `0` and logs a `USER_LOCKOUT_RESET` audit event.
7. **CLI Unlock Utility**: Administrators can also run `php scripts/unlock_user.php [username]` from the server console to clear lockouts.
8. **Successful Login Reset**: A successful login resets `failed_attempts` to `0` and clears `last_failed_login_at` to `NULL`.

---

## 5. File & Backup Isolation

* Database backups are exported directly to `storage/backups/` under the project directory.
* Access to the backup downloads endpoint is restricted strictly to logged-in administrator accounts.
* Filenames utilize date-stamped naming conventions to prevent path traversal downloads.
