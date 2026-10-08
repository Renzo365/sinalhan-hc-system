# SinalHealth Project Instructions

## Project

SinalHealth is a web-based patient management system for Barangay Sinalhan Santa Rosa Health Center.

## Technology

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap 5
- jQuery
- DataTables
- XAMPP for local development

## Important Compatibility

The existing project uses PHP 5.5.11.

Do not use PHP syntax introduced after PHP 5.5 unless the project is explicitly migrated to a newer PHP version.

## Architecture

Before changing a feature, understand how the frontend, JavaScript, PHP/backend, and database work together.

## Data

Do not invent database fields.

Inspect the actual database schema before making database-related changes.

## Records

The system includes:

- Patients
- Health Records
- Individual Health Profile
- Prenatal Records
- Well-Baby Records
- Consultations
- Vital Signs
- Immunizations
- Appointments
- Queue Management
- User Accounts

## Data Deletion

Prefer archive/soft-delete behavior where the existing system uses it.

Do not permanently delete records unless explicitly required.

## UI

Keep the interface:

- Simple
- Clean
- Low-clutter
- Consistent
- Easy for health center staff to understand

Avoid unnecessary buttons, icons, badges, and duplicate controls.

## Development Workflow

For complex tasks:

1. Inspect first.
2. Understand the existing implementation.
3. Identify the affected files.
4. Make a plan.
5. Implement the smallest appropriate change.
6. Test the result.
7. Check for regressions.