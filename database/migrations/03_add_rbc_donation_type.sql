ALTER TABLE donations
    MODIFY donation_type ENUM('WHOLE_BLOOD','RBC','PLASMA','PLATELET') NOT NULL;

UPDATE donations don
JOIN donor_matches dm ON dm.match_id = don.donor_match_id
JOIN request_items ri ON ri.request_item_id = dm.request_item_id
SET don.donation_type = 'RBC'
WHERE ri.component_type = 'RBC'
    AND don.donation_type = 'WHOLE_BLOOD';

UPDATE blood_bags bb
JOIN donations don ON don.donation_id = bb.donation_id
SET bb.component_type = 'RBC'
WHERE don.donation_type = 'RBC'
    AND bb.component_type = 'WHOLE_BLOOD';