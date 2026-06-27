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

---

## 4. Brute Force & Lockout Safeguards

To prevent brute force dictionary attacks on login credentials:
1. The `users` table tracks failed login attempts using the `failed_attempts` column.
2. A failed login attempt increments the count.
3. If an account logs **5 consecutive failed attempts**, the system automatically transitions the user account status to `suspended` and writes an audit log.
4. A suspended account is blocked from login attempts, even if they input the correct password.
5. Successful login resets the `failed_attempts` counter back to `0`.
6. Only an administrator can unlock the account by editing the user's status back to `Active` in the User Management dashboard.

---

## 5. File & Backup Isolation

* Database backups are exported directly to `storage/backups/` under the project directory.
* Access to the backup downloads endpoint is restricted strictly to logged-in administrator accounts.
* Filenames utilize date-stamped naming conventions to prevent path traversal downloads.
