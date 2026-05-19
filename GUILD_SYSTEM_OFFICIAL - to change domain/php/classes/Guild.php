<?php
class Guild {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createGuild($leaderId, $guildName, $game, $leaderIGN, $leaderUsername, $description, $requirements) {
        if (strlen($guildName) < 3) {
            return ["success" => false, "message" => "Guild name must be at least 3 characters."];
        }

        // Check if guild name already exists
        $stmt = $this->pdo->prepare("SELECT id FROM guilds WHERE name = ?");
        $stmt->execute([$guildName]);
        if ($stmt->fetch()) {
            return ["success" => false, "message" => "Guild name already exists."];
        }

        $stmt = $this->pdo->prepare("INSERT INTO guilds (leader_id, name, game, description, requirements, created_at) 
                                     VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$leaderId, $guildName, $game, $description, $requirements]);

        $guildId = $this->pdo->lastInsertId();
        $memberInsert = $this->pdo->prepare("INSERT INTO members (guild_id, user_id, ign, role, authority, status, joined_at) VALUES (?, ?, ?, 'leader', 'Leader', 'Offline', NOW())");
        $memberInsert->execute([$guildId, $leaderId, $leaderIGN]);

        return ["success" => true, "message" => "Guild created successfully.", "guildId" => $guildId];
    }

    public function listGuilds() {
        $stmt = $this->pdo->query(
            "SELECT g.*, u.username AS leader_username, m.ign AS leader_ign, 
                    COALESCE(mc.member_count, 0) AS memberCount, 
                    COALESCE(a.pending_count, 0) AS pendingCount
             FROM guilds g
             JOIN users u ON g.leader_id = u.id
             JOIN members m ON g.id = m.guild_id AND m.user_id = g.leader_id
             LEFT JOIN (
                 SELECT guild_id, COUNT(id) AS member_count
                 FROM members
                 WHERE status IN ('Online', 'Offline')
                 GROUP BY guild_id
             ) mc ON mc.guild_id = g.id
             LEFT JOIN (
                 SELECT guild_id, COUNT(id) AS pending_count
                 FROM applications
                 WHERE status = 'pending'
                 GROUP BY guild_id
             ) a ON a.guild_id = g.id
             ORDER BY g.created_at DESC"
        );
        $guilds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $guilds];
    }

    public function getGuildById($guildId, $userId = null) {
        $stmt = $this->pdo->prepare("SELECT g.*, u.username AS leader_username, m.ign AS leader_ign FROM guilds g JOIN users u ON g.leader_id = u.id JOIN members m ON g.id = m.guild_id AND m.user_id = g.leader_id WHERE g.id = ?");
        $stmt->execute([$guildId]);
        $guild = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guild) {
            return ["success" => false, "message" => "Guild not found."];
        }

        if ($userId !== null) {
            $membershipCheck = $this->pdo->prepare("SELECT id FROM members WHERE guild_id = ? AND user_id = ?");
            $membershipCheck->execute([$guildId, $userId]);
            if (!$membershipCheck->fetch()) {
                return ["success" => false, "message" => "You do not have permission to view this guild's admin dashboard."];
            }
        }

        $membersStmt = $this->pdo->prepare("SELECT m.*, u.username FROM members m JOIN users u ON m.user_id = u.id WHERE m.guild_id = ? ORDER BY m.role DESC, m.joined_at ASC");
        $membersStmt->execute([$guildId]);
        $guild['members'] = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

        $memberIds = array_column($guild['members'], 'id');
        if (!empty($memberIds)) {
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $rolesStmt = $this->pdo->prepare(
                "SELECT ur.member_id, r.id, r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.member_id IN ($placeholders) ORDER BY r.name ASC"
            );
            $rolesStmt->execute($memberIds);
            $memberRoles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
            $roleMap = [];
            foreach ($memberRoles as $roleRow) {
                $roleMap[$roleRow['member_id']][] = [
                    'id' => $roleRow['id'],
                    'name' => $roleRow['name']
                ];
            }
            foreach ($guild['members'] as &$member) {
                $member['roles'] = $roleMap[$member['id']] ?? [];
            }
            unset($member);
        } else {
            foreach ($guild['members'] as &$member) {
                $member['roles'] = [];
            }
            unset($member);
        }

        // Fetch pending applications with user data and IGN from applications table
        $appsStmt = $this->pdo->prepare("SELECT a.*, u.username FROM applications a JOIN users u ON a.user_id = u.id WHERE a.guild_id = ? AND a.status = 'pending'");
        $appsStmt->execute([$guildId]);
        $guild['applications'] = $appsStmt->fetchAll(PDO::FETCH_ASSOC);

        return ["success" => true, "data" => $guild];
    }

    public function updateMemberAuthority($memberId, $newAuthority, $actorUserId) {
        $allowed = ['Leader', 'Sub leader', 'member'];
        if (!in_array($newAuthority, $allowed, true)) {
            return ["success" => false, "message" => "Invalid authority level."];
        }

        $stmt = $this->pdo->prepare("SELECT m.guild_id, m.user_id, g.leader_id FROM members m JOIN guilds g ON m.guild_id = g.id WHERE m.id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            return ["success" => false, "message" => "Member not found."];
        }

        $actorStmt = $this->pdo->prepare("SELECT authority FROM members WHERE guild_id = ? AND user_id = ?");
        $actorStmt->execute([$member['guild_id'], $actorUserId]);
        $actorRow = $actorStmt->fetch(PDO::FETCH_ASSOC);
        $actorAuthority = $actorRow['authority'] ?? null;

        if (!in_array($actorAuthority, ['Leader', 'Sub leader'], true)) {
            return ["success" => false, "message" => "Only guild leadership can update member authority."];
        }

        if ($newAuthority === 'Leader') {
            if ($actorAuthority !== 'Leader') {
                return ["success" => false, "message" => "Only the guild leader can promote to Leader."];
            }

            if ($member['user_id'] === $actorUserId) {
                // already the leader
                return ["success" => true, "message" => "Member is already the leader."];
            }

            $this->pdo->beginTransaction();
            try {
                $demoteStmt = $this->pdo->prepare("UPDATE members SET authority = 'Sub leader' WHERE guild_id = ? AND authority = 'Leader'");
                $demoteStmt->execute([$member['guild_id']]);

                $updateGuild = $this->pdo->prepare("UPDATE guilds SET leader_id = ? WHERE id = ?");
                $updateGuild->execute([$member['user_id'], $member['guild_id']]);

                $updateAuthority = $this->pdo->prepare("UPDATE members SET authority = ? WHERE id = ?");
                $updateAuthority->execute([$newAuthority, $memberId]);

                $this->pdo->commit();
                return ["success" => true, "message" => "Member promoted to Leader successfully."];
            } catch (Exception $e) {
                $this->pdo->rollBack();
                return ["success" => false, "message" => "Error promoting member: " . $e->getMessage()];
            }
        }

        if ($member['user_id'] === $actorUserId && $newAuthority !== 'Leader') {
            return ["success" => false, "message" => "You cannot demote yourself from Leader here."];
        }

        $updateAuthority = $this->pdo->prepare("UPDATE members SET authority = ? WHERE id = ?");
        $updateAuthority->execute([$newAuthority, $memberId]);

        return ["success" => true, "message" => "Member authority updated successfully."];
    }

    public function kickMember($memberId, $actorUserId) {
        $stmt = $this->pdo->prepare(
            "SELECT m.guild_id, m.user_id AS target_user_id, g.leader_id, a.authority AS actor_authority, a.user_id AS actor_user_id
             FROM members m
             JOIN guilds g ON m.guild_id = g.id
             JOIN members a ON a.guild_id = g.id AND a.user_id = ?
             WHERE m.id = ?"
        );
        $stmt->execute([$actorUserId, $memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            return ["success" => false, "message" => "Member not found."];
        }

        if ($member['target_user_id'] == $actorUserId) {
            return ["success" => false, "message" => "You cannot kick yourself. Use the leave guild action instead."];
        }

        if ($member['target_user_id'] == $member['leader_id']) {
            return ["success" => false, "message" => "You cannot kick the guild leader."];
        }

        $actorAuthority = $member['actor_authority'] ?? 'member';
        if (!in_array($actorAuthority, ['Leader', 'Sub leader'], true)) {
            return ["success" => false, "message" => "Only guild leadership can kick members."];
        }

        $delete = $this->pdo->prepare("DELETE FROM members WHERE id = ?");
        $delete->execute([$memberId]);

        return ["success" => true, "message" => "Member has been kicked from the guild."];
    }

    public function getGuildsByMember($userId) {
        $stmt = $this->pdo->prepare(
            "SELECT g.*, u.username AS leader_username, m.ign AS leader_ign, 
                    COALESCE(mc.member_count, 0) AS memberCount, 
                    COALESCE(a.pending_count, 0) AS pendingCount,
                    COALESCE(mem.authority, 'member') AS userAuthority
             FROM guilds g 
             JOIN members mem ON g.id = mem.guild_id 
             JOIN users u ON g.leader_id = u.id 
             JOIN members m ON g.id = m.guild_id AND m.user_id = g.leader_id
             LEFT JOIN ( 
                 SELECT guild_id, COUNT(id) AS member_count 
                 FROM members 
                 WHERE status IN ('Online', 'Offline') 
                 GROUP BY guild_id 
             ) mc ON mc.guild_id = g.id 
             LEFT JOIN ( 
                 SELECT guild_id, COUNT(id) AS pending_count 
                 FROM applications 
                 WHERE status = 'pending' 
                 GROUP BY guild_id 
             ) a ON a.guild_id = g.id 
             WHERE mem.user_id = ?
             ORDER BY g.created_at DESC"
        );
        $stmt->execute([$userId]);
        $guilds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $guilds];
    }

    // ---------------- APPLICATIONS ----------------
    public function listApplicationsByUser($userId) {
        $stmt = $this->pdo->prepare("SELECT a.*, g.name AS guildName 
                                     FROM applications a 
                                     JOIN guilds g ON a.guild_id = g.id 
                                     WHERE a.user_id = ?");
        $stmt->execute([$userId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $apps];
    }

    public function submitApplication($guildId, $userId, $message, $ign = null) {
        if (!$guildId || !$userId) {
            return ["success" => false, "message" => "Guild ID and user ID are required."];
        }

        $stmt = $this->pdo->prepare("SELECT id FROM guilds WHERE id = ?");
        $stmt->execute([$guildId]);
        if (!$stmt->fetch()) {
            return ["success" => false, "message" => "Guild not found."];
        }

        $memberCheck = $this->pdo->prepare("SELECT id FROM members WHERE guild_id = ? AND user_id = ?");
        $memberCheck->execute([$guildId, $userId]);
        if ($memberCheck->fetch()) {
            return ["success" => false, "message" => "You are already a member of this guild."];
        }

        $applicationCheck = $this->pdo->prepare("SELECT id FROM applications WHERE guild_id = ? AND user_id = ? AND status = 'pending'");
        $applicationCheck->execute([$guildId, $userId]);
        if ($applicationCheck->fetch()) {
            return ["success" => false, "message" => "You already have a pending application for this guild."];
        }

        $insert = $this->pdo->prepare("INSERT INTO applications (guild_id, user_id, ign, message, status, applied_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $insert->execute([$guildId, $userId, $ign, $message]);

        return ["success" => true, "message" => "Application submitted successfully.", "applicationId" => $this->pdo->lastInsertId()];
    }

    public function approveApplication($applicationId, $actorUserId) {
        $stmt = $this->pdo->prepare("SELECT a.guild_id, a.user_id, a.ign, a.status, g.leader_id FROM applications a JOIN guilds g ON a.guild_id = g.id WHERE a.id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return ["success" => false, "message" => "Application not found."];
        }

        // Validate IGN is not empty
        if (!$application['ign'] || trim($application['ign']) === '') {
            return ["success" => false, "message" => "Cannot approve: applicant has not provided an IGN."];
        }

        $authStmt = $this->pdo->prepare("SELECT authority FROM members WHERE guild_id = ? AND user_id = ?");
        $authStmt->execute([$application['guild_id'], $actorUserId]);
        $authRow = $authStmt->fetch(PDO::FETCH_ASSOC);
        $authority = $authRow['authority'] ?? null;

        if (!in_array($authority, ['Leader', 'Sub leader'], true)) {
            return ["success" => false, "message" => "Only guild leadership can approve applications."];
        }

        if ($application['status'] !== 'pending') {
            return ["success" => false, "message" => "This application has already been processed."];
        }

        $membershipCheck = $this->pdo->prepare("SELECT id FROM members WHERE guild_id = ? AND user_id = ?");
        $membershipCheck->execute([$application['guild_id'], $application['user_id']]);
        if ($membershipCheck->fetch()) {
            $update = $this->pdo->prepare("UPDATE applications SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
            $update->execute([$actorUserId, $applicationId]);
            return ["success" => true, "message" => "Application approved, user is already a member."];
        }

        $insert = $this->pdo->prepare("INSERT INTO members (guild_id, user_id, ign, role, authority, status, joined_at) VALUES (?, ?, ?, 'None', 'member', 'Offline', NOW())");
        $insert->execute([$application['guild_id'], $application['user_id'], $application['ign']]);

        $update = $this->pdo->prepare("UPDATE applications SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $update->execute([$actorUserId, $applicationId]);

        return ["success" => true, "message" => "Application approved and member added to the guild."];
    }

    public function rejectApplication($applicationId, $actorUserId) {
        $stmt = $this->pdo->prepare("SELECT a.guild_id, a.status, g.leader_id FROM applications a JOIN guilds g ON a.guild_id = g.id WHERE a.id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return ["success" => false, "message" => "Application not found."];
        }

        $authStmt = $this->pdo->prepare("SELECT authority FROM members WHERE guild_id = ? AND user_id = ?");
        $authStmt->execute([$application['guild_id'], $actorUserId]);
        $authRow = $authStmt->fetch(PDO::FETCH_ASSOC);
        $authority = $authRow['authority'] ?? null;

        if (!in_array($authority, ['Leader', 'Sub leader'], true)) {
            return ["success" => false, "message" => "Only guild leadership can reject applications."];
        }

        if ($application['status'] !== 'pending') {
            return ["success" => false, "message" => "This application has already been processed."];
        }

        $update = $this->pdo->prepare("UPDATE applications SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $update->execute([$actorUserId, $applicationId]);

        return ["success" => true, "message" => "Application rejected."];
    }

    public function leaveGuild($guildId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Get the user's membership details
            $stmt = $this->pdo->prepare("SELECT g.id AS guild_id, g.leader_id, m.id AS member_id, m.user_id AS member_user_id, m.role 
                                         FROM guilds g 
                                         JOIN members m ON g.id = m.guild_id 
                                         WHERE g.id = ? AND m.user_id = ?");
            $stmt->execute([$guildId, $userId]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$membership) {
                $this->pdo->rollBack();
                return ["success" => false, "message" => "You are not an approved member of this guild."];
            }

            // Check if user is the leader (handle case where leader_id might be NULL or invalid)
            $isLeader = $membership['leader_id'] !== null && $membership['leader_id'] == $userId;

            // Get all other members remaining after user leaves
            $stmt = $this->pdo->prepare("SELECT id, user_id, last_active FROM members WHERE guild_id = ? AND user_id != ? ORDER BY last_active DESC, joined_at ASC");
            $stmt->execute([$guildId, $userId]);
            $remainingMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no members will remain after leaving, disband the guild
            if (empty($remainingMembers)) {
                $this->deleteGuild($guildId, false);
                $this->pdo->commit();
                return ["success" => true, "message" => "You left the guild. The guild has been disbanded because no members remain."];
            }

            // If user is the leader, promote the next most active member to leader
            // This doesn't fail even if the new leader is missing IGN or other credentials
            if ($isLeader) {
                $newLeader = $remainingMembers[0];
                $meta = $this->pdo->prepare("UPDATE guilds SET leader_id = ? WHERE id = ?");
                $meta->execute([$newLeader['user_id'], $guildId]);

                $roleUpdate = $this->pdo->prepare("UPDATE members SET role = 'leader', authority = 'Leader' WHERE id = ?");
                $roleUpdate->execute([$newLeader['id']]);
            }

            // Remove the user from the guild
            $delete = $this->pdo->prepare("DELETE FROM members WHERE id = ?");
            $delete->execute([$membership['member_id']]);

            $this->pdo->commit();

            if ($isLeader) {
                return ["success" => true, "message" => "You left the guild. Leadership has been passed to the next most active member."];
            }

            return ["success" => true, "message" => "You left the guild."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["success" => false, "message" => "Error leaving guild: " . $e->getMessage()];
        }
    }

    public function disbandGuild($guildId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT leader_id FROM guilds WHERE id = ?");
            $stmt->execute([$guildId]);
            $guild = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$guild) {
                return ["success" => false, "message" => "Guild not found."];
            }
            if ($guild['leader_id'] != $userId) {
                return ["success" => false, "message" => "Only the guild leader can disband this guild."];
            }

            $this->deleteGuild($guildId, true);
            return ["success" => true, "message" => "Guild disbanded successfully."];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error disbanding guild: " . $e->getMessage()];
        }
    }

    private function deleteGuild($guildId, $manageTransaction = true) {
        try {
            // Only start transaction if we're not already in one
            $shouldCommit = false;
            if ($manageTransaction && !$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $shouldCommit = true;
            }

            // Delete related records in order of dependencies
            // 1. Delete applications related to this guild
            $stmt = $this->pdo->prepare("DELETE FROM applications WHERE guild_id = ?");
            $stmt->execute([$guildId]);

            // 2. Delete guild members
            $stmt = $this->pdo->prepare("DELETE FROM members WHERE guild_id = ?");
            $stmt->execute([$guildId]);

            // 3. Delete chat messages related to this guild
            $stmt = $this->pdo->prepare("DELETE FROM messages WHERE guild_id = ?");
            $stmt->execute([$guildId]);

            // 4. Finally delete the guild itself
            $stmt = $this->pdo->prepare("DELETE FROM guilds WHERE id = ?");
            $stmt->execute([$guildId]);

            // Commit transaction only if we started it
            if ($shouldCommit) {
                $this->pdo->commit();
            }
        } catch (Exception $e) {
            // Rollback on error only if we started the transaction
            if ($shouldCommit && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getGuildChatMessages($guildId, $userId = null) {
        if (!$guildId) {
            return ["success" => false, "message" => "Guild ID is required."];
        }

        if ($userId) {
            $membershipStmt = $this->pdo->prepare("SELECT id FROM members WHERE guild_id = ? AND user_id = ?");
            $membershipStmt->execute([$guildId, $userId]);
            if (!$membershipStmt->fetch()) {
                return ["success" => false, "message" => "Only guild members can view this chat."];
            }
        }

        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.user_id, u.username AS sender, m.text, m.timestamp
             FROM messages m
             JOIN users u ON m.user_id = u.id
             WHERE m.guild_id = ?
             ORDER BY m.timestamp ASC
             LIMIT 100"
        );
        $stmt->execute([$guildId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ["success" => true, "data" => $messages];
    }

    public function sendGuildChatMessage($guildId, $userId, $text) {
        $text = trim($text ?? '');
        if (!$guildId || !$userId || $text === '') {
            return ["success" => false, "message" => "Guild, user, and message text are required."];
        }

        $membershipStmt = $this->pdo->prepare("SELECT id FROM members WHERE guild_id = ? AND user_id = ?");
        $membershipStmt->execute([$guildId, $userId]);
        if (!$membershipStmt->fetch()) {
            return ["success" => false, "message" => "You must be a member of this guild to send messages."];
        }

        $insert = $this->pdo->prepare("INSERT INTO messages (guild_id, user_id, text, timestamp) VALUES (?, ?, ?, NOW())");
        $insert->execute([$guildId, $userId, $text]);

        return [
            "success" => true,
            "message" => "Message sent successfully.",
            "data" => [
                "id" => $this->pdo->lastInsertId(),
                "guild_id" => $guildId,
                "user_id" => $userId,
                "sender" => $this->getUsernameById($userId),
                "text" => $text,
                "timestamp" => date('Y-m-d H:i:s')
            ]
        ];
    }

    private function getUsernameById($userId) {
        $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user['username'] ?? 'Unknown';
    }

    // -------------------- ROLE MANAGEMENT --------------------
    public function createRole($guildId, $roleName, $maxLimit = null) {
        if (strlen($roleName) < 1 || strlen($roleName) > 100) {
            return ["success" => false, "message" => "Role name must be between 1 and 100 characters."];
        }

        $checkStmt = $this->pdo->prepare("SELECT id FROM roles WHERE guild_id = ? AND name = ?");
        $checkStmt->execute([$guildId, $roleName]);
        if ($checkStmt->fetch()) {
            return ["success" => false, "message" => "This role already exists in the guild."];
        }

        $stmt = $this->pdo->prepare("INSERT INTO roles (guild_id, name, max_limit) VALUES (?, ?, ?)");
        $stmt->execute([$guildId, $roleName, $maxLimit]);

        return ["success" => true, "message" => "Role created successfully.", "roleId" => $this->pdo->lastInsertId()];
    }

    public function getRolesByGuild($guildId) {
        $stmt = $this->pdo->prepare("SELECT id, name, max_limit FROM roles WHERE guild_id = ? ORDER BY name ASC");
        $stmt->execute([$guildId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $roles];
    }

    public function deleteRole($roleId, $guildId) {
        $stmt = $this->pdo->prepare("SELECT guild_id FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role || $role['guild_id'] != $guildId) {
            return ["success" => false, "message" => "Role not found."];
        }

        $this->pdo->beginTransaction();
        try {
            $deleteUserRoles = $this->pdo->prepare("DELETE FROM user_roles WHERE role_id = ?");
            $deleteUserRoles->execute([$roleId]);

            $deleteRole = $this->pdo->prepare("DELETE FROM roles WHERE id = ?");
            $deleteRole->execute([$roleId]);

            $this->pdo->commit();
            return ["success" => true, "message" => "Role deleted successfully."];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ["success" => false, "message" => "Error deleting role: " . $e->getMessage()];
        }
    }

    public function assignRoleToMember($memberId, $roleId, $actorUserId) {
        $stmt = $this->pdo->prepare(
            "SELECT m.guild_id, m.user_id, g.leader_id, a.authority AS actor_authority
             FROM members m
             JOIN guilds g ON m.guild_id = g.id
             JOIN members a ON a.guild_id = g.id AND a.user_id = ?
             WHERE m.id = ?"
        );
        $stmt->execute([$actorUserId, $memberId]);
        $memberData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$memberData) {
            return ["success" => false, "message" => "Member not found."];
        }

        $actorAuthority = $memberData['actor_authority'] ?? 'member';
        $isSelfAssignment = $memberData['user_id'] === $actorUserId;
        if (!$isSelfAssignment && !in_array($actorAuthority, ['Leader', 'Sub leader'], true)) {
            return ["success" => false, "message" => "Only guild leadership can assign roles to other members."];
        }

        $checkRole = $this->pdo->prepare("SELECT id, guild_id, max_limit FROM roles WHERE id = ?");
        $checkRole->execute([$roleId]);
        $role = $checkRole->fetch(PDO::FETCH_ASSOC);

        if (!$role || $role['guild_id'] != $memberData['guild_id']) {
            return ["success" => false, "message" => "Role not found in this guild."];
        }

        if ($role['max_limit'] !== null) {
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM user_roles WHERE role_id = ?");
            $countStmt->execute([$roleId]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC);
            if ($count['count'] >= $role['max_limit']) {
                return ["success" => false, "message" => "This role has reached its member limit."];
            }
        }

        $checkExists = $this->pdo->prepare("SELECT id FROM user_roles WHERE member_id = ? AND role_id = ?");
        $checkExists->execute([$memberId, $roleId]);
        if ($checkExists->fetch()) {
            return ["success" => false, "message" => "Member already has this role."];
        }

        $insert = $this->pdo->prepare("INSERT INTO user_roles (member_id, role_id) VALUES (?, ?)");
        $insert->execute([$memberId, $roleId]);

        return ["success" => true, "message" => "Role assigned successfully."];
    }

    public function removeRoleFromMember($memberId, $roleId, $actorUserId) {
        $stmt = $this->pdo->prepare(
            "SELECT m.guild_id, m.user_id, g.leader_id, a.authority AS actor_authority
             FROM members m
             JOIN guilds g ON m.guild_id = g.id
             JOIN members a ON a.guild_id = g.id AND a.user_id = ?
             WHERE m.id = ?"
        );
        $stmt->execute([$actorUserId, $memberId]);
        $memberData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$memberData) {
            return ["success" => false, "message" => "Member not found."];
        }

        $actorAuthority = $memberData['actor_authority'] ?? 'member';
        $isSelfRemoval = $memberData['user_id'] === $actorUserId;
        if (!$isSelfRemoval && !in_array($actorAuthority, ['Leader', 'Sub leader'], true)) {
            return ["success" => false, "message" => "Only guild leadership can remove roles from other members."];
        }

        $delete = $this->pdo->prepare("DELETE FROM user_roles WHERE member_id = ? AND role_id = ?");
        $delete->execute([$memberId, $roleId]);

        return ["success" => true, "message" => "Role removed successfully."];
    }

    public function getMemberRoles($memberId) {
        $stmt = $this->pdo->prepare(
            "SELECT r.id, r.name FROM user_roles ur
             JOIN roles r ON ur.role_id = r.id
             WHERE ur.member_id = ?
             ORDER BY r.name ASC"
        );
        $stmt->execute([$memberId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $roles];
    }
}
?>
