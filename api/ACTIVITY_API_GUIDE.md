# Activity Logging API Guide

Complete guide for testing the Carbon Tracking Activity Logging API.

## 📍 Activity API Endpoints

### **Activity Types (Public - No Auth Required)**

#### 1. Get All Activity Types

**GET** `/api/activity-types`

**Query Parameters:**
- `grouped` (optional): `true` or `false` - Group by category (default: true)

**Test URLs:**
```
http://localhost:8080/api/activity-types
http://localhost:8080/api/activity-types?grouped=true
```

**Expected Response:**
```json
{
    "success": true,
    "count": 16,
    "activity_types": {
        "Transport": [
            {"id": 1, "category": "Transport", "name": "Car (Petrol)", "unit": "km", "kg_co2_per_unit": 0.192},
            {"id": 2, "category": "Transport", "name": "Bus / Train travel", "unit": "km", "kg_co2_per_unit": 0.089}
        ],
        "Diet": [...],
        "Energy": [...],
        "Recycling": [...]
    }
}
```

---

#### 2. Get Available Categories

**GET** `/api/activity-types/categories`

**Test URL:**
```
http://localhost:8080/api/activity-types/categories
```

**Expected Response:**
```json
{
    "success": true,
    "categories": ["Diet", "Energy", "Recycling", "Transport"]
}
```

---

#### 3. Get Activity Types by Category

**GET** `/api/activity-types/category/{category}`

**Test URLs:**
```
http://localhost:8080/api/activity-types/category/Transport
http://localhost:8080/api/activity-types/category/Diet
http://localhost:8080/api/activity-types/category/Energy
http://localhost:8080/api/activity-types/category/Recycling
```

**Expected Response:**
```json
{
    "success": true,
    "category": "Transport",
    "count": 5,
    "activity_types": [
        {"id": 1, "category": "Transport", "name": "Car (Petrol)", "unit": "km", "kg_co2_per_unit": 0.192},
        {"id": 2, "category": "Transport", "name": "Bus / Train travel", "unit": "km", "kg_co2_per_unit": 0.089},
        {"id": 3, "category": "Transport", "name": "Bicycle commute", "unit": "km", "kg_co2_per_unit": 0},
        {"id": 4, "category": "Transport", "name": "Motorcycle", "unit": "km", "kg_co2_per_unit": 0.103},
        {"id": 5, "category": "Transport", "name": "Car (Electric)", "unit": "km", "kg_co2_per_unit": 0.053}
    ]
}
```

---

### **Activity Logging (Requires User ID - Will use JWT Later)**

#### 4. Log New Activity

**POST** `/api/activities`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "activity_type_id": 1,
    "amount": 15.5,
    "date": "2026-06-17"
}
```

**Test with cURL:**
```bash
curl -X POST http://localhost:8080/api/activities \
  -H "Content-Type: application/json" \
  -d '{"activity_type_id": 1, "amount": 15.5, "date": "2026-06-17"}'
```

**Expected Response (201 Created):**
```json
{
    "success": true,
    "message": "Activity logged successfully",
    "activity": {
        "id": 1,
        "activity_type_id": 1,
        "category": "Transport",
        "activity_name": "Car (Petrol)",
        "unit": "km",
        "amount": 15.5,
        "carbon_footprint": 2.976,
        "emission_factor": 0.192,
        "logged_on": "2026-06-17"
    }
}
```

**Note:** The `carbon_footprint` is calculated automatically: `15.5 km × 0.192 kg/km = 2.976 kg CO₂`

---

#### 5. Get All Activities

**GET** `/api/activities`

**Query Parameters:**
- `start_date` (optional): `YYYY-MM-DD`
- `end_date` (optional): `YYYY-MM-DD`

**Test URLs:**
```
http://localhost:8080/api/activities
http://localhost:8080/api/activities?start_date=2026-06-01&end_date=2026-06-30
```

**Expected Response:**
```json
{
    "success": true,
    "count": 3,
    "activities": [
        {
            "id": 1,
            "amount": 15.5,
            "carbon_footprint": 2.976,
            "logged_on": "2026-06-17",
            "activity_type_id": 1,
            "category": "Transport",
            "activity_name": "Car (Petrol)",
            "unit": "km",
            "emission_factor": 0.192
        }
    ]
}
```

---

#### 6. Get Today's Activities

**GET** `/api/activities/today`

**Test URL:**
```
http://localhost:8080/api/activities/today
```

**Expected Response:**
```json
{
    "success": true,
    "date": "2026-06-17",
    "count": 2,
    "total_footprint": 5.352,
    "activities": [
        {
            "id": 1,
            "category": "Transport",
            "activity_name": "Car (Petrol)",
            "amount": 15.5,
            "carbon_footprint": 2.976,
            ...
        },
        {
            "id": 2,
            "category": "Diet",
            "activity_name": "Red Meat Meal",
            "amount": 1,
            "carbon_footprint": 6.61,
            ...
        }
    ]
}
```

---

#### 7. Get Activity Statistics

**GET** `/api/activities/stats`

**Query Parameters:**
- `start_date` (optional): `YYYY-MM-DD`
- `end_date` (optional): `YYYY-MM-DD`

**Test URLs:**
```
http://localhost:8080/api/activities/stats
http://localhost:8080/api/activities/stats?start_date=2026-06-01&end_date=2026-06-30
```

**Expected Response:**
```json
{
    "success": true,
    "stats": {
        "total_footprint": 12.586,
        "activity_count": 5,
        "category_breakdown": {
            "Transport": 2.976,
            "Diet": 6.61,
            "Energy": 3.0
        },
        "period_start": "2026-06-01",
        "period_end": "2026-06-30"
    }
}
```

---

#### 8. Delete Activity

**DELETE** `/api/activities/{id}`

**Test with cURL:**
```bash
curl -X DELETE http://localhost:8080/api/activities/1
```

**Expected Response (Success):**
```json
{
    "success": true,
    "message": "Activity deleted successfully"
}
```

**Expected Response (Not Found):**
```json
{
    "success": false,
    "message": "Activity not found"
}
```

---

## 🔢 Emission Factors Reference

| Category | Activity | Unit | kg CO₂ / Unit |
|----------|----------|------|---------------|
| **Transport** | Car (Petrol) | km | 0.192 |
| | Bus / Train | km | 0.089 |
| | Bicycle | km | 0.000 |
| | Motorcycle | km | 0.103 |
| | Car (Electric) | km | 0.053 |
| **Diet** | Red Meat Meal | meal | 6.610 |
| | Vegetarian Meal | meal | 1.770 |
| | Vegan Meal | meal | 1.050 |
| | Chicken Meal | meal | 1.370 |
| | Fish Meal | meal | 1.430 |
| **Energy** | Electricity | kWh | 0.475 |
| | Air Conditioner | hour | 0.850 |
| | Natural Gas | kWh | 0.185 |
| **Recycling** | Paper | kg | -0.170 |
| | Plastic | kg | -0.450 |
| | Glass | kg | -0.300 |
| | Aluminum | kg | -9.130 |

**Note:** Negative values indicate carbon reduction (savings).

---

## 🧪 Testing Workflow

### Test 1: Get Activity Types
```bash
curl http://localhost:8080/api/activity-types
```

### Test 2: Log Multiple Activities
```bash
# Log car travel
curl -X POST http://localhost:8080/api/activities \
  -H "Content-Type: application/json" \
  -d '{"activity_type_id": 1, "amount": 15.5}'

# Log vegetarian meal
curl -X POST http://localhost:8080/api/activities \
  -H "Content-Type: application/json" \
  -d '{"activity_type_id": 7, "amount": 1}'

# Log paper recycling (carbon reduction)
curl -X POST http://localhost:8080/api/activities \
  -H "Content-Type: application/json" \
  -d '{"activity_type_id": 14, "amount": 5}'
```

### Test 3: Check Today's Activities
```bash
curl http://localhost:8080/api/activities/today
```

### Test 4: Get Statistics
```bash
curl http://localhost:8080/api/activities/stats
```

---

## 📝 Sample Activity Type IDs

| ID | Category | Name |
|----|----------|------|
| 1 | Transport | Car (Petrol) |
| 2 | Transport | Bus / Train travel |
| 3 | Transport | Bicycle commute |
| 6 | Diet | Red Meat Meal |
| 7 | Diet | Vegetarian Meal |
| 8 | Diet | Vegan Meal |
| 11 | Energy | Electricity Usage |
| 12 | Energy | Air Conditioner usage |
| 14 | Recycling | Paper Recycling |
| 15 | Recycling | Plastic Recycling |

**Full list:** Use `GET /api/activity-types` to get all IDs.

---

## ⚠️ Error Responses

### Invalid Activity Type
```json
{
    "success": false,
    "message": "Activity type not found"
}
```

### Missing Required Fields
```json
{
    "success": false,
    "message": "Missing required fields: activity_type_id and amount are required"
}
```

### Invalid Amount
```json
{
    "success": false,
    "message": "Amount must be greater than 0"
}
```

---

## ✅ Integration with Frontend

The frontend `ActivityLogView.vue` can now connect to these endpoints:

1. **Load categories:** `GET /api/activity-types` (populate dropdowns)
2. **Submit form:** `POST /api/activities`
3. **Display list:** `GET /api/activities/today`
4. **Delete item:** `DELETE /api/activities/{id}`

Next step: Create the API service layer in Vue to connect to these endpoints!
