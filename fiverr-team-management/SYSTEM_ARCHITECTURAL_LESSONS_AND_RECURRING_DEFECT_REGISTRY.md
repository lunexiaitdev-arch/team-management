# FB International Team Management System
## Architectural Knowledge Base, Recurring Defect Registry & Engineering Playbook

> **Target Audience:** Future AI Agents, Senior Software Engineers, and System Architects.  
> **Repository:** `d:\Team Management FBINT\fiverr-team-management`  
> **Production Server:** `82.180.141.215:65002`  
> **Core Stack:** WordPress Custom Plugin, Vanilla JS (ES6+), jQuery, REST API, MySQL / MariaDB, Custom CSS Engine.  
> **Document Purpose:** This document records all recurring architecture flaws, root causes, permanent solutions, and hard engineering rules discovered across multiple production cycles. Any agent working on this repository **MUST** study this registry before making changes.

---

## 1. System Architecture & Fundamental Contracts

### A. The Single Source of Truth Rule
- **The Defect:** Earlier implementations maintained user roles and teams in two disparate places: WordPress `wp_usermeta` (`ftm_team`, `ftm_role`, `ftm_custom_id`) and custom tables (`wp_ftm_team_members`). Updates in one location left the other stale ("Split-Brain" syndrome), causing a member to appear in `WpCraft` on one screen and `WpTitans` on another.
- **The Rule:**
  - `wp_usermeta` is the **Canonical Source of Truth** for current live user state (`ftm_team`, `ftm_role`, `ftm_custom_id`, `ftm_custom_avatar`, `ftm_call_sign`).
  - Custom tables (`wp_ftm_team_members`) are secondary indexes updated synchronously in the same transaction or query execution.
  - Controllers must **never** fall back to static hardcoded PHP arrays when queries return null. If data does not exist in the database, return clean empty responses (`[]`), never fictitious mock arrays.

### B. ID Identity Contract: WP User ID vs. Custom Badge ID
- **WordPress User ID (`ID` / `user_id`):** Primary database key (`BIGINT(20) UNSIGNED`). Used for all relational joins, foreign keys, assignment tables (`wp_ftm_project_assignees`), and REST route permissions.
- **Agency Custom ID (`custom_id`):** Display badge (e.g., `#1086`, `#1027`, `#1069`). Used **strictly** for UI presentation and search filtering.
- **The Trap:** Never execute `WHERE user_id = %d` using a value passed from a badge input (e.g. `1086`). If the WP user ID is `12` and their badge is `#1086`, querying `user_id = 1086` yields zero rows or queries a non-existent user.

### C. Financial Formula Contract
$$\text{Net Revenue} = \text{Gross Order Value} \times 0.80$$
- Fiverr deducts an unconditional $20\%$ platform commission.
- Addons and tips are calculated dynamically. Tips are not subject to standard delivery milestones but contribute to gross and net realization.
- Cancelled orders ($0\%$ realization) and Partial refunds must be deducted strictly according to financial settlement records.

---

## 2. Category 1: Frontend & Viewport/Mobile Failures

### Defect 1.1: Desktop-Centric Bias & `white-space: nowrap` Text Blowout
- **Symptom:** On mobile screens ($\le 400\text{px}$), the dashboard notice banner broke: the notice title pushed horizontally to over $600\text{px}$, and the "View Notice $\rightarrow$" button floated directly on top of the text (`media_1792898106280_751637a1.png`).
- **Root Cause:** Developers designed components on 1920x1080 desktop monitors and added inline `white-space: nowrap;` without mobile media queries.
- **Permanent Solution:**
  1. Never use `white-space: nowrap;` on variable user text (titles, descriptions, notices). Always apply `word-break: break-word; overflow-wrap: break-word;`.
  2. Implement mobile-first container wrapping: on screens $\le 768\text{px}$, banner items must stack vertically (`flex-direction: column; align-items: flex-start;`) and action buttons must become full-width pills.

### Defect 1.2: Rigid Multi-Column Grids on Mobile Viewports
- **Symptom:** In the Executive Daily Briefing modal, 5 KPI boxes ("Staff Count", "Submissions", "Total Work Hours", "Tasks Finished", "Blockers") were forced into a single line (`grid-template-columns: repeat(5, 1fr)`). On a 360px mobile screen, each column had only $\sim 60\text{px}$, causing letters in "TOTAL WORK HOURS" to stack into unreadable single-character columns (`media_1792898103927_3a82ffb2.png`).
- **Permanent Solution:**
  - Wrap all KPI grids in responsive breakpoints:
    ```css
    .ftm-exec-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 12px;
    }
    @media (max-width: 768px) {
        .ftm-exec-kpi-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }
        .ftm-exec-kpi-grid > div:last-child:nth-child(odd) {
            grid-column: span 2;
        }
    }
    ```

### Defect 1.3: Modal Action Bars Overcrowding
- **Symptom:** Modal headers crammed badge pill, document title, "Copy Text" button, "Print / Save PDF" button, and the close icon onto a single flex row. On mobile, button text wrapped across 3-4 lines and collided with the close icon.
- **Permanent Solution:**
  - In mobile media queries ($\le 768\text{px}$), modal headers must use column layout:
    - Top row: Badge and title on the left; close icon (`&times;`) pinned via `position: absolute; top: 12px; right: 14px;`.
    - Bottom row: Action buttons stretch to $50\%$ width each (`flex: 1 1 50%;`) side-by-side with clean padding and no text wrapping.

### Defect 1.4: Table Horizontal Overflow vs. Fluid Mobile Cards
- **Symptom:** Wide financial tables (15 columns in Monthly Sheet) broke viewport boundaries and created horizontal scroll lag on smartphones.
- **Permanent Solution:**
  - Under `max-width: 768px`, hide `table.ftm-monthly-table` (`display: none !important;`) and dynamically render `.ftm-monthly-mobile-cards` where each specialist is rendered as a clean, self-contained card showing Delivery, WIP, Ratings, and Status.

### Defect 1.5: Password Visibility Eye Toggle Failure
- **Symptom:** On the login page, clicking the eye icon failed to reveal the password or caused page jumps.
- **Root Cause:** Event listener was bound to the SVG path instead of the button, and did not prevent default behavior, causing focus loss.
- **Permanent Solution:** Bind directly to button container with `.on('click', function(e) { e.preventDefault(); const input = $('#password'); input.attr('type', input.attr('type') === 'password' ? 'text' : 'password'); });`.

---

## 3. Category 2: Print & PDF Architecture Failures

### Defect 2.1: The 2-Page Blank Whitespace Problem
- **Symptom:** Printing the Monthly Report statement generated 2 completely blank pages before displaying table contents on Page 3 and Page 4 (`media_1792850500122_d132ef98.png`).
- **Root Cause:**
  - `@media print` hid `#ftm-view-monthly-reports` but failed to hide `.ftm-main-container`, `.ftm-content-container`, `.ftm-main-workspace`, and WordPress theme wrappers (`.wp-block-template-part`, `header`, `footer`, `.wp-site-blocks`).
  - These wrapper elements preserved `min-height: 100vh !important;`, forcing the browser's pagination engine to allocate 2 full physical pages for invisible web layout boxes before `#ftm-print-stage` was reached.
- **Permanent Solution:**
  ```css
  @media print {
      body > *:not(#ftm-app),
      header, footer, nav,
      .wp-block-template-part,
      .wp-site-blocks > *:not(#ftm-app),
      .entry-content > *:not(#ftm-app),
      #ftm-app > :not(#ftm-print-stage),
      .ftm-header, .ftm-sidebar,
      .ftm-main-container, .ftm-main-workspace,
      .ftm-content-container, .ftm-view-panel,
      .ftm-modal-backdrop, .ftm-sheet-backdrop {
          display: none !important;
          visibility: hidden !important;
          height: 0 !important;
          min-height: 0 !important;
          margin: 0 !important;
          padding: 0 !important;
          overflow: hidden !important;
      }
      #ftm-print-stage {
          display: block !important;
          position: static !important;
          width: 100% !important;
          margin: 0 !important;
          page-break-before: avoid !important;
          break-before: avoid !important;
      }
  }
  ```

### Defect 2.2: Mobile Android Chrome 100% Blank PDF
- **Symptom:** Clicking "Print / Save PDF" in mobile Chrome opened the Android print preview, but displayed a completely empty white canvas (`1/1`, `media_1792898105110_5f3b2bf3.png`).
- **Root Cause (Triple Convergence):**
  1. `templates/dashboard.php` applied `overflow-x: clip !important;` globally to `html, body`. In Chromium's mobile PDF rasterizer, `overflow-x: clip` or `overflow: hidden` on root nodes signals that the page has zero scrollable/printable height, collapsing the canvas to blank.
  2. `#ftm-print-stage` was declared with `aria-hidden="true"`, causing mobile accessibility and print trees to discard its DOM children.
  3. Calling `window.print()` while a `position: fixed` modal backdrop was active caused mobile Chrome to capture the backdrop or clip the canvas.
- **Permanent Solution (Dedicated Clean Document Engine):**
  - Scoped `overflow-x: clip !important;` strictly inside `@media screen { ... }`.
  - In `assets/js/frontend.js`, created `printReportDocument(contentHtml, docTitle, orientation)`:
    - If user agent is mobile (`/Android|iPhone|iPad/i` or `width <= 768px`), open a dedicated clean window/tab via `window.open('', '_blank')` containing **only** the pure HTML5 document, clean print CSS, and an auto-triggering `window.print()` script.
    - Zero fixed modal overlays, zero WordPress wrappers, zero clipping. Guaranteed 100% clean PDF generation across all devices.

---

## 4. Category 3: Database & State Synchronization Failures

### Defect 3.1: Inconsistency Between Project Modal & Daily Standup Page
- **Symptom:** Submitting a daily progress update inside a project wiped out the update previously submitted on the Daily Standup page, and vice versa.
- **Root Cause:**
  - Two parallel controllers existed: the Project Controller wrote to `wp_ftm_projects.latest_progress`, while the Daily Standup Controller wrote to `wp_ftm_daily_reports` using `REPLACE INTO`.
  - Submitting from the project modal did not populate `wp_ftm_daily_report_items`. Submitting from the standup page replaced the entire day's record without checking existing logged items.
- **Permanent Solution:**
  - Unified the persistence pipeline in `FTM_Daily_Report_Controller`.
  - When saving progress from either entry point:
    1. Check for an existing daily report row for the user on that date.
    2. If found, **merge/append** the project task item (`UPDATE` with preserved item collection).
    3. If not found, create a new master report row.
    4. Synchronously update `latest_progress` on the project table.

### Defect 3.2: In-Memory Frontend Mutation & Missing Dropdown Options
- **Symptom:** Filtering the dashboard by `WpCraft` caused subsequent "Add Project" or "Broadcast Notice" modals to show only WpCraft specialists in their dropdowns, making other agency specialists invisible.
- **Root Cause:** In `frontend.js`, `this.state.teamMembers` was overwritten by filtered API responses instead of storing the master roster in an immutable state property.
- **Permanent Solution:**
  - Segregate state: `this.state.masterTeamMembers` (never filtered) vs. `this.state.filteredMembers`. Dropdowns always populate from `this.state.masterTeamMembers`.

---

## 5. Category 4: Business Rules & Historical Continuity (The 9-Month Ledger)

### The Single Source of Truth for Dec 2025 – Sep 2026
The following historical operational facts must **never** be altered, assumed, or overwritten by current live metadata:

1. **WpHorizon Team Formation (August 2026):**
   - Does **NOT** exist prior to August 2026.
   - Formed in August 2026 under a separate manager.
   - **Tahrima Billal Biva (#1027):** Team Leader from August 2026. Prior to August, she was in `WpTitans`.
   - **Nasim Uddin (#1069):** Co-Leader from August 2026. Prior to August, he was in `WpTitans`.
   - **Abu Sayeed (#1052)** and **Habibur Rahman Romel (#1049):** Migrated to Horizon in August.
2. **WpNexus Merger (August 2026):**
   - Prior to August 2026, Nexus was an independent unit. In August 2026, operational reorganization consolidated teams into Craft, Nexus, Titans, and the newly launched Horizon.
3. **Md. Saidur Rahman (#1086) Migration:**
   - January 2026 – July 2026: **`WpTitans`**.
   - August 2026 – Present: **`WpCraft`**.
4. **Departed Specialists (Former Status):**
   - **Sahariar Alam Redwan (#1078)** and **Bijoya Sarkar (#1088)** departed the agency.
   - Their historical delivery ($Delivered, WIP, Stars) in past months must remain intact in historical archives, but they must be excluded or designated as Former in current live operational rosters.
5. **Manager Operational Exemption:**
   - Super Admin/Manager has **no personal Fiverr order deliverables**.
   - Manager does **NOT** log daily standups.
   - Manager interface replaces "+ Log Daily Progress" with "+ Broadcast Notice" (`#ftm-btn-broadcast-notice`).
   - Manager controls the **Agency Notice Board** to broadcast operational directives targeted to:
     - All Agency Members (`all`)
     - Specific Team (`WpCraft`, `WpNexus`, `WpTitans`, `WpHorizon`)
     - Specific Specialist (`member_custom_id` / `user_id`)

---

## 6. Category 5: Encoding, Sanitization & Text Formatting Failures

### Defect 5.1: HTML Entity Apostrophe Leaking (`&#039;`)
- **Symptom:** Submitting `"Review Ariel's next round of corrections"` rendered on the manager review screen as `"Review Ariel&#039;s next round of corrections"`.
- **Root Cause:** WordPress `sanitize_text_field()` or `esc_html()` converted single quotes into `&#039;`. When returned via REST JSON without decoding, frontend jQuery/text nodes rendered the literal entity string.
- **Permanent Solution:**
  - In all REST controllers (`class-ftm-notices-controller.php`, `class-ftm-daily-report-controller.php`, etc.), wrap text outputs in:
    ```php
    html_entity_decode(wp_unslash($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')
    ```
  - In `frontend.js`, avoid double escaping when injecting sanitized server text into HTML templates.

---

## 6. Category 6: User Identity, Profile Synchronization & Client-Side Error Extraction

### Defect 6.1: Profile Setup HTTP 400 & REST Client Error Swallowing
- **Symptom:** When specialist/leader Zam zam Nishan (`zamzam_nishan`, User ID `9`) opened the "Edit Professional Profile" modal and updated their official email to `zamzamnishan15@gmail.com`, saving failed with a red toast: `Error saving profile: HTTP 400` (`media_1792899370206_754138ca.png`).
- **Root Cause (Two-Part):**
  1. **User Identity Collision:** The default WordPress initial administrator (`admin`, User ID `1`) had been registered with `zamzamnishan15@gmail.com`. When Zam zam Nishan attempted to claim their personal email on their dedicated member account (`zamzam_nishan`, User ID `9`), `email_exists($new_email)` detected User `1` and failed the check `(int)$existing_user_id !== $target_user_id`, returning a 400 Bad Request error.
  2. **Fetch Client Promise Error Swallowing:** In `assets/js/frontend.js`:
     ```javascript
     return res.json().then(err => Promise.reject(err)).catch(() => Promise.reject(new Error('HTTP ' + res.status)));
     ```
     When `res.json()` parsed the server error response `{ success: false, message: "..." }`, the `.then(err => Promise.reject(err))` rejected with the error object. However, the immediately chained `.catch(...)` intercepted that rejection and unconditionally replaced it with `new Error('HTTP ' + res.status)`. This completely obscured the server's diagnostic error message from the UI toast.
- **Permanent Solution:**
  1. In `class-ftm-legacy-controller.php` `update_user_profile()`: If `email_exists()` detects a collision with the default system admin (User `1`, `admin`), automatically migrate User `1` to `admin@agencyfbinternational.com` so the legitimate staff member can claim their email without collision.
  2. In `assets/js/frontend.js` `api()`: Preserve the parsed error object and status code across promise chains so server messages (`err.message`) are displayed cleanly to users:
     ```javascript
     return res.json().then(err => {
         const errMsg = (err && (err.message || err.error || err.code)) || ('HTTP ' + res.status);
         const error = new Error(errMsg);
         error.data = err;
         error.status = res.status;
         return Promise.reject(error);
     }).catch(e => {
         if (e && e.status) return Promise.reject(e);
         return Promise.reject(new Error('HTTP ' + res.status));
     });
     ```
  3. Ensure `update_user_profile()` returns the updated `email` and `email_locked` boolean in the REST response.

---

## 7. Category 7: UI Guardrails: Preventing Double Plus Sign (`+ +`) & Icon Redundancy

### Defect 7.1: Double Plus Sign Collision on Action Buttons (`+ + Action`)
- **Symptom:** Buttons rendered with duplicate plus signs (e.g., `+ + Post Notice` or `+ + Add Project`) as captured in `media_1792901051198_d9c8f5ea.png`.
- **Root Cause:** A button contains an SVG plus icon (`<svg><line x1="12" y1="5" x2="12" y2="19"></line>...`) while the `<span>` label simultaneously hardcodes a literal `+` character (e.g. `<span>+ Post Notice</span>`), causing browser UI to display `+ + Post Notice`.
- **Permanent Solution:**
  1. Never prepend a literal `+` inside button text if an SVG plus icon is present. Always write `<span>Post Notice</span>`, `<span>New Project</span>`, `<span>Add Project</span>`, `<span>Log Daily Progress</span>`.
  2. Single Source of Truth for Icons / Symbols: Icons and prefix symbols must be handled either strictly via SVG icon markup OR via text—never combine both.
  3. Before committing UI changes, grep/search all button definitions for `<span>\+` or `>\+` and verify zero double-plus or double-symbol occurrences.

---

## 8. Mandatory 12-Gate Engineering Checklist for Future Agents

Before declaring any task complete or release-verified, any future agent working on this repository **MUST** prove all of the following 12 gates:

```
[ ] 1. ZERO ASSUMPTIONS ON TEAMS:
       Did you verify real team assignments against the 9-month historical records?
[ ] 2. VIEWPORT VERIFICATION:
       Did you verify the layout on 360px mobile, 768px tablet, and 1440px desktop?
[ ] 3. MOBILE TEXT OVERFLOW:
       Are there any `white-space: nowrap;` properties on variable user text?
[ ] 4. MOBILE DRAWER GEOMETRY:
       Does the mobile sidebar start at top: 0 with 100dvh full height, brand header, logo, close button, and user card?
[ ] 5. UNIVERSAL MOBILE CARDS:
       Do data tables convert cleanly to native mobile cards on <= 768px viewports?
[ ] 6. PRINT & PDF OUTPUT:
       Does printing produce a clean, populated document (not a blank canvas or clipped viewport)?
[ ] 7. PERSISTENCE SYNC:
       Does submitting progress from the project view sync with the daily standup view?
[ ] 8. ID INTEGRITY:
       Are database queries using WP User ID (`BIGINT`), not Badge Custom ID (`#1086`)?
[ ] 9. ZERO ENTITY LEAKS:
       Do apostrophes (') render as plain text, not `&#039;`?
[ ] 10. ROLE SEPARATION:
       Is Daily Standup hidden for Super Admin/Manager, and Notice Broadcasting visible?
[ ] 11. ZERO DOUBLE-SYMBOL REDUNDANCY:
       Are all action buttons verified free of double plus (`+ +`) or duplicated icon prefixes?
[ ] 12. SYNTAX & ARTIFACT GATES:
       Did `php -l` and `node --check` exit with code 0, and did you verify the extracted zip on the production server via SSH/WP-CLI?
```

---
*Authored by Antigravity Senior Systems Engineer & DeepMind Agentic Pair Programmer.*  
*Maintained under strict Production-Critical Engineering Rules 1 & 15.*

