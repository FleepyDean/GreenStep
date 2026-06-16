# GreenStep Backend - Progressive Commit Guide

This document outlines the progressive commit strategy for backend implementation.

## ✅ Commit 1: Initial Backend Structure Setup

**Status:** READY TO COMMIT

**Branch:** `backend/project-setup`

**Files Added:**
```
api/
├── public/
│   ├── index.php          # Slim app entry point
│   └── .htaccess          # URL rewriting
├── src/
│   ├── Controllers/.gitkeep
│   ├── Models/.gitkeep
│   ├── Middleware/.gitkeep
│   └── Services/.gitkeep
├── config/.gitkeep
├── composer.json          # PHP dependencies
├── .env.example          # Environment template
├── .gitignore
├── .htaccess
└── README.md             # Backend documentation

database/.gitkeep
README.md (updated)       # Main project README
COMMIT_GUIDE.md          # This file
```

**Commit Message:**
```
feat(backend): initialize PHP Slim 4 project structure

- Add api/ directory with Slim 4 boilerplate
- Create composer.json with required dependencies
- Setup folder structure (Controllers, Models, Middleware, Services)
- Add .env.example for environment configuration
- Create backend README with setup instructions
- Update main README with project overview and tech stack
```

**Next Steps After This Commit:**
1. Run `composer install` in api/ directory
2. Create `.env` file from `.env.example`
3. Configure database credentials in `.env`

---

## 📋 Commit 2: Install Dependencies & Environment Setup

**Status:** PENDING

**What to do:**
```bash
cd api
composer install
cp .env.example .env
```

Edit `.env`:
```
DB_HOST=localhost
DB_NAME=greenstep_db
DB_USER=root
DB_PASS=         # Your Laragon MySQL password (usually empty)
JWT_SECRET=your-super-secret-jwt-key-change-this
```

**Files to commit:**
- `api/vendor/` (added to .gitignore, won't be committed)
- `api/.env` (added to .gitignore, won't be committed)
- `api/composer.lock` (will be generated)

**Commit Message:**
```
chore(backend): install PHP dependencies via composer

- Install Slim 4, PSR-7, JWT, and dotenv packages
- Generate composer.lock for dependency locking
```

---

## 📋 Commit 3: Database Connection & Configuration

**Status:** PENDING

**Files to create:**
- `api/config/database.php` - PDO connection class
- `api/src/Models/Database.php` - Database singleton

**Commit Message:**
```
feat(backend): add database connection configuration

- Create PDO database connection class
- Implement singleton pattern for DB instance
- Add environment-based configuration loading
```

---

## 📋 Commit 4: MySQL Database Schema

**Status:** PENDING

**Files to create:**
- `database/schema.sql` - Complete database schema
- `database/seed.sql` - Sample data for testing

**Tables to create:**
1. User
2. ActivityType
3. ActivityLog
4. Tip
5. TipBadge
6. Challenge
7. ChallengeMember
8. Badge
9. UserBadge

**Commit Message:**
```
feat(database): create MySQL schema with 9 tables

- Design ER diagram based on project requirements
- Create User table with bcrypt password support
- Add ActivityType and ActivityLog for carbon tracking
- Implement Tip, Challenge, and Badge tables
- Setup foreign key relationships
- Add seed data for activity types, tips, and challenges
```

---

## 📋 Commit 5: JWT Middleware & Error Handling

**Status:** PENDING

**Files to create:**
- `api/src/Middleware/JwtMiddleware.php`
- `api/src/Middleware/CorsMiddleware.php`
- `api/src/Middleware/ErrorMiddleware.php`

**Commit Message:**
```
feat(backend): implement JWT authentication middleware

- Add JWT token verification middleware
- Create CORS middleware for frontend integration
- Implement centralized error handling
- Add JSON response helpers
```

---

## 📋 Commit 6: Authentication Endpoints (Register/Login)

**Status:** PENDING

**Files to create:**
- `api/src/Controllers/AuthController.php`
- `api/src/Models/User.php`
- `api/src/routes.php`

**Endpoints:**
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login with JWT

**Commit Message:**
```
feat(auth): implement user registration and login

- Add AuthController with register and login methods
- Implement bcrypt password hashing
- Generate JWT tokens on successful login
- Add User model with PDO prepared statements
- Create routes.php for endpoint definitions
```

---

## 📋 Commit 7: Activity Logging Endpoints

**Status:** PENDING

**Files to create:**
- `api/src/Controllers/ActivityController.php`
- `api/src/Models/ActivityLog.php`
- `api/src/Services/CarbonCalculator.php`

**Endpoints:**
- `POST /api/activities` - Log new activity
- `GET /api/activities` - Get user activities
- `GET /api/activities/today` - Today's activities
- `DELETE /api/activities/{id}` - Delete activity
- `GET /api/activities/stats` - Carbon statistics

**Commit Message:**
```
feat(activities): implement activity logging CRUD endpoints

- Add ActivityController with CRUD operations
- Create ActivityLog model with PDO
- Implement CarbonCalculator service for emissions
- Add configurable emission factors
- Calculate carbon footprint per activity
```

---

## 📋 Commit 8: Tips Management Endpoints

**Status:** PENDING

**Commit Message:**
```
feat(tips): implement eco tips management API

- Add TipsController for tips CRUD
- Implement daily randomized tip selection
- Add category filtering
- Track user's received tips
```

---

## 📋 Commit 9: Challenges System Endpoints

**Status:** PENDING

**Commit Message:**
```
feat(challenges): implement community challenges API

- Add ChallengesController
- Implement join/leave challenge logic
- Track challenge participation
- Calculate progress towards targets
```

---

## 📋 Commit 10: Badges & Gamification

**Status:** PENDING

**Commit Message:**
```
feat(badges): implement achievement badges system

- Add BadgesController
- Create badge awarding logic
- Track user achievements
- Implement streak calculations
```

---

## 📋 Commit 11: Frontend API Integration

**Status:** PENDING

**Files to create:**
- `src/services/api.js` - Axios API client
- `src/stores/auth.js` - Pinia auth store
- `src/stores/activities.js` - Activities store

**Commit Message:**
```
feat(frontend): integrate Vue frontend with backend API

- Create API service layer with Axios
- Implement JWT token storage
- Update Pinia stores to fetch from API
- Add loading and error states
- Connect all views to backend endpoints
```

---

## 🎯 Summary

**Total Commits Planned:** 11
**Current Progress:** 1/11 (9%)

**Recommended Workflow:**
1. Complete each commit in order
2. Test functionality after each commit
3. Push to your branch after each commit
4. Create clear commit messages following conventional commits

**Naming Convention:**
- Feature commits: `feat(scope): description`
- Configuration: `chore(scope): description`
- Bug fixes: `fix(scope): description`
- Documentation: `docs(scope): description`
