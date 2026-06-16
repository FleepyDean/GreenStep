# GreenStep API Backend

RESTful API backend for GreenStep Carbon Footprint Tracker built with PHP Slim 4.

## Technology Stack

- **PHP 8.0+** - Server-side language
- **Slim Framework 4** - Micro-framework for RESTful APIs
- **MySQL** - Relational database
- **PDO** - Database access layer with prepared statements
- **JWT** - JSON Web Token authentication
- **Bcrypt** - Password hashing

## Project Structure

```
api/
├── public/
│   ├── index.php          # Application entry point
│   └── .htaccess          # URL rewriting rules
├── src/
│   ├── Controllers/       # API endpoint controllers
│   ├── Models/            # Database models (PDO)
│   ├── Middleware/        # JWT auth, CORS, error handling
│   ├── Services/          # Business logic (carbon calculations)
│   └── routes.php         # Route definitions
├── config/
│   └── database.php       # Database connection configuration
├── composer.json          # PHP dependencies
├── .env.example           # Environment variables template
└── .gitignore
```

## Setup Instructions

### 1. Install Dependencies

```bash
cd api
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` file with your database credentials:
```
DB_HOST=localhost
DB_NAME=greenstep_db
DB_USER=root
DB_PASS=your_password
JWT_SECRET=generate-a-random-secret-key
```

### 3. Create Database

Run the SQL schema file in your MySQL client:
```bash
mysql -u root -p < ../database/schema.sql
```

### 4. Start Development Server

Using PHP built-in server:
```bash
composer start
```

Or configure Laragon to point to the `public/` directory.

### 5. Test API

Visit: `http://localhost:8080/`

Expected response:
```json
{
    "message": "GreenStep API v1.0",
    "status": "running",
    "timestamp": "2026-06-14 13:10:00"
}
```

## API Documentation

API endpoints will be documented as they are implemented.

### Base URL
- Development: `http://localhost:8080/api`
- Production: TBD

### Authentication
Protected endpoints require JWT token in header:
```
Authorization: Bearer <your-jwt-token>
```

## Development Roadmap

- [x] Project structure setup
- [ ] Database schema implementation
- [ ] Authentication endpoints (register/login)
- [ ] Activity logging CRUD
- [ ] Carbon calculation service
- [ ] Tips management
- [ ] Challenges system
- [ ] Badges & gamification
- [ ] Frontend integration

## Team Member

**Member 2** - Backend & API Lead
- PHP Slim project setup
- RESTful endpoint design
- Business logic implementation
- JSON request/response shaping
- Error handling
- API documentation
