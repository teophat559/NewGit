<?php
namespace BVOTE\Services;

use BVOTE\Core\Database;
use BVOTE\Core\Logger;
use BVOTE\Core\Cache;

/**
 * BVOTE Vote Service
 * Xử lý logic voting và contest management
 */
class VoteService {
    private $db;
    private $cache;

    public function __construct() {
        $this->db = new Database(
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_DATABASE'] ?? 'bvote_system',
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            (int)($_ENV['DB_PORT'] ?? 3306),
            $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        );

        $this->cache = new Cache();
    }

    /**
     * Get contest by ID
     */
    public function getContest(int $contestId): ?array {
        $cacheKey = "contest:{$contestId}";

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $contest = $this->db->selectOne('contests', 'id = :id', ['id' => $contestId]);

            if ($contest) {
                // Cache for 5 minutes
                $this->cache->set($cacheKey, $contest, 300);
            }

            return $contest;

        } catch (\Exception $e) {
            Logger::error('Error getting contest: ' . $e->getMessage(), ['contest_id' => $contestId]);
            return null;
        }
    }

    /**
     * Check if user has already voted
     */
    public function hasUserVoted(int $userId, int $contestId): bool {
        $cacheKey = "user_vote:{$userId}:{$contestId}";

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $vote = $this->db->selectOne(
                'votes',
                'user_id = :user_id AND contest_id = :contest_id',
                ['user_id' => $userId, 'contest_id' => $contestId]
            );

            $hasVoted = $vote !== null;

            // Cache for 1 minute
            $this->cache->set($cacheKey, $hasVoted, 60);

            return $hasVoted;

        } catch (\Exception $e) {
            Logger::error('Error checking user vote: ' . $e->getMessage(), [
                'user_id' => $userId,
                'contest_id' => $contestId
            ]);
            return false;
        }
    }

    /**
     * Create new vote
     */
    public function createVote(array $voteData): int {
        try {
            // Begin transaction
            $this->db->beginTransaction();

            // Insert vote
            $voteId = $this->db->insert('votes', $voteData);

            // Update contestant vote count
            $this->db->update(
                'contestants',
                ['vote_count = vote_count + 1'],
                'id = :id',
                ['id' => $voteData['contestant_id']]
            );

            // Update contest total votes
            $this->db->update(
                'contests',
                ['total_votes = total_votes + 1'],
                'id = :id',
                ['id' => $voteData['contest_id']]
            );

            // Commit transaction
            $this->db->commit();

            // Clear related cache
            $this->clearVoteCache($voteData['user_id'], $voteData['contest_id']);

            Logger::info('Vote created successfully', [
                'vote_id' => $voteId,
                'user_id' => $voteData['user_id'],
                'contest_id' => $voteData['contest_id']
            ]);

            return $voteId;

        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();

            Logger::error('Error creating vote: ' . $e->getMessage(), $voteData);
            throw $e;
        }
    }

    /**
     * Get contest statistics
     */
    public function getContestStats(int $contestId): array {
        $cacheKey = "contest_stats:{$contestId}";

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Get contest info
            $contest = $this->db->selectOne('contests', 'id = :id', ['id' => $contestId]);
            if (!$contest) {
                return [];
            }

            // Get contestants with vote counts
            $contestants = $this->db->select(
                'contestants',
                'contest_id = :contest_id',
                ['contest_id' => $contestId],
                ['id', 'name', 'photo', 'vote_count', 'description'],
                'vote_count DESC'
            );

            // Calculate percentages
            $totalVotes = $contest['total_votes'] ?? 0;
            foreach ($contestants as &$contestant) {
                $contestant['percentage'] = $totalVotes > 0 ? round(($contestant['vote_count'] / $totalVotes) * 100, 2) : 0;
            }

            $stats = [
                'contest' => $contest,
                'contestants' => $contestants,
                'total_votes' => $totalVotes,
                'total_contestants' => count($contestants),
                'last_updated' => date('Y-m-d H:i:s')
            ];

            // Cache for 2 minutes
            $this->cache->set($cacheKey, $stats, 120);

            return $stats;

        } catch (\Exception $e) {
            Logger::error('Error getting contest stats: ' . $e->getMessage(), ['contest_id' => $contestId]);
            return [];
        }
    }

    /**
     * Get user voting history
     */
    public function getUserVotingHistory(int $userId, int $limit = 20): array {
        try {
            $votes = $this->db->select(
                'votes v
                 JOIN contests c ON v.contest_id = c.id
                 JOIN contestants ct ON v.contestant_id = ct.id',
                'v.user_id = :user_id',
                ['user_id' => $userId],
                [
                    'v.id as vote_id',
                    'v.created_at as vote_date',
                    'c.name as contest_name',
                    'c.id as contest_id',
                    'ct.name as contestant_name',
                    'ct.photo as contestant_photo'
                ],
                'v.created_at DESC',
                $limit
            );

            return $votes;

        } catch (\Exception $e) {
            Logger::error('Error getting user voting history: ' . $e->getMessage(), ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get active contests
     */
    public function getActiveContests(int $limit = 10): array {
        $cacheKey = 'active_contests';

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $contests = $this->db->select(
                'contests',
                'status = :status AND start_date <= :now AND end_date >= :now',
                ['status' => 'active', 'now' => date('Y-m-d H:i:s')],
                ['id', 'name', 'description', 'start_date', 'end_date', 'total_votes', 'photo'],
                'start_date DESC',
                $limit
            );

            // Cache for 5 minutes
            $this->cache->set($cacheKey, $contests, 300);

            return $contests;

        } catch (\Exception $e) {
            Logger::error('Error getting active contests: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get upcoming contests
     */
    public function getUpcomingContests(int $limit = 5): array {
        try {
            return $this->db->select(
                'contests',
                'status = :status AND start_date > :now',
                ['status' => 'upcoming', 'now' => date('Y-m-d H:i:s')],
                ['id', 'name', 'description', 'start_date', 'end_date', 'photo'],
                'start_date ASC',
                $limit
            );

        } catch (\Exception $e) {
            Logger::error('Error getting upcoming contests: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get contest results
     */
    public function getContestResults(int $contestId): array {
        $cacheKey = "contest_results:{$contestId}";

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $contest = $this->db->selectOne('contests', 'id = :id', ['id' => $contestId]);
            if (!$contest) {
                return [];
            }

            // Get top contestants
            $topContestants = $this->db->select(
                'contestants',
                'contest_id = :contest_id',
                ['contest_id' => $contestId],
                ['id', 'name', 'photo', 'vote_count', 'description'],
                'vote_count DESC',
                10
            );

            // Calculate rankings
            $rank = 1;
            foreach ($topContestants as &$contestant) {
                $contestant['rank'] = $rank++;
                $contestant['percentage'] = $contest['total_votes'] > 0 ?
                    round(($contestant['vote_count'] / $contest['total_votes']) * 100, 2) : 0;
            }

            $results = [
                'contest' => $contest,
                'top_contestants' => $topContestants,
                'total_votes' => $contest['total_votes'] ?? 0,
                'generated_at' => date('Y-m-d H:i:s')
            ];

            // Cache for 10 minutes
            $this->cache->set($cacheKey, $results, 600);

            return $results;

        } catch (\Exception $e) {
            Logger::error('Error getting contest results: ' . $e->getMessage(), ['contest_id' => $contestId]);
            return [];
        }
    }

    /**
     * Validate vote data
     */
    public function validateVote(array $voteData): array {
        $errors = [];

        // Required fields
        if (empty($voteData['contest_id'])) {
            $errors[] = 'Contest ID is required';
        }

        if (empty($voteData['contestant_id'])) {
            $errors[] = 'Contestant ID is required';
        }

        if (empty($voteData['user_id'])) {
            $errors[] = 'User ID is required';
        }

        // Validate contest exists and is active
        if (!empty($voteData['contest_id'])) {
            $contest = $this->getContest((int)$voteData['contest_id']);
            if (!$contest) {
                $errors[] = 'Contest not found';
            } elseif ($contest['status'] !== 'active') {
                $errors[] = 'Contest is not active';
            }
        }

        // Validate contestant exists
        if (!empty($voteData['contestant_id'])) {
            try {
                $contestant = $this->db->selectOne(
                    'contestants',
                    'id = :id AND contest_id = :contest_id',
                    ['id' => $voteData['contestant_id'], 'contest_id' => $voteData['contest_id']]
                );

                if (!$contestant) {
                    $errors[] = 'Contestant not found in this contest';
                }
            } catch (\Exception $e) {
                $errors[] = 'Error validating contestant';
            }
        }

        return $errors;
    }

    /**
     * Clear vote-related cache
     */
    private function clearVoteCache(int $userId, int $contestId): void {
        $this->cache->delete("user_vote:{$userId}:{$contestId}");
        $this->cache->delete("contest_stats:{$contestId}");
        $this->cache->delete("contest_results:{$contestId}");
        $this->cache->delete('active_contests');
    }

    /**
     * Get voting analytics
     */
    public function getVotingAnalytics(int $contestId, string $period = 'daily'): array {
        try {
            $sql = match($period) {
                'hourly' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
                'daily' => "DATE(created_at)",
                'weekly' => "YEARWEEK(created_at)",
                'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "DATE(created_at)"
            };

            $analytics = $this->db->raw("
                SELECT
                    {$sql} as period,
                    COUNT(*) as vote_count,
                    COUNT(DISTINCT user_id) as unique_voters
                FROM votes
                WHERE contest_id = :contest_id
                GROUP BY {$sql}
                ORDER BY period DESC
                LIMIT 30
            ", ['contest_id' => $contestId]);

            return $analytics->fetchAll();

        } catch (\Exception $e) {
            Logger::error('Error getting voting analytics: ' . $e->getMessage(), ['contest_id' => $contestId]);
            return [];
        }
    }

    /**
     * Get service statistics
     */
    public function getStats(): array {
        try {
            $stats = [
                'total_contests' => $this->db->count('contests'),
                'active_contests' => $this->db->count('contests', 'status = :status', ['status' => 'active']),
                'total_votes' => $this->db->count('votes'),
                'total_contestants' => $this->db->count('contestants'),
                'total_users_voted' => $this->db->count('votes', '1', [], ['DISTINCT user_id']),
                'cache_stats' => $this->cache->getStats()
            ];

            return $stats;

        } catch (\Exception $e) {
            Logger::error('Error getting vote service stats: ' . $e->getMessage());
            return [];
        }
    }
}
