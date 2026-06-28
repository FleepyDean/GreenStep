# GreenStep - Carbon Footprint & Eco-Lifestyle Tracker

**SCSM2223 Cross-Platform Application Development - Group Project**

A full-stack web application that helps users track and reduce their daily carbon footprint through actionable insights, gamification, social challenges, and personalized goal projections. Deployed on Railway with PHP Slim 4 backend, Vue 3 frontend, and MySQL database.

---

## Project Overview

**Problem Statement:** Climate change is among the most urgent global challenges, and individuals collectively account for a large share of avoidable carbon emissions through transport, diet, and energy choices.

**Solution:** GreenStep is a friendly daily logger that estimates a user's carbon footprint, suggests one realistic action per day, lets friends compete on streaks and reductions, and provides admin tools for managing emission factors, badges, challenges, and tips.

---

## Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Frontend** | Vue 3 + Vue Router + Pinia | Dynamic, mobile-first SPA |
| **Build Tool** | Vite | Fast dev server and production bundling |
| **HTTP Client** | Axios | API calls with JWT interceptors |
| **Backend API** | PHP Slim 4 + PDO | RESTful API with business logic |
| **Database** | MySQL | Persistent storage with relationships |
| **Security** | JWT (firebase/php-jwt) + Bcrypt | Authentication & password hashing |
| **Deployment** | Railway (Nixpacks) | Frontend + Backend + MySQL hosting |
| **Mobile** | Capacitor | Android native build (optional) |

---

## Live Deployment

| Service | URL |
|---------|-----|
| **Frontend** | https://greenstep.up.railway.app |
| **Backend API** | https://greenstep-backend-production.up.railway.app/api |
| **Admin Login** | `admin@greenstep.my` / `admin123` |

---

## Project Structure

```
GreenStep/
├── src/                            # Vue 3 frontend
│   ├── components/                 # Page-level view components
│   │   ├── DashboardView.vue           # User dashboard with charts & goal tracking
│   │   ├── AdminDashboard.vue          # Admin dashboard with platform stats
│   │   ├── ActivityLogView.vue         # Activity logging with photo upload
│   │   ├── ChallengesView.vue          # Eco challenges listing
│   │   ├── ChallengeDetailsView.vue    # Challenge details & join/leave
│   │   ├── CreateChallengeModal.vue    # Admin challenge creation modal
│   │   ├── TipsView.vue                # Eco tips management (admin CRUD)
│   │   ├── ProfileView.vue             # Auth, profile & user badges
│   │   ├── FriendsView.vue             # Friends & social connections
│   │   ├── LeaderboardView.vue         # Global/friends leaderboard
│   │   ├── AddBadgesView.vue           # Admin badge management (CRUD)
│   │   ├── EmissionFactorView.vue      # Admin emission factor management
│   │   ├── WeeklyTrendChart.vue        # Weekly carbon trend chart
│   │   ├── MonthlyTrendChart.vue       # Monthly carbon trend chart
│   │   ├── CategoryBreakdownChart.vue  # Doughnut chart for categories
│   │   ├── AdminCategoryChart.vue      # Admin category distribution chart
│   │   └── ToastNotification.vue       # Toast UI component
│   ├── composables/
│   │   └── useToast.js             # Toast notification composable
│   ├── services/
│   │   └── api.js                  # Axios instance + all API endpoint wrappers
│   ├── stores/
│   │   ├── auth.js                 # Pinia store for JWT auth state
│   │   └── social.js               # Pinia store for friends/leaderboard
│   ├── router/
│   │   └── index.js                # Vue Router config (hash mode, role guards)
│   ├── utils/
│   │   └── eventBus.js             # Event bus for cross-component communication
│   ├── assets/
│   │   └── main.css                # Global styles
│   ├── App.vue                     # Root layout with nav + drawer
│   ├── main.js                     # App entry point
│   └── style.css                   # Legacy global styles
│
├── api/                            # PHP Slim 4 backend
│   ├── public/
│   │   └── index.php               # App entry (middleware, CORS, timezone, DI)
│   ├── config/
│   │   └── Database.php            # PDO database connection
│   ├── src/
│   │   ├── Controllers/
│   │   │   ├── HealthController.php        # Health check endpoint
│   │   │   ├── AuthController.php          # Register, login, profile
│   │   │   ├── ActivityController.php      # CRUD for activity logs + badge engine
│   │   │   ├── ActivityTypeController.php  # Activity types, categories, admin CRUD
│   │   │   ├── DashboardController.php     # Dashboard metrics & charts data
│   │   │   ├── GoalController.php          # Carbon reduction goal & projection
│   │   │   ├── BadgeController.php         # Badge CRUD, user badge evaluation
│   │   │   ├── ChallengeController.php     # Challenge CRUD, join/leave, progress
│   │   │   ├── TipController.php           # Eco tips CRUD
│   │   │   ├── SocialController.php        # Friends, requests, leaderboard
│   │   │   └── AdminController.php         # Admin dashboard stats & dataset
│   │   ├── Middleware/
│   │   │   └── JwtMiddleware.php           # JWT token validation
│   │   ├── Models/
│   │   │   ├── User.php                    # User model (bcrypt hashing)
│   │   │   ├── ActivityLog.php             # Activity log model
│   │   │   ├── ActivityType.php            # Activity type model
│   │   │   ├── Challenge.php               # Challenge model & participant logic
│   │   │   ├── Friendship.php              # Friendship model
│   │   │   ├── Tip.php                     # Tip model
│   │   │   └── Admin.php                   # Admin model
│   │   ├── Services/
│   │   │   ├── JwtService.php              # JWT generate & verify
│   │   │   ├── CarbonCalculator.php        # Carbon footprint math
│   │   │   └── CarbonDatasetService.php    # Emission factor dataset
│   │   └── routes.php                      # All API route definitions
│   ├── .env.example                # Environment config template
│   ├── composer.json               # PHP dependencies & start script
│   ├── nixpacks.toml               # Railway build config for backend
│   └── railway.json                # Railway deployment config for backend
│
├── database/
│   ├── schema.sql                  # Full DB schema + seed data (local dev)
│   ├── schema_railway.sql          # Railway-safe schema (no DROP/CREATE DB)
│   ├── add_dynamic_categories.sql  # Migration: add dynamic categories
│   ├── add_photo_url.sql           # Migration: add photo_url column
│   ├── alter_activity_type_ids.sql # Migration: alter challenge target IDs
│   ├── fix_badge_autoincrement.sql # Migration: fix badge AUTO_INCREMENT
│   └── migrations/                 # Additional migration scripts
│
├── nixpacks.toml                   # Railway build config for frontend
├── railway.json                    # Railway deployment config for frontend
├── vite.config.js                  # Vite configuration
├── package.json                    # Node dependencies & scripts
├── .gitignore                      # Git ignore rules
└── README.md                       # You are here
```

---

## Features

### User Features
- **Authentication** - Register, login, JWT-based session management
- **Activity Logging** - Log daily activities (transport, diet, energy, recycling) with automatic carbon footprint calculation
- **Photo Upload** - Attach photos to activity logs
- **Dashboard** - Real-time stats with weekly/monthly trend charts, category breakdown doughnut chart, daily streak counter
- **Carbon Reduction Goal** - Set personal reduction targets, view progress bar, pace projection, and on-track/off-track status
- **Badges & Gamification** - Automatic badge unlocking based on activity milestones, streaks, and category totals
- **Eco Challenges** - Browse, join, and track challenges with progress indicators
- **Eco Tips** - Daily random tips, category-filtered browsing, admin-managed content
- **Social** - Add friends, send/accept/reject requests, remove friends
- **Leaderboard** - Global and friends-only rankings by carbon savings
- **Profile** - View profile info, earned badges (non-admin), logout

### Admin Features
- **Admin Dashboard** - Platform-wide statistics and user dataset overview
- **Badge Management** - Create custom badges with category rules, activity type targeting, and threshold values; delete badges
- **Emission Factor Management** - Add/edit/delete activity types and categories with custom emission factors
- **Challenge Management** - Create, update, and delete challenges
- **Tips Management** - Create and delete eco tips

---

## API Reference

### Authentication (Public)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register` | `{name, email, password}` | Register new user, returns JWT |
| `POST` | `/api/auth/login` | `{email, password}` | Login, returns JWT |
| `GET` | `/api/auth/me` | — | Get current user (protected) |

### Activity Types (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/activity-types` | All types grouped by category |
| `GET` | `/api/activity-types/categories` | List of categories |
| `GET` | `/api/activity-types/category/{category}` | Types filtered by category |

### Activity Logging (Protected)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `GET` | `/api/activities` | — (query: `start_date`, `end_date`) | Get user's activities |
| `GET` | `/api/activities/today` | — | Get today's activities + total footprint |
| `GET` | `/api/activities/stats` | — (query: `start_date`, `end_date`) | Get aggregated stats |
| `POST` | `/api/activities` | `{activity_type_id, amount, date}` or `multipart/form-data` | Log new activity (with optional photo) |
| `DELETE` | `/api/activities/{id}` | — | Delete an activity |

### Dashboard & Goals (Protected)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/dashboard/{userId}` | Dashboard metrics, charts data, streak, badge count |
| `GET` | `/api/goal` | Get user's goal + projection (progress, pace, savings) |
| `PUT` | `/api/goal` | Update goal target, duration, baseline, or reset cycle |

### Badges (Protected)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `GET` | `/api/badges` | — | Get user's badges with unlock status |
| `GET` | `/api/admin/badges` | — | Get all badges (admin only) |
| `POST` | `/api/admin/badges` | `{name, description, icon, category_rule, activity_type_ids, threshold_value}` | Create custom badge (admin only) |
| `DELETE` | `/api/admin/badges/{id}` | — | Delete badge (admin only) |

### Challenges (Protected)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `GET` | `/api/challenges` | — | List all challenges |
| `GET` | `/api/challenges/{id}/details` | — | Get challenge details + participant status |
| `POST` | `/api/challenges` | `{title, description, ...}` | Create challenge (admin only) |
| `PUT` | `/api/challenges/{id}` | `{...}` | Update challenge (admin only) |
| `DELETE` | `/api/challenges/{id}` | — | Delete challenge (admin only) |
| `POST` | `/api/challenges/{id}/join` | — | Join a challenge |
| `DELETE` | `/api/challenges/{id}/leave` | — | Leave a challenge |

### Tips (Protected)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `GET` | `/api/tips` | — | List all tips |
| `GET` | `/api/tips/random` | — | Get a random tip |
| `POST` | `/api/tips` | `{title, content, category}` | Create tip (admin only) |
| `DELETE` | `/api/tips/{id}` | — | Delete tip (admin only) |

### Social (Protected)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `GET` | `/api/friends` | — | Get friends list + pending requests |
| `POST` | `/api/friends/request` | `{email}` | Send friend request |
| `PUT` | `/api/friends/request/{id}` | `{status}` | Accept/reject request |
| `DELETE` | `/api/friends/{id}` | — | Remove friend |
| `GET` | `/api/leaderboard` | — (query: `filter`) | Get leaderboard (global/friends) |

### Admin (Protected + Admin Role)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/dashboard` | Platform statistics |
| `GET` | `/api/admin/dataset` | Full user dataset |
| `POST` | `/api/admin/categories` | Create activity category |
| `POST` | `/api/admin/activity-types` | Create activity type |
| `PUT` | `/api/admin/activity-types/{id}` | Update activity type |
| `DELETE` | `/api/admin/activity-types/{id}` | Delete activity type |

### Request Headers (for protected routes)
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

---

## Database Schema Overview

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `User` | User accounts with goal settings | → ActivityLog, ChallengeParticipant, UserBadge, Friendship |
| `ActivityType` | Activity types with emission factors | → ActivityLog |
| `ActivityLog` | Individual activity entries with carbon footprint | → User, ActivityType |
| `Tip` | Eco tips | — |
| `Challenge` | Eco challenges with targets | → ChallengeParticipant |
| `ChallengeParticipant` | User-challenge joins | → Challenge, User |
| `Badge` | Achievement badges with rules | → UserBadge |
| `UserBadge` | Earned badges | → User, Badge |
| `Friendship` | Social connections | → User (sender, receiver) |

### Seed Data
- 17+ activity types with emission factors (Transport, Diet, Energy, Recycling)
- 10 eco tips across all categories
- 4 challenges with date ranges and targets
- 6+ badges (streak + category-based)
- 1 admin user (`admin@greenstep.my` / `admin123`)

---

## Setup Instructions

### Prerequisites
- **Node.js** 18+ and npm
- **PHP** 8.0+ with Composer
- **MySQL** (via Laragon or standalone)
- **Git**

### 1. Database Setup

**Using Laragon phpMyAdmin (recommended):**
1. Start Laragon (MySQL + Apache)
2. Open `http://localhost/phpmyadmin`
3. Create database: `greenstep_db` (collation: `utf8mb4_general_ci`)
4. Click Import → Choose `database/schema.sql` → Import
5. Verify: `ActivityType` table should have 17+ rows

**Using command line:**
```bash
mysql -u root -p
CREATE DATABASE greenstep_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE greenstep_db;
SOURCE database/schema.sql;
```

### 2. Backend Setup

```bash
cd api

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your DB credentials and set a JWT_SECRET

# Start development server
composer start
# API runs at http://localhost:8080
```

**Environment variables (`.env`):**
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=greenstep_db
DB_USER=root
DB_PASS=
JWT_SECRET=your_secret_key
FRONTEND_URL=http://localhost:5173
```

**Verify backend is running:**
```bash
curl http://localhost:8080/api/health
# Should return: {"message":"GreenStep API v1.0","status":"running","database":"connected"}
```

### 3. Frontend Setup

```bash
# Install dependencies
npm install

# Run development server
npm run dev
# Frontend runs at http://localhost:5173
```

**Environment variable (`.env`):**
```
VITE_API_URL=http://localhost:8080/api
```

### 4. Test the Full Flow

1. Open `http://localhost:5173/profile`
2. Register a new user or login as admin (`admin@greenstep.my` / `admin123`)
3. Go to `/activity` — log an activity (select category, type, amount, date)
4. Go to `/dashboard` — view charts, stats, and goal progress
5. Go to `/challenges` — browse and join challenges
6. Go to `/tips` — view eco tips
7. Go to `/friends` — add friends and view leaderboard
8. Go to `/profile` — view profile and earned badges

---

## Railway Deployment

### Architecture
- **Frontend** service: Vue 3 SPA built with Vite, served as static files
- **Backend** service: PHP Slim 4 API running with `php -S` built-in server
- **Database** service: MySQL on Railway

### Configuration Files
- `nixpacks.toml` (root) — Frontend build config (Node 20, npm build, serve dist)
- `railway.json` (root) — Frontend Railway deployment config
- `api/nixpacks.toml` — Backend build config (PHP 8.3, Composer)
- `api/railway.json` — Backend Railway deployment config

### Environment Variables on Railway

**Frontend service:**
```
VITE_API_URL=https://greenstep-backend-production.up.railway.app/api
```

**Backend service:**
```
DB_HOST=<railway-mysql-host>
DB_PORT=<railway-mysql-port>
DB_NAME=railway
DB_USER=root
DB_PASS=<railway-mysql-password>
JWT_SECRET=<your-secret-key>
FRONTEND_URL=https://greenstep.up.railway.app
PORT=<railway-assigned-port>
```

### Database Initialization on Railway
1. Open the Railway MySQL service in the dashboard
2. Go to the **MySQL** tab and open the query console
3. Run the contents of `database/schema_railway.sql` (this is the Railway-safe version without `DROP DATABASE`/`CREATE DATABASE`/`USE` statements)
4. Run `database/fix_badge_autoincrement.sql` if needed

### Important Notes for Railway
- The backend sets `date_default_timezone_set('Asia/Kuala_Lumpur')` globally so dates match the user's local time (UTC+8)
- MySQL on Linux is case-sensitive — all SQL queries use exact table name casing (`ActivityLog`, `ActivityType`, `Badge`, `UserBadge`, `User`, etc.)
- CORS is configured via the `FRONTEND_URL` environment variable
- The PHP server binds to `0.0.0.0:$PORT` as required by Railway

---

## Key Files Reference

| If you're working on... | Start here |
|------------------------|------------|
| Frontend pages | `src/components/` — each view is a `.vue` file |
| API calls | `src/services/api.js` — all endpoint wrappers |
| Auth state | `src/stores/auth.js` — Pinia store, handles JWT |
| Social state | `src/stores/social.js` — Pinia store for friends/leaderboard |
| New API endpoints | `api/src/routes.php` — add routes, then create controller |
| New DB tables | `database/schema.sql` — add table + seed data |
| JWT middleware | `api/src/Middleware/JwtMiddleware.php` — protects routes |
| Carbon calculation | `api/src/Services/CarbonCalculator.php` — amount × emission factor |
| Goal projection | `api/src/Controllers/GoalController.php` — baseline, pace, progress |
| Badge engine | `api/src/Controllers/BadgeController.php` — unlock logic |
| Railway config | `nixpacks.toml`, `railway.json`, `api/nixpacks.toml`, `api/railway.json` |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "Loading..." stuck on a page | Make sure you're logged in (check localStorage for `auth_token`) |
| Empty activity type dropdown | Import `database/schema.sql` into MySQL |
| CORS error | Ensure `FRONTEND_URL` env var matches your frontend URL on Railway |
| 401 Unauthorized | Token expired or missing — login again |
| 405 Method Not Allowed | Check `api/src/routes.php` — endpoint may only accept certain methods |
| 500 Internal Server Error | Check if SQL table names match case (Linux MySQL is case-sensitive) |
| Date mismatch on Railway | Backend timezone is set to `Asia/Kuala_Lumpur` — ensure activities use today's date |
| Goal progress bar not updating | Progress tracks carbon reduction vs baseline — logging activities increases footprint |
| Database connection error | Check `api/.env` — ensure DB credentials match your MySQL setup |
| Badge 500 error | Ensure all SQL queries use correct table name casing (`ActivityLog`, not `activitylog`) |

---

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
