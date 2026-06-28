-- Step 1: Change the data type to match ActivityType.id perfectly
ALTER TABLE Challenge 
MODIFY target_activity_type_id INT(10) UNSIGNED NULL DEFAULT NULL;

-- Step 2: Add the foreign key constraint
ALTER TABLE Challenge 
ADD CONSTRAINT fk_challenge_activity_type 
FOREIGN KEY (target_activity_type_id) 
REFERENCES ActivityType(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;