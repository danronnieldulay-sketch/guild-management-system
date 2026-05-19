<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Guilds - Guild Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header Bar -->
    <div class="guilds-header-bar">
        <button type="button" class="back-button-inline" onclick="navigateTo('dashboard.php')">
            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Dashboard
        </button>
        
        <h1 class="page-title">My Guilds</h1>
        
        <div class="user-actions">
            <button type="button" class="profile-button" onclick="openUserProfile()">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Profile
            </button>
            <button type="button" class="logout-button" onclick="handleLogout()">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="guilds-main-container">
        <!-- Empty State (shown when no guilds) -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <h2>No Guilds Yet</h2>
            <p>Create your first guild and start building your gaming empire</p>
            <button type="button" class="cta-button" onclick="navigateTo('create-guild.php')">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Create Your First Guild
            </button>
        </div>

        <!-- Guilds List (shown when guilds exist) -->
        <div id="guildsList" class="guilds-list-container" style="display: none;">
            <div class="guilds-list-header">
                <div>
                    <h2>Your Guilds</h2>
                    <p class="subtitle">Manage and oversee all your guild operations</p>
                </div>
                <button type="button" class="create-guild-button" onclick="navigateTo('create-guild.php')">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Create New Guild
                </button>
            </div>

            <div id="guildsGrid" class="guilds-grid"></div>
        </div>
    </div>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeUserProfile()" style="pointer-events: none;"></div>
        <div class="modal-content profile-modal" style="max-height: 90vh; display: flex; flex-direction: column; pointer-events: auto;">
            <div class="modal-header">
                <h2>User Profile</h2>
                <button type="button" class="close-modal-button" onclick="closeUserProfile()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="profile-content" style="overflow-y: auto; flex: 1; padding: 1.5rem;">
                <div class="profile-section">
                    <h3>Account Information</h3>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>User ID</label>
                            <p id="profileUserId">-</p>
                        </div>
                        <div class="profile-field">
                            <label>Username</label>
                            <p id="profileUsername">-</p>
                        </div>
                        <div class="profile-field">
                            <label>Account Created</label>
                            <p id="profileCreatedAt">-</p>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Change Password</h3>
                    <form id="changePasswordForm" onsubmit="handleChangePassword(event)">
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="currentPassword" required>
                        </div>
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="newPassword" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirmPassword" required minlength="6">
                        </div>
                        <div id="passwordError" class="error-message" style="display: none;"></div>
                        <div id="passwordSuccess" class="success-message" style="display: none;"></div>
                        <button type="submit" class="submit-button">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        async function loadMyGuilds() {
            if (!await requireLogin()) return;
            const user = User.getCurrent();
            if (!user) return;

            const response = await fetch('php/api.php?action=user/memberships', {
                credentials: 'same-origin'
            });
            const result = await response.json();
            const myGuilds = result.success ? result.data || [] : [];

            if (myGuilds.length === 0) {
                document.getElementById('emptyState').style.display = 'flex';
                document.getElementById('guildsList').style.display = 'none';
            } else {
                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('guildsList').style.display = 'block';
                displayMyGuilds(myGuilds);
            }
        }

        function displayMyGuilds(guilds) {
            const container = document.getElementById('guildsGrid');
            container.innerHTML = '';

            guilds.forEach(guild => {
                const card = document.createElement('div');
                card.className = 'my-guild-card';
                card.onclick = () => openGuildDashboard(guild.id, guild.name);

                card.innerHTML = `
                    <div class="guild-card-main">
                        <div class="guild-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div class="guild-details">
                            <h3>${guild.name}</h3>
                            <div class="game-badge-small">${guild.game || 'Unknown Game'}</div>
                            <p class="guild-desc">${guild.description}</p>
                        </div>
                    </div>

                    <div class="guild-stats-row">
                        <div class="stat-box">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <div>
                                <span class="stat-number">${guild.memberCount || 0}</span>
                                <span class="stat-label">Members</span>
                            </div>
                        </div>

                        <div class="stat-box">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <div>
                                <span class="stat-number">${guild.pendingCount || 0}</span>
                                <span class="stat-label">Pending</span>
                            </div>
                        </div>
                    </div>

                    <div class="guild-card-footer">
                        <button class="manage-button" type="button" onclick="openGuildDashboard('${guild.id}', '${guild.name}')">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            ${(guild.userAuthority === 'Leader' || guild.userAuthority === 'Sub leader') ? 'Manage Guild' : 'View Guild'}
                        </button>
                    </div>
                `;

                container.appendChild(card);
            });
        }

        function openGuildDashboard(guildId, guildName) {
            const user = User.getCurrent();
            if (!user) return;
            navigateTo(`admin-dashboard.php?guildId=${encodeURIComponent(guildId)}`);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            initializeApp();
            await loadUserInfo();
            await loadMyGuilds();
        });
    </script>
</body>
</html>
