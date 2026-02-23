<?php
/**
 * Subscription Model
 * Space of Hope - Phase 2
 * Handles user-to-user subscriptions (following system)
 */

require_once __DIR__ . '/../config/database.php';

class Subscription {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /**
     * Subscribe to a user
     * @param int $subscriber_id User doing the subscribing
     * @param int $subscribed_to_id User being subscribed to
     * @return bool Success status
     */
    public function subscribe($subscriber_id, $subscribed_to_id) {
        // Prevent self-subscription
        if ($subscriber_id == $subscribed_to_id) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO user_subscriptions (subscriber_id, subscribed_to_id)
                VALUES (?, ?)
            ");
            return $stmt->execute([$subscriber_id, $subscribed_to_id]);
        } catch (PDOException $e) {
            error_log('Subscribe error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsubscribe from a user
     * @param int $subscriber_id User doing the unsubscribing
     * @param int $subscribed_to_id User being unsubscribed from
     * @return bool Success status
     */
    public function unsubscribe($subscriber_id, $subscribed_to_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_subscriptions
                WHERE subscriber_id = ? AND subscribed_to_id = ?
            ");
            return $stmt->execute([$subscriber_id, $subscribed_to_id]);
        } catch (PDOException $e) {
            error_log('Unsubscribe error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user A is subscribed to user B
     * @param int $subscriber_id
     * @param int $subscribed_to_id
     * @return bool
     */
    public function isSubscribed($subscriber_id, $subscribed_to_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM user_subscriptions
                WHERE subscriber_id = ? AND subscribed_to_id = ?
            ");
            $stmt->execute([$subscriber_id, $subscribed_to_id]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('Is subscribed check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of users who subscribe to this user (subscribers)
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array Subscribers array
     */
    public function getSubscribers($user_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.full_name, u.is_verified,
                       p.avatar_type, p.avatar_path,
                       s.created_at as subscribed_at
                FROM user_subscriptions s
                INNER JOIN users u ON s.subscriber_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE s.subscribed_to_id = ? AND u.is_active = 1
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get subscribers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get list of users this user subscribes to (subscriptions)
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array Subscriptions array
     */
    public function getSubscriptions($user_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.full_name, u.is_verified,
                       p.avatar_type, p.avatar_path,
                       s.created_at as subscribed_at
                FROM user_subscriptions s
                INNER JOIN users u ON s.subscribed_to_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE s.subscriber_id = ? AND u.is_active = 1
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get subscriptions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get subscriber count for a user
     * @param int $user_id
     * @return int Count
     */
    public function getSubscriberCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM user_subscriptions s
                INNER JOIN users u ON s.subscriber_id = u.id
                WHERE s.subscribed_to_id = ? AND u.is_active = 1
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log('Get subscriber count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get subscription count for a user
     * @param int $user_id
     * @return int Count
     */
    public function getSubscriptionCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM user_subscriptions s
                INNER JOIN users u ON s.subscribed_to_id = u.id
                WHERE s.subscriber_id = ? AND u.is_active = 1
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log('Get subscription count error: ' . $e->getMessage());
            return 0;
        }
    }
}
