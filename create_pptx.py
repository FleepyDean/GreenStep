from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN
from pptx.dml.color import RGBColor

def add_title_slide(prs, title_text, subtitle_text):
    slide = prs.slides.add_slide(prs.slide_layouts[0])
    title = slide.shapes.title
    subtitle = slide.placeholders[1]
    title.text = title_text
    subtitle.text = subtitle_text
    return slide

def add_content_slide(prs, title_text, content_items):
    slide = prs.slides.add_slide(prs.slide_layouts[1])
    title = slide.shapes.title
    title.text = title_text
    
    body = slide.placeholders[1]
    tf = body.text_frame
    tf.clear()
    
    for item in content_items:
        p = tf.add_paragraph()
        p.text = item
        p.level = 0
        p.font.size = Pt(14)
    
    return slide

def add_two_column_slide(prs, title_text, left_items, right_items):
    slide = prs.slides.add_slide(prs.slide_layouts[1])
    title = slide.shapes.title
    title.text = title_text
    
    left_box = slide.shapes.add_textbox(Inches(0.5), Inches(1.5), Inches(4.5), Inches(5))
    tf_left = left_box.text_frame
    for item in left_items:
        p = tf_left.add_paragraph()
        p.text = item
        p.font.size = Pt(12)
    
    right_box = slide.shapes.add_textbox(Inches(5.2), Inches(1.5), Inches(4.5), Inches(5))
    tf_right = right_box.text_frame
    for item in right_items:
        p = tf_right.add_paragraph()
        p.text = item
        p.font.size = Pt(12)
    
    return slide

print("Creating GreenStep presentation...")
prs = Presentation()
prs.slide_width = Inches(10)
prs.slide_height = Inches(7.5)

# Slide 1: Title
add_title_slide(prs, "GreenStep", "Carbon Footprint & Eco-Lifestyle Tracker\nSCSM2223 Cross-Platform Application Development\nGroup Project Final Presentation")

# Slide 2: Team Profile
add_content_slide(prs, "Team Profile and Roles", [
    "Team Member: Danish (Developer)",
    "Role: Full-Stack Developer",
    "Contributions:",
    "  • Frontend development (Vue 3, Vite, Pinia)",
    "  • Backend API development (PHP Slim 4, PDO)",
    "  • Database design and implementation (MySQL)",
    "  • DevOps and deployment (Railway, Git/GitHub)",
    "  • UI/UX design and implementation",
    "  • Testing and debugging"
])

# Slide 3: Project Overview
add_content_slide(prs, "Project Overview", [
    "Problem Statement:",
    "  Climate change is urgent; individuals account for large share of avoidable emissions",
    "",
    "Solution: GreenStep",
    "  • Daily carbon footprint tracker with automatic calculation",
    "  • Gamification through badges and challenges",
    "  • Social features: friends, leaderboard, challenges",
    "  • Personalized carbon reduction goals with projections",
    "  • Admin tools for managing emission factors, badges, tips"
])

# Slide 4: Target Users & Objectives
add_content_slide(prs, "Target Users & Objectives", [
    "Target Users:",
    "  • Environmentally conscious individuals",
    "  • Students and young professionals",
    "  • Community groups and organizations",
    "",
    "Objectives:",
    "  • Track daily carbon footprint across transport, diet, energy, recycling",
    "  • Provide actionable eco tips",
    "  • Motivate through gamification (badges, challenges, leaderboard)",
    "  • Enable social accountability through friend connections",
    "  • Help users set and achieve carbon reduction goals"
])

# Slide 5: Main Features
add_two_column_slide(prs, "Main Features & Functional Scope", [
    "User Features:",
    "• Activity logging with photo upload",
    "• Real-time dashboard with charts",
    "• Carbon reduction goal tracking",
    "• Badge unlocking system",
    "• Eco challenges (join/leave/track)",
    "• Daily eco tips",
    "• Friends & social connections",
    "• Global/friends leaderboard"
], [
    "Admin Features:",
    "• Platform statistics dashboard",
    "• Badge management (CRUD)",
    "• Emission factor management",
    "• Challenge management (CRUD)",
    "• Tips management (CRUD)",
    "• User dataset overview"
])

# Slide 6: Technology Stack
add_content_slide(prs, "Technology Stack", [
    "Frontend: Vue 3 + Vue Router + Pinia + Vite",
    "  • Dynamic, mobile-first SPA with reactive state management",
    "",
    "Backend: PHP Slim 4 + PDO",
    "  • RESTful API with business logic and middleware",
    "",
    "Database: MySQL",
    "  • Relational database with 9 tables and foreign key constraints",
    "",
    "Security: JWT (firebase/php-jwt) + Bcrypt",
    "  • Token-based authentication with password hashing",
    "",
    "Deployment: Railway (Nixpacks)",
    "  • Frontend, Backend, and MySQL hosted on Railway"
])

# Slide 7: DevOps - Version Control
add_content_slide(prs, "DevOps: Version Control & Collaboration", [
    "GitHub Repository:",
    "  • Repository: FleepyDean/GreenStep",
    "  • Main branch for production code",
    "",
    "Branching Strategy:",
    "  • Main branch for stable releases",
    "  • Feature branches for new features",
    "  • Direct commits for hotfixes",
    "",
    "Collaboration:",
    "  • Commit history with descriptive messages",
    "  • Code organized in modular structure",
    "  • .gitignore for sensitive data (.env files)"
])

# Slide 8: DevOps - CI/CD & Deployment
add_content_slide(prs, "DevOps: CI/CD & Deployment", [
    "Continuous Deployment:",
    "  • Railway auto-deploys on Git push to main branch",
    "  • Separate services: Frontend, Backend, MySQL",
    "",
    "Build Process:",
    "  • Frontend: Node 20, npm install, npm run build, serve dist/",
    "  • Backend: PHP 8.3, Composer install, php -S 0.0.0.0:$PORT",
    "",
    "Environment Variables:",
    "  • Frontend: VITE_API_URL",
    "  • Backend: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, JWT_SECRET, FRONTEND_URL",
    "",
    "Deployment URLs:",
    "  • Frontend: https://greenstep.up.railway.app",
    "  • Backend: https://greenstep-backend-production.up.railway.app/api"
])

# Slide 9: System Architecture Diagram
add_content_slide(prs, "System Architecture", [
    "Client Layer (Browser):",
    "  • Vue 3 SPA with Vite build tool",
    "  • Axios HTTP client with JWT interceptors",
    "",
    "Server Layer (Railway):",
    "  • PHP Slim 4 REST API",
    "  • JWT middleware for authentication",
    "  • CORS middleware for cross-origin requests",
    "",
    "Database Layer (Railway MySQL):",
    "  • MySQL 8.0 with 9 relational tables",
    "",
    "External Services:",
    "  • None (self-contained system)",
    "",
    "Deployment:",
    "  • Railway hosting platform (Frontend + Backend + MySQL)"
])

# Slide 10: Sequence Diagram - Activity Logging
add_content_slide(prs, "Sequence Diagram: Activity Logging Process", [
    "1. User selects category and activity type",
    "2. User enters amount, date, and optional photo",
    "3. Frontend sends POST /api/activities with JWT token",
    "4. Backend validates JWT token (JwtMiddleware)",
    "5. Backend calculates carbon footprint (amount × emission factor)",
    "6. Backend inserts activity into ActivityLog table",
    "7. Backend checks and awards badges (BadgeController)",
    "8. Backend returns success response with activity data",
    "9. Frontend emits 'activity-logged' event via eventBus",
    "10. Dashboard listens and refreshes goal + metrics",
    "11. User sees updated dashboard with new activity"
])

# Slide 11: Activity Diagram - User Workflow
add_content_slide(prs, "Activity Diagram: User Workflow", [
    "Start → User opens app",
    "  ↓",
    "Decision: Authenticated?",
    "  No → Redirect to /profile (login/register)",
    "  Yes → Show dashboard",
    "  ↓",
    "User navigates to /activity",
    "  ↓",
    "User logs activity (category, type, amount, date, photo)",
    "  ↓",
    "System calculates carbon footprint",
    "  ↓",
    "System checks and awards badges",
    "  ↓",
    "User navigates to /dashboard",
    "  ↓",
    "System displays updated metrics, charts, goal progress",
    "  ↓",
    "End"
])

# Slide 12: Deployment Diagram
add_content_slide(prs, "Deployment Diagram", [
    "Client Device (Browser):",
    "  • Vue 3 SPA served as static files",
    "  • HTTPS connection to Railway frontend",
    "",
    "Railway Frontend Server:",
    "  • Nginx/static file server",
    "  • Serves index.html + JS/CSS bundles",
    "",
    "Railway Backend Server:",
    "  • PHP 8.3 built-in server",
    "  • REST API endpoints",
    "  • JWT authentication middleware",
    "",
    "Railway MySQL Database:",
    "  • MySQL 8.0 cloud database",
    "  • 9 tables with foreign key constraints",
    "",
    "Network: HTTPS/TLS for all connections"
])

# Slide 13: Database Schema
add_content_slide(prs, "Database and Data Management", [
    "Database: MySQL 8.0",
    "",
    "Main Tables:",
    "  • User (id, name, email, password, role, goal settings)",
    "  • ActivityType (id, name, category, emission_factor)",
    "  • ActivityLog (id, user_id, activity_type_id, amount, carbon_footprint, date, photo_url)",
    "  • Badge (id, name, description, icon, category_rule, threshold_value)",
    "  • UserBadge (user_id, badge_id, earned_at)",
    "  • Challenge (id, title, description, target_type, target_value, start_date, end_date)",
    "  • ChallengeParticipant (id, challenge_id, user_id, progress)",
    "  • Friendship (id, sender_id, receiver_id, status)",
    "  • Tip (id, title, content, category)",
    "",
    "Security: Bcrypt password hashing, JWT tokens, parameterized queries"
])

# Slide 14: Data Validation & Security
add_content_slide(prs, "Data Validation and Security", [
    "Input Validation:",
    "  • Required field checks on frontend and backend",
    "  • Type validation (integers, floats, dates)",
    "  • Email format validation",
    "",
    "Security Measures:",
    "  • Password hashing with Bcrypt (cost factor 10)",
    "  • JWT token-based authentication (24-hour expiry)",
    "  • Parameterized SQL queries to prevent injection",
    "  • CORS policy restricts API access to frontend domain",
    "  • Role-based access control (user vs admin)",
    "",
    "Data Integrity:",
    "  • Foreign key constraints in database",
    "  • Unique constraints on email addresses",
    "  • NOT NULL constraints on required fields"
])

# Slide 15: Demo Data
add_content_slide(prs, "Demonstration Data", [
    "Seed Data Included:",
    "  • 17+ activity types with realistic emission factors",
    "  • 10 eco tips across all categories",
    "  • 4 challenges with date ranges and targets",
    "  • 6+ badges (streak-based and category-based)",
    "  • 1 admin user (admin@greenstep.my / admin123)",
    "",
    "Realistic Demo Scenario:",
    "  • User registers and logs activities",
    "  • System calculates carbon footprint automatically",
    "  • User sets carbon reduction goal (e.g., 30% in 30 days)",
    "  • User earns badges based on activity milestones",
    "  • User joins challenges and tracks progress",
    "  • User adds friends and compares on leaderboard",
    "  • Admin manages emission factors, badges, challenges, tips"
])

# Slide 16: System Demonstration - User Flow
add_content_slide(prs, "System Demonstration: User Flow", [
    "1. Authentication:",
    "   • Register new account or login",
    "   • JWT token stored in localStorage",
    "",
    "2. Dashboard:",
    "   • View today's footprint, weekly/monthly trends",
    "   • See category breakdown doughnut chart",
    "   • Track carbon reduction goal progress",
    "",
    "3. Activity Logging:",
    "   • Select category (Transport, Diet, Energy, Recycling)",
    "   • Choose activity type from dropdown",
    "   • Enter amount and date",
    "   • Upload optional photo",
    "   • Submit and see carbon footprint calculated",
    "",
    "4. Gamification:",
    "   • Earn badges automatically",
    "   • Join challenges and track progress",
    "   • View leaderboard rankings"
])

# Slide 17: System Demonstration - Admin Flow
add_content_slide(prs, "System Demonstration: Admin Flow", [
    "1. Admin Dashboard:",
    "   • View platform statistics (total users, activities, emissions)",
    "   • See user dataset overview",
    "",
    "2. Emission Factor Management:",
    "   • Add/edit/delete activity types",
    "   • Create custom categories",
    "   • Set emission factors (kg CO₂ per unit)",
    "",
    "3. Badge Management:",
    "   • Create custom badges with category rules",
    "   • Set threshold values",
    "   • Delete badges",
    "",
    "4. Challenge Management:",
    "   • Create challenges with targets and date ranges",
    "   • Update challenge details",
    "   • Delete challenges",
    "",
    "5. Tips Management:",
    "   • Add eco tips with categories",
    "   • Delete tips"
])

# Slide 18: Key Features Demonstrated
add_content_slide(prs, "Key Features Demonstrated", [
    "✓ User authentication with JWT",
    "✓ Activity logging with automatic carbon calculation",
    "✓ Real-time dashboard with interactive charts",
    "✓ Carbon reduction goal with progress tracking",
    "✓ Badge unlocking based on activity milestones",
    "✓ Challenge join/leave with progress tracking",
    "✓ Friend connections and leaderboard",
    "✓ Admin CRUD operations for all entities",
    "✓ Photo upload for activities",
    "✓ Responsive mobile-first design",
    "✓ Professional UI with SVG icons",
    "✓ Event-driven architecture (eventBus)",
    "✓ Error handling and validation",
    "✓ Toast notifications for user feedback"
])

# Slide 19: Technical Achievements
add_content_slide(prs, "Technical Achievements & Best Practices", [
    "Software Engineering Practices:",
    "  • Modular MVC architecture (Models, Controllers, Views)",
    "  • RESTful API design with proper HTTP methods",
    "  • Separation of concerns (frontend/backend/database)",
    "  • Reusable components and services",
    "",
    "DevOps Practices:",
    "  • Git version control with descriptive commits",
    "  • Continuous deployment with Railway",
    "  • Environment variable management",
    "  • Timezone handling for global deployment",
    "",
    "Code Quality:",
    "  • Consistent naming conventions",
    "  • Proper error handling and logging",
    "  • SQL query optimization",
    "  • Frontend state management with Pinia"
])

# Slide 20: Conclusion & Live Demo
add_title_slide(prs, "Thank You!", "Live Demonstration\n\nFrontend: https://greenstep.up.railway.app\nBackend API: https://greenstep-backend-production.up.railway.app/api\n\nGitHub: FleepyDean/GreenStep\n\nQuestions?")

prs.save("GreenStep_Presentation.pptx")
print("✓ Presentation created successfully: GreenStep_Presentation.pptx")
print("✓ Total slides: 20")
