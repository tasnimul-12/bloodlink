<?php
/**
 * Test: Authentication, RBAC, and User Creation
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/AuthService.php';

echo "--- RUNNING TEST: Authentication & RBAC ---\n";

// 1. Valid Admin Login
$resAdmin = AuthService::authenticate('admin', 'Admin@123');
assert($resAdmin['success'] === true, "Admin login failed");
assert($resAdmin['user']['role_name'] === 'ADMIN', "Admin role mismatch");
echo "  [OK] Admin authentication passed (Role: {$resAdmin['user']['role_name']})\n";

// 2. Valid Hospital Staff Login
$resStaff = AuthService::authenticate('square_staff', 'Hospital@123');
assert($resStaff['success'] === true, "Staff login failed");
assert($resStaff['user']['role_name'] === 'HOSPITAL_STAFF', "Staff role mismatch");
assert(!empty($resStaff['user']['hospital_id']), "Hospital ID not attached");
echo "  [OK] Hospital Staff authentication passed (Hospital: {$resStaff['user']['hospital_name']})\n";

// 3. Valid Donor Login
$resDonor = AuthService::authenticate('rahim_donor', 'Donor@123');
assert($resDonor['success'] === true, "Donor login failed");
assert($resDonor['user']['role_name'] === 'DONOR', "Donor role mismatch");
assert(!empty($resDonor['user']['donor_id']), "Donor ID not attached");
echo "  [OK] Donor authentication passed (Donor: {$resDonor['user']['full_name']})\n";

// 4. Invalid Password Rejection
$badRes = AuthService::authenticate('admin', 'WrongPass@999');
assert($badRes['success'] === false, "Invalid password was unexpectedly accepted!");
echo "  [OK] Invalid password correctly rejected\n";

$staffRegistrationData = [
    'username' => 'test_staff_' . time(),
    'email' => 'test_staff_' . time() . '@example.com',
    'password' => 'TestStaff@123',
    'staff_name' => 'Test Hospital Staff',
    'designation' => 'Test Officer',
    'hospital_id' => 1
];
$staffRegistration = AuthService::registerHospitalStaff($staffRegistrationData);
assert($staffRegistration['success'] === true, 'Hospital staff registration failed: ' . ($staffRegistration['message'] ?? ''));
$staffStatusStmt = Database::getConnection()->prepare('SELECT hs.staff_status, u.user_id FROM hospital_staff hs JOIN users u ON u.user_id = hs.user_id WHERE u.username = :username');
$staffStatusStmt->execute([':username' => $staffRegistrationData['username']]);
$registeredStaff = $staffStatusStmt->fetch();
assert($registeredStaff && $registeredStaff['staff_status'] === 'INACTIVE', 'New hospital staff must remain inactive until administrator approval.');
$inactiveStaffLogin = AuthService::authenticate($staffRegistrationData['username'], $staffRegistrationData['password']);
assert($inactiveStaffLogin['success'] === false, 'Unapproved hospital staff must not be able to sign in.');
echo "  [OK] New hospital staff remains inactive until administrator approval\n";

$crossHospitalMatch = MatchingService::matchDonorsForRequest(3, 1, null, 1);
assert(
    !$crossHospitalMatch['success'] && str_contains($crossHospitalMatch['message'], 'does not belong'),
    'Hospital staff must not trigger matching for another hospital request.'
);
echo "  [OK] Cross-hospital donor matching is rejected\n";

// 5. Test Registering a new Donor
$newDonorData = [
    'username'        => 'test_donor_' . time(),
    'email'           => 'test_donor_' . time() . '@example.com',
    'password'        => 'TestDonor@123',
    'phone'           => '017' . substr((string)time(), -8),
    'full_name'       => 'Test Donor Automation',
    'date_of_birth'   => '1998-05-15',
    'gender'          => 'FEMALE',
    'blood_group_id'  => 8, // O-
    'city'            => 'Sylhet',
    'address'         => 'Zindabazar'
];
$regRes = AuthService::registerDonor($newDonorData);
assert($regRes['success'] === true, "New donor registration failed: " . ($regRes['message'] ?? ''));
$newDonorStmt = Database::getConnection()->prepare('SELECT donor_id, user_id FROM donors WHERE full_name = :full_name ORDER BY donor_id DESC LIMIT 1');
$newDonorStmt->execute([':full_name' => $newDonorData['full_name']]);
$newDonor = $newDonorStmt->fetch();
$newDonorMatchStmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM donor_matches WHERE donor_id = :donor_id AND match_status = 'NOTIFIED'");
$newDonorMatchStmt->execute([':donor_id' => $newDonor['donor_id']]);
assert((int)$newDonorMatchStmt->fetchColumn() > 0, 'A newly registered eligible donor must be matched to compatible open requests automatically.');
$newDonorNotificationStmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND notification_type = 'URGENT_MATCH'");
$newDonorNotificationStmt->execute([':user_id' => $newDonor['user_id']]);
assert((int)$newDonorNotificationStmt->fetchColumn() > 0, 'A newly registered compatible donor must receive an automatic match notification.');
echo "  [OK] New donor registration passed: {$newDonorData['username']}\n";

// 6. Test Logging in as the new Donor
$newLogin = AuthService::authenticate($newDonorData['username'], 'TestDonor@123');
assert($newLogin['success'] === true, "New donor login failed");
assert($newLogin['user']['role_name'] === 'DONOR', "New donor role mismatch");
echo "  [OK] New registered donor login verified\n";

echo ">>> PASS: Authentication and RBAC tests verified successfully!\n\n";
