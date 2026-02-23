<?php
/**
 * User Model
 * Space of Hope - Phase 2
 * Handles user authentication and profile management
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /**
     * Register a new user
     * @param array $data User registration data
     * @return array ['success' => bool, 'user_id' => int, 'message' => string]
     */
    public function register($data) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'full_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '{$field}' is required"];
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Validate username (alphanumeric, underscore, 3-50 chars)
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
                return ['success' => false, 'message' => 'Username must be 3-50 characters (letters, numbers, underscore only)'];
            }

            // Validate password strength (min 8 chars)
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }

            // Check if username already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already taken'];
            }

            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            // Hash password
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, full_name, is_reader, is_author, is_community_owner, language_preference)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $is_reader = isset($data['is_reader']) ? (bool)$data['is_reader'] : true;
            $is_author = isset($data['is_author']) ? (bool)$data['is_author'] : false;
            $is_community_owner = isset($data['is_community_owner']) ? (bool)$data['is_community_owner'] : false;
            $language_preference = isset($data['language_preference']) ? $data['language_preference'] : 'en';

            $stmt->execute([
                $data['username'],
                $data['email'],
                $password_hash,
                $data['full_name'],
                $is_reader,
                $is_author,
                $is_community_owner,
                $language_preference
            ]);

            $user_id = $this->pdo->lastInsertId();

            // Create default profile
            $stmt = $this->pdo->prepare("
                INSERT INTO user_profiles (user_id, avatar_type)
                VALUES (?, 'generated')
            ");
            $stmt->execute([$user_id]);

            return [
                'success' => true,
                'user_id' => $user_id,
                'message' => 'Registration successful'
            ];

        } catch (PDOException $e) {
            error_log('User registration error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Authenticate user with username/email and password
     * @param string $identifier Username or email
     * @param string $password Plain text password
     * @return array ['success' => bool, 'user' => array|null, 'message' => string]
     */
    public function authenticate($identifier, $password) {
        try {
            // Find user by username or email
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password_hash, full_name, is_verified, is_active, language_preference
                FROM users
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials', 'user' => null];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid credentials', 'user' => null];
            }

            // Remove password hash from returned data
            unset($user['password_hash']);

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Login successful'
            ];

        } catch (PDOException $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed', 'user' => null];
        }
    }

    /**
     * Get user by ID
     * @param int $user_id
     * @return array|null User data or null
     */
    public function getById($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.full_name, u.is_reader, u.is_author, 
                       u.is_community_owner, u.is_verified, u.language_preference, u.created_at,
                       p.bio, p.avatar_type, p.avatar_path
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.id = ? AND u.is_active = 1
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get user by ID error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by username
     * @param string $username
     * @return array|null User data or null
     */
    public function getByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.full_name, u.is_reader, u.is_author, 
                       u.is_community_owner, u.is_verified, u.language_preference, u.created_at,
                       p.bio, p.avatar_type, p.avatar_path
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get user by username error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user profile
     * @param int $user_id
     * @param array $data Profile data (bio, avatar_type, avatar_path)
     * @return bool Success status
     */
    public function updateProfile($user_id, $data) {
        try {
            $fields = [];
            $values = [];

            if (isset($data['bio'])) {
                $fields[] = "bio = ?";
                $values[] = $data['bio'];
            }

            if (isset($data['avatar_type'])) {
                $fields[] = "avatar_type = ?";
                $values[] = $data['avatar_type'];
            }

            if (isset($data['avatar_path'])) {
                $fields[] = "avatar_path = ?";
                $values[] = $data['avatar_path'];
            }

            if (empty($fields)) {
                return false;
            }

            $values[] = $user_id;
            $sql = "UPDATE user_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);

        } catch (PDOException $e) {
            error_log('Update profile error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Search users by username or full name
     * @param string $query Search query
     * @param bool $verified_only Only return verified users
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return array Users array
     */
    public function search($query, $verified_only = false, $limit = 20, $offset = 0) {
        try {
            $sql = "
                SELECT u.id, u.username, u.full_name, u.is_verified, u.is_author,
                       p.avatar_type, p.avatar_path
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.is_active = 1
                AND (u.username LIKE ? OR u.full_name LIKE ?)
            ";

            $params = ["%{$query}%", "%{$query}%"];

            if ($verified_only) {
                $sql .= " AND u.is_verified = 1";
            }

            $sql .= " ORDER BY u.is_verified DESC, u.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('User search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user is verified
     * @param int $user_id
     * @return bool
     */
    public function isVerified($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result ? (bool)$result['is_verified'] : false;
        } catch (PDOException $e) {
            error_log('Check verified error: ' . $e->getMessage());
            return false;
        }
    }
}
