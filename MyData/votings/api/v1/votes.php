<?php
/**
 * BVOTE 2025 - Votes API
 * Handle voting-related API requests
 */

if (!defined('VOTING_SYSTEM_ACCESS')) {
    die('Direct access not allowed');
}

$db = $GLOBALS['db'];

switch ($method) {
    case 'POST':
        // Cast a vote
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['campaign_id']) || !isset($input['contestant_id'])) {
            apiError('Missing required fields', 400);
        }

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            apiError('User authentication required', 401);
        }

        try {
            // Check if user already voted for this campaign
            $stmt = $db->prepare("SELECT id FROM votes WHERE user_id = ? AND campaign_id = ?");
            $stmt->execute([$_SESSION['user_id'], $input['campaign_id']]);

            if ($stmt->fetch()) {
                apiError('You have already voted for this campaign', 400);
            }

            // Verify campaign and contestant exist
            $stmt = $db->prepare("SELECT c.id FROM campaigns c JOIN contestants con ON c.id = con.campaign_id WHERE c.id = ? AND con.id = ? AND c.status = 'active'");
            $stmt->execute([$input['campaign_id'], $input['contestant_id']]);

            if (!$stmt->fetch()) {
                apiError('Invalid campaign or contestant', 400);
            }

            // Cast the vote
            $db->beginTransaction();

            // Insert vote record
            $stmt = $db->prepare("INSERT INTO votes (user_id, campaign_id, contestant_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['user_id'],
                $input['campaign_id'],
                $input['contestant_id'],
                $_SERVER['REMOTE_ADDR']
            ]);

            // Update contestant vote count
            $stmt = $db->prepare("UPDATE contestants SET vote_count = vote_count + 1 WHERE id = ?");
            $stmt->execute([$input['contestant_id']]);

            $db->commit();

            apiResponse(['vote_id' => $db->lastInsertId()], 201, 'Vote cast successfully');

        } catch (PDOException $e) {
            $db->rollBack();
            apiError('Failed to cast vote', 500);
        }
        break;

    case 'GET':
        // Get voting statistics
        if ($id) {
            // Get votes for specific campaign
            try {
                $stmt = $db->prepare("SELECT contestant_id, COUNT(*) as vote_count FROM votes WHERE campaign_id = ? GROUP BY contestant_id");
                $stmt->execute([$id]);
                $results = $stmt->fetchAll();

                apiResponse($results);
            } catch (PDOException $e) {
                apiError('Database error', 500);
            }
        } else {
            // Get user's voting history (if logged in)
            if (!isset($_SESSION['user_id'])) {
                apiError('User authentication required', 401);
            }

            try {
                $stmt = $db->prepare("
                    SELECT v.*, c.title as campaign_title, con.name as contestant_name
                    FROM votes v
                    JOIN campaigns c ON v.campaign_id = c.id
                    JOIN contestants con ON v.contestant_id = con.id
                    WHERE v.user_id = ?
                    ORDER BY v.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $votes = $stmt->fetchAll();

                apiResponse($votes);
            } catch (PDOException $e) {
                apiError('Database error', 500);
            }
        }
        break;

    default:
        apiError('Method not allowed', 405);
}
?>
