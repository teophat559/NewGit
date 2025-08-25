<?php
/**
 * BVOTE 2025 - Campaigns API
 * Handle campaign-related API requests
 */

if (!defined('VOTING_SYSTEM_ACCESS')) {
    die('Direct access not allowed');
}

$db = $GLOBALS['db'];

switch ($method) {
    case 'GET':
        if ($id) {
            // Get specific campaign
            try {
                $stmt = $db->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'active'");
                $stmt->execute([$id]);
                $campaign = $stmt->fetch();

                if ($campaign) {
                    // Get contestants for this campaign
                    $stmt = $db->prepare("SELECT * FROM contestants WHERE campaign_id = ? ORDER BY vote_count DESC");
                    $stmt->execute([$id]);
                    $campaign['contestants'] = $stmt->fetchAll();

                    apiResponse($campaign);
                } else {
                    apiError('Campaign not found', 404);
                }
            } catch (PDOException $e) {
                apiError('Database error', 500);
            }
        } else {
            // Get all active campaigns
            try {
                $stmt = $db->query("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
                $campaigns = $stmt->fetchAll();

                apiResponse($campaigns);
            } catch (PDOException $e) {
                apiError('Database error', 500);
            }
        }
        break;

    case 'POST':
        // Create new campaign (admin only)
        if (!isset($_SESSION['bvote_admin_logged_in'])) {
            apiError('Admin authentication required', 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['title'])) {
            apiError('Invalid input data', 400);
        }

        try {
            $stmt = $db->prepare("INSERT INTO campaigns (title, description, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([
                $input['title'],
                $input['description'] ?? '',
                $input['start_date'] ?? date('Y-m-d H:i:s'),
                $input['end_date'] ?? date('Y-m-d H:i:s', strtotime('+30 days'))
            ]);

            $campaignId = $db->lastInsertId();
            apiResponse(['id' => $campaignId], 201, 'Campaign created successfully');
        } catch (PDOException $e) {
            apiError('Failed to create campaign', 500);
        }
        break;

    default:
        apiError('Method not allowed', 405);
}
?>
