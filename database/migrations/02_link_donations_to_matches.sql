-- Migration 02: Link each donor-accepted match to its pending donation record.
-- Apply after 01_fix_fulfillment_user.sql.

USE bloodlink_db;

ALTER TABLE donations
    ADD COLUMN donor_match_id INT UNSIGNED NULL AFTER donor_id,
    ADD CONSTRAINT uq_donations_match UNIQUE (donor_match_id),
    ADD CONSTRAINT fk_donations_match
        FOREIGN KEY (donor_match_id) REFERENCES donor_matches(match_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
