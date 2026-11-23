# FB International Team Management System (`GEMINI.md`)
## Architectural Knowledge Base, Recurring Defect Registry & Engineering Playbook

> **Target Audience:** Antigravity AI Agents, Senior Software Engineers, and System Architects.  
> **Repository:** `d:\Team Management FBINT\fiverr-team-management`  
> **Production Server:** `82.180.141.215:65002`  
> **Core Stack:** WordPress Custom Plugin, Vanilla JS (ES6+), jQuery, REST API, MySQL / MariaDB, Custom CSS Engine.  
> **Mandate:** Any agent working on this repository MUST study and enforce every rule in this registry before writing or modifying any code.

---

## 1. System Architecture & Fundamental Contracts

### A. The Single Source of Truth Rule
- `wp_usermeta` is the **Canonical Source of Truth** for current live user state (`ftm_team`, `ftm_role`, `ftm_custom_id`, `ftm_custom_avatar`, `ftm_call_sign`, `ftm_email_updated_once`).
- Custom tables (`wp_ftm_team_members`) are secondary indexes updated synchronously in the same transaction or execution flow.
- Controllers must **never** fall back to static hardcoded PHP arrays when queries return null. Always return clean empty responses (`[]`).

### B. ID Identity Contract: WP User ID vs. Custom Badge ID
- **WordPress User ID (`ID` / `user_id`):** Primary database key (`BIGINT(20) UNSIGNED`). Used for all relational joins, foreign keys, `wp_ftm_project_assignees`, and REST permission checks.
- **Agency Custom ID (`custom_id`):** Display badge (`#1086`, `#1027`, `#1069`). Used strictly for UI badges and search filtering. Never pass a badge value into `WHERE user_id = %d`.

### C. Financial Formula Contract
- `Net Revenue = Gross Order Value * 0.80` (20% unconditional Fiverr platform commission).
- Addons and tips are calculated dynamically. Tips contribute to gross and net realization without standard delivery milestone locks.
- Cancelled orders (0% realization) and partial refunds must be deducted strictly according to settlement records.

---

## 2. Category 1: Frontend, Mobile Drawer & Table-to-Card UI Standards

### Defect 1.1: Mobile Off-Canvas Sidebar Drawer Geometry (`<= 768px`)
- **Symptom:** The mobile sidebar started below the main top bar (`top: 60px`), lacked a dedicated close button or brand header, and left a massive dead whitespace gap above `Log Out`.
- **Permanent Solution:**
  1. Render the mobile sidebar as a true full-viewport off-canvas drawer: `position: fixed; top: 0; left: 0; bottom: 0; height: 100dvh; width: min(280px, 82vw); z-index: 9999; display: flex; flex-direction: column;`.
  2. **Drawer Header:** Include the FB International logo/title on the left and a dedicated `&times;` close button (`44x44px` tap target) on the right.
  3. **Scrollable Nav Body:** Set `flex: 1; overflow-y: auto; padding: 12px 16px;` on the navigation container so items never overflow or leave awkward gaps.
  4. **Pinned Drawer Footer:** Use `margin-top: auto; border-top: 1px solid #e2e8f0; padding: 14px 16px;` containing the logged-in member's mini profile summary and the `Log Out` button.
  5. Lock background scroll (`body.ftm-drawer-open { overflow: hidden !important; }`) when the drawer is open.

### Defect 1.2: Universal Mobile Table-to-Card Conversion (`<= 768px`)
- **Symptom:** While the Monthly Sheet converted to cards, **My Assigned Orders & Deliveries** and **All Projects** remained as `<table>` elements on mobile. This caused horizontal truncation (hiding Order Price, Deadline, Status, and Action buttons) and squished text into multi-line fragments (`web \n symbol`, `Code \n vers`).
- **Permanent Solution:**
  1. **Zero Tables on Mobile:** Under `@media (max-width: 768px)`, hide **ALL** data tables (`table.ftm-monthly-table, table.ftm-assigned-table, table.ftm-projects-table, .ftm-table-responsive table { display: none !important; }`).
  2. **Dedicated Mobile Cards:** Dynamically render `.ftm-mobile-card-list` for every tabular view on mobile:
     - **Assigned / Project Cards MUST include:**
       - Top row: Order ID badge (`#FO42...`) on the left + Status Pill (`Active`, `WIP`, `Completed`) on the right.
       - Middle row: Full Profile Name and Client Username (single line with `text-overflow: ellipsis`, never breaking short words vertically).
       - Metrics row: 2-column or 3-column mini grid showing **Order Value / Net**, **Deadline / Countdown**, and **Progress %**.
       - Footer row: Full-width action buttons (`Update Progress` / `View Details`).

### Defect 1.3: Monthly Member Card Flaws & Redundant Call Signs
- **Symptom:** Member cards rendered `F M Afzalul Bare` as the heading and `Call Sign: F M Afzalul Bare` directly underneath. Additionally, the team filter bar omitted `WpHorizon`.
- **Permanent Solution:**
  1. **Deduplicate Call Sign:** If `member.call_sign.trim().toLowerCase() === member.display_name.trim().toLowerCase()` (or if `call_sign` is empty), do **not** render the redundant `Call Sign:` line; render the member's role/specialty (`member.professional_title || member.role`) instead.
  2. **Inner Metric Box Geometry:** Style the 3-column metric box (`DELIVERY`, `ACTIVE WIP`, `TIPS`) with `display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; padding: 10px 12px;` and `font-variant-numeric: tabular-nums;` so larger dollar amounts (`$1,250.00`) never wrap or collide on `360px` screens.
  3. **Complete Team Filter Bar:** Ensure all active teams (`All Teams`, `WpTitans`, `WpNexus`, `WpCraft`, and `WpHorizon` for Aug 2026+) are rendered in the filter pill group.

### Defect 1.4: Desktop-Centric Bias & `white-space: nowrap` Text Blowout
- **Permanent Solution:**
  1. Never use `white-space: nowrap;` on variable user text (titles, descriptions, notices). Always apply `word-break: break-word; overflow-wrap: break-word;`.
  2. On screens `<= 768px`, notice banners must stack vertically (`flex-direction: column; align-items: flex-start;`) with full-width action buttons.

### Defect 1.5: Rigid Multi-Column KPI Grids & Modal Action Bars on Mobile
- **Permanent Solution:**
  1. Convert 5-column KPI grids (`.ftm-exec-kpi-grid`) to `grid-template-columns: repeat(2, 1fr) !important; gap: 8px !important;` under `@media (max-width: 768px)`, with `.ftm-exec-kpi-grid > div:last-child:nth-child(odd) { grid-column: span 2; }`.
  2. Stack modal headers into 2 rows on mobile: Title/Badge on top with close icon pinned (`position: absolute; top: 12px; right: 14px;`), and action buttons (`flex: 1 1 50%;`) side-by-side on the bottom row.
  3. Bind password visibility eye toggles directly to the `<button>` container with `e.preventDefault()`.

---

## 3. Category 2: Profile & One-Time Email Onboarding Failures (`HTTP 400`)

### Defect 2.1: One-Time Email Update Loop & Subsequent `HTTP 400` on Profile Save
- **Symptom:** Initially, members were prompted to update their email on first sign-in, but the prompt reappeared endlessly. After hiding the email prompt post-update, clicking "Save Changes" in the **Edit Professional Profile** modal failed with `Error saving profile: HTTP 400`.
- **Root Cause (Triple Convergence):**
  1. The REST route (`/profile/update`) or controller still enforced `email` as a required parameter (`'required' => true` or `if (empty($email)) return 400`), even though the frontend modal omitted/disabled the email input once `ftm_email_updated_once` was set.
  2. Using strict WP REST schema validators (`'format' => 'date'` on human-readable dates like `"01 Feb 2024"`, or `'format' => 'uri'` / `'format' => 'email'` on optional empty strings `""`) caused WordPress core `WP_REST_Server` to reject the request with `HTTP 400` before reaching the controller callback.
  3. Calling `wp_set_password()` when a user updated their password invalidated their current session cookie, causing subsequent AJAX/REST calls to fail.
- **Permanent Solution:**
  1. **Never set `'required' => true` or strict `'format' => 'date'|'uri'|'email'` in `register_rest_route` args** for profile update endpoints. Perform conditional validation inside the controller callback.
  2. **State Flag Contract:** Store `ftm_email_updated_once = 1` in `wp_usermeta` immediately after a member successfully updates their email on first login.
  3. **Optional Email Handling:** In the profile update controller, if `ftm_email_updated_once` is already `1` (or if `email` is omitted from the payload), skip email validation and update all other profile metadata (`display_name`, `ftm_call_sign`, `ftm_professional_title`, `ftm_joining_date`, `ftm_skills`, `ftm_bio`, `ftm_custom_avatar`) cleanly.
  4. **Self-Email Exemption:** When checking `email_exists($submitted_email)`, always verify `$owner_id && (int) $owner_id !== (int) $current_user_id` so saving the user's own email never triggers a duplicate error.
  5. **Session Preservation:** If `password` is non-empty, call `wp_set_password($new_password, $user_id)` followed immediately by `wp_set_auth_cookie($user_id, true)` so the user's REST nonce/session stays active.

---

## 4. Category 3: Print & PDF Architecture Failures (Fixing 100% Blank White PDF)

### Defect 3.1: Blank White PDF Preview / Multi-Page Blank Whitespace
- **Symptom:** Triggering "Print / Save PDF" or exporting Team Reports, Monthly Sheets, or Executive Briefings generates a 100% blank white canvas or multi-page blank whitespace.
- **Root Cause:** The browser's print engine captures WordPress theme wrappers, dashboard sidebars, modal backdrops, and root `overflow: hidden` / `overflow-x: clip` properties, which collapses the printable height to 0.
- **Permanent Solution:**
  1. **Dedicated Clean Document Engine (`printReportDocument`):** On mobile viewports (`<= 768px`) or mobile Chrome/Android, always generate printable documents using a dedicated clean window/tab via `window.open('', '_blank')` containing only pure HTML5 markup, clean inline print CSS, and an auto-triggering `window.print()` script. Zero fixed modal overlays, zero dashboard sidebars, and zero WordPress wrappers.
  2. **Strict `@media print` Wrapper Collapse:** Inside `@media print`, explicitly hide and collapse all layout wrappers (`body > *:not(#ftm-app)`, `header`, `footer`, `nav`, `.wp-block-template-part`, `.wp-site-blocks`, `.ftm-main-container`, `.ftm-main-workspace`, `.ftm-modal-backdrop`, etc.) using:
     ```css
     display: none !important;
     visibility: hidden !important;
     height: 0 !important;
     min-height: 0 !important;
     margin: 0 !important;
     padding: 0 !important;
     overflow: hidden !important;
     ```
  3. **Print Stage Isolation:** Ensure `#ftm-print-stage` is declared with `display: block !important; position: static !important; page-break-before: avoid !important; break-before: avoid !important;`. Never leave `aria-hidden="true"` on active print containers.
  4. **Scoped Root Overflow:** Never apply `overflow-x: clip !important;` or `overflow: hidden` globally to root `html, body` elements unless it is explicitly scoped inside `@media screen { ... }`.

---

## 5. Category 4: Database & State Synchronization Failures

### Defect 4.1: Project Modal vs. Daily Standup Persistence Sync
- **Permanent Solution:** Route all progress updates through `FTM_Daily_Report_Controller`. Check for an existing `wp_ftm_daily_reports` row for `(user_id, report_date)`. If found, merge/append the task item in `wp_ftm_daily_report_items` via `UPDATE` (never `REPLACE INTO`). Synchronously update `wp_ftm_projects.latest_progress`.

### Defect 4.2: Immutable Frontend Roster State
- **Permanent Solution:** Separate `this.state.masterTeamMembers` (never filtered, used for modal dropdowns) from `this.state.filteredMembers` (used for active view rendering).

---

## 6. Category 5: Business Rules & Historical Continuity (Dec 2025 – Sep 2026)

1. **WpHorizon Team Formation (August 2026):** Does NOT exist prior to August 2026. Formed in Aug 2026 with **Tahrima Billal Biva (`#1027`)** as Team Leader (previously `WpTitans`), **Nasim Uddin (`#1069`)** as Co-Leader (previously `WpTitans`), **Abu Sayeed (`#1052`)**, and **Habibur Rahman Romel (`#1049`)**.
2. **WpNexus Merger (August 2026):** Independent prior to Aug 2026; reorganized alongside Craft, Nexus, Titans, and Horizon in Aug 2026.
3. **Md. Saidur Rahman (`#1086`) Migration:** Assigned to **`WpTitans`** (Jan 2026 – Jul 2026) and **`WpCraft`** (Aug 2026 – Present).
4. **Departed Specialists (Former Status):** Preserve historical metrics for **Sahariar Alam Redwan (`#1078`)** and **Bijoya Sarkar (`#1088`)** in past monthly sheets, but exclude or mark as Former in live operational rosters.
5. **Manager Operational Exemption:** Super Admin/Manager has no personal Fiverr deliverables, never logs daily standups, and sees "+ Broadcast Notice" (`#ftm-btn-broadcast-notice`) instead of "+ Log Daily Progress".

---

## 7. Category 6: Encoding & Sanitization

- In all REST controllers, decode outgoing text strings using `html_entity_decode(wp_unslash($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')` so apostrophes never render as `&#039;`.

---

## 8. Category 7: UI Guardrails (Preventing Double Plus Sign `+ +`)

- **Single Source of Truth for Icons / Symbols:** Icons and prefix symbols (such as `+`, `→`, or SVG icons) must be handled **either** strictly in the HTML/PHP template **or** strictly in the dynamic JS rendering function—**never in both**.
- **Strict Prohibition of Hardcoded Symbols in HTML:** Never hardcode text symbols like `+` or `&plus;` inside button HTML labels if the button text is managed, modified, or generated via JavaScript/REST templates (e.g., write `<button>Post Notice</button>` instead of `<button>+ Post Notice</button>`).
- **CSS/SVG Preferred:** For action buttons, always use inline SVGs or CSS pseudo-elements (`::before`) for icons rather than literal text characters (`+`).
- **Pre-Completion Review:** Before committing UI changes, inspect all Add/Create/Post buttons (`+ Post Notice`, `+ Add Project`, `+ Log Daily Progress`) to ensure zero duplication of prefix icons or text.

---

## 9. Category 8: Print & PDF Generation Guardrails (Eliminating Blank Pages)

- **Never Hide Ancestor Wrappers with Generic `:not()`:**
  WordPress block themes (like Twenty Twenty-Five) wrap plugins inside `.wp-site-blocks`, `<main>`, and `.entry-content`.
  Using selectors like `body > *:not(#ftm-app)` or `.wp-site-blocks > *:not(#ftm-app)` in `@media print` will match and hide the WordPress wrapper itself (`display: none !important;`). Because descendants of a hidden ancestor cannot be rendered under CSS specifications, this causes the entire page (including `#ftm-print-stage`) to print as a 100% blank white page.
- **Explicit Ancestor Passthrough:**
  All ancestors (`html, body, .wp-site-blocks, main, .wp-block-group, .entry-content, #ftm-app`) must explicitly be styled as `display: block !important; position: static !important; overflow: visible !important; margin: 0 !important; padding: 0 !important; background: #fff !important;` in `@media print`.
- **Targeted Non-Print Suppression:**
  Only explicitly hide headers (`header.wp-block-template-part`), footers, admin bar (`#wpadminbar`), sidebars (`.ftm-sidebar`), navigation, and direct children of `#ftm-app` other than `#ftm-print-stage` (`#ftm-app > *:not(#ftm-print-stage)`).
- **Never Rely on jQuery `:visible` in `beforeprint`:**
  During `beforeprint`, browsers evaluate `@media print` rules, which set `.ftm-view-panel` to `display: none`. This causes jQuery's `$('#ftm-view-...').is(':visible')` to return `false`! Always check the active router state (`self.state.currentView === ...`) or DOM class (`.hasClass('active')`).
- **Support Direct View Printing in `beforeprint`:**
  Handle all view routes (Monthly Sheet, Team Daily Report, Executive Doc, Weekly Report) so direct `Ctrl+P` or browser Print dynamically populates `#ftm-print-stage` before the preview renders.
- **No Premature Stage Emptying in `afterprint`:**
  Chromium may re-render or scale print previews asynchronously. Delay any `#ftm-print-stage` cleanup rather than synchronously clearing it on `afterprint`.

---

## 10. Mandatory 13-Gate Engineering Checklist for Future Agents

Before declaring any task complete, verify all 13 gates:

1. **Zero Assumptions on Teams:** Historical team assignments verified against Dec 2025 – Sep 2026 ledger (WpHorizon strictly from Aug 2026).
2. **Responsive Layout:** Responsive layout verified across 360px, 768px, and 1440px breakpoints.
3. **No Dynamic Text Blowout:** Zero `white-space: nowrap;` on variable user text.
4. **Mobile Drawer Geometry:** Mobile sidebar starts at `top: 0`, has full-height 100dvh overlay, dedicated brand header, logo, close button (`&times;`), and user card.
5. **Universal Mobile Card Conversion:** Tables convert to cards on `<= 768px` viewports.
6. **Print & PDF Clean Output:** Desktop and mobile PDF print produce clean, populated documents with zero blank leading pages and zero wrapper suppression.
7. **Persistence Sync:** Project progress updates and daily standups merge cleanly without overwriting.
8. **Relational ID Integrity:** All SQL relational queries use `BIGINT` WP `user_id`, never `#custom_id` badges.
9. **Zero Entity Leaks:** Apostrophes render cleanly as `'` without `&#039;`.
10. **Role Separation:** Super Admin/Manager hides Daily Standup logging and shows Notice Broadcasting.
11. **Zero Double-Symbol Redundancy:** Zero double plus (`+ +`) or duplicate icon prefixes on action buttons.
12. **Print Ancestor Passthrough & State-Based `beforeprint`:** WordPress theme wrappers (`.wp-site-blocks`, `main`) pass through cleanly in print CSS, and `beforeprint` checks router state (`currentView`) rather than jQuery `:visible`.
13. **Syntax & Deployment Verification:** `php -l` and `node --check` exit with code 0, archive checksum matched, and remote deployment verified via SSH/WP-CLI.

Before declaring any task complete, verify all 12 gates:

1. **Zero Assumptions on Teams:** Historical team assignments verified against Dec 2025 – Sep 2026 ledger (WpHorizon strictly from Aug 2026).
2. **Responsive Layout:** Responsive layout verified across 360px, 768px, and 1440px breakpoints.
3. **No Dynamic Text Blowout:** Zero `white-space: nowrap;` on variable user text.
4. **Mobile Drawer Geometry:** Mobile sidebar starts at `top: 0`, has full-height 100dvh overlay, dedicated brand header, logo, close button (`&times;`), and user card.
5. **Universal Mobile Card Conversion:** Tables convert to cards on `<= 768px` viewports.
6. **Print & PDF Clean Output:** Desktop and mobile PDF print produce clean, populated documents with zero blank leading pages.
7. **Persistence Sync:** Project progress updates and daily standups merge cleanly without overwriting.
8. **Relational ID Integrity:** All SQL relational queries use `BIGINT` WP `user_id`, never `#custom_id` badges.
9. **Zero Entity Leaks:** Apostrophes render cleanly as `'` without `&#039;`.
10. **Role Separation:** Super Admin/Manager hides Daily Standup logging and shows Notice Broadcasting.
11. **Zero Double-Symbol Redundancy:** Zero double plus (`+ +`) or duplicate icon prefixes on action buttons.
12. **Syntax & Deployment Verification:** `php -l` and `node --check` exit with code 0, archive checksum matched, and remote deployment verified via SSH/WP-CLI.