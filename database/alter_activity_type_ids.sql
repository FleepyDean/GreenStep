-- Change target_activity_type_id from INT to VARCHAR to support multiple IDs (comma-separated)
ALTER TABLE Challenge 
  MODIFY COLUMN target_activity_type_id VARCHAR(200) NULL DEFAULT NULL;
