# 🚀 Next Phase: Database & Authentication Setup

## ✅ What Just Happened (Commit 3 & 4 Prep)

I've created:
1. **Database Connection** (`api/config/database.php`)
2. **Health Controller** (`api/src/Controllers/HealthController.php`)
3. **Routes Setup** (`api/src/routes.php`)
4. **Updated Entry Point** (`api/public/index.php` with CORS & DotEnv)
5. **MySQL Schema** (`database/schema.sql`) - Complete with 10 tables + seed data

---

## 📋 Your Action Items

### Step 1: Commit Current Changes

```bash
cd C:\Users\Danish\Desktop\GreenStep

# Remove .gitkeep files (they're no longer needed)
del api\src\Controllers\.gitkeep api\src\Models\.gitkeep api\src\Middleware\.gitkeep api\src\Services\.gitkeep api\config\.gitkeep database\.gitkeep

# Add all new files
git add api/config/ api/src/Controllers/ api/src/routes.php api/public/index.php database/schema.sql

# Commit
git commit -m "feat(backend): add database connection and MySQL schema

- Create Database class with PDO singleton pattern
- Add HealthController for API health checks
- Setup routes structure with CORS middleware
- Implement MySQL schema with 10 tables:
  * User, ActivityType, ActivityLog, Tip, TipBadge
  * Challenge, ChallengeMember, Badge, UserBadge, Friendship
- Add seed data for activity types, tips, challenges, and badges
- Include admin user for testing"

git push origin backend/project-setup
```

### Step 2: Create MySQL Database

Open **Laragon's Terminal** or any MySQL client:

```bash
# Connect to MySQL (Laragon default)
mysql -u root -p

# If no password, just press Enter
# Then create and populate the database:

source C:\Users\Danish\Desktop\GreenStep\database\schema.sql
```

Or use **HeidiSQL** (comes with Laragon):
1. Open HeidiSQL
2. Connect to localhost
3. File → Load SQL file → Select `schema.sql`
4. Execute

**Verify the database was created:**
```sql
USE greenstep_db;
SHOW TABLES;
SELECT * FROM ActivityType;
```

### Step 3: Test Database Connection

1. Make sure your `.env` file has correct credentials:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=greenstep_db
   DB_USER=root
   DB_PASS=          # Empty if Laragon has no password
   ```

2. Restart the API server:
   ```bash
   cd api
   composer start
   ```

3. Test the health endpoint:
   ```
   http://localhost:8080/api/health
   ```

   **Expected Response:**
   ```json
   {
       "message": "GreenStep API v1.0",
       "status": "running",
       "timestamp": "2026-06-17 00:00:00",
       "database": "connected"
   }
   ```

   If you see `"database": "connected"`, you're good to go! 🎉

---

## 🎯 What's Next (Commit 5 & 6)

After the database is connected, we'll build:

### Commit 5: JWT Middleware
- JWT token generation
- JWT verification middleware
- Error handling for auth

### Commit 6: Authentication Endpoints
- `POST /api/auth/register` - User registration with bcrypt
- `POST /api/auth/login` - Login with JWT token
- `GET /api/users/profile` - Get user profile (protected)

### Commit 7: Activity Logging API
- `POST /api/activities` - Log activity with carbon calculation
- `GET /api/activities` - Get user's activities
- `GET /api/activities/today` - Today's activities
- Server-side carbon footprint calculation

---

## 📝 Your Role Reminder

As **Backend & API Lead**, you're responsible for:

✅ **Vertical Features:**
- Daily log interface backend (done next)
- Server-side carbon calculation (done next)

✅ **Horizontal Responsibilities:**
- PHP Slim project setup ✅ DONE
- RESTful endpoint design ✅ IN PROGRESS
- Business logic implementation (next)
- JSON request/response shaping (next)
- Error handling (next)
- API documentation

---

## ⚠️ Common Issues

### "database": "disconnected"
- Check if MySQL is running in Laragon
- Verify `.env` credentials match Laragon settings
- Check if `greenstep_db` was created successfully

### CORS errors when frontend tries to connect
- CORS is already enabled in `index.php`
- Frontend should be able to connect from any origin

### SQL import errors
- Make sure you're using MySQL 5.7+ or MariaDB 10.2+
- Check for syntax errors in the SQL file

---

## ✅ Success Criteria

You know you're ready for the next phase when:
- ✅ Database created in MySQL
- ✅ Tables visible when running `SHOW TABLES;`
- ✅ API returns `"database": "connected"` at `/api/health`
- ✅ Changes committed and pushed

**Once that's working, I'll build the JWT authentication system for you!** 🚀
