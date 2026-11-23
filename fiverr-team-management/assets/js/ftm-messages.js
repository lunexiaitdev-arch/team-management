/**
 * FTM Team Messages & Real-Time Communication Engine
 * Supports All-Hands Global, Squad Channels, Project Discussions, & Direct DMs
 * Matching Flutter Mobile App Experience & Architecture
 */

(function($) {
  'use strict';

  const ftmMessages = {
    state: {
      channels: {
        global: null,
        teams: [],
        projects: [],
        direct: []
      },
      activeTab: 'global', // 'global' | 'team' | 'direct' | 'project'
      activeChannel: null,
      messages: [],
      searchQuery: '',
      isLoadingChannels: false,
      isLoadingThread: false,
      isSending: false,
      threadPollTimer: null,
      channelsPollTimer: null,
      userScrolledUp: false
    },

    init: function() {
      if (!window.ftmData || !ftmData.is_user_logged_in) {
        return;
      }

      this.bindEvents();
      this.fetchChannels(false);
      this.startChannelsPolling();

      // Check if current hash is #messages on load
      const hash = (window.location.hash || '').replace(/^#\/?/, '').trim();
      if (hash.startsWith('messages')) {
        this.handleHashRoute(hash);
      }

      // Listen to window hash changes
      $(window).on('hashchange', () => {
        const h = (window.location.hash || '').replace(/^#\/?/, '').trim();
        if (h.startsWith('messages')) {
          this.handleHashRoute(h);
        }
      });
    },

    bindEvents: function() {
      const self = this;

      // Category Tab Clicks
      $(document).on('click', '.ftm-chat-tab', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        self.setTab(tab);
      });

      // Channel Item Click
      $(document).on('click', '.ftm-chat-channel-item', function(e) {
        e.preventDefault();
        const type = $(this).data('type');
        const id = $(this).data('id');
        const recipientId = $(this).data('recipient-id');
        const projectId = $(this).data('project-id');

        const ch = self.findChannel(type, id, recipientId, projectId);
        if (ch) {
          self.openChannel(ch);
        }
      });

      // Mobile Back Button
      $(document).on('click', '#ftm-chat-back-btn', function(e) {
        e.preventDefault();
        $('.ftm-chat-workspace').removeClass('ftm-chat-open');
        self.stopThreadPolling();
      });

      // Refresh Channels List Button
      $(document).on('click', '#ftm-chat-channels-refresh', function(e) {
        e.preventDefault();
        const $btn = $(this);
        $btn.addClass('ftm-spin');
        self.fetchChannels(false).always(function() {
          setTimeout(() => $btn.removeClass('ftm-spin'), 600);
        });
      });

      // Refresh Active Thread Button
      $(document).on('click', '#ftm-chat-refresh-thread-btn', function(e) {
        e.preventDefault();
        if (!self.state.activeChannel) return;
        const $btn = $(this);
        $btn.addClass('spinning');
        self.fetchThread(self.state.activeChannel, false).always(function() {
          setTimeout(() => $btn.removeClass('spinning'), 600);
        });
      });

      // Search Channels & Direct Contacts
      $(document).on('input', '#ftm-chat-search-input', function() {
        const val = $(this).val().trim();
        self.state.searchQuery = val.toLowerCase();
        if (val.length > 0) {
          $('#ftm-chat-search-clear').removeClass('hidden');
        } else {
          $('#ftm-chat-search-clear').addClass('hidden');
        }
        self.renderChannels();
      });

      // Clear Search
      $(document).on('click', '#ftm-chat-search-clear', function() {
        $('#ftm-chat-search-input').val('').trigger('input').focus();
      });

      // Auto-growing textarea & Enter key to send
      $(document).on('input', '#ftm-chat-input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        const hasText = $(this).val().trim().length > 0;
        $('#ftm-chat-send-btn').prop('disabled', !hasText || self.state.isSending);
      });

      $(document).on('keydown', '#ftm-chat-input', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          $('#ftm-chat-composer-form').trigger('submit');
        }
      });

      // Send Message Form Submit
      $(document).on('submit', '#ftm-chat-composer-form', function(e) {
        e.preventDefault();
        self.sendMessage();
      });

      // Track Scroll in Messages Container
      $(document).on('scroll', '#ftm-chat-messages-container', function() {
        const el = this;
        const distFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
        self.state.userScrolledUp = (distFromBottom > 75);
      });

      // Project "View Order Details" Header Button
      $(document).on('click', '#ftm-chat-btn-view-order', function(e) {
        e.preventDefault();
        if (self.state.activeChannel && self.state.activeChannel.projectId) {
          // Open project modal if FTM app has openProjectModal
          if (window.ftmApp && typeof ftmApp.openProjectModal === 'function') {
            ftmApp.openProjectModal(self.state.activeChannel.projectId);
          } else {
            // Trigger project row click or open project view
            window.location.hash = '#dashboard';
          }
        }
      });

      // Direct DM trigger from Team Members card
      $(document).on('click', '.ftm-open-member-dm-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const uid = $(this).data('user-id');
        if (uid) {
          self.openDirect(uid);
        }
      });

      // Order Chat trigger from Project Details modal
      $(document).on('click', '#ftm-project-open-chat-btn', function(e) {
        e.preventDefault();
        let pid = $(this).data('project-id');
        if (!pid && window.App && App.state && App.state.activeProjectId) {
          pid = App.state.activeProjectId;
        }
        if (!pid && window.ftmApp && ftmApp.state && ftmApp.state.activeProjectId) {
          pid = ftmApp.state.activeProjectId;
        }
        if (pid) {
          // Close modal
          $('.ftm-modal-backdrop').addClass('hidden');
          self.openProject(pid);
        }
      });

      // View visibility observer
      const viewEl = document.getElementById('ftm-view-messages');
      if (viewEl && window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
              const isActive = $('#ftm-view-messages').hasClass('active');
              if (isActive) {
                if (!self.state.activeChannel && self.state.channels.global) {
                  self.openChannel(self.state.channels.global);
                } else if (self.state.activeChannel) {
                  self.scrollToBottom();
                  self.startThreadPolling();
                }
              } else {
                self.stopThreadPolling();
              }
            }
          });
        });
        observer.observe(viewEl, { attributes: true });
      }

      // Automatically inject Direct Message button on Team Member cards
      const injectMemberChatButtons = function() {
        $('.ftm-member-card-actions').each(function() {
          const $actions = $(this);
          if (!$actions.find('.ftm-open-member-dm-btn').length) {
            const uid = $actions.find('.ftm-trigger-member-profile').data('id');
            if (uid) {
              $actions.append(`
                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-btn-block ftm-open-member-dm-btn" data-user-id="${uid}" style="margin-top:6px; display:flex; align-items:center; justify-content:center; gap:6px;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                  <span>Direct Message</span>
                </button>
              `);
            }
          }
        });
      };

      $(document).ajaxStop(injectMemberChatButtons);
      setInterval(injectMemberChatButtons, 2500);
    },

    setTab: function(tab) {
      this.state.activeTab = tab;
      $('.ftm-chat-tab').removeClass('active');
      $(`.ftm-chat-tab[data-tab="${tab}"]`).addClass('active');
      this.renderChannels();
      if (tab === 'project' && (!this.state.channels.projects || this.state.channels.projects.length === 0)) {
        this.fetchChannels(false);
      }
    },

    handleHashRoute: function(hash) {
      // Format: messages or messages?type=direct&id=12 or messages?type=project&id=42
      if (hash.includes('?')) {
        const queryStr = hash.split('?')[1] || '';
        const params = new URLSearchParams(queryStr);
        const type = params.get('type');
        const id = params.get('id');

        if (type === 'direct' && id) {
          this.openDirect(parseInt(id, 10));
          return;
        } else if (type === 'project' && id) {
          this.openProject(parseInt(id, 10));
          return;
        } else if (type && id) {
          this.openChannelByTypeAndId(type, id);
          return;
        }
      }

      // Default: ensure Global channel is loaded if none active
      if (!this.state.activeChannel && this.state.channels.global) {
        this.openChannel(this.state.channels.global);
      }
    },

    findChannel: function(type, id, recipientId, projectId) {
      if (type === 'global') {
        return this.state.channels.global;
      }
      if (type === 'team') {
        return this.state.channels.teams.find(t => t.id === id || t.team === id);
      }
      if (type === 'direct') {
        return this.state.channels.direct.find(d => (recipientId && d.recipient_id == recipientId) || d.id === id);
      }
      if (type === 'project') {
        return this.state.channels.projects.find(p => (projectId && p.project_id == projectId) || p.id === id);
      }
      return null;
    },

    fetchChannels: function(silent) {
      const self = this;
      if (!silent) self.state.isLoadingChannels = true;

      return $.ajax({
        url: ftmData.root + 'messages/channels',
        method: 'GET',
        headers: { 'X-WP-Nonce': ftmData.nonce }
      }).done(function(res) {
        if (res && res.success && res.channels) {
          self.state.channels = res.channels;
          self.updateUnreadBadges();
          self.renderChannels();

          // Auto-select global if no active channel on desktop
          if (!self.state.activeChannel && window.innerWidth > 768) {
            if (self.state.channels.global) {
              self.openChannel(self.state.channels.global);
            }
          }
        }
      }).fail(function(xhr) {
        console.warn('Failed to fetch chat channels:', xhr.responseText);
      }).always(function() {
        self.state.isLoadingChannels = false;
      });
    },

    startChannelsPolling: function() {
      const self = this;
      if (this.state.channelsPollTimer) clearInterval(this.state.channelsPollTimer);
      this.state.channelsPollTimer = setInterval(function() {
        self.fetchChannels(true);
      }, 15000);
    },

    updateUnreadBadges: function() {
      const ch = this.state.channels;
      let globalUnread = ch.global ? (ch.global.unread_count || 0) : 0;
      let teamUnread = (ch.teams || []).reduce((acc, cur) => acc + (cur.unread_count || 0), 0);
      let directUnread = (ch.direct || []).reduce((acc, cur) => acc + (cur.unread_count || 0), 0);
      let projectUnread = (ch.projects || []).reduce((acc, cur) => acc + (cur.unread_count || 0), 0);

      const totalUnread = globalUnread + teamUnread + directUnread + projectUnread;

      // Update Header Bell / Messages Badge
      const $hdrBadge = $('#ftm-header-messages-badge');
      if (totalUnread > 0) {
        $hdrBadge.text(totalUnread > 99 ? '99+' : totalUnread).removeClass('hidden');
      } else {
        $hdrBadge.addClass('hidden');
      }

      // Update Sidebar Nav Badge
      const $navBadge = $('#ftm-nav-messages-badge');
      if (totalUnread > 0) {
        $navBadge.text(totalUnread > 99 ? '99+' : totalUnread).removeClass('hidden');
      } else {
        $navBadge.addClass('hidden');
      }

      // Update Category Tab Badges
      this.renderTabBadge('#ftm-chat-badge-global', globalUnread);
      this.renderTabBadge('#ftm-chat-badge-team', teamUnread);
      this.renderTabBadge('#ftm-chat-badge-direct', directUnread);
      this.renderTabBadge('#ftm-chat-badge-project', projectUnread);
    },

    renderTabBadge: function(selector, count) {
      const $el = $(selector);
      if (count > 0) {
        $el.text(count > 99 ? '99+' : count).removeClass('hidden');
      } else {
        $el.addClass('hidden');
      }
    },

    renderChannels: function() {
      const $list = $('#ftm-chat-channel-list');
      const tab = this.state.activeTab;
      const q = this.state.searchQuery;
      let items = [];

      if (tab === 'global') {
        if (this.state.channels.global) {
          items = [this.state.channels.global];
        }
      } else if (tab === 'team') {
        items = this.state.channels.teams || [];
      } else if (tab === 'direct') {
        items = this.state.channels.direct || [];
      } else if (tab === 'project') {
        items = this.state.channels.projects || [];
      }

      // Filter by search query if present
      if (q.length > 0) {
        items = items.filter(it => {
          const title = (it.title || '').toLowerCase();
          const sub = (it.subtitle || '').toLowerCase();
          const lastMsg = (it.last_message || '').toLowerCase();
          const orderId = (it.order_id || '').toLowerCase();
          return title.includes(q) || sub.includes(q) || lastMsg.includes(q) || orderId.includes(q);
        });
      }

      if (this.state.isLoadingChannels && items.length === 0) {
        $list.html(`
          <div style="padding:40px 16px; text-align:center; color:var(--ftm-text-muted);">
            <div class="ftm-loading-spinner" style="width:24px; height:24px; border:2px solid #2563eb; border-top-color:transparent; border-radius:50%; animation:ftmSpin 0.6s linear infinite; margin:0 auto 10px;"></div>
            <p style="margin:0; font-size:12px; font-weight:600;">Loading conversations...</p>
          </div>
        `);
        return;
      }

      if (items.length === 0) {
        $list.html(`
          <div style="padding:30px 16px; text-align:center; color:var(--ftm-text-muted); font-size:12px;">
            <p style="margin:0 0 4px; font-weight:700;">No conversations found</p>
            <p style="margin:0;">Try another search term or switch tabs.</p>
          </div>
        `);
        return;
      }

      let html = '';
      const active = this.state.activeChannel;

      items.forEach(it => {
        const isSelected = active && (
          (active.type === it.type && active.id === it.id) ||
          (it.type === 'direct' && active.recipientId == it.recipient_id) ||
          (it.type === 'project' && active.projectId == it.project_id)
        );

        const unreadCount = it.unread_count || 0;
        const unreadClass = unreadCount > 0 ? 'unread' : '';
        const selectedClass = isSelected ? 'active' : '';

        // Avatar / Icon
        let avatarHtml = '';
        if (it.type === 'direct') {
          const avatarUrl = it.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(it.title)}&background=2563eb&color=fff&size=80`;
          avatarHtml = `
            <div class="ftm-chat-item-avatar-wrap">
              <img src="${avatarUrl}" class="ftm-chat-item-avatar-img" alt="${it.title}" />
              <span class="ftm-chat-item-status-dot"></span>
            </div>
          `;
        } else if (it.type === 'global') {
          avatarHtml = `
            <div class="ftm-chat-item-avatar-wrap">
              <div class="ftm-chat-item-avatar-icon global">🌐</div>
            </div>
          `;
        } else if (it.type === 'team') {
          avatarHtml = `
            <div class="ftm-chat-item-avatar-wrap">
              <div class="ftm-chat-item-avatar-icon team">⚡</div>
            </div>
          `;
        } else if (it.type === 'project') {
          avatarHtml = `
            <div class="ftm-chat-item-avatar-wrap">
              <div class="ftm-chat-item-avatar-icon project">📦</div>
            </div>
          `;
        }

        const snippet = it.last_message ? (it.last_sender ? `${it.last_sender}: ${it.last_message}` : it.last_message) : 'No messages yet';

        html += `
          <div class="ftm-chat-channel-item ${selectedClass} ${unreadClass}"
               data-type="${it.type}"
               data-id="${it.id || ''}"
               data-recipient-id="${it.recipient_id || ''}"
               data-project-id="${it.project_id || ''}">
            ${avatarHtml}
            <div class="ftm-chat-item-body">
              <div class="ftm-chat-item-top">
                <span class="ftm-chat-item-title">${this.escapeHtml(it.title)}</span>
                <span class="ftm-chat-item-time">${it.last_time || ''}</span>
              </div>
              <div class="ftm-chat-item-sub">
                ${it.subtitle ? this.escapeHtml(it.subtitle) : ''}
              </div>
              <div class="ftm-chat-item-bottom">
                <span class="ftm-chat-item-snippet">${this.escapeHtml(snippet)}</span>
                ${unreadCount > 0 ? `<span class="ftm-chat-item-unread-badge">${unreadCount}</span>` : ''}
              </div>
            </div>
          </div>
        `;
      });

      $list.html(html);
    },

    openChannel: function(channel) {
      if (!channel) return;

      this.state.activeChannel = {
        type: channel.type,
        id: channel.id,
        recipientId: channel.recipient_id || null,
        projectId: channel.project_id || null,
        orderId: channel.order_id || null,
        title: channel.title,
        subtitle: channel.subtitle || '',
        avatar: channel.avatar || '',
        team: channel.team || ''
      };

      // Mobile slide into chat pane
      $('.ftm-chat-workspace').addClass('ftm-chat-open');

      // Update Channel List Active Class
      this.renderChannels();

      // Update Chat Header
      $('#ftm-chat-header-title').text(channel.title);
      $('#ftm-chat-header-sub').text(channel.subtitle || '');

      // Header Avatar
      const $hdrAvatar = $('#ftm-chat-header-avatar');
      if (channel.type === 'direct' && channel.avatar) {
        $hdrAvatar.html(`<img src="${channel.avatar}" style="width:100%; height:100%; border-radius:12px; object-fit:cover;" alt="${channel.title}" />`);
      } else if (channel.type === 'global') {
        $hdrAvatar.html(`<div class="ftm-chat-avatar-placeholder" style="background:linear-gradient(135deg, #2563eb, #06b6d4);">🌐</div>`);
      } else if (channel.type === 'team') {
        $hdrAvatar.html(`<div class="ftm-chat-avatar-placeholder" style="background:linear-gradient(135deg, #8b5cf6, #3b82f6);">⚡</div>`);
      } else if (channel.type === 'project') {
        $hdrAvatar.html(`<div class="ftm-chat-avatar-placeholder" style="background:linear-gradient(135deg, #059669, #10b981);">📦</div>`);
      }

      // Order Details Button
      const $orderBtn = $('#ftm-chat-btn-view-order');
      if (channel.type === 'project' && channel.project_id) {
        $orderBtn.data('project-id', channel.project_id).removeClass('hidden');
      } else {
        $orderBtn.addClass('hidden');
      }

      // Reset scroll flag
      this.state.userScrolledUp = false;

      // Fetch Thread
      this.fetchThread(this.state.activeChannel, false);
      this.startThreadPolling();
    },

    openDirect: function(userId) {
      const self = this;
      this.setTab('direct');

      // Switch view to messages if not currently active
      if (window.ftmApp && typeof ftmApp.switchView === 'function') {
        ftmApp.switchView('messages');
      } else {
        window.location.hash = '#messages';
      }

      let ch = (this.state.channels.direct || []).find(d => d.recipient_id == userId);
      if (ch) {
        this.openChannel(ch);
      } else {
        // Fetch fresh channels then find
        this.fetchChannels(false).done(function() {
          ch = (self.state.channels.direct || []).find(d => d.recipient_id == userId);
          if (ch) {
            self.openChannel(ch);
          }
        });
      }
    },

    openProject: function(projectId) {
      const self = this;
      this.setTab('project');

      if (window.ftmApp && typeof ftmApp.switchView === 'function') {
        ftmApp.switchView('messages');
      } else {
        window.location.hash = '#messages';
      }

      let ch = (this.state.channels.projects || []).find(p => p.project_id == projectId);
      if (ch) {
        this.openChannel(ch);
      } else {
        this.fetchChannels(false).done(function() {
          ch = (self.state.channels.projects || []).find(p => p.project_id == projectId);
          if (ch) {
            self.openChannel(ch);
          } else {
            // Temporary project channel
            const tempCh = {
              type: 'project',
              id: 'proj_' + projectId,
              project_id: projectId,
              order_id: '#' + projectId,
              title: 'Order Discussion #' + projectId,
              subtitle: 'Order Workspace Channel',
              last_message: 'Active conversation',
              unread_count: 0
            };
            if (!self.state.channels.projects) self.state.channels.projects = [];
            self.state.channels.projects.unshift(tempCh);
            self.renderChannels();
            self.openChannel(tempCh);
          }
        });
      }
    },

    openChannelByTypeAndId: function(type, id) {
      const ch = this.findChannel(type, id, null, null);
      if (ch) {
        this.setTab(type === 'team' ? 'team' : (type === 'project' ? 'project' : (type === 'direct' ? 'direct' : 'global')));
        this.openChannel(ch);
      }
    },

    fetchThread: function(channel, silent) {
      const self = this;
      if (!channel) return $.Deferred().resolve();

      if (!silent) {
        self.state.isLoadingThread = true;
        $('#ftm-chat-messages-container').html(`
          <div style="flex:1; display:flex; align-items:center; justify-content:center; color:var(--ftm-text-muted);">
            <div class="ftm-loading-spinner" style="width:24px; height:24px; border:2px solid #2563eb; border-top-color:transparent; border-radius:50%; animation:ftmSpin 0.6s linear infinite;"></div>
          </div>
        `);
      }

      const params = {
        type: channel.type,
        id: channel.id || '',
        recipient_id: channel.recipientId || '',
        project_id: channel.projectId || '',
        limit: 50
      };

      return $.ajax({
        url: ftmData.root + 'messages/thread',
        method: 'GET',
        data: params,
        headers: { 'X-WP-Nonce': ftmData.nonce }
      }).done(function(res) {
        if (res && res.success && res.messages) {
          self.state.messages = res.messages;
          self.renderMessages(silent);

          // Clear channel unread count locally
          if (channel.unread_count) {
            channel.unread_count = 0;
            self.updateUnreadBadges();
            self.renderChannels();
          }
        }
      }).fail(function(xhr) {
        if (!silent) {
          $('#ftm-chat-messages-container').html(`
            <div style="padding:40px 20px; text-align:center; color:var(--ftm-text-muted);">
              <p style="color:#ef4444; font-weight:700;">Could not load messages</p>
              <p style="font-size:12px;">Please check your connection and try refreshing.</p>
            </div>
          `);
        }
      }).always(function() {
        self.state.isLoadingThread = false;
      });
    },

    startThreadPolling: function() {
      const self = this;
      this.stopThreadPolling();
      this.state.threadPollTimer = setInterval(function() {
        if (self.state.activeChannel && !self.state.isLoadingThread && !self.state.isSending) {
          self.fetchThread(self.state.activeChannel, true);
        }
      }, 3500);
    },

    stopThreadPolling: function() {
      if (this.state.threadPollTimer) {
        clearInterval(this.state.threadPollTimer);
        this.state.threadPollTimer = null;
      }
    },

    renderMessages: function(silent) {
      const $container = $('#ftm-chat-messages-container');
      const messages = this.state.messages || [];

      if (messages.length === 0) {
        $container.html(`
          <div class="ftm-chat-empty-thread">
            <div class="ftm-chat-empty-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <h4 class="ftm-chat-empty-title">Welcome to #${this.escapeHtml(this.state.activeChannel.title)}</h4>
            <p class="ftm-chat-empty-desc">This is the start of the discussion. Send a message below to begin collaborating!</p>
          </div>
        `);
        return;
      }

      let html = '';
      let lastDate = '';

      messages.forEach(msg => {
        // Simple date header divider (e.g. "Nov 1, 2026")
        const msgDate = (msg.created_at || '').split(',')[0];
        if (msgDate && msgDate !== lastDate) {
          html += `
            <div class="ftm-chat-date-divider">
              <span class="ftm-chat-date-pill">${this.escapeHtml(msgDate)}</span>
            </div>
          `;
          lastDate = msgDate;
        }

        const isMine = !!msg.is_mine;

        if (isMine) {
          html += `
            <div class="ftm-chat-msg ftm-chat-msg-outgoing">
              <div class="ftm-chat-bubble-outgoing">
                ${this.formatMessageText(msg.message)}
              </div>
              <div class="ftm-chat-outgoing-meta">
                <span>${msg.created_at || ''}</span>
                <span class="ftm-chat-check-read">✓✓</span>
              </div>
            </div>
          `;
        } else {
          const avatarUrl = msg.sender_avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.sender_name)}&background=2563eb&color=fff&size=64`;
          const roleLabel = msg.sender_role ? `<span class="ftm-chat-msg-role-tag">${this.escapeHtml(msg.sender_role)}</span>` : '';
          const teamLabel = msg.sender_team ? `<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="font-size:9px;">${this.escapeHtml(msg.sender_team)}</span>` : '';

          html += `
            <div class="ftm-chat-msg ftm-chat-msg-incoming">
              <img src="${avatarUrl}" class="ftm-chat-msg-avatar" alt="${this.escapeHtml(msg.sender_name)}" />
              <div class="ftm-chat-msg-body">
                <div class="ftm-chat-msg-sender-row">
                  <span class="ftm-chat-msg-sender-name">${this.escapeHtml(msg.sender_name)}</span>
                  ${roleLabel}
                  ${teamLabel}
                  <span class="ftm-chat-msg-time">${msg.created_at || ''}</span>
                </div>
                <div class="ftm-chat-bubble-incoming">
                  ${this.formatMessageText(msg.message)}
                </div>
              </div>
            </div>
          `;
        }
      });

      $container.html(html);

      // Auto-scroll logic: If user was not scrolled up or if this is the initial load, scroll to bottom
      if (!silent || !this.state.userScrolledUp) {
        this.scrollToBottom();
      }
    },

    scrollToBottom: function() {
      const el = document.getElementById('ftm-chat-messages-container');
      if (el) {
        el.scrollTop = el.scrollHeight;
      }
    },

    sendMessage: function() {
      const self = this;
      const $input = $('#ftm-chat-input');
      const text = $input.val().trim();
      const channel = this.state.activeChannel;

      if (!text || !channel || this.state.isSending) return;

      this.state.isSending = true;
      $('#ftm-chat-send-btn').prop('disabled', true);

      // Optimistic message append
      const optMsg = {
        id: 'opt_' + Date.now(),
        is_mine: true,
        sender_name: ftmData.current_user ? ftmData.current_user.name : 'You',
        message: text,
        created_at: 'Just now'
      };

      this.state.messages.push(optMsg);
      this.renderMessages(false);
      this.scrollToBottom();

      $input.val('').css('height', '40px');

      const payload = {
        type: channel.type,
        id: channel.id || '',
        recipient_id: channel.recipientId || '',
        project_id: channel.projectId || '',
        message: text
      };

      $.ajax({
        url: ftmData.root + 'messages/send',
        method: 'POST',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        headers: { 'X-WP-Nonce': ftmData.nonce }
      }).done(function(res) {
        if (res && res.success) {
          self.fetchThread(channel, true);
        }
      }).fail(function(xhr) {
        alert('Could not deliver message. Please check connection.');
      }).always(function() {
        self.state.isSending = false;
        $('#ftm-chat-send-btn').prop('disabled', $input.val().trim().length === 0);
        $input.focus();
      });
    },

    escapeHtml: function(text) {
      if (!text) return '';
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },

    formatMessageText: function(text) {
      if (!text) return '';
      const safe = this.escapeHtml(text);
      // Linkify URLs safely
      const urlPattern = /(\b(https?):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
      const linked = safe.replace(urlPattern, '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:underline;">$1</a>');
      // Convert newlines to <br>
      return linked.replace(/\n/g, '<br/>');
    }
  };

  // Expose globally
  window.ftmMessages = ftmMessages;

  // Initialize on DOM ready
  $(document).ready(function() {
    ftmMessages.init();
  });

})(jQuery);
