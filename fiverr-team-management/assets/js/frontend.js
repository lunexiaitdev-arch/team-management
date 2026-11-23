/**



 * Fiverr Team Management System - Frontend App JavaScript



 * With Full Team-Based Role Scoping (WpCraft, WpNexus, WpTitans)



 */







(function ($) {



    'use strict';







    // Escape server-provided text before it is interpolated into HTML templates
    const ftmFeedIcon = (tag) => {
        const open = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
        if (tag === 'Extension') return open + '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>';
        if (tag === 'Assignment' || tag === 'Assigned') return open + '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
        if (tag === 'Delivered' || tag === 'Delivery Submitted' || tag === 'Delivery Accepted') return open + '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
        return open + '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
    };
    const ftmCombined = {
        SKILLS: ['WordPress / Horizon', 'Full Stack', 'SEO', 'UI/UX', 'Custom', 'Shopify', 'Graphics', 'Other'],
        row(sp) {
            sp = sp || {};
            const opts = this.SKILLS.map(k => '<option value="' + ftmEsc(k) + '"' + (sp.skill === k ? ' selected' : '') + '>' + ftmEsc(k) + '</option>').join('');
            return '<div class="ftm-split-row">'
                + '<input type="text" class="ftm-input ftm-split-label" placeholder="Team / member (e.g. SEO / Pial)" value="' + ftmEsc(sp.party_label || '') + '">'
                + '<select class="ftm-select ftm-split-skill">' + opts + '</select>'
                + '<input type="number" step="0.01" min="0" class="ftm-input ftm-split-amount" placeholder="Net $" value="' + (sp.share_net !== undefined ? ftmEsc(sp.share_net) : '') + '">'
                + '<input type="text" class="ftm-input ftm-split-note" placeholder="Note (optional)" value="' + ftmEsc(sp.note || '') + '">'
                + '<button type="button" class="ftm-split-remove" aria-label="Remove party" title="Remove">&times;</button></div>';
        },
        fill(combined) {
            const on = !!(combined && combined.is_combined);
            $('#ftm-form-combined-toggle').prop('checked', on);
            $('#ftm-combined-fields').toggleClass('hidden', !on);
            $('#ftm-form-order-total').val(on ? combined.order_total_value : '');
            $('#ftm-form-splits').html(on ? (combined.splits || []).map(sp => this.row(sp)).join('') : '');
            this.summary();
        },
        reset() { this.fill(null); },
        collect() {
            if (!$('#ftm-form-combined-toggle').is(':checked')) return { order_total_value: 0, splits: [] };
            const splits = [];
            $('#ftm-form-splits .ftm-split-row').each(function () {
                const r = $(this);
                const label = r.find('.ftm-split-label').val().trim();
                const amount = parseFloat(r.find('.ftm-split-amount').val()) || 0;
                if (label || amount > 0) splits.push({ party_label: label, skill: r.find('.ftm-split-skill').val(), share_net: amount, note: r.find('.ftm-split-note').val().trim() });
            });
            return { order_total_value: parseFloat($('#ftm-form-order-total').val()) || 0, splits };
        },
        summary() {
            const box = $('#ftm-combined-summary');
            if (!$('#ftm-form-combined-toggle').is(':checked')) { box.empty(); return; }
            const mine = parseFloat($('#ftm-form-net-value').val()) || 0;
            const total = parseFloat($('#ftm-form-order-total').val()) || 0;
            let others = 0;
            $('#ftm-form-splits .ftm-split-amount').each(function () { others += parseFloat($(this).val()) || 0; });
            const left = Math.round((total - mine - others) * 100) / 100;
            const money = v => '$' + v.toFixed(2);
            let status = '<span class="ftm-cs-ok">Fully accounted for</span>';
            if (total <= 0) status = '<span class="ftm-cs-warn">Enter the full order value</span>';
            else if (left > 0.009) status = '<span class="ftm-cs-warn">' + money(left) + ' not assigned to anyone yet</span>';
            else if (left < -0.009) status = '<span class="ftm-cs-bad">Shares exceed the order by ' + money(-left) + '</span>';
            box.html('<span>Order <strong>' + money(total) + '</strong></span><span>My share <strong>' + money(mine) + '</strong></span><span>Others <strong>' + money(others) + '</strong></span>' + status);
        },
        renderDetails(c) {
            const box = $('#ftm-details-combined');
            if (!c || !c.is_combined) { box.addClass('hidden').empty(); return; }
            const money = v => '$' + (parseFloat(v) || 0).toFixed(2);
            const total = c.order_total_value || 0;
            const pct = v => total > 0 ? Math.max(2, Math.round(v / total * 100)) : 0;
            const rows = (c.splits || []).map(sp => '<li><div><strong>' + ftmEsc(sp.party_label || 'Unnamed party') + '</strong>'
                + (sp.skill ? ' <span class="ftm-cd-skill">' + ftmEsc(sp.skill) + '</span>' : '')
                + (sp.note ? '<div class="ftm-cd-note">' + ftmEsc(sp.note) + '</div>' : '') + '</div><span>' + money(sp.share_net) + '</span></li>').join('');
            let warn = '';
            if (c.unallocated > 0.009) warn = '<div class="ftm-cd-warn">' + money(c.unallocated) + ' of this order is not assigned to any party yet. Edit the project to record who gets it.</div>';
            if (c.over_allocated > 0.009) warn = '<div class="ftm-cd-warn ftm-cd-bad">Recorded shares exceed the order value by ' + money(c.over_allocated) + '.</div>';
            box.removeClass('hidden').html(
                '<div class="ftm-cd-head"><span class="ftm-cd-title">Combined order</span><span class="ftm-cd-total">Full order ' + money(total) + '</span></div>'
                + '<div class="ftm-cd-bar"><i class="ftm-cd-mine" style="width:' + pct(c.my_share) + '%"></i><i class="ftm-cd-others" style="width:' + pct(c.others_total) + '%"></i></div>'
                + '<div class="ftm-cd-legend"><span><b class="ftm-cd-dot ftm-cd-mine"></b>Mine ' + money(c.my_share) + ' (' + c.my_share_percent + '%)</span><span><b class="ftm-cd-dot ftm-cd-others"></b>Others ' + money(c.others_total) + '</span></div>'
                + (rows ? '<ul class="ftm-cd-list">' + rows + '</ul>' : '') + warn);
        }
    };
    const ftmSub = {
        fill(p) {
            const isSub = !!p && p.order_type === 'subscription';
            $('#ftm-subscription-fields').toggleClass('hidden', !(isSub || $('#ftm-form-order-type').val() === 'subscription'));
            $('#ftm-form-sub-active').prop('checked', !p || !p.subscription_status || p.subscription_status === 'active');
            $('#ftm-form-sub-next').val(p && p.subscription_next_date ? p.subscription_next_date : '');
            $('#ftm-form-sub-term').val(p && p.subscription_term_days ? p.subscription_term_days : 7);
        },
        toggle() {
            const on = $('#ftm-form-order-type').val() === 'subscription';
            $('#ftm-subscription-fields').toggleClass('hidden', !on);
            if (on && !$('#ftm-form-sub-next').val()) {
                const d = new Date(); d.setMonth(d.getMonth() + 1);
                $('#ftm-form-sub-next').val(d.toISOString().split('T')[0]);
            }
        },
        collect() {
            if ($('#ftm-form-order-type').val() !== 'subscription') return {};
            return {
                subscription_status: $('#ftm-form-sub-active').is(':checked') ? 'active' : 'stopped',
                subscription_next_date: $('#ftm-form-sub-next').val() || '',
                subscription_term_days: parseInt($('#ftm-form-sub-term').val(), 10) || 7
            };
        },
        line(p) {
            if (!p || p.order_type !== 'subscription') return '';
            if (p.subscription_parent_id) return 'Monthly subscription renewal';
            if (p.subscription_status === 'active') return 'Monthly subscription - renews ' + (p.subscription_next_date || 'on the next cycle');
            if (p.subscription_status === 'stopped') return 'Monthly subscription - stopped';
            return 'Monthly subscription';
        }
    };
    const ftmIsMgmt = (t) => { const v = String(t || '').toLowerCase(); return v === 'management' || v.indexOf('elite') !== -1; };
    const ftmShare = {
        saved: {},
        names() {
            const out = [];
            $('#ftm-form-assignees-list .ftm-assignee-checkbox-item').each(function () {
                const cb = $(this).find('input[type="checkbox"]');
                if (cb.is(':checked')) {
                    const id = parseInt(cb.val(), 10);
                    const name = $(this).find('.ftm-user-title').first().find('span').first().text().trim() || $(this).find('.ftm-user-title').first().text().trim() || 'Member';
                    const team = $(this).data('team') || $(this).find('.ftm-assignee-team-tag').text().trim() || '';
                    out.push({ id, name, team });
                }
            });
            return out;
        },
        render() {
            const box = $('#ftm-split-panel');
            const list = this.names();
            if (list.length < 2) { box.addClass('hidden').empty(); return; }
            const current = {};
            box.find('input[data-uid]').each(function () { current[$(this).data('uid')] = $(this).val(); });
            const baseEqual = Math.floor(10000 / list.length) / 100;
            const remainder = Math.round((100 - baseEqual * list.length) * 100) / 100;

            // Check if saved shares exist for all currently selected assignees AND sum up to 100%
            let savedSum = 0;
            let hasValidSaved = (list.length > 0);
            list.forEach(m => {
                if (this.saved && this.saved[m.id] !== undefined) {
                    savedSum += parseFloat(this.saved[m.id]) || 0;
                } else {
                    hasValidSaved = false;
                }
            });
            const useSaved = hasValidSaved && Math.abs(savedSum - 100) <= 0.05;

            // Check if current input values (if already entered in this open modal) sum to 100%
            let currentSum = 0;
            let hasCurrent = (list.length > 0);
            list.forEach(m => {
                if (current[m.id] !== undefined && current[m.id] !== '') {
                    currentSum += parseFloat(current[m.id]) || 0;
                } else {
                    hasCurrent = false;
                }
            });
            const useCurrent = hasCurrent && Math.abs(currentSum - 100) <= 0.05;

            const teamBadgeColors = {
                'WpCraft': 'bg-blue-soft text-blue',
                'WpNexus': 'bg-emerald-soft text-emerald',
                'WpTitans': 'bg-purple-soft text-purple',
                'WpHorizon': 'bg-amber-soft text-amber'
            };

            const rows = list.map((m, i) => {
                const defaultEqual = (i === list.length - 1) ? (baseEqual + remainder) : baseEqual;
                let v = defaultEqual;
                if (useCurrent) {
                    v = current[m.id];
                } else if (useSaved) {
                    v = this.saved[m.id];
                }
                const teamBadge = m.team ? `<span class="ftm-badge ftm-badge-xs ${teamBadgeColors[m.team] || 'bg-blue-soft text-blue'}" style="margin-left:6px; font-size:10px; font-weight:700; padding:1px 6px;">${ftmEsc(m.team)}</span>` : '';
                return '<div class="ftm-sp-row"><span class="ftm-sp-name">' + ftmEsc(m.name) + teamBadge + '</span><span class="ftm-sp-input"><input type="number" min="0" max="100" step="0.01" data-uid="' + m.id + '" value="' + ftmEsc(v) + '" class="ftm-input ftm-input-sm"><b>%</b></span><span class="ftm-sp-money" data-money-uid="' + m.id + '"></span></div>';
            }).join('');
            box.removeClass('hidden').html('<div class="ftm-sp-title">Value split between assignees</div>' + rows
                + '<div class="ftm-sp-foot"><button type="button" class="ftm-btn ftm-btn-xs ftm-btn-secondary" id="ftm-sp-equal">Split equally</button><button type="button" class="ftm-btn ftm-btn-xs ftm-btn-outline" id="ftm-sp-autobalance" style="margin-left:6px;">Auto-Balance 100%</button><span id="ftm-sp-status" style="margin-left:auto;"></span></div>');
            this.summary();
        },
        summary() {
            const net = parseFloat($('#ftm-form-net-value').val()) || 0;
            let total = 0;
            $('#ftm-split-panel input[data-uid]').each(function () {
                const v = parseFloat($(this).val()) || 0; total += v;
                $('#ftm-split-panel [data-money-uid="' + $(this).data('uid') + '"]').text('$' + (net * v / 100).toFixed(2));
            });
            total = Math.round(total * 100) / 100;
            const st = $('#ftm-sp-status');
            if (Math.abs(total - 100) <= 0.05) {
                st.attr('class', 'ftm-cs-ok').html('✓ Adds up to 100%');
            } else {
                st.attr('class', 'ftm-cs-bad').html('Total is ' + total + '% &bull; <a href="#" id="ftm-sp-quick-fix" style="text-decoration:underline; font-weight:700; color:inherit;">Fix to 100%</a>');
            }
        },
        autoBalance() {
            const inputs = $('#ftm-split-panel input[data-uid]');
            const n = inputs.length;
            if (n <= 0) return;
            let currentTotal = 0;
            const vals = [];
            inputs.each(function () {
                const v = Math.max(0, parseFloat($(this).val()) || 0);
                vals.push(v);
                currentTotal += v;
            });
            if (currentTotal > 0) {
                let runningSum = 0;
                inputs.each(function (idx) {
                    if (idx === n - 1) {
                        const rem = Math.round((100 - runningSum) * 100) / 100;
                        $(this).val(Math.max(0, rem).toFixed(2));
                    } else {
                        const normalized = Math.round((vals[idx] / currentTotal * 100) * 100) / 100;
                        runningSum += normalized;
                        $(this).val(normalized.toFixed(2));
                    }
                });
            } else {
                const base = Math.floor(10000 / n) / 100;
                const rem = Math.round((100 - base * n) * 100) / 100;
                inputs.each(function (idx) {
                    $(this).val((idx === n - 1 ? base + rem : base).toFixed(2));
                });
            }
            this.summary();
        },
        collect() {
            if ($('#ftm-split-panel').hasClass('hidden')) return {};
            if (!this.valid()) {
                this.autoBalance();
            }
            const out = {};
            $('#ftm-split-panel input[data-uid]').each(function () { out[$(this).data('uid')] = parseFloat($(this).val()) || 0; });
            return { assignee_splits: out };
        },
        valid() {
            if ($('#ftm-split-panel').hasClass('hidden')) return true;
            let t = 0; $('#ftm-split-panel input[data-uid]').each(function () { t += parseFloat($(this).val()) || 0; });
            return Math.abs(t - 100) <= 0.05;
        }
    };
    const ftmEsc = (value) => App.escapeHtml(value);
    const ftmTint = (name) => { let h = 0; const t = String(name || '').trim().toLowerCase(); for (let i = 0; i < t.length; i++) h = (h * 31 + t.charCodeAt(i)) % 360; return 'background:linear-gradient(135deg,hsl(' + h + ',68%,52%),hsl(' + ((h + 28) % 360) + ',70%,42%)) !important;color:#fff !important;'; };
    const ftmTipChip = (p) => { const t = parseFloat(p && p.tip_amount) || 0; if (t <= 0) return ''; const n = (t * 0.8).toFixed(2); return '<span class="ftm-tip-chip" title="Client tip $' + t.toFixed(2) + ' paid, $' + n + ' after the 20% marketplace fee">&#127873; +$' + n + ' tip</span>'; };
    const ftmNetCell = (p) => {
        if (!p) return '';
        if (p.is_financial_unrecorded) {
            return '<span class="ftm-badge ftm-badge-outline ftm-text-xs" title="Historical Order: Financial value unrecorded" style="color:var(--ftm-text-muted); opacity:0.8;">Unrecorded</span>';
        }
        const curMonth = (window.App && window.App.state && window.App.state.currentMonth) || $('#ftm-filter-month').val() || '';
        const isTipMonth = (p.is_cross_month_tip && curMonth && curMonth !== 'all' && curMonth === p.tip_month && curMonth !== p.delivered_month);
        const isDeliveredMonth = (p.is_cross_month_tip && curMonth && curMonth !== 'all' && curMonth === p.delivered_month && curMonth !== p.tip_month);
        const tipFee = (p.fiverr_fee_percent !== undefined && p.fiverr_fee_percent !== null) ? parseFloat(p.fiverr_fee_percent) : 20;
        const netTipAmt = (parseFloat(p.tip_amount) || 0) * (1 - tipFee / 100);

        if (isTipMonth) {
            return '<div class="ftm-tip-only-cell" style="display:inline-flex; flex-direction:column; gap:2px; line-height:1.25;">' +
                '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; width:fit-content;" title="Delivered in ' + (p.delivered_month || 'prior month') + '; only this tip is credited in ' + p.tip_month + '">🎁 Tip-Only Month</span>' +
                '<div><strong class="text-amber" style="font-size:13.5px; font-weight:700;">+$' + netTipAmt.toFixed(2) + ' tip</strong></div>' +
                '<span class="ftm-text-muted" style="font-size:11px;">Delivered in ' + (p.delivered_month || 'prior') + ' ($' + parseFloat(p.net_delivery_value || 0).toFixed(2) + ')</span>' +
                '</div>';
        }
        if (isDeliveredMonth) {
            return '<div style="display:inline-flex; flex-direction:column; gap:2px; line-height:1.25;">' +
                '<strong class="text-emerald" style="font-size:13.5px; font-weight:700;">$' + parseFloat(p.net_delivery_value || 0).toFixed(2) + '</strong>' +
                '<span class="ftm-badge bg-slate-soft text-muted ftm-badge-xs" style="font-size:9.5px; padding:1px 5px; width:fit-content;" title="Client tip credited in ' + p.tip_month + '">Tip in ' + p.tip_month + '</span>' +
                '</div>';
        }
        return '<strong class="text-emerald">$' + parseFloat(p.net_delivery_value || 0).toFixed(2) + '</strong>' + ftmTipChip(p);
    };
    const ftmStar = (v) => (parseFloat(v) || 0).toFixed(1);
    const ftmRating = (sel) => Math.round(Math.min(5, Math.max(1, parseFloat($(sel).val()) || 5)) * 10) / 10;
    const ftmRateSync = ($w, v) => { v = Math.round(Math.min(5, Math.max(1, parseFloat(v) || 5)) * 10) / 10; $w.find('.ftm-rate-range').val(v).css('--pct', ((v - 1) / 4 * 100) + '%'); $w.find('.ftm-rate-stars').css('--fill', (v / 5 * 100) + '%'); $w.find('.ftm-rate-chips button').removeClass('active').filter(function () { return parseFloat($(this).data('v')) === v; }).addClass('active'); $w.attr('data-tone', v >= 4.5 ? 'great' : v >= 3.5 ? 'good' : v >= 2.5 ? 'mid' : 'low'); };
    const ftmInitials = (name) => { const w = String(name || '').trim().split(/\s+/).filter(Boolean); if (!w.length) return '?'; return (w.length > 1 ? w[0].charAt(0) + w[1].charAt(0) : w[0].slice(0, 2)).toUpperCase(); };
    const App = {



        state: {



            page: 1,



            perPage: parseInt($('#ftm-filter-per-page').val()) || 50,



            month: $('#ftm-filter-month').val() || 'all',



            status: 'all',



            team: 'all',



            client: 'all',



            profileId: 0,



            memberId: 0,



            search: '',



            sort: 'delivery_asc',



            viewFilter: '',



            startDate: '',



            endDate: '',



            currentView: 'dashboard',



            profiles: [],



            teamMembers: [],



            activeProjectId: null,



            activeMemberActivityId: null,



            activeMemberActivityPeriod: 'current_month',



            userRole: $('#ftm-app').data('user-role') || 'member',



            userTeam: $('#ftm-app').data('user-team') || 'WpCraft',



            isAdmin: $('#ftm-app').data('is-admin') === 1,



            isLeader: $('#ftm-app').data('is-leader') === 1,



            userId: parseInt($('#ftm-app').data('user-id')) || 0,



            leaderboardPeriod: 'monthly',



            leaderboardPeriodKey: '',



            leaderboardTeam: 'all',



            leaderboardSort: 'score',



            leaderboardSearch: '',



            leaderboardData: null,

            timeTracker: {
                active: false,
                sessionId: null,
                projectId: null,
                orderId: '',
                clientName: '',
                profileName: '',
                sessionType: 'regular',
                startTime: 0,
                lastSnapshotTime: 0,
                lastHeartbeatTime: 0,
                stream: null,
                video: null,
                elapsedSeconds: 0,
                intervalSeconds: 300,
                snapshotsCount: 0,
                snapshotCount: 0,
                intervalId: null
            },
            activeTracker: null
        },







        init() {
            if (window.ftmData && window.ftmData.current_user) {
                this.state.isDeputy = !!window.ftmData.current_user.is_deputy;
                this.state.isMember = !!window.ftmData.current_user.is_member;
                this.state.isAdmin = !!window.ftmData.current_user.is_admin && !this.state.isDeputy;
                this.state.isLeader = !!window.ftmData.current_user.is_leader && !this.state.isDeputy;
                this.state.userTeam = window.ftmData.current_user.team || 'WpCraft';
                this.state.userRole = window.ftmData.current_user.role || (this.state.isAdmin ? 'admin' : (this.state.isDeputy ? 'deputy' : (this.state.isLeader ? 'leader' : 'member')));
            }
            const filterMonthVal = $('#ftm-filter-month').val();
            if (filterMonthVal) {
                this.state.month = filterMonthVal;
            } else {
                this.state.month = '2026-11';
            }

            this.initTheme();



            this.initGreeting();
            this._attachActivityListeners();



            this.bindEvents();



            this.initRouter();



            this.loadInitialData();



            this.loadActiveNoticesBanner();
            this.restoreActiveProjectTimer();

            window.addEventListener('beforeunload', (e) => {
                if (this.state.timeTracker && this.state.timeTracker.active) {
                    e.preventDefault();
                    e.returnValue = 'You have an active work timer running. Reloading may pause screen sharing.';
                    return e.returnValue;
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.state.timeTracker && this.state.timeTracker.active) {
                    this._processTrackerTick();
                }
            });







            // Dismiss onboarding profile banner if official email is already set



            if (window.ftmData && window.ftmData.current_user && !window.ftmData.current_user.profile_setup_needed) {



                $('#ftm-banner-profile-setup').remove();



            }



        },







        initTheme() {



            const savedTheme = localStorage.getItem('ftm_theme') || 'system';



            this.applyTheme(savedTheme, false);







            if (window.matchMedia) {



                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {



                    const currentPref = localStorage.getItem('ftm_theme') || 'system';



                    if (currentPref === 'system') {



                        this.applyTheme('system', false);



                    }



                });



            }



        },







        applyTheme(theme, save = true) {



            let effective = theme;



            if (theme === 'system') {



                const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;



                effective = systemPrefersDark ? 'dark' : 'light';



            }







            const _root = document.documentElement;
            _root.classList.add('ftm-theme-switching');
            _root.setAttribute('data-theme', effective);
            requestAnimationFrame(() => requestAnimationFrame(() => _root.classList.remove('ftm-theme-switching')));



            if (save) {



                localStorage.setItem('ftm_theme', theme);



            }







            const isDark = effective === 'dark';



            $('#ftm-header-theme-toggle').attr('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');



            $('#ftm-header-theme-toggle').attr('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');



            const themeLabel = $('#ftm-sheet-theme-label');



            if (themeLabel.length) {



                themeLabel.text(isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');



            }



        },







        toggleTheme() {



            const current = document.documentElement.getAttribute('data-theme') || 'light';



            const nextTheme = current === 'dark' ? 'light' : 'dark';



            this.applyTheme(nextTheme, true);



            this.toast(`Theme switched to ${nextTheme} mode`, 'info');



        },







        initGreeting() {



            const hour = new Date().getHours();



            let salutation = 'Welcome back';



            if (hour >= 5 && hour < 12) {



                salutation = 'Good morning';



            } else if (hour >= 12 && hour < 17) {



                salutation = 'Good afternoon';



            } else {



                salutation = 'Good evening';



            }







            if ($('#ftm-greeting-salutation').length) {



                $('#ftm-greeting-salutation').text(salutation);



            }







            const banner = $('#ftm-dynamic-greeting');



            if (banner.length) {



                const userName = banner.data('user-name') || '';



                const greetingText = userName ? `${salutation}, ${userName}` : `${salutation}`;



                banner.text(greetingText);



            }







            const datePill = $('#ftm-today-date-pill');



            if (datePill.length) {



                try {



                    const now = new Date();



                    const options = { weekday: 'short', month: 'short', day: 'numeric' };



                    datePill.text(now.toLocaleDateString('en-US', options));



                } catch (e) { }



            }



        },







        api(endpoint, method = 'GET', data = null) {



            // Normalize endpoint so it does not have duplicate leading slashes



            const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;



            const url = ftmData.root + cleanEndpoint;



            const options = {



                method: method,



                headers: {



                    'Content-Type': 'application/json',



                    'X-WP-Nonce': ftmData.nonce



                }



            };



            if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {



                options.body = JSON.stringify(data);



            }

            this._activeApiCount = (this._activeApiCount || 0) + 1;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            if (controller) {
                options.signal = controller.signal;
            }
            const timeoutId = controller ? setTimeout(() => controller.abort(), 25000) : null;

            return fetch(url, options).then(res => {



                if (!res.ok) {



                    if (res.status === 401 && ftmData && ftmData.login_url) {



                        window.location.href = ftmData.login_url;



                    }



                    return res.json().then(err => {



                        const errMsg = (err && (err.message || err.error || err.code)) || ('HTTP ' + res.status);



                        const error = new Error(errMsg);



                        error.data = err;



                        error.status = res.status;



                        return Promise.reject(error);



                    }).catch(e => {



                        if (e && e.status) {



                            return Promise.reject(e);



                        }



                        return Promise.reject(new Error('HTTP ' + res.status));



                    });



                }



                return res.json();



            }).catch(err => {
                if (err && err.name === 'AbortError') {
                    throw new Error('Request timed out. Please check your connection and try again.');
                }
                throw err;
            }).finally(() => {
                if (timeoutId) clearTimeout(timeoutId);
                this._activeApiCount = Math.max(0, (this._activeApiCount || 1) - 1);
                if (this._activeApiCount === 0 && typeof $ !== 'undefined') {
                    $(document).trigger('ftm:apiIdle');
                }
            });



        },







        bindEvents() {



            const self = this;







            // Extend Project Delivery Button



            $(document).off('click', '.ftm-extend-project-btn').on('click', '.ftm-extend-project-btn', function (e) {



                e.preventDefault();



                e.stopPropagation();



                const pid = $(this).data('id');
                const orderId = $(this).data('order') || '';
                const client = $(this).data('client') || '';
                const deadline = $(this).data('deadline') || '';

                $('#ftm-extend-project-id').val(pid);
                $('#ftm-extend-modal-sub').text(`Order #${orderId} · Client: ${client}`);
                $('#ftm-extend-reason-input').val('');
                self.setupExtendDatePicker(pid, deadline);

                $('#ftm-modal-extend-delivery').removeClass('hidden');
            });

            // Calendar picker: the number of added days is worked out from the chosen date
            $(document).off('change input', '#ftm-extend-date-input').on('change input', '#ftm-extend-date-input', function () {
                self.updateExtendSummary();
            });

            // Submit extension form
            $('#ftm-extend-delivery-form').off('submit').on('submit', function (e) {
                e.preventDefault();
                const pid = $('#ftm-extend-project-id').val();
                const newDate = $('#ftm-extend-date-input').val();
                let days = parseInt($('#ftm-extend-days-input').val() || 0, 10);
                if (!newDate) {
                    self.toast('Pick a new deadline from the calendar.', 'error');
                    return;
                }
                if (days < 1) {
                    days = 1;
                }

                const reason = $('#ftm-extend-reason-input').val().trim();
                const btn = $('#ftm-btn-submit-extend');
                btn.prop('disabled', true).text('Extending...');

                self.api(`projects/${pid}/extend`, 'POST', { new_date: newDate, days, reason }).then(res => {
                    btn.prop('disabled', false).text('Extend Deadline');
                    $('#ftm-modal-extend-delivery').addClass('hidden');
                    self.toast(`Delivery deadline extended to ${newDate} (+${days}d).`, 'success');

                    const updatedItem = (res && res.id) ? res : null;
                    const updateItem = (item) => {
                        if (!item) return;
                        if (updatedItem && String(updatedItem.id) === String(item.id)) {
                            Object.assign(item, updatedItem);
                        } else {
                            item.delivery_date = newDate;
                            item.delivery_date_formatted = new Date(newDate + 'T12:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            item.extension_count = (parseInt(item.extension_count || 0, 10)) + 1;
                            item.extension_days = (parseInt(item.extension_days || 0, 10)) + days;
                            item.is_ontime = 0;
                            item.delivered_without_extension = 0;
                            const nowTs = new Date().setHours(0,0,0,0);
                            const targetTs = new Date(newDate + 'T12:00:00').setHours(0,0,0,0);
                            const diffDays = Math.ceil((targetTs - nowTs) / 86400000);
                            item.days_remaining = {
                                days: diffDays,
                                urgency: diffDays < 0 ? 'danger' : (diffDays <= 2 ? 'warning' : 'safe'),
                                short_badge: diffDays < 0 ? Math.abs(diffDays) + 'd late' : (diffDays === 0 ? 'Due today' : diffDays + 'd left'),
                                label: diffDays < 0 ? Math.abs(diffDays) + 'd late' : diffDays + 'd remaining'
                            };
                        }
                    };

                    if (Array.isArray(self.state.projects)) {
                        const target = self.state.projects.find(x => String(x.id) === String(pid));
                        if (target) updateItem(target);
                        if (typeof self.renderProjectsTable === 'function') {
                            self.renderProjectsTable(self.state.projects, $('#ftm-projects-tbody'));
                        }
                    }
                    if (Array.isArray(self.state.myAssignedProjects)) {
                        const target = self.state.myAssignedProjects.find(x => String(x.id) === String(pid));
                        if (target) updateItem(target);
                        if (typeof self.renderMyAssignedProjectsList === 'function') {
                            self.renderMyAssignedProjectsList();
                        } else if (typeof self.renderMyProjectsTable === 'function') {
                            self.renderMyProjectsTable(self.state.myAssignedProjects);
                        }
                    }
                    if (Array.isArray(self.state.myProjects)) {
                        const target = self.state.myProjects.find(x => String(x.id) === String(pid));
                        if (target) updateItem(target);
                    }

                    self.loadProjects();
                    if (typeof self.loadMyProjectsView === 'function') {
                        self.loadMyProjectsView();
                    }
                }).catch(err => {



                    btn.prop('disabled', false).text('Extend Deadline');



                    self.toast(err.message || 'Failed to extend delivery', 'error');



                });



            });







            // Quick Progress Modal Event Handlers



            $('#ftm-update-progress-slider').on('input change', function () {



                const val = $(this).val();



                $('#ftm-update-progress-val-badge').text(val + '%');



            });







            $('.ftm-prog-preset-btn').on('click', function (e) {



                e.preventDefault();



                const prog = $(this).data('prog');



                $('#ftm-update-progress-slider').val(prog).trigger('input');



            });







            $('#ftm-update-progress-form').off('submit').on('submit', function (e) {



                e.preventDefault();



                const pid = $('#ftm-update-progress-pid').val();



                const prog = parseInt($('#ftm-update-progress-slider').val() || 0);



                const btn = $('#ftm-btn-submit-quick-prog');



                btn.prop('disabled', true).text('Saving...');







                self.api(`projects/${pid}/progress`, 'POST', { progress_percent: prog }).then(res => {



                    btn.prop('disabled', false).text('Save Progress');



                    $('#ftm-modal-update-progress').addClass('hidden');



                    self.toast(`Project progress updated to ${prog}%!`, 'success');







                    // If on standup view, sync state and workspace controls



                    if (self.state.assignedProjects) {



                        const proj = self.state.assignedProjects.find(p => parseInt(p.id) === parseInt(pid));



                        if (proj) {



                            proj.progress_percent = prog;



                            proj.progress = prog;



                        }



                        self.renderTodaysProjectsWidget(self.state.assignedProjects);



                    }



                    if ($('#ftm-ws-select-project').val() == pid) {



                        $('#ftm-ws-progress-percent').val(prog).trigger('input');



                    }







                    // If projects list is active, reload



                    if (window.location.hash.includes('dashboard')) {



                        self.loadProjects();



                    }



                }).catch(err => {



                    btn.prop('disabled', false).text('Save Progress');



                    self.toast(err.message || 'Failed to update progress', 'error');



                });



            });







            // Member Activity Period Toggle



            $('.ftm-period-btn').on('click', function (e) {



                e.preventDefault();



                const period = $(this).data('period');



                $('.ftm-period-btn').removeClass('active');



                $(this).addClass('active');



                if (self.state.activeMemberActivityId) {



                    self.openMemberActivityModal(self.state.activeMemberActivityId, period);



                }



            });







            // Filter main dashboard by member from Activity Modal



            $('#ftm-activity-view-all-projects-btn').on('click', function () {



                if (self.state.activeMemberActivityId) {



                    const mId = self.state.activeMemberActivityId;



                    $('#ftm-modal-member-activity').addClass('hidden');



                    self.filterByMember(mId);



                }



            });







            // Trigger Member Activity Modal from anywhere with .ftm-trigger-member-activity



            $(document).on('click', '.ftm-trigger-member-activity', function (e) {



                e.preventDefault();



                e.stopPropagation();



                const memberId = $(this).data('id') || $(this).data('member-id');
                if (memberId) {
                    const currentFilterMonth = self.state.month || $('#ftm-filter-month').val() || 'current_month';
                    self.openMemberActivityModal(memberId, self.state.activeMemberActivityPeriod || currentFilterMonth);
                }



            });







            // Trigger Member Profile & Portfolio View from anywhere with .ftm-trigger-member-profile



            $(document).on('click', '.ftm-trigger-member-profile', function (e) {



                e.preventDefault();



                e.stopPropagation();



                const memberId = $(this).data('id') || $(this).data('member-id');
                if (memberId) {
                    const midNum = parseInt(memberId, 10);
                    self.state.selectedMemberProfileId = midNum;
                    try { localStorage.setItem('ftm_selected_member_profile_id', midNum); } catch (e) { }
                    window.location.hash = '#member-profile?id=' + midNum;
                    self.switchView('member-profile');
                }



            });







            // Hash Router / Navigation



            $(window).on('hashchange', () => self.handleHashChange());







            $('.ftm-nav-item, .ftm-nav-trigger').on('click', function (e) {



                const target = $(this).data('view') || $(this).data('target');



                const memberId = $(this).data('member-id');



                if (memberId) {



                    self.state.selectedMemberProfileId = memberId;



                }



                if (target === 'add-project') {



                    e.preventDefault();



                    self.openAddProjectModal();



                    return;



                }



                if (target) {



                    self.switchView(target);



                }



            });







            // Mobile sidebar toggle with overlay backdrop



            $('#ftm-mobile-toggle').on('click', function (e) {



                e.stopPropagation();



                $('#ftm-sidebar').toggleClass('open');



                const isOpen = $('#ftm-sidebar').hasClass('open');



                $('#ftm-sidebar-overlay').toggleClass('open', isOpen);



                $('body').toggleClass('ftm-drawer-open', isOpen);



            });







            // Close mobile sidebar on overlay click or explicit close button



            $(document).on('click', '#ftm-sidebar-overlay, #ftm-sidebar-close-btn', function (e) {



                e.preventDefault();



                $('#ftm-sidebar').removeClass('open');



                $('#ftm-sidebar-overlay').removeClass('open');



                $('body').removeClass('ftm-drawer-open');



            });







            // Edit Admin Executive Profile Button



            $(document).on('click', '#ftm-btn-edit-admin-profile', function (e) {



                e.preventDefault();



                const curUserId = window.ftmData && window.ftmData.current_user ? window.ftmData.current_user.id : 1;



                self.openMemberProfileEdit(curUserId);



            });







            // Close mobile sidebar when clicking outside



            $(document).on('click', function (e) {



                if (!$(e.target).closest('#ftm-sidebar, #ftm-mobile-toggle').length) {



                    if ($('#ftm-sidebar').hasClass('open')) {



                        $('#ftm-sidebar').removeClass('open');



                        $('#ftm-sidebar-overlay').removeClass('open');



                        $('body').removeClass('ftm-drawer-open');



                    }



                }



            });







            // Automatically keep body.ftm-modal-open in sync whenever any modal changes



            if (window.MutationObserver) {



                const modalObserver = new MutationObserver(function () {



                    const hasModal = $('.ftm-modal-backdrop:not(.hidden)').length > 0;



                    $('body').toggleClass('ftm-modal-open', hasModal);



                });



                const appEl = document.getElementById('ftm-app');



                if (appEl) {



                    modalObserver.observe(appEl, { attributes: true, subtree: true, attributeFilter: ['class'] });



                }



            }







            // Theme Toggle Button (Header & Action Sheet)



            $('#ftm-header-theme-toggle, #ftm-sheet-btn-theme').on('click', function (e) {



                e.preventDefault();



                self.toggleTheme();



            });







            // Mobile Bottom Navigation Bar Tabs



            $('.ftm-mob-nav-item').on('click', function (e) {



                e.preventDefault();



                const view = $(this).data('view');



                if (view) {



                    self.switchView(view);



                }



            });







            // Mobile Quick Action Center (+) Button -> opens Action Sheet



            $('#ftm-mob-quick-action-btn').on('click', function (e) {



                e.preventDefault();



                $('#ftm-mobile-action-sheet-backdrop').removeClass('hidden');



            });







            // Close Action Sheet



            $('#ftm-sheet-close-btn, #ftm-mobile-action-sheet-backdrop').on('click', function (e) {



                if (e.target === this || $(this).is('#ftm-sheet-close-btn') || $(this).closest('#ftm-sheet-close-btn').length) {



                    $('#ftm-mobile-action-sheet-backdrop').addClass('hidden');



                }



            });







            // Action Sheet buttons



            $('#ftm-sheet-btn-project, #ftm-sheet-btn-add-project').on('click', function (e) {



                e.preventDefault();



                $('#ftm-mobile-action-sheet-backdrop').addClass('hidden');



                self.openAddProjectModal();



            });







            $('#ftm-sheet-btn-standup').on('click', function (e) {



                e.preventDefault();



                $('#ftm-mobile-action-sheet-backdrop').addClass('hidden');



                self.openDailyStandupModal();



            });







            $('#ftm-sheet-btn-notice').on('click', function (e) {



                e.preventDefault();



                $('#ftm-mobile-action-sheet-backdrop').addClass('hidden');



                self.openBroadcastNoticeModal();



            });







            $('#ftm-sheet-btn-profile, #ftm-sheet-btn-add-profile').on('click', function (e) {



                e.preventDefault();



                $('#ftm-mobile-action-sheet-backdrop').addClass('hidden');



                self.openAddProfileModal();



            });







            // Topbar Dropdowns



            $('#ftm-bell-btn').on('click', function (e) {
                e.stopPropagation();
                if ("Notification" in window && Notification.permission === "default") {
                    Notification.requestPermission();
                }
                $('#ftm-alerts-dropdown').toggleClass('hidden');
                $('#ftm-user-dropdown').addClass('hidden');
            });

            // Mark all alerts read
            $(document).on('click', '#ftm-btn-mark-all-alerts-read', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.api('alerts/mark-read', 'POST', { all: true }).catch(() => { });
                self.api('notifications/mark-read', 'POST', { all: true }).catch(() => { });
                $('#ftm-header-alert-badge').text('0').addClass('hidden');
                $('#ftm-dropdown-alerts-list').html('<div class="ftm-text-muted ftm-text-sm" style="padding:14px; text-align:center;">All alerts marked as read! </div>');
                $('#ftm-widget-alerts-list').html('<div class="ftm-text-muted ftm-text-sm" style="padding:10px 0;">No urgent alerts right now! </div>');
            });

            // Mark single alert read / dismissed
            $(document).on('click', '.ftm-alert-dismiss-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const alertId = $(this).data('alert-id');
                const $item = $(this).closest('.ftm-alert-item');
                $item.fadeOut(150, function () {
                    $(this).remove();
                    const remaining = $('#ftm-dropdown-alerts-list .ftm-alert-item').length;
                    if (remaining <= 0) {
                        $('#ftm-header-alert-badge').text('0').addClass('hidden');
                        $('#ftm-dropdown-alerts-list').html('<div class="ftm-text-muted ftm-text-sm" style="padding:14px; text-align:center;">All team projects are on track! </div>');
                        $('#ftm-widget-alerts-list').html('<div class="ftm-text-muted ftm-text-sm" style="padding:10px 0;">No urgent alerts right now! </div>');
                    } else {
                        $('#ftm-header-alert-badge').text(remaining).removeClass('hidden');
                    }
                });
                self.api('alerts/mark-read', 'POST', { id: alertId }).catch(() => { });
                self.api('notifications/mark-read', 'POST', { id: alertId }).catch(() => { });
            });







            $('#ftm-user-btn').on('click', function (e) {



                e.stopPropagation();



                $('#ftm-user-dropdown').toggleClass('hidden');



                $('#ftm-alerts-dropdown').addClass('hidden');



            });







            $(document).on('click', function (e) {



                if (!$(e.target).closest('#ftm-alerts-dropdown, #ftm-bell-btn, #ftm-user-dropdown, #ftm-user-btn').length) {



                    $('#ftm-alerts-dropdown, #ftm-user-dropdown').addClass('hidden');



                }



            });







            // Top 7 Status Cards Click Filter



            $('.ftm-stat-card, .ftm-stat-link').on('click', function (e) {



                e.preventDefault();



                e.stopPropagation();



                const status = $(this).data('filter-status');



                if (status !== undefined) {



                    $('.ftm-stat-card').removeClass('active-filter');



                    $(this).closest('.ftm-stat-card').addClass('active-filter');



                    self.state.status = status;



                    $('#ftm-filter-status').val(status);



                    self.state.page = 1;



                    self.loadProjects();



                }



            });







            // Right Sidebar Drawer Toggle & Close



            $('#ftm-right-sidebar-toggle-btn').on('click', function (e) {



                e.stopPropagation();



                $('#ftm-right-sidebar').toggleClass('open');



                $('#ftm-right-sidebar-backdrop').toggleClass('hidden', !$('#ftm-right-sidebar').hasClass('open'));



            });







            $('#ftm-close-right-sidebar-btn, #ftm-right-sidebar-backdrop').on('click', function () {



                $('#ftm-right-sidebar').removeClass('open');



                $('#ftm-right-sidebar-backdrop').addClass('hidden');



            });







            // Right Sidebar Quick Stat click filter



            $(document).on('click', '.ftm-quick-stat-item', function () {



                const filter = $(this).data('filter');



                self.state.viewFilter = filter;



                $('#ftm-filter-view').val(filter);



                self.state.page = 1;



                self.loadProjects();



                $('#ftm-right-sidebar').removeClass('open');



                $('#ftm-right-sidebar-backdrop').addClass('hidden');



            });







            // Right Sidebar & Header Alert click filter & modal opener



            $(document).on('click', '.ftm-alert-item', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $item = $(this);
                const projectId = $item.attr('data-project-id') || $item.data('project-id');
                const alertId = $item.attr('data-alert-id') || $item.data('alert-id') || projectId;
                const filter = $item.attr('data-filter') || $item.data('filter');

                // Mark this alert as read & remove from UI immediately
                if (alertId) {
                    self.api('alerts/mark-read', 'POST', { id: alertId }).catch(() => { });
                    self.api('notifications/mark-read', 'POST', { id: alertId }).catch(() => { });
                    $item.remove();
                    const remaining = $('#ftm-dropdown-alerts-list .ftm-alert-item').length;
                    if (remaining <= 0) {
                        $('#ftm-header-alert-badge').text('0').addClass('hidden');
                        $('#ftm-dropdown-alerts-list').html('<div class="ftm-text-muted ftm-text-sm" style="padding:14px; text-align:center;">All alerts marked as read! </div>');
                        $('#ftm-widget-alerts-list').html('<div class="ftm-text-muted ftm-text-sm" style="padding:10px 0;">No urgent alerts right now! </div>');
                    } else {
                        $('#ftm-header-alert-badge').text(remaining).removeClass('hidden');
                    }
                }

                $('#ftm-alerts-dropdown').addClass('hidden');
                $('#ftm-right-sidebar').removeClass('open');
                $('#ftm-right-sidebar-backdrop').addClass('hidden');

                // Switch to dashboard view
                window.location.hash = '#dashboard';
                self.switchView('dashboard');

                if (projectId && parseInt(projectId) > 0) {
                    self.openProjectDetailsModal(parseInt(projectId));
                    self.state.search = '';
                    self.state.status = 'all';
                    self.state.viewFilter = '';



                    $('#ftm-filter-search').val('');



                    $('#ftm-filter-status').val('all');



                    self.state.page = 1;



                    self.loadProjects();



                } else if (filter) {



                    if (filter === 'revision') {



                        self.state.status = 'revision';



                        $('#ftm-filter-status').val('revision');



                    } else if (filter === 'due_today' || filter === 'overdue') {



                        self.state.viewFilter = filter;



                    }



                    self.state.page = 1;



                    self.loadProjects();



                }



            });







            // Search input (debounced)



            let searchTimer;



            $('#ftm-filter-search').on('input', function () {



                clearTimeout(searchTimer);



                const val = $(this).val().trim();



                searchTimer = setTimeout(() => {



                    self.state.search = val;



                    self.state.page = 1;



                    self.loadProjects();



                }, 300);



            });







            // Filter dropdown changes



            $('#ftm-filter-client').on('change', function () {
                self.state.client = $(this).val();
                self.state.page = 1;
                self.loadProjects();
                self.loadStats();
            });
            $('#ftm-filter-team').on('change', function () {



                self.state.team = $(this).val();

                self.populateMemberFilter(self.state.team);

                self.state.page = 1;



                self.loadProjects();



                self.loadStats();



            });







            const syncActiveKpiCard = (statusVal) => {
                $('.ftm-refb-kpi-card.ftm-kpi-clickable').removeClass('ftm-kpi-active');
                if (statusVal && statusVal !== 'all') {
                    $(`.ftm-refb-kpi-card.ftm-kpi-clickable[data-kpi-filter="${statusVal}"]`).addClass('ftm-kpi-active');
                } else {
                    $('#ftm-kpi-card-total').addClass('ftm-kpi-active');
                }
            };

            $('#ftm-filter-status').on('change', function () {
                self.state.status = $(this).val();
                self.state.page = 1;
                syncActiveKpiCard(self.state.status);
                self.loadProjects();
                self.loadStats();
            });

            $(document).off('click', '.ftm-refb-kpi-card.ftm-kpi-clickable').on('click', '.ftm-refb-kpi-card.ftm-kpi-clickable', function () {
                const targetFilter = $(this).data('kpi-filter') || 'all';
                const next = (targetFilter !== 'all' && self.state.status === targetFilter) ? 'all' : targetFilter;
                $('#ftm-filter-status').val(next).trigger('change');
            });







            $('#ftm-filter-profile').on('change', function () {
                self.state.profileId = parseInt($(this).val()) || 0;
                self.state.page = 1;
                self.loadProjects();
                self.loadStats();
            });







            $('#ftm-filter-member').off('change').on('change', function () {



                self.state.memberId = parseInt($(this).val()) || 0;



                self.state.page = 1;



                self.loadProjects();



                self.loadStats();



            });







            $('#ftm-filter-view').on('change', function () {



                self.state.viewFilter = $(this).val();



                self.state.page = 1;
                self.loadProjects();
                self.loadStats();
            });

            $('#ftm-filter-month').on('change', function () {
                self.state.month = $(this).val();
                self.state.page = 1;
                self.loadProjects();
                self.loadStats();
            });

            $('#ftm-filter-per-page').on('change', function () {
                self.state.perPage = parseInt($(this).val()) || 50;
                self.state.page = 1;
                self.loadProjects();
            });







            // Centered Date Range Modal Trigger & Handler (Mobile-First Architecture)



            let tempStartDate = self.state.startDate || '';



            let tempEndDate = self.state.endDate || '';



            let tempPreset = 'all';







            $('#ftm-btn-date-range-toggle').on('click', function (e) {



                e.preventDefault();



                e.stopPropagation();



                $('#ftm-modal-date-range').removeClass('hidden');



                $('body').addClass('ftm-modal-open');



                // Sync inputs with current state



                $('#ftm-modal-date-start').val(self.state.startDate || '');



                $('#ftm-modal-date-end').val(self.state.endDate || '');



            });







            // Date Range Modal Close



            $('#ftm-date-range-modal-close, #ftm-btn-date-range-cancel').on('click', function (e) {



                e.preventDefault();



                $('#ftm-modal-date-range').addClass('hidden');



                $('body').removeClass('ftm-modal-open');



            });







            $('#ftm-modal-date-range').on('click', function (e) {



                if ($(e.target).is('#ftm-modal-date-range')) {



                    $('#ftm-modal-date-range').addClass('hidden');



                    $('body').removeClass('ftm-modal-open');



                }



            });







            // Mode Tabs (Quick Select vs Custom Range)



            $('.ftm-date-tab-btn').on('click', function (e) {



                e.preventDefault();



                $('.ftm-date-tab-btn').removeClass('active');



                $(this).addClass('active');



                const tab = $(this).data('tab');



                if (tab === 'quick') {



                    $('#ftm-date-panel-quick').removeClass('hidden');



                    $('#ftm-date-panel-custom').addClass('hidden');



                } else {



                    $('#ftm-date-panel-custom').removeClass('hidden');



                    $('#ftm-date-panel-quick').addClass('hidden');



                }



            });







            // Quick Preset Selection



            $('.ftm-date-preset-row').on('click', function (e) {



                e.preventDefault();



                $('.ftm-date-preset-row').removeClass('active');



                $(this).addClass('active');



                const preset = $(this).data('preset');



                tempPreset = preset;







                const today = new Date();



                const yyyy = today.getFullYear();



                const mm = String(today.getMonth() + 1).padStart(2, '0');



                const dd = String(today.getDate()).padStart(2, '0');



                const todayStr = `${yyyy}-${mm}-${dd}`;







                const formatDate = (d) => {



                    const y = d.getFullYear();



                    const m = String(d.getMonth() + 1).padStart(2, '0');



                    const day = String(d.getDate()).padStart(2, '0');



                    return `${y}-${m}-${day}`;



                };







                if (preset === 'all') {



                    tempStartDate = '';



                    tempEndDate = '';



                } else if (preset === 'today') {



                    tempStartDate = todayStr;



                    tempEndDate = todayStr;



                } else if (preset === 'yesterday') {



                    const yest = new Date(today);



                    yest.setDate(today.getDate() - 1);



                    tempStartDate = formatDate(yest);



                    tempEndDate = formatDate(yest);



                } else if (preset === 'last_7_days') {



                    const past7 = new Date(today);



                    past7.setDate(today.getDate() - 6);



                    tempStartDate = formatDate(past7);



                    tempEndDate = todayStr;



                } else if (preset === 'last_30_days') {



                    const past30 = new Date(today);



                    past30.setDate(today.getDate() - 29);



                    tempStartDate = formatDate(past30);



                    tempEndDate = todayStr;



                } else if (preset === 'this_month') {



                    tempStartDate = `${yyyy}-${mm}-01`;



                    const lastDayOfMonth = new Date(yyyy, today.getMonth() + 1, 0).getDate();



                    tempEndDate = `${yyyy}-${mm}-${String(lastDayOfMonth).padStart(2, '0')}`;



                } else if (preset === 'last_month') {



                    const prevMonthLast = new Date(yyyy, today.getMonth(), 0);



                    const prevMonthFirst = new Date(prevMonthLast.getFullYear(), prevMonthLast.getMonth(), 1);



                    tempStartDate = formatDate(prevMonthFirst);



                    tempEndDate = formatDate(prevMonthLast);



                } else if (preset === 'this_year') {



                    tempStartDate = `${yyyy}-01-01`;



                    tempEndDate = `${yyyy}-12-31`;



                }







                $('#ftm-modal-date-start').val(tempStartDate);



                $('#ftm-modal-date-end').val(tempEndDate);



            });







            // Apply Date Range



            $('#ftm-btn-date-range-apply').on('click', function (e) {



                e.preventDefault();



                const activeTab = $('.ftm-date-tab-btn.active').data('tab');



                let start = '';



                let end = '';



                let label = 'Date Range';







                if (activeTab === 'custom') {



                    start = $('#ftm-modal-date-start').val() || '';



                    end = $('#ftm-modal-date-end').val() || '';



                    if (start && end) {



                        label = `${start} – ${end}`;



                    } else if (start) {



                        label = `From ${start}`;



                    } else if (end) {



                        label = `Until ${end}`;



                    } else {



                        label = 'Date Range';



                    }



                } else {



                    start = tempStartDate;



                    end = tempEndDate;



                    const presetLabel = $('.ftm-date-preset-row.active').text();



                    label = (presetLabel === 'All Time') ? 'Date Range' : presetLabel;



                }







                self.state.startDate = start;



                self.state.endDate = end;



                $('#ftm-date-range-label').text(label);







                $('#ftm-modal-date-range').addClass('hidden');



                $('body').removeClass('ftm-modal-open');



                self.state.page = 1;
                self.loadProjects();
                self.loadStats();
            });







            // Sorting



            $('#ftm-sort-select').on('change', function () {



                self.state.sort = $(this).val();



                self.state.page = 1;
                self.loadProjects();
                self.loadStats();
            });







            // Team filter inside Members View



            $('#ftm-members-team-filter, #ftm-members-show-inactive').on('change', function () {



                self.loadTeamMembersView($('#ftm-members-team-filter').val() || 'all');



            });

            $('#ftm-btn-add-member').on('click', function (e) {
                e.preventDefault();
                self.openManageMemberModal(null);
            });

            $('#ftm-btn-open-isolation-vault').on('click', function (e) {
                e.preventDefault();
                self.openIsolationVaultModal();
            });

            $('#ftm-btn-restore-wphorizon').on('click', function (e) {
                e.preventDefault();
                self.restoreIsolatedTeam('WpHorizon');
            });

            $('#ftm-vault-search').on('input', function () {
                const term = $(this).val().toLowerCase().trim();
                self.filterVaultProjects(term);
            });

            $('#ftm-form-manage-member').on('submit', function (e) {
                e.preventDefault();
                self.submitManageMemberForm();
            });

            $('#ftm-form-leave-quota').on('submit', function (e) {
                e.preventDefault();
                self.submitLeaveQuotaForm();
            });







            // Modals: Close Handlers (Supports Stacked Modals)



            $(document).on('click', '.ftm-modal-close-btn', function (e) {



                e.preventDefault();



                $(this).closest('.ftm-modal-backdrop').addClass('hidden');



            });







            $(document).on('click', '.ftm-modal-backdrop', function (e) {



                if (e.target === this) {



                    $(this).addClass('hidden');



                }



            });







            // Modals: Add Project



            $('.ftm-open-modal-add-project, #ftm-btn-add-project').on('click', () => self.openAddProjectModal());

            // View All / Show Less on Top Contributors Widget
            $(document).off('click', '#ftm-link-top-contributors-all').on('click', '#ftm-link-top-contributors-all', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!self.dailyTopContributors || self.dailyTopContributors.length === 0) return;
                self.showingAllDailyContributors = !self.showingAllDailyContributors;
                self.renderContributorsList(self.showingAllDailyContributors);
                $(this).text(self.showingAllDailyContributors ? 'Show Top 4' : `View All (${self.dailyTopContributors.length})`);
            });

            // Click contributor name to navigate to member profile
            $(document).off('click', '.ftm-contributor-name').on('click', '.ftm-contributor-name', function (e) {
                e.preventDefault();
                const mid = $(this).data('member-id');
                if (mid) {
                    window.location.hash = `#member-profile?id=${mid}`;
                }
            });



            $('#ftm-project-form').on('submit', function (e) {
                e.preventDefault();
                self.saveProjectForm();
            });

            $(document).off('click', '#ftm-btn-save-project').on('click', '#ftm-btn-save-project', function (e) {
                e.preventDefault();
                self.saveProjectForm();
            });

            $('#ftm-form-btn-reset-ext').on('click', function () {
                $('#ftm-form-extension-count').val(0);
                $('#ftm-form-extension-days').val(0);
                $('#ftm-form-extension-reason').val('');
                self.updateFormOnTimeStatus();
                self.toast('Extension counters reset to 0. Click Save Project to save changes.', 'info');
            });

            $('#ftm-form-extension-count, #ftm-form-status').on('input change', function () {
                self.updateFormOnTimeStatus();
            });







            // Financial calculation live listener (Supports Base + Addon, Fee, and Live Visualizer Bar)



            $('#ftm-form-base-value, #ftm-form-addon-value').on('input', function () {



                const base = parseFloat($('#ftm-form-base-value').val()) || 0;



                const addon = parseFloat($('#ftm-form-addon-value').val()) || 0;



                if (base > 0 || addon > 0) {



                    $('#ftm-form-gross-value').val((base + addon).toFixed(2));



                }



                $('#ftm-form-gross-value').trigger('input');



            });







                $('#ftm-form-gross-value, #ftm-form-fee-percent').on('input change', function () {



                    const gross = parseFloat($('#ftm-form-gross-value').val()) || 0;
                    const feeInput = $('#ftm-form-fee-percent').val();
                    let fee = 20;
                    if (feeInput !== '' && feeInput !== null && !isNaN(parseFloat(feeInput))) {
                        fee = Math.max(0, parseFloat(feeInput));
                    }

                    const feeAmount = gross * (fee / 100);
                    const net = Math.max(0, gross - feeAmount);

                    $('#ftm-form-net-value').val(net.toFixed(2));

                    const netPct = gross > 0 ? (net / gross) * 100 : Math.max(0, 100 - fee);
                    const feePct = gross > 0 ? (feeAmount / gross) * 100 : fee;

                    $('#ftm-visual-net-bar').css('width', Math.min(100, Math.max(0, netPct)) + '%');
                    $('#ftm-visual-fee-bar').css('width', Math.min(100, Math.max(0, feePct)) + '%');

                    $('#ftm-visual-net-text').text(`● Net Payout: $${net.toFixed(2)} (${netPct.toFixed(0)}%)`);



                    $('#ftm-visual-fee-text').text(`● Marketplace Fee: $${feeAmount.toFixed(2)} (${feePct.toFixed(0)}%)`);

                    $('#ftm-pricing-formula-hint').text(`Net = Total Gross - ${fee}% Fee`);



                });







                // Order ID Auto-formatting



                $('#ftm-form-order-id').on('input blur', function () {



                    let val = $(this).val().trim().toUpperCase();



                    if (val && !val.startsWith('#')) {



                        val = '#' + val;



                    }



                    if (val.startsWith('#') && !val.startsWith('#FO') && !val.startsWith('#NP')) {



                        val = '#FO' + val.substring(1);



                    }



                    $(this).val(val);



                });







                // Repeat Client Intelligence



                $('#ftm-form-client').on('input change', function () {



                    const clientName = $(this).val().trim().toLowerCase();



                    const badge = $('#ftm-client-repeat-badge');



                    if (!clientName || clientName.length < 2) {



                        badge.addClass('hidden').html('');



                        return;



                    }



                    const checkRepeat = (projectsList) => {



                        const currentEditingId = parseInt($('#ftm-form-project-id').val()) || 0;



                        const matches = (projectsList || []).filter(p => {



                            if (currentEditingId && parseInt(p.id) === currentEditingId) {



                                return false; // Crucial fix: Do not count the current project itself as a repeat order!



                            }



                            return (p.client_name || '').toLowerCase() === clientName;



                        });



                        if (matches.length > 0) {



                            const count = matches.length;



                            const totalNet = matches.reduce((acc, p) => acc + (parseFloat(p.net_delivery_value) || 0), 0);



                            const lastProj = matches[0];



                            badge.removeClass('hidden').html(`



                            <div>



                                <strong>⭐ Repeat Client Detected:</strong> ${count} previous ${count === 1 ? 'order' : 'orders'} ($${totalNet.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} lifetime net)



                            </div>



                            <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Verified History</span>



                        `);



                            if (!$('#ftm-form-profile').val() && lastProj.fiverr_profile_id) {



                                $('#ftm-form-profile').val(lastProj.fiverr_profile_id);



                            }



                        } else {



                            badge.addClass('hidden').html('');



                        }



                    };







                    if (self.state.projects && self.state.projects.length) {



                        checkRepeat(self.state.projects);



                    } else {



                        self.api('projects?per_page=100').then(res => {



                            self.state.projects = res.items || [];



                            checkRepeat(self.state.projects);



                        });



                    }



                });







                // Deadline Live Countdown



                $('#ftm-form-delivery-date').on('input change', function () {



                    self.updateProjectDeadlineCountdown($(this).val());



                });







                // Assignee Filter & Toolbar



                $('#ftm-assignee-search').on('input', function () {



                    const q = $(this).val().toLowerCase().trim();



                    $('#ftm-form-assignees-list .ftm-assignee-checkbox-item').each(function () {



                        const text = $(this).text().toLowerCase();



                        $(this).toggle(text.includes(q));



                    });



                });







                $('#ftm-btn-assign-select-all').on('click', function (e) {



                    e.preventDefault();



                    $('#ftm-form-assignees-list .ftm-assignee-checkbox-item:visible input[type="checkbox"]').prop('checked', true);



                    $('#ftm-form-assignees-list .ftm-assignee-checkbox-item:visible').addClass('checked');



                    self.updateFormAssigneesSummary();



                });







                $('#ftm-btn-assign-clear').on('click', function (e) {



                    e.preventDefault();



                    $('#ftm-form-assignees-list input[type="checkbox"]').prop('checked', false);



                    $('#ftm-form-assignees-list .ftm-assignee-checkbox-item').removeClass('checked');



                    self.updateFormAssigneesSummary();



                });







                // Onboarding Profile Setup Banner Button



                $('#ftm-btn-open-onboarding-profile').on('click', function (e) {



                    e.preventDefault();



                    const curUserId = window.ftmData && window.ftmData.current_user ? window.ftmData.current_user.id : 0;



                    self.openMemberProfileEdit(curUserId);



                    setTimeout(() => {



                        $('#ftm-edit-profile-email').focus();



                    }, 200);



                });







                // Daily Standup Handlers



                $('#ftm-btn-new-daily-standup').on('click', function (e) {



                    e.preventDefault();



                    window.location.hash = '#daily-reports';



                    setTimeout(() => {

                        $('#ftm-ws-proj-picker-btn').trigger('click');

                    }, 150);



                });







                $('#ftm-btn-open-standup-modal').on('click', function (e) {



                    e.preventDefault();



                    self.openDailyStandupModal();



                });







                $('#ftm-btn-add-standup-project').on('click', function (e) {



                    e.preventDefault();



                    self.addStandupProjectItem();



                });







                $('#ftm-standup-form').on('submit', function (e) {



                    e.preventDefault();



                    self.saveDailyStandupForm();



                });

                // Tasks & Milestones Events
                $(document).off("click", "#ftm-details-add-task-btn").on("click", "#ftm-details-add-task-btn", function (e) {
                    e.preventDefault();
                    self.openAddTaskModal(self.state.activeProjectId, null);
                });

                $(document).off("click", "#ftm-profile-btn-assign-task, #ftm-profile-tab-assign-btn").on("click", "#ftm-profile-btn-assign-task, #ftm-profile-tab-assign-btn", function (e) {
                    e.preventDefault();
                    self.openAddTaskModal(null, self.state.selectedMemberProfileId);
                });

                $(document).off("click", ".ftm-task-preset-btn").on("click", ".ftm-task-preset-btn", function (e) {
                    e.preventDefault();
                    const title = $(this).data("title");
                    const priority = $(this).data("priority") || "medium";
                    $("#ftm-form-task-title-input").val(title);
                    $("#ftm-form-task-priority").val(priority);

                    const now = new Date();
                    if (title.indexOf("Initial") !== -1) {
                        now.setHours(now.getHours() + 5);
                        $("#ftm-form-task-deadline").val(now.toISOString().slice(0, 16));
                    } else if (title.indexOf("1st Draft") !== -1) {
                        now.setDate(now.getDate() + 3);
                        $("#ftm-form-task-deadline").val(now.toISOString().slice(0, 16));
                    } else if (title.indexOf("Fixing") !== -1) {
                        $("#ftm-form-task-deadline").val("");
                    }
                });

                $("#ftm-task-form").off("submit").on("submit", function (e) {
                    e.preventDefault();
                    self.saveTaskForm();
                });

                $(document).off("change", ".ftm-task-toggle-check").on("change", ".ftm-task-toggle-check", function () {
                    const tid = $(this).data("task-id");
                    self.toggleTaskStatus(tid, $(this).is(":checked"));
                });

                $(document).off("click", ".ftm-task-delete-btn").on("click", ".ftm-task-delete-btn", function (e) {
                    e.preventDefault();
                    const tid = $(this).data("task-id");
                    if (confirm("Are you sure you want to delete this task milestone?")) {
                        self.deleteTask(tid);
                    }
                });

                $(document).off("click", "#ftm-project-open-chat-btn").on("click", "#ftm-project-open-chat-btn", function (e) {
                    e.preventDefault();
                    const pid = self.state.activeProjectId;
                    if (!pid) return;
                    $("#ftm-modal-details").addClass("hidden");
                    if (window.ftmMessages && typeof window.ftmMessages.openProject === "function") {
                        window.ftmMessages.openProject(pid);
                    } else {
                        window.location.hash = "#messages";
                    }
                });

                $(document).off("click", "#ftm-details-btn-extend-delivery").on("click", "#ftm-details-btn-extend-delivery", function (e) {
                    e.preventDefault();
                    const pid = self.state.activeProjectId;
                    if (!pid) return;
                    const orderId = $('#ftm-details-order-id-badge').text() || '';
                    const client = $('#ftm-details-client-name').text() || '';
                    const deadline = $('#ftm-details-delivery-date').val() || '';
                    $('#ftm-extend-project-id').val(pid);
                    $('#ftm-extend-modal-sub').text(`Order ${orderId} · Client: ${client}`);
                    $('#ftm-extend-reason-input').val('');
                    self.setupExtendDatePicker(pid, deadline);
                    $('#ftm-modal-extend-delivery').removeClass('hidden');
                });

                // Leaves & Attendance Events
                $("#ftm-btn-manage-leaves").on("click", function (e) {
                    e.preventDefault();
                    self.openManageLeavesModal();
                });

                $("#ftm-btn-apply-leave-header, #ftm-btn-apply-leave-inside").on("click", function (e) {
                    e.preventDefault();
                    self.openApplyLeaveModal();
                });

                $("#ftm-leave-request-type").on("change", function () {
                    const isSwitch = $(this).val() === "day_off_switch";
                    $("#ftm-leave-type-group, #ftm-leave-dates-row").toggleClass("hidden", isSwitch);
                    $("#ftm-switch-dates-row").toggleClass("hidden", !isSwitch);
                    $("#ftm-leave-start-date, #ftm-leave-end-date").prop("required", !isSwitch);
                    $("#ftm-switch-from-date, #ftm-switch-to-date").prop("required", isSwitch);
                });

                $("#ftm-form-leave-quota").off("submit").on("submit", function (e) {
                    e.preventDefault();
                    self.submitLeaveQuotaForm();
                });

                $(document).off("click", ".ftm-btn-adjust-quota").on("click", ".ftm-btn-adjust-quota", function (e) {
                    e.preventDefault();
                    const uid = $(this).data("uid");
                    const name = $(this).data("name");
                    const quota = $(this).data("quota");
                    const adj = $(this).data("adj");
                    const day = $(this).data("day");
                    const note = $(this).data("note");
                    self.openLeaveQuotaModal(uid, name, quota, adj, day, note);
                });

                $(document).off("click", ".ftm-btn-apply-member-leave").on("click", ".ftm-btn-apply-member-leave", function (e) {
                    e.preventDefault();
                    const uid = $(this).data("uid");
                    const name = $(this).data("name");
                    self.openApplyLeaveModal(uid, name);
                });

                $(document).off("input", "#ftm-leave-member-search").on("input", "#ftm-leave-member-search", function () {
                    const q = ($(this).val() || '').toLowerCase().trim();
                    $("#ftm-leave-member-id option").each(function () {
                        if (!$(this).val()) return;
                        const txt = $(this).text().toLowerCase();
                        $(this).toggle(txt.includes(q));
                    });
                });

                $("#ftm-form-apply-leave").on("submit", function (e) {
                    e.preventDefault();
                    self.submitLeaveForm();
                });

                $("#ftm-modal-manage-leaves #ftm-leaves-filter-status").on("change", function () {
                    self.loadLeavesList();
                });

                $("#ftm-leaves-refresh-btn").on("click", function (e) {
                    e.preventDefault();
                    self.loadLeavesList();
                });

                // Export CSV & Print Report
                $("#ftm-btn-export-team-report").on("click", function (e) {
                    e.preventDefault();
                    const d = $("#ftm-team-report-date").val() || new Date().toISOString().split("T")[0];
                    const t = $("#ftm-team-report-team-filter").val() || "all";
                    window.open((window.ftmData ? window.ftmData.root : "/wp-json/") + `ftm/v1/attendance/export?date=${d}&team=${encodeURIComponent(t)}`, "_blank");
                });

                $("#ftm-btn-print-team-daily").on("click", function (e) {
                    e.preventDefault();
                    window.print();
                });

                // Attendance & Leaves View Controls
                $("#ftm-view-att-date, #ftm-view-att-team").on("change", function () {
                    self.loadAttendanceView($("#ftm-view-att-date").val(), $("#ftm-view-att-team").val());
                });
                $("#ftm-view-att-btn-refresh").on("click", function (e) {
                    e.preventDefault();
                    self.loadAttendanceView($("#ftm-view-att-date").val(), $("#ftm-view-att-team").val());
                });
                $("#ftm-att-btn-apply-leave").on("click", function (e) {
                    e.preventDefault();
                    self.openApplyLeaveModal();
                });
                $("#ftm-att-btn-export-csv").on("click", function (e) {
                    e.preventDefault();
                    const d = $("#ftm-view-att-date").val() || new Date().toISOString().split("T")[0];
                    const t = $("#ftm-view-att-team").val() || "all";
                    window.open((window.ftmData ? window.ftmData.root : "/wp-json/") + `ftm/v1/attendance/export?date=${d}&team=${encodeURIComponent(t)}`, "_blank");
                });
                $("#ftm-att-btn-print").on("click", function (e) {
                    e.preventDefault();
                    window.print();
                });







                // Agency Broadcast Notice Handlers



                $('#ftm-btn-broadcast-notice, #ftm-btn-open-notice-modal').on('click', function (e) {



                    e.preventDefault();



                    self.openBroadcastNoticeModal();



                });







                $('#ftm-notice-target-type').on('change', function () {



                    const val = $(this).val();



                    $('#ftm-notice-team-group').toggleClass('hidden', val !== 'team');



                    $('#ftm-notice-member-group').toggleClass('hidden', val !== 'member');



                });







                $('#ftm-notice-form').on('submit', function (e) {



                    self.submitBroadcastNotice(e);



                });







                $('#ftm-notice-type-filters .ftm-pill-tab').on('click', function (e) {



                    e.preventDefault();



                    $('#ftm-notice-type-filters .ftm-pill-tab').removeClass('active');



                    $(this).addClass('active');



                    self.state.noticeFilter = $(this).data('type') || 'all';



                    self.renderAgencyNotices();



                });







                $(document).on('click', '.ftm-notice-pin-btn', function (e) {



                    e.preventDefault();



                    const id = $(this).data('id');



                    if (id) self.toggleNoticePin(id);



                });







                $(document).on('click', '.ftm-notice-delete-btn', function (e) {



                    e.preventDefault();



                    const id = $(this).data('id');



                    if (id) self.deleteNotice(id);



                });







                // Member Profile Edit Form Submit



                $('#ftm-form-add-milestone').on('submit', function (e) {
                    e.preventDefault();
                    const targetId = $('#ftm-milestone-user-id').val() || self.state.selectedMemberProfileId;
                    const title = $('#ftm-milestone-title').val().trim();
                    const tag = $('#ftm-milestone-tag').val();
                    const date = $('#ftm-milestone-date').val().trim();
                    const color = $('#ftm-milestone-color').val();
                    const desc = $('#ftm-milestone-desc').val().trim();

                    const saveBtn = $('#ftm-btn-save-milestone');
                    const origText = saveBtn.text();
                    saveBtn.prop('disabled', true).text('Saving...');

                    self.api(`team-members/${targetId}/milestones`, 'POST', {
                        title: title,
                        tag: tag,
                        date: date,
                        color: color,
                        description: desc
                    }).then(res => {
                        saveBtn.prop('disabled', false).text(origText);
                        $('#ftm-modal-add-milestone').addClass('hidden');
                        self.toast(res.message || 'Career milestone added successfully!', 'success');
                        self.loadMemberProfile(targetId);
                    }).catch(err => {
                        saveBtn.prop('disabled', false).text(origText);
                        self.toast('Error saving milestone: ' + (err.message || 'Server error'), 'error');
                    });
                });

                $('#ftm-form-edit-member-profile').on('submit', function (e) {



                    e.preventDefault();



                    self.saveMemberProfileEdit();



                });







                // Standup Update & Resubmit Button Handler



                $(document).on('click', '.ftm-btn-edit-standup', function (e) {



                    e.preventDefault();



                    const repId = parseInt($(this).data('report-id'));



                    if (repId) {



                        self.openDailyStandupModalForEdit(repId);



                    }



                });







                // Team Leader Forward Handler & Filters



                $('#ftm-btn-forward-report').on('click', function (e) {



                    e.preventDefault();



                    self.forwardTeamDailyReport();



                });







                $('#ftm-team-report-date, #ftm-team-report-team-filter').on('change', function () {



                    self.loadTeamDailyReportView();



                });







                // Overview Month Change & Sync
                $('#ftm-overview-month-select').on('change', function () {
                    self.loadOverviewView($(this).val());
                });

                $('#ftm-overview-refresh-btn').on('click', function (e) {
                    e.preventDefault();
                    self.loadOverviewView($('#ftm-overview-month-select').val());
                });

                $(document).on('click', '.ftm-ov-team-pill', function (e) {
                    e.preventDefault();
                    self.filterOverviewSpecialists($(this).data('team'));
                });

                $('#ftm-overview-search-input').on('input', function () {
                    self.searchOverviewSpecialists($(this).val());
                });

                $(document).on('click', '.ftm-ov-view-member', function (e) {
                    e.preventDefault();
                    const mid = parseInt($(this).data('member-id'), 10);
                    const month = $('#ftm-overview-month-select').val();
                    if (mid && typeof self.openMemberActivityModal === 'function') {
                        self.openMemberActivityModal(mid, month);
                    }
                });

                $(document).on('click', '.ftm-ov-project-link', function (e) {
                    e.preventDefault();
                    const pid = parseInt($(this).data('project-id'), 10);
                    if (pid && typeof self.openEditProjectModal === 'function') {
                        self.openEditProjectModal(pid);
                    }
                });

                // Monthly Report Filter & Export



                $('#ftm-monthly-select-month').on('change', function () {



                    self.loadMonthlyReportsView($(this).val());



                });







                $('#ftm-btn-export-monthly-csv').on('click', function (e) {



                    e.preventDefault();



                    self.exportMonthlyCsv();



                });







                $('#ftm-btn-print-monthly').on('click', function (e) {



                    e.preventDefault();



                    self.printMonthlyExecutiveStatement();



                });







                // Navigate to Full Performance Leaderboard from Monthly Report Highlights



                $(document).on('click', '#ftm-btn-view-full-leaderboard', function (e) {



                    e.preventDefault();



                    self.switchView('leaderboard');



                    window.location.hash = '#leaderboard';



                });







                // Leaderboard Methodology Modal Triggers



                $(document).on('click', '#ftm-lb-btn-methodology', function (e) {



                    e.preventDefault();



                    $('#ftm-modal-leaderboard-methodology').removeClass('hidden');



                });







                // Leaderboard Period Switcher Tabs



                $(document).on('click', '.ftm-lb-tab', function (e) {



                    e.preventDefault();



                    const period = $(this).data('period');



                    $('.ftm-lb-tab').removeClass('active');



                    $(this).addClass('active');



                    self.state.leaderboardPeriod = period;







                    if (period === 'monthly') {



                        $('#ftm-lb-month-select').removeClass('hidden');



                        $('#ftm-lb-quarter-select').addClass('hidden');



                        $('#ftm-lb-period-badge').addClass('hidden');



                        self.state.leaderboardPeriodKey = $('#ftm-lb-month-select').val();



                    } else if (period === 'quarterly') {



                        $('#ftm-lb-month-select').addClass('hidden');



                        $('#ftm-lb-quarter-select').removeClass('hidden');



                        $('#ftm-lb-period-badge').addClass('hidden');



                        self.state.leaderboardPeriodKey = $('#ftm-lb-quarter-select').val();



                    } else {



                        $('#ftm-lb-month-select').addClass('hidden');



                        $('#ftm-lb-quarter-select').addClass('hidden');



                        $('#ftm-lb-period-badge').removeClass('hidden');



                        $('#ftm-lb-period-text').text('All Historical Records (Jan 2026 – Present)');



                        self.state.leaderboardPeriodKey = 'all_time';



                    }







                    self.loadLeaderboardView(period, self.state.leaderboardPeriodKey, self.state.leaderboardTeam, self.state.leaderboardSort);



                });







                // Leaderboard Dropdown Selectors



                $('#ftm-lb-month-select').on('change', function () {



                    self.state.leaderboardPeriodKey = $(this).val();



                    self.loadLeaderboardView('monthly', $(this).val(), self.state.leaderboardTeam, self.state.leaderboardSort);



                });







                $('#ftm-lb-quarter-select').on('change', function () {



                    self.state.leaderboardPeriodKey = $(this).val();



                    self.loadLeaderboardView('quarterly', $(this).val(), self.state.leaderboardTeam, self.state.leaderboardSort);



                });







                // Leaderboard Team Filter Pills



                $(document).on('click', '#ftm-leaderboard-team-pills .ftm-lb-pill', function (e) {



                    e.preventDefault();



                    $('#ftm-leaderboard-team-pills .ftm-lb-pill').removeClass('active');



                    $(this).addClass('active');



                    const team = $(this).data('team');



                    self.state.leaderboardTeam = team;



                    self.filterLeaderboardData();



                });







                // Leaderboard Sort Dropdown



                $('#ftm-leaderboard-sort').on('change', function () {



                    self.state.leaderboardSort = $(this).val();



                    self.loadLeaderboardView(self.state.leaderboardPeriod, self.state.leaderboardPeriodKey, self.state.leaderboardTeam, $(this).val());



                });







                // Leaderboard Instant Search Input



                $('#ftm-leaderboard-search').off('input').on('input', function () {



                    self.state.leaderboardSearch = $(this).val().toLowerCase().trim();



                    self.filterLeaderboardData();



                });







                // Historical CSV Import Modal Trigger & Form Submit



                $('#ftm-btn-open-import-csv').on('click', function (e) {



                    e.preventDefault();



                    $('#ftm-modal-import-csv').removeClass('hidden');



                });







                $('#ftm-form-import-csv').on('submit', function (e) {



                    e.preventDefault();



                    self.handleCsvImport();



                });







                // Team selector in Add Project form (Super Admin only)



                $('#ftm-form-team-name').on('change', function () {



                    self.populateFormMembers($(this).val());



                });







                // Modals: Add Profile



                $(document).on('click', '.ftm-open-modal-add-profile', () => self.openAddProfileModal());



                $('#ftm-profile-form').on('submit', function (e) {



                    e.preventDefault();



                    self.saveProfileForm();



                });







                // Edit Profile Button Click



                $(document).on('click', '.ftm-edit-profile-btn', function (e) {



                    e.preventDefault();



                    e.stopPropagation();



                    const id = $(this).data('id');



                    const name = $(this).data('name');



                    const url = $(this).data('url') || '';



                    const status = $(this).data('status') || 'active';



                    self.openEditProfileModal(id, name, url, status);



                });







                // Delete Profile Button Click



                $('#ftm-delete-profile-btn').on('click', function () {



                    const id = $('#ftm-form-profile-id').val();



                    const name = $('#ftm-profile-name-input').val();



                    if (id && confirm(`Are you sure you want to permanently delete profile "${name}"?`)) {



                        self.deleteProfile(id);



                    }



                });







                // Project Details Tabs



                $('.ftm-detail-tab-btn').on('click', function () {



                    $('.ftm-detail-tab-btn').removeClass('active');



                    $(this).addClass('active');



                    const tab = $(this).data('tab');



                    $('#ftm-tab-timeline, #ftm-tab-notes, #ftm-tab-dailylogs, #ftm-tab-tasks').addClass('hidden').removeClass('active');



                    $('#ftm-tab-' + tab).removeClass('hidden').addClass('active');



                });







                // Submit Note



                $('#ftm-submit-note-btn').on('click', function () {



                    const note = $('#ftm-new-note-text').val().trim();



                    if (!note) return;



                    self.addProjectNote(self.state.activeProjectId, note);



                });







                // Status change in Add/Edit modal
                $('#ftm-form-status').on('change', function () {
                    const st = $(this).val();
                    if (st === 'delivery_accepted' || st === 'delivery_submitted') {
                        if (!$('#ftm-form-delivered-date').val()) {
                            $('#ftm-form-delivered-date').val(new Date().toISOString().split('T')[0]);
                        }
                    }
                    if (st === 'delivery_accepted') {
                        $('#ftm-form-review-section').removeClass('hidden');
                    } else {
                        $('#ftm-form-review-section').addClass('hidden');
                    }
                });







                $('#ftm-form-client-reviewed').on('change', function () {



                    if ($(this).val() === 'yes') {



                        $('#ftm-form-star-rating-wrap, #ftm-form-review-feedback-wrap').removeClass('hidden');



                    } else {



                        $('#ftm-form-star-rating-wrap, #ftm-form-review-feedback-wrap').addClass('hidden');



                    }



                });







                // Status change in Details Modal
                $('#ftm-details-new-status-select').on('change', function () {
                    const st = $(this).val();
                    if (st === 'delivery_accepted') {
                        $('#ftm-details-review-section').removeClass('hidden');
                    } else {
                        $('#ftm-details-review-section').addClass('hidden');
                    }
                    if (st === 'delivery_accepted' || st === 'delivery_submitted') {
                        $('#ftm-details-settle-card').removeClass('hidden');
                        if (!$('#ftm-details-delivered-date').val()) {
                            $('#ftm-details-delivered-date').val(new Date().toISOString().split('T')[0]);
                        }
                    }
                });

                $('#ftm-details-client-reviewed').on('change', function () {
                    if ($(this).val() === 'yes') {
                        $('#ftm-details-star-rating-wrap, #ftm-details-review-feedback-wrap').removeClass('hidden');
                        const curR = parseFloat($('#ftm-details-star-rating').val());
                        if (isNaN(curR) || curR <= 0) {
                            $('#ftm-details-star-rating').val('5.0').trigger('input');
                        }
                    } else {
                        $('#ftm-details-star-rating-wrap, #ftm-details-review-feedback-wrap').addClass('hidden');
                        if (self.state.activeProjectStatus === 'delivery_accepted') {
                            $('#ftm-details-save-review-btn').trigger('click');
                        }
                    }
                });

                const collectDetailsExtraPayload = (targetStatus) => {
                    const extraData = {};
                    if (targetStatus === 'delivery_accepted' || targetStatus === 'delivery_submitted' || !$('#ftm-details-settle-card').hasClass('hidden')) {
                        extraData.delivered_date = $('#ftm-details-delivered-date').val() || null;
                        extraData.tip_date = $('#ftm-details-tip-date').val() || null;
                        const tipV = parseFloat($('#ftm-details-tip-amount').val());
                        if (!isNaN(tipV)) {
                            extraData.tip_amount = Math.max(0, tipV);
                        }
                    }
                    if (targetStatus === 'delivery_accepted') {
                        extraData.client_reviewed = $('#ftm-details-client-reviewed').val() || 'no';
                        extraData.star_rating = extraData.client_reviewed === 'yes' ? ftmRating('#ftm-details-star-rating') : 0;
                        extraData.review_feedback = extraData.client_reviewed === 'yes' ? ($('#ftm-details-review-feedback').val() || '') : '';
                    }
                    return extraData;
                };

                // Update Status in Details Modal
                $('#ftm-details-update-status-btn').off('click').on('click', function () {
                    const newStatus = $('#ftm-details-new-status-select').val();
                    const extraData = collectDetailsExtraPayload(newStatus);
                    if (self.state.activeProjectId) {
                        self.state.pendingTableStatusSelect = null;
                        self.updateProjectStatus(self.state.activeProjectId, newStatus, true, extraData);
                    }
                });

                // Save Review & Rating in Details Modal
                $(document).off('click', '#ftm-details-save-review-btn').on('click', '#ftm-details-save-review-btn', function (e) {
                    e.preventDefault();
                    const id = self.state.activeProjectId;
                    if (!id) return;
                    const selectedStatus = $('#ftm-details-new-status-select').val();
                    // If user is transitioning to Delivery Accepted from table or dropdown, commit status + review + dates/tips in one step
                    if (selectedStatus === 'delivery_accepted' && self.state.activeProjectStatus !== 'delivery_accepted') {
                        self.state.pendingTableStatusSelect = null;
                        self.updateProjectStatus(id, 'delivery_accepted', true, collectDetailsExtraPayload('delivery_accepted'));
                        return;
                    }

                    const $btn = $(this);
                    const clientReviewed = $('#ftm-details-client-reviewed').val() || 'no';
                    const starRating = clientReviewed === 'yes' ? ftmRating('#ftm-details-star-rating') : 0;
                    const reviewFeedback = clientReviewed === 'yes' ? ($('#ftm-details-review-feedback').val() || '') : '';

                    $btn.prop('disabled', true).html('<span class="ftm-spinner"></span> Saving...');
                    self.api('projects/' + id + '/review', 'POST', {
                        client_reviewed: clientReviewed,
                        star_rating: starRating,
                        review_feedback: reviewFeedback
                    }).then(res => {
                        $btn.prop('disabled', false).html('<span>💾</span> Save Review & Rating');
                        self.toast((res && res.message) || 'Review & rating saved successfully!', 'success');
                        self.openProjectDetailsModal(id);
                        self.loadProjects();
                        self.loadStats();
                    }).catch(err => {
                        $btn.prop('disabled', false).html('<span>💾</span> Save Review & Rating');
                        self.toast('Error saving review: ' + ((err && err.message) || 'Unknown error'), 'error');
                    });
                });

                // Save Delivery Date & Monthly Settlement Tips in Details Modal
                $(document).off('click', '#ftm-details-save-settle-btn').on('click', '#ftm-details-save-settle-btn', function (e) {
                    e.preventDefault();
                    const id = self.state.activeProjectId;
                    if (!id) return;
                    const selectedStatus = $('#ftm-details-new-status-select').val();
                    // If user selected Delivery Accepted or Delivery Submitted and hasn't committed status yet, commit status + dates/tips/review together
                    if ((selectedStatus === 'delivery_accepted' || selectedStatus === 'delivery_submitted') && self.state.activeProjectStatus !== selectedStatus) {
                        self.state.pendingTableStatusSelect = null;
                        self.updateProjectStatus(id, selectedStatus, true, collectDetailsExtraPayload(selectedStatus));
                        return;
                    }

                    const $btn = $(this);
                    const deliveredDate = $('#ftm-details-delivered-date').val() || null;
                    const tipDate = $('#ftm-details-tip-date').val() || null;
                    const rawTip = parseFloat($('#ftm-details-tip-amount').val());
                    const tipAmount = isNaN(rawTip) ? 0 : Math.max(0, rawTip);

                    $btn.prop('disabled', true).html('<span class="ftm-spinner"></span> Saving...');
                    self.api('projects/' + id + '/settlement', 'POST', {
                        delivered_date: deliveredDate,
                        tip_date: tipDate,
                        tip_amount: tipAmount
                    }).then(res => {
                        $btn.prop('disabled', false).html('<span>💾</span> Save Dates & Tips');
                        self.toast((res && res.message) || 'Delivery dates & tips saved!', 'success');
                        self.openProjectDetailsModal(id);
                        self.loadProjects();
                        self.loadStats();
                    }).catch(err => {
                        $btn.prop('disabled', false).html('<span>💾</span> Save Dates & Tips');
                        self.toast('Error saving settlement: ' + ((err && err.message) || 'Unknown error'), 'error');
                    });
                });

                // Toggle On-Time Delivery Status in Details Modal
                $(document).off('change', '#ftm-details-ontime-checkbox').on('change', '#ftm-details-ontime-checkbox', function () {
                    const id = self.state.activeProjectId;
                    if (!id) return;
                    const $cb = $(this);
                    const isOntime = $cb.is(':checked') ? 1 : 0;
                    $cb.prop('disabled', true);
                    self.api('projects/' + id + '/ontime', 'POST', { is_ontime: isOntime }).then(res => {
                        $cb.prop('disabled', false);
                        $('#ftm-details-ontime-badge')
                            .removeClass('bg-emerald-soft text-emerald bg-rose-soft text-rose')
                            .addClass(isOntime ? 'bg-emerald-soft text-emerald' : 'bg-rose-soft text-rose')
                            .text(isOntime ? '✓ On-Time Delivery' : '✕ Not On-Time / Delayed');
                        self.toast((res && res.message) || 'On-time delivery status updated!', 'success');
                        self.loadProjects();
                        self.loadStats();
                    }).catch(err => {
                        $cb.prop('disabled', false).prop('checked', !isOntime);
                        self.toast('Error updating on-time status: ' + ((err && err.message) || 'Unknown error'), 'error');
                    });
                });

                // Export Projects CSV Button
                $(document).off('click', '#ftm-btn-export-projects').on('click', '#ftm-btn-export-projects', function (e) {
                    e.preventDefault();
                    const rows = self.state.projects || [];
                    if (!rows.length) {
                        self.toast('No projects loaded to export.', 'warning');
                        return;
                    }
                    const headers = ['ID', 'Team', 'Profile', 'Client', 'Order ID', 'Gross ($)', 'Net ($)', 'Tip ($)', 'Status', 'Assign Date', 'Delivery Date', 'Assignees'];
                    const escCsv = (v) => '"' + String(v === null || v === undefined ? '' : v).replace(/"/g, '""') + '"';
                    const csvLines = [headers.map(escCsv).join(',')];
                    rows.forEach(p => {
                        const assignees = (p.assigned_members && p.assigned_members.length)
                            ? p.assigned_members.map(m => m.name).join('; ')
                            : (p.assigned_user_name || '');
                        csvLines.push([
                            p.id,
                            p.team_name || '',
                            p.profile_name || '',
                            p.client_name || '',
                            p.order_id || '',
                            parseFloat(p.gross_order_value || 0).toFixed(2),
                            parseFloat(p.net_delivery_value || 0).toFixed(2),
                            parseFloat(p.tip_amount || 0).toFixed(2),
                            p.status_label || p.status || '',
                            p.created_at ? p.created_at.slice(0, 10) : '',
                            p.delivery_date || '',
                            assignees
                        ].map(escCsv).join(','));
                    });
                    const blob = new Blob([csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'projects-export-' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    self.toast('Exported ' + rows.length + ' projects to CSV.', 'success');
                });







                // Quick Assign Team Members



                $(document).on('click', '.ftm-assignee-edit-btn', function (e) {



                    e.preventDefault();



                    e.stopPropagation();



                    const id = $(this).data('id');



                    const team = $(this).data('team');



                    const order = $(this).data('order');



                    self.openQuickAssignModal(id, team, order);



                });







                // Quick Assign Search Filter



                $('#ftm-quick-assign-search').on('input', function () {



                    const query = $(this).val().toLowerCase().trim();



                    $('#ftm-quick-assign-members-list .ftm-assignee-checkbox-item').each(function () {



                        const name = $(this).data('name') || '';



                        if (!query || name.includes(query)) {



                            $(this).removeClass('hidden');



                        } else {



                            $(this).addClass('hidden');



                        }



                    });



                });







                // Quick Assign Checkbox Click



                $(document).on('change', '#ftm-quick-assign-members-list input[type="checkbox"]', function () {



                    $(this).closest('.ftm-assignee-checkbox-item').toggleClass('checked', $(this).is(':checked'));



                });







                // Quick Assign Form Submit



                $('#ftm-quick-assign-form').on('submit', function (e) {



                    e.preventDefault();



                    self.saveQuickAssignees();



                });







                // Multi-select dropdown in Add/Edit modal



                $('#ftm-form-assignees-toggle').on('click', function (e) {



                    e.stopPropagation();



                    $('#ftm-form-assignees-dropdown').toggleClass('hidden');



                });







                $(document).on('click', function (e) {



                    if (!$(e.target).closest('#ftm-form-assignees-wrap').length) {



                        $('#ftm-form-assignees-dropdown').addClass('hidden');



                    }



                });







                $(document).on('change', '#ftm-form-assignees-list input[type="checkbox"]', function () {



                    $(this).closest('.ftm-assignee-checkbox-item').toggleClass('checked', $(this).is(':checked'));



                    self.updateFormAssigneesSummary();



                });







                // Trigger Quick Assign from Details Modal



                $(document).on('click', '#ftm-details-edit-assignees-btn', function (e) {



                    e.preventDefault();



                    if (self.state.activeProjectId) {



                        self.openQuickAssignModal(self.state.activeProjectId);



                    }



                });







                // Edit button in Details Modal



                $('#ftm-details-edit-btn').on('click', function () {
                    if ((!self.state.isAdmin && !self.state.isLeader) || self.state.isDeputy) {
                        self.toast('You do not have permission to edit projects.', 'error');
                        return;
                    }
                    if (self.state.activeProjectId) {



                        $('#ftm-modal-details').addClass('hidden');



                        self.openEditProjectModal(self.state.activeProjectId);



                    }



                });







                // Delete button in Details Modal



                $('#ftm-details-delete-btn').on('click', function () {



                    if (self.state.activeProjectId && confirm('Are you sure you want to permanently delete this project?')) {



                        self.deleteProject(self.state.activeProjectId);



                    }



                });







                // Delegated table clicks: View Details



                $(document).on('click', '.ftm-view-project-btn', function () {
                    const id = $(this).data('id');
                    self.openProjectDetailsModal(id);
                });

                $(document).on('click', '.ftm-edit-project-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if ((!self.state.isAdmin && !self.state.isLeader) || self.state.isDeputy) {
                        self.toast('You do not have permission to edit projects.', 'error');
                        return;
                    }
                    const id = $(this).data('id') || self.state.activeProjectId;
                    if (id) {
                        $('#ftm-modal-details').addClass('hidden');
                        self.openEditProjectModal(id);
                    }
                });

                $(document).on('click', '#ftm-details-btn-edit-order, #ftm-details-edit-btn', function (e) {
                    e.preventDefault();
                    if ((!self.state.isAdmin && !self.state.isLeader) || self.state.isDeputy) {
                        self.toast('You do not have permission to edit projects.', 'error');
                        return;
                    }
                    const id = self.state.activeProjectId;
                    if (id) {
                        $('#ftm-modal-details').addClass('hidden');
                        self.openEditProjectModal(id);
                    }
                });

                $(document).on('click', '#ftm-details-btn-reset-extension', function (e) {
                    e.preventDefault();
                    const pid = self.state.activeProjectId;
                    if (!pid) return;
                    if (!confirm('Are you sure you want to reset and clear the delivery extension on this order?')) return;
                    self.api('projects/' + pid, 'POST', {
                        extension_count: 0,
                        extension_days: 0,
                        extension_reason: ''
                    }).then(() => {
                        self.toast('Delivery extension reset successfully!', 'success');
                        self.openProjectDetailsModal(pid);
                        self.loadProjects();
                        self.loadStats();
                    }).catch(err => {
                        self.toast('Error resetting extension: ' + (err.message || 'Unknown'), 'error');
                    });
                });

                $(document).on('click', '#ftm-details-btn-delete-order, #ftm-details-delete-btn', function (e) {
                    e.preventDefault();
                    const pid = self.state.activeProjectId;
                    if (!pid) return;
                    if (!confirm('Are you sure you want to permanently delete this project? This action cannot be undone.')) return;
                    self.api('projects/' + pid, 'DELETE').then(() => {
                        self.toast('Project deleted successfully.', 'success');
                        $('#ftm-modal-details').addClass('hidden');
                        self.loadProjects();
                        self.loadStats();
                    }).catch(err => {
                        self.toast('Error deleting project: ' + (err.message || 'Unknown'), 'error');
                    });
                });

                // Ensure Project Sheet and Order Links reliably open in new tab
                $(document).off('click', '#ftm-details-sheet-btn').on('click', '#ftm-details-sheet-btn', function (e) {
                    const url = $(this).attr('href');
                    if (url && url !== '#' && !url.startsWith('javascript:')) {
                        window.open(url, '_blank', 'noopener,noreferrer');
                        e.preventDefault();
                    }
                });
                $(document).off('click', '#ftm-details-order-link-btn').on('click', '#ftm-details-order-link-btn', function (e) {
                    const url = $(this).attr('href');
                    if (url && url !== '#' && !url.startsWith('javascript:')) {
                        window.open(url, '_blank', 'noopener,noreferrer');
                        e.preventDefault();
                    }
                });

                // TIME TRACKER & WORK SCREENSHOTS EVENT BINDINGS
                $(document).off('click', '.ftm-start-tracker-btn').on('click', '.ftm-start-tracker-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const pid = parseInt($(this).data('id'), 10) || 0;
                    const order = $(this).data('order') || '';
                    const client = $(this).data('client') || '';
                    const profile = $(this).data('profile') || '';
                    self.startProjectTimer(pid, order, client, profile);
                });

                $(document).off('click', '.ftm-stop-tracker-btn, #ftm-btn-tracker-stop, #ftm-pip-btn-stop').on('click', '.ftm-stop-tracker-btn, #ftm-btn-tracker-stop, #ftm-pip-btn-stop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.stopProjectTimer(false);
                });

                $(document).off('click', '#ftm-btn-tracker-snap-now').on('click', '#ftm-btn-tracker-snap-now', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.captureAndUploadSnapshot(true);
                });

                $(document).off('click', '#ftm-btn-resume-stream, #ftm-btn-tracker-reconnect, #ftm-pip-btn-reconnect').on('click', '#ftm-btn-resume-stream, #ftm-btn-tracker-reconnect, #ftm-pip-btn-reconnect', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.resumeScreenSharingStream();
                });

                $(document).off('click', '#ftm-pip-toggle-min').on('click', '#ftm-pip-toggle-min', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const body = $('#ftm-pip-body');
                    if (body.is(':visible')) {
                        body.slideUp(160);
                        $(this).text('+');
                    } else {
                        body.slideDown(160);
                        $(this).text('_');
                    }
                });

                $(document).off('click', '#ftm-pip-toast-close').on('click', '#ftm-pip-toast-close', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $('#ftm-pip-snap-toast').hide().addClass('hidden');
                });

                $(document).off('click', '#ftm-pip-toast-thumb-wrap').on('click', '#ftm-pip-toast-thumb-wrap', function (e) {
                    e.preventDefault();
                    if (!self.state.isAdmin && !self.state.isLeader && !self.state.isDeputy) return;
                    const src = $('#ftm-pip-toast-thumb').attr('src');
                    const tr = self.state.timeTracker || {};
                    if (src) {
                        self.openSnapshotLightbox({
                            image_url: src,
                            url: src,
                            order_id: tr.orderId || $('#ftm-pip-toast-order').text(),
                            client_name: tr.clientName || 'Client',
                            specialist_name: (window.ftmData && window.ftmData.current_user && window.ftmData.current_user.name) || 'Specialist',
                            team_name: self.state.userTeam || 'Team',
                            session_type: tr.sessionType || 'regular',
                            captured_at: 'Just now'
                        });
                    }
                });

                // Profile Extra Time (Overtime after 6:20 PM) Controls
                $(document).off('click', '#ftm-btn-start-extra-tracker-prof').on('click', '#ftm-btn-start-extra-tracker-prof', function (e) {
                    e.preventDefault();
                    const pList = (self.state.myAssignedProjects && self.state.myAssignedProjects.length)
                        ? self.state.myAssignedProjects
                        : (self.state.assignedProjects || []);
                    const activeList = pList.filter(p => p.status !== 'delivery_accepted' && p.status !== 'cancelled');
                    const p = activeList[0] || pList[0];
                    if (!p) {
                        self.toast('No assigned projects found. You can still submit a manual Extra Time Report.', 'warning');
                        return;
                    }
                    self.startProjectTimer(p.id, p.order_id || '', p.client_name || '', p.profile_name || '', 'extra_time');
                });

                $(document).off('click', '#ftm-btn-open-extra-report-modal').on('click', '#ftm-btn-open-extra-report-modal', function (e) {
                    e.preventDefault();
                    $('#ftm-extra-date').val(new Date().toISOString().split('T')[0]);
                    $('#ftm-extra-hours').val('');
                    $('#ftm-extra-tasks').val('');
                    $('#ftm-extra-reason-text').val('').addClass('hidden');
                    $('#ftm-extra-reason-select').prop('selectedIndex', 0);

                    const sel = $('#ftm-extra-project-select');
                    sel.empty();
                    const pList = (self.state.myAssignedProjects && self.state.myAssignedProjects.length)
                        ? self.state.myAssignedProjects
                        : (self.state.assignedProjects || []);
                    if (pList.length > 0) {
                        pList.forEach(p => {
                            sel.append(`<option value="${p.id}">${self.escapeHtml(p.order_id || ('#ID-' + p.id))} — ${self.escapeHtml(p.client_name || 'Client')}</option>`);
                        });
                        $('#ftm-extra-custom-wrap').addClass('hidden');
                    } else {
                        $('#ftm-extra-custom-wrap').removeClass('hidden');
                    }
                    sel.append('<option value="custom">+ Custom Project / Urgent Revision</option>');
                    $('#ftm-modal-extra-time-report').removeClass('hidden');
                });

                $(document).off('change', '#ftm-extra-project-select').on('change', '#ftm-extra-project-select', function () {
                    $('#ftm-extra-custom-wrap').toggleClass('hidden', $(this).val() !== 'custom');
                });

                $(document).off('change', '#ftm-extra-reason-select').on('change', '#ftm-extra-reason-select', function () {
                    $('#ftm-extra-reason-text').toggleClass('hidden', $(this).val() !== 'other');
                });

                $(document).off('submit', '#ftm-form-extra-time-report').on('submit', '#ftm-form-extra-time-report', function (e) {
                    e.preventDefault();
                    const pVal = $('#ftm-extra-project-select').val();
                    const isCustom = (pVal === 'custom');
                    const pId = isCustom ? 0 : (parseInt(pVal, 10) || 0);
                    const pList = (self.state.myAssignedProjects && self.state.myAssignedProjects.length)
                        ? self.state.myAssignedProjects
                        : (self.state.assignedProjects || []);
                    const assignedProj = pList.find(p => parseInt(p.id, 10) === pId) || {};

                    const rawHours = $('#ftm-extra-hours').val().trim();
                    const hours = self.parseHours(rawHours);
                    const reasonSel = $('#ftm-extra-reason-select').val();
                    const reason = (reasonSel === 'other') ? $('#ftm-extra-reason-text').val().trim() : reasonSel;
                    const deliverables = $('#ftm-extra-tasks').val().trim();
                    const reportDate = $('#ftm-extra-date').val() || new Date().toISOString().split('T')[0];

                    if (hours <= 0) {
                        self.toast('Please enter valid extra hours worked (e.g. 1h 30m or 1.5).', 'warning');
                        return;
                    }
                    if (!reason) {
                        self.toast('Please specify the reason for overtime work.', 'warning');
                        return;
                    }
                    if (!deliverables) {
                        self.toast('Please describe the deliverables completed.', 'warning');
                        return;
                    }

                    const payload = {
                        project_id: pId,
                        order_id: isCustom ? ($('#ftm-extra-custom-order').val().trim() || 'EXTRA-WORK') : (assignedProj.order_id || ''),
                        client_name: isCustom ? ($('#ftm-extra-custom-client').val().trim() || 'Client') : (assignedProj.client_name || 'Client'),
                        report_date: reportDate,
                        hours_spent: hours,
                        extra_time_reason: reason,
                        tasks_completed: deliverables
                    };

                    const btn = $('#ftm-btn-submit-extra-report-save');
                    btn.prop('disabled', true);
                    self.api('time-tracker/extra-time-report', 'POST', payload).then(res => {
                        btn.prop('disabled', false);
                        $('#ftm-modal-extra-time-report').addClass('hidden');
                        self.toast(res.message || 'Extra time work report logged successfully!', 'success');
                        const curMemberId = self.state.selectedMemberProfileId || (window.ftmData && window.ftmData.current_user_id);
                        if (typeof self.loadMemberExtraTimeSection === 'function') {
                            self.loadMemberExtraTimeSection(curMemberId);
                        }
                        if (typeof self.loadTeamDailyReportView === 'function') {
                            self.loadTeamDailyReportView();
                        }
                    }).catch(err => {
                        btn.prop('disabled', false);
                        self.toast('Error submitting overtime report: ' + (err.message || 'Unknown'), 'error');
                    });
                });

                $(document).off('click', '.ftm-view-tracker-snaps-btn').on('click', '.ftm-view-tracker-snaps-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const pid = parseInt($(this).data('id'), 10) || 0;
                    self.openTrackerGalleryModal(pid);
                    const sid = parseInt($(this).data('session-id'), 10) || 0;
                    self.openTrackerGalleryModal(pid, sid);
                });

                $(document).off('click', '#ftm-details-view-tracker-snaps-btn').on('click', '#ftm-details-view-tracker-snaps-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const pid = self.state.activeProjectId || 0;
                    self.openTrackerGalleryModal(pid);
                });

                $(document).off('click', '#ftm-modal-tracker-gallery-close, #ftm-btn-tracker-gallery-close').on('click', '#ftm-modal-tracker-gallery-close, #ftm-btn-tracker-gallery-close', function (e) {
                    e.preventDefault();
                    $('#ftm-modal-tracker-gallery').addClass('hidden');
                });

                $(document).off('click', '.ftm-snap-thumb-wrap').on('click', '.ftm-snap-thumb-wrap', function (e) {
                    const fullUrl = $(this).data('full');
                    if (fullUrl) {
                        window.open(fullUrl, '_blank', 'noopener,noreferrer');
                    }
                });

                // Screenshot Lightbox Inspection Modal Controls
                $(document).off('click', '#ftm-modal-snap-lightbox .ftm-modal-close-btn').on('click', '#ftm-modal-snap-lightbox .ftm-modal-close-btn', function (e) {
                    e.preventDefault();
                    $('#ftm-modal-snap-lightbox').addClass('hidden');
                });

                // Manager only: Delete snapshot from lightbox
                $(document).off('click', '#ftm-snap-lightbox-btn-delete').on('click', '#ftm-snap-lightbox-btn-delete', function (e) {
                    e.preventDefault();
                    if (!self.state.isAdmin) {
                        self.toast('Only Operations Managers and Administrators can delete screenshots.', 'warning');
                        return;
                    }
                    const sid = $(this).data('snap-id');
                    if (!sid) return;
                    if (!confirm('Are you sure you want to permanently delete this snapshot capture?')) return;
                    self.api('time-tracker/snapshots/' + sid, 'DELETE').then(() => {
                        self.toast('Snapshot deleted successfully.', 'success');
                        $('#ftm-modal-snap-lightbox').addClass('hidden');
                        if (typeof self.loadTimeTrackerHubView === 'function' && $('#ftm-view-time-tracker').is(':visible')) {
                            self.loadTimeTrackerHubView();
                        } else if (typeof self.loadHubSnapshotsGallery === 'function') {
                            self.loadHubSnapshotsGallery();
                        }
                    }).catch(err => {
                        self.toast('Error deleting snapshot: ' + (err.message || 'Permission denied'), 'error');
                    });
                });

                // Manager only: Edit audit note from lightbox
                $(document).off('click', '#ftm-snap-lightbox-btn-edit-note').on('click', '#ftm-snap-lightbox-btn-edit-note', function (e) {
                    e.preventDefault();
                    if (!self.state.isAdmin) {
                        self.toast('Only Operations Managers and Administrators can edit audit notes.', 'warning');
                        return;
                    }
                    const sid = $(this).data('snap-id');
                    const currentNote = $(this).data('notes') || '';
                    const newNote = prompt('Edit Audit Notes for this screenshot:', currentNote);
                    if (newNote === null) return;
                    self.api('time-tracker/snapshots/' + sid + '/update', 'POST', { notes: newNote }).then(() => {
                        self.toast('Audit notes updated.', 'success');
                        $('#ftm-snap-lightbox-notes-text').text(newNote || 'No audit notes attached.');
                        $('#ftm-snap-lightbox-btn-edit-note').data('notes', newNote);
                        if (typeof self.loadHubSnapshotsGallery === 'function') {
                            self.loadHubSnapshotsGallery();
                        }
                    }).catch(err => {
                        self.toast('Error updating note: ' + (err.message || 'Permission denied'), 'error');
                    });
                });

                // My Assigned Orders & Deliveries filter pills (Reference Image 4 Screen 4)



                $(document).on('click', '#ftm-my-work-filter-pills .ftm-pill-btn', function (e) {



                    e.preventDefault();



                    $('#ftm-my-work-filter-pills .ftm-pill-btn').removeClass('active');



                    $(this).addClass('active');



                    const filter = $(this).data('filter') || 'active';



                    self.renderMyAssignedProjectsList(filter);



                });







                // Delegated quick status dropdown in table
                $(document).on('change', '.ftm-table-status-changer', function () {
                    const $sel = $(this);
                    const id = $sel.data('id');
                    const status = $sel.val();

                    if (status === 'delivery_accepted') {
                        // Open details modal with Delivery Accepted pre-selected so user can enter review/dates and save in one step
                        self.state.pendingTableStatusSelect = { id: id, $el: $sel };
                        self.openProjectDetailsModal(id, 'delivery_accepted');
                    } else {
                        self.state.pendingTableStatusSelect = null;
                        self.updateProjectStatus(id, status, false);
                    }
                });

                // If user closes Project Details modal without saving a pending table status change, revert the table dropdown
                $(document).on('click', '#ftm-modal-details .ftm-modal-close-btn', function () {
                    const pending = self.state.pendingTableStatusSelect;
                    if (pending && String(pending.id) === String(self.state.activeProjectId)) {
                        if (pending.$el && self.state.activeProjectStatus) {
                            pending.$el.val(self.state.activeProjectStatus);
                        }
                        self.state.pendingTableStatusSelect = null;
                    }
                });

                // Toggle On-Time status directly from Projects Table
                $(document).on('change', '.ftm-ontime-toggle-checkbox', function (e) {
                    e.stopPropagation();
                    const chk = $(this);
                    const pid = chk.data('id');
                    const isChecked = chk.is(':checked') ? 1 : 0;
                    chk.prop('disabled', true);

                    self.api('projects/' + pid + '/ontime', 'POST', {
                        is_ontime: isChecked
                    }).then(res => {
                        chk.prop('disabled', false);
                        self.toast(res.message || 'On-Time status updated successfully.', 'success');
                        const badgeSpan = chk.siblings('.ftm-badge');
                        if (isChecked) {
                            badgeSpan.removeClass('bg-rose-soft text-rose').addClass('bg-emerald-soft text-emerald').text('✓ On-Time');
                        } else {
                            badgeSpan.removeClass('bg-emerald-soft text-emerald').addClass('bg-rose-soft text-rose').text('✕ Not On-Time');
                        }
                    }).catch(err => {
                        chk.prop('disabled', false);
                        chk.prop('checked', !isChecked);
                        self.toast((err && err.message) || 'Could not update On-Time status.', 'error');
                    });
                });







                // Trigger Avatar File Input



                $('#ftm-btn-trigger-upload').on('click', function () {



                    $('#ftm-avatar-file-input').trigger('click');



                });







                // Handle Avatar File Upload



                $('#ftm-avatar-file-input').on('change', function (e) {



                    const file = e.target.files[0];



                    if (!file) return;



                    self.uploadAvatarFile(file);



                });







                // Handle Avatar Preset Selection



                $('.ftm-preset-avatar-btn').on('click', function () {



                    $('.ftm-preset-avatar-btn').removeClass('active');



                    $(this).addClass('active');



                    const avatarUrl = $(this).data('avatar');



                    $('#ftm-avatar-url-input').val(avatarUrl);



                    $('#ftm-settings-avatar-preview').attr('src', avatarUrl);



                });







                // Reset Avatar button



                $('#ftm-btn-remove-avatar').on('click', function () {



                    $('.ftm-preset-avatar-btn').removeClass('active');



                    $('#ftm-avatar-url-input').val('');



                    self.saveUserProfile('', true);



                });







                // Submit User Profile Settings Form



                $('#ftm-user-profile-settings-form').on('submit', function (e) {



                    e.preventDefault();



                    self.saveUserProfile();



                });







                // Direct Save Profile Button Click



                $(document).on('click', '#ftm-btn-save-profile', function (e) {



                    e.preventDefault();



                    self.saveUserProfile();



                });







                // Universal Password Visibility Toggle (Settings form, modals)



                $(document).on('click', '.ftm-pwd-eye-toggle', function (e) {



                    e.preventDefault();



                    e.stopPropagation();



                    const btn = $(this);



                    const targetId = btn.data('target');



                    const input = targetId ? $('#' + targetId) : btn.siblings('input');



                    if (!input || !input.length) return;







                    const isPassword = input.attr('type') === 'password';



                    input.attr('type', isPassword ? 'text' : 'password');







                    const eyeOpen = btn.find('.ftm-eye-open, #ftm-eye-open, .eye-open');



                    const eyeClosed = btn.find('.ftm-eye-closed, #ftm-eye-closed, .eye-closed');







                    if (isPassword) {



                        eyeOpen.addClass('hidden').css('display', 'none');



                        eyeClosed.removeClass('hidden').css('display', 'block');



                    } else {



                        eyeClosed.addClass('hidden').css('display', 'none');



                        eyeOpen.removeClass('hidden').css('display', 'block');



                    }



                });







                // Submit Password Change Form



                $('#ftm-password-form').on('submit', function (e) {



                    e.preventDefault();



                    self.changePassword();



                });







                // Global print event listeners (handles browser Print / Ctrl+P seamlessly)



                window.addEventListener('beforeprint', () => {



                    const stage = $('#ftm-print-stage');



                    if (stage.is(':empty') || !stage.children().length || !stage.text().trim().length) {



                        const isWeeklyModal = !$('#ftm-modal-weekly-report').hasClass('hidden');



                        const isDocModal = !$('#ftm-modal-executive-doc').hasClass('hidden');



                        const isMonthly = self.state.currentView === 'monthly-reports' || $('#ftm-view-monthly-reports').hasClass('active');



                        const isTeamDaily = self.state.currentView === 'team-daily-report' || $('#ftm-view-team-daily-report').hasClass('active');







                        if (isWeeklyModal) {



                            self.populateWeeklyPrintStage();



                        } else if (isDocModal) {



                            self.populateDailyDocPrintStage();



                        } else if (isMonthly) {



                            self.populateMonthlyPrintStage();



                        } else if (isTeamDaily) {



                            if (!$('#ftm-exec-doc-body').children().length) {



                                self.openExecutiveDocReport('all');



                                $('#ftm-modal-executive-doc').addClass('hidden');



                            }



                            self.populateDailyDocPrintStage();



                        }



                    }



                });







                window.addEventListener('afterprint', () => {



                    setTimeout(() => {



                        $('body').removeClass('ftm-printing');



                        $('#ftm-print-stage').empty();



                    }, 2000);



                });



            },







                initRouter() {
                this.handleHashChange();
                this.refreshMyTasksBadge();
            },







                handleHashChange() {



                    const rawHash = (window.location.hash || '').replace(/^#\/?/, '').trim();
                    let view = 'dashboard';

                    if(rawHash) {
                        if (rawHash.startsWith('member-profile')) {
                            view = 'member-profile';
                            let parsedId = null;
                            if (rawHash.includes('id=')) {
                                parsedId = new URLSearchParams(rawHash.split('?')[1] || '').get('id');
                            } else if (rawHash.includes('/')) {
                                parsedId = rawHash.split('/')[1];
                            }
                            if (parsedId) {
                                const midNum = parseInt(parsedId, 10);
                                this.state.selectedMemberProfileId = midNum;
                                try { localStorage.setItem('ftm_selected_member_profile_id', midNum); } catch (e) { }
                            }
                        } else if (rawHash.includes('=')) {
                            const params = new URLSearchParams(rawHash);
                            view = params.get('view') || 'dashboard';
                        } else {
                            view = rawHash;
                        }
                    }

            // The address bar shows #profiles; the view keeps its internal name
            if(view === 'profiles') {
                view = 'fiverr-profiles';
            } else if(view === 'fiverr-profiles') {
                try { history.replaceState(null, '', window.location.pathname + window.location.search + '#profiles');
} catch (e) { }
            }

this.switchView(view);



        },







switchView(viewName) {
    const isMember = this.state.isMember || (!this.state.isAdmin && !this.state.isLeader && !this.state.isDeputy);
    if (isMember) {
        if (['monthly-reports', 'fiverr-profiles', 'settings'].includes(viewName)) {
            this.toast('Access denied. This section is restricted to leadership.', 'warning');
            viewName = 'dashboard';
        }
    }
    if (this.state.isLeader && !this.state.isAdmin && !this.state.isDeputy) {
        if (viewName === 'settings') {
            this.toast('Access denied. Settings are restricted to Super Admin.', 'warning');
            viewName = 'dashboard';
        }
    }

    this.state.currentView = viewName;
    $('.ftm-nav-item').removeClass('active');
    $(`.ftm-nav-item[data-view="${viewName}"]`).addClass('active');







    $('.ftm-mob-nav-item').removeClass('active');



    $(`.ftm-mob-nav-item[data-view="${viewName}"]`).addClass('active');



    $('#ftm-sidebar').removeClass('open');



    $('#ftm-sidebar-overlay').removeClass('open');



    $('body').removeClass('ftm-drawer-open');







    const panelId = (viewName === 'projects' || viewName === 'dashboard') ? 'dashboard' : viewName;



    const targetPanel = $(`#ftm-view-${panelId}`);







    // Smooth panel transition with Zero Layout Shift



    if (!targetPanel.hasClass('active')) {



        $('.ftm-view-panel').addClass('hidden').removeClass('active ftm-loading');



        targetPanel.removeClass('hidden').addClass('active ftm-loading');
        const _t0 = Date.now();
        let _cleared = false;
        let _failsafe = null;
        const _clear = () => {
            if (_cleared) return;
            _cleared = true;
            if (_failsafe) clearTimeout(_failsafe);
            $(document).off('ftm:apiIdle ajaxStop', _clear);
            if (!targetPanel.hasClass('active')) {
                targetPanel.removeClass('ftm-loading');
                return;
            }
            setTimeout(() => targetPanel.removeClass('ftm-loading'), Math.max(0, 180 - (Date.now() - _t0)));
        };
        setTimeout(() => {
            if (_cleared || !targetPanel.hasClass('active')) return;
            if ((this._activeApiCount || 0) > 0 || $.active > 0) {
                $(document).on('ftm:apiIdle ajaxStop', _clear);
                _failsafe = setTimeout(_clear, 1500);
            } else {
                _clear();
            }
        }, 80);



    }







    if (viewName === 'projects' || viewName === 'dashboard') {
        this.loadStats();



        this.state.status = 'all';



        this.state.viewFilter = '';



        this.state.search = '';



        this.state.memberId = 0;



        this.state.profileId = 0;



        $('#ftm-filter-status').val('all');



        $('#ftm-filter-view').val('');



        $('#ftm-filter-search').val('');



        $('#ftm-filter-member').val('0');



        $('#ftm-filter-profile').val('0');



        $('.ftm-stat-card').removeClass('active-filter');



        this.loadProjects();



    } else if (viewName === 'team-members') {



        this.loadTeamMembersView();



    } else if (viewName === 'fiverr-profiles') {



        this.loadFiverrProfilesView();



    } else if (viewName === 'my-projects') {
        this.loadMyProjectsView();
    } else if (viewName === 'my-tasks') {
        this.loadMyTasksView();
    } else if (viewName === 'daily-reports') {



        this.loadDailyReportsView();



    } else if (viewName === 'team-daily-report') {
        this.loadTeamDailyReportView();
    } else if (viewName === 'time-tracker') {
        this.loadTimeTrackerHubView();
    } else if (viewName === 'attendance') {
        this.loadAttendanceView();

    } else if (viewName === 'overview') {

        this.loadOverviewView();

    } else if (viewName === 'monthly-reports') {



        this.loadMonthlyReportsView();



    } else if (viewName === 'leaderboard') {



        this.loadLeaderboardView();



    } else if (viewName === 'member-profile') {
        let storedId = null;
        try { storedId = localStorage.getItem('ftm_selected_member_profile_id'); } catch (e) { }
        const targetMemberId = this.state.selectedMemberProfileId ||
            (storedId ? parseInt(storedId, 10) : null) ||
            (window.ftmData && window.ftmData.current_user && !window.ftmData.current_user.is_admin ? window.ftmData.current_user.id : 12);
        this.loadMemberProfile(targetMemberId);



    } else if (viewName === 'admin-profile') {



        this.loadManagerOverview();



    } else if (viewName === 'agency-notices') {



        this.loadAgencyNotices();



    } else if (viewName === 'messages') {
        if (window.ftmMessages && typeof window.ftmMessages.fetchChannels === 'function') {
            window.ftmMessages.fetchChannels(false);
        }
    } else {



        this.loadProjects();



    }



},







loadInitialData() {



    this.loadStats();
    this.loadAlerts();
    this.loadProfiles();
    this.loadTeamMembers();
    this.loadProjects();

    if (!this._alertsTimer) {
        this._alertsTimer = setInterval(() => {
            this.loadAlerts();
        }, 60000);
    }



},







loadStats() {
    const isAdminOrDeputy = !!(this.state.isAdmin || this.state.isDeputy);
    let teamParam = this.state.team;
    if (!teamParam || teamParam === '') {
        teamParam = isAdminOrDeputy ? 'all' : (this.state.userTeam || 'all');
    }
    let url = 'stats?team=' + encodeURIComponent(teamParam);
    if (this.state.memberId && parseInt(this.state.memberId, 10) > 0) {
        url += '&user_id=' + encodeURIComponent(this.state.memberId);
    }
    const monthVal = this.state.month || $('#ftm-filter-month').val() || 'all';
    if (monthVal) {
        url += '&month=' + encodeURIComponent(monthVal);
    }
    if (this.state.startDate && this.state.endDate) {
        url += '&start_date=' + encodeURIComponent(this.state.startDate) + '&end_date=' + encodeURIComponent(this.state.endDate);
    }
    if (this.state.client && this.state.client !== 'all') {
        url += '&client=' + encodeURIComponent(this.state.client);
    }
    if (this.state.profileId && parseInt(this.state.profileId, 10) > 0) {
        url += '&profile_id=' + encodeURIComponent(this.state.profileId);
    }



    this.api(url).then(data => {



        $('#stat-total').text(data.total);



        $('#stat-nra').text(data.nra);



        $('#stat-wip').text(data.wip);



        $('#stat-submitted').text(data.delivery_submitted);



        $('#stat-revision').text(data.revision);



        $('#stat-accepted').text(data.delivered_total !== undefined ? data.delivered_total : data.delivery_accepted);



        $('#stat-cancelled').text(data.cancelled);







        // Reference B: 6 Top Metric Cards (Calculated directly from real data)



        $('#refb-stat-total').text(data.total !== undefined ? data.total : 0);
        const nsCnt = parseInt(data.new_sales_count !== undefined ? data.new_sales_count : 0, 10);
        const nsVal = parseFloat(data.new_sales_volume !== undefined ? data.new_sales_volume : 0);
        const cfCnt = parseInt(data.carry_forward_count !== undefined ? data.carry_forward_count : 0, 10);
        const cfVal = parseFloat(data.carry_forward_volume !== undefined ? data.carry_forward_volume : 0);
        $('#refb-stat-total-sub').html(`<span style="color:#f97316; font-weight:700;" title="New Sales: $${nsVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}">${nsCnt} New ($${nsVal.toLocaleString('en-US', {maximumFractionDigits:0})})</span> &bull; <span style="color:#eab308; font-weight:700;" title="Carry Forward: $${cfVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}">${cfCnt} Carry ($${cfVal.toLocaleString('en-US', {maximumFractionDigits:0})})</span>`);

        $('#refb-stat-active').text((parseInt(data.wip || 0) + parseInt(data.nra || 0)));

        $('#refb-stat-wip').text(data.wip !== undefined ? data.wip : 0); const wipVal = (data.financials && data.financials.total_wip_net !== undefined) ? parseFloat(data.financials.total_wip_net) : (parseFloat(data.active_wip_value || 0)); $('#refb-stat-wip-value').text('$' + wipVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        $('#refb-stat-revision').text(data.revision !== undefined ? data.revision : 0); const revVal = (data.financials && data.financials.total_revision_net !== undefined) ? parseFloat(data.financials.total_revision_net) : (parseFloat(data.active_revision_value || 0)); $('#refb-stat-revision-value').text('$' + revVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        const compDelCount = (data.delivery_accepted !== undefined) ? parseInt(data.delivery_accepted, 10) : ((data.delivered_total !== undefined) ? parseInt(data.delivered_total, 10) : 0);
        $('#refb-stat-completed').text(compDelCount);

        const cancCount = (data.cancelled !== undefined) ? parseInt(data.cancelled, 10) : ((data.financials && data.financials.total_cancelled_orders !== undefined) ? parseInt(data.financials.total_cancelled_orders, 10) : 0);
        const cancVal = (data.financials && data.financials.total_cancelled_net !== undefined) ? parseFloat(data.financials.total_cancelled_net) : 0;
        $('#refb-stat-cancelled').text(isNaN(cancCount) ? 0 : cancCount);
        $('#refb-stat-cancelled-value').text('$' + (isNaN(cancVal) ? 0 : cancVal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        const repeatCount = (data.repeat_orders_count !== undefined) ? parseInt(data.repeat_orders_count, 10) : 0;
        const repeatVal = (data.repeat_net_revenue !== undefined) ? parseFloat(data.repeat_net_revenue) : 0;
        $('#refb-stat-repeat').text(isNaN(repeatCount) ? 0 : repeatCount);
        $('#refb-stat-repeat-value').text('$' + (isNaN(repeatVal) ? 0 : repeatVal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        const netDel = (data.financials && data.financials.total_delivery_net !== undefined) ? parseFloat(data.financials.total_delivery_net) : (parseFloat(data.total_delivery_net || 0));

        $('#refb-stat-net-delivery').text('$' + netDel.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));







        // Financial KPI updates (Phase 1)



        if (data.financials) {



            const f = data.financials;



            $('#kpi-total-delivery').text('$' + parseFloat(f.total_delivery_net || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));



            $('#kpi-total-wip').text('$' + parseFloat(f.total_wip_net || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));



            $('#kpi-total-assigned').text('$' + parseFloat(f.total_work_assigned || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));



            $('#kpi-completed-orders').text(f.total_completed_orders || 0);



        }







        // Right sidebar quick stats



        $('#qs-active').text(data.active_projects);



        $('#qs-due-today').text(data.due_today);



        $('#qs-due-tomorrow').text(data.due_tomorrow);



        $('#qs-overdue').text(data.overdue_projects);



        $('#qs-revision').text(data.in_revision);



        $('#qs-awaiting').text(data.awaiting_acceptance);



    }).catch(console.error);



},







loadAlerts() {
    const self = this;
    this.api('alerts').then(alerts => {
        alerts = Array.isArray(alerts) ? alerts : [];
        if (alerts.length > 0) {
            $('#ftm-header-alert-badge').text(alerts.length).removeClass('hidden');
        } else {
            $('#ftm-header-alert-badge').text('0').addClass('hidden');
        }

        // Push Web Notifications for newly detected alerts
        self._knownAlertIds = self._knownAlertIds || new Set();
        if (self._alertsInitialized && alerts.length > 0) {
            alerts.forEach(a => {
                const aid = a.id || a.project_id;
                if (aid && !self._knownAlertIds.has(aid)) {
                    self.sendWebNotification(a.title || 'New Leadership Alert', {
                        body: (a.subtitle || a.message || '').replace(/<[^>]+>/g, '')
                    });
                }
            });
        }
        alerts.forEach(a => {
            const aid = a.id || a.project_id;
            if (aid) self._knownAlertIds.add(aid);
        });
        self._alertsInitialized = true;

        let html = '';
        let dropdownHtml = '';

        if (alerts.length === 0) {
            html = '<div class="ftm-text-muted ftm-text-sm" style="padding:10px 0;">No urgent alerts right now! </div>';
            dropdownHtml = '<div class="ftm-text-muted ftm-text-sm" style="padding:14px; text-align:center;">All team projects are on track! </div>';
        } else {
            alerts.forEach(a => {
                const iconMap = {
                    due_today: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
                    overdue: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
                    revision: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>',
                    awaiting: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
                };
                const iconBg = {
                    due_today: 'bg-amber-soft',
                    overdue: 'bg-rose-soft',
                    revision: 'bg-purple-soft',
                    awaiting: 'bg-emerald-soft'
                };

                const itemWidget = `
                            <div class="ftm-alert-item" data-filter="${a.filter}" data-project-id="${a.project_id || a.id}" style="cursor:pointer;">
                                <div class="ftm-alert-icon-wrap ${iconBg[a.type] || 'bg-blue-soft'}">
                                    ${iconMap[a.type] || ''}
                                </div>
                                <div class="ftm-alert-content">
                                    <span class="ftm-alert-title">${ftmEsc(a.title)}</span>
                                    <span class="ftm-alert-subtitle">${a.subtitle}</span>
                                </div>
                            </div>
                        `;
                const itemDropdown = `
                            <div class="ftm-alert-item" data-filter="${a.filter}" data-project-id="${a.project_id || a.id}" data-alert-id="${a.id}" style="cursor:pointer; display:flex; align-items:center;">
                                <div class="ftm-alert-icon-wrap ${iconBg[a.type] || 'bg-blue-soft'}">
                                    ${iconMap[a.type] || ''}
                                </div>
                                <div class="ftm-alert-content" style="flex:1; min-width:0;">
                                    <span class="ftm-alert-title">${ftmEsc(a.title)}</span>
                                    <span class="ftm-alert-subtitle">${a.subtitle}</span>
                                </div>
                                <button type="button" class="ftm-alert-dismiss-btn" data-alert-id="${a.id}" title="Dismiss / Mark as read" style="margin-left:auto; background:none; border:none; cursor:pointer; padding:6px 8px; color:var(--ftm-text-secondary, #94a3b8); font-size:16px; line-height:1; border-radius:4px; flex-shrink:0;">&times;</button>
                            </div>
                        `;
                html += itemWidget;
                dropdownHtml += itemDropdown;
            });
        }

        $('#ftm-widget-alerts-list').html(html);
        $('#ftm-dropdown-alerts-list').html(dropdownHtml);
    }).catch(console.error);
},

loadProfiles() {



    this.api('profiles').then(profiles => {



        this.state.profiles = profiles;



        let optHtml = '<option value="0">All Profiles</option>';



        let formOptHtml = '<option value="">Select Profile...</option>';



        profiles.forEach(p => {



            optHtml += `<option value="${p.id}">${ftmEsc(p.name)}</option>`;



            formOptHtml += `<option value="${p.id}">${ftmEsc(p.name)}</option>`;



        });



        $('#ftm-filter-profile').html(optHtml);



        $('#ftm-form-profile').html(formOptHtml);



    }).catch(console.error);



},







loadTeamMembers() {



    this.api('team-members').then(members => {



        this.state.teamMembers = members;



        let optHtml = `<option value="0">${this.state.isAdmin ? 'All Members' : 'Team Members'}</option>`;



        let widgetHtml = '';







        members.slice(0, 5).forEach(m => {



            widgetHtml += `



                        <div class="ftm-widget-member-row ftm-trigger-member-activity" data-id="${m.id}" style="cursor:pointer;" title="Click to view ${ftmEsc(m.name)}'s activity metrics">



                            <div class="ftm-widget-member-info">



                                <img src="${m.avatar}" class="ftm-avatar ftm-avatar-sm" alt="${ftmEsc(m.name)}">



                                <div class="ftm-widget-member-meta">



                                    <span class="ftm-widget-member-name">${ftmEsc(m.name)}</span>



                                    <span class="ftm-widget-member-projects">${m.team} • ${ftmEsc(m.role_label)}</span>



                                </div>



                            </div>



                            <span class="ftm-tag-active">${m.active_projects} active</span>



                        </div>



                    `;



        });







        members.forEach(m => {



            optHtml += `<option value="${m.id}">${ftmEsc(m.name)} (${m.team})</option>`;



        });







        this.populateMemberFilter(this.state.team || 'all');

        $('#ftm-widget-team-list').html(widgetHtml);



        this.populateFormMembers((this.state.isAdmin || this.state.isLeader) ? $('#ftm-form-team-name').val() : this.state.userTeam);



    }).catch(console.error);



},

populateMemberFilter(targetTeam = null) {
    const memberSel = $('#ftm-filter-member');
    if (!memberSel.length) return;
    const currentVal = this.state.memberId ? String(this.state.memberId) : '0';
    const isAllAccess = !!(this.state.isAdmin || this.state.isDeputy);
    const leaderTeam = this.state.userTeam || 'all';
    const effectiveTeam = targetTeam !== null ? targetTeam : (isAllAccess ? (this.state.team || 'all') : leaderTeam);

    let optHtml = `<option value="0">${isAllAccess ? 'All Specialists' : 'Team Members'}</option>`;
    const members = this.state.teamMembers || [];
    members.forEach(m => {
        if (!effectiveTeam || effectiveTeam === 'all' || (m.team && m.team.toLowerCase() === effectiveTeam.toLowerCase())) {
            optHtml += `<option value="${m.id}">${ftmEsc(m.name)}${m.team ? ' (' + ftmEsc(m.team) + ')' : ''}</option>`;
        }
    });
    memberSel.html(optHtml);
    if (currentVal !== '0' && memberSel.find(`option[value="${currentVal}"]`).length) {
        memberSel.val(currentVal);
    } else {
        memberSel.val('0');
        if (currentVal !== '0') {
            this.state.memberId = 0;
        }
    }
},







populateFormMembers(targetTeam, selectedIds = []) {
    const self = this;
    const allMembers = this.state.teamMembers || [];
    const normTargetTeam = (targetTeam || '').trim();

    // Ensure selectedIds are array of integers
    const selIds = (Array.isArray(selectedIds) ? selectedIds : [selectedIds]).map(x => parseInt(x, 10)).filter(n => !isNaN(n));

    // Sort:
    // 1. Currently selected assignees first
    // 2. Members belonging to the targetTeam (primary squad)
    // 3. Members from other squads (grouped by team name, then member name)
    const sortedMembers = [...allMembers].sort((a, b) => {
        const aChecked = selIds.includes(parseInt(a.id, 10));
        const bChecked = selIds.includes(parseInt(b.id, 10));
        if (aChecked !== bChecked) return aChecked ? -1 : 1;

        const aPrimary = normTargetTeam && (a.team || '').toLowerCase() === normTargetTeam.toLowerCase();
        const bPrimary = normTargetTeam && (b.team || '').toLowerCase() === normTargetTeam.toLowerCase();
        if (aPrimary !== bPrimary) return aPrimary ? -1 : 1;

        const tCompare = (a.team || '').localeCompare(b.team || '');
        if (tCompare !== 0) return tCompare;

        return (a.name || '').localeCompare(b.name || '');
    });

    const teamColors = {
        'WpCraft': 'bg-blue-soft text-blue',
        'WpNexus': 'bg-emerald-soft text-emerald',
        'WpTitans': 'bg-purple-soft text-purple',
        'WpHorizon': 'bg-amber-soft text-amber'
    };

    // Build filter toolbar chips
    const uniqueTeams = Array.from(new Set(allMembers.map(m => m.team).filter(Boolean)));

    let toolbarHtml = `
        <div class="ftm-assignee-team-filter-bar" style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid var(--ftm-border, #1e293b);">
            <button type="button" class="ftm-team-filter-pill active" data-team="all" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; background:rgba(59,130,246,0.18); color:#60a5fa; border:1px solid rgba(59,130,246,0.35);">
                All Specialists (${allMembers.length})
            </button>
            ${normTargetTeam ? `
            <button type="button" class="ftm-team-filter-pill" data-team="${ftmEsc(normTargetTeam)}" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; background:var(--ftm-card-bg, #0f172a); color:var(--ftm-text-secondary, #94a3b8); border:1px solid var(--ftm-border, #334155);">
                ★ Primary: ${ftmEsc(normTargetTeam)}
            </button>
            ` : ''}
    `;

    uniqueTeams.forEach(t => {
        if (t !== normTargetTeam) {
            const count = allMembers.filter(m => m.team === t).length;
            toolbarHtml += `
                <button type="button" class="ftm-team-filter-pill" data-team="${ftmEsc(t)}" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; background:var(--ftm-card-bg, #0f172a); color:var(--ftm-text-secondary, #94a3b8); border:1px solid var(--ftm-border, #334155);">
                    ${ftmEsc(t)} (${count})
                </button>
            `;
        }
    });

    toolbarHtml += `
            <button type="button" class="ftm-team-filter-pill" data-team="assigned" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; margin-left:auto; background:var(--ftm-card-bg, #0f172a); color:#10b981; border:1px solid rgba(16,185,129,0.3);">
                ✓ Assigned (<span class="ftm-team-filter-assigned-count">${selIds.length}</span>)
            </button>
        </div>
    `;

    let listHtml = '';
    if (sortedMembers.length === 0) {
        listHtml = '<div class="ftm-text-muted ftm-text-sm" style="padding:14px; text-align:center;">No specialists found.</div>';
    } else {
        sortedMembers.forEach(m => {
            const isChecked = selIds.includes(parseInt(m.id, 10));
            const isPrimary = normTargetTeam && (m.team || '').toLowerCase() === normTargetTeam.toLowerCase();
            const badgeClass = teamColors[m.team] || 'bg-blue-soft text-blue';

            // Active project count for this member
            const activeCount = (self.state.projects || []).filter(p => {
                const ids = (p.assigned_user_ids || []).map(x => parseInt(x, 10));
                return (ids.includes(parseInt(m.id, 10)) || parseInt(p.assigned_user_id, 10) === parseInt(m.id, 10)) && !['delivery_accepted', 'cancelled'].includes(p.status);
            }).length;

            listHtml += `
                <label class="ftm-assignee-checkbox-item ${isChecked ? 'checked' : ''}" 
                       data-id="${m.id}" 
                       data-team="${ftmEsc(m.team || '')}" 
                       data-name="${(m.name || '').toLowerCase()}" 
                       data-role="${(m.role_label || '').toLowerCase()}"
                       style="display:flex; align-items:center; justify-content:space-between; padding:8px 10px; margin-bottom:5px; border-radius:8px; border:1px solid var(--ftm-border, #334155); background:var(--ftm-card-bg, #0f172a); cursor:pointer;">
                    <div class="ftm-assignee-user-info" style="display:flex; align-items:center; gap:10px;">
                        <img src="${m.avatar}" alt="${ftmEsc(m.name)}" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--ftm-border, #e2e8f0);">
                        <div>
                            <div class="ftm-user-title" style="font-weight:700; font-size:13px; color:var(--ftm-text-primary); display:flex; align-items:center; gap:6px;">
                                <span>${ftmEsc(m.name)}</span>
                                <span class="ftm-badge ${badgeClass} ftm-badge-xs ftm-assignee-team-tag" style="font-size:10px; font-weight:700; padding:1px 6px;">${ftmEsc(m.team || 'Member')}</span>
                                ${!isPrimary && normTargetTeam ? '<span class="ftm-badge ftm-badge-outline ftm-badge-xs" style="font-size:9.5px; opacity:0.85; border-color:rgba(59,130,246,0.4); color:#93c5fd;">Cross-Team</span>' : ''}
                            </div>
                            <div class="ftm-user-subtitle" style="font-size:11px; color:var(--ftm-text-secondary); display:flex; align-items:center; gap:6px; margin-top:2px;">
                                <span>${ftmEsc(m.role_label || 'Specialist')}</span>
                                <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">${activeCount} active</span>
                            </div>
                        </div>
                    </div>
                    <input type="checkbox" name="form_assigned_user_ids[]" value="${m.id}" data-name="${ftmEsc(m.name)}" class="ftm-assignee-custom-cb" ${isChecked ? 'checked' : ''} style="width:16px; height:16px; cursor:pointer;">
                </label>
            `;
        });
    }

    $('#ftm-form-assignees-list').html(toolbarHtml + '<div class="ftm-assignees-scroll-container" style="max-height:260px; overflow-y:auto; padding-right:2px;">' + listHtml + '</div>');
    this.updateFormAssigneesSummary();

    // Attach pill click handler
    $('#ftm-form-assignees-list .ftm-team-filter-pill').off('click').on('click', function(e) {
        e.preventDefault();
        $('#ftm-form-assignees-list .ftm-team-filter-pill').removeClass('active').css({
            'background': 'var(--ftm-card-bg, #0f172a)',
            'color': 'var(--ftm-text-secondary, #94a3b8)',
            'border-color': 'var(--ftm-border, #334155)'
        });
        $(this).addClass('active').css({
            'background': 'rgba(59,130,246,0.18)',
            'color': '#60a5fa',
            'border-color': 'rgba(59,130,246,0.35)'
        });

        const filterTeam = $(this).data('team');
        const q = ($('#ftm-assignee-search').val() || '').toLowerCase().trim();

        $('#ftm-form-assignees-list .ftm-assignee-checkbox-item').each(function() {
            const itemTeam = ($(this).data('team') || '').toLowerCase();
            const isChecked = $(this).find('input[type="checkbox"]').is(':checked');
            const textMatch = !q || $(this).text().toLowerCase().includes(q);

            let teamMatch = true;
            if (filterTeam === 'all') {
                teamMatch = true;
            } else if (filterTeam === 'assigned') {
                teamMatch = isChecked;
            } else {
                teamMatch = (itemTeam === String(filterTeam).toLowerCase());
            }

            $(this).toggle(teamMatch && textMatch);
        });
    });
},







updateFormAssigneesSummary() {



    const checked = $('#ftm-form-assignees-list input[type="checkbox"]:checked');



    const summarySpan = $('#ftm-form-assignees-summary');



    const countBadge = $('#ftm-form-assignees-count-badge');







    if (countBadge.length) {
        countBadge.text(`${checked.length} Assigned`);
        if (checked.length > 0) {
            countBadge.attr('class', 'ftm-badge bg-blue-soft text-blue ftm-badge-sm');
        } else {
            countBadge.attr('class', 'ftm-badge bg-rose-soft text-rose ftm-badge-sm');
        }
    }
    $('.ftm-team-filter-assigned-count').text(checked.length);







    if (summarySpan.length) {



        if (checked.length === 0) {



            summarySpan.text('Select members...').css('color', '#94a3b8');



        } else if (checked.length === 1) {



            summarySpan.text(checked.first().data('name') || '1 member selected').css('color', 'var(--ftm-text-primary)');



        } else {



            summarySpan.text(`${checked.length} members assigned`).css('color', 'var(--ftm-text-primary)');



        }



    }



},







updateProjectDeadlineCountdown(dateStr) {



    const badge = $('#ftm-form-deadline-countdown');



    if (!badge.length) return;



    if (!dateStr) {



        badge.hide();



        return;



    }



    badge.show();



    const now = new Date();



    now.setHours(0, 0, 0, 0);



    const target = new Date(dateStr + 'T00:00:00');



    const diffTime = target.getTime() - now.getTime();



    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));



    const dayName = target.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });







    if (diffDays < 0) {



        badge.attr('class', 'ftm-badge bg-rose-soft text-rose ftm-badge-sm').html(`Overdue by ${Math.abs(diffDays)}d (${dayName})`);



    } else if (diffDays === 0) {



        badge.attr('class', 'ftm-badge bg-amber-soft text-amber ftm-badge-sm').html(`Due Today (${dayName})`);



    } else if (diffDays === 1) {



        badge.attr('class', 'ftm-badge bg-amber-soft text-amber ftm-badge-sm').html(`Due Tomorrow (${dayName})`);



    } else {



        badge.attr('class', 'ftm-badge bg-emerald-soft text-emerald ftm-badge-sm').html(`Due in ${diffDays} days (${dayName})`);



    }



},







setupExtendDatePicker(pid, givenDeadline) {
    let currentDeadline = givenDeadline || null;
    if (!currentDeadline) {
        const found = (this.state.projects || []).find(p => String(p.id) === String(pid))
                   || (this.state.myAssignedProjects || []).find(p => String(p.id) === String(pid))
                   || (this.state.myProjects || []).find(p => String(p.id) === String(pid));
        if (found && found.delivery_date) {
            currentDeadline = found.delivery_date;
        }
    }

    const apply = (deadline) => {
        const today = new Date().toISOString().split('T')[0];
        const base = deadline ? deadline.slice(0, 10) : today;
        const next = new Date(base + 'T12:00:00');
        next.setDate(next.getDate() + 1);
        let min = next.toISOString().split('T')[0];
        if (min < today) min = today;

        const def = new Date(base + 'T12:00:00');
        def.setDate(def.getDate() + 3);
        let defIso = def.toISOString().split('T')[0];
        if (defIso < min) defIso = min;

        this._extendBase = base;
        $('#ftm-extend-current-deadline').text(deadline ? new Date(base + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : 'No deadline set');
        $('#ftm-extend-date-input').attr('min', min).val(defIso);
        this.updateExtendSummary();
    };

    if (currentDeadline) {
        apply(currentDeadline);
    } else {
        apply(null);
        this.api('projects/' + pid).then(r => {
            const d = (r && r.project && r.project.delivery_date) || (r && r.delivery_date);
            if (d) apply(d);
        });
    }
},
updateExtendSummary() {
    const val = $('#ftm-extend-date-input').val();
    const box = $('#ftm-extend-summary');
    if (!val || !this._extendBase) {
        $('#ftm-extend-days-input').val(0);
        box.removeClass('ok').text('Pick the new deadline from the calendar.');
        return;
    }
    const days = Math.round((new Date(val + 'T12:00:00') - new Date(this._extendBase + 'T12:00:00')) / 86400000);
    $('#ftm-extend-days-input').val(days > 0 ? days : 1);
    if (days < 1) {
        box.removeClass('ok').text('The new deadline must be after the current deadline (' + this._extendBase + ').');
        return;
    }
    box.addClass('ok').html('<strong>+' + days + ' day' + (days > 1 ? 's' : '') + '</strong> added to the deadline (New deadline: ' + val + ')');
},
syncProjectFilterOptions(res) {
    const clientSel = $('#ftm-filter-client');
    if (clientSel.length && Array.isArray(res.clients)) {
        const current = this.state.client || 'all';
        let html = '<option value="all">All Clients</option>';
        res.clients.forEach(c => { html += '<option value="' + ftmEsc(c) + '">' + ftmEsc(c) + '</option>'; });
        clientSel.html(html);
        if (current !== 'all' && res.clients.indexOf(current) === -1) { this.state.client = 'all'; }
        clientSel.val(this.state.client || 'all');
        clientSel.toggle(res.clients.length > 0);
    }
    if (Array.isArray(res.teams)) {
        const teamSel = $('#ftm-filter-team');
        const canSeeAllTeams = !!(this.state.isAdmin || this.state.isDeputy);
        if (canSeeAllTeams) {
            let t = '<option value="all">All Teams</option>';
            res.teams.forEach(n => { t += '<option value="' + ftmEsc(n) + '">' + ftmEsc(n) + '</option>'; });
            teamSel.html(t).val(this.state.team || 'all');
        }
        // A member or leader only ever sees one team, so the team filter adds nothing
        teamSel.toggle(canSeeAllTeams && (res.teams.length > 1 || this.state.team !== 'all'));
    }
},
loadProjects() {



    const params = new URLSearchParams({



        page: this.state.page,



        per_page: this.state.perPage,



        status: this.state.status,



        team: this.state.team,
        client: this.state.client,



        profile_id: this.state.profileId,



        member_id: this.state.memberId,



        search: this.state.search,



        sort: this.state.sort,



        view_filter: this.state.viewFilter



    });

    if (this.state.month && this.state.month !== 'all') {
        params.set('month', this.state.month);
    }







    if (this.state.startDate) {



        params.set('start_date', this.state.startDate);



    }



    if (this.state.endDate) {



        params.set('end_date', this.state.endDate);



    }







    const scrollSnap = this.captureScrollState();
    const tbody = $('#ftm-projects-tbody');



    if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {



        tbody.html(`



                    <tr>



                        <td colspan="11" class="ftm-table-loading">



                            <div class="ftm-spinner"></div> Fetching projects...



                        </td>



                    </tr>



                `);



    } else {



        tbody.css('opacity', '0.65');



    }







    this.api('projects?' + params.toString()).then(res => {



        tbody.css('opacity', '1');



        this.state.projects = res.items || [];
        this.syncProjectFilterOptions(res);



        this.renderProjectsTable(res.items, tbody);



        this.renderPagination(res);
        this.loadStats();
        this.restoreScrollState(scrollSnap);



    }).catch(err => {



        tbody.css('opacity', '1');



        tbody.html(`<tr><td colspan="11" class="ftm-text-danger" style="text-align:center; padding:20px;">Error loading projects.</td></tr>`);



    });



},







renderDeliveryBadge(p) {
    const isCompleted = (p.status === 'delivery_accepted' || p.status === 'completed' || p.status === 'delivered');
    if (isCompleted) {
        const extCount = parseInt(p.extension_count || 0, 10);
        const extDays = parseInt(p.extension_days || 0, 10);
        const hasExt = (extCount > 0 || extDays > 0);
        const isOntime = (p.is_ontime !== undefined && p.is_ontime !== null)
            ? (parseInt(p.is_ontime) === 1)
            : (!hasExt);
        const canToggle = !!(this.state.isAdmin || this.state.isLeader);

        const extTag = extCount > 0
            ? `<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-size:10px; font-weight:700;" title="Extended ${extCount} time(s) (+${extDays}d)">+${extDays}d (${extCount}x)</span>`
            : '';

        return `
                    <div class="ftm-ontime-toggle-cell" style="display:inline-flex; align-items:center; gap:5px;">
                        ${canToggle ? `
                        <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer;" title="Click to verify/toggle On-Time delivery status (Manager & Leader)">
                            <input type="checkbox" class="ftm-ontime-toggle-checkbox" data-id="${p.id}" ${isOntime ? 'checked' : ''} style="width:14px; height:14px; accent-color:#10b981; cursor:pointer;">
                            <span class="ftm-badge ${isOntime ? 'bg-emerald-soft text-emerald' : 'bg-rose-soft text-rose'} ftm-badge-sm" style="font-weight:700; display:inline-flex; align-items:center; gap:3px;">
                                ${isOntime ? '✓ On-Time' : '✕ Not On-Time'}
                            </span>
                        </label>
                        ` : `
                        <span class="ftm-badge ${isOntime ? 'bg-emerald-soft text-emerald' : 'bg-rose-soft text-rose'} ftm-badge-sm" style="font-weight:700; display:inline-flex; align-items:center; gap:3px;">
                            ${isOntime ? '✓ On-Time' : '✕ Not On-Time'}
                        </span>
                        `}
                        ${extTag}
                    </div>
                `;
    }

    if (p.status === 'delivery_submitted') {
        const extCount = parseInt(p.extension_count || 0, 10);
        const extDays = parseInt(p.extension_days || 0, 10);
        if (extCount > 0) {
            return `<span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm" style="font-weight:700;" title="Extended ${extCount} time(s)">Extended (${extCount}x${extDays > 0 ? ' · +' + extDays + 'd' : ''})</span>`;
        }
        return `<span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">Submitted (0 Ext)</span>`;
    } else if (p.status === 'cancelled') {



        return `<span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm">Cancelled</span>`;



    } else {



        const days = p.days_remaining || {};



        const extCount = parseInt(p.extension_count || 0);



        const extDays = parseInt(p.extension_days || 0);



        let extPill = '';



        if (extCount > 0) {



            extPill = `<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="margin-left:4px; font-weight:700;" title="Extended ${extCount} time(s) (+${extDays} days)">+${extDays}d (${extCount}x)</span>`;



        }



        const daysBadgeClass = `badge-days-${days.urgency || 'safe'}`;



        return `<div style="display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap;">



                    <span class="ftm-badge ${daysBadgeClass}" title="${days.label || ''}">



                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                        <span>${days.short_badge || 'N/A'}</span>



                    </span>



                    ${extPill}



                </div>`;



    }



},







renderMobileProjectCard(p, isMyWork = false) {
    const self = this;
    const teamColors = {



        'Management': 'bg-rose-soft text-rose',



        'WpCraft': 'bg-blue-soft text-blue',



        'WpNexus': 'bg-emerald-soft text-emerald',



        'WpTitans': 'bg-purple-soft text-purple', 'WpHorizon': 'bg-amber-soft text-amber'



    };



    const teamClass = teamColors[p.team_name] || 'bg-blue-soft text-blue';



    const statusBadgeClass = `badge-status-${p.status}`;



    const statusLabel = p.status_label || (p.status ? p.status.replace(/_/g, ' ').toUpperCase() : 'NRA');



    const deliveryBadge = this.renderDeliveryBadge(p);







    let ratingPill = '';



    if (p.status === 'delivery_accepted' && p.client_reviewed === 'yes' && p.star_rating > 0) {



        const starsSymbol = '★'.repeat(Math.round(parseFloat(p.star_rating) || 0));



        ratingPill = `<span class="ftm-star-rating-pill" style="font-size:11px;" title="${p.review_feedback || ''}">⭐ ${ftmStar(p.star_rating)} (${starsSymbol})</span>`;



    }







    const priceHtml = ftmNetCell(p);

    const isActive = !['delivery_accepted', 'completed', 'delivered', 'cancelled'].includes(p.status);
    const daysRem = p.days_remaining || {};
    let cardUrgencyClass = '';
    let cardUrgencyStyle = '';
    if (isActive) {
        if (daysRem.urgency === 'danger' || (daysRem.days !== undefined && daysRem.days < 0)) {
            cardUrgencyClass = 'ftm-card-late';
            cardUrgencyStyle = 'border-left: 4px solid #ef4444 !important; background: rgba(239, 68, 68, 0.08) !important;';
        } else if (daysRem.urgency === 'warning' || (daysRem.days !== undefined && daysRem.days <= 2)) {
            cardUrgencyClass = 'ftm-card-short-deadline';
            cardUrgencyStyle = 'border-left: 4px solid #f59e0b !important; background: rgba(245, 158, 11, 0.06) !important;';
        }
    }

    return `
                <div class="ftm-mobile-project-card ${cardUrgencyClass}" data-id="${p.id}" style="${cardUrgencyStyle}">



                    <div class="ftm-mob-card-header">



                        <div style="display:flex; align-items:center; gap:6px;">



                            <span class="ftm-badge ${teamClass} ftm-badge-sm" style="font-weight:700;">${p.team_name || 'WpCraft'}</span>



                            <span class="ftm-badge ${statusBadgeClass} ftm-badge-sm">${statusLabel}</span>



                        </div>



                        <button type="button" class="ftm-mob-card-view-btn ftm-view-project-btn" data-id="${p.id}" title="View details">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>



                        </button>



                    </div>



                    <div class="ftm-mob-card-body ftm-view-project-btn" data-id="${p.id}" style="cursor:pointer;">



                        <div class="ftm-mob-card-client-row">



                            <span class="ftm-profile-icon" style="${ftmTint(p.profile_name)}">${ftmEsc(ftmInitials(p.profile_name || 'P'))}</span>



                            <div class="ftm-mob-card-client-info">



                                <div class="ftm-mob-card-title">${ftmEsc(p.client_name)}</div>



                                <div class="ftm-mob-card-client-sub">${p.profile_name || 'Profile'} ${p.is_carry_forward ? '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-size:10px; font-weight:700; padding:1px 5px; border-radius:4px;">Carry</span>' : '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-size:10px; font-weight:700; padding:1px 5px; border-radius:4px;">New</span>'} ${ratingPill}</div>



                            </div>



                            <span class="ftm-badge ftm-badge-outline ftm-text-xs ftm-mob-order-id">${ftmEsc(p.order_id)}</span>



                        </div>



                    </div>



                    <div class="ftm-mob-card-footer">



                        <div class="ftm-mob-card-price">${priceHtml}</div>



                        <div class="ftm-mob-card-date">



                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                            <span>${p.delivery_date_formatted || 'N/A'}</span>



                        </div>



                        <div class="ftm-mob-card-delivery-status">${deliveryBadge}</div>
                    </div>
                    ${isMyWork ? `
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-top:10px; padding-top:10px; border-top:1px dashed var(--ftm-border); flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                ${(self.state.timeTracker && self.state.timeTracker.active && self.state.timeTracker.projectId === p.id) ? `
                                    <button type="button" class="ftm-btn ftm-btn-xs ftm-stop-tracker-btn" data-id="${p.id}" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); font-weight:700;">
                                        ⏹ Tracking...
                                    </button>
                                ` : `
                                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-start-tracker-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" data-profile="${ftmEsc(p.profile_name)}" style="color:#10b981; border-color:rgba(16,185,129,0.35);">
                                        ⏱ Timer
                                    </button>
                                `}
                                ${(self.state.isAdmin || self.state.isLeader) ? `
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-tracker-snaps-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" title="View Screenshots">
                                    📸 Screens
                                </button>
                                ` : ''}
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                ${isActive ? `
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-extend-project-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" data-deadline="${p.delivery_date || ''}">
                                    Extend
                                </button>
                                ` : ''}
                                ${(!!(self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy && p.can_edit) ? `
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-edit-project-btn" data-id="${p.id}">
                                    Edit
                                </button>
                                ` : ''}
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-project-btn" data-id="${p.id}">
                                    View
                                </button>
                            </div>
                        </div>
                    ` : `
                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px; margin-top:10px; padding-top:10px; border-top:1px dashed var(--ftm-border); flex-wrap:wrap;">
                            ${isActive ? `
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-extend-project-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" data-deadline="${p.delivery_date || ''}">
                                Extend
                            </button>
                            ` : ''}
                            ${(!!(self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy && p.can_edit) ? `
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-edit-project-btn" data-id="${p.id}">
                                Edit
                            </button>
                            ` : ''}
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-project-btn" data-id="${p.id}">
                                View
                            </button>
                        </div>
                    `}
                </div>
            `;



},







renderProjectsTable(items, tbody) {



    const self = this;



    if (!items || items.length === 0) {



        tbody.html(`



                    <tr>



                        <td colspan="11" style="text-align:center; padding:40px 20px; color:var(--ftm-text-muted);">



                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px; display:block;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>



                            No projects found matching the current filter criteria.



                        </td>



                    </tr>



                `);



        $('#ftm-mobile-projects-list').html(`



                    <div style="text-align:center; padding:32px 16px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:12px; color:var(--ftm-text-muted);">



                        No projects found matching the current filter criteria.



                    </div>



                `);



        return;



    }







    let html = '';



    let mobileCardsHtml = '';



    items.forEach((p, idx) => {



        const days = p.days_remaining;



        let daysBadgeClass = `badge-days-${days.urgency}`;



        if (p.status === 'delivery_accepted') {



            daysBadgeClass = 'badge-days-accepted';



        } else if (p.status === 'delivery_submitted') {



            daysBadgeClass = 'badge-days-submitted';



        } else if (p.status === 'cancelled') {



            daysBadgeClass = 'badge-days-cancelled';



        }







        const statusBadgeClass = `badge-status-${p.status}`;







        const teamColors = {



            'Management': 'bg-rose-soft text-rose',



            'WpCraft': 'bg-blue-soft text-blue',



            'WpNexus': 'bg-emerald-soft text-emerald',



            'WpTitans': 'bg-purple-soft text-purple', 'WpHorizon': 'bg-amber-soft text-amber'



        };



        const teamClass = teamColors[p.team_name] || 'bg-blue-soft text-blue';







        let ratingPill = '';



        if (p.status === 'delivery_accepted') {



            if (p.client_reviewed === 'yes' && p.star_rating > 0) {



                const starsSymbol = '★'.repeat(Math.round(parseFloat(p.star_rating) || 0));



                ratingPill = `<div class="ftm-star-rating-pill" title="${p.review_feedback || 'Client Rating: ' + p.star_rating + ' Stars'}">⭐ ${ftmStar(p.star_rating)} (${starsSymbol})</div>`;



            } else if (p.order_type === 'subscription' || p.order_type === 'outside') {
                ratingPill = `<div class="ftm-order-type-pill ftm-ot-${p.order_type}">${p.order_type === 'subscription' ? 'Subscription' : 'Outside order'}</div>`;
            } else if (p.client_reviewed === 'no') {
                ratingPill = `<div class="ftm-no-review-pill">No Review Left</div>`;



            }



        }







        // Render Assigned Members Cell



        const members = p.assigned_members && p.assigned_members.length > 0 ? p.assigned_members : [



            { id: p.assigned_user_id, name: p.assigned_user_name, avatar: p.assigned_user_avatar, role_label: p.status_label }



        ];







        let avatarsHtml = '';



        const maxAvatars = 3;



        members.slice(0, maxAvatars).forEach(m => {



            avatarsHtml += `<img src="${m.avatar}" class="ftm-avatar" alt="${ftmEsc(m.name)}" title="${ftmEsc(m.name)} (${m.role_label || m.team || ''})">`;



        });







        const primaryName = members[0] ? members[0].name : p.assigned_user_name;



        const extraCount = members.length > 1 ? members.length - 1 : 0;







        const isNearBottom = (idx >= items.length - 2) && items.length > 3;



        const dropClass = isNearBottom ? 'drop-up' : '';







        const popoverMembersHtml = members.map(m => `



                    <div class="ftm-popover-member-item ftm-trigger-member-activity" data-id="${m.id}" style="cursor:pointer;" title="View ${ftmEsc(m.name)}'s activity report">



                        <img src="${m.avatar}" alt="">



                        <div>



                            <div class="ftm-popover-member-name">${ftmEsc(m.name)}</div>



                            <div class="ftm-popover-member-role">${m.team ? m.team + ' • ' : ''}${m.role_label || 'Member'}</div>



                        </div>



                    </div>



                `).join('');







        const popoverHtml = `



                    <div class="ftm-assignee-popover ${dropClass}">



                        <div class="ftm-popover-header">Assigned Members (${members.length})</div>



                        <div class="ftm-popover-members">



                            ${popoverMembersHtml}



                        </div>



                    </div>



                `;







        let extraBadgeHtml = '';



        if (extraCount > 0) {



            extraBadgeHtml = `



                        <div class="ftm-assignee-tooltip-wrap">



                            <span class="ftm-assignee-extra-badge">+${extraCount} more</span>



                            ${popoverHtml}



                        </div>



                    `;



        } else {



            extraBadgeHtml = `<span class="ftm-assignee-role-tag">${members[0].role_label || members[0].team || ''}</span>`;



        }







        const canManageProjects = !!(self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy;
        let editBtnHtml = '';



        if (canManageProjects && p.can_edit) {



            editBtnHtml = `



                        <button type="button" class="ftm-assignee-edit-btn" data-id="${p.id}" data-team="${ftmEsc(p.team_name)}" data-order="${ftmEsc(p.order_id)}" title="Assign / Change team members">



                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>



                            <span>Edit</span>



                        </button>



                    `;



        }







        const assigneesCellHtml = `



                    <div class="ftm-assigned-cell">



                        <div class="ftm-assignee-stack-wrap">



                            <div class="ftm-assignee-avatar-stack ftm-trigger-member-activity" data-id="${members[0].id}" style="cursor:pointer;" title="View ${primaryName}'s activity report">



                                ${avatarsHtml}



                            </div>



                            <div class="ftm-assignee-names-wrap">



                                <span class="ftm-assignee-primary-name ftm-trigger-member-activity" data-id="${members[0].id}" style="cursor:pointer;" title="View ${primaryName}'s activity report">${primaryName}</span>



                                ${extraBadgeHtml}



                            </div>



                        </div>



                        ${editBtnHtml}



                    </div>



                `;







        const isActive = !['delivery_accepted', 'completed', 'delivered', 'cancelled'].includes(p.status);
        const daysRem = p.days_remaining || {};
        let rowUrgencyClass = '';
        let cellUrgencyStyle = '';
        let firstCellStyle = '';
        if (isActive) {
            if (daysRem.urgency === 'danger' || (daysRem.days !== undefined && daysRem.days < 0)) {
                rowUrgencyClass = 'ftm-row-late';
                cellUrgencyStyle = 'background-color: rgba(239, 68, 68, 0.18) !important; border-top: 1px solid rgba(239, 68, 68, 0.45) !important; border-bottom: 1px solid rgba(239, 68, 68, 0.45) !important;';
                firstCellStyle = cellUrgencyStyle + ' border-left: 5px solid #ef4444 !important;';
            } else if (daysRem.urgency === 'warning' || (daysRem.days !== undefined && daysRem.days <= 2)) {
                rowUrgencyClass = 'ftm-row-short-deadline';
                cellUrgencyStyle = 'background-color: rgba(245, 158, 11, 0.14) !important; border-top: 1px solid rgba(245, 158, 11, 0.40) !important; border-bottom: 1px solid rgba(245, 158, 11, 0.40) !important;';
                firstCellStyle = cellUrgencyStyle + ' border-left: 5px solid #f59e0b !important;';
            }
        }

        html += `
                    <tr data-id="${p.id}" class="${rowUrgencyClass}" style="${cellUrgencyStyle}">
                        <td class="ftm-font-medium ftm-text-muted" style="${firstCellStyle}">${idx + 1}</td>

                        <td style="${cellUrgencyStyle}">

                            <span class="ftm-badge ${teamClass} ftm-badge-sm" style="font-weight:700;">

                                ${p.team_name || 'WpCraft'}

                            </span>

                        </td>

                        <td style="${cellUrgencyStyle}">

                            <div class="ftm-profile-badge-cell">

                                <span class="ftm-profile-icon" style="${ftmTint(p.profile_name)}">${ftmEsc(ftmInitials(p.profile_name || 'P'))}</span>

                                <span>${p.profile_name || 'Profile'}</span>
                                ${p.is_carry_forward ? '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-size:10px; font-weight:700; padding:1px 5px; border-radius:4px; margin-left:5px;">Carry</span>' : '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-size:10px; font-weight:700; padding:1px 5px; border-radius:4px; margin-left:5px;">New</span>'}
                            </div>

                        </td>

                        <td class="ftm-font-medium ftm-cell-client-order" style="${cellUrgencyStyle}">
                            <div class="ftm-client-name" style="font-weight:600; font-size:13px; color:var(--ftm-text-primary); line-height:1.3;">${ftmEsc(p.client_name)}</div>
                            <div style="margin-top:3px;"><span class="ftm-badge ftm-badge-outline ftm-text-xs ftm-order-id-badge" style="font-size:10.5px; font-weight:600; padding:1px 6px; letter-spacing:0.3px;">${ftmEsc(p.order_id)}</span></div>
                            ${ratingPill ? `<div style="margin-top:3px;">${ratingPill}</div>` : ''}
                        </td>

                        <td style="${cellUrgencyStyle}">${ftmNetCell(p)}</td>

                        <td class="ftm-nowrap" style="color:var(--ftm-text-secondary); font-size:12px; ${cellUrgencyStyle}">${p.assign_date_formatted || p.created_at_formatted || 'N/A'}</td>
                        <td class="ftm-nowrap" style="${cellUrgencyStyle}">${p.delivery_date_formatted}</td>

                        <td style="${cellUrgencyStyle}">
                            ${self.renderDeliveryBadge(p)}
                        </td>

                        <td style="${cellUrgencyStyle}">
                            ${assigneesCellHtml}
                        </td>

                        <td style="${cellUrgencyStyle}">
                            ${(canManageProjects || p.can_change_status) ? `
                            <select class="ftm-select ftm-select-sm ftm-table-status-changer ${statusBadgeClass}" data-id="${p.id}">
                                <option value="nra" ${p.status === 'nra' ? 'selected' : ''}>NRA</option>
                                <option value="wip" ${p.status === 'wip' ? 'selected' : ''}>WIP</option>
                                <option value="delivery_submitted" ${p.status === 'delivery_submitted' ? 'selected' : ''}>Delivery Submitted</option>
                                <option value="revision" ${p.status === 'revision' ? 'selected' : ''}>Revision</option>
                                <option value="delivery_accepted" ${p.status === 'delivery_accepted' ? 'selected' : ''}>Delivery Accepted</option>
                                <option value="cancelled" ${p.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                            ` : `
                            <span class="ftm-badge ${statusBadgeClass} ftm-badge-sm">${ftmEsc(p.status_label || p.status)}</span>
                            `}
                        </td>
                        <td class="ftm-text-right" style="${cellUrgencyStyle}">
                            <div class="ftm-action-group">
                                ${(isActive && !self.state.isDeputy) ? `
                                <button class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-extend-project-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" data-deadline="${p.delivery_date || ''}" title="Extend project delivery date">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                </button>
                                ` : ''}
                                ${(canManageProjects && p.can_edit) ? `
                                <button class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-edit-project-btn" data-id="${p.id}" title="Edit Order">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                ` : ''}
                                <button class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-view-project-btn" data-id="${p.id}" title="View project details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </td>



                    </tr>



                `;







        mobileCardsHtml += self.renderMobileProjectCard(p);



    });







    tbody.html(html);



    $('#ftm-mobile-projects-list').html(mobileCardsHtml);



},







renderPagination(res) {



    const start = res.total > 0 ? (res.page - 1) * res.per_page + 1 : 0;



    const end = Math.min(res.total, res.page * res.per_page);



    $('#ftm-pagination-info').text(`Showing ${start} to ${end} of ${res.total} projects`);







    const ctrl = $('#ftm-pagination-controls');



    if (!res.pages || res.pages <= 1) {



        ctrl.html('').addClass('hidden');



        return;



    }







    ctrl.removeClass('hidden');



    let btnHtml = '';



    btnHtml += `<button type="button" class="ftm-page-btn" ${res.page <= 1 ? 'disabled' : ''} data-page="${res.page - 1}" title="Previous Page">&lsaquo;</button>`;



    for (let i = 1; i <= res.pages; i++) {



        btnHtml += `<button type="button" class="ftm-page-btn ${i === res.page ? 'active' : ''}" data-page="${i}">${i}</button>`;



    }



    btnHtml += `<button type="button" class="ftm-page-btn" ${res.page >= res.pages ? 'disabled' : ''} data-page="${res.page + 1}" title="Next Page">&rsaquo;</button>`;







    ctrl.html(btnHtml);



    ctrl.find('.ftm-page-btn').on('click', (e) => {



        const p = parseInt($(e.currentTarget).data('page'));



        if (p && p !== this.state.page) {



            this.state.page = p;



            this.loadProjects();



        }



    });



},







updateFormOnTimeStatus() {
    const extCount = Math.max(0, parseInt($('#ftm-form-extension-count').val(), 10) || 0);
    const extDays = Math.max(0, parseInt($('#ftm-form-extension-days').val(), 10) || 0);
    const hasExt = (extCount > 0 || extDays > 0);
    const status = ($('#ftm-form-status').val() || 'nra').toLowerCase();
    const isCompleted = status === 'delivery_accepted' || status === 'completed';
    const isOnTime = !hasExt && isCompleted;
    $('#ftm-form-delivered-without-ext').val(isOnTime ? '1' : '0');

    const badge = $('#ftm-form-ontime-auto-badge');
    if (!badge.length) return;
    if (hasExt) {
        badge
            .attr('class', 'ftm-badge bg-amber-soft text-amber ftm-badge-sm')
            .text(`⏳ Not On-Time (${extCount}x Extended${extDays > 0 ? ' · +' + extDays + 'd' : ''})`);
    } else if (isCompleted) {
        badge
            .attr('class', 'ftm-badge bg-emerald-soft text-emerald ftm-badge-sm')
            .text('✓ On-Time (Completed · 0 Ext)');
    } else if (status === 'cancelled') {
        badge
            .attr('class', 'ftm-badge bg-rose-soft text-rose ftm-badge-sm')
            .text('✕ Cancelled');
    } else {
        badge
            .attr('class', 'ftm-badge bg-blue-soft text-blue ftm-badge-sm')
            .text('⏱️ Auto-Tracked on Completion (0 Ext)');
    }
},

openAddProjectModal() {



    this.state.editingProjectTeam = null;



    $('#ftm-modal-project-title').text('Add New Project');



    $('#ftm-form-project-id').val('');

    $('#ftm-member-edit-notice').remove();
    $('#ftm-form-gross-value').prop('readonly', false).removeAttr('title').css('opacity', '1');
    $('#ftm-form-fee-percent').prop('readonly', false).removeAttr('title').css('opacity', '1');
    $('#ftm-form-order-total').prop('readonly', false).css('opacity', '1');
    $('#ftm-form-team-name').prop('disabled', false).removeAttr('title');
    $('#ftm-form-profile').prop('disabled', false).removeAttr('title');
    $('#ftm-assignee-search').prop('disabled', false);
    $('#ftm-split-panel-btn').removeClass('hidden');

    $('#ftm-project-form')[0].reset();
    $('#ftm-form-sheet-link').val('');
    $('#ftm-form-order-source').val('Query');
    ftmCombined.reset();
    ftmShare.saved = {}; $('#ftm-split-panel').addClass('hidden').empty();







    $('#ftm-form-review-section').addClass('hidden');



    $('#ftm-form-client-reviewed').val('no');
    $('#ftm-form-order-type').val('standard');
    $('#ftm-form-extension-count').val(0);
    $('#ftm-form-extension-days').val(0);
    $('#ftm-form-extension-reason').val('');
    this.updateFormOnTimeStatus();
    ftmSub.fill(null);

    $('#ftm-form-star-rating').val('5.0').trigger('input');
    $('#ftm-form-review-feedback').val('');

    $('#ftm-form-star-rating-wrap, #ftm-form-review-feedback-wrap').addClass('hidden');







    // Reset financial fields



    $('#ftm-form-base-value').val('');



    $('#ftm-form-addon-value').val('');



    $('#ftm-form-addon-note').val('');



    $('#ftm-form-gross-value').val('');



    $('#ftm-form-fee-percent').val('20.0');
    $('#ftm-pricing-formula-hint').text('Net = Total Gross - 20% Fee');



    $('#ftm-form-net-value').val('');







    // Auto-populate datalist of known clients



    if (this.state.knownClients && this.state.knownClients.length) {



        $('#ftm-client-datalist').html(this.state.knownClients.map(c => `<option value="${c}">`).join(''));



    }







    const targetTeam = (this.state.isAdmin || this.state.isLeader) ? ($('#ftm-form-team-name').val() || this.state.userTeam || 'WpCraft') : this.state.userTeam;

    if (this.state.isAdmin || this.state.isLeader) {

        $('#ftm-form-team-name').val(targetTeam);

    }



    this.populateFormMembers(targetTeam, [this.state.userId]);







    // Set default date to 3 days ahead



    const d = new Date();



    d.setDate(d.getDate() + 3);



    const defDate = d.toISOString().split('T')[0];



    $('#ftm-form-delivery-date').val(defDate);



    this.updateProjectDeadlineCountdown(defDate);







    // Clear search and reset badges



    $('#ftm-assignee-search').val('');



    $('#ftm-client-repeat-badge').addClass('hidden').html('');



    $('#ftm-form-gross-value').trigger('input');







    $('#ftm-modal-project').removeClass('hidden');



},







openEditProjectModal(id) {



    const self = this;



    this.api('projects/' + id).then(res => {



        const p = res.project;
        this.state.editingProject = p;
        this.state.editingProjectTeam = p.team_name;

        $('#ftm-modal-project-title').text('Edit Project ' + (p.order_id.startsWith('#') ? p.order_id : '#' + p.order_id));
        $('#ftm-form-project-id').val(p.id);

        const teamSel = $('#ftm-form-team-name');
        if (teamSel.is('select')) {
            if (p.team_name && teamSel.find(`option[value="${p.team_name}"]`).length === 0) {
                teamSel.append(`<option value="${ftmEsc(p.team_name)}">${ftmEsc(p.team_name)}</option>`);
            }
            teamSel.val(p.team_name);
        } else {
            teamSel.val(p.team_name);
        }

        let assignedIds = [];
        if (Array.isArray(p.assigned_user_ids)) {
            assignedIds = p.assigned_user_ids.map(s => parseInt(s)).filter(n => !isNaN(n));
        } else if (typeof p.assigned_user_ids === 'string' && p.assigned_user_ids.trim() !== '') {
            assignedIds = p.assigned_user_ids.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));
        } else if (p.assigned_members && Array.isArray(p.assigned_members)) {
            assignedIds = p.assigned_members.map(m => parseInt(m.id)).filter(n => !isNaN(n));
        } else if (p.assigned_user_id) {
            assignedIds = [parseInt(p.assigned_user_id)];
        }

        this.populateFormMembers(p.team_name, assignedIds);

        // Enable team switching: re-populate members dynamically when team is changed
        teamSel.off('change.editTeam').on('change.editTeam', function () {
            const newTeam = $(this).val();
            self.state.editingProjectTeam = newTeam;
            const currentSelected = [];
            $('#ftm-form-assignees-list input[type="checkbox"]:checked').each(function () {
                currentSelected.push(parseInt($(this).val()));
            });
            self.populateFormMembers(newTeam, currentSelected);
        });

        // Profile matching & preservation
        const profSel = $('#ftm-form-profile');
        if (p.fiverr_profile_id && profSel.find(`option[value="${p.fiverr_profile_id}"]`).length) {
            profSel.val(p.fiverr_profile_id);
        } else if (p.profile_name) {
            let matchedOpt = null;
            profSel.find('option').each(function () {
                if ($(this).text().trim().toLowerCase() === p.profile_name.trim().toLowerCase()) {
                    matchedOpt = $(this).val();
                }
            });
            if (matchedOpt !== null) {
                profSel.val(matchedOpt);
            } else {
                profSel.append(`<option value="${p.fiverr_profile_id || 0}" selected>${ftmEsc(p.profile_name)}</option>`);
                profSel.val(p.fiverr_profile_id || 0);
            }
        }



        $('#ftm-form-client').val(p.client_name);



        $('#ftm-form-order-id').val(p.order_id);
        $('#ftm-form-sheet-link').val(p.project_sheet_link || '');
        $('#ftm-form-order-source').val(p.order_source || 'Query');



        $('#ftm-form-gross-value').val(p.gross_order_value !== undefined ? p.gross_order_value : '');
        $('#ftm-form-tip').val(parseFloat(p.tip_amount) > 0 ? p.tip_amount : '');
        ftmCombined.fill({ is_combined: p.is_combined, order_total_value: p.order_total_value, splits: [] });
        ftmShare.saved = {}; this.api('projects/' + p.id).then(r => { if (r && r.assignee_shares) { ftmShare.saved = r.assignee_shares; $('#ftm-split-panel').addClass('hidden'); ftmShare.render(); } });
        if (p.is_combined && p.id) { this.api('projects/' + p.id).then(r => { if (r && r.combined) ftmCombined.fill(r.combined); }); }



        $('#ftm-form-fee-percent').val(p.fiverr_fee_percent !== undefined ? p.fiverr_fee_percent : '20.0');
        $('#ftm-form-fee-percent').val((p.fiverr_fee_percent !== undefined && p.fiverr_fee_percent !== null) ? p.fiverr_fee_percent : '20.0');



        $('#ftm-form-net-value').val(p.net_delivery_value !== undefined ? p.net_delivery_value : '');



        $('#ftm-form-delivery-date').val(p.delivery_date);



        $('#ftm-form-status').val(p.status);



        $('#ftm-form-notes').val(p.project_notes);
        $('#ftm-form-extension-count').val(p.extension_count !== undefined ? p.extension_count : 0);
        $('#ftm-form-extension-days').val(p.extension_days !== undefined ? p.extension_days : 0);
        $('#ftm-form-extension-reason').val(p.extension_reason || '');
        this.updateFormOnTimeStatus();







        this.updateProjectDeadlineCountdown(p.delivery_date);



        $('#ftm-assignee-search').val('');



        $('#ftm-form-gross-value').trigger('input');



        $('#ftm-form-client').trigger('input');







        // Review fields



        const isReviewed = (p.client_reviewed === 'yes');
        $('#ftm-form-client-reviewed').val(isReviewed ? 'yes' : 'no');
        $('#ftm-form-order-type').val(p.order_type || 'standard');
        ftmSub.fill(p);

        const assignDateVal = p.assign_date || (p.created_at ? p.created_at.slice(0, 10) : '');
        $('#ftm-form-assign-date').val(assignDateVal);

        const delDateVal = p.delivered_date ? p.delivered_date.slice(0, 10) : '';
        $('#ftm-form-delivered-date').val(delDateVal);

        const tipDateVal = p.tip_date ? p.tip_date.slice(0, 10) : '';
        $('#ftm-form-tip-date').val(tipDateVal);

        if (isReviewed && parseFloat(p.star_rating) > 0) {
            $('#ftm-form-star-rating').val(ftmStar(p.star_rating)).trigger('input');
        } else {
            $('#ftm-form-star-rating').val('0.0').trigger('input');
        }



        $('#ftm-form-review-feedback').val(p.review_feedback || '');







        if (p.status === 'delivery_accepted') {



            $('#ftm-form-review-section').removeClass('hidden');



        } else {



            $('#ftm-form-review-section').addClass('hidden');



        }







        if (p.client_reviewed === 'no') {



            $('#ftm-form-star-rating-wrap, #ftm-form-review-feedback-wrap').addClass('hidden');



        } else {



            $('#ftm-form-star-rating-wrap, #ftm-form-review-feedback-wrap').removeClass('hidden');



        }







        // Populate extension tracking fields
        $('#ftm-form-extension-count').val(p.extension_count || 0);
        $('#ftm-form-extension-days').val(p.extension_days || 0);
        $('#ftm-form-extension-reason').val(p.extension_reason || '');
        this.updateFormOnTimeStatus();

        // Member view restrictions vs Leader/Admin permissions
        const isLeaderOrAdmin = !!(self.state.isAdmin || self.state.isLeader);
        $('#ftm-member-edit-notice').remove();
        if (!isLeaderOrAdmin) {
            $('#ftm-project-form').prepend(`
                        <div id="ftm-member-edit-notice" style="margin-bottom:14px; padding:10px 14px; border-radius:8px; background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.25); color:var(--ftm-text-primary); font-size:12.5px; display:flex; align-items:center; gap:8px;">
                            <span style="font-size:16px;">🔒</span>
                            <div>
                                <strong>Member View:</strong> Financial values and team assignments are locked and managed by your Squad Leader. You can edit delivery status, settlement dates, client tips, and review ratings.
                            </div>
                        </div>
                    `);
            $('#ftm-form-gross-value').prop('readonly', true).attr('title', 'Locked: Managed by Squad Leader').css('opacity', '0.7');
            $('#ftm-form-fee-percent').prop('readonly', true).attr('title', 'Locked: Managed by Squad Leader').css('opacity', '0.7');
            $('#ftm-form-order-total').prop('readonly', true).css('opacity', '0.7');
            $('#ftm-form-team-name').prop('disabled', true).attr('title', 'Locked: Managed by Squad Leader');
            $('#ftm-form-profile').prop('disabled', true).attr('title', 'Locked: Managed by Squad Leader');
            $('#ftm-form-assignees-list input').prop('disabled', true);
            $('#ftm-assignee-search').prop('disabled', true);
            $('#ftm-split-panel-btn').addClass('hidden');
        } else {
            $('#ftm-form-gross-value').prop('readonly', false).removeAttr('title').css('opacity', '1');
            $('#ftm-form-fee-percent').prop('readonly', false).removeAttr('title').css('opacity', '1');
            $('#ftm-form-order-total').prop('readonly', false).css('opacity', '1');
            $('#ftm-form-team-name').prop('disabled', false).removeAttr('title');
            $('#ftm-form-profile').prop('disabled', false).removeAttr('title');
            $('#ftm-form-assignees-list input').prop('disabled', false);
            $('#ftm-assignee-search').prop('disabled', false);
            $('#ftm-split-panel-btn').removeClass('hidden');
        }

        $('#ftm-modal-project').removeClass('hidden');



    }).catch(console.error);



},







saveProjectForm() {



    const id = $('#ftm-form-project-id').val();



    const status = $('#ftm-form-status').val();







    const selectedMemberIds = [];



    $('#ftm-form-assignees-list input[type="checkbox"]:checked').each(function () {



        selectedMemberIds.push(parseInt($(this).val()));



    });

    const editProj = this.state.editingProject || {};
    if (selectedMemberIds.length === 0 && id && editProj.id) {
        if (Array.isArray(editProj.assigned_user_ids) && editProj.assigned_user_ids.length) {
            editProj.assigned_user_ids.forEach(x => { const n = parseInt(x); if (!isNaN(n) && n > 0) selectedMemberIds.push(n); });
        } else if (typeof editProj.assigned_user_ids === 'string' && editProj.assigned_user_ids.trim()) {
            editProj.assigned_user_ids.split(',').forEach(x => { const n = parseInt(x.trim()); if (!isNaN(n) && n > 0) selectedMemberIds.push(n); });
        } else if (editProj.assigned_user_id) {
            selectedMemberIds.push(parseInt(editProj.assigned_user_id));
        }
    }

    if (!ftmShare.valid()) {
        ftmShare.autoBalance();
        this.toast('Assignee value split was automatically balanced to 100%.', 'info');
    }
    if (selectedMemberIds.length === 0) {
        this.toast('Please assign at least one team member.', 'error');
        return;
    }

    let finalTeam = $('#ftm-form-team-name').val();
    if (!finalTeam) {
        finalTeam = this.state.editingProjectTeam || editProj.team_name || this.state.userTeam || 'WpCraft';
    }

    const profileSelect = $('#ftm-form-profile');
    let profileId = profileSelect.val() ? parseInt(profileSelect.val()) : null;
    let profileName = (profileId && profileSelect.find('option:selected').length) ? profileSelect.find('option:selected').text().trim() : '';
    if (!profileName || profileName.toLowerCase() === 'select profile...') {
        profileName = editProj.profile_name || '';
        if (!profileId && editProj.fiverr_profile_id) {
            profileId = editProj.fiverr_profile_id;
        }
    }







    const addonNote = ($('#ftm-form-addon-note').val() || '').trim();



    let baseNotes = ($('#ftm-form-notes').val() || '').trim();



    if (addonNote && !baseNotes.includes(addonNote)) {



        baseNotes = (baseNotes ? baseNotes + "\n" : "") + "[Addon / Pricing]: " + addonNote;



    }







    const data = {



        team_name: finalTeam,



        fiverr_profile_id: profileId,



        profile_name: profileName,



        client_name: $('#ftm-form-client').val().trim(),



        order_id: $('#ftm-form-order-id').val().trim(),
        project_sheet_link: ($('#ftm-form-sheet-link').val() || '').trim(),
        order_source: $('#ftm-form-order-source').val() || 'Query',



        gross_order_value: parseFloat($('#ftm-form-gross-value').val()) || 0.00,
        tip_amount: parseFloat($('#ftm-form-tip').val()) || 0,
        fiverr_fee_percent: ($('#ftm-form-fee-percent').val() !== '' && $('#ftm-form-fee-percent').val() !== null && !isNaN(parseFloat($('#ftm-form-fee-percent').val()))) ? Math.max(0, parseFloat($('#ftm-form-fee-percent').val())) : 20.00,
        net_delivery_value: parseFloat($('#ftm-form-net-value').val()) || 0.00,



        delivery_date: $('#ftm-form-delivery-date').val(),



        assigned_user_id: selectedMemberIds[0],



        assigned_user_ids: selectedMemberIds,
        ...ftmShare.collect(),



        status: status,



        project_notes: baseNotes,



        client_reviewed: ($('#ftm-form-client-reviewed').val() || 'no'),
        order_type: $('#ftm-form-order-type').val() || 'standard',
        assign_date: $('#ftm-form-assign-date').val() || editProj.assign_date || (editProj.created_at ? editProj.created_at.slice(0, 10) : null),
        delivered_date: $('#ftm-form-delivered-date').val() || null,
        tip_date: $('#ftm-form-tip-date').val() || null,
        ...ftmSub.collect(),

        star_rating: ($('#ftm-form-client-reviewed').val() === 'yes') ? ftmRating('#ftm-form-star-rating') : 0.0,



        review_feedback: $('#ftm-form-review-feedback').val(),
        extension_count: Math.max(0, parseInt($('#ftm-form-extension-count').val(), 10) || 0),
        extension_days: Math.max(0, parseInt($('#ftm-form-extension-days').val(), 10) || 0),
        extension_reason: ($('#ftm-form-extension-reason').val() || '').trim(),
        delivered_without_extension: ((Math.max(0, parseInt($('#ftm-form-extension-count').val(), 10) || 0) === 0) && ['delivery_accepted', 'completed'].includes(($('#ftm-form-status').val() || '').toLowerCase())) ? 1 : 0,
        ...ftmCombined.collect()



    };







    const endpoint = id ? 'projects/' + id : 'projects';
    const saveBtn = $('#ftm-btn-save-project');
    saveBtn.prop('disabled', true).html('<span class="ftm-spinner"></span> Saving Project...');

    this.api(endpoint, 'POST', data).then(res => {
        saveBtn.prop('disabled', false).text('Save Project');
        $('#ftm-modal-project').addClass('hidden');
        this.toast(res.message || 'Project saved successfully!', 'success');
        this.loadProjects();
        if (typeof this.loadMyAssignedProjects === 'function') {
            this.loadMyAssignedProjects();
        }
        this.loadStats();
        this.loadAlerts();
    }).catch(err => {
        saveBtn.prop('disabled', false).text('Save Project');
        this.toast(err.message || 'Error saving project', 'error');
    });



},







openQuickAssignModal(projectId, teamName, orderId) {



    const self = this;



    $('#ftm-quick-assign-project-id').val(projectId);



    $('#ftm-quick-assign-search').val('');







    if (orderId) {



        $('#ftm-quick-assign-project-order').text('#' + String(orderId).replace('#', ''));



    }







    $('#ftm-quick-assign-members-list').html('<div class="ftm-spinner" style="margin:20px auto; display:block;"></div>');



    $('#ftm-modal-quick-assign').removeClass('hidden');







    this.api('projects/' + projectId).then(res => {



        const p = res.project;

        $('#ftm-quick-assign-project-order').text('#' + p.order_id.replace('#', ''));

        const pTeam = p.team_name || teamName || self.state.userTeam || 'WpCraft';
        $('#ftm-quick-assign-team-select').val(pTeam);

        let assignedIds = [];

        if (Array.isArray(p.assigned_user_ids)) {

            assignedIds = p.assigned_user_ids.map(s => parseInt(s)).filter(n => !isNaN(n));

        } else if (typeof p.assigned_user_ids === 'string' && p.assigned_user_ids.trim() !== '') {

            assignedIds = p.assigned_user_ids.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));

        } else if (p.assigned_members && Array.isArray(p.assigned_members)) {

            assignedIds = p.assigned_members.map(m => parseInt(m.id)).filter(n => !isNaN(n));

        } else if (p.assigned_user_id) {

            assignedIds = [parseInt(p.assigned_user_id)];

        }

        const renderQuickMembers = (targetTeam, preSelected = assignedIds) => {
            const members = self.state.teamMembers.filter(m => {
                if (self.state.isAdmin && ftmIsMgmt(targetTeam)) return true;
                return !targetTeam || targetTeam === 'all' || m.team === targetTeam || m.is_global || m.is_admin || parseInt(m.id) === 1;
            });

            let listHtml = '';
            if (members.length === 0) {
                listHtml = '<div class="ftm-text-muted ftm-text-sm" style="padding:10px; text-align:center;">No members found for this team.</div>';
            } else {
                members.forEach(m => {
                    const isChecked = preSelected.includes(parseInt(m.id));
                    listHtml += `
                                <label class="ftm-assignee-checkbox-item ${isChecked ? 'checked' : ''}" data-name="${m.name.toLowerCase()}">
                                    <div class="ftm-assignee-user-info">
                                        <img src="${m.avatar}" alt="${ftmEsc(m.name)}">
                                        <div>
                                            <div class="ftm-user-title">${ftmEsc(m.name)}</div>
                                            <div class="ftm-user-subtitle">${m.team} &bull; ${ftmEsc(m.role_label)}</div>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="quick_assigned_ids[]" value="${m.id}" class="ftm-assignee-custom-cb" ${isChecked ? 'checked' : ''}>
                                </label>
                            `;
                });
            }
            $('#ftm-quick-assign-members-list').html(listHtml);
        };

        renderQuickMembers(pTeam, assignedIds);

        $('#ftm-quick-assign-team-select').off('change').on('change', function () {
            const currentSelected = [];
            $('#ftm-quick-assign-members-list input[type="checkbox"]:checked').each(function () {
                currentSelected.push(parseInt($(this).val()));
            });
            renderQuickMembers($(this).val(), currentSelected);
        });



    }).catch(err => {



        self.toast(err.message || 'Error loading project assignees', 'error');



    });



},







saveQuickAssignees() {



    const self = this;



    const projectId = $('#ftm-quick-assign-project-id').val();



    if (!projectId) return;







    const selectedIds = [];



    $('#ftm-quick-assign-members-list input[type="checkbox"]:checked').each(function () {



        selectedIds.push(parseInt($(this).val()));



    });







    if (selectedIds.length === 0) {



        this.toast('Please select at least one assigned member.', 'error');



        return;



    }







    const saveBtn = $('#ftm-quick-assign-submit-btn');



    saveBtn.prop('disabled', true).html('<span class="ftm-spinner"></span> <span>Saving...</span>');







    const newTeam = $('#ftm-quick-assign-team-select').val();
    this.api(`projects/${projectId}/assignees`, 'POST', { assigned_user_ids: selectedIds, team_name: newTeam })



        .then(res => {



            saveBtn.prop('disabled', false).html('<span>Save Assignees</span>');



            $('#ftm-modal-quick-assign').addClass('hidden');



            self.toast(res.message || 'Assigned members updated successfully!', 'success');



            self.loadProjects();



            self.loadStats();



            if (self.state.activeProjectId === parseInt(projectId)) {



                self.openProjectDetailsModal(projectId);



            }



        })



        .catch(err => {



            saveBtn.prop('disabled', false).html('<span>Save Assignees</span>');



            self.toast(err.message || 'Failed to update assignees.', 'error');



        });



},







openProjectDetailsModal(id, presetStatus = null) {



    const self = this;
    const isSameModalOpen = !$('#ftm-modal-details').hasClass('hidden') && String(this.state.activeProjectId) === String(id);
    const modalScrollSnap = isSameModalOpen ? this.captureScrollState() : null;



    this.state.activeProjectId = id;



    this.api('projects/' + id).then(res => {



        const p = res.project;
        self.state.activeProjectStatus = p.status;



        $('#ftm-details-team-badge').text(p.team_name || 'WpCraft');



        $('#ftm-details-order-id').text(p.order_id);



        $('#ftm-details-spec-order-id').text(p.order_id);



        $('#ftm-details-client-title').text(p.client_name + ' – ' + p.profile_name);



        $('#ftm-details-profile').text(p.profile_name);



        $('#ftm-details-client-username').text(p.client_name);







        // Financial calculations & KPIs



        const gross = parseFloat(p.gross_order_value) || 0;



        const net = parseFloat(p.net_delivery_value) || 0;



        const feePercent = (p.fiverr_fee_percent !== undefined && p.fiverr_fee_percent !== null) ? parseFloat(p.fiverr_fee_percent) : 20;
        const fee = (p.fiverr_fee_amount !== undefined && p.fiverr_fee_amount !== null) ? parseFloat(p.fiverr_fee_amount) : (gross * (feePercent / 100));

        $('#ftm-details-gross').text('$' + gross.toFixed(2));
        $('#ftm-details-net').text('$' + net.toFixed(2));
        const tipVal = parseFloat(p.tip_amount) || 0;
        const tipFeePercent = feePercent;
        const tipNet = tipVal * (1 - tipFeePercent / 100);
        $('#ftm-details-net').closest('.ftm-details-kpi-card').find('.ftm-details-kpi-sub').text(tipVal > 0 ? '+ $' + tipNet.toFixed(2) + ' tip after ' + tipFeePercent + '% fee ($' + tipVal.toFixed(2) + ' paid)' : 'Agency Net Earnings');

        $('#ftm-details-fee-calc').text('Marketplace Fee (' + feePercent + '%): -$' + fee.toFixed(2));
        ftmCombined.renderDetails(res.combined);

        // Google Sheet Link & Repeat Client Badge in Details
        if (p.project_sheet_link && p.project_sheet_link.trim() !== '') {
            const sLink = p.project_sheet_link.trim();
            let targetUrl = sLink;
            if (!targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
                targetUrl = 'https://drive.google.com/drive/search?q=' + encodeURIComponent(sLink);
            }
            $('#ftm-details-sheet-btn')
                .attr('href', targetUrl)
                .attr('target', '_blank')
                .attr('rel', 'noopener noreferrer')
                .removeClass('hidden');
            $('#ftm-details-sheet-name').text(sLink.length > 55 ? sLink.substring(0, 52) + '...' : sLink);
            $('#ftm-details-resources-banner').removeClass('hidden');
        } else {
            $('#ftm-details-sheet-btn').addClass('hidden');
            $('#ftm-details-sheet-name').text('No sheet attached');
        }

        if (p.order_source === 'Repeat Client' || p.is_repeat_client) {
            $('#ftm-details-repeat-badge').removeClass('hidden');
        } else {
            $('#ftm-details-repeat-badge').addClass('hidden');
        }

        if (p.order_link && p.order_link.startsWith('http')) {
            $('#ftm-details-order-link-btn').attr('href', p.order_link).removeClass('hidden');
        } else {
            $('#ftm-details-order-link-btn').addClass('hidden');
        }







        // Render Assigned Members in Details Modal with spacious cards



        const dMembers = p.assigned_members && p.assigned_members.length > 0 ? p.assigned_members : [



            { id: p.assigned_user_id, name: p.assigned_user_name, avatar: p.assigned_user_avatar, role_label: p.status_label }



        ];
        const dShares = res.assignee_shares || {};
        const dNet = parseFloat(p.net_delivery_value) || 0;
        const dMulti = dMembers.length > 1;
        const dDelivered = p.status === 'delivery_submitted' || p.status === 'delivery_accepted';







        let dCardsHtml = '';



        dMembers.forEach(m => {



            dCardsHtml += `



                        <div class="ftm-details-assignee-card">



                            <img src="${m.avatar || ''}" class="ftm-details-assignee-avatar" alt="${m.name || 'Member'}">



                            <div class="ftm-details-assignee-meta">



                                <div class="ftm-details-assignee-name">${ftmEsc(m.name)}</div>



                                <div class="ftm-details-assignee-role">${m.team || p.team_name || 'WpCraft'} &bull; ${m.role_label || 'WordPress Developer'}</div>



                            </div>



                            ${(() => {
                    const pct = dMulti ? (dShares[m.id] !== undefined ? parseFloat(dShares[m.id]) : Math.round(10000 / dMembers.length) / 100) : 100;
                    const money = (dNet * pct / 100).toFixed(2);
                    return '<span class="ftm-share-chip' + (dDelivered ? ' ftm-share-paid' : '') + '"><b>$' + money + '</b><small>' + pct + '% ' + (dDelivered ? 'of delivery' : 'planned') + '</small></span>';
                })()}



                        </div>



                    `;



        });



        $('#ftm-details-member').html(dCardsHtml);







        const canManageDetails = !!(self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy;
        if (canManageDetails && p.can_edit) {
            $('#ftm-details-edit-assignees-container').html(`${dMulti ? '<button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-edit-split-btn" id="ftm-details-edit-split-btn" style="font-size:11.5px; padding:3px 8px; border-radius:6px; margin-right:6px;">Edit split</button>' : ''}
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-details-edit-assignees-btn" style="font-size:11.5px; padding:3px 8px; border-radius:6px;">Edit Assignees</button>
                    `);
        } else {
            $('#ftm-details-edit-assignees-container').html('');
        }







        $('#ftm-details-delivery').text(p.delivery_date_formatted);







        $('#ftm-details-remaining').html(self.renderDeliveryBadge(p));







        // Client Review in details grid



        if (p.status === 'delivery_accepted') {



            if (p.client_reviewed === 'yes' && p.star_rating > 0) {



                const stars = '★'.repeat(Math.round(parseFloat(p.star_rating) || 0));



                $('#ftm-details-rating-display').html(`



                            <span class="ftm-badge bg-emerald-soft text-emerald" style="font-weight:700;" title="${p.review_feedback || ''}">



                                ⭐ ${ftmStar(p.star_rating)} (${stars})



                            </span>



                        `);



            } else if (p.order_type === 'subscription' || p.order_type === 'outside') {
                $('#ftm-details-rating-display').html(`<span class="ftm-badge ftm-badge-outline ftm-text-xs">${p.order_type === 'subscription' ? ftmEsc(ftmSub.line(p)) + ' - no review expected' : 'Outside order - no review expected'}</span>`);
            } else if (p.client_reviewed === 'no') {
                $('#ftm-details-rating-display').html(`<span class="ftm-badge ftm-badge-outline ftm-text-xs">No Review Left</span>`);



            } else {



                $('#ftm-details-rating-display').html(`<span class="ftm-badge ftm-badge-outline ftm-text-xs">Review Pending</span>`);



            }



        } else {



            $('#ftm-details-rating-display').html(`<span class="ftm-text-muted ftm-text-xs">&mdash;</span>`);



        }







        $('#ftm-details-current-status')



            .attr('class', 'ftm-badge badge-status-' + p.status)



            .text(p.status_label);







        $('#ftm-details-new-status-select').val(p.status);



        $('#ftm-notes-count').text(res.notes_list ? res.notes_list.length : 0);







        // Populate Details Review Box
        const curReviewed = (p.client_reviewed === 'yes') ? 'yes' : 'no';
        $('#ftm-details-client-reviewed').val(curReviewed);
        $('#ftm-details-star-rating').val(p.star_rating > 0 ? ftmStar(p.star_rating) : '5.0').trigger('input');
        $('#ftm-details-review-feedback').val(p.review_feedback || '');

        if (p.status === 'delivery_accepted') {
            $('#ftm-details-review-section').removeClass('hidden');
        } else {
            $('#ftm-details-review-section').addClass('hidden');
        }

        if (curReviewed === 'no') {
            $('#ftm-details-star-rating-wrap, #ftm-details-review-feedback-wrap').addClass('hidden');
        } else {
            $('#ftm-details-star-rating-wrap, #ftm-details-review-feedback-wrap').removeClass('hidden');
        }

        // On-Time Delivery Checkbox in Details Modal (for completed/delivered orders)
        const isCompletedOrder = (p.status === 'delivery_accepted' || p.status === 'completed' || p.status === 'delivered');
        if (isCompletedOrder) {
            const extCount = parseInt(p.extension_count || 0, 10);
            const extDays = parseInt(p.extension_days || 0, 10);
            const hasExt = (extCount > 0 || extDays > 0);
            const isOntimeVal = (p.is_ontime !== undefined && p.is_ontime !== null) ? (parseInt(p.is_ontime) === 1) : (!hasExt);
            $('#ftm-details-ontime-card').removeClass('hidden');
            $('#ftm-details-ontime-checkbox').prop('checked', isOntimeVal);
            $('#ftm-details-ontime-badge')
                .removeClass('bg-emerald-soft text-emerald bg-rose-soft text-rose')
                .addClass(isOntimeVal ? 'bg-emerald-soft text-emerald' : 'bg-rose-soft text-rose')
                .text(isOntimeVal ? '✓ On-Time Delivery' : '✕ Not On-Time / Delayed');
            $('#ftm-details-ontime-checkbox').prop('disabled', !self.state.isAdmin && !self.state.isLeader);
        } else {
            $('#ftm-details-ontime-card').addClass('hidden');
        }

        // Populate Monthly Settlement & Attribution (Delivered Date, Tip Date, Tip Amount)
        const delDateVal = p.delivered_date ? p.delivered_date.slice(0, 10) : '';
        $('#ftm-details-delivered-date').val(delDateVal);

        const tipDateVal = p.tip_date ? p.tip_date.slice(0, 10) : '';
        $('#ftm-details-tip-date').val(tipDateVal);

        const tipAmtVal = parseFloat(p.tip_amount) || 0;
        $('#ftm-details-tip-amount').val(tipAmtVal > 0 ? tipAmtVal.toFixed(2) : '');
        $('#ftm-details-tip-input').val(tipAmtVal > 0 ? tipAmtVal.toFixed(2) : '');

        // Show settlement card for all delivered, submitted, accepted orders, or any order with dates/tips set
        const isDeliveredOrSubmitted = (p.status === 'delivery_accepted' || p.status === 'delivery_submitted' || p.status === 'completed' || !!p.delivered_date || !!p.tip_date || tipAmtVal > 0);
        $('#ftm-details-settle-card').toggleClass('hidden', !isDeliveredOrSubmitted);

        if (p.is_cross_month_tip) {
            const tipFeePct = (p.fiverr_fee_percent !== undefined && p.fiverr_fee_percent !== null) ? parseFloat(p.fiverr_fee_percent) : 20;
            const tipNetVal = tipAmtVal * (1 - tipFeePct / 100);
            $('#ftm-details-cross-month-callout').removeClass('hidden').html(`
                <div style="display:flex; align-items:flex-start; gap:8px;">
                    <span style="font-size:16px;">🎁</span>
                    <div>
                        <strong style="color:#f59e0b;">Cross-Month Tip Attribution:</strong> This project was delivered in <strong>${p.delivered_month || 'prior month'}</strong> (base net of <strong>$${parseFloat(p.net_delivery_value || 0).toFixed(2)}</strong> credited there).
                        The client tip of <strong>+$${tipNetVal.toFixed(2)} net</strong> ($${tipAmtVal.toFixed(2)} paid) was received on <strong>${p.tip_date_formatted || p.tip_date}</strong> and counts towards <strong>${p.tip_month || 'tip month'}</strong> earnings.
                    </div>
                </div>
            `);
        } else {
            $('#ftm-details-cross-month-callout').addClass('hidden');
        }

        // Ensure buttons are reset and never stuck in disabled or 'Saving...' state
        $('#ftm-details-save-review-btn').prop('disabled', false).html('<span>💾</span> Save Review & Rating');
        $('#ftm-details-save-settle-btn').prop('disabled', false).html('<span>💾</span> Save Dates & Tips');
        $('#ftm-details-tip-save').prop('disabled', false).text('Save tip');

        // Reset tabs to Timeline tab



        $('.ftm-detail-tab-btn').removeClass('active');



        $('.ftm-detail-tab-btn[data-tab="timeline"]').addClass('active');



        $('#ftm-tab-timeline').removeClass('hidden').addClass('active');



        $('#ftm-tab-notes, #ftm-tab-dailylogs, #ftm-tab-tasks').addClass('hidden').removeClass('active');







        $('#ftm-details-tip-box').toggleClass('hidden', !p.can_edit);
        $('#ftm-details-tip-input').val(tipVal > 0 ? tipVal : '');
        // Permissions for Edit / Delete in Details



        // Permissions for Edit / Delete in Details
        const canEdit = !!(self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy && !!p.can_edit;
        $('#ftm-details-edit-btn, #ftm-details-btn-edit-order').toggleClass('hidden', !canEdit);

        const canDelete = !self.state.isDeputy && !!p.can_delete;
        $('#ftm-details-delete-btn, #ftm-details-btn-delete-order').toggleClass('hidden', !canDelete);

        const hasExt = parseInt(p.extension_count || 0) > 0 || parseInt(p.extension_days || 0) > 0;
        $('#ftm-details-btn-reset-extension').toggleClass('hidden', !(canEdit && hasExt));
        $('.ftm-quick-status-form').toggleClass('hidden', !canEdit);







        // Render Timeline



        let timelineHtml = '';



        if (res.history && res.history.length > 0) {



            res.history.forEach(h => {



                timelineHtml += `



                            <div class="ftm-timeline-item${h.is_event ? ' ftm-timeline-event' : ''}">



                                <div class="ftm-timeline-dot"></div>



                                <div class="ftm-timeline-content">



                                    <div class="ftm-timeline-header">



                                        <strong>${h.is_event ? ftmEsc(h.user_name) + ' <span class="ftm-tl-event-tag">' + (/extend/i.test(h.note) ? 'Extension' : 'Assignment') + '</span>' : ftmEsc(h.user_name) + ' changed status to <span class="ftm-badge badge-status-' + h.new_status + ' ftm-badge-sm">' + h.new_label + '</span>'}</strong>



                                        <span class="ftm-text-muted ftm-text-xs">${h.date_formatted}</span>



                                    </div>



                                    ${h.note ? `<div class="ftm-text-sm" style="margin-top:4px;">${ftmEsc(h.note)}</div>` : ''}



                                </div>



                            </div>



                        `;



            });



        } else {



            timelineHtml = '<div class="ftm-text-muted ftm-text-sm">No status changes recorded yet.</div>';



        }



        $('#ftm-timeline-list').html(timelineHtml);







        // Render Notes



        let notesHtml = '';



        if (res.notes_list && res.notes_list.length > 0) {



            res.notes_list.forEach(n => {



                notesHtml += `



                            <div class="ftm-note-card">



                                <img src="${n.user_avatar}" class="ftm-avatar ftm-avatar-sm" alt="">



                                <div style="flex:1;">



                                    <div class="ftm-flex-between">



                                        <strong class="ftm-text-sm">${n.user_name}</strong>



                                        <span class="ftm-text-muted ftm-text-xs">${n.date_formatted}</span>



                                    </div>



                                    <div class="ftm-text-sm" style="margin-top:4px; line-height:1.5;">${ftmEsc(n.note)}</div>



                                </div>



                            </div>



                        `;



            });



        } else {



            notesHtml = '<div class="ftm-text-muted ftm-text-sm">No notes added yet. Add one above!</div>';



        }



        $('#ftm-notes-feed-list').html(notesHtml);







        // Render Daily Progress Logs



        $('#ftm-dailylogs-count').text(res.daily_logs ? res.daily_logs.length : 0);



        let dailyLogsHtml = '';



        if (res.daily_logs && res.daily_logs.length > 0) {



            res.daily_logs.forEach(l => {



                const blockerBadge = l.has_blocker ? `<span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm" style="margin-left:6px;">⚠️ Blocker</span>` : '';



                dailyLogsHtml += `



                            <div class="ftm-note-card" style="margin-bottom:10px; border-left:3px solid var(--ftm-primary, #3b82f6);">



                                <img src="${l.member_avatar}" class="ftm-avatar ftm-avatar-sm" alt="${ftmEsc(l.member_name)}">



                                <div style="flex:1;">



                                    <div class="ftm-flex-between">



                                        <div>



                                            <strong class="ftm-text-sm">${ftmEsc(l.member_name)}</strong>



                                            ${blockerBadge}



                                        </div>



                                        <div style="display:flex; align-items:center; gap:8px;">



                                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">${l.progress_percent}% Progress</span>



                                            <span class="ftm-text-muted ftm-text-xs">${l.report_date_formatted || l.report_date}</span>



                                        </div>



                                    </div>



                                    <div class="ftm-text-sm" style="margin-top:6px; line-height:1.4;">



                                        ${l.tasks_completed ? `<div><strong class="text-emerald">Completed:</strong> ${ftmEsc(l.tasks_completed)}</div>` : ''}



                                        ${l.work_in_progress ? `<div style="margin-top:4px;"><strong class="text-blue">WIP:</strong> ${ftmEsc(l.work_in_progress)}</div>` : ''}



                                        ${l.next_day_plan ? `<div style="margin-top:4px;"><strong class="text-amber">Next:</strong> ${ftmEsc(l.next_day_plan)}</div>` : ''}



                                        ${l.blocker_description ? `<div style="margin-top:4px; color:#ef4444;"><strong>Blocker:</strong> ${ftmEsc(l.blocker_description)}</div>` : ''}



                                    </div>



                                    <div style="font-size:11px; color:var(--ftm-text-muted); margin-top:4px;">



                                        Time logged: <strong>${l.hours_spent || 0} hrs</strong>



                                    </div>



                                </div>



                            </div>



                        `;



            });



        } else {



            dailyLogsHtml = '<div class="ftm-text-muted ftm-text-sm" style="text-align:center; padding:16px;">No daily progress logs filed for this project yet. Click &quot;Log Today&#39;s Progress&quot; above to file one.</div>';



        }



        $('#ftm-dailylogs-feed-list').html(dailyLogsHtml);







        $('#ftm-project-log-standup-btn').off('click').on('click', function (e) {



            e.preventDefault();



            $('#ftm-modal-details').addClass('hidden');



            self.openDailyStandupModal({



                project_id: p.id,



                order_id: p.order_id,



                client_name: p.client_name,



                status_label: p.status_label



            });



        });







        // Render Extension Banner
        self.renderProjectExtensionBanner(p);

        // Render Leader Quality Assurance & Approval Gates
        try {
            const gatesData = res.leader_approvals || (res.tasks && res.tasks.leader_approvals) || null;
            self.renderLeaderQualityGates(p, gatesData);
        } catch (gateErr) {
            console.error('renderLeaderQualityGates error:', gateErr);
        }

        // Render Tasks & Milestones
        try {
            self.renderProjectTasks(p.id, res.tasks ? (res.tasks.tasks || []) : []);
        } catch (taskErr) {
            console.error('renderProjectTasks error:', taskErr);
        }
        $('#ftm-details-tasks-count').text(res.tasks ? (res.tasks.total_tasks || 0) : 0);

        // If opened via table status transition (e.g. Delivery Accepted), pre-select target status AFTER data loads (no race condition)
        if (presetStatus && presetStatus !== p.status) {
            $('#ftm-details-new-status-select').val(presetStatus).trigger('change');
        }

        $('#ftm-modal-details').removeClass('hidden');
        if (modalScrollSnap) {
            self.restoreScrollState(modalScrollSnap);
        }



    }).catch(err => {
        console.error('openProjectDetailsModal error:', err);
        self.toast('Failed to open project details: ' + ((err && err.message) || 'Unknown error'), 'error');
    });



},

renderProjectTasks(projectId, tasks) {
    const self = this;
    const container = $('#ftm-details-tasks-list');
    if (!container.length) return;

    const list = Array.isArray(tasks) ? tasks : [];
    if (!list.length) {
        container.html('<div class="ftm-text-muted ftm-text-sm" style="text-align:center; padding:18px; background:var(--ftm-card-bg); border:1px dashed var(--ftm-border); border-radius:8px;">No pipeline tasks or milestones yet. Click "Add Task" above to create one.</div>');
        return;
    }

    const canDelete = !this.state.isDeputy && !!(this.state.isAdmin || this.state.isLeader);
    const priorityMap = {
        urgent: { label: 'Urgent', cls: 'bg-rose-soft text-rose' },
        high: { label: 'High', cls: 'bg-amber-soft text-amber' },
        medium: { label: 'Medium', cls: 'bg-blue-soft text-blue' },
        low: { label: 'Low', cls: 'ftm-badge-outline' }
    };

    let html = '';
    list.forEach(t => {
        const isDone = !!(t.is_done || t.status === 'done');
        const pr = priorityMap[t.priority] || priorityMap.medium;
        const deadlineBadge = t.deadline_formatted && t.deadline_formatted !== 'No Deadline'
            ? `<span class="ftm-badge ${t.is_overdue && !isDone ? 'bg-rose-soft text-rose' : 'ftm-badge-outline'} ftm-badge-xs" style="font-size:10.5px;">${ftmEsc(t.deadline_formatted)}${t.countdown_label && !isDone ? ' · ' + ftmEsc(t.countdown_label) : ''}</span>`
            : '';
        const assignedList = (Array.isArray(t.assigned_users) && t.assigned_users.length)
            ? t.assigned_users
            : (t.assigned_user && t.assigned_user.name ? [t.assigned_user] : []);
        const assigneeChip = assignedList.length
            ? assignedList.map(u => `<span style="display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--ftm-text-muted); background:rgba(148,163,184,0.08); border:1px solid var(--ftm-border); border-radius:999px; padding:2px 8px 2px 3px;"><img src="${ftmEsc(u.avatar || '')}" alt="" style="width:16px; height:16px; border-radius:50%; object-fit:cover;">${ftmEsc(u.name)}</span>`).join(' ')
            : '';

        html += `
            <div class="ftm-task-item-card" style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1px solid var(--ftm-border); border-radius:8px; background:var(--ftm-card-bg); opacity:${isDone ? '0.68' : '1'};">
                <input type="checkbox" class="ftm-task-toggle-check" data-task-id="${t.id}" ${isDone ? 'checked' : ''} style="margin-top:3px; width:16px; height:16px; cursor:pointer;">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                        <strong style="font-size:13px; color:var(--ftm-text-primary); ${isDone ? 'text-decoration:line-through;' : ''}">${ftmEsc(t.title)}</strong>
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <span class="ftm-badge ${pr.cls} ftm-badge-xs" style="font-size:10px; font-weight:700;">${pr.label}</span>
                            ${deadlineBadge}
                            ${canDelete ? `<button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-task-delete-btn" data-task-id="${t.id}" title="Delete task" style="padding:2px 6px; font-size:11px; color:#ef4444;">&times;</button>` : ''}
                        </div>
                    </div>
                    ${t.description ? `<div style="font-size:12px; color:var(--ftm-text-muted); margin-top:4px; line-height:1.4;">${ftmEsc(t.description)}</div>` : ''}
                    ${assigneeChip ? `<div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:6px;">${assigneeChip}</div>` : ''}
                </div>
            </div>
        `;
    });

    container.html(html);
},

renderLeaderQualityGates(project, gatesData) {
    const self = this;
    const container = $('#ftm-details-gates-grid');
    const badge = $('#ftm-details-approvals-status-badge');
    if (!container.length) return;

    if (!gatesData || !Array.isArray(gatesData.gates) || !gatesData.gates.length) {
        container.html('<div class="ftm-text-muted ftm-text-sm" style="grid-column:1/-1; text-align:center; padding:12px;">Quality gates not initialized.</div>');
        return;
    }

    const total = gatesData.total_gates || gatesData.gates.length || 4;
    const approved = gatesData.approved_gates || 0;
    const isAllApproved = !!gatesData.is_all_approved;

    if (badge.length) {
        badge.text(`${approved} of ${total} Approved`)
             .removeClass('bg-amber-soft text-amber bg-emerald-soft text-emerald')
             .addClass(isAllApproved ? 'bg-emerald-soft text-emerald' : 'bg-amber-soft text-amber');
    }

    const isAdmin = !!(this.state.isAdmin || this.state.isDeputy);
    const isLeader = !!this.state.isLeader;
    const userTeam = this.state.userTeam || '';
    const projTeam = project.team_name || '';
    const canApprove = isAdmin || (isLeader && (!userTeam || !projTeam || userTeam.toLowerCase() === projTeam.toLowerCase()));

    let html = '';
    gatesData.gates.forEach(g => {
        const isDone = !!(g.is_approved || g.status === 'done');
        const cardCls = isDone ? 'ftm-gate-approved' : 'ftm-gate-pending';
        const stepNum = g.step || 1;
        const icon = g.icon || '🛡️';

        let approverHtml = '';
        if (isDone && g.approved_by) {
            approverHtml = `
                <div class="ftm-gate-approver-info">
                    <img src="${ftmEsc(g.approved_by.avatar || '')}" alt="" class="ftm-gate-approver-avatar">
                    <div style="min-width:0; flex:1;">
                        <div class="ftm-gate-approver-name" title="${ftmEsc(g.approved_by.name)}">${ftmEsc(g.approved_by.name)}</div>
                        <div class="ftm-gate-approver-meta">
                            <span>${ftmEsc(g.approved_by.role || 'Team Leader')}</span>
                            ${g.approved_at_formatted ? ` · <span>${ftmEsc(g.approved_at_formatted)}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        let actionHtml = '';
        if (canApprove) {
            if (isDone) {
                actionHtml = `
                    <div class="ftm-gate-actions">
                        <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs ftm-unapprove-gate-btn text-muted" data-project-id="${project.id}" data-gate-type="${ftmEsc(g.gate_type)}" data-task-id="${g.task_id || 0}" title="Reset gate to pending" style="font-size:11px; padding:2px 8px;">
                            ↺ Reset
                        </button>
                    </div>
                `;
            } else {
                actionHtml = `
                    <div class="ftm-gate-actions">
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs ftm-approve-gate-btn" data-project-id="${project.id}" data-gate-type="${ftmEsc(g.gate_type)}" data-task-id="${g.task_id || 0}" style="font-size:11.5px; font-weight:700; width:100%; justify-content:center; background:#0d9488; border-color:#0d9488;">
                            ✓ Approve Gate
                        </button>
                    </div>
                `;
            }
        }

        html += `
            <div class="ftm-gate-card ${cardCls}">
                <div>
                    <div class="ftm-gate-header">
                        <span class="ftm-gate-step-badge ftm-badge ${isDone ? 'bg-emerald-soft text-emerald' : 'bg-amber-soft text-amber'} ftm-badge-xs">
                            Step ${stepNum}
                        </span>
                        <span style="font-size:15px;">${icon}</span>
                    </div>
                    <div class="ftm-gate-title">${ftmEsc(g.label || g.short_title)}</div>
                    <div>
                        ${isDone
                            ? `<span class="ftm-gate-status-pill bg-emerald-soft text-emerald">✓ Approved</span>`
                            : `<span class="ftm-gate-status-pill bg-amber-soft text-amber">⏳ Pending</span>`}
                    </div>
                </div>
                <div>
                    ${approverHtml}
                    ${actionHtml}
                </div>
            </div>
        `;
    });

    container.html(html);

    // Bind approval action events
    container.off('click', '.ftm-approve-gate-btn').on('click', '.ftm-approve-gate-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const pid = $btn.data('project-id') || project.id;
        const gateType = $btn.data('gate-type');
        const taskId = $btn.data('task-id');
        $btn.prop('disabled', true).text('Approving...');
        self.api(`projects/${pid}/approve-gate`, 'POST', { gate_type: gateType, task_id: taskId, action: 'approve' }).then(res => {
            self.toast((res && res.message) || 'Quality gate approved! 🛡️', 'success');
            self.openProjectDetailsModal(pid);
            if (self.state.currentView === 'my-tasks' || $('#ftm-view-my-tasks').is(':visible')) {
                self.loadMyTasksView({ preserveScroll: true });
            } else {
                self.refreshMyTasksBadge();
            }
        }).catch(err => {
            $btn.prop('disabled', false).text('✓ Approve Gate');
            self.toast('Failed to approve gate: ' + ((err && err.message) || 'Unknown error'), 'error');
        });
    });

    container.off('click', '.ftm-unapprove-gate-btn').on('click', '.ftm-unapprove-gate-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const pid = $btn.data('project-id') || project.id;
        const gateType = $btn.data('gate-type');
        const taskId = $btn.data('task-id');
        if (!confirm('Are you sure you want to reset this approval gate to pending?')) return;
        $btn.prop('disabled', true).text('Resetting...');
        self.api(`projects/${pid}/approve-gate`, 'POST', { gate_type: gateType, task_id: taskId, action: 'unapprove' }).then(res => {
            self.toast((res && res.message) || 'Quality gate reset to pending.', 'success');
            self.openProjectDetailsModal(pid);
            if (self.state.currentView === 'my-tasks' || $('#ftm-view-my-tasks').is(':visible')) {
                self.loadMyTasksView({ preserveScroll: true });
            } else {
                self.refreshMyTasksBadge();
            }
        }).catch(err => {
            $btn.prop('disabled', false).text('↺ Reset');
            self.toast('Failed to reset gate: ' + ((err && err.message) || 'Unknown error'), 'error');
        });
    });
},

openAddTaskModal(projectId = null, memberId = null) {
    const self = this;
    const form = $('#ftm-task-form');
    if (form.length && form[0]) form[0].reset();
    $('#ftm-form-task-id').val('');
    $('#ftm-form-task-member-id').val(memberId || '');
    $('#ftm-form-task-assignee-search').val('');

    const sel = $('#ftm-form-task-project-id');
    let projectsMap = {};
    let allMembers = [];
    let selectedIds = new Set();
    let projectMemberIds = new Set();
    let activeTeamFilter = 'all';

    if (memberId && parseInt(memberId, 10) > 0) {
        selectedIds.add(parseInt(memberId, 10));
    }

    const teamColors = {
        'WpCraft': 'bg-blue-soft text-blue',
        'WpNexus': 'bg-emerald-soft text-emerald',
        'WpTitans': 'bg-purple-soft text-purple',
        'WpHorizon': 'bg-amber-soft text-amber',
        'Management': 'bg-indigo-soft text-indigo'
    };

    const extractProjectMemberIds = (proj) => {
        const ids = [];
        if (!proj) return ids;
        if (Array.isArray(proj.assigned_members) && proj.assigned_members.length) {
            proj.assigned_members.forEach(m => {
                const mid = parseInt(m.id || m.user_id, 10);
                if (mid > 0 && !ids.includes(mid)) ids.push(mid);
            });
        }
        if (proj.assigned_user_ids) {
            const rawArr = Array.isArray(proj.assigned_user_ids) ? proj.assigned_user_ids : String(proj.assigned_user_ids).split(',');
            rawArr.forEach(raw => {
                const mid = parseInt(String(raw).trim(), 10);
                if (mid > 0 && !ids.includes(mid)) ids.push(mid);
            });
        }
        if (proj.assigned_user_id) {
            const mid = parseInt(proj.assigned_user_id, 10);
            if (mid > 0 && !ids.includes(mid)) ids.push(mid);
        }
        return ids;
    };

    const renderSelectedChips = () => {
        const chipsWrap = $('#ftm-form-task-selected-chips');
        const countBadge = $('#ftm-form-task-assignees-count');
        const arr = Array.from(selectedIds);
        if (!arr.length) {
            chipsWrap.html('<span class="ftm-text-muted ftm-text-xs">No member selected yet (will default to project assignee).</span>');
            countBadge.text('Auto-selected from project');
            return;
        }
        countBadge.text(arr.length + ' selected');
        let cHtml = '';
        arr.forEach(uid => {
            const m = allMembers.find(x => parseInt(x.id, 10) === uid);
            const name = m ? (m.name || m.display_name || ('Member #' + uid)) : ('Member #' + uid);
            const av = m ? (m.avatar || '') : '';
            const isProjMem = projectMemberIds.has(uid);
            cHtml += `
                <span style="display:inline-flex; align-items:center; gap:6px; padding:3px 9px 3px 4px; border-radius:999px; font-size:12px; font-weight:600; background:${isProjMem ? 'rgba(59,130,246,0.16)' : 'rgba(16,185,129,0.16)'}; color:${isProjMem ? '#60a5fa' : '#34d399'}; border:1px solid ${isProjMem ? 'rgba(59,130,246,0.35)' : 'rgba(16,185,129,0.35)'};">
                    ${av ? `<img src="${ftmEsc(av)}" alt="" style="width:18px; height:18px; border-radius:50%; object-fit:cover;">` : ''}
                    <span>${ftmEsc(name)}</span>
                    <button type="button" class="ftm-task-chip-remove" data-uid="${uid}" style="background:none; border:none; color:inherit; cursor:pointer; font-size:14px; line-height:1; padding:0 2px;" title="Remove">&times;</button>
                </span>
            `;
        });
        chipsWrap.html(cHtml);
    };

    const renderAssigneeList = () => {
        const listWrap = $('#ftm-form-task-assignees-list');
        const q = ($('#ftm-form-task-assignee-search').val() || '').toLowerCase().trim();
        const uniqueTeams = Array.from(new Set(allMembers.map(m => m.team).filter(Boolean)));

        // Squad filter pills bar (matching Project Assignee Picker)
        let barHtml = `
            <div style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--ftm-border);">
                <button type="button" class="ftm-task-team-pill" data-team="all" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; background:${activeTeamFilter === 'all' ? 'rgba(59,130,246,0.18)' : 'var(--ftm-card-bg)'}; color:${activeTeamFilter === 'all' ? '#60a5fa' : 'var(--ftm-text-secondary)'}; border:1px solid ${activeTeamFilter === 'all' ? 'rgba(59,130,246,0.4)' : 'var(--ftm-border)'};">
                    All (${allMembers.length})
                </button>
        `;
        uniqueTeams.forEach(t => {
            const cnt = allMembers.filter(m => m.team === t).length;
            const act = activeTeamFilter === t;
            barHtml += `
                <button type="button" class="ftm-task-team-pill" data-team="${ftmEsc(t)}" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; background:${act ? 'rgba(59,130,246,0.18)' : 'var(--ftm-card-bg)'}; color:${act ? '#60a5fa' : 'var(--ftm-text-secondary)'}; border:1px solid ${act ? 'rgba(59,130,246,0.4)' : 'var(--ftm-border)'};">
                    ${ftmEsc(t)} (${cnt})
                </button>
            `;
        });
        const actSel = activeTeamFilter === 'selected';
        barHtml += `
                <button type="button" class="ftm-task-team-pill" data-team="selected" style="font-size:11px; padding:3px 9px; border-radius:12px; font-weight:600; cursor:pointer; margin-left:auto; background:${actSel ? 'rgba(16,185,129,0.18)' : 'var(--ftm-card-bg)'}; color:#10b981; border:1px solid rgba(16,185,129,0.35);">
                    ✓ Selected (${selectedIds.size})
                </button>
            </div>
        `;

        // Sort: project members first, then selected members, then squad + name
        const sorted = allMembers.slice().sort((a, b) => {
            const aProj = projectMemberIds.has(parseInt(a.id, 10)) ? 0 : 1;
            const bProj = projectMemberIds.has(parseInt(b.id, 10)) ? 0 : 1;
            if (aProj !== bProj) return aProj - bProj;
            const aSel = selectedIds.has(parseInt(a.id, 10)) ? 0 : 1;
            const bSel = selectedIds.has(parseInt(b.id, 10)) ? 0 : 1;
            if (aSel !== bSel) return aSel - bSel;
            return String(a.name || '').localeCompare(String(b.name || ''));
        });

        let rowsHtml = '';
        sorted.forEach(m => {
            const uid = parseInt(m.id, 10);
            const mName = m.name || m.display_name || ('Member #' + uid);
            const hay = (mName + ' ' + (m.team || '') + ' ' + (m.role_label || '') + ' ' + (m.email || '')).toLowerCase();
            if (q && !hay.includes(q)) return;
            if (activeTeamFilter === 'selected' && !selectedIds.has(uid)) return;
            if (activeTeamFilter !== 'all' && activeTeamFilter !== 'selected' && (m.team || '') !== activeTeamFilter) return;

            const isChecked = selectedIds.has(uid);
            const isProjMem = projectMemberIds.has(uid);
            const badgeClass = teamColors[m.team] || 'bg-blue-soft text-blue';
            rowsHtml += `
                <label class="ftm-assignee-checkbox-item ${isChecked ? 'checked' : ''}" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:7px 10px; margin-bottom:4px; border-radius:8px; cursor:pointer; background:${isChecked ? 'rgba(59,130,246,0.12)' : 'var(--ftm-card-bg)'}; border:1px solid ${isChecked ? 'rgba(59,130,246,0.4)' : 'var(--ftm-border)'};">
                    <div style="display:flex; align-items:center; gap:9px; min-width:0;">
                        <img src="${ftmEsc(m.avatar || '')}" alt="${ftmEsc(mName)}" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid var(--ftm-border); flex-shrink:0;">
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:12.5px; color:var(--ftm-text-primary); display:flex; align-items:center; gap:5px; flex-wrap:wrap;">
                                <span>${ftmEsc(mName)}</span>
                                ${m.team ? `<span class="ftm-badge ${badgeClass} ftm-badge-xs" style="font-size:10px; font-weight:700; padding:1px 6px;">${ftmEsc(m.team)}</span>` : ''}
                                ${isProjMem ? '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-size:9.5px; font-weight:700;">Project Member</span>' : ''}
                            </div>
                            <div style="font-size:11px; color:var(--ftm-text-muted); margin-top:1px;">${ftmEsc(m.role_label || 'Specialist')}</div>
                        </div>
                    </div>
                    <input type="checkbox" class="ftm-task-assignee-cb" value="${uid}" ${isChecked ? 'checked' : ''} style="width:16px; height:16px; cursor:pointer; accent-color:#3b82f6; flex-shrink:0;">
                </label>
            `;
        });

        listWrap.html(barHtml + (rowsHtml || '<div class="ftm-text-muted ftm-text-xs" style="padding:10px; text-align:center;">No matching members found.</div>'));
        renderSelectedChips();
    };

    const syncProjectAssignees = (pid) => {
        projectMemberIds = new Set();
        const proj = projectsMap[String(pid)];
        if (proj) {
            const pIds = extractProjectMemberIds(proj);
            pIds.forEach(idNum => {
                projectMemberIds.add(idNum);
                selectedIds.add(idNum);
            });
            renderAssigneeList();
        } else if (pid) {
            self.api('projects/' + pid).then(r => {
                const pObj = r && r.project ? r.project : null;
                if (pObj) {
                    projectsMap[String(pid)] = pObj;
                    extractProjectMemberIds(pObj).forEach(idNum => {
                        projectMemberIds.add(idNum);
                        selectedIds.add(idNum);
                    });
                    renderAssigneeList();
                }
            }).catch(() => {});
        } else {
            renderAssigneeList();
        }
    };

    // Wire dynamic events inside the modal
    sel.off('change.ftmTaskAssignees').on('change.ftmTaskAssignees', function () {
        const newPid = $(this).val();
        selectedIds = new Set();
        if (memberId && parseInt(memberId, 10) > 0) selectedIds.add(parseInt(memberId, 10));
        syncProjectAssignees(newPid);
    });

    $('#ftm-form-task-assignee-search').off('input.ftmTaskAssignees').on('input.ftmTaskAssignees', function () {
        renderAssigneeList();
    });

    $('#ftm-form-task-assignees-list')
        .off('change.ftmTaskAssignees')
        .on('change.ftmTaskAssignees', '.ftm-task-assignee-cb', function () {
            const uid = parseInt($(this).val(), 10);
            if ($(this).is(':checked')) {
                selectedIds.add(uid);
            } else {
                selectedIds.delete(uid);
            }
            renderAssigneeList();
        })
        .off('click.ftmTaskAssignees')
        .on('click.ftmTaskAssignees', '.ftm-task-team-pill', function (e) {
            e.preventDefault();
            activeTeamFilter = $(this).data('team') || 'all';
            renderAssigneeList();
        });

    $('#ftm-form-task-selected-chips').off('click.ftmTaskAssignees').on('click.ftmTaskAssignees', '.ftm-task-chip-remove', function (e) {
        e.preventDefault();
        const uid = parseInt($(this).data('uid'), 10);
        selectedIds.delete(uid);
        renderAssigneeList();
    });

    const populateProjects = (items) => {
        projectsMap = {};
        let opts = '<option value="">Select Project...</option>';
        (items || []).forEach(p => {
            projectsMap[String(p.id)] = p;
            const label = `${p.client_name || 'Client'} (${p.order_id || '#' + p.id})`;
            opts += `<option value="${p.id}" ${projectId && String(p.id) === String(projectId) ? 'selected' : ''}>${ftmEsc(label)}</option>`;
        });
        sel.html(opts);
        if (projectId) sel.val(String(projectId));
    };

    const cachedProjects = self.state.projects || self.state.allProjectsList || [];
    const projectsPromise = cachedProjects.length
        ? Promise.resolve(cachedProjects)
        : self.api('projects?per_page=150').then(res => (res && (res.items || res.projects)) || (Array.isArray(res) ? res : [])).catch(() => []);

    const cachedMembers = self.state.teamMembers || [];
    const membersPromise = cachedMembers.length
        ? Promise.resolve(cachedMembers)
        : self.api('team-members').then(res => {
            const list = Array.isArray(res) ? res : (res && (res.members || res.items)) || [];
            if (list.length) self.state.teamMembers = list;
            return list;
        }).catch(() => []);

    Promise.all([projectsPromise, membersPromise]).then(([projItems, memItems]) => {
        allMembers = memItems || [];
        populateProjects(projItems || []);
        if (projectId) {
            syncProjectAssignees(projectId);
        } else {
            renderAssigneeList();
        }
        $('#ftm-modal-add-task').removeClass('hidden');
    });
},

saveTaskForm() {
    const self = this;
    const projectId = $('#ftm-form-task-project-id').val() || self.state.activeProjectId;
    const title = ($('#ftm-form-task-title-input').val() || '').trim();
    const priority = $('#ftm-form-task-priority').val() || 'medium';
    const deadlineRaw = $('#ftm-form-task-deadline').val() || '';
    const deadline = deadlineRaw ? deadlineRaw.replace('T', ' ') + (deadlineRaw.length === 16 ? ':00' : '') : '';
    const description = ($('#ftm-form-task-desc').val() || '').trim();

    // Collect all selected members from chips/checkboxes
    const selectedMemberIds = [];
    $('#ftm-form-task-selected-chips .ftm-task-chip-remove').each(function () {
        const uid = parseInt($(this).data('uid'), 10);
        if (uid > 0 && !selectedMemberIds.includes(uid)) selectedMemberIds.push(uid);
    });
    if (!selectedMemberIds.length) {
        const fallbackUid = parseInt($('#ftm-form-task-member-id').val() || '0', 10);
        if (fallbackUid > 0) selectedMemberIds.push(fallbackUid);
    }

    if (!projectId) {
        self.toast('Please select an associated project.', 'error');
        return;
    }
    if (!title) {
        self.toast('Task title is required.', 'error');
        return;
    }

    const payload = { title, priority, description };
    if (deadline) payload.deadline = deadline;
    if (selectedMemberIds.length) {
        payload.assigned_user_id = selectedMemberIds[0];
        payload.assigned_user_ids = selectedMemberIds;
    }

    this.api(`projects/${projectId}/tasks`, 'POST', payload).then(res => {
        $('#ftm-modal-add-task').addClass('hidden');
        self.toast((res && res.message) || 'Task added successfully!', 'success');
        if (self.state.activeProjectId && !$('#ftm-modal-details').hasClass('hidden')) {
            self.openProjectDetailsModal(self.state.activeProjectId);
        }
        if (self.state.currentView === 'my-tasks' || $('#ftm-view-my-tasks').is(':visible')) {
            self.loadMyTasksView();
        } else {
            self.refreshMyTasksBadge();
        }
    }).catch(err => {
        self.toast('Error saving task: ' + ((err && err.message) || 'Unknown error'), 'error');
    });
},

toggleTaskStatus(taskId, isDone) {
    const self = this;
    if (!taskId) return;
    const $row = $(`.ftm-task-toggle-check[data-task-id="${taskId}"]`).closest('tr, .ftm-task-item');
    if ($row.length) $row.css('opacity', '0.6');
    this.api(`tasks/${taskId}/status`, 'POST', { status: isDone ? 'done' : 'pending' }).then(res => {
        self.toast((res && res.message) || (isDone ? 'Task completed & logged to order daily report!' : 'Task reopened.'), 'success');
        if (self.state.activeProjectId && !$('#ftm-modal-details').hasClass('hidden')) {
            self.openProjectDetailsModal(self.state.activeProjectId);
        }
        if (self.state.currentView === 'my-tasks' || $('#ftm-view-my-tasks').is(':visible')) {
            self.loadMyTasksView({ preserveScroll: true });
        } else {
            self.refreshMyTasksBadge();
        }
    }).catch(err => {
        if ($row.length) $row.css('opacity', '1');
        $(`.ftm-task-toggle-check[data-task-id="${taskId}"]`).prop('checked', !isDone);
        self.toast('Error updating task: ' + ((err && err.message) || 'Unknown error'), 'error');
    });
},

deleteTask(taskId) {
    const self = this;
    if (!taskId) return;
    const $row = $(`.ftm-task-delete-btn[data-task-id="${taskId}"]`).closest('tr, .ftm-task-item');
    if ($row.length) $row.css('opacity', '0.5');
    this.api(`tasks/${taskId}`, 'DELETE').then(res => {
        self.toast((res && res.message) || 'Task deleted.', 'success');
        if (self.state.activeProjectId && !$('#ftm-modal-details').hasClass('hidden')) {
            self.openProjectDetailsModal(self.state.activeProjectId);
        }
        if (self.state.currentView === 'my-tasks' || $('#ftm-view-my-tasks').is(':visible')) {
            self.loadMyTasksView({ preserveScroll: true });
        } else {
            self.refreshMyTasksBadge();
        }
    }).catch(err => {
        if ($row.length) $row.css('opacity', '1');
        self.toast('Error deleting task: ' + ((err && err.message) || 'Unknown error'), 'error');
    });
},

refreshMyTasksBadge() {
    this.api('tasks/my-pending').then(res => {
        const cnt = parseInt((res && res.pending_count) || 0, 10) || 0;
        const badge = $('#ftm-nav-tasks-badge');
        if (badge.length) {
            badge.text(cnt).toggleClass('hidden', cnt <= 0);
        }
    }).catch(() => {});
},

bindMyTasksEvents() {
    const self = this;
    if (this._myTasksEventsBound) return;
    this._myTasksEventsBound = true;

    if (!this.state.myTasksFilter) {
        const isLeadOrAdmin = !!(this.state.isAdmin || this.state.isLeader || this.state.isDeputy);
        this.state.myTasksFilter = {
            scope: isLeadOrAdmin ? 'all' : 'mine',
            team: 'all'
        };
    }

    const syncTaskKpiActive = (st) => {
        $('.ftm-task-kpi-card').removeClass('ftm-kpi-active');
        $(`.ftm-task-kpi-card[data-task-status="${st || 'all'}"]`).addClass('ftm-kpi-active');
    };

    $(document).on('click', '.ftm-task-kpi-card', function () {
        const target = $(this).data('task-status') || 'all';
        const cur = $('#ftm-my-tasks-status').val() || 'all';
        const next = (target !== 'all' && cur === target) ? 'all' : target;
        $('#ftm-my-tasks-status').val(next).trigger('change');
    });

    $(document).on('click', '.ftm-tasks-team-tab-btn', function (e) {
        e.preventDefault();
        const scope = $(this).data('scope') || 'all';
        const team = $(this).data('team') || 'all';
        self.state.myTasksFilter = { scope, team };
        // Reset specialist filter when switching teams or switching to My Dedicated Tasks
        $('#ftm-my-tasks-member').val('0').toggleClass('hidden', scope === 'mine');
        self.loadMyTasksView();
    });

    $('#ftm-my-tasks-status').on('change', function () {
        syncTaskKpiActive($(this).val());
        self.loadMyTasksView();
    });

    $('#ftm-my-tasks-priority, #ftm-my-tasks-member, #ftm-my-tasks-group').on('change', function () {
        self.loadMyTasksView();
    });

    $('#ftm-my-tasks-search').on('input', function () {
        clearTimeout(self._myTasksSearchTimer);
        self._myTasksSearchTimer = setTimeout(() => self.loadMyTasksView(), 250);
    });

    $('#ftm-my-tasks-refresh-btn').on('click', function (e) {
        e.preventDefault();
        self.loadMyTasksView();
    });

    $('#ftm-my-tasks-add-btn').on('click', function (e) {
        e.preventDefault();
        self.openAddTaskModal(null, null);
    });
},

loadMyTasksView(options = {}) {
    const self = this;
    this.bindMyTasksEvents();

    const tbody = $('#ftm-my-tasks-tbody');
    if (!tbody.length) return;
    const scrollSnap = this.captureScrollState();
    const hasExistingRows = tbody.children().length > 0 && !tbody.find('.ftm-table-loading').length;
    if (!hasExistingRows) {
        tbody.html('<tr><td colspan="8" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading tasks...</td></tr>');
    } else {
        tbody.css('opacity', '0.65');
    }

    const isAdmin = !!(this.state.isAdmin || this.state.isDeputy);
    const isLeader = !!this.state.isLeader;
    const isLeadOrAdmin = isAdmin || isLeader;
    const fState = this.state.myTasksFilter || { scope: isLeadOrAdmin ? 'all' : 'mine', team: 'all' };
    const scope = isLeadOrAdmin ? (fState.scope || 'all') : 'mine';
    const activeTeam = isAdmin ? (fState.team || 'all') : (isLeader ? (this.state.userTeam || fState.team || 'all') : 'all');

    // Populate specialist dropdown scoped strictly to the active team (or leader's team)
    const populateMemberDropdown = (list) => {
        const $memberSel = $('#ftm-my-tasks-member');
        if (!$memberSel.length) return;
        const prevVal = $memberSel.val() || '0';
        const filteredMembers = (list || []).filter(m => {
            if (!isAdmin && isLeader && self.state.userTeam) {
                return (m.team || '').toLowerCase() === self.state.userTeam.toLowerCase();
            }
            if (activeTeam && activeTeam !== 'all') {
                return (m.team || '').toLowerCase() === activeTeam.toLowerCase();
            }
            return true;
        });
        let opts = `<option value="0">${activeTeam && activeTeam !== 'all' ? 'All ' + ftmEsc(activeTeam) + ' Specialists' : 'All Specialists'}</option>`;
        filteredMembers.forEach(m => {
            opts += `<option value="${m.id}">${ftmEsc(m.name)}${m.team ? ' (' + ftmEsc(m.team) + ')' : ''}</option>`;
        });
        $memberSel.html(opts);
        if ($memberSel.find(`option[value="${prevVal}"]`).length) {
            $memberSel.val(prevVal);
        } else {
            $memberSel.val('0');
        }
        $memberSel.toggleClass('hidden', scope === 'mine');
    };

    if (isLeadOrAdmin) {
        if (self.state.teamMembers && self.state.teamMembers.length) {
            populateMemberDropdown(self.state.teamMembers);
        } else {
            this.api('team-members').then(res => {
                const list = Array.isArray(res) ? res : (res && (res.members || res.items)) || [];
                if (list.length) self.state.teamMembers = list;
                populateMemberDropdown(list);
            }).catch(() => {});
        }
    }

    const userId = (isLeadOrAdmin && scope !== 'mine') ? ($('#ftm-my-tasks-member').val() || '0') : '0';
    const status = $('#ftm-my-tasks-status').val() || 'all';
    const priority = $('#ftm-my-tasks-priority').val() || 'all';
    const groupMode = $('#ftm-my-tasks-group').val() || 'auto';
    const search = ($('#ftm-my-tasks-search').val() || '').trim();

    let query = `?scope=${encodeURIComponent(scope)}&team=${encodeURIComponent(activeTeam)}&status=${encodeURIComponent(status)}&priority=${encodeURIComponent(priority)}`;
    if (userId && userId !== '0') query += `&user_id=${encodeURIComponent(userId)}`;
    if (search) query += `&search=${encodeURIComponent(search)}`;

    this.api('tasks' + query).then(res => {
        const total = parseInt((res && res.total_tasks) || 0, 10) || 0;
        const pending = parseInt((res && res.pending_count) || 0, 10) || 0;
        const overdue = parseInt((res && res.overdue_count) || 0, 10) || 0;
        const completed = parseInt((res && res.completed_count) || 0, 10) || 0;
        const myPending = parseInt((res && res.my_pending_count) || 0, 10) || 0;
        const myTotal = parseInt((res && res.my_total_count) || 0, 10) || 0;

        $('#ftm-tasks-kpi-total').text(total);
        $('#ftm-tasks-kpi-pending').text(pending);
        $('#ftm-tasks-kpi-overdue').text(overdue);
        $('#ftm-tasks-kpi-completed').text(completed);

        // Keep sidebar badge aligned with the user's own pending tasks
        const navBadge = $('#ftm-nav-tasks-badge');
        if (navBadge.length) {
            navBadge.text(myPending).toggleClass('hidden', myPending <= 0);
        }

        // Render Dedicated Team / Squad Organization Tabs for Managers & Leaders
        const tabsBar = $('#ftm-my-tasks-team-tabs');
        if (tabsBar.length && isLeadOrAdmin) {
            const teamCounts = (res && res.team_counts) || {};
            const allVis = parseInt((res && res.all_visible_count) || 0, 10) || 0;
            const resTeam = (res && res.active_team) || activeTeam;
            const roleScope = (res && res.role_scope) || (isAdmin ? 'admin' : 'leader');
            const leaderTeam = (res && res.user_team) || self.state.userTeam || '';

            const pillStyle = (isAct, accent = 'blue') => {
                if (isAct) {
                    const bg = accent === 'emerald' ? 'rgba(16,185,129,0.18)' : 'rgba(59,130,246,0.22)';
                    const clr = accent === 'emerald' ? '#34d399' : '#60a5fa';
                    const bdr = accent === 'emerald' ? 'rgba(16,185,129,0.5)' : 'rgba(59,130,246,0.55)';
                    return `padding:7px 14px; border-radius:999px; font-size:12.5px; font-weight:700; cursor:pointer; background:${bg}; color:${clr}; border:1px solid ${bdr}; display:inline-flex; align-items:center; gap:7px;`;
                }
                return `padding:7px 14px; border-radius:999px; font-size:12.5px; font-weight:600; cursor:pointer; background:var(--ftm-card-bg); color:var(--ftm-text-secondary); border:1px solid var(--ftm-border); display:inline-flex; align-items:center; gap:7px;`;
            };

            let tabsHtml = '';
            if (roleScope === 'admin') {
                const isAllAct = (scope !== 'mine' && resTeam === 'all');
                tabsHtml += `<button type="button" class="ftm-tasks-team-tab-btn" data-scope="all" data-team="all" style="${pillStyle(isAllAct)}">All Teams <span class="ftm-badge ftm-badge-outline ftm-badge-xs">${allVis}</span></button>`;
                Object.keys(teamCounts).sort().forEach(tm => {
                    const isTmAct = (scope !== 'mine' && resTeam.toLowerCase() === tm.toLowerCase());
                    tabsHtml += `<button type="button" class="ftm-tasks-team-tab-btn" data-scope="all" data-team="${ftmEsc(tm)}" style="${pillStyle(isTmAct)}">${ftmEsc(tm)} <span class="ftm-badge ftm-badge-outline ftm-badge-xs">${teamCounts[tm]}</span></button>`;
                });
            } else if (roleScope === 'leader') {
                const isSquadAct = (scope !== 'mine');
                tabsHtml += `<button type="button" class="ftm-tasks-team-tab-btn" data-scope="all" data-team="${ftmEsc(leaderTeam || 'all')}" style="${pillStyle(isSquadAct)}">${ftmEsc(leaderTeam || 'My Squad')} Squad Tasks <span class="ftm-badge ftm-badge-outline ftm-badge-xs">${allVis}</span></button>`;
            }
            const isMineAct = (scope === 'mine');
            tabsHtml += `<button type="button" class="ftm-tasks-team-tab-btn" data-scope="mine" data-team="all" style="${pillStyle(isMineAct, 'emerald')} margin-left:auto;">My Dedicated Tasks <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">${myTotal}</span></button>`;
            tabsBar.html(tabsHtml);
        }

        const tasks = (res && res.tasks) || [];
        if (!tasks.length) {
            tbody.html('<tr><td colspan="8" style="text-align:center; padding:36px 16px; color:var(--ftm-text-muted);">No tasks found matching the selected filters.</td></tr>');
            return;
        }

        const canDelete = !self.state.isDeputy && !!(self.state.isAdmin || self.state.isLeader);
        const priorityMap = {
            urgent: { label: 'Urgent', cls: 'bg-rose-soft text-rose' },
            high: { label: 'High', cls: 'bg-amber-soft text-amber' },
            medium: { label: 'Medium', cls: 'bg-blue-soft text-blue' },
            low: { label: 'Low', cls: 'ftm-badge-outline' }
        };
        const squadColors = {
            'WpCraft': 'bg-blue-soft text-blue',
            'WpNexus': 'bg-emerald-soft text-emerald',
            'WpTitans': 'bg-purple-soft text-purple',
            'WpHorizon': 'bg-amber-soft text-amber'
        };

        const renderTaskRow = (t) => {
            const isDone = !!(t.is_done || t.status === 'done');
            const pr = priorityMap[t.priority] || priorityMap.medium;
            const proj = t.project || {};
            const orderLabel = proj.order_id || ('#' + t.project_id);
            const clientLabel = proj.client_name || 'Client';
            const teamLabel = proj.team_name || '';
            const sqCls = squadColors[teamLabel] || 'bg-indigo-soft text-indigo';

            const taskUsers = (Array.isArray(t.assigned_users) && t.assigned_users.length)
                ? t.assigned_users
                : (t.assigned_user && t.assigned_user.name ? [t.assigned_user] : []);
            const assigneeHtml = taskUsers.length
                ? `<div style="display:flex; flex-direction:column; gap:4px;">${taskUsers.map(u => `<div style="display:flex; align-items:center; gap:6px;"><img src="${ftmEsc(u.avatar || '')}" alt="" style="width:22px; height:22px; border-radius:50%; object-fit:cover;"><span style="font-size:12px; font-weight:600; color:var(--ftm-text-primary);">${ftmEsc(u.name)}</span></div>`).join('')}</div>`
                : '<span class="ftm-text-muted">Unassigned</span>';

            let deadlineHtml = '<span class="ftm-text-muted" style="font-size:12px;">No Deadline</span>';
            if (t.deadline_formatted && t.deadline_formatted !== 'No Deadline') {
                const badgeCls = (!isDone && t.is_overdue) ? 'bg-rose-soft text-rose' : (isDone ? 'ftm-badge-outline' : 'bg-blue-soft text-blue');
                deadlineHtml = `
                    <div style="display:flex; flex-direction:column; gap:3px; align-items:flex-start;">
                        <span style="font-size:12px; font-weight:600; color:var(--ftm-text-primary);">${ftmEsc(t.deadline_formatted)}</span>
                        ${(!isDone && t.countdown_label) ? `<span class="ftm-badge ${badgeCls} ftm-badge-xs" style="font-size:10px; font-weight:700;">${ftmEsc(t.countdown_label)}</span>` : ''}
                    </div>
                `;
            }

            const statusHtml = isDone
                ? `<div style="display:flex; flex-direction:column; gap:3px; align-items:flex-start;">
                       <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-weight:700;">✓ Completed</span>
                       <span style="font-size:10.5px; color:#10b981; font-weight:600;">Synced to Order Report</span>
                   </div>`
                : `<span class="ftm-badge ${t.is_overdue ? 'bg-rose-soft text-rose' : 'bg-amber-soft text-amber'} ftm-badge-xs" style="font-weight:700;">${t.is_overdue ? '⚠ Overdue' : '⏳ Pending'}</span>`;

            const isLeaderGate = !!(t.is_leader_gate || (t.task_type && String(t.task_type).indexOf('leader_') === 0));
            const approverNotice = (isDone && t.completed_by_user)
                ? `<div style="font-size:11px; color:#10b981; font-weight:600; display:flex; align-items:center; gap:5px; margin-top:4px;">
                       <img src="${ftmEsc(t.completed_by_user.avatar || '')}" style="width:14px; height:14px; border-radius:50%; object-fit:cover;">
                       <span>Approved by ${ftmEsc(t.completed_by_user.name)}${t.completed_by_user.role ? ' (' + ftmEsc(t.completed_by_user.role) + ')' : ''}</span>
                       ${t.completed_at_formatted ? `<span class="ftm-text-muted">· ${ftmEsc(t.completed_at_formatted)}</span>` : ''}
                   </div>`
                : '';

            return `
                <tr style="${isDone ? 'opacity:0.72;' : ''}">
                    <td style="text-align:center;">
                        <input type="checkbox" class="ftm-task-toggle-check" data-task-id="${t.id}" ${isDone ? 'checked' : ''} title="${isDone ? 'Mark as pending' : 'Mark complete & log to order report'}" style="width:17px; height:17px; cursor:pointer; accent-color:#10b981;">
                    </td>
                    <td style="max-width:300px;">
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary); ${isDone ? 'text-decoration:line-through;' : ''}">${ftmEsc(t.title)}</div>
                            ${isLeaderGate ? `<span class="ftm-badge bg-purple-soft text-purple ftm-badge-xs" style="font-size:10px; font-weight:700;">🛡️ Leader Gate</span>` : ''}
                        </div>
                        ${t.description ? `<div style="font-size:11.5px; color:var(--ftm-text-muted); margin-top:2px; line-height:1.35;">${ftmEsc(t.description)}</div>` : ''}
                        ${approverNotice}
                    </td>
                    <td>
                        <div class="ftm-view-project-btn" data-id="${t.project_id}" style="cursor:pointer; display:inline-flex; flex-direction:column; gap:3px;" title="Open project details">
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span class="ftm-badge ftm-badge-outline ftm-badge-xs" style="font-weight:700;">${ftmEsc(orderLabel)}</span>
                                ${teamLabel ? `<span class="ftm-badge ${sqCls} ftm-badge-xs" style="font-size:10px;">${ftmEsc(teamLabel)}</span>` : ''}
                            </div>
                            <span style="font-size:12px; font-weight:600; color:var(--ftm-text-secondary);">${ftmEsc(clientLabel)}</span>
                        </div>
                    </td>
                    <td>${assigneeHtml}</td>
                    <td><span class="ftm-badge ${pr.cls} ftm-badge-xs" style="font-weight:700;">${pr.label}</span></td>
                    <td>${deadlineHtml}</td>
                    <td>${statusHtml}</td>
                    <td style="text-align:right; white-space:nowrap;">
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-project-btn" data-id="${t.project_id}" title="View Order & Daily Progress">View Order</button>
                        ${canDelete ? `<button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-task-delete-btn" data-task-id="${t.id}" title="Delete task" style="color:#ef4444; margin-left:4px;">&times;</button>` : ''}
                    </td>
                </tr>
            `;
        };

        let html = '';
        const shouldGroupByTeam = (groupMode === 'auto' && isAdmin && scope !== 'mine' && activeTeam === 'all');
        const shouldGroupByProject = (groupMode === 'project' || (groupMode === 'auto' && !shouldGroupByTeam));

        if (shouldGroupByTeam) {
            // Organize strictly by Dedicated Team / Squad sections
            const byTeam = {};
            tasks.forEach(t => {
                const tm = (t.project && t.project.team_name) || 'Unassigned';
                if (!byTeam[tm]) byTeam[tm] = [];
                byTeam[tm].push(t);
            });
            Object.keys(byTeam).sort().forEach(tm => {
                const groupTasks = byTeam[tm];
                const pendG = groupTasks.filter(x => !x.is_done).length;
                const ovdG = groupTasks.filter(x => !x.is_done && x.is_overdue).length;
                const sqCls = squadColors[tm] || 'bg-indigo-soft text-indigo';
                html += `
                    <tr class="ftm-task-group-header" style="background:rgba(59,130,246,0.07);">
                        <td colspan="8" style="padding:10px 16px; border-top:2px solid var(--ftm-border); border-bottom:1px solid var(--ftm-border);">
                            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span class="ftm-badge ${sqCls}" style="font-size:12px; font-weight:800; padding:3px 10px;">${ftmEsc(tm)}</span>
                                    <span style="font-size:12.5px; font-weight:700; color:var(--ftm-text-primary);">${groupTasks.length} Task${groupTasks.length === 1 ? '' : 's'}</span>
                                    <span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">${pendG} pending</span>
                                    ${ovdG > 0 ? `<span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs">${ovdG} overdue</span>` : ''}
                                </div>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-tasks-team-tab-btn" data-scope="all" data-team="${ftmEsc(tm)}" style="font-size:11px;">Focus ${ftmEsc(tm)} &rarr;</button>
                            </div>
                        </td>
                    </tr>
                `;
                groupTasks.forEach(t => { html += renderTaskRow(t); });
            });
        } else if (shouldGroupByProject) {
            // Organize by Dedicated Order / Project
            const byProject = {};
            const orderKeys = [];
            tasks.forEach(t => {
                const key = String(t.project_id);
                if (!byProject[key]) {
                    byProject[key] = { proj: t.project || {}, items: [] };
                    orderKeys.push(key);
                }
                byProject[key].items.push(t);
            });
            orderKeys.forEach(key => {
                const grp = byProject[key];
                const proj = grp.proj;
                const doneG = grp.items.filter(x => x.is_done).length;
                const sqCls = squadColors[proj.team_name] || 'bg-indigo-soft text-indigo';
                html += `
                    <tr class="ftm-task-group-header" style="background:rgba(148,163,184,0.06);">
                        <td colspan="8" style="padding:9px 16px; border-top:1px solid var(--ftm-border); border-bottom:1px solid var(--ftm-border);">
                            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                                <div class="ftm-view-project-btn" data-id="${proj.id || key}" style="display:flex; align-items:center; gap:8px; cursor:pointer; flex-wrap:wrap;">
                                    <span class="ftm-badge ftm-badge-outline ftm-badge-xs" style="font-weight:800;">${ftmEsc(proj.order_id || ('#' + key))}</span>
                                    <strong style="font-size:13px; color:var(--ftm-text-primary);">${ftmEsc(proj.client_name || 'Project')}</strong>
                                    ${proj.team_name ? `<span class="ftm-badge ${sqCls} ftm-badge-xs" style="font-size:10px;">${ftmEsc(proj.team_name)}</span>` : ''}
                                    <span style="font-size:11.5px; color:var(--ftm-text-muted);">${doneG}/${grp.items.length} tasks completed</span>
                                </div>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-project-btn" data-id="${proj.id || key}" style="font-size:11px;">Open Order &rarr;</button>
                            </div>
                        </td>
                    </tr>
                `;
                grp.items.forEach(t => { html += renderTaskRow(t); });
            });
        } else {
            tasks.forEach(t => { html += renderTaskRow(t); });
        }

        tbody.css('opacity', '1').html(html);
        self.restoreScrollState(scrollSnap);
    }).catch(err => {
        tbody.css('opacity', '1').html(`<tr><td colspan="8" style="text-align:center; padding:24px; color:#ef4444;">Error loading tasks: ${ftmEsc((err && err.message) || 'Unknown error')}</td></tr>`);
    });
},

captureScrollState() {
    const scrollers = [];
    const seen = new Set();
    const selectors = [
        '.ftm-main-content', '.ftm-main', '.ftm-content', '.ftm-table-responsive',
        '#ftm-modal-details .ftm-modal-body', '#ftm-modal-details .ftm-modal-content', '#ftm-modal-details'
    ];
    selectors.forEach(sel => {
        document.querySelectorAll(sel).forEach(el => {
            if (el && !seen.has(el) && (el.scrollTop > 0 || el.scrollLeft > 0)) {
                seen.add(el);
                scrollers.push({ el, top: el.scrollTop, left: el.scrollLeft });
            }
        });
    });
    return {
        winY: window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0,
        winX: window.scrollX || window.pageXOffset || document.documentElement.scrollLeft || 0,
        scrollers
    };
},

restoreScrollState(snap) {
    if (!snap) return;
    const apply = () => {
        if (typeof snap.winY === 'number') {
            try {
                window.scrollTo({ top: snap.winY, left: snap.winX || 0, behavior: 'instant' });
            } catch (e) {
                window.scrollTo(snap.winX || 0, snap.winY);
            }
        }
        if (Array.isArray(snap.scrollers)) {
            snap.scrollers.forEach(item => {
                if (item.el && document.body.contains(item.el)) {
                    item.el.scrollTop = item.top;
                    item.el.scrollLeft = item.left;
                }
            });
        }
    };
    apply();
    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(() => {
            apply();
            requestAnimationFrame(apply);
        });
    }
},

addStandupItemRow(itemData = null) {
    if (typeof this.addStandupProjectItem === 'function') {
        return this.addStandupProjectItem(itemData);
    }
},

sendWebNotification(title, options = {}) {
    try {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title || 'FB International', options || {});
        }
    } catch (e) {
        // Ignore browser notification errors
    }
},

renderProjectExtensionBanner(p) {
    const banner = $('#ftm-details-extension-banner');
    if (!banner.length || !p) return;

    const extCount = parseInt(p.extension_count || 0, 10);
    const extDays = parseInt(p.extension_days || 0, 10);
    const hasExt = extCount > 0 || extDays > 0;
    const isDelivered = ['delivery_accepted', 'completed', 'delivered'].includes(p.status);
    const isActive = !isDelivered && p.status !== 'cancelled';

    const textEl = $('#ftm-details-extension-text');
    const badgeEl = $('#ftm-details-delivery-badge');
    const reasonWrap = $('#ftm-details-extension-reason-wrap');
    const reasonEl = $('#ftm-details-extension-reason-text');

    if (hasExt) {
        textEl.text(`Extended (${extCount}x · +${extDays}d total)`);
        badgeEl.removeClass('bg-emerald-soft text-emerald bg-rose-soft text-rose')
               .addClass('bg-amber-soft text-amber')
               .text(`+${extDays}d (${extCount}x)`);
        if (p.extension_reason) {
            reasonWrap.removeClass('hidden');
            reasonEl.text(p.extension_reason);
        } else {
            reasonWrap.addClass('hidden');
        }
    } else {
        textEl.text('On-Time Delivery (No extensions recorded)');
        badgeEl.removeClass('bg-amber-soft text-amber bg-rose-soft text-rose')
               .addClass('bg-emerald-soft text-emerald')
               .text('✓ On-Time Delivery');
        reasonWrap.addClass('hidden');
    }

    // Extend Delivery button: visible on any active project for non-deputy users (including individual members)
    const canExtend = isActive && !this.state.isDeputy;
    $('#ftm-details-btn-extend-delivery').toggleClass('hidden', !canExtend);

    // Reset Extension button: visible for admins / leaders when project has extensions
    const canReset = hasExt && !this.state.isDeputy && !!(this.state.isAdmin || this.state.isLeader);
    $('#ftm-details-btn-reset-extension').toggleClass('hidden', !canReset);
},

renderMyProjectsTable(projects) {
    return this.renderMyAssignedProjectsList();
},








loadAttendanceRoster(targetDate, targetTeam) {
    const self = this;
    const date = targetDate || $('#ftm-team-report-date').val() || new Date().toISOString().split('T')[0];
    const team = (targetTeam && targetTeam !== 'all') ? targetTeam : '';
    const query = team ? `?date=${encodeURIComponent(date)}&team=${encodeURIComponent(team)}` : `?date=${encodeURIComponent(date)}`;

    return this.api('attendance' + query).then(res => {
        if (!res) return;
        const kpis = res.kpis || {};
        $('#ftm-att-kpi-present').text(`Present: ${kpis.present || 0}`);
        $('#ftm-att-kpi-working').text(`Working: ${kpis.working || 0}`);
        $('#ftm-att-kpi-home-office').text(`Home Office: ${kpis.home_office || 0}`);
        $('#ftm-att-kpi-leave').text(`On Leave: ${kpis.on_leave || 0}`);
        $('#ftm-att-kpi-dayoff').text(`Day-Off: ${kpis.day_off || 0}`);
        $('#ftm-att-kpi-late').text(`Late / Absent: ${(kpis.late || 0) + (kpis.absent || 0)}`);

        const roster = res.roster || [];
        const container = $('#ftm-attendance-roster-list');
        if (!container.length) return;

        if (!roster.length) {
            container.html('<div style="text-align:center; padding:12px; color:var(--ftm-text-muted); font-size:12px;">No roster records found.</div>');
            return;
        }

        let html = '';
        roster.forEach(r => {
            let statusBadge = '';
            if (r.status === 'present') {
                statusBadge = '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">✓ Present</span>';
            } else if (r.status === 'working') {
                statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">⏳ Working</span>';
            } else if (r.status === 'home_office') {
                statusBadge = '<span class="ftm-badge bg-indigo-soft text-indigo ftm-badge-xs">🏠 Home Office</span>';
            } else if (r.status === 'on_leave') {
                statusBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">🏖️ On Leave</span>';
            } else if (r.status === 'day_off') {
                statusBadge = '<span class="ftm-badge ftm-badge-outline ftm-badge-xs">Off-Day</span>';
            } else if (r.status === 'late') {
                statusBadge = '<span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs">⚠️ Late</span>';
            } else {
                statusBadge = '<span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs">Absent</span>';
            }

            let reportBadge = '';
            if (r.has_report) {
                reportBadge = `<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Submitted ${r.submission_time ? r.submission_time.slice(0,5) : ''}</span>`;
            } else if (r.status === 'day_off' || r.status === 'on_leave') {
                reportBadge = '<span class="ftm-text-muted" style="font-size:11px;">Not expected</span>';
            } else {
                reportBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">Report Pending</span>';
            }

            html += `
                <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--ftm-card-bg, #ffffff); border:1px solid var(--ftm-border); border-radius:8px; gap:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <img src="${ftmEsc(r.avatar)}" alt="${ftmEsc(r.name)}" style="width:26px; height:26px; border-radius:50%; object-fit:cover;">
                        <div>
                            <div style="font-size:12.5px; font-weight:700; color:var(--ftm-text-primary); line-height:1.2;">${ftmEsc(r.name)}</div>
                            <div style="font-size:11px; color:var(--ftm-text-muted);">${ftmEsc(r.team)} · ${ftmEsc(r.role_label || '')} · Day-off: ${ftmEsc(r.weekly_day_off)}</div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        ${statusBadge}
                        ${reportBadge}
                    </div>
                </div>
            `;
        });
        container.html(html);
    }).catch(err => {
        console.warn('loadAttendanceRoster error:', err);
    });
},

loadAttendanceView(date, team) {
    const self = this;
    const targetDate = date || $('#ftm-view-att-date').val() || new Date().toISOString().split('T')[0];
    const targetTeam = team || $('#ftm-view-att-team').val() || 'all';

    $('#ftm-view-att-date').val(targetDate);
    $('#ftm-view-att-team').val(targetTeam);

    const tbody = $('#ftm-view-attendance-table-body');
    if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {
        tbody.html('<tr><td colspan="6" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading attendance roster...</td></tr>');
    } else {
        tbody.css('opacity', '0.65');
    }

    const teamQuery = (targetTeam && targetTeam !== 'all') ? `&team=${encodeURIComponent(targetTeam)}` : '';

    this.api(`attendance?date=${encodeURIComponent(targetDate)}${teamQuery}`).then(res => {
        tbody.css('opacity', '1');
        if (!res) return;

        const kpis = res.kpis || {};
        $('#ftm-view-att-kpi-present').text(kpis.present || 0);
        $('#ftm-view-att-kpi-working').text(kpis.working || 0);
        $('#ftm-view-att-kpi-home-office').text(kpis.home_office || 0);
        $('#ftm-view-att-kpi-leave').text(kpis.on_leave || 0);
        $('#ftm-view-att-kpi-dayoff').text(kpis.day_off || 0);
        $('#ftm-view-att-kpi-late').text((kpis.late || 0) + (kpis.absent || 0));

        const roster = res.roster || [];
        if (!roster.length) {
            tbody.html('<tr><td colspan="6" style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">No attendance records found for this date.</td></tr>');
            return;
        }

        const canManage = (self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy;

        let html = '';
        roster.forEach(r => {
            let statusBadge = '';
            if (r.status === 'present') {
                statusBadge = '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm">✓ Present</span>';
            } else if (r.status === 'working') {
                statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">⏳ Working</span>';
            } else if (r.status === 'home_office') {
                statusBadge = '<span class="ftm-badge bg-indigo-soft text-indigo ftm-badge-sm">🏠 Home Office</span>';
            } else if (r.status === 'on_leave') {
                statusBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm">🏖️ On Leave</span>';
            } else if (r.status === 'day_off') {
                statusBadge = '<span class="ftm-badge ftm-badge-outline ftm-badge-sm">Weekly Day-Off</span>';
            } else if (r.status === 'late') {
                statusBadge = '<span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm">⚠️ Late (Cutoff Passed)</span>';
            } else {
                statusBadge = '<span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm">❌ Absent</span>';
            }

            let reportSubmissionHtml = '';
            if (r.has_report) {
                reportSubmissionHtml = `<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Submitted ${r.submission_time ? r.submission_time.slice(0,5) : ''}</span>`;
            } else if (r.status === 'day_off' || r.status === 'on_leave') {
                reportSubmissionHtml = '<span class="ftm-text-muted" style="font-size:12px;">Not expected</span>';
            } else {
                reportSubmissionHtml = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">⏳ Report Pending</span>';
            }

            let overrideActionHtml = '';
            if (canManage) {
                overrideActionHtml = `
                    <div style="display:inline-flex; align-items:center; gap:6px; justify-content:flex-end;">
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs ftm-btn-apply-member-leave" data-uid="${r.user_id}" data-name="${ftmEsc(r.name)}" title="Apply leave for ${ftmEsc(r.name)}">
                            🏖️ Leave
                        </button>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-btn-change-day-off" data-uid="${r.user_id}" data-name="${ftmEsc(r.name)}" data-current="${ftmEsc(r.weekly_day_off)}" title="Change weekly day-off">
                            📅 Day-off
                        </button>
                        <select class="ftm-select ftm-select-xs ftm-select-mark-att" data-uid="${r.user_id}" data-date="${targetDate}" style="font-size:11.5px; padding:3px 6px;">
                            <option value="" disabled selected>Override...</option>
                            <option value="present">Present</option>
                            <option value="working">Working</option>
                            <option value="home_office">Home Office</option>
                            <option value="on_leave">On Leave</option>
                            <option value="day_off">Day Off</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="auto">Reset to Auto</option>
                        </select>
                    </div>
                `;
            } else {
                overrideActionHtml = '<span class="ftm-text-muted" style="font-size:12px;">-</span>';
            }

            html += `
                <tr data-uid="${r.user_id}">
                    <td style="padding:10px 16px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="${ftmEsc(r.avatar)}" alt="${ftmEsc(r.name)}" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                            <div>
                                <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary);">${ftmEsc(r.name)}</div>
                                <div style="font-size:11px; color:var(--ftm-text-muted);">${ftmEsc(r.custom_id || '')} · ${ftmEsc(r.role_label || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:10px 16px;"><span class="ftm-badge ftm-badge-outline ftm-badge-sm">${ftmEsc(r.team)}</span></td>
                    <td style="padding:10px 16px;"><span style="font-weight:600; font-size:12.5px;">${ftmEsc(r.weekly_day_off)}</span></td>
                    <td style="padding:10px 16px;">
                        ${statusBadge}
                        ${r.notes ? `<div style="font-size:11px; color:var(--ftm-text-muted); margin-top:2px;">${ftmEsc(r.notes)}</div>` : ''}
                    </td>
                    <td style="padding:10px 16px;">${reportSubmissionHtml}</td>
                    <td style="padding:10px 16px; text-align:right;">${overrideActionHtml}</td>
                </tr>
            `;
        });

        tbody.html(html);
    }).catch(err => {
        tbody.css('opacity', '1');
        tbody.html(`<tr><td colspan="6" style="text-align:center; padding:24px; color:#ef4444;">Error loading attendance roster: ${ftmEsc(err.message || 'Unknown error')}</td></tr>`);
    });

    this.initAttendanceSubtabs();

    this.api('leaves').then(leaves => {
        const pending = (leaves || []).filter(l => l.status === 'pending');
        $('#ftm-att-tab-leaves-count').text(pending.length);
        $('#ftm-pending-leaves-count-badge').text(`${pending.length} Pending`);
    }).catch(() => {});
},

initAttendanceSubtabs() {
    const self = this;
    if (this._attendanceSubtabsBound) return;
    this._attendanceSubtabsBound = true;

    $(document).off('click', '#ftm-att-subtab-btn-roster').on('click', '#ftm-att-subtab-btn-roster', function(e) {
        e.preventDefault();
        $('#ftm-view-attendance .ftm-tab-btn').removeClass('active').css('color', 'var(--ftm-text-muted)');
        $(this).addClass('active').css('color', 'var(--ftm-text-primary)');
        $('#ftm-att-panel-roster').removeClass('hidden');
        $('#ftm-att-panel-leaves, #ftm-att-panel-report').addClass('hidden');
        self.loadAttendanceView();
    });

    $(document).off('click', '#ftm-att-subtab-btn-leaves').on('click', '#ftm-att-subtab-btn-leaves', function(e) {
        e.preventDefault();
        $('#ftm-view-attendance .ftm-tab-btn').removeClass('active').css('color', 'var(--ftm-text-muted)');
        $(this).addClass('active').css('color', 'var(--ftm-text-primary)');
        $('#ftm-att-panel-leaves').removeClass('hidden');
        $('#ftm-att-panel-roster, #ftm-att-panel-report').addClass('hidden');
        self.loadLeavesList();
    });

    $(document).off('click', '#ftm-att-subtab-btn-report').on('click', '#ftm-att-subtab-btn-report', function(e) {
        e.preventDefault();
        $('#ftm-view-attendance .ftm-tab-btn').removeClass('active').css('color', 'var(--ftm-text-muted)');
        $(this).addClass('active').css('color', 'var(--ftm-text-primary)');
        $('#ftm-att-panel-report').removeClass('hidden');
        $('#ftm-att-panel-roster, #ftm-att-panel-leaves').addClass('hidden');
        self.loadLeaveManagementReportView();
    });

    $(document).off('change', '.ftm-select-mark-att').on('change', '.ftm-select-mark-att', function() {
        const uid = $(this).data('uid');
        const date = $(this).data('date');
        const status = $(this).val();
        if (!status) return;

        self.api('attendance/mark', 'POST', { user_id: uid, date: date, status: status }).then(res => {
            self.toast(res.message || 'Attendance status updated', 'success');
            self.loadAttendanceView();
        }).catch(err => {
            self.toast('Error updating attendance: ' + (err.message || 'Unknown'), 'error');
            self.loadAttendanceView();
        });
    });

    $(document).off('click', '.ftm-btn-change-day-off').on('click', '.ftm-btn-change-day-off', function(e) {
        e.preventDefault();
        const uid = $(this).data('uid');
        const name = $(this).data('name');
        const current = $(this).data('current');
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const chosen = prompt(`Change weekly day-off for ${name}\nCurrent day-off: ${current}\n\nEnter new day-off (${days.join(', ')}):`, current);
        if (!chosen) return;
        const matched = days.find(d => d.toLowerCase() === chosen.trim().toLowerCase());
        if (!matched) {
            self.toast('Invalid day of week entered.', 'error');
            return;
        }

        self.api(`members/${uid}/day-off`, 'POST', { day_off: matched.toLowerCase() }).then(res => {
            self.toast(res.message || `Weekly day-off updated to ${matched}.`, 'success');
            self.loadAttendanceView();
        }).catch(err => {
            self.toast('Error setting day-off: ' + (err.message || 'Unknown'), 'error');
        });
    });

    $(document).off('change', '#ftm-leaves-filter-status').on('change', '#ftm-leaves-filter-status', function() {
        self.loadLeavesList();
    });

    $(document).off('change', '#ftm-lr-filter-month, #ftm-lr-filter-team').on('change', '#ftm-lr-filter-month, #ftm-lr-filter-team', function() {
        self.loadLeaveManagementReportView();
    });

    $(document).off('click', '#ftm-lr-btn-refresh').on('click', '#ftm-lr-btn-refresh', function(e) {
        e.preventDefault();
        self.loadLeaveManagementReportView();
    });
},

loadLeavesList() {
    const self = this;
    const filterStatus = $('#ftm-leaves-filter-status').val() || 'pending';
    const scrollSnap = this.captureScrollState();
    const tbody = $('#ftm-view-leaves-queue-body');
    const modalContainer = $('#ftm-leaves-table-container');

    if (tbody.length) {
        if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {
            tbody.html('<tr><td colspan="7" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading leave applications...</td></tr>');
        } else {
            tbody.css('opacity', '0.65');
        }
    }
    if (modalContainer.length && !modalContainer.children().length) {
        modalContainer.html('<div style="text-align:center; padding:20px;"><div class="ftm-spinner"></div> Loading applications...</div>');
    }

    this.api('leaves').then(leaves => {
        if (tbody.length) tbody.css('opacity', '1');
        leaves = leaves || [];
        let filtered = leaves;
        if (filterStatus !== 'all') {
            filtered = leaves.filter(l => l.status === filterStatus);
        }

        const pending = leaves.filter(l => l.status === 'pending');
        $('#ftm-att-tab-leaves-count').text(pending.length);
        $('#ftm-pending-leaves-count-badge').text(`${pending.length} Pending`);

        const canReview = (self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy;

        // Render table in Attendance subtab 2 (#ftm-view-leaves-queue-body)
        if (tbody.length) {
            if (!filtered.length) {
                tbody.html('<tr><td colspan="7" style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">No leave applications found.</td></tr>');
            } else {
                let html = '';
                filtered.forEach(l => {
                    let statusBadge = '';
                    if (l.status === 'approved') {
                        statusBadge = '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm">Approved</span>';
                    } else if (l.status === 'leader_approved') {
                        statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">Leader Verified</span>';
                    } else if (l.status === 'rejected') {
                        statusBadge = '<span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm">Rejected</span>';
                    } else {
                        statusBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm">Pending</span>';
                    }

                    let actionsHtml = '';
                    if (canReview && (l.status === 'pending' || (self.state.isAdmin && l.status === 'leader_approved'))) {
                        actionsHtml = `
                            <div style="display:inline-flex; gap:6px;">
                                <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-primary ftm-btn-review-leave" data-id="${l.id}" data-action="${self.state.isAdmin ? 'approve' : 'leader_approve'}" title="Approve leave">Approve</button>
                                <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-secondary ftm-btn-review-leave" data-id="${l.id}" data-action="reject" title="Reject leave">Reject</button>
                            </div>
                        `;
                    } else {
                        actionsHtml = statusBadge;
                    }

                    html += `
                        <tr>
                            <td style="padding:10px 16px;">
                                <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary);">${ftmEsc(l.user_name)}</div>
                                <div style="font-size:11px; color:var(--ftm-text-muted);">${ftmEsc(l.team_name || '')}</div>
                            </td>
                            <td style="padding:10px 16px;"><span class="ftm-badge ftm-badge-outline ftm-badge-xs">${ftmEsc(l.leave_type_label || l.request_type_label || 'Leave')}</span></td>
                            <td style="padding:10px 16px; font-size:12.5px;">${ftmEsc(l.start_date_formatted || l.start_date)} to ${ftmEsc(l.end_date_formatted || l.end_date)} (<b>${l.days_count}d</b>)</td>
                            <td style="padding:10px 16px; font-size:12px; color:var(--ftm-text-muted);">${ftmEsc(l.reason || '-')}</td>
                            <td style="padding:10px 16px; font-size:12px;">${ftmEsc(l.leader_status || 'Pending')}</td>
                            <td style="padding:10px 16px; font-size:12px;">${ftmEsc(l.manager_status || 'Pending')}</td>
                            <td style="padding:10px 16px; text-align:right;">${actionsHtml}</td>
                        </tr>
                    `;
                });
                tbody.html(html);
            }
        }

        // Render modal container
        if (modalContainer.length) {
            if (!filtered.length) {
                modalContainer.html('<div style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">No applications found for this filter.</div>');
            } else {
                let mHtml = '<div style="display:flex; flex-direction:column; gap:10px;">';
                filtered.forEach(l => {
                    let stBadge = l.status === 'approved' ? '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Approved</span>' : (l.status === 'leader_approved' ? '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Leader Verified</span>' : (l.status === 'rejected' ? '<span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs">Rejected</span>' : '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">Pending</span>'));
                    mHtml += `
                        <div style="background:var(--ftm-surface, rgba(255,255,255,0.05)); border:1px solid var(--ftm-border); border-radius:8px; padding:12px 14px; display:flex; justify-content:space-between; align-items:center; gap:12px;">
                            <div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <strong style="font-size:13px; color:var(--ftm-text-primary);">${ftmEsc(l.user_name)}</strong>
                                    <span class="ftm-badge ftm-badge-outline ftm-badge-xs">${ftmEsc(l.team_name)}</span>
                                    ${stBadge}
                                </div>
                                <div style="font-size:12px; color:var(--ftm-text-secondary); margin-top:3px;">
                                    ${ftmEsc(l.leave_type_label || 'Leave')}: ${ftmEsc(l.start_date)} to ${ftmEsc(l.end_date)} (${l.days_count} days)
                                </div>
                                <div style="font-size:11.5px; color:var(--ftm-text-muted); margin-top:2px;">
                                    Reason: ${ftmEsc(l.reason || '-')}
                                </div>
                            </div>
                            ${(canReview && (l.status === 'pending' || (self.state.isAdmin && l.status === 'leader_approved'))) ? `
                            <div style="display:inline-flex; gap:6px;">
                                <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-primary ftm-btn-review-leave" data-id="${l.id}" data-action="${self.state.isAdmin ? 'approve' : 'leader_approve'}">Approve</button>
                                <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-secondary ftm-btn-review-leave" data-id="${l.id}" data-action="reject">Reject</button>
                            </div>
                            ` : ''}
                        </div>
                    `;
                });
                mHtml += '</div>';
                modalContainer.html(mHtml);
            }
        }

        // Attach Approved Remarks Popup Handler for Leaves
        $(document).off('click', '.ftm-btn-review-leave').on('click', '.ftm-btn-review-leave', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const action = $(this).data('action');

            const isReject = (action === 'reject');
            const promptTitle = isReject
                ? 'Enter rejection reason (required):'
                : 'Enter approval remarks / feedback for specialist (optional):';

            const remarks = prompt(promptTitle, isReject ? '' : 'Approved by leadership');
            if (isReject && (!remarks || !remarks.trim())) {
                self.toast('Rejection reason is required.', 'warning');
                return;
            }

            self.api(`leaves/${id}/review`, 'POST', { action: action, feedback: remarks || '' }).then(res => {
                self.toast(res.message || `Leave application updated successfully!`, 'success');
                self.loadLeavesList();
                self.loadAttendanceView();
            }).catch(err => {
                self.toast('Error reviewing leave: ' + (err.message || 'Unknown error'), 'error');
            });
        });
        self.restoreScrollState(scrollSnap);
    }).catch(err => {
        if (tbody.length) {
            tbody.css('opacity', '1').html(`<tr><td colspan="7" style="text-align:center; padding:24px; color:#ef4444;">Error loading leaves: ${ftmEsc(err.message || 'Unknown error')}</td></tr>`);
        }
    });
},

loadLeaveManagementReportView(month, team) {
    const self = this;
    const targetMonth = month || $('#ftm-lr-filter-month').val() || new Date().toISOString().slice(0, 7);
    const targetTeam = team || $('#ftm-lr-filter-team').val() || 'all';

    $('#ftm-lr-filter-month').val(targetMonth);
    $('#ftm-lr-filter-team').val(targetTeam);

    const scrollSnap = this.captureScrollState();
    const tbody = $('#ftm-lr-matrix-table-body');
    if (tbody.length) {
        if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {
            tbody.html('<tr><td colspan="10" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading specialist leave quota matrix...</td></tr>');
        } else {
            tbody.css('opacity', '0.65');
        }
    }

    const teamQuery = (targetTeam && targetTeam !== 'all') ? `&team=${encodeURIComponent(targetTeam)}` : '';

    this.api(`leaves/report?month=${encodeURIComponent(targetMonth)}${teamQuery}`).then(res => {
        if (tbody.length) tbody.css('opacity', '1');
        if (!res) return;
        const kpis = res.kpis || {};
        $('#ftm-lr-kpi-month-days').text((parseFloat(kpis.total_taken_month || 0)).toFixed(1) + 'd');
        $('#ftm-lr-kpi-year-days').text((parseFloat(kpis.total_taken_year || 0)).toFixed(1) + 'd');
        $('#ftm-lr-kpi-casual-days').text((parseFloat(kpis.casual_taken_month || 0)).toFixed(1) + 'd');
        $('#ftm-lr-kpi-sick-days').text((parseFloat(kpis.sick_taken_month || 0)).toFixed(1) + 'd');
        $('#ftm-lr-kpi-pending').text(kpis.pending_approvals || 0);

        const matrix = res.matrix || [];
        if (!matrix.length) {
            if (tbody.length) tbody.html('<tr><td colspan="10" style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">No specialists found in report.</td></tr>');
            return;
        }

        const canAdjust = self.state.isAdmin && !self.state.isDeputy;
        const canApply = !!(self.state.isAdmin || self.state.isLeader);

        let html = '';
        matrix.forEach(m => {
            const barPct = m.effective_quota > 0 ? Math.min(100, Math.round((m.total_taken_year / m.effective_quota) * 100)) : 0;
            const barColor = barPct > 80 ? '#ef4444' : (barPct > 50 ? '#f59e0b' : '#10b981');
            const remBal = (m.balance_remaining !== undefined && m.balance_remaining !== null) ? parseFloat(m.balance_remaining) : (parseFloat(m.remaining_balance || 0));

            html += `
                <tr>
                    <td style="padding:10px 16px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="${ftmEsc(m.avatar)}" alt="${ftmEsc(m.name)}" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                            <div>
                                <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary);">${ftmEsc(m.name)}</div>
                                <div style="font-size:11px; color:var(--ftm-text-muted);">${ftmEsc(m.role_label || 'Specialist')}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:10px 16px;"><span class="ftm-badge ftm-badge-outline ftm-badge-sm">${ftmEsc(m.team)}</span></td>
                    <td style="padding:10px 16px; text-align:center;">
                        <span style="font-weight:700; color:var(--ftm-text-primary);">${m.effective_quota}d</span>
                        ${m.adjustment_days ? `<span style="font-size:10px; font-weight:600; color:${m.adjustment_days > 0 ? '#10b981' : '#ef4444'}; margin-left:3px;">(${m.adjustment_days > 0 ? '+' : ''}${m.adjustment_days}d)</span>` : ''}
                    </td>
                    <td style="padding:10px 16px; text-align:center;">${m.casual_taken_month || 0}d</td>
                    <td style="padding:10px 16px; text-align:center;">${m.sick_taken_month || 0}d</td>
                    <td style="padding:10px 16px; text-align:center;">${m.switches_month || 0}</td>
                    <td style="padding:10px 16px; text-align:center; font-weight:700; color:#f59e0b;">${m.total_taken_year || 0}d</td>
                    <td style="padding:10px 16px; text-align:center;">
                        <span style="font-weight:700; color:${remBal <= 2 ? '#ef4444' : '#10b981'};">${remBal}d left</span>
                    </td>
                    <td style="padding:10px 16px; text-align:center;">
                        <div style="display:flex; align-items:center; gap:6px; justify-content:center;">
                            <div style="width:50px; height:5px; background:var(--ftm-border); border-radius:3px; overflow:hidden;">
                                <div style="width:${barPct}%; height:100%; background:${barColor}; border-radius:3px;"></div>
                            </div>
                            <span style="font-size:11px; font-weight:600; color:var(--ftm-text-muted);">${barPct}%</span>
                        </div>
                    </td>
                    <td style="padding:10px 16px; text-align:right;">
                        <div style="display:inline-flex; align-items:center; gap:6px; justify-content:flex-end;">
                            ${canApply ? `
                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs ftm-btn-apply-member-leave" data-uid="${m.user_id}" data-name="${ftmEsc(m.name)}" title="Apply leave for ${ftmEsc(m.name)}">
                                + Apply Leave
                            </button>
                            ` : ''}
                            ${canAdjust ? `
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-btn-adjust-quota" data-uid="${m.user_id}" data-name="${ftmEsc(m.name)}" data-quota="${m.annual_quota}" data-adj="${m.adjustment_days}" data-day="${ftmEsc(m.weekly_day_off)}" data-note="${ftmEsc(m.quota_note || '')}" title="Adjust leave quota & balance">
                                ⚙ Adjust
                            </button>
                            ` : ''}
                            ${(!canApply && !canAdjust) ? '-' : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
        if (tbody.length) tbody.html(html);

        // Also render records history table (#ftm-lr-records-table-body)
        const recordsBody = $('#ftm-lr-records-table-body');
        if (recordsBody.length && Array.isArray(res.records)) {
            $('#ftm-lr-records-count').text(`${res.records.length} Records`);
            if (!res.records.length) {
                recordsBody.html('<tr><td colspan="8" style="text-align:center; padding:24px; color:var(--ftm-text-muted);">No leave records for this period.</td></tr>');
            } else {
                let recHtml = '';
                res.records.forEach(r => {
                    recHtml += `
                        <tr>
                            <td style="padding:10px 16px;"><strong>${ftmEsc(r.user_name)}</strong></td>
                            <td style="padding:10px 16px;"><span class="ftm-badge ftm-badge-outline ftm-badge-xs">${ftmEsc(r.leave_type_label || 'Leave')}</span></td>
                            <td style="padding:10px 16px;">${ftmEsc(r.start_date)} to ${ftmEsc(r.end_date)} (${r.days_count}d)</td>
                            <td style="padding:10px 16px; font-size:12px; color:var(--ftm-text-muted);">${ftmEsc(r.reason || '-')}</td>
                            <td style="padding:10px 16px;">${ftmEsc(r.leader_status || 'Pending')}</td>
                            <td style="padding:10px 16px;">${ftmEsc(r.manager_status || 'Pending')}</td>
                            <td style="padding:10px 16px; text-align:center;"><span class="ftm-badge ${r.status === 'approved' ? 'bg-emerald-soft text-emerald' : 'bg-amber-soft text-amber'} ftm-badge-xs">${ftmEsc(r.status)}</span></td>
                            <td style="padding:10px 16px; text-align:right;">-</td>
                        </tr>
                    `;
                });
                recordsBody.html(recHtml);
            }
        }
        self.restoreScrollState(scrollSnap);
    }).catch(err => {
        if (tbody.length) tbody.css('opacity', '1').html(`<tr><td colspan="10" style="text-align:center; padding:24px; color:#ef4444;">Error loading report: ${ftmEsc(err.message || 'Unknown error')}</td></tr>`);
    });
},

openApplyLeaveModal(memberId, memberName) {
    const form = $('#ftm-form-apply-leave');
    if (!form.length) return;
    form[0].reset();
    $('#ftm-leave-edit-id').val('');

    const today = new Date().toISOString().split('T')[0];
    $('#ftm-leave-start-date').val(today);
    $('#ftm-leave-end-date').val(today);
    $('#ftm-leave-request-type').val('leave').trigger('change');

    const canSelectMember = !!(this.state.isAdmin || this.state.isLeader);
    $('#ftm-leave-member-select-group').toggleClass('hidden', !canSelectMember);
    $('#ftm-leave-admin-controls').toggleClass('hidden', !this.state.isAdmin);

    const populateMembers = (members) => {
        const sel = $('#ftm-leave-member-id');
        sel.empty();
        sel.append('<option value="">-- Choose Team Member --</option>');
        (members || []).forEach(m => {
            const uid = m.id || m.ID || m.user_id;
            sel.append(`<option value="${uid}">${ftmEsc(m.name)} (${ftmEsc(m.team || 'Specialist')})</option>`);
        });
        if (memberId) {
            sel.val(memberId);
        }
    };

    if (canSelectMember) {
        if (this.state.teamMembers && this.state.teamMembers.length > 0) {
            populateMembers(this.state.teamMembers);
        } else {
            this.api('team-members').then(members => {
                this.state.teamMembers = members;
                populateMembers(members);
            }).catch(() => {});
        }
    }

    if (memberId) {
        const displayName = memberName || 'Specialist';
        $('#ftm-modal-leave-title').text(`Apply Leave for ${displayName}`);
        $('#ftm-leave-edit-member-banner').removeClass('hidden');
        $('#ftm-leave-edit-member-name').text(displayName);
    } else {
        $('#ftm-modal-leave-title').text('Apply for Leave / Adjust Day-Off');
        $('#ftm-leave-edit-member-banner').addClass('hidden');
    }

    $('#ftm-modal-apply-leave').removeClass('hidden');
},

openManageLeavesModal() {
    $('#ftm-modal-manage-leaves').removeClass('hidden');
    this.loadLeavesList();
},

submitLeaveForm() {
    const self = this;
    const reqType = $('#ftm-leave-request-type').val();
    const isSwitch = (reqType === 'day_off_switch');

    const data = {
        request_type: reqType,
        leave_type: isSwitch ? 'day_off_switch' : $('#ftm-leave-category').val(),
        start_date: isSwitch ? $('#ftm-switch-from-date').val() : $('#ftm-leave-start-date').val(),
        end_date: isSwitch ? $('#ftm-switch-to-date').val() : $('#ftm-leave-end-date').val(),
        switch_from_date: $('#ftm-switch-from-date').val(),
        switch_to_date: $('#ftm-switch-to-date').val(),
        reason: $('#ftm-leave-reason').val().trim()
    };

    if (this.state.isAdmin || this.state.isLeader) {
        const chosenUid = $('#ftm-leave-member-id').val();
        if (chosenUid) data.user_id = parseInt(chosenUid, 10);
    }

    if (this.state.isAdmin) {
        const stOverride = $('#ftm-leave-status-override').val();
        if (stOverride) {
            data.status = stOverride;
            data.status_override = stOverride;
        }
        const daysOverride = $('#ftm-leave-days-override').val();
        if (daysOverride !== '') {
            data.days_count = parseFloat(daysOverride);
            data.days_override = parseFloat(daysOverride);
        }
        const fb = $('#ftm-leave-manager-feedback').val();
        if (fb) data.manager_feedback = fb;
    }

    const btn = $('#ftm-btn-submit-leave');
    btn.prop('disabled', true).text('Submitting...');

    this.api('leaves', 'POST', data).then(res => {
        btn.prop('disabled', false).text('Submit Application');
        $('#ftm-modal-apply-leave').addClass('hidden');
        self.toast(res.message || 'Leave application submitted successfully!', 'success');
        self.loadAttendanceView();
        self.loadLeavesList();
        self.loadLeaveManagementReportView();
    }).catch(err => {
        btn.prop('disabled', false).text('Submit Application');
        self.toast(err.message || 'Failed to submit leave application', 'error');
    });
},

openLeaveQuotaModal(uid, name, quota, adj, day, note) {
    $('#ftm-quota-user-id').val(uid);
    $('#ftm-quota-member-name').text(name || 'Specialist');
    $('#ftm-quota-annual-days').val(quota !== undefined ? quota : 18);
    $('#ftm-quota-adjustment-days').val(adj !== undefined ? adj : 0);
    if (day) $('#ftm-quota-day-off').val(day.toLowerCase());
    $('#ftm-quota-note').val(note || '');
    $('#ftm-modal-leave-quota').removeClass('hidden');
},

submitLeaveQuotaForm() {
    const self = this;
    const uid = $('#ftm-quota-user-id').val();
    if (!uid) return;

    const data = {
        annual_quota: parseFloat($('#ftm-quota-annual-days').val()) || 18,
        adjustment_days: parseFloat($('#ftm-quota-adjustment-days').val()) || 0,
        day_off: $('#ftm-quota-day-off').val(),
        quota_note: $('#ftm-quota-note').val().trim()
    };

    this.api(`members/${uid}/leave-quota`, 'POST', data).then(res => {
        $('#ftm-modal-leave-quota').addClass('hidden');
        self.toast(res.message || 'Leave quota settings saved successfully!', 'success');
        self.loadLeaveManagementReportView();
        self.loadAttendanceView();
    }).catch(err => {
        self.toast(err.message || 'Failed to save leave quota', 'error');
    });
},

loadTimeTrackerHubView() {
    const self = this;
    const view = $('#ftm-view-time-tracker');
    if (!view.length) return;

    this.api('time-tracker/stats').then(stats => {
        if (!stats) return;
        $('#ftm-hub-kpi-total-hours').text(stats.total_hours_formatted || '0h 00m');
        $('#ftm-hub-kpi-today-hours').text(stats.today_hours_formatted || '0h 00m');
        $('#ftm-hub-kpi-total-snaps').text(stats.total_snapshots !== undefined ? stats.total_snapshots : 0);
        $('#ftm-hub-kpi-active-sessions').text(stats.active_sessions_count !== undefined ? stats.active_sessions_count : 0);

        const banner = $('#ftm-hub-live-active-container');
        const list = $('#ftm-hub-live-active-list');
        if (stats.active_sessions && stats.active_sessions.length) {
            banner.removeClass('hidden');
            let sHtml = '';
            stats.active_sessions.forEach(s => {
                sHtml += `
                    <div style="background:var(--ftm-card-bg, #ffffff); border:1px solid #10b981; border-radius:10px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="${ftmEsc(s.avatar)}" alt="${ftmEsc(s.specialist_name)}" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #10b981;">
                            <div>
                                <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary);">● ${ftmEsc(s.specialist_name)}</div>
                                <div style="font-size:11.5px; color:var(--ftm-text-muted);">${ftmEsc(s.team_name || '')} · ${ftmEsc(s.order_id)} · ${ftmEsc(s.time_formatted || '')}</div>
                            </div>
                        </div>
                        <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-weight:700;">Live Timer</span>
                    </div>
                `;
            });
            list.html(sHtml);
        } else {
            banner.addClass('hidden');
        }
    }).catch(() => {});

    this.loadHubSnapshotsGallery();
    this.initHubEvents();
},

loadHubSnapshotsGallery() {
    const self = this;
    const container = $('#ftm-hub-gallery-grid');
    if (!container.length) return;

    const squad = $('#ftm-hub-filter-team').val() || 'all';
    const date = $('#ftm-hub-filter-date').val() || '';
    const type = $('#ftm-hub-filter-session-type').val() || 'all';
    const search = ($('#ftm-hub-filter-search').val() || '').trim();

    container.html('<div style="grid-column:1/-1; text-align:center; padding:40px;"><div class="ftm-spinner"></div> Loading screenshots...</div>');

    let query = `?team=${encodeURIComponent(squad)}&session_type=${encodeURIComponent(type)}`;
    if (date) query += `&date=${encodeURIComponent(date)}`;
    if (search) query += `&search=${encodeURIComponent(search)}`;

    this.api('time-tracker/snapshots' + query).then(res => {
        const snaps = (res && res.snapshots) ? res.snapshots : (Array.isArray(res) ? res : []);
        $('#ftm-hub-tab-snaps-count').text(snaps.length);
        if (!snaps.length) {
            container.html('<div style="grid-column:1/-1; text-align:center; padding:48px 16px; color:var(--ftm-text-muted);">No screenshot captures found matching filters.</div>');
            return;
        }

        let html = '';
        snaps.forEach(s => {
            const isExtra = s.session_type === 'extra_time';
            html += `
                <div class="ftm-snap-card" data-snap-id="${s.id}" style="background:var(--ftm-card-bg, #ffffff); border:1px solid var(--ftm-border); border-radius:10px; overflow:hidden; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s;">
                    <div style="position:relative; aspect-ratio:16/9; background:#0f172a; overflow:hidden;">
                        <img src="${ftmEsc(s.image_url || s.url || '')}" alt="Capture" style="width:100%; height:100%; object-fit:cover; display:block;" loading="lazy">
                        <span class="ftm-badge ${isExtra ? 'bg-amber-soft text-amber' : 'bg-blue-soft text-blue'} ftm-badge-xs" style="position:absolute; top:8px; right:8px; font-weight:700;">
                            ${isExtra ? 'Extra' : 'Regular'}
                        </span>
                    </div>
                    <div style="padding:10px 12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong style="font-size:12.5px; color:var(--ftm-text-primary);">${ftmEsc(s.specialist_name)}</strong>
                            <span style="font-size:11px; color:var(--ftm-text-muted);">${ftmEsc(s.team_name || '')}</span>
                        </div>
                        <div style="font-size:11.5px; color:var(--ftm-text-secondary); margin-top:2px;">
                            ${ftmEsc(s.order_id)} · ${ftmEsc(s.client_name || 'Client')}
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; font-size:10.5px; color:var(--ftm-text-muted);">
                            <span>🖱️ ${s.mouse_clicks || 0} · ⌨️ ${s.keyboard_clicks ?? s.keystrokes ?? 0}</span>
                            <span>${ftmEsc(s.captured_at_formatted || s.captured_at || '')}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        container.html(html);

        container.find('.ftm-snap-card').off('click').on('click', function() {
            const sid = $(this).data('snap-id');
            const found = snaps.find(x => String(x.id) === String(sid));
            if (found) self.openSnapshotLightbox(found);
        });
    }).catch(err => {
        container.html(`<div style="grid-column:1/-1; text-align:center; padding:32px; color:#ef4444;">Error loading screenshots: ${ftmEsc(err.message || 'Unknown')}</div>`);
    });
},

loadHubDailyRecords() {
    const self = this;
    const tbody = $('#ftm-hub-daily-table-body');
    if (!tbody.length) return;
    const scrollSnap = this.captureScrollState();
    if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {
        tbody.html('<tr><td colspan="9" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading date-wise time records...</td></tr>');
    } else {
        tbody.css('opacity', '0.65');
    }

    const fromDate = $('#ftm-hub-daily-from').val() || '';
    const toDate = $('#ftm-hub-daily-to').val() || '';
    const team = $('#ftm-hub-daily-team').val() || 'all';
    const uid = $('#ftm-hub-daily-specialist').val() || '0';

    let q = `?team=${encodeURIComponent(team)}`;
    if (fromDate) q += `&date_from=${encodeURIComponent(fromDate)}`;
    if (toDate) q += `&date_to=${encodeURIComponent(toDate)}`;
    if (uid && uid !== '0') q += `&user_id=${encodeURIComponent(uid)}`;

    this.api('time-tracker/daily-summary' + q).then(res => {
        const summary = res.summary || {};
        $('#ftm-hub-daily-kpi-total').text(summary.total_formatted || '0h 00m');
        $('#ftm-hub-daily-kpi-regular').text(summary.regular_formatted || '0h 00m');
        $('#ftm-hub-daily-kpi-extra').text(summary.extra_formatted || '0h 00m');
        $('#ftm-hub-daily-kpi-counts').text(`${summary.sessions_count || 0} / ${summary.snapshots_count || 0}`);

        const records = res.records || [];
        $('#ftm-hub-tab-daily-count').text(records.length);

        if (!records.length) {
            tbody.html('<tr><td colspan="9" style="text-align:center; padding:32px; color:var(--ftm-text-muted);">No date-wise time records found.</td></tr>');
            return;
        }

        let html = '';
        records.forEach(r => {
            const ordersStr = (r.orders || []).map(o => `${o.order_id} (${o.time_formatted})`).join(', ') || '-';
            html += `
                <tr>
                    <td style="padding:10px 16px;"><strong>${ftmEsc(r.date_formatted || r.date)}</strong></td>
                    <td style="padding:10px 16px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <img src="${ftmEsc(r.avatar)}" alt="" style="width:26px; height:26px; border-radius:50%; object-fit:cover;">
                            <span>${ftmEsc(r.specialist_name)}</span>
                        </div>
                    </td>
                    <td style="padding:10px 16px;"><span class="ftm-badge ftm-badge-outline ftm-badge-xs">${ftmEsc(r.team_name || '')}</span></td>
                    <td style="padding:10px 16px;">${ftmEsc(r.regular_formatted || '0h 00m')}</td>
                    <td style="padding:10px 16px; color:#f59e0b; font-weight:600;">${ftmEsc(r.extra_formatted || '0h 00m')}</td>
                    <td style="padding:10px 16px; font-weight:700; color:var(--ftm-text-primary);">${ftmEsc(r.total_formatted || '0h 00m')}</td>
                    <td style="padding:10px 16px; font-size:12px; color:var(--ftm-text-secondary); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${ftmEsc(ordersStr)}">${ftmEsc(ordersStr)}</td>
                    <td style="padding:10px 16px;">${r.sessions_count || 0} sessions · ${r.snapshots_count || 0} snaps</td>
                    <td style="padding:10px 16px; text-align:right;">
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-hub-inspect-day-btn" data-date="${r.date}" data-uid="${r.user_id}">
                            Inspect
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.css('opacity', '1').html(html);
        self.restoreScrollState(scrollSnap);

        tbody.find('.ftm-hub-inspect-day-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const d = $(this).data('date');
            $('#ftm-hub-filter-date').val(d);
            $('#ftm-hub-subtab-gallery').trigger('click');
        });
    }).catch(err => {
        tbody.css('opacity', '1').html(`<tr><td colspan="9" style="text-align:center; padding:24px; color:#ef4444;">Error loading daily records: ${ftmEsc(err.message || 'Unknown')}</td></tr>`);
    });
},

loadHubSessions() {
    const self = this;
    const tbody = $('#ftm-hub-sessions-table-body');
    if (!tbody.length) return;
    const scrollSnap = this.captureScrollState();
    if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {
        tbody.html('<tr><td colspan="10" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading sessions log...</td></tr>');
    } else {
        tbody.css('opacity', '0.65');
    }

    const date = $('#ftm-hub-sessions-date').val() || '';
    const squad = $('#ftm-hub-sessions-team').val() || 'all';
    const type = $('#ftm-hub-sessions-type').val() || 'all';
    const search = ($('#ftm-hub-sessions-search').val() || '').trim();

    let query = `?team=${encodeURIComponent(squad)}&session_type=${encodeURIComponent(type)}`;
    if (date) query += `&date=${encodeURIComponent(date)}`;
    if (search) query += `&search=${encodeURIComponent(search)}`;

    this.api('time-tracker/sessions' + query).then(res => {
        const sessions = (res && res.sessions) ? res.sessions : (Array.isArray(res) ? res : []);
        $('#ftm-hub-tab-sessions-count').text(sessions.length);
        if (!sessions.length) {
            tbody.html('<tr><td colspan="10" style="text-align:center; padding:32px; color:var(--ftm-text-muted);">No tracking sessions found.</td></tr>');
            return;
        }

        let html = '';
        sessions.forEach(s => {
            const isAct = (s.status === 'active');
            const mClicks = parseInt(s.total_mouse_clicks ?? s.mouse_clicks ?? 0, 10) || 0;
            const kClicks = parseInt(s.total_keyboard_clicks ?? s.keyboard_clicks ?? 0, 10) || 0;
            const isSynced = !!(s.logged_to_report || s.synced_to_daily_report);
            html += `
                <tr>
                    <td style="padding:10px 16px;">#${s.id}</td>
                    <td style="padding:10px 16px;"><strong>${ftmEsc(s.specialist_name)}</strong></td>
                    <td style="padding:10px 16px;"><span class="ftm-badge ftm-badge-outline ftm-badge-xs">${ftmEsc(s.team_name || '')}</span></td>
                    <td style="padding:10px 16px;">${ftmEsc(s.order_id)} <span class="ftm-text-muted" style="font-size:11px;">(${ftmEsc(s.client_name || 'Client')})</span></td>
                    <td style="padding:10px 16px; font-size:12px;">${ftmEsc(s.start_time || '')} to ${ftmEsc(s.end_time || (isAct ? 'Running' : '-'))}</td>
                    <td style="padding:10px 16px; font-weight:700;">${ftmEsc(s.time_formatted || s.duration_formatted || '0m')}</td>
                    <td style="padding:10px 16px; font-size:11.5px; white-space:nowrap;">🖱️ <strong>${mClicks.toLocaleString()}</strong> · ⌨️ <strong>${kClicks.toLocaleString()}</strong></td>
                    <td style="padding:10px 16px;"><span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">${s.snapshots_count || 0} snaps</span></td>
                    <td style="padding:10px 16px; font-size:11.5px;">${isSynced ? '<span class="text-emerald" style="font-weight:600;">✓ Synced</span>' : '-'}</td>
                    <td style="padding:10px 16px; text-align:right;">
                        <span class="ftm-badge ${isAct ? 'bg-emerald-soft text-emerald' : 'bg-slate-soft text-slate'} ftm-badge-xs">${isAct ? 'Active' : 'Completed'}</span>
                    </td>
                </tr>
            `;
        });
        tbody.css('opacity', '1').html(html);
        self.restoreScrollState(scrollSnap);
    }).catch(err => {
        tbody.css('opacity', '1').html(`<tr><td colspan="10" style="text-align:center; padding:24px; color:#ef4444;">Error loading sessions: ${ftmEsc(err.message || 'Unknown')}</td></tr>`);
    });
},

initHubEvents() {
    const self = this;
    if (this._hubEventsBound) return;
    this._hubEventsBound = true;

    $('#ftm-hub-subtab-gallery').on('click', function(e) {
        e.preventDefault();
        $('#ftm-view-time-tracker .ftm-tab-btn').removeClass('active').attr('aria-selected', 'false');
        $(this).addClass('active').attr('aria-selected', 'true');
        $('#ftm-hub-view-gallery').removeClass('hidden');
        $('#ftm-hub-view-daily, #ftm-hub-view-sessions').addClass('hidden');
        self.loadHubSnapshotsGallery();
    });

    $('#ftm-hub-subtab-daily').on('click', function(e) {
        e.preventDefault();
        $('#ftm-view-time-tracker .ftm-tab-btn').removeClass('active').attr('aria-selected', 'false');
        $(this).addClass('active').attr('aria-selected', 'true');
        $('#ftm-hub-view-daily').removeClass('hidden');
        $('#ftm-hub-view-gallery, #ftm-hub-view-sessions').addClass('hidden');
        self.loadHubDailyRecords();
    });

    $('#ftm-hub-subtab-sessions').on('click', function(e) {
        e.preventDefault();
        $('#ftm-view-time-tracker .ftm-tab-btn').removeClass('active').attr('aria-selected', 'false');
        $(this).addClass('active').attr('aria-selected', 'true');
        $('#ftm-hub-view-sessions').removeClass('hidden');
        $('#ftm-hub-view-gallery, #ftm-hub-view-daily').addClass('hidden');
        self.loadHubSessions();
    });

    $('#ftm-hub-btn-refresh').on('click', function(e) {
        e.preventDefault();
        self.loadTimeTrackerHubView();
        if ($('#ftm-hub-subtab-daily').hasClass('active')) self.loadHubDailyRecords();
        if ($('#ftm-hub-subtab-sessions').hasClass('active')) self.loadHubSessions();
    });

    $('#ftm-hub-filter-team, #ftm-hub-filter-date, #ftm-hub-filter-session-type').on('change', function() {
        self.loadHubSnapshotsGallery();
    });
    $('#ftm-hub-filter-search').on('input', function() {
        clearTimeout(self._hubSearchTimer);
        self._hubSearchTimer = setTimeout(() => {
            self.loadHubSnapshotsGallery();
        }, 300);
    });
    $('#ftm-hub-btn-reset-filters').on('click', function(e) {
        e.preventDefault();
        $('#ftm-hub-filter-team').val('all');
        $('#ftm-hub-filter-date').val('');
        $('#ftm-hub-filter-session-type').val('all');
        $('#ftm-hub-filter-search').val('');
        self.loadHubSnapshotsGallery();
    });

    $('.ftm-hub-daily-preset').on('click', function(e) {
        e.preventDefault();
        $('.ftm-hub-daily-preset').removeClass('active');
        $(this).addClass('active');
        const p = $(this).data('preset');
        const now = new Date();
        const formatDate = (d) => d.toISOString().split('T')[0];

        if (p === 'today') {
            const today = formatDate(now);
            $('#ftm-hub-daily-from').val(today);
            $('#ftm-hub-daily-to').val(today);
        } else if (p === 'yesterday') {
            const y = new Date(now); y.setDate(y.getDate() - 1);
            const yStr = formatDate(y);
            $('#ftm-hub-daily-from').val(yStr);
            $('#ftm-hub-daily-to').val(yStr);
        } else if (p === '7d') {
            const past7 = new Date(now); past7.setDate(past7.getDate() - 7);
            $('#ftm-hub-daily-from').val(formatDate(past7));
            $('#ftm-hub-daily-to').val(formatDate(now));
        } else if (p === '30d') {
            const past30 = new Date(now); past30.setDate(past30.getDate() - 30);
            $('#ftm-hub-daily-from').val(formatDate(past30));
            $('#ftm-hub-daily-to').val(formatDate(now));
        }
        self.loadHubDailyRecords();
    });

    $('#ftm-hub-daily-from, #ftm-hub-daily-to, #ftm-hub-daily-team, #ftm-hub-daily-specialist').on('change', function() {
        self.loadHubDailyRecords();
    });
    $('#ftm-hub-daily-reset').on('click', function(e) {
        e.preventDefault();
        $('.ftm-hub-daily-preset[data-preset="30d"]').trigger('click');
        $('#ftm-hub-daily-team').val('all');
        $('#ftm-hub-daily-specialist').val('0');
        self.loadHubDailyRecords();
    });

    $('#ftm-hub-sessions-date, #ftm-hub-sessions-team, #ftm-hub-sessions-type').on('change', function() {
        self.loadHubSessions();
    });
    $('#ftm-hub-sessions-search').on('input', function() {
        clearTimeout(self._sessSearchTimer);
        self._sessSearchTimer = setTimeout(() => {
            self.loadHubSessions();
        }, 300);
    });
    $('#ftm-hub-sessions-reset').on('click', function(e) {
        e.preventDefault();
        $('#ftm-hub-sessions-date').val('');
        $('#ftm-hub-sessions-team').val('all');
        $('#ftm-hub-sessions-type').val('all');
        $('#ftm-hub-sessions-search').val('');
        self.loadHubSessions();
    });
},

openSnapshotLightbox(snap) {
    if (!snap) return;
    const imgUrl = snap.image_url || snap.url || '';
    $('#ftm-snap-lightbox-title').text(`Screenshot #${snap.id || ''} · ${snap.specialist_name || 'Specialist'}`);
    $('#ftm-snap-lightbox-sub').text(`${snap.order_id || 'Order'} · ${snap.client_name || 'Client'} · Squad: ${snap.team_name || ''}`);
    $('#ftm-snap-lightbox-img').attr('src', imgUrl);
    $('#ftm-snap-lightbox-timestamp').text(`Captured at ${snap.captured_at_formatted || snap.captured_at || snap.created_at || 'Recently'}`);
    $('#ftm-snap-lightbox-activity-badge').text(`🖱️ ${snap.mouse_clicks || 0} clicks · ⌨️ ${snap.keyboard_clicks ?? snap.keystrokes ?? 0} keys`);

    const isExtra = snap.session_type === 'extra_time';
    $('#ftm-snap-lightbox-badge')
        .text(isExtra ? '⚡ Extra Time' : '⏱️ Regular Shift')
        .removeClass('bg-blue-soft text-blue bg-amber-soft text-amber')
        .addClass(isExtra ? 'bg-amber-soft text-amber' : 'bg-blue-soft text-blue');

    $('#ftm-snap-lightbox-notes-text').text(snap.notes || 'No audit notes attached.');
    $('#ftm-snap-lightbox-btn-edit-note').data('snap-id', snap.id).data('notes', snap.notes || '');
    $('#ftm-snap-lightbox-btn-delete').data('snap-id', snap.id);
    $('#ftm-snap-lightbox-btn-delete').toggleClass('hidden', !this.state.isAdmin);

    $('#ftm-modal-snap-lightbox').removeClass('hidden');
},

// Shared people-and-percent dialog; resolves with { userId: percent } or null when cancelled
askValueSplit(members, shares, net, title) {
    return new Promise(resolve => {
        $('#ftm-modal-value-split').remove();
        const equal = Math.round(10000 / members.length) / 100;
        const rows = members.map(m => '<div class="ftm-sp-row"><span class="ftm-sp-name">' + ftmEsc(m.name) + '</span><span class="ftm-sp-input"><input type="number" min="0" max="100" step="0.01" data-uid="' + m.id + '" value="' + ftmEsc(shares[m.id] !== undefined ? shares[m.id] : equal) + '" class="ftm-input ftm-input-sm"><b>%</b></span><span class="ftm-sp-money" data-money-uid="' + m.id + '"></span></div>').join('');
        const modal = $('<div id="ftm-modal-value-split" class="ftm-modal-backdrop"><div class="ftm-modal-dialog" style="max-width:480px;"><div class="ftm-modal-header"><div><h3 class="ftm-modal-title" style="margin:0;">' + ftmEsc(title || 'Divide the delivery value') + '</h3><div class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Net value to share: <b>$' + net.toFixed(2) + '</b></div></div></div><div class="ftm-modal-body" style="padding:18px 20px;"><div class="ftm-split-panel" style="margin:0;">' + rows + '<div class="ftm-sp-foot"><button type="button" class="ftm-btn ftm-btn-xs ftm-btn-secondary" id="ftm-vs-equal">Split equally</button><span id="ftm-vs-status"></span></div></div></div><div class="ftm-modal-footer" style="display:flex; justify-content:flex-end; gap:8px; padding:14px 20px;"><button type="button" class="ftm-btn ftm-btn-secondary" id="ftm-vs-cancel">Cancel</button><button type="button" class="ftm-btn ftm-btn-primary" id="ftm-vs-ok">' + (/^Edit/.test(title || '') ? 'Save split' : 'Confirm and deliver') + '</button></div></div></div>');
        $('body').append(modal);
        const refresh = () => {
            let total = 0;
            modal.find('input[data-uid]').each(function () { const v = parseFloat($(this).val()) || 0; total += v; modal.find('[data-money-uid="' + $(this).data('uid') + '"]').text('$' + (net * v / 100).toFixed(2)); });
            total = Math.round(total * 100) / 100;
            const ok = Math.abs(total - 100) < 0.01;
            modal.find('#ftm-vs-status').attr('class', ok ? 'ftm-cs-ok' : 'ftm-cs-bad').text(ok ? 'Adds up to 100%' : 'Total is ' + total + '% - must be 100%');
            modal.find('#ftm-vs-ok').prop('disabled', !ok);
        };
        modal.on('input', 'input[data-uid]', refresh);
        modal.on('click', '#ftm-vs-equal', () => { modal.find('input[data-uid]').val(equal); refresh(); });
        modal.on('click', '#ftm-vs-cancel', () => { modal.remove(); resolve(null); });
        modal.on('click', '#ftm-vs-ok', () => { const out = {}; modal.find('input[data-uid]').each(function () { out[$(this).data('uid')] = parseFloat($(this).val()) || 0; }); modal.remove(); resolve(out); });
        refresh();
        modal.removeClass('hidden').css('display', 'flex');
    });
},
updateProjectStatus(id, newStatus, reloadModal = true, extraData = {}) {
    // Delivering a project that has two or more assignees: decide who gets what share first
    if ((newStatus === 'delivery_submitted' || newStatus === 'delivery_accepted') && !extraData.assignee_splits && !extraData._splitAsked) {
        this.api('projects/' + id).then(r => {
            const members = (r && r.project && r.project.assigned_members) || [];
            if (members.length < 2) { this.updateProjectStatus(id, newStatus, reloadModal, Object.assign({ _splitAsked: true }, extraData)); return; }
            const net = parseFloat(r.project.net_delivery_value) || 0;
            this.askValueSplit(members, r.assignee_shares || {}, net, 'Divide the delivery value').then(sp => {
                if (sp === null) { this.loadProjects(); return; }
                this.updateProjectStatus(id, newStatus, reloadModal, Object.assign({ _splitAsked: true, assignee_splits: sp }, extraData));
            });
        }).catch(() => this.updateProjectStatus(id, newStatus, reloadModal, Object.assign({ _splitAsked: true }, extraData)));
        return;
    }
    delete extraData._splitAsked;
    const payload = Object.assign({ status: newStatus }, extraData);



    this.api(`projects/${id}/status`, 'POST', payload).then(res => {
        this.state.pendingTableStatusSelect = null;
        if (String(this.state.activeProjectId) === String(id)) {
            this.state.activeProjectStatus = newStatus;
        }
        this.toast(`Status updated to ${res.new_label}!`, 'success');



        this.loadStats();



        this.loadAlerts();



        this.loadProjects();



        if (reloadModal && this.state.activeProjectId === id) {



            this.openProjectDetailsModal(id);



        }



        if (this.state.activeMemberActivityId && !$('#ftm-modal-member-activity').hasClass('hidden')) {



            this.openMemberActivityModal(this.state.activeMemberActivityId, this.state.activeMemberActivityPeriod || 'current_month');



        }



    }).catch(err => {



        this.toast(err.message || 'Error updating status', 'error');



    });



},







addProjectNote(projectId, note) {



    this.api(`projects/${projectId}/notes`, 'POST', { note: note }).then(() => {



        $('#ftm-new-note-text').val('');



        this.toast('Note added!', 'success');



        this.openProjectDetailsModal(projectId);



    }).catch(err => {



        this.toast(err.message || 'Error adding note', 'error');



    });



},







deleteProject(id) {



    this.api(`projects/${id}`, 'DELETE').then(() => {



        $('#ftm-modal-details').addClass('hidden');



        this.toast('Project deleted successfully', 'success');



        this.loadProjects();



        this.loadStats();



        this.loadAlerts();



    }).catch(err => {



        this.toast(err.message || 'Error deleting project', 'error');



    });



},







// Team Members View



loadTeamMembersView(teamFilter = 'all') {
    const self = this;
    const container = $('#ftm-team-members-container');
    container.html('<div class="ftm-spinner"></div>');

    const includeInactive = $('#ftm-members-show-inactive').is(':checked');
    const params = [];
    if (teamFilter && teamFilter !== 'all') {
        params.push('team=' + encodeURIComponent(teamFilter));
    }
    if (includeInactive) {
        params.push('include_inactive=1');
    }
    const query = params.length ? ('?' + params.join('&')) : '';

    this.api('team-members' + query).then(members => {
        members = members || [];
        self.state.teamMembersDirectory = members;
        const canManageMembers = self.state.isAdmin && !self.state.isDeputy;
        let html = '';

        members.forEach(m => {
            const roleBadgeClass = m.team_role === 'leader' ? 'bg-amber-soft text-amber' : (m.team_role === 'co_leader' ? 'bg-purple-soft text-purple' : 'bg-blue-soft text-blue');
            const inactiveBadge = (m.status && m.status !== 'active')
                ? `<span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm" style="margin-left:4px;">${ftmEsc(m.status)}</span>`
                : '';

            html += `
                        <div class="ftm-member-profile-card">
                            <div class="ftm-member-avatar-wrap ftm-trigger-member-profile" data-id="${m.id}" style="cursor:pointer;" title="Click to view ${ftmEsc(m.name)}'s profile & portfolio">
                                <img src="${m.avatar}" alt="${ftmEsc(m.name)}">
                            </div>

                            <h3 class="ftm-member-card-name ftm-trigger-member-profile" data-id="${m.id}" style="cursor:pointer;" title="Click to view ${ftmEsc(m.name)}'s profile & portfolio">
                                ${ftmEsc(m.name)}
                                ${m.custom_id ? `<span class="ftm-badge ftm-badge-outline ftm-badge-sm" style="font-size:11px; margin-left:4px;">#${m.custom_id}</span>` : ''}
                                ${inactiveBadge}
                            </h3>

                            <div class="ftm-member-card-role" style="margin-top:4px;">
                                <span class="ftm-badge ${roleBadgeClass} ftm-badge-sm" style="font-weight:700;">${ftmEsc(m.team)} • ${ftmEsc(m.role_label)}</span>
                            </div>

                            <div class="ftm-member-metrics-box" style="margin-top:14px;">
                                <div class="ftm-metric-item">
                                    <span class="ftm-metric-val text-emerald">${m.active_projects}</span>
                                    <span class="ftm-metric-lbl">Active</span>
                                </div>
                                <div class="ftm-metric-item">
                                    <span class="ftm-metric-val text-amber">${m.wip_projects}</span>
                                    <span class="ftm-metric-lbl">WIP</span>
                                </div>
                                <div class="ftm-metric-item">
                                    <span class="ftm-metric-val text-purple">${m.revision_projects}</span>
                                    <span class="ftm-metric-lbl">Revision</span>
                                </div>
                            </div>

                            <div class="ftm-member-card-actions">
                                <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm ftm-btn-block ftm-trigger-member-profile" data-id="${m.id}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    <span>View Portfolio & Journey</span>
                                </button>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-btn-block ftm-trigger-member-activity" data-id="${m.id}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                    <span>Activity Report</span>
                                </button>
                                ${canManageMembers ? `
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm ftm-btn-block ftm-btn-edit-member-account" data-id="${m.id}" style="margin-top:4px;">
                                    <span>⚙️ Edit Member / Role / Quota</span>
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
        });

        container.html(html || '<div class="ftm-empty-state" style="padding:40px 20px; text-align:center;">No team members found.</div>');

        container.find('.ftm-btn-edit-member-account').off('click').on('click', function (e) {
            e.preventDefault();
            const mid = parseInt($(this).data('id'), 10);
            const memberObj = members.find(x => parseInt(x.id, 10) === mid);
            if (memberObj) {
                self.openManageMemberModal(memberObj);
            }
        });
    }).catch(err => {
        const msg = (err && err.message) ? self.escapeHtml(err.message) : 'Network error';
        container.html(`
                    <div class="ftm-empty-state" style="padding:40px 20px; text-align:center;">
                        <p style="color:#f87171; margin-bottom:12px;">Failed to load team members: ${msg}</p>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-retry-team-btn">Retry</button>
                    </div>
                `);
        container.find('.ftm-retry-team-btn').on('click', () => self.loadTeamMembersView(teamFilter));
    });
},

openIsolationVaultModal() {
    const self = this;
    const modal = $('#ftm-modal-isolation-vault');
    if (!modal.length) return;

    $('#ftm-vault-projects-table-body').html('<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--ftm-text-muted);"><div class="ftm-spinner"></div> Loading vault ledger...</td></tr>');
    $('#ftm-vault-members-list').html('<div class="ftm-spinner" style="margin:8px 0;"></div>');
    modal.removeClass('hidden');

    Promise.all([
        self.api('isolation/summary'),
        self.api('isolation/projects')
    ]).then(([summaryRes, projectsRes]) => {
        const teams = summaryRes.isolated_teams || [];
        const firstTeam = teams.length ? teams[0] : null;

        $('#ftm-isolation-vault-badge').text(summaryRes.total_isolated_projects || 0);
        $('#ftm-vault-total-projects').text(summaryRes.total_isolated_projects || 0);
        $('#ftm-vault-total-members').text(summaryRes.total_isolated_members || 0);
        $('#ftm-vault-total-value').text('$' + (summaryRes.total_isolated_value ? summaryRes.total_isolated_value.toLocaleString('en-US', { minimumFractionDigits: 2 }) : '0.00'));

        if (firstTeam) {
            $('#ftm-vault-team-name').text(firstTeam.team_name);
            $('#ftm-vault-project-status-sub').text(`${firstTeam.wip_count || 0} WIP · ${firstTeam.completed_count || 0} Completed`);
            $('#ftm-vault-wip-value-sub').text('WIP: $' + (firstTeam.wip_value ? firstTeam.wip_value.toLocaleString('en-US', { minimumFractionDigits: 2 }) : '0.00'));

            let memHtml = '';
            (firstTeam.members || []).forEach(m => {
                memHtml += `
                            <div style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3); border-radius:18px; font-size:12px;">
                                <span style="font-weight:700; color:#fbbf24;">${self.escapeHtml(m.name)}</span>
                                <span style="font-size:11px; color:var(--ftm-text-muted);">(${self.escapeHtml(m.role)})</span>
                                <span class="ftm-badge ftm-badge-xs ftm-badge-amber">Isolated</span>
                            </div>
                        `;
            });
            $('#ftm-vault-members-list').html(memHtml || '<span class="ftm-text-muted ftm-text-xs">No members assigned</span>');
        }

        const projects = (projectsRes && projectsRes.projects) ? projectsRes.projects : [];
        self._cachedVaultProjects = projects;
        self.renderVaultProjectsTable(projects);

    }).catch(err => {
        console.error(err);
        $('#ftm-vault-projects-table-body').html(`<tr><td colspan="6" style="text-align:center; padding:24px; color:#f87171;">Failed to load vault records: ${self.escapeHtml(err.message || 'Error')}</td></tr>`);
    });
},

renderVaultProjectsTable(projects) {
    const self = this;
    const tbody = $('#ftm-vault-projects-table-body');
    if (!projects.length) {
        tbody.html('<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--ftm-text-muted);">No records found matching criteria in isolation vault.</td></tr>');
        return;
    }

    let html = '';
    projects.forEach(p => {
        const statusChip = p.status === 'wip'
            ? '<span class="ftm-status-chip ftm-chip-amber" style="background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.3); padding:2px 8px; border-radius:12px; font-size:11px;">WIP</span>'
            : '<span class="ftm-status-chip ftm-chip-green" style="background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); padding:2px 8px; border-radius:12px; font-size:11px;">' + self.escapeHtml(p.status) + '</span>';

        const netVal = p.net_delivery_value ? '$' + parseFloat(p.net_delivery_value).toFixed(2) : '$0.00';
        const assigneeName = p.assigned_user_name || (p.assigned_user_id ? ('User #' + p.assigned_user_id) : 'Unassigned');

        html += `
                    <tr style="border-bottom:1px solid var(--ftm-border);">
                        <td style="padding:8px 12px; font-weight:600; font-family:monospace; color:var(--ftm-text-main);">${self.escapeHtml(p.order_id || '')}</td>
                        <td style="padding:8px 12px; color:var(--ftm-text-secondary);">${self.escapeHtml(p.client_name || '-')}</td>
                        <td style="padding:8px 12px; color:var(--ftm-text-main);">${self.escapeHtml(assigneeName)}</td>
                        <td style="padding:8px 12px; text-align:right; font-weight:700; color:#10b981;">${netVal}</td>
                        <td style="padding:8px 12px; text-align:center;">${statusChip}</td>
                        <td style="padding:8px 12px; color:var(--ftm-text-muted); font-size:11.5px;">${self.escapeHtml(p.delivery_date || '-')}</td>
                    </tr>
                `;
    });
    tbody.html(html);
},

filterVaultProjects(term) {
    const self = this;
    const all = self._cachedVaultProjects || [];
    if (!term) {
        self.renderVaultProjectsTable(all);
        return;
    }
    const filtered = all.filter(p => {
        return (p.order_id && p.order_id.toLowerCase().includes(term)) ||
            (p.client_name && p.client_name.toLowerCase().includes(term)) ||
            (p.assigned_user_name && p.assigned_user_name.toLowerCase().includes(term)) ||
            (p.project_notes && p.project_notes.toLowerCase().includes(term));
    });
    self.renderVaultProjectsTable(filtered);
},

restoreIsolatedTeam(teamName = 'WpHorizon') {
    const self = this;
    if (!confirm(`Are you sure you want to restore ${teamName} from the Isolation Vault?\n\nAll 24 projects and 4 team members will be returned to active live operations.`)) {
        return;
    }

    const restoreBtn = $('#ftm-btn-restore-wphorizon');
    const origHtml = restoreBtn.html();
    restoreBtn.prop('disabled', true).html('<div class="ftm-spinner ftm-spinner-xs"></div> Restoring...');

    self.api('isolation/restore', 'POST', { team_name: teamName }).then(res => {
        restoreBtn.prop('disabled', false).html(origHtml);
        self.toast(res.message || `${teamName} restored successfully!`, 'success');
        $('#ftm-modal-isolation-vault').addClass('hidden');

        // Reload all core states
        self.loadStats();
        self.loadProjects();
        self.loadTeamMembers();
        if ($('#ftm-view-team-members').hasClass('active')) {
            self.loadTeamMembersView();
        }
    }).catch(err => {
        restoreBtn.prop('disabled', false).html(origHtml);
        self.toast('Error restoring team: ' + (err.message || 'Unknown'), 'error');
    });
},

openManageMemberModal(member = null) {
    const modal = $('#ftm-modal-manage-member');
    $('#ftm-form-manage-member')[0].reset();
    const isEdit = !!(member && member.id);

    $('#ftm-member-edit-id').val(isEdit ? member.id : '');
    $('#ftm-modal-member-title').text(isEdit ? ('Edit Member: ' + (member.name || 'Specialist')) : 'Add New Team Member');
    $('#ftm-btn-save-member').text(isEdit ? 'Save Member Changes' : 'Create Member');
    $('#ftm-member-password-label').text(isEdit ? 'Reset Password (optional, leave blank to keep)' : 'Password * (min 6 chars)');
    $('#ftm-member-password').prop('required', !isEdit);

    if (isEdit) {
        $('#ftm-member-name').val(member.name || '');
        $('#ftm-member-email').val(member.email || '');
        $('#ftm-member-team').val(member.team || 'WpCraft');
        $('#ftm-member-role').val(member.team_role || 'member');
        $('#ftm-member-title').val(member.professional_title || '');
        $('#ftm-member-custom-id').val(member.custom_id || '');
        $('#ftm-member-day-off').val((member.weekly_day_off || 'friday').toLowerCase());
        $('#ftm-member-status').val(member.status && member.status !== 'active' ? 'inactive' : 'active');
        $('#ftm-member-quota').val(member.annual_quota !== undefined ? member.annual_quota : 18);
        $('#ftm-member-leave-adj').val(member.leave_adjustment !== undefined ? member.leave_adjustment : 0);
    } else {
        $('#ftm-member-team').val('WpCraft');
        $('#ftm-member-role').val('member');
        $('#ftm-member-day-off').val('friday');
        $('#ftm-member-status').val('active');
        $('#ftm-member-quota').val(18);
        $('#ftm-member-leave-adj').val(0);
    }

    modal.removeClass('hidden');
},

submitManageMemberForm() {
    const self = this;
    const editId = $('#ftm-member-edit-id').val();
    const password = $('#ftm-member-password').val();
    const payload = {
        display_name: $('#ftm-member-name').val().trim(),
        email: $('#ftm-member-email').val().trim(),
        team: $('#ftm-member-team').val(),
        team_role: $('#ftm-member-role').val(),
        professional_title: $('#ftm-member-title').val().trim(),
        custom_id: $('#ftm-member-custom-id').val().trim(),
        weekly_day_off: $('#ftm-member-day-off').val(),
        status: $('#ftm-member-status').val(),
        annual_quota: parseFloat($('#ftm-member-quota').val() || '18'),
        leave_adjustment: parseFloat($('#ftm-member-leave-adj').val() || '0'),
    };

    if (!editId) {
        if (!password || password.length < 6) {
            self.toast('Password must be at least 6 characters long.', 'error');
            return;
        }
        payload.password = password;
    } else if (password && password.length > 0) {
        payload.new_password = password;
    }

    const endpoint = editId ? ('team-members/' + editId) : 'team-members';
    self.api(endpoint, 'POST', payload).then(res => {
        self.toast(res.message || 'Member saved successfully!', 'success');
        $('#ftm-modal-manage-member').addClass('hidden');
        self.state.leaveMembersCache = null;
        self.loadTeamMembersView($('#ftm-members-team-filter').val() || 'all');
        if ($('#ftm-view-attendance').hasClass('active')) {
            self.loadAttendanceView();
            self.loadLeaveManagementReportView();
        }
    }).catch(err => {
        self.toast('Error saving member: ' + (err.message || 'Unknown'), 'error');
    });
},







openMemberActivityModal(memberId, period = 'current_month') {



    const self = this;



    this.state.activeMemberActivityId = memberId;



    this.state.activeMemberActivityPeriod = period;







    let normalizedPeriod = period;
    const currentYm = new Date().toISOString().slice(0, 7);
    const lastYm = new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1).toISOString().slice(0, 7);
    if (normalizedPeriod === currentYm) {
        normalizedPeriod = 'current_month';
    } else if (normalizedPeriod === lastYm) {
        normalizedPeriod = 'last_month';
    }

    $('.ftm-period-btn').removeClass('active');
    let matchedBtn = $(`.ftm-period-btn[data-period="${normalizedPeriod}"]`);
    if (!matchedBtn.length) {
        matchedBtn = $(`.ftm-period-btn[data-period="${period}"]`);
    }
    if (matchedBtn.length) {
        matchedBtn.addClass('active');
    } else {
        $('.ftm-period-btn[data-period="current_month"]').addClass('active');
    }

    $('#ftm-modal-member-activity').removeClass('hidden');

    // Reset placeholders while loading
    $('#ftm-activity-member-name').text('Loading member data...');
    $('#ftm-activity-member-custom-id').addClass('hidden');
    $('#ftm-activity-member-badge').text('Loading...');
    $('#ftm-activity-member-email').text('');
    $('#ftm-activity-projects-tbody').html('<tr><td colspan="8" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading activity report...</td></tr>');

    this.api(`team-members/${memberId}/activity?period=${period}`)
        .then(res => {
            const m = res.member || {};
            const met = res.metrics || res.stats || {};
            const arc = res.archive;

            $('#ftm-activity-member-avatar').attr('src', m.avatar || '');
            $('#ftm-activity-member-name').text(m.name || 'Team Member');
            if (m.custom_id) {
                $('#ftm-activity-member-custom-id').text('#' + m.custom_id).removeClass('hidden');
            } else {
                $('#ftm-activity-member-custom-id').addClass('hidden');
            }
            $('#ftm-activity-member-badge').text(`${m.team || ''} • ${m.role_label || 'Member'}`);
            $('#ftm-activity-member-email').text(m.email || '');

            $('#ftm-activity-period-label').text(res.period_label || 'All Time');

            const compRate = met.completion_rate !== undefined ? met.completion_rate : 100;
            $('#ftm-activity-completion-badge').text(`${compRate}% Completion Rate`);

            const delCount = (met.accepted !== undefined && met.accepted !== null) ? met.accepted : ((arc ? arc.completed : 0) || 0);
            const delVal = (met.delivered_value !== undefined && met.delivered_value !== null) ? parseFloat(met.delivered_value) : (arc ? parseFloat(arc.delivered_value) : 0);
            const delMoney = '$' + delVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#ftm-act-accepted').text(delVal > 0 ? delMoney : '$0.00');
            $('#ftm-act-accepted-sub').text(`${delCount} Accepted Orders`);

            const totCount = met.total || 0;
            const totVal = (met.total_assigned_value !== undefined && met.total_assigned_value !== null) ? parseFloat(met.total_assigned_value) : delVal;
            const totMoney = '$' + totVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#ftm-act-total').text(totVal > 0 ? totMoney : '$0.00');
            $('#ftm-act-total-sub').text(`${totCount} Total Items`);

            const noteBox = $('#ftm-activity-archive-note');
            if (arc && arc.months > 0) {
                const money = v => '$' + (parseFloat(v) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                noteBox.removeClass('hidden').html(
                    '<strong>' + ftmEsc(arc.label) + '</strong>: '
                    + '<span><b>' + money(arc.delivered_value) + '</b> delivered</span> &middot; '
                    + '<span><b>' + (arc.completed || 0) + '</b> orders</span> &middot; '
                    + '<span><b>' + money(arc.cancelled_value) + '</b> cancelled (' + (arc.cancel_count || 0) + ')</span>'
                    + (parseFloat(arc.tips) > 0 ? ' &middot; <span><b>' + money(arc.tips) + '</b> tips</span>' : ''));
            } else {
                noteBox.addClass('hidden').empty();
            }

            $('#ftm-act-active').text(met.active || 0);
            $('#ftm-act-submitted').text(met.submitted || 0);
            $('#ftm-act-revision').text(met.revision || 0);







            const totalReviews = met.total_reviews || 0;
            const avgRating = totalReviews > 0 ? (met.avg_rating || met.average_rating || '5.0') : '0.0';
            const fiveStars = met.five_stars || met.five_star_reviews || 0;

            $('#ftm-act-rating').text(`${avgRating} ★`);
            const reviewsLabel = totalReviews > 0
                ? (fiveStars === totalReviews ? `${totalReviews} ${totalReviews === 1 ? 'Review' : 'Reviews'}` : `${totalReviews} Reviews (${fiveStars} 5★)`)
                : 'No Reviews';
            $('#ftm-act-reviews-count').text(reviewsLabel);
            $('#ftm-act-cancelled').text(met.cancelled || 0);

            $('#ftm-act-extensions').text(`${met.extension_count || 0} (${met.extension_days || 0}d)`);
            $('#ftm-act-ontime').text(`${met.on_time_count || 0}`);
            $('#ftm-act-late').text(`${met.late_count || 0}`);
            const tipVal = parseFloat(met.tip_amount) || 0;
            $('#ftm-act-tips').text(`$${tipVal.toFixed(2)} (${met.tip_count || 0})`);







            const projects = res.projects || [];



            $('#ftm-activity-projects-count').text(`${projects.length} projects found`);







            const tbody = $('#ftm-activity-projects-tbody');



            const mobContainer = $('#ftm-activity-projects-mobile-cards');



            if (projects.length === 0) {



                const emptyHtml = `



                            <div class="ftm-act-empty-box">



                                <div class="ftm-act-empty-icon">



                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>



                                </div>



                                <div class="ftm-act-empty-title">${res.archive ? 'No individual orders kept' : 'No Projects in this Period'}</div>



                                <div class="ftm-act-empty-desc">${res.archive ? 'This period is kept as a monthly total (see the summary above).' : 'No active or completed deliverables were recorded for this timeframe.'}</div>



                            </div>



                        `;



                tbody.html(`



                            <tr>



                                <td colspan="9" style="padding:24px 12px; border:none;">



                                    ${emptyHtml}



                                </td>



                            </tr>



                        `);



                if (mobContainer.length) {



                    mobContainer.html(emptyHtml);



                }



                return;



            }







            let rowsHtml = '';



            let mobileHtml = '';



            projects.forEach((p, idx) => {



                let ratingDisplay = '<span class="ftm-text-muted ftm-text-xs">&mdash;</span>';



                let ratingBadge = '';



                if (p.status === 'delivery_accepted' || p.status === 'completed') {
                    if (p.client_reviewed === 'yes' && p.star_rating > 0) {
                        const feedbackTitle = p.review_feedback ? ` title="${ftmEsc(p.review_feedback)}"` : '';
                        ratingDisplay = `<span class="ftm-star-rating-pill" style="margin-top:0;"${feedbackTitle}>⭐ ${ftmStar(p.star_rating)}${p.review_feedback ? ' 💬' : ''}</span>`;
                        ratingBadge = ratingDisplay;
                    } else if (p.client_reviewed === 'no') {
                        ratingDisplay = `<span class="ftm-no-review-pill" style="margin-top:0;">No review</span>`;
                        ratingBadge = ratingDisplay;
                    }
                }







                const rawOrderId = (p.order_id || '').trim().replace(/^#+/, '');



                const formattedOrderId = rawOrderId ? '#' + rawOrderId : '';







                rowsHtml += `



                            <tr>



                                <td class="ftm-text-muted">${idx + 1}</td>



                                <td><span class="ftm-badge ftm-badge-outline ftm-text-xs">${formattedOrderId || '&mdash;'}</span></td>



                                <td>${p.profile_name || 'Profile'}</td>



                                <td class="ftm-font-medium">${ftmEsc(p.client_name)}</td>



                                <td class="ftm-nowrap"><strong class="text-emerald">$${(parseFloat(p.net_delivery_value) || 0).toFixed(2)}</strong></td>



                                <td>${p.delivery_date_formatted || p.delivery_date}</td>



                                <td>
                                    <span class="ftm-badge badge-status-${p.status} ftm-badge-sm">${p.status_label}</span>
                                    ${(p.status === 'delivery_accepted' || p.status === 'completed') ? (
                        (parseInt(p.is_ontime) === 1 || (parseInt(p.extension_count || 0) === 0 && parseInt(p.extension_days || 0) === 0 && p.is_ontime !== 0))
                            ? `<div style="margin-top:3px;"><span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">⚡ On-Time</span></div>`
                            : `<div style="margin-top:3px;"><span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs">⚠️ Late / Extended</span></div>`
                    ) : ''}
                                    ${p.extension_count > 0 ? `<div style="margin-top:3px;"><span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">⏱️ ${p.extension_count}x (+${p.extension_days}d)</span></div>` : ''}
                                    ${parseFloat(p.tip_amount) > 0 ? `<div style="margin-top:3px;"><span class="ftm-tip-chip">+$${parseFloat(p.tip_amount).toFixed(2)} tip</span></div>` : ''}
                                </td>



                                <td>${ratingDisplay}</td>



                                <td class="ftm-text-right">



                                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-project-btn" data-id="${p.id}" title="View Details">



                                        View



                                    </button>



                                </td>



                            </tr>



                        `;







                mobileHtml += `



                            <div class="ftm-activity-project-card">



                                <div class="ftm-ap-card-header">



                                    <div class="ftm-ap-card-order-row">



                                        <span class="ftm-badge ftm-badge-outline ftm-text-xs">${formattedOrderId || '&mdash;'}</span>



                                        <span class="ftm-badge badge-status-${p.status} ftm-badge-sm">${p.status_label}</span>



                                    </div>



                                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-project-btn" data-id="${p.id}" title="View Details">



                                        View



                                    </button>



                                </div>



                                <div class="ftm-ap-card-body">



                                    <div class="ftm-ap-card-client">${ftmEsc(p.client_name)}</div>



                                    <div class="ftm-ap-card-meta">



                                        <span>${p.profile_name || 'Profile'}</span>



                                        ${ratingBadge ? ` &bull; ${ratingBadge}` : ''}



                                    </div>



                                </div>



                                <div class="ftm-ap-card-footer">



                                    <div class="ftm-ap-card-deadline">



                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                                        <span>Deadline: ${p.delivery_date_formatted || p.delivery_date || 'N/A'}</span>



                                    </div>



                                </div>



                            </div>



                        `;



            });



            tbody.html(rowsHtml);



            if (mobContainer.length) {



                mobContainer.html(mobileHtml);



            }



        })



        .catch(err => {



            $('#ftm-activity-projects-tbody').html(`<tr><td colspan="8" class="ftm-text-danger" style="text-align:center; padding:20px;">Failed to load activity data: ${err.message || 'Error'}</td></tr>`);



            if ($('#ftm-activity-projects-mobile-cards').length) {



                $('#ftm-activity-projects-mobile-cards').html(`<div class="ftm-text-danger" style="text-align:center; padding:20px;">Failed to load activity data: ${err.message || 'Error'}</div>`);



            }



            self.toast(err.message || 'Error loading member activity', 'error');



        });



},







filterByMember(memberId) {



    this.state.memberId = memberId;



    $('#ftm-filter-member').val(memberId);



    this.switchView('dashboard');



},







// Fiverr Profiles View



loadFiverrProfilesView() {



    const container = $('#ftm-profiles-container');



    container.html('<div class="ftm-spinner"></div>');







    const self = this;



    this.api('profiles').then(profiles => {



        self.state.fiverrProfiles = profiles || [];



        self.renderFiverrProfiles(self.state.fiverrProfiles);







        // Setup real-time search filter



        $('#ftm-profiles-search-input').off('input').on('input', function () {



            const query = $(this).val().toLowerCase().trim();



            if (!query) {



                self.renderFiverrProfiles(self.state.fiverrProfiles);



                return;



            }



            const filtered = self.state.fiverrProfiles.filter(p => {



                const name = (p.name || '').toLowerCase();



                const url = (p.url || '').toLowerCase();



                const status = (p.status || '').toLowerCase();



                return name.includes(query) || url.includes(query) || status.includes(query);



            });



            self.renderFiverrProfiles(filtered);



        });



    }).catch(err => {



        container.html(`<div style="grid-column:1/-1; padding:30px; text-align:center; color:var(--ftm-cancelled);">Failed to load profiles: ${err.message || 'Error'}</div>`);



    });



},







renderFiverrProfiles(profiles) {



    const container = $('#ftm-profiles-container');



    const canManageProfiles = this.state.isAdmin || this.state.isLeader;







    // Update badge count



    const activeCount = (profiles || []).filter(p => p.status === 'active').length;



    const totalCount = (profiles || []).length;



    $('#ftm-profiles-count-badge').text(`${activeCount} Active / ${totalCount} Total`);







    if (!profiles || profiles.length === 0) {



        container.html(`



                    <div style="grid-column: 1 / -1; text-align: center; padding: 48px 20px; color: var(--ftm-text-muted);">



                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px; display:block; opacity: 0.6;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>



                        <div style="font-weight: 700; font-size: 15px; color: var(--ftm-text-primary);">No Profiles Found</div>



                        <div style="font-size: 13px; margin-top: 4px;">Try refining your search filter or add a new seller profile.</div>



                    </div>



                `);



        return;



    }







    let html = '';



    profiles.forEach(p => {



        const safeUrl = p.url ? p.url.replace(/"/g, '&quot;') : '';



        const safeName = p.name ? p.name.replace(/"/g, '&quot;') : '';



        const isActive = p.status === 'active';







        let editBtn = '';



        if (canManageProfiles) {



            editBtn = `



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-edit-profile-btn" data-id="${p.id}" data-name="${safeName}" data-url="${safeUrl}" data-status="${p.status}" title="Edit profile settings">



                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>



                            <span>Edit</span>



                        </button>



                    `;



        }







        let urlDisplay = '';



        // profile link intentionally not shown (marketplace is masked)







        html += `



                    <div class="ftm-profile-card">



                        <div>



                            <div class="ftm-profile-card-top">



                                <div class="ftm-profile-identity">



                                    <div class="ftm-profile-fiverr-avatar" style="${ftmTint(p.name)}">${ftmEsc(ftmInitials(p.name))}</div>



                                    <div>



                                        <div class="ftm-profile-name-row">



                                            <h4 class="ftm-profile-name">${ftmEsc(p.name)}</h4>



                                            <span class="ftm-verified-check" title="Verified Agency Seller Profile">



                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="#2563eb"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>



                                            </span>



                                        </div>



                                        ${urlDisplay}



                                    </div>



                                </div>



                                <span class="ftm-profile-status-pill ${isActive ? 'active' : 'inactive'}">



                                    <span class="ftm-status-dot-pulse"></span>



                                    <span>${p.status}</span>



                                </span>



                            </div>







                            <div class="ftm-profile-metrics-strip">



                                <div>



                                    <div class="ftm-metric-col-val">${p.total_projects || 0}</div>



                                    <div class="ftm-metric-col-lbl">Orders</div>



                                </div>



                                <div>



                                    <div class="ftm-metric-col-val text-amber">${p.active_projects || 0}</div>



                                    <div class="ftm-metric-col-lbl">In Progress</div>



                                </div>



                                <div>



                                    <div class="ftm-metric-col-val text-blue">${p.completed_projects || 0}</div>



                                    <div class="ftm-metric-col-lbl">Completed</div>



                                </div>



                            </div>



                        </div>







                        <div class="ftm-profile-footer-actions">



                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" style="flex:1;" onclick="App.filterByProfile(${p.id})">



                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                <span>View Projects (${p.total_projects || 0})</span>



                            </button>



                            ${editBtn}



                        </div>



                    </div>



                `;



    });



    container.html(html);



},







filterByProfile(profileId) {



    this.state.profileId = profileId;



    $('#ftm-filter-profile').val(profileId);



    this.switchView('dashboard');



},







openAddProfileModal() {



    if (!this.state.isAdmin && !this.state.isLeader) {



        this.toast('Only Super Admin, Team Leaders, and Co-Leaders can add profiles.', 'error');



        return;



    }



    $('#ftm-modal-profile-title').text('Add New Profile');



    $('#ftm-profile-form')[0].reset();



    $('#ftm-form-profile-id').val('');



    $('#ftm-save-profile-btn').text('Save Profile');



    $('#ftm-delete-profile-btn').addClass('hidden');



    $('#ftm-modal-profile').removeClass('hidden');



},







openEditProfileModal(id, name, url, status) {



    if (!this.state.isAdmin && !this.state.isLeader) {



        this.toast('Only Super Admin, Team Leaders, and Co-Leaders can edit profiles.', 'error');



        return;



    }



    $('#ftm-modal-profile-title').text('Edit Profile: ' + name);



    $('#ftm-form-profile-id').val(id);



    $('#ftm-profile-name-input').val(name);



    $('#ftm-profile-url-input').val(url || '');



    $('#ftm-profile-status-input').val(status || 'active');



    $('#ftm-save-profile-btn').text('Update Profile');



    $('#ftm-delete-profile-btn').removeClass('hidden');



    $('#ftm-modal-profile').removeClass('hidden');



},







saveProfileForm() {



    if (!this.state.isAdmin && !this.state.isLeader) {



        this.toast('Only Super Admin, Team Leaders, and Co-Leaders can save profiles.', 'error');



        return;



    }



    const id = $('#ftm-form-profile-id').val();



    const data = {



        id: id ? parseInt(id) : 0,



        name: $('#ftm-profile-name-input').val(),



        url: $('#ftm-profile-url-input').val(),



        status: $('#ftm-profile-status-input').val()



    };







    const submitBtn = $('#ftm-save-profile-btn');



    const origText = submitBtn.text();



    submitBtn.prop('disabled', true).text('Saving...');







    this.api('profiles', 'POST', data).then((res) => {



        submitBtn.prop('disabled', false).text(origText);



        $('#ftm-modal-profile').addClass('hidden');



        this.toast(res.message || 'Profile saved successfully!', 'success');



        this.loadProfiles();



        this.loadFiverrProfilesView();



    }).catch(err => {



        submitBtn.prop('disabled', false).text(origText);



        this.toast(err.message || 'Error saving profile', 'error');



    });



},







deleteProfile(id) {



    if (!this.state.isAdmin && !this.state.isLeader) {



        this.toast('Only Super Admin, Team Leaders, and Co-Leaders can delete profiles.', 'error');



        return;



    }



    this.api('profiles/' + id, 'DELETE').then((res) => {



        $('#ftm-modal-profile').addClass('hidden');



        this.toast(res.message || 'Profile deleted successfully.', 'success');



        this.loadProfiles();



        this.loadFiverrProfilesView();



    }).catch(err => {



        this.toast(err.message || 'Error deleting profile', 'error');



    });



},







// My Projects View



loadMyProjectsView() {



    const self = this;



    const scrollSnap = this.captureScrollState();
    const tbody = $('#ftm-my-projects-tbody');
    const mobListEl = $('#ftm-mobile-my-projects-list');

    if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {
        tbody.html(`<tr><td colspan="10" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading your projects...</td></tr>`);
    } else {
        tbody.css('opacity', '0.65');
    }

    if (!mobListEl.children().length) {
        mobListEl.html(`<div style="text-align:center; padding:30px;"><div class="ftm-spinner"></div> Loading your projects...</div>`);
    } else {
        mobListEl.css('opacity', '0.65');
    }







    const myMonth = (self.state.month && self.state.month !== 'all') ? `&month=${encodeURIComponent(self.state.month)}` : '';
    this.api(`projects?view_filter=my&per_page=100${myMonth}`).then(res => {
        tbody.css('opacity', '1');
        mobListEl.css('opacity', '1');



        const items = res.items || [];



        self.state.myAssignedProjects = items;







        // Compute counts for filter pills (Reference Image 4 Screen 4)



        let countActive = 0;



        let countCompleted = 0;



        items.forEach(p => {



            if (p.status === 'delivery_accepted' || p.status === 'delivery_submitted') {



                countCompleted++;



            } else if (p.status !== 'cancelled') {



                countActive++;



            }



        });







        $('#ftm-my-count-active').text(countActive);



        $('#ftm-my-count-completed').text(countCompleted);



        $('#ftm-my-count-all').text(items.length);







        const activeFilter = $('#ftm-my-work-filter-pills .ftm-pill-btn.active').data('filter') || 'active';



        self.renderMyAssignedProjectsList(activeFilter);
        self.restoreScrollState(scrollSnap);



    }).catch(err => {
        tbody.css('opacity', '1');
        mobListEl.css('opacity', '1');
        const msg = (err && err.message) ? self.escapeHtml(err.message) : 'Network error';
        tbody.html(`<tr><td colspan="10" style="text-align:center; padding:32px; color:#f87171;">Failed to load your projects: ${msg} <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-retry-my-projects-btn" style="margin-left:10px;">Retry</button></td></tr>`);
        const mobList = $('#ftm-mobile-my-projects-list');
        mobList.html(`<div style="text-align:center; padding:24px; color:#f87171;"><p style="margin-bottom:10px;">Failed to load your projects: ${msg}</p><button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-retry-my-projects-btn">Retry</button></div>`);
        tbody.add(mobList).find('.ftm-retry-my-projects-btn').on('click', () => self.loadMyProjectsView());
    });



},







renderMyAssignedProjectsList(filter = 'active') {



    const self = this;



    const allItems = this.state.myAssignedProjects || [];



    let items = allItems;







    if (filter === 'active') {



        items = allItems.filter(p => p.status !== 'delivery_accepted' && p.status !== 'delivery_submitted' && p.status !== 'cancelled');



    } else if (filter === 'completed') {



        items = allItems.filter(p => p.status === 'delivery_accepted' || p.status === 'delivery_submitted');



    }







    const tbody = $('#ftm-my-projects-tbody');



    const mobList = $('#ftm-mobile-my-projects-list');







    if (!items || items.length === 0) {
        const emptyMsg = filter === 'active'
            ? 'You have no active projects currently in progress.'
            : (filter === 'completed' ? 'You have no completed deliveries yet.' : 'You currently have no assigned projects.');
        tbody.html(`<tr><td colspan="9" style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">${emptyMsg}</td></tr>`);
        mobList.html(`
                    <div style="text-align:center; padding:32px 16px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:12px; color:var(--ftm-text-muted);">
                        ${emptyMsg}
                    </div>
                `);
        return;
    }

    let html = '';
    let mobHtml = '';

    items.forEach((p, idx) => {
        let ratingPill = '';
        if (p.status === 'delivery_accepted') {
            if (p.client_reviewed === 'yes' && p.star_rating > 0) {
                const starsSymbol = '★'.repeat(Math.round(parseFloat(p.star_rating) || 0));
                ratingPill = `<div class="ftm-star-rating-pill" title="${p.review_feedback || 'Client Rating: ' + p.star_rating + ' Stars'}">⭐ ${ftmStar(p.star_rating)} (${starsSymbol})</div>`;
            } else if (p.order_type === 'subscription' || p.order_type === 'outside') {
                ratingPill = `<div class="ftm-order-type-pill ftm-ot-${p.order_type}">${p.order_type === 'subscription' ? 'Subscription' : 'Outside order'}</div>`;
            } else if (p.client_reviewed === 'no') {
                ratingPill = `<div class="ftm-no-review-pill">No Review Left</div>`;
            }
        }

        const deliveryBadge = self.renderDeliveryBadge(p);
        const carryBadge = p.is_carry_forward
            ? '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:4px;">Carry</span>'
            : '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:4px;">New</span>';

        let trackerBtn = '';
        if (self.state.timeTracker && self.state.timeTracker.active && self.state.timeTracker.projectId === p.id) {
            trackerBtn = `
                        <button type="button" class="ftm-btn ftm-btn-sm ftm-stop-tracker-btn" data-id="${p.id}" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); font-weight:700;" title="Timer running. Click to Stop & Log">
                            ⏹ <span class="ftm-hide-mobile">Tracking...</span>
                        </button>
                    `;
        } else {
            trackerBtn = `
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-start-tracker-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" data-profile="${ftmEsc(p.profile_name)}" style="color:#10b981; border-color:rgba(16,185,129,0.35);" title="Start Live Work Timer & 10m Screenshot">
                            ⏱ <span class="ftm-hide-mobile">Timer</span>
                        </button>
                    `;
        }

        const galleryBtn = (self.state.isAdmin || self.state.isLeader || self.state.isDeputy) ? `
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-view-tracker-snaps-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" title="View Screen Captures & Time Tracking Log">
                        📸
                    </button>
                ` : '';

        const isActive = !['delivery_accepted', 'completed', 'delivered', 'cancelled'].includes(p.status);
        const daysRem = p.days_remaining || {};
        let rowUrgencyClass = '';
        let cellUrgencyStyle = '';
        let firstCellStyle = '';
        if (isActive) {
            if (daysRem.urgency === 'danger' || (daysRem.days !== undefined && daysRem.days < 0)) {
                rowUrgencyClass = 'ftm-row-late';
                cellUrgencyStyle = 'background-color: rgba(239, 68, 68, 0.18) !important; border-top: 1px solid rgba(239, 68, 68, 0.45) !important; border-bottom: 1px solid rgba(239, 68, 68, 0.45) !important;';
                firstCellStyle = cellUrgencyStyle + ' border-left: 5px solid #ef4444 !important;';
            } else if (daysRem.urgency === 'warning' || (daysRem.days !== undefined && daysRem.days <= 2)) {
                rowUrgencyClass = 'ftm-row-short-deadline';
                cellUrgencyStyle = 'background-color: rgba(245, 158, 11, 0.14) !important; border-top: 1px solid rgba(245, 158, 11, 0.40) !important; border-bottom: 1px solid rgba(245, 158, 11, 0.40) !important;';
                firstCellStyle = cellUrgencyStyle + ' border-left: 5px solid #f59e0b !important;';
            }
        }

        const canManageProjects = !!(self.state.isAdmin || self.state.isLeader) && !self.state.isDeputy;
        html += `
                    <tr data-id="${p.id}" class="${rowUrgencyClass}" style="${cellUrgencyStyle}">
                        <td class="ftm-text-muted" style="${firstCellStyle}">${idx + 1}</td>
                        <td style="${cellUrgencyStyle}"><strong>${ftmEsc(p.profile_name)}</strong></td>
                        <td class="ftm-font-medium ftm-cell-client-order" style="${cellUrgencyStyle}">
                            <div class="ftm-client-name" style="font-weight:600; font-size:13px; color:var(--ftm-text-primary); line-height:1.3;">${ftmEsc(p.client_name)}</div>
                            <div style="margin-top:3px;"><span class="ftm-badge ftm-badge-outline ftm-text-xs ftm-order-id-badge" style="font-size:10.5px; font-weight:600; padding:1px 6px; letter-spacing:0.3px;">${ftmEsc(p.order_id)}</span></div>
                            ${ratingPill ? `<div style="margin-top:3px;">${ratingPill}</div>` : ''}
                        </td>
                        <td style="${cellUrgencyStyle}">${carryBadge}</td>
                        <td class="ftm-nowrap" style="color:var(--ftm-text-secondary); font-size:12px; ${cellUrgencyStyle}">${p.assign_date_formatted || p.created_at_formatted || 'N/A'}</td>
                        <td style="${cellUrgencyStyle}">${p.delivery_date_formatted}</td>
                        <td style="${cellUrgencyStyle}">
                            ${deliveryBadge}
                        </td>
                        <td style="${cellUrgencyStyle}">
                            <select class="ftm-select ftm-select-sm ftm-table-status-changer badge-status-${p.status}" data-id="${p.id}">
                                <option value="nra" ${p.status === 'nra' ? 'selected' : ''}>NRA</option>
                                <option value="wip" ${p.status === 'wip' ? 'selected' : ''}>WIP</option>
                                <option value="delivery_submitted" ${p.status === 'delivery_submitted' ? 'selected' : ''}>Delivery Submitted</option>
                                <option value="revision" ${p.status === 'revision' ? 'selected' : ''}>Revision</option>
                                <option value="delivery_accepted" ${p.status === 'delivery_accepted' ? 'selected' : ''}>Delivery Accepted</option>
                                <option value="cancelled" ${p.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </td>
                        <td class="ftm-text-right" style="${cellUrgencyStyle}">
                            <div class="ftm-action-group">
                                ${trackerBtn}
                                ${galleryBtn}
                                ${(isActive && !self.state.isDeputy) ? `
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-extend-project-btn" data-id="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" data-deadline="${p.delivery_date || ''}" title="Extend project delivery date">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                </button>
                                ` : ''}
                                ${(canManageProjects && p.can_edit) ? `
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-edit-project-btn" data-id="${p.id}" title="Edit Order & Tips/Review">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                ` : ''}
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-view-project-btn" data-id="${p.id}" title="View project details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;







        mobHtml += self.renderMobileProjectCard(p, true);
    });

    tbody.html(html);
    mobList.html(mobHtml);
},

pickNextInterval(sessionType = 'regular') {
    if (sessionType === 'extra_time') {
        // Overtime work (after 6:20 PM): Random between 140s and 260s (2.3 to 4.3 min, ~8-12 times/hour)
        return Math.floor(Math.random() * (260 - 140 + 1)) + 140;
    }
    // Regular shift work: Random between 300s and 660s (5 to 11 min, ~5-7 times/hour)
    return Math.floor(Math.random() * (660 - 300 + 1)) + 300;
},

_formatClockHMS(sec) {
    const total = Math.max(0, parseInt(sec || 0, 10));
    const pad = n => String(n).padStart(2, '0');
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    return `${pad(h)}:${pad(m)}:${pad(s)}`;
},

_setTrackerState(tracker) {
    this.state.timeTracker = tracker;
    this.state.activeTracker = tracker;
},

_attachActivityListeners() {
    if (this._trackerListenersAttached) return;
    this._trackerListenersAttached = true;

    const self = this;
    let lastMouseTime = 0;
    let lastMouseX = -1;
    let lastMouseY = -1;
    let lastKeyTime = 0;
    let storageSaveTimer = null;

    const queueStorageSave = () => {
        if (storageSaveTimer) return;
        storageSaveTimer = setTimeout(() => {
            storageSaveTimer = null;
            const tr = self.state.timeTracker;
            if (tr && tr.active) {
                self._saveTrackerStorage(tr);
            }
        }, 1500);
    };

    const recordMouse = (e) => {
        const tr = self.state.timeTracker;
        if (!tr || !tr.active) return;

        const now = Date.now();
        const x = e ? (e.clientX || 0) : 0;
        const y = e ? (e.clientY || 0) : 0;

        // Deduplicate rapid event succession for a single physical click (pointerdown -> mousedown -> click)
        if (now - lastMouseTime < 40 && Math.abs(x - lastMouseX) < 10 && Math.abs(y - lastMouseY) < 10) {
            return;
        }
        lastMouseTime = now;
        lastMouseX = x;
        lastMouseY = y;

        tr.mouseClicks = (tr.mouseClicks || 0) + 1;
        tr.totalMouseClicks = (tr.totalMouseClicks || 0) + 1;

        // Instant UI reflection on screen
        const mCount = tr.totalMouseClicks;
        const el1 = document.getElementById('ftm-pip-mouse-count');
        if (el1) el1.textContent = mCount;
        const el2 = document.getElementById('ftm-bar-mouse-count');
        if (el2) el2.textContent = mCount;

        queueStorageSave();
    };

    const recordKey = (e) => {
        const tr = self.state.timeTracker;
        if (!tr || !tr.active) return;

        const now = Date.now();
        const eventType = e ? e.type : '';

        // If an 'input' event fires immediately after 'keydown', don't double count that keypress
        if (eventType === 'input' && (now - lastKeyTime < 40)) {
            return;
        }
        lastKeyTime = now;

        tr.keyboardClicks = (tr.keyboardClicks || 0) + 1;
        tr.totalKeyboardClicks = (tr.totalKeyboardClicks || 0) + 1;

        // Instant UI reflection on screen
        const kCount = tr.totalKeyboardClicks;
        const el1 = document.getElementById('ftm-pip-key-count');
        if (el1) el1.textContent = kCount;
        const el2 = document.getElementById('ftm-bar-key-count');
        if (el2) el2.textContent = kCount;

        queueStorageSave();
    };

    // Attach in capture phase on window & document to guarantee every click and keystroke anywhere is captured
    const opts = { capture: true, passive: true };
    window.addEventListener('pointerdown', recordMouse, opts);
    window.addEventListener('mousedown', recordMouse, opts);
    document.addEventListener('pointerdown', recordMouse, opts);
    document.addEventListener('mousedown', recordMouse, opts);
    document.addEventListener('click', recordMouse, opts);

    window.addEventListener('keydown', recordKey, opts);
    document.addEventListener('keydown', recordKey, opts);
    document.addEventListener('input', recordKey, opts);

    window.addEventListener('beforeunload', () => {
        const tr = self.state.timeTracker;
        if (tr && tr.active) {
            self._saveTrackerStorage(tr);
        }
    });
},

_saveTrackerStorage(tr) {
    try {
        if (!tr || !tr.active) {
            localStorage.removeItem('ftm_active_tracker_session');
            return;
        }
        localStorage.setItem('ftm_active_tracker_session', JSON.stringify({
            projectId: tr.projectId,
            orderId: tr.orderId,
            clientName: tr.clientName,
            profileName: tr.profileName,
            sessionId: tr.sessionId,
            sessionType: tr.sessionType || 'regular',
            startTime: tr.startTime,
            lastSnapshotTime: tr.lastSnapshotTime || tr.startTime,
            intervalSeconds: tr.intervalSeconds || 300,
            snapshotCount: tr.snapshotCount || 0,
            totalMouseClicks: tr.totalMouseClicks || 0,
            totalKeyboardClicks: tr.totalKeyboardClicks || 0
        }));
    } catch (e) { }
},

_hasLiveStream(tr) {
    return !!(tr && tr.stream && typeof tr.stream.getVideoTracks === 'function' && tr.stream.getVideoTracks().some(t => t.readyState === 'live'));
},

        async _attachTrackerStream(stream) {
    const self = this;
    let video = document.getElementById('ftm-tracker-stream-video');
    if (!video) {
        video = document.createElement('video');
        video.id = 'ftm-tracker-stream-video';
        video.style.cssText = 'position:fixed; bottom:0; right:0; width:4px; height:4px; opacity:0.001; pointer-events:none; z-index:-999;';
        document.body.appendChild(video);
    }
    video.srcObject = stream;
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;
    if (video.readyState < 1) {
        await new Promise(resolve => {
            const timer = setTimeout(resolve, 2000);
            video.onloadedmetadata = () => { clearTimeout(timer); resolve(); };
        });
    }
    video.play().catch(e => console.warn('Video play warning:', e));

    const videoTrack = stream.getVideoTracks()[0];
    if (videoTrack) {
        videoTrack.addEventListener('ended', () => {
            const cur = self.state.timeTracker;
            if (cur && cur.active) {
                cur.stream = null;
                cur.video = null;
                self.toast('Screen sharing paused. Click "⚡ Connect Screen" to resume screenshot audit.', 'warning');
                self.updatePipWidget(cur);
            }
        });
    }
    return video;
},

_processTrackerTick() {
    const self = this;
    const tr = self.state.timeTracker;
    if (!tr || !tr.active) return;

    const now = Date.now();
    tr.elapsedSeconds = Math.max(0, Math.floor((now - tr.startTime) / 1000));
    const timeStr = self._formatClockHMS(tr.elapsedSeconds);

    $('#ftm-pip-clock, #ftm-tracker-clock').text(timeStr);
    $(`#ftm-tracker-display-${tr.projectId}, .ftm-live-timer-${tr.projectId}`).text(timeStr);
    $('#ftm-pip-mouse-count').text(tr.totalMouseClicks || 0);
    $('#ftm-pip-key-count').text(tr.totalKeyboardClicks || 0);
    $('#ftm-bar-mouse-count').text(tr.totalMouseClicks || 0);
    $('#ftm-bar-key-count').text(tr.totalKeyboardClicks || 0);

    // Heartbeat every 30s based on wall clock (immune to background tab throttling)
    if (tr.sessionId && (now - (tr.lastHeartbeatTime || tr.startTime)) >= 30000) {
        tr.lastHeartbeatTime = now;
        self._saveTrackerStorage(tr);
        self.api('time-tracker/heartbeat', 'POST', {
            session_id: tr.sessionId,
            elapsed_seconds: tr.elapsedSeconds,
            total_seconds: tr.elapsedSeconds,
            total_mouse_clicks: tr.totalMouseClicks || 0,
            total_keyboard_clicks: tr.totalKeyboardClicks || 0
        }).catch(console.warn);
    }

    // Randomized snapshot check using wall clock
    if ((now - (tr.lastSnapshotTime || tr.startTime)) >= ((tr.intervalSeconds || 300) * 1000)) {
        tr.lastSnapshotTime = now;
        tr.intervalSeconds = self.pickNextInterval(tr.sessionType);
        if (self._hasLiveStream(tr)) {
            self.captureAndUploadSnapshot(false);
        }
    }
},

_startTrackerLoop() {
    const self = this;
    const tr = self.state.timeTracker;
    if (!tr || !tr.active) return;
    if (tr.intervalId) {
        clearInterval(tr.intervalId);
    }
    tr.intervalId = setInterval(() => self._processTrackerTick(), 1000);
},

updatePipWidget(tracker) {
    const self = this;
    if (!tracker || !tracker.active) {
        $('#ftm-tracker-pip-container').hide().addClass('hidden');
        $('#ftm-active-tracker-bar').addClass('hidden');
        $('#ftm-btn-tracker-reconnect, #ftm-btn-resume-stream').remove();
        return;
    }

    const isExtra = (tracker.sessionType === 'extra_time');
    const isLeadership = !!(self.state.isAdmin || self.state.isLeader);
    const titleStr = `${tracker.orderId ? (String(tracker.orderId).startsWith('#') ? tracker.orderId : '#' + tracker.orderId) : ('#ID-' + tracker.projectId)} - ${tracker.clientName || 'Client'}`;
    const timeStr = self._formatClockHMS(tracker.elapsedSeconds || 0);

    // 1. Update Persistent Floating PIP Widget (exact dashboard.php IDs)
    const container = $('#ftm-tracker-pip-container');
    container.css('display', 'flex').removeClass('hidden');
    $('#ftm-pip-project-title').text(titleStr);
    $('#ftm-pip-clock').text(timeStr);

    if (isExtra) {
        $('#ftm-pip-mode-badge').text('⚡ EXTRA TIME WORK').css('color', '#f59e0b');
        $('#ftm-pip-pulse-dot').css({ background: '#f59e0b', boxShadow: '0 0 10px #f59e0b' });
        $('#ftm-pip-main-card').css('border-color', '#f59e0b');
    } else {
        $('#ftm-pip-mode-badge').text('⏱️ REGULAR TRACKER').css('color', '#10b981');
        $('#ftm-pip-pulse-dot').css({ background: '#10b981', boxShadow: '0 0 10px #10b981' });
        $('#ftm-pip-main-card').css('border-color', '#1e293b');
    }

    const streamLive = self._hasLiveStream(tracker);
    if (!streamLive) {
        $('#ftm-pip-reconnect-bar').css('display', 'flex').removeClass('hidden');
    } else {
        $('#ftm-pip-reconnect-bar').hide().addClass('hidden');
    }

    // 2. Update Inline Top Tracker Banner on My Assigned View
    const topBar = $('#ftm-active-tracker-bar');
    if (topBar.length) {
        topBar.removeClass('hidden');
        $('#ftm-tracker-project-title').text(titleStr);
        $('#ftm-tracker-clock').text(timeStr);
        $('#ftm-tracker-snaps-count').text(tracker.snapshotCount || 0);

        // Keep screenshots 100% stealth for standard members
        const nextSnapWrap = $('#ftm-tracker-next-snap').parent();
        const snapsCountWrap = $('#ftm-tracker-snaps-count').parent();
        if (isLeadership) {
            nextSnapWrap.show();
            snapsCountWrap.show();
            $('#ftm-btn-tracker-snap-now').show();
        } else {
            nextSnapWrap.hide();
            snapsCountWrap.hide();
            $('#ftm-btn-tracker-snap-now').hide();
        }

        if (!streamLive && $('#ftm-btn-tracker-reconnect').length === 0) {
            $('#ftm-btn-tracker-stop').before(`
                        <button type="button" class="ftm-btn ftm-btn-sm" id="ftm-btn-tracker-reconnect" style="background:#f59e0b; color:#000; border:none; font-weight:700; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
                            ⚡ Connect Screen
                        </button>
                    `);
        } else if (streamLive) {
            $('#ftm-btn-tracker-reconnect, #ftm-btn-resume-stream').remove();
        }
    }
},

        async startProjectTimer(projectId, orderId, clientName, profileName, sessionType = 'regular') {
    const self = this;

    // Auto-detect Extra Time if started after 6:20 PM (18:20) local time
    const nowDate = new Date();
    const currentMins = nowDate.getHours() * 60 + nowDate.getMinutes();
    if (currentMins >= (18 * 60 + 20) && sessionType === 'regular') {
        sessionType = 'extra_time';
    }

    // Check if timer already running
    if (this.state.timeTracker && this.state.timeTracker.active) {
        if (parseInt(this.state.timeTracker.projectId, 10) === parseInt(projectId, 10)) {
            this.toast('Tracker is already actively recording for this project.', 'info');
            return;
        }
        if (!confirm(`Project ${this.state.timeTracker.orderId || this.state.timeTracker.projectId} is currently being tracked. Stop it and switch to ${orderId || projectId}?`)) {
            return;
        }
        await this.stopProjectTimer(true);
    }

    // Request screen capture permissions
    let stream = null;
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            this.toast('Screen capture is not supported by your browser or requires HTTPS.', 'error');
            return;
        }
        stream = await navigator.mediaDevices.getDisplayMedia({
            video: { cursor: 'always', displaySurface: 'monitor' },
            audio: false
        });
    } catch (err) {
        console.warn('Screen share permission denied or cancelled:', err);
        this.toast('Screen capture permission is required to track project time.', 'warning');
        return;
    }

    const video = await this._attachTrackerStream(stream);

    // Register session in backend and unwrap response.data cleanly
    let sessionId = 0;
    try {
        const res = await this.api('time-tracker/start', 'POST', {
            project_id: projectId,
            order_id: orderId,
            client_name: clientName,
            profile_name: profileName,
            session_type: sessionType
        });
        const payload = (res && res.data) ? res.data : (res || {});
        sessionId = parseInt(payload.session_id || (res && res.session_id) || 0, 10);
    } catch (e) {
        console.error('Failed to register tracker session in backend:', e);
        if (stream) stream.getTracks().forEach(t => t.stop());
        this.toast('Failed to start tracking session on server: ' + (e.message || 'Network error'), 'error');
        return;
    }

    if (!sessionId) {
        if (stream) stream.getTracks().forEach(t => t.stop());
        this.toast('Server did not return a valid session ID. Please try again.', 'error');
        return;
    }

    const nowMs = Date.now();
    const initialInterval = this.pickNextInterval(sessionType);

    const trackerState = {
        active: true,
        projectId: parseInt(projectId, 10) || 0,
        orderId: orderId || '',
        clientName: clientName || 'Client',
        profileName: profileName || '',
        sessionId: sessionId,
        sessionType: sessionType,
        startTime: nowMs,
        lastSnapshotTime: nowMs,
        lastHeartbeatTime: nowMs,
        elapsedSeconds: 0,
        intervalSeconds: initialInterval,
        snapshotCount: 0,
        snapshotsCount: 0,
        mouseClicks: 0,
        keyboardClicks: 0,
        totalMouseClicks: 0,
        totalKeyboardClicks: 0,
        stream: stream,
        video: video,
        intervalId: null
    };
    this._attachActivityListeners();
    this._setTrackerState(trackerState);
    this._saveTrackerStorage(trackerState);

    this._startTrackerLoop();
    this.updatePipWidget(trackerState);

    const activeFilter = $('#ftm-my-work-filter-pills .ftm-pill-btn.active').data('filter') || 'active';
    if (typeof this.renderMyAssignedProjectsList === 'function') {
        this.renderMyAssignedProjectsList(activeFilter);
    }

    // Initial proof-of-work snapshot after 8 seconds
    setTimeout(() => {
        const cur = self.state.timeTracker;
        if (cur && cur.active && cur.sessionId === sessionId && (cur.snapshotCount || 0) === 0) {
            cur.lastSnapshotTime = Date.now();
            self.captureAndUploadSnapshot(false);
        }
    }, 8000);

    const modeLabel = sessionType === 'extra_time' ? '⚡ Extra Time Tracker (After 6:20 PM)' : '⏱️ Regular Project Tracker';
    this.toast(`${modeLabel} is now active for ${orderId || projectId}.`, 'success');
},

        async captureAndUploadSnapshot(isManual = false) {
    const self = this;
    const tr = this.state.timeTracker;
    if (!tr || !tr.active) return;

    if (!tr.sessionId) {
        console.warn('Cannot upload snapshot: missing sessionId.');
        return;
    }

    if (!self._hasLiveStream(tr)) {
        if (isManual) {
            self.toast('Screen sharing is disconnected. Click "⚡ Connect Screen" first.', 'warning');
        }
        self.updatePipWidget(tr);
        return;
    }

    let dataUrl = null;
    const drawAuditWatermark = (canvas) => {
        try {
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            ctx.fillStyle = 'rgba(15, 23, 42, 0.78)';
            ctx.fillRect(0, height - 34, width, 34);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            const tag = (tr.sessionType === 'extra_time') ? '⚡ [EXTRA TIME] ' : '';
            const dateStr = new Date().toLocaleString();
            ctx.fillText(`${tag}FB Team Hub · #${tr.orderId || tr.projectId} (${tr.clientName || 'Client'}) · ${dateStr}`, 14, height - 12);
        } catch (e) { }
    };

    // 1. Try ImageCapture API first (works even when Chrome tab is backgrounded)
    try {
        if (window.ImageCapture && tr.stream && tr.stream.getVideoTracks().length > 0) {
            const track = tr.stream.getVideoTracks()[0];
            if (track.readyState === 'live') {
                const imageCapture = new ImageCapture(track);
                const bitmap = await imageCapture.grabFrame();
                const canvas = document.createElement('canvas');
                canvas.width = bitmap.width || 1280;
                canvas.height = bitmap.height || 720;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                if (typeof bitmap.close === 'function') bitmap.close();
                drawAuditWatermark(canvas);
                dataUrl = canvas.toDataURL('image/jpeg', 0.72);
            }
        }
    } catch (e) {
        console.warn('ImageCapture grabFrame fallback to video canvas:', e);
    }

    // 2. Fallback to DOM video element
    if (!dataUrl && tr.video && tr.video.readyState >= 2) {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = tr.video.videoWidth || 1280;
            canvas.height = tr.video.videoHeight || 720;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(tr.video, 0, 0, canvas.width, canvas.height);
            drawAuditWatermark(canvas);
            dataUrl = canvas.toDataURL('image/jpeg', 0.72);
        } catch (e) {
            console.warn('Video canvas drawing failed:', e);
        }
    }

    if (!dataUrl) {
        console.warn('Could not extract frame from active screen capture stream.');
        return;
    }

    try {
        const elapsedNow = Math.max(0, Math.floor((Date.now() - tr.startTime) / 1000));
        tr.elapsedSeconds = elapsedNow;
        const defaultNote = isManual
            ? 'Manual screen capture by specialist'
            : (tr.sessionType === 'extra_time' ? 'Automated overtime activity capture' : 'Automated shift activity capture');

        const res = await this.api('time-tracker/snapshot', 'POST', {
            session_id: tr.sessionId,
            project_id: tr.projectId,
            order_id: tr.orderId,
            client_name: tr.clientName,
            session_type: tr.sessionType,
            elapsed_seconds: elapsedNow,
            total_seconds: elapsedNow,
            mouse_clicks: tr.mouseClicks || 0,
            keyboard_clicks: tr.keyboardClicks || 0,
            total_mouse_clicks: tr.totalMouseClicks || 0,
            total_keyboard_clicks: tr.totalKeyboardClicks || 0,
            image: dataUrl,
            image_base64: dataUrl,
            notes: defaultNote,
            is_manual: isManual ? 1 : 0
        });

        // Reset interval activity counters for next snapshot cycle
        tr.mouseClicks = 0;
        tr.keyboardClicks = 0;

        const resData = (res && res.data) ? res.data : (res || {});
        const snapUrl = resData.snapshot_url || resData.url || (res && res.image_url) || dataUrl;

        tr.snapshotCount = (tr.snapshotCount || 0) + 1;
        tr.snapshotsCount = tr.snapshotCount;
        this._saveTrackerStorage(tr);
        this.updatePipWidget(tr);

        // Only show snapshot preview toast for Leaders/Admins (100% stealth for members)
        if (self.state.isAdmin || self.state.isLeader) {
            $('#ftm-pip-toast-thumb').attr('src', snapUrl);
            $('#ftm-pip-toast-order').text(`#${tr.orderId || tr.projectId} (${tr.clientName || 'Client'})`);
            $('#ftm-pip-toast-headline').text(
                isManual
                    ? '📸 Manual Activity Captured!'
                    : (tr.sessionType === 'extra_time' ? '⚡ Extra Time Activity Captured!' : `📸 Work Activity #${tr.snapshotCount} Captured!`)
            );
            $('#ftm-pip-toast-time').text(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
            $('#ftm-pip-snap-toast').stop(true, true).removeClass('hidden').css('display', 'block');

            clearTimeout(this._snapToastTimeout);
            this._snapToastTimeout = setTimeout(() => {
                $('#ftm-pip-snap-toast').fadeOut(300, function () { $(this).addClass('hidden'); });
            }, 7000);
        }

        if (isManual && (self.state.isAdmin || self.state.isLeader)) {
            self.toast('📸 Manual screenshot captured and saved.', 'success');
        }
    } catch (err) {
        console.error('Failed to capture and upload tracking snapshot:', err);
        if (isManual) {
            self.toast('Failed to capture snapshot: ' + (err.message || 'Upload error'), 'error');
        }
    }
},

        async stopProjectTimer(silent = false) {
    const self = this;
    const tr = this.state.timeTracker;
    if (!tr || !tr.active) return;

    const elapsedSec = Math.max(0, Math.floor((Date.now() - tr.startTime) / 1000));
    tr.elapsedSeconds = elapsedSec;
    const timeFormatted = this._formatClockHMS(elapsedSec);
    const isExtraTime = (tr.sessionType === 'extra_time');
    const isLeadership = !!(self.state.isAdmin || self.state.isLeader);

    let extraReason = '';
    let tasksNote = '';

    if (!silent) {
        const snapInfo = isLeadership ? `\nScreenshots Saved: ${tr.snapshotCount || 0}` : '';
        const confirmed = confirm(
            `Stop tracking work on #${tr.orderId || tr.projectId} (${tr.clientName || 'Client'})?\n\n` +
            `Mode: ${isExtraTime ? '⚡ EXTRA TIME (After 6:20 PM)' : '⏱ Regular Shift'}\n` +
            `Total Tracked: ${timeFormatted}${snapInfo}\n\n` +
            `Click OK to stop and sync this time to today's Daily Standup, or Cancel to continue tracking.`
        );
        if (!confirmed) return;

        if (isExtraTime) {
            extraReason = prompt('⚡ You worked Extra Time after 6:20 PM. Enter the overtime reason / milestone worked on:', 'Extended work deliverables to meet project deadline') || 'Extended work deliverables';
        }
    }

    if (tr.intervalId) {
        clearInterval(tr.intervalId);
    }
    if (tr.stream) {
        tr.stream.getTracks().forEach(t => t.stop());
    }

    const hoursDecimal = parseFloat((elapsedSec / 3600).toFixed(2));
    tasksNote = `${isExtraTime ? '[⚡ Extra Time after 6:20 PM]: ' : ''}Active work tracking on #${tr.orderId || tr.projectId} (${tr.clientName || 'Client'}): ${this.formatHoursDisplay(hoursDecimal)}.`;

    try {
        await this.api('time-tracker/stop', 'POST', {
            session_id: tr.sessionId,
            project_id: tr.projectId,
            order_id: tr.orderId,
            elapsed_seconds: elapsedSec,
            total_seconds: elapsedSec,
            hours_decimal: hoursDecimal,
            log_to_daily_report: true,
            tasks_completed: tasksNote,
            session_type: tr.sessionType || 'regular',
            extra_time_reason: extraReason || '',
            total_mouse_clicks: tr.totalMouseClicks || 0,
            total_keyboard_clicks: tr.totalKeyboardClicks || 0
        });

        this.toast(`Tracking stopped for ${tr.orderId || tr.projectId}. Total logged: ${this.formatHoursDisplay(hoursDecimal)}. Synced to daily progress.`, 'success');
    } catch (e) {
        console.error('Error stopping tracker session:', e);
        this.toast('Session stopped locally (sync warning: ' + (e.message || 'error') + ').', 'warning');
    } finally {
        localStorage.removeItem('ftm_active_tracker_session');
        this._setTrackerState({
            active: false,
            sessionId: null,
            projectId: null,
            orderId: '',
            clientName: '',
            profileName: '',
            sessionType: 'regular',
            startTime: 0,
            lastSnapshotTime: 0,
            lastHeartbeatTime: 0,
            stream: null,
            video: null,
            elapsedSeconds: 0,
            intervalSeconds: 300,
            snapshotsCount: 0,
            snapshotCount: 0,
            mouseClicks: 0,
            keyboardClicks: 0,
            totalMouseClicks: 0,
            totalKeyboardClicks: 0,
            intervalId: null
        });
        this.updatePipWidget(null);
        $('#ftm-pip-snap-toast').hide().addClass('hidden');

        this.loadProjects();
        const activeFilter = $('#ftm-my-work-filter-pills .ftm-pill-btn.active').data('filter') || 'active';
        if (typeof this.renderMyAssignedProjectsList === 'function') {
            this.renderMyAssignedProjectsList(activeFilter);
        }
        if (typeof this.loadTeamDailyReportView === 'function') {
            this.loadTeamDailyReportView();
        }
        if ($('#ftm-view-time-tracker').is(':visible') && typeof this.loadTimeTrackerHubView === 'function') {
            this.loadTimeTrackerHubView();
        }
    }
},

        async restoreActiveProjectTimer() {
    const self = this;
    let saved = null;
    try {
        const raw = localStorage.getItem('ftm_active_tracker_session');
        if (raw) saved = JSON.parse(raw);
    } catch (e) { }

    // Always verify live active session with backend DB
    let sessionData = null;
    try {
        const res = await self.api('time-tracker/active', 'GET');
        const backend = (res && res.data !== undefined) ? res.data : (res && res.session_id ? res : null);
        if (backend && backend.session_id) {
            const elapsedSec = Math.max(0, parseInt(backend.elapsed_seconds || 0, 10));
            const startMs = Date.now() - (elapsedSec * 1000);
            sessionData = {
                projectId: parseInt(backend.project_id || 0, 10),
                orderId: backend.order_id || '',
                clientName: backend.client_name || 'Client',
                profileName: backend.profile_name || '',
                sessionId: parseInt(backend.session_id, 10),
                sessionType: backend.session_type || 'regular',
                startTime: startMs,
                lastSnapshotTime: Date.now(),
                intervalSeconds: (saved && saved.intervalSeconds) ? saved.intervalSeconds : self.pickNextInterval(backend.session_type || 'regular'),
                snapshotCount: parseInt(backend.snapshots_count || 0, 10),
                totalMouseClicks: Math.max(parseInt(backend.total_mouse_clicks || 0, 10), parseInt(saved && saved.totalMouseClicks || 0, 10)),
                totalKeyboardClicks: Math.max(parseInt(backend.total_keyboard_clicks || 0, 10), parseInt(saved && saved.totalKeyboardClicks || 0, 10))
            };
        }
    } catch (e) {
        if (saved && saved.sessionId && saved.startTime && (Date.now() - saved.startTime) < 43200000) {
            sessionData = saved;
        }
    }

    if (!sessionData || !sessionData.sessionId) {
        localStorage.removeItem('ftm_active_tracker_session');
        this.updatePipWidget(null);
        return;
    }

    const startMs = parseInt(sessionData.startTime || Date.now(), 10);
    const elapsed = Math.max(0, Math.floor((Date.now() - startMs) / 1000));
    const trackerState = {
        active: true,
        projectId: sessionData.projectId,
        orderId: sessionData.orderId || '',
        clientName: sessionData.clientName || 'Client',
        profileName: sessionData.profileName || '',
        sessionId: sessionData.sessionId,
        sessionType: sessionData.sessionType || 'regular',
        startTime: startMs,
        lastSnapshotTime: Date.now(),
        lastHeartbeatTime: Date.now(),
        elapsedSeconds: elapsed,
        intervalSeconds: sessionData.intervalSeconds || self.pickNextInterval(sessionData.sessionType || 'regular'),
        snapshotCount: sessionData.snapshotCount || 0,
        snapshotsCount: sessionData.snapshotCount || 0,
        mouseClicks: 0,
        keyboardClicks: 0,
        totalMouseClicks: sessionData.totalMouseClicks || 0,
        totalKeyboardClicks: sessionData.totalKeyboardClicks || 0,
        stream: null,
        video: null,
        intervalId: null
    };

    this._attachActivityListeners();
    this._setTrackerState(trackerState);
    this._saveTrackerStorage(trackerState);
    this.updatePipWidget(trackerState);
    this._startTrackerLoop();

    const activeFilter = $('#ftm-my-work-filter-pills .ftm-pill-btn.active').data('filter') || 'active';
    if (typeof this.renderMyAssignedProjectsList === 'function') {
        this.renderMyAssignedProjectsList(activeFilter);
    }

    this.toast(`⏱️ Resumed tracking session for ${trackerState.orderId || trackerState.projectId}. Click "⚡ Connect Screen" to resume captures.`, 'info');
},

        async resumeScreenSharingStream() {
    const tr = this.state.timeTracker;
    if (!tr || !tr.active) return;

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            this.toast('Screen capture is not supported by your browser or requires HTTPS.', 'error');
            return;
        }
        const stream = await navigator.mediaDevices.getDisplayMedia({
            video: { cursor: 'always', displaySurface: 'monitor' },
            audio: false
        });

        const video = await this._attachTrackerStream(stream);
        tr.stream = stream;
        tr.video = video;
        tr.lastSnapshotTime = Date.now();
        this.updatePipWidget(tr);
        this.toast('✅ Screen sharing reconnected! Automated captures resumed.', 'success');
        setTimeout(() => {
            if (this.state.timeTracker && this.state.timeTracker.active) {
                this.captureAndUploadSnapshot(false);
            }
        }, 1500);
    } catch (err) {
        console.warn('Failed to reconnect screen stream:', err);
        this.toast('Could not connect screen sharing stream.', 'warning');
    }
},

        async openTrackerGalleryModal(projectId = 0, sessionId = 0) {
    const self = this;
    if (!self.state.isAdmin && !self.state.isLeader) {
        return;
    }
    $('#ftm-modal-tracker-gallery').removeClass('hidden');
    $('#ftm-tracker-gallery-badge').text('Loading...');
    $('#ftm-tracker-gallery-grid').html(`
                <div style="grid-column:1/-1; text-align:center; padding:40px 16px; color:var(--ftm-text-muted);">
                    <span class="ftm-spinner"></span> Loading captured screenshots...
                </div>
            `);

    try {
        const params = [];
        if (projectId > 0) params.push(`project_id=${projectId}`);
        if (sessionId > 0) params.push(`session_id=${sessionId}`);
        const query = params.length ? '?' + params.join('&') : '';

        const res = await self.api('time-tracker/snapshots' + query, 'GET');
        const snaps = Array.isArray(res) ? res : (res && res.data ? res.data : []);

        if (!snaps || snaps.length === 0) {
            $('#ftm-tracker-gallery-badge').text('0 Screenshots');
            $('#ftm-tracker-gallery-grid').html(`
                        <div style="grid-column:1/-1; text-align:center; padding:48px 16px; background:var(--ftm-card-bg); border:1px dashed var(--ftm-border); border-radius:12px; color:var(--ftm-text-muted);">
                            <div style="font-size:36px; margin-bottom:10px;">📸</div>
                            <div style="font-weight:700; font-size:15px; color:var(--ftm-text-primary);">No Screenshots Captured Yet</div>
                            <div style="font-size:12.5px; margin-top:4px;">When a member starts the project timer, work activity screenshots are captured every 10 minutes and recorded here for leadership review.</div>
                        </div>
                    `);
            return;
        }



        $('#ftm-tracker-gallery-badge').text(`${snaps.length} Screenshot${snaps.length > 1 ? 's' : ''}`);
        let cardsHtml = '';
        snaps.forEach((sn, idx) => {
            cardsHtml += `
                        <div class="ftm-tracker-snap-card" style="background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:10px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                            <div style="position:relative; aspect-ratio:16/9; background:#0f172a; overflow:hidden; cursor:pointer;" class="ftm-snap-thumb-wrap" data-full="${sn.url}" title="Click to view full image in new tab">
                                <img src="${sn.url}" alt="Screenshot ${idx + 1}" style="width:100%; height:100%; object-fit:cover; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                                <span style="position:absolute; bottom:8px; left:8px; background:rgba(0,0,0,0.75); color:#fff; font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:4px; backdrop-filter:blur(4px);">
                                    ⏱ ${sn.elapsed_label || '+10m'}
                                </span>
                                <span style="position:absolute; bottom:8px; right:8px; background:rgba(15,23,42,0.85); color:#93c5fd; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px; border:1px solid rgba(147,197,253,0.3); backdrop-filter:blur(4px);">
                                    🖱️ ${sn.mouse_clicks || 0} · ⌨️ ${sn.keyboard_clicks || 0}
                                </span>
                                <span style="position:absolute; top:8px; right:8px; background:rgba(16,185,129,0.9); color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px;">
                                    #${sn.order_id || 'ORDER'}
                                </span>
                            </div>
                            <div style="padding:10px 12px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--ftm-border); font-size:11.5px;">
                                <div>
                                    <div style="font-weight:700; color:var(--ftm-text-primary);">${ftmEsc(sn.specialist_name)}</div>
                                    <div style="color:var(--ftm-text-muted); font-size:10.5px;">${sn.captured_at}</div>
                                    <div style="color:#94a3b8; font-size:10.5px; margin-top:2px;">🖱️ ${sn.mouse_clicks || 0} clicks · ⌨️ ${sn.keyboard_clicks || 0} keys</div>
                                </div>
                                <a href="${sn.url}" target="_blank" rel="noopener noreferrer" class="ftm-btn ftm-btn-secondary ftm-btn-xs" style="text-decoration:none;">
                                    🔍 Full Size
                                </a>
                            </div>
                        </div>
                    `;
        });
        $('#ftm-tracker-gallery-grid').html(cardsHtml);
    } catch (err) {
        console.error('Error loading snapshots gallery:', err);
        $('#ftm-tracker-gallery-badge').text('Error');
        $('#ftm-tracker-gallery-grid').html(`
                    <div style="grid-column:1/-1; text-align:center; padding:32px 16px; color:#ef4444;">
                        Failed to load screenshots: ${ftmEsc(err.message || 'Unknown error')}
                    </div>
                `);
    }
},







uploadAvatarFile(file) {



    const self = this;



    const formData = new FormData();



    formData.append('avatar', file);







    const uploadBtn = $('#ftm-btn-trigger-upload');



    const origHtml = uploadBtn.html();



    uploadBtn.prop('disabled', true).html('<span class="ftm-spinner"></span> <span>Uploading...</span>');







    const url = ftmData.root + 'user/avatar';



    fetch(url, {



        method: 'POST',



        headers: {



            'X-WP-Nonce': ftmData.nonce



        },



        body: formData



    })



        .then(res => res.json())



        .then(data => {



            uploadBtn.prop('disabled', false).html(origHtml);



            $('#ftm-avatar-file-input').val('');



            if (data.code || data.error || !data.avatar_url) {



                self.toast(data.message || 'Avatar upload failed.', 'error');



            } else {



                $('#ftm-settings-avatar-preview').attr('src', data.avatar_url);



                $('#ftm-avatar-url-input').val(data.avatar_url);



                $('.ftm-user-profile-btn .ftm-avatar, .ftm-dropdown-user-header .ftm-avatar, #ftm-header-user-avatar').attr('src', data.avatar_url);



                $('.ftm-preset-avatar-btn').removeClass('active');



                self.toast('Profile photo uploaded and saved successfully!', 'success');



            }



        })



        .catch(err => {



            uploadBtn.prop('disabled', false).html(origHtml);



            $('#ftm-avatar-file-input').val('');



            self.toast('Failed to upload image. Please try again.', 'error');



        });



},







saveUserProfile(explicitAvatarUrl = null, isReset = false) {



    const self = this;



    const name = $('#ftm-setting-display-name').val().trim();



    const email = $('#ftm-setting-email').val() ? $('#ftm-setting-email').val().trim() : '';



    const avatarUrl = explicitAvatarUrl !== null ? explicitAvatarUrl : $('#ftm-avatar-url-input').val();



    const feedback = $('#ftm-profile-feedback');



    const saveBtn = $('#ftm-btn-save-profile');







    if (!name) {



        feedback.removeClass('hidden success').addClass('error').text('Please enter your display name.');



        return;



    }







    if ($('#ftm-setting-email').length && !email) {



        feedback.removeClass('hidden success').addClass('error').text('Please enter a valid email address.');



        return;



    }







    saveBtn.prop('disabled', true).html('<span class="ftm-spinner"></span> <span>Saving...</span>');



    feedback.addClass('hidden').removeClass('success error');







    const payload = { display_name: name };



    if (email) {



        payload.email = email;



    }



    if (isReset || avatarUrl !== '') {



        payload.avatar_url = avatarUrl;



    }







    this.api('/user/profile', 'POST', payload)



        .then(res => {



            saveBtn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> <span>Save Profile Changes</span>');







            // Update topbar name & avatars in real-time



            $('#ftm-dropdown-user-name, .ftm-user-name, .ftm-sidebar-user-name, .ftm-dropdown-user-header .ftm-font-semibold').text(res.display_name);



            $('#ftm-view-admin-profile .ftm-refa-name').text(res.display_name);







            if (res.email) {



                $('#ftm-dropdown-user-email, #ftm-admin-profile-email, #ftm-profile-meta-email, #ftm-header-user-email').text(res.email);



                $('#ftm-setting-email').val(res.email);



                if (window.ftmData && window.ftmData.current_user) {



                    window.ftmData.current_user.email = res.email;



                }



            }



            if (res.display_name && window.ftmData && window.ftmData.current_user) {



                window.ftmData.current_user.name = res.display_name;



            }



            if (res.avatar_url) {



                $('#ftm-settings-avatar-preview').attr('src', res.avatar_url);



                $('.ftm-user-profile-btn .ftm-header-avatar, .ftm-user-profile-btn .ftm-avatar, .ftm-dropdown-user-header .ftm-header-avatar, .ftm-dropdown-user-header .ftm-avatar, .ftm-sidebar-user-avatar').attr('src', res.avatar_url);



            }







            feedback.removeClass('hidden error').addClass('success').text(res.message || 'Profile saved successfully!');



            self.toast('Profile updated successfully!', 'success');



        })



        .catch(err => {



            saveBtn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> <span>Save Profile Changes</span>');



            feedback.removeClass('hidden success').addClass('error').text(err.message || 'Error updating profile.');



        });



},







changePassword() {



    const self = this;



    const oldPass = $('#ftm-old-password').val();



    const newPass = $('#ftm-new-password').val();



    const confirmPass = $('#ftm-confirm-password').val();



    const feedback = $('#ftm-password-feedback');



    const submitBtn = $('#ftm-btn-update-password');







    feedback.addClass('hidden').removeClass('success error');







    if (!oldPass || !newPass || !confirmPass) {



        feedback.removeClass('hidden success').addClass('error').text('Please fill in all password fields.');



        return;



    }







    if (newPass !== confirmPass) {



        feedback.removeClass('hidden success').addClass('error').text('New password and confirm password do not match.');



        return;



    }







    if (newPass.length < 6) {



        feedback.removeClass('hidden success').addClass('error').text('New password must be at least 6 characters long.');



        return;



    }







    submitBtn.prop('disabled', true).html('<span class="ftm-spinner"></span> <span>Updating Password...</span>');







    this.api('/user/password', 'POST', {



        old_password: oldPass,



        new_password: newPass,



        confirm_password: confirmPass



    })



        .then(res => {



            submitBtn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> <span>Update Password</span>');



            feedback.removeClass('hidden error').addClass('success').text(res.message || 'Password updated successfully!');



            $('#ftm-password-form')[0].reset();



            self.toast('Password updated successfully!', 'success');



        })



        .catch(err => {



            submitBtn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> <span>Update Password</span>');



            feedback.removeClass('hidden success').addClass('error').text(err.message || 'Failed to update password.');



        });



},







openDailyStandupModal(prefillProject = null) {



    const self = this;



    const wrap = $('#ftm-standup-items-wrap');



    wrap.empty();







    $('#ftm-standup-report-id').val('');



    $('#ftm-modal-standup-title').text('Daily Progress Report');



    $('#ftm-btn-submit-standup').text('Submit Report to Leader');



    $('#ftm-standup-feedback-banner').addClass('hidden').empty();



    const todayIso = new Date().toISOString().split('T')[0];



    $('#ftm-standup-date').val(todayIso);



    self._isEditingFullReport = false;







    Promise.all([
        this.api("projects?view_filter=my&per_page=50"),
        self.state.myDailyReports ? Promise.resolve(self.state.myDailyReports) : this.api("daily-reports?scope=mine"),
        this.api("tasks/my-pending").catch(() => [])
    ]).then(([projRes, repList, pendingTasks]) => {
        // Cutoff Countdown Timer (9:15 AM)
        const now = new Date();
        const cutoff = new Date();
        cutoff.setHours(9, 15, 0, 0);
        const timerEl = $("#ftm-standup-deadline-timer");
        if (now < cutoff) {
            const diffMs = cutoff - now;
            const diffHrs = Math.floor(diffMs / 3600000);
            const diffMins = Math.floor((diffMs % 3600000) / 60000);
            timerEl.html(`<span style="color:#059669; font-weight:700;">⏳ ${diffHrs}h ${diffMins}m remaining until 9:15 AM cutoff</span>`);
        } else {
            timerEl.html(`<span style="color:#dc2626; font-weight:700;">Past 9:15 AM Cutoff (Will be marked Late)</span>`);
        }

        // Render Active Assigned Tasks & Milestones
        const tasksWrap = $("#ftm-standup-active-tasks-wrap");
        const tasksPills = $("#ftm-standup-tasks-pills");
        const taskCount = $("#ftm-standup-tasks-count-badge");
        self._standupCompletedTaskIds = [];

        if (pendingTasks && pendingTasks.length > 0) {
            tasksWrap.removeClass("hidden");
            taskCount.text(pendingTasks.length);
            let pills = "";
            pendingTasks.forEach(t => {
                pills += `
                            <div class="ftm-standup-task-pill" style="display:inline-flex; align-items:center; gap:6px; background:#fff; border:1px solid var(--ftm-border); border-radius:6px; padding:4px 8px; font-size:12px;">
                                <span><strong>${self.escapeHtml(t.title)}</strong> <small class="ftm-text-muted">(${t.order_id ? "#" + t.order_id : ""})</small></span>
                                <button type="button" class="ftm-btn-pill-add-done" data-task-id="${t.id}" data-title="${self.escapeHtml(t.title)}" data-project-id="${t.project_id}" style="border:none; background:#ecfdf5; color:#059669; font-size:11px; font-weight:600; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Add to Completed Tasks">+ Done</button>
                                <button type="button" class="ftm-btn-pill-add-wip" data-task-id="${t.id}" data-title="${self.escapeHtml(t.title)}" data-project-id="${t.project_id}" style="border:none; background:#eff6ff; color:#2563eb; font-size:11px; font-weight:600; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Add to WIP">+ WIP</button>
                            </div>
                        `;
            });
            tasksPills.html(pills);

            tasksPills.find(".ftm-btn-pill-add-done").off("click").on("click", function (e) {
                e.preventDefault();
                const tTitle = $(this).data("title");
                const tId = parseInt($(this).data("task-id"));
                if (!self._standupCompletedTaskIds.includes(tId)) {
                    self._standupCompletedTaskIds.push(tId);
                }
                const firstBox = wrap.find(".ftm-standup-item-box").first();
                if (firstBox.length) {
                    const completedArea = firstBox.find(".ftm-standup-completed");
                    const cur = completedArea.val().trim();
                    completedArea.val(cur ? `${cur}
• Completed: ${tTitle}` : `• Completed: ${tTitle}`);
                    $(this).closest(".ftm-standup-task-pill").css("opacity", "0.45");
                    self.toast(`Task added to Completed: ${tTitle}`, "info");
                }
            });

            tasksPills.find(".ftm-btn-pill-add-wip").off("click").on("click", function (e) {
                e.preventDefault();
                const tTitle = $(this).data("title");
                const firstBox = wrap.find(".ftm-standup-item-box").first();
                if (firstBox.length) {
                    const wipArea = firstBox.find(".ftm-standup-wip");
                    const cur = wipArea.val().trim();
                    wipArea.val(cur ? `${cur}
• WIP: ${tTitle}` : `• WIP: ${tTitle}`);
                    self.toast(`Task added to WIP: ${tTitle}`, "info");
                }
            });
        } else {
            tasksWrap.addClass("hidden");
        }



        self.state.myActiveProjects = projRes.items || [];



        self.state.myDailyReports = repList || [];







        const todayRep = (self.state.myDailyReports || []).find(r => r.report_date === todayIso);







        if (prefillProject) {



            const pId = typeof prefillProject === 'object' ? parseInt(prefillProject.project_id || prefillProject.id) : parseInt(prefillProject);



            let itemData = typeof prefillProject === 'object' ? Object.assign({}, prefillProject) : { project_id: pId };







            // Check if existing item exists today for this project to prefill



            if (todayRep && todayRep.items) {



                const existingItem = todayRep.items.find(it => parseInt(it.project_id) === pId);



                if (existingItem) {



                    itemData = Object.assign({}, itemData, existingItem);



                }



            }



            self.addStandupProjectItem(itemData);



        } else if (todayRep && todayRep.items && todayRep.items.length > 0) {



            // Open full day's report



            self._isEditingFullReport = true;



            $('#ftm-standup-report-id').val(todayRep.id);



            todayRep.items.forEach(it => {



                self.addStandupProjectItem(it);



            });



        } else {



            self.addStandupProjectItem();



        }







        $('#ftm-modal-standup').removeClass('hidden');



    }).catch(err => {



        self.toast('Error preparing daily standup: ' + (err.message || 'Unknown'), 'error');



    });



},







openDailyStandupModalForEdit(reportId) {



    const self = this;



    self._isEditingFullReport = true;



    const reports = self.state.myDailyReports || [];



    let r = reports.find(x => parseInt(x.id) === parseInt(reportId));



    if (!r && self.state.teamDailyReports) {



        r = self.state.teamDailyReports.find(x => parseInt(x.id) === parseInt(reportId));



    }



    if (!r) {



        self.toast('Report details not found.', 'error');



        return;



    }







    const wrap = $('#ftm-standup-items-wrap');



    wrap.empty();







    $('#ftm-standup-report-id').val(r.id);



    $('#ftm-standup-date').val(r.report_date);







    if (r.leader_feedback) {



        $('#ftm-standup-feedback-banner')



            .html(`<strong>Leader Correction Request:</strong> ${r.leader_feedback}`)



            .removeClass('hidden');



    } else {



        $('#ftm-standup-feedback-banner').addClass('hidden').empty();



    }







    // Configure Manager Review & Feedback Section



    const u = (window.ftmData && window.ftmData.current_user) ? window.ftmData.current_user : {};



    const isManagerOrAdmin = u.is_admin || u.is_leader || (u.role && ['admin', 'leader', 'co_leader'].includes(u.role)) || (u.role_label && (u.role_label.toLowerCase().includes('leader') || u.role_label.toLowerCase().includes('admin') || u.role_label.toLowerCase().includes('manager')));







    if (isManagerOrAdmin) {



        $('#ftm-manager-feedback-section').removeClass('hidden');



        $('#ftm-manager-feedback-input').val(r.leader_feedback || '');



        const stClass = r.status === 'approved' ? 'bg-emerald-soft text-emerald' : (r.status === 'revision_requested' ? 'bg-rose-soft text-rose' : 'bg-blue-soft text-blue');



        $('#ftm-manager-feedback-status').attr('class', `ftm-badge ${stClass} ftm-badge-xs`).text(r.status_label || r.status || 'Approved');







        $('#ftm-btn-save-manager-comment').off('click').on('click', function (e) {



            e.preventDefault();



            const repId = parseInt($('#ftm-standup-report-id').val());



            const feedback = $('#ftm-manager-feedback-input').val().trim();



            if (!repId) return;







            const btn = $(this);



            btn.prop('disabled', true).text('Saving...');



            self.api(`daily-reports/${repId}/review`, 'POST', {



                action: 'comment',



                feedback: feedback



            }).then(res => {



                self.toast('Manager comment saved.', 'success');



                btn.prop('disabled', false).html('<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> <span>Save Comment</span>');



                self.loadTeamDailyReportView();



            }).catch(err => {



                self.toast('Error saving comment: ' + (err.message || 'Unknown'), 'error');



                btn.prop('disabled', false).text('Save Comment');



            });



        });







        $('#ftm-btn-request-revision').off('click').on('click', function (e) {



            e.preventDefault();



            const repId = parseInt($('#ftm-standup-report-id').val());



            const feedback = $('#ftm-manager-feedback-input').val().trim();



            if (!repId) return;



            if (!feedback) {



                self.toast('Please enter the reason or revision details in the feedback box.', 'warning');



                $('#ftm-manager-feedback-input').focus();



                return;



            }







            if (!confirm('Request revision for this report? The member will be required to update and resubmit.')) return;







            const btn = $(this);



            btn.prop('disabled', true).text('Requesting...');



            self.api(`daily-reports/${repId}/review`, 'POST', {



                action: 'revision',



                feedback: feedback



            }).then(res => {



                self.toast('Revision requested from member.', 'success');



                $('#ftm-modal-standup').addClass('hidden');



                self.loadTeamDailyReportView();



            }).catch(err => {



                self.toast('Error requesting revision: ' + (err.message || 'Unknown'), 'error');



                btn.prop('disabled', false).text('Request Revision');



            });



        });



    } else {



        $('#ftm-manager-feedback-section').addClass('hidden');



    }







    // 1. Weekend Report View
    if (r.report_type === 'weekend') {
        $('#ftm-modal-standup-title').text('Daily Status: Weekend / Weekly Off');
        const isApproved = r.status === 'approved';
        wrap.html(`
                    <div style="padding:28px 20px; text-align:center; background:var(--ftm-bg); border-radius:12px; border:1px dashed var(--ftm-border);">
                        <div style="font-size:40px; margin-bottom:10px;">🌴</div>
                        <h4 style="font-size:17px; font-weight:700; color:var(--ftm-text-primary); margin:0 0 6px 0;">Weekend / Weekly Off Day</h4>
                        <p style="font-size:13px; color:var(--ftm-text-muted); margin:0;">
                            Logged for <strong>${r.report_date}</strong> by <strong>${self.escapeHtml(r.user_name || 'Team Member')}</strong> (${ftmEsc(r.team_name)}).
                        </p>
                        <div style="margin-top:14px;">
                            <span class="ftm-badge ${isApproved ? 'bg-emerald-soft text-emerald' : 'bg-amber-soft text-amber'}" style="font-size:12px; font-weight:700; padding:6px 14px;">
                                ${isApproved ? '✓ Status: Approved / Off' : '⏳ Status: Submitted (Awaiting Leader Verification)'}
                            </span>
                        </div>
                        <div style="margin-top:20px; display:inline-flex; gap:10px; flex-wrap:wrap; justify-content:center;">
                            <button type="button" class="ftm-btn ftm-btn-warning ftm-btn-sm" id="ftm-btn-convert-weekend-to-work">
                                <span>✏️ Submitted by Mistake? Switch to Work Deliverables</span>
                            </button>
                            ${isManagerOrAdmin ? `
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm text-rose" id="ftm-btn-reset-weekend-entry" style="border-color:rgba(244,63,94,0.4);">
                                    <span>↺ Reset Mistake Report</span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `);

        $('#ftm-btn-convert-weekend-to-work').off('click').on('click', function (e) {
            e.preventDefault();
            r.report_type = 'work';
            $('#ftm-modal-standup-title').text('Update & Resubmit Daily Progress Report');
            wrap.empty();
            self.addStandupItemRow();
            $('#ftm-btn-submit-standup').show();
        });

        $('#ftm-btn-reset-weekend-entry').off('click').on('click', function (e) {
            e.preventDefault();
            if (!confirm('Reset this mistaken weekend report? The member will be able to file their regular work deliverables.')) return;
            $('#ftm-modal-standup').addClass('hidden');
            self.reviewReportAction(r.id, 'reset');
        });

        $('#ftm-btn-submit-standup').hide();
        $('#ftm-modal-standup').removeClass('hidden');
        return;
    }







    // 2. On Leave Report View



    if (r.report_type === 'on_leave') {



        $('#ftm-modal-standup-title').text('Daily Status: Leave Notice');



        const rangeStr = (r.leave_start_date && r.leave_end_date && r.leave_start_date !== r.leave_end_date)



            ? `${r.leave_start_date} &rarr; ${r.leave_end_date}`



            : (r.leave_start_date || r.report_date);







        wrap.html(`



                    <div style="padding:22px; background:var(--ftm-bg); border-radius:12px; border:1px solid var(--ftm-border);">



                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">



                            <span style="font-size:32px;"><span class="dashicons dashicons-calendar-alt"></span></span>



                            <div>



                                <h4 style="font-size:16px; font-weight:700; color:var(--ftm-text-primary); margin:0;">On Leave &bull; ${self.escapeHtml(r.leave_type_label || 'Leave Notice')}</h4>



                                <div style="font-size:12px; color:var(--ftm-text-muted); margin-top:3px;">



                                    Submitted by <strong>${self.escapeHtml(r.user_name || 'Team Member')}</strong> (${ftmEsc(r.team_name)})



                                </div>



                            </div>



                        </div>







                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; background:var(--ftm-surface); padding:12px 14px; border-radius:8px; border:1px solid var(--ftm-border);">



                            <div>



                                <span style="font-size:11px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:700;">Leave Period</span>



                                <div style="font-size:13px; font-weight:700; color:#2563eb; margin-top:2px;">${rangeStr}</div>



                            </div>



                            <div>



                                <span style="font-size:11px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:700;">Category</span>



                                <div style="font-size:13px; font-weight:600; color:var(--ftm-text-primary); margin-top:2px;">${self.escapeHtml(r.leave_type_label || 'Leave')}</div>



                            </div>



                        </div>







                        <div style="background:var(--ftm-surface); padding:12px 14px; border-radius:8px; border:1px solid var(--ftm-border);">



                            <span style="font-size:11px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:700;">Reason Provided</span>



                            <div style="font-size:13px; color:var(--ftm-text-primary); margin-top:4px; line-height:1.5;">${self.escapeHtml(r.leave_reason || 'Personal / General leave notice')}</div>



                        </div>







                        <div style="margin-top:14px; text-align:center;">
                            <span class="ftm-badge ${r.status === 'approved' ? 'bg-emerald-soft text-emerald' : 'bg-amber-soft text-amber'}" style="font-size:12px; font-weight:700; padding:6px 14px;">
                                ${r.status === 'approved' ? '✓ Approved Leave' : '⏳ Application Submitted (Awaiting Approval)'}
                            </span>
                        </div>
                        <div style="margin-top:20px; display:inline-flex; gap:10px; flex-wrap:wrap; justify-content:center;">
                            <button type="button" class="ftm-btn ftm-btn-warning ftm-btn-sm" id="ftm-btn-convert-leave-to-work">
                                <span>✏️ Applied by Mistake? Switch to Work Deliverables</span>
                            </button>
                            ${isManagerOrAdmin ? `
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm text-rose" id="ftm-btn-reset-leave-entry" style="border-color:rgba(244,63,94,0.4);">
                                    <span>↺ Reset Mistake Application</span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `);

        $('#ftm-btn-convert-leave-to-work').off('click').on('click', function (e) {
            e.preventDefault();
            r.report_type = 'work';
            $('#ftm-modal-standup-title').text('Update & Resubmit Daily Progress Report');
            wrap.empty();
            self.addStandupItemRow();
            $('#ftm-btn-submit-standup').show();
        });

        $('#ftm-btn-reset-leave-entry').off('click').on('click', function (e) {
            e.preventDefault();
            if (!confirm('Reset this mistaken leave report? The member will be able to file their regular work deliverables.')) return;
            $('#ftm-modal-standup').addClass('hidden');
            self.reviewReportAction(r.id, 'reset');
        });

        $('#ftm-btn-submit-standup').hide();
        $('#ftm-modal-standup').removeClass('hidden');
        return;
    }







    // 3. Regular Work Report View & Edit



    $('#ftm-btn-submit-standup').show().text('Resubmit Report to Leader');



    $('#ftm-modal-standup-title').text('Update & Resubmit Daily Progress Report');







    this.api('projects?view_filter=my&per_page=50').then(res => {



        self.state.myActiveProjects = res.items || [];



        if (r.items && r.items.length > 0) {



            r.items.forEach(it => {



                self.addStandupProjectItem(it);



            });



        } else {



            self.addStandupProjectItem();



        }



        $('#ftm-modal-standup').removeClass('hidden');



    }).catch(err => {



        self.toast('Error fetching assigned projects: ' + (err.message || 'Unknown'), 'error');



    });



},







addStandupProjectItem(itemData = null) {



    const wrap = $('#ftm-standup-items-wrap');



    const idx = wrap.find('.ftm-standup-item-box').length + 1;



    const projects = this.state.myActiveProjects || [];







    const isCustom = itemData && (parseInt(itemData.project_id) === 0 || itemData.project_id === 'custom' || (!itemData.project_id && itemData.client_name));



    const customClient = isCustom ? (itemData.client_name || '') : '';



    const customOrder = isCustom ? (itemData.order_id || '') : '';







    let optHtml = '<option value="0">-- Select Assigned Project --</option>';



    projects.forEach(p => {



        const isSelected = !isCustom && itemData && (parseInt(itemData.project_id) === parseInt(p.id) || (itemData.order_id && p.order_id === itemData.order_id));



        optHtml += `<option value="${p.id}" data-order="${ftmEsc(p.order_id)}" data-client="${ftmEsc(p.client_name)}" ${isSelected ? 'selected' : ''}>#${ftmEsc(p.order_id)} - ${ftmEsc(p.client_name)} (${p.status_label || p.status})</option>`;



    });







    // If itemData project is not in active projects, add it as an option so selection is retained



    if (!isCustom && itemData && itemData.project_id && !projects.some(p => parseInt(p.id) === parseInt(itemData.project_id))) {



        optHtml += `<option value="${itemData.project_id}" data-order="${ftmEsc(itemData.order_id)}" data-client="${ftmEsc(itemData.client_name)}" selected>#${ftmEsc(itemData.order_id)} - ${ftmEsc(itemData.client_name)} (Logged Project)</option>`;



    }







    optHtml += `<option value="custom" ${isCustom ? 'selected' : ''}> Old Client / Custom / Maintenance Task (Unlisted)</option>`;







    const completed = itemData ? this.decodeHtml(itemData.tasks_completed || '') : '';



    const wip = itemData ? this.decodeHtml(itemData.work_in_progress || '') : '';



    const plan = itemData ? this.decodeHtml(itemData.next_day_plan || '') : '';



    const hours = itemData ? (parseFloat(itemData.hours_spent) || 0.0) : 0.0;



    const hoursDisplay = hours > 0 ? this.formatHoursDisplay(hours) : '';



    const percent = itemData ? (parseInt(itemData.progress_percent) || 0) : 70;



    const blockerDesc = itemData ? this.decodeHtml(itemData.blocker_description || '') : '';







    const itemHtml = `



                <div class="ftm-standup-item-box" data-index="${idx}" style="background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:12px; padding:18px;">



                    <div class="ftm-standup-item-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">



                        <div style="display:flex; align-items:center; gap:8px;">



                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">Project Item #${idx}</span>



                        </div>



                        ${idx > 1 ? `<button type="button" class="ftm-btn ftm-btn-danger ftm-btn-xs ftm-remove-standup-item" style="padding:3px 8px; font-size:11px;">&times; Remove Item</button>` : ''}



                    </div>







                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; margin-bottom:14px;">



                        <div>



                            <label class="ftm-form-label" style="font-size:12px; font-weight:600; margin-bottom:6px; display:block;">Assigned Project <span class="text-rose">*</span></label>



                            <select class="ftm-form-control ftm-standup-project-select" required>



                                ${optHtml}



                            </select>



                        </div>



                        <div>



                            <label class="ftm-form-label" style="font-size:12px; font-weight:600; margin-bottom:6px; display:block;">Work Hours <span class="text-rose">*</span></label>
                            <label class="ftm-form-label" style="font-size:12px; font-weight:600; margin-bottom:6px; display:block;">Work Hours <span class="text-rose">*</span> <span class="ftm-standup-hours-live-preview ftm-badge bg-blue-soft text-blue ftm-badge-xs hidden" style="margin-left:6px; font-weight:700;"></span></label>



                            <input type="text" class="ftm-form-control ftm-standup-hours" placeholder="e.g. 2h 30m or 2.5" value="${hoursDisplay || (hours > 0 ? hours : '')}">



                        </div>



                    </div>







                    <!-- Custom Project Row -->



                    <div class="ftm-standup-custom-row ${isCustom ? '' : 'hidden'}" style="margin-bottom:14px; padding:12px; background:var(--ftm-bg, #f8fafc); border:1px dashed var(--ftm-border); border-radius:10px;">



                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">



                            <div>



                                <label class="ftm-form-label" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block;">Old / Unlisted Client Name <span class="text-rose">*</span></label>



                                <input type="text" class="ftm-form-control ftm-input-sm ftm-standup-custom-client" placeholder="e.g. Past Client / External Client" value="${customClient}">



                            </div>



                            <div>



                                <label class="ftm-form-label" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block;">Order or Ticket ID (Optional)</label>



                                <input type="text" class="ftm-form-control ftm-input-sm ftm-standup-custom-order" placeholder="e.g. #FO-PREV / Maintenance" value="${customOrder}">



                            </div>



                        </div>



                    </div>







                    <!-- Modern 4 Quadrants Grid (Reference B & C) -->



                    <div class="ftm-standup-quadrants-grid" style="margin-bottom:14px;">



                        <!-- Quadrant 1: Completed -->



                        <div class="ftm-quadrant-card ftm-quadrant-completed">



                            <div class="ftm-quadrant-header">



                                <span class="ftm-quadrant-icon bg-emerald-soft text-emerald">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>



                                </span>



                                <span class="ftm-quadrant-title">What is completed? <span class="text-rose">*</span></span>



                            </div>



                            <textarea class="ftm-quadrant-input ftm-standup-completed" rows="3" placeholder="Describe deliverables finished today...">${completed}</textarea>



                        </div>







                        <!-- Quadrant 2: WIP -->



                        <div class="ftm-quadrant-card ftm-quadrant-wip">



                            <div class="ftm-quadrant-header">



                                <span class="ftm-quadrant-icon bg-amber-soft text-amber">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 10"></polyline></svg>



                                </span>



                                <span class="ftm-quadrant-title">What are you working on now?</span>



                            </div>



                            <textarea class="ftm-quadrant-input ftm-standup-wip" rows="3" placeholder="What is currently open on your bench...">${wip}</textarea>



                        </div>







                        <!-- Quadrant 3: Blockers -->



                        <div class="ftm-quadrant-card ftm-quadrant-blockers">



                            <div class="ftm-quadrant-header">



                                <span class="ftm-quadrant-icon bg-rose-soft text-rose">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>



                                </span>



                                <span class="ftm-quadrant-title">Any blockers or issues?</span>



                            </div>



                            <textarea class="ftm-quadrant-input ftm-standup-blocker-desc" rows="3" placeholder="Explain any impediments, dependencies, or client delays...">${blockerDesc}</textarea>



                        </div>







                        <!-- Quadrant 4: Tomorrow's Plan -->



                        <div class="ftm-quadrant-card ftm-quadrant-plan">



                            <div class="ftm-quadrant-header">



                                <span class="ftm-quadrant-icon bg-blue-soft text-blue">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                </span>



                                <span class="ftm-quadrant-title">Next Plan / Tomorrow's Plan</span>



                            </div>



                            <textarea class="ftm-quadrant-input ftm-standup-plan" rows="3" placeholder="Planned deliverables for tomorrow...">${plan}</textarea>



                        </div>



                    </div>







                    <!-- Est. Project Progress Slider & Presets -->



                    <div style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:12px 14px;">



                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">



                            <label class="ftm-form-label" style="font-size:12px; font-weight:600; margin:0;">Est. Project Progress</label>



                            <span class="ftm-badge bg-blue-soft text-blue ftm-standup-prog-badge" style="font-size:13px; font-weight:700; padding:3px 8px;">${percent}%</span>



                        </div>



                        <input type="range" class="ftm-range-slider ftm-standup-percent" min="0" max="100" step="5" value="${percent}" style="width:100%; accent-color:#2563eb; cursor:pointer;">



                        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--ftm-text-muted); margin-top:2px;">



                            <span>0%</span>



                            <span>25%</span>



                            <span>50%</span>



                            <span>75%</span>



                            <span>100%</span>



                        </div>



                    </div>



                </div>



            `;



    const el = $(itemHtml);



    el.find('.ftm-standup-project-select').on('change', function () {



        const val = $(this).val();



        el.find('.ftm-standup-custom-row').toggleClass('hidden', val !== 'custom');



    });



    el.find('.ftm-standup-percent').on('input change', function () {



        el.find('.ftm-standup-prog-badge').text($(this).val() + '%');



    });

    el.find('.ftm-standup-hours').on('input', function () {
        const val = $(this).val().trim();
        const badge = el.find('.ftm-standup-hours-live-preview');
        if (!val) {
            badge.addClass('hidden').text('');
            return;
        }
        const parsed = self.parseHours(val);
        if (parsed > 0) {
            badge.removeClass('hidden').text('= ' + self.formatHoursDisplay(parsed));
        } else {
            badge.addClass('hidden').text('');
        }
    }).on('blur', function () {
        const val = $(this).val().trim();
        if (!val) return;
        const parsed = self.parseHours(val);
        if (parsed > 0) {
            $(this).val(self.formatHoursDisplay(parsed));
            el.find('.ftm-standup-hours-live-preview').addClass('hidden').text('');
        }
    });



    el.find('.ftm-remove-standup-item').on('click', function () {



        el.remove();



    });



    wrap.append(el);



},







saveDailyStandupForm() {



    const self = this;



    const date = $('#ftm-standup-date').val();



    const repId = parseInt($('#ftm-standup-report-id').val()) || undefined;



    const items = [];







    let validationError = false;



    $('#ftm-standup-items-wrap .ftm-standup-item-box').each(function () {



        const box = $(this);



        const select = box.find('.ftm-standup-project-select option:selected');



        const selVal = box.find('.ftm-standup-project-select').val();



        const pId = parseInt(selVal) || 0;



        const hours = self.parseHours(box.find('.ftm-standup-hours').val());



        const completed = box.find('.ftm-standup-completed').val().trim();



        const wip = box.find('.ftm-standup-wip').val().trim();



        const plan = box.find('.ftm-standup-plan').val().trim();



        const blockerDesc = box.find('.ftm-standup-blocker-desc').val().trim();



        const progress = parseInt(box.find('.ftm-standup-percent').val()) || 0;







        if (hours <= 0) {
            self.toast('Every task deliverable requires a valid time entry (hours spent). Reports cannot be submitted without time entered.', 'warning');
            box.find('.ftm-standup-hours').focus();
            validationError = true;
            return false;
        }

        if (!completed && !wip) {



            self.toast('Please write deliverables completed or in progress for each project item.', 'warning');



            validationError = true;



            return false;



        }







        if (selVal === 'custom') {



            const customClient = box.find('.ftm-standup-custom-client').val().trim();



            const customOrder = box.find('.ftm-standup-custom-order').val().trim();



            if (!customClient) {



                self.toast('Please enter the client name for unlisted/maintenance work.', 'error');



                validationError = true;



                return false;



            }



            items.push({



                project_id: 0,



                order_id: customOrder || 'MAINTENANCE',



                client_name: customClient,



                tasks_completed: completed,



                work_in_progress: wip,



                next_day_plan: plan,



                has_blocker: blockerDesc.length > 0 ? 1 : 0,



                blocker_description: blockerDesc,



                hours_spent: hours,



                progress_percent: progress



            });



        } else if (pId > 0) {



            items.push({



                project_id: pId,



                order_id: select.data('order') || '',



                client_name: select.data('client') || '',



                tasks_completed: completed,



                work_in_progress: wip,



                next_day_plan: plan,



                has_blocker: blockerDesc.length > 0 ? 1 : 0,



                blocker_description: blockerDesc,



                hours_spent: hours,



                progress_percent: progress



            });



        } else {



            self.toast('Please select an assigned project or choose custom.', 'warning');



            validationError = true;



            return false;



        }



    });







    if (validationError) return;

    let sumHours = 0;
    items.forEach(it => { sumHours += parseFloat(it.hours_spent || 0); });
    if (sumHours > 8.00) {
        this.toast(`Daily regular report cannot exceed 8.00 hours (attempted: ${self.formatHoursDisplay(sumHours)}). Any work beyond 8 hours must be tracked via the Work Tracker tool in Extra Time mode.`, 'error');
        return;
    }







    if (items.length === 0) {



        this.toast('Please select at least one assigned project.', 'error');



        return;



    }







    const submitBtn = $('#ftm-btn-submit-standup');



    const origText = submitBtn.text();



    submitBtn.prop('disabled', true).text('Submitting...');







    this.api("daily-reports", "POST", {
        report_id: repId,
        report_date: date,
        status: "submitted",
        replace_all: !!self._isEditingFullReport,
        items: items,
        completed_task_ids: self._standupCompletedTaskIds || []
    }).then(res => {



        submitBtn.prop('disabled', false).text(origText);



        $('#ftm-modal-standup').addClass('hidden');



        self.toast('Daily progress report submitted successfully!', 'success');



        self.loadDailyReportsView(date);



    }).catch(err => {



        submitBtn.prop('disabled', false).text(origText);



        self.toast(err.message || 'Error updating report', 'error');



    });



},







// =========================================================================



// REFERENCE B: MY DAILY STANDUP WORKSPACE (IMAGE 2)



// =========================================================================



initStandupWorkspace() {



    const self = this;



    if (this._standupWorkspaceInitialized) return;



    this._standupWorkspaceInitialized = true;







    // Date navigation



    $('#ftm-standup-date-prev').on('click', function (e) {



        e.preventDefault();



        self.shiftStandupDate(-1);



    });



    $('#ftm-standup-date-next').on('click', function (e) {



        e.preventDefault();



        self.shiftStandupDate(1);



    });



    $('#ftm-standup-date-display-wrap').on('click', function (e) {



        const picker = $('#ftm-standup-date-input')[0];



        if (picker && typeof picker.showPicker === 'function') {



            picker.showPicker();



        }



    });



    $('#ftm-standup-date-input').on('change', function () {



        const val = $(this).val();



        if (val) {



            self.setStandupDate(val);



        }



    });







    // Project selection toggle & prefill existing deliverables for selected date

    self.bindWorkspaceSearchableProjectSelect();

    $('#ftm-ws-select-project').on('change', function () {

        const val = $(this).val();
        self.syncWorkspaceProjectPickerLabel();



        if (val === 'custom') {



            $('#ftm-ws-custom-project-wrap').removeClass('hidden');



        } else {



            $('#ftm-ws-custom-project-wrap').addClass('hidden');



        }







        const targetDate = $('#ftm-standup-date-input').val() || new Date().toISOString().split('T')[0];



        const todayRep = (self.state.myDailyReports || []).find(r => r.report_date === targetDate);







        if (todayRep && todayRep.items && val && val !== 'custom') {



            const pId = parseInt(val);



            const existingItem = todayRep.items.find(it => parseInt(it.project_id) === pId);



            if (existingItem) {



                $('#ftm-ws-work-hours').val(self.formatHoursDisplay(existingItem.hours_spent));



                $('#ftm-ws-completed').val(self.decodeHtml(existingItem.tasks_completed || ''));



                $('#ftm-ws-wip').val(self.decodeHtml(existingItem.work_in_progress || ''));



                $('#ftm-ws-blockers').val(self.decodeHtml(existingItem.blocker_description || ''));



                $('#ftm-ws-next-plan').val(self.decodeHtml(existingItem.next_day_plan || ''));



                const prog = existingItem.progress_percent !== undefined ? existingItem.progress_percent : 70;



                $('#ftm-ws-progress-percent').val(prog).trigger('input');



                return;



            }



        }







        if (val && val !== 'custom') {



            const pId = parseInt(val);



            const pObj = (self.state.assignedProjects || []).find(p => parseInt(p.id) === pId);



            const prog = pObj ? (parseInt(pObj.progress_percent !== undefined ? pObj.progress_percent : (pObj.progress || 0))) : 0;



            $('#ftm-ws-progress-percent').val(prog).trigger('input');



            $('#ftm-ws-work-hours').val('');



            $('#ftm-ws-completed').val('');



            $('#ftm-ws-wip').val('');



            $('#ftm-ws-blockers').val('');



            $('#ftm-ws-next-plan').val('');



        }



    });







    // Progress slider



    $('#ftm-ws-progress-percent').on('input', function () {



        $('#ftm-ws-progress-val').text($(this).val() + '%');



    });

    // Live hours preview & normalization on input and blur
    $('#ftm-ws-work-hours').on('input', function () {
        const val = $(this).val().trim();
        const badge = $('#ftm-ws-hours-live-preview');
        if (!val) {
            badge.addClass('hidden').text('');
            return;
        }
        const parsed = self.parseHours(val);
        if (parsed > 0) {
            badge.removeClass('hidden').text('= ' + self.formatHoursDisplay(parsed));
        } else {
            badge.addClass('hidden').text('');
        }
    }).on('blur', function () {
        const val = $(this).val().trim();
        if (!val) return;
        const parsed = self.parseHours(val);
        if (parsed > 0) {
            $(this).val(self.formatHoursDisplay(parsed));
            $('#ftm-ws-hours-live-preview').addClass('hidden').text('');
        }
    });







    // Attachment picker



    $('#ftm-ws-attachment').on('change', function () {



        const files = this.files;



        if (files && files.length > 0) {



            const names = Array.from(files).map(f => f.name).join(', ');



            $('#ftm-ws-attachment-name').text(names.length > 30 ? names.substring(0, 30) + '...' : names);



        } else {



            $('#ftm-ws-attachment-name').text('');



        }



    });







    // Smooth jump to history



    $('#ftm-btn-jump-history').on('click', function (e) {



        e.preventDefault();



        const hist = document.getElementById('ftm-standup-history-section');



        if (hist) {



            hist.scrollIntoView({ behavior: 'smooth', block: 'start' });



        }



    });







    // Form Draft Button



    $('#ftm-ws-btn-draft').on('click', function (e) {



        e.preventDefault();



        self.saveStandupWorkspaceForm('draft');



    });







    // Form Submit Button



    $('#ftm-standup-workspace-form').on('submit', function (e) {



        e.preventDefault();



        self.saveStandupWorkspaceForm('submitted');



    });







    // Report Type Toggle Switcher (Work, Weekend, Leave)



    $(document).on('click', '.ftm-report-type-toggle', function (e) {



        e.preventDefault();



        const type = $(this).data('type');



        self.switchStandupReportType(type);



    });







    // Leave duration auto-calculation



    $('#ftm-ws-leave-start, #ftm-ws-leave-end').on('change', function () {



        self.updateLeaveDurationDisplay();



    });







    // Confirm Weekend button handler



    $('#ftm-ws-btn-confirm-weekend').on('click', function (e) {



        e.preventDefault();



        const targetDate = $('#ftm-standup-date-input').val() || new Date().toISOString().split('T')[0];



        const btn = $(this);



        const orig = btn.html();



        btn.prop('disabled', true).text('Confirming Weekend...');







        self.api('daily-reports', 'POST', {



            report_type: 'weekend',



            report_date: targetDate



        }).then(res => {



            btn.prop('disabled', false).html(orig);



            self.toast('Weekend / Off Day recorded successfully!', 'success');



            self.loadDailyReportsView(targetDate);



        }).catch(err => {



            btn.prop('disabled', false).html(orig);



            self.toast(err.message || 'Error recording weekend', 'error');



        });



    });







    // Submit Leave Notice button handler



    $('#ftm-ws-btn-submit-leave').on('click', function (e) {



        e.preventDefault();



        const targetDate = $('#ftm-standup-date-input').val() || new Date().toISOString().split('T')[0];



        const startDate = $('#ftm-ws-leave-start').val();



        const endDate = $('#ftm-ws-leave-end').val();



        const leaveType = $('#ftm-ws-leave-type').val();



        const reason = $('#ftm-ws-leave-reason').val().trim();







        if (!startDate || !endDate) {



            self.toast('Please select both start and end dates.', 'warning');



            return;



        }



        if (!reason) {



            self.toast('Please write a reason or details for management.', 'warning');



            $('#ftm-ws-leave-reason').focus();



            return;



        }







        const btn = $(this);



        const orig = btn.html();



        btn.prop('disabled', true).text('Submitting Leave...');







        self.api('daily-reports', 'POST', {



            report_type: 'on_leave',



            report_date: targetDate,



            leave_start_date: startDate,



            leave_end_date: endDate,



            leave_type: leaveType,



            leave_reason: reason



        }).then(res => {



            btn.prop('disabled', false).html(orig);



            self.toast('Leave notice submitted and approved for manager review!', 'success');



            self.loadDailyReportsView(targetDate);



        }).catch(err => {



            btn.prop('disabled', false).html(orig);



            self.toast(err.message || 'Error submitting leave notice', 'error');



        });



    });







    // History table filters



    $('#ftm-history-filter-project, #ftm-history-filter-range').on('change', function () {



        self.renderStandupHistoryTable();



    });







    // View previous reports modal / detail trigger



    $(document).on('click', '.ftm-btn-view-standup-detail', function (e) {



        e.preventDefault();



        const reportId = parseInt($(this).data('report-id'));



        if (reportId) {



            self.openDailyStandupModalForEdit(reportId);



        }



    });



},







shiftStandupDate(days) {



    const currentIso = $('#ftm-standup-date-input').val() || new Date().toISOString().split('T')[0];



    const todayIso = new Date().toLocaleDateString('en-CA');



    if (days > 0 && currentIso >= todayIso) return;



    const d = new Date(currentIso + 'T12:00:00');



    d.setDate(d.getDate() + days);



    let nextIso = d.toISOString().split('T')[0];



    if (nextIso > todayIso) nextIso = todayIso;



    this.setStandupDate(nextIso);



},







setStandupDate(isoDate) {



    const todayIso = new Date().toLocaleDateString('en-CA');



    if (isoDate > todayIso) isoDate = todayIso;



    $('#ftm-standup-date-input').val(isoDate);







    // Block going to future dates: disable next button when on today or later



    const nextBtn = $('#ftm-standup-date-next');



    if (isoDate >= todayIso) {



        nextBtn.attr('disabled', true).css({ opacity: '0.35', cursor: 'not-allowed', pointerEvents: 'none' });



    } else {



        nextBtn.removeAttr('disabled').css({ opacity: '1', cursor: 'pointer', pointerEvents: 'auto' });



    }







    const d = new Date(isoDate + 'T12:00:00');



    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };



    $('#ftm-standup-current-date-label').text(d.toLocaleDateString('en-US', options));



    $('#ftm-ws-weekend-date-label').text(d.toLocaleDateString('en-US', options));







    // Set leave date default to this date if empty or previous



    if (!$('#ftm-ws-leave-start').val()) $('#ftm-ws-leave-start').val(isoDate);



    if (!$('#ftm-ws-leave-end').val() || $('#ftm-ws-leave-end').val() < isoDate) {



        $('#ftm-ws-leave-end').val(isoDate);



    }



    this.updateLeaveDurationDisplay();



    this.loadDailyReportsView(isoDate);



},







switchStandupReportType(type) {



    $('.ftm-report-type-toggle').removeClass('active');



    $(`.ftm-report-type-toggle[data-type="${type}"]`).addClass('active');



    $('#ftm-ws-selected-type').val(type);







    if (type === 'work') {



        $('#ftm-ws-work-section').removeClass('hidden');



        $('#ftm-ws-weekend-section').addClass('hidden');



        $('#ftm-ws-leave-section').addClass('hidden');



        $('#ftm-ws-select-project, #ftm-ws-work-hours, #ftm-ws-completed').prop('required', true);



    } else if (type === 'weekend') {



        $('#ftm-ws-work-section').addClass('hidden');



        $('#ftm-ws-weekend-section').removeClass('hidden');



        $('#ftm-ws-leave-section').addClass('hidden');



        $('#ftm-ws-select-project, #ftm-ws-work-hours, #ftm-ws-completed').prop('required', false);



    } else if (type === 'on_leave') {



        $('#ftm-ws-work-section').addClass('hidden');



        $('#ftm-ws-weekend-section').addClass('hidden');



        $('#ftm-ws-leave-section').removeClass('hidden');



        $('#ftm-ws-select-project, #ftm-ws-work-hours, #ftm-ws-completed').prop('required', false);



    }



},







updateLeaveDurationDisplay() {



    const s = $('#ftm-ws-leave-start').val();



    const e = $('#ftm-ws-leave-end').val();



    if (!s || !e) return;



    const sDate = new Date(s + 'T12:00:00');



    const eDate = new Date(e + 'T12:00:00');



    if (eDate < sDate) {



        $('#ftm-ws-leave-end').val(s);



        $('#ftm-ws-leave-duration-badge').text('1 day');



        return;



    }



    const diff = Math.round((eDate - sDate) / (1000 * 60 * 60 * 24)) + 1;



    $('#ftm-ws-leave-duration-badge').text(`${diff} day${diff > 1 ? 's' : ''}`);



},







parseHours(str) {
    if (!str && str !== 0) return 0;
    str = String(str).toLowerCase().trim();
    if (!str) return 0;

    // 1. Colon format: "2:30", "02:30", "0:45", ":30", ":45"
    const colonMatch = str.match(/^(\d+)?\s*:\s*(\d{1,2})$/);
    if (colonMatch) {
        const h = colonMatch[1] ? parseInt(colonMatch[1], 10) : 0;
        const m = parseInt(colonMatch[2], 10) || 0;
        return parseFloat((h + m / 60).toFixed(2));
    }

    // 2. Combined hours + minutes in one string: "2h30m", "2h 30m", "2h30", "2h 30", "2 hrs 30 mins", "2 hours 30 min", "2hr 30"
    const combinedMatch = str.match(/^(\d+(?:\.\d+)?)\s*(?:h|hr|hrs|hours?)\s*(\d+(?:\.\d+)?)\s*(?:m|min|mins|minutes?)?$/i);
    if (combinedMatch) {
        const h = parseFloat(combinedMatch[1]) || 0;
        const m = parseFloat(combinedMatch[2]) || 0;
        return parseFloat((h + m / 60).toFixed(2));
    }

    // 3. Explicit h only: "2h", "2.5h", "2 hours", "1 hr"
    const hOnlyMatch = str.match(/^(\d+(?:\.\d+)?)\s*(?:h|hr|hrs|hours?)$/i);
    if (hOnlyMatch) {
        return parseFloat(parseFloat(hOnlyMatch[1]).toFixed(2));
    }

    // 4. Explicit m only: "30m", "45 mins", "15 minutes", "30 min"
    const mOnlyMatch = str.match(/^(\d+(?:\.\d+)?)\s*(?:m|min|mins|minutes?)$/i);
    if (mOnlyMatch) {
        return parseFloat((parseFloat(mOnlyMatch[1]) / 60).toFixed(2));
    }

    // 5. If user wrote "2.30" with 2 digits (e.g. .30 for 30m, .15 for 15m, .45 for 45m)
    const dotTimeMatch = str.match(/^(\d+)\.(\d{2})$/);
    if (dotTimeMatch) {
        const h = parseInt(dotTimeMatch[1], 10);
        const m = parseInt(dotTimeMatch[2], 10);
        if (m === 30) return parseFloat((h + 0.5).toFixed(2));
        if (m === 15) return parseFloat((h + 0.25).toFixed(2));
        if (m === 45) return parseFloat((h + 0.75).toFixed(2));
        if (m < 60 && m % 5 === 0 && m !== 25 && m !== 50 && m !== 75) {
            return parseFloat((h + m / 60).toFixed(2));
        }
    }

    // 6. Match arbitrary h and m in text
    const hMatch = str.match(/(\d+(?:\.\d+)?)\s*(?:h|hr|hrs|hours?)/);
    const mMatch = str.match(/(\d+(?:\.\d+)?)\s*(?:m|min|mins|minutes?)/);
    if (hMatch || mMatch) {
        let total = 0;
        if (hMatch) total += parseFloat(hMatch[1]) || 0;
        if (mMatch) total += (parseFloat(mMatch[1]) || 0) / 60;
        return parseFloat(total.toFixed(2));
    }

    // 7. Generic numbers: "2.5", "2", "30", "45"
    const num = parseFloat(str);
    if (!isNaN(num)) {
        // If single whole number > 24, user typed minutes (e.g. 30, 45, 90 mins)
        if (num > 24 && !str.includes('.')) {
            return parseFloat((num / 60).toFixed(2));
        }
        return parseFloat(num.toFixed(2));
    }

    return 0;
},

formatHoursDisplay(hoursNum) {
    const totalMins = Math.round((parseFloat(hoursNum) || 0) * 60);
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return `${h}h ${m < 10 ? '0' + m : m}m`;
},







decodeHtml(str) {



    if (!str) return '';



    let val = String(str);



    // Peel away any single or double encoded entities (&amp;#039; -> &#039; -> ')



    for (let i = 0; i < 2; i++) {



        if (!val.includes('&')) break;



        const txt = document.createElement('textarea');



        txt.innerHTML = val;



        val = txt.value;



    }



    return val;



},







escapeHtml(str) {



    if (!str) return '';



    // Decode entities first to preserve true apostrophes and clean text



    const decoded = this.decodeHtml(str);



    return String(decoded)



        .replace(/&/g, '&amp;')



        .replace(/</g, '&lt;')



        .replace(/>/g, '&gt;')



        .replace(/"/g, '&quot;')



        .replace(/'/g, '&#39;');



},







saveStandupWorkspaceForm(status = 'submitted') {



    const self = this;



    const submitBtn = $('#ftm-ws-btn-submit');



    const draftBtn = $('#ftm-ws-btn-draft');



    const origSubmitText = submitBtn.html();







    const projectVal = $('#ftm-ws-select-project').val();



    if (!projectVal) {



        self.toast('Please select a project or choose custom project.', 'warning');



        $('#ftm-ws-proj-picker-btn').trigger('click');



        return;



    }







    const rawHours = $('#ftm-ws-work-hours').val().trim();



    const hoursSpent = self.parseHours(rawHours);



    if (hoursSpent <= 0 && status === 'submitted') {
        self.toast('Every task deliverable requires a valid time entry (hours spent). Reports cannot be submitted without time entered.', 'warning');
        $('#ftm-ws-work-hours').focus();
        return;
    }

    if (hoursSpent > 8.00 && status === 'submitted') {
        self.toast('Daily regular report cannot exceed 8.00 hours. For any work beyond 8 hours, you must use the Work Tracker tool in Extra Time mode.', 'error');
        $('#ftm-ws-work-hours').focus();
        return;
    }







    const completed = $('#ftm-ws-completed').val().trim();



    const wip = $('#ftm-ws-wip').val().trim();



    const blockers = $('#ftm-ws-blockers').val().trim();



    const nextPlan = $('#ftm-ws-next-plan').val().trim();



    const progress = parseInt($('#ftm-ws-progress-percent').val()) || 0;



    const reportDate = $('#ftm-standup-date-input').val() || new Date().toISOString().split('T')[0];



    const reportId = parseInt($('#ftm-ws-report-id').val()) || 0;







    if (!completed && !wip && status === 'submitted') {



        self.toast('Please write deliverables completed or in progress for this project.', 'warning');



        $('#ftm-ws-completed').focus();



        return;



    }







    let finalTasksCompleted = completed;







    let projectId = 0;



    let orderId = '';



    let clientName = '';







    if (projectVal === 'custom') {



        projectId = 0;



        clientName = $('#ftm-ws-custom-title').val().trim() || 'Custom Project / Old Client';



        orderId = $('#ftm-ws-custom-order').val().trim() || 'CUSTOM';



    } else {



        projectId = parseInt(projectVal);



        const assignedProj = (self.state.assignedProjects || []).find(p => parseInt(p.id) === projectId) || {};



        clientName = assignedProj.client_name || 'Client';



        orderId = assignedProj.order_id || `#ID-${projectId}`;



    }







    const item = {



        project_id: projectId,



        order_id: orderId,



        client_name: clientName,



        tasks_completed: finalTasksCompleted.trim(),



        work_in_progress: wip,



        has_blocker: blockers.length > 0 ? 1 : 0,



        blocker_description: blockers,



        next_day_plan: nextPlan,



        hours_spent: hoursSpent,



        progress_percent: progress



    };







    const payload = {



        report_id: reportId > 0 ? reportId : undefined,



        report_date: reportDate,



        status: status,



        replace_all: false,



        items: [item]



    };







    submitBtn.prop('disabled', true).text('Submitting...');



    draftBtn.prop('disabled', true);







    this.api('daily-reports', 'POST', payload).then(res => {



        submitBtn.prop('disabled', false).html(origSubmitText);



        draftBtn.prop('disabled', false);



        self.toast(status === 'draft' ? 'Daily report saved as draft!' : 'Daily progress report submitted successfully!', 'success');







        // Clear form inputs



        $('#ftm-ws-report-id').val('');



        $('#ftm-ws-work-hours').val('');



        $('#ftm-ws-summary').val('');



        $('#ftm-ws-completed').val('');



        $('#ftm-ws-wip').val('');



        $('#ftm-ws-blockers').val('');



        $('#ftm-ws-next-plan').val('');



        $('#ftm-ws-custom-title').val('');



        $('#ftm-ws-custom-order').val('');



        $('#ftm-ws-custom-project-wrap').addClass('hidden');



        $('#ftm-ws-attachment-name').text('');







        self.loadDailyReportsView(reportDate);



    }).catch(err => {



        submitBtn.prop('disabled', false).html(origSubmitText);



        draftBtn.prop('disabled', false);



        self.toast(err.message || 'Error saving daily report', 'error');



    });



},

bindWorkspaceSearchableProjectSelect() {
    const self = this;
    const $wrap = $('#ftm-ws-proj-picker-wrap');
    if (!$wrap.length || $wrap.data('ftmBound')) return;
    $wrap.data('ftmBound', true);

    const $btn = $('#ftm-ws-proj-picker-btn');
    const $drop = $('#ftm-ws-proj-dropdown');
    const $search = $('#ftm-ws-proj-search');

    $btn.on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const isHidden = $drop.hasClass('hidden');
        if (isHidden) {
            $drop.removeClass('hidden');
            $btn.attr('aria-expanded', 'true');
            $search.val('');
            self.renderWorkspaceProjectOptions('');
            setTimeout(() => $search.trigger('focus'), 30);
        } else {
            $drop.addClass('hidden');
            $btn.attr('aria-expanded', 'false');
        }
    });

    $search.on('input', function () {
        self.renderWorkspaceProjectOptions($(this).val() || '');
    }).on('click', function (e) {
        e.stopPropagation();
    }).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $drop.addClass('hidden');
            $btn.attr('aria-expanded', 'false');
            $btn.trigger('focus');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const $first = $('#ftm-ws-proj-options .ftm-search-select-opt').first();
            if ($first.length) $first.trigger('click');
        }
    });

    $(document).on('click.ftmWsProjPicker', function (e) {
        if (!$(e.target).closest('#ftm-ws-proj-picker-wrap').length) {
            $drop.addClass('hidden');
            $btn.attr('aria-expanded', 'false');
        }
    });
},

syncWorkspaceProjectPickerLabel() {
    const $select = $('#ftm-ws-select-project');
    const val = $select.val();
    const $selected = $select.find('option:selected');
    const text = ($selected.length && val) ? $selected.text().trim() : '-- Choose Assigned Project --';
    $('#ftm-ws-proj-picker-label').text(text);
    $('#ftm-ws-proj-options .ftm-search-select-opt').removeClass('is-selected');
    if (val) {
        $(`#ftm-ws-proj-options .ftm-search-select-opt[data-value="${String(val).replace(/"/g, '\\"')}"]`).addClass('is-selected');
    }
},

renderWorkspaceProjectOptions(query) {
    const self = this;
    const q = String(query || '').trim().toLowerCase();
    const $select = $('#ftm-ws-select-project');
    const currentVal = $select.val();
    const $container = $('#ftm-ws-proj-options');
    if (!$container.length) return;

    let html = '';
    let matchCount = 0;

    $select.find('option').each(function () {
        const val = $(this).attr('value') || '';
        const label = $(this).text().trim();
        const meta = ($(this).attr('data-search') || label).toLowerCase();
        if (!val) return; // skip empty prompt
        const isCustom = val === 'custom';
        if (!isCustom && q && !meta.includes(q)) return;
        if (isCustom && q && !'custom other old client internal task'.includes(q) && matchCount > 0) {
            // Always allow custom when query matches custom or zero project results
        }
        matchCount++;
        const selectedCls = String(currentVal) === String(val) ? ' is-selected' : '';
        const customCls = isCustom ? ' is-custom' : '';
        html += `<button type="button" class="ftm-search-select-opt${selectedCls}${customCls}" data-value="${ftmEsc(val)}"><span>${ftmEsc(label)}</span></button>`;
    });

    if (matchCount === 0) {
        html = '<div class="ftm-search-select-empty">No matching projects found. Clear search or choose Custom Project below.</div>';
    }

    $container.html(html);
    $container.find('.ftm-search-select-opt').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const picked = $(this).attr('data-value') || '';
        $select.val(picked).trigger('change');
        $('#ftm-ws-proj-dropdown').addClass('hidden');
        $('#ftm-ws-proj-picker-btn').attr('aria-expanded', 'false');
    });
},

loadDailyReportsView(customDate) {



    const self = this;



    this.initStandupWorkspace();







    const targetDate = customDate || $('#ftm-standup-date-input').val() || new Date().toISOString().split('T')[0];







    // 1. Fetch assigned projects for current user only (Strict View Filter)



    this.api('projects?view_filter=my&assigned=1&per_page=250').then(res => {



        const projects = res.items || [];



        self.state.assignedProjects = projects;







        // Populate Project Select Dropdown



        let optionsHtml = '<option value="">-- Choose Assigned Project --</option>';



        if (projects.length > 0) {



            projects.forEach(p => {



                const oid = p.order_id ? ('#' + String(p.order_id).replace(/^#/, '')) : '';
                const prof = p.profile_name ? (' • ' + ftmEsc(p.profile_name)) : '';
                const searchMeta = `${oid} ${p.client_name || ''} ${p.title || ''} ${p.profile_name || ''} ${p.status_label || p.status || ''}`;
                optionsHtml += `<option value="${p.id}" data-search="${ftmEsc(searchMeta)}" data-status="${ftmEsc(p.status_label || p.status || '')}">${oid ? oid + ' - ' : ''}${ftmEsc(p.client_name)}${prof} (${p.status_label || p.status})</option>`;



            });



        }



        optionsHtml += '<option value="custom">+ Other / Custom Project (Old Client / Internal Task)</option>';



        $('#ftm-ws-select-project').html(optionsHtml);
        self.renderWorkspaceProjectOptions('');
        self.syncWorkspaceProjectPickerLabel();







        // Populate Today's Projects Sidebar Widget (Reference B)



        self.renderTodaysProjectsWidget(projects);







        // Compute and render KPIs



        self.updateStandupKPIs(projects, self.state.myDailyReports || [], targetDate);



    }).catch(console.error);







    // 2. Fetch User's Daily Reports History



    this.api('daily-reports?scope=mine').then(reports => {



        self.state.myDailyReports = reports || [];







        // Render Recent Reports Sidebar Widget (Reference B)



        self.renderRecentReportsWidget(reports);







        // Render My Progress History Table (Reference B)



        self.renderStandupHistoryTable();







        // Re-calculate KPIs



        if (self.state.assignedProjects !== undefined) {



            self.updateStandupKPIs(self.state.assignedProjects, reports, targetDate);



        }



    }).catch(console.error);



},







updateStandupKPIs(projects, reports, targetDate) {



    // KPI 1: Assigned Projects (Strict to user's actual active assigned projects)



    const activeAssigned = (projects || []).filter(p => !['delivery_accepted', 'cancelled'].includes(p.status));



    $('#ftm-standup-kpi-assigned').text(activeAssigned.length);







    // Find report for targetDate



    const todayReport = (reports || []).find(r => r.report_date === targetDate);



    let updatedProjectsCount = 0;



    let totalHours = 0;



    let pendingTasksCount = Math.max(0, activeAssigned.length - (todayReport && todayReport.items ? todayReport.items.length : 0));







    if (todayReport && todayReport.items) {



        updatedProjectsCount = todayReport.items.length;



        todayReport.items.forEach(it => {



            totalHours += parseFloat(it.hours_spent || 0);



        });



    }







    $('#ftm-standup-kpi-updated').text(updatedProjectsCount);



    $('#ftm-standup-kpi-hours').text(this.formatHoursDisplay(totalHours));



    $('#ftm-standup-kpi-pending').text(pendingTasksCount);



},







renderTodaysProjectsWidget(projects) {



    const list = $('#ftm-standup-todays-projects-list');



    if (!projects || projects.length === 0) {



        list.html(`



                    <div style="padding:16px; text-align:center; background:var(--ftm-bg, #f8fafc); border-radius:10px; border:1px dashed var(--ftm-border);">



                        <div style="font-size:12px; font-weight:700; color:var(--ftm-text-primary); margin-bottom:4px;">No Assigned Projects Active</div>



                        <div class="ftm-text-muted ftm-text-xs">You currently have no client projects assigned to your desk. Select <strong>+ Other / Custom Project</strong> in the form to log ad-hoc deliverables or old client tasks.</div>



                    </div>



                `);



        return;



    }







    let html = '';



    projects.slice(0, 4).forEach(p => {



        const progressPct = parseInt(p.progress_percent !== undefined ? p.progress_percent : (p.progress !== undefined ? p.progress : 0));



        const statusBadge = p.status === 'in_progress' || p.status === 'wip' ? 'bg-amber-soft text-amber' : (p.status === 'revision' ? 'bg-purple-soft text-purple' : 'bg-emerald-soft text-emerald');



        const progressFillClass = p.status === 'revision' ? 'bg-purple' : (progressPct >= 70 ? 'bg-emerald' : 'bg-blue');







        html += `



                    <div class="ftm-widget-project-row">



                        <div class="ftm-widget-project-top">



                            <div class="ftm-widget-project-title-wrap">



                                <div class="ftm-widget-project-thumb" style="display:flex; align-items:center; justify-content:center; font-weight:700; color:#3b82f6; font-size:12px;">



                                    ${p.client_name ? p.client_name.substring(0, 2).toUpperCase() : 'PR'}



                                </div>



                                <div style="min-width:0;">



                                    <div class="ftm-widget-project-name">${ftmEsc(p.client_name || 'Project')}</div>



                                    <div class="ftm-widget-project-sub">${p.order_id ? '#' + ftmEsc(String(p.order_id).replace(/^#+/, '')) : ''}</div>



                                </div>



                            </div>



                            <div style="display:flex; align-items:center; gap:6px;">



                                <span class="ftm-badge ${statusBadge} ftm-badge-xs">${p.status_label || p.status}</span>



                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-btn-widget-update-proj" data-project-id="${p.id}" data-project-title="${p.client_name || ''}" data-progress="${progressPct}">Update</button>



                            </div>



                        </div>



                        <div class="ftm-widget-project-progress">



                            <div class="ftm-widget-progress-track">



                                <div class="ftm-widget-progress-fill ${progressFillClass}" style="width:${progressPct}%;"></div>



                            </div>



                            <span class="ftm-widget-progress-label">${progressPct}%</span>



                        </div>



                    </div>



                `;



    });







    list.html(html);







    // Bind Update button click in Today's Projects widget



    list.find('.ftm-btn-widget-update-proj').on('click', function (e) {



        e.preventDefault();



        const pId = $(this).data('project-id');



        const prog = parseInt($(this).data('progress') || 0);



        const title = $(this).data('project-title') || 'Project';







        // Sync workspace dropdown & slider



        $('#ftm-ws-select-project').val(pId).trigger('change');



        $('#ftm-ws-progress-percent').val(prog).trigger('input');







        // Open quick progress update modal



        self.openQuickProgressModal(pId, title, prog);



    });



},







openQuickProgressModal(pId, title, prog) {



    $('#ftm-update-progress-pid').val(pId);



    $('#ftm-update-progress-project-name').text(title ? `Project: ${title}` : 'Adjust completion milestone');



    $('#ftm-update-progress-slider').val(prog || 0).trigger('input');



    $('#ftm-modal-update-progress').removeClass('hidden');



},







renderRecentReportsWidget(reports) {



    const list = $('#ftm-standup-recent-reports-list');



    if (!reports || reports.length === 0) {



        list.html('<div class="ftm-text-muted ftm-text-xs" style="padding:10px 0;">No recent submissions.</div>');



        return;



    }







    let html = '';



    reports.slice(0, 4).forEach(r => {



        const dateParts = r.report_date.split('-');



        const d = new Date(r.report_date + 'T12:00:00');



        const monthName = d.toLocaleDateString('en-US', { month: 'short' });



        const dayNum = d.getDate();







        let summaryText = 'Daily progress update';



        let totalH = 0;



        let badgeClass = r.status === 'approved' ? 'bg-emerald-soft text-emerald' : 'bg-blue-soft text-blue';



        let badgeLabel = r.status_label || r.status;







        if (r.report_type === 'weekend') {



            summaryText = 'Weekend / Weekly Off Day';



            badgeClass = 'ftm-badge-slate text-muted';



            badgeLabel = 'Weekend';



        } else if (r.report_type === 'on_leave') {



            summaryText = `On Leave: ${r.leave_reason || r.leave_type_label || 'Approved Leave'}`;



            badgeClass = 'bg-amber-soft text-amber';



            badgeLabel = 'On Leave';



        } else if (r.items && r.items.length > 0) {



            summaryText = r.items[0].tasks_completed || r.items[0].client_name || 'Work completed';



            r.items.forEach(it => { totalH += parseFloat(it.hours_spent || 0); });



        }







        html += `



                    <div class="ftm-widget-report-row">



                        <div class="ftm-widget-report-date-badge">



                            <span>${monthName}</span>



                            <strong>${dayNum}</strong>



                        </div>



                        <div class="ftm-widget-report-content">



                            <div class="ftm-widget-report-summary">${summaryText}</div>



                            <div class="ftm-widget-report-hours">${r.report_type === 'weekend' ? 'Off Day' : (r.report_type === 'on_leave' ? 'On Leave' : (totalH > 0 ? this.formatHoursDisplay(totalH) : 'Work logged'))}</div>



                        </div>



                        <span class="ftm-badge ${badgeClass} ftm-badge-xs">



                            ${badgeLabel}



                        </span>



                    </div>



                `;



    });







    list.html(html);







    $('#ftm-link-recent-view-all').off('click').on('click', function (e) {



        e.preventDefault();



        const hist = document.getElementById('ftm-standup-history-section');



        if (hist) {



            hist.scrollIntoView({ behavior: 'smooth', block: 'start' });



        }



    });



},







renderStandupHistoryTable() {



    const reports = this.state.myDailyReports || [];



    const tbody = $('#ftm-standup-history-tbody');



    const mobileContainer = $('#ftm-standup-history-mobile-cards');



    const projectFilter = $('#ftm-history-filter-project').val() || 'all';



    const rangeFilter = $('#ftm-history-filter-range').val() || '7';







    // Populate Project Filter Dropdown if needed



    const select = $('#ftm-history-filter-project');



    if (select.children().length <= 1) {



        const clients = new Set();



        reports.forEach(r => {



            (r.items || []).forEach(it => {



                if (it.client_name) clients.add(it.client_name);



            });



        });



        clients.forEach(c => {



            select.append(`<option value="${c}">${c}</option>`);



        });



    }







    if (reports.length === 0) {



        tbody.html('<tr><td colspan="6" class="ftm-text-muted" style="text-align:center; padding:30px;">No progress history submitted yet.</td></tr>');



        if (mobileContainer.length) {



            mobileContainer.html('<div class="ftm-text-muted" style="text-align:center; padding:24px;">No progress history submitted yet.</div>');



        }



        return;



    }







    let flattened = [];



    const now = new Date();







    reports.forEach(r => {



        const rDate = new Date(r.report_date + 'T12:00:00');



        const diffDays = Math.round((now - rDate) / (1000 * 60 * 60 * 24));







        if (rangeFilter !== 'all') {



            const maxDays = parseInt(rangeFilter);



            if (diffDays > maxDays) return;



        }







        if (r.report_type === 'weekend') {



            if (projectFilter === 'all') {



                flattened.push({



                    report_id: r.id,



                    date: r.report_date,



                    project: 'Weekend / Off Day',



                    order_id: '',



                    summary: 'Scheduled weekly day off / Weekend',



                    hours: 0,



                    status: 'approved',



                    status_label: 'Weekend',



                    badge_class: 'ftm-badge-slate text-muted'



                });



            }



        } else if (r.report_type === 'on_leave') {



            if (projectFilter === 'all') {



                const rangeStr = (r.leave_start_date && r.leave_end_date && r.leave_start_date !== r.leave_end_date)



                    ? `${r.leave_start_date} – ${r.leave_end_date}`



                    : (r.leave_start_date || r.report_date);



                flattened.push({



                    report_id: r.id,



                    date: r.report_date,



                    project: `On Leave (${r.leave_type_label || 'Leave'})`,



                    order_id: '',



                    summary: `Leave (${rangeStr}): ${r.leave_reason || 'Personal leave'}`,



                    hours: 0,



                    status: 'approved',



                    status_label: 'On Leave',



                    badge_class: 'bg-amber-soft text-amber'



                });



            }



        } else {



            (r.items || []).forEach(it => {



                if (projectFilter !== 'all' && it.client_name !== projectFilter) {



                    return;



                }



                flattened.push({



                    report_id: r.id,



                    date: r.report_date,



                    project: it.client_name || it.order_id || 'Project',



                    order_id: it.order_id,



                    summary: it.tasks_completed || 'Daily deliverables',



                    hours: parseFloat(it.hours_spent || 0),



                    status: r.status,



                    status_label: r.status_label || r.status,



                    badge_class: r.status === 'approved' ? 'bg-emerald-soft text-emerald' : (r.status === 'revision_requested' ? 'bg-amber-soft text-amber' : 'bg-blue-soft text-blue')



                });



            });



        }



    });







    if (flattened.length === 0) {



        tbody.html('<tr><td colspan="6" class="ftm-text-muted" style="text-align:center; padding:30px;">No records match your selected filters.</td></tr>');



        if (mobileContainer.length) {



            mobileContainer.html('<div class="ftm-text-muted" style="text-align:center; padding:24px;">No records match your selected filters.</div>');



        }



        return;



    }







    let html = '';



    let mobileHtml = '';



    flattened.forEach(row => {



        const badgeClass = row.badge_class || (row.status === 'approved' ? 'bg-emerald-soft text-emerald' : (row.status === 'revision_requested' ? 'bg-amber-soft text-amber' : 'bg-blue-soft text-blue'));



        const rawOrderId = (row.order_id || '').trim().replace(/^#+/, '');



        const formattedOrderId = rawOrderId ? '#' + rawOrderId : '';







        // Desktop Table Row



        html += `



                    <tr>



                        <td style="font-weight:600; color:var(--ftm-text-primary); white-space:nowrap;">${row.date}</td>



                        <td>



                            <div style="display:flex; align-items:center; gap:8px;">



                                <div style="width:28px; height:28px; border-radius:6px; background:var(--ftm-bg, #f1f5f9); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#3b82f6;">



                                    ${row.project.substring(0, 2).toUpperCase()}



                                </div>



                                <div>



                                    <strong style="font-size:13px; color:var(--ftm-text-primary);">${row.project}</strong>



                                    ${formattedOrderId ? `<div class="ftm-text-xs ftm-text-muted">${formattedOrderId}</div>` : ''}



                                </div>



                            </div>



                        </td>



                        <td style="max-width:320px; font-size:13px; color:var(--ftm-text-secondary); line-height:1.4;">${ftmEsc(row.summary)}</td>



                        <td style="font-weight:600; font-size:13px; white-space:nowrap;">${this.formatHoursDisplay(row.hours)}</td>



                        <td><span class="ftm-badge ${badgeClass} ftm-badge-sm">${row.status_label}</span></td>



                        <td style="text-align:right;">



                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-btn-view-standup-detail" data-report-id="${row.report_id}">



                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                <span>View</span>



                            </button>



                        </td>



                    </tr>



                `;







        // Mobile Card Item



        mobileHtml += `



                    <div class="ftm-standup-history-card-item">



                        <div class="ftm-sh-card-header">



                            <div class="ftm-sh-card-date">



                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                <span>${row.date}</span>



                            </div>



                            <div class="ftm-sh-card-badges">



                                <span class="ftm-badge ${badgeClass} ftm-badge-sm">${row.status_label}</span>



                            </div>



                        </div>







                        <div class="ftm-sh-card-project-bar">



                            <div class="ftm-sh-card-thumb">



                                ${row.project.substring(0, 2).toUpperCase()}



                            </div>



                            <div class="ftm-sh-card-project-info">



                                <div class="ftm-sh-card-project-title">${row.project}</div>



                                <div class="ftm-sh-card-project-meta">



                                    ${formattedOrderId ? `<span class="ftm-sh-card-order">${formattedOrderId}</span> &bull; ` : ''}



                                    <span class="ftm-sh-card-hours text-blue"><strong>${this.formatHoursDisplay(row.hours)}</strong></span>



                                </div>



                            </div>



                        </div>







                        <div class="ftm-sh-card-summary">



                            <div class="ftm-sh-summary-label">Work Summary</div>



                            <div class="ftm-sh-summary-text">${ftmEsc(row.summary)}</div>



                        </div>







                        <div class="ftm-sh-card-actions">



                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-btn-view-standup-detail" data-report-id="${row.report_id}" style="width:100%; justify-content:center;">



                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                <span>View Report Details</span>



                            </button>



                        </div>



                    </div>



                `;



    });







    tbody.html(html);



    if (mobileContainer.length) {



        mobileContainer.html(mobileHtml);



    }



},







// =========================================================================



// REFERENCE C: TEAM DAILY REPORT MANAGER WORKSPACE (IMAGE 3)



// =========================================================================



initTeamDailyReportWorkspace() {



    const self = this;



    if (this._teamDailyReportInitialized) return;



    this._teamDailyReportInitialized = true;







    // Date navigation



    $('#ftm-team-date-prev').on('click', function (e) {



        e.preventDefault();



        self.shiftTeamReportDate(-1);



    });



    $('#ftm-team-date-next').on('click', function (e) {



        e.preventDefault();



        self.shiftTeamReportDate(1);



    });



    $('#ftm-team-date-display-wrap').on('click', function (e) {



        const picker = $('#ftm-team-report-date')[0];



        if (picker && typeof picker.showPicker === 'function') {



            picker.showPicker();



        }



    });



    $('#ftm-team-report-date').on('change', function () {



        const val = $(this).val();



        if (val) {



            self.setTeamReportDate(val);



        }



    });







    // Filters



    $('#ftm-team-filter-search, #ftm-team-filter-project, #ftm-team-filter-status').on('input change', function () {



        self.filterTeamDailyTable();



    });







    $('#ftm-team-filter-reset').on('click', function (e) {



        e.preventDefault();



        $('#ftm-team-filter-search').val('');



        $('#ftm-team-report-team-filter').val('all');



        $('#ftm-team-filter-project').val('all');



        $('#ftm-team-filter-status').val('all');



        self.loadTeamDailyReportView();



    });







    // Export to CSV



    $('#ftm-btn-export-team-report').on('click', function (e) {



        e.preventDefault();



        self.exportTeamDailyReportCsv();



    });







    // Executive Briefing Document Modal Handlers



    $('#ftm-btn-open-executive-doc').on('click', function (e) {



        e.preventDefault();



        self.openExecutiveDocReport();



    });







    $('#ftm-modal-exec-doc-close').on('click', function (e) {



        e.preventDefault();



        $('#ftm-modal-executive-doc').addClass('hidden');



    });







    $('#ftm-btn-print-doc').on('click', function (e) {



        e.preventDefault();



        self.printDailyExecutiveDoc();



    });







    $('#ftm-btn-print-team-daily').on('click', function (e) {



        e.preventDefault();



        if (!$('#ftm-exec-doc-body').children().length) {



            self.openExecutiveDocReport('all');



            $('#ftm-modal-executive-doc').addClass('hidden');



        }



        self.printDailyExecutiveDoc();



    });







    $('#ftm-btn-copy-doc-text').on('click', function (e) {



        e.preventDefault();



        self.copyExecutiveDocText();



    });







    // Weekly report button



    $('#ftm-btn-view-weekly-report').on('click', function (e) {



        e.preventDefault();



        self.openWeeklyReportModal();



    });







    $('#ftm-modal-weekly-close').on('click', function (e) {



        e.preventDefault();



        $('#ftm-modal-weekly-report').addClass('hidden');



    });







    $('#ftm-btn-print-weekly').on('click', function (e) {



        e.preventDefault();



        self.printWeeklyRollupStatement();



    });







    $('#ftm-btn-prev-week').on('click', function (e) {



        e.preventDefault();



        self.shiftWeeklyReport(-1);



    });







    $('#ftm-btn-next-week').on('click', function (e) {



        e.preventDefault();



        self.shiftWeeklyReport(1);



    });







    $('#ftm-weekly-team-pills .ftm-team-pill').on('click', function (e) {



        e.preventDefault();



        const team = $(this).data('team') || 'all';



        self.openWeeklyReportModal(self.state.weeklyReportStart, team);



    });







    $('#ftm-exec-team-pills .ftm-team-pill').on('click', function (e) {



        e.preventDefault();



        const team = $(this).data('team') || 'all';



        self.openExecutiveDocReport(team);



    });



},







shiftTeamReportDate(days) {



    const currentIso = $('#ftm-team-report-date').val() || new Date().toISOString().split('T')[0];



    const todayIso = new Date().toLocaleDateString('en-CA');



    if (days > 0 && currentIso >= todayIso) return;



    const d = new Date(currentIso + 'T12:00:00');



    d.setDate(d.getDate() + days);



    let nextIso = d.toISOString().split('T')[0];



    if (nextIso > todayIso) nextIso = todayIso;



    this.setTeamReportDate(nextIso);



},







setTeamReportDate(isoDate) {



    const todayIso = new Date().toLocaleDateString('en-CA');



    if (isoDate > todayIso) isoDate = todayIso;



    $('#ftm-team-report-date').val(isoDate);







    // Block going to future dates: disable next button when on today or later



    const nextBtn = $('#ftm-team-date-next');



    if (isoDate >= todayIso) {



        nextBtn.attr('disabled', true).css({ opacity: '0.35', cursor: 'not-allowed', pointerEvents: 'none' });



    } else {



        nextBtn.removeAttr('disabled').css({ opacity: '1', cursor: 'pointer', pointerEvents: 'auto' });



    }







    const d = new Date(isoDate + 'T12:00:00');



    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };



    $('#ftm-team-current-date-label').text(d.toLocaleDateString('en-US', options));



    $('#ftm-team-table-date-label').text(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));



    this.loadTeamDailyReportView(isoDate);



},







loadTeamDailyReportView(date) {



    const self = this;



    this.initTeamDailyReportWorkspace();







    const targetDate = date || $('#ftm-team-report-date').val() || new Date().toISOString().split('T')[0];



    const targetTeam = $('#ftm-team-report-team-filter').val() || '';



    const teamQuery = (targetTeam && targetTeam !== 'all') ? `&team=${encodeURIComponent(targetTeam)}` : '';







    const tbody = $('#ftm-team-progress-tbody');



    if (!tbody.children().length || tbody.find('.ftm-table-loading').length) {



        tbody.html('<tr><td colspan="11" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading team submissions...</td></tr>');



    } else {



        tbody.css('opacity', '0.65');



    }







    // Fetch team members, projects, and daily reports



    Promise.all([



        this.api(`daily-reports?date=${targetDate}${teamQuery}`),



        this.api('team-members'),



        this.api('projects?per_page=100')



    ]).then(([reports, members, projectsRes]) => {



        tbody.css('opacity', '1');



        self.state.teamDailyReports = reports || [];



        self.state.teamMembers = members || [];



        const projects = (projectsRes && projectsRes.items) || (Array.isArray(projectsRes) ? projectsRes : []);



        self.state.allProjectsList = projects;
        if (typeof self.loadAttendanceRoster === 'function') {
            try {
                self.loadAttendanceRoster(targetDate, targetTeam);
            } catch (e) {
                console.warn('loadAttendanceRoster error:', e);
            }
        }

        // Filter out management / super admin from worker submission tracking



        const workingMembers = (members || []).filter(m => {
            if (parseInt(m.id) === 1 || m.is_admin || m.is_global) return true;
            return m.team_role !== 'super_admin' && !ftmIsMgmt(m.team) && !(m.role_label && m.role_label.toLowerCase().includes('admin'));
        });







        // 1. Compute 5 KPIs (Reference C)



        self.renderTeamKPIs(reports, workingMembers, projects, targetDate);







        // 2. Render Team Daily Progress Table (Reference C)



        self.renderTeamProgressTable(reports, workingMembers);







        // 3. Render Donut Chart (Work Hours Distribution)



        self.renderWorkHoursDonut(reports, workingMembers);







        // 4. Render Project Status Overview Bars



        self.renderProjectStatusBars(projects);







        // 5. Render Top Contributors Widget



        self.renderTopContributors(reports, workingMembers);



    }).catch(err => {



        tbody.html(`<tr><td colspan="10" class="ftm-text-danger" style="text-align:center; padding:20px;">Error loading team reports: ${err.message || 'Unknown error'}</td></tr>`);



    });



},







renderTeamKPIs(reports, members, projects, targetDate) {



    const totalMembers = members.length || 8;



    const submittedCount = (reports || []).length;



    const subPct = totalMembers > 0 ? Math.round((submittedCount / totalMembers) * 100) : 0;







    let totalHours = 0;



    let completedTasksCount = 0;



    let issuesCount = 0;







    (reports || []).forEach(r => {



        (r.items || []).forEach(it => {



            totalHours += parseFloat(it.hours_spent || 0);



            if (it.tasks_completed) completedTasksCount++;



            if (it.has_blocker) issuesCount++;



        });



    });







    // Active projects



    const activeProjectsCount = (projects || []).filter(p => !['delivery_accepted', 'cancelled'].includes(p.status)).length;







    $('#ftm-tkpi-members-count').text(totalMembers);



    $('#ftm-tkpi-members-sub').text(`${submittedCount} submitted (${subPct}%)`);



    $('#ftm-team-submission-ratio-badge').text(`${submittedCount} of ${totalMembers} submitted`);







    $('#ftm-tkpi-hours-count').text(this.formatHoursDisplay(totalHours));
    $('#ftm-tkpi-hours-sub').text(totalHours > 0 ? `${submittedCount} members logged` : 'No hours logged yet');

    $('#ftm-tkpi-projects-count').text(activeProjectsCount || projects.length);

    $('#ftm-tkpi-tasks-count').text(completedTasksCount);
    $('#ftm-tkpi-tasks-sub').text(completedTasksCount > 0 ? `${completedTasksCount} finished today` : 'No tasks finished yet');

    $('#ftm-tkpi-issues-count').text(issuesCount);
    if (issuesCount === 0) {
        $('#ftm-tkpi-issues-sub').removeClass('text-rose').addClass('text-emerald').text('All clear / No blockers');
    } else {
        $('#ftm-tkpi-issues-sub').removeClass('text-emerald').addClass('text-rose').text(`${issuesCount} blockers reported`);
    }



},







renderTeamProgressTable(reports, members) {



    const self = this;



    const tbody = $('#ftm-team-progress-tbody');



    const mobileCardsContainer = $('#ftm-team-mobile-cards');



    const targetTeam = $('#ftm-team-report-team-filter').val() || 'all';







    // Filter out super admin and filter by team if selected



    const filteredMembers = (members || []).filter(m => {



        if (m.team_role === 'super_admin' || ftmIsMgmt(m.team) || (m.role_label && m.role_label.toLowerCase().includes('admin'))) {



            return false;



        }



        return targetTeam === 'all' || m.team === targetTeam;



    });







    if (filteredMembers.length === 0) {



        tbody.html('<tr><td colspan="10" class="ftm-text-muted" style="text-align:center; padding:30px;">No team members found for this view.</td></tr>');



        mobileCardsContainer.html('<div class="ftm-text-muted" style="text-align:center; padding:24px;">No team members found for this view.</div>');



        return;



    }







    let html = '';



    let mobileCardsHtml = '';



    filteredMembers.forEach((m, idx) => {



        const rep = (reports || []).find(r => parseInt(r.user_id) === parseInt(m.id));



        const isSubmitted = !!rep;







        let projectsWorkedText = '-';



        let hoursSpent = 0;



        let completedTasks = 0;



        let wipCount = 0;



        let issuesCount = 0;







        let realWorkItems = [];
        if (isSubmitted && rep && rep.items) {
            realWorkItems = rep.items.filter(it => it.order_id !== 'WEEKEND' && it.order_id !== 'LEAVE' && (it.client_name || it.order_id || it.tasks_completed || parseFloat(it.hours_spent || 0) > 0));
        }

        if (isSubmitted) {

            if (realWorkItems.length > 0) {

                const firstProj = realWorkItems[0].client_name || realWorkItems[0].order_id || 'Project';

                const moreCount = realWorkItems.length - 1;

                projectsWorkedText = `<span class="ftm-badge ftm-badge-outline ftm-badge-xs">${firstProj}</span>` + (moreCount > 0 ? ` <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">+${moreCount}</span>` : '');

                realWorkItems.forEach(it => {

                    hoursSpent += parseFloat(it.hours_spent || 0);

                    if (it.tasks_completed) completedTasks++;

                    if (it.work_in_progress) wipCount++;

                    if (it.has_blocker) issuesCount++;

                });

            } else if (rep.report_type === 'weekend') {

                projectsWorkedText = `<span class="ftm-badge ftm-badge-outline ftm-badge-xs text-muted" style="border-style:dashed;">🌴 Weekend / Off Day</span>`;

            } else if (rep.report_type === 'on_leave') {

                const rangeStr = (rep.leave_start_date && rep.leave_end_date && rep.leave_start_date !== rep.leave_end_date)

                    ? `${rep.leave_start_date} &rarr; ${rep.leave_end_date}`

                    : (rep.leave_start_date || rep.report_date);

                projectsWorkedText = `<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" title="Leave: ${self.escapeHtml(rep.leave_reason || '')}">🏖️ ${self.escapeHtml(rep.leave_type_label || 'On Leave')}</span>`;

            } else if (rep.items && rep.items.length > 0) {

                const firstProj = rep.items[0].client_name || rep.items[0].order_id || 'Project';

                const moreCount = rep.items.length - 1;

                projectsWorkedText = `<span class="ftm-badge ftm-badge-outline ftm-badge-xs">${firstProj}</span>` + (moreCount > 0 ? ` <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">+${moreCount}</span>` : '');

                rep.items.forEach(it => {

                    hoursSpent += parseFloat(it.hours_spent || 0);

                    if (it.tasks_completed) completedTasks++;

                    if (it.work_in_progress) wipCount++;

                    if (it.has_blocker) issuesCount++;

                });

            }

        }







        // Team color pill



        const teamColorClass = m.team === 'WpCraft' ? 'bg-blue-soft text-blue' : (m.team === 'WpNexus' ? 'bg-purple-soft text-purple' : 'bg-amber-soft text-amber');







        // Status pill



        let statusBadge = '<span class="ftm-badge bg-gray-soft text-gray ftm-badge-xs">Not Submitted</span>';



        if (isSubmitted) {

            if (realWorkItems.length > 0) {

                if (rep.status === 'approved') {

                    statusBadge = '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Approved</span>';

                } else if (rep.status === 'leader_approved') {

                    statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Leader Verified</span>';

                } else if (rep.status === 'revision_requested') {

                    statusBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">Revision</span>';

                } else {

                    statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Submitted</span>';

                }

            } else if (rep.report_type === 'weekend') {

                if (rep.status === 'approved') {

                    statusBadge = '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">🌴 Weekend (Approved)</span>';

                } else {

                    statusBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">🌴 Weekend (Pending)</span>';

                }

            } else if (rep.report_type === 'on_leave') {

                const rangeStr = (rep.leave_start_date && rep.leave_end_date && rep.leave_start_date !== rep.leave_end_date)

                    ? `${rep.leave_start_date} – ${rep.leave_end_date}`

                    : (rep.leave_start_date || rep.report_date);

                const reasonTip = `(${rangeStr}) Reason: ${rep.leave_reason || 'N/A'}`;

                if (rep.status === 'approved') {

                    statusBadge = `<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-weight:600;" title="${self.escapeHtml(reasonTip)}">🏖️ On Leave (Approved)</span>`;

                } else {

                    statusBadge = `<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-weight:600;" title="${self.escapeHtml(reasonTip)}">🏖️ On Leave (Pending)</span>`;

                }

            } else if (rep.status === 'approved') {

                statusBadge = '<span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Approved</span>';

            } else if (rep.status === 'leader_approved') {

                statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Leader Verified</span>';

            } else if (rep.status === 'revision_requested') {

                statusBadge = '<span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs">Revision</span>';

            } else {

                statusBadge = '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Submitted</span>';

            }

        }







        const actionsHtml = isSubmitted ? `



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-btn-team-view-rep" data-report-id="${rep.id}" title="View Report">



                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                        <span>View</span>



                    </button>



                    ${rep.status !== 'approved' ? `



                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs ftm-btn-quick-approve" data-report-id="${rep.id}" title="Approve">



                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>



                        </button>



                    ` : ''}

                    ${(self.state.isAdmin || self.state.isLeader) ? `
                        <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs text-rose ftm-btn-quick-reset-rep" data-report-id="${rep.id}" title="Reset / cancel mistaken submission so member can submit work deliverables" style="border-color:rgba(244,63,94,0.35); padding:2px 7px;">
                            ↺ Reset
                        </button>
                    ` : ''}



                ` : `



                    <span class="ftm-text-muted ftm-text-xs">-</span>



                `;







        html += `



                    <tr class="ftm-team-table-row" data-name="${(m.name || '').toLowerCase()}" data-team="${m.team || ''}" data-status="${isSubmitted ? rep.status : 'not_submitted'}">



                        <td style="color:var(--ftm-text-muted); font-size:12px;">${idx + 1}</td>



                        <td>
                            <div class="ftm-trigger-member-activity" data-id="${m.id}" style="display:flex; align-items:center; gap:10px; cursor:pointer;" title="Click to view ${ftmEsc(m.name)}'s activity report">
                                <img src="${m.avatar || ''}" class="ftm-avatar ftm-avatar-sm" alt="${ftmEsc(m.name)}">
                                <div>
                                    <strong style="font-size:13px; color:var(--ftm-primary); text-decoration:underline; text-decoration-color:rgba(59,130,246,0.3); text-underline-offset:3px;">${ftmEsc(m.name)}</strong>
                                    <div class="ftm-text-xs ftm-text-muted">${m.role_label || 'WordPress Developer'}</div>
                                </div>
                            </div>
                        </td>



                        <td><span class="ftm-badge ${teamColorClass} ftm-badge-xs">${m.team}</span></td>



                        <td>${projectsWorkedText}</td>



                        <td style="font-weight:600; font-size:12.5px; white-space:nowrap; text-align:center;">${(isSubmitted && rep.report_type !== 'on_leave' && rep.report_type !== 'weekend') ? this.formatHoursDisplay(hoursSpent) : '<span class="ftm-text-muted">-</span>'}</td>

                        <td style="font-weight:600; color:#10b981;">${(isSubmitted && rep.report_type !== 'on_leave' && rep.report_type !== 'weekend') ? completedTasks : '<span class="ftm-text-muted">-</span>'}</td>

                        <td style="font-weight:600; color:#3b82f6;">${(isSubmitted && rep.report_type !== 'on_leave' && rep.report_type !== 'weekend') ? wipCount : '<span class="ftm-text-muted">-</span>'}</td>

                        <td>${(isSubmitted && rep.report_type !== 'on_leave' && rep.report_type !== 'weekend') ? (issuesCount > 0 ? `<span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs" style="font-weight:700;">${issuesCount}</span>` : '0') : '<span class="ftm-text-muted">-</span>'}</td>



                        <td>${statusBadge}</td>



                        <td style="text-align:right;">



                            <div style="display:inline-flex; align-items:center; gap:4px;">



                                ${actionsHtml}



                            </div>



                        </td>



                    </tr>



                `;







        // Build native mobile card for team member



        const mobileActionsHtml = isSubmitted ? `



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-btn-team-view-rep" data-report-id="${rep.id}" title="View Report">



                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                        <span>View</span>



                    </button>



                    ${rep.status !== 'approved' ? `



                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs ftm-btn-quick-approve" data-report-id="${rep.id}" title="Approve">



                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>



                            <span>Approve</span>



                        </button>



                    ` : ''}

                    ${(self.state.isAdmin || self.state.isLeader) ? `
                        <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs text-rose ftm-btn-quick-reset-rep" data-report-id="${rep.id}" title="Reset / cancel mistaken submission so member can submit work deliverables" style="border-color:rgba(244,63,94,0.35); padding:2px 7px;">
                            ↺ Reset
                        </button>
                    ` : ''}



                ` : `



                    <span class="ftm-text-muted ftm-text-xs">No Report Filed</span>



                `;







        mobileCardsHtml += `



                    <div class="ftm-tm-card" data-name="${(m.name || '').toLowerCase()}" data-team="${m.team || ''}" data-status="${isSubmitted ? rep.status : 'not_submitted'}">



                        <div class="ftm-tm-card-header">



                            <div class="ftm-tm-card-identity">



                                <img src="${m.avatar || ''}" class="ftm-tm-card-avatar" alt="${ftmEsc(m.name)}">



                                <div>



                                    <div class="ftm-tm-card-name-row">



                                        <span class="ftm-tm-card-name">${ftmEsc(m.name)}</span>



                                        <span class="ftm-badge ${teamColorClass} ftm-badge-xs">${m.team}</span>



                                    </div>



                                    <div class="ftm-tm-card-role-meta">



                                        <span>#${idx + 1}</span> &bull; <span>${m.role_label || 'WordPress Developer'}</span>



                                    </div>



                                </div>



                            </div>



                            <div>



                                ${statusBadge}



                            </div>



                        </div>







                        <div class="ftm-tm-card-grid">



                            <div class="ftm-tm-grid-item">



                                <span class="ftm-tm-grid-label">Hours</span>



                                <span class="ftm-tm-grid-val" style="color:var(--ftm-text-primary);">${isSubmitted ? this.formatHoursDisplay(hoursSpent) : '-'}</span>



                            </div>



                            <div class="ftm-tm-grid-item">



                                <span class="ftm-tm-grid-label">Done</span>



                                <span class="ftm-tm-grid-val text-emerald">${isSubmitted ? completedTasks : '-'}</span>



                            </div>



                            <div class="ftm-tm-grid-item">



                                <span class="ftm-tm-grid-label">WIP</span>



                                <span class="ftm-tm-grid-val text-blue">${isSubmitted ? wipCount : '-'}</span>



                            </div>



                            <div class="ftm-tm-grid-item">



                                <span class="ftm-tm-grid-label">Blockers</span>



                                <span class="ftm-tm-grid-val ${issuesCount > 0 ? 'text-rose' : 'text-muted'}">${issuesCount > 0 ? issuesCount : (isSubmitted ? '0' : '-')}</span>



                            </div>



                        </div>







                        <div class="ftm-tm-card-footer">



                            <div class="ftm-tm-card-projects">



                                ${projectsWorkedText}



                            </div>



                            <div class="ftm-tm-card-actions">



                                ${mobileActionsHtml}



                            </div>



                        </div>



                    </div>



                `;



    });







    tbody.html(html);



    mobileCardsContainer.html(mobileCardsHtml);







    // Bind click handlers for both desktop table and mobile cards



    $('#ftm-team-progress-tbody, #ftm-team-mobile-cards').find('.ftm-btn-team-view-rep').on('click', function (e) {



        e.preventDefault();



        const rId = parseInt($(this).data('report-id'));



        if (rId) self.openMemberReportReviewModal(rId);



    });







    $('#ftm-team-progress-tbody, #ftm-team-mobile-cards').find('.ftm-btn-quick-reset-rep').off('click').on('click', function (e) {
        e.preventDefault();
        const rId = parseInt($(this).data('report-id'));
        if (!rId) return;
        if (!confirm('Are you sure you want to delete/reset this submission so the member can submit work deliverables?')) return;
        self.reviewReportAction(rId, 'reset', 'Reset by leadership');
    });

    $('#ftm-team-progress-tbody, #ftm-team-mobile-cards').find('.ftm-btn-quick-approve').on('click', function (e) {



        e.preventDefault();



        const rId = parseInt($(this).data('report-id'));



        if (rId) self.reviewReportAction(rId, 'approve');



    });

    $("#ftm-team-progress-tbody, #ftm-team-mobile-cards").find(".ftm-btn-quick-verify").on("click", function (e) {
        e.preventDefault();
        const rId = parseInt($(this).data("report-id"));
        if (rId) self.reviewReportAction(rId, "leader_approve");
    });



},







filterTeamDailyTable() {



    const query = ($('#ftm-team-filter-search').val() || '').toLowerCase();



    const statusFilter = $('#ftm-team-filter-status').val() || 'all';







    $('.ftm-team-table-row, .ftm-tm-card').each(function () {



        const name = $(this).data('name') || '';



        const status = $(this).data('status') || '';







        const matchesQuery = !query || name.includes(query);



        const matchesStatus = statusFilter === 'all' || status === statusFilter;







        if (matchesQuery && matchesStatus) {



            $(this).show();



        } else {



            $(this).hide();



        }



    });



},







renderWorkHoursDonut(reports, members) {



    // Group total work hours by team



    const teamHours = { WpCraft: 0, WpNexus: 0, WpTitans: 0 };



    let totalHours = 0;







    (reports || []).forEach(r => {



        const m = (members || []).find(mem => parseInt(mem.id) === parseInt(r.user_id));



        const team = (m ? m.team : r.team_name) || 'WpCraft';



        let hours = 0;



        (r.items || []).forEach(it => { hours += parseFloat(it.hours_spent || 0); });







        if (teamHours[team] !== undefined) {



            teamHours[team] += hours;



        } else {



            teamHours[team] = hours;



        }



        totalHours += hours;



    });







    $('#ftm-donut-total-hours').text(this.formatHoursDisplay(totalHours));







    // Circumference of r=48 is 2 * PI * 48 ≈ 301.6



    const C = 301.6;



    const teams = Object.keys(teamHours);



    const teamColors = { WpCraft: '#3b82f6', WpNexus: '#8b5cf6', WpTitans: '#f59e0b' };







    let currentOffset = 0;



    let legendHtml = '';







    teams.forEach((t, i) => {



        const h = teamHours[t];



        const pct = totalHours > 0 ? (h / totalHours) : (1 / teams.length);



        const segLen = pct * C;







        const segEl = $(`#ftm-team-donut-svg .ftm-donut-seg-${i + 1}`);



        if (segEl.length) {



            segEl.attr('stroke', teamColors[t] || '#3b82f6');



            segEl.attr('stroke-dasharray', `${segLen.toFixed(1)} ${(C - segLen).toFixed(1)}`);



            segEl.attr('stroke-dashoffset', `-${currentOffset.toFixed(1)}`);



        }



        currentOffset += segLen;







        const pctFormatted = Math.round(pct * 100);



        legendHtml += `



                    <div class="ftm-donut-legend-item">



                        <div class="ftm-donut-legend-left">



                            <span class="ftm-donut-legend-dot" style="background:${teamColors[t]};"></span>



                            <span>${t}</span>



                        </div>



                        <div class="ftm-donut-legend-right">${this.formatHoursDisplay(h)} (${pctFormatted}%)</div>



                    </div>



                `;



    });







    $('#ftm-team-donut-legend').html(legendHtml);



},







renderProjectStatusBars(projects) {



    const counts = { active: 0, wip: 0, revision: 0, completed: 0 };



    (projects || []).forEach(p => {



        if (p.status === 'delivery_accepted') counts.completed++;



        else if (p.status === 'revision') counts.revision++;



        else if (p.status === 'in_progress' || p.status === 'wip') counts.wip++;



        else counts.active++;



    });







    const maxCount = Math.max(counts.active, counts.wip, counts.revision, counts.completed, 1);







    $('#ftm-sbar-active-count').text(counts.active);



    $('#ftm-sbar-active-fill').css('width', `${Math.min(100, Math.round((counts.active / maxCount) * 100))}%`);







    $('#ftm-sbar-wip-count').text(counts.wip);



    $('#ftm-sbar-wip-fill').css('width', `${Math.min(100, Math.round((counts.wip / maxCount) * 100))}%`);







    $('#ftm-sbar-rev-count').text(counts.revision);



    $('#ftm-sbar-rev-fill').css('width', `${Math.min(100, Math.round((counts.revision / maxCount) * 100))}%`);







    $('#ftm-sbar-comp-count').text(counts.completed);



    $('#ftm-sbar-comp-fill').css('width', `${Math.min(100, Math.round((counts.completed / maxCount) * 100))}%`);



},







renderTopContributors(reports, members) {
    const memberHours = [];

    (members || []).forEach(m => {
        const rep = (reports || []).find(r => parseInt(r.user_id) === parseInt(m.id));
        let totalH = 0;
        if (rep && rep.items) {
            rep.items.forEach(it => { totalH += parseFloat(it.hours_spent || 0); });
        }
        if (totalH > 0) {
            memberHours.push({ member: m, hours: totalH });
        }
    });

    // Sort descending by hours
    memberHours.sort((a, b) => b.hours - a.hours);
    this.dailyTopContributors = memberHours;
    this.showingAllDailyContributors = false;

    const link = $('#ftm-link-top-contributors-all');
    if (memberHours.length <= 4) {
        link.hide();
    } else {
        link.show().text(`View All (${memberHours.length})`);
    }

    this.renderContributorsList(false);
},

renderContributorsList(showAll = false) {
    const list = $('#ftm-team-top-contributors-list');
    const items = (this.dailyTopContributors || []);

    // If no reports or 0 hours across all members
    if (!items.length) {
        $('#ftm-link-top-contributors-all').hide();
        list.html(`
                    <div class="ftm-contributors-empty" style="text-align:center; padding:24px 12px; background:rgba(248,250,252,0.6); border-radius:10px; border:1px dashed #e2e8f0;">
                        <div style="width:36px; height:36px; margin:0 auto 8px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#64748b;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div style="font-weight:600; font-size:13px; color:#334155;">No Standup Reports Yet</div>
                        <div style="font-size:12px; color:#94a3b8; margin-top:2px;">No work hours recorded for this date.</div>
                    </div>
                `);
        return;
    }

    const displayItems = showAll ? items : items.slice(0, 4);
    let html = '';
    displayItems.forEach((item, idx) => {
        const rankBadge = idx === 0
            ? '<span class="ftm-rank-badge" style="background:#fef3c7; color:#b45309; font-weight:700; font-size:11px; padding:2px 7px; border-radius:5px; margin-right:8px; flex-shrink:0;">#1</span>'
            : (idx === 1
                ? '<span class="ftm-rank-badge" style="background:#f1f5f9; color:#475569; font-weight:700; font-size:11px; padding:2px 7px; border-radius:5px; margin-right:8px; flex-shrink:0;">#2</span>'
                : (idx === 2
                    ? '<span class="ftm-rank-badge" style="background:#fff7ed; color:#c2410c; font-weight:700; font-size:11px; padding:2px 7px; border-radius:5px; margin-right:8px; flex-shrink:0;">#3</span>'
                    : `<span style="font-size:11px; color:#94a3b8; font-weight:600; width:22px; display:inline-block; text-align:center; margin-right:6px; flex-shrink:0;">${idx + 1}</span>`));

        html += `
                    <div class="ftm-contributor-row">
                        <div class="ftm-contributor-info">
                            ${rankBadge}
                            <img src="${item.member.avatar || ''}" class="ftm-contributor-avatar" alt="${ftmEsc(item.member.name)}" style="width:30px; height:30px; border-radius:50%; object-fit:cover; margin-right:8px; flex-shrink:0; border:1px solid rgba(226,232,240,0.8);">
                            <div class="ftm-contributor-name" style="font-weight:600; font-size:13px; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor:pointer;" data-member-id="${item.member.id}" title="${ftmEsc(item.member.name)}">${ftmEsc(item.member.name)}</div>
                        </div>
                        <div class="ftm-contributor-hours">
                            <span style="font-weight:700; font-size:12px; color:#2563eb; background:rgba(37,99,235,0.08); padding:3px 8px; border-radius:6px; white-space:nowrap;">${this.formatHoursDisplay(item.hours)}</span>
                        </div>
                    </div>
                `;
    });

    if (showAll && items.length > 4) {
        html += `
                    <div style="text-align:center; padding-top:10px; margin-top:6px; border-top:1px dashed #e2e8f0;">
                        <a href="#leaderboard" class="ftm-btn-goto-leaderboard" style="font-size:12px; font-weight:600; color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            <span>Open Performance Leaderboard</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                `;
    }

    list.html(html);
},

exportTeamDailyReportCsv() {



    const reports = this.state.teamDailyReports || [];



    const members = this.state.teamMembers || [];



    const date = $('#ftm-team-report-date').val() || new Date().toISOString().split('T')[0];







    let csv = 'Member,Team,Report Date,Status,Hours,Tasks Completed,WIP,Blockers\n';



    (members || []).forEach(m => {



        const rep = reports.find(r => parseInt(r.user_id) === parseInt(m.id));



        if (rep) {



            let totalH = 0;



            let tasks = [];



            let wips = [];



            let blockers = [];



            (rep.items || []).forEach(it => {



                totalH += parseFloat(it.hours_spent || 0);



                if (it.tasks_completed) tasks.push(it.tasks_completed.replace(/"/g, '""'));



                if (it.work_in_progress) wips.push(it.work_in_progress.replace(/"/g, '""'));



                if (it.blocker_description) blockers.push(it.blocker_description.replace(/"/g, '""'));



            });



            csv += `"${m.name}","${m.team}","${date}","${rep.status_label || rep.status}","${totalH}","${tasks.join('; ')}","${wips.join('; ')}","${blockers.join('; ')}"\n`;



        } else {



            csv += `"${m.name}","${m.team}","${date}","Not Submitted","0","","",""\n`;



        }



    });







    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });



    const url = URL.createObjectURL(blob);



    const a = document.createElement('a');



    a.href = url;



    a.download = `Team_Daily_Report_${date}.csv`;



    document.body.appendChild(a);



    a.click();



    document.body.removeChild(a);



},







openExecutiveDocReport(selectedTeam = 'all') {



    const self = this;



    const modal = $('#ftm-modal-executive-doc');



    const body = $('#ftm-exec-doc-body');



    const targetDate = $('#ftm-team-report-date').val() || new Date().toISOString().split('T')[0];



    const d = new Date(targetDate + 'T12:00:00');



    const dateStr = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });







    $('#ftm-exec-team-pills .ftm-team-pill').removeClass('active');



    $(`#ftm-exec-team-pills .ftm-team-pill[data-team="${selectedTeam}"]`).addClass('active');







    const allMembers = (self.state.teamMembers || []).filter(m => {



        return m.team_role !== 'super_admin' && !ftmIsMgmt(m.team) && !(m.role_label && m.role_label.toLowerCase().includes('admin'));



    });



    const workingMembers = (selectedTeam === 'all') ? allMembers : allMembers.filter(m => m.team === selectedTeam);



    const reports = self.state.teamDailyReports || [];



    const projects = self.state.allProjectsList || [];







    let totalH = 0;



    let totalDone = 0;



    let totalWip = 0;



    let totalBlockers = 0;



    const blockersList = [];







    // Group by team



    const teamsMap = {};



    if (selectedTeam === 'all') {



        teamsMap['WpCraft'] = [];



        teamsMap['WpNexus'] = [];



        teamsMap['WpTitans'] = [];
        teamsMap['WpHorizon'] = [];



    } else {



        teamsMap[selectedTeam] = [];



    }







    workingMembers.forEach(m => {



        const rep = reports.find(r => parseInt(r.user_id) === parseInt(m.id));



        const team = m.team || 'WpCraft';



        if (!teamsMap[team]) teamsMap[team] = [];







        let mHours = 0;



        let itemsList = [];







        if (rep && rep.items) {



            rep.items.forEach(it => {



                const h = parseFloat(it.hours_spent || 0);



                mHours += h;



                totalH += h;



                if (it.tasks_completed) totalDone++;



                if (it.work_in_progress) totalWip++;



                if (it.has_blocker) {



                    totalBlockers++;



                    blockersList.push({



                        member: m.name,



                        team: m.team,



                        project: it.client_name || it.order_id || 'Project',



                        blocker: it.blocker_description || 'Need assistance'



                    });



                }



                itemsList.push(it);



            });



        }







        teamsMap[team].push({



            member: m,



            report: rep,



            hours: mHours,



            items: itemsList



        });



    });







    const submittedCount = workingMembers.filter(m => reports.some(r => parseInt(r.user_id) === parseInt(m.id))).length;



    const parityPct = workingMembers.length > 0 ? Math.round((submittedCount / workingMembers.length) * 100) : 0;







    let activeTeamsHtml = '';



    const standbyUnitsList = [];







    for (const [teamName, teamData] of Object.entries(teamsMap)) {



        let teamH = 0;



        let activeRows = '';



        const inactiveMembers = [];







        teamData.forEach(td => {



            teamH += td.hours;



            if (td.report && td.report.report_type === 'weekend') {



                activeRows += `



                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 12px; margin-bottom:6px; page-break-inside:avoid; break-inside:avoid;">



                                <div style="display:flex; justify-content:space-between; align-items:center;">



                                    <div><strong style="color:#0f172a; font-size:12px;">${ftmEsc(td.member.name)}</strong> <span style="font-size:10.5px; color:#64748b;">(${td.member.role_label || 'WordPress Developer'})</span></div>



                                    <div style="font-size:11px; color:#64748b; font-weight:600;">Scheduled Off / Weekend</div>



                                </div>



                            </div>



                        `;



            } else if (td.report && td.report.report_type === 'on_leave') {



                const rangeStr = (td.report.leave_start_date && td.report.leave_end_date && td.report.leave_start_date !== td.report.leave_end_date)



                    ? `${td.report.leave_start_date} &rarr; ${td.report.leave_end_date}`



                    : (td.report.leave_start_date || targetDate);



                activeRows += `



                            <div style="background:#fffbeb; border:1px solid #fef3c7; border-radius:6px; padding:6px 12px; margin-bottom:6px; page-break-inside:avoid; break-inside:avoid;">



                                <div style="display:flex; justify-content:space-between; align-items:center;">



                                    <div><strong style="color:#0f172a; font-size:12px;">${ftmEsc(td.member.name)}</strong> <span style="font-size:10.5px; color:#64748b;">(${td.member.role_label || 'WordPress Developer'})</span></div>



                                    <div style="font-size:11px; color:#d97706; font-weight:700;">On Leave: ${self.escapeHtml(td.report.leave_type_label || 'Approved Leave')} (${rangeStr})</div>



                                </div>



                            </div>



                        `;



            } else if (td.report && td.items && td.items.length > 0) {



                let workDetails = '';



                td.items.forEach(it => {



                    const rawOrderId = (it.order_id || '').trim().replace(/^#+/, '');



                    const formattedOrderId = rawOrderId ? '#' + rawOrderId : '';



                    workDetails += `



                                <div style="margin-bottom:6px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">



                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">



                                        <div style="font-weight:700; color:#0f172a; font-size:12px;">



                                            ${self.escapeHtml(it.client_name || 'Project')} 



                                            ${formattedOrderId ? `<span style="font-size:10.5px; font-weight:700; color:#2563eb; background:#eff6ff; padding:1px 6px; border-radius:3px; margin-left:4px;">${formattedOrderId}</span>` : ''}



                                        </div>



                                        <div style="font-size:11px; font-weight:800; color:#0f172a; font-family:'Consolas',monospace;">



                                            ${self.formatHoursDisplay(it.hours_spent)}



                                        </div>



                                    </div>



                                    <div style="font-size:11px; line-height:1.45; color:#334155; margin-top:2px;">



                                        <strong style="color:#047857; font-size:10px; text-transform:uppercase; letter-spacing:0.03em;">Completed:</strong> ${self.escapeHtml(it.tasks_completed || 'None logged')}



                                    </div>



                                    ${it.work_in_progress ? `



                                    <div style="font-size:11px; line-height:1.45; color:#334155; margin-top:2px;">



                                        <strong style="color:#2563eb; font-size:10px; text-transform:uppercase; letter-spacing:0.03em;">In-Progress:</strong> ${self.escapeHtml(it.work_in_progress)}



                                    </div>` : ''}



                                    ${it.next_day_plan ? `



                                    <div style="font-size:11px; line-height:1.45; color:#334155; margin-top:2px;">



                                        <strong style="color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.03em;">Next Day:</strong> ${self.escapeHtml(it.next_day_plan)}



                                    </div>` : ''}



                                    ${it.has_blocker ? `



                                    <div style="font-size:11px; line-height:1.45; color:#991b1b; margin-top:3px; background:#fff1f2; border:1px solid #fecdd3; border-left:3px solid #dc2626; border-radius:4px; padding:3px 8px;">



                                        <strong style="color:#dc2626; font-size:10px; text-transform:uppercase; letter-spacing:0.03em;">Blocker Flag:</strong> ${self.escapeHtml(it.blocker_description || 'Assistance requested')}



                                    </div>` : ''}



                                </div>



                            `;



                });



                activeRows += `



                            <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; margin-bottom:6px; box-shadow:0 1px 2px rgba(0,0,0,0.02); page-break-inside:avoid; break-inside:avoid;">



                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; border-bottom:1px solid #f1f5f9; padding-bottom:3px;">



                                    <div style="display:flex; align-items:center; gap:6px;">



                                        <strong style="font-size:12px; color:#0f172a;">${ftmEsc(td.member.name)}</strong>



                                        <span style="font-size:9.5px; font-weight:600; color:#64748b; background:#f1f5f9; padding:1px 5px; border-radius:3px;">${td.member.role_label || 'WordPress Developer'}</span>



                                    </div>



                                    <div style="font-size:11px; font-weight:800; color:#10b981;">Total: ${self.formatHoursDisplay(td.hours)}</div>



                                </div>



                                <div>${workDetails}</div>



                            </div>



                        `;



            } else {



                inactiveMembers.push(`${ftmEsc(td.member.name)} <span style="font-size:9.5px; color:#94a3b8;">(${td.member.role_label || 'WordPress Developer'})</span>`);



            }



        });







        if (activeRows) {



            let unitStandbyNote = '';



            if (inactiveMembers.length > 0) {



                unitStandbyNote = `



                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:4px; padding:4px 8px; margin-top:4px; font-size:9.5px; color:#64748b; page-break-inside:avoid; break-inside:avoid;">



                                <strong style="color:#475569;">Standby Specialists (${inactiveMembers.length}):</strong> ${inactiveMembers.join(' &bull; ')}



                            </div>



                        `;



            }







            activeTeamsHtml += `



                        <div style="margin-top:8px; page-break-inside:avoid; break-inside:avoid;">



                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; padding-bottom:2px; border-bottom:1.5px solid #2563eb;">



                                <h3 style="margin:0; font-size:11.5px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:0.04em;">Operational Unit: ${teamName}</h3>



                                <span style="font-size:11px; font-weight:800; color:#2563eb;">Unit Hours: ${self.formatHoursDisplay(teamH)}</span>



                            </div>



                            <div>${activeRows}</div>



                            ${unitStandbyNote}



                        </div>



                    `;



        } else if (teamData.length > 0) {



            standbyUnitsList.push({



                name: teamName,



                count: teamData.length,



                members: inactiveMembers



            });



        }



    }







    let standbyUnitsHtml = '';



    if (standbyUnitsList.length > 0) {



        let suRows = '';



        standbyUnitsList.forEach(su => {



            suRows += `



                        <div style="font-size:10px; color:#475569; margin-top:3px; line-height:1.4;">



                            <strong style="color:#0f172a;">${ftmEsc(su.name)} (${su.count} Specialists &bull; 0h 00m):</strong> ${su.members.join(' &bull; ')}



                        </div>



                    `;



        });



        standbyUnitsHtml = `



                    <div style="margin-top:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 12px; page-break-inside:avoid; break-inside:avoid;">



                        <div style="font-size:9.5px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">



                            Standby Operational Units (${standbyUnitsList.length} Units &bull; Zero Field Submissions)



                        </div>



                        ${suRows}



                    </div>



                `;



    }







    let blockersHtml = '';



    if (blockersList.length > 0) {



        let bRows = '';



        blockersList.forEach(b => {



            bRows += `



                        <div style="margin-top:3px; font-size:11px; line-height:1.4; color:#991b1b;">



                            <strong style="color:#7f1d1d;">${b.member} (${b.team} &bull; ${b.project}):</strong> ${ftmEsc(b.blocker)}



                        </div>



                    `;



        });



        blockersHtml = `



                    <div style="margin-top:6px; margin-bottom:8px; background:#fef2f2; border:1px solid #fecaca; border-left:4px solid #dc2626; border-radius:6px; padding:6px 12px; page-break-inside:avoid; break-inside:avoid;">



                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">



                            <h4 style="margin:0; font-size:11px; font-weight:900; color:#991b1b; text-transform:uppercase; letter-spacing:0.03em;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:4px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/></svg>Critical Attention Ledger: Active Blockers (${blockersList.length})</h4>



                            <span style="font-size:8.5px; font-weight:800; color:#dc2626; background:#fee2e2; padding:1px 6px; border-radius:3px;">ESCALATION REQUIRED</span>



                        </div>



                        ${bRows}



                    </div>



                `;



    } else {



        blockersHtml = `



                    <div style="margin-top:6px; margin-bottom:8px; background:#f0fdf4; border:1px solid #bbf7d0; border-left:4px solid #16a34a; border-radius:6px; padding:6px 12px; font-size:11px; color:#166534; font-weight:600; page-break-inside:avoid; break-inside:avoid;">



                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:4px;"><path d="M20 6 9 17l-5-5"/></svg>Zero critical blockers reported across active operational units. All client deliverables are on track.



                    </div>



                `;



    }







    const logoSrc = window.ftmData ? window.ftmData.plugin_url + 'assets/images/logo.webp' : '';







    const scopeLabel = (selectedTeam === 'all') ? 'All Operational Units' : `Operational Unit: ${selectedTeam}`;







    const docContent = `



                <div class="ftm-print-sheet ftm-sheet-portrait">



                    <!-- Formal Corporate Masthead -->



                    <div class="ftm-p-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #0f172a; padding-bottom:6px; margin-bottom:8px;">



                        <div style="display:flex; align-items:center; gap:10px;">



                            ${logoSrc ? `<img src="${logoSrc}" style="width:32px; height:32px; border-radius:4px; object-fit:contain;" alt="FB International">` : ''}



                            <div>



                                <h1 style="margin:0; font-size:15px; font-weight:900; color:#0f172a; letter-spacing:-0.01em; line-height:1.1;">FB INTERNATIONAL</h1>



                                <div style="font-size:9px; color:#2563eb; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:1px;">Digital Production & Team Operations</div>



                            </div>



                        </div>



                        <div style="text-align:right;">



                            <h2 style="margin:0; font-size:12px; font-weight:900; color:#0f172a; text-transform:uppercase; letter-spacing:0.02em;">Daily Operations Executive Briefing</h2>



                            <div style="font-size:10.5px; font-weight:700; color:#2563eb; margin-top:1px;">${dateStr}</div>



                            <div style="font-size:8.5px; color:#64748b; margin-top:1px;">Scope: <strong>${scopeLabel}</strong> &bull; <span style="color:#047857; font-weight:700;">AUDIT VERIFIED</span></div>



                        </div>



                    </div>







                    <!-- Executive KPI Rollup -->



                    <div class="ftm-exec-kpi-grid" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:6px; margin-bottom:8px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:6px 10px; page-break-inside:avoid; break-inside:avoid;">



                        <div style="text-align:center;">



                            <div style="font-size:8px; font-weight:800; color:#64748b; text-transform:uppercase;">Staff Count</div>



                            <div style="font-size:14px; font-weight:900; color:#0f172a; margin-top:1px;">${workingMembers.length}</div>



                        </div>



                        <div style="text-align:center;">



                            <div style="font-size:8px; font-weight:800; color:#64748b; text-transform:uppercase;">Submissions</div>



                            <div style="font-size:14px; font-weight:900; color:#2563eb; margin-top:1px;">${submittedCount} <span style="font-size:9.5px; font-weight:600;">(${parityPct}%)</span></div>



                        </div>



                        <div style="text-align:center;">



                            <div style="font-size:8px; font-weight:800; color:#64748b; text-transform:uppercase;">Total Work Hours</div>



                            <div style="font-size:14px; font-weight:900; color:#10b981; margin-top:1px;">${self.formatHoursDisplay(totalH)}</div>



                        </div>



                        <div style="text-align:center;">



                            <div style="font-size:8px; font-weight:800; color:#64748b; text-transform:uppercase;">Tasks Finished</div>



                            <div style="font-size:14px; font-weight:900; color:#0f172a; margin-top:1px;">${totalDone}</div>



                        </div>



                        <div style="text-align:center;">



                            <div style="font-size:8px; font-weight:800; color:#64748b; text-transform:uppercase;">Blockers</div>



                            <div style="font-size:14px; font-weight:900; color:${totalBlockers > 0 ? '#dc2626' : '#10b981'}; margin-top:1px;">${totalBlockers}</div>



                        </div>



                    </div>







                    <!-- Blockers Box -->



                    ${blockersHtml}







                    <!-- Active Team Work Breakdown -->



                    ${activeTeamsHtml || '<div style="padding:16px; text-align:center; color:#64748b; font-size:12px;">No active unit deliverables logged for this date.</div>'}







                    <!-- Standby Units Summary Band -->



                    ${standbyUnitsHtml}







                    <!-- Boardroom Sign-off & Audit Certification -->



                    <div style="margin-top:10px; padding-top:6px; border-top:1px solid #cbd5e1; display:flex; justify-content:space-between; align-items:flex-end; page-break-inside:avoid; break-inside:avoid;">



                        <div>



                            <div style="font-size:10px; font-weight:800; color:#0f172a;">Compiled By: Operations Directorate</div>



                            <div style="font-size:8.5px; color:#64748b;">FB International Operations & Team Delivery</div>



                        </div>



                        <div style="text-align:right;">



                            <div style="font-size:8.5px; color:#94a3b8;">Document Generated: ${new Date().toLocaleString()}</div>



                            <div style="font-size:8.5px; color:#2563eb; font-weight:700;">Official Certified Operational Digest &bull; Page 1 of 1</div>



                        </div>



                    </div>



                </div>



            `;







    body.html(docContent);



    body.css({ opacity: '0', transition: 'none' });



    requestAnimationFrame(() => {



        body.css({ opacity: '1', transition: 'opacity 0.2s ease' });



    });



    modal.removeClass('hidden');



},







copyExecutiveDocText() {



    const docEl = document.getElementById('ftm-exec-doc-body');



    if (!docEl) return;



    const text = docEl.innerText || docEl.textContent;



    navigator.clipboard.writeText(text).then(() => {



        this.toast('Executive briefing text copied to clipboard!', 'success');



    }).catch(() => {



        this.toast('Failed to copy to clipboard', 'error');



    });



},







openWeeklyReportModal(startDateIso, selectedTeam = 'all', direction = 0) {



    const self = this;



    const modal = $('#ftm-modal-weekly-report');



    const content = $('#ftm-weekly-report-content');







    // Calculate 7-day range



    let start;



    if (startDateIso) {



        start = new Date(startDateIso + 'T12:00:00');



    } else {



        // Default to past 7 days ending today



        start = new Date();



        start.setDate(start.getDate() - 6);



    }







    const end = new Date(start);



    end.setDate(end.getDate() + 6);







    const startStr = start.toISOString().split('T')[0];



    const endStr = end.toISOString().split('T')[0];







    self.state.weeklyReportStart = startStr;



    self.state.weeklyReportTeam = selectedTeam;







    const formatRange = `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;



    $('#ftm-weekly-range-label').text(formatRange);







    $('#ftm-weekly-team-pills .ftm-team-pill').removeClass('active');



    $(`#ftm-weekly-team-pills .ftm-team-pill[data-team="${selectedTeam}"]`).addClass('active');







    const isInitialOpen = modal.hasClass('hidden') || !content.children().length;



    modal.removeClass('hidden');







    if (isInitialOpen) {



        content.html('<div style="text-align:center; padding:60px 0;"><div class="ftm-spinner" style="margin:0 auto 12px auto;"></div><div class="ftm-text-muted">Loading weekly rollup and member analytics...</div></div>');



    } else {



        // Keep table layout intact during week shift to eliminate jumpy CLS



        content.css({



            opacity: '0.6',



            filter: 'blur(1px)',



            pointerEvents: 'none',



            transition: 'all 0.15s ease'



        });



    }







    self.api(`daily-reports/weekly-summary?start_date=${startStr}&end_date=${endStr}&team=${encodeURIComponent(selectedTeam)}`).then(res => {



        self.renderWeeklySummaryModalContent(res, direction);



    }).catch(err => {



        content.css({ opacity: '1', pointerEvents: 'auto' });



        content.html(`<div class="ftm-text-danger" style="text-align:center; padding:40px;">Error loading weekly report: ${err.message || 'Unknown error'}</div>`);



    });



},







shiftWeeklyReport(weeks) {



    const curStart = this.state.weeklyReportStart || new Date().toISOString().split('T')[0];



    const d = new Date(curStart + 'T12:00:00');



    d.setDate(d.getDate() + (weeks * 7));



    this.openWeeklyReportModal(d.toISOString().split('T')[0], this.state.weeklyReportTeam || 'all', weeks);



},







renderWeeklySummaryModalContent(data, direction = 0) {



    const self = this;



    self.state.weeklyReportData = data;



    const content = $('#ftm-weekly-report-content');



    const members = data.members || [];



    const logoSrc = window.ftmData ? window.ftmData.plugin_url + 'assets/images/logo.webp' : '';



    const teamScope = self.state.weeklyReportTeam === 'all' ? 'All Operational Units' : `Operational Unit: ${self.state.weeklyReportTeam}`;



    const formatRange = $('#ftm-weekly-range-label').text() || '7-Day Operations Rollup';







    let html = `



                <!-- Dedicated Print Header for PDF / Print Output -->



                <div class="ftm-print-only-header" style="justify-content:space-between; align-items:flex-start; border-bottom:2px solid #0f172a; padding-bottom:14px; margin-bottom:20px;">



                    <div style="display:flex; align-items:center; gap:10px;">



                        ${logoSrc ? `<img src="${logoSrc}" style="width:40px; height:40px; border-radius:8px; object-fit:contain;" alt="FB International">` : ''}



                        <div>



                            <h1 style="margin:0; font-size:18px; font-weight:800; color:#0f172a; letter-spacing:-0.02em;">FB INTERNATIONAL</h1>



                            <div style="font-size:11px; color:#475569; font-weight:600; text-transform:uppercase; letter-spacing:0.06em;">Digital Production & Team Operations</div>



                        </div>



                    </div>



                    <div style="text-align:right;">



                        <h2 style="margin:0; font-size:14px; font-weight:800; color:#2563eb; text-transform:uppercase;">Weekly Performance & Capacity Rollup</h2>



                        <div style="font-size:12px; font-weight:600; color:#0f172a; margin-top:2px;">${formatRange}</div>



                        <div style="font-size:11px; color:#475569; margin-top:2px;">Scope: <strong>${teamScope}</strong></div>



                    </div>



                </div>







                <!-- 4 KPI Cards -->



                <div class="ftm-weekly-kpi-grid">



                    <div class="ftm-weekly-kpi-card" style="--card-accent:#2563eb;">



                        <div class="ftm-weekly-kpi-icon-wrap" style="width:44px; height:44px; border-radius:10px; background:rgba(37, 99, 235, 0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                        </div>



                        <div>



                            <div class="ftm-weekly-kpi-lbl" style="font-size:12px; font-weight:600; color:var(--ftm-text-muted);">Weekly Hours</div>



                            <div class="ftm-weekly-kpi-val" style="font-size:20px; font-weight:800; color:var(--ftm-text-primary); margin-top:2px;">${self.formatHoursDisplay(data.total_weekly_hours)}</div>



                            <div class="ftm-weekly-kpi-sub" style="font-size:11px; color:#10b981; margin-top:2px;">&uarr; 7-Day Cumulative</div>



                        </div>



                    </div>







                    <div class="ftm-weekly-kpi-card" style="--card-accent:#10b981;">



                        <div class="ftm-weekly-kpi-icon-wrap" style="width:44px; height:44px; border-radius:10px; background:rgba(16, 185, 129, 0.1); color:#10b981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>



                        </div>



                        <div>



                            <div class="ftm-weekly-kpi-lbl" style="font-size:12px; font-weight:600; color:var(--ftm-text-muted);">Completed Tasks</div>



                            <div class="ftm-weekly-kpi-val text-emerald" style="font-size:20px; font-weight:800; margin-top:2px;">${data.total_tasks_completed || 0}</div>



                            <div class="ftm-weekly-kpi-sub" style="font-size:11px; color:#10b981; margin-top:2px;">Deliverables logged</div>



                        </div>



                    </div>







                    <div class="ftm-weekly-kpi-card" style="--card-accent:${data.total_blockers > 0 ? '#ef4444' : '#64748b'};">



                        <div class="ftm-weekly-kpi-icon-wrap" style="width:44px; height:44px; border-radius:10px; background:${data.total_blockers > 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(100, 116, 139, 0.1)'}; color:${data.total_blockers > 0 ? '#ef4444' : '#64748b'}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>



                        </div>



                        <div>



                            <div class="ftm-weekly-kpi-lbl" style="font-size:12px; font-weight:600; color:var(--ftm-text-muted);">Blockers / Flags</div>



                            <div class="ftm-weekly-kpi-val" style="font-size:20px; font-weight:800; color:${data.total_blockers > 0 ? '#ef4444' : 'var(--ftm-text-primary)'}; margin-top:2px;">${data.total_blockers || 0}</div>



                            <div class="ftm-weekly-kpi-sub" style="font-size:11px; color:${data.total_blockers > 0 ? '#ef4444' : '#64748b'}; margin-top:2px;">${data.total_blockers > 0 ? 'Requires attention' : 'All clear'}</div>



                        </div>



                    </div>







                    <div class="ftm-weekly-kpi-card" style="--card-accent:#8b5cf6;">



                        <div class="ftm-weekly-kpi-icon-wrap" style="width:44px; height:44px; border-radius:10px; background:rgba(139, 92, 246, 0.1); color:#8b5cf6; display:flex; align-items:center; justify-content:center; flex-shrink:0;">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>



                        </div>



                        <div>



                            <div class="ftm-weekly-kpi-lbl" style="font-size:12px; font-weight:600; color:var(--ftm-text-muted);">Active Projects</div>



                            <div class="ftm-weekly-kpi-val" style="font-size:20px; font-weight:800; color:#8b5cf6; margin-top:2px;">${data.active_projects_count || 0}</div>



                            <div class="ftm-weekly-kpi-sub" style="font-size:11px; color:#8b5cf6; margin-top:2px;">Milestones in flight</div>



                        </div>



                    </div>



                </div>







                <!-- Weekly Team Table -->



                <div class="ftm-table-responsive" style="background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:12px; overflow:hidden;">



                    <table class="ftm-table ftm-table-hover" style="margin:0;">



                        <thead>



                            <tr>



                                <th style="width:36px;">#</th>



                                <th>Member</th>



                                <th>Team</th>



                                <th>Days Active</th>



                                <th style="white-space:nowrap; min-width:85px; text-align:center;">Weekly Hours</th>



                                <th style="text-align:center;">Completed</th>



                                <th style="text-align:center;">In Progress</th>



                                <th style="text-align:center;">Blockers</th>



                                <th>Projects Worked</th>



                            </tr>



                        </thead>



                        <tbody>



            `;







    if (members.length === 0) {



        html += '<tr><td colspan="9" class="ftm-text-muted" style="text-align:center; padding:30px;">No members found for this team in this period.</td></tr>';



    } else {



        members.forEach((m, idx) => {



            const teamColorClass = m.team === 'WpCraft' ? 'bg-blue-soft text-blue' : (m.team === 'WpNexus' ? 'bg-purple-soft text-purple' : 'bg-amber-soft text-amber');



            const projectsText = (m.projects && m.projects.length > 0)



                ? m.projects.slice(0, 2).map(p => `<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="margin:1px 2px;">${self.escapeHtml(p)}</span>`).join('') + (m.projects.length > 2 ? ` <span class="ftm-badge bg-slate-soft text-muted ftm-badge-xs">+${m.projects.length - 2}</span>` : '')



                : '<span class="ftm-text-muted ftm-text-xs">-</span>';







            const daysActiveText = `<span style="font-weight:600;">${m.days_worked}d work</span>` +



                (m.days_leave > 0 ? ` &bull; <span style="color:#d97706;">${m.days_leave}d leave</span>` : '') +



                (m.days_weekend > 0 ? ` &bull; <span style="color:#64748b;">${m.days_weekend}d off</span>` : '');







            html += `



                        <tr class="${(parseFloat(m.total_hours) || 0) > 0 || (m.tasks_completed || 0) > 0 ? 'ftm-row-active' : 'ftm-row-idle'}">



                            <td style="color:var(--ftm-text-muted); font-size:12px;">${idx + 1}</td>



                            <td class="ftm-wk-member">



                                <div style="display:flex; align-items:center; gap:10px;">



                                    <img src="${m.avatar || ''}" class="ftm-avatar ftm-avatar-sm" alt="${ftmEsc(m.name)}">



                                    <div>



                                        <strong style="font-size:13px; color:var(--ftm-text-primary);">${ftmEsc(m.name)}</strong>



                                        <div class="ftm-text-xs ftm-text-muted">${m.role_label || 'WordPress Developer'}</div>



                                    </div>



                                </div>



                            </td>



                            <td><span class="ftm-badge ${teamColorClass} ftm-badge-xs">${m.team}</span></td>



                            <td style="font-size:12px; white-space:nowrap;">${daysActiveText}</td>



                            <td style="font-weight:700; font-size:13px; white-space:nowrap; min-width:85px; text-align:center; color:#2563eb;">${self.formatHoursDisplay(m.total_hours)}</td>



                            <td style="font-weight:600; color:#10b981; text-align:center;">${m.tasks_completed || 0}</td>



                            <td style="font-weight:600; color:#3b82f6; text-align:center;">${m.wip_count || 0}</td>



                            <td style="text-align:center;">${m.blockers_count > 0 ? `<span class="ftm-badge bg-rose-soft text-rose ftm-badge-xs" style="font-weight:700;">${m.blockers_count}</span>` : '<span class="ftm-text-muted">0</span>'}</td>



                            <td>${projectsText}</td>



                        </tr>



                    `;



        });



    }







    html += `



                        </tbody>



                    </table>



                </div>







                <!-- Dedicated Professional Print Footer -->



                <div class="ftm-print-only-footer" style="margin-top:24px; padding-top:14px; border-top:1.5px solid #0f172a; justify-content:space-between; align-items:flex-end;">



                    <div>



                        <div style="font-size:11px; font-weight:700; color:#0f172a;">FB INTERNATIONAL &bull; DIGITAL OPERATIONS & TEAM PERFORMANCE</div>



                        <div style="font-size:10px; color:#64748b; margin-top:2px;">Official confidential operations document &bull; Internal executive & operational review</div>



                    </div>



                    <div style="text-align:right;">



                        <div style="font-size:10px; color:#475569;">Generated: ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div>



                        <div style="font-size:10px; font-weight:600; color:#2563eb; margin-top:2px;">Status: Certified Rollup</div>



                    </div>



                </div>



            `;







    content.html(html);







    // Silky smooth, glitch-free reveal



    requestAnimationFrame(() => {



        content.css({



            opacity: '1',



            filter: 'none',



            transform: 'none',



            pointerEvents: 'auto',



            transition: 'opacity 0.2s ease, filter 0.2s ease'



        });



    });



},







openMemberReportReviewModal(rId) {



    const self = this;



    const reports = self.state.teamDailyReports || [];



    const members = self.state.teamMembers || [];



    const report = reports.find(r => parseInt(r.id) === parseInt(rId));



    if (!report) {



        self.toast('Report details not found', 'error');



        return;



    }







    const member = members.find(m => parseInt(m.id) === parseInt(report.user_id)) || {



        name: report.user_name || 'Team Member',



        team: report.team_name || 'WpCraft',



        role_label: 'Team Member',



        avatar: report.user_avatar || ''



    };







    const modal = $('#ftm-modal-report-review');



    const body = $('#ftm-review-modal-body');







    // Header info



    $('#ftm-review-member-avatar').attr('src', member.avatar || '');



    $('#ftm-review-member-name').text(member.name);



    $('#ftm-review-member-role').text(member.role_label || 'WordPress Developer');



    $('#ftm-review-team-badge').text(member.team).attr('class', `ftm-badge ${member.team === 'WpCraft' ? 'bg-blue-soft text-blue' : (member.team === 'WpNexus' ? 'bg-purple-soft text-purple' : 'bg-amber-soft text-amber')} ftm-badge-xs`);







    // Date & Status



    const d = new Date(report.report_date + 'T12:00:00');



    $('#ftm-review-report-date').text(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));







    let statusClass = 'bg-blue-soft text-blue';



    let statusLabel = 'Submitted';



    if (report.status === 'approved') {



        statusClass = 'bg-emerald-soft text-emerald';



        statusLabel = 'Approved';



    } else if (report.status === 'revision_requested') {



        statusClass = 'bg-amber-soft text-amber';



        statusLabel = 'Revision Requested';



    }



    $('#ftm-review-status-badge').attr('class', `ftm-badge ${statusClass} ftm-badge-xs`).text(statusLabel);







    // Build Body



    let bodyHtml = '';







    if (report.report_type === 'weekend') {



        bodyHtml = `



                    <div style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:12px; padding:30px; text-align:center;">



                        <div style="font-size:36px; margin-bottom:10px;"><span class="dashicons dashicons-palmtree"></span></div>



                        <h4 style="margin:0 0 6px 0; font-size:16px; font-weight:700; color:var(--ftm-text-primary);">Scheduled Weekend / Weekly Off Day</h4>



                        <p style="margin:0; font-size:13px; color:var(--ftm-text-muted);">Member logged this date as their regular weekend off day.</p>



                    </div>



                `;



    } else if (report.report_type === 'on_leave') {
        const rangeStr = (report.leave_start_date && report.leave_end_date && report.leave_start_date !== report.leave_end_date)
            ? `${report.leave_start_date} &rarr; ${report.leave_end_date}`
            : (report.leave_start_date || report.report_date);
        const canManageLeave = self.state.isAdmin || self.state.isLeader;
        bodyHtml = `
                    <div class="ftm-amber-panel" style="background:#fffbeb; border:1px solid #fef3c7; border-radius:12px; padding:24px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:26px;"><span class="dashicons dashicons-calendar-alt"></span></span>
                                <div>
                                    <h4 style="margin:0; font-size:16px; font-weight:700; color:#b45309;">On Leave: ${self.escapeHtml(report.leave_type_label || 'Leave')}</h4>
                                    <div style="font-size:12px; color:#92400e; margin-top:2px;">Duration: <strong>${rangeStr}</strong></div>
                                </div>
                            </div>
                            ${canManageLeave ? `
                                <div style="display:flex; gap:8px;">
                                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-modal-edit-leave" data-leave-id="${report.leave_id || ''}" style="font-size:12px;">
                                        ✏️ Change Dates
                                    </button>
                                    <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm text-rose" id="ftm-btn-modal-delete-leave" data-leave-id="${report.leave_id || ''}" data-report-id="${report.id}" style="border-color:rgba(244,63,94,0.4); font-size:12px;">
                                        🗑️ Delete / Clear Leave
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                        <div class="ftm-amber-panel-inner" style="background:#ffffff; border:1px solid #fde68a; border-radius:8px; padding:12px 16px; font-size:13px; color:#475569;">
                            <strong style="color:#0f172a;">Reason / Notice:</strong> ${self.escapeHtml(report.leave_reason || 'N/A')}
                        </div>
                    </div>
                `;



    } else {
        // Delivered Projects Today Section
        if (report.delivered_projects && report.delivered_projects.length > 0) {
            bodyHtml += `
                <div style="margin-bottom:16px;">
                    <div style="font-size:12.5px; font-weight:800; color:#059669; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">
                        🚀 Delivered Projects Today (${report.delivered_projects.length})
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:10px;">
            `;
            report.delivered_projects.forEach(dp => {
                const isOntime = dp.delivered_without_extension == 1;
                bodyHtml += `
                    <div style="background:rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.25); border-radius:10px; padding:12px 14px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary);">${self.escapeHtml(dp.order_id || '')} &bull; ${self.escapeHtml(dp.client_name || '')}</div>
                            <div style="font-size:11.5px; color:var(--ftm-text-muted); margin-top:2px;">
                                ${self.escapeHtml(dp.profile_name || '')} &bull; Net: <strong style="color:#059669;">$${parseFloat(dp.net_value || 0).toFixed(2)}</strong>
                                ${dp.star_rating > 0 ? ` &bull; ⭐ ${parseFloat(dp.star_rating).toFixed(1)}` : ''}
                            </div>
                        </div>
                        <span class="ftm-badge ${isOntime ? 'bg-emerald-soft text-emerald' : 'bg-amber-soft text-amber'} ftm-badge-xs" style="font-weight:700;">
                            ${isOntime ? 'On-Time' : `Extended (${dp.extension_count}x)`}
                        </span>
                    </div>
                `;
            });
            bodyHtml += `</div></div>`;
        }

        // Extended Projects Today Section
        if (report.extended_projects && report.extended_projects.length > 0) {
            bodyHtml += `
                <div style="margin-bottom:16px;">
                    <div style="font-size:12.5px; font-weight:800; color:#d97706; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">
                        ⏱ Extended Projects Today (${report.extended_projects.length})
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:10px;">
            `;
            report.extended_projects.forEach(ep => {
                bodyHtml += `
                    <div style="background:rgba(245, 158, 11, 0.05); border:1px solid rgba(245, 158, 11, 0.25); border-radius:10px; padding:12px 14px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary);">${self.escapeHtml(ep.order_id || '')} &bull; ${self.escapeHtml(ep.client_name || '')}</div>
                            <span class="ftm-badge bg-amber-soft text-amber ftm-badge-xs" style="font-weight:700;">
                                ${ep.extension_count}x Extended (+${ep.extension_days}d)
                            </span>
                        </div>
                        <div style="font-size:11.5px; color:var(--ftm-text-muted); margin-top:4px;">
                            Reason: <em>${self.escapeHtml(ep.extension_reason || 'Client requested revision/more time')}</em>
                        </div>
                    </div>
                `;
            });
            bodyHtml += `</div></div>`;
        }

        if (report.items && report.items.length > 0) {



        let totalHours = 0;



        report.items.forEach(it => { totalHours += parseFloat(it.hours_spent || 0); });







        bodyHtml += `



                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:10px 16px;">



                        <span style="font-size:13px; font-weight:700; color:var(--ftm-text-primary);">Logged Deliverables (${report.items.length} Project${report.items.length > 1 ? 's' : ''})</span>



                        <span style="font-size:14px; font-weight:800; color:#2563eb;">Total Logged: ${self.formatHoursDisplay(totalHours)}</span>



                    </div>



                `;







        report.items.forEach((it, idx) => {



            bodyHtml += `



                        <div style="background:var(--ftm-card-bg, #ffffff); border:1px solid var(--ftm-border); border-radius:12px; padding:18px; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">



                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid var(--ftm-border-light);">



                                <div style="display:flex; align-items:center; gap:8px;">



                                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="font-weight:700;">#${idx + 1}</span>



                                    <strong style="font-size:14px; color:var(--ftm-text-primary);">${self.escapeHtml(it.client_name || it.order_id || 'Project')}</strong>



                                    ${it.order_id ? `<span class="ftm-badge ftm-badge-outline ftm-badge-xs">#${self.escapeHtml(it.order_id)}</span>` : ''}



                                </div>



                                <span style="font-size:13px; font-weight:700; color:#2563eb;">${self.formatHoursDisplay(it.hours_spent)}</span>



                            </div>







                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-bottom:12px;">



                                <div style="background:rgba(16, 185, 129, 0.06); border:1px solid rgba(16, 185, 129, 0.2); border-radius:8px; padding:10px 12px;">



                                    <div style="font-size:11px; font-weight:700; color:#059669; text-transform:uppercase; margin-bottom:4px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:4px;"><path d="M20 6 9 17l-5-5"/></svg>What is completed?</div>



                                    <div style="font-size:12.5px; color:var(--ftm-text-primary); line-height:1.5; white-space:pre-line;">${self.escapeHtml(it.tasks_completed || 'None logged')}</div>



                                </div>



                                <div style="background:rgba(245, 158, 11, 0.06); border:1px solid rgba(245, 158, 11, 0.2); border-radius:8px; padding:10px 12px;">



                                    <div style="font-size:11px; font-weight:700; color:#d97706; text-transform:uppercase; margin-bottom:4px;">⚙ What are you working on now?</div>



                                    <div style="font-size:12.5px; color:var(--ftm-text-primary); line-height:1.5; white-space:pre-line;">${self.escapeHtml(it.work_in_progress || 'None logged')}</div>



                                </div>



                            </div>







                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-bottom:12px;">



                                <div style="background:${it.has_blocker ? 'rgba(239, 68, 68, 0.08)' : 'var(--ftm-bg, #f8fafc)'}; border:1px solid ${it.has_blocker ? 'rgba(239, 68, 68, 0.25)' : 'var(--ftm-border)'}; border-radius:8px; padding:10px 12px;">



                                    <div style="font-size:11px; font-weight:700; color:${it.has_blocker ? '#dc2626' : 'var(--ftm-text-muted)'}; text-transform:uppercase; margin-bottom:4px;">${it.has_blocker ? '⚠ Any blockers or issues?' : 'Blockers / Issues'}</div>



                                    <div style="font-size:12.5px; color:${it.has_blocker ? '#dc2626' : 'var(--ftm-text-muted)'}; line-height:1.5; font-weight:${it.has_blocker ? '600' : '400'}; white-space:pre-line;">${self.escapeHtml(it.has_blocker ? (it.blocker_description || 'Need help') : 'No blockers')}</div>



                                </div>



                                <div style="background:rgba(59, 130, 246, 0.06); border:1px solid rgba(59, 130, 246, 0.2); border-radius:8px; padding:10px 12px;">



                                    <div style="font-size:11px; font-weight:700; color:#2563eb; text-transform:uppercase; margin-bottom:4px;">📅 Next Plan / Tomorrow</div>



                                    <div style="font-size:12.5px; color:var(--ftm-text-primary); line-height:1.5; white-space:pre-line;">${self.escapeHtml(it.next_day_plan || 'None logged')}</div>



                                </div>



                            </div>







                            ${it.estimated_progress ? `



                                <div style="margin-top:8px;">



                                    <div style="display:flex; justify-content:space-between; font-size:11.5px; font-weight:600; color:var(--ftm-text-muted); margin-bottom:4px;">



                                        <span>Est. Project Progress</span>



                                        <span style="color:#2563eb;">${parseInt(it.estimated_progress)}%</span>



                                    </div>



                                    <div class="ftm-progress-bar" style="height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">



                                        <div class="ftm-progress-fill" style="width:${parseInt(it.estimated_progress)}%; height:100%; background:#2563eb; border-radius:3px;"></div>



                                    </div>



                                </div>



                            ` : ''}



                        </div>



                    `;



                });



            } else if ((!report.delivered_projects || !report.delivered_projects.length) && (!report.extended_projects || !report.extended_projects.length)) {

                bodyHtml += '<div style="text-align:center; padding:24px; color:var(--ftm-text-muted); background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px;">No task deliverables logged for this report.</div>';

            }

        }



        if (report.manager_feedback) {



                bodyHtml += `



                    <div style="margin-top:16px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:14px 18px;">



                        <div style="font-size:12px; font-weight:700; color:#1d4ed8; text-transform:uppercase; margin-bottom:4px;">💬 Manager Feedback / Instructions</div>



                        <div style="font-size:13px; color:#1e40af;">${self.escapeHtml(report.manager_feedback)}</div>



                    </div>



                `;



            }







            body.html(bodyHtml);







            // Bind review actions



            $('#ftm-btn-review-revision').off('click').on('click', function(e) {



                e.preventDefault();



                modal.addClass('hidden');



                self.reviewReportAction(report.id, 'revision');



            });







            $('#ftm-btn-review-approve').off('click').on('click', function(e) {
                e.preventDefault();
                modal.addClass('hidden');
                self.reviewReportAction(report.id, 'approve');
            });

            $('#ftm-btn-review-reset').off('click').on('click', function(e) {
                e.preventDefault();
                if (!confirm('Reset this report so the member can submit a fresh work report?')) return;
                modal.addClass('hidden');
                self.reviewReportAction(report.id, 'reset');
            });







            $('#ftm-btn-review-close, #ftm-modal-review-close').off('click').on('click', function(e) {



                e.preventDefault();



                modal.addClass('hidden');



            });







            // If current viewer is the report owner, show edit button



            const currentUserId = window.ftmData ? parseInt(window.ftmData.current_user_id || 0) : 0;



            const authorActions = $('#ftm-review-author-actions');



            if (currentUserId && parseInt(report.user_id) === currentUserId) {



                authorActions.html(`



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-review-edit-own">



                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>



                        <span>Edit My Standup</span>



                    </button>



                `);



                $('#ftm-btn-review-edit-own').on('click', function(e) {



                    e.preventDefault();



                    modal.addClass('hidden');



                    self.openDailyStandupModalForEdit(report.id);



                });



            } else {



                authorActions.empty();



            }







            modal.removeClass('hidden');



        },







        reviewReportAction(id, action) {
        const self = this;
        const modal = $('#ftm-modal-report-feedback');
        if (!modal.length) {
            const note = prompt(action === 'revision' ? 'Enter feedback/correction request for member:' : (action === 'reset' ? 'Reason for resetting report:' : 'Enter optional approval remarks:'));
            if (note === null) return;
            this.api(`daily-reports/${id}/review`, 'POST', { action: action, feedback: note || '' }).then(res => {
                self.toast(res.message || 'Report reviewed successfully!', 'success');
                self.loadTeamDailyReportView();
            }).catch(err => {
                self.toast(err.message || 'Error reviewing report', 'error');
            });
            return;
        }

        $('#ftm-feedback-report-id').val(id);
        $('#ftm-feedback-action').val(action);
        $('#ftm-feedback-notes').val('');

        if (action === 'revision') {
            $('#ftm-modal-feedback-title').text('Request Report Revision');
            $('#ftm-feedback-label').text('Enter Revision / Correction Request:');
            $('#ftm-feedback-notes').attr('placeholder', 'Describe the deliverables needed or corrections required...');
            $('#ftm-modal-feedback-confirm').text('Request Revision').removeClass('ftm-btn-primary ftm-btn-danger').addClass('ftm-btn-warning');
        } else if (action === 'reset') {
            $('#ftm-modal-feedback-title').text('Reset Report Mistake');
            $('#ftm-feedback-label').text('Reason for Resetting Report:');
            $('#ftm-feedback-notes').attr('placeholder', 'Enter note for specialist...');
            $('#ftm-modal-feedback-confirm').text('Reset Report').removeClass('ftm-btn-primary ftm-btn-warning').addClass('ftm-btn-danger');
        } else {
            $('#ftm-modal-feedback-title').text('Approve Daily Report');
            $('#ftm-feedback-label').text('Enter Optional Approval Remarks:');
            $('#ftm-feedback-notes').attr('placeholder', 'Optional feedback or praise for specialist...');
            $('#ftm-modal-feedback-confirm').text('✓ Confirm Approval').removeClass('ftm-btn-warning ftm-btn-danger').addClass('ftm-btn-primary');
        }

        modal.removeClass('hidden');

        $('#ftm-modal-feedback-close, #ftm-modal-feedback-cancel').off('click').on('click', function(e) {
            e.preventDefault();
            modal.addClass('hidden');
        });

        $('#ftm-modal-feedback-confirm').off('click').on('click', function(e) {
            e.preventDefault();
            const rId = $('#ftm-feedback-report-id').val();
            const act = $('#ftm-feedback-action').val();
            const feedback = $('#ftm-feedback-notes').val().trim();
            modal.addClass('hidden');

            self.api(`daily-reports/${rId}/review`, 'POST', {
                action: act,
                feedback: feedback
            }).then(res => {
                self.toast(res.message || 'Report reviewed successfully!', 'success');
                self.loadTeamDailyReportView();
            }).catch(err => {
                self.toast(err.message || 'Error reviewing report', 'error');
            });
        });
    },







        forwardTeamDailyReport() {



            const self = this;



            const date = $('#ftm-team-report-date').val();



            const notes = prompt('Enter consolidated leadership remarks for Super Admin / Manager:');



            if (notes === null) return;







            this.api('daily-reports/team-forward', 'POST', {



                report_date: date,



                summary_notes: notes



            }).then(() => {



                self.toast('Team Daily Status Report successfully forwarded to Super Admin / Manager!', 'success');



            }).catch(err => {



                self.toast(err.message || 'Error forwarding report', 'error');



            });



        },

        formatOverviewMoney(v) {
            const num = parseFloat(v) || 0;
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        loadOverviewView(targetMonth) {
            const self = this;
            const monthSelect = $('#ftm-overview-month-select');
            const targetMonthVal = targetMonth || monthSelect.val() || (new Date().toISOString().slice(0, 7));

            if (monthSelect.length && monthSelect.val() !== targetMonthVal) {
                monthSelect.val(targetMonthVal);
            }

            const selectedOptionText = monthSelect.find(`option[value="${targetMonthVal}"]`).text() || targetMonthVal;
            $('#ftm-overview-month-pill').text(selectedOptionText);

            const refreshBtn = $('#ftm-overview-refresh-btn');
            refreshBtn.find('svg').addClass('ftm-spin');

            const isDeputy = !!this.state.isDeputy;
            const isAdmin = !!this.state.isAdmin;
            const isLeader = !isAdmin && !isDeputy && !!this.state.isLeader;
            const isMember = !isAdmin && !isLeader && !isDeputy;
            const currentUserId = (window.ftmData && window.ftmData.current_user) ? parseInt(window.ftmData.current_user.id, 10) : 0;
            const userTeam = this.state.userTeam || (window.ftmData && window.ftmData.current_user ? window.ftmData.current_user.team : 'WpCraft') || 'WpCraft';
            const currentUserName = (window.ftmData && window.ftmData.current_user ? window.ftmData.current_user.name : '') || 'Specialist';

            const roleBadge = $('#ftm-overview-role-badge');
            if (isAdmin) {
                roleBadge.text('Super Admin / Operations • Agency-Wide Scope')
                    .removeClass('bg-blue-soft text-blue bg-indigo-soft text-indigo bg-amber-soft text-amber')
                    .addClass('bg-emerald-soft text-emerald');
                $('#ftm-overview-subtitle').text(`Real-time agency operational drivers, financial reconciliation & specialist deliverable tracking for ${selectedOptionText}.`);
            } else if (isDeputy) {
                roleBadge.text('Chairman / Executive • Agency-Wide Strategic Scope')
                    .removeClass('bg-blue-soft text-blue bg-indigo-soft text-indigo bg-emerald-soft text-emerald')
                    .addClass('bg-amber-soft text-amber');
                $('#ftm-overview-subtitle').text(`Executive operational milestones, financial health & company-wide revenue reconciliation for ${selectedOptionText}.`);
            } else if (isLeader) {
                roleBadge.text(`Team Leader • ${userTeam} Squad Scope`)
                    .removeClass('bg-amber-soft text-amber bg-indigo-soft text-indigo bg-emerald-soft text-emerald')
                    .addClass('bg-blue-soft text-blue');
                $('#ftm-overview-subtitle').text(`${userTeam} squad performance, WIP deliverables, repeat orders & specialist capacity for ${selectedOptionText}.`);
            } else {
                roleBadge.text(`Specialist • ${currentUserName} Performance Scope`)
                    .removeClass('bg-amber-soft text-amber bg-blue-soft text-blue bg-emerald-soft text-emerald')
                    .addClass('bg-indigo-soft text-indigo');
                $('#ftm-overview-subtitle').text(`Your personal delivery achievements, active assignments, reviews & repeat client retention for ${selectedOptionText}.`);
            }

            $('#ftm-overview-kpi-grid').html(`
                <div class="ftm-table-loading" style="grid-column: 1 / -1; text-align:center; padding:30px;">
                    <div class="ftm-spinner"></div> Compiling live operational metrics for ${selectedOptionText}...
                </div>
            `);
            $('#ftm-overview-specialists-tbody').html(`
                <tr><td colspan="12" class="ftm-table-loading" style="text-align:center; padding:40px;">
                    <div class="ftm-spinner"></div> Reconciling specialist deliverables and revenue...
                </td></tr>
            `);
            $('#ftm-overview-deliverables-tbody').html(`
                <tr><td colspan="9" class="ftm-table-loading" style="text-align:center; padding:30px;">
                    <div class="ftm-spinner"></div> Loading active deliverables pipeline...
                </td></tr>
            `);

            Promise.all([
                this.api(`reports/monthly?month=${encodeURIComponent(targetMonthVal)}`),
                this.api(`projects?month=${encodeURIComponent(targetMonthVal)}&status=active&per_page=100`)
            ]).then(([monthlyRes, projectsRes]) => {
                refreshBtn.find('svg').removeClass('ftm-spin');

                self.state.overviewMonthlyData = monthlyRes;
                self.state.overviewProjectsData = projectsRes;
                
                // For admin, executive, or management, default team filter is ALWAYS 'all'
                if (isAdmin || isDeputy || userTeam.toLowerCase() === 'management') {
                    self.state.overviewActiveTeamFilter = 'all';
                } else if (isLeader) {
                    self.state.overviewActiveTeamFilter = userTeam;
                } else {
                    self.state.overviewActiveTeamFilter = 'all';
                }

                self.state.overviewSearchQuery = '';
                self.state.overviewTargetMonth = targetMonthVal;

                const roleContext = { isAdmin, isDeputy, isLeader, isMember, currentUserId, userTeam, currentUserName, targetMonthVal };

                self.renderOverviewKpis(monthlyRes, roleContext);
                self.renderOverviewSquads(monthlyRes, roleContext);
                self.renderOverviewSpecialists(monthlyRes, roleContext);
                self.renderOverviewDeliverables(projectsRes, roleContext);
            }).catch(err => {
                refreshBtn.find('svg').removeClass('ftm-spin');
                $('#ftm-overview-kpi-grid').html(`<div style="grid-column: 1 / -1; padding:20px; color:#ef4444; text-align:center;">Failed to load overview data: ${err.message || 'Unknown error'}</div>`);
                self.toast(err.message || 'Failed to load overview data', 'error');
            });
        },

        renderOverviewKpis(res, ctx) {
            const grid = $('#ftm-overview-kpi-grid');
            if (!grid.length) return;

            const rows = res.rows || [];
            const totals = res.totals || {};

            let delVal = 0, delNet = 0, tipsVal = 0, delCount = 0;
            let submVal = 0, submCount = 0;
            let wipVal = 0, wipCount = 0;
            let repeatVal = 0, repeatCount = 0;
            let stars5 = 0, totalReviews = 0, ratingSum = 0;
            let onTimeCount = 0, lateCount = 0, extCount = 0, extDays = 0;
            let assignedCount = 0, assignedVal = 0, newAssignedCount = 0, newAssignedVal = 0, carryCount = 0, carryVal = 0;
            let cancelledCount = 0, cancelledVal = 0;

            if (ctx.isAdmin || ctx.isDeputy) {
                delVal = parseFloat(totals.total_delivery || 0);
                delNet = parseFloat(totals.delivery_net || (totals.total_delivery - totals.total_tips) || 0);
                tipsVal = parseFloat(totals.total_tips || totals.tips || 0);
                delCount = parseInt(totals.delivery_count || totals.total_completed || 0, 10);

                submVal = parseFloat(totals.total_submitted || totals.submitted_val || 0);
                submCount = parseInt(totals.submitted_count || 0, 10);

                wipVal = parseFloat(totals.total_wip || 0);
                wipCount = parseInt(totals.wip_count || 0, 10);

                repeatVal = parseFloat(totals.repeat_val || totals.repeat_net_value || 0);
                repeatCount = parseInt(totals.repeat_count || totals.repeat_orders_count || 0, 10);

                stars5 = parseInt(totals.stars_5 || 0, 10);
                rows.forEach(r => {
                    const rc = parseInt(r.review_count || 0, 10);
                    const ar = parseFloat(r.avg_rating || 5.0);
                    totalReviews += rc;
                    ratingSum += (ar * rc);
                });

                onTimeCount = parseInt(totals.delivered_without_extension || 0, 10);
                lateCount = parseInt(totals.late_count || 0, 10);
                extCount = parseInt(totals.extension_count || 0, 10);
                extDays = parseInt(totals.extension_days || 0, 10);

                assignedCount = parseInt(totals.total_assigned_count || totals.total_assigned || 0, 10);
                assignedVal = parseFloat(totals.total_assigned_val || 0);
                newAssignedCount = parseInt(totals.new_assigned_count || totals.new_assigned || 0, 10);
                newAssignedVal = parseFloat(totals.new_assigned_val || 0);
                carryCount = parseInt(totals.carry_forward_count || totals.carry_forward || 0, 10);
                carryVal = parseFloat(totals.carry_forward_val || 0);

                cancelledCount = parseInt(totals.cancelled_count || totals.total_cancel || 0, 10);
                cancelledVal = parseFloat(totals.cancelled_val || totals.cancelled || 0);
            } else if (ctx.isLeader) {
                const squadRows = rows.filter(r => (r.team || '').toLowerCase() === ctx.userTeam.toLowerCase());
                squadRows.forEach(r => {
                    delVal += parseFloat(r.total_delivery || 0);
                    delNet += parseFloat(r.delivery_net || 0);
                    tipsVal += parseFloat(r.tips || 0);
                    delCount += parseInt(r.delivery_count || 0, 10);

                    submVal += parseFloat(r.submitted_val || r.total_submitted || 0);
                    submCount += parseInt(r.submitted_count || 0, 10);

                    wipVal += parseFloat(r.total_wip || 0);
                    wipCount += parseInt(r.wip_count || 0, 10);

                    repeatVal += parseFloat(r.repeat_val || 0);
                    repeatCount += parseInt(r.repeat_count || 0, 10);

                    stars5 += parseInt(r.stars_5 || 0, 10);
                    const rc = parseInt(r.review_count || 0, 10);
                    const ar = parseFloat(r.avg_rating || 5.0);
                    totalReviews += rc;
                    ratingSum += (ar * rc);

                    onTimeCount += parseInt(r.delivered_without_extension || 0, 10);
                    lateCount += parseInt(r.late_count || 0, 10);
                    extCount += parseInt(r.extension_count || 0, 10);
                    extDays += parseInt(r.extension_days || 0, 10);

                    assignedCount += parseInt(r.total_assigned_count || 0, 10);
                    assignedVal += parseFloat(r.total_assigned_val || 0);
                    newAssignedCount += parseInt(r.new_assigned_count || 0, 10);
                    newAssignedVal += parseFloat(r.new_assigned_val || 0);
                    carryCount += parseInt(r.carry_forward_count || 0, 10);
                    carryVal += parseFloat(r.carry_forward_val || 0);

                    cancelledCount += parseInt(r.cancelled_count || 0, 10);
                    cancelledVal += parseFloat(r.cancelled_val || 0);
                });
            } else {
                const myRow = rows.find(r => parseInt(r.user_id, 10) === ctx.currentUserId) || {};
                delVal = parseFloat(myRow.total_delivery || 0);
                delNet = parseFloat(myRow.delivery_net || (delVal - parseFloat(myRow.tips || 0)));
                tipsVal = parseFloat(myRow.tips || 0);
                delCount = parseInt(myRow.delivery_count || 0, 10);

                submVal = parseFloat(myRow.submitted_val || myRow.total_submitted || 0);
                submCount = parseInt(myRow.submitted_count || 0, 10);

                wipVal = parseFloat(myRow.total_wip || 0);
                wipCount = parseInt(myRow.wip_count || 0, 10);

                repeatVal = parseFloat(myRow.repeat_val || 0);
                repeatCount = parseInt(myRow.repeat_count || 0, 10);

                stars5 = parseInt(myRow.stars_5 || 0, 10);
                totalReviews = parseInt(myRow.review_count || 0, 10);
                ratingSum = parseFloat(myRow.avg_rating || 5.0) * totalReviews;

                onTimeCount = parseInt(myRow.delivered_without_extension || 0, 10);
                lateCount = parseInt(myRow.late_count || 0, 10);
                extCount = parseInt(myRow.extension_count || 0, 10);
                extDays = parseInt(myRow.extension_days || 0, 10);

                assignedCount = parseInt(myRow.total_assigned_count || 0, 10);
                assignedVal = parseFloat(myRow.total_assigned_val || 0);
                newAssignedCount = parseInt(myRow.new_assigned_count || 0, 10);
                newAssignedVal = parseFloat(myRow.new_assigned_val || 0);
                carryCount = parseInt(myRow.carry_forward_count || 0, 10);
                carryVal = parseFloat(myRow.carry_forward_val || 0);

                cancelledCount = parseInt(myRow.cancelled_count || 0, 10);
                cancelledVal = parseFloat(myRow.cancelled_val || 0);
            }

            const avgRating = totalReviews > 0 ? (ratingSum / totalReviews).toFixed(1) : '5.0';
            const totalDeliveryOrders = (onTimeCount + lateCount);
            const onTimePct = totalDeliveryOrders > 0 ? Math.round((onTimeCount / totalDeliveryOrders) * 100) : 100;
            const repeatPct = delVal > 0 ? Math.round((repeatVal / delVal) * 100) : 0;
            const cancelPct = assignedCount > 0 ? ((cancelledCount / assignedCount) * 100).toFixed(1) : '0.0';
            const uniqueDelCount = parseInt(totals.unique_delivery_count || 0, 10);
            const displayDeliveredOrders = (uniqueDelCount > 0 && (ctx.isAdmin || ctx.isDeputy)) ? uniqueDelCount : delCount;

            const uniqueSubmCount = parseInt(totals.unique_submitted_count || 0, 10);
            const displaySubmOrders = (uniqueSubmCount > 0 && (ctx.isAdmin || ctx.isDeputy)) ? uniqueSubmCount : submCount;

            const uniqueWipCount = parseInt(totals.unique_wip_count || 0, 10);
            const displayWipOrders = (uniqueWipCount > 0 && (ctx.isAdmin || ctx.isDeputy)) ? uniqueWipCount : wipCount;

            const uniqueNewCount = parseInt(totals.unique_new_assigned_count || 0, 10);
            const displayNewOrders = (uniqueNewCount > 0 && (ctx.isAdmin || ctx.isDeputy)) ? uniqueNewCount : newAssignedCount;

            const uniqueCfCount = parseInt(totals.unique_carry_forward_count || 0, 10);
            const displayCfOrders = (uniqueCfCount > 0 && (ctx.isAdmin || ctx.isDeputy)) ? uniqueCfCount : carryCount;

            const uniqueTotalCount = parseInt(totals.unique_total_assigned_count || 0, 10);
            const displayTotalOrders = (uniqueTotalCount > 0 && (ctx.isAdmin || ctx.isDeputy)) ? uniqueTotalCount : assignedCount;

            const cardsHtml = `
                <!-- 1. Delivered Revenue -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#10b981;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Total Delivered</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(16,185,129,0.15); color:#10b981;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#10b981;">$${this.formatOverviewMoney(delVal)}</div>
                        <div class="ftm-ov-sub">${displayDeliveredOrders} orders delivered</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(16,185,129,0.1); color:#10b981;">
                            <span>Net: $${this.formatOverviewMoney(delNet)}</span>
                            ${tipsVal > 0 ? `<span style="color:#a855f7; margin-left:4px;">+$${this.formatOverviewMoney(tipsVal)} Tips</span>` : ''}
                        </div>
                    </div>
                </div>

                <!-- 2. Delivery Submitted -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#06b6d4;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Delivery Submitted</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(6,182,212,0.15); color:#06b6d4;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#06b6d4;">$${this.formatOverviewMoney(submVal)}</div>
                        <div class="ftm-ov-sub">${displaySubmOrders} orders submitted</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(6,182,212,0.1); color:#06b6d4;">
                            <span>Awaiting Client Acceptance</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Active WIP -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#3b82f6;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Active WIP</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(59,130,246,0.15); color:#3b82f6;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#60a5fa;">$${this.formatOverviewMoney(wipVal)}</div>
                        <div class="ftm-ov-sub">${displayWipOrders} active deliverables</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(59,130,246,0.1); color:#60a5fa;">
                            <span>In-Production Pipeline</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Repeat Revenue -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#a855f7;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Repeat Clients</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(168,85,247,0.15); color:#a855f7;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"></path><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><path d="M7 23l-4-4 4-4"></path><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#c084fc;">$${this.formatOverviewMoney(repeatVal)}</div>
                        <div class="ftm-ov-sub">${repeatCount} repeat orders (${repeatPct}% share)</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(168,85,247,0.1); color:#c084fc;">
                            <span>Client Retention</span>
                        </div>
                    </div>
                </div>

                <!-- 5. New Sales Assigned -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#f97316;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">New Sales Assigned</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(249,115,22,0.15); color:#f97316;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#fb923c;">$${this.formatOverviewMoney(newAssignedVal)}</div>
                        <div class="ftm-ov-sub">${displayNewOrders} new orders booked</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(249,115,22,0.1); color:#fb923c;">
                            <span>Current Month Inflow</span>
                        </div>
                    </div>
                </div>

                <!-- 6. Carry Forward -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#eab308;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Carry Forward</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(234,179,8,0.15); color:#eab308;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 10"></polyline><path d="M16 16l4-4-4-4"></path></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#facc15;">$${this.formatOverviewMoney(carryVal)}</div>
                        <div class="ftm-ov-sub">${displayCfOrders} carry forward orders</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(234,179,8,0.1); color:#facc15;">
                            <span>Pre-Month Active Work</span>
                        </div>
                    </div>
                </div>

                <!-- 7. Client Satisfaction & Quality -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#f59e0b;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Satisfaction</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(245,158,11,0.15); color:#f59e0b;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#fbbf24;">${avgRating} <span style="font-size:16px;">★</span></div>
                        <div class="ftm-ov-sub">${stars5} 5-Star reviews (${totalReviews} total)</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(245,158,11,0.1); color:#fbbf24;">
                            <span>${onTimePct}% On-Time (${onTimeCount} On-Time &bull; ${lateCount} Late)</span>
                        </div>
                    </div>
                </div>

                <!-- 8. Total Work Pipeline -->
                <div class="ftm-ov-card">
                    <div class="ftm-ov-accent-top" style="background:#6366f1;"></div>
                    <div>
                        <div class="ftm-ov-head">
                            <span class="ftm-ov-title">Total Assigned</span>
                            <div class="ftm-ov-icon-wrap" style="background:rgba(99,102,241,0.15); color:#6366f1;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </div>
                        </div>
                        <div class="ftm-ov-val" style="color:#818cf8;">${displayTotalOrders} <span style="font-size:15px; font-weight:600;">($${this.formatOverviewMoney(assignedVal)})</span></div>
                        <div class="ftm-ov-sub">New: $${this.formatOverviewMoney(newAssignedVal)} (${displayNewOrders}) &bull; Carry: $${this.formatOverviewMoney(carryVal)} (${displayCfOrders})${tipsVal > 0 ? ` &bull; Tips: +$${this.formatOverviewMoney(tipsVal)}` : ''}</div>
                    </div>
                    <div>
                        <div class="ftm-ov-pill" style="background:rgba(99,102,241,0.1); color:#818cf8;">
                            <span>${cancelledCount > 0 ? `${cancelledCount} Cancelled ($${this.formatOverviewMoney(cancelledVal)})` : '100% Workload Tracked'}</span>
                        </div>
                    </div>
                </div>
            `;

            grid.html(cardsHtml);
        },

        renderOverviewSquads(res, ctx) {
            const wrap = $('#ftm-overview-squads-wrap');
            if (!wrap.length) return;

            const squads = res.team_breakdown || [];
            const rows = res.rows || [];

            if (ctx.isAdmin || ctx.isDeputy) {
                let cardsHtml = '<div class="ftm-ov-squads-grid">';
                squads.forEach(sq => {
                    const membersCount = parseInt(sq.members_count || sq.member_count || 0, 10);
                    const sqDelivered = parseFloat(sq.delivery_val || sq.total_delivery || 0);
                    const totalCompleted = parseInt(sq.delivery_count || sq.total_completed || 0, 10);
                    const sqSubmitted = parseFloat(sq.submitted_val || 0);
                    const submCount = parseInt(sq.submitted_count || 0, 10);
                    const sqWip = parseFloat(sq.wip_val || sq.total_wip || 0);
                    const wipCount = parseInt(sq.wip_count || 0, 10);
                    const repeatVal = parseFloat(sq.repeat_val || 0);
                    const repeatCount = parseInt(sq.repeat_count || 0, 10);
                    const newAssignedVal = parseFloat(sq.new_assigned_val || 0);
                    const newAssignedCount = parseInt(sq.new_assigned_count || 0, 10);
                    const carryVal = parseFloat(sq.carry_forward_val || 0);
                    const carryCount = parseInt(sq.carry_forward_count || 0, 10);
                    const sqAssigned = parseFloat(sq.total_assigned_val || sq.total_assigned || 0);

                    // Compute accurate stars and on-time rate from squad specialists
                    let sqStars5 = 0, sqRatingsCount = 0, sqRatingsSum = 0;
                    let sqOnTime = 0, sqLate = 0;
                    rows.forEach(r => {
                        if ((r.team || '').toLowerCase() === (sq.team_name || '').toLowerCase()) {
                            sqStars5 += parseInt(r.stars_5 || 0, 10);
                            const rc = parseInt(r.review_count || 0, 10);
                            sqRatingsCount += rc;
                            sqRatingsSum += (parseFloat(r.avg_rating || 5.0) * rc);
                            sqOnTime += parseInt(r.delivered_without_extension || 0, 10);
                            sqLate += parseInt(r.late_count || 0, 10);
                        }
                    });
                    const sqAvgRating = sqRatingsCount > 0 ? (sqRatingsSum / sqRatingsCount).toFixed(1) : '5.0';
                    const sqOnTimeRate = (sqOnTime + sqLate) > 0 ? Math.round((sqOnTime / (sqOnTime + sqLate)) * 100) : 100;
                    const sqPct = sqAssigned > 0 ? Math.min(100, Math.round((sqDelivered / sqAssigned) * 100)) : 0;

                    let badgeColor = 'bg-blue-soft text-blue';
                    let borderAccent = '#3b82f6';
                    if (sq.team_name === 'WpNexus') {
                        badgeColor = 'bg-purple-soft text-purple';
                        borderAccent = '#a855f7';
                    } else if (sq.team_name === 'WpTitans') {
                        badgeColor = 'bg-emerald-soft text-emerald';
                        borderAccent = '#10b981';
                    }

                    cardsHtml += `
                        <div class="ftm-squad-ov-card" style="border-top:3px solid ${borderAccent};">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                <div>
                                    <h3 style="margin:0; font-size:16px; font-weight:800; color:var(--ftm-text-primary);">${sq.team_name}</h3>
                                    <span class="ftm-text-muted" style="font-size:12px;">${membersCount} Specialists</span>
                                </div>
                                <span class="ftm-badge ${badgeColor} ftm-badge-sm" style="font-weight:700;">Squad Health: High</span>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px; background:rgba(255,255,255,0.02); padding:10px; border-radius:8px;">
                                <div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Delivered</div>
                                    <div style="font-size:16px; font-weight:800; color:#10b981;">$${this.formatOverviewMoney(sqDelivered)}</div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${totalCompleted} orders</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Submitted</div>
                                    <div style="font-size:16px; font-weight:800; color:#06b6d4;">$${this.formatOverviewMoney(sqSubmitted)}</div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${submCount} orders</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Active WIP</div>
                                    <div style="font-size:16px; font-weight:800; color:#60a5fa;">$${this.formatOverviewMoney(sqWip)}</div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${wipCount} orders</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Repeat Orders</div>
                                    <div style="font-size:15px; font-weight:700; color:#c084fc;">$${this.formatOverviewMoney(repeatVal)}</div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${repeatCount} orders</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">New Sale Assigned</div>
                                    <div style="font-size:15px; font-weight:700; color:#f97316;">$${this.formatOverviewMoney(newAssignedVal)}</div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${newAssignedCount} new orders</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Carry Forward</div>
                                    <div style="font-size:15px; font-weight:700; color:#fb923c;">$${this.formatOverviewMoney(carryVal)}</div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${carryCount} carry orders</div>
                                </div>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; margin-bottom:12px; padding:0 2px;">
                                <span style="color:#fbbf24; font-weight:700;">★ ${sqAvgRating} (${sqRatingsCount > 0 ? `${sqRatingsCount} reviews` : 'No reviews'})</span>
                                <span style="color:#2dd4bf; font-weight:700;">${sqOnTimeRate}% On-Time</span>
                            </div>

                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                                    <span style="color:var(--ftm-text-muted);">Delivery Realization</span>
                                    <span style="font-weight:700; color:var(--ftm-text-primary);">${sqPct}% <span style="font-size:11px; font-weight:500; color:var(--ftm-text-muted);">($${this.formatOverviewMoney(sqDelivered)} / $${this.formatOverviewMoney(sqAssigned)})</span></span>
                                </div>
                                <div style="height:6px; background:rgba(255,255,255,0.08); border-radius:3px; overflow:hidden;">
                                    <div style="width:${sqPct}%; height:100%; background:${borderAccent}; border-radius:3px;"></div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                cardsHtml += '</div>';
                wrap.html(cardsHtml);
            } else if (ctx.isLeader) {
                const sq = squads.find(s => (s.team_name || '').toLowerCase() === ctx.userTeam.toLowerCase()) || {};
                const sqDelivered = parseFloat(sq.delivery_val || sq.total_delivery || 0);
                const sqAssigned = parseFloat(sq.total_assigned_val || sq.total_assigned || 0);
                const sqPct = sqAssigned > 0 ? Math.min(100, Math.round((sqDelivered / sqAssigned) * 100)) : 0;
                const members = rows.filter(r => (r.team || '').toLowerCase() === ctx.userTeam.toLowerCase());
                const repeatVal = parseFloat(sq.repeat_val || 0);
                const repeatCount = parseInt(sq.repeat_count || 0, 10);
                const wipVal = parseFloat(sq.wip_val || sq.total_wip || 0);
                const totalCompleted = parseInt(sq.delivery_count || sq.total_completed || 0, 10);

                let sqStars5 = 0;
                members.forEach(r => { sqStars5 += parseInt(r.stars_5 || 0, 10); });

                wrap.html(`
                    <div class="ftm-squad-ov-card" style="border-top:3px solid #3b82f6;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--ftm-text-primary);">${ctx.userTeam} Squad Operational Overview</h3>
                                <div class="ftm-text-muted" style="font-size:13px; margin-top:2px;">Leadership command center for ${members.length} specialists in ${ctx.userTeam}</div>
                            </div>
                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700; padding:4px 10px;">Pacing: ${sqPct}% Realized</span>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; background:rgba(255,255,255,0.02); padding:12px; border-radius:8px;">
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Squad Delivered</div>
                                <div style="font-size:18px; font-weight:800; color:#10b981;">$${this.formatOverviewMoney(sqDelivered)}</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">${totalCompleted} completed orders</div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Squad Submitted</div>
                                <div style="font-size:18px; font-weight:800; color:#06b6d4;">$${this.formatOverviewMoney(parseFloat(sq.submitted_val || 0))}</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">${sq.submitted_count || 0} awaiting acceptance</div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Squad WIP Pipeline</div>
                                <div style="font-size:18px; font-weight:800; color:#60a5fa;">$${this.formatOverviewMoney(wipVal)}</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">${sq.wip_count || 0} active in production</div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Repeat Client Revenue</div>
                                <div style="font-size:18px; font-weight:800; color:#c084fc;">$${this.formatOverviewMoney(repeatVal)}</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">${repeatCount} Repeat Orders</div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">New Sale Assigned</div>
                                <div style="font-size:18px; font-weight:800; color:#f97316;">$${this.formatOverviewMoney(parseFloat(sq.new_assigned_val || 0))}</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">${parseInt(sq.new_assigned_count || 0, 10)} new orders</div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Carry Forward</div>
                                <div style="font-size:18px; font-weight:800; color:#fb923c;">$${this.formatOverviewMoney(parseFloat(sq.carry_forward_val || 0))}</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">${parseInt(sq.carry_forward_count || 0, 10)} carry orders</div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--ftm-text-muted); text-transform:uppercase; font-weight:600;">Client Ratings</div>
                                <div style="font-size:18px; font-weight:800; color:#fbbf24;">${sqStars5} 5★ Reviews</div>
                                <div style="font-size:12px; color:var(--ftm-text-muted);">100% Quality Adherence</div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                const sq = squads.find(s => (s.team_name || '').toLowerCase() === ctx.userTeam.toLowerCase()) || {};
                const sqDelivered = parseFloat(sq.delivery_val || sq.total_delivery || 0);
                const myRow = rows.find(r => parseInt(r.user_id, 10) === ctx.currentUserId) || {};
                const myDelivery = parseFloat(myRow.total_delivery || 0);
                const contribPct = sqDelivered > 0 ? Math.round((myDelivery / sqDelivered) * 100) : 0;

                wrap.html(`
                    <div class="ftm-squad-ov-card" style="border-top:3px solid #818cf8;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <div>
                                <h3 style="margin:0; font-size:17px; font-weight:800; color:var(--ftm-text-primary);">${ctx.userTeam} &bull; Your Monthly Performance Standing</h3>
                                <div class="ftm-text-muted" style="font-size:13px; margin-top:2px;">You contributed <strong>$${this.formatOverviewMoney(myDelivery)}</strong> (${contribPct}%) towards your squad's total <strong>$${this.formatOverviewMoney(sqDelivered)}</strong> delivered revenue!</div>
                            </div>
                            <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" style="font-weight:700; padding:6px 12px;">★ Top Specialist Contributor</span>
                        </div>
                    </div>
                `);
            }
        },

        renderOverviewSpecialists(res, ctx) {
            const pillsWrap = $('#ftm-overview-team-pills');
            const rows = res.rows || [];
            const activeTeam = this.state.overviewActiveTeamFilter || 'all';

            if (pillsWrap.length) {
                if (ctx.isAdmin || ctx.isDeputy) {
                    pillsWrap.html(`
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === 'all' ? 'active' : ''}" data-team="all">All (${rows.length})</button>
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === 'WpCraft' ? 'active' : ''}" data-team="WpCraft">WpCraft</button>
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === 'WpNexus' ? 'active' : ''}" data-team="WpNexus">WpNexus</button>
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === 'WpTitans' ? 'active' : ''}" data-team="WpTitans">WpTitans</button>
                    `);
                } else if (ctx.isLeader) {
                    pillsWrap.html(`
                        <button type="button" class="ftm-ov-team-pill active" data-team="${ctx.userTeam}">${ctx.userTeam} Squad</button>
                    `);
                } else {
                    pillsWrap.html(`
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === 'all' ? 'active' : ''}" data-team="all">All (${rows.length})</button>
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === 'my-standing' ? 'active' : ''}" data-team="my-standing">My Standing</button>
                        <button type="button" class="ftm-ov-team-pill ${activeTeam === ctx.userTeam ? 'active' : ''}" data-team="${ctx.userTeam}">${ctx.userTeam} Peers</button>
                    `);
                }
            }

            this.renderOverviewSpecialistsTable();
        },

        renderOverviewSpecialistsTable() {
            const tbody = $('#ftm-overview-specialists-tbody');
            if (!tbody.length) return;

            const res = this.state.overviewMonthlyData;
            if (!res || !res.rows) return;

            const rows = res.rows;
            const filterTeam = (this.state.overviewActiveTeamFilter || 'all').toLowerCase();
            const searchQuery = (this.state.overviewSearchQuery || '').toLowerCase().trim();
            const currentUserId = (window.ftmData && window.ftmData.current_user) ? parseInt(window.ftmData.current_user.id, 10) : 0;

            let filtered = rows.filter(r => {
                if (filterTeam === 'my-standing') {
                    return parseInt(r.user_id, 10) === currentUserId;
                }
                if (filterTeam !== 'all') {
                    if ((r.team || '').toLowerCase() !== filterTeam) {
                        return false;
                    }
                }
                if (searchQuery) {
                    const name = (r.member_name || '').toLowerCase();
                    const role = (r.role || '').toLowerCase();
                    const team = (r.team || '').toLowerCase();
                    return name.includes(searchQuery) || role.includes(searchQuery) || team.includes(searchQuery);
                }
                return true;
            });

            $('#ftm-overview-roster-count').text(`${filtered.length} Specialists`);

            if (!filtered.length) {
                tbody.html(`
                    <tr><td colspan="12" style="text-align:center; padding:30px; color:var(--ftm-text-muted);">
                        No specialists found matching your filter criteria.
                    </td></tr>
                `);
                return;
            }

            let html = '';
            filtered.forEach((r, idx) => {
                const isMe = parseInt(r.user_id, 10) === currentUserId;
                const del = parseFloat(r.total_delivery || 0);
                const tips = parseFloat(r.tips || 0);
                const completed = parseInt(r.delivery_count || 0, 10);
                const subVal = parseFloat(r.submitted_val || r.total_submitted || 0);
                const subCnt = parseInt(r.submitted_count || 0, 10);
                const wip = parseFloat(r.total_wip || 0);
                const wipCnt = parseInt(r.wip_count || 0, 10);
                const repeatVal = parseFloat(r.repeat_val || 0);
                const repeatCnt = parseInt(r.repeat_count || 0, 10);
                const s5 = parseInt(r.stars_5 || 0, 10);
                const avgRating = r.review_count > 0 ? parseFloat(r.avg_rating || 5.0).toFixed(1) : '5.0';
                const onTime = parseInt(r.delivered_without_extension || 0, 10);
                const late = parseInt(r.late_count || 0, 10);
                const totalFin = (onTime + late);
                const onTimePct = totalFin > 0 ? Math.round((onTime / totalFin) * 100) : 100;
                const assignedVal = parseFloat(r.total_assigned_val || 0);
                const assignedCnt = parseInt(r.total_assigned_count || 0, 10);
                const newCnt = parseInt(r.new_assigned || r.new_assigned_count || 0, 10);
                const newVal = parseFloat(r.new_assigned_val || 0);
                const carryCnt = parseInt(r.carry_forward || r.carry_forward_count || 0, 10);
                const carryVal = parseFloat(r.carry_forward_val || 0);

                let squadBadgeClass = 'bg-blue-soft text-blue';
                if (r.team === 'WpNexus') squadBadgeClass = 'bg-purple-soft text-purple';
                if (r.team === 'WpTitans') squadBadgeClass = 'bg-emerald-soft text-emerald';

                html += `
                    <tr class="ftm-ov-roster-row" style="${isMe ? 'background:rgba(99,102,241,0.08);' : ''}">
                        <td style="text-align:center; font-weight:700; color:var(--ftm-text-muted); font-size:12px;">${idx + 1}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <img src="${r.avatar || 'https://www.gravatar.com/avatar/?d=mp'}" alt="${r.member_name}" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.1);">
                                <div>
                                    <div style="font-weight:700; color:var(--ftm-text-primary); font-size:13px; display:flex; align-items:center; gap:6px;">
                                        <span>${r.member_name}</span>
                                        ${isMe ? '<span class="ftm-badge bg-indigo-soft text-indigo ftm-badge-xs">You</span>' : ''}
                                    </div>
                                    <div style="font-size:11px; color:var(--ftm-text-muted);">${r.role || 'Specialist'} &bull; #${r.user_id}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="ftm-badge ${squadBadgeClass} ftm-badge-xs" style="font-weight:700;">${r.team || '—'}</span>
                        </td>
                        <td style="text-align:right;">
                            <div style="font-weight:800; color:#10b981; font-size:14px;">$${this.formatOverviewMoney(del)}</div>
                            ${tips > 0 ? `<div style="font-size:10px; color:#c084fc;">+$${this.formatOverviewMoney(tips)} Tips</div>` : ''}
                        </td>
                        <td style="text-align:right;">
                            <div style="font-weight:700; color:#06b6d4; font-size:13px;">$${this.formatOverviewMoney(subVal)}</div>
                            <div style="font-size:11px; color:var(--ftm-text-muted);">${subCnt} submitted</div>
                        </td>
                        <td style="text-align:center;">
                            <div style="font-weight:800; color:var(--ftm-text-primary); font-size:14px;">${completed}</div>
                            <div style="font-size:11px; color:var(--ftm-text-muted);">completed</div>
                        </td>
                        <td style="text-align:right;">
                            <div style="font-weight:700; color:#60a5fa; font-size:13px;">$${this.formatOverviewMoney(wip)}</div>
                            <div style="font-size:11px; color:var(--ftm-text-muted);">${wipCnt} active</div>
                        </td>
                        <td style="text-align:right;">
                            <div style="font-weight:700; color:#c084fc; font-size:13px;">$${this.formatOverviewMoney(repeatVal)}</div>
                            <div style="font-size:11px; color:var(--ftm-text-muted);">${repeatCnt} repeat</div>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; align-items:center; gap:4px; font-weight:700; color:#fbbf24; font-size:13px;">
                                <span>${avgRating} ★</span>
                            </div>
                            <div style="font-size:11px; color:var(--ftm-text-muted);">${r.review_count > 0 ? `${r.review_count} ${r.review_count === 1 ? 'Review' : 'Reviews'}` : 'No reviews'}</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="ftm-badge ${onTimePct >= 90 ? 'bg-emerald-soft text-emerald' : (onTimePct >= 75 ? 'bg-amber-soft text-amber' : 'bg-rose-soft text-rose')} ftm-badge-xs" style="font-weight:700;">${onTimePct}%</span>
                            ${late > 0 ? `<div style="font-size:10px; color:#f87171; margin-top:2px;">${late} late</div>` : ''}
                        </td>
                        <td style="text-align:right;">
                            <div style="font-weight:700; color:var(--ftm-text-primary); font-size:13px;">$${this.formatOverviewMoney(assignedVal)}</div>
                            <div style="font-size:10px; color:#f97316;">${newCnt} new ($${this.formatOverviewMoney(newVal)})</div>
                            ${carryCnt > 0 ? `<div style="font-size:10px; color:#fb923c;">${carryCnt} carry ($${this.formatOverviewMoney(carryVal)})</div>` : ''}
                            ${parseFloat(r.tips || 0) > 0 ? `<div style="font-size:10px; color:#a855f7;">+$${this.formatOverviewMoney(r.tips)} tips</div>` : ''}
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-ov-view-member" data-member-id="${r.user_id}" style="padding:4px 8px; font-size:11px; font-weight:700; border-radius:6px;">
                                <span>Activity</span>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.html(html);
        },

        filterOverviewSpecialists(team) {
            this.state.overviewActiveTeamFilter = team;
            $('.ftm-ov-team-pill').removeClass('active');
            $(`.ftm-ov-team-pill[data-team="${team}"]`).addClass('active');
            this.renderOverviewSpecialistsTable();
        },

        searchOverviewSpecialists(query) {
            this.state.overviewSearchQuery = query;
            this.renderOverviewSpecialistsTable();
        },

        renderOverviewDeliverables(res, ctx) {
            const tbody = $('#ftm-overview-deliverables-tbody');
            if (!tbody.length) return;

            const allItems = (res && res.items) ? res.items : [];

            let filtered = allItems.filter(p => {
                if (ctx.isAdmin || ctx.isDeputy) return true;
                if (ctx.isLeader) {
                    return (p.team_name || '').toLowerCase() === ctx.userTeam.toLowerCase();
                }
                if (ctx.isMember) {
                    const assignees = p.assignees || [];
                    return assignees.some(a => parseInt(a.id || a.user_id, 10) === ctx.currentUserId);
                }
                return true;
            });

            $('#ftm-overview-deliverables-count').text(`${filtered.length} Open`);

            if (!filtered.length) {
                tbody.html(`
                    <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--ftm-text-muted);">
                        No active deliverables currently open for this scope.
                    </td></tr>
                `);
                return;
            }

            filtered.sort((a, b) => {
                const da = a.delivery_date ? new Date(a.delivery_date).getTime() : 9999999999999;
                const db = b.delivery_date ? new Date(b.delivery_date).getTime() : 9999999999999;
                return da - db;
            });

            let html = '';
            filtered.forEach(p => {
                const netVal = parseFloat(p.order_value_net || 0);
                const assignees = p.assignees || [];
                const assigneeNames = assignees.map(a => a.name || a.display_name).join(', ') || 'Unassigned';

                let countdownHtml = '';
                if (p.delivery_date) {
                    const deadline = new Date(p.delivery_date);
                    const now = new Date();
                    const diffHours = (deadline - now) / 3600000;
                    const diffDays = Math.ceil(diffHours / 24);

                    let pillClass = 'bg-emerald-soft text-emerald';
                    let label = `${diffDays}d left`;
                    if (diffHours < 0) {
                        pillClass = 'bg-rose-soft text-rose';
                        label = 'Overdue';
                    } else if (diffHours <= 24) {
                        pillClass = 'bg-rose-soft text-rose';
                        label = `${Math.max(1, Math.round(diffHours))}h left`;
                    } else if (diffDays <= 3) {
                        pillClass = 'bg-amber-soft text-amber';
                        label = `${diffDays}d left`;
                    }

                    const dateStr = deadline.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    countdownHtml = `
                        <div style="font-size:12px; font-weight:600; color:var(--ftm-text-primary);">${dateStr}</div>
                        <span class="ftm-badge ${pillClass} ftm-badge-xs" style="font-weight:700; margin-top:2px;">${label}</span>
                    `;
                } else {
                    countdownHtml = '<span class="ftm-text-muted">—</span>';
                }

                let statusBadge = 'bg-blue-soft text-blue';
                let statusLabel = (p.status || 'wip').toUpperCase();
                if (p.status === 'nra') {
                    statusBadge = 'bg-indigo-soft text-indigo';
                    statusLabel = 'NRA';
                } else if (p.status === 'revision') {
                    statusBadge = 'bg-purple-soft text-purple';
                    statusLabel = 'REVISION';
                } else if (p.status === 'delivery_submitted' || p.status === 'submitted') {
                    statusBadge = 'bg-cyan-soft text-cyan';
                    statusLabel = 'SUBMITTED';
                }

                html += `
                    <tr class="ftm-ov-roster-row">
                        <td>
                            <a href="#" class="ftm-order-link ftm-ov-project-link" data-project-id="${p.id}" style="font-weight:700; color:var(--ftm-primary); text-decoration:none; font-size:13px;">
                                ${p.order_id || ('#' + p.id)}
                            </a>
                        </td>
                        <td style="font-size:12px; color:var(--ftm-text-primary); font-weight:600;">${p.profile_name || '—'}</td>
                        <td style="font-size:12px; font-weight:600; color:var(--ftm-text-primary);">${p.client_name || '—'}</td>
                        <td>
                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="font-weight:700;">${p.team_name || '—'}</span>
                        </td>
                        <td>
                            <div style="font-size:12px; font-weight:600; color:var(--ftm-text-primary); max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${assigneeNames}">
                                ${assigneeNames}
                            </div>
                        </td>
                        <td style="text-align:right; font-weight:800; color:#10b981; font-size:13px;">$${this.formatOverviewMoney(netVal)}</td>
                        <td>${countdownHtml}</td>
                        <td style="text-align:center;">
                            <span class="ftm-badge ${statusBadge} ftm-badge-xs" style="font-weight:700;">${statusLabel}</span>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-ov-project-link" data-project-id="${p.id}" style="padding:4px 8px; font-size:11px; font-weight:700; border-radius:6px;">
                                <span>Inspect</span>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.html(html);
        },

        loadMonthlyReportsView(month) {



            const currentMonthVal = $('#ftm-monthly-select-month').val() || (new Date().toISOString().slice(0, 7));



            const targetMonth = month || currentMonthVal;



            const tbody = $('#ftm-monthly-tbody');



            tbody.html('<tr><td colspan="14" class="ftm-table-loading"><div class="ftm-spinner"></div> Compiling monthly sheet, visual driver graphs, and metrics...</td></tr>');







            const self = this;



            this.api(`reports/monthly?month=${encodeURIComponent(targetMonth)}`).then(res => {



                const rows = res.rows || [];



                const tot = res.totals || {};



                self.state.monthlyReportRows = rows;



                self.state.monthlyReportTotals = tot;



                self.state.monthlyReportRaw = res;



                self.state.activeMonthlyTeam = 'all';







                // Mode Indicator Badge



                if (res.is_quarter_report) {



                    $('#ftm-monthly-badge-mode').text((res.quarter_name || 'Quarter') + ' Review').removeClass('bg-blue-soft text-blue bg-amber-soft text-amber').addClass('bg-indigo-soft text-indigo');



                } else if (res.is_historical_archive) {



                    $('#ftm-monthly-badge-mode').text('Historical Archive').removeClass('bg-blue-soft text-blue bg-indigo-soft text-indigo').addClass('bg-amber-soft text-amber');



                } else {



                    $('#ftm-monthly-badge-mode').text('Live Database').removeClass('bg-amber-soft text-amber bg-indigo-soft text-indigo').addClass('bg-blue-soft text-blue');



                }







                // Update print title & period for PDF / print layout



                const displayMonth = $('#ftm-monthly-select-month option:selected').text() || month;



                $('#ftm-print-monthly-title').text(res.is_quarter_report ? (res.quarter_name + ' Financial & Operations Statement') : (displayMonth + ' Statement'));



                $('#ftm-print-monthly-period').text(res.quarter_period ? ('Period: ' + res.quarter_period) : ('Period: ' + displayMonth));







                // 1. Executive Top Performer Spotlight Podium



                self.renderMonthlySpotlight(rows, !!res.is_quarter_report, res.quarter_name, tot);







                // 2. Visual KPI Driver Graphs & Executive Analytics Suite



                self.renderMonthlyVisualDrivers(tot, rows);







                // 2B. Compact Team Performance Highlights



                self.renderMonthlyHighlights(rows, !!res.is_quarter_report, res.quarter_name);







                // 3. Key Summary KPI Cards (6 Grid)



                self.renderMonthlySummaryKpis(tot);







                // 4. Interactive Team Filter Tabs



                self.renderMonthlyTeamTabs(rows);







                // 5. Monthly Table



                self.renderMonthlyTable(rows);







                // 6. Real-Time Member Search Filter



                $('#ftm-monthly-search-input').off('input').on('input', function() {



                    const query = $(this).val().toLowerCase().trim();



                    self.filterMonthlyTable(self.state.activeMonthlyTeam, query);



                });



            }).catch(err => {



                console.error(err);



                tbody.html(`<tr><td colspan="14" style="text-align:center; padding:30px; color:var(--ftm-cancelled);">Failed to load monthly sheet: ${err.message || 'Error'}</td></tr>`);



            });



        },







        renderMonthlySpotlight(rows, isQuarter, quarterName, tot) {
            const wrap = $('#ftm-monthly-spotlight-wrap');
            if (!wrap.length) return;

            tot = tot || this.state.monthlyReportTotals || {};
            rows = rows || [];

            // 1. Total Delivered Revenue
            const totalDel = parseFloat(tot.total_delivery || 0);

            // 2. Completed Orders
            const completedCount = parseInt(tot.total_completed || 0, 10);

            // 3. Active Pipeline Value
            const activeWip = parseFloat(tot.total_wip || 0);

            // 4. Team Workload (WIP project count)
            let activeProjectsCount = 0;
            rows.forEach(r => {
                if (parseFloat(r.total_wip || 0) > 0) activeProjectsCount++;
            });

            // 5. Average Client Rating
            let starsSum = 0;
            let ratingsCount = 0;
            rows.forEach(r => {
                const s5 = parseInt(r.stars_5 || 0, 10);
                const s4 = parseInt(r.stars_4 || 0, 10);
                const s3 = parseInt(r.stars_3 || 0, 10);
                const s2 = parseInt(r.stars_2 || 0, 10);
                const s1 = parseInt(r.stars_1 || 0, 10);
                const rCount = s5 + s4 + s3 + s2 + s1;
                if (rCount > 0) {
                    starsSum += (5*s5 + 4*s4 + 3*s3 + 2*s2 + 1*s1);
                    ratingsCount += rCount;
                }
            });
            const avgRating = ratingsCount > 0 ? (starsSum / ratingsCount).toFixed(1) : (parseInt(tot.stars_5 || 0, 10) > 0 ? '5.0' : '0.0');
            const totalReviews = ratingsCount > 0 ? ratingsCount : parseInt(tot.stars_5 || 0, 10);

            let html = `
                <div class="ftm-refa-kpi-row ftm-monthly-top-kpi-row">
                    <!-- Card 1: Delivered Revenue -->
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-emerald">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Total Delivered Revenue</div>
                            <div class="ftm-refa-kpi-value text-emerald">$${totalDel.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="ftm-refa-kpi-sub text-emerald">Successfully delivered</div>
                        </div>
                    </div>

                    <!-- Card 2: Completed Orders -->
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-blue">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Completed Orders</div>
                            <div class="ftm-refa-kpi-value">${completedCount}</div>
                            <div class="ftm-refa-kpi-sub">Delivered deliverables</div>
                        </div>
                    </div>

                    <!-- Card 3: Active Pipeline Value -->
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-amber">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Active Pipeline Value</div>
                            <div class="ftm-refa-kpi-value text-amber">$${activeWip.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="ftm-refa-kpi-sub">Active WIP in production</div>
                        </div>
                    </div>

                    <!-- Card 4: Team Workload (WIP) -->
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-purple" style="background:#f5f3ff; color:#7c3aed;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Team Workload (WIP)</div>
                            <div class="ftm-refa-kpi-value text-purple">${activeProjectsCount > 0 ? activeProjectsCount : (activeWip > 0 ? 'Active' : '0')} <span style="font-size:14px; font-weight:600; color:#64748b;">projects</span></div>
                            <div class="ftm-refa-kpi-sub">In progress deliverables</div>
                        </div>
                    </div>

                    <!-- Card 5: Average Client Rating -->
                    <div class="ftm-refa-kpi-card ftm-refa-kpi-card-rating">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-gold">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Average Client Rating</div>
                            <div class="ftm-refa-kpi-value text-amber">${avgRating} <span style="font-size:14px; font-weight:600; color:#94a3b8;">/ 5.0</span></div>
                            <div class="ftm-refa-kpi-sub">${totalReviews > 0 ? totalReviews + ' client reviews' : 'No reviews logged'}</div>
                        </div>
                    </div>
                </div>
            `;
            wrap.html(html);
        },







        renderMonthlyVisualDrivers(tot, rows) {



            const container = $('#ftm-monthly-visual-drivers');



            const totalAssigned = parseFloat(tot.total_work_assigned || 0);



            const totalDel = parseFloat(tot.total_delivery || 0);



            const totalWip = parseFloat(tot.total_wip || 0);



            const totalLoss = parseFloat(tot.cancelled || 0);







            const delPct = totalAssigned > 0 ? Math.min(100, Math.round((totalDel / totalAssigned) * 100)) : 0;



            const wipPct = totalAssigned > 0 ? Math.min(100 - delPct, Math.round((totalWip / totalAssigned) * 100)) : 0;



            const lossPct = totalAssigned > 0 ? Math.max(0, 100 - delPct - wipPct) : 0;







            // Radial progress calculations for Delivery Ratio



            const rawRatio = Math.min(100, Math.max(0, parseFloat(tot.delivery_ratio || 0)));



            const ratioFormatted = Number.isInteger(rawRatio) ? rawRatio.toString() : rawRatio.toFixed(1);



            const radius = 36;



            const circ = 2 * Math.PI * radius; // ~226.19



            const dashoffset = circ - (circ * (rawRatio / 100));







            // Aggregate Team Data



            const teamsMap = {};



            rows.forEach(r => {



                const tName = r.team_name || 'General';



                if (!teamsMap[tName]) {



                    teamsMap[tName] = { name: tName, delivery: 0, wip: 0, members: 0, completed: 0 };



                }



                teamsMap[tName].delivery += parseFloat(r.total_delivery || 0);



                teamsMap[tName].wip += parseFloat(r.total_wip || 0);



                teamsMap[tName].members += 1;



                teamsMap[tName].completed += parseInt(r.total_completed || 0);



            });







            let maxTeamVal = 1;



            Object.values(teamsMap).forEach(t => {



                const sum = t.delivery + t.wip;



                if (sum > maxTeamVal) maxTeamVal = sum;



            });







            let teamsListHtml = '';



            const teamColors = {



                'WpCraft': '#3b82f6',



                'WpTitans': '#8b5cf6',



                'WpNexus': '#10b981', 'WpHorizon': '#f59e0b',



                'Management': '#6366f1'



            };







            const teamsArr = Object.values(teamsMap);



            if (teamsArr.length === 1) {



                const singleTeam = teamsArr[0];



                const teamTotal = singleTeam.delivery + singleTeam.wip;



                const delPct = teamTotal > 0 ? Math.round((singleTeam.delivery / teamTotal) * 100) : 0;



                const wipPct = teamTotal > 0 ? 100 - delPct : 0;



                const teamColor = teamColors[singleTeam.name] || '#3b82f6';







                // Top 4 members within this team



                const sortedMembers = [...rows].sort((a,b) => parseFloat(b.total_delivery||0) - parseFloat(a.total_delivery||0)).slice(0, 4);



                let memberBarsHtml = '';



                const maxMemDel = Math.max(1, parseFloat(sortedMembers[0]?.total_delivery || 1));







                sortedMembers.forEach(m => {



                    const mDel = parseFloat(m.total_delivery || 0);



                    const mWidth = Math.min(100, Math.max(10, Math.round((mDel / maxMemDel) * 100)));



                    memberBarsHtml += `



                        <div class="ftm-team-bar-item" style="margin-bottom:6px;">



                            <div class="ftm-team-bar-meta" style="font-size:11.5px;">



                                <span class="ftm-font-semibold">${m.member_name} <span class="ftm-text-muted ftm-text-xs">(${m.call_sign || m.team_name})</span></span>



                                <span class="ftm-font-bold text-emerald">${mDel.toFixed(0)}</span>



                            </div>



                            <div class="ftm-team-dual-track" style="height:4px; width:100%;">



                                <div style="width:${mWidth}%; background:#10b981; height:100%; border-radius:999px;"></div>



                            </div>



                        </div>



                    `;



                });







                teamsListHtml = `



                    <div style="background:var(--ftm-border-light); border:1px solid var(--ftm-border); border-radius:8px; padding:10px 12px; margin-bottom:8px;">



                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; font-size:12px;">



                            <span class="ftm-font-bold" style="color:${teamColor};">${ftmEsc(singleTeam.name)}</span>



                            <span class="ftm-font-semibold"><span class="text-emerald">${singleTeam.delivery.toFixed(0)}</span> / <span class="text-amber">${singleTeam.wip.toFixed(0)}</span></span>



                        </div>



                        <div class="ftm-team-dual-track" style="height:6px; width:100%;">



                            <div style="width:${delPct}%; background:#10b981; height:100%;" title="Delivered: ${singleTeam.delivery.toFixed(2)} (${delPct}%)"></div>



                            <div style="width:${wipPct}%; background:#f59e0b; height:100%;" title="WIP: ${singleTeam.wip.toFixed(2)} (${wipPct}%)"></div>



                        </div>



                        <div style="display:flex; justify-content:space-between; margin-top:4px; font-size:10.5px; color:var(--ftm-text-muted);">



                            <span>Delivered: ${delPct}%</span>



                            <span>Pipeline WIP: ${wipPct}%</span>



                        </div>



                    </div>



                    <div class="ftm-team-bar-meta" style="margin-bottom:5px; font-size:11px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:700;">



                        <span>Top Contributors</span>



                        <span>Output</span>



                    </div>



                    ${memberBarsHtml}



                `;



            } else {



                teamsArr.forEach(t => {



                    const teamTotal = t.delivery + t.wip;



                    const barWidth = Math.min(100, Math.max(8, Math.round((teamTotal / maxTeamVal) * 100)));



                    const delPart = teamTotal > 0 ? Math.round((t.delivery / teamTotal) * 100) : 0;



                    const wipPart = teamTotal > 0 ? 100 - delPart : 0;



                    const teamColor = teamColors[t.name] || '#64748b';







                    teamsListHtml += `



                        <div class="ftm-team-bar-item">



                            <div class="ftm-team-bar-meta">



                                <span class="ftm-team-bar-name" style="color:${teamColor};">${ftmEsc(t.name)} <span class="ftm-text-muted ftm-text-xs">(${t.members} mem)</span></span>



                                <span class="ftm-team-bar-vals">



                                    <span class="text-emerald">${t.delivery.toFixed(0)}</span> / <span class="text-amber">${t.wip.toFixed(0)}</span>



                                </span>



                            </div>



                            <div class="ftm-team-dual-track" style="width:${barWidth}%;">



                                <div style="width:${delPart}%; background:#10b981; height:100%;" title="${ftmEsc(t.name)} Delivered: ${t.delivery.toFixed(2)} (${delPart}%)"></div>



                                <div style="width:${wipPart}%; background:#f59e0b; height:100%;" title="${ftmEsc(t.name)} WIP: ${t.wip.toFixed(2)} (${wipPart}%)"></div>



                            </div>



                        </div>



                    `;



                });



            }







            const completedCount = parseInt(tot.total_completed || 0);



            const ontimeCount = parseInt(tot.delivered_without_extension || 0);



            const ontimePct = completedCount > 0 ? Math.round((ontimeCount / completedCount) * 100) : 100;



            const avgDelivery = parseFloat(tot.average_delivery || 0).toFixed(2);



            const fiveStarRatio = parseFloat(tot.five_star_ratio || 0).toFixed(0);







            const html = `



                <!-- Driver 1: Revenue Realization & Pipeline Gauge -->



                <div class="ftm-driver-card">



                    <div class="ftm-driver-card-header">



                        <span class="ftm-driver-title">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-emerald"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                            <span>Revenue Realization & Pipeline</span>



                        </span>



                        <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm">Target 85%</span>



                    </div>







                    <div class="ftm-driver-visual-wrap">



                        <div class="ftm-radial-gauge-container">



                            <svg class="ftm-radial-gauge-svg" viewBox="0 0 88 88">



                                <circle class="ftm-radial-gauge-bg" cx="44" cy="44" r="${radius}" stroke-width="6.5"></circle>



                                <circle class="ftm-radial-gauge-fill" cx="44" cy="44" r="${radius}" stroke-width="6.5" stroke="#10b981" stroke-dasharray="${circ}" stroke-dashoffset="${dashoffset}"></circle>



                            </svg>



                            <div class="ftm-radial-gauge-center">



                                <span class="ftm-gauge-pct-text">${ratioFormatted}%</span>



                                <span class="ftm-gauge-lbl-text">Realized</span>



                            </div>



                        </div>







                        <div class="ftm-driver-progress-detail">



                            <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:12px;">



                                <span class="ftm-font-semibold">Pipeline Volume</span>



                                <strong class="text-blue">$${totalAssigned.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>



                            </div>



                            <div class="ftm-driver-stack-track">



                                <div class="ftm-driver-track-seg ftm-seg-delivery" style="width:${delPct}%;" title="Delivered: $${totalDel.toFixed(2)} (${delPct}%)"></div>



                                <div class="ftm-driver-track-seg ftm-seg-wip" style="width:${wipPct}%;" title="Active WIP: $${totalWip.toFixed(2)} (${wipPct}%)"></div>



                                <div class="ftm-driver-track-seg ftm-seg-loss" style="width:${lossPct}%;" title="Cancelled/Loss: $${totalLoss.toFixed(2)} (${lossPct}%)"></div>



                            </div>



                            <div class="ftm-driver-legend-chips">



                                <span class="ftm-driver-chip"><span class="ftm-dot-delivery"></span> Delivered ($${totalDel.toFixed(0)})</span>



                                <span class="ftm-driver-chip"><span class="ftm-dot-wip"></span> Active WIP ($${totalWip.toFixed(0)})</span>



                            </div>



                        </div>



                    </div>



                </div>







                <!-- Driver 2: Team Revenue & Workload Breakdown -->



                <div class="ftm-driver-card">



                    <div class="ftm-driver-card-header">



                        <span class="ftm-driver-title">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>



                            <span>Team Workload & Output</span>



                        </span>



                        <span class="ftm-text-muted ftm-text-xs">Delivered vs WIP</span>



                    </div>







                    <div class="ftm-team-bars-list">



                        ${teamsListHtml}



                    </div>



                </div>







                <!-- Driver 3: Agency Quality & Velocity Radar -->



                <div class="ftm-driver-card">



                    <div class="ftm-driver-card-header">



                        <span class="ftm-driver-title">



                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-blue"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>



                            <span>Quality & Velocity Drivers</span>



                        </span>



                        <span class="ftm-badge ftm-badge-outline ftm-badge-sm">KPI Index</span>



                    </div>







                    <div class="ftm-efficiency-grid">



                        <div class="ftm-efficiency-tile">



                            <span class="ftm-efficiency-tile-lbl">5★ Rating Ratio</span>



                            <span class="ftm-efficiency-tile-val" style="color: ${parseInt(tot.stars_5 || 0) > 0 ? '#8b5cf6' : 'var(--ftm-text-muted)'};">${parseInt(tot.stars_5 || 0) > 0 ? fiveStarRatio + '%' : '0%'}</span>



                            <span class="ftm-efficiency-tile-sub">${parseInt(tot.stars_5 || 0) > 0 ? (tot.stars_5 || 0) + ' Perfect Reviews' : 'No Reviews Logged'}</span>



                        </div>



                        <div class="ftm-efficiency-tile">



                            <span class="ftm-efficiency-tile-lbl">On-Time Velocity</span>



                            <span class="ftm-efficiency-tile-val text-emerald">${ontimePct}%</span>



                            <span class="ftm-efficiency-tile-sub">No Ext: ${ontimeCount} orders</span>



                        </div>



                        <div class="ftm-efficiency-tile">



                            <span class="ftm-efficiency-tile-lbl">Avg Delivery</span>



                            <span class="ftm-efficiency-tile-val text-blue">$${avgDelivery}</span>



                            <span class="ftm-efficiency-tile-sub">Per team member</span>



                        </div>



                        <div class="ftm-efficiency-tile">



                            <span class="ftm-efficiency-tile-lbl">Order Retention</span>



                            <span class="ftm-efficiency-tile-val text-amber">${tot.total_cancel > 0 ? (100 - (tot.total_cancel * 5)).toFixed(0) + '%' : '100%'}</span>



                            <span class="ftm-efficiency-tile-sub">Cancels: ${tot.total_cancel || 0}</span>



                        </div>



                    </div>



                </div>



            `;



            container.html(html);



        },







        renderMonthlySummaryKpis(tot) {
            const summaryKpis = $('#ftm-monthly-summary-kpis');
            const totalAssigned = parseFloat(tot.total_work_assigned !== undefined ? tot.total_work_assigned : (tot.total_assigned_val || 0));
            const totalDel = parseFloat(tot.total_delivery_net !== undefined ? tot.total_delivery_net : (tot.total_delivery || 0));
            const totalWip = parseFloat(tot.total_wip_net !== undefined ? tot.total_wip_net : (tot.total_wip || 0));
            const totalCancelled = parseFloat(tot.cancelled_val !== undefined ? tot.cancelled_val : (tot.cancelled || 0));
            const totalCancelCount = parseInt(tot.cancelled_count !== undefined ? tot.cancelled_count : (tot.total_cancel || 0), 10) || 0;
            const totalCompleted = parseInt(tot.total_completed || 0, 10) || 0;
            const cancelRate = (totalCompleted + totalCancelCount) > 0 ? ((totalCancelCount / (totalCompleted + totalCancelCount)) * 100).toFixed(1) : '0.0';
            const delRatio = parseFloat(tot.delivery_ratio || 0);
            const stars5Count = parseInt(tot.stars_5 || 0);
            const fiveStarRatio = parseFloat(tot.five_star_ratio || 0);
            const avgDelivery = parseFloat(tot.average_delivery || 0);
            const totalTips = parseFloat(tot.total_tips || tot.tips || 0);
            const repeatCount = parseInt(tot.repeat_count !== undefined ? tot.repeat_count : (tot.repeat_orders_count || 0), 10) || 0;
            const repeatVal = parseFloat(tot.repeat_val !== undefined ? tot.repeat_val : (tot.repeat_net_value || 0)) || 0;
            const repeatShare = totalAssigned > 0 ? Math.min(100, Math.round((repeatVal / totalAssigned) * 100)) : 0;

            summaryKpis.html(`
                <div class="ftm-monthly-kpi-card">
                    <div class="ftm-monthly-kpi-top">
                        <span class="ftm-monthly-kpi-lbl">Total Work Assigned</span>
                        <div class="ftm-monthly-kpi-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                    </div>
                    <div class="ftm-monthly-kpi-val">$${totalAssigned.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:100%; background:var(--ftm-primary);"></div></div>
                    <div class="ftm-monthly-kpi-subtext">Total workload in period</div>
                </div>

                <div class="ftm-monthly-kpi-card">
                    <div class="ftm-monthly-kpi-top">
                        <span class="ftm-monthly-kpi-lbl">Total Delivery (Net)</span>
                        <div class="ftm-monthly-kpi-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div class="ftm-monthly-kpi-val text-emerald">$${totalDel.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:${Math.min(100, delRatio)}%; background:#10b981;"></div></div>
                    <div class="ftm-monthly-kpi-subtext">${totalCompleted} completed payouts (incl. $${totalTips.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} tips)</div>
                </div>

                <div class="ftm-monthly-kpi-card">
                    <div class="ftm-monthly-kpi-top">
                        <span class="ftm-monthly-kpi-lbl">Active WIP Value</span>
                        <div class="ftm-monthly-kpi-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="ftm-monthly-kpi-val text-amber">$${totalWip.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:${totalAssigned > 0 ? Math.min(100, Math.round((totalWip/totalAssigned)*100)) : 0}%; background:#f59e0b;"></div></div>
                    <div class="ftm-monthly-kpi-subtext">In-progress pipeline balance</div>
                </div>

                <div class="ftm-monthly-kpi-card">
                    <div class="ftm-monthly-kpi-top">
                        <span class="ftm-monthly-kpi-lbl">Cancelled Overview</span>
                        <div class="ftm-monthly-kpi-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        </div>
                    </div>
                    <div class="ftm-monthly-kpi-val text-rose">$${totalCancelled.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:${Math.min(100, parseFloat(cancelRate))}%; background:#ef4444;"></div></div>
                    <div class="ftm-monthly-kpi-subtext">${totalCancelCount} cancelled order${totalCancelCount === 1 ? '' : 's'} (${cancelRate}% cancel rate)</div>
                </div>







                <div class="ftm-monthly-kpi-card">



                    <div class="ftm-monthly-kpi-top">



                        <span class="ftm-monthly-kpi-lbl">Delivery Realization</span>



                        <div class="ftm-monthly-kpi-icon">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>



                        </div>



                    </div>



                    <div class="ftm-monthly-kpi-val" style="color:#0ea5e9;">${delRatio}%</div>



                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:${Math.min(100, delRatio)}%; background:#0ea5e9;"></div></div>



                    <div class="ftm-monthly-kpi-subtext">Realized vs assigned ratio</div>



                </div>







                <div class="ftm-monthly-kpi-card">



                    <div class="ftm-monthly-kpi-top">



                        <span class="ftm-monthly-kpi-lbl">5★ Satisfaction</span>



                        <div class="ftm-monthly-kpi-icon">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>



                        </div>



                    </div>



                    <div class="ftm-monthly-kpi-val" style="color:${stars5Count > 0 ? '#8b5cf6' : 'var(--ftm-text-muted)'};">${stars5Count > 0 ? fiveStarRatio + '%' : '0%'}</div>



                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:${stars5Count > 0 ? Math.min(100, fiveStarRatio) : 0}%; background:#8b5cf6;"></div></div>



                    <div class="ftm-monthly-kpi-subtext">${stars5Count > 0 ? stars5Count + ' reviews with 5 stars' : 'No 5-star reviews recorded'}</div>



                </div>







                <div class="ftm-monthly-kpi-card">



                    <div class="ftm-monthly-kpi-top">



                        <span class="ftm-monthly-kpi-lbl">Average Delivery</span>



                        <div class="ftm-monthly-kpi-icon">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>



                        </div>



                    </div>



                    <div class="ftm-monthly-kpi-val">$${avgDelivery.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>



                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:75%; background:var(--ftm-text-muted);"></div></div>



                    <div class="ftm-monthly-kpi-subtext">Per member average</div>



                </div>

                <div class="ftm-monthly-kpi-card">
                    <div class="ftm-monthly-kpi-top">
                        <span class="ftm-monthly-kpi-lbl">Repeat Client Orders</span>
                        <div class="ftm-monthly-kpi-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                        </div>
                    </div>
                    <div class="ftm-monthly-kpi-val" style="color:#06b6d4;">${repeatCount} <span style="font-size:13px; font-weight:700; opacity:0.85;">($${repeatVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})</span></div>
                    <div class="ftm-monthly-kpi-meter"><div style="height:100%; width:${repeatShare}%; background:#06b6d4;"></div></div>
                    <div class="ftm-monthly-kpi-subtext">${repeatShare}% of monthly assigned value</div>
                </div>



            `);



        },







        renderMonthlyTeamTabs(rows) {



            const tabsContainer = $('#ftm-monthly-team-tabs');



            const counts = { 'all': rows.length };



            rows.forEach(r => {



                const t = r.team_name || 'General';



                counts[t] = (counts[t] || 0) + 1;



            });







            let tabsHtml = `<button type="button" class="ftm-team-tab-btn active" data-team="all">All Teams <span class="ftm-team-tab-badge">${counts['all']}</span></button>`;



            Object.keys(counts).forEach(t => {



                if (t === 'all') return;



                // Suppress 'Former' filter tabs from active manager toolbar tabs



                if (t.toLowerCase().startsWith('former')) return;



                tabsHtml += `<button type="button" class="ftm-team-tab-btn" data-team="${t}">${t} <span class="ftm-team-tab-badge">${counts[t]}</span></button>`;



            });



            tabsContainer.html(tabsHtml);







            const self = this;



            tabsContainer.find('.ftm-team-tab-btn').off('click').on('click', function(e) {



                e.preventDefault();



                tabsContainer.find('.ftm-team-tab-btn').removeClass('active');



                $(this).addClass('active');



                self.state.activeMonthlyTeam = $(this).data('team');



                const query = $('#ftm-monthly-search-input').val().toLowerCase().trim();



                self.filterMonthlyTable(self.state.activeMonthlyTeam, query);



            });



        },







        filterMonthlyTable(team, query) {



            const allRows = this.state.monthlyReportRows || [];



            const filtered = allRows.filter(r => {



                const matchesTeam = (team === 'all' || !team || r.team_name === team);



                if (!matchesTeam) return false;



                if (!query) return true;



                const name = (r.member_name || '').toLowerCase();



                const id = (r.member_custom_id || '').toLowerCase();



                const call = (r.call_sign || '').toLowerCase();



                return name.includes(query) || id.includes(query) || call.includes(query);



            });



            this.renderMonthlyTable(filtered);



        },







        renderMonthlyTable(rows) {



            const tbody = $('#ftm-monthly-tbody');



            const tfoot = $('#ftm-monthly-tfoot');



            const mobileContainer = $('#ftm-monthly-mobile-cards');



            const topDel = (this.state.monthlyReportRows || []).reduce((max, r) => parseFloat(r.total_delivery || 0) > parseFloat(max.total_delivery || 0) ? r : max, { total_delivery: 0 });







            if (!rows || rows.length === 0) {



                tbody.html('<tr><td colspan="14" style="text-align:center; padding:32px; color:var(--ftm-text-muted);">No members match the selected team or search filter.</td></tr>');



                tfoot.html('');



                mobileContainer.html('<div class="ftm-text-muted" style="text-align:center; padding:24px;">No members match the selected team or search filter.</div>');



                return;



            }







            let subDel = 0, subWip = 0, subTips = 0, subCanc = 0, subPart = 0, subCompl = 0, subRepeatCount = 0, subRepeatVal = 0, subS5 = 0, subCancCount = 0, subOntime = 0;







            let html = '';



            let mobileCardsHtml = '';



            rows.forEach(r => {



                const del = parseFloat(r.total_delivery || 0);



                const wip = parseFloat(r.total_wip || 0);



                const tips = parseFloat(r.tips || 0);



                const cancelled = parseFloat(r.cancelled || 0);



                const partial = parseFloat(r.partial || 0);



                const completed = parseInt(r.total_completed || 0);
                const repeatCnt = parseInt(r.repeat_count !== undefined ? r.repeat_count : (r.repeat_orders || 0), 10) || 0;
                const repeatVal = parseFloat(r.repeat_val !== undefined ? r.repeat_val : (r.repeat_net_value || 0)) || 0;



                const s5 = parseInt(r.stars_5 || 0);



                const totalCancel = parseInt(r.total_cancel || 0);



                const ontime = parseInt(r.delivered_without_extension || 0);







                subDel += del;



                subWip += wip;



                subTips += tips;



                subCanc += cancelled;



                subPart += partial;



                subCompl += completed;
                subRepeatCount += repeatCnt;
                subRepeatVal += repeatVal;



                subS5 += s5;



                subCancCount += totalCancel;



                subOntime += ontime;







                let memberBadges = '';



                if (topDel && del > 0 && r.member_custom_id === topDel.member_custom_id) {



                    memberBadges += ' <span class="ftm-badge ftm-badge-outline text-emerald" style="font-size:9.5px; padding:1px 5px; margin-left:6px; font-weight:600;">Top Delivery</span>';



                }



                if (s5 > 0) {



                    memberBadges += ` <span class="ftm-badge ftm-badge-outline text-blue" style="font-size:9.5px; padding:1px 5px; margin-left:4px; font-weight:600;">${s5}x 5★</span>`;



                }



                if (r.member_custom_id === '1027') {



                    memberBadges += ' <span class="ftm-badge bg-indigo-soft text-indigo" style="font-size:9.5px; padding:1px 5px; margin-left:4px; font-weight:700;">Leader</span>';



                } else if (r.member_custom_id === '1069') {



                    memberBadges += ' <span class="ftm-badge bg-blue-soft text-blue" style="font-size:9.5px; padding:1px 5px; margin-left:4px; font-weight:700;">Co-Leader</span>';



                }







                const teamColorMap = {



                    'WpCraft': 'bg-blue-soft text-blue',



                    'WpTitans': 'bg-purple-soft text-purple', 'WpHorizon': 'bg-amber-soft text-amber',



                    'WpNexus': 'bg-emerald-soft text-emerald',



                    'WpHorizon': 'bg-cyan-soft text-cyan',



                    'Management': 'bg-indigo-soft text-indigo'



                };



                const teamClass = teamColorMap[r.team_name] || 'ftm-badge-outline';







                html += `



                    <tr>



                        <td><span class="ftm-badge ftm-badge-outline ftm-badge-sm" style="font-weight:700;">#${r.member_custom_id}</span></td>



                        <td><strong>${r.member_name}</strong>${memberBadges}</td>



                        <td><span class="ftm-badge ${teamClass} ftm-badge-sm">${ftmEsc(r.team_name)}</span></td>



                        <td>
                            ${del > 0 ? `<strong class="text-emerald">$${del.toFixed(2)}</strong>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">$0.00</span>'}
                            ${tips > 0 ? `<div style="font-size:10.5px; color:#10b981; font-weight:600; margin-top:2px;">(incl. $${tips.toFixed(2)} tip)</div>` : ''}
                        </td>
                        <td>
                            ${wip > 0 ? `<strong class="text-amber">$${wip.toFixed(2)}</strong>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">$0.00</span>'}
                        </td>
                        <td>${tips > 0 ? `<strong class="text-emerald">$${tips.toFixed(2)}</strong>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">$0.00</span>'}</td>



                        <td>${cancelled > 0 ? `<span class="text-rose font-bold">$${cancelled.toFixed(2)}</span>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">$0.00</span>'}</td>



                        <td>${partial > 0 ? '$' + partial.toFixed(2) : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">$0.00</span>'}</td>



                        <td><strong>${completed}</strong></td>

                        <td>
                            ${repeatCnt > 0
                                ? `<strong style="color:#06b6d4;">${repeatCnt}</strong><div style="font-size:10.5px; color:#06b6d4; font-weight:600; margin-top:1px;">$${repeatVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>`
                                : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">0</span>'}
                        </td>



                        <td>${s5 > 0 ? `<strong class="text-blue">${s5}</strong>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">0</span>'}</td>



                        <td>${(r.stars_4 && parseInt(r.stars_4) > 0) ? `<strong>${r.stars_4}</strong>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">0</span>'}</td>



                        <td>${totalCancel > 0 ? `<span class="text-rose font-bold">${totalCancel}</span>` : '<span class="ftm-text-muted" style="color:var(--ftm-text-secondary, #64748b); font-size:12px; font-weight:500;">0</span>'}</td>



                        <td>${ontime}</td>



                    </tr>



                `;







                // Build premium mobile card for monthly sheet



                mobileCardsHtml += `



                    <div class="ftm-monthly-member-card">



                        <div class="ftm-mm-card-header">



                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">



                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm" style="font-weight:700;">#${r.member_custom_id}</span>



                                <span class="ftm-mm-card-name">${r.member_name}</span>



                                ${memberBadges}



                            </div>



                            <span class="ftm-badge ${teamClass} ftm-badge-sm" style="font-weight:700;">${ftmEsc(r.team_name)}</span>



                        </div>







                        <div class="ftm-mm-card-financials">



                            <div class="ftm-mm-fin-item">



                                <span class="ftm-mm-fin-label">Delivery</span>



                                <span class="ftm-mm-fin-val text-emerald">${del > 0 ? ("$" + del.toFixed(2)) : "$0.00"}</span>



                            </div>



                            <div class="ftm-mm-fin-item">



                                <span class="ftm-mm-fin-label">Active WIP</span>



                                <span class="ftm-mm-fin-val text-amber">${wip > 0 ? ("$" + wip.toFixed(2)) : "$0.00"}</span>



                            </div>



                            <div class="ftm-mm-fin-item">



                                <span class="ftm-mm-fin-label">Tips</span>



                                <span class="ftm-mm-fin-val">${tips > 0 ? ("$" + tips.toFixed(2)) : "$0.00"}</span>



                            </div>



                        </div>







                        <div class="ftm-mm-card-details">



                            <span class="ftm-mm-pill"><span>Orders:</span> <strong>${completed}</strong></span>

                            <span class="ftm-mm-pill"><span>Repeat:</span> <strong style="color:#06b6d4;">${repeatCnt}${repeatCnt > 0 ? ' ($' + repeatVal.toFixed(2) + ')' : ''}</strong></span>



                            <span class="ftm-mm-pill"><span>5★ Reviews:</span> <strong class="text-blue">${s5}</strong></span>



                            ${cancelled > 0 ? (`<span class="ftm-mm-pill ftm-mm-pill-danger"><span>Cancelled:</span> <strong>$${cancelled.toFixed(2)} (${totalCancel})</strong></span>`) : ""}



                            <span class="ftm-mm-pill"><span>On-Time:</span> <strong>${ontime}</strong></span>



                        </div>



                    </div>



                `;



            });



            tbody.html(html);

            const isAllMembersView = rows.length === (this.state.monthlyReportRows || []).length;
            const totRepeatCountDisplay = (isAllMembersView && this.state.monthlyReportTotals && this.state.monthlyReportTotals.repeat_count !== undefined)
                ? parseInt(this.state.monthlyReportTotals.repeat_count, 10)
                : subRepeatCount;







            // Append Period Totals summary card for mobile



            mobileCardsHtml += `



                <div class="ftm-monthly-member-card ftm-mm-totals-card">



                    <div class="ftm-mm-card-header">



                        <span style="font-weight:800; font-size:13px; color:#2563eb; text-transform:uppercase; letter-spacing:0.04em;">



                            Period Consolidated Totals



                        </span>



                        <span class="ftm-badge ftm-badge-primary ftm-badge-sm" style="font-weight:700;">${rows.length} Specialists</span>



                    </div>



                    <div class="ftm-mm-card-financials">



                        <div class="ftm-mm-fin-item">



                            <span class="ftm-mm-fin-label">Delivered</span>



                            <span class="ftm-mm-fin-val text-emerald">$${subDel.toFixed(2)}</span>



                        </div>



                        <div class="ftm-mm-fin-item">



                            <span class="ftm-mm-fin-label">Active WIP</span>



                            <span class="ftm-mm-fin-val text-amber">$${subWip.toFixed(2)}</span>



                        </div>



                        <div class="ftm-mm-fin-item">



                            <span class="ftm-mm-fin-label">Tips</span>



                            <span class="ftm-mm-fin-val">$${subTips.toFixed(2)}</span>



                        </div>



                    </div>



                    <div class="ftm-mm-card-details">



                        <span class="ftm-mm-pill"><span>Orders:</span> <strong>${subCompl}</strong></span>

                        <span class="ftm-mm-pill"><span>Repeat:</span> <strong style="color:#06b6d4;">${totRepeatCountDisplay} ($${subRepeatVal.toFixed(2)})</strong></span>



                        <span class="ftm-mm-pill"><span>5★:</span> <strong class="text-blue">${subS5}</strong></span>



                        ${subCanc > 0 ? (`<span class="ftm-mm-pill ftm-mm-pill-danger"><span>Cancelled:</span> <strong>$${subCanc.toFixed(2)} (${subCancCount})</strong></span>`) : ""}



                        <span class="ftm-mm-pill"><span>On-Time:</span> <strong>${subOntime}</strong></span>



                    </div>



                </div>



            `;



            mobileContainer.html(mobileCardsHtml);







            // Dynamic Subtotal / Total Footer



            tfoot.html(`



                <tr>



                    <td colspan="3" style="font-weight:700; font-size:12px; letter-spacing:0.04em; color:var(--ftm-text-muted);">PERIOD TOTALS (${rows.length} MEMBERS)</td>



                    <td class="text-emerald" style="font-weight:800;">$${subDel.toFixed(2)}</td>



                    <td class="text-amber" style="font-weight:800;">$${subWip.toFixed(2)}</td>



                    <td class="text-emerald" style="font-weight:700;">$${subTips.toFixed(2)}</td>



                    <td class="text-rose font-bold">$${subCanc.toFixed(2)}</td>



                    <td>$${subPart.toFixed(2)}</td>



                    <td style="font-weight:800;">${subCompl}</td>

                    <td style="font-weight:800; color:#06b6d4;">${totRepeatCountDisplay}<div style="font-size:10.5px; font-weight:700;">$${subRepeatVal.toFixed(2)}</div></td>



                    <td style="font-weight:800;">${subS5}</td>



                    <td>-</td>



                    <td class="text-rose font-bold">${subCancCount}</td>



                    <td>${subOntime}</td>



                </tr>



            `);



        },







        exportMonthlyCsv() {



            let csv = [];



            const rows = document.querySelectorAll('#ftm-monthly-table tr');



            for (let i = 0; i < rows.length; i++) {



                let row = [], cols = rows[i].querySelectorAll('td, th');



                for (let j = 0; j < cols.length; j++) {



                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');



                }



                csv.push(row.join(','));



            }



            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });



            const downloadLink = document.createElement('a');



            downloadLink.download = `FBINT_Monthly_Report_${$('#ftm-monthly-select-month').val()}.csv`;



            downloadLink.href = window.URL.createObjectURL(csvFile);



            downloadLink.style.display = 'none';



            document.body.appendChild(downloadLink);



            downloadLink.click();



            document.body.removeChild(downloadLink);



        },







        populateMonthlyPrintStage() {



            const self = this;



            const stage = $('#ftm-print-stage');



            const allRows = this.state.monthlyReportRows || [];



            const activeTeam = this.state.activeMonthlyTeam || 'all';



            const rows = allRows.filter(r => (activeTeam === 'all' || !activeTeam || r.team_name === activeTeam));



            const raw = this.state.monthlyReportRaw || {};



            const tot = this.state.monthlyReportTotals || {};



            const logoSrc = window.ftmData ? window.ftmData.plugin_url + 'assets/images/logo.webp' : '';







            // Compute active period string



            const isQuarter = !!raw.is_quarter_report;



            const selectText = $('#ftm-monthly-select-month option:selected').text();



            const periodLabel = isQuarter ? (raw.quarter_name + ' Review (' + (raw.quarter_period || '') + ')') : (selectText || 'Monthly Operational Ledger');



            const docYearMonth = (new Date().toISOString().slice(0, 7)).replace('-', '');



            const generationDate = new Date().toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });



            const teamScopeLabel = (activeTeam === 'all' || !activeTeam) ? 'All Operational Units (WpCraft, WpNexus, WpTitans, WpHorizon)' : `Operational Unit: ${activeTeam}`;







            // Calculate aggregations for the filtered set



            let subDel = 0, subWip = 0, subTips = 0, subCanc = 0, subPart = 0, subCompl = 0, subS5 = 0, subS4 = 0, subCancCount = 0, subOntime = 0;



            const teamsMap = {};







            rows.forEach(r => {



                const del = parseFloat(r.total_delivery || 0);



                const wip = parseFloat(r.total_wip || 0);



                const tips = parseFloat(r.tips || 0);



                const canc = parseFloat(r.cancelled || 0);



                const part = parseFloat(r.partial || 0);



                const compl = parseInt(r.total_completed || 0);



                const s5 = parseInt(r.stars_5 || 0);



                const s4 = parseInt(r.stars_4 || 0);



                const cancCount = parseInt(r.total_cancel || 0);



                const ontime = parseInt(r.delivered_without_extension || 0);







                subDel += del;



                subWip += wip;



                subTips += tips;



                subCanc += canc;



                subPart += part;



                subCompl += compl;



                subS5 += s5;



                subS4 += s4;



                subCancCount += cancCount;



                subOntime += ontime;







                const tm = r.team_name || 'Unassigned';



                if (!teamsMap[tm]) teamsMap[tm] = { delivery: 0, wip: 0, cancelled: 0, cancelCount: 0, count: 0 };
                teamsMap[tm].delivery += del;
                teamsMap[tm].wip += wip;
                teamsMap[tm].cancelled += canc;
                teamsMap[tm].cancelCount += cancCount;
                teamsMap[tm].count++;
            });

            const subWorkload = subDel + subWip;
            const realizationRate = subWorkload > 0 ? ((subDel / subWorkload) * 100).toFixed(1) : '0.0';
            const avgPerOrder = subCompl > 0 ? (subDel / subCompl).toFixed(2) : '0.00';
            const fiveStarRatio = subCompl > 0 ? ((subS5 / subCompl) * 100).toFixed(1) : (tot.five_star_ratio || '0');
            const cancelRatePrint = (subCompl + subCancCount) > 0 ? ((subCancCount / (subCompl + subCancCount)) * 100).toFixed(1) : '0.0';

            // Format Unit Breakdown Strip
            let teamBreakdownHtml = '';
            Object.keys(teamsMap).sort().forEach(tm => {
                const item = teamsMap[tm];
                const cancPart = (item.cancelled > 0 || item.cancelCount > 0)
                    ? ` &bull; <span style="color:#dc2626; font-weight:700;">$${item.cancelled.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Cancelled (${item.cancelCount})</span>`
                    : '';
                teamBreakdownHtml += `
                    <div class="ftm-p-unit-item">
                        <strong>${self.escapeHtml(tm)}:</strong> $${item.delivery.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Delivered &bull; $${item.wip.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} WIP${cancPart} (${item.count} Specialists)
                    </div>
                `;



            });







            // Format Ledger Rows



            let tableRowsHtml = '';



            if (rows.length === 0) {



                tableRowsHtml = '<tr><td colspan="15" style="text-align:center; padding:20px; color:#64748b;">No specialist records available for this period and filter.</td></tr>';



            } else {



                rows.forEach((r, idx) => {



                    const del = parseFloat(r.total_delivery || 0);



                    const wip = parseFloat(r.total_wip || 0);



                    const tips = parseFloat(r.tips || 0);



                    const canc = parseFloat(r.cancelled || 0);



                    const part = parseFloat(r.partial || 0);



                    const compl = parseInt(r.total_completed || 0);



                    const s5 = parseInt(r.stars_5 || 0);



                    const s4 = parseInt(r.stars_4 || 0);



                    const totalCancel = parseInt(r.total_cancel || 0);



                    const ontime = parseInt(r.delivered_without_extension || 0);



                    const totalWorkload = del + wip;



                    const memberRealization = totalWorkload > 0 ? ((del / totalWorkload) * 100).toFixed(1) + '%' : '0.0%';







                    const rawMemberId = (r.member_custom_id || '').toString().trim().replace(/^#+/, '');



                    const formattedMemberId = rawMemberId ? '#' + rawMemberId : '-';







                    tableRowsHtml += `



                        <tr>



                            <td style="text-align:center; color:#64748b; font-weight:600;">${idx + 1}</td>



                            <td style="text-align:center;"><span class="ftm-p-badge">${self.escapeHtml(formattedMemberId)}</span></td>



                            <td><strong style="color:#0f172a; font-size:10px;">${self.escapeHtml(r.member_name || '')}</strong></td>



                            <td style="text-align:center;"><span class="ftm-p-badge" style="background:#f1f5f9; color:#1e293b;">${self.escapeHtml(r.team_name || '')}</span></td>



                            <td class="ftm-p-num" style="text-align:center; font-weight:700;">${compl}</td>



                            <td class="ftm-p-num ${del > 0 ? 'ftm-p-text-green' : ''}" style="font-weight:800; font-size:10px;">$${del.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>



                            <td class="ftm-p-num ${wip > 0 ? 'ftm-p-text-amber' : ''}" style="font-weight:700; font-size:10px;">$${wip.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>



                            <td class="ftm-p-num" style="font-weight:700; font-size:10px;">$${totalWorkload.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>



                            <td class="ftm-p-num" style="text-align:center; font-weight:700; color:${del > 0 ? '#047857' : '#64748b'};">${memberRealization}</td>



                            <td style="text-align:center; font-weight:700; color:#1d4ed8;">${s5 > 0 ? '⭐ ' + s5 : '<span style="color:#94a3b8;">&mdash;</span>'}</td>



                            <td style="text-align:center; font-weight:600;">${ontime > 0 ? ontime : '<span style="color:#94a3b8;">0</span>'}</td>



                        </tr>



                    `;



                });



            }







            const html = `



                <div class="ftm-print-doc ftm-print-monthly-statement ftm-page-landscape">



                    <div class="ftm-print-sheet ftm-sheet-landscape">



                        <!-- Formal Corporate Header -->



                        <div class="ftm-p-header">



                            <div class="ftm-p-header-brand">



                                ${logoSrc ? `<img src="${logoSrc}" class="ftm-p-logo" alt="FB International">` : ''}



                                <div class="ftm-p-brand-meta">



                                    <div class="ftm-p-company-name">FB INTERNATIONAL</div>



                                    <div class="ftm-p-department">GLOBAL DIGITAL OPERATIONS & FINANCIAL RECONCILIATION</div>



                                    <div class="ftm-p-subtitle">Specialized WordPress Engineering & Enterprise Client Delivery</div>



                                </div>



                            </div>



                            <div class="ftm-p-header-docmeta">



                                <div class="ftm-p-doc-title">EXECUTIVE PERFORMANCE & FINANCIAL RECONCILIATION STATEMENT</div>



                                <div class="ftm-p-doc-period">Reporting Period: <strong>${self.escapeHtml(periodLabel)}</strong></div>



                                <div class="ftm-p-doc-audit">Certified Audit Ledger &bull; Generated: ${generationDate} &bull; Scope: ${self.escapeHtml(teamScopeLabel)}</div>



                                <div class="ftm-p-badge-certified">CERTIFIED OPERATIONAL RECONCILIATION</div>



                            </div>



                        </div>







                        <!-- 6-Grid Executive KPI Matrix -->



                        <div class="ftm-p-kpi-row">



                            <div class="ftm-p-kpi-card">



                                <div class="ftm-p-kpi-lbl">TOTAL WORK ASSIGNED</div>



                                <div class="ftm-p-kpi-num">$${subWorkload.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>



                                <div class="ftm-p-kpi-sub">Committed Workload Pipeline</div>



                            </div>



                            <div class="ftm-p-kpi-card ftm-p-kpi-highlight">



                                <div class="ftm-p-kpi-lbl">NET DELIVERED REVENUE</div>



                                <div class="ftm-p-kpi-num ftm-p-text-green">$${subDel.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>



                                <div class="ftm-p-kpi-sub">Completed & Realized Earnings</div>



                            </div>



                            <div class="ftm-p-kpi-card">



                                <div class="ftm-p-kpi-lbl">ACTIVE WORK-IN-PROGRESS</div>



                                <div class="ftm-p-kpi-num ftm-p-text-amber">$${subWip.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>



                                <div class="ftm-p-kpi-sub">In-Flight Production Pipeline</div>



                            </div>

                            <div class="ftm-p-kpi-card">
                                <div class="ftm-p-kpi-lbl">CANCELLED OVERVIEW</div>
                                <div class="ftm-p-kpi-num" style="color:#dc2626;">$${subCanc.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                                <div class="ftm-p-kpi-sub">${subCancCount} cancelled &bull; ${cancelRatePrint}% rate</div>
                            </div>



                            <div class="ftm-p-kpi-card">



                                <div class="ftm-p-kpi-lbl">DELIVERY REALIZATION RATE</div>



                                <div class="ftm-p-kpi-num">${realizationRate}%</div>



                                <div class="ftm-p-kpi-sub">Target: 85.0% Benchmark</div>



                            </div>



                            <div class="ftm-p-kpi-card">



                                <div class="ftm-p-kpi-lbl">COMPLETED ORDERS</div>



                                <div class="ftm-p-kpi-num">${subCompl} Orders</div>



                                <div class="ftm-p-kpi-sub">Avg $${avgPerOrder} / delivery</div>



                            </div>



                            <div class="ftm-p-kpi-card">



                                <div class="ftm-p-kpi-lbl">5★ CLIENT SATISFACTION</div>



                                <div class="ftm-p-kpi-num">${fiveStarRatio}% <span style="font-size:10px; font-weight:700;">(${subS5}x 5★)</span></div>



                                <div class="ftm-p-kpi-sub">Satisfaction Benchmark Rating</div>



                            </div>



                        </div>







                        <!-- Operational Unit Breakdown Strip -->



                        <div class="ftm-p-unit-strip">



                            <div class="ftm-p-unit-label">OPERATIONAL UNIT RECONCILIATION:</div>



                            <div class="ftm-p-unit-badges">



                                ${teamBreakdownHtml || '<span style="color:#64748b;">No unit breakdown available</span>'}



                            </div>



                        </div>







                        <!-- Formal Financial Ledger Table -->



                        <table class="ftm-p-table">



                            <thead>



                                <tr>



                                    <th style="width:26px; text-align:center;">#</th>



                                    <th style="width:46px; text-align:center;">ID</th>



                                    <th>SPECIALIST NAME</th>



                                    <th style="width:75px; text-align:center;">TEAM / UNIT</th>



                                    <th style="width:55px; text-align:center;">ORDERS</th>



                                    <th style="text-align:right; width:90px;">NET DELIVERED</th>



                                    <th style="text-align:right; width:90px;">ACTIVE WIP</th>



                                    <th style="text-align:right; width:95px;">TOTAL WORKLOAD</th>



                                    <th style="text-align:center; width:80px;">REALIZATION</th>



                                    <th style="text-align:center; width:75px;">5★ REVIEWS</th>



                                    <th style="text-align:center; width:65px;">ON-TIME</th>



                                </tr>



                            </thead>



                            <tbody>



                                ${tableRowsHtml}



                            </tbody>



                            <tfoot>



                                <tr class="ftm-p-tfoot-row">



                                    <td colspan="4" style="text-align:left; font-weight:800; font-size:10px;">PERIOD CONSOLIDATED TOTALS (${rows.length} SPECIALISTS)</td>



                                    <td style="text-align:center; font-weight:800; font-size:10px;">${subCompl}</td>



                                    <td style="text-align:right; font-weight:900; font-size:10.5px; color:#047857;">$${subDel.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>



                                    <td style="text-align:right; font-weight:800; font-size:10px; color:#b45309;">$${subWip.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>



                                    <td style="text-align:right; font-weight:900; font-size:10.5px; color:#0f172a;">$${subWorkload.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>



                                    <td style="text-align:center; font-weight:800; font-size:10px; color:#2563eb;">${realizationRate}%</td>



                                    <td style="text-align:center; font-weight:800; font-size:10px; color:#1d4ed8;">${subS5 > 0 ? '⭐ ' + subS5 : '0'}</td>



                                    <td style="text-align:center; font-weight:800; font-size:10px;">${subOntime}</td>



                                </tr>



                            </tfoot>



                        </table>







                        <!-- Boardroom Sign-off & Audit Certification -->



                        <div class="ftm-p-signoff-wrap">



                            <div class="ftm-p-signoff-box">



                                <div class="ftm-p-signoff-title">PREPARED BY &bull; OPERATIONS LEAD</div>



                                <div class="ftm-p-signoff-line"></div>



                                <div class="ftm-p-signoff-name">Operations & Delivery Directorate</div>



                                <div class="ftm-p-signoff-meta">Signature & Date: ___________________________________</div>



                            </div>



                            <div class="ftm-p-signoff-box">



                                <div class="ftm-p-signoff-title">APPROVED BY &bull; EXECUTIVE MANAGEMENT</div>



                                <div class="ftm-p-signoff-line"></div>



                                <div class="ftm-p-signoff-name">Managing Director / FB International Executive Board</div>



                                <div class="ftm-p-signoff-meta">Executive Seal: FBINT-STATEMENT-AUDIT-VERIFIED</div>



                            </div>



                        </div>







                        <!-- Confidentiality & Compliance Footer -->



                        <div class="ftm-p-footer">



                            <div><strong>STRICTLY CONFIDENTIAL</strong> &bull; Agency FB International Management & Finance. Proprietary & Confidential.</div>



                            <div>Official Certified Operational Record &bull; Document Ref: FBINT-FIN-${docYearMonth} &bull; Page 1 of 1</div>



                        </div>



                    </div>



                </div>



            `;







            stage.html(html);



        },







        printReportDocument(contentHtml, docTitle, orientation = 'portrait') {



            const self = this;







            // 1. Strict explicit A4 @page CSS rule injection for Chromium / WebKit Print Engine



            let pageStyle = document.getElementById('ftm-print-page-style');



            if (!pageStyle) {



                pageStyle = document.createElement('style');



                pageStyle.id = 'ftm-print-page-style';



                document.head.appendChild(pageStyle);



            }



            pageStyle.textContent = `@page { size: A4 ${orientation}; margin: 10mm 12mm; }`;







            // 2. Populate in-page stage



            const stage = $('#ftm-print-stage');



            stage.removeAttr('aria-hidden');



            const wrappedContent = (contentHtml && contentHtml.includes('ftm-print-doc'))



                ? contentHtml



                : `<div class="ftm-print-doc ftm-page-${orientation}">${contentHtml}</div>`;



            stage.html(wrappedContent);







            // 3. Set document title so PDF save name matches report title



            const prevTitle = document.title;



            if (docTitle) {



                document.title = docTitle + ' - Agency FB International';



            }







            // 4. Trigger native print pipeline directly without altering screen DOM



            setTimeout(() => {



                window.print();



                setTimeout(() => {



                    document.title = prevTitle;



                }, 1000);



            }, 120);



        },







        printMonthlyExecutiveStatement() {



            this.populateMonthlyPrintStage();



            const content = $('#ftm-print-stage').html();



            this.printReportDocument(content, 'Executive Monthly Financial Reconciliation Statement', 'landscape');



        },







        populateWeeklyPrintStage() {



            const self = this;



            const stage = $('#ftm-print-stage');



            const data = this.state.weeklyReportData || {};



            const members = data.members || [];



            const logoSrc = window.ftmData ? window.ftmData.plugin_url + 'assets/images/logo.webp' : '';



            const teamScope = (self.state.weeklyReportTeam === 'all' || !self.state.weeklyReportTeam) ? 'All Operational Units' : `Operational Unit: ${self.state.weeklyReportTeam}`;



            const formatRange = $('#ftm-weekly-range-label').text() || '7-Day Operations Rollup';



            const generationDate = new Date().toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });







            let totHours = data.total_weekly_hours || 0;



            let totTasks = data.total_tasks_completed || 0;



            let totBlockers = data.total_blockers || 0;



            let totProjects = data.active_projects_count || 0;



            let totWipProjects = 0;







            let weeklyRowsHtml = '';



            if (members.length === 0) {



                weeklyRowsHtml = '<tr><td colspan="10" style="text-align:center; padding:20px; color:#64748b;">No member records found for this week.</td></tr>';



            } else {



                members.forEach((m, idx) => {



                    totWipProjects += (m.wip_count || 0);



                    const daysActiveText = `${m.days_worked}d work` + 



                        (m.days_leave > 0 ? ` &bull; ${m.days_leave}d leave` : '') +



                        (m.days_weekend > 0 ? ` &bull; ${m.days_weekend}d off` : '');







                    const projectsText = (m.projects && m.projects.length > 0)



                        ? m.projects.join(', ')



                        : '-';







                    const rawId = (m.custom_id || m.id || m.user_id || '').toString().trim().replace(/^#+/, '');



                    const formattedId = rawId ? '#' + rawId : '-';







                    weeklyRowsHtml += `



                        <tr>



                            <td style="text-align:center; color:#64748b;">${idx + 1}</td>



                            <td style="text-align:center;"><span class="ftm-p-badge">${formattedId}</span></td>



                            <td>



                                <strong>${self.escapeHtml(m.name || '')}</strong>



                                <div style="font-size:8.5px; color:#64748b;">${self.escapeHtml(m.role_label || 'WordPress Developer')}</div>



                            </td>



                            <td><span class="ftm-p-badge">${self.escapeHtml(m.team || '')}</span></td>



                            <td style="white-space:nowrap; font-size:9px;">${daysActiveText}</td>



                            <td style="text-align:center; font-weight:800; color:#1d4ed8; font-family:'Consolas',monospace;">${self.formatHoursDisplay(m.total_hours)}</td>



                            <td style="text-align:center; font-weight:700; color:#047857;">${m.tasks_completed || 0}</td>



                            <td style="text-align:center; font-weight:700; color:#2563eb;">${m.wip_count || 0}</td>



                            <td style="text-align:center; font-weight:700; color:${m.blockers_count > 0 ? '#b91c1c' : '#64748b'};">${m.blockers_count || 0}</td>



                            <td style="font-size:9px; color:#334155;">${self.escapeHtml(projectsText)}</td>



                        </tr>



                    `;



                });



            }







            const html = `



                <div class="ftm-print-doc ftm-print-weekly-rollup ftm-page-portrait">



                    <div class="ftm-print-sheet ftm-sheet-portrait">



                        <!-- Formal Header -->



                        <div class="ftm-p-header">



                            <div class="ftm-p-header-brand">



                                ${logoSrc ? `<img src="${logoSrc}" class="ftm-p-logo" alt="FB International">` : ''}



                                <div class="ftm-p-brand-meta">



                                    <div class="ftm-p-company-name">FB INTERNATIONAL</div>



                                    <div class="ftm-p-department">DIGITAL OPERATIONS & PRODUCTION MANAGEMENT</div>



                                    <div class="ftm-p-subtitle">Weekly Operational Capacity & Output Rollup</div>



                                </div>



                            </div>



                            <div class="ftm-p-header-docmeta">



                                <div class="ftm-p-doc-title">WEEKLY PERFORMANCE & CAPACITY ROLLUP</div>



                                <div class="ftm-p-doc-period">${self.escapeHtml(formatRange)}</div>



                                <div class="ftm-p-doc-audit">Scope: <strong>${self.escapeHtml(teamScope)}</strong> &bull; Generated: ${generationDate}</div>



                                <div class="ftm-p-badge-certified">CERTIFIED OPERATIONS ROLLUP</div>



                            </div>



                        </div>







                        <!-- 4 Compact KPI Cards -->



                        <div class="ftm-p-weekly-kpis">



                            <div class="ftm-p-weekly-kpi">



                                <div class="ftm-p-weekly-kpi-lbl">TOTAL HOURS LOGGED</div>



                                <div class="ftm-p-weekly-kpi-val ftm-p-text-blue">${self.formatHoursDisplay(totHours)}</div>



                                <div class="ftm-p-weekly-kpi-sub">7-Day Team Cumulative</div>



                            </div>



                            <div class="ftm-p-weekly-kpi">



                                <div class="ftm-p-weekly-kpi-lbl">TASKS COMPLETED</div>



                                <div class="ftm-p-weekly-kpi-val ftm-p-text-green">${totTasks}</div>



                                <div class="ftm-p-weekly-kpi-sub">Deliverables Logged</div>



                            </div>



                            <div class="ftm-p-weekly-kpi">



                                <div class="ftm-p-weekly-kpi-lbl">BLOCKERS & FLAGS</div>



                                <div class="ftm-p-weekly-kpi-val ${totBlockers > 0 ? 'ftm-p-text-rose' : ''}">${totBlockers}</div>



                                <div class="ftm-p-weekly-kpi-sub">${totBlockers > 0 ? 'Requires attention' : 'All clear'}</div>



                            </div>



                            <div class="ftm-p-weekly-kpi">



                                <div class="ftm-p-weekly-kpi-lbl">ACTIVE PROJECTS</div>



                                <div class="ftm-p-weekly-kpi-val ftm-p-text-purple">${totProjects}</div>



                                <div class="ftm-p-weekly-kpi-sub">Milestones in Flight</div>



                            </div>



                        </div>







                        <!-- Compact Weekly Member Table -->



                        <table class="ftm-p-table ftm-p-weekly-table">



                            <thead>



                                <tr>



                                    <th style="width:28px; text-align:center;">#</th>



                                    <th style="width:55px; text-align:center;">ID</th>



                                    <th>SPECIALIST</th>



                                    <th style="width:85px; text-align:center;">TEAM</th>



                                    <th style="width:115px;">DAYS ACTIVE</th>



                                    <th style="text-align:center; width:85px;">HOURS</th>



                                    <th style="text-align:center; width:65px;">COMPLETED</th>



                                    <th style="text-align:center; width:65px;">IN PROGRESS</th>



                                    <th style="text-align:center; width:60px;">BLOCKERS</th>



                                    <th>PROJECTS WORKED</th>



                                </tr>



                            </thead>



                            <tbody>



                                ${weeklyRowsHtml}



                            </tbody>



                            <tfoot>



                                <tr class="ftm-p-tfoot-row">



                                    <td colspan="5" style="text-align:left; font-weight:800;">PERIOD CAPACITY TOTALS (${members.length} SPECIALISTS)</td>



                                    <td style="text-align:center; font-weight:800; color:#1d4ed8; font-family:'Consolas',monospace;">${self.formatHoursDisplay(totHours)}</td>



                                    <td style="text-align:center; font-weight:800; color:#047857;">${totTasks}</td>



                                    <td style="text-align:center; font-weight:800; color:#2563eb;">${totWipProjects}</td>



                                    <td style="text-align:center; font-weight:800; color:${totBlockers > 0 ? '#b91c1c' : '#64748b'};">${totBlockers}</td>



                                    <td style="font-weight:700;">${totProjects} Active Milestones</td>



                                </tr>



                            </tfoot>



                        </table>







                        <!-- Boardroom Sign-off Block -->



                        <div class="ftm-p-signoff-wrap">



                            <div class="ftm-p-signoff-box">



                                <div class="ftm-p-signoff-title">REVIEWED BY &bull; TEAM LEADER / PM</div>



                                <div class="ftm-p-signoff-line"></div>



                                <div class="ftm-p-signoff-name">Team Operations & Project Coordination</div>



                                <div class="ftm-p-signoff-meta">Signature: ___________________________ Date: ____________</div>



                            </div>



                            <div class="ftm-p-signoff-box">



                                <div class="ftm-p-signoff-title">VERIFIED BY &bull; OPERATIONS DIRECTOR</div>



                                <div class="ftm-p-signoff-line"></div>



                                <div class="ftm-p-signoff-name">FB International Digital Production Directorate</div>



                                <div class="ftm-p-signoff-meta">Approval Seal: CERTIFIED-WEEKLY-CAPACITY</div>



                            </div>



                        </div>







                        <!-- Footer -->



                        <div class="ftm-p-footer">



                            <div><strong>FB INTERNATIONAL &bull; DIGITAL OPERATIONS & TEAM PERFORMANCE</strong></div>



                            <div>Official confidential operations document &bull; Internal executive & operational review &bull; Page 1 of 1</div>



                        </div>



                    </div>



                </div>



            `;







            stage.html(html);



        },







        printWeeklyRollupStatement() {



            this.populateWeeklyPrintStage();



            const content = $('#ftm-print-stage').html();



            this.printReportDocument(content, 'Weekly Performance & Capacity Rollup', 'portrait');



        },







        populateDailyDocPrintStage() {



            const stage = $('#ftm-print-stage');



            const docContent = $('#ftm-exec-doc-body').html();



            stage.html(`



                <div class="ftm-print-doc ftm-page-portrait">



                    ${docContent}



                </div>



            `);



        },







        printDailyExecutiveDoc() {



            this.populateDailyDocPrintStage();



            const content = $('#ftm-print-stage').html();



            this.printReportDocument(content, 'Daily Operations Executive Briefing', 'portrait');



        },







        handleCsvImport() {



            const self = this;



            const fileInput = document.getElementById('ftm-import-csv-file');



            if (!fileInput || !fileInput.files.length) {



                this.toast('Please select a CSV file to upload.', 'error');



                return;



            }







            const month = $('#ftm-import-reporting-month').val() || $('#ftm-monthly-select-month').val();



            const formData = new FormData();



            formData.append('csv_file', fileInput.files[0]);



            formData.append('reporting_month', month);







            const submitBtn = $('#ftm-btn-submit-import-csv');



            const origHtml = submitBtn.html();



            submitBtn.prop('disabled', true).html('<span class="ftm-spinner"></span> <span>Importing...</span>');







            fetch(ftmData.root + 'reports/monthly/import-csv', {



                method: 'POST',



                headers: {



                    'X-WP-Nonce': ftmData.nonce



                },



                body: formData



            })



            .then(res => res.json())



            .then(data => {



                submitBtn.prop('disabled', false).html(origHtml);



                if (data.code || data.error) {



                    self.toast(data.message || 'Import failed.', 'error');



                } else {



                    $('#ftm-modal-import-csv').addClass('hidden');



                    self.toast(data.message || 'Historical CSV imported successfully!', 'success');



                    self.loadMonthlyReportsView(month);



                }



            })



            .catch(err => {



                submitBtn.prop('disabled', false).html(origHtml);



                self.toast('Network error during CSV upload: ' + err.message, 'error');



            });



        },











        renderMonthlyHighlights(rows, isQuarter, quarterName) {
            const grid = $('#ftm-monthly-highlights-grid');
            if (!grid.length) return;

            if (!rows || !rows.length) {
                grid.html('<div style="grid-column: 1 / -1; text-align:center; padding:16px; color:var(--ftm-text-muted); font-size:13px;">No member records available for the selected period.</div>');
                return;
            }

            const eligibleRows = rows.filter(r => !ftmIsMgmt(r.team_name));

            // 1. Highest Delivered Revenue
            let topRev = null;
            eligibleRows.forEach(r => {
                const rev = parseFloat(r.total_delivery || 0);
                if (rev > 0 && (!topRev || rev > parseFloat(topRev.total_delivery || 0))) {
                    topRev = r;
                }
            });

            // 2. Most Completed Orders
            let topOrders = null;
            eligibleRows.forEach(r => {
                const orders = parseInt(r.total_completed || 0);
                if (orders > 0 && (!topOrders || orders > parseInt(topOrders.total_completed || 0))) {
                    topOrders = r;
                }
            });

            // 3. Best On-Time Delivery
            let topOnTime = null;
            eligibleRows.forEach(r => {
                const orders = parseInt(r.total_completed || 0);
                const ontime = parseInt(r.delivered_without_extension || 0);
                if (orders >= 1) {
                    const rate = Math.min(100, Math.round((ontime / orders) * 100));
                    if (!topOnTime || rate > topOnTime.rate || (rate === topOnTime.rate && orders > topOnTime.orders)) {
                        topOnTime = { row: r, rate: rate, count: ontime, orders: orders };
                    }
                }
            });

            // 4. Overall Top Performer
            let topScoreMember = null;
            let maxCohortRev = 0;
            eligibleRows.forEach(r => {
                const rRev = parseFloat(r.total_delivery || 0);
                if (rRev > maxCohortRev) maxCohortRev = rRev;
            });

            eligibleRows.forEach(r => {
                const orders = parseInt(r.total_completed || 0);
                if (orders >= 1) {
                    const ontime = Math.min(orders, parseInt(r.delivered_without_extension || 0));
                    const cancel = parseInt(r.total_cancel || 0);
                    const s5 = parseInt(r.stars_5 || 0);
                    const s4 = parseInt(r.stars_4 || 0);
                    const s3 = parseInt(r.stars_3 || 0);
                    const s2 = parseInt(r.stars_2 || 0);
                    const s1 = parseInt(r.stars_1 || 0);
                    const reviewCount = s5 + s4 + s3 + s2 + s1;
                    const delRev = parseFloat(r.total_delivery || 0);

                    const p1 = maxCohortRev > 0 ? (delRev / maxCohortRev) * 40.0 : 0.0;
                    let p2Rating = 12.0;
                    if (reviewCount > 0) {
                        const avgRating = (5*s5 + 4*s4 + 3*s3 + 2*s2 + 1*s1) / reviewCount;
                        p2Rating = Math.min(1.0, avgRating / 5.0) * 20.0;
                    }
                    const p2S5Bonus = Math.min(10.0, s5 * 2.0);
                    const p2 = p2Rating + p2S5Bonus;
                    const p3 = (orders > 0) ? Math.min(1.0, ontime / orders) * 15.0 : 0.0;
                    const p4 = Math.max(0.0, 1.0 - (cancel / Math.max(1, orders + cancel))) * 15.0;
                    const totalScore = Math.min(100, Math.round(p1 + p2 + p3 + p4));

                    if (!topScoreMember || totalScore > topScoreMember.score || (totalScore === topScoreMember.score && delRev > topScoreMember.rev)) {
                        topScoreMember = { row: r, score: totalScore, rev: delRev };
                    }
                }
            });

            const makeAvatar = (row) => {
                const name = row ? (row.member_name || '') : 'User';
                const avatar = row ? (row.avatar_url || '') : '';
                if (avatar) {
                    return `<img src="${avatar}" alt="${name}" class="ftm-highlight-avatar" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #ffffff; box-shadow:0 1px 4px rgba(0,0,0,0.1); flex-shrink:0;">`;
                }
                const initials = name.split(' ').map(p => p[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'SP';
                return `<div class="ftm-highlight-avatar" style="width:36px; height:36px; border-radius:50%; background:#eff6ff; color:#2563eb; font-weight:700; font-size:12px; display:flex; align-items:center; justify-content:center; border:2px solid #ffffff; box-shadow:0 1px 4px rgba(0,0,0,0.1); flex-shrink:0;">${initials}</div>`;
            };

            let html = `
                <!-- 1. Overall Top Performer -->
                <div class="ftm-highlight-card">
                    <div class="ftm-highlight-card-top">
                        <div class="ftm-highlight-icon-box gold">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <span class="ftm-highlight-label">Overall Top Performer</span>
                        <span class="ftm-highlight-rank-pill gold">#1</span>
                    </div>
                    <div class="ftm-highlight-card-body">
                        ${makeAvatar(topScoreMember ? topScoreMember.row : null)}
                        <div class="ftm-highlight-details">
                            <div class="ftm-highlight-name">${topScoreMember ? topScoreMember.row.member_name : 'No eligible specialist'}</div>
                            <div class="ftm-highlight-meta-val">${topScoreMember ? `${ftmEsc(topScoreMember.row.team_name)} • Score ${topScoreMember.score}/100` : 'Pending completed orders'}</div>
                        </div>
                    </div>
                </div>

                <!-- 2. Top Delivered Revenue -->
                <div class="ftm-highlight-card">
                    <div class="ftm-highlight-card-top">
                        <div class="ftm-highlight-icon-box emerald">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <span class="ftm-highlight-label">Top Delivered Revenue</span>
                        <span class="ftm-highlight-rank-pill emerald">#1</span>
                    </div>
                    <div class="ftm-highlight-card-body">
                        ${makeAvatar(topRev)}
                        <div class="ftm-highlight-details">
                            <div class="ftm-highlight-name">${topRev ? topRev.member_name : 'No deliveries yet'}</div>
                            <div class="ftm-highlight-meta-val text-emerald">${topRev ? `${ftmEsc(topRev.team_name)} • $${parseFloat(topRev.total_delivery).toFixed(2)}` : '$0.00 realized'}</div>
                        </div>
                    </div>
                </div>

                <!-- 3. Most Completed Orders -->
                <div class="ftm-highlight-card">
                    <div class="ftm-highlight-card-top">
                        <div class="ftm-highlight-icon-box blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"></path></svg>
                        </div>
                        <span class="ftm-highlight-label">Most Completed Orders</span>
                        <span class="ftm-highlight-rank-pill blue">#1</span>
                    </div>
                    <div class="ftm-highlight-card-body">
                        ${makeAvatar(topOrders)}
                        <div class="ftm-highlight-details">
                            <div class="ftm-highlight-name">${topOrders ? topOrders.member_name : 'No orders completed'}</div>
                            <div class="ftm-highlight-meta-val">${topOrders ? `${ftmEsc(topOrders.team_name)} • ${topOrders.total_completed} orders` : 'Pipeline active'}</div>
                        </div>
                    </div>
                </div>

                <!-- 4. Best On-Time Delivery -->
                <div class="ftm-highlight-card">
                    <div class="ftm-highlight-card-top">
                        <div class="ftm-highlight-icon-box indigo">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                        </div>
                        <span class="ftm-highlight-label">Best On-Time Delivery</span>
                        <span class="ftm-highlight-rank-pill indigo">#1</span>
                    </div>
                    <div class="ftm-highlight-card-body">
                        ${makeAvatar(topOnTime ? topOnTime.row : null)}
                        <div class="ftm-highlight-details">
                            <div class="ftm-highlight-name">${topOnTime ? topOnTime.row.member_name : 'No deliveries evaluated'}</div>
                            <div class="ftm-highlight-meta-val">${topOnTime ? `${ftmEsc(topOnTime.row.team_name)} • ${topOnTime.rate}% (${topOnTime.count}/${topOnTime.orders})` : 'Standby'}</div>
                        </div>
                    </div>
                </div>
            `;
            grid.html(html);
        },







        loadLeaderboardView(period, periodKey, team, sort) {



            const self = this;



            const targetPeriod = period || this.state.leaderboardPeriod || 'monthly';



            const targetKey = (periodKey !== undefined && periodKey !== null) ? periodKey : (this.state.leaderboardPeriodKey || '');



            const targetTeam = team || this.state.leaderboardTeam || 'all';



            const targetSort = sort || this.state.leaderboardSort || 'score';







            this.state.leaderboardPeriod = targetPeriod;



            this.state.leaderboardPeriodKey = targetKey;



            this.state.leaderboardTeam = targetTeam;



            this.state.leaderboardSort = targetSort;







            const tbody = $('#ftm-leaderboard-tbody');



            tbody.html('<tr><td colspan="10" class="ftm-table-loading"><div class="ftm-spinner"></div> Computing transparent performance rankings and metric pillars...</td></tr>');



            $('#ftm-leaderboard-podium').html('<div style="grid-column: 1 / -1; text-align:center; padding:20px;"><div class="ftm-spinner"></div></div>');







            const queryUrl = `reports/leaderboard?period=${encodeURIComponent(targetPeriod)}&period_key=${encodeURIComponent(targetKey)}&team=${encodeURIComponent(targetTeam)}&sort=${encodeURIComponent(targetSort)}`;







            this.api(queryUrl).then(res => {



                self.state.leaderboardData = res;







                // Sync badge & labels



                if (targetPeriod === 'all_time') {



                    $('#ftm-lb-period-text').text(res.period_label || 'All Historical Records (Jan 2026 – Present)');



                    $('#ftm-lb-period-badge').removeClass('hidden');



                    $('#ftm-lb-month-select').addClass('hidden');



                    $('#ftm-lb-quarter-select').addClass('hidden');



                } else if (targetPeriod === 'quarterly') {



                    $('#ftm-lb-period-badge').addClass('hidden');



                    $('#ftm-lb-month-select').addClass('hidden');



                    $('#ftm-lb-quarter-select').removeClass('hidden');



                } else {



                    $('#ftm-lb-period-badge').addClass('hidden');



                    $('#ftm-lb-month-select').removeClass('hidden');



                    $('#ftm-lb-quarter-select').addClass('hidden');



                }







                // Update team counts in filter pills



                if (res.team_counts) {



                    $('#ftm-lb-count-all').text(res.team_counts.all || 0);



                    $('#ftm-lb-count-wptitans').text(res.team_counts.WpTitans || 0);



                    $('#ftm-lb-count-wpnexus').text(res.team_counts.WpNexus || 0);



                    $('#ftm-lb-count-wpcraft').text(res.team_counts.WpCraft || 0);
                    $('#ftm-lb-count-wphorizon').text(res.team_counts.WpHorizon || 0);



                }







                self.renderLeaderboard(res);



            }).catch(err => {



                console.error(err);



                tbody.html(`<tr><td colspan="10" style="text-align:center; padding:30px; color:var(--ftm-cancelled);">Failed to load performance leaderboard: ${err.message || 'Error'}</td></tr>`);



                $('#ftm-leaderboard-podium').html('');



            });



        },







        renderLeaderboard(data) {



            const self = this;



            const podiumWrap = $('#ftm-leaderboard-podium');



            const tbody = $('#ftm-leaderboard-tbody');



            const mobileCardsWrap = $('#ftm-leaderboard-mobile-cards');







            const formatUsd = (val) => {



                const n = parseFloat(val) || 0;



                return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });



            };







            // Strictly qualify podium members to those with completed orders and positive score



            const rawPodium = data.podium || [];



            const podium = rawPodium.filter(m => (m.completed_orders > 0 && m.performance_score > 0));



            const rankings = data.rankings || [];







            // 1. Render Top Performers Podium with Mobile Auto-Slider



            podiumWrap.removeClass('podium-count-1 podium-count-2 podium-count-3 has-slider');



            if (self._lbSliderInterval) {



                clearInterval(self._lbSliderInterval);



                self._lbSliderInterval = null;



            }







            let dotsWrap = $('#ftm-lb-slider-dots');



            if (!dotsWrap.length) {



                podiumWrap.after('<div id="ftm-lb-slider-dots" class="ftm-lb-slider-dots hidden"></div>');



                dotsWrap = $('#ftm-lb-slider-dots');



            }



            dotsWrap.empty().addClass('hidden').removeClass('visible');







            if (podium.length > 0) {



                podiumWrap.addClass('podium-count-' + podium.length);



                if (podium.length > 1) {



                    podiumWrap.addClass('has-slider');



                }







                let podHtml = '';



                const rankClasses = ['ftm-podium-gold', 'ftm-podium-silver', 'ftm-podium-bronze'];



                const rankBadges = ['rank-gold', 'rank-silver', 'rank-bronze'];



                const rankLabels = ['#1', '#2', '#3'];



                const rankTrophies = [



                    '<span class="ftm-podium-trophy gold" title="Rank 1: Champion">👑</span>',



                    '<span class="ftm-podium-trophy silver" title="Rank 2: Excellence Finalist">🥈</span>',



                    '<span class="ftm-podium-trophy bronze" title="Rank 3: High Performer">🥉</span>'



                ];







                podium.forEach((m, idx) => {



                    const cardClass = rankClasses[idx] || 'ftm-podium-bronze';



                    const badgeClass = rankBadges[idx] || 'rank-bronze';



                    const badgeLabel = rankLabels[idx] || `#${idx+1}`;



                    const trophyIcon = rankTrophies[idx] || '🏅';



                    const reviewCount = parseInt(m.review_count, 10) || 0;



                    const ratingHtml = reviewCount > 0



                        ? `<span class="ftm-metric-val"><span style="color:#f59e0b;">★</span> ${parseFloat(m.client_rating).toFixed(1)} <span style="font-size:10.5px; font-weight:normal; color:var(--ftm-text-muted);">(${reviewCount})</span></span>`



                        : `<span class="ftm-metric-val ftm-val-muted">No rating (0)</span>`;







                    podHtml += `



                        <div class="ftm-lb-podium-card ${cardClass}" data-slide-index="${idx}">



                            <div class="ftm-podium-rank-badge ${badgeClass}">



                                <span class="ftm-podium-rank-star">★</span>



                                <span>${badgeLabel}</span>



                            </div>



                            <div class="ftm-podium-profile">



                                <div class="ftm-podium-avatar-wrap">



                                    <img class="ftm-podium-avatar ftm-trigger-member-profile" data-id="${m.user_id}" src="${m.avatar_url}" alt="${m.member_name}">



                                    <span class="ftm-podium-rank-indicator ${badgeClass}">${idx+1}</span>



                                </div>



                                <div class="ftm-podium-info">



                                    <div class="ftm-podium-name-row">



                                        ${trophyIcon}



                                        <span class="ftm-podium-name ftm-trigger-member-profile" data-id="${m.user_id}">${m.member_name}</span>



                                    </div>



                                    <div class="ftm-podium-team-role">${ftmEsc(m.team_name)} • ${ftmEsc(m.role_label)}</div>



                                    ${m.is_top_performer ? `



                                        <div class="ftm-podium-top-badge">



                                            <span style="color:#d97706;">★</span>



                                            <span>Top Performer</span>



                                        </div>



                                    ` : ''}



                                </div>



                            </div>



                            <div class="ftm-podium-metrics">



                                <div class="ftm-podium-metric">



                                    <span class="ftm-metric-val">${formatUsd(m.net_delivered_revenue)}</span>



                                    <span class="ftm-metric-lbl">DELIVERED</span>



                                </div>



                                <div class="ftm-podium-metric">



                                    <span class="ftm-metric-val">${m.completed_orders}</span>



                                    <span class="ftm-metric-lbl">ORDERS</span>



                                </div>



                                <div class="ftm-podium-metric">



                                    <span class="ftm-metric-val ${m.on_time_rate >= 80 ? 'text-emerald' : (m.on_time_rate >= 50 ? 'text-amber' : 'text-rose')}">${m.on_time_rate}%</span>



                                    <span class="ftm-metric-lbl">ON-TIME</span>



                                </div>



                                <div class="ftm-podium-metric">



                                    ${ratingHtml}



                                    <span class="ftm-metric-lbl">RATING</span>



                                </div>



                                <div class="ftm-podium-metric ftm-metric-score">



                                    <span class="ftm-score-val">${m.performance_score}</span>



                                    <span class="ftm-metric-lbl">SCORE</span>



                                </div>



                            </div>



                        </div>



                    `;



                });



                podiumWrap.html(podHtml);







                // Setup Mobile Auto-Slider if multiple podium cards



                if (podium.length > 1) {



                    let dotsHtml = '';



                    podium.forEach((_, dIdx) => {



                        dotsHtml += `<button type="button" class="ftm-slider-dot ${dIdx === 0 ? 'active' : ''}" data-index="${dIdx}" aria-label="Slide ${dIdx + 1}"></button>`;



                    });



                    dotsWrap.html(dotsHtml).removeClass('hidden').addClass('visible');







                    let activeSlide = 0;



                    const cardCount = podium.length;







                    const scrollToSlide = (idx) => {



                        if (idx < 0) idx = cardCount - 1;



                        if (idx >= cardCount) idx = 0;



                        activeSlide = idx;



                        const el = podiumWrap[0];



                        if (el) {



                            // Align to the card's real left edge so the slide never lands part-way
                            const cards = el.querySelectorAll('.ftm-lb-podium-card');
                            const target = cards[idx];
                            const left = target ? (target.getBoundingClientRect().left - el.getBoundingClientRect().left + el.scrollLeft) : idx * el.clientWidth;
                            el.scrollTo({ left: Math.max(0, Math.round(left)), behavior: 'smooth' });



                        }



                        dotsWrap.find('.ftm-slider-dot').removeClass('active');



                        dotsWrap.find(`.ftm-slider-dot[data-index="${idx}"]`).addClass('active');



                    };







                    const stopAutoSlide = () => {



                        if (self._lbSliderInterval) {



                            clearInterval(self._lbSliderInterval);



                            self._lbSliderInterval = null;



                        }



                    };







                    const startAutoSlide = () => { stopAutoSlide(); }; // no auto-advance: it moved cards while people were reading







                    dotsWrap.off('click', '.ftm-slider-dot').on('click', '.ftm-slider-dot', function(e) {



                        e.preventDefault();



                        const targetIdx = parseInt($(this).data('index'), 10) || 0;



                        stopAutoSlide();



                        scrollToSlide(targetIdx);



                        startAutoSlide();



                    });







                    podiumWrap.off('scroll.lbSlider').on('scroll.lbSlider', function() {



                        const el = this;



                        const slideWidth = el.clientWidth;



                        if (slideWidth > 0) {



                            const currentIdx = Math.round(el.scrollLeft / slideWidth);



                            if (currentIdx !== activeSlide && currentIdx >= 0 && currentIdx < cardCount) {



                                activeSlide = currentIdx;



                                dotsWrap.find('.ftm-slider-dot').removeClass('active');



                                dotsWrap.find(`.ftm-slider-dot[data-index="${currentIdx}"]`).addClass('active');



                            }



                        }



                    });







                    startAutoSlide();







                    podiumWrap.off('touchstart.lbSlider mouseenter.lbSlider').on('touchstart.lbSlider mouseenter.lbSlider', stopAutoSlide);



                    podiumWrap.off('touchend.lbSlider mouseleave.lbSlider').on('touchend.lbSlider mouseleave.lbSlider', () => {



                        stopAutoSlide();



                        startAutoSlide();



                    });



                }



            } else {



                podiumWrap.html('');



            }







            // 2. Render Full Table Rows



            if (!rankings.length) {



                tbody.html('<tr><td colspan="10" style="text-align:center; padding:30px; color:var(--ftm-text-muted);">No specialists matching current filters.</td></tr>');



                mobileCardsWrap.html('<div style="text-align:center; padding:20px; color:var(--ftm-text-muted);">No specialists matching current filters.</div>');



                return;



            }







            let tableHtml = '';



            let mobHtml = '';







            rankings.forEach(m => {



                const rank = m.rank;



                const isInsufficient = (m.eligibility_status === 'insufficient_data');



                const ontimePillClass = isInsufficient ? 'pill-gray' : (m.on_time_rate >= 80 ? 'pill-green' : (m.on_time_rate >= 50 ? 'pill-amber' : 'pill-red'));



                const cancelPillClass = (m.cancellation_rate <= 5) ? 'pill-green' : 'pill-red';



                const teamClass = (m.team_name || 'wpcraft').toLowerCase().replace(/\s+/g, '-');



                const reviewCount = parseInt(m.review_count, 10) || 0;



                const ratingTableHtml = reviewCount > 0



                    ? `<span style="font-weight:600; font-size:12.5px;"><span style="color:#f59e0b;">★</span> ${parseFloat(m.client_rating).toFixed(1)} <span style="font-size:11px; color:var(--ftm-text-muted);">(${reviewCount})</span></span>`



                    : `<span style="font-size:12px; color:var(--ftm-text-muted);">No rating (0)</span>`;







                tableHtml += `



                    <tr>



                        <td style="text-align:center; font-weight:700; color:${rank === '-' ? 'var(--ftm-text-muted)' : 'var(--ftm-text-secondary)'};">${rank === '-' ? '&mdash;' : rank}</td>



                        <td>



                            <div class="ftm-lb-member-flex">



                                <img src="${m.avatar_url}" class="ftm-lb-avatar ftm-trigger-member-profile" data-id="${m.user_id}" alt="${m.member_name}">



                                <div class="ftm-lb-member-meta">



                                    <span class="ftm-lb-name ftm-trigger-member-profile" data-id="${m.user_id}">${m.member_name}</span>



                                    <span class="ftm-lb-role">${ftmEsc(m.role_label)}</span>



                                </div>



                            </div>



                        </td>



                        <td>



                            <span class="ftm-team-badge ftm-team-${teamClass}">${ftmEsc(m.team_name)}</span>



                        </td>



                        <td style="text-align:right; font-weight:600; color:var(--ftm-text-primary);">



                            ${formatUsd(m.net_delivered_revenue)}



                        </td>



                        <td style="text-align:center; font-weight:600;">



                            ${m.completed_orders}



                        </td>



                        <td style="text-align:center;">



                            <span class="ftm-rate-pill ${ontimePillClass}">${m.on_time_rate}%</span>



                        </td>



                        <td style="text-align:center;">



                            ${ratingTableHtml}



                        </td>



                        <td style="text-align:center;">



                            <span class="ftm-rate-pill ${cancelPillClass}">${m.cancellation_rate}%</span>



                        </td>



                        <td>



                            <div class="ftm-score-bar-wrap">



                                <span class="ftm-score-num">${m.performance_score}</span>



                                <div class="ftm-score-track" title="P1 Revenue: ${m.score_breakdown ? m.score_breakdown.p1_revenue : 0} | P2 Satisfaction: ${m.score_breakdown ? m.score_breakdown.p2_satisfaction : 0} | P3 On-Time: ${m.score_breakdown ? m.score_breakdown.p3_ontime : 0} | P4 Retention: ${m.score_breakdown ? m.score_breakdown.p4_retention : 0}">



                                    <div class="ftm-score-fill" style="width:${m.performance_score}%;"></div>



                                </div>



                            </div>



                        </td>



                        <td style="text-align:center;">



                            <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-outline ftm-trigger-member-profile" data-id="${m.user_id}">



                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                <span>View</span>



                            </button>



                        </td>



                    </tr>



                `;







                // Mobile Card layout



                const ratingMobHtml = reviewCount > 0



                    ? `<strong style="font-size:12.5px;"><span style="color:#f59e0b;">★</span> ${parseFloat(m.client_rating).toFixed(1)} (${reviewCount})</strong>`



                    : `<strong style="font-size:12.5px; color:var(--ftm-text-muted); font-weight:500;">No rating (0)</strong>`;







                mobHtml += `



                    <div class="ftm-lb-mobile-card">



                        <div class="ftm-lb-mc-head">



                            <div class="ftm-lb-mc-member">



                                <span style="font-weight:800; font-size:14px; min-width:24px; color:var(--ftm-text-muted);">${rank === '-' ? '&mdash;' : '#' + rank}</span>



                                <img src="${m.avatar_url}" class="ftm-lb-avatar ftm-trigger-member-profile" data-id="${m.user_id}" alt="${m.member_name}">



                                <div>



                                    <div class="ftm-lb-name ftm-trigger-member-profile" data-id="${m.user_id}">${m.member_name}</div>



                                    <div class="ftm-lb-role">${ftmEsc(m.team_name)} • ${ftmEsc(m.role_label)}</div>



                                </div>



                            </div>



                            <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-outline ftm-trigger-member-profile" data-id="${m.user_id}">



                                <span>View</span>



                            </button>



                        </div>







                        <div class="ftm-score-bar-wrap" style="margin:4px 0;">



                            <span style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--ftm-text-muted);">Score</span>



                            <span class="ftm-score-num" style="font-size:14px; color:#2563eb;">${m.performance_score}</span>



                            <div class="ftm-score-track">



                                <div class="ftm-score-fill" style="width:${m.performance_score}%;"></div>



                            </div>



                        </div>







                        <div class="ftm-lb-mc-stats">



                            <div>



                                <span class="ftm-metric-lbl" style="display:block; font-size:10px; color:var(--ftm-text-muted);">DELIVERED</span>



                                <strong style="font-size:12.5px;">${formatUsd(m.net_delivered_revenue)}</strong>



                            </div>



                            <div>



                                <span class="ftm-metric-lbl" style="display:block; font-size:10px; color:var(--ftm-text-muted);">COMPLETED</span>



                                <strong style="font-size:12.5px;">${m.completed_orders} orders</strong>



                            </div>



                            <div>



                                <span class="ftm-metric-lbl" style="display:block; font-size:10px; color:var(--ftm-text-muted);">ON-TIME</span>



                                <strong style="font-size:12.5px; color:${m.on_time_rate >= 80 ? '#10b981' : (m.on_time_rate >= 50 ? '#f59e0b' : '#ef4444')};">${m.on_time_rate}%</strong>



                            </div>



                            <div>



                                <span class="ftm-metric-lbl" style="display:block; font-size:10px; color:var(--ftm-text-muted);">RATING</span>



                                ${ratingMobHtml}



                            </div>



                        </div>



                    </div>



                `;



            });







            tbody.html(tableHtml);



            mobileCardsWrap.html(mobHtml);



        },



        filterLeaderboardData() {



            if (!this.state.leaderboardData || !this.state.leaderboardData.rankings) return;







            const query = (this.state.leaderboardSearch || '').toLowerCase().trim();



            const activeTeam = this.state.leaderboardTeam || 'all';







            const filteredRankings = this.state.leaderboardData.rankings.filter(m => {



                const matchesTeam = (activeTeam === 'all' || (m.team_name && m.team_name.toLowerCase() === activeTeam.toLowerCase()));



                if (!matchesTeam) return false;







                if (!query) return true;



                const name = (m.member_name || '').toLowerCase();



                const cid = (m.member_custom_id || '').toLowerCase();



                const team = (m.team_name || '').toLowerCase();



                const role = (m.role_label || '').toLowerCase();



                return name.includes(query) || cid.includes(query) || team.includes(query) || role.includes(query);



            });







            const filteredData = Object.assign({}, this.state.leaderboardData, {



                rankings: filteredRankings



            });







            this.renderLeaderboard(filteredData);



        },







        loadMemberProfile(memberId) {
            const self = this;
            let storedId = null;
            try { storedId = localStorage.getItem('ftm_selected_member_profile_id'); } catch(e) {}
            const mid = parseInt(memberId, 10) || (storedId ? parseInt(storedId, 10) : null) || (window.ftmData && window.ftmData.current_user && !window.ftmData.current_user.is_admin ? window.ftmData.current_user.id : 12);
            this.state.selectedMemberProfileId = mid;
            try { localStorage.setItem('ftm_selected_member_profile_id', mid); } catch(e) {}
            if (!window.location.hash.includes('id=' + mid)) {
                window.location.hash = '#member-profile?id=' + mid;
            }







            // Reset tab state to Overview



            $('.ftm-refa-tab').removeClass('active');



            $('.ftm-refa-tab[data-tab="overview"]').addClass('active');



            $('#ftm-profile-team-insights-section').addClass('hidden');



            $('#ftm-profile-projects-section').addClass('hidden');



            $('.ftm-refa-two-column-layout').show();







            this.api(`team-members/${mid}/profile`).then(res => {



                $('#ftm-profile-bc-name').text(res.name);



                $('#ftm-profile-hero-avatar').attr('src', res.avatar);



                $('#ftm-profile-hero-name').text(res.name);



                $('#ftm-profile-hero-id').text('#' + (res.custom_id || mid));



                $('#ftm-profile-hero-status').text(res.status || 'Active');



                $('#ftm-profile-hero-bio').text(res.bio);



                const isMgmtProfile = (res.role === 'admin' || res.role === 'deputy' || res.is_admin);
                const profRole = isMgmtProfile ? (res.professional_title || (res.role === 'deputy' ? 'Deputy Manager' : 'Super Admin & Operations Executive')) : 'WordPress Developer';
                $('#ftm-profile-hero-role-text').text(profRole);
                if (res.leadership_label) {
                    $('#ftm-profile-hero-leader-badge').html(`<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="margin-left:4px;">${res.leadership_label}</span>`);
                } else {
                    $('#ftm-profile-hero-leader-badge').empty();
                }
                const teamLabel = (typeof ftmIsMgmt === 'function' && ftmIsMgmt(res.team)) ? 'Executive Management' : `${res.team || 'WpCraft'} Team`;
                $('#ftm-profile-hero-team-text').text(teamLabel);

                $('#ftm-profile-meta-joined').text(res.joined_date || '12 Jan 2024');
                $('#ftm-profile-meta-role').text(res.leadership_label ? `${profRole} (${res.leadership_label})` : profRole);
                $('#ftm-profile-meta-team').text((typeof ftmIsMgmt === 'function' && ftmIsMgmt(res.team)) ? 'Executive Management' : (res.team || 'WpCraft'));
                $('#ftm-profile-meta-status').html(`<span class="ftm-refa-online-dot"></span> ${res.status || 'Active'}`);
                $('#ftm-profile-meta-email').text(res.email);

                // Permission-based Edit Button
                if (res.can_edit) {
                    $('#ftm-profile-btn-edit').show().off('click').on('click', function() {
                        self.openMemberProfileEdit(res.id || mid, res);
                    });
                } else {
                    $('#ftm-profile-btn-edit').hide();
                }

                // View Assigned Projects Button
                $('#ftm-profile-btn-view-projects').off('click').on('click', function() {
                    $('.ftm-refa-tab[data-tab="projects"]').click();
                });

                // Wire helper actions
                $('#ftm-btn-add-milestone, #ftm-profile-btn-add-achievement').off('click').on('click', function() {
                    $('#ftm-milestone-user-id').val(mid);
                    $('#ftm-milestone-title').val('');
                    const todayStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    $('#ftm-milestone-date').val(todayStr);
                    $('#ftm-milestone-desc').val('');
                    $('#ftm-modal-add-milestone').removeClass('hidden');
                });
                $('#ftm-profile-btn-view-all-activity').off('click').on('click', function() {
                    $('.ftm-refa-tab[data-tab="activity"]').click();
                });
                $('#ftm-profile-btn-edit-skills').off('click').on('click', function() {
                    if (res.can_edit) {
                        self.openMemberProfileEdit(res.id || mid, res);
                    }
                });

                // Tech tags with authentic vector SVG icons matching Reference 1
                const tagsHtml = (res.skills || []).slice(0, 4).map(s => {
                    const m = self.getSkillBadgeMeta(s);
                    return `<span class="ftm-refa-skill-pill" style="background:${m.bg}; color:${m.color}; border-color:${m.border};">${m.icon}<span>${s}</span></span>`;
                }).join('') + (res.skills && res.skills.length > 4 ? `<span class="ftm-refa-skill-pill ftm-refa-skill-pill-more">+${res.skills.length - 4}</span>` : '');
                $('#ftm-profile-hero-tags').html(tagsHtml);







                // Top 5 Metric Cards



                const st = res.stats || {};



                const totalProjects = (st.total_projects !== undefined && st.total_projects !== null) ? parseInt(st.total_projects, 10) : 0;



                const completedProjects = (st.completed !== undefined && st.completed !== null) ? parseInt(st.completed, 10) : 0;



                const inProgressProjects = (st.in_progress !== undefined && st.in_progress !== null) ? parseInt(st.in_progress, 10) : 0;



                const totalNetValue = parseFloat(st.total_net_value || 0);



                const rating = parseFloat(st.rating || 0);



                const reviewsCount = parseInt(st.reviews_count || 0, 10);







                $('#ftm-profile-kpi-total').text(totalProjects);



                $('#ftm-profile-kpi-completed').text(completedProjects);



                $('#ftm-profile-kpi-inprogress').text(inProgressProjects);



                $('#ftm-profile-kpi-value').text('$' + totalNetValue.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}));



                $('#ftm-profile-kpi-rating').text(rating > 0 ? `⭐ ${rating.toFixed(1)}` : '⭐ 5.0');



                $('#ftm-profile-kpi-reviews').text(`${reviewsCount} ${reviewsCount === 1 ? 'review' : 'reviews'}`);







                const completionRate = totalProjects > 0 ? Math.round((completedProjects / totalProjects) * 100) : 100;



                $('#ftm-profile-kpi-completed-sub').text(`${completionRate}% completion rate`);



                $('#ftm-profile-kpi-inprogress-sub').text(inProgressProjects > 0 ? `${inProgressProjects} active right now` : 'No active projects');







                // 1. Render SVG Donut Chart



                self.renderProfileDonutChart(res.donut_breakdown || [], totalProjects);







                // 2. Render Professional Journey



                self.renderProfileJourney(res.journey || []);







                // 3. Render Skills Cloud



                self.renderProfileSkills(res.skills || []);







                // 4. Render Achievements



                self.renderProfileAchievements(res.achievements || []);







                // 5. Render Monthly Activity Bar Chart



                self.renderProfileActivityBarChart(res.monthly_activity || []);







                // 6. Render Recent Work Activity Feed



                self.renderProfileRecentFeed(res.recent_activity || []);
                (function() {
                    const box = $('#ftm-profile-managed-scope');
                    const sc = res.managed_scope || [];
                    if (!sc.length) { box.addClass('hidden').empty(); return; }
                    box.removeClass('hidden').html('<div class="ftm-ms-title">Teams managed - totals above count only these months</div><ul>'
                        + sc.map(x => '<li><b>' + ftmEsc(x.team) + '</b><span>' + ftmEsc(x.from) + ' &rarr; ' + ftmEsc(x.to) + '</span>' + (x.note ? '<em>' + ftmEsc(x.note) + '</em>' : '') + '</li>').join('') + '</ul>');
                })();







                // 7. Wire Tab Switching



                $('.ftm-refa-tab').off('click').on('click', function() {
                    $('.ftm-refa-tab').removeClass('active');
                    $(this).addClass('active');
                    if (this.scrollIntoView) {
                        this.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    }



                    const tabKey = $(this).data('tab');



                    if (tabKey === 'overview') {



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('#ftm-profile-projects-section').addClass('hidden');



                        $('.ftm-refa-two-column-layout').show();



                    } else if (tabKey === 'projects') {



                        $('.ftm-refa-two-column-layout').hide();



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('#ftm-profile-projects-section').removeClass('hidden');



                        self.renderProfileProjects(res.projects || [], res.name);



                    } else if (tabKey === 'journey') {



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('#ftm-profile-projects-section').addClass('hidden');



                        $('.ftm-refa-two-column-layout').show();



                        $('html, body').animate({ scrollTop: $('#ftm-profile-journey-timeline').offset().top - 100 }, 300);



                    } else if (tabKey === 'activity') {



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('#ftm-profile-projects-section').addClass('hidden');



                        $('.ftm-refa-two-column-layout').show();



                        $('html, body').animate({ scrollTop: $('#ftm-profile-activity-chart').offset().top - 100 }, 300);



                    } else if (tabKey === 'skills') {



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('#ftm-profile-projects-section').addClass('hidden');



                        $('.ftm-refa-two-column-layout').show();



                        $('html, body').animate({ scrollTop: $('#ftm-profile-skills-cloud').offset().top - 100 }, 300);



                    } else if (tabKey === 'achievements') {



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('#ftm-profile-projects-section').addClass('hidden');



                        $('.ftm-refa-two-column-layout').show();



                        $('html, body').animate({ scrollTop: $('#ftm-profile-achievements-list').offset().top - 100 }, 300);



                    } else if (tabKey === 'team-insights') {



                        $('.ftm-refa-two-column-layout').hide();



                        $('#ftm-profile-projects-section').addClass('hidden');



                        $('#ftm-profile-team-insights-section').removeClass('hidden');



                        self.loadTeamInsights(res.team || 'WpCraft', res.name, parseFloat(st.total_net_value || 0));



                    }



                });



            }).catch(err => {



                console.error(err);



                self.toast('Error loading member profile: ' + (err.message || 'Unknown error'), 'error');



            });



        },







        renderProfileProjects(projects, memberName) {



            const container = $('#ftm-profile-projects-container');



            const titleEl = $('#ftm-profile-projects-title');



            const badgeEl = $('#ftm-profile-projects-badge');



            const count = projects ? projects.length : 0;



            



            titleEl.text(`${memberName}'s Assigned Projects`);



            badgeEl.html(`<span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">${count} Projects</span>`);







            if (!projects || projects.length === 0) {



                container.html(`



                    <div style="text-align:center; padding:48px 24px; border:1px dashed var(--ftm-border); border-radius:12px; background:var(--ftm-bg-subtle, #f8fafc); margin-top:8px;">



                        <div style="width:54px; height:54px; border-radius:50%; background:rgba(37,99,235,0.08); display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px; color:var(--ftm-primary);">



                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>



                        </div>



                        <h4 style="margin:0 0 6px; font-weight:700; font-size:16px;">No Projects Assigned in Current Pipeline</h4>



                        <p class="ftm-text-muted ftm-text-sm" style="max-width:440px; margin:0 auto 16px;">This specialist currently has no active client deliverables assigned in October. New orders assigned to ${memberName} will automatically appear here with full delivery tracking.</p>



                    </div>



                `);



                return;



            }







            let html = `



                <div class="ftm-table-responsive" style="margin-top:12px;">



                    <table class="ftm-table">



                        <thead>



                            <tr>



                                <th>Order ID</th>



                                <th>Client</th>



                                <th>Profile</th>



                                <th>Delivery Date</th>



                                <th>Net Payout</th>



                                <th>Status</th>



                                <th style="text-align:right;">Actions</th>



                            </tr>



                        </thead>



                        <tbody>



            `;







            projects.forEach(p => {



                const statusBadgeClass = (p.status === 'delivery_accepted' || p.status === 'completed') ? 'bg-emerald-soft text-emerald' : (



                    (p.status === 'wip' || p.status === 'in_progress') ? 'bg-blue-soft text-blue' : (



                        (p.status === 'revision' || p.status === 'in_revision') ? 'bg-amber-soft text-amber' : 'bg-purple-soft text-purple'



                    )



                );



                html += `



                    <tr>



                        <td style="font-weight:700; font-family:monospace; color:var(--ftm-primary);">${ftmEsc(p.order_id)}</td>



                        <td style="font-weight:600;">${p.client_name || 'N/A'}</td>



                        <td><span class="ftm-badge ftm-badge-outline ftm-badge-sm">${p.profile_name || 'Profile'}</span></td>



                        <td>${p.delivery_date ? p.delivery_date.slice(0, 10) : 'N/A'}</td>



                        <td style="font-weight:700;" class="text-emerald">$${parseFloat(p.net_delivery_value || 0).toFixed(2)}</td>



                        <td><span class="ftm-badge ${statusBadgeClass} ftm-badge-sm" style="font-weight:700;">${p.status_label || p.status}</span></td>



                        <td style="text-align:right;">



                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-view-profile-project-btn" data-project-id="${p.id}">



                                View Details



                            </button>



                        </td>



                    </tr>



                `;



            });







            html += `



                        </tbody>



                    </table>



                </div>



            `;







            container.html(html);







            const self = this;



            container.find('.ftm-view-profile-project-btn').on('click', function() {



                const pid = $(this).data('project-id');



                if (pid) {



                    self.openProjectDetailsModal(pid);



                }



            });



        },







        renderProfileDonutChart(data, totalCount = 0) {



            const wrap = $('#ftm-profile-donut-wrap');



            const legend = $('#ftm-profile-donut-legend');



            if (!data || data.length === 0) return;







            const radius = 54;



            const strokeWidth = 18;



            const circumference = 2 * Math.PI * radius;



            let offset = 0;







            let circles = '';



            let legendHtml = '';







            data.forEach(item => {



                const strokeDasharray = `${(item.percentage / 100) * circumference} ${circumference}`;



                const strokeDashoffset = -offset;



                if (item.percentage > 0) {
                    circles += `<circle cx="75" cy="75" r="${radius}" fill="none" stroke="${item.color}" stroke-width="${strokeWidth}" stroke-dasharray="${strokeDasharray}" stroke-dashoffset="${strokeDashoffset}" stroke-linecap="${item.percentage >= 100 ? 'butt' : 'round'}" transform="rotate(-90 75 75)" />`;
                }



                offset += (item.percentage / 100) * circumference;







                legendHtml += `



                    <div class="ftm-refa-legend-item">



                        <div class="ftm-refa-legend-dot" style="background:${item.color};"></div>



                        <div class="ftm-refa-legend-text">



                            <span class="ftm-refa-legend-label">${ftmEsc(item.label)}</span>



                            <span class="ftm-refa-legend-count">(${item.count}, ${item.percentage}%)</span>



                        </div>



                    </div>



                `;



            });







            const svg = `



                <svg width="150" height="150" viewBox="0 0 150 150" class="ftm-refa-donut-svg">



                    <circle cx="75" cy="75" r="${radius}" fill="none" stroke="var(--ftm-border)" stroke-width="${strokeWidth}" />



                    ${circles}



                    <text x="75" y="70" text-anchor="middle" class="ftm-refa-donut-center-num">${totalCount}</text>



                    <text x="75" y="88" text-anchor="middle" class="ftm-refa-donut-center-sub">Projects</text>



                </svg>



            `;







            wrap.html(svg);



            legend.html(legendHtml);



        },







        renderProfileJourney(journey) {



            const container = $('#ftm-profile-journey-timeline');



            if (!journey || journey.length === 0) {



                container.html('<div class="ftm-text-muted ftm-text-sm">No milestones logged yet.</div>');



                return;



            }







            let html = '';



            journey.forEach((m, idx) => {



                const isLast = idx === journey.length - 1;



                html += `



                    <div class="ftm-refa-timeline-item">



                        <div class="ftm-refa-tl-left">



                            <span class="ftm-refa-tl-dot"></span>



                            ${!isLast ? '<span class="ftm-refa-tl-line"></span>' : ''}



                        </div>



                        <div class="ftm-refa-tl-content">



                            <div class="ftm-refa-tl-header">



                                <span class="ftm-refa-tl-title">${ftmEsc(m.title)}</span>



                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm">${m.tag || 'Milestone'}</span>



                            </div>



                            <div class="ftm-refa-tl-date">${m.date}</div>



                            <div class="ftm-refa-tl-desc">${ftmEsc(m.description)}</div>



                        </div>



                    </div>



                `;



            });



            container.html(html);



        },







        getSkillBadgeMeta(skill) {
            const k = (skill || '').toLowerCase().trim();
            // 1. WordPress (Official WP Core 64x64 vector on #21759B circle)
            if (k === 'wordpress') {
                return {
                    bg: 'rgba(33, 117, 155, 0.08)',
                    color: '#1d6487',
                    border: 'rgba(33, 117, 155, 0.25)',
                    icon: '<svg width="15" height="15" viewBox="0 0 64 64" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><circle cx="32" cy="32" r="30" fill="#21759B"/><path d="M4.548 31.999c0 10.9 6.3 20.3 15.5 24.706L6.925 20.827C5.402 24.2 4.5 28 4.5 31.999z M50.531 30.614c0-3.394-1.219-5.742-2.264-7.57c-1.391-2.263-2.695-4.177-2.695-6.439c0-2.523 1.912-4.872 4.609-4.872 c0.121 0 0.2 0 0.4 0.022C45.653 7.3 39.1 4.5 32 4.548c-9.591 0-18.027 4.921-22.936 12.4 c0.645 0 1.3 0 1.8 0.033c2.871 0 7.316-0.349 7.316-0.349c1.479-0.086 1.7 2.1 0.2 2.3 c0 0-1.487 0.174-3.142 0.261l9.997 29.735l6.008-18.017l-4.276-11.718c-1.479-0.087-2.879-0.261-2.879-0.261 c-1.48-0.087-1.306-2.349 0.174-2.262c0 0 4.5 0.3 7.2 0.349c2.87 0 7.317-0.349 7.317-0.349 c1.479-0.086 1.7 2.1 0.2 2.262c0 0-1.489 0.174-3.142 0.261l9.92 29.508l2.739-9.148 C49.628 35.7 50.5 33 50.5 30.614z M32.481 34.4l-8.237 23.934c2.46 0.7 5.1 1.1 7.8 1.1 c3.197 0 6.262-0.552 9.116-1.556c-0.072-0.118-0.141-0.243-0.196-0.379L32.481 34.4z M56.088 18.8 c0.119 0.9 0.2 1.8 0.2 2.823c0 2.785-0.521 5.916-2.088 9.832l-8.385 24.242c8.161-4.758 13.65-13.6 13.65-23.728 C59.451 27.2 58.2 22.7 56.1 18.83z" fill="#ffffff"/></svg>'
                };
            }
            // 2. Elementor (Official Brand Badge: circle + 1 vertical bar + 3 horizontal bars)
            if (k === 'elementor') {
                return {
                    bg: 'rgba(211, 12, 92, 0.08)',
                    color: '#be0a53',
                    border: 'rgba(211, 12, 92, 0.25)',
                    icon: '<svg width="15" height="15" viewBox="0 0 24 24" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><circle cx="12" cy="12" r="11" fill="#D30C5C"/><rect x="7" y="6.5" width="2.4" height="11" rx="1.2" fill="#ffffff"/><rect x="11.2" y="6.5" width="5.8" height="2.4" rx="1.2" fill="#ffffff"/><rect x="11.2" y="10.8" width="5.8" height="2.4" rx="1.2" fill="#ffffff"/><rect x="11.2" y="15.1" width="5.8" height="2.4" rx="1.2" fill="#ffffff"/></svg>'
                };
            }
            // 3. Custom Themes
            if (k.includes('custom theme') || k.includes('theme architecture')) {
                return {
                    bg: 'rgba(2, 132, 199, 0.08)',
                    color: '#0369a1',
                    border: 'rgba(2, 132, 199, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><circle cx="13.5" cy="6.5" r=".5" fill="#0284c7"></circle><circle cx="17.5" cy="10.5" r=".5" fill="#0284c7"></circle><circle cx="8.5" cy="7.5" r=".5" fill="#0284c7"></circle><circle cx="6.5" cy="12.5" r=".5" fill="#0284c7"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.9 0 1.6-.7 1.6-1.6 0-.4-.2-.8-.5-1.1-.3-.3-.4-.7-.4-1.1 0-.9.7-1.6 1.6-1.6H16c3.3 0 6-2.7 6-6 0-5.5-4.5-10-10-10z"></path></svg>'
                };
            }
            // 4. Plugin Development
            if (k.includes('plugin')) {
                return {
                    bg: 'rgba(124, 58, 237, 0.08)',
                    color: '#6d28d9',
                    border: 'rgba(124, 58, 237, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><path d="M19.439 7.85c0-1.57-1.28-2.85-2.85-2.85h-2.14a2.85 2.85 0 0 1-5.7 0H6.61C5.04 5 3.76 6.28 3.76 7.85v2.14a2.85 2.85 0 0 0 0 5.7v2.14c0 1.57 1.28 2.85 2.85 2.85h2.14a2.85 2.85 0 0 0 5.7 0h2.14c1.57 0 2.85-1.28 2.85-2.85v-2.14a2.85 2.85 0 0 1 0-5.7V7.85z"></path></svg>'
                };
            }
            // 5. Full Stack
            if (k.includes('full stack')) {
                return {
                    bg: 'rgba(5, 150, 105, 0.08)',
                    color: '#047857',
                    border: 'rgba(5, 150, 105, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>'
                };
            }
            // 6. WooCommerce / E-Commerce
            if (k.includes('woocommerce') || k.includes('ecommerce') || k.includes('e-commerce')) {
                return {
                    bg: 'rgba(150, 88, 138, 0.08)',
                    color: '#7e4673',
                    border: 'rgba(150, 88, 138, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#96588a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>'
                };
            }
            // 7. PHP
            if (k === 'php') {
                return {
                    bg: 'rgba(79, 91, 147, 0.08)',
                    color: '#4f5b93',
                    border: 'rgba(79, 91, 147, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f5b93" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>'
                };
            }
            // 8. Web Design
            if (k.includes('web design') || k.includes('ui/ux') || k.includes('figma')) {
                return {
                    bg: 'rgba(217, 119, 6, 0.08)',
                    color: '#b45309',
                    border: 'rgba(217, 119, 6, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>'
                };
            }
            // 9. Responsive Layouts
            if (k.includes('responsive')) {
                return {
                    bg: 'rgba(37, 99, 235, 0.08)',
                    color: '#2563eb',
                    border: 'rgba(37, 99, 235, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><rect x="2" y="3" width="20" height="14" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>'
                };
            }
            // 10. React
            if (k === 'react') {
                return {
                    bg: 'rgba(2, 132, 199, 0.08)',
                    color: '#0369a1',
                    border: 'rgba(2, 132, 199, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><ellipse cx="12" cy="12" rx="10" ry="4.2"></ellipse><ellipse cx="12" cy="12" rx="10" ry="4.2" transform="rotate(60 12 12)"></ellipse><ellipse cx="12" cy="12" rx="10" ry="4.2" transform="rotate(120 12 12)"></ellipse><circle cx="12" cy="12" r="1.8" fill="#0284c7"></circle></svg>'
                };
            }
            // 11. REST API
            if (k.includes('api') || k.includes('rest')) {
                return {
                    bg: 'rgba(99, 102, 241, 0.08)',
                    color: '#6366f1',
                    border: 'rgba(99, 102, 241, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>'
                };
            }
            // 12. HTML / CSS
            if (k.includes('html') || k.includes('css')) {
                return {
                    bg: 'rgba(234, 88, 12, 0.08)',
                    color: '#ea580c',
                    border: 'rgba(234, 88, 12, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><path d="M4 3h16l-1.5 17-6.5 2-6.5-2L4 3z"></path></svg>'
                };
            }
            // 13. Executive / Management / Operations
            if (k.includes('management') || k.includes('executive') || k.includes('operation')) {
                return {
                    bg: 'rgba(30, 64, 175, 0.08)',
                    color: '#1e40af',
                    border: 'rgba(30, 64, 175, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>'
                };
            }
            // 14. Quality Assurance
            if (k.includes('quality') || k.includes('qa')) {
                return {
                    bg: 'rgba(16, 185, 129, 0.08)',
                    color: '#047857',
                    border: 'rgba(16, 185, 129, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>'
                };
            }
            // 15. Team Leadership
            if (k.includes('leader') || k.includes('team')) {
                return {
                    bg: 'rgba(139, 92, 246, 0.08)',
                    color: '#6d28d9',
                    border: 'rgba(139, 92, 246, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'
                };
            }
            // 16. Resource Allocation / Planning
            if (k.includes('resource') || k.includes('allocation') || k.includes('planning')) {
                return {
                    bg: 'rgba(217, 119, 6, 0.08)',
                    color: '#b45309',
                    border: 'rgba(217, 119, 6, 0.25)',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>'
                };
            }
            // Table-driven icons for common skills (lucide-style strokes, colour follows the pill)
            const ic = (d) => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;">' + d + '</svg>';
            const SKILL_TABLE = [
                [/javascript|\bjs\b|jquery|node|typescript/, '#a16207', ic('<path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/>')],
                [/mysql|database|sql|\bdb\b/, '#0e7490', ic('<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>')],
                [/seo|marketing|analytics/, '#15803d', ic('<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>')],
                [/speed|performance|optimi|cache/, '#c2410c', ic('<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>')],
                [/security|secure|hardening/, '#be123c', ic('<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3z"/><path d="m9 12 2 2 4-4"/>')],
                [/shopify|stripe|payment|checkout/, '#4d7c0f', ic('<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>')],
                [/migrat|deploy|hosting|server|devops/, '#475569', ic('<rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 6h.01M6 18h.01"/>')],
                [/maintenance|support|debug|bug|fix/, '#b45309', ic('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/>')],
                [/divi|gutenberg|acf|bakery|block|page ?builder/, '#7c3aed', ic('<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>')],
                [/bootstrap|tailwind|sass|scss|style/, '#0369a1', ic('<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.9 0 1.7-.7 1.7-1.6 0-.4-.2-.8-.5-1.1-.3-.3-.4-.7-.4-1.1 0-.9.7-1.7 1.7-1.7H16c3.3 0 6-2.7 6-6 0-4.4-4.5-8-10-8z"/>')],
                [/git|version control|github/, '#c2410c', ic('<circle cx="12" cy="12" r="3"/><path d="M12 3v6M12 15v6"/>')],
                [/communicat|client|support|customer/, '#0f766e', ic('<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>')],
                [/python|laravel|java|\.net|ruby/, '#1d4ed8', ic('<polyline points="4 17 10 11 4 5"/><line x1="12" x2="20" y1="19" y2="19"/>')]
            ];
            for (const [re, col, icon] of SKILL_TABLE) {
                if (re.test(k)) {
                    return { bg: 'rgba(100, 116, 139, 0.08)', color: col, border: 'rgba(100, 116, 139, 0.25)', icon: icon };
                }
            }
            // Fallback for any other skill
            return {
                bg: 'rgba(71, 85, 105, 0.06)',
                color: '#475569',
                border: 'rgba(203, 213, 225, 0.8)',
                icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:5px; flex-shrink:0;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'
            };
        },

        renderProfileSkills(skills) {
            const container = $('#ftm-profile-skills-cloud');
            if (!skills || skills.length === 0) {
                container.html('<div class="ftm-text-muted ftm-text-sm">No skills added yet.</div>');
                return;
            }

            let html = '';
            skills.forEach((s) => {
                const m = this.getSkillBadgeMeta(s);
                html += `<span class="ftm-refa-skill-pill" style="background:${m.bg}; color:${m.color}; border-color:${m.border};">${m.icon}<span>${s}</span></span>`;
            });
            container.html(html);
        },







        renderProfileAchievements(achievements) {



            const container = $('#ftm-profile-achievements-list');



            if (!achievements || achievements.length === 0) {



                container.html(`



                    <div style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">



                        <div style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:10px; background:var(--ftm-bg-subtle, rgba(0,0,0,0.04)); margin-bottom:10px;">



                            <svg class="ftm-icon" style="width:20px; height:20px; opacity:0.6;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>



                        </div>



                        <div class="ftm-font-medium ftm-text-sm" style="color:var(--ftm-text-primary);">No milestone achievements unlocked yet.</div>



                        <div class="ftm-text-xs" style="margin-top:4px; opacity:0.8;">Completed project deliveries and 5-star ratings will unlock achievements here.</div>



                    </div>



                `);



                return;



            }







            let html = '';



            achievements.forEach(a => {



                html += `



                    <div class="ftm-refa-achievement-item">



                        <div class="ftm-refa-ach-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg></div>



                        <div class="ftm-refa-ach-details">



                            <div class="ftm-refa-ach-title">${ftmEsc(a.title)}</div>



                            <div class="ftm-refa-ach-date">${a.date}</div>



                            <div class="ftm-refa-ach-desc">${ftmEsc(a.description)}</div>



                        </div>



                    </div>



                `;



            });



            container.html(html);



        },







        renderProfileActivityBarChart(activity) {



            const container = $('#ftm-profile-activity-chart');



            if (!activity || activity.length === 0) {



                container.html('<div class="ftm-text-muted ftm-text-sm">No recent activity recorded.</div>');



                return;



            }







            const counts = activity.map(a => parseInt(a.count, 10) || 0);
            const peakCount = Math.max(...counts, 0);
            const maxCount = peakCount > 0 ? Math.ceil(peakCount * 1.15) : 10;



            let html = '<div class="ftm-refa-barchart-bars">';







            activity.forEach(a => {



                const heightPercent = maxCount > 0 ? Math.round((a.count / maxCount) * 100) : 0;



                html += `



                    <div class="ftm-refa-bar-col">



                        <div class="ftm-refa-bar-val">${a.count}</div>



                        <div class="ftm-refa-bar-track">



                            <div class="ftm-refa-bar-fill" style="height:${heightPercent}%;" title="${a.month}: ${a.count} projects"></div>



                        </div>



                        <div class="ftm-refa-bar-label">${a.month}</div>



                    </div>



                `;



            });



            html += '</div>';



            container.html(html);



        },







        renderProfileRecentFeed(feed) {



            const container = $('#ftm-profile-recent-feed');



            if (!feed || feed.length === 0) {



                container.html(`



                    <div style="text-align:center; padding:32px 16px; color:var(--ftm-text-muted);">



                        <div style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:10px; background:var(--ftm-bg-subtle, rgba(0,0,0,0.04)); margin-bottom:10px;">



                            <svg class="ftm-icon" style="width:20px; height:20px; opacity:0.6;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>



                        </div>



                        <div class="ftm-font-medium ftm-text-sm" style="color:var(--ftm-text-primary);">No recent work activity logged yet.</div>



                        <div class="ftm-text-xs" style="margin-top:4px; opacity:0.8;">Daily standup submissions and project updates will appear here automatically.</div>



                    </div>



                `);



                return;



            }







            let html = '';
            feed.forEach(item => {
                const colorClass = item.color === 'emerald' ? 'bg-emerald-soft text-emerald' : (item.color === 'purple' ? 'bg-purple-soft text-purple' : (item.color === 'amber' ? 'bg-amber-soft text-amber' : 'bg-blue-soft text-blue'));
                const iconColorStyle = item.color === 'emerald' ? 'background:#ecfdf5; color:#059669;' : (item.color === 'purple' ? 'background:#f5f3ff; color:#7c3aed;' : (item.color === 'amber' ? 'background:#fffbeb; color:#d97706;' : 'background:#eff6ff; color:#2563eb;'));

                html += `
                    <div class="ftm-refa-feed-item">
                        <div class="ftm-refa-feed-left-icon" style="${iconColorStyle}">
                            ${ftmFeedIcon(item.tag)}
                        </div>
                        <div class="ftm-refa-feed-content">
                            <div class="ftm-refa-feed-top">
                                <div class="ftm-refa-feed-title-wrap">
                                    <span class="ftm-refa-feed-title">${ftmEsc(item.title)}</span>
                                    <span class="ftm-refa-feed-date-inline">• ${item.date}</span>
                                </div>
                                <span class="ftm-badge ${colorClass} ftm-badge-sm">${item.tag || 'Project'}</span>
                            </div>
                            <div class="ftm-refa-feed-desc">${ftmEsc(item.description)}</div>
                        </div>
                    </div>
                `;
            });
            container.html(html);



        },







        loadManagerOverview() {



            const self = this;



            this.api('manager/overview').then(res => {



                const st = res.stats || {};



                $('#ftm-manager-kpi-volume').text('$' + parseFloat(st.total_agency_volume !== undefined ? st.total_agency_volume : 151663.24).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));



                const nTeams = st.active_production_teams !== undefined ? st.active_production_teams : 3;
                $('#ftm-manager-kpi-teams').text(`${nTeams} ${nTeams === 1 ? 'Team' : 'Teams'}`);
                const teamNames = (res.teams || []).filter(t => t.current !== false).map(t => String(t.team)).filter(Boolean);
                if (teamNames.length) { $('#ftm-manager-kpi-teams-sub').text(teamNames.join(', ')); }



                $('#ftm-manager-kpi-projects').text(`${st.live_managed_projects !== undefined ? st.live_managed_projects : 24} Deliverables`);



                $('#ftm-manager-kpi-compliance').text(st.compliance_rate || '-');
                $('#ftm-manager-meta-teams').text((st.active_production_teams !== undefined ? st.active_production_teams : 3) + ' Production Units');
                $('#ftm-manager-meta-members').text((st.total_members !== undefined ? st.total_members : 14) + ' Specialists');







                const tbody = $('#ftm-manager-teams-tbody');
                const mobileCards = $('#ftm-manager-teams-mobile-cards');
                const teams = res.teams || [];
                let html = '';
                let mobileHtml = '';

                teams.forEach(t => {
                    const statusClass = (t.status || '').toLowerCase() === 'optimal' 
                        ? 'bg-emerald-soft text-emerald' 
                        : ((t.status || '').toLowerCase() === 'strong' ? 'bg-blue-soft text-blue' : 'bg-slate-soft text-slate');
                    
                    html += `
                        <tr>
                            <td><strong class="ftm-font-semibold">${t.team}</strong>${t.periods ? `<div class="ftm-matrix-periods">${ftmEsc(t.periods)}</div>` : ''}</td>
                            <td>${t.leader}</td>
                            <td>${t.members_count} Members</td>
                            <td><strong class="text-emerald">$${parseFloat(t.total_volume).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                            <td><span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">${t.active_projects} Projects</span></td>
                            <td><span class="text-emerald ftm-font-semibold">${t.on_time_rate}</span></td>
                            <td><span class="ftm-badge ${statusClass} ftm-badge-sm">${t.status}</span></td>
                        </tr>
                    `;

                    mobileHtml += `
                        <div class="ftm-matrix-mobile-card">
                            <div class="ftm-matrix-card-head">
                                <div class="ftm-matrix-team-info">
                                    <div class="ftm-matrix-team-title">${t.team}</div>${t.periods ? `<div class="ftm-matrix-periods">${ftmEsc(t.periods)}</div>` : ''}
                                    <div class="ftm-matrix-team-lead">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px; margin-right:4px;"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <span>${t.leader}</span>
                                    </div>
                                </div>
                                <span class="ftm-badge ${statusClass} ftm-badge-sm">${t.status}</span>
                            </div>
                            <div class="ftm-matrix-card-grid">
                                <div class="ftm-matrix-metric-box">
                                    <span class="ftm-matrix-metric-lbl">2026 Delivery Volume</span>
                                    <span class="ftm-matrix-metric-val text-emerald">$${parseFloat(t.total_volume).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                </div>
                                <div class="ftm-matrix-metric-box">
                                    <span class="ftm-matrix-metric-lbl">Active Pipeline</span>
                                    <span class="ftm-matrix-metric-val text-blue">${t.active_projects} Projects</span>
                                </div>
                                <div class="ftm-matrix-metric-box">
                                    <span class="ftm-matrix-metric-lbl">Team Size</span>
                                    <span class="ftm-matrix-metric-val">${t.members_count} Members</span>
                                </div>
                                <div class="ftm-matrix-metric-box">
                                    <span class="ftm-matrix-metric-lbl">On-Time Rate</span>
                                    <span class="ftm-matrix-metric-val text-emerald">${t.on_time_rate}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                tbody.html(html);
                if (mobileCards.length) {
                    mobileCards.html(mobileHtml);
                }



            }).catch(err => {



                console.error(err);



                self.toast('Error loading executive overview: ' + (err.message || 'Unknown error'), 'error');



            });



        },







        openMemberProfileEdit(mid, preloadedData) {



            const self = this;



            const targetId = mid || (window.ftmData && window.ftmData.current_user ? window.ftmData.current_user.id : 0);



            if (!targetId) return;







            const populateModal = (res) => {



                $('#ftm-edit-profile-user-id').val(res.id || targetId);



                $('#ftm-edit-profile-name').val(res.name || '');



                $('#ftm-edit-profile-callsign').val(res.call_sign || '');



                $('#ftm-edit-profile-title').val(res.professional_title || '');



                $('#ftm-edit-profile-joining').val(res.joined_date || '');



                $('#ftm-edit-profile-skills').val(Array.isArray(res.skills) ? res.skills.join(', ') : (res.skills || ''));



                $('#ftm-edit-profile-bio').val(res.bio || '');



                $('#ftm-edit-profile-avatar').val(res.avatar && !res.avatar.includes('gravatar') && !res.avatar.includes('ui-avatars') ? res.avatar : '');



                $('#ftm-edit-profile-email').val(res.email || '');



                $('#ftm-edit-profile-password').val('');







                // Check 1-Time Email Lock Status



                const isSuperAdminUser = (res.id == 1 || (window.ftmData && window.ftmData.current_user && window.ftmData.current_user.is_admin && res.id == window.ftmData.current_user.id));



                const isLocked = !isSuperAdminUser && Boolean(res.email_locked);



                const emailInput = $('#ftm-edit-profile-email');



                const lockBadge = $('#ftm-profile-email-badge');



                const hint = $('#ftm-profile-email-hint');







                if (isSuperAdminUser) {



                    $('#ftm-modal-edit-member-title').text('Edit Executive Profile');



                    emailInput.prop('disabled', false).prop('readonly', false).css('background', '');



                    lockBadge.addClass('hidden');



                    hint.html('<span class="text-blue" style="font-weight:600;">👑 Administrator Access:</span> You can freely update your display name, executive title, and official email address at any time.');



                } else if (isLocked) {



                    $('#ftm-modal-edit-member-title').text('Edit Professional Profile');



                    emailInput.prop('disabled', true).prop('readonly', true).css('background', 'var(--ftm-bg, #f8fafc)');



                    lockBadge.removeClass('hidden');



                    hint.html('<span style="color:#10b981; font-weight:600;">🔒 Official Email Verified:</span> Your official email has already been set and is locked. Contact Super Admin to request future modifications.');



                } else {



                    $('#ftm-modal-edit-member-title').text('Edit Professional Profile');



                    emailInput.prop('disabled', false).prop('readonly', false).css('background', '');



                    lockBadge.addClass('hidden');



                    hint.html('<span class="text-amber" style="font-weight:600;">⚠️ One-Time Self Update:</span> You can only set your official email address once. Once saved, it will be locked to prevent unauthorized changes.');



                }







                $('#ftm-modal-edit-member-profile').removeClass('hidden');



            };







            if (preloadedData && preloadedData.name) {



                populateModal(preloadedData);



            } else {



                this.api(`team-members/${targetId}/profile`).then(res => {



                    populateModal(res);



                }).catch(err => {



                    self.toast('Error opening profile edit: ' + (err.message || 'Unknown error'), 'error');



                });



            }



        },







        saveMemberProfileEdit() {



            const self = this;



            const saveBtn = $('#ftm-btn-save-member-profile');



            const origHtml = saveBtn.html();



            saveBtn.prop('disabled', true).html('Saving...');







            const targetUserId = $('#ftm-edit-profile-user-id').val();



            const skillsRaw = $('#ftm-edit-profile-skills').val();



            const skillsArr = skillsRaw ? skillsRaw.split(',').map(s => s.trim()).filter(Boolean) : [];







            const payload = {



                target_user_id: targetUserId ? parseInt(targetUserId) : undefined,



                email: $('#ftm-edit-profile-email').val(),



                new_password: $('#ftm-edit-profile-password').val(),



                display_name: $('#ftm-edit-profile-name').val(),



                call_sign: $('#ftm-edit-profile-callsign').val(),



                professional_title: $('#ftm-edit-profile-title').val(),



                joined_date: $('#ftm-edit-profile-joining').val(),



                skills: skillsArr,



                bio: $('#ftm-edit-profile-bio').val(),



                avatar_url: $('#ftm-edit-profile-avatar').val()



            };







            this.api('user/profile', 'POST', payload).then(res => {



                saveBtn.prop('disabled', false).html(origHtml);



                $('#ftm-modal-edit-member-profile').addClass('hidden');



                self.toast(res.message || 'Profile and official email updated successfully!', 'success');







                // Dismiss onboarding banner



                $('#ftm-banner-profile-setup').slideUp(250, function() { $(this).remove(); });



                if (window.ftmData && window.ftmData.current_user) {



                    window.ftmData.current_user.profile_setup_needed = false;



                    window.ftmData.current_user.email_locked = (res.email_locked !== undefined ? Boolean(res.email_locked) : true);



                    if (res.email) window.ftmData.current_user.email = res.email;



                    if (res.display_name) window.ftmData.current_user.name = res.display_name;



                }







                // Update real-time DOM elements



                if (res.display_name) {



                    $('#ftm-dropdown-user-name, .ftm-user-name, .ftm-sidebar-user-name, #ftm-view-admin-profile .ftm-refa-name, .ftm-dropdown-user-header .ftm-font-semibold').text(res.display_name);



                }



                if (res.email) {



                    $('#ftm-dropdown-user-email, #ftm-admin-profile-email, #ftm-profile-meta-email, #ftm-header-user-email').text(res.email);



                    $('#ftm-setting-email').val(res.email);



                }







                if (window.location.hash.includes('member-profile') || window.location.hash.includes('profile')) {



                    self.loadMemberProfile(targetUserId);



                }



            }).catch(err => {



                saveBtn.prop('disabled', false).html(origHtml);



                const msg = (err && (err.message || err.error)) || 'Unknown error';



                self.toast('Error saving profile: ' + msg, 'error');



            });



        },







        loadTeamInsights(teamName, memberName, memberVolume) {



            const self = this;



            const rosterGrid = $('#ftm-insights-teammates-grid');



            rosterGrid.html('<div class="ftm-spinner"></div> Loading team data...');







            const cleanTeam = (teamName || 'WpCraft').trim();



            const isExecutive = cleanTeam.toLowerCase().includes('executive') || cleanTeam.toLowerCase().includes('management');







            // Immediate DOM update to prevent stale data flash



            $('#ftm-insights-team-title').text(isExecutive ? 'Agency Operations & Multi-Team Insights' : `${cleanTeam} Team Insights`);



            $('#ftm-insights-team-badge').text(isExecutive ? 'Executive Unit' : 'Production Unit');



            $('#ftm-insights-team-desc').text(isExecutive ? 'Cross-team collaborative capacity, deliverable realization, and leadership overview.' : `Collaborative capacity, deliverable realization, and specialist roster for ${cleanTeam}.`);



            $('#ftm-insights-team-leader').text('Loading...');







            // Fetch Manager Overview for team metrics



            this.api('manager/overview').then(res => {



                const teams = res.teams || [];



                let team = teams.find(t => (t.team || '').trim().toLowerCase() === cleanTeam.toLowerCase()) || 



                           teams.find(t => (t.team || '').toLowerCase().includes(cleanTeam.toLowerCase()));







                if (isExecutive || !team) {



                    team = {



                        team: cleanTeam,



                        leader: (res.manager_name || 'Super Admin') + ' (Executive Operations)',



                        total_volume: res.stats ? res.stats.total_agency_volume : 151663.24,



                        active_projects: res.stats ? res.stats.live_managed_projects : 3,



                        members_count: res.stats ? res.stats.total_members : 14



                    };



                }







                if (team) {



                    $('#ftm-insights-team-title').text(isExecutive ? 'Agency Operations & Multi-Team Insights' : `${team.team} Team Insights`);



                    $('#ftm-insights-team-badge').text(isExecutive ? 'Executive Unit' : 'Production Unit');



                    $('#ftm-insights-team-desc').text(isExecutive ? 'Cross-team collaborative capacity, deliverable realization, and leadership overview.' : `Collaborative capacity, deliverable realization, and specialist roster for ${team.team}.`);



                    $('#ftm-insights-team-leader').text(team.leader.includes('(') ? team.leader : `${team.leader} (Lead)`);



                    $('#ftm-insights-kpi-volume').text('$' + parseFloat(team.total_volume || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));



                    $('#ftm-insights-kpi-active').text(`${team.active_projects || 0} Deliverables`);



                    $('#ftm-insights-kpi-members').text(`${team.members_count || 0} Members`);







                    const teamVol = parseFloat(team.total_volume || 0);



                    if (teamVol > 0 && memberVolume > 0) {



                        const pct = Math.min(100, (memberVolume / teamVol) * 100).toFixed(1);



                        $('#ftm-insights-kpi-contrib').text(`${pct}%`);



                        $('#ftm-insights-kpi-contrib-sub').text(`Delivery share in ${team.team}`);



                    } else {



                        $('#ftm-insights-kpi-contrib').text('Core Contributor');



                        $('#ftm-insights-kpi-contrib-sub').text(`Active specialist in ${team.team}`);



                    }



                }



            }).catch(console.error);







            // Fetch Team Members for Roster



            this.api('team-members?team=' + encodeURIComponent(cleanTeam)).then(members => {



                if (!members || members.length === 0) {



                    rosterGrid.html('<div class="ftm-text-muted ftm-text-sm">No team members found.</div>');



                    return;



                }







                let html = '';



                members.forEach(m => {



                    const isCurrent = (m.name || '').toLowerCase() === (memberName || '').toLowerCase();



                    html += `



                        <div class="ftm-refa-card ftm-insights-teammate-card" data-member-id="${m.id}" style="padding:14px; display:flex; align-items:center; gap:12px; cursor:pointer; transition:transform 0.15s, border-color 0.15s; ${isCurrent ? 'border-color:var(--ftm-primary); background:rgba(37,99,235,0.03);' : ''}">



                            <img src="${m.avatar}" style="width:44px; height:44px; border-radius:50%; object-fit:cover; border:1px solid var(--ftm-border);" alt="${ftmEsc(m.name)}">



                            <div style="flex:1; min-width:0;">



                                <div class="ftm-font-semibold ftm-text-sm" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">



                                    ${ftmEsc(m.name)} ${isCurrent ? '<span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Viewing</span>' : ''}



                                </div>



                                <div class="ftm-text-xs ftm-text-muted">${ftmEsc(m.role_label)} &bull; #${m.custom_id}</div>



                                <div style="margin-top:6px; display:flex; gap:6px;">



                                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">${m.active_projects || 0} Active</span>



                                    <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm">${m.delivered_projects || 0} Delivered</span>



                                </div>



                            </div>



                        </div>



                    `;



                });



                rosterGrid.html(html);







                // Clicking a teammate opens their profile



                rosterGrid.find('.ftm-insights-teammate-card').on('click', function() {



                    const mid = $(this).data('member-id');



                    if (mid) {



                        self.loadMemberProfile(mid);



                        $('.ftm-refa-tab').removeClass('active');



                        $('.ftm-refa-tab[data-tab="overview"]').addClass('active');



                        $('#ftm-profile-team-insights-section').addClass('hidden');



                        $('.ftm-refa-two-column-layout').show();



                    }



                });



            }).catch(err => {



                console.error(err);



                rosterGrid.html('<div class="text-rose ftm-text-sm">Error loading team members: ' + (err.message || 'Unknown') + '</div>');



            });



        },







        loadActiveNoticesBanner() {



            const self = this;



            this.api('notices').then(res => {



                const notices = res.notices || [];



                const badge = $('#ftm-nav-notices-badge');



                if (notices.length > 0) {



                    badge.text(notices.length).removeClass('hidden');



                } else {



                    badge.addClass('hidden');



                }







                // Show top banner for pinned or urgent notices



                const bannerContainer = $('#ftm-active-notices-banner');



                const bannerNotices = notices.filter(n => n.is_pinned || n.notice_type === 'urgent').slice(0, 2);



                if (bannerNotices.length === 0) {



                    bannerContainer.addClass('hidden').empty();



                    return;



                }







                let bannerHtml = '';



                bannerNotices.forEach(n => {



                    let icon = '📢';



                    if (n.notice_type === 'urgent') icon = '🚨';



                    else if (n.notice_type === 'important') icon = '📌';



                    else if (n.notice_type === 'kudos') icon = '🌟';







                    const shortMsg = n.message.length > 120 ? (n.message.substring(0, 120) + '...') : n.message;



                    bannerHtml += `



                        <div class="ftm-notice-banner-item ftm-notice-banner-${n.notice_type}">



                            <div class="ftm-notice-banner-text-wrap">



                                <span style="font-size:16px; flex-shrink:0;">${icon}</span>



                                <strong style="word-break:break-word;">${self.escapeHtml(n.title)}:</strong>



                                <span style="color:inherit; word-break:break-word;">${self.escapeHtml(shortMsg)}</span>



                            </div>



                            <a href="#agency-notices" class="ftm-btn ftm-btn-xs ftm-btn-ghost ftm-notice-banner-btn" style="color:inherit; font-weight:700; text-decoration:none;">



                                View Notice &rarr;



                            </a>



                        </div>



                    `;



                });







                bannerContainer.html(bannerHtml).removeClass('hidden');



            }).catch(err => {



                console.warn('Failed to load notices banner:', err);



            });



        },







        loadAgencyNotices() {



            const self = this;



            const feed = $('#ftm-notices-feed');



            feed.html('<div style="text-align:center; padding:30px; color:var(--ftm-text-secondary);"><div class="ftm-spinner" style="margin:0 auto 10px;"></div>Loading official notice board...</div>');







            this.api('notices').then(res => {



                self.state.agencyNotices = res.notices || [];



                self.state.canManageNotices = res.can_manage || self.state.isAdmin;



                self.renderAgencyNotices();



            }).catch(err => {



                feed.html(`<div style="padding:20px; color:#b91c1c; text-align:center;">Failed to load notices: ${self.escapeHtml(err.message || 'Unknown error')}</div>`);



            });



        },







        renderAgencyNotices() {



            const self = this;



            const feed = $('#ftm-notices-feed');



            const all = self.state.agencyNotices || [];



            const filter = self.state.noticeFilter || 'all';







            const filtered = all.filter(n => {



                if (filter === 'all') return true;



                return n.notice_type === filter;



            });







            if (filtered.length === 0) {



                feed.html(`



                    <div style="text-align:center; padding:48px 20px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:12px;">



                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px; color:var(--ftm-text-muted);"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>



                        <h4 style="font-size:15px; font-weight:700; margin-bottom:4px; color:var(--ftm-text-primary);">No Notices in this Category</h4>



                        <p style="font-size:13px; color:var(--ftm-text-secondary); margin:0;">There are currently no active broadcasts matching this filter.</p>



                    </div>



                `);



                return;



            }







            let html = '';



            filtered.forEach(n => {



                const isPinned = !!n.is_pinned;



                const canManage = !!self.state.canManageNotices;



                let badgeClass = 'ftm-badge-announcement';



                let icon = '📢';



                let typeLabel = 'Announcement';







                if (n.notice_type === 'urgent') {



                    badgeClass = 'ftm-badge-urgent';



                    icon = '🚨';



                    typeLabel = 'Urgent Action';



                } else if (n.notice_type === 'important') {



                    badgeClass = 'ftm-badge-important';



                    icon = '📌';



                    typeLabel = 'Important Directive';



                } else if (n.notice_type === 'kudos') {



                    badgeClass = 'ftm-badge-kudos';



                    icon = '🌟';



                    typeLabel = 'Kudos & Recognition';



                }







                html += `



                    <div class="ftm-notice-card ${isPinned ? 'pinned' : ''}" data-id="${n.id}">



                        <div class="ftm-notice-header">



                            <div class="ftm-notice-title-row">



                                <span class="ftm-badge ${badgeClass} ftm-badge-sm">${icon} ${typeLabel}</span>



                                <span class="ftm-badge bg-gray-soft text-secondary ftm-badge-sm">



                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px; vertical-align:-1px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>



                                    ${self.escapeHtml(n.target_label || 'All Members')}



                                </span>



                                ${isPinned ? `<span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">📌 Pinned</span>` : ''}



                                <h3 class="ftm-notice-title">${self.escapeHtml(n.title)}</h3>



                            </div>



                            ${canManage ? `



                            <div class="ftm-notice-actions">



                                <button type="button" class="ftm-btn ftm-btn-ghost ftm-btn-xs ftm-notice-pin-btn" data-id="${n.id}" title="${isPinned ? 'Unpin Notice' : 'Pin to Top'}">



                                    ${isPinned ? 'Unpin' : 'Pin to Top'}



                                </button>



                                <button type="button" class="ftm-btn ftm-btn-ghost ftm-btn-xs ftm-notice-delete-btn text-rose" data-id="${n.id}" title="Archive / Delete Notice">



                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>



                                </button>



                            </div>



                            ` : ''}



                        </div>







                        <div class="ftm-notice-meta">



                            <img src="${self.escapeHtml(n.author_avatar || '')}" alt="${self.escapeHtml(n.author_name || '')}" style="width:20px; height:20px; border-radius:50%; object-fit:cover;">



                            <span><strong>${self.escapeHtml(n.author_name || 'Management')}</strong></span>



                            <span>&bull;</span>



                            <span>${self.escapeHtml(n.created_at_formatted || n.created_at)}</span>



                        </div>







                        <div class="ftm-notice-message">${self.escapeHtml(n.message)}</div>



                    </div>



                `;



            });







            feed.html(html);



        },







        openBroadcastNoticeModal() {



            const self = this;



            // Populate specialists dropdown in case not already populated



            const memberSelect = $('#ftm-notice-target-member');



            memberSelect.html('<option value="">Select Specialist...</option>');



            const members = self.state.teamMembers || [];



            members.forEach(m => {



                memberSelect.append(`<option value="${m.id}">${self.escapeHtml(m.name)} (#${self.escapeHtml(m.custom_id || m.id)}) - ${self.escapeHtml(m.team || '')}</option>`);



            });







            $('#ftm-notice-title').val('');



            $('#ftm-notice-type').val('announcement');



            $('#ftm-notice-target-type').val('all');



            $('#ftm-notice-target-team').val('WpCraft');



            $('#ftm-notice-team-group').addClass('hidden');



            $('#ftm-notice-member-group').addClass('hidden');



            $('#ftm-notice-message').val('');



            $('#ftm-notice-pin').prop('checked', false);



            $('#ftm-modal-notice').removeClass('hidden');



            setTimeout(() => { $('#ftm-notice-title').focus(); }, 150);



        },







        submitBroadcastNotice(e) {



            e.preventDefault();



            const self = this;



            const targetType = $('#ftm-notice-target-type').val();



            let targetVal = 'all';



            if (targetType === 'team') {



                targetVal = $('#ftm-notice-target-team').val();



            } else if (targetType === 'member') {



                targetVal = $('#ftm-notice-target-member').val();



                if (!targetVal) {



                    alert('Please select a specialist for targeted delivery.');



                    return;



                }



            }







            const payload = {



                title: $('#ftm-notice-title').val().trim(),



                notice_type: $('#ftm-notice-type').val(),



                target_type: targetType,



                target_value: targetVal,



                message: $('#ftm-notice-message').val().trim(),



                is_pinned: $('#ftm-notice-pin').is(':checked') ? 1 : 0



            };







            const submitBtn = $('#ftm-btn-submit-notice');



            submitBtn.prop('disabled', true).text('Broadcasting...');







            this.api('notices', 'POST', payload).then(res => {



                submitBtn.prop('disabled', false).html(`



                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>



                    <span>Publish Notice</span>



                `);



                $('#ftm-modal-notice').addClass('hidden');



                self.toast('Notice broadcasted successfully to targeted members!', 'success');



                self.loadActiveNoticesBanner();



                if (self.state.currentView === 'agency-notices') {



                    self.loadAgencyNotices();



                }



            }).catch(err => {



                submitBtn.prop('disabled', false).html(`



                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>



                    <span>Publish Notice</span>



                `);



                self.toast(err.message || 'Failed to publish notice.', 'error');



            });



        },







        toggleNoticePin(id) {



            const self = this;



            this.api(`notices/${id}/pin`, 'POST').then(res => {



                self.toast(res.message || 'Pin status updated.', 'success');



                self.loadActiveNoticesBanner();



                self.loadAgencyNotices();



            }).catch(err => {



                self.toast(err.message || 'Failed to toggle pin.', 'error');



            });



        },







        deleteNotice(id) {



            const self = this;



            if (!confirm('Are you sure you want to remove this notice? It will be archived immediately.')) {



                return;



            }



            this.api(`notices/${id}`, 'DELETE').then(res => {



                self.toast('Notice successfully archived.', 'success');



                self.loadActiveNoticesBanner();



                self.loadAgencyNotices();



            }).catch(err => {



                self.toast(err.message || 'Failed to delete notice.', 'error');



            });



        },







        toast(message, type = 'info') {



            const container = $('#ftm-toast-container');



            const toast = $(`<div class="ftm-toast ftm-toast-${type}"><span>${message}</span></div>`);



            container.append(toast);



            setTimeout(() => {



                toast.fadeOut(300, function() { $(this).remove(); });



            }, 3500);



        }



    };







    // Full text tooltip for single-line labels that get truncated on narrower desktops
    $(document).on('mouseenter', '.ftm-refa-kpi-label, .ftm-refa-kpi-sub, .ftm-monthly-kpi-lbl, .ftm-refa-meta-label, .ftm-refb-kpi-title, .ftm-refb-kpi-sub', function() {
        if (this.scrollWidth > this.clientWidth && !this.title) this.title = this.textContent.trim();
    });
        $(document).on('change', '#ftm-form-combined-toggle', function() {
        $('#ftm-combined-fields').toggleClass('hidden', !this.checked);
        if (this.checked && !$('#ftm-form-splits .ftm-split-row').length) $('#ftm-form-splits').append(ftmCombined.row());
        ftmCombined.summary();
    });
    $(document).on('click', '#ftm-form-split-add', function() { $('#ftm-form-splits').append(ftmCombined.row()); ftmCombined.summary(); });
    $(document).on('click', '.ftm-split-remove', function() { $(this).closest('.ftm-split-row').remove(); ftmCombined.summary(); });
    $(document).on('input change', '#ftm-form-order-total, #ftm-form-gross-value, #ftm-form-fee-percent, .ftm-split-amount', function() { setTimeout(() => ftmCombined.summary(), 0); });
        if ($('#ftm-app').data('view-only') === 1 || $('#ftm-app').data('view-only') === '1') {
        $('#ftm-app').addClass('ftm-view-only');
        $(document).on('click', '.ftm-view-only-block', function(e) { e.preventDefault(); e.stopImmediatePropagation(); });
        // Table status dropdowns and any form field loaded later stay read-only
        const lockControls = () => { $('.ftm-view-only .ftm-table-status-changer').prop('disabled', true); };
        new MutationObserver(lockControls).observe(document.body, { childList: true, subtree: true });
        lockControls();
    }
        $(document).on('change', '#ftm-form-order-type', function() { ftmSub.toggle(); });
        $(document).on('change', '#ftm-form-assignees-list input[type="checkbox"]', function() { setTimeout(() => ftmShare.render(), 0); });
    $(document).on('click', '#ftm-form-assignees-list .ftm-assignee-checkbox-item, #ftm-form-assignees-list button', function() { setTimeout(() => ftmShare.render(), 30); });
    $(document).on('input', '#ftm-split-panel input[data-uid]', function() { ftmShare.summary(); });
    $(document).on('input change', '#ftm-form-gross-value, #ftm-form-fee-percent', function() { setTimeout(() => ftmShare.summary(), 0); });
    $(document).on('click', '#ftm-sp-equal', function() { $('#ftm-split-panel input[data-uid]').val(''); ftmShare.saved = {}; const n = $('#ftm-split-panel input[data-uid]').length; $('#ftm-split-panel input[data-uid]').val(Math.round(10000 / n) / 100); ftmShare.summary(); });
        $(document).on('click', '#ftm-details-edit-split-btn', function() {
        const id = window.App.state.activeProjectId;
        if (!id) return;
        window.App.api('projects/' + id).then(r => {
            const members = (r && r.project && r.project.assigned_members) || [];
            if (members.length < 2) return;
            window.App.askValueSplit(members, r.assignee_shares || {}, parseFloat(r.project.net_delivery_value) || 0, 'Edit the value split').then(sp => {
                if (sp === null) return;
                window.App.api('projects/' + id + '/split', 'POST', { assignee_splits: sp }).then(() => {
                    window.App.toast('Value split updated.', 'success');
                    window.App.openProjectDetailsModal(id);
                }).catch(e => window.App.toast((e && e.message) || 'Could not update the split.', 'error'));
            });
        });
    });
    $(document).off('click', '#ftm-details-tip-save').on('click', '#ftm-details-tip-save', function() {
        const id = window.App.state.activeProjectId;
        if (!id) return;
        const $btn = $(this);
        const v = parseFloat($('#ftm-details-tip-input').val());
        const tip = isNaN(v) ? 0 : Math.max(0, v);
        $btn.prop('disabled', true).text('Saving...');
        window.App.api('projects/' + id + '/tip', 'POST', { tip_amount: tip }).then(() => {
            $btn.prop('disabled', false).text('Save tip');
            window.App.toast('Client tip saved.', 'success');
            window.App.openProjectDetailsModal(id);
            window.App.loadProjects();
            window.App.loadStats();
        }).catch(e => {
            $btn.prop('disabled', false).text('Save tip');
            window.App.toast((e && e.message) || 'Could not save the tip.', 'error');
        });
    });
        $(document).on('input change', '.ftm-rate-num', function() { ftmRateSync($(this).closest('[data-ftm-rate]'), $(this).val()); });
    $(document).on('input', '.ftm-rate-range', function() { const $w = $(this).closest('[data-ftm-rate]'); const v = parseFloat($(this).val()).toFixed(1); $w.find('.ftm-rate-num').val(v); ftmRateSync($w, v); });
    $(document).on('blur', '.ftm-rate-num', function() { const v = ftmRating(this).toFixed(1); $(this).val(v); ftmRateSync($(this).closest('[data-ftm-rate]'), v); });
    $(document).on('click', '.ftm-rate-chips button', function() { const $w = $(this).closest('[data-ftm-rate]'); const v = parseFloat($(this).data('v')).toFixed(1); $w.find('.ftm-rate-num').val(v); ftmRateSync($w, v); });
    window.App = App;







    $(document).ready(function() {



        if ($('#ftm-app').length) {



            App.init();



        }



    });







})(jQuery);



