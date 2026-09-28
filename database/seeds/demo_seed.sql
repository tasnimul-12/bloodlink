-- ============================================================
-- BloodLink Comprehensive Demo Seed Data
-- Database: bloodlink_db
-- ============================================================

USE bloodlink_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. USERS
-- Password for admin: Admin@123
-- Password for hospital staff: Hospital@123
-- Password for donors: Donor@123
-- ------------------------------------------------------------
INSERT INTO users (user_id, role_id, username, password_hash, email, phone, account_status) VALUES
(1, 1, 'admin', '$2y$10$hS.XMjJ7zBgmlHSMFJ9dle92rFJN00UQQRnNYHtAUfDJZv8FngeWi', 'admin@bloodlink.org', '01711000001', 'ACTIVE'),
(2, 3, 'square_staff', '$2y$10$2agKZ/.EYnaznCwvohnV1.MwPy6q1rl1zBLRQi0PF8fUSuxcFw.nq', 'staff@squarehospital.com', '01711000002', 'ACTIVE'),
(3, 3, 'dmc_staff', '$2y$10$2agKZ/.EYnaznCwvohnV1.MwPy6q1rl1zBLRQi0PF8fUSuxcFw.nq', 'staff@dmch.gov.bd', '01711000003', 'ACTIVE'),
(4, 3, 'evercare_staff', '$2y$10$2agKZ/.EYnaznCwvohnV1.MwPy6q1rl1zBLRQi0PF8fUSuxcFw.nq', 'staff@evercare.com', '01711000004', 'ACTIVE'),
(5, 2, 'rahim_donor', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'rahim@example.com', '01711000010', 'ACTIVE'),
(6, 2, 'karim_donor', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'karim@example.com', '01711000011', 'ACTIVE'),
(7, 2, 'sarah_donor', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'sarah@example.com', '01711000012', 'ACTIVE'),
(8, 2, 'tanvir_donor', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'tanvir@example.com', '01711000013', 'ACTIVE'),
(9, 2, 'nusrat_donor', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'nusrat@example.com', '01711000014', 'ACTIVE'),
(10, 2, 'zero_donor', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'zero@example.com', '01711000015', 'ACTIVE')
ON DUPLICATE KEY UPDATE account_status = VALUES(account_status);

-- Additional donor accounts for compatibility, eligibility, availability, and
-- account-status testing. Password for all accounts below: Donor@123
INSERT INTO users (user_id, role_id, username, password_hash, email, phone, account_status) VALUES
(101, 2, 'nadia_bnegative', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'nadia@example.com', '01711000016', 'ACTIVE'),
(102, 2, 'omar_abnegative', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'omar@example.com', '01711000017', 'ACTIVE'),
(103, 2, 'lima_opositive', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'lima@example.com', '01711000018', 'ACTIVE'),
(104, 2, 'sakib_unavailable', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'sakib@example.com', '01711000019', 'ACTIVE'),
(105, 2, 'mita_suspended', '$2y$10$kwqvwkTQv5EGthQYCVFMTeHp3Sre2Ew.La.CEKr.PkjnTPfd9k3fK', 'mita@example.com', '01711000020', 'SUSPENDED')
ON DUPLICATE KEY UPDATE account_status = VALUES(account_status);

-- ------------------------------------------------------------
-- 2. HOSPITALS
-- ------------------------------------------------------------
INSERT INTO hospitals (hospital_id, hospital_name, registration_number, address, city, contact_person, email, phone, approval_status, approved_by, approved_at) VALUES
(1, 'Square Hospital Ltd.', 'REG-HOSP-001', '18/F Bir Uttam Qazi Nuruzzaman Sarak, West Panthapath', 'Dhaka', 'Dr. Arman Hossain', 'info@squarehospital.com', '02-8144400', 'APPROVED', 1, NOW()),
(2, 'Dhaka Medical College Hospital', 'REG-HOSP-002', 'Secretariat Road, Ramna', 'Dhaka', 'Dr. Shamima Nasrin', 'info@dmch.gov.bd', '02-55165088', 'PENDING', NULL, NULL),
(3, 'Evercare Hospital Dhaka', 'REG-HOSP-003', 'Plot 81, Block E, Bashundhara R/A', 'Dhaka', 'Dr. Tariq Rahman', 'info@evercarebd.com', '09666710678', 'APPROVED', 1, NOW())
ON DUPLICATE KEY UPDATE hospital_name = VALUES(hospital_name);

-- ------------------------------------------------------------
-- 3. HOSPITAL STAFF
-- ------------------------------------------------------------
INSERT INTO hospital_staff (staff_id, hospital_id, user_id, staff_name, designation, staff_status, joined_at) VALUES
(1, 1, 2, 'Dr. Arman Hossain', 'Blood Bank Medical Officer', 'ACTIVE', '2025-01-10'),
(2, 2, 3, 'Dr. Shamima Nasrin', 'Emergency Ward Incharge', 'ACTIVE', '2025-02-15'),
(3, 3, 4, 'Dr. Tariq Rahman', 'Transfusion Specialist', 'ACTIVE', '2025-03-01')
ON DUPLICATE KEY UPDATE staff_name = VALUES(staff_name);

-- ------------------------------------------------------------
-- 4. DONORS
-- Blood group mapping:
-- 1: A+, 2: A-, 3: B+, 4: B-, 5: AB+, 6: AB-, 7: O+, 8: O-
-- ------------------------------------------------------------
INSERT INTO donors (donor_id, user_id, full_name, date_of_birth, gender, blood_group_id, address, city, availability_status, eligibility_status, next_eligible_date, registration_date) VALUES
(1, 5, 'Rahim Ahmed', '1995-04-12', 'MALE', 1, 'House 24, Road 7, Dhanmondi', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '2024-01-15'),
(2, 6, 'Karim Chowdhury', '1992-08-22', 'MALE', 8, 'Flat B3, Green Road', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), '2024-02-20'),
(3, 7, 'Sarah Khan', '1998-11-05', 'FEMALE', 5, 'House 12, Road 4, Banani', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '2024-03-10'),
(4, 8, 'Tanvir Islam', '1990-01-18', 'MALE', 3, 'GEC Circle, Nasirabad', 'Chittagong', 'UNAVAILABLE', 'NOT_ELIGIBLE', DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY), '2024-04-01'),
(5, 9, 'Nusrat Jahan', '1997-06-30', 'FEMALE', 7, 'Sector 4, Uttara', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), '2024-05-12'),
(6, 10, 'Farhan Kabir', '2001-09-14', 'MALE', 1, 'Mirpur 10, Section 2', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', NULL, '2024-06-01')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO donors (donor_id, user_id, full_name, date_of_birth, gender, blood_group_id, address, city, availability_status, eligibility_status, next_eligible_date, registration_date) VALUES
(101, 101, 'Nadia Rahman', '1996-02-14', 'FEMALE', 4, 'Road 10, Dhanmondi', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', NULL, '2025-01-10'),
(102, 102, 'Omar Hasan', '1991-07-19', 'MALE', 6, 'Road 2, Banani', 'Dhaka', 'AVAILABLE', 'ELIGIBLE', NULL, '2025-02-12'),
(103, 103, 'Lima Akter', '1999-09-02', 'FEMALE', 7, 'House 8, Uttara', 'Dhaka', 'AVAILABLE', 'NOT_ELIGIBLE', DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), '2025-03-15'),
(104, 104, 'Sakib Hossain', '1994-12-11', 'MALE', 3, 'GEC Circle', 'Chittagong', 'UNAVAILABLE', 'ELIGIBLE', NULL, '2025-04-20'),
(105, 105, 'Mita Sultana', '1997-05-25', 'FEMALE', 5, 'Zindabazar', 'Sylhet', 'AVAILABLE', 'ELIGIBLE', NULL, '2025-05-01')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), availability_status = VALUES(availability_status), eligibility_status = VALUES(eligibility_status), next_eligible_date = VALUES(next_eligible_date);

-- ------------------------------------------------------------
-- 5. STORAGE LOCATIONS
-- ------------------------------------------------------------
INSERT INTO storage_locations (storage_location_id, location_code, location_name, storage_type, temperature_min, temperature_max, capacity_units, location_status) VALUES
(1, 'SL-REF-A', 'Central Blood Bank Refrigerator A', 'REFRIGERATOR', 2.00, 6.00, 500, 'ACTIVE'),
(2, 'SL-FRZ-1', 'Plasma Ultra-Low Freezer 1', 'FREEZER', -30.00, -18.00, 300, 'ACTIVE'),
(3, 'SL-AGT-1', 'Platelet Agitator Station 1', 'PLATELET_AGITATOR', 20.00, 24.00, 150, 'ACTIVE'),
(4, 'SL-TRANSIT', 'Emergency Transit Holding Bay', 'OTHER', 4.00, 8.00, 100, 'ACTIVE')
ON DUPLICATE KEY UPDATE location_name = VALUES(location_name);

-- ------------------------------------------------------------
-- 6. DONATIONS
-- ------------------------------------------------------------
INSERT INTO donations (donation_id, donor_id, donation_type, donation_date, quantity_ml, screening_status, donation_status, notes) VALUES
(1, 1, 'WHOLE_BLOOD', '2024-03-01 10:30:00', 450.00, 'PASSED', 'COMPLETED', 'First successful whole blood donation'),
(2, 1, 'WHOLE_BLOOD', '2024-07-05 11:15:00', 450.00, 'PASSED', 'COMPLETED', 'Second regular donation'),
(3, 1, 'WHOLE_BLOOD', '2024-11-10 09:45:00', 450.00, 'PASSED', 'COMPLETED', 'Third donation, reached Silver level'),
(4, 2, 'WHOLE_BLOOD', '2024-06-15 14:00:00', 450.00, 'PASSED', 'COMPLETED', 'O- negative emergency drive donor'),
(5, 3, 'WHOLE_BLOOD', '2024-01-10 10:00:00', 450.00, 'PASSED', 'COMPLETED', 'Regular donation'),
(6, 3, 'PLATELET',    '2024-04-12 11:30:00', 250.00, 'PASSED', 'COMPLETED', 'Apheresis platelet donation'),
(7, 3, 'WHOLE_BLOOD', '2024-07-20 09:00:00', 450.00, 'PASSED', 'COMPLETED', 'Whole blood donation'),
(8, 3, 'PLATELET',    '2024-10-15 15:00:00', 250.00, 'PASSED', 'COMPLETED', 'Apheresis platelet donation'),
(9, 3, 'WHOLE_BLOOD', '2025-01-20 10:15:00', 450.00, 'PASSED', 'COMPLETED', 'Fifth donation, achieved Gold tier'),
(10, 5, 'WHOLE_BLOOD', '2024-09-01 12:00:00', 450.00, 'PASSED', 'COMPLETED', 'Standard donation')
ON DUPLICATE KEY UPDATE quantity_ml = VALUES(quantity_ml);

-- Non-successful donation records for admin filtering and validation tests.
INSERT INTO donations (donation_id, donor_id, donation_type, donation_date, quantity_ml, screening_status, donation_status, notes) VALUES
(101, 101, 'WHOLE_BLOOD', DATE_SUB(NOW(), INTERVAL 2 DAY), 450.00, 'PENDING', 'SCHEDULED', 'Upcoming appointment'),
(102, 102, 'PLASMA', DATE_SUB(NOW(), INTERVAL 20 DAY), 250.00, 'FAILED', 'REJECTED', 'Screening criteria not met'),
(103, 103, 'PLATELET', DATE_SUB(NOW(), INTERVAL 10 DAY), 250.00, 'PASSED', 'CANCELLED', 'Appointment cancelled by donor'),
(104, 105, 'PLASMA', DATE_SUB(NOW(), INTERVAL 5 DAY), 250.00, 'PASSED', 'COMPLETED', 'Plasma donation for component test')
ON DUPLICATE KEY UPDATE screening_status = VALUES(screening_status), donation_status = VALUES(donation_status), notes = VALUES(notes);

-- ------------------------------------------------------------
-- 7. DONOR RECOGNITION
-- ------------------------------------------------------------
INSERT INTO donor_recognition (donor_id, recognition_level_id, achieved_date, notes) VALUES
(1, 1, '2024-03-01', 'First donation achieved Bronze'),
(1, 2, '2024-11-10', '3 donations achieved Silver'),
(2, 1, '2024-06-15', 'First donation achieved Bronze'),
(3, 1, '2024-01-10', 'First donation achieved Bronze'),
(3, 2, '2024-07-20', '3 donations achieved Silver'),
(3, 3, '2025-01-20', '5 donations achieved Gold'),
(5, 1, '2024-09-01', 'First donation achieved Bronze')
ON DUPLICATE KEY UPDATE achieved_date = VALUES(achieved_date);

-- ------------------------------------------------------------
-- 8. BLOOD BAGS
-- Dates dynamically calculated from CURRENT_DATE for consistent testing:
-- Fresh, Near-Expiry (<= 5 days), Expired, and Discarded
-- ------------------------------------------------------------
INSERT INTO blood_bags (blood_bag_id, bag_number, donation_id, blood_group_id, component_type, collection_date, expiry_date, quantity_ml, status, storage_location_id) VALUES
-- Unit 1: A+ Whole Blood, Fresh, 25 days remaining
(1, 'BAG-A-101', 1, 1, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 25 DAY), 450.00, 'AVAILABLE', 1),
-- Unit 2: A+ Whole Blood, Near-Expiry, 5 days remaining (FEFO will pick this before Unit 1!)
(2, 'BAG-A-102', 2, 1, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 450.00, 'AVAILABLE', 1),
-- Unit 3: A+ Whole Blood, Urgent Near-Expiry, 2 days remaining (FEFO priority #1 for A+)
(3, 'BAG-A-103', 3, 1, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 33 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 450.00, 'AVAILABLE', 1),
-- Unit 4: O- Universal Donor Whole Blood, 20 days remaining
(4, 'BAG-O-201', 4, 8, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 450.00, 'AVAILABLE', 1),
-- Unit 5: O- Universal Donor Whole Blood, 30 days remaining
(5, 'BAG-O-202', 4, 8, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 450.00, 'AVAILABLE', 1),
-- Unit 6: B+ Red Blood Cells (RBC), 28 days remaining
(6, 'BAG-B-301', 5, 3, 'RBC', DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 28 DAY), 300.00, 'AVAILABLE', 1),
-- Unit 7: AB+ Plasma, Frozen shelf life 365 days
(7, 'BAG-AB-401', 6, 5, 'PLASMA', DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 345 DAY), 250.00, 'AVAILABLE', 2),
-- Unit 8: O+ Platelet, Short shelf life (Agitator)
(8, 'BAG-PLT-501', 8, 7, 'PLATELET', DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 200.00, 'AVAILABLE', 3),
-- Unit 9: Expired unit for testing FEFO exclusion
(9, 'BAG-EXP-901', 7, 1, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 450.00, 'EXPIRED', 1),
-- Unit 10: Discarded unit
(10, 'BAG-DIS-902', 10, 3, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 50 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), 450.00, 'DISCARDED', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Additional status and component fixtures: RESERVED, ISSUED, and PLASMA.
INSERT INTO blood_bags (blood_bag_id, bag_number, donation_id, blood_group_id, component_type, collection_date, expiry_date, quantity_ml, status, storage_location_id) VALUES
(101, 'BAG-BNEG-601', 101, 4, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 26 DAY), 450.00, 'ISSUED', 1),
(102, 'BAG-ABNEG-602', 102, 6, 'PLASMA', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 340 DAY), 250.00, 'ISSUED', 2),
(103, 'BAG-ABPOS-603', 104, 5, 'PLASMA', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 340 DAY), 250.00, 'AVAILABLE', 2),
(104, 'BAG-BNEG-602', 101, 4, 'WHOLE_BLOOD', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 26 DAY), 450.00, 'RESERVED', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- ------------------------------------------------------------
-- 9. INVENTORY MOVEMENTS (Initial Reception)
-- ------------------------------------------------------------
INSERT INTO inventory_movements (movement_id, blood_bag_id, from_location_id, to_location_id, movement_type, quantity_ml, reason, notes, performed_by, movement_date) VALUES
(1, 1, NULL, 1, 'RECEIVED', 450.00, 'Initial collection and testing passed', 'Whole blood unit stored in Ref A', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 2, NULL, 1, 'RECEIVED', 450.00, 'Initial collection and testing passed', 'Whole blood unit stored in Ref A', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(3, 3, NULL, 1, 'RECEIVED', 450.00, 'Initial collection and testing passed', 'Whole blood unit stored in Ref A', 1, DATE_SUB(NOW(), INTERVAL 33 DAY)),
(4, 4, NULL, 1, 'RECEIVED', 450.00, 'Initial collection and testing passed', 'O- unit stored in Ref A', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(5, 5, NULL, 1, 'RECEIVED', 450.00, 'Initial collection and testing passed', 'O- unit stored in Ref A', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 6, NULL, 1, 'RECEIVED', 300.00, 'Component separation completed', 'RBC unit stored in Ref A', 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(7, 7, NULL, 2, 'RECEIVED', 250.00, 'Plasma frozen at -25C', 'Plasma stored in Freezer 1', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(8, 8, NULL, 3, 'RECEIVED', 200.00, 'Apheresis platelets under agitation', 'Platelet unit stored in Agitator 1', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(9, 9, 1, NULL, 'RECEIVED', 450.00, 'Initial collection', 'Unit expired past 35 days', 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),
(10, 10, 1, NULL, 'DISCARDED', 450.00, 'Clot observed during inspection', 'Unit safely discarded', 1, DATE_SUB(NOW(), INTERVAL 15 DAY))
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

INSERT INTO inventory_movements (movement_id, blood_bag_id, from_location_id, to_location_id, movement_type, quantity_ml, reason, notes, performed_by, movement_date) VALUES
(101, 104, 1, 1, 'RESERVED', 450.00, 'Held for emergency request #101', 'Reserved test fixture', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(102, 102, 2, NULL, 'ISSUED', 250.00, 'Issued for plasma request', 'Issued test fixture', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(103, 103, 2, 4, 'TRANSFERRED', 250.00, 'Transferred to emergency holding', 'Transfer test fixture', 1, NOW()),
(104, 101, 1, NULL, 'ISSUED', 450.00, 'Fulfilled request #101', 'Issued partial fulfillment fixture', 2, DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE reason = VALUES(reason), notes = VALUES(notes);

UPDATE blood_bags SET storage_location_id = 4 WHERE blood_bag_id = 103;

-- ------------------------------------------------------------
-- 10. BLOOD REQUESTS
-- ------------------------------------------------------------
INSERT INTO blood_requests (request_id, hospital_id, requested_by, request_type, urgency, status, required_date, required_time, reason, special_notes, request_date) VALUES
-- Request 1: Pending Emergency request from Square Hospital (needs A+ Whole Blood)
(1, 1, 1, 'EMERGENCY', 'CRITICAL', 'PENDING', CURRENT_DATE, '18:00:00', 'Emergency trauma patient with massive hemorrhage', 'Urgent cross-match requested', NOW()),
-- Request 2: Fulfilled Routine request from Square Hospital
(2, 1, 1, 'ROUTINE', 'MEDIUM', 'FULFILLED', DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), '14:00:00', 'Elective orthopedic hip replacement', 'Completed successfully', DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- Request 3: Shortage / Matching request from Evercare Hospital for rare B- Whole Blood (triggers donor sourcing)
(3, 3, 3, 'SURGERY', 'HIGH', 'MATCHING', DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), '09:00:00', 'Scheduled pediatric cardiac surgery', 'No B- in central stock; donor matching initiated', NOW())
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO blood_requests (request_id, hospital_id, requested_by, request_type, urgency, status, required_date, required_time, reason, special_notes, request_date) VALUES
(101, 1, 1, 'EMERGENCY', 'HIGH', 'PARTIALLY_FULFILLED', CURRENT_DATE, '20:00:00', 'Partial stock test request', 'Requires additional compatible units', NOW()),
(102, 1, 1, 'ROUTINE', 'LOW', 'CANCELLED', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00', 'Cancelled elective procedure', 'Cancellation workflow fixture', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(103, 3, 3, 'OTHER', 'MEDIUM', 'EXPIRED', DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '12:00:00', 'Expired request workflow fixture', 'Required date has passed', DATE_SUB(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- ------------------------------------------------------------
-- 11. REQUEST ITEMS
-- ------------------------------------------------------------
INSERT INTO request_items (request_item_id, request_id, blood_group_id, component_type, quantity_requested, quantity_fulfilled) VALUES
-- For Request 1: Needs 900 mL (2 bags) of A+ Whole Blood; 0 fulfilled
(1, 1, 1, 'WHOLE_BLOOD', 900.00, 0.00),
-- For Request 2: Needed 450 mL of O- Whole Blood; 450 fulfilled
(2, 2, 8, 'WHOLE_BLOOD', 450.00, 450.00),
-- For Request 3: Needs 450 mL of B- Whole Blood; 0 fulfilled
(3, 3, 4, 'WHOLE_BLOOD', 450.00, 0.00)
ON DUPLICATE KEY UPDATE quantity_fulfilled = VALUES(quantity_fulfilled);

INSERT INTO request_items (request_item_id, request_id, blood_group_id, component_type, quantity_requested, quantity_fulfilled) VALUES
(101, 101, 4, 'WHOLE_BLOOD', 900.00, 450.00),
(102, 102, 5, 'PLASMA', 250.00, 0.00),
(103, 103, 7, 'PLATELET', 200.00, 0.00)
ON DUPLICATE KEY UPDATE quantity_fulfilled = VALUES(quantity_fulfilled);

-- ------------------------------------------------------------
-- 12. FULFILLMENTS & FULFILLMENT ITEMS (For Request 2)
-- Note: fulfilled_by uses user_id (1 = Admin or 2 = Staff)
-- ------------------------------------------------------------
INSERT INTO fulfillments (fulfillment_id, request_id, fulfilled_by, fulfillment_status, issued_at, notes, created_at) VALUES
(1, 2, 2, 'COMPLETED', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Dispatched via cold-chain courier to Square Hospital', DATE_SUB(NOW(), INTERVAL 2 DAY))
ON DUPLICATE KEY UPDATE fulfillment_status = VALUES(fulfillment_status);

INSERT INTO fulfillments (fulfillment_id, request_id, fulfilled_by, fulfillment_status, issued_at, notes, created_at) VALUES
(101, 101, 2, 'COMPLETED', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Partial allocation test fixture', DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE fulfillment_status = VALUES(fulfillment_status);

-- Complete the existing fulfilled request and represent the partial request
-- with fulfillment items, so reports and detail pages have consistent data.
UPDATE blood_bags SET status = 'ISSUED' WHERE blood_bag_id = 4;
INSERT INTO fulfillment_items (fulfillment_item_id, fulfillment_id, request_item_id, blood_bag_id, quantity_issued, issued_at) VALUES
(1, 1, 2, 4, 450.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(101, 101, 101, 101, 450.00, DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE quantity_issued = VALUES(quantity_issued);

-- ------------------------------------------------------------
-- 13. DONOR MATCHES & NOTIFICATIONS (For Request 3)
-- Donor Karim Chowdhury (O- universal donor) matched for B- request
-- ------------------------------------------------------------
INSERT INTO donor_matches (match_id, request_item_id, donor_id, match_score, match_reason, match_status, notified_at, response_at, created_at) VALUES
(1, 3, 2, 95.00, 'Universal donor (O-), eligible, available in Dhaka', 'NOTIFIED', NOW(), NULL, NOW())
ON DUPLICATE KEY UPDATE match_status = VALUES(match_status);

INSERT INTO notifications (notification_id, user_id, notification_type, title, message, related_match_id, is_read, created_at) VALUES
(1, 6, 'URGENT_MATCH', 'Urgent Emergency Blood Request in Dhaka', 'An urgent request for compatible blood has been posted by Evercare Hospital. Your blood type O- is compatible. Can you donate?', 1, FALSE, NOW()),
(2, 2, 'REQUEST_UPDATE', 'Request #2 Fulfilled', 'Your blood request for 450 mL O- Whole Blood has been completely fulfilled.', NULL, TRUE, DATE_SUB(NOW(), INTERVAL 2 DAY))
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ------------------------------------------------------------
-- 14. AUDIT LOGS
-- ------------------------------------------------------------
INSERT INTO audit_logs (audit_id, user_id, action, entity_name, entity_id, old_value, new_value, ip_address, created_at) VALUES
(1, 1, 'APPROVE_HOSPITAL', 'hospitals', '1', JSON_OBJECT('approval_status', 'PENDING'), JSON_OBJECT('approval_status', 'APPROVED'), '127.0.0.1', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 1, 'APPROVE_HOSPITAL', 'hospitals', '3', JSON_OBJECT('approval_status', 'PENDING'), JSON_OBJECT('approval_status', 'APPROVED'), '127.0.0.1', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(3, 1, 'RECORD_DONATION', 'donations', '1', NULL, JSON_OBJECT('donor_id', 1, 'type', 'WHOLE_BLOOD', 'quantity_ml', 450), '127.0.0.1', DATE_SUB(NOW(), INTERVAL 10 DAY))
ON DUPLICATE KEY UPDATE action = VALUES(action);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Comprehensive demo seed data loaded successfully.' AS status;
