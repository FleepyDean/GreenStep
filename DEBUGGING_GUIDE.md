# Debugging "Loading..." Issue

## 🔍 Check These Things

### **1. Are You Logged In?**
The activities endpoint requires JWT authentication.

**Check:**
- Press F12 to open browser console
- Go to "Application" tab → "Local Storage" → `http://localhost:5173`
- Look for `auth_token` - is it there?

**If NO token:**
1. Go to `/profile`
2. Register or login
3. Then go back to `/activity`

---

### **2. Is Backend Running?**
**Test:**
```bash
curl http://localhost:8080/api/health
```

**Expected:**
```json
{
    "message": "GreenStep API v1.0",
    "status": "running",
    "database": "connected"
}
```

**If it fails:** Start backend with `cd api && composer start`

---

### **3. Check Browser Console for Errors**

**Open Console (F12):**
- Look for red error messages
- Common errors:

#### **"Network Error"**
- Backend is not running
- Start: `cd api && composer start`

#### **"401 Unauthorized"**
- Not logged in
- Go to `/profile` and login

#### **"Failed to load activities"**
- Check backend logs
- Verify database has data

#### **CORS Error**
- Backend CORS middleware issue
- Should be fixed already in `index.php`

---

### **4. Test API Directly**

**With Token (after login):**
```bash
# Get your token from localStorage (F12 → Application → Local Storage)
curl http://localhost:8080/api/activities/today \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
    "success": true,
    "date": "2026-06-18",
    "count": 0,
    "total_footprint": 0,
    "activities": []
}
```

---

### **5. Check Database**

**In phpMyAdmin:**
```sql
-- Check if ActivityType table has data
SELECT COUNT(*) FROM ActivityType;
-- Should return 17

-- Check if you have a user
SELECT * FROM User;

-- Check activities
SELECT * FROM ActivityLog;
```

---

## 🐛 Common Issues & Fixes

### **Issue: "Loading..." Forever**

**Cause:** API request is failing silently

**Fix:**
1. Open browser console (F12)
2. Look for error message
3. Check Network tab for failed requests
4. Verify you're logged in (check localStorage for `auth_token`)

---

### **Issue: "Please login to view activities"**

**Cause:** No JWT token or expired token

**Fix:**
1. Go to `/profile`
2. Login with your credentials
3. Go back to `/activity`

---

### **Issue: Empty Activities List**

**Cause:** No activities logged yet (this is normal!)

**Fix:**
1. Log an activity using the form
2. It will appear in the list

---

### **Issue: Dropdown Empty (No Activity Types)**

**Cause:** Database not imported

**Fix:**
1. Import `database/schema.sql` in phpMyAdmin
2. Verify: `http://localhost:8080/api/activity-types`
3. Refresh page

---

## 🧪 Step-by-Step Debug

1. **Open Browser Console (F12)**
   - Go to Console tab
   - Refresh page
   - Look for errors

2. **Check Network Tab**
   - Go to Network tab
   - Refresh page
   - Look for `/activities/today` request
   - Click on it to see response

3. **Check Request Headers**
   - Does it have `Authorization: Bearer ...`?
   - If NO → You're not logged in

4. **Check Response**
   - Status 200 = Success
   - Status 401 = Not authenticated
   - Status 500 = Server error

---

## ✅ Quick Checklist

- [ ] Backend running (`http://localhost:8080/api/health`)
- [ ] Database imported (17 activity types)
- [ ] Logged in (check localStorage for `auth_token`)
- [ ] Frontend running (`http://localhost:5173`)
- [ ] No console errors
- [ ] axios installed (`npm install axios`)

---

## 🚀 If Still Stuck

**Share this info:**
1. Browser console errors (screenshot)
2. Network tab showing failed request
3. Backend terminal output
4. Whether you're logged in (check localStorage)

**Most likely cause:** Not logged in! Go to `/profile` first! 🔐
