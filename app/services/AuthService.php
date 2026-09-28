<?php
/**
 * BloodLink - Authentication and User Management Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/MatchingService.php';

class AuthService {
    public static function authenticate(string $login, string $password): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT u.user_id, u.role_id, r.role_name, u.username, u.email, u.phone, 
                   u.password_hash, u.account_status
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = :uname OR u.email = :uemail
            LIMIT 1
        ");
        $stmt->execute([':uname' => $login, ':uemail' => $login]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'No account found matching those credentials.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid password entered.'];
        }

        if ($user['account_status'] !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Your account is currently ' . $user['account_status'] . '. Please contact support.'
            ];
        }

        // For Hospital Staff, verify hospital approval status
        if ($user['role_name'] === 'HOSPITAL_STAFF') {
            $hospStmt = $pdo->prepare("
                SELECT h.hospital_id, h.hospital_name, h.approval_status, hs.staff_id, hs.staff_status
                FROM hospital_staff hs
                JOIN hospitals h ON hs.hospital_id = h.hospital_id
                WHERE hs.user_id = :user_id
                LIMIT 1
            ");
            $hospStmt->execute([':user_id' => $user['user_id']]);
            $hospInfo = $hospStmt->fetch();

            if ($hospInfo) {
                if ($hospInfo['staff_status'] !== 'ACTIVE') {
                    return ['success' => false, 'message' => 'Your hospital staff profile is inactive.'];
                }
                // Attach hospital info to session
                $user['hospital_id'] = $hospInfo['hospital_id'];
                $user['hospital_name'] = $hospInfo['hospital_name'];
                $user['hospital_approval_status'] = $hospInfo['approval_status'];
                $user['staff_id'] = $hospInfo['staff_id'];
            }
        } elseif ($user['role_name'] === 'DONOR') {
            $donorStmt = $pdo->prepare("
                SELECT donor_id, full_name, blood_group_id, city, availability_status, eligibility_status
                FROM donors
                WHERE user_id = :user_id
                LIMIT 1
            ");
            $donorStmt->execute([':user_id' => $user['user_id']]);
            $donorInfo = $donorStmt->fetch();
            if ($donorInfo) {
                $user['donor_id'] = $donorInfo['donor_id'];
                $user['full_name'] = $donorInfo['full_name'];
                $user['blood_group_id'] = $donorInfo['blood_group_id'];
                $user['city'] = $donorInfo['city'];
                $user['availability_status'] = $donorInfo['availability_status'];
                $user['eligibility_status'] = $donorInfo['eligibility_status'];
            }
        }

        // Update last login
        $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :user_id");
        $upd->execute([':user_id' => $user['user_id']]);

        // Regenerate session id to prevent session fixation
        Session::regenerate();
        unset($user['password_hash']);
        Session::set('user', $user);

        AuditService::log('LOGIN', 'users', $user['user_id'], null, ['username' => $user['username']], (int)$user['user_id']);

        return ['success' => true, 'user' => $user];
    }

    public static function registerDonor(array $data): array {
        $pdo = Database::getConnection();

        // Validation
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone    = trim($data['phone'] ?? '');
        $fullName = trim($data['full_name'] ?? '');
        $dob      = trim($data['date_of_birth'] ?? '');
        $gender   = trim($data['gender'] ?? 'MALE');
        $groupId  = (int)($data['blood_group_id'] ?? 0);
        $city     = trim($data['city'] ?? '');
        $address  = trim($data['address'] ?? '');

        if (!$username || !$email || !$password || !$fullName || !$dob || !$groupId || !$city) {
            return ['success' => false, 'message' => 'Please fill in all required fields.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        // Check duplicates
        $check = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Username or email already registered.'];
        }

        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            // Insert user
            $userStmt = $pdo->prepare("
                INSERT INTO users (role_id, username, password_hash, email, phone, account_status)
                VALUES (2, :username, :hash, :email, :phone, 'ACTIVE')
            ");
            $userStmt->execute([
                ':username' => $username,
                ':hash'     => $hash,
                ':email'    => $email,
                ':phone'    => $phone ?: null
            ]);
            $userId = (int)$pdo->lastInsertId();

            // Insert donor
            $donorStmt = $pdo->prepare("
                INSERT INTO donors (
                    user_id, full_name, date_of_birth, gender, blood_group_id, address, city,
                    availability_status, eligibility_status, registration_date
                ) VALUES (
                    :user_id, :full_name, :dob, :gender, :blood_group_id, :address, :city,
                    'AVAILABLE', 'ELIGIBLE', CURRENT_DATE
                )
            ");
            $donorStmt->execute([
                ':user_id'        => $userId,
                ':full_name'      => $fullName,
                ':dob'            => $dob,
                ':gender'         => $gender,
                ':blood_group_id' => $groupId,
                ':address'        => $address,
                ':city'           => $city
            ]);
            $donorId = (int)$pdo->lastInsertId();

            AuditService::log('REGISTER_DONOR', 'donors', $donorId, null, ['full_name' => $fullName, 'city' => $city], $userId);

            $pdo->commit();
            try {
                MatchingService::matchNewDonorToOpenRequests($donorId, $userId);
            } catch (Throwable $e) {
                error_log('Automatic donor matching failed: ' . $e->getMessage());
            }
            return ['success' => true, 'message' => 'Registration successful! You may now sign in.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Donor registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public static function registerHospitalStaff(array $data): array {
        $pdo = Database::getConnection();

        $username    = trim($data['username'] ?? '');
        $email       = trim($data['email'] ?? '');
        $password    = $data['password'] ?? '';
        $phone       = trim($data['phone'] ?? '');
        $staffName   = trim($data['staff_name'] ?? '');
        $designation = trim($data['designation'] ?? '');
        $hospitalId  = !empty($data['hospital_id']) ? (int)$data['hospital_id'] : null;

        // If new hospital
        $newHospitalName = trim($data['new_hospital_name'] ?? '');
        $newRegNumber    = trim($data['new_registration_number'] ?? '');
        $newCity         = trim($data['new_city'] ?? '');
        $newAddress      = trim($data['new_address'] ?? '');
        $newPhone        = trim($data['new_phone'] ?? '');

        if (!$username || !$email || !$password || !$staffName) {
            return ['success' => false, 'message' => 'Please fill in all required credentials.'];
        }

        // Check duplicates
        $check = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Username or email already registered.'];
        }

        $pdo->beginTransaction();
        try {
            // If registering a brand new hospital
            if (!$hospitalId && $newHospitalName && $newRegNumber) {
                $hospStmt = $pdo->prepare("
                    INSERT INTO hospitals (hospital_name, registration_number, address, city, contact_person, email, phone, approval_status)
                    VALUES (:name, :reg, :address, :city, :contact, :email, :phone, 'PENDING')
                ");
                $hospStmt->execute([
                    ':name'    => $newHospitalName,
                    ':reg'     => $newRegNumber,
                    ':address' => $newAddress ?: 'Pending Address',
                    ':city'    => $newCity ?: 'Dhaka',
                    ':contact' => $staffName,
                    ':email'   => $email,
                    ':phone'   => $newPhone ?: $phone
                ]);
                $hospitalId = (int)$pdo->lastInsertId();
            }

            if (!$hospitalId) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Please select an existing hospital or enter new hospital details.'];
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (role_id, username, password_hash, email, phone, account_status)
                VALUES (3, :username, :hash, :email, :phone, 'ACTIVE')
            ");
            $userStmt->execute([
                ':username' => $username,
                ':hash'     => $hash,
                ':email'    => $email,
                ':phone'    => $phone ?: null
            ]);
            $userId = (int)$pdo->lastInsertId();

            $staffStmt = $pdo->prepare("
                INSERT INTO hospital_staff (hospital_id, user_id, staff_name, designation, staff_status, joined_at)
                VALUES (:hospital_id, :user_id, :staff_name, :designation, 'ACTIVE', CURRENT_DATE)
            ");
            $staffStmt->execute([
                ':hospital_id' => $hospitalId,
                ':user_id'     => $userId,
                ':staff_name'  => $staffName,
                ':designation' => $designation ?: 'Staff Officer'
            ]);

            AuditService::log('REGISTER_HOSPITAL_STAFF', 'hospital_staff', $userId, null, ['hospital_id' => $hospitalId], $userId);

            $pdo->commit();
            return ['success' => true, 'message' => 'Registration submitted! Hospital accounts undergo administrator verification before requests can be processed.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Staff registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
}
