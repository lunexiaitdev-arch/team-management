import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/message.dart';
import '../../models/project.dart';
import '../../providers/auth_provider.dart';
import '../../providers/messages_provider.dart';
import '../../providers/projects_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import '../projects/project_detail_sheet.dart';

class ChatConversationScreen extends StatefulWidget {
  final String conversationType; // 'global', 'team', 'project', 'direct'
  final String conversationId;
  final String title;
  final String subtitle;
  final String? avatar;
  final String? team;
  final int? recipientId;
  final int? projectId;
  final String? orderId;

  const ChatConversationScreen({
    super.key,
    required this.conversationType,
    required this.conversationId,
    required this.title,
    required this.subtitle,
    this.avatar,
    this.team,
    this.recipientId,
    this.projectId,
    this.orderId,
  });

  @override
  State<ChatConversationScreen> createState() => _ChatConversationScreenState();
}

class _ChatConversationScreenState extends State<ChatConversationScreen> {
  final TextEditingController _textController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isFirstLoad = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final prov = Provider.of<MessagesProvider>(context, listen: false);
      prov.startThreadPolling(
        type: widget.conversationType,
        id: widget.conversationId,
        recipientId: widget.recipientId,
        projectId: widget.projectId,
      );
    });
  }

  @override
  void dispose() {
    // Stop thread polling when navigating away
    final prov = Provider.of<MessagesProvider>(context, listen: false);
    prov.stopThreadPolling();
    _textController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _handleSendMessage() async {
    final text = _textController.text.trim();
    if (text.isEmpty) return;

    _textController.clear();
    final prov = Provider.of<MessagesProvider>(context, listen: false);
    final success = await prov.sendMessage(
      type: widget.conversationType,
      id: widget.conversationId,
      text: text,
      recipientId: widget.recipientId,
      projectId: widget.projectId,
    );

    if (success) {
      _scrollToBottom();
    }
  }

  void _openProjectDetails() {
    if (widget.projectId == null) return;
    final projectsProv = Provider.of<ProjectsProvider>(context, listen: false);
    final proj = projectsProv.projects.firstWhere(
      (p) => p.id == widget.projectId,
      orElse: () => ProjectItem(
        id: widget.projectId!,
        orderId: widget.orderId ?? '#${widget.projectId}',
        profileName: 'Fiverr Order',
        buyerUsername: 'Client',
        title: widget.title,
        orderValue: 0,
        netValue: 0,
        status: 'wip',
        deliveryDate: '',
        progressPercent: 0,
        team: widget.team ?? '',
        createdAt: '',
      ),
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => ProjectDetailSheet(project: proj),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<MessagesProvider>(context);
    final auth = context.watch<AuthProvider>();

    // Auto scroll on first load
    if (_isFirstLoad && prov.currentMessages.isNotEmpty) {
      _isFirstLoad = false;
      _scrollToBottom();
    }

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
              backgroundImage: (widget.avatar != null && widget.avatar!.isNotEmpty)
                  ? NetworkImage(widget.avatar!)
                  : null,
              child: (widget.avatar == null || widget.avatar!.isEmpty)
                  ? Text(
                      widget.title.isNotEmpty ? widget.title[0].toUpperCase() : 'C',
                      style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight, fontSize: 13),
                    )
                  : null,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.title,
                    style: TextStyle(
                      color: isDark ? AppColors.textLight : AppColors.textDark,
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    widget.subtitle,
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 11,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          if (widget.projectId != null)
            IconButton(
              icon: const Icon(Icons.info_outline_rounded),
              onPressed: _openProjectDetails,
              tooltip: 'View Order Details',
            ),
          if (widget.team != null && widget.team!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: Center(child: TeamBadge(team: widget.team!, isSmall: true)),
            ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Project Order Context Banner if Order Chat
            if (widget.projectId != null)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightCard,
                  border: Border(
                    bottom: BorderSide(
                      color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    ),
                  ),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.inventory_2_outlined, size: 16, color: AppColors.primaryBlueLight),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Discussion linked to Order ${widget.orderId ?? "#${widget.projectId}"}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.primaryBlueLight),
                      ),
                    ),
                    TextButton(
                      onPressed: _openProjectDetails,
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        minimumSize: Size.zero,
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      child: const Text('View Order →', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
              ),

            // Messages Stream
            Expanded(
              child: prov.isLoadingThread && prov.currentMessages.isEmpty
                  ? const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue))
                  : prov.currentMessages.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.chat_bubble_outline_rounded,
                                size: 48,
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'No messages yet',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Send a message to start the conversation!',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                ),
                              ),
                            ],
                          ),
                        )
                      : ListView.builder(
                          controller: _scrollController,
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          itemCount: prov.currentMessages.length,
                          itemBuilder: (context, index) {
                            final msg = prov.currentMessages[index];
                            return _buildMessageBubble(msg, isDark);
                          },
                        ),
            ),

            // Input Bar
            if (!auth.isViewOnly)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                border: Border(
                  top: BorderSide(
                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    width: 1,
                  ),
                ),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: isDark ? AppColors.darkCard : AppColors.lightCard,
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(
                          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                        ),
                      ),
                      child: TextField(
                        controller: _textController,
                        textCapitalization: TextCapitalization.sentences,
                        keyboardType: TextInputType.multiline,
                        maxLines: 4,
                        minLines: 1,
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 14,
                        ),
                        decoration: InputDecoration(
                          hintText: 'Type your message...',
                          hintStyle: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 13,
                          ),
                          border: InputBorder.none,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        ),
                        onSubmitted: (_) => _handleSendMessage(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    width: 44,
                    height: 44,
                    decoration: const BoxDecoration(
                      gradient: AppColors.brandGradient,
                      shape: BoxShape.circle,
                    ),
                    child: IconButton(
                      icon: prov.isSending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                      onPressed: prov.isSending ? null : _handleSendMessage,
                    ),
                  ),
                ],
              ),
            )
            else
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                border: Border(
                  top: BorderSide(
                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    width: 1,
                  ),
                ),
              ),
              child: Text(
                'View-only oversight mode — sending messages is disabled.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMessageBubble(ChatMessage msg, bool isDark) {
    if (msg.isMine) {
      // Sent by current user (Right-aligned, primary blue)
      return Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.end,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            const SizedBox(width: 48),
            Flexible(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF0072CE), Color(0xFF0689E3)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: const BorderRadius.only(
                        topLeft: Radius.circular(16),
                        topRight: Radius.circular(16),
                        bottomLeft: Radius.circular(16),
                        bottomRight: Radius.circular(4),
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primaryBlue.withOpacity(0.25),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Text(
                      msg.message,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        height: 1.35,
                      ),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    msg.createdAt,
                    style: const TextStyle(
                      color: AppColors.textDim,
                      fontSize: 10,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    } else {
      // Received from other member (Left-aligned, card surface)
      return Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.start,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 15,
              backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
              backgroundImage: msg.senderAvatar.isNotEmpty ? NetworkImage(msg.senderAvatar) : null,
              child: msg.senderAvatar.isEmpty
                  ? Text(
                      msg.senderName.isNotEmpty ? msg.senderName[0].toUpperCase() : 'M',
                      style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight, fontSize: 11),
                    )
                  : null,
            ),
            const SizedBox(width: 8),
            Flexible(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        msg.senderName,
                        style: const TextStyle(
                          color: AppColors.primaryBlueLight,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        msg.senderRole,
                        style: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 10,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      if (msg.senderTeam.isNotEmpty) ...[
                        const SizedBox(width: 4),
                        Text(
                          '• ${msg.senderTeam}',
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 10,
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 3),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkCard : AppColors.lightCard,
                      borderRadius: const BorderRadius.only(
                        topLeft: Radius.circular(4),
                        topRight: Radius.circular(16),
                        bottomLeft: Radius.circular(16),
                        bottomRight: Radius.circular(16),
                      ),
                      border: Border.all(
                        color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                      ),
                    ),
                    child: Text(
                      msg.message,
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 14,
                        height: 1.35,
                      ),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    msg.createdAt,
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 10,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 48),
          ],
        ),
      );
    }
  }
}
