-- ------------------------------------------------------------
-- BloodLink
-- FINAL DATABASE  
-- 20 tables 
-- ------------------------------------------------------------

DROP DATABASE IF EXISTS bloodlink_db;
CREATE DATABASE bloodlink_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bloodlink_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. ROLES
-- ------------------------------------------------------------
CREATE TABLE roles (
    role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) UNIQUE,
    account_status ENUM('ACTIVE','INACTIVE','SUSPENDED','LOCKED')
        NOT NULL DEFAULT 'ACTIVE',
    last_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_users_role_status (role_id, account_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. BLOOD GROUPS
-- ------------------------------------------------------------
CREATE TABLE blood_groups (
    blood_group_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(5) NOT NULL UNIQUE,
    abo_type ENUM('A','B','AB','O') NOT NULL,
    rh_factor ENUM('+','-') NOT NULL,

    UNIQUE KEY uq_blood_group_abo_rh (abo_type, rh_factor)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. DONORS
-- ------------------------------------------------------------
CREATE TABLE donors (
    donor_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('MALE','FEMALE','OTHER') NOT NULL,
    blood_group_id INT UNSIGNED NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100),
    availability_status ENUM('AVAILABLE','UNAVAILABLE')
        NOT NULL DEFAULT 'AVAILABLE',
    eligibility_status ENUM('ELIGIBLE','NOT_ELIGIBLE','PENDING_REVIEW')
        NOT NULL DEFAULT 'PENDING_REVIEW',
    next_eligible_date DATE NULL,
    registration_date DATE NOT NULL DEFAULT (CURRENT_DATE),

    CONSTRAINT fk_donors_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_donors_blood_group
        FOREIGN KEY (blood_group_id) REFERENCES blood_groups(blood_group_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_donor_matching
        (blood_group_id, eligibility_status, availability_status),
    INDEX idx_donor_city (city)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. HOSPITALS
-- ------------------------------------------------------------
CREATE TABLE hospitals (
    hospital_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_name VARCHAR(150) NOT NULL,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    approval_status ENUM('PENDING','APPROVED','REJECTED','SUSPENDED')
        NOT NULL DEFAULT 'PENDING',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_hospitals_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_hospital_status_city (approval_status, city)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. HOSPITAL STAFF
-- ------------------------------------------------------------
CREATE TABLE hospital_staff (
    staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    staff_name VARCHAR(100) NOT NULL,
    designation VARCHAR(80),
    staff_status ENUM('ACTIVE','INACTIVE')
        NOT NULL DEFAULT 'ACTIVE',
    joined_at DATE NOT NULL DEFAULT (CURRENT_DATE),

    CONSTRAINT fk_staff_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_staff_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_staff_hospital_status (hospital_id, staff_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. DONATIONS
-- ------------------------------------------------------------
CREATE TABLE donations (
    donation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_id INT UNSIGNED NOT NULL,
    donation_type ENUM('WHOLE_BLOOD','PLASMA','PLATELET') NOT NULL,
    donation_date DATETIME NOT NULL,
    quantity_ml DECIMAL(7,2) NOT NULL,
    screening_status ENUM('PENDING','PASSED','FAILED')
        NOT NULL DEFAULT 'PENDING',
    donation_status ENUM('SCHEDULED','COMPLETED','CANCELLED','REJECTED')
        NOT NULL DEFAULT 'SCHEDULED',
    notes VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_donations_donor
        FOREIGN KEY (donor_id) REFERENCES donors(donor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_donation_quantity
        CHECK (quantity_ml > 0),

    INDEX idx_donations_donor_date (donor_id, donation_date),
    INDEX idx_donations_status (donation_status, screening_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. STORAGE LOCATIONS
-- ------------------------------------------------------------
CREATE TABLE storage_locations (
    storage_location_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_code VARCHAR(30) NOT NULL UNIQUE,
    location_name VARCHAR(100) NOT NULL,
    storage_type ENUM('REFRIGERATOR','FREEZER','PLATELET_AGITATOR','OTHER')
        NOT NULL,
    temperature_min DECIMAL(5,2),
    temperature_max DECIMAL(5,2),
    capacity_units INT UNSIGNED NOT NULL DEFAULT 0,
    location_status ENUM('ACTIVE','INACTIVE','MAINTENANCE')
        NOT NULL DEFAULT 'ACTIVE',

    CONSTRAINT chk_storage_capacity
        CHECK (capacity_units >= 0),

    CONSTRAINT chk_storage_temperature
        CHECK (temperature_min IS NULL OR temperature_max IS NULL
               OR temperature_min <= temperature_max)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. BLOOD BAGS
-- ------------------------------------------------------------
CREATE TABLE blood_bags (
    blood_bag_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bag_number VARCHAR(30) NOT NULL UNIQUE,
    donation_id INT UNSIGNED NOT NULL,
    blood_group_id INT UNSIGNED NOT NULL,
    component_type ENUM('WHOLE_BLOOD','RBC','PLASMA','PLATELET') NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    quantity_ml DECIMAL(7,2) NOT NULL,
    status ENUM('AVAILABLE','RESERVED','ISSUED','EXPIRED','DISCARDED')
        NOT NULL DEFAULT 'AVAILABLE',
    storage_location_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_bags_donation
        FOREIGN KEY (donation_id) REFERENCES donations(donation_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_bags_blood_group
        FOREIGN KEY (blood_group_id) REFERENCES blood_groups(blood_group_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_bags_storage
        FOREIGN KEY (storage_location_id)
        REFERENCES storage_locations(storage_location_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_bag_quantity
        CHECK (quantity_ml > 0),

    CONSTRAINT chk_bag_dates
        CHECK (expiry_date > collection_date),

    INDEX idx_blood_bags_fefo
        (blood_group_id, component_type, status, expiry_date),
    INDEX idx_blood_bags_storage (storage_location_id, status),
    INDEX idx_blood_bags_expiry (expiry_date, status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. INVENTORY MOVEMENTS
-- ------------------------------------------------------------
CREATE TABLE inventory_movements (
    movement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blood_bag_id INT UNSIGNED NOT NULL,
    from_location_id INT UNSIGNED NULL,
    to_location_id INT UNSIGNED NULL,
    movement_type ENUM('RECEIVED','TRANSFERRED','RESERVED','ISSUED','DISCARDED')
        NOT NULL,
    quantity_ml DECIMAL(7,2) NOT NULL,
    reason VARCHAR(255),
    notes VARCHAR(500),
    performed_by INT UNSIGNED NOT NULL,
    movement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_movements_bag
        FOREIGN KEY (blood_bag_id) REFERENCES blood_bags(blood_bag_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_movements_from
        FOREIGN KEY (from_location_id)
        REFERENCES storage_locations(storage_location_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_movements_to
        FOREIGN KEY (to_location_id)
        REFERENCES storage_locations(storage_location_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_movements_user
        FOREIGN KEY (performed_by) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_movement_quantity
        CHECK (quantity_ml > 0),

    INDEX idx_movements_bag_date (blood_bag_id, movement_date),
    INDEX idx_movements_type_date (movement_type, movement_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. BLOOD REQUESTS
-- ------------------------------------------------------------
CREATE TABLE blood_requests (
    request_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    request_type ENUM('EMERGENCY','ROUTINE','SURGERY','MATERNITY','OTHER')
        NOT NULL,
    urgency ENUM('CRITICAL','HIGH','MEDIUM','LOW') NOT NULL DEFAULT 'MEDIUM',
    status ENUM(
        'PENDING',
        'MATCHING',
        'PARTIALLY_FULFILLED',
        'FULFILLED',
        'CANCELLED',
        'EXPIRED'
    ) NOT NULL DEFAULT 'PENDING',
    required_date DATE NOT NULL,
    required_time TIME NULL,
    reason VARCHAR(500) NOT NULL,
    special_notes VARCHAR(1000),
    request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_requests_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_requests_staff
        FOREIGN KEY (requested_by) REFERENCES hospital_staff(staff_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_requests_workflow (status, urgency, required_date),
    INDEX idx_requests_hospital (hospital_id, status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 12. REQUEST ITEMS
-- ------------------------------------------------------------
CREATE TABLE request_items (
    request_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    blood_group_id INT UNSIGNED NOT NULL,
    component_type ENUM('WHOLE_BLOOD','RBC','PLASMA','PLATELET') NOT NULL,
    quantity_requested DECIMAL(7,2) NOT NULL,
    quantity_fulfilled DECIMAL(7,2) NOT NULL DEFAULT 0.00,

    CONSTRAINT fk_request_items_request
        FOREIGN KEY (request_id) REFERENCES blood_requests(request_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_request_items_blood_group
        FOREIGN KEY (blood_group_id) REFERENCES blood_groups(blood_group_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_request_quantity
        CHECK (quantity_requested > 0),

    CONSTRAINT chk_fulfilled_quantity
        CHECK (quantity_fulfilled >= 0
               AND quantity_fulfilled <= quantity_requested),

    INDEX idx_request_items_matching
        (blood_group_id, component_type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 13. DONOR MATCHES
-- ------------------------------------------------------------
CREATE TABLE donor_matches (
    match_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_item_id INT UNSIGNED NOT NULL,
    donor_id INT UNSIGNED NOT NULL,
    match_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    match_reason VARCHAR(1000),
    match_status ENUM(
        'SUGGESTED',
        'NOTIFIED',
        'ACCEPTED',
        'DECLINED',
        'EXPIRED',
        'CANCELLED'
    ) NOT NULL DEFAULT 'SUGGESTED',
    notified_at DATETIME NULL,
    response_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_matches_request_item
        FOREIGN KEY (request_item_id)
        REFERENCES request_items(request_item_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_matches_donor
        FOREIGN KEY (donor_id) REFERENCES donors(donor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_request_donor_match
        UNIQUE (request_item_id, donor_id),

    CONSTRAINT chk_match_score
        CHECK (match_score >= 0 AND match_score <= 100),

    INDEX idx_matches_status (match_status, match_score),
    INDEX idx_matches_donor (donor_id, match_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 14. NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    notification_type ENUM(
        'URGENT_MATCH',
        'REQUEST_UPDATE',
        'DONATION_REMINDER',
        'ELIGIBILITY_UPDATE',
        'SYSTEM_ALERT'
    ) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    related_match_id INT UNSIGNED NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_notifications_match
        FOREIGN KEY (related_match_id)
        REFERENCES donor_matches(match_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_notifications_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 15. FULFILLMENTS
-- ------------------------------------------------------------
CREATE TABLE fulfillments (
    fulfillment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    fulfilled_by INT UNSIGNED NOT NULL,
    fulfillment_status ENUM('PROCESSING','COMPLETED','CANCELLED')
        NOT NULL DEFAULT 'PROCESSING',
    issued_at DATETIME NULL,
    notes VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fulfillments_request
        FOREIGN KEY (request_id) REFERENCES blood_requests(request_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_fulfillments_staff
        FOREIGN KEY (fulfilled_by) REFERENCES hospital_staff(staff_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_fulfillments_request (request_id, fulfillment_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 16. FULFILLMENT ITEMS
-- ------------------------------------------------------------
CREATE TABLE fulfillment_items (
    fulfillment_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fulfillment_id INT UNSIGNED NOT NULL,
    request_item_id INT UNSIGNED NOT NULL,
    blood_bag_id INT UNSIGNED NOT NULL,
    quantity_issued DECIMAL(7,2) NOT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fulfillment_items_fulfillment
        FOREIGN KEY (fulfillment_id)
        REFERENCES fulfillments(fulfillment_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_fulfillment_items_request_item
        FOREIGN KEY (request_item_id)
        REFERENCES request_items(request_item_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_fulfillment_items_bag
        FOREIGN KEY (blood_bag_id)
        REFERENCES blood_bags(blood_bag_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_quantity_issued
        CHECK (quantity_issued > 0),

    INDEX idx_fulfillment_items_request (request_item_id),
    INDEX idx_fulfillment_items_bag (blood_bag_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 17. RECOGNITION LEVELS
-- ------------------------------------------------------------
CREATE TABLE recognition_levels (
    recognition_level_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level_name VARCHAR(50) NOT NULL UNIQUE,
    minimum_donations INT UNSIGNED NOT NULL,
    description VARCHAR(255),

    CONSTRAINT chk_minimum_donations
        CHECK (minimum_donations >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 18. DONOR RECOGNITION
-- ------------------------------------------------------------
CREATE TABLE donor_recognition (
    donor_recognition_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_id INT UNSIGNED NOT NULL,
    recognition_level_id INT UNSIGNED NOT NULL,
    achieved_date DATE NOT NULL,
    notes VARCHAR(255),

    CONSTRAINT fk_donor_recognition_donor
        FOREIGN KEY (donor_id) REFERENCES donors(donor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_donor_recognition_level
        FOREIGN KEY (recognition_level_id)
        REFERENCES recognition_levels(recognition_level_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_donor_recognition
        UNIQUE (donor_id, recognition_level_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 19. AUDIT LOGS
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    entity_name VARCHAR(80) NOT NULL,
    entity_id VARCHAR(50) NOT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_audit_entity (entity_name, entity_id, created_at),
    INDEX idx_audit_user_date (user_id, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 20. SYSTEM SETTINGS
-- ------------------------------------------------------------
CREATE TABLE system_settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(500) NOT NULL,
    description VARCHAR(255),
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_settings_user
        FOREIGN KEY (updated_by) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SEED DATA: ROLES
-- ------------------------------------------------------------
INSERT INTO roles (role_name, description) VALUES
('ADMIN', 'System administrator'),
('DONOR', 'Registered blood donor'),
('HOSPITAL_STAFF', 'Authorized hospital staff');

-- ------------------------------------------------------------
-- SEED DATA: BLOOD GROUPS
-- ------------------------------------------------------------
INSERT INTO blood_groups (group_name, abo_type, rh_factor) VALUES
('A+',  'A',  '+'),
('A-',  'A',  '-'),
('B+',  'B',  '+'),
('B-',  'B',  '-'),
('AB+', 'AB', '+'),
('AB-', 'AB', '-'),
('O+',  'O',  '+'),
('O-',  'O',  '-');

-- ------------------------------------------------------------
-- SEED DATA: RECOGNITION LEVELS
-- ------------------------------------------------------------
INSERT INTO recognition_levels
    (level_name, minimum_donations, description)
VALUES
('Bronze', 1, 'First successful donation'),
('Silver', 3, 'At least three successful donations'),
('Gold', 5, 'At least five successful donations'),
('Platinum', 10, 'At least ten successful donations');

-- ------------------------------------------------------------
-- SEED DATA: SYSTEM SETTINGS
-- ------------------------------------------------------------
INSERT INTO system_settings
    (setting_key, setting_value, description)
VALUES
('MATCHING_RADIUS_KM', '25',
 'Maximum donor matching radius used by the application'),
('FEFO_ENABLED', '1',
 'Use First-Expire, First-Out inventory allocation'),
('DONATION_MIN_INTERVAL_DAYS', '90',
 'Default minimum interval used by the donor eligibility workflow');

-- ------------------------------------------------------------
-- FEFO VIEW
-- Shows currently available blood inventory in expiry order.
-- ------------------------------------------------------------
CREATE VIEW vw_available_inventory AS
SELECT
    bb.blood_bag_id,
    bb.bag_number,
    bg.group_name AS blood_group,
    bb.component_type,
    bb.quantity_ml,
    bb.expiry_date,
    bb.status,
    sl.location_code,
    sl.location_name
FROM blood_bags bb
JOIN blood_groups bg
    ON bg.blood_group_id = bb.blood_group_id
JOIN storage_locations sl
    ON sl.storage_location_id = bb.storage_location_id
WHERE bb.status = 'AVAILABLE'
  AND bb.expiry_date >= CURRENT_DATE
ORDER BY bb.expiry_date ASC;

-- ------------------------------------------------------------
-- DONOR MATCHING VIEW
-- ------------------------------------------------------------
CREATE VIEW vw_eligible_donors AS
SELECT
    d.donor_id,
    d.full_name,
    d.blood_group_id,
    bg.group_name AS blood_group,
    d.city,
    d.availability_status,
    d.eligibility_status,
    d.next_eligible_date
FROM donors d
JOIN blood_groups bg
    ON bg.blood_group_id = d.blood_group_id
WHERE d.availability_status = 'AVAILABLE'
  AND d.eligibility_status = 'ELIGIBLE'
  AND (d.next_eligible_date IS NULL
       OR d.next_eligible_date <= CURRENT_DATE);

-- ------------------------------------------------------------
-- PROCEDURE: FEFO INVENTORY LOOKUP
-- Locks selected inventory rows when used inside a transaction.
-- The PHP/backend layer should call this logic as part of the
-- reservation/fulfillment transaction.
-- ------------------------------------------------------------
DELIMITER $$

CREATE PROCEDURE sp_find_fefo_inventory (
    IN p_blood_group_id INT UNSIGNED,
    IN p_component_type VARCHAR(20),
    IN p_required_ml DECIMAL(7,2)
)
BEGIN
    SELECT
        bb.blood_bag_id,
        bb.bag_number,
        bb.blood_group_id,
        bb.component_type,
        bb.quantity_ml,
        bb.expiry_date,
        bb.storage_location_id
    FROM blood_bags bb
    WHERE bb.blood_group_id = p_blood_group_id
      AND bb.component_type = p_component_type
      AND bb.status = 'AVAILABLE'
      AND bb.expiry_date >= CURRENT_DATE
      AND bb.quantity_ml > 0
    ORDER BY bb.expiry_date ASC, bb.blood_bag_id ASC
    FOR UPDATE;
END$$

DELIMITER ;

-- ------------------------------------------------------------
-- TRIGGER: AUDIT BLOOD BAG STATUS CHANGES
-- ------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER trg_blood_bag_status_audit
AFTER UPDATE ON blood_bags
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_name,
            entity_id,
            old_value,
            new_value
        )
        VALUES (
            NULL,
            'STATUS_CHANGE',
            'blood_bags',
            CAST(NEW.blood_bag_id AS CHAR),
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status)
        );
    END IF;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- BASIC VERIFICATION
-- ------------------------------------------------------------
SELECT 'BloodLink schema created successfully.' AS message;
SHOW TABLES;
