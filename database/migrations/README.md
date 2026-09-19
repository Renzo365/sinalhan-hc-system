# Database Migrations (Changelog Archive)

This directory contains ordered migration scripts used to upgrade an existing installation.

## Important Note for New Installations / Other PCs
> **You DO NOT need to run or import any files from this folder when setting up a new PC or server.**
>
> All tables, columns, indexes, and constraints from these migrations have already been permanently consolidated into:
> - **[`database/complete_setup.sql`](../complete_setup.sql)** (Schema + Default Seed Data in one file)
> - **[`database/schema.sql`](../schema.sql)** (Full DDL Schema)
> - **[`database/seed.sql`](../seed.sql)** (Initial Admin & Staff Accounts)
> - **[`setup_db.bat`](../../setup_db.bat)** (1-Click automated database installer)

---

## Upgrade procedure

1. Confirm the application is stopped or in maintenance mode.
2. Create and verify a backup using the application's backup function or an external database dump.
3. Confirm the target database name with `SELECT DATABASE();` before running a migration.
4. Apply migration files in filename order, only through the current migration.
5. Check the client output for errors and verify the changed columns, indexes, and tables in `INFORMATION_SCHEMA`.
6. Run the application smoke tests before allowing staff to use the system.
7. For recovery testing, restore a copy of the backup into an isolated database and verify the schema, row counts, login, patient lookup, queue, and report workflows before replacing the live database.

Migrations must be applied to an existing database only. Do not run `schema.sql` or
`complete_setup.sql` as an upgrade: both scripts intentionally recreate tables and
can destroy existing patient data.

The current migration files are written to be repeatable where possible. A successful
partial migration should be rechecked rather than blindly rerun; the latest integrity
migration has guards for its columns and index.

## Migration History
- `2026_09_02_ihp_maternal_wellbaby_schema.sql`: Added tables for Prenatal care, Well-Baby care, Past Obstetric matrix, and child growth logs.
- `2026_09_02_phase6_integration.sql`: Added appointment program and queue service fields.
- `2026_09_06_ihp_annex_a1_alignment.sql`: Added `physical_examination` and `external_immunizations` JSON fields to `patient_medical_histories`, and aligned `civil_status` and `education_attainment` ENUM options with PhilHealth Annex A1.
- `2026_09_07_pcb_ledger_tables.sql`: Added PhilHealth PCB1 obligated preventive services and service encounter ledger tables.
- `2026_09_15_add_soft_deletes_to_clinical_tables.sql`: Added `deleted_at`, `deleted_by`, and `archive_reason` columns across clinical tables for legal audit compliance.
- `2026_09_15_normalize_ihp_medical_histories.sql`: Normalized IHP medical histories into relational tables (`patient_conditions`, `patient_surgeries`, `patient_external_immunizations`).
- `2026_09_15_record_integrity_and_immunization_provenance.sql`: Added provenance and documentation status to immunization events and an index for active prenatal episode checks.
- `2026_09_17_align_clinic_workflow.sql`: Added `envelope_no` to `patients` and `lineage` to `patient_conditions`.
- `2026_09_17_refactor_prenatal_records.sql`: Dropped misplaced legacy IHP columns (`pre_eclampsia`, `fp_counselling`) from `prenatal_records`.
- `2026_09_17_03_consolidate_schema_and_soft_deletes.sql`: **Universal Idempotent Catch-up Migration**: Brings any older database up-to-date with complete soft-delete tracking across all 13 clinical tables, physical envelope number, virtual generated column `active_patient_id` with unique active episode constraint, and `queue_daily_counters` table.

