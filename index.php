<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guild Management System - Unite Your Team</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Hero Section -->
    <div class="landing-hero">
        <div class="landing-container">
            <div class="hero-content">
                <h1 class="hero-title">Cross-Game Guild Management</h1>
                <p class="hero-subtitle">Unite your team across multiple games. Manage members, track performance, and build your gaming empire.</p>
                <button type="button" class="cta-button" onclick="navigateTo('signup.php')">
                    Get Started Free
                    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
                <p class="login-prompt">Already have an account? <button type="button" class="link-button" onclick="navigateTo('login.php')">Sign In</button></p>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <div class="landing-container">
            <h2 class="section-title">Everything You Need to Lead</h2>
            <p class="section-subtitle">Powerful tools for guild leaders and seamless experience for members</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h3>Create & Manage Guilds</h3>
                    <p>Build your guild empire across multiple games. Full admin control over members, roles, and sub-guilds.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3>Member Management</h3>
                    <p>Track IGNs, activity status, and assign authority. Approve or reject applications instantly.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <h3>Guild Communication</h3>
                    <p>Built-in chat system keeps your team connected. Real-time messaging for coordination and strategy.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M2 12h20"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <h3>Cross-Game Support</h3>
                    <p>Manage guilds for Clash of Clans, Pokemon Unite, Mobile Legends, Genshin Impact, and more.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6M23 11h-6"/>
                        </svg>
                    </div>
                    <h3>Application System</h3>
                    <p>Review member applications with detailed player overviews. Make informed recruitment decisions.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    <h3>Activity Tracking</h3>
                    <p>Monitor member activity and performance. Keep your guild active with engagement insights.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <div class="landing-container">
            <h2>Ready to Build Your Guild?</h2>
            <p>Join thousands of guild leaders managing their teams effectively</p>
            <button type="button" class="cta-button secondary" onclick="navigateTo('signup.php')">Create Free Account</button>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>
