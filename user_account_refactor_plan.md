# User Account Management Analysis & Plan

Based on my inspection of the current implementation across `/profile`, `/users/create`, and `/users/edit`, here is my analysis of the inconsistencies, redundancies, and a proposed plan for updates.

## 1. Analysis of "Reset User Password" Redundancy

**Are they redundant?** 
No, they serve two entirely different and necessary purposes in a standard security workflow:

*   **Profile Page Password Update (Self-Service):** 
    This is for a logged-in user who *knows* their current password and wants to change it for security reasons. It requires them to input their `current_password` to authorize the change.
*   **Admin "Reset Password" Modal (Administrative Override):** 
    This is an IT/Support function used when a staff member *forgets* their password and cannot log in. The admin uses their own `admin_password` to authorize the action and issues a new "Temporary Password" to the staff member.

**Recommendation:** Keep both features exactly as they are. They are not redundant.

## 2. Inconsistencies Between Profile and Create/Edit Pages

Currently, the Profile page provides a "Read-Only Overview" of administrative fields (Employee ID, Department, Job Title), while the Create/Edit pages allow admins to set them. 

Since you want to remove unused fields, this naturally brings the pages closer in consistency.

### Fields to Remove:
*   **Employee ID / PRC License No.** (Remove from Create, Edit, and Profile overview, and Users List table)
*   **Department / Clinic Unit** (Remove from Create, Edit, and Profile overview, and Users List table)

### Fields to Keep:
*   **Job Title** (Keep input on Create/Edit, keep read-only display on Profile, keep column on Users List table)

## 3. "Current Status: Active" Display

Since the system now uses an **Archive (Soft-Delete)** model, the binary "Active/Inactive" status is obsolete. 
*   A user who can log in and view their Profile is, by definition, Active.
*   A user who is archived does not appear in the standard active user list and cannot log in.

**Recommendation:** 
*   **Profile Page:** Remove the "Status: Active" row entirely. It's redundant since only active users can view this page.
*   **Edit Page:** Replace the "Current Status: Active" card with an **"Account Status"** card that checks if `deleted_at` is set. If `deleted_at` is null, it shows "Active". If it is set, it shows "Archived" (in case you ever implement a feature to view archived users).

## 4. Execution Plan

If you approve, I will make the following changes across the system:

1.  **`app/Views/profile/index.php` (Profile Page):**
    *   Remove "Employee ID", "Department", and "Status" from the read-only overview list.
    *   Keep "Job Title".
2.  **`app/Views/users/create.php` (Create User):**
    *   Remove the inputs for "Employee ID" and "Department".
    *   Adjust the grid layout so "Job Title" fits nicely in the Professional Details section.
3.  **`app/Views/users/edit.php` (Edit User):**
    *   Remove the inputs for "Employee ID" and "Department".
    *   Update the "Current Status" card to reflect the `deleted_at` architecture instead of the hardcoded `status` field, or remove it if deemed unnecessary.
4.  **`app/Views/users/index.php` (Users List):**
    *   Remove "Employee ID" and "Department" from the DataTables columns and the search filter placeholder.
5.  **`app/Controllers/UserController.php` (Backend):**
    *   Remove `employee_id` and `department` from the validated fields in `store` and `update` methods.

---
**Ready to proceed?** Just let me know and I will implement these changes.
