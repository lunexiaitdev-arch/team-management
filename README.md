# FB International - Team Management Platform

Enterprise Operations, Deliverable Tracking, Time Tracking & Revenue Reconciliation Platform for FB International.

---

## 📁 Repository Structure

```
team-management/
├── fiverr-team-management/    # WordPress / Backend & Web Operations Hub Plugin
│   ├── assets/                # Web Dashboard styles (CSS), scripts (JS), branding images
│   ├── includes/              # REST Controllers, Permissions, Financial Engine, Auth
│   ├── templates/             # Dashboard and Authentication layouts
│   └── fiverr-team-management.php # Core plugin entry point
│
└── flutter_team_app/          # Cross-Platform Mobile Application (Flutter / Dart)
    ├── android/               # Android native configuration & Gradle scripts
    ├── ios/                   # iOS native workspace
    ├── lib/                   # Flutter source (screens, providers, models, services, widgets)
    ├── test/                  # Automated integration & unit tests
    └── pubspec.yaml           # Flutter dependencies & app metadata
```

---

## 🌟 Key Features

### 1. WordPress Plugin (`fiverr-team-management`)
- **Executive & Leadership Dashboards**: Real-time project tracking, pipeline monitoring, and financial reconciliation.
- **Strict Role-Based Access Control**:
  - `Manager / Executive` $\rightarrow$ Complete agency-wide oversight.
  - `Team Leader / Co-Leader` $\rightarrow$ Scoped strictly to their dedicated squad members and deliverables.
  - `Specialists / Members` $\rightarrow$ View only their assigned tasks and personal performance.
- **Time Tracker & Proof-of-Work**: High-frequency activity tracking, mouse/keyboard activity logging, and snapshot retention.
- **Dynamic Reconciliation**: Real-time alignment between monthly income sheets, active WIP, submitted deliverables, and tip attribution.
- **Daily Standups & Reports**: Dedicated daily logging for accomplishments, blockers, date-specific extensions, and manager feedback.

### 2. Flutter Mobile Application (`flutter_team_app`)
- **Native Android & iOS Support**: Built with Flutter 3.x and Dart.
- **Executive Command Hub**: Total Workload overview, active squads breakdown, and one-tap month filtering.
- **Smart Back Navigation**: `PopScope`-backed tab history prevents accidental application exits and provides seamless nested navigation.
- **Live Time Tracker Sync**: Monitor ongoing timers, review timesheets, and log shifts on the go.
- **Real-Time Push Notifications**: Firebase Cloud Messaging (FCM) integration for order deadlines, manager comments, and revision alerts.

---

## 🚀 Setup & Development

### Web Plugin Setup
1. Clone or copy `fiverr-team-management` into your WordPress installation's `wp-content/plugins/` directory.
2. Activate the plugin via WP Admin or WP-CLI (`wp plugin activate fiverr-team-management`).
3. Ensure the database migrations and seeders execute cleanly.

### Flutter App Setup
1. Navigate to `flutter_team_app/`:
   ```bash
   cd flutter_team_app
   ```
2. Install dependencies:
   ```bash
   flutter pub get
   ```
3. Run on a connected device:
   ```bash
   flutter run
   ```
4. Build release APK:
   ```bash
   flutter build apk --release
   ```

---

## 🔒 Security & Data Privacy
- Leader-to-member data isolation ensures no cross-squad data leaks.
- All REST endpoints strictly authenticate credentials via WordPress REST Nonces and Bearer tokens.
