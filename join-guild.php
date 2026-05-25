<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Guild</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- User Header Bar -->
    <div class="user-header-bar">
        <button type="button" class="back-button-inline" onclick="navigateTo('dashboard.php')">
            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </button>
        
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

    <div class="container">
        <main class="join-guild-main">
            <section class="join-guild-header">
                <h2>Available Guilds</h2>
                <p class="subtitle">Choose a guild that matches your playstyle</p>
            </section>

            <section class="available-guilds-section">
                <div class="guilds-list-header">
                    <div>
                        <h2>Available Guilds</h2>
                        <p class="subtitle">Choose a guild that matches your playstyle</p>
                    </div>
                    <select id="guildFilterDropdown" class="filter-select guild-view-filter" onchange="handleGuildFilterChange()">
                        <option value="available">Guilds to Apply</option>
                        <option value="joined">Your Joined Guilds</option>
                        <option value="pending">Pending Applications</option>
                    </select>
                </div>

                <div class="search-filter-bar">
                    <div class="search-input-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search guilds..." autocomplete="off" oninput="handleGuildFilterChange()">
                    </div>
                    <select id="gameFilter" class="filter-select" onchange="handleGuildFilterChange()">
                        <option value="">All Games</option>
                    </select>
                </div>

                <div id="availableGuildsGrid" class="guilds-grid"></div>
            </section>
        </main>
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

    <!-- Application Modal -->
    <div id="applicationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <button class="back-button" onclick="closeModal()">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Guilds
            </button>

            <div class="form-header">
                <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
                <h2 id="modalGuildName">Apply to Guild</h2>
                <p class="subtitle">Fill in your details to join</p>
            </div>

            <form id="applicationForm" onsubmit="handleApplication(event)">
                <input type="hidden" name="guildId" id="selectedGuildId">
                <input type="hidden" name="userId" id="appUserId">
                <input type="hidden" name="username" id="appUsername">
                <div class="form-group">
                    <label>IGN (In-Game Name) *</label>
                    <input type="text" name="playerIGN" id="appIGN" required>
                </div>
                <div class="form-group">
                    <label>Player Overview *</label>
                    <textarea name="playerOverview" required placeholder="Tell us about your experience, playstyle, and why you want to join this guild" rows="5"></textarea>
                </div>
                <button type="submit" class="submit-button">Submit Application</button>
            </form>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            initializeApp();
            await loadUserInfo();
            await loadJoinGuild();
        });
    </script>
</body>
</html>
