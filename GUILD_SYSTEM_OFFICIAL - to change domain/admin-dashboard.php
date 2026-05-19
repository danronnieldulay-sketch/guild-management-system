<?php
require_once 'php/start_session.php';
require_once 'php/db.php';
require_once 'php/classes/User.php';
$userObj = new User($pdo);
$userObj->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-header">
        <div class="header-content">
            <div>
                <h1 id="guildName">Guild Name</h1>
                <p class="subtitle" id="leaderName">Guild Leader: </p>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="back-to-dashboard-button" type="button" onclick="navigateTo('my-guilds.php')">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to My Guilds
                </button>
                <button class="profile-button" type="button" onclick="dashboard.openProfile()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profile
                </button>
                <button class="logout-button" type="button" onclick="handleLogout()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="tabs">
            <button class="tab-button active" data-tab="members" type="button" onclick="switchTab('members')">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Members (<span id="memberCount">0</span>)
            </button>
            <button class="tab-button" data-tab="pending" type="button" onclick="switchTab('pending')">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M20 8v6M23 11h-6"/>
                </svg>
                Pending (<span id="pendingCount">0</span>)
                <span id="pendingBadge" class="badge" style="display: none;">0</span>
            </button>
            <button class="tab-button" data-tab="roles" type="button" onclick="switchTab('roles')">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <circle cx="12" cy="9" r="2"/>
                </svg>
                <span id="rolesTabLabel">Manage Roles</span>
            </button>
            <button class="tab-button" data-tab="chat" type="button" onclick="switchTab('chat')">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Guild Chat
            </button>
            <button class="tab-button" data-tab="manage" type="button" onclick="switchTab('manage')">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                </svg>
                Manage
            </button>
        </div>

        <div id="membersTab" class="tab-content active">
            <div class="content-card">
                <h2 class="section-title">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Guild Members
                </h2>
                <div class="table-container">
                    <table id="membersTable" class="guild-table">
                        <thead>
                            <tr>
                                <th>IGN</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Authority</th>
                                <th class="actions-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody"></tbody>
                    </table>
                </div>

                <!-- Popover Roles Card -->
                <div id="rolesPopover" class="roles-popover" style="display: none;">
                    <div class="popover-header">
                        <div class="popover-member-info">
                            <span class="popover-member-name" id="popoverMemberName">Member Name</span>
                            <span class="popover-member-ign" id="popoverMemberIGN">IGN: -</span>
                        </div>
                        <button class="popover-close-btn" onclick="closeRolesPopover()">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    <div class="popover-roles-list" id="popoverRolesList"></div>
                </div>
            </div>
        </div>

        <div id="pendingTab" class="tab-content">
            <h2 class="section-title">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M20 8v6M23 11h-6"/>
                </svg>
                Pending Applications
            </h2>
            <div id="pendingApplications"></div>
        </div>

        <div id="chatTab" class="tab-content">
            <div class="chat-container-wrapper">
                <!-- Chat Header -->
                <div class="chat-header-section">
                    <div class="chat-title-group">
                        <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <div>
                            <h3>Guild Chat</h3>
                            <p class="chat-subtitle">Stay connected with your guild members</p>
                        </div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="chat-messages-container" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>

                <!-- Chat Input -->
                <form class="chat-input-section" onsubmit="dashboard.sendMessage(event)">
                    <div class="chat-input-wrapper">
                        <input 
                            type="text" 
                            id="chatInput" 
                            class="chat-input-field" 
                            placeholder="Type your message..." 
                            required
                            autocomplete="off"
                        >
                        <button type="submit" class="chat-send-button">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                            Send
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="rolesTab" class="tab-content">
            <div class="content-card">
                <h2 class="section-title">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <circle cx="12" cy="9" r="2"/>
                    </svg>
                    <span id="rolesTabTitle">Manage Member Roles</span>
                </h2>

                <div class="roles-add-section">
                    <h3>Add New Role</h3>
                    <form class="role-input-group" onsubmit="dashboard.addRole(event)">
                        <input type="text" id="newRoleName" class="role-input" placeholder="Role name (e.g., Tank, DPS, Support)" required>
                        <button type="submit" class="add-role-button">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Add Role
                        </button>
                    </form>
                </div>

                <div id="rolesContent" class="roles-list-section">
                    <table id="rolesTable" class="guild-table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rolesTableBody"></tbody>
                    </table>
                </div>

                <!-- Member Roles Section -->
                <div id="memberRolesSection" class="member-roles-content" style="display: none;">
                    <div class="current-equipped-role">
                        <h3>Currently Equipped</h3>
                        <div class="equipped-role-card">
                            <span class="role-badge" id="currentEquippedRole">None</span>
                            <p id="equipppedRoleStatus" style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--color-text-secondary);">No role equipped</p>
                        </div>
                    </div>
                    <div class="available-member-roles">
                        <h3>Available Roles</h3>
                        <p class="section-note">These roles are created by your guild leader or subleader. You can equip them here, but you cannot delete them from this tab.</p>
                        <div class="roles-cards-grid" id="memberRolesGrid"></div>
                        <div id="noRolesMessage" class="empty-state-card" style="display: none;">No roles available</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="manageTab" class="tab-content">
            <h2 class="section-title">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 0-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Guild Management
            </h2>
            <div class="content-card">
                <p>Manage your guild settings and actions. As the guild leader, you have the authority to leave or disband the guild.</p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                    <button class="leave-button" type="button" onclick="dashboard.leaveGuild()">
                        Leave Guild
                    </button>
                    <button id="disbandGuildButton" class="disband-button" type="button" onclick="dashboard.disbandGuild()" style="display: none;">
                        Disband Guild
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="promoteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Promote Member</h2>
                <button type="button" class="close-modal-button" onclick="dashboard.closeModals()">×</button>
            </div>
            <form id="promoteForm" class="modal-form" onsubmit="dashboard.confirmPromoteMember(event)">
                <p id="promoteMemberLabel">Select a new authority level for this member.</p>
                <label>
                    New Role
                    <select id="newAuthoritySelect" onchange="updatePromoteButtonText()">
                        <option value="Leader">Leader</option>
                        <option value="Sub leader">Sub leader</option>
                        <option value="member">Member</option>
                    </select>
                </label>
                <div class="modal-actions">
                    <button type="button" class="action-btn cancel-btn" onclick="dashboard.closeModals()">Cancel</button>
                    <button type="submit" class="action-btn promote-btn" id="promoteActionBtn">Confirm Promote</button>
                </div>
            </form>
        </div>
    </div>

    <div id="kickModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Kick</h2>
                <button type="button" class="close-modal-button" onclick="dashboard.closeModals()">×</button>
            </div>
            <div class="modal-form">
                <p id="kickMemberLabel">Are you sure you want to remove this member?</p>
                <div class="modal-actions">
                    <button type="button" class="action-btn cancel-btn" onclick="dashboard.closeModals()">Cancel</button>
                    <button type="button" class="action-btn kick-btn" onclick="dashboard.confirmKickMember()">Confirm Kick</button>
                </div>
            </div>
        </div>
    </div>

    <div id="memberRolesModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="dashboard.closeMemberRolesModal()"></div>
        <div class="modal-content role-popup-modal">
            <div class="modal-header">
                <h2 id="memberRolesModalTitle">Member Roles</h2>
                <button type="button" class="close-modal-button" onclick="dashboard.closeMemberRolesModal()">×</button>
            </div>
            <div id="memberRolesModalBody" class="member-roles-popup-body"></div>
        </div>
    </div>

    <div id="equipRoleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="equipRoleTitle">Manage Roles</h2>
                <button type="button" class="close-modal-button" onclick="dashboard.closeModals()">×</button>
            </div>
            <form id="equipRoleForm" class="modal-form" onsubmit="dashboard.confirmEquipRole(event)">
                <p id="equipRoleLabel">Select a role to equip or unequip.</p>
                <label id="equipRoleSelectLabel" for="equipRoleSelect">Select Role</label>
                <select id="equipRoleSelect" onchange="updateEquipRoleButtonText()"></select>
                <p id="equipRoleMessage" class="status-text" style="margin-top: 0.75rem;"></p>
                <div class="modal-actions">
                    <button type="button" class="action-btn cancel-btn" onclick="dashboard.closeModals()">Cancel</button>
                    <button type="submit" class="action-btn promote-btn" id="equipRoleActionBtn">Select Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Member Profile Modal -->
    <div id="memberProfileModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="dashboard.closeProfile()" style="pointer-events: none;"></div>
        <div class="modal-content profile-modal" style="max-height: 90vh; display: flex; flex-direction: column; pointer-events: auto;">
            <div class="modal-header">
                <h2>My Profile</h2>
                <button type="button" class="close-modal-button" onclick="dashboard.closeProfile()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="profile-content" style="overflow-y: auto; flex: 1; padding: 1.5rem;">
                <div class="profile-section">
                    <h3>Guild Information</h3>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>Guild Name</label>
                            <p id="profileGuildName">-</p>
                        </div>
                        <div class="profile-field">
                            <label>Your Authority</label>
                            <p id="profileAuthority">-</p>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Account Information</h3>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>Username</label>
                            <p id="profileUsername">-</p>
                        </div>
                        <div class="profile-field">
                            <label>IGN (In-Game Name)</label>
                            <div id="ignDisplay" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                <p id="profileIGN" style="margin: 0; flex: 1; min-width: 150px;">-</p>
                                <button type="button" id="editIgnBtn" class="edit-btn" onclick="dashboard.editIGN()" style="background-color: #7c3aed; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='#6d28d9'" onmouseout="this.style.backgroundColor='#7c3aed'">Edit</button>
                            </div>
                            <div id="ignEditForm" style="display: none; margin-top: 0.75rem;">
                                <input type="text" id="ignInput" placeholder="Enter IGN" style="padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px solid #4b5563; background-color: #1a1f2e; color: #e2e8f0; width: 100%; max-width: 300px; font-size: 0.875rem;">
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                                    <button type="button" id="saveIgnBtn" class="action-btn save-btn" onclick="dashboard.saveIGN()" style="background-color: #10b981; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">Save</button>
                                    <button type="button" id="cancelIgnBtn" class="action-btn cancel-btn" onclick="dashboard.cancelEditIGN()" style="background-color: #6b7280; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        class GuildAdminDashboard {
            constructor() {
                this.members = [];
                this.pendingApplications = [];
                this.chatMessages = [];
                this.roles = [];
                this.guildId = null;
                this.currentMemberId = null;
                this.currentAuthority = 'member';
                this.selectedMemberId = null;
                this.selectedMemberName = '';
                this.selectedRoleId = null;
                this.selectedRoleName = '';
                this.equipMode = 'member';
                this.currentMemberData = null;
                this.currentGuildData = null;
            }

            async init() {
                await this.loadGuildInfo();
                this.updateTabState();
            }

            getQueryParam(name) {
                const params = new URLSearchParams(window.location.search);
                return params.get(name);
            }

            async loadGuildInfo() {
                const guildId = this.getQueryParam('guildId');
                if (!guildId) {
                    navigateTo('dashboard.php');
                    return;
                }

                const response = await fetch(`php/api.php?action=guild/get&guildId=${encodeURIComponent(guildId)}&admin=true`, {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (!result.success) {
                    navigateTo('dashboard.php');
                    return;
                }

                const data = result.data;
                this.guildId = guildId;
                this.currentGuildData = data;
                document.getElementById('guildName').textContent = data.name;
                document.getElementById('leaderName').textContent = 'Guild Leader: ' + (data.leader_ign || data.leaderUsername || data.leader_username || 'Unknown');
                this.members = data.members || [];
                this.pendingApplications = data.applications || [];

                const currentUser = User.getCurrent();
                const currentMember = this.members.find(member => String(member.user_id) === String(currentUser?.id));
                this.currentMemberId = currentMember ? (currentMember.id || currentMember.member_id || null) : null;
                this.currentMemberData = currentMember;
                const currentAuthorityLabel = currentMember ? String(currentMember.authority || currentMember.sub_guild || currentMember.subGuild || 'member').trim() : 'member';
                const normalizedCurrentAuthority = currentAuthorityLabel.toLowerCase();
                this.currentAuthority = normalizedCurrentAuthority;
                this.canManageRoles = normalizedCurrentAuthority === 'leader' || normalizedCurrentAuthority === 'sub leader';
                document.querySelector('.roles-add-section').style.display = this.canManageRoles ? 'block' : 'none';
                document.getElementById('rolesContent').style.display = this.canManageRoles ? 'block' : 'none';
                document.getElementById('memberRolesSection').style.display = this.canManageRoles ? 'none' : 'block';
                const rolesTabButton = document.querySelector('.tab-button[data-tab="roles"]');
                if (rolesTabButton) {
                    const labelEl = document.getElementById('rolesTabLabel');
                    if (labelEl) {
                        labelEl.textContent = this.canManageRoles ? 'Manage Roles' : 'My Roles';
                    }
                    const titleEl = document.getElementById('rolesTabTitle');
                    if (titleEl) {
                        titleEl.textContent = this.canManageRoles ? 'Manage Member Roles' : 'My Roles';
                    }
                }
                const rolesAddSection = document.querySelector('.roles-add-section');
                if (rolesAddSection) {
                    rolesAddSection.style.display = this.canManageRoles ? 'block' : 'none';
                }
                const memberRolesSection = document.getElementById('memberRolesSection');
                if (memberRolesSection) {
                    memberRolesSection.style.display = this.canManageRoles ? 'none' : 'block';
                }
                const memberRolesTabButton = document.querySelector('.tab-button[data-tab="member-roles"]');
                if (memberRolesTabButton) {
                    memberRolesTabButton.style.display = 'none'; // Hide the separate member-roles tab
                }
                const disbandButton = document.getElementById('disbandGuildButton');
                if (disbandButton) {
                    disbandButton.style.display = normalizedCurrentAuthority === 'leader' ? 'block' : 'none';
                }

                this.renderMembers();
                this.renderPending();
                await this.loadRoles();
                await this.loadChatMessages();
                this.listenForChatUpdates();
            }

            async loadChatMessages() {
                if (!this.guildId) return;
                try {
                    const response = await fetch(`php/firebase-api.php?action=chat/list&guildId=${encodeURIComponent(this.guildId)}`, {
                        credentials: 'same-origin'
                    });
                    const result = await response.json();
                    if (result.success) {
                        // Map Firebase format to UI format
                        this.chatMessages = (result.data || []).map(msg => ({
                            id: msg.id || Math.random(),
                            sender: msg.username || 'Anonymous',
                            text: msg.text || '',
                            timestamp: msg.timestamp || msg.createdAt,
                            userId: msg.userId
                        }));
                    } else {
                        console.error('Unable to load chat messages:', result.message);
                        this.chatMessages = [];
                    }
                } catch (error) {
                    console.error('Error loading chat messages:', error);
                    this.chatMessages = [];
                }
                this.renderChat();
            }

            listenForChatUpdates() {
                if (!this.guildId) return;
                if (this.chatRefreshInterval) {
                    clearInterval(this.chatRefreshInterval);
                }
                this.chatRefreshInterval = setInterval(() => this.loadChatMessages(), 3000);
            }

            async sendMessage(event) {
                event.preventDefault();
                const input = document.getElementById('chatInput');
                const text = input.value.trim();
                if (!text || !this.guildId) return;

                try {
                    const response = await fetch('php/firebase-api.php?action=chat/send', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ guildId: this.guildId, text })
                    });
                    const result = await response.json();
                    if (!result.success) {
                        throw new Error(result.message || 'Unable to send message.');
                    }
                    input.value = '';
                    await this.loadChatMessages();
                } catch (error) {
                    console.error('Error sending chat message:', error);
                    alert('Failed to send message.');
                }
            }

            renderChat() {
                const container = document.getElementById('chatMessages');
                container.innerHTML = '';
                if (this.chatMessages.length === 0) {
                    container.innerHTML = '<div class="no-messages">No messages yet. Start the conversation!</div>';
                } else {
                    this.chatMessages.forEach(msg => {
                        const date = msg.timestamp ? new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                        const messageHTML = `
                            <div class="chat-message">
                                <div class="message-avatar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <span class="message-author">${escapeHtml(msg.sender)}</span>
                                        <span class="message-time">${escapeHtml(date)}</span>
                                    </div>
                                    <div class="message-text">${escapeHtml(msg.text)}</div>
                                </div>
                            </div>
                        `;
                        container.innerHTML += messageHTML;
                    });
                    container.scrollTop = container.scrollHeight;
                }
            }

            updateTabState() {
                const roles = member.roles || [];
                const memberId = member.id || member.member_id || '';
                if (roles.length === 0) {
                    return `<span class="role-badge empty">None</span>`;
                }
                if (roles.length === 1) {
                    const roleName = roles[0].name || roles[0];
                    return `<span class="role-badge">${roleName}</span>`;
                }
                const firstRole = roles[0].name || roles[0];
                const additionalCount = roles.length - 1;
                return `
                    <div class="role-display-container">
                        <span class="role-badge">${firstRole}</span>
                        <button type="button" class="expand-roles-btn" onclick="toggleRoleExpansion(event, '${memberId}')">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                            +${additionalCount} more
                        </button>
                    </div>
                `;
            }

            createActionButtons(member, currentAuthority, currentUserId) {
                const normalizedAuthority = String(member.authority || member.sub_guild || member.subGuild || 'member').trim().toLowerCase();
                const isLeaderRow = normalizedAuthority === 'leader';
                const isLeader = currentAuthority === 'leader';
                const isSubLeader = currentAuthority === 'sub leader';
                const canManage = isLeader || isSubLeader;
                if (isLeaderRow || !canManage) {
                    return `<span class="no-actions">-</span>`;
                }
                const memberId = member.id || member.member_id || '';
                const memberName = (member.username || 'Member').replace(/'/g, "\\'");
                let buttons = '<div class="action-buttons-group">';
                if (canManage) {
                    buttons += `
                        <button type="button" class="equip-role-btn-action" onclick="dashboard.showEquipRoleModal(${memberId}, '${memberName}')">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                            Equip
                        </button>`;
                }
                if (isLeader && !isLeaderRow) {
                    buttons += `
                        <button type="button" class="promote-btn" onclick="dashboard.showPromoteModal(${memberId}, '${memberName}', '${member.authority || member.sub_guild || member.subGuild}')">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 19V5M5 12l7-7 7 7"/>
                            </svg>
                            Promote
                        </button>`;
                }
                if (canManage) {
                    buttons += `
                        <button type="button" class="remove-btn" onclick="dashboard.showKickModal(${memberId}, '${memberName}')">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                            Remove
                        </button>`;
                }
                buttons += '</div>';
                return buttons;
            }

            createRoleDisplay(member) {
                const roles = Array.isArray(member.roles) ? member.roles : [];
                if (roles.length === 0) { 
                    return '<span class="no-roles-message">—</span>'; 
                }
                if (roles.length === 1) {
                    return `<span class="role-badge">${escapeHtml(roles[0].name || roles[0])}</span>`;
                }

                const firstRole = escapeHtml(roles[0].name || roles[0]);
                const memberName = (member.username || '—').replace(/'/g, "\\'");
                const memberId = member.id || member.member_id || '';
                return `
                    <div class="role-display-container">
                        <span class="role-badge">${firstRole}</span>
                        <button type="button" class="role-badge additional more-roles-btn" data-member-id="${memberId}" data-member-name="${memberName}" onclick="dashboard.showMemberAllRoles(${memberId}, '${memberName}')">+${roles.length - 1}</button>
                    </div>`;
            }

            renderMembers() {
                const tbody = document.getElementById('membersTableBody');
                tbody.innerHTML = '';
                const currentUser = User.getCurrent();
                const currentUserId = currentUser ? currentUser.id : null;
                const currentMember = this.members.find(member => String(member.user_id) === String(currentUserId));
                const currentAuthorityLabel = currentMember ? String(currentMember.authority || currentMember.sub_guild || currentMember.subGuild || 'member').trim() : 'member';
                const currentAuthority = currentAuthorityLabel.toLowerCase();

                this.members.forEach(m => {
                    const statusDisplay = m.status === 'Offline' && m.last_active ?
                        `Offline (${Math.floor((Date.now() - new Date(m.last_active).getTime()) / 60000)} min ago)` :
                        (m.status || 'Offline');
                    const memberName = (m.username || '—').replace(/'/g, "\\'");
                    const authorityLabel = String(m.authority || m.sub_guild || m.subGuild || 'member').trim();
                    const normalizedAuthority = authorityLabel.toLowerCase();
                    const memberId = m.id || m.member_id || '';
                    const isLeaderRow = normalizedAuthority === 'leader';
                    const isSelfRow = String(m.user_id) === String(currentUserId);
                    const canPromote = (currentAuthority === 'leader' || currentAuthority === 'sub leader') && !isLeaderRow && !(currentAuthority === 'sub leader' && isSelfRow);
                    const canKick = (currentAuthority === 'leader' || currentAuthority === 'sub leader') && !isLeaderRow && !isSelfRow;
                    const canEquip = this.canManageRoles;

                    let actionButtons = '';
                    if (canPromote || canKick) {
                        actionButtons = '<div class="action-buttons-group">';
                        if (canPromote) {
                            actionButtons += `
                                <button type="button" class="promote-btn" aria-label="Promote ${memberName}" onclick="dashboard.showPromoteModal(${memberId}, '${memberName}', '${authorityLabel}')">
                                    <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 19V5M5 12l7-7 7 7"/>
                                    </svg>
                                    Promote
                                </button>`;
                        }
                        if (canKick) {
                            actionButtons += `
                                <button type="button" class="remove-btn" aria-label="Remove ${memberName}" onclick="dashboard.showKickModal(${memberId}, '${memberName}')">
                                    <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                    Remove
                                </button>`;
                        }
                        actionButtons += '</div>';
                    }

                    const displayRole = this.createRoleDisplay(m);
                    const statusClass = (String(m.status || '').toLowerCase() === 'online') ? 'status-online' : 'status-offline';
                    const authorityDisplay = `<span class="authority-badge ${normalizedAuthority === 'leader' ? 'leader' : 'member'}">${authorityLabel || '-'}</span>`;
                    const roleActions = this.createActionButtons(m, currentAuthority, currentUserId);

                    // Show IGN, fallback to username, highlight if missing
                    let ignDisplay = m.ign ? escapeHtml(m.ign) : `<span style='color:#ff6b6b;font-style:italic'>Missing IGN</span>`;
                    const row = `<tr>
                        <td class="text-white">${ignDisplay}</td>
                        <td>${escapeHtml(m.username || '—')}</td>
                        <td>${displayRole}</td>
                        <td><span class="${statusClass}">${statusDisplay}</span></td>
                        <td>${authorityDisplay}</td>
                        <td>${roleActions}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
                document.getElementById('memberCount').textContent = this.members.length;
            }

            renderPending() {
                const container = document.getElementById('pendingApplications');
                container.innerHTML = '';
                if (this.pendingApplications.length === 0) {
                    container.innerHTML = '<div class="empty-state-card">No pending applications</div>';
                } else {
                    this.pendingApplications.forEach(app => {
                        const hasIGN = app.ign && app.ign.trim() !== '';
                        const approveButtonState = hasIGN 
                            ? 'class="approve-button"' 
                            : 'class="approve-button" disabled style="opacity: 0.5; cursor: not-allowed;"';
                        const ignDisplay = hasIGN 
                            ? `<span>${app.ign}</span>` 
                            : `<span style="color: #ef4444; font-weight: bold;">MISSING</span>`;
                        const warningNote = !hasIGN 
                            ? '<p style="color: #ef4444; margin: 0.5rem 0 0 0; font-size: 0.85rem;">⚠️ Cannot approve: IGN is missing</p>' 
                            : '';
                        
                        const card = `<div class="application-card">
                            <div class="application-overview">
                                <p class="application-user"><strong>${app.username || 'Unknown'}</strong> applied with IGN ${ignDisplay}</p>
                                <p class="application-message">${app.message ? app.message : 'No message provided.'}</p>
                                ${warningNote}
                            </div>
                            <div class="application-actions">
                                <button type="button" ${approveButtonState} ${hasIGN ? `onclick="dashboard.approveApplication(${app.id})"` : ''}>Approve</button>
                                <button type="button" class="reject-button" onclick="dashboard.rejectApplication(${app.id})">Reject</button>
                            </div>
                        </div>`;
                        container.innerHTML += card;
                    });
                }
                document.getElementById('pendingCount').textContent = this.pendingApplications.length;
                const badge = document.getElementById('pendingBadge');
                if (this.pendingApplications.length > 0) {
                    badge.textContent = this.pendingApplications.length;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }

            renderRoles() {
                const tbody = document.getElementById('rolesTableBody');
                tbody.innerHTML = '';
                if (this.roles.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="2" style="padding: 1rem; text-align: center;">None</td></tr>';
                    return;
                }

                // Get current member's equipped roles
                const currentUser = User.getCurrent();
                const currentMember = this.members.find(member =>
                    String(member.user_id) === String(currentUser?.id) ||
                    String(member.id) === String(this.currentMemberId) ||
                    String(member.member_id) === String(this.currentMemberId)
                );
                const equippedRoleIds = currentMember && Array.isArray(currentMember.roles)
                    ? new Set(currentMember.roles.map(r => String(r.id)))
                    : new Set();

                this.roles.forEach(role => {
                    const roleId = role.id || 0;
                    const normalizedRoleName = (role.name || role).toString().replace(/'/g, "\\'");
                    const isEquipped = equippedRoleIds.has(String(roleId));
                    
                    const equipButton = this.canManageRoles ? `
                        <button type="button" class="equip-role-btn ${isEquipped ? 'equipped' : ''}" aria-label="${isEquipped ? 'Unequip' : 'Equip'} role ${normalizedRoleName} to yourself" onclick="dashboard.toggleRoleForSelf(${roleId}, '${normalizedRoleName}', ${isEquipped})">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                ${isEquipped ? '<path d="M20 6L9 17l-5-5"/>' : '<path d="M12 5v14M5 12h14"/>'}
                            </svg>
                            ${isEquipped ? 'Unequip' : 'Equip'}
                        </button>
                    ` : '';
                    const deleteButton = this.canManageRoles ? `
                        <button type="button" class="delete-role-button" onclick="dashboard.removeRole(${roleId})" aria-label="Delete role ${normalizedRoleName}">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                            </svg>
                            Delete
                        </button>
                    ` : '';

                    const actionGroup = this.canManageRoles ? `
                        <div class="role-actions-group">
                            ${equipButton}
                            ${deleteButton}
                        </div>
                    ` : `<span class="no-actions">-</span>`;

                    const row = `<tr>
                        <td>${role.name || role}</td>
                        <td>${actionGroup}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            }

            async loadRoles() {
                try {
                    const response = await fetch(`php/api.php?action=role/list&guildId=${encodeURIComponent(this.guildId)}`, {
                        credentials: 'same-origin'
                    });
                    const result = await response.json();
                    if (result.success && Array.isArray(result.data)) {
                        this.roles = result.data;
                    } else if (result.success) {
                        this.roles = [];
                    } else {
                        this.roles = [];
                    }
                    if (this.canManageRoles) {
                        this.renderRoles();
                    } else {
                        this.renderMemberRoles();
                    }
                } catch (error) {
                    console.error('Error loading roles:', error);
                    this.roles = [];
                    if (this.canManageRoles) {
                        this.renderRoles();
                    } else {
                        this.renderMemberRoles();
                    }
                }
            }

            renderMemberRoles() {
                const currentUser = User.getCurrent();
                const currentMember = this.members.find(member =>
                    String(member.user_id) === String(currentUser?.id) ||
                    String(member.id) === String(this.currentMemberId) ||
                    String(member.member_id) === String(this.currentMemberId)
                );
                if (!currentMember) {
                    console.warn('Current member not found in guild members array. Falling back to available roles only.');
                }

                const equippedRoles = (currentMember && currentMember.roles) ? currentMember.roles : [];
                const currentEquippedRoleEl = document.getElementById('currentEquippedRole');
                const equipppedRoleStatusEl = document.getElementById('equipppedRoleStatus');
                
                if (equippedRoles.length === 0) {
                    currentEquippedRoleEl.textContent = 'None';
                    currentEquippedRoleEl.classList.add('empty');
                    equipppedRoleStatusEl.textContent = 'No role equipped';
                } else {
                    const firstRole = equippedRoles[0];
                    const roleName = firstRole.name || firstRole;
                    currentEquippedRoleEl.textContent = roleName;
                    currentEquippedRoleEl.classList.remove('empty');
                    if (equippedRoles.length === 1) {
                        equipppedRoleStatusEl.textContent = `You are currently equipped with the ${roleName} role`;
                    } else {
                        equipppedRoleStatusEl.textContent = `You are equipped with ${equippedRoles.length} roles. Currently showing: ${roleName}`;
                    }
                }

                const rolesGrid = document.getElementById('memberRolesGrid');
                const noRolesMessage = document.getElementById('noRolesMessage');
                
                if (this.roles.length === 0) {
                    rolesGrid.innerHTML = '';
                    noRolesMessage.style.display = 'block';
                    return;
                }

                noRolesMessage.style.display = 'none';
                rolesGrid.innerHTML = '';

                const equippedRoleIds = new Set(equippedRoles.map(r => String(r.id)));

                this.roles.forEach(role => {
                    const roleId = role.id || 0;
                    const roleName = role.name || 'Unknown Role';
                    const isEquipped = equippedRoleIds.has(String(roleId));
                    const roleCard = document.createElement('div');
                    roleCard.className = `member-role-card ${isEquipped ? 'equipped' : ''}`;
                    roleCard.innerHTML = `
                        <div class="member-role-card-header">
                            <svg class="member-role-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <circle cx="12" cy="9" r="2"/>
                            </svg>
                            <h4>${escapeHtml(roleName)}</h4>
                        </div>
                        <p class="member-role-card-description">Guild role - ${isEquipped ? 'Currently equipped' : 'Available to equip'}</p>
                        <button type="button" class="member-role-equip-btn ${isEquipped ? 'equipped' : ''}" onclick="dashboard.toggleMemberRole(${roleId}, '${roleName.replace(/'/g, "\\'")}', ${isEquipped ? 'true' : 'false'})">
                            <svg class="icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                ${isEquipped ? '<path d="M20 6L9 17l-5-5"/>' : '<path d="M12 5v14M5 12h14"/>'}
                            </svg>
                            ${isEquipped ? 'Equipped' : 'Equip'}
                        </button>
                    `;
                    rolesGrid.appendChild(roleCard);
                });
            }

            async toggleMemberRole(roleId, roleName, isCurrentlyEquipped) {
                if (!this.currentMemberId) {
                    alert('Unable to find your guild membership.');
                    return;
                }

                if (!isCurrentlyEquipped) {
                    if (!confirm(`Equip the ${roleName} role?`)) {
                        return;
                    }
                    try {
                        const response = await fetch('php/api.php?action=role/assign', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ memberId: this.currentMemberId, roleId })
                        });
                        const result = await response.json();
                        if (result.success) {
                            alert(result.message || `Role "${roleName}" equipped successfully`);
                            await this.loadGuildInfo();
                        } else {
                            alert('Unable to equip role: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error equipping role:', error);
                        alert('Failed to equip role.');
                    }
                } else {
                    alert(`The ${roleName} role is currently equipped. Role unequipping is not yet available.`);
                }
            }

            async addRole(event) {
                event.preventDefault();
                const input = document.getElementById('newRoleName');
                const roleName = input.value.trim();
                if (!roleName) return;

                try {
                    const response = await fetch('php/api.php?action=role/create', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            guildId: this.guildId, 
                            roleName: roleName,
                            maxLimit: null 
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(`Role "${roleName}" created successfully`);
                        input.value = '';
                        await this.loadRoles();
                    } else {
                        alert('Error creating role: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error adding role:', error);
                    alert('Failed to create role.');
                }
            }

            async removeRole(roleId) {
                if (!confirm('Are you sure you want to delete this role?')) {
                    return;
                }

                try {
                    const response = await fetch('php/api.php?action=role/delete', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            roleId: roleId,
                            guildId: this.guildId
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Role deleted successfully');
                        await this.loadRoles();
                    } else {
                        alert('Error deleting role: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error removing role:', error);
                    alert('Failed to delete role.');
                }
            }

            async leaveGuild() {
                if (!this.guildId) {
                    alert('Guild ID not found.');
                    return;
                }
                if (!confirm('Leave this guild? If you are the only member, the guild will be disbanded.')) {
                    return;
                }
                try {
                    const response = await fetch('php/api.php?action=guild/leave', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ guildId: this.guildId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        navigateTo('dashboard.php');
                    } else {
                        alert('Unable to leave guild: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error leaving guild:', error);
                    alert('Failed to leave guild.');
                }
            }

            async disbandGuild() {
                if (!this.guildId) {
                    alert('Guild ID not found.');
                    return;
                }
                if (!confirm('Disband this guild? This action deletes the guild for everyone instantly.')) {
                    return;
                }
                try {
                    const response = await fetch('php/api.php?action=guild/disband', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ guildId: this.guildId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        navigateTo('dashboard.php');
                    } else {
                        alert('Unable to disband guild: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error disbanding guild:', error);
                    alert('Failed to disband guild.');
                }
            }

            updateTabState() {
                const activeButton = document.querySelector('.tab-button.active');
                if (activeButton) {
                    switchTab(activeButton.dataset.tab);
                }
            }

            showPromoteModal(memberId, memberName, currentAuthority) {
                this.selectedMemberId = memberId;
                this.selectedMemberName = memberName;
                this.memberCurrentAuthority = (currentAuthority || 'member').toLowerCase();
                document.getElementById('promoteMemberLabel').textContent = `Promote ${memberName} to a new authority level.`;

                const select = document.getElementById('newAuthoritySelect');
                const authorities = ['Leader', 'Sub leader', 'member'];
                const currentAuth = this.memberCurrentAuthority;

                select.innerHTML = authorities
                    .filter(auth => auth.toLowerCase() !== currentAuth)
                    .map(auth => `<option value="${auth}">${auth}</option>`)
                    .join('');

                if (!select.value && select.options.length > 0) {
                    select.value = select.options[0].value;
                }

                updatePromoteButtonText();
                document.getElementById('promoteModal').style.display = 'flex';
            }

            showKickModal(memberId, memberName) {
                this.selectedMemberId = memberId;
                this.selectedMemberName = memberName;
                document.getElementById('kickMemberLabel').textContent = `Are you sure you want to remove ${memberName} from the guild?`;
                document.getElementById('kickModal').style.display = 'flex';
            }

            showEquipRoleModal(memberId, memberName) {
                this.equipMode = 'member';
                this.selectedMemberId = memberId;
                this.selectedMemberName = memberName;
                this.selectedRoleId = null;
                this.selectedRoleName = '';

                const member = this.members.find(member => String(member.id) === String(memberId));
                const equippedRoles = member?.roles || [];
                const equippedRoleIds = new Set(equippedRoles.map(role => String(role.id)));
                const select = document.getElementById('equipRoleSelect');
                select.innerHTML = '';

                // Create two groups: equipped and available
                const equippedRolesList = equippedRoles.map(role => ({
                    id: role.id,
                    name: role.name || role,
                    isEquipped: true
                }));
                
                const availableRoles = this.roles
                    .filter(role => !equippedRoleIds.has(String(role.id)))
                    .map(role => ({
                        id: role.id,
                        name: role.name || role,
                        isEquipped: false
                    }));

                // Show message if no roles
                if (equippedRolesList.length === 0 && availableRoles.length === 0) {
                    select.innerHTML = '<option value="">No roles available</option>';
                    select.disabled = true;
                    document.getElementById('equipRoleMessage').textContent = 'No roles available for this member.';
                    document.querySelector('.action-btn.promote-btn').textContent = 'No Actions';
                    document.querySelector('.action-btn.promote-btn').disabled = true;
                } else {
                    select.disabled = false;
                    document.getElementById('equipRoleMessage').textContent = '';
                    document.querySelector('.action-btn.promote-btn').disabled = false;
                    
                    // Add equipped roles as optgroup
                    if (equippedRolesList.length > 0) {
                        const equippedGroup = document.createElement('optgroup');
                        equippedGroup.label = '⚙️ Currently Equipped';
                        equippedRolesList.forEach(role => {
                            const option = document.createElement('option');
                            option.value = `unequip_${role.id}`;
                            option.textContent = role.name;
                            option.dataset.isEquipped = 'true';
                            equippedGroup.appendChild(option);
                        });
                        select.appendChild(equippedGroup);
                    }
                    
                    // Add available roles as optgroup
                    if (availableRoles.length > 0) {
                        const availableGroup = document.createElement('optgroup');
                        availableGroup.label = '✨ Available to Equip';
                        availableRoles.forEach(role => {
                            const option = document.createElement('option');
                            option.value = `equip_${role.id}`;
                            option.textContent = role.name;
                            option.dataset.isEquipped = 'false';
                            availableGroup.appendChild(option);
                        });
                        select.appendChild(availableGroup);
                    }

                    // Set default selection: prefer available roles to equip first
                    if (availableRoles.length > 0) {
                        select.value = `equip_${availableRoles[0].id}`;
                    } else if (equippedRolesList.length > 0) {
                        select.value = `unequip_${equippedRolesList[0].id}`;
                    }

                    // Update button text based on default selection
                    updateEquipRoleButtonText();
                }

                document.getElementById('equipRoleSelectLabel').textContent = 'Select Role';
                document.getElementById('equipRoleLabel').textContent = `Manage roles for ${memberName}`;
                document.getElementById('equipRoleModal').style.display = 'flex';
            }

            async toggleRoleForSelf(roleId, roleName, isCurrentlyEquipped) {
                if (!this.currentMemberId) {
                    alert('Unable to find your guild membership.');
                    return;
                }

                if (isCurrentlyEquipped) {
                    // Unequip role
                    if (!confirm(`Unequip the ${roleName} role?`)) {
                        return;
                    }
                    try {
                        const response = await fetch('php/api.php?action=role/remove', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ memberId: this.currentMemberId, roleId })
                        });
                        const result = await response.json();
                        if (result.success) {
                            alert(result.message || `Role "${roleName}" unequipped successfully`);
                            await this.loadGuildInfo();
                        } else {
                            alert('Unable to unequip role: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error unequipping role from self:', error);
                        alert('Failed to unequip role.');
                    }
                } else {
                    // Equip role
                    if (!confirm(`Equip role ${roleName} to yourself?`)) {
                        return;
                    }
                    try {
                        const response = await fetch('php/api.php?action=role/assign', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ memberId: this.currentMemberId, roleId })
                        });
                        const result = await response.json();
                        if (result.success) {
                            alert(result.message);
                            await this.loadGuildInfo();
                        } else {
                            alert('Unable to equip role: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error equipping role to self:', error);
                        alert('Failed to equip role.');
                    }
                }
            }

            async assignRoleToSelf(roleId, roleName) {
                // This function is kept for backwards compatibility
                if (!this.currentMemberId) {
                    alert('Unable to find your guild membership.');
                    return;
                }
                if (!confirm(`Equip role ${roleName} to yourself?`)) {
                    return;
                }

                try {
                    const response = await fetch('php/api.php?action=role/assign', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ memberId: this.currentMemberId, roleId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        await this.loadGuildInfo();
                    } else {
                        alert('Unable to equip role: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error equipping role to self:', error);
                    alert('Failed to equip role.');
                }
            }

            toggleRoleCards(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.style.display = element.style.display === 'flex' ? 'none' : 'flex';
                }
            }

            showMemberRolesModal(memberName, roleNames) {
                const modal = document.getElementById('memberRolesModal');
                const title = document.getElementById('memberRolesModalTitle');
                const body = document.getElementById('memberRolesModalBody');
                if (!modal || !title || !body) return;
                title.textContent = `${memberName} Roles`;
                body.innerHTML = roleNames.map(role => `<span class="member-role-card-popup">${escapeHtml(role)}</span>`).join('');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            showMemberAllRoles(memberId, memberName) {
                const member = this.members.find(m => 
                    (m.id === memberId || m.member_id === memberId) || 
                    (String(m.id) === String(memberId) || String(m.member_id) === String(memberId))
                );
                
                if (!member || !Array.isArray(member.roles)) {
                    return;
                }

                const roleNames = member.roles.map(role => role.name || role);
                this.showMemberRolesModal(memberName, roleNames);
            }

            closeMemberRolesModal() {
                const modal = document.getElementById('memberRolesModal');
                if (!modal) return;
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            async confirmEquipRole(event) {
                event.preventDefault();
                const selectionValue = document.getElementById('equipRoleSelect').value;
                if (!selectionValue) {
                    alert('Please select a role.');
                    return;
                }

                // Parse the selection value to determine if it's equip or remove
                const [action, roleIdStr] = selectionValue.split('_');
                const roleId = parseInt(roleIdStr, 10);

                if (!this.selectedMemberId) {
                    return;
                }

                const payload = { memberId: this.selectedMemberId, roleId };

                try {
                    // Use different endpoint based on action
                    const endpoint = action === 'unequip' ? 'role/remove' : 'role/assign';
                    const response = await fetch(`php/api.php?action=${endpoint}`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        this.closeModals();
                        await this.loadGuildInfo();
                    } else {
                        alert(`Unable to ${action === 'unequip' ? 'remove' : 'equip'} role: ` + result.message);
                    }
                } catch (error) {
                    console.error(`Error ${action === 'unequip' ? 'removing' : 'equipping'} role:`, error);
                    alert(`Failed to ${action === 'unequip' ? 'remove' : 'equip'} role.`);
                }
            }

            closeModals() {
                this.selectedRoleId = null;
                this.selectedRoleName = '';
                this.equipMode = 'member';
                this.selectedMemberId = null;
                this.selectedMemberName = '';
                document.getElementById('promoteModal').style.display = 'none';
                document.getElementById('kickModal').style.display = 'none';
                document.getElementById('equipRoleModal').style.display = 'none';
                document.getElementById('equipRoleMessage').textContent = '';
            }

            async confirmPromoteMember(event) {
                event.preventDefault();
                if (!this.selectedMemberId) {
                    return;
                }

                const authority = document.getElementById('newAuthoritySelect').value;
                try {
                    const response = await fetch('php/api.php?action=member/update-authority', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ memberId: this.selectedMemberId, authority })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        this.closeModals();
                        await this.loadGuildInfo();
                    } else {
                        alert('Unable to promote member: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error promoting member:', error);
                    alert('Failed to promote member.');
                }
            }

            async confirmKickMember() {
                if (!this.selectedMemberId) {
                    return;
                }

                try {
                    const response = await fetch('php/api.php?action=member/kick', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ memberId: this.selectedMemberId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        this.closeModals();
                        await this.loadGuildInfo();
                    } else {
                        alert('Unable to remove member: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error kicking member:', error);
                    alert('Failed to remove member.');
                }
            }

            async approveApplication(id) {
                // Find the application to check IGN
                const application = this.pendingApplications.find(app => app.id === id);
                if (!application || !application.ign || application.ign.trim() === '') {
                    alert('Cannot approve: applicant has not provided an IGN');
                    return;
                }

                try {
                    const response = await fetch('php/api.php?action=application/approve', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ applicationId: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        await this.loadGuildInfo();
                    } else {
                        alert('Unable to approve application: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error approving application:', error);
                    alert('Failed to approve application.');
                }
            }

            async rejectApplication(id) {
                try {
                    const response = await fetch('php/api.php?action=application/reject', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ applicationId: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        await this.loadGuildInfo();
                    } else {
                        alert('Unable to reject application: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error rejecting application:', error);
                    alert('Failed to reject application.');
                }
            }

            openProfile() {
                if (!this.currentMemberData || !this.currentGuildData) return;
                
                const currentUser = User.getCurrent();
                
                document.getElementById('profileGuildName').textContent = this.currentGuildData.name || 'Unknown';
                document.getElementById('profileUsername').textContent = currentUser?.username || 'Unknown';
                document.getElementById('profileIGN').textContent = this.currentMemberData.ign || 'Not set';
                document.getElementById('profileAuthority').textContent = this.currentMemberData.authority || this.currentMemberData.sub_guild || this.currentMemberData.subGuild || 'Member';
                
                document.getElementById('memberProfileModal').style.display = 'flex';
            }

            closeProfile() {
                document.getElementById('memberProfileModal').style.display = 'none';
                document.getElementById('ignEditForm').style.display = 'none';
                document.getElementById('ignDisplay').style.display = 'flex';
            }

            editIGN() {
                const currentIGN = this.currentMemberData.ign || '';
                document.getElementById('ignInput').value = currentIGN;
                document.getElementById('ignDisplay').style.display = 'none';
                document.getElementById('ignEditForm').style.display = 'block';
                document.getElementById('ignInput').focus();
            }

            cancelEditIGN() {
                document.getElementById('ignEditForm').style.display = 'none';
                document.getElementById('ignDisplay').style.display = 'flex';
            }

            async saveIGN() {
                const newIGN = document.getElementById('ignInput').value.trim();
                if (!newIGN) {
                    alert('IGN cannot be empty');
                    return;
                }

                try {
                    const response = await fetch('php/api.php?action=member/update-ign', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            memberId: this.currentMemberId,
                            ign: newIGN
                        })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        this.currentMemberData.ign = newIGN;
                        document.getElementById('profileIGN').textContent = newIGN;
                        document.getElementById('ignEditForm').style.display = 'none';
                        document.getElementById('ignDisplay').style.display = 'flex';
                        alert('IGN updated successfully!');
                    } else {
                        alert('Error updating IGN: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error updating IGN:', error);
                    alert('Error updating IGN');
                }
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function updateEquipRoleButtonText() {
            const select = document.getElementById('equipRoleSelect');
            const button = document.getElementById('equipRoleActionBtn');
            const selectedValue = select.value;

            if (!selectedValue) {
                button.textContent = 'Select Role';
                button.disabled = true;
            } else {
                const [action, roleId] = selectedValue.split('_');
                if (action === 'unequip') {
                    button.textContent = '✕ Remove Role';
                    button.classList.remove('promote-btn');
                    button.classList.add('remove-btn');
                } else {
                    button.textContent = '✔ Equip Role';
                    button.classList.remove('remove-btn');
                    button.classList.add('promote-btn');
                }
                button.disabled = false;
            }
        }

        function updatePromoteButtonText() {
            const select = document.getElementById('newAuthoritySelect');
            const button = document.getElementById('promoteActionBtn');
            const selectedValue = select.value;
            const memberCurrentAuthority = dashboard?.memberCurrentAuthority || 'member';

            // Check if demoting: current is sub leader and changing to member
            const isDemoting = memberCurrentAuthority === 'sub leader' && selectedValue.toLowerCase() === 'member';

            if (isDemoting) {
                button.textContent = 'Demote Member';
                button.classList.remove('promote-btn');
                button.classList.add('remove-btn');
            } else {
                button.textContent = 'Promote Member';
                button.classList.remove('remove-btn');
                button.classList.add('promote-btn');
            }
        }

        const dashboard = new GuildAdminDashboard();

        function getMemberById(memberId) {
            return dashboard.members.find(member => String(member.id) === String(memberId) || String(member.member_id) === String(memberId));
        }

        function toggleRoleExpansion(event, memberId) {
            event.stopPropagation();
            const button = event.target.closest('.expand-roles-btn');
            if (!button) return;
            const member = getMemberById(memberId);
            if (!member) return;

            document.getElementById('popoverMemberName').textContent = member.username || 'Unknown';
            document.getElementById('popoverMemberIGN').textContent = `IGN: ${member.ign || '—'}`;

            const rolesList = document.getElementById('popoverRolesList');
            if (!member.roles || member.roles.length === 0) {
                rolesList.innerHTML = '<div class="no-roles-message">No roles assigned</div>';
            } else {
                rolesList.innerHTML = member.roles.map(role => {
                    const roleName = role.name || role;
                    return `<span class="role-badge">${roleName}</span>`;
                }).join('');
            }

            const popover = document.getElementById('rolesPopover');
            popover.style.display = 'block';
            popover.style.position = 'fixed';
            popover.style.top = '50%';
            popover.style.left = '50%';
            popover.style.transform = 'translate(-50%, -50%)';

            setTimeout(() => {
                document.addEventListener('click', closePopoverOnOutsideClick);
            }, 0);
        }

        function closeRolesPopover() {
            const popover = document.getElementById('rolesPopover');
            if (!popover) return;
            popover.style.display = 'none';
            document.removeEventListener('click', closePopoverOnOutsideClick);
        }

        function closePopoverOnOutsideClick(event) {
            const popover = document.getElementById('rolesPopover');
            if (!popover) return;
            if (!popover.contains(event.target) && !event.target.closest('.expand-roles-btn')) {
                closeRolesPopover();
            }
        }

        async function switchTab(tabName) {
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tabName);
            });
            document.querySelectorAll('.tab-content').forEach(section => {
                section.classList.toggle('active', section.id === `${tabName}Tab`);
            });
            if (tabName === 'roles' && typeof dashboard?.loadRoles === 'function') {
                await dashboard.loadRoles();
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            initializeApp();
            await loadUserInfo();
            await requireLogin();
            dashboard.init();
        });
    </script>
</body>
</html>
