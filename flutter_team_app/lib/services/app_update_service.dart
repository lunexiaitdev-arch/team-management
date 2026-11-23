import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:package_info_plus/package_info_plus.dart';
import 'package:ota_update/ota_update.dart';
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_colors.dart';

class AppUpdateInfo {
  final bool hasUpdate;
  final String currentVersion;
  final String latestVersion;
  final String releaseNotes;
  final String? apkDownloadUrl;

  AppUpdateInfo({
    required this.hasUpdate,
    required this.currentVersion,
    required this.latestVersion,
    required this.releaseNotes,
    this.apkDownloadUrl,
  });
}

class AppUpdateService {
  static const String repoOwner = 'lunexiaitdev-arch';
  static const String repoName = 'team-app';

  static const String latestReleaseUrl =
      'https://api.github.com/repos/$repoOwner/$repoName/releases/latest';

  static bool isDialogShowing = false;

  /// Check server first (fast & reliable for team APK), then fallback to GitHub
  static Future<AppUpdateInfo?> checkForUpdate() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();
      final currentVersion = packageInfo.version;
      final currentBuild = packageInfo.buildNumber;

      // 1. Check official agency server API (instant, no auth required, avoids private GitHub 404)
      try {
        final serverRes = await http.get(
          Uri.parse('https://team.fbinternationalbd.com/wp-json/ftm/v1/app-version'),
        ).timeout(const Duration(seconds: 4));
        if (serverRes.statusCode == 200) {
          final sData = jsonDecode(serverRes.body);
          final sVersion = sData['version']?.toString() ?? '';
          final sBuild = sData['build']?.toString() ?? '';
          final sFull = sData['full_version']?.toString() ?? (sBuild.isNotEmpty ? '$sVersion+$sBuild' : sVersion);
          final sApk = sData['apk_url']?.toString() ?? 'https://team.fbinternationalbd.com/downloads/FBIBD_Team.apk';
          final sBody = sData['notes']?.toString() ?? 'Bug fixes and performance improvements.';
          final hasUpdate = _isVersionNewer(sFull, currentVersion, currentBuild);
          return AppUpdateInfo(
            hasUpdate: hasUpdate,
            currentVersion: currentVersion,
            latestVersion: sFull,
            releaseNotes: sBody,
            apkDownloadUrl: sApk,
          );
        }
      } catch (serverErr) {
        debugPrint('Server update check error: $serverErr');
      }

      // 2. Fallback to GitHub releases API
      final res = await http.get(
        Uri.parse(latestReleaseUrl),
        headers: {'Accept': 'application/vnd.github.v3+json'},
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode != 200) {
        return null;
      }

      final data = jsonDecode(res.body);
      final tagName = data['tag_name']?.toString() ?? '';
      final cleanLatestVersion = tagName.replaceAll(RegExp(r'^[vV]'), '').trim();
      final body = data['body']?.toString() ?? 'Bug fixes and performance improvements.';

      // Find APK in assets
      String? apkUrl;
      final assets = data['assets'] as List? ?? [];
      for (final asset in assets) {
        final name = asset['name']?.toString() ?? '';
        if (name.toLowerCase().endsWith('.apk')) {
          apkUrl = asset['browser_download_url']?.toString();
          break;
        }
      }

      // Fallback direct URL if named standard asset
      if (apkUrl == null && tagName.isNotEmpty) {
        apkUrl = 'https://github.com/$repoOwner/$repoName/releases/download/$tagName/FBIBD_Team.apk';
      }

      final hasUpdate = _isVersionNewer(cleanLatestVersion, currentVersion, currentBuild);

      return AppUpdateInfo(
        hasUpdate: hasUpdate,
        currentVersion: currentVersion,
        latestVersion: cleanLatestVersion.isEmpty ? tagName : cleanLatestVersion,
        releaseNotes: body,
        apkDownloadUrl: apkUrl,
      );
    } catch (e) {
      debugPrint('Error checking GitHub for updates: $e');
      return null;
    }
  }

  /// Compares semantic versions (e.g. 1.0.1 > 1.0.0 or 1.0.0+2 > 1.0.0+1)
  static bool _isVersionNewer(String latest, String current, [String currentBuild = '']) {
    if (latest.isEmpty || current.isEmpty) return false;
    try {
      final lClean = latest.replaceAll(RegExp(r'^[vV]'), '').trim();
      final cClean = current.replaceAll(RegExp(r'^[vV]'), '').trim();

      final lMain = lClean.split('+')[0].split('-')[0].trim();
      final cMain = cClean.split('+')[0].split('-')[0].trim();

      final lParts = lMain.split('.').map((p) => int.tryParse(p) ?? 0).toList();
      final cParts = cMain.split('.').map((p) => int.tryParse(p) ?? 0).toList();

      while (lParts.length < 3) {
        lParts.add(0);
      }
      while (cParts.length < 3) {
        cParts.add(0);
      }

      for (int i = 0; i < 3; i++) {
        if (lParts[i] > cParts[i]) return true;
        if (lParts[i] < cParts[i]) return false;
      }

      // If semantic versions are identical, compare build numbers if present
      int lBuild = 0;
      if (lClean.contains('+')) {
        lBuild = int.tryParse(lClean.split('+')[1]) ?? 0;
      }
      int cBuild = int.tryParse(currentBuild) ?? 0;
      if (cBuild == 0 && cClean.contains('+')) {
        cBuild = int.tryParse(cClean.split('+')[1]) ?? 0;
      }

      if (lBuild > cBuild && lBuild > 0) return true;
    } catch (_) {}
    return false;
  }

  /// Show the update modal dialog with live download progress
  static void showUpdateDialog(BuildContext context, AppUpdateInfo info) {
    if (isDialogShowing) return;
    isDialogShowing = true;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => _UpdateDialogContent(info: info),
    ).then((_) {
      isDialogShowing = false;
    });
  }
}

class _UpdateDialogContent extends StatefulWidget {
  final AppUpdateInfo info;

  const _UpdateDialogContent({required this.info});

  @override
  State<_UpdateDialogContent> createState() => _UpdateDialogContentState();
}

class _UpdateDialogContentState extends State<_UpdateDialogContent> {
  bool _isDownloading = false;
  String _progressText = '0%';
  double _progressValue = 0.0;
  String? _errorMessage;

  void _startOtaUpdate() {
    final apkUrl = widget.info.apkDownloadUrl;
    if (apkUrl == null || apkUrl.isEmpty) {
      setState(() {
        _errorMessage = 'No APK asset found in the latest GitHub release.';
      });
      return;
    }

    setState(() {
      _isDownloading = true;
      _errorMessage = null;
    });

    try {
      OtaUpdate()
          .execute(apkUrl, destinationFilename: 'FBIBD_Team.apk')
          .listen(
        (OtaEvent event) {
          if (!mounted) return;
          if (event.status == OtaStatus.DOWNLOADING) {
            final val = double.tryParse(event.value ?? '0') ?? 0.0;
            setState(() {
              _progressValue = (val / 100.0).clamp(0.0, 1.0);
              _progressText = '${val.toInt()}%';
            });
          } else if (event.status == OtaStatus.INSTALLING) {
            setState(() {
              _progressText = 'Installing update...';
            });
          } else if (event.status == OtaStatus.ALREADY_RUNNING_ERROR) {
            setState(() {
              _errorMessage = 'An update is already in progress.';
            });
          } else if (event.status == OtaStatus.PERMISSION_NOT_GRANTED_ERROR) {
            setState(() {
              _errorMessage = 'Storage/Install permission not granted.';
              _isDownloading = false;
            });
          } else if (event.status == OtaStatus.INTERNAL_ERROR) {
            setState(() {
              _errorMessage = 'Download error. Please check your internet connection.';
              _isDownloading = false;
            });
          }
        },
        onError: (err) {
          if (!mounted) return;
          setState(() {
            _isDownloading = false;
            _errorMessage = 'Update error: $err';
          });
        },
      );
    } catch (e) {
      setState(() {
        _isDownloading = false;
        _errorMessage = 'Failed to start download: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      title: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.primaryBlue.withValues(alpha: 0.15),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.system_update_rounded, color: AppColors.primaryBlueLight, size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Update Available!',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                ),
                Text(
                  'v${widget.info.currentVersion} ➔ v${widget.info.latestVersion}',
                  style: const TextStyle(fontSize: 12, color: AppColors.primaryBlueLight, fontWeight: FontWeight.w700),
                ),
              ],
            ),
          ),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'What\'s New in This Version:',
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 6),
          Container(
            constraints: const BoxConstraints(maxHeight: 120),
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
            ),
            child: SingleChildScrollView(
              child: Text(
                widget.info.releaseNotes,
                style: TextStyle(
                  fontSize: 12,
                  color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                  height: 1.35,
                ),
              ),
            ),
          ),
          if (_isDownloading) ...[
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Downloading update...', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                Text(_progressText, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.primaryBlueLight)),
              ],
            ),
            const SizedBox(height: 8),
            LinearProgressIndicator(
              value: _progressValue > 0 ? _progressValue : null,
              backgroundColor: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              color: AppColors.primaryBlue,
              minHeight: 6,
              borderRadius: BorderRadius.circular(3),
            ),
          ],
          if (_errorMessage != null) ...[
            const SizedBox(height: 12),
            Text(
              _errorMessage!,
              style: const TextStyle(color: AppColors.danger, fontSize: 11, fontWeight: FontWeight.w600),
            ),
          ],
        ],
      ),
      actions: [
        if (!_isDownloading) ...[
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Later', style: TextStyle(color: AppColors.textDim)),
          ),
          OutlinedButton.icon(
            onPressed: () {
              final url = widget.info.apkDownloadUrl ?? 'https://team.fbinternationalbd.com/downloads/FBIBD_Team.apk';
              launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
            },
            icon: const Icon(Icons.open_in_browser_rounded, size: 16),
            label: const Text('Download in Browser'),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.primaryBlueLight,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            ),
          ),
          ElevatedButton.icon(
            onPressed: _startOtaUpdate,
            icon: const Icon(Icons.download_rounded, size: 16),
            label: const Text('Update Now'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryBlue,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            ),
          ),
        ] else ...[
          TextButton.icon(
            onPressed: () {
              final url = widget.info.apkDownloadUrl ?? 'https://team.fbinternationalbd.com/downloads/FBIBD_Team.apk';
              launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
            },
            icon: const Icon(Icons.open_in_browser_rounded, size: 16),
            label: const Text('Download in Browser instead'),
          ),
        ],
      ],
    );
  }
}
