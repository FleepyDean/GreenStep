# GreenStep - Carbon Footprint & Eco-Lifestyle Tracker

**SCSM2223 Cross-Platform Application Development - Group Project**

A full-stack web application to help users track and reduce their daily carbon footprint through actionable insights, gamification, and community challenges.

## 🌱 Project Overview

**Problem Statement:** Climate change is among the most urgent global challenges, and individuals collectively account for a large share of avoidable carbon emissions through transport, diet, and energy choices.

**Solution:** GreenStep targets the gap - a friendly daily logger that estimates a user's carbon footprint, suggests one realistic action per day, and lets friends compete on streaks and reductions.

## 🛠️ Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Frontend** | Vue 3 + Router + Pinia | Dynamic, mobile-first SPA |
| **Backend API** | PHP Slim 4 + PDO | RESTful API with business logic |
| **Database** | MySQL | Persistent storage with relationships |
| **Security** | JWT + Bcrypt | Authentication & password hashing |
| **Mobile** | Capacitor | Android native build |

## 📁 Project Structure

```
GreenStep/
├── src/              # Vue 3 frontend (Member 1)
├── api/              # PHP Slim 4 backend (Member 2)
├── database/         # MySQL schema & seeds (Member 3)
└── README.md
```

## 👥 Team Roles

- **Member 1** - Frontend Lead (Vue scaffolding, components, routing, UI/UX)
- **Member 2** - Backend & API Lead (PHP Slim, RESTful endpoints, business logic)
- **Member 3** - Database & Security Lead (ER diagram, MySQL schema, JWT, validation)
- **Member 4** - DevOps & Mobile Lead (Deployment, Capacitor, integration testing)

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## 🚀 Setup Instructions

### Frontend Setup (Vue 3)

```bash
# Install dependencies
npm install

# Run development server
npm run dev

# Build for production
npm run build

# Lint code
npm run lint
```

### Backend Setup (PHP Slim 4)

```bash
# Navigate to API directory
cd api

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Start development server
composer start
# Or use Laragon and point to api/public/ directory
```

### Database Setup (MySQL)

```bash
# Create database
mysql -u root -p
CREATE DATABASE greenstep_db;

# Import schema (after it's created)
mysql -u root -p greenstep_db < database/schema.sql
```

## 📖 Documentation

- **Frontend**: See `src/` directory
- **Backend API**: See `api/README.md`
- **Database**: See `database/` directory
