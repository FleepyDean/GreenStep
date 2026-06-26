-- Migration: Change ActivityType.category from ENUM to VARCHAR to support dynamic categories
ALTER TABLE ActivityType 
  MODIFY COLUMN category VARCHAR(100) NOT NULL;

-- Migration: Change Tip.category from ENUM to VARCHAR to keep consistency
ALTER TABLE Tip
  MODIFY COLUMN category VARCHAR(100) NOT NULL;
