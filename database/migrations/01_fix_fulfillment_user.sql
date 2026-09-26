-- Migration 01: Allow both Administrators and Hospital Staff users to fulfill requests
-- In the base schema, fulfillments.fulfilled_by referenced hospital_staff(staff_id).
-- This migration updates the FK constraint to reference users(user_id),
-- ensuring any authenticated authorized user (Staff or Admin) can be recorded.

USE bloodlink_db;

ALTER TABLE fulfillments DROP FOREIGN KEY fk_fulfillments_staff;

ALTER TABLE fulfillments
    ADD CONSTRAINT fk_fulfillments_user
    FOREIGN KEY (fulfilled_by) REFERENCES users(user_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;
