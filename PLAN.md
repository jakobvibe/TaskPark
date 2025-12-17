# TaskPark Implementation Plan

A stress-reduction task management web app organized by weekdays.

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │   Twig Templates + Stimulus.js + Turbo (Hotwire)      │  │
│  │                  + Tailwind CSS                        │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Symfony 7 Backend                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ Controllers │  │  Services   │  │ Security (OAuth2)   │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Entities   │  │   Repos     │  │ Doctrine ORM        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       MariaDB                               │
│         users, tasks, supportive_messages                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Tech Stack Details

### Backend
- **Framework:** Symfony 7.x (latest LTS)
- **PHP Version:** 8.2+
- **ORM:** Doctrine
- **Authentication:** KnpUOAuth2ClientBundle (Google SSO)

### Frontend
- **Templating:** Twig
- **Interactivity:** Symfony UX (Stimulus + Turbo/Hotwire)
- **Styling:** Tailwind CSS 3.x
- **Build Tool:** Webpack Encore

**Why Stimulus/Turbo over React/Vue?**
- Single codebase (no separate API needed)
- Perfect for this scope - CRUD operations with simple interactions
- Faster development, easier maintenance
- Still provides smooth SPA-like experience via Turbo
- Easy to scale later if needed

---

## 3. Database Schema

### Tables

#### `user`
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment |
| email | VARCHAR(180) | Unique, from OAuth |
| google_id | VARCHAR(255) | Google OAuth identifier |
| name | VARCHAR(255) | Display name |
| avatar_url | VARCHAR(500) | Profile picture URL |
| roles | JSON | User roles |
| created_at | DATETIME | Registration timestamp |
| last_login_at | DATETIME | Last login timestamp |

#### `task`
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment |
| user_id | INT (FK) | Owner of the task |
| title | VARCHAR(255) | Task description |
| scheduled_date | DATE | Which day the task is on |
| position | INT | Order within the day |
| status | ENUM | 'pending', 'completed' |
| completed_at | DATETIME | When marked complete |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last modification |

#### `supportive_message`
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment |
| message | TEXT | The supportive text |
| category | VARCHAR(50) | 'welcome', 'encouragement', 'completion' |
| is_active | BOOLEAN | Enable/disable message |

---

## 4. Core Features Breakdown

### 4.1 Authentication Flow
```
User clicks "Sign in with Google"
        │
        ▼
Redirect to Google OAuth consent screen
        │
        ▼
Google redirects back with auth code
        │
        ▼
Symfony exchanges code for user info
        │
        ▼
Create/update user in database
        │
        ▼
Redirect to dashboard with session
```

### 4.2 Week View
- Display Monday through Sunday
- Each day is a column/card
- Current day highlighted
- Navigation: ← Previous Week | Today | Next Week →
- URL structure: `/week/{year}/{week-number}` or `/week?date=2025-12-15`

### 4.3 Task Operations
| Action | Implementation |
|--------|----------------|
| **Add** | Modal or inline form, assign to specific day |
| **Complete** | Checkbox toggle, moves to "completed" section |
| **Move** | Drag-and-drop between days (Stimulus controller) |
| **Delete** | Confirm dialog, soft or hard delete |

### 4.4 Supportive Messages
- Welcome message on first visit of the day
- Random encouragement when completing tasks
- Calming messages when task list is overwhelming (>X tasks)
- Stored in database, easily editable

---

## 5. Project Structure

```
TaskPark/
├── assets/
│   ├── controllers/           # Stimulus controllers
│   │   ├── task_controller.js
│   │   ├── drag_controller.js
│   │   └── message_controller.js
│   ├── styles/
│   │   └── app.css           # Tailwind imports
│   └── app.js
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   ├── knpu_oauth2_client.yaml
│   │   └── webpack_encore.yaml
│   └── routes.yaml
├── migrations/
├── public/
├── src/
│   ├── Controller/
│   │   ├── DashboardController.php
│   │   ├── TaskController.php
│   │   ├── SecurityController.php
│   │   └── GoogleController.php
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Task.php
│   │   └── SupportiveMessage.php
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── TaskRepository.php
│   │   └── SupportiveMessageRepository.php
│   ├── Service/
│   │   ├── TaskService.php
│   │   └── MessageService.php
│   └── Security/
│       └── GoogleAuthenticator.php
├── templates/
│   ├── base.html.twig
│   ├── dashboard/
│   │   └── index.html.twig
│   ├── components/
│   │   ├── week_view.html.twig
│   │   ├── day_column.html.twig
│   │   ├── task_card.html.twig
│   │   └── supportive_message.html.twig
│   └── security/
│       └── login.html.twig
├── .env
├── composer.json
├── package.json
├── tailwind.config.js
├── webpack.config.js
└── docker-compose.yml        # For local MariaDB
```

---

## 6. API Endpoints / Routes

| Method | Route | Controller | Description |
|--------|-------|------------|-------------|
| GET | `/` | DashboardController::index | Redirect to current week |
| GET | `/week/{date}` | DashboardController::week | Show week view |
| GET | `/login` | SecurityController::login | Login page |
| GET | `/connect/google` | GoogleController::connect | Start OAuth |
| GET | `/connect/google/check` | GoogleController::check | OAuth callback |
| POST | `/logout` | SecurityController::logout | End session |
| POST | `/task` | TaskController::create | Create task (Turbo Frame) |
| PATCH | `/task/{id}` | TaskController::update | Update task |
| PATCH | `/task/{id}/complete` | TaskController::complete | Toggle complete |
| PATCH | `/task/{id}/move` | TaskController::move | Change date/position |
| DELETE | `/task/{id}` | TaskController::delete | Delete task |

---

## 7. UI/UX Design Concept

### Color Palette (Calming/Stress-reduction theme)
```css
/* Tailwind custom colors */
--primary: #6366f1    /* Indigo - calm, focused */
--success: #10b981    /* Green - completion, positivity */
--background: #f8fafc /* Light, airy */
--text: #334155       /* Soft dark, easy on eyes */
--accent: #f0abfc     /* Soft purple for highlights */
```

### Layout
```
┌──────────────────────────────────────────────────────────────────┐
│  🌿 TaskPark                              [User Avatar] [Logout] │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  "Take it one task at a time. You've got this! 💪"              │
│                                                                  │
│  ◀ Previous    Week of Dec 15-21, 2025    Next ▶   [Today]      │
│                                                                  │
│  ┌────────┬────────┬────────┬────────┬────────┬────────┬──────┐ │
│  │  Mon   │  Tue   │  Wed   │  Thu   │  Fri   │  Sat   │ Sun  │ │
│  │  15    │  16    │★ 17 ★ │  18    │  19    │  20    │  21  │ │
│  ├────────┼────────┼────────┼────────┼────────┼────────┼──────┤ │
│  │ ☐ Task │ ☑ Done │ ☐ Task │        │ ☐ Task │        │      │ │
│  │ ☐ Task │        │ ☐ Task │        │        │        │      │ │
│  │        │        │        │        │        │        │      │ │
│  │ [+ Add]│ [+ Add]│ [+ Add]│ [+ Add]│ [+ Add]│ [+ Add]│[+Add]│ │
│  └────────┴────────┴────────┴────────┴────────┴────────┴──────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 8. Implementation Phases

### Phase 1: Foundation (Core Setup)
- [ ] Initialize Symfony project
- [ ] Configure MariaDB + Doctrine
- [ ] Set up Webpack Encore + Tailwind
- [ ] Install Symfony UX (Stimulus, Turbo)
- [ ] Create base layout template
- [ ] Set up Docker Compose for local DB

### Phase 2: Authentication
- [ ] Install KnpUOAuth2ClientBundle
- [ ] Configure Google OAuth2 credentials
- [ ] Create User entity with Google fields
- [ ] Build GoogleAuthenticator
- [ ] Create login/logout pages
- [ ] Protect routes with security firewall

### Phase 3: Core Task Management
- [ ] Create Task entity + repository
- [ ] Build TaskService for business logic
- [ ] Create DashboardController with week view
- [ ] Build Twig templates for week display
- [ ] Implement CRUD operations for tasks
- [ ] Add Turbo Frames for seamless updates

### Phase 4: Interactivity
- [ ] Create Stimulus controller for task completion toggle
- [ ] Implement drag-and-drop for task moving
- [ ] Add inline task creation form
- [ ] Build task deletion with confirmation
- [ ] Week navigation (prev/next/today)

### Phase 5: Supportive Features
- [ ] Create SupportiveMessage entity
- [ ] Seed database with encouraging messages
- [ ] Display welcome message on dashboard
- [ ] Show random encouragement on task completion
- [ ] Add visual feedback for completed tasks

### Phase 6: Polish & Deployment Prep
- [ ] Responsive design for mobile
- [ ] Loading states and error handling
- [ ] Database migrations finalized
- [ ] Environment configuration for production
- [ ] Documentation (README update)

---

## 9. Google OAuth Setup Instructions

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project or select existing
3. Enable "Google+ API" or "Google Identity" API
4. Go to Credentials → Create Credentials → OAuth 2.0 Client ID
5. Application type: Web application
6. Authorized redirect URI: `http://localhost:8000/connect/google/check`
7. Copy Client ID and Client Secret to `.env`:
   ```
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   ```

---

## 10. Commands to Get Started

```bash
# Create Symfony project
composer create-project symfony/skeleton TaskPark
cd TaskPark

# Install required packages
composer require webapp
composer require knpuniversity/oauth2-client-bundle
composer require league/oauth2-google
composer require symfony/webpack-encore-bundle
composer require symfony/ux-turbo
composer require symfony/stimulus-bundle

# Frontend setup
npm install
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init

# Database
docker-compose up -d  # Start MariaDB
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Run development server
symfony server:start
npm run watch
```

---

## 11. Sample Supportive Messages

### Welcome Messages
- "Welcome back! Let's make today count, one small step at a time."
- "Good to see you! Remember: progress, not perfection."
- "Hey there! Your future self will thank you for showing up today."

### Encouragement (on task completion)
- "Nice work! Every completed task is a victory. 🎉"
- "You did it! Small wins lead to big changes."
- "Task complete! You're building momentum."

### Calming (when many tasks)
- "Feeling overwhelmed? Focus on just one task. The rest can wait."
- "Remember to breathe. You don't have to do everything today."
- "It's okay to move tasks to another day. Be kind to yourself."

---

## Questions Before Implementation

1. **Google OAuth:** Do you have a Google Cloud project set up, or should I include Docker-based local testing without SSO first?

2. **Task details:** Should tasks have any additional fields? (priority, notes, color labels, estimated time?)

3. **Week start:** Should weeks start on Monday (ISO) or Sunday?

4. **Completed tasks:** Hide them, show crossed out, or move to bottom of day?

5. **Data retention:** Keep completed tasks forever or auto-archive after X days?

---

Ready to start implementation when you approve this plan!
