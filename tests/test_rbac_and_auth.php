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

// 5. Test Registering a new Donor
$newDonorData = [
    'username'        => 'test_donor_' . time(),
    'email'           => 'test_donor_' . time() . '@example.com',
    'password'        => 'TestDonor@123',
    'phone'           => '017' . substr((string)time(), -8),
    'full_name'       => 'Test Donor Automation',
    'date_of_birth'   => '1998-05-15',
    'gender'          => 'FEMALE',
    'blood_group_id'  => 3, // B+
    'city'            => 'Sylhet',
    'address'         => 'Zindabazar'
];
$regRes = AuthService::registerDonor($newDonorData);
assert($regRes['success'] === true, "New donor registration failed: " . ($regRes['message'] ?? ''));
echo "  [OK] New donor registration passed: {$newDonorData['username']}\n";

// 6. Test Logging in as the new Donor
$newLogin = AuthService::authenticate($newDonorData['username'], 'TestDonor@123');
assert($newLogin['success'] === true, "New donor login failed");
assert($newLogin['user']['role_name'] === 'DONOR', "New donor role mismatch");
echo "  [OK] New registered donor login verified\n";

echo ">>> PASS: Authentication and RBAC tests verified successfully!\n\n";
