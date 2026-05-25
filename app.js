/**
 * GuildHub - Cross-Game Guild Management System
 * Object-Oriented JavaScript Application
 * 
 * Architecture:
 * - User class: Account management, authentication
 * - Guild class: Guild creation, management
 * - GuildMember class: Member management
 * - Application class: Guild applications
 * - Chat class: Guild messaging
 * - DataStore class: LocalStorage abstraction
 */

// =====================================================
// DATA STORE - LocalStorage Abstraction Layer
// =====================================================
class DataStore {
    constructor() {
        this.keys = {
            USERS: 'guildhub_users',
            GUILDS: 'guildhub_guilds',
            MEMBERS: 'guildhub_members',
            APPLICATIONS: 'guildhub_applications',
            MESSAGES: 'guildhub_messages',
            CURRENT_USER: 'guildhub_current_user'
        };
    }

    get(key) {
        const data = localStorage.getItem(this.keys[key] || key);
        return data ? JSON.parse(data) : null;
    }

    set(key, data) {
        localStorage.setItem(this.keys[key] || key, JSON.stringify(data));
    }

    initialize() {
        if (!this.get('GUILDS')) {
            const sampleGuilds = [
                {id: '1', name: 'Dragon Slayers', game: 'World of Warcraft', description: 'Elite PvE guild focused on endgame content and boss raids', requirements: 'Min Level 80, Active daily', leaderId: 'leader1', leaderIGN: 'DragonKing', memberCount: 45},
                {id: '2', name: 'Shadow Legends', game: 'League of Legends', description: 'Competitive PvP guild with focus on arena and guild wars', requirements: 'Min Level 75, PvP experience required', leaderId: 'leader2', leaderIGN: 'ShadowMaster', memberCount: 38},
                {id: '3', name: 'Phoenix Rising', game: 'Genshin Impact', description: 'Casual friendly guild welcoming all players', requirements: 'Active participation, friendly attitude', leaderId: 'leader3', leaderIGN: 'PhoenixLeader', memberCount: 52}
            ];
            this.set('GUILDS', sampleGuilds);
        }
        if (!this.get('MEMBERS')) this.set('MEMBERS', []);
        if (!this.get('APPLICATIONS')) this.set('APPLICATIONS', []);
        if (!this.get('MESSAGES')) this.set('MESSAGES', []);
        if (!this.get('USERS')) this.set('USERS', []);
    }
}

const dataStore = new DataStore();

// =====================================================
// USER CLASS - Account Management
// =====================================================
class User {
    constructor(data = {}) {
        this.id = data.id || null;
        this.username = data.username || '';
        this.password = data.password || '';
        this.createdAt = data.createdAt || null;
    }

    static create(username, password) {
        const users = dataStore.get('USERS') || [];
        if (users.find(u => u.username === username)) {
            throw new Error('Username already exists');
        }
        const newUser = new User({
            id: Date.now().toString(),
            username, password,
            createdAt: new Date().toISOString()
        });
        users.push(newUser);
        dataStore.set('USERS', users);
        return newUser;
    }

    static authenticate(username, password) {
        const users = dataStore.get('USERS') || [];
        const user = users.find(u => u.username === username && u.password === password);
        if (!user) throw new Error('Invalid username or password');
        return new User(user);
    }

    static getCurrent() {
        const userData = dataStore.get('CURRENT_USER');
        return userData ? new User(userData) : null;
    }

    static async syncFromServer() {
        try {
            const response = await fetch('php/api.php?action=user/login-status', {
                credentials: 'same-origin'
            });
            if (!response.ok) return null;
            const result = await response.json();
            if (result.success && result.data) {
                const user = new User(result.data);
                user.setCurrent();
                return user;
            }
        } catch (error) {
            console.warn('User session sync failed:', error);
        }
        return null;
    }

    setCurrent() {
        dataStore.set('CURRENT_USER', this);
    }

    static logout() {
        localStorage.removeItem('guildhub_current_user');
    }

    toJSON() {
        return { id: this.id, username: this.username, createdAt: this.createdAt };
    }
}

// =====================================================
// GUILD CLASS - Guild Management
// =====================================================
class Guild {
    constructor(data = {}) {
        Object.assign(this, data);
        this.memberCount = data.memberCount || 1;
        this.maxMembers = data.maxMembers || 50;
    }

    static create(leader, guildData) {
        const guilds = dataStore.get('GUILDS') || [];
        if (guilds.find(g => g.name.toLowerCase() === guildData.name.toLowerCase())) {
            throw new Error('Guild name already exists');
        }
        const newGuild = new Guild({
            id: Date.now().toString(),
            ...guildData,
            leaderId: leader.id,
            leaderIGN: leader.ign,
            leaderUsername: leader.username,
            memberCount: 1,
            createdAt: new Date().toISOString()
        });
        guilds.push(newGuild);
        dataStore.set('GUILDS', guilds);
        GuildMember.create(newGuild.id, leader, 'leader');
        Chat.addMessage(newGuild.id, { user: 'System', text: `Welcome to ${newGuild.name}! This is your guild chat.` });
        return newGuild;
    }

    static getById(id) {
        const guilds = dataStore.get('GUILDS') || [];
        return guilds.find(g => g.id === id);
    }

    static getAll() {
        return dataStore.get('GUILDS') || [];
    }

    static getByLeader(leaderId) {
        const guilds = dataStore.get('GUILDS') || [];
        return guilds.filter(g => g.leaderId === leaderId);
    }

    static search(query, gameFilter = '') {
        let guilds = dataStore.get('GUILDS') || [];
        if (query) {
            const lowerQuery = query.toLowerCase();
            guilds = guilds.filter(g => g.name.toLowerCase().includes(lowerQuery) || g.description.toLowerCase().includes(lowerQuery));
        }
        if (gameFilter) {
            guilds = guilds.filter(g => g.game === gameFilter);
        }
        return guilds;
    }
}

// =====================================================
// GUILD MEMBER CLASS - Member Management
// =====================================================
class GuildMember {
    constructor(data = {}) {
        Object.assign(this, data);
    }

    static create(guildId, user, role = 'member', ign = null) {
        const members = dataStore.get('MEMBERS') || [];
        if (members.find(m => m.guildId === guildId && m.userId === user.id)) {
            throw new Error('Already a member of this guild');
        }
        const newMember = new GuildMember({
            id: Date.now().toString(),
            guildId, userId: user.id,
            username: user.username,
            ign: ign || user.ign || null,
            role,
            authority: '',
            status: 'approved',
            joinedAt: new Date().toISOString()
        });
        members.push(newMember);
        dataStore.set('MEMBERS', members);
        return newMember;
    }

    static getByGuild(guildId) {
        const members = dataStore.get('MEMBERS') || [];
        return members.filter(m => m.guildId === guildId && m.status === 'approved');
    }

    static getByUser(userId) {
        const members = dataStore.get('MEMBERS') || [];
        const guilds = dataStore.get('GUILDS') || [];
        const userGuildIds = members.filter(m => m.userId === userId && m.status === 'approved').map(m => m.guildId);
        return guilds.filter(g => userGuildIds.includes(g.id));
    }

    static remove(memberId) {
        const members = dataStore.get('MEMBERS') || [];
        const filtered = members.filter(m => m.id !== memberId);
        dataStore.set('MEMBERS', filtered);
        return true;
    }
}

// =====================================================
// APPLICATION CLASS - Guild Applications
// =====================================================
class Application {
    constructor(data = {}) {
        Object.assign(this, data);
    }

    static apply(guildId, user, message = '', ign = null) {
        const applications = dataStore.get('APPLICATIONS') || [];
        if (applications.find(a => a.guildId === guildId && a.userId === user.id && a.status === 'pending')) {
            throw new Error('Already applied to this guild');
        }
        const members = dataStore.get('MEMBERS') || [];
        if (members.find(m => m.guildId === guildId && m.userId === user.id)) {
            throw new Error('Already a member of this guild');
        }
        const newApplication = new Application({
            id: Date.now().toString(),
            guildId, userId: user.id,
            username: user.username,
            ign: ign || user.ign || null,
            message, status: 'pending',
            appliedAt: new Date().toISOString()
        });
        applications.push(newApplication);
        dataStore.set('APPLICATIONS', applications);
        return newApplication;
    }

    static getByGuild(guildId, status = null) {
        const applications = dataStore.get('APPLICATIONS') || [];
        let result = applications.filter(a => a.guildId === guildId);
        if (status) result = result.filter(a => a.status === status);
        return result;
    }

    static getByUser(userId) {
        const applications = dataStore.get('APPLICATIONS') || [];
        return applications.filter(a => a.userId === userId);
    }

    approve(reviewedBy) {
        this.status = 'approved';
        this.reviewedAt = new Date().toISOString();
        this.reviewedBy = reviewedBy;
        const applications = dataStore.get('APPLICATIONS') || [];
        const index = applications.findIndex(a => a.id === this.id);
        if (index !== -1) { applications[index] = this; dataStore.set('APPLICATIONS', applications); }
        GuildMember.create(this.guildId, { id: this.userId, username: this.username, ign: this.ign }, 'member');
        return this;
    }

    reject(reviewedBy) {
        this.status = 'rejected';
        this.reviewedAt = new Date().toISOString();
        this.reviewedBy = reviewedBy;
        const applications = dataStore.get('APPLICATIONS') || [];
        const index = applications.findIndex(a => a.id === this.id);
        if (index !== -1) { applications[index] = this; dataStore.set('APPLICATIONS', applications); }
        return this;
    }
}

// =====================================================
// CHAT CLASS - Guild Messaging
// =====================================================
class Chat {
    constructor(data = {}) {
        Object.assign(this, data);
    }

    static addMessage(guildId, messageData) {
        const messages = dataStore.get('MESSAGES') || [];
        const newMessage = new Chat({
            id: Date.now().toString(),
            guildId,
            user: messageData.user,
            userId: messageData.userId,
            text: messageData.text,
            timestamp: new Date().toISOString()
        });
        messages.push(newMessage);
        dataStore.set('MESSAGES', messages);
        return newMessage;
    }

    static getByGuild(guildId, limit = 100) {
        const messages = dataStore.get('MESSAGES') || [];
        return messages.filter(m => m.guildId === guildId).sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp)).slice(-limit);
    }

    static send(guildId, user, text) {
        return Chat.addMessage(guildId, { user: user.username, userId: user.id, text });
    }
}

// =====================================================
// NAVIGATION & UTILITIES
// =====================================================
function navigateTo(page) { window.location.href = page; }
function scrollToFeatures() { document.getElementById('features')?.scrollIntoView({ behavior: 'smooth' }); }

async function handleLogout() {
    await fetch('php/api.php?action=user/logout', {
        credentials: 'same-origin'
    });
    User.logout();
    navigateTo('login.php');
}

function handleCreateGuild(event) {
    event.preventDefault();
    const user = User.getCurrent();
    if (!user) { alert('Please login first!'); navigateTo('login.php'); return; }
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const guildData = {
            name: formData.get('guildName'),
            game: formData.get('game'),
            description: formData.get('description'),
            requirements: formData.get('requirements')
        };
        Guild.create(user, guildData);
        navigateTo('my-guilds.php');
    } catch (error) {
        alert(error.message);
    }
}

function handleApplyToGuild(guildId) {
    const user = User.getCurrent();
    if (!user) { navigateTo('login.php'); return; }
    
    const modal = document.getElementById('applicationModal');
    if (modal) {
        const guild = Guild.getById(guildId);
        document.getElementById('selectedGuildId').value = guildId;
        document.getElementById('modalGuildName').textContent = `Apply to ${guild?.name || 'Guild'}`;
        document.getElementById('appUserId').value = user.id;
        document.getElementById('appUsername').value = user.username;
        document.getElementById('appIGN').value = user.ign || '';
        modal.style.display = 'flex';
    }
}

async function handleApplication(event) {
    event.preventDefault();
    if (!await requireLogin()) return;

    const form = event.target;
    const formData = new FormData(form);
    const guildId = formData.get('guildId');
    const playerOverview = formData.get('playerOverview');
    const ign = formData.get('playerIGN');

    try {
        const response = await fetch('php/api.php?action=application/submit', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ guildId, message: playerOverview, ign })
        });
        const result = await response.json();
        if (!result.success) {
            alert(result.message || 'Unable to submit application.');
            return;
        }

        alert(result.message || 'Application submitted successfully!');
        form.reset();
        closeModal();
        await loadAvailableGuilds();
    } catch (error) {
        console.error('Error submitting application:', error);
        alert('Failed to submit application. Please try again later.');
    }
}

function closeModal() {
    const modal = document.getElementById('applicationModal');
    if (modal) modal.style.display = 'none';
}

function handleApproveApplication(applicationId) {
    const user = User.getCurrent();
    if (!user) return;
    
    const applications = dataStore.get('APPLICATIONS') || [];
    const application = applications.find(a => a.id === applicationId);
    if (application) {
        const app = new Application(application);
        app.approve(user.id);
        loadPendingApplications();
    }
}

function handleRejectApplication(applicationId) {
    const user = User.getCurrent();
    if (!user) return;
    
    const applications = dataStore.get('APPLICATIONS') || [];
    const application = applications.find(a => a.id === applicationId);
    if (application) {
        const app = new Application(application);
        app.reject(user.id);
        loadPendingApplications();
    }
}

function handleSendMessage(event) {
    event.preventDefault();
    const user = User.getCurrent();
    if (!user) return;
    
    const guildId = localStorage.getItem('currentGuildId');
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    
    if (text && guildId) {
        Chat.send(guildId, user, text);
        input.value = '';
        loadChatMessages();
    }
}

function handleChangePassword(event) {
    event.preventDefault();
    const user = User.getCurrent();
    if (!user) return;
    
    const form = event.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('passwordError');
    const successDiv = document.getElementById('passwordSuccess');
    
    const users = dataStore.get('USERS') || [];
    const index = users.findIndex(u => u.id === user.id);
    
    if (users[index].password !== formData.get('currentPassword')) {
        errorDiv.textContent = 'Current password is incorrect';
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
        return;
    }
    
    users[index].password = formData.get('newPassword');
    dataStore.set('USERS', users);
    user.password = formData.get('newPassword');
    user.setCurrent();
    
    successDiv.textContent = 'Password updated successfully!';
    successDiv.style.display = 'block';
    errorDiv.style.display = 'none';
    form.reset();
}

// =====================================================
// PAGE INITIALIZATION
// =====================================================
async function initializeApp() {
    dataStore.initialize();
    await User.syncFromServer();
}

async function getEffectiveUser() {
    const serverUser = await User.syncFromServer();
    if (serverUser) return serverUser;
    return User.getCurrent();
}

async function requireLogin() {
    const user = await getEffectiveUser();
    if (!user) { navigateTo('login.php'); return false; }
    return true;
}

async function loadUserInfo() {
    const user = await getEffectiveUser();
    if (user) {
        document.querySelectorAll('#headerUsername, #profileUsername').forEach(el => { if (el) el.textContent = user.username; });
    }
}

async function fetchServerGuilds() {
    try {
        const response = await fetch('php/api.php?action=guild/list', { credentials: 'same-origin' });
        if (!response.ok) return null;
        const result = await response.json();
        return result.success ? result.data || [] : null;
    } catch (error) {
        return null;
    }
}

async function fetchUserMemberships() {
    try {
        const response = await fetch('php/api.php?action=user/memberships', { credentials: 'same-origin' });
        if (!response.ok) return null;
        const result = await response.json();
        return result.success ? result.data || [] : null;
    } catch (error) {
        return null;
    }
}

async function fetchUserApplications() {
    try {
        const response = await fetch('php/api.php?action=user/applications', { credentials: 'same-origin' });
        if (!response.ok) return null;
        const result = await response.json();
        return result.success ? result.data || [] : null;
    } catch (error) {
        return null;
    }
}

function formatCreatedAt(dateValue) {
    if (!dateValue) return 'N/A';
    const date = new Date(dateValue);
    return isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString();
}

function openUserProfile() {
    const user = User.getCurrent();
    if (!user) return;
    
    document.getElementById('profileUserId').textContent = user.id;
    document.getElementById('profileUsername').textContent = user.username;
    document.getElementById('profileCreatedAt').textContent = formatCreatedAt(user.createdAt);
    document.getElementById('userProfileModal').style.display = 'flex';
}

function closeUserProfile() { document.getElementById('userProfileModal').style.display = 'none'; }

// =====================================================
// DASHBOARD FUNCTIONS
// =====================================================
async function loadDashboard() {
    if (!await requireLogin()) return;
    const user = User.getCurrent();
    document.getElementById('welcomeUser').textContent = user.username;
    const userGuilds = GuildMember.getByUser(user.id);
    displayJoinedGuilds(userGuilds);
}

function displayJoinedGuilds(guilds) {
    const container = document.getElementById('joinedGuildsList');
    if (!container) return;
    
    if (guilds.length === 0) {
        container.innerHTML = '<p class="text-secondary">You haven\'t joined any guilds yet.</p>';
        return;
    }
    
    container.innerHTML = guilds.map(guild => `
        <div class="guild-preview-card" onclick="openGuildDashboard('${guild.id}', '${guild.name}')">
            <div class="guild-preview-header">
                <div class="guild-preview-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="guild-preview-info">
                    <h4>${guild.name}</h4>
                    <span class="game-tag">${guild.game}</span>
                </div>
            </div>
            <div class="guild-preview-stats">
                <div class="guild-preview-stat"><span class="count">${guild.memberCount}</span><span class="label">Members</span></div>
            </div>
        </div>
    `).join('');
}

function openGuildDashboard(guildId, guildName) {
    navigateTo(`admin-dashboard.php?guildId=${encodeURIComponent(guildId)}`);
}

// =====================================================
// MY GUILDS PAGE
// =====================================================
async function loadMyGuilds() {
    if (!await requireLogin()) return;
    const user = User.getCurrent();
    document.getElementById('headerUsername').textContent = user.username;
    const myGuilds = Guild.getByLeader(user.id);
    
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
        const members = GuildMember.getByGuild(guild.id);
        const pendingApps = Application.getByGuild(guild.id, 'pending');
        
        const card = document.createElement('div');
        card.className = 'my-guild-card';
        card.onclick = () => openGuildDashboard(guild.id, guild.name);
        
        card.innerHTML = `
            <div class="guild-card-main">
                <div class="guild-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="guild-details">
                    <h3>${guild.name}</h3>
                    <span class="game-badge-small">${guild.game}</span>
                    <p class="guild-desc">${guild.description}</p>
                </div>
            </div>
            <div class="guild-stats-row">
                <div class="stat-box">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <div><span class="stat-number">${members.length}</span><span class="stat-label">Members</span></div>
                </div>
                <div class="stat-box">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <div><span class="stat-number">${pendingApps.length}</span><span class="stat-label">Pending</span></div>
                </div>
            </div>
            <div class="guild-card-footer">
                <button class="manage-button">
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Manage Guild
                </button>
            </div>
        `;
        container.appendChild(card);
    });
}

// =====================================================
// JOIN GUILD PAGE
// =====================================================
async function loadJoinGuild() {
    if (!await requireLogin()) return;
    const user = User.getCurrent();
    if (!user) {
        navigateTo('login.php');
        return;
    }
    
    const headerUsername = document.getElementById('headerUsername');
    if (headerUsername) {
        headerUsername.textContent = user.username;
    }
    
    // Populate game filter with available games
    await populateGameFilter();
    
    // Initialize search suggestions
    await initSearchSuggestions();
    
    // Set dropdown to 'available' by default
    const filterDropdown = document.getElementById('guildFilterDropdown');
    if (filterDropdown) {
        filterDropdown.value = 'available';
    }
    
    // Trigger the filter change to load guilds
    setTimeout(() => {
        handleGuildFilterChange();
    }, 100);
}

async function populateGameFilter() {
    const gameFilter = document.getElementById('gameFilter');
    if (!gameFilter) return;

    let guilds = await fetchServerGuilds();
    if (!guilds) guilds = Guild.getAll() || [];

    // Get unique games from guilds, limit to 10
    const uniqueGames = [...new Set(guilds.map(g => g.game).filter(game => game && game !== 'Unknown Game'))];
    const limitedGames = uniqueGames.slice(0, 10);

    // Keep "All Games" option and update the rest
    gameFilter.innerHTML = '<option value="">All Games</option>';
    limitedGames.forEach(game => {
        const option = document.createElement('option');
        option.value = game;
        option.textContent = game;
        gameFilter.appendChild(option);
    });
}

async function initSearchSuggestions() {
    const searchInput = document.getElementById('searchInput');
    const searchInputWrapper = searchInput?.parentElement;
    
    if (!searchInput || !searchInputWrapper) return;

    // Create suggestions container
    let suggestionsContainer = document.getElementById('searchSuggestions');
    if (!suggestionsContainer) {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = 'searchSuggestions';
        suggestionsContainer.className = 'search-suggestions';
        searchInputWrapper.appendChild(suggestionsContainer);
    }

    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value.toLowerCase().trim();
        
        // Always filter the main grid
        await handleGuildFilterChange();

        if (query.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        let guilds = await fetchServerGuilds();
        if (!guilds) guilds = Guild.getAll() || [];

        // Filter guilds based on search query for suggestions
        const suggestions = guilds
            .filter(g => g.name.toLowerCase().includes(query) || g.description.toLowerCase().includes(query))
            .slice(0, 10);

        if (suggestions.length === 0) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        // Display suggestions
        suggestionsContainer.innerHTML = suggestions.map(guild => `
            <div class="search-suggestion-item" onclick="selectGuildSuggestion('${guild.id}', '${guild.name}', '${query}')">
                <div class="suggestion-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div class="suggestion-info">
                    <div class="suggestion-name">${guild.name}</div>
                    <div class="suggestion-game">${guild.game}</div>
                </div>
            </div>
        `).join('');
        suggestionsContainer.style.display = 'block';
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInputWrapper.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

function selectGuildSuggestion(guildId, guildName, searchQuery) {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = guildName;
    }
    
    const suggestionsContainer = document.getElementById('searchSuggestions');
    if (suggestionsContainer) {
        suggestionsContainer.style.display = 'none';
    }
    
    handleGuildFilterChange();
}

async function handleGuildFilterChange() {
    const filterDropdown = document.getElementById('guildFilterDropdown');
    const searchInput = document.getElementById('searchInput');
    const gameFilter = document.getElementById('gameFilter');
    
    const filterValue = filterDropdown?.value || 'available';
    const searchQuery = searchInput?.value || '';
    const gameValue = gameFilter?.value || '';
    
    const user = User.getCurrent();
    if (!user) return;

    const response = await fetch('php/api.php?action=user/memberships', {
        credentials: 'same-origin'
    });
    const result = await response.json();
    const memberGuilds = result.success ? result.data || [] : [];

    let guilds = await fetchServerGuilds();
    if (!guilds) guilds = Guild.getAll() || [];

    const applications = await fetchUserApplications() || Application.getByUser(user.id);
    const joinedGuildIds = memberGuilds.map(g => String(g.id));
    const pendingApplications = applications.filter(a => a.status === 'pending');
    const pendingGuildIds = pendingApplications.map(a => String(a.guild_id || a.guildId));

    // Apply search and game filters
    if (searchQuery) {
        const lowerQuery = searchQuery.toLowerCase();
        guilds = guilds.filter(g => 
            g.name.toLowerCase().includes(lowerQuery) || 
            g.description.toLowerCase().includes(lowerQuery)
        );
    }
    
    if (gameValue) {
        guilds = guilds.filter(g => g.game === gameValue);
    }

    let filteredGuilds = [];

    if (filterValue === 'joined') {
        // Show guilds user has joined
        filteredGuilds = guilds.filter(g => joinedGuildIds.includes(String(g.id)));
        await displayJoinedGuilds(filteredGuilds);
    } else if (filterValue === 'pending') {
        // Show guilds with pending applications
        filteredGuilds = guilds.filter(g => pendingGuildIds.includes(String(g.id)));
        await displayPendingApplications(filteredGuilds, applications);
    } else {
        // Show available guilds to apply
        filteredGuilds = guilds.filter(g => 
            String(g.leader_id) !== String(user.id) && 
            !joinedGuildIds.includes(String(g.id))
        );
        await displayAvailableGuilds(filteredGuilds, applications);
    }

    // Update header title
    const headerDiv = document.querySelector('.guilds-list-header > div');
    if (headerDiv) {
        if (filterValue === 'joined') {
            headerDiv.innerHTML = `
                <h2>Your Joined Guilds</h2>
                <p class="subtitle">Guilds you're already a member of</p>
            `;
        } else if (filterValue === 'pending') {
            headerDiv.innerHTML = `
                <h2>Pending Applications</h2>
                <p class="subtitle">Guilds awaiting your application response</p>
            `;
        } else {
            headerDiv.innerHTML = `
                <h2>Available Guilds</h2>
                <p class="subtitle">Choose a guild that matches your playstyle</p>
            `;
        }
    }
}

async function displayAvailableGuilds(guilds, applications = []) {
    const container = document.getElementById('availableGuildsGrid');
    if (!container) return;

    if (!applications || !Array.isArray(applications)) {
        applications = Application.getByUser(User.getCurrent().id);
    }
    
    if (guilds.length === 0) {
        container.innerHTML = '<p class="text-secondary">No guilds available to join.</p>';
        return;
    }
    
    container.innerHTML = guilds.map(guild => {
        const hasApplied = applications.find(a => String(a.guild_id || a.guildId) === String(guild.id) && a.status === 'pending');
        const leaderName = guild.leader_ign || guild.leaderIGN || 'Unknown';
        const memberCount = guild.memberCount ?? guild.member_count ?? 0;
        const pendingCount = guild.pendingCount ?? guild.pending_count ?? 0;
        
        return `
            <div class="my-guild-card">
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
                            <span class="stat-number">${memberCount}</span>
                            <span class="stat-label">Members</span>
                        </div>
                    </div>

                    <div class="stat-box">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <div>
                            <span class="stat-number">${pendingCount}</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                </div>

                <div class="guild-card-footer">
                    ${hasApplied ? 
                        `<div class="applied-badge"><svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>Application Pending</div>` :
                        `<button class="apply-button manage-button" type="button" onclick="handleApplyToGuild('${guild.id}')">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Apply to Join
                        </button>`
                    }
                </div>
            </div>
        `;
    }).join('');
}

async function displayJoinedGuilds(guilds) {
    const container = document.getElementById('availableGuildsGrid');
    if (!container) return;

    if (guilds.length === 0) {
        container.innerHTML = '<p class="text-secondary">You haven\'t joined any guilds yet.</p>';
        return;
    }
    
    container.innerHTML = guilds.map(guild => {
        const memberCount = guild.memberCount ?? guild.member_count ?? 0;
        const pendingCount = guild.pendingCount ?? guild.pending_count ?? 0;
        
        return `
            <div class="my-guild-card" onclick="openGuildDashboard('${guild.id}', '${guild.name}')">
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
                            <span class="stat-number">${memberCount}</span>
                            <span class="stat-label">Members</span>
                        </div>
                    </div>

                    <div class="stat-box">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <div>
                            <span class="stat-number">${pendingCount}</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                </div>

                <div class="guild-card-footer">
                    <button class="manage-button" type="button" onclick="event.stopPropagation(); openGuildDashboard('${guild.id}', '${guild.name}')">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        View Guild
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

async function displayPendingApplications(guilds, applications = []) {
    const container = document.getElementById('availableGuildsGrid');
    if (!container) return;

    if (guilds.length === 0) {
        container.innerHTML = '<p class="text-secondary">No pending applications.</p>';
        return;
    }
    
    container.innerHTML = guilds.map(guild => {
        const memberCount = guild.memberCount ?? guild.member_count ?? 0;
        const pendingCount = guild.pendingCount ?? guild.pending_count ?? 0;
        const pendingApp = applications.find(a => String(a.guild_id || a.guildId) === String(guild.id) && a.status === 'pending');
        
        return `
            <div class="my-guild-card">
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
                            <span class="stat-number">${memberCount}</span>
                            <span class="stat-label">Members</span>
                        </div>
                    </div>

                    <div class="stat-box">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <div>
                            <span class="stat-number">${pendingCount}</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                </div>

                <div class="guild-card-footer">
                    <div class="applied-badge">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                        Application Pending
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// =====================================================
// ADMIN DASHBOARD FUNCTIONS
// =====================================================
function loadAdminDashboard() {
    if (!requireLogin()) return;
    const guildId = localStorage.getItem('currentGuildId');
    const guildName = localStorage.getItem('currentGuildName');
    if (!guildId) { navigateTo('dashboard.php'); return; }
    
    const guild = Guild.getById(guildId);
    if (!guild) { navigateTo('dashboard.php'); return; }
    
    const user = User.getCurrent();
    if (guild.leaderId !== user.id) { navigateTo('dashboard.php'); return; }
    
    document.getElementById('guildName').textContent = guild.name;
    document.getElementById('leaderName').textContent = `Guild Leader: ${guild.leaderIGN}`;
    loadGuildMembers();
    loadPendingApplications();
    loadChatMessages();
}

function loadGuildMembers() {
    const guildId = localStorage.getItem('currentGuildId');
    const members = GuildMember.getByGuild(guildId);
    const container = document.getElementById('membersTableBody');
    if (!container) return;
    
    document.getElementById('memberCount').textContent = members.length;
    
    if (members.length === 0) { container.innerHTML = '<tr><td colspan="7">No members yet</td></tr>'; return; }
    
    container.innerHTML = members.map(member => `
        <tr>
            <td class="member-ign">${member.ign}</td>
            <td class="member-username">${member.username}</td>
            <td><span class="member-role ${member.role}">${member.role}</span></td>
            <td><span class="member-status ${member.status}"><span class="status-dot"></span>${member.status}</span></td>
            <td>${member.authority || member.subGuild || '-'}</td>
            <td class="action-buttons">
                <button class="action-btn" onclick="alert('Edit feature coming soon')">Edit</button>
                <button class="action-btn danger" onclick="removeMember('${member.id}')">Remove</button>
            </td>
        </tr>
    `).join('');
}

function loadPendingApplications() {
    const guildId = localStorage.getItem('currentGuildId');
    const applications = Application.getByGuild(guildId, 'pending');
    const container = document.getElementById('pendingApplications');
    if (!container) return;
    
    document.getElementById('pendingCount').textContent = applications.length;
    const badge = document.getElementById('pendingBadge');
    if (badge) { badge.style.display = applications.length > 0 ? 'inline-flex' : 'none'; badge.textContent = applications.length; }
    
    if (applications.length === 0) { container.innerHTML = '<p class="text-secondary">No pending applications</p>'; return; }
    
    container.innerHTML = applications.map(app => `
        <div class="pending-app-card">
            <div class="pending-app-info">
                <div class="pending-app-avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="pending-app-details">
                    <h4>${app.ign}</h4>
                    <p>@${app.username}</p>
                </div>
            </div>
            <div class="pending-app-actions">
                <button class="approve-btn" onclick="handleApproveApplication('${app.id}')">Approve</button>
                <button class="reject-btn" onclick="handleRejectApplication('${app.id}')">Reject</button>
            </div>
        </div>
    `).join('');
}

function loadChatMessages() {
    const guildId = localStorage.getItem('currentGuildId');
    const messages = Chat.getByGuild(guildId);
    const container = document.getElementById('chatMessages');
    if (!container) return;
    
    container.innerHTML = messages.map(msg => `
        <div class="chat-message">
            <div class="chat-message-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="chat-message-content">
                <div class="chat-message-header">
                    <span class="chat-message-author">${msg.user}</span>
                    <span class="chat-message-time">${new Date(msg.timestamp).toLocaleTimeString()}</span>
                </div>
                <p class="chat-message-text">${msg.text}</p>
            </div>
        </div>
    `).join('');
    container.scrollTop = container.scrollHeight;
}

function removeMember(memberId) {
    if (confirm('Are you sure you want to remove this member?')) {
        GuildMember.remove(memberId);
        loadGuildMembers();
    }
}

// =====================================================
// TAB SWITCHING
// =====================================================
function switchTab(tabName) {
    document.querySelectorAll('.tab-button').forEach(btn => { btn.classList.remove('active'); });
    document.querySelectorAll('.tab-content').forEach(content => { content.classList.remove('active'); });
    document.getElementById(tabName + 'Tab')?.classList.add('active');
    document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => { dataStore.initialize(); });

