# GreenStep - Sequence Diagrams

## 1. User Authentication Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Vue App
    participant Auth Store
    participant API
    participant JWT Middleware
    participant Database

    User->>Browser: Navigate to /profile
    Browser->>Vue App: Load ProfileView
    Vue App->>User: Display login/register form
    
    User->>Vue App: Enter credentials & submit
    Vue App->>Auth Store: login(email, password)
    Auth Store->>API: POST /api/auth/login
    API->>Database: SELECT * FROM User WHERE email=?
    Database-->>API: User record
    API->>API: Verify password (bcrypt)
    API->>API: Generate JWT token
    API-->>Auth Store: {success: true, token, user}
    Auth Store->>Auth Store: Store token in localStorage
    Auth Store->>Auth Store: Set user state
    Auth Store-->>Vue App: Login successful
    Vue App->>Browser: Redirect to /dashboard
```

## 2. Activity Logging Flow (Complete)

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant ActivityLogView
    participant EventBus
    participant API
    participant JWT Middleware
    participant ActivityController
    participant BadgeController
    participant Database

    User->>Browser: Navigate to /activity
    Browser->>ActivityLogView: Mount component
    ActivityLogView->>API: GET /api/activity-types
    API->>Database: SELECT * FROM ActivityType
    Database-->>API: Activity types list
    API-->>ActivityLogView: {success: true, data: [...]}
    ActivityLogView->>User: Display activity form

    User->>ActivityLogView: Select category & type
    User->>ActivityLogView: Enter amount, date, photo
    User->>ActivityLogView: Click "Log Activity"
    
    ActivityLogView->>API: POST /api/activities {activity_type_id, amount, date, photo}
    API->>JWT Middleware: Validate JWT token
    JWT Middleware->>JWT Middleware: Decode & verify token
    JWT Middleware-->>API: User ID extracted
    
    API->>ActivityController: logActivity(user_id, data)
    ActivityController->>Database: SELECT emission_factor FROM ActivityType WHERE id=?
    Database-->>ActivityController: emission_factor
    ActivityController->>ActivityController: Calculate carbon_footprint = amount × emission_factor
    
    ActivityController->>Database: INSERT INTO ActivityLog (user_id, activity_type_id, amount, carbon_footprint, logged_on, photo_url)
    Database-->>ActivityController: Insert successful, activity_id
    
    ActivityController->>BadgeController: checkAndAwardBadges(user_id)
    BadgeController->>Database: SELECT SUM(amount) FROM ActivityLog WHERE user_id=? GROUP BY category
    Database-->>BadgeController: User category totals
    BadgeController->>Database: SELECT * FROM Badge
    Database-->>BadgeController: All badges
    BadgeController->>BadgeController: Check badge criteria
    BadgeController->>Database: INSERT IGNORE INTO UserBadge (user_id, badge_id)
    Database-->>BadgeController: Badges awarded
    BadgeController-->>ActivityController: Newly earned badges
    
    ActivityController-->>API: {success: true, activity, newBadges}
    API-->>ActivityLogView: Success response
    
    ActivityLogView->>EventBus: emit('activity-logged')
    EventBus->>DashboardView: Trigger 'activity-logged' listener
    DashboardView->>API: GET /api/dashboard
    DashboardView->>API: GET /api/goal
    API->>Database: Fetch updated metrics
    Database-->>API: Updated data
    API-->>DashboardView: Fresh dashboard data
    
    ActivityLogView->>User: Show success toast
    ActivityLogView->>ActivityLogView: Refresh activity history
```

## 3. Dashboard Data Fetching Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant DashboardView
    participant API
    participant JWT Middleware
    participant DashboardController
    participant GoalController
    participant Database

    User->>Browser: Navigate to /dashboard
    Browser->>DashboardView: Mount component
    
    DashboardView->>API: GET /api/dashboard
    API->>JWT Middleware: Validate JWT token
    JWT Middleware-->>API: User ID
    API->>DashboardController: getDashboard(user_id)
    
    par Fetch Today's Footprint
        DashboardController->>Database: SELECT SUM(carbon_footprint) FROM ActivityLog WHERE user_id=? AND DATE(logged_on)=TODAY
        Database-->>DashboardController: today_footprint
    and Fetch Weekly Footprint
        DashboardController->>Database: SELECT SUM(carbon_footprint) FROM ActivityLog WHERE user_id=? AND logged_on >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        Database-->>DashboardController: weekly_footprint
    and Fetch Monthly Footprint
        DashboardController->>Database: SELECT SUM(carbon_footprint) FROM ActivityLog WHERE user_id=? AND MONTH(logged_on)=MONTH(NOW())
        Database-->>DashboardController: monthly_footprint
    and Fetch Category Breakdown
        DashboardController->>Database: SELECT category, SUM(carbon_footprint) FROM ActivityLog JOIN ActivityType WHERE user_id=? GROUP BY category
        Database-->>DashboardController: category_breakdown
    and Fetch Recent Activities
        DashboardController->>Database: SELECT * FROM ActivityLog WHERE user_id=? ORDER BY logged_on DESC LIMIT 5
        Database-->>DashboardController: recent_activities
    and Fetch Badges
        DashboardController->>Database: SELECT * FROM Badge LEFT JOIN UserBadge WHERE user_id=?
        Database-->>DashboardController: badges_data
    end
    
    DashboardController-->>API: {success: true, data: {...}}
    API-->>DashboardView: Dashboard metrics
    
    DashboardView->>API: GET /api/goal
    API->>GoalController: getGoal(user_id)
    GoalController->>Database: SELECT * FROM User WHERE id=?
    Database-->>GoalController: User goal settings
    GoalController->>GoalController: ensureGoal() - calculate baseline if needed
    GoalController->>Database: SELECT SUM(carbon_footprint) FROM ActivityLog WHERE user_id=? AND logged_on >= goal_start_date
    Database-->>GoalController: current_footprint
    GoalController->>GoalController: calculateProjection()
    GoalController-->>API: {success: true, goal, projection, progress}
    API-->>DashboardView: Goal data
    
    DashboardView->>User: Render dashboard with charts & metrics
```

## 4. Challenge Join & Progress Tracking Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant ChallengesView
    participant API
    participant JWT Middleware
    participant ChallengeController
    participant Database

    User->>Browser: Navigate to /challenges
    Browser->>ChallengesView: Mount component
    
    ChallengesView->>API: GET /api/challenges
    API->>Database: SELECT * FROM Challenge WHERE end_date >= NOW()
    Database-->>API: Active challenges
    API->>Database: SELECT * FROM ChallengeParticipant WHERE user_id=?
    Database-->>API: User's participations
    API-->>ChallengesView: {challenges, userParticipations}
    ChallengesView->>User: Display challenge cards
    
    User->>ChallengesView: Click "Join Challenge"
    ChallengesView->>API: POST /api/challenges/:id/join
    API->>JWT Middleware: Validate JWT token
    JWT Middleware-->>API: User ID
    API->>ChallengeController: joinChallenge(challenge_id, user_id)
    
    ChallengeController->>Database: SELECT * FROM Challenge WHERE id=?
    Database-->>ChallengeController: Challenge details
    ChallengeController->>Database: INSERT INTO ChallengeParticipant (challenge_id, user_id, progress, joined_at)
    Database-->>ChallengeController: Participation created
    
    ChallengeController->>Database: SELECT COUNT(*) FROM ActivityLog WHERE user_id=? AND category=? AND logged_on >= ?
    Database-->>ChallengeController: Initial progress
    ChallengeController->>Database: UPDATE ChallengeParticipant SET progress=? WHERE id=?
    Database-->>ChallengeController: Progress updated
    
    ChallengeController-->>API: {success: true, participation}
    API-->>ChallengesView: Success response
    ChallengesView->>User: Show success toast & update UI
```

## 5. Friend Connection Flow

```mermaid
sequenceDiagram
    actor User A
    participant Browser
    participant FriendsView
    participant API
    participant JWT Middleware
    participant SocialController
    participant Database
    actor User B

    User A->>Browser: Navigate to /friends
    Browser->>FriendsView: Mount component
    
    FriendsView->>API: GET /api/friends
    API->>JWT Middleware: Validate JWT token
    JWT Middleware-->>API: User A ID
    API->>Database: SELECT * FROM Friendship WHERE sender_id=? OR receiver_id=?
    Database-->>API: Friendships list
    API-->>FriendsView: {friends, pendingRequests, sentRequests}
    
    User A->>FriendsView: Search for User B by email
    FriendsView->>API: GET /api/friends/search?email=userB@email.com
    API->>Database: SELECT id, name, email FROM User WHERE email LIKE ?
    Database-->>API: User B found
    API-->>FriendsView: {success: true, users: [userB]}
    
    User A->>FriendsView: Click "Add Friend"
    FriendsView->>API: POST /api/friends/request {receiver_id: userB_id}
    API->>JWT Middleware: Validate JWT token
    JWT Middleware-->>API: User A ID
    API->>SocialController: sendFriendRequest(sender_id, receiver_id)
    
    SocialController->>Database: INSERT INTO Friendship (sender_id, receiver_id, status) VALUES (?, ?, 'pending')
    Database-->>SocialController: Friend request created
    SocialController-->>API: {success: true, request}
    API-->>FriendsView: Success response
    FriendsView->>User A: Show "Request sent" toast
    
    Note over User B: User B logs in later
    User B->>API: GET /api/friends
    API->>Database: SELECT * FROM Friendship WHERE receiver_id=? AND status='pending'
    Database-->>API: Pending request from User A
    API-->>User B: Show pending friend request
    
    User B->>API: POST /api/friends/accept/:friendship_id
    API->>Database: UPDATE Friendship SET status='accepted' WHERE id=?
    Database-->>API: Friendship accepted
    API-->>User B: {success: true}
    
    Note over User A, User B: Now they can see each other on leaderboard
```

## 6. Admin Badge Management Flow

```mermaid
sequenceDiagram
    actor Admin
    participant Browser
    participant AddBadgesView
    participant API
    participant JWT Middleware
    participant BadgeController
    participant Database

    Admin->>Browser: Navigate to /admin/add-badges
    Browser->>AddBadgesView: Mount component
    
    AddBadgesView->>API: GET /api/activity-types/categories
    API->>Database: SELECT DISTINCT category FROM ActivityType
    Database-->>API: Categories list
    API-->>AddBadgesView: {categories: [...]}
    
    AddBadgesView->>API: GET /api/badges
    API->>Database: SELECT * FROM Badge ORDER BY id ASC
    Database-->>API: All badges
    API-->>AddBadgesView: {success: true, badges: [...]}
    AddBadgesView->>Admin: Display badge management UI
    
    Admin->>AddBadgesView: Fill badge form (name, description, icon, category, threshold)
    Admin->>AddBadgesView: Click "Create Badge"
    
    AddBadgesView->>API: POST /api/badges {name, description, icon, category_rule, threshold_value, activity_type_ids}
    API->>JWT Middleware: Validate JWT token
    JWT Middleware->>JWT Middleware: Check if user.role === 'admin'
    JWT Middleware-->>API: Admin verified
    
    API->>BadgeController: createCustomBadge(data)
    BadgeController->>BadgeController: Validate required fields
    BadgeController->>Database: INSERT INTO Badge (name, description, icon, category_rule, activity_type_ids, threshold_value)
    Database-->>BadgeController: Badge created, badge_id
    BadgeController-->>API: {success: true, badge_id}
    API-->>AddBadgesView: Success response
    
    AddBadgesView->>AddBadgesView: Refresh badge list
    AddBadgesView->>Admin: Show success toast
    
    Note over BadgeController, Database: Badge is now available for all users to unlock
```

## 7. Goal Update & Projection Calculation Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant DashboardView
    participant API
    participant JWT Middleware
    participant GoalController
    participant Database

    User->>DashboardView: Click "Edit Goal"
    DashboardView->>User: Show goal settings modal
    
    User->>DashboardView: Set target_reduction=30%, duration_days=30
    User->>DashboardView: Click "Save Goal"
    
    DashboardView->>API: PUT /api/goal {target_reduction_percent: 30, duration_days: 30}
    API->>JWT Middleware: Validate JWT token
    JWT Middleware-->>API: User ID
    
    API->>GoalController: updateGoal(user_id, data)
    GoalController->>Database: SELECT * FROM User WHERE id=?
    Database-->>GoalController: Current user data
    
    GoalController->>GoalController: Calculate new goal_start_date = TODAY
    GoalController->>GoalController: Calculate goal_end_date = TODAY + duration_days
    
    GoalController->>Database: SELECT SUM(carbon_footprint)/COUNT(DISTINCT DATE(logged_on)) FROM ActivityLog WHERE user_id=? AND logged_on < goal_start_date LIMIT 30
    Database-->>GoalController: Historical daily average
    GoalController->>GoalController: Calculate baseline_footprint
    
    GoalController->>Database: UPDATE User SET target_reduction_percent=?, duration_days=?, goal_start_date=?, goal_end_date=?, baseline_footprint=? WHERE id=?
    Database-->>GoalController: Goal updated
    
    GoalController->>Database: SELECT SUM(carbon_footprint) FROM ActivityLog WHERE user_id=? AND logged_on >= goal_start_date
    Database-->>GoalController: current_total
    
    GoalController->>GoalController: calculateProjection(baseline, current, days_elapsed, total_days)
    GoalController->>GoalController: Calculate progress percentage
    GoalController->>GoalController: Determine if on_track
    
    GoalController-->>API: {success: true, goal: {...}, projection: {...}, progress: {...}}
    API-->>DashboardView: Updated goal data
    DashboardView->>User: Update progress bar & projection chart
```

## 8. Leaderboard Ranking Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant LeaderboardView
    participant API
    participant JWT Middleware
    participant SocialController
    participant Database

    User->>Browser: Navigate to /leaderboard
    Browser->>LeaderboardView: Mount component
    
    LeaderboardView->>API: GET /api/leaderboard?scope=global
    API->>JWT Middleware: Validate JWT token
    JWT Middleware-->>API: User ID
    
    API->>SocialController: getLeaderboard(scope='global', user_id)
    SocialController->>Database: SELECT user_id, SUM(carbon_footprint) as total FROM ActivityLog WHERE logged_on >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY user_id ORDER BY total ASC LIMIT 50
    Database-->>SocialController: Top 50 users by lowest footprint
    
    SocialController->>Database: SELECT id, name FROM User WHERE id IN (...)
    Database-->>SocialController: User details
    
    SocialController->>SocialController: Merge data & calculate ranks
    SocialController-->>API: {success: true, leaderboard: [...], userRank: 15}
    API-->>LeaderboardView: Leaderboard data
    LeaderboardView->>User: Display ranked list
    
    User->>LeaderboardView: Switch to "Friends" tab
    LeaderboardView->>API: GET /api/leaderboard?scope=friends
    API->>SocialController: getLeaderboard(scope='friends', user_id)
    
    SocialController->>Database: SELECT receiver_id FROM Friendship WHERE sender_id=? AND status='accepted' UNION SELECT sender_id FROM Friendship WHERE receiver_id=? AND status='accepted'
    Database-->>SocialController: Friend IDs
    
    SocialController->>Database: SELECT user_id, SUM(carbon_footprint) as total FROM ActivityLog WHERE user_id IN (...) AND logged_on >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY user_id ORDER BY total ASC
    Database-->>SocialController: Friends' footprints
    
    SocialController-->>API: {success: true, leaderboard: [...]}
    API-->>LeaderboardView: Friends leaderboard
    LeaderboardView->>User: Display friends ranking
```

## Notes

- All API requests include JWT token in Authorization header
- JWT Middleware validates token and extracts user_id for all protected routes
- EventBus enables real-time UI updates across components
- Database queries use parameterized statements to prevent SQL injection
- Timezone is set to Asia/Kuala_Lumpur (UTC+8) globally in PHP
- Badge unlocking happens automatically after each activity log
- Goal projections recalculate based on current pace vs baseline
```
