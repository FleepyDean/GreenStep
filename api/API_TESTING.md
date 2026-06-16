# API Testing Guide

Quick reference for testing GreenStep API endpoints.

## 🛠️ Testing Tools

### Option 1: Browser (GET requests only)
Simply visit the URL in your browser.

### Option 2: Postman
Download: https://www.postman.com/downloads/

### Option 3: Thunder Client (VS Code Extension)
Install in VS Code: Thunder Client

### Option 4: cURL (Command Line)
```bash
curl http://localhost:8080/api/endpoint
```

---

## 📍 Current Endpoints

### Health Check (Public)

**GET** `/`

**Response:**
```json
{
    "message": "GreenStep API v1.0",
    "status": "running",
    "timestamp": "2026-06-14 13:10:00"
}
```

**Test in Browser:**
```
http://localhost:8080/
```

**Test with cURL:**
```bash
curl http://localhost:8080/
```

---

## 🔐 Authentication Endpoints (Coming Soon)

### Register User

**POST** `/api/auth/register`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "name": "Danish",
    "email": "danish@utm.my",
    "password": "SecurePass123"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "name": "Danish",
        "email": "danish@utm.my",
        "role": "end-user"
    }
}
```

---

### Login User

**POST** `/api/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "email": "danish@utm.my",
    "password": "SecurePass123"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
        "id": 1,
        "name": "Danish",
        "email": "danish@utm.my",
        "role": "end-user"
    }
}
```

---

## 🔒 Protected Endpoints (Require JWT)

For protected endpoints, add this header:

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

### Example: Get User Profile

**GET** `/api/users/profile`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Expected Response:**
```json
{
    "success": true,
    "user": {
        "id": 1,
        "name": "Danish",
        "email": "danish@utm.my",
        "role": "end-user",
        "joined_at": "2026-06-14 13:00:00"
    }
}
```

---

## 📝 Activity Endpoints (Coming Soon)

### Log New Activity

**POST** `/api/activities`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
    "activity_type_id": 1,
    "amount": 15.5,
    "date": "2026-06-14"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Activity logged successfully",
    "activity": {
        "id": 1,
        "user_id": 1,
        "activity_type_id": 1,
        "amount": 15.5,
        "carbon_footprint": 2.98,
        "logged_on": "2026-06-14"
    }
}
```

---

### Get Today's Activities

**GET** `/api/activities/today`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Expected Response:**
```json
{
    "success": true,
    "activities": [
        {
            "id": 1,
            "category": "Transport",
            "type": "Car (Petrol)",
            "amount": 15.5,
            "carbon_footprint": 2.98,
            "logged_on": "2026-06-14"
        }
    ],
    "total_footprint": 2.98
}
```

---

## 🧪 Testing Workflow

### 1. Test Health Check
```bash
curl http://localhost:8080/
```

### 2. Register a User
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Danish","email":"danish@utm.my","password":"Test123"}'
```

### 3. Login and Get Token
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"danish@utm.my","password":"Test123"}'
```

### 4. Use Token for Protected Endpoints
```bash
curl http://localhost:8080/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📊 Expected HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful GET/PUT/DELETE |
| 201 | Created | Successful POST (resource created) |
| 400 | Bad Request | Invalid input/validation error |
| 401 | Unauthorized | Missing/invalid JWT token |
| 403 | Forbidden | Valid token but insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 500 | Server Error | Internal server error |

---

## 🐛 Common Errors & Solutions

### Error: "Cannot connect to localhost:8080"
**Solution:** Ensure PHP server is running (`composer start` in api/)

### Error: "Unauthorized" (401)
**Solution:** Include valid JWT token in Authorization header

### Error: "Invalid JSON"
**Solution:** Check Content-Type header and JSON syntax

### Error: "Database connection failed"
**Solution:** Verify MySQL is running and .env credentials are correct

---

## 📝 Postman Collection (Coming Soon)

After all endpoints are implemented, we'll create a Postman collection for easy testing.

---

## ✅ Testing Checklist

Before marking an endpoint as complete:

- [ ] Returns correct HTTP status code
- [ ] Returns valid JSON response
- [ ] Handles invalid input gracefully
- [ ] Protected endpoints verify JWT
- [ ] Database operations use prepared statements
- [ ] Error messages are clear and helpful
- [ ] Response structure is consistent

---

**Happy Testing! 🚀**
