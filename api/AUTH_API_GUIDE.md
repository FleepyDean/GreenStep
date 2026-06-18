# Authentication API Guide

Complete guide for testing JWT Authentication endpoints.

## 🔐 Authentication Endpoints

### **1. Register New User**

**POST** `/api/auth/register`

**Public Access** (No token required)

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Danish Farish",
    "email": "danish@utm.my",
    "password": "SecurePass123"
}
```

**Test with cURL:**
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Danish Farish", "email": "danish@utm.my", "password": "SecurePass123"}'
```

**Expected Response (201 Created):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 86400,
    "user": {
        "id": 1,
        "name": "Danish Farish",
        "email": "danish@utm.my",
        "role": "end-user"
    }
}
```

**Error Response (409 Conflict - Email exists):**
```json
{
    "success": false,
    "message": "Email already registered"
}
```

**Error Response (400 Bad Request - Validation):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": "Invalid email format",
        "password": "Password must be at least 8 characters"
    }
}
```

---

### **2. Login**

**POST** `/api/auth/login`

**Public Access** (No token required)

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "email": "danish@utm.my",
    "password": "SecurePass123"
}
```

**Test with cURL:**
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "danish@utm.my", "password": "SecurePass123"}'
```

**Expected Response (200 OK):**
```json
{
    "success": true,
    "message": "Login successful",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 86400,
    "user": {
        "id": 1,
        "name": "Danish Farish",
        "email": "danish@utm.my",
        "role": "end-user",
        "joined_at": "2026-06-17 12:00:00"
    }
}
```

**Error Response (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

---

### **3. Get Current User Profile (Protected)**

**GET** `/api/auth/me`

**Protected Access** (Requires valid JWT token)

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

**Test with cURL:**
```bash
curl http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

**Expected Response (200 OK):**
```json
{
    "success": true,
    "user": {
        "id": 1,
        "name": "Danish Farish",
        "email": "danish@utm.my",
        "role": "end-user",
        "joined_at": "2026-06-17 12:00:00"
    }
}
```

**Error Response (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Authorization header missing"
}
```

**Error Response (401 - Invalid Token):**
```json
{
    "success": false,
    "message": "Invalid or expired token"
}
```

---

## 🔒 Protected Activity Endpoints (Require JWT)

Now that authentication is in place, all activity endpoints require a JWT token:

### **Test Activity Logging with Auth**

**Step 1: Login and get token**
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "danish@utm.my", "password": "SecurePass123"}'
```

**Step 2: Use token to log activity**
```bash
# Replace YOUR_TOKEN with the token from login response
curl -X POST http://localhost:8080/api/activities \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"activity_type_id": 1, "amount": 15.5}'
```

**Step 3: Get today's activities (protected)**
```bash
curl http://localhost:8080/api/activities/today \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🧪 Complete Testing Workflow

### Test 1: Register a new user
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Test User", "email": "test@utm.my", "password": "TestPass123"}'
```

### Test 2: Try to login with wrong password
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@utm.my", "password": "wrongpassword"}'
```
Should return: `"Invalid credentials"`

### Test 3: Login with correct password
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@utm.my", "password": "TestPass123"}'
```
Save the token from response!

### Test 4: Access protected route without token
```bash
curl http://localhost:8080/api/auth/me
```
Should return: `"Authorization header missing"`

### Test 5: Access protected route with token
```bash
curl http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```
Should return: User profile

### Test 6: Try to access activity logging without token
```bash
curl http://localhost:8080/api/activities/today
```
Should return: 401 Unauthorized

### Test 7: Access activity logging with token
```bash
curl http://localhost:8080/api/activities/today \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```
Should return: Activities list (empty for new user)

---

## 📋 Testing Checklist

- [ ] Register new user returns token
- [ ] Duplicate email registration fails
- [ ] Login with correct credentials works
- [ ] Login with wrong password fails
- [ ] Protected route without token returns 401
- [ ] Protected route with valid token works
- [ ] Activity endpoints now require token
- [ ] Token expires after 24 hours (JWT_EXPIRY setting)

---

## 🔐 JWT Token Format

The JWT token contains:
```json
{
    "iat": 1718650000,        // Issued at timestamp
    "exp": 1718736400,        // Expiration (24 hours)
    "sub": 1,                 // User ID
    "email": "danish@utm.my",
    "role": "end-user"
}
```

**Header format:** `Authorization: Bearer <token>`

---

## ⚠️ Common Errors

### "Authorization header missing"
- Solution: Add `Authorization: Bearer YOUR_TOKEN` header

### "Invalid token format. Use: Bearer TOKEN"
- Solution: Ensure header format is exactly: `Bearer YOUR_TOKEN_HERE`

### "Invalid or expired token"
- Solution: Token is expired or invalid. Login again to get new token.

### "Email already registered"
- Solution: Use a different email or login with existing credentials.

### "Invalid credentials"
- Solution: Wrong email or password. Check credentials and try again.

---

## 🔧 Environment Variables

Ensure your `.env` file has:
```env
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
JWT_EXPIRY=86400
```

- `JWT_SECRET`: Secret key for signing tokens (keep this secure!)
- `JWT_EXPIRY`: Token expiration in seconds (86400 = 24 hours)

---

## 🚀 Next Steps

After authentication is working:

1. ✅ **Test all auth endpoints** (register, login, me)
2. ✅ **Test protected activity endpoints** with token
3. 🔄 **Connect Vue frontend** to use these endpoints
4. 🔄 **Store token in localStorage** on frontend
5. 🔄 **Add token to all API requests** from frontend

**Ready to test? Start the server and run through the testing workflow above!** 🎉
