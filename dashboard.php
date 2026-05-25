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
    <title>Dashboard - Guild Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header Bar -->
    <div class="dashboard-header-bar">
        <div class="header-bar-content">
            <div class="header-brand">
                <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                <div>
                    <h1>Guild Dashboard</h1>
                    <p class="header-welcome">Welcome back, <span id="dashboardUsername">User</span></p>
                </div>
            </div>
            <div class="header-actions">
                <button class="profile-button" onclick="dashboard.openUserProfile()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profile
                </button>
                <button class="logout-button" onclick="dashboard.handleLogout()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="dashboard-main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header-section">
            <div class="header-content">
                <h2 class="header-title">What would you like to do?</h2>
                <p class="header-subtitle">Manage your guild journey and connect with communities</p>
            </div>
            <div class="header-decoration"></div>
        </div>

        <div class="dashboard-grid">
            
            <!-- My Guilds Card -->
            <div class="dashboard-card guilds-card">
                <div class="card-header">
                    <div class="card-icon-wrapper purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="card-title-group">
                        <h2>My Guilds</h2>
                        <p>Manage your guilds</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="guild-stats">
                        <div class="stat-item">
                            <span class="stat-number" id="ownedGuildsCount">0</span>
                            <span class="stat-label">Guilds Owned</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number" id="joinedGuildsCount">0</span>
                            <span class="stat-label">Guilds Joined</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="card-action-button primary" onclick="navigateTo('my-guilds.php')">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 5l7 7-7 7"/>
                        </svg>
                        View All Guilds
                    </button>
                </div>
            </div>

            <!-- Join Guild Card -->
            <div class="dashboard-card join-card">
                <div class="card-header">
                    <div class="card-icon-wrapper purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6M23 11h-6"/>
                        </svg>
                    </div>
                    <div class="card-title-group">
                        <h2>Join Guild</h2>
                        <p>Find your perfect team</p>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-description">
                        Browse available guilds and apply to join. Find a community that matches your playstyle.
                    </p>
                </div>
                <div class="card-footer">
                    <button class="card-action-button primary" onclick="navigateTo('join-guild.php')">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <path d="M15 3h6v6M10 14L21 3"/>
                        </svg>
                        Browse Guilds
                    </button>
                </div>
            </div>

            <!-- Applications Card -->
            <div class="dashboard-card applications-card">
                <div class="card-header">
                    <div class="card-icon-wrapper purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                        </svg>
                    </div>
                    <div class="card-title-group">
                        <h2>My Applications</h2>
                        <p>Track your applications</p>
                    </div>
                </div>
                <div class="card-body">
                    <div id="appliedGuildsList" class="applied-guilds-list">
                        <p class="loading-text">Loading applications...</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="card-action-button primary" onclick="dashboard.viewApplications()">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        View All Applications
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="dashboard.closeUserProfile()" style="pointer-events: none;"></div>
        <div class="modal-content profile-modal" style="max-height: 90vh; display: flex; flex-direction: column; pointer-events: auto;">
            <div class="modal-header">
                <h2>User Profile</h2>
                <button class="close-modal-button" onclick="dashboard.closeUserProfile()">
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
                    <form id="changePasswordForm" onsubmit="dashboard.handleChangePassword(event)">
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

    <!-- Applications Modal -->
    <div id="applicationsModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="dashboard.closeApplicationsModal()"></div>
        <div class="modal-content applications-modal">
            <div class="modal-header">
                <h2>My Applications</h2>
                <button class="close-modal-button" onclick="dashboard.closeApplicationsModal()">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="applications-modal-content">
                <div id="applicationsListContainer" class="applications-list-container">
                    <p class="loading-text">Loading applications...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        class Dashboard {
            async init() {
                await this.loadDashboard();
                this.bindEvents();
            }

            async loadDashboard() {
                const response = await fetch("php/api.php?action=user/login-status", {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (!result.success) {
                    window.location.href = "login.php";
                    return;
                }
                const user = result.data;
                document.getElementById('dashboardUsername').textContent = user.username;
                this.currentUser = user;

                // Load all statistics
                await this.loadDashboardStats(user.id);
            }

            async loadDashboardStats(userId) {
                try {
                    // Get user's guilds/memberships
                    const guildsResponse = await fetch("php/api.php?action=user/memberships", {
                        credentials: 'same-origin'
                    });
                    const guildsResult = await guildsResponse.json();
                    const guilds = guildsResult.data || [];

                    // Get user's applications
                    const appsResponse = await fetch(`php/api.php?action=application/list&userId=${userId}`, {
                        credentials: 'same-origin'
                    });
                    const appsResult = await appsResponse.json();
                    const applications = appsResult.data || [];

                    // Update guild counts
                    const ownedGuilds = guilds.filter(g => g.userAuthority && (g.userAuthority === 'Leader' || g.userAuthority === 'Sub leader'));
                    const joinedGuilds = guilds.filter(g => g.userAuthority && g.userAuthority !== 'Leader' && g.userAuthority !== 'Sub leader');
                    
                    document.getElementById('ownedGuildsCount').textContent = ownedGuilds.length;
                    document.getElementById('joinedGuildsCount').textContent = joinedGuilds.length;

                    // Update applied guilds list
                    this.displayAppliedGuilds(applications);
                } catch (error) {
                    console.error('Error loading dashboard stats:', error);
                }
            }

            displayAppliedGuilds(applications) {
                const container = document.getElementById('appliedGuildsList');
                
                if (!applications || applications.length === 0) {
                    container.innerHTML = '<p class="no-applications">No applications yet. Browse guilds to get started!</p>';
                    return;
                }

                let html = '<div class="applied-guilds-items">';
                applications.slice(0, 3).forEach(app => {
                    const statusBadge = app.status === 'pending' 
                        ? '<span class="status-badge pending">Pending</span>'
                        : '<span class="status-badge approved">Approved</span>';
                    
                    html += `
                        <div class="applied-guild-item">
                            <div class="guild-info-mini">
                                <p class="guild-name">${app.guildName || 'Unknown Guild'}</p>
                            </div>
                            ${statusBadge}
                        </div>
                    `;
                });
                html += '</div>';
                
                if (applications.length > 3) {
                    html += `<p class="more-applications">+${applications.length - 3} more applications</p>`;
                }
                
                container.innerHTML = html;
            }

            viewApplications() {
                if (!this.currentUser) return;
                
                // Show modal
                document.getElementById('applicationsModal').style.display = 'flex';
                
                // Fetch and display applications
                this.loadApplicationsForModal();
            }

            async loadApplicationsForModal() {
                try {
                    const appsResponse = await fetch(`php/api.php?action=application/list&userId=${this.currentUser.id}`, {
                        credentials: 'same-origin'
                    });
                    const appsResult = await appsResponse.json();
                    const applications = appsResult.data || [];
                    
                    this.displayAllApplications(applications);
                } catch (error) {
                    console.error('Error loading applications:', error);
                    document.getElementById('applicationsListContainer').innerHTML = '<p class="no-applications">Error loading applications</p>';
                }
            }

            displayAllApplications(applications) {
                const container = document.getElementById('applicationsListContainer');
                
                if (!applications || applications.length === 0) {
                    container.innerHTML = '<p class="no-applications">No applications yet. Start by browsing available guilds!</p>';
                    return;
                }

                let html = '<div class="applications-grid">';
                applications.forEach(app => {
                    const statusClass = app.status === 'pending' ? 'pending' : 'approved';
                    const statusLabel = app.status === 'pending' ? 'Pending' : 'Approved';
                    
                    html += `
                        <div class="application-card ${statusClass}">
                            <div class="application-header">
                                <h3>${app.guildName || 'Unknown Guild'}</h3>
                                <span class="status-badge ${statusClass}">${statusLabel}</span>
                            </div>
                            <div class="application-body">
                                ${app.message ? `<p><strong>Message:</strong> ${app.message}</p>` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                container.innerHTML = html;
            }

            closeApplicationsModal() {
                document.getElementById('applicationsModal').style.display = 'none';
            }

            async handleLogout() {
                await fetch("php/api.php?action=user/logout", {
                    credentials: 'same-origin'
                });
                window.location.href = "login.php";
            }

            openUserProfile() {
                if (!this.currentUser) return;
                
                document.getElementById('profileUserId').textContent = this.currentUser.id;
                document.getElementById('profileUsername').textContent = this.currentUser.username;
                const created = new Date(this.currentUser.createdAt);
                document.getElementById('profileCreatedAt').textContent = !isNaN(created.getTime()) ? created.toLocaleDateString() : 'N/A';
                
                document.getElementById('userProfileModal').style.display = 'flex';
            }

            closeUserProfile() {
                document.getElementById('userProfileModal').style.display = 'none';
                document.getElementById('changePasswordForm').reset();
                document.getElementById('passwordError').style.display = 'none';
                document.getElementById('passwordSuccess').style.display = 'none';
            }

            async handleChangePassword(event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);
                const newPassword = formData.get('newPassword');
                const confirmPassword = formData.get('confirmPassword');
                
                const errorDiv = document.getElementById('passwordError');
                const successDiv = document.getElementById('passwordSuccess');
                errorDiv.style.display = 'none';
                successDiv.style.display = 'none';
                
                if (newPassword !== confirmPassword) {
                    errorDiv.textContent = 'New passwords do not match!';
                    errorDiv.style.display = 'block';
                    return;
                }
                
                const response = await fetch("php/api.php?action=user/change-password", {
                    method: "POST",
                    credentials: 'same-origin',
                    body: formData
                });
                const result = await response.json();
                
                if (!result.success) {
                    errorDiv.textContent = result.message;
                    errorDiv.style.display = 'block';
                } else {
                    successDiv.textContent = result.message;
                    successDiv.style.display = 'block';
                    form.reset();
                }
            }

            bindEvents() {
                const form = document.getElementById('changePasswordForm');
                if (form) form.addEventListener('submit', e => this.handleChangePassword(e));
            }
        }

        const dashboard = new Dashboard();
        document.addEventListener("DOMContentLoaded", () => dashboard.init());
    </script>
</body>
</html>
