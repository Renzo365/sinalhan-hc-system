# Database Migrations (Changelog Archive)

This directory contains historical migration scripts used during development and version milestones.

## Important Note for New Installations / Other PCs
> **You DO NOT need to run or import any files from this folder when setting up a new PC or server.**
>
> All tables, columns, indexes, and constraints from these migrations have already been permanently consolidated into:
> - **[`database/complete_setup.sql`](../complete_setup.sql)** (Schema + Default Seed Data in one file)
> - **[`database/schema.sql`](../schema.sql)** (Full DDL Schema)
> - **[`database/seed.sql`](../seed.sql)** (Initial Admin & Staff Accounts)
> - **[`setup_db.bat`](../../setup_db.bat)** (1-Click automated database installer)

---

## Migration History
- `2026_09_02_ihp_maternal_wellbaby_schema.sql`: Added tables for Prenatal care, Well-Baby care, Past Obstetric matrix, and child growth logs.
- `2026_09_02_phase6_integration.sql`: Performance indexes and status triggers.
- `2026_09_06_ihp_annex_a1_alignment.sql`: Added `physical_examination` and `external_immunizations` JSON fields to `patient_medical_histories`, and aligned `civil_status` and `education_attainment` ENUM options with PhilHealth Annex A1.
