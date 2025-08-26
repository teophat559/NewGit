<?php
/**
 * BVOTE Vote Controller
 * Xử lý các thao tác voting
 */

require_once __DIR__ . '/bootstrap.php';

use BVOTE\Core\Database;
use BVOTE\Core\Validator;
use BVOTE\Core\Logger;
use BVOTE\Services\VoteService;
use BVOTE\Services\NotificationService;

class VoteController {
    private $voteService;
    private $notificationService;

    public function __construct() {
        $this->voteService = new VoteService();
        $this->notificationService = new NotificationService();
    }

    /**
     * Xử lý vote cho contest
     */
    public function vote() {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Validate input
            $data = $this->validateVoteData($_POST);

            // Check if user is authenticated
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not authenticated');
            }

            // Check if contest exists and is active
            $contest = $this->voteService->getContest($data['contest_id']);
            if (!$contest || $contest['status'] !== 'active') {
                throw new Exception('Contest not found or inactive');
            }

            // Check if user has already voted
            if ($this->voteService->hasUserVoted($_SESSION['user_id'], $data['contest_id'])) {
                throw new Exception('User has already voted for this contest');
            }

            // Process vote
            $voteId = $this->voteService->createVote([
                'user_id' => $_SESSION['user_id'],
                'contest_id' => $data['contest_id'],
                'contestant_id' => $data['contestant_id'],
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Send notification
            $this->notificationService->sendVoteConfirmation($_SESSION['user_id'], $voteId);

            // Log vote
            Logger::info('Vote created successfully', [
                'vote_id' => $voteId,
                'user_id' => $_SESSION['user_id'],
                'contest_id' => $data['contest_id']
            ]);

            // Return success response
            $this->jsonResponse([
                'success' => true,
                'message' => 'Vote submitted successfully',
                'vote_id' => $voteId
            ]);

        } catch (Exception $e) {
            Logger::error('Vote error: ' . $e->getMessage(), [
                'user_id' => $_SESSION['user_id'] ?? null,
                'contest_id' => $_POST['contest_id'] ?? null
            ]);

            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validate vote data
     */
    private function validateVoteData($data) {
        $validator = new Validator();

        $rules = [
            'contest_id' => 'required|integer|min:1',
            'contestant_id' => 'required|integer|min:1'
        ];

        if (!$validator->validate($data, $rules)) {
            throw new Exception('Invalid vote data: ' . implode(', ', $validator->getErrors()));
        }

        return $data;
    }

    /**
     * Get voting statistics
     */
    public function getStats($contestId) {
        try {
            $stats = $this->voteService->getContestStats($contestId);

            $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Logger::error('Get stats error: ' . $e->getMessage());

            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Get user voting history
     */
    public function getUserHistory() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not authenticated');
            }

            $history = $this->voteService->getUserVotingHistory($_SESSION['user_id']);

            $this->jsonResponse([
                'success' => true,
                'data' => $history
            ]);

        } catch (Exception $e) {
            Logger::error('Get user history error: ' . $e->getMessage());

            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to get voting history'
            ], 500);
        }
    }

    /**
     * JSON Response helper
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    $controller = new VoteController();

    switch ($_POST['action']) {
        case 'vote':
            $controller->vote();
            break;
        case 'get_stats':
            $controller->getStats($_POST['contest_id'] ?? null);
            break;
        case 'get_history':
            $controller->getUserHistory();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
