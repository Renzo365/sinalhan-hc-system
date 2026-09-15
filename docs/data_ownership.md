# Data Ownership and Workflow Boundaries

This document defines which module is authoritative for each category of health-center data.
The goal is to prevent the same clinical fact from being maintained independently in multiple
tables or screens.

## Authoritative records

| Data category | Authoritative table/module | Notes |
| --- | --- | --- |
| Patient identity and demographics | `patients` / Patient Registry | Names, date of birth, sex, address, family number, contact, and PhilHealth details belong to the master patient record. |
| General medical history | `patient_medical_histories` / IHP | Historical conditions, surgery, family history, social history, baseline information, physical examination, and external immunization declarations belong here. |
| Current pregnancy | `prenatal_records` / Prenatal | Each row is a pregnancy episode. Only one episode should be active for a patient at a time. |
| Prior pregnancy and delivery history | `past_obstetric_histories` / Prenatal | Each row represents one prior pregnancy or delivery entry. |
| Prenatal follow-up | `prenatal_visits` / Prenatal | Repeated observations belong to the pregnancy episode, not the permanent patient profile. |
| Child birth record | `wellbaby_records` / Well Baby | Birth circumstances and newborn screening belong to the child record and may reference the mother patient. |
| Child growth monitoring | `child_growth_logs` / Well Baby | Each row is one dated growth-monitoring encounter. |
| Vaccine administration or documented receipt | `immunizations` / Immunization History | This is the authoritative event ledger for vaccine name, dose, date, source, and documentation status. |
| Vaccines mentioned during a growth visit | `child_growth_logs.vaccines_administered` | Encounter narrative only. It must not be used as the vaccine history source for reports or dose completion. |
| General measurements | `vital_signs` / Vital Signs | A dated set of measurements that may optionally be linked to a consultation. |
| Clinical encounter narrative | `consultations` / Consultation | SOAP documentation and consultation status belong here. |
| Planned service | `appointments` / Appointments | An appointment is a plan for a future service, not proof that the patient arrived or was consulted. |
| Same-day service order | `queue_entries` / Queue | A queue entry represents arrival and operational order for a date. Walk-ins are valid. |
| PhilHealth PCB obligations | `pcb_obligated_services` and `pcb_service_logs` / PCB | Annual obligations and service encounters are maintained separately from general consultations. |
| Audit history | `audit_logs` / Audit | Sensitive actions and security events are recorded independently of clinical tables. |

## Workflow relationships

- An appointment may result in a queue entry, but a queue entry may also be created for a walk-in.
- A queue entry may result in a consultation, but not every queued service is a consultation.
- A consultation may reference vital signs, but vital signs can be recorded independently.
- Prenatal and well-baby records are program-specific clinical records and should not duplicate the patient master record.
- The child is an independent patient. `wellbaby_records.mother_patient_id` is a relationship, not a replacement for the child record.

## Data entry rules

1. Use the authoritative module when creating or correcting a fact.
2. Do not copy a value into another table merely to make a summary display easier.
3. Keep program-specific notes when they describe the encounter, but do not treat those notes as structured history.
4. Use explicit `Unknown`, `Not assessed`, or `Not applicable` values when a paper record does not provide a fact.
5. Completed clinical records should not be silently overwritten or permanently deleted.

## Immunization provenance

The `immunizations` table distinguishes:

- `source = Health Center`: administered by this facility.
- `source = External`: documented as administered by another facility.
- `source = Patient Reported`: reported by the patient or parent without a facility administration record.
- `source = Unknown`: source cannot be established.

`documentation_status` distinguishes whether the event is recorded as administered, reported, or unknown.
Historical IHP entries may remain in `patient_medical_histories.external_immunizations` when exact event
details are unavailable; they should not be silently converted into precise immunization events.
