# Multi-Agent AI Development Workflow & Skill Architecture
### Barangay Sinalhan Health Center Patient Management System

---

## 1. Overview

This document defines the standardized **Multi-Agent AI Engineering Workflow** for the Sinalhan Health Center system. It establishes specialized AI roles, execution tracks, human approval checkpoints, failure classification loops, and model tier/reasoning guidelines.

Instead of an uncoordinated single-agent approach or an over-engineered 10-agent pipeline, the system uses a **Lean 4-Role Squad** managed by the **Orchestrator** (Antigravity AI).

```text
User Request ──► Orchestrator ──► [Fast / Standard / Deep Track]
                       │
         ┌─────────────┴─────────────┐
         ▼                           ▼
[Fast Track: Bugfix/Tweak]  [Standard / Deep: Feature/Schema]
         │                           │
         │                           ▼
         │                 sinalhan_architect (Plan)
         │                           │
         │                           ▼
         │                🛑 HUMAN APPROVAL GATE (If Schema/RBAC)
         │                           │
         └─────────────┬─────────────┘
                       ▼
             sinalhan_coder (Implement)
                       │
                       ▼
             sinalhan_qa (Verify & Security Audit)
                       │
           ┌───────────┴───────────┐
      Pass │                       │ Fail
           ▼                       ▼
   sinalhan_documentor      [CLASSIFY FAILURE]
           │                - Code Bug  ──► Back to Coder
           ▼                - Design    ──► Back to Architect
    TASK COMPLETE
```

---

## 2. Specialized Roles & Model Tier Specifications

| Role Name | Subagent Identifier | Primary Responsibility | Model Tier | Reasoning Effort | Write Tools |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Orchestrator** | *Antigravity Main* | Triage, Task Dispatching, Human Gatekeeper, Failure Categorization | `pro` / `inherit` | `medium` | Enabled |
| **System Architect** | `sinalhan_architect` | Requirements Analysis, Blast Radius Mapping, Database Schema Spec, Implementation Plan | `pro` | `high` | **Read-Only** |
| **Full-Stack Coder** | `sinalhan_coder` | Precision PHP 8.2+ MVC implementation, PDO prepared statements, CSRF, UI layouts | `pro` / `flash` | `medium` | Enabled |
| **QA & Security Auditor** | `sinalhan_qa` | Standalone CLI assertion tests (`scratch/test_*.php`), edge cases, SQLi/XSS/CSRF/RBAC audits | `pro` | `high` | Enabled |
| **Documentor & Sync** | `sinalhan_documentor` | Updating `CODEX.md`, `docs/database.md`, `docs/features.md`, generating `walkthrough.md` | `flash` | `low` | Enabled |

---

## 3. Dynamic Execution Tracks

The Orchestrator dynamically classifies every incoming request into one of three tracks:

### Track 1: Fast Track (Localized Fixes & UI Tweaks)
- **Applicable For:** Simple bug fixes, layout adjustments, typo fixes, CSS styling, localized helper adjustments.
- **Workflow:** `Orchestrator ──► Coder ──► QA Check ──► Documentor`
- **Speed:** Instant turnaround (1-2 minutes). Skips deep architectural overhead.

### Track 2: Standard Track (Single-Module Features & Minor Query Changes)
- **Applicable For:** Creating a new report page, adding a non-breaking column to a table, adding an export format, creating a new modal view.
- **Workflow:** `Architect (Plan) ──► Coder (Build) ──► QA (Test) ──► Documentor (Sync)`
- **Speed:** 3-5 minutes with automated verification.

### Track 3: Deep Track (Multi-Module Features, Schema Overhauls, Auth/RBAC)
- **Applicable For:** Modifying authentication/session logic, adding new clinical modules (e.g. Pharmacy/Lab), structural database refactors, changing user permissions.
- **Workflow:** `Architect (Deep Plan) ──► 🛑 HUMAN APPROVAL GATE ──► Coder ──► QA & Security Audit ──► Documentor`
- **Safety:** Halts execution and requires human confirmation before touching critical data structures.

---

## 4. Human Approval Gates (Mandatory Pause Points)

The workflow **must stop and prompt the user for confirmation** under these conditions:

1. **Database Schema Alterations:** Running `ALTER TABLE`, adding/dropping columns, changing data types, or modifying foreign key rules.
2. **Destructive SQL Operations:** Any bulk update or delete query affecting existing records.
3. **Authentication & Authorization Changes:** Modifying `AuthMiddleware`, `AdminMiddleware`, role privileges (`admin` vs `staff`), session timeouts, or password hashing algorithms.
4. **Clinical Calculation Rules:** Modifying Naegele's rule (AOG/EDC), BMI formula, abnormal vital sign alert thresholds, or DOH report registries.
5. **Feature Deletion or Deprecation:** Removing any existing route, button, or workflow.
6. **Ambiguous Requirements:** When requirements conflict or could be implemented in multiple fundamentally different ways.

---

## 5. Feedback Loops & Failure Routing Protocol

When `sinalhan_qa` detects a test failure:

1. **Category A: Code Bug (`[CODE_BUG]`)**
   - *Examples:* Syntax error, undefined variable, missing array key, broken CSS selector, CSRF token omitted.
   - *Routing:* Sent directly back to `sinalhan_coder` with the exact file, line number, and stack trace.
   - *Re-testing:* Only the failing test is re-executed.

2. **Category B: Design Defect (`[DESIGN_DEFECT]`)**
   - *Examples:* Foreign key constraint violation, impossible relational query, business logic contradiction.
   - *Routing:* Sent back to `sinalhan_architect` to update the plan and schema specification. If database changes are required, triggers the Human Approval Gate.

3. **Circuit Breaker Rule:**
   - If any single task fails QA **3 consecutive times**, all automated attempts halt immediately. The Orchestrator summarizes the failure and escalates to the user with actionable diagnostic details.

---

## 6. Context-Sharing & Working Memory Strategy

- **Artifacts as Working Memory:**
  - `implementation_plan.md`: Created by Architect; read by Coder and QA.
  - `walkthrough.md`: Created by Documentor; read by User.
  - `scratch/test_<feature>.php`: Created and run by QA.
- **Reference Anchoring:**
  - Agents cite specific documentation line numbers (e.g. `docs/database.md#L40-L80`) rather than dumping full file text into context.
- **Scope Fences:**
  - Every agent must adhere to negative constraints: *Do not touch unrelated files, do not invent undocumented dependencies, do not delete tests.*
