# Database Setup Guide

## 🗄️ Import Database Schema

You need to run the `schema.sql` file to create tables and seed data.

### **Option 1: Using Laragon phpMyAdmin (Easiest)**

1. **Start Laragon** and make sure MySQL is running

2. **Open phpMyAdmin:**
   - Click "Database" button in Laragon
   - OR go to: `http://localhost/phpmyadmin`

3. **Create Database:**
   - Click "New" in left sidebar
   - Database name: `greenstep_db`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

4. **Import Schema:**
   - Click on `greenstep_db` in left sidebar
   - Click "Import" tab at the top
   - Click "Choose File"
   - Select: `C:\Users\Danish\Desktop\GreenStep\database\schema.sql`
   - Scroll down and click "Import"

5. **Verify:**
   - You should see 10 tables created
   - Click on `ActivityType` table
   - Click "Browse" - you should see 17 activity types!

---

### **Option 2: Using MySQL Command Line**

```bash
# Open terminal in Laragon or use MySQL command line

# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE greenstep_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Use the database
USE greenstep_db;

# Import schema
SOURCE C:/Users/Danish/Desktop/GreenStep/database/schema.sql;

# Verify
SELECT COUNT(*) FROM ActivityType;
# Should return 17

SELECT * FROM ActivityType WHERE category = 'Transport';
# Should show 5 transport types
```

---

### **Option 3: Using Laragon Terminal**

```bash
# Right-click Laragon tray icon → Terminal

mysql -u root -p greenstep_db < C:/Users/Danish/Desktop/GreenStep/database/schema.sql
```

---

## ✅ Verify Database is Ready

### **Check Tables:**
```sql
SHOW TABLES;
```

Should show:
- User
- ActivityType
- ActivityLog
- Tip
- Challenge
- ChallengeParticipant
- Badge
- UserBadge
- Friendship

### **Check Activity Types:**
```sql
SELECT category, name, unit, kg_co2_per_unit 
FROM ActivityType 
ORDER BY category, name;
```

Should show 17 rows:
- 5 Transport types
- 5 Diet types
- 3 Energy types
- 4 Recycling types

### **Check Tips:**
```sql
SELECT COUNT(*) FROM Tip;
```
Should return: 10

---

## 🔧 If You Get Errors

### "Database already exists"
```sql
DROP DATABASE greenstep_db;
CREATE DATABASE greenstep_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE greenstep_db;
SOURCE C:/Users/Danish/Desktop/GreenStep/database/schema.sql;
```

### "Table already exists"
```sql
DROP TABLE IF EXISTS UserBadge, Friendship, ChallengeParticipant, Badge, Challenge, Tip, ActivityLog, ActivityType, User;
SOURCE C:/Users/Danish/Desktop/GreenStep/database/schema.sql;
```

---

## 🧪 Test After Import

1. **Test API endpoint:**
```bash
curl http://localhost:8080/api/activity-types
```

Should return JSON with all 17 activity types grouped by category!

2. **Test in browser:**
```
http://localhost:8080/api/activity-types
```

Should see:
```json
{
    "success": true,
    "count": 17,
    "activity_types": {
        "Transport": [...],
        "Diet": [...],
        "Energy": [...],
        "Recycling": [...]
    }
}
```

---

## 🎯 After Database is Ready

1. Restart backend server:
```bash
cd api
composer start
```

2. Refresh your frontend page

3. Activity Type dropdown should now show all options!

---

**Once you import the schema, the dropdown will populate automatically!** 🚀
