# Guild Management System - Full Stack PHP Application

A complete guild management system with PHP backend, MySQL database, and Firebase integration for real-time chat functionality.

## System Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Composer**: For dependency management
- **Firebase Account**: For real-time chat (optional but recommended)

## Files Included

### Core Pages (Root Level):
- `index.php` - Login page
- `signup.php` - User registration
- `dashboard.php` - Main dashboard
- `create-guild.php` - Create new guild
- `join-guild.php` - Browse and join guilds
- `admin-dashboard.php` - Guild leader management
- `member-view.php` - Member profile and status
- `login.php` - Login processing
- `styles.css` - All styling
- `app.js` - Frontend functionality

### PHP Backend:
- `php/api.php` - API endpoints
- `php/db.php` - Database connection
- `php/firebase-api.php` - Firebase integration
- `php/login.php` - Login logic
- `php/logout.php` - Logout logic
- `php/register.php` - Registration logic
- `php/session.php` - Session management
- `php/classes/User.php` - User model
- `php/classes/Guild.php` - Guild model
- `php/firebase/FirebaseChat.php` - Firebase chat service

### Configuration:
- `composer.json` - PHP dependencies
- `.env.example` - Environment template
- `firebase-credentials.json.example` - Firebase template
- `guildsystem.sql` - Database schema

## Installation Instructions

### 1. **Clone/Download to XAMPP:**
```bash
cd C:\xampp\htdocs
# Copy GUILD_SYSTEM_OFFICIAL folder here
```

### 2. **Install PHP Dependencies:**
```bash
cd C:\xampp\htdocs\GUILD_SYSTEM_OFFICIAL
composer install
```

### 3. **Setup Database:**
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Create new database: `guild_management`
- Import `guildsystem.sql`:
  - Select database → Import tab → Choose `guildsystem.sql`

### 4. **Configure Environment:**
- Copy `.env.example` to `.env`
- Edit `.env` with your database credentials:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=guild_management
DB_USER=root
DB_PASSWORD=
```

### 5. **Setup Firebase (Optional for Chat):**
- Go to Firebase Console: https://console.firebase.google.com/
- Create project and Realtime Database
- Generate Service Account key (Settings → Service Accounts)
- Create `firebase-credentials.json` from downloaded key
- Update `.env` with Firebase URL and Project ID

### 6. **Start XAMPP:**
- Open XAMPP Control Panel
- Start Apache
- Start MySQL

### 7. **Access the Application:**
- Open browser: `http://localhost/GUILD_SYSTEM_OFFICIAL`

## Features

✅ **Authentication System**
- User registration and login
- Session management
- Secure password handling

✅ **Guild Management**
- Create guilds (become leader)
- Browse and join guilds
- Apply to guilds with applications
- Member approval system

✅ **Admin Dashboard (Guild Leaders)**
- View guild members
- Approve/reject applications
- Manage guild settings
- Kick members
- Real-time chat with Firebase

✅ **Member Features**
- Application status tracking
- View guild information
- Real-time guild chat
- Member profiles

✅ **Real-Time Chat**
- Firebase Realtime Database integration
- Live guild communications
- Message persistence

## Database Structure

The application uses MySQL with the following main tables:
- `users` - User accounts and profiles
- `guilds` - Guild information
- `members` - Guild membership records
- `applications` - Join applications
- See `guildsystem.sql` for complete schema

## Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Real-Time**: Firebase Realtime Database
- **Package Manager**: Composer
- **API**: REST API endpoints in `php/api.php`

## API Endpoints

### Authentication
- `POST /php/api.php?action=login` - User login
- `POST /php/api.php?action=register` - User registration
- `GET /php/api.php?action=logout` - User logout

### Guilds
- `GET /php/api.php?action=list_guilds` - List all guilds
- `POST /php/api.php?action=create_guild` - Create new guild
- `GET /php/api.php?action=guild_members` - Get guild members
- `POST /php/api.php?action=apply_guild` - Apply to join guild

### Admin
- `POST /php/api.php?action=approve_member` - Approve member
- `POST /php/api.php?action=reject_member` - Reject application
- `POST /php/api.php?action=kick_member` - Remove member

### Firebase Chat
- `GET /php/firebase-api.php?action=get_messages` - Get guild messages
- `POST /php/firebase-api.php?action=send_message` - Send message

## Deployment to InfinityFree

1. **Prepare Repository:**
   - Push cleaned code to GitHub
   - Ensure credentials NOT included

2. **On InfinityFree:**
   - Connect GitHub repository
   - Run `composer install` via terminal
   - Create MySQL database
   - Import `guildsystem.sql`
   - Configure `.env` with server credentials
   - Add `firebase-credentials.json`

3. **Verify:**
   - Access your domain
   - Test login/signup
   - Test guild creation
   - Test Firebase chat

## Troubleshooting

**"Composer not found"**
- Make sure `vendor/` was deleted (it will auto-install on server)

**"Firebase initialization failed"**
- Ensure `firebase-credentials.json` exists and is valid
- Check `.env` has correct `FIREBASE_DATABASE_URL`

**"Database connection failed"**
- Verify MySQL is running
- Check `.env` database credentials
- Ensure `guildsystem.sql` was imported

**"API endpoints returning errors"**
- Check browser console (F12) for error messages
- Verify PHP error logs in XAMPP
- Ensure all required files exist in `php/` folder

## Project Structure

```
GUILD_SYSTEM_OFFICIAL/
├── php/
│   ├── api.php
│   ├── db.php
│   ├── firebase-api.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── session.php
│   ├── classes/
│   │   ├── Guild.php
│   │   └── User.php
│   └── firebase/
│       └── FirebaseChat.php
├── index.php
├── signup.php
├── login.php
├── dashboard.php
├── create-guild.php
├── join-guild.php
├── admin-dashboard.php
├── member-view.php
├── styles.css
├── app.js
├── composer.json
├── .env.example
├── firebase-credentials.json.example
├── guildsystem.sql
├── README.md
└── .gitignore
```

## Security Notes

- Never commit `.env` or `firebase-credentials.json` to GitHub
- Use `.example` files as templates for new installations
- Always use HTTPS in production
- Validate all user inputs on server-side
- Keep PHP and dependencies updated

## Support

For issues or questions:
1. Check the `GITHUB-DEPLOYMENT-GUIDE.md` for detailed deployment steps
2. Verify all environment variables are set correctly
3. Check PHP error logs for debugging information
