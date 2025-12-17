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
│  │ Controllers │  │  Services   │  │ Security (MagicLink) │  │
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
- **Authentication:** Magic Link (passwordless email login)
- **Email:** Symfony Mailer (with Mailpit for local dev)

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
| email | VARCHAR(180) | Unique, user's email |
| name | VARCHAR(255) | Display name (from email or user-set) |
| roles | JSON | User roles |
| is_verified | BOOLEAN | Email verified via magic link |
| created_at | DATETIME | Registration timestamp |
| last_login_at | DATETIME | Last login timestamp |

#### `login_token`
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment |
| user_id | INT (FK) | Associated user |
| token | VARCHAR(64) | Secure random token (hashed) |
| expires_at | DATETIME | Token expiration (15 min) |
| used_at | DATETIME | When token was used (null if unused) |
| created_at | DATETIME | Creation timestamp |

#### `task`
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment |
| user_id | INT (FK) | Owner of the task |
| title | VARCHAR(255) | Task description |
| scheduled_date | DATE | Which day the task is on |
| position | INT | Order within the day (for drag-drop) |
| is_completed | BOOLEAN | Completion status |
| completed_at | DATETIME | When marked complete (for 30-day archive) |
| created_at | DATETIME | Creation timestamp |

#### `archived_task`
Same structure as `task` - stores tasks completed > 30 days ago.

#### `supportive_message`
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment |
| message | TEXT | The supportive text |
| category | VARCHAR(50) | 'welcome', 'encouragement', 'completion' |
| is_active | BOOLEAN | Enable/disable message |

---

## 4. Core Features Breakdown

### 4.1 Authentication Flow (Magic Link)
```
User enters email on login page
        │
        ▼
System creates/finds user + generates token
        │
        ▼
Email sent with magic link: /login/verify/{token}
        │
        ▼
User clicks link in email
        │
        ▼
System validates token (not expired, not used)
        │
        ▼
Mark token as used, log user in
        │
        ▼
Redirect to dashboard with session
```

**Security features:**
- Tokens expire after 15 minutes
- Tokens are single-use (marked used after login)
- Token stored as hash in DB (like passwords)
- Rate limiting on login requests

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
│   │   ├── mailer.yaml
│   │   └── webpack_encore.yaml
│   └── routes.yaml
├── migrations/
├── public/
├── src/
│   ├── Controller/
│   │   ├── DashboardController.php
│   │   ├── TaskController.php
│   │   └── SecurityController.php
│   ├── Entity/
│   │   ├── User.php
│   │   ├── LoginToken.php
│   │   ├── Task.php
│   │   └── SupportiveMessage.php
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── LoginTokenRepository.php
│   │   ├── TaskRepository.php
│   │   └── SupportiveMessageRepository.php
│   ├── Service/
│   │   ├── MagicLinkService.php
│   │   ├── TaskService.php
│   │   └── MessageService.php
│   └── Security/
│       └── MagicLinkAuthenticator.php
├── templates/
│   ├── base.html.twig
│   ├── dashboard/
│   │   └── index.html.twig
│   ├── components/
│   │   ├── week_view.html.twig
│   │   ├── day_column.html.twig
│   │   ├── task_card.html.twig
│   │   └── supportive_message.html.twig
│   ├── security/
│   │   ├── login.html.twig
│   │   └── check_email.html.twig
│   └── emails/
│       └── magic_link.html.twig
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
| GET | `/login` | SecurityController::login | Login page (enter email) |
| POST | `/login` | SecurityController::sendMagicLink | Send magic link email |
| GET | `/login/check-email` | SecurityController::checkEmail | "Check your email" page |
| GET | `/login/verify/{token}` | SecurityController::verify | Verify token & login |
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
- [ ] Set up Docker Compose for local DB + Mailpit

### Phase 2: Authentication (Magic Link)
- [ ] Install Symfony Mailer
- [ ] Create User + LoginToken entities
- [ ] Build MagicLinkService (token generation, validation)
- [ ] Build MagicLinkAuthenticator
- [ ] Create login page (email input form)
- [ ] Create "check your email" page
- [ ] Create magic link email template
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

## 9. Magic Link Email Setup

### Local Development (Mailpit)
Mailpit is included in docker-compose.yml - catches all emails locally.
- Web UI: http://localhost:8025
- SMTP: localhost:1025

### Production Options
1. **SMTP Provider** (Mailgun, SendGrid, Amazon SES)
2. **Native PHP mail** (not recommended)

### Environment Variables
```env
# Local development (Mailpit)
MAILER_DSN=smtp://localhost:1025

# Production example (Mailgun)
MAILER_DSN=mailgun+smtp://USERNAME:PASSWORD@default?region=eu

# App settings
APP_URL=http://localhost:8000
MAGIC_LINK_EXPIRY_MINUTES=15
```

---

## 10. Commands to Get Started

```bash
# Create Symfony project
composer create-project symfony/skeleton TaskPark
cd TaskPark

# Install required packages
composer require webapp
composer require symfony/mailer
composer require symfony/webpack-encore-bundle
composer require symfony/ux-turbo
composer require symfony/stimulus-bundle

# Frontend setup
npm install
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init

# Database + Email (via Docker)
docker-compose up -d  # Start MariaDB + Mailpit
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Run development server
symfony server:start
npm run watch

# View emails at http://localhost:8025 (Mailpit)
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

## Design Decisions (Confirmed)

| Decision | Choice |
|----------|--------|
| **Authentication** | Magic Link (passwordless email) |
| **Task fields** | Title only (for now) |
| **Week start** | Monday (ISO standard) |
| **Completed tasks** | Show with strikethrough |
| **Data retention** | Auto-archive after 30 days |

---

## Auto-Archive Implementation

A Symfony console command will run daily to archive old completed tasks:

```php
// src/Command/ArchiveCompletedTasksCommand.php
#[AsCommand(name: 'app:archive-tasks')]
class ArchiveCompletedTasksCommand extends Command
{
    // Archives tasks completed > 30 days ago
    // Can be run via cron: 0 2 * * * php bin/console app:archive-tasks
}
```

Archived tasks move to an `archived_task` table (same structure) for potential recovery.

---

**Plan finalized! Ready to start implementation.**
