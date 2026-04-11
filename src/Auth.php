<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Register a new user
     */
    public function register($email, $password, $fullName) {
        // Validate input
        if (!isValidEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Check if user already exists
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password and insert user
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO users (email, password, full_name)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $email,
                hashPassword($password),
                sanitize($fullName)
            ]);

            return ['success' => true, 'message' => 'Registration successful. Please sign in.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        // Find user
        $stmt = $this->pdo->prepare('SELECT id, email, full_name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Set session
        startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name']
        ];

        return ['success' => true, 'message' => 'Logged in successfully'];
    }

    /**
     * Logout user
     */
    public function logout() {
        startSession();
        session_destroy();
        return true;
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare('SELECT id, email, full_name, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
