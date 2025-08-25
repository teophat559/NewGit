<?php
/**
 * BVOTE 2025 - User Database Configuration
 * Simple database configuration for user interface
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'newb_db');
define('DB_USER', 'newb_vote2025');
define('DB_PASS', '123123zz@');

/**
 * Get database connection
 */
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

/**
 * Execute a prepared statement
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Query execution failed");
    }
}

/**
 * Get all campaigns
 */
function getCampaigns() {
    $stmt = executeQuery("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get contestants by campaign
 */
function getContestantsByCampaign($campaignId) {
    $stmt = executeQuery(
        "SELECT * FROM contestants WHERE campaign_id = ? AND status = 'active' ORDER BY contestant_number ASC",
        [$campaignId]
    );
    return $stmt->fetchAll();
}

/**
 * Get vote count for contestant
 */
function getVoteCount($contestantId) {
    $stmt = executeQuery(
        "SELECT COUNT(*) as vote_count FROM votes WHERE contestant_id = ?",
        [$contestantId]
    );
    $result = $stmt->fetch();
    return $result['vote_count'] ?? 0;
}

/**
 * Check if user voted for campaign
 */
function hasUserVoted($userId, $campaignId) {
    $stmt = executeQuery(
        "SELECT COUNT(*) as vote_count FROM votes WHERE user_id = ? AND campaign_id = ?",
        [$userId, $campaignId]
    );
    $result = $stmt->fetch();
    return $result['vote_count'] > 0;
}

/**
 * Cast a vote
 */
function castVote($userId, $campaignId, $contestantId, $ipAddress = null) {
    try {
        // Check if user already voted
        if (hasUserVoted($userId, $campaignId)) {
            throw new Exception("User has already voted for this campaign");
        }

        // Insert vote
        executeQuery(
            "INSERT INTO votes (user_id, campaign_id, contestant_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$userId, $campaignId, $contestantId, $ipAddress]
        );

        return true;
    } catch (Exception $e) {
        error_log("Vote casting failed: " . $e->getMessage());
        throw $e;
    }
}
?>
