<?php
namespace BVOTE\Services;

use BVOTE\Core\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * BVOTE Notification Service
 * Xử lý thông báo qua email, SMS, và push notifications
 */
class NotificationService {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->config = [
            'smtp_host' => $_ENV['MAIL_HOST'] ?? 'localhost',
            'smtp_port' => (int)($_ENV['MAIL_PORT'] ?? 587),
            'smtp_username' => $_ENV['MAIL_USERNAME'] ?? '',
            'smtp_password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'smtp_encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_email' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bvote.com',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'BVOTE System'
        ];
        
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer
     */
    private function initializeMailer(): void {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_encryption'];
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Default settings
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            Logger::error('Failed to initialize mailer: ' . $e->getMessage());
        }
    }
    
    /**
     * Send vote confirmation email
     */
    public function sendVoteConfirmation(int $userId, int $voteId): bool {
        try {
            // Get user and vote information
            $user = $this->getUserInfo($userId);
            $vote = $this->getVoteInfo($voteId);
            
            if (!$user || !$vote) {
                return false;
            }
            
            $subject = 'Vote Confirmation - BVOTE System';
            $body = $this->getVoteConfirmationTemplate($user, $vote);
            
            $this->mailer->addAddress($user['email'], $user['name']);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            $result = $this->mailer->send();
            
            if ($result) {
                Logger::info('Vote confirmation email sent', [
                    'user_id' => $userId,
                    'vote_id' => $voteId,
                    'email' => $user['email']
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Logger::error('Failed to send vote confirmation: ' . $e->getMessage(), [
                'user_id' => $userId,
                'vote_id' => $voteId
            ]);
            return false;
        }
    }
    
    /**
     * Send contest reminder
     */
    public function sendContestReminder(int $contestId, array $userIds): int {
        $sentCount = 0;
        
        try {
            $contest = $this->getContestInfo($contestId);
            if (!$contest) {
                return 0;
            }
            
            foreach ($userIds as $userId) {
                $user = $this->getUserInfo($userId);
                if (!$user) {
                    continue;
                }
                
                $subject = "Reminder: {$contest['name']} - Don't forget to vote!";
                $body = $this->getContestReminderTemplate($user, $contest);
                
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($user['email'], $user['name']);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
                
                if ($this->mailer->send()) {
                    $sentCount++;
                    Logger::info('Contest reminder sent', [
                        'user_id' => $userId,
                        'contest_id' => $contestId,
                        'email' => $user['email']
                    ]);
                }
            }
            
        } catch (Exception $e) {
            Logger::error('Failed to send contest reminders: ' . $e->getMessage(), [
                'contest_id' => $contestId
            ]);
        }
        
        return $sentCount;
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail(int $userId): bool {
        try {
            $user = $this->getUserInfo($userId);
            if (!$user) {
                return false;
            }
            
            $subject = 'Welcome to BVOTE System!';
            $body = $this->getWelcomeEmailTemplate($user);
            
            $this->mailer->addAddress($user['email'], $user['name']);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            $result = $this->mailer->send();
            
            if ($result) {
                Logger::info('Welcome email sent', [
                    'user_id' => $userId,
                    'email' => $user['email']
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Logger::error('Failed to send welcome email: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(int $userId, string $resetToken): bool {
        try {
            $user = $this->getUserInfo($userId);
            if (!$user) {
                return false;
            }
            
            $subject = 'Password Reset Request - BVOTE System';
            $body = $this->getPasswordResetTemplate($user, $resetToken);
            
            $this->mailer->addAddress($user['email'], $user['name']);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            $result = $this->mailer->send();
            
            if ($result) {
                Logger::info('Password reset email sent', [
                    'user_id' => $userId,
                    'email' => $user['email']
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Logger::error('Failed to send password reset email: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Send SMS notification (placeholder for future implementation)
     */
    public function sendSMS(string $phoneNumber, string $message): bool {
        // TODO: Implement SMS service integration
        Logger::info('SMS notification (placeholder)', [
            'phone' => $phoneNumber,
            'message' => $message
        ]);
        
        return true;
    }
    
    /**
     * Send push notification (placeholder for future implementation)
     */
    public function sendPushNotification(int $userId, string $title, string $message, array $data = []): bool {
        // TODO: Implement push notification service
        Logger::info('Push notification (placeholder)', [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'data' => $data
        ]);
        
        return true;
    }
    
    /**
     * Get user information
     */
    private function getUserInfo(int $userId): ?array {
        try {
            $db = new \BVOTE\Core\Database(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_DATABASE'] ?? 'bvote_system',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                (int)($_ENV['DB_PORT'] ?? 3306),
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );
            
            return $db->selectOne('users', 'id = :id', ['id' => $userId]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get user info: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get vote information
     */
    private function getVoteInfo(int $voteId): ?array {
        try {
            $db = new \BVOTE\Core\Database(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_DATABASE'] ?? 'bvote_system',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                (int)($_ENV['DB_PORT'] ?? 3306),
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );
            
            return $db->selectOne('votes', 'id = :id', ['id' => $voteId]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get vote info: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get contest information
     */
    private function getContestInfo(int $contestId): ?array {
        try {
            $db = new \BVOTE\Core\Database(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_DATABASE'] ?? 'bvote_system',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                (int)($_ENV['DB_PORT'] ?? 3306),
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );
            
            return $db->selectOne('contests', 'id = :id', ['id' => $contestId]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get contest info: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Email templates
     */
    private function getVoteConfirmationTemplate(array $user, array $vote): string {
        return "
        <html>
        <body>
            <h2>Vote Confirmation</h2>
            <p>Dear {$user['name']},</p>
            <p>Your vote has been successfully recorded!</p>
            <p><strong>Vote ID:</strong> {$vote['id']}</p>
            <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p>Thank you for participating in our voting system.</p>
            <br>
            <p>Best regards,<br>BVOTE Team</p>
        </body>
        </html>";
    }
    
    private function getContestReminderTemplate(array $user, array $contest): string {
        return "
        <html>
        <body>
            <h2>Contest Reminder</h2>
            <p>Dear {$user['name']},</p>
            <p>Don't forget to vote in the contest: <strong>{$contest['name']}</strong></p>
            <p><strong>End Date:</strong> {$contest['end_date']}</p>
            <p>Your vote matters! Participate now.</p>
            <br>
            <p>Best regards,<br>BVOTE Team</p>
        </body>
        </html>";
    }
    
    private function getWelcomeEmailTemplate(array $user): string {
        return "
        <html>
        <body>
            <h2>Welcome to BVOTE System!</h2>
            <p>Dear {$user['name']},</p>
            <p>Welcome to our advanced voting system!</p>
            <p>You can now:</p>
            <ul>
                <li>Participate in contests</li>
                <li>Vote for your favorite contestants</li>
                <li>Track voting results</li>
                <li>Manage your profile</li>
            </ul>
            <br>
            <p>Best regards,<br>BVOTE Team</p>
        </body>
        </html>";
    }
    
    private function getPasswordResetTemplate(array $user, string $resetToken): string {
        $resetUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $resetUrl .= "/reset-password?token={$resetToken}";
        
        return "
        <html>
        <body>
            <h2>Password Reset Request</h2>
            <p>Dear {$user['name']},</p>
            <p>You have requested a password reset for your BVOTE account.</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='{$resetUrl}'>{$resetUrl}</a></p>
            <p>If you didn't request this, please ignore this email.</p>
            <p><strong>This link will expire in 1 hour.</strong></p>
            <br>
            <p>Best regards,<br>BVOTE Team</p>
        </body>
        </html>";
    }
    
    /**
     * Get service statistics
     */
    public function getStats(): array {
        return [
            'email_service' => 'available',
            'smtp_host' => $this->config['smtp_host'],
            'smtp_port' => $this->config['smtp_port'],
            'from_email' => $this->config['from_email'],
            'sms_service' => 'placeholder',
            'push_notifications' => 'placeholder'
        ];
    }
}
