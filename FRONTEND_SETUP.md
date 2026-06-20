# Frontend-Backend Integration Setup

## 🎯 What Was Done

Connected the Activity Log page to your backend API with:
1. ✅ API service layer (`src/services/api.js`)
2. ✅ Auth store for JWT management (`src/stores/auth.js`)
3. ✅ Updated ActivityLogView to use real API
4. ✅ Updated ProfileView for real authentication

---

## 📦 Install Dependencies

```bash
npm install axios
```

---

## 🚀 Start Both Servers

### Terminal 1: Backend API
```bash
cd api
composer start
```
Server runs at: `http://localhost:8080`

### Terminal 2: Frontend
```bash
npm run dev
```
Frontend runs at: `http://localhost:5173`

---

## 🧪 Testing Workflow

### 1. Register a New User
1. Go to `http://localhost:5173/profile`
2. Click "Register account"
3. Fill in:
   - Name: Your Name
   - Email: test@utm.my
   - Password: TestPass123
4. Click "Complete Registration"
5. You should be logged in automatically!

### 2. Log an Activity
1. Go to `http://localhost:5173/activity`
2. Select:
   - Category: Transport
   - Activity Type: Car (Petrol) (km)
   - Amount: 15.5
   - Date: Today
3. Click "Log Activity"
4. Activity appears in "Today's Activities" with carbon footprint!

### 3. View Carbon Footprint
- Today's Total Footprint shows real calculation
- Each activity shows: amount, unit, and CO₂ emissions

### 4. Delete Activity
- Click "Delete" button on any activity
- Confirms deletion
- Total footprint updates automatically

---

## 🔑 How JWT Authentication Works

1. **Register/Login** → Backend returns JWT token
2. **Token Stored** → Saved in localStorage
3. **Auto-Attached** → Every API request includes token in header
4. **Protected Routes** → Activity endpoints require valid token
5. **Auto-Redirect** → If token expires, redirects to login

---

## 📊 API Endpoints Used

### Authentication (Public)
- `POST /api/auth/register` - Create account
- `POST /api/auth/login` - Get JWT token
- `GET /api/auth/me` - Get profile (protected)

### Activity Types (Public)
- `GET /api/activity-types` - Get all types with emission factors

### Activity Logging (Protected - Requires JWT)
- `POST /api/activities` - Log new activity
- `GET /api/activities/today` - Get today's activities
- `DELETE /api/activities/{id}` - Delete activity

---

## 🐛 Troubleshooting

### "Authorization header missing"
- You're not logged in
- Go to `/profile` and login

### "Failed to load activities"
- Backend server not running
- Check `http://localhost:8080/api/health`

### "Network Error"
- CORS issue or backend offline
- Restart backend server

### Activities not showing
- Check browser console (F12)
- Verify you're logged in
- Check backend logs

---

## ✅ Features Now Working

| Feature | Status |
|---------|--------|
| ✅ User Registration | Working |
| ✅ User Login | Working |
| ✅ JWT Token Management | Working |
| ✅ Activity Type Dropdown | Working (from DB) |
| ✅ Log Activity | Working (saves to DB) |
| ✅ View Today's Activities | Working (from DB) |
| ✅ Carbon Calculation | Working (automatic) |
| ✅ Delete Activity | Working |
| ✅ Total Footprint | Working (real-time) |

---

## 🎉 Your Responsibility Complete!

**Activity Logging Interface** is now fully functional with:
- Real database integration
- JWT authentication
- Carbon footprint calculation
- CRUD operations
- Error handling
- Loading states

**Ready to demo!** 🚀
