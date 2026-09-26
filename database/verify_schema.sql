-- ============================================================
-- BloodLink Database Verification Script
-- Target: bloodlink_db
-- ============================================================

USE bloodlink_db;

SELECT '==============================================' AS '';
SELECT '1. VERIFYING 20 TABLES AND VIEWS' AS Section;
SELECT '==============================================' AS '';

SELECT 
    TABLE_NAME, 
    TABLE_TYPE, 
    ENGINE, 
    TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'bloodlink_db'
ORDER BY TABLE_TYPE, TABLE_NAME;

SELECT '==============================================' AS '';
SELECT '2. VERIFYING FOREIGN KEYS' AS Section;
SELECT '==============================================' AS '';

SELECT 
    CONSTRAINT_NAME, 
    TABLE_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'bloodlink_db' 
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT '==============================================' AS '';
SELECT '3. VERIFYING VIEWS' AS Section;
SELECT '==============================================' AS '';

SELECT 'Testing vw_available_inventory:' AS View_Test;
SELECT blood_bag_id, bag_number, blood_group, component_type, quantity_ml, expiry_date, location_code
FROM vw_available_inventory
LIMIT 5;

SELECT 'Testing vw_eligible_donors:' AS View_Test;
SELECT donor_id, full_name, blood_group, city, eligibility_status, availability_status
FROM vw_eligible_donors
LIMIT 5;

SELECT '==============================================' AS '';
SELECT '4. VERIFYING STORED PROCEDURE sp_find_fefo_inventory' AS Section;
SELECT '==============================================' AS '';

-- Procedure test for blood group 1 (A+), WHOLE_BLOOD
CALL sp_find_fefo_inventory(1, 'WHOLE_BLOOD', 450.00);

SELECT '==============================================' AS '';
SELECT '5. VERIFYING TRIGGERS' AS Section;
SELECT '==============================================' AS '';

SELECT 
    TRIGGER_NAME, 
    EVENT_MANIPULATION, 
    EVENT_OBJECT_TABLE, 
    ACTION_TIMING 
FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'bloodlink_db';

SELECT '==============================================' AS '';
SELECT '6. DBMS REQUIRED JOINS & AGGREGATIONS' AS Section;
SELECT '==============================================' AS '';

SELECT 'Aggregate: Available Blood Volume by Blood Group (SUM, COUNT, GROUP BY):' AS Query_Name;
SELECT 
    bg.group_name AS blood_group,
    bb.component_type,
    COUNT(bb.blood_bag_id) AS total_units,
    SUM(bb.quantity_ml) AS total_volume_ml,
    MIN(bb.expiry_date) AS earliest_expiry,
    MAX(bb.expiry_date) AS latest_expiry
FROM blood_bags bb
JOIN blood_groups bg ON bb.blood_group_id = bg.blood_group_id
WHERE bb.status = 'AVAILABLE'
GROUP BY bg.group_name, bb.component_type
ORDER BY bg.group_name;

SELECT 'DBMS Showcase: Donors with Zero Donation History (LEFT JOIN + HAVING total = 0):' AS Query_Name;
SELECT 
    d.donor_id,
    d.full_name,
    bg.group_name AS blood_group,
    d.city,
    COUNT(don.donation_id) AS total_donations
FROM donors d
JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
LEFT JOIN donations don ON d.donor_id = don.donor_id
GROUP BY d.donor_id, d.full_name, bg.group_name, d.city
HAVING total_donations = 0;

SELECT 'DBMS Showcase: Nested Subquery - Requests with Unfulfilled Items:' AS Query_Name;
SELECT 
    br.request_id,
    h.hospital_name,
    br.urgency,
    br.status
FROM blood_requests br
JOIN hospitals h ON br.hospital_id = h.hospital_id
WHERE br.request_id IN (
    SELECT ri.request_id 
    FROM request_items ri 
    WHERE ri.quantity_fulfilled < ri.quantity_requested
);

SELECT '==============================================' AS '';
SELECT 'SCHEMA VERIFICATION COMPLETED SUCCESSFULLY!' AS Status;
SELECT '==============================================' AS '';
