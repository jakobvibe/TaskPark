# TaskPark

A stress-reduction task management web app organized by weekdays. Built with Symfony 7, Tailwind CSS, and Turbo/Stimulus.

## Features

- **Week-based task view** - Organize tasks by day, navigate between weeks
- **Magic Link authentication** - Passwordless login via email
- **Multi-user support** - Each user has their own tasks
- **Supportive messaging** - Contextual encouragement based on task load
- **Real-time updates** - Turbo Frames for seamless interactions
- **Stress-reduction theme** - Calming design and messaging

## Tech Stack

- **Backend:** Symfony 7.x (PHP 8.2+)
- **Database:** MariaDB 11
- **Frontend:** Twig + Stimulus + Turbo (Hotwire)
- **Styling:** Tailwind CSS 4
- **Email:** Symfony Mailer (Mailpit for local dev)

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Docker & Docker Compose

### Setup

1. **Clone and install dependencies:**
   ```bash
   cd TaskPark
   composer install
   ```

2. **Start Docker services (MariaDB + Mailpit):**
   ```bash
   docker-compose up -d
   ```

3. **Create database and run migrations:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Build Tailwind CSS:**
   ```bash
   php bin/console tailwind:build
   ```

5. **Start the development server:**
   ```bash
   symfony server:start
   # Or: php -S localhost:8000 -t public
   ```

6. **Access the app:**
   - App: http://localhost:8000
   - Mailpit (view emails): http://localhost:8025

## Usage

1. Go to http://localhost:8000
2. Enter your email address
3. Check Mailpit at http://localhost:8025 for the magic link
4. Click the link to log in
5. Add tasks to any day of the week
6. Check off tasks when complete
7. Navigate between weeks with Previous/Next buttons

## Project Structure

```
TaskPark/
├── src/
│   ├── Controller/        # HTTP request handlers
│   ├── Entity/            # Doctrine entities
│   ├── Repository/        # Database queries
│   ├── Service/           # Business logic
│   └── Security/          # Authentication
├── templates/             # Twig templates
├── assets/
│   ├── controllers/       # Stimulus controllers
│   └── styles/           # Tailwind CSS
├── migrations/           # Database migrations
└── docker-compose.yml    # Local services
```

## Environment Variables

Key variables in `.env`:

```env
DATABASE_URL="mysql://taskpark:taskpark@127.0.0.1:3306/taskpark"
MAILER_DSN=smtp://localhost:1025
APP_URL=http://localhost:8000
MAGIC_LINK_EXPIRY_MINUTES=15
```

## Development

### Watch mode for Tailwind:
```bash
php bin/console tailwind:build --watch
```

### Clear cache:
```bash
php bin/console cache:clear
```

## License

MIT
