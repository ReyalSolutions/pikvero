<?php
namespace App\Application\Services;

use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\AuditLogRepository;
use App\Core\Auth\Auth;
use App\Core\Database\Connection;
use Exception;

class AuthService {
    private UserRepository $userRepo;
    private AuditLogRepository $auditRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    public function login(string $identifier, string $password): array {
        $user = $this->userRepo->findByEmailOrUsername($identifier);
        if (!$user) {
            throw new Exception("Invalid username/email or password.");
        }

        if ($user['status'] !== 'active') {
            throw new Exception("Account is inactive or suspended. Please contact administrator.");
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid username/email or password.");
        }

        Auth::login($user);
        $this->auditRepo->log((int)$user['id'], 'user.login', 'Authentication', 'User logged in successfully');

        return [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => $user['role_name'],
            'organization_id' => $user['organization_id'] ? (int)$user['organization_id'] : null
        ];
    }

    public function register(array $data): array {
        if (!empty($data['email'])) {
            $existingEmail = $this->userRepo->findByEmail($data['email']);
            if ($existingEmail) {
                throw new Exception("An account with this email address already exists.");
            }
        }

        if (!empty($data['username'])) {
            $existingUsername = $this->userRepo->findByUsername($data['username']);
            if ($existingUsername) {
                throw new Exception("This username is already taken. Please choose another.");
            }
        }

        if (!empty($data['phone'])) {
            $existingPhone = $this->userRepo->findByPhone($data['phone']);
            if ($existingPhone) {
                throw new Exception("This phone number is already registered to another account.");
            }
        }

        $roleName = ($data['account_type'] ?? '') === 'owner' ? 'court_owner' : 'customer';
        $roleId = $this->userRepo->getRoleIdByName($roleName);

        $db = Connection::getInstance();
        $db->beginTransaction();

        try {
            $userId = $this->userRepo->create([
                'username' => $data['username'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'phone' => $data['phone'] ?? null,
                'role_id' => $roleId
            ]);

            $orgId = null;
            if ($roleName === 'court_owner') {
                $orgName = !empty($data['organization_name']) ? $data['organization_name'] : ($data['first_name'] . "'s Pickleball Club");
                $db->execute("INSERT INTO organizations (name, owner_id, status) VALUES (?, ?, 'active')", [$orgName, $userId], 'si');
                $orgId = $db->getLastInsertId();
            }

            $db->commit();

            $user = $this->userRepo->findById($userId);
            Auth::login($user);

            $this->auditRepo->log($userId, 'user.register', 'Authentication', "New user registered as {$roleName}");

            return [
                'id' => $userId,
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role_name'],
                'organization_id' => $orgId
            ];
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
