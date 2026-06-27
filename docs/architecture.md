# System Architecture Guide - Barangay Sinalhan Health Center

This document outlines the software architecture, class organization, request lifecycle, and local network (LAN) deployment architecture for the **Patient Management System** of the Barangay Sinalhan Health Center.

---

## 1. Architectural Overview

The system is built on a custom, lightweight **Model-View-Controller (MVC)** framework written in PHP 8.2+. It is designed specifically to run inside a local area network (LAN) using a local XAMPP stack on a Windows machine.

### Key Architectural Constraints
* **Framework-Free**: Avoids complex external frameworks (like Laravel or Symfony) to prevent deployment and compilation overhead on LAN workstations, making it easier to audit and defend during the capstone review.
* **Zero CDN Dependency**: All styling, script assets, and libraries are stored locally in the public folder to ensure uninterrupted operation during internet outages.
* **Thin Controllers / Thick Models**: Business and database validations, auto-number generation, and direct SQL parameter binding are placed in Models, keeping Controllers focused on request routing and output delegation.

---

## 2. Request-Response Lifecycle

The request flow utilizes a centralized **Front Controller** pattern where all client browser requests are processed through a single entry point:

```mermaid
sequenceDiagram
    actor Client as Web Browser (LAN Client)
    participant Index as public/index.php (Front Controller)
    participant Router as App\Core\Router
    participant Auth as App\Middleware (Auth/Admin/Guest)
    participant Controller as App\Controllers (UserController, PatientController, etc.)
    participant Model as App\Models (User, Patient, etc.)
    participant View as App\Views (HTML/PHP templates)
    participant DB as MySQL Database

    Client->>Index: HTTP Request (e.g. GET /patients)
    activate Index
    Note over Index: Set timezone, secure cookie params, start session,<br/>generate CSRF tokens, define global escape h()
    Index->>Router: Dispatch Request URL and Method
    activate Router
    Router->>Router: Match URL route pattern
    Router->>Auth: Check Middleware filters (is logged in? is admin?)
    activate Auth
    alt Auth Fails
        Auth-->>Client: HTTP Redirect (302) to /login
    else Auth Success
        Auth->>Controller: Invoke controller action (e.g. index())
        deactivate Auth
        activate Controller
        Controller->>Model: Query database data
        activate Model
        Model->>DB: execute SQL query with prepared parameters
        DB-->>Model: Return rows
        Model-->>Controller: Return records/objects
        deactivate Model
        Controller->>View: Load view template (extract variables)
        activate View
        View-->>Controller: Compile HTML output
        deactivate View
        Controller-->>Client: Send HTTP Response (200 OK HTML/JSON)
        deactivate Controller
    end
    deactivate Router
    deactivate Index
```

---

## 3. Core Classes & Helpers

The custom MVC engine is located inside the `app/Core/` directory.

### 3.1 Autoloader (`app/Core/Autoloader.php`)
Resolves PHP classes dynamically by mapping namespaces directly to folder directories using PSR-4 standards:
* Namespace `App\` maps to the `app/` folder.
* Classes are loaded on demand, avoiding slow global requires.

### 3.2 Database Wrapper (`app/Core/Database.php`)
Implemented as a thread-safe **Singleton Pattern** that wraps the PDO instance:
* `Database::getInstance()` retrieves the active singleton wrapper.
* `.getConnection()` exposes the unified PDO channel configured with strict attributes:
  * `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (throws exceptions for SQL errors).
  * `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (returns associative arrays).
  * `PDO::ATTR_EMULATE_PREPARES => false` (forces native MySQL prepared statements).

### 3.3 Core Router (`app/Core/Router.php`)
Maintains a routing dictionary registering request verbs (`GET`, `POST`) and URL patterns using regular expressions.
* Handles wildcards (e.g. `/patients/{id}` converts to `/patients/(\d+)`).
* Captures route parameters and passes them dynamically to controller action parameters.

### 3.4 Base Controller (`app/Core/Controller.php`)
Provides shared utility functions for sub-controllers:
* `view($name, $data)`: Extracts variables and requires view template files securely.
* `redirect($url)`: Prepends base folder subdirectories dynamically.
* `json($data, $statusCode)`: Formats and outputs HTTP JSON payloads.

---

## 4. Middleware & Hooks

Operational security and route protection are executed through middleware checks triggered by the Router during dispatch:

1. **GuestMiddleware**: Restricts access to `/login` for users who are already logged in, redirecting them straight to the `/dashboard`.
2. **AuthMiddleware**: Intercepts active checks to verify if `$_SESSION['user_id']` exists. If not, it redirects the request to `/login` with an warning message.
3. **AdminMiddleware**: Validates that `$_SESSION['user_role'] === 'admin'`. If standard staff members attempt to access admin routes (like `/users`, `/backup`, `/audit-logs`, `/archive/patients`), they are returned to `/dashboard` with an unauthorized alert.
4. **Password Reset Interceptor**: Executed during authentication check. If a user's record has `must_change_password = 1`, all requests (except `/change-password` and `/logout`) redirect them immediately to the `/change-password` template to secure their account.

---

## 5. Local Area Network (LAN) Deployment Architecture

The application runs inside a local area network to prevent internet dependency:

```text
                                  +---------------------------------------+
                                  |         Windows Local Server          |
                                  |               (XAMPP)                 |
                                  |  [Apache 2.4]  <--->  [MySQL 8.0]     |
                                  |  IP: 192.168.1.100                    |
                                  +-------------------+-------------------+
                                                      |
                             +------------------------+------------------------+
                             | LAN Router / Switch                             |
                             +------------------------+------------------------+
                                                      |
             +----------------------------------------+----------------------------------------+
             |                                        |                                        |
             v                                        v                                        v
+-------------------------+              +-------------------------+              +-------------------------+
|    Records Workstation  |              |    Nurse Workstation    |              |   Doctor Workstation    |
|   Client (192.168.1.11) |              |  Client (192.168.1.12)  |              |  Client (192.168.1.13)  |
|   Browser -> Patients   |              |   Browser -> Vitals     |              |  Browser -> Consults    |
+-------------------------+              +-------------------------+              +-------------------------+
```

### 5.1 Apache Virtual Host Configuration (`httpd-vhosts.conf`)
To host the system locally so other LAN workstations can connect, the host machine binds the project public folder as follows:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/sinalhan-hc-system/public"
    ServerName sinalhan-hc.local
    
    <Directory "C:/xampp/htdocs/sinalhan-hc-system/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 5.2 Server Address Binding (`httpd.conf`)
Ensure Apache listens on all network interfaces:
```apache
Listen 80
```
LAN workstations access the system using the server host's local IP (e.g., `http://192.168.1.100/sinalhan-hc-system/public` or custom local hosts DNS names mapped in the router/clients hosts files).
