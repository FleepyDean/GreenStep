# GreenStep - Carbon Footprint & Eco-Lifestyle Tracker

**SCSM2223 Cross-Platform Application Development - Group Project**

A full-stack web application to help users track and reduce their daily carbon footprint through actionable insights, gamification, and community challenges.

---

## 🌱 Project Overview

**Problem Statement:** Climate change is among the most urgent global challenges, and individuals collectively account for a large share of avoidable carbon emissions through transport, diet, and energy choices.

**Solution:** GreenStep targets the gap - a friendly daily logger that estimates a user's carbon footprint, suggests one realistic action per day, and lets friends compete on streaks and reductions.

---

## 🛠️ Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Frontend** | Vue 3 + Vue Router + Pinia | Dynamic, mobile-first SPA |
| **HTTP Client** | Axios | API calls with JWT interceptors |
| **Backend API** | PHP Slim 4 + PDO | RESTful API with business logic |
| **Database** | MySQL (via Laragon) | Persistent storage with relationships |
| **Security** | JWT (firebase/php-jwt) + Bcrypt | Authentication & password hashing |
| **Mobile** | Capacitor | Android native build |

---

## 📁 Project Structure

```
GreenStep/
├── src/                        # Vue 3 frontend
│   ├── components/             # Page-level view components
│   │   ├── DashboardView.vue       # Dashboard with stats overview
│   │   ├── ActivityLogView.vue     # Activity logging (CONNECTED TO API)
│   │   ├── ChallengesView.vue      # Eco challenges (mock data)
│   │   ├── TipsView.vue            # Eco tips (mock data)
│   │   └── ProfileView.vue         # Auth & profile (CONNECTED TO API)
│   ├── services/
│   │   └── api.js              # Axios instance + API endpoint wrappers
│   ├── stores/
│   │   └── auth.js             # Pinia store for JWT auth state
│   ├── router/
│   │   └── index.js            # Vue Router config (hash mode)
│   ├── App.vue                 # Root layout with nav
│   ├── main.js                 # App entry point
│   └── style.css               # Global styles
│
├── api/                        # PHP Slim 4 backend
│   ├── public/
│   │   └── index.php           # App entry point (middleware, CORS, routes)
│   ├── src/
│   │   ├── Controllers/
│   │   │   ├── HealthController.php        # Health check
│   │   │   ├── AuthController.php          # Register, login, profile
│   │   │   ├── ActivityController.php      # CRUD for activity logs
│   │   │   └── ActivityTypeController.php  # Activity types & categories
│   │   ├── Middleware/
│   │   │   └── JwtMiddleware.php           # JWT token validation
│   │   ├── Models/
│   │   │   ├── User.php                    # User model (bcrypt hashing)
│   │   │   ├── ActivityLog.php             # Activity log model
│   │   │   └── ActivityType.php            # Activity type model
│   │   ├── Services/
│   │   │   ├── JwtService.php              # JWT generate & verify
│   │   │   └── CarbonCalculator.php        # Carbon footprint math
│   │   └── routes.php                      # All API route definitions
│   ├── .env.example            # Environment config template
│   └── composer.json           # PHP dependencies
│
├── database/
│   └── schema.sql              # Full DB schema + seed data (10 tables)
│
└── README.md                   # You are here
```

---

## 👥 Team Roles & Progress

| Member | Role | Status |
|--------|------|--------|
| **Member 1** | Frontend Lead (Vue scaffolding, components, routing, UI/UX) | ✅ UI complete, 2 pages connected to API |
| **Member 2** | Backend & API Lead (PHP Slim, RESTful endpoints, business logic) | ✅ Auth + Activity Logging APIs complete |
| **Member 3** | Database & Security Lead (ER diagram, MySQL schema, JWT, validation) | ✅ Schema + seed data + JWT implemented |
| **Member 4** | DevOps & Mobile Lead (Deployment, Capacitor, integration testing) | ⬜ Pending |

---

## ✅ What's Been Completed

### Database (`database/schema.sql`)
- **10 tables** created with proper foreign keys and indexes:
  - `User`, `ActivityType`, `ActivityLog`, `Tip`, `Challenge`
  - `ChallengeParticipant`, `Badge`, `UserBadge`, `Friendship`
- **Seed data** included:
  - 17 activity types with emission factors (Transport, Diet, Energy, Recycling)
  - 10 eco tips across all categories
  - 4 challenges with date ranges and targets
  - 6 badges with criteria JSON
  - 1 admin user (email: `admin@greenstep.my`, password: `admin123`)

### Backend API (`api/`)
- **Authentication system** with JWT:
  - `POST /api/auth/register` - Register with name, email, password
  - `POST /api/auth/login` - Login and receive JWT token
  - `GET /api/auth/me` - Get current user profile (protected)
- **Activity Logging** (all protected with JWT):
  - `POST /api/activities` - Log a new activity (auto-calculates carbon footprint)
  - `GET /api/activities` - Get all activities (with optional date filters)
  - `GET /api/activities/today` - Get today's activities + total footprint
  - `GET /api/activities/stats` - Get aggregated statistics
  - `DELETE /api/activities/{id}` - Delete an activity
- **Activity Types** (public):
  - `GET /api/activity-types` - All types grouped by category
  - `GET /api/activity-types/categories` - List of categories
  - `GET /api/activity-types/category/{category}` - Types filtered by category
- **Middleware**:
  - `BodyParsingMiddleware` - JSON body parsing
  - `JwtMiddleware` - Token validation for protected routes
  - CORS headers - `Access-Control-Allow-Origin: *`
- **Services**:
  - `JwtService` - Token generation & verification with firebase/php-jwt
  - `CarbonCalculator` - Multiplies amount by emission factor per activity type

### Frontend (`src/`)
- **API service layer** (`src/services/api.js`):
  - Axios instance with baseURL `http://localhost:8080/api`
  - Request interceptor: auto-attaches JWT token from localStorage
  - Response interceptor: auto-redirects to `/profile` on 401
  - Pre-built API wrappers: `authAPI` and `activityAPI`
- **Auth store** (`src/stores/auth.js`):
  - Pinia store managing token + user state
  - `register()`, `login()`, `logout()`, `fetchProfile()` actions
  - Persists token + user to localStorage
- **Connected pages**:
  - `ProfileView.vue` - Real registration, login, logout, profile display
  - `ActivityLogView.vue` - Real activity logging, today's list, delete, carbon totals
- **Not yet connected** (still using mock data):
  - `DashboardView.vue` - Needs stats from `GET /api/activities/stats`
  - `ChallengesView.vue` - Needs `GET /api/challenges` endpoint
  - `TipsView.vue` - Needs `GET /api/tips` endpoint

---

## ⬜ What's Left To Do

### For Frontend Lead (Member 1)
1. **Dashboard** (`DashboardView.vue`):
   - Connect to `GET /api/activities/stats` for real statistics
   - Show total footprint, category breakdown, daily/weekly trends
   - Use charts (consider Chart.js or similar)

2. **Challenges** (`ChallengesView.vue`):
   - Needs backend endpoints first (see Member 2 below)
   - Display available challenges from `GET /api/challenges`
   - Show user's joined challenges from `GET /api/challenges/my`
   - Join/leave challenge buttons

3. **Tips** (`TipsView.vue`):
   - Needs backend endpoint `GET /api/tips`
   - Display tips from database, filter by category
   - Optional: admin can add tips via `POST /api/tips`

4. **Navigation guards**:
   - Add router guard to redirect unauthenticated users from `/activity`, `/dashboard` to `/profile`
   - Example in `src/router/index.js`:
   ```js
   router.beforeEach((to, from, next) => {
     const token = localStorage.getItem('auth_token')
     const publicPages = ['/profile']
     if (!token && !publicPages.includes(to.path)) {
       next('/profile')
     } else {
       next()
     }
   })
   ```

### For Backend Lead (Member 2)
1. **Challenge endpoints** (needed by ChallengesView):
   - `GET /api/challenges` - List all active challenges
   - `GET /api/challenges/{id}` - Get single challenge details
   - `POST /api/challenges/{id}/join` - Join a challenge (protected)
   - `DELETE /api/challenges/{id}/leave` - Leave a challenge (protected)
   - `GET /api/challenges/my` - Get user's joined challenges (protected)
   - Create `ChallengeController.php` and `ChallengeModel.php`

2. **Tip endpoints** (needed by TipsView):
   - `GET /api/tips` - List all tips (optional: filter by category via query param)
   - `GET /api/tips/category/{category}` - Tips by category
   - `POST /api/tips` - Create tip (admin only)
   - Create `TipController.php` and `TipModel.php`

3. **Badge endpoints** (needed by ProfileView gamification):
   - `GET /api/badges` - List all badges
   - `GET /api/badges/my` - Get user's earned badges (protected)
   - `POST /api/badges/check` - Check and award badges based on activity (protected)
   - Create `BadgeController.php` and `BadgeModel.php`

4. **Admin endpoints** (optional):
   - `GET /api/admin/users` - List all users (admin only)
   - `POST /api/admin/activity-types` - Add activity type (admin only)
   - `POST /api/admin/tips` - Add tip (admin only)

### For DevOps Lead (Member 4)
1. **Capacitor setup**:
   - `npm install @capacitor/core @capacitor/cli`
   - `npx cap init GreenStep com.greenstep.app`
   - `npm run build && npx cap add android`
   - `npx cap sync` after each build
   - Test on Android Studio emulator

2. **Deployment** (optional):
   - Backend: Deploy `api/` to a PHP host (e.g., shared hosting, VPS)
   - Frontend: Deploy `dist/` to Netlify/Vercel
   - Database: Use production MySQL with updated `.env`

---

## 🚀 Setup Instructions (Full Stack)

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
5. Verify: `ActivityType` table should have 17 rows

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

**Verify backend is running:**
```bash
curl http://localhost:8080/api/health
# Should return: {"message":"GreenStep API v1.0","status":"running","database":"connected"}
```

### 3. Frontend Setup

```bash
# Install dependencies (including axios)
npm install

# Run development server
npm run dev
# Frontend runs at http://localhost:5173
```

### 4. Test the Full Flow

1. Open `http://localhost:5173/profile`
2. Register a new user (e.g., name: Danish, email: danish@utm.my, password: Test123456)
3. Go to `/activity` - you should see the activity log form
4. Select a category, activity type, enter amount, and submit
5. Activity appears in "Today's Activities" with real carbon footprint
6. Click "Refresh" to reload, "Delete" to remove an activity

---

## 📡 API Reference

### Authentication (Public)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register` | `{name, email, password}` | Register new user, returns JWT |
| `POST` | `/api/auth/login` | `{email, password}` | Login, returns JWT |
| `GET` | `/api/auth/me` | — | Get current user (requires `Authorization: Bearer <token>`) |

### Activity Types (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/activity-types` | All types grouped by category |
| `GET` | `/api/activity-types/categories` | List of categories |
| `GET` | `/api/activity-types/category/{category}` | Types filtered by category |

### Activity Logging (Protected - requires JWT)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| `GET` | `/api/activities` | — (query: `start_date`, `end_date`) | Get user's activities |
| `GET` | `/api/activities/today` | — | Get today's activities + total footprint |
| `GET` | `/api/activities/stats` | — (query: `start_date`, `end_date`) | Get aggregated stats |
| `POST` | `/api/activities` | `{activity_type_id, amount, date}` | Log new activity |
| `DELETE` | `/api/activities/{id}` | — | Delete an activity |

### Request Headers (for protected routes)
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

---

## 🗄️ Database Schema Overview

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `User` | User accounts | → ActivityLog, ChallengeParticipant, UserBadge, Friendship |
| `ActivityType` | Activity types with emission factors | → ActivityLog |
| `ActivityLog` | Individual activity entries | → User, ActivityType |
| `Tip` | Eco tips | — |
| `Challenge` | Eco challenges | → ChallengeParticipant |
| `ChallengeParticipant` | User-challenge joins | → Challenge, User |
| `Badge` | Achievement badges | → UserBadge |
| `UserBadge` | Earned badges | → User, Badge |
| `Friendship` | Social connections | → User (sender, receiver) |

---

## 🧪 Testing with curl (PowerShell)

**Note:** PowerShell's `curl` is an alias for `Invoke-WebRequest`. For JSON payloads, use a file:

```bash
# Create test-register.json
@'
{"name": "Test User", "email": "test@utm.my", "password": "TestPass123"}
'@ | Set-Content test-register.json

# Register
curl -X POST http://localhost:8080/api/auth/register -H "Content-Type: application/json" --data-binary "@test-register.json"

# Login (create test-login.json similarly)
curl -X POST http://localhost:8080/api/auth/login -H "Content-Type: application/json" --data-binary "@test-login.json"

# Get profile (replace TOKEN)
curl http://localhost:8080/api/auth/me -H "Authorization: Bearer TOKEN"

# Get activity types
curl http://localhost:8080/api/activity-types

# Log activity (create test-activity.json)
curl -X POST http://localhost:8080/api/activities -H "Content-Type: application/json" -H "Authorization: Bearer TOKEN" --data-binary "@test-activity.json"

# Get today's activities
curl http://localhost:8080/api/activities/today -H "Authorization: Bearer TOKEN"
```

---

## 🔑 Key Files for Team Members

| If you're working on... | Start here |
|------------------------|------------|
| Frontend pages | `src/components/` — each view is a `.vue` file |
| API calls | `src/services/api.js` — add new endpoint wrappers here |
| Auth state | `src/stores/auth.js` — Pinia store, already handles JWT |
| New API endpoints | `api/src/routes.php` — add routes, then create controller |
| New DB tables | `database/schema.sql` — add table + seed data |
| JWT middleware | `api/src/Middleware/JwtMiddleware.php` — protects routes |
| Carbon calculation | `api/src/Services/CarbonCalculator.php` — amount × emission factor |

---

## 🐛 Troubleshooting

| Problem | Fix |
|---------|-----|
| "Loading..." stuck on Activity page | Make sure you're logged in (check localStorage for `auth_token`) |
| Empty activity type dropdown | Import `database/schema.sql` into MySQL |
| `getParsedBody() returns null` | Use `--data-binary "@file.json"` with curl in PowerShell |
| CORS error | Backend has CORS middleware, ensure `index.php` middleware order is correct |
| 401 Unauthorized | Token expired or missing — login again |
| 405 Method Not Allowed | Check `api/src/routes.php` — endpoint may only accept certain methods |
| Database connection error | Check `api/.env` — ensure DB credentials match your Laragon setup |

---

## 📖 Additional Documentation

- `api/AUTH_API_GUIDE.md` - Detailed auth endpoint testing guide
- `api/ACTIVITY_API_GUIDE.md` - Activity endpoint testing guide
- `FRONTEND_SETUP.md` - Frontend-backend integration guide
- `DATABASE_SETUP.md` - Database import instructions

---

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)

## 📖 Documentation

- **Frontend**: See `src/` directory
- **Backend API**: See `api/README.md`
- **Database**: See `database/` directory
