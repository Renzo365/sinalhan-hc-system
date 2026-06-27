# UI Wireframes - Barangay Sinalhan Health Center Patient Management System

> Low-fidelity wireframes for the capstone system. These wireframes define screen structure, navigation, and workflow before detailed UI implementation.

---

## 1. Wireframe Goals

The wireframes should help guide development of the Bootstrap 5 interface. They focus on:

- Main layout and navigation
- Staff workflow
- Patient lookup and record handling
- Appointment, queue, vital signs, and consultation flow
- Admin-only pages such as archived records, audit logs, and backup
- Privacy-aware display of patient information

These are low-fidelity wireframes. Colors, spacing, icons, and final visual styling will follow `CODEX.md`.

---

## 2. Shared App Layout

Most authenticated pages should use the same layout.

```text
+--------------------------------------------------------------------------------+
| Topbar: Page Title / Search / Logged-in User / Logout                           |
+----------------------+---------------------------------------------------------+
| Sidebar              | Main Content Area                                       |
|                      |                                                         |
| Dashboard            | Breadcrumbs / Page Header                               |
| Patients             |                                                         |
| Appointments         | Page-specific filters, actions, forms, tables, cards    |
| Queue                |                                                         |
| Vital Signs          |                                                         |
| Consultations        |                                                         |
| Reports              |                                                         |
| Archive (Admin)      |                                                         |
| Audit Logs (Admin)   |                                                         |
| Backup (Admin)       |                                                         |
| Settings (Admin)     |                                                         |
+----------------------+---------------------------------------------------------+
```

Layout notes:

- Sidebar should remain stable across pages.
- Admin-only items should be hidden from staff users.
- The topbar may include a quick patient search if implementation time allows.
- Main content should prioritize tables, forms, and readable clinical information.

---

## 3. Login Page

```text
+--------------------------------------------------------------------------------+
| Login Gradient Background                                                       |
|                                                                                |
|                         +--------------------------------+                     |
|                         | Health Center Name             |                     |
|                         | Patient Management System      |                     |
|                         |--------------------------------|                     |
|                         | Username                       |                     |
|                         | [____________________________] |                     |
|                         | Password                       |                     |
|                         | [____________________________] |                     |
|                         |                                |                     |
|                         | [ Sign In ]                    |                     |
|                         |                                |                     |
|                         | Error / validation message     |                     |
|                         +--------------------------------+                     |
|                                                                                |
+--------------------------------------------------------------------------------+
```

Key behavior:

- Show validation errors clearly.
- Do not expose whether username or password is specifically wrong.
- Redirect logged-in users to dashboard.

---

## 4. Dashboard

```text
+--------------------------------------------------------------------------------+
| Dashboard                                                    Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Dashboard                                               |
|                      |                                                         |
|                      | +------------+ +------------+ +------------+ +----------+ |
|                      | | Patients   | | Today Appt | | Queue Now  | | Visits   | |
|                      | | 1,245      | | 12         | | 8          | | 31       | |
|                      | +------------+ +------------+ +------------+ +----------+ |
|                      |                                                         |
|                      | +----------------------------+ +------------------------+ |
|                      | | Today's Appointments       | | Queue Summary          | |
|                      | | Table/List                 | | Chart/List             | |
|                      | +----------------------------+ +------------------------+ |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Recent Activity / Recent Consultations                | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Dashboard notes:

- Keep the dashboard operational, not decorative.
- Staff should quickly see today's appointments, queue count, and patient activity.
- Admin may see additional summary cards such as user activity or backups.

---

## 5. Patient List and Search

```text
+--------------------------------------------------------------------------------+
| Patients                                                     Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Patients                                                |
|                      |                                                         |
|                      | [ Search patient name, number, contact, barangay ]       |
|                      | [Barangay v] [Sex v] [Age Group v] [ Search ] [ + New ] |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Patient No | Name | Age/Sex | Barangay | Contact | ...| |
|                      | |------------|------|---------|----------|---------|----| |
|                      | | P-000001   | ...  | ...     | ...      | ...     |View| |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | Pagination / DataTables controls                         |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Search should help prevent duplicate patient registration.
- Show possible matches before creating a new patient.
- Use DataTables for sorting, filtering, and pagination if practical.

---

## 6. Patient Registration Form

```text
+--------------------------------------------------------------------------------+
| New Patient                                                  Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Register Patient                                        |
|                      |                                                         |
|                      | Personal Information                                    |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | First Name     | | Middle Name    | | Last Name     | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Date of Birth  | | Sex            | | Civil Status  | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      |                                                         |
|                      | Contact and Address                                      |
|                      | +----------------+ +---------------------------------+ |
|                      | | Contact No.    | | Address                         | |
|                      | +----------------+ +---------------------------------+ |
|                      |                                                         |
|                      | Emergency / Other Details                                |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Emergency Name | | Emergency No.  | | PhilHealth No.| |
|                      | +----------------+ +----------------+ +---------------+ |
|                      |                                                         |
|                      | [ Cancel ]                              [ Save Patient ] |
+----------------------+---------------------------------------------------------+
```

Form notes:

- Group fields into readable sections.
- Preserve entered values after validation errors.
- Use clear required markers.
- Use date picker for date of birth.

---

## 7. Patient Profile

```text
+--------------------------------------------------------------------------------+
| Patient Profile                                               Staff Name| Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Patient Name                          [ Edit Patient ]   |
|                      | Patient No. | Age | Sex | Barangay | Contact             |
|                      |                                                         |
|                      | +----------------------+ +----------------------------+ |
|                      | | Patient Details      | | Quick Actions              | |
|                      | | DOB, Address, etc.   | | + Appointment             | |
|                      | | Emergency Contact    | | + Queue Entry             | |
|                      | +----------------------+ | + Vital Signs             | |
|                      |                          | + Consultation            | |
|                      |                          +----------------------------+ |
|                      |                                                         |
|                      | Tabs: [Vitals] [Consultations] [Appointments] [Queue]    |
|                      | +------------------------------------------------------+ |
|                      | | Selected tab table/history                            | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Patient identity should stay visible.
- Clinical history should be easy to scan.
- Quick actions should support the main daily workflow.

---

## 8. Appointment List

```text
+--------------------------------------------------------------------------------+
| Appointments                                                 Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Appointments                                            |
|                      |                                                         |
|                      | [Date From] [Date To] [Status v] [ Search ] [ + New ]   |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Date | Time | Patient | Purpose | Status | Actions    | |
|                      | |------|------|---------|---------|--------|------------| |
|                      | | ...  | ...  | ...     | ...     | Badge  | View/Edit  | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Status values:

- Scheduled
- Completed
- Cancelled
- Missed

---

## 9. Appointment Form

```text
+--------------------------------------------------------------------------------+
| New Appointment                                             Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Schedule Appointment                                    |
|                      |                                                         |
|                      | Patient                                                 |
|                      | [ Search or select patient ]                             |
|                      |                                                         |
|                      | Appointment Details                                      |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Date           | | Time           | | Status        | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Purpose                                                   |
|                      | +--------------------------------------------------------+ |
|                      | | Notes                                                     |
|                      | +--------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Cancel ]                           [ Save Appointment ] |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Use Flatpickr for date/time fields.
- Validate required patient, date, time, and purpose.
- Warn if the selected schedule conflicts with an existing appointment when possible.

---

## 10. Queue Management

```text
+--------------------------------------------------------------------------------+
| Queue                                                        Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Queue - Today                                           |
|                      |                                                         |
|                      | [ Search/select patient ] [ + Add to Queue ]             |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Queue No. | Patient | Status | Time In | Actions      | |
|                      | |-----------|---------|--------|---------|--------------| |
|                      | | 001       | ...     | Waiting| 08:01   | Call/Serve   | |
|                      | | 002       | ...     | Called | 08:05   | Complete     | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Open Queue Display ]                                  |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Queue numbers reset per day.
- Status changes should be confirmed where appropriate.
- Queue actions should be quick and easy to use.

---

## 11. Public Queue Display

```text
+--------------------------------------------------------------------------------+
| Barangay Sinalhan Health Center                                                 |
| Queue Display                                                                   |
|                                                                                |
|                NOW SERVING                                                      |
|                                                                                |
|                  004                                                           |
|                                                                                |
| +----------------------------+ +----------------------------+                  |
| | Waiting                    | | Recently Called            |                  |
| | 005                        | | 003                        |                  |
| | 006                        | | 002                        |                  |
| | 007                        | |                            |                  |
| +----------------------------+ +----------------------------+                  |
+--------------------------------------------------------------------------------+
```

Privacy note:

- Show queue numbers only unless the health center approves showing patient initials or names.

---

## 12. Vital Signs Form

```text
+--------------------------------------------------------------------------------+
| Vital Signs                                                  Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Record Vital Signs                                      |
|                      | Patient: Name | Patient No. | Age | Sex                 |
|                      |                                                         |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | BP Systolic    | | BP Diastolic   | | Heart Rate    | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Respiratory    | | Temperature    | | Oxygen Sat.   | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Weight kg      | | Height cm      | | BMI auto      | |
|                      | +----------------+ +----------------+ +---------------+ |
|                      | | Notes                                                     |
|                      | +--------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Cancel ]                            [ Save Vital Signs ]|
+----------------------+---------------------------------------------------------+
```

Key behavior:

- BMI may be auto-calculated if weight and height are provided.
- Important abnormal values may be highlighted carefully.
- Patient identity must be visible while recording vitals.

---

## 13. Consultation Form

```text
+--------------------------------------------------------------------------------+
| Consultation                                                Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | New Consultation                                        |
|                      | Patient: Name | Patient No. | Age | Sex                 |
|                      |                                                         |
|                      | [ Link latest vital signs v ]                            |
|                      |                                                         |
|                      | Subjective                                               |
|                      | +------------------------------------------------------+ |
|                      | | Chief complaint / history                              | |
|                      | +------------------------------------------------------+ |
|                      | Objective                                                |
|                      | +------------------------------------------------------+ |
|                      | | Findings / observations                                | |
|                      | +------------------------------------------------------+ |
|                      | Assessment                                               |
|                      | +------------------------------------------------------+ |
|                      | | Diagnosis or clinical impression                       | |
|                      | +------------------------------------------------------+ |
|                      | Plan                                                     |
|                      | +------------------------------------------------------+ |
|                      | | Treatment plan / advice / follow-up                    | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Cancel ]                            [ Save Consultation]|
+----------------------+---------------------------------------------------------+
```

Form notes:

- Use SOAP-style sections.
- Keep patient identity visible.
- Preserve form data after validation errors.

---

## 14. Reports Page

```text
+--------------------------------------------------------------------------------+
| Reports                                                      Staff Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Reports                                                 |
|                      |                                                         |
|                      | Report Type                                             |
|                      | [ Daily Visits v ] [Date From] [Date To] [ Generate ]   |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Summary Cards / Chart                                 | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Report Results Table                                  | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Print ] [ Export PDF ] [ Export CSV ]                 |
+----------------------+---------------------------------------------------------+
```

Initial report types:

- Daily patient visits
- Consultation summary
- Patient registration summary
- Queue summary
- Vital signs record report

---

## 15. Archived Records

```text
+--------------------------------------------------------------------------------+
| Archived Records                                           Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Archived Patient Records                                |
|                      |                                                         |
|                      | [ Search name or patient no. ] [Date From] [Date To]    |
|                      | [Archived By v] [ Search ]                              |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Patient No | Name | Archived Date | By | Reason | ... | |
|                      | |------------|------|---------------|----|--------|-----| |
|                      | | P-000001   | ...  | YYYY-MM-DD    | ...| ...    |View | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | Selected archived patient                               |
|                      | +------------------------------------------------------+ |
|                      | | Patient summary and archive reason                    | |
|                      | | [ View Details ] [ Restore Record ]                   | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Key behavior:

- Archived records are hidden from normal patient lists.
- Archive page is admin-focused.
- Restore action should require confirmation.
- Permanent delete should not be available in the initial capstone scope.

---

## 16. Audit Logs

```text
+--------------------------------------------------------------------------------+
| Audit Logs                                                   Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Audit Logs                                              |
|                      |                                                         |
|                      | [Date From] [Date To] [User v] [Action v] [ Search ]    |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Date/Time | User | Action | Module | IP | Details     | |
|                      | |-----------|------|--------|--------|----|-------------| |
|                      | | ...       | ...  | ...    | ...    | ...| View        | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Access note:

- Audit logs are admin-only.
- Logs should be filterable and readable, but not editable.

---

## 17. Backup Page

```text
+--------------------------------------------------------------------------------+
| Backup                                                       Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | Database Backup                                         |
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Last Backup: YYYY-MM-DD HH:MM                        | |
|                      | | Backup Location: storage/backups                     | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | [ Create Backup ]                                      |
|                      |                                                         |
|                      | Backup History                                          |
|                      | +------------------------------------------------------+ |
|                      | | Filename | Date Created | Size | Created By | Action  | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Safety notes:

- Backup is admin-only.
- Backup creation should require confirmation.
- Full restore can remain a future enhancement.

---

## 18. User Management Page

```text
+--------------------------------------------------------------------------------+
| User Management                                              Admin Name | Logout |
+----------------------+---------------------------------------------------------+
| Sidebar              | User Accounts Management                  [ + Add User ]|
|                      |                                                         |
|                      | +------------------------------------------------------+ |
|                      | | Username | Name | Role | Status | Last Login | Action  | |
|                      | |----------|------|------|--------|------------|---------| |
|                      | | admin    | ...  | Admin| Active | YYYY-MM-DD | [Edit]  | |
|                      | | doctor1  | ...  | Doctor| Active| YYYY-MM-DD | [Edit]  | |
|                      | +------------------------------------------------------+ |
|                      |                                                         |
|                      | Add / Edit User Modal                                   |
|                      | +------------------------------------------------------+ |
|                      | | Username: [               ] Role: [ Staff    v ]      | |
|                      | | First Name: [             ] Last Name: [           ]  | |
|                      | | Email: [                  ]                           | |
|                      | | [x] Force password reset on next login                | |
|                      | |                                                       | |
|                      | |                      [ Cancel ] [ Save Account ]      | |
|                      | +------------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

Access control:
- Access is restricted strictly to Administrator role.
- Activity logging: creation, updates, password resets, and role modifications must generate audit trails.
- Automatic account lockout: accounts are locked or suspended after 5 failed login attempts.

---

## 19. Suggested User Workflow

```text
Login
  |
  v
Dashboard
  |
  +--> Search/Register Patient
          |
          v
      Patient Profile
          |
          +--> Schedule Appointment
          |
          +--> Add to Queue
          |
          +--> Record Vital Signs
          |
          +--> Create Consultation
          |
          +--> Generate/Print Related Records

Admin
  |
  +--> View Archived Records
  |       |
  |       +--> View Archived Patient Details
  |       |
  |       +--> Restore Record
  |
  +--> User Management
          |
          +--> Add/Edit Users & Reset Passwords
```

This workflow should guide the main navigation and quick action buttons.
