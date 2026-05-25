CREATE DATABASE guild_management;
USE guild_management;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100),
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- GUILDS
CREATE TABLE guilds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL,
  game VARCHAR(100) NOT NULL,
  description TEXT,
  requirements TEXT,
  leader_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (leader_id) REFERENCES users(id)
);

-- MEMBERS
CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guild_id INT NOT NULL,
  user_id INT NOT NULL,
  ign VARCHAR(50) NULL,
  role VARCHAR(50) DEFAULT 'None',
  authority ENUM('Leader','Sub leader','member') DEFAULT 'member',
  status ENUM('Online','Offline') DEFAULT 'Offline',
  last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (guild_id) REFERENCES guilds(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ROLES
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guild_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  max_limit INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (guild_id) REFERENCES guilds(id),
  UNIQUE KEY unique_role_name (guild_id, name)
);

-- USER_ROLES (Junction table for many-to-many relationship)
CREATE TABLE user_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  role_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  UNIQUE KEY unique_member_role (member_id, role_id)
);

-- APPLICATIONS
CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guild_id INT NOT NULL,
  user_id INT NOT NULL,
  ign VARCHAR(50) NULL,
  message TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  reviewed_by INT NULL,
  FOREIGN KEY (guild_id) REFERENCES guilds(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- CHAT
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guild_id INT NOT NULL,
  user_id INT NOT NULL,
  text TEXT NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (guild_id) REFERENCES guilds(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
