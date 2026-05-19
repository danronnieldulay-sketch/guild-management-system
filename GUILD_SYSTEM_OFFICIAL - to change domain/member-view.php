<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member View</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="pendingStatus" style="display: none;">
        <div class="container centered">
            <div class="status-card">
                <svg class="icon-lg status-icon pending" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
                <h2>Application Pending</h2>
                <p class="status-text">Your application to <span id="pendingGuildName"></span> is being reviewed by the guild leader.</p>
                <p class="status-subtext">You will be notified once a decision is made.</p>
                <button class="submit-button" onclick="logout()">Back to Home</button>
            </div>
        </div>
    </div>

    <div id="rejectedStatus" style="display: none;">
        <div class="container centered">
            <div class="status-card">
                <svg class="icon-lg status-icon rejected" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
                <h2>Application Rejected</h2>
                <p class="status-text">Unfortunately, your application to <span id="rejectedGuildName"></span> was not approved.</p>
                <p class="status-subtext">You can apply to other guilds or try again later.</p>
                <button class="submit-button" onclick="logout()">Back to Home</button>
            </div>
        </div>
    </div>

    <div id="approvedView" style="display: none;">
        <div class="dashboard-header">
            <div class="header-content">
                <div>
                    <h1 id="guildName">Guild Name</h1>
                    <p class="subtitle" id="memberName">Member: </p>
                </div>
                <button class="logout-button" onclick="logout()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    Logout
                </button>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="tabs">
                <button class="tab-button active" data-tab="members" onclick="switchMemberTab('members')">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Guild Members
                </button>
                <button class="tab-button" data-tab="chat" onclick="switchMemberTab('chat')">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Guild Chat
                </button>
                <button class="tab-button" data-tab="roles" onclick="switchMemberTab('roles')">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l7 12-7 8-7-8z"/>
                    </svg>
                    Role
                </button>
            </div>

            <div id="membersMemberTab" class="tab-content active">
                <div class="content-card">
                    <h2 class="section-title">Guild Members</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>IGN</th>
                                    <th>Role</th>
                                    <th>Authority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="memberViewTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="chatMemberTab" class="tab-content">
                <div class="chat-container">
                    <div class="chat-header">
                        <h3>Guild Chat</h3>
                        <p class="chat-subtitle">Stay connected with your guild members</p>
                    </div>
                    <div class="chat-messages" id="memberChatMessages"></div>
                    <form class="chat-input-form" onsubmit="sendMemberMessage(event)">
                        <input type="text" id="memberChatInput" placeholder="Type your message..." required>
                        <button type="submit">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                            Send
                        </button>
                    </form>
                </div>
            </div>

            <div id="rolesMemberTab" class="tab-content">
                <div class="content-card">
                    <h2 class="section-title">Guild Roles</h2>
                    <p class="section-description">Equip roles created by your guild leadership.</p>
                    <div id="availableRolesContainer" class="role-list"></div>
                </div>
                <div class="content-card" style="margin-top: 1rem;">
                    <h3 class="section-subtitle">Your Equipped Roles</h3>
                    <div id="memberRolesContainer" class="role-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        function getQueryParam(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name);
        }

        function createRoleCard(role, isEquipped) {
            return `
                <div class="role-card">
                    <div>
                        <strong>${role.name}</strong>
                    </div>
                    <div class="role-card-actions">
                        ${isEquipped ?
                            `<button class="remove-role-button" onclick="removeRole(${role.id})">Unequip</button>` :
                            `<button class="equip-role-button" onclick="assignRole(${role.id})">Equip</button>`
                        }
                    </div>
                </div>
            `;
        }

        async function fetchJson(url, options = {}) {
            const response = await fetch(url, { credentials: 'same-origin', ...options });
            if (!response.ok) throw new Error('Network error');
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Request failed');
            return result;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        async function loadMemberView() {
            const user = await getEffectiveUser();
            if (!user) { window.location.href = 'login.php'; return; }

            const guildId = getQueryParam('guildId');
            if (!guildId) { window.location.href = 'dashboard.php'; return; }

            try {
                const guildResult = await fetchJson(`php/api.php?action=guild/get&guildId=${encodeURIComponent(guildId)}`);
                const guild = guildResult.data;
                document.getElementById('guildName').textContent = guild.name;
                document.getElementById('memberName').textContent = `Member: ${user.username}`;

                const currentMember = guild.members.find(m => String(m.user_id) === String(user.id));
                if (!currentMember) {
                    alert('You are not a member of this guild.');
                    window.location.href = 'dashboard.php';
                    return;
                }

                document.getElementById('approvedView').style.display = 'block';
                document.getElementById('pendingStatus').style.display = 'none';
                document.getElementById('rejectedStatus').style.display = 'none';

                window.currentGuildId = guildId;
                window.currentUser = user;
                renderMemberTable(guild.members);
                await Promise.all([
                    loadRoleSections(guildId, currentMember.id),
                    loadMemberChat(guildId)
                ]);
                listenForMemberChatUpdates(guildId);
            } catch (error) {
                console.error(error);
                alert('Unable to load guild information.');
                window.location.href = 'dashboard.php';
            }
        }

        function renderMemberTable(members) {
            const tbody = document.getElementById('memberViewTableBody');
            tbody.innerHTML = members.map(member => {
                const roleDisplay = member.role && member.role.toLowerCase() !== 'member' ? member.role : 'None';
                const authority = member.authority || member.sub_guild || member.subGuild || '-';
                const status = member.status || 'Offline';
                return `
                    <tr>
                        <td>${member.ign || '—'}</td>
                        <td>${roleDisplay}</td>
                        <td>${authority}</td>
                        <td>${status}</td>
                    </tr>
                `;
            }).join('');
        }

        async function loadMemberChat(guildId) {
            try {
                const response = await fetch(`php/firebase-api.php?action=chat/list&guildId=${encodeURIComponent(guildId)}`, {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                const container = document.getElementById('memberChatMessages');

                if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                    const currentUser = window.currentUser?.username;
                    container.innerHTML = result.data.map(msg => {
                        // Map Firebase format (username) to UI format (sender)
                        const sender = msg.username || msg.sender || 'Anonymous';
                        const time = msg.timestamp ? new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                        const bubbleClass = sender === currentUser ? 'message sent' : 'message received';
                        return `<div class="${bubbleClass}">
                                    <div class="message-bubble">
                                        <div class="message-user">${escapeHtml(sender)}</div>
                                        <div class="message-text">${escapeHtml(msg.text)}</div>
                                        <div class="message-time">${escapeHtml(time)}</div>
                                    </div>
                                </div>`;
                    }).join('');
                    container.scrollTop = container.scrollHeight;
                } else {
                    container.innerHTML = '<div class="empty-state-card">No messages yet.</div>';
                }

                window.currentGuildId = guildId;
            } catch (error) {
                console.error('Error loading guild chat:', error);
                document.getElementById('memberChatMessages').innerHTML = '<div class="empty-state-card">Unable to load chat messages.</div>';
            }
        }

        function listenForMemberChatUpdates(guildId) {
            if (window.memberChatInterval) {
                clearInterval(window.memberChatInterval);
            }
            window.memberChatInterval = setInterval(() => loadMemberChat(guildId), 3000);
        }

        async function loadRoleSections(guildId, memberId) {
            const [rolesResult, memberRolesResult] = await Promise.all([
                fetchJson(`php/api.php?action=role/list&guildId=${encodeURIComponent(guildId)}`),
                fetchJson(`php/api.php?action=member/roles&memberId=${encodeURIComponent(memberId)}`)
            ]);

            const roles = rolesResult.data || [];
            const memberRoles = memberRolesResult.data || [];
            const equippedRoleIds = new Set(memberRoles.map(r => String(r.id)));

            const availableContainer = document.getElementById('availableRolesContainer');
            availableContainer.innerHTML = roles.length === 0
                ? '<div class="empty-state-card">No guild roles have been created yet.</div>'
                : roles.map(role => createRoleCard(role, equippedRoleIds.has(String(role.id)))).join('');

            const equippedContainer = document.getElementById('memberRolesContainer');
            equippedContainer.innerHTML = memberRoles.length === 0
                ? '<div class="empty-state-card">You have not equipped any roles yet.</div>'
                : memberRoles.map(role => `
                    <div class="role-card">
                        <div><strong>${role.name}</strong></div>
                        <div class="role-card-actions">
                            <button class="remove-role-button" onclick="removeRole(${role.id})">Unequip</button>
                        </div>
                    </div>
                `).join('');

            window.currentGuildId = guildId;
            window.currentMemberId = memberId;
        }

        async function assignRole(roleId) {
            try {
                await fetchJson('php/api.php?action=role/assign', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ memberId: window.currentMemberId, roleId })
                });
                await loadRoleSections(window.currentGuildId, window.currentMemberId);
            } catch (error) {
                alert(error.message || 'Failed to equip role.');
            }
        }

        async function removeRole(roleId) {
            try {
                await fetchJson('php/api.php?action=role/remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ memberId: window.currentMemberId, roleId })
                });
                await loadRoleSections(window.currentGuildId, window.currentMemberId);
            } catch (error) {
                alert(error.message || 'Failed to unequip role.');
            }
        }

        function switchMemberTab(tabName) {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabName));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.toggle('active', content.id === `${tabName}MemberTab`));
        }

        async function sendMemberMessage(event) {
            event.preventDefault();
            const input = document.getElementById('memberChatInput');
            const message = input.value.trim();
            if (!message) return;
            if (!window.currentGuildId) {
                alert('Unable to determine guild context.');
                return;
            }

            try {
                const response = await fetch('php/firebase-api.php?action=chat/send', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ guildId: window.currentGuildId, text: message })
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Unable to send message.');
                }
                input.value = '';
                await loadMemberChat(window.currentGuildId);
            } catch (error) {
                console.error('Error sending member chat message:', error);
                alert('Failed to send message.');
            }
        }
    </script>
    <script>loadMemberView();</script>
</body>
</html>
