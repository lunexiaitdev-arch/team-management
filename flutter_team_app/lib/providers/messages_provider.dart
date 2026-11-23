import 'dart:async';
import 'package:flutter/material.dart';
import '../models/message.dart';
import '../services/api_service.dart';

class MessagesProvider extends ChangeNotifier {
  final ApiService _api;

  ChatChannel? _globalChannel;
  List<ChatChannel> _teamChannels = [];
  List<ChatChannel> _projectChannels = [];
  List<ChatChannel> _directChannels = [];

  List<ChatMessage> _currentMessages = [];
  bool _isLoadingChannels = false;
  bool _isLoadingThread = false;
  bool _isSending = false;
  String? _errorMessage;

  Timer? _threadPollTimer;
  String? _activeThreadType;
  String? _activeThreadId;
  int? _activeRecipientId;
  int? _activeProjectId;

  MessagesProvider(this._api);

  ChatChannel? get globalChannel => _globalChannel;
  List<ChatChannel> get teamChannels => _teamChannels;
  List<ChatChannel> get projectChannels => _projectChannels;
  List<ChatChannel> get directChannels => _directChannels;
  List<ChatMessage> get currentMessages => _currentMessages;

  bool get isLoadingChannels => _isLoadingChannels;
  bool get isLoadingThread => _isLoadingThread;
  bool get isSending => _isSending;
  String? get errorMessage => _errorMessage;

  int get totalUnread {
    int count = _globalChannel?.unreadCount ?? 0;
    for (final t in _teamChannels) {
      count += t.unreadCount;
    }
    for (final p in _projectChannels) {
      count += p.unreadCount;
    }
    for (final d in _directChannels) {
      count += d.unreadCount;
    }
    return count;
  }

  Future<void> fetchChannels() async {
    _isLoadingChannels = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _api.getChatChannels();
      final channelsData = res['channels'] as Map<String, dynamic>? ?? {};

      if (channelsData.containsKey('global')) {
        _globalChannel = ChatChannel.fromJson(Map<String, dynamic>.from(channelsData['global']));
      }

      if (channelsData['teams'] is List) {
        _teamChannels = (channelsData['teams'] as List)
            .map((t) => ChatChannel.fromJson(Map<String, dynamic>.from(t)))
            .toList();
      }

      if (channelsData['projects'] is List) {
        _projectChannels = (channelsData['projects'] as List)
            .map((p) => ChatChannel.fromJson(Map<String, dynamic>.from(p)))
            .toList();
      }

      if (channelsData['direct'] is List) {
        _directChannels = (channelsData['direct'] as List)
            .map((d) => ChatChannel.fromJson(Map<String, dynamic>.from(d)))
            .toList();
      }
    } catch (e) {
      _errorMessage = e.toString();
      debugPrint('Failed to load chat channels: $e');
    } finally {
      _isLoadingChannels = false;
      notifyListeners();
    }
  }

  Future<void> fetchThreadMessages({
    required String type,
    required String id,
    int? recipientId,
    int? projectId,
    bool silent = false,
  }) async {
    if (!silent) {
      _isLoadingThread = true;
      notifyListeners();
    }

    try {
      final res = await _api.getChatMessages(
        type: type,
        id: id,
        recipientId: recipientId,
        projectId: projectId,
      );

      final rawList = res['messages'] as List? ?? [];
      final parsed = rawList
          .map((m) => ChatMessage.fromJson(Map<String, dynamic>.from(m)))
          .toList();

      _currentMessages = parsed;

      // Update local unread counter in channels
      if (type == 'global' && _globalChannel != null) {
        _globalChannel = ChatChannel(
          type: _globalChannel!.type,
          id: _globalChannel!.id,
          title: _globalChannel!.title,
          subtitle: _globalChannel!.subtitle,
          lastMessage: _currentMessages.isNotEmpty ? _currentMessages.last.message : _globalChannel!.lastMessage,
          lastSender: _currentMessages.isNotEmpty ? _currentMessages.last.senderName : _globalChannel!.lastSender,
          lastTime: _currentMessages.isNotEmpty ? _currentMessages.last.createdAt : _globalChannel!.lastTime,
          unreadCount: 0,
        );
      }
    } catch (e) {
      if (!silent) _errorMessage = e.toString();
      debugPrint('Failed to fetch messages for thread: $e');
    } finally {
      if (!silent) {
        _isLoadingThread = false;
      }
      notifyListeners();
    }
  }

  Future<bool> sendMessage({
    required String type,
    required String id,
    required String text,
    int? recipientId,
    int? projectId,
  }) async {
    if (text.trim().isEmpty) return false;

    _isSending = true;
    notifyListeners();

    try {
      final res = await _api.sendChatMessage(
        type: type,
        id: id,
        message: text.trim(),
        recipientId: recipientId,
        projectId: projectId,
      );

      if (res['success'] == true) {
        // Refresh thread immediately to show new message
        await fetchThreadMessages(
          type: type,
          id: id,
          recipientId: recipientId,
          projectId: projectId,
          silent: true,
        );
        fetchChannels(); // refresh last message snippet in background
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('Failed to send message: $e');
      return false;
    } finally {
      _isSending = false;
      notifyListeners();
    }
  }

  void startThreadPolling({
    required String type,
    required String id,
    int? recipientId,
    int? projectId,
  }) {
    stopThreadPolling();
    _activeThreadType = type;
    _activeThreadId = id;
    _activeRecipientId = recipientId;
    _activeProjectId = projectId;

    fetchThreadMessages(
      type: type,
      id: id,
      recipientId: recipientId,
      projectId: projectId,
    );

    // Poll every 3.5 seconds for live conversation
    _threadPollTimer = Timer.periodic(const Duration(milliseconds: 3500), (_) {
      if (_activeThreadType != null && _activeThreadId != null) {
        fetchThreadMessages(
          type: _activeThreadType!,
          id: _activeThreadId!,
          recipientId: _activeRecipientId,
          projectId: _activeProjectId,
          silent: true,
        );
      }
    });
  }

  void stopThreadPolling() {
    _threadPollTimer?.cancel();
    _threadPollTimer = null;
    _activeThreadType = null;
    _activeThreadId = null;
    _activeRecipientId = null;
    _activeProjectId = null;
  }

  @override
  void dispose() {
    stopThreadPolling();
    super.dispose();
  }
}
