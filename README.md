# CMC Project

A full-stack web application built with **Laravel** backend and **Nuxt 3** frontend.

## 📋 Table of Contents

- [Overview](#overview)
- [Project Structure](#project-structure)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Project](#running-the-project)
- [Project Structure Details](#project-structure-details)
- [Configuration](#configuration)
- [Development](#development)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

CMC Project is a modern, full-stack web application that combines:
- **Laravel** - A powerful PHP web application framework for the backend
- **Nuxt 3** - A Vue-based meta-framework for building modern frontend applications

This architecture provides a robust REST API backend with a responsive, interactive frontend.

## 📁 Project Structure

```
CMC-project/
├── backend/          # Laravel PHP application
│   ├── app/          # Application code
│   ├── routes/       # API routes
│   ├── controllers/  # Route controllers
│   ├── models/       # Database models
│   └── ...
├── frontend/         # Nuxt 3 application
│   ├── pages/        # Application pages
│   ├── components/   # Vue components
│   ├── composables/  # Composition functions
│   └── ...
└── README.md         # This file
```

## 🛠️ Tech Stack

### Backend
- **Laravel** - Web application framework
- **PHP** - Server-side language
- **Database** - Configurable (MySQL, PostgreSQL, SQLite, etc.)
- **RESTful API** - For frontend communication

### Frontend
- **Nuxt 3** - Vue meta-framework
- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next generation frontend tooling

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

### Backend Requirements
- **PHP** >= 8.1
- **Composer** - PHP package manager
- **Node.js** >= 16 (for asset compilation)
- **Database** - MySQL, PostgreSQL, SQLite, or similar

### Frontend Requirements
- **Node.js** >= 16
- **npm** or **pnpm** or **yarn** or **bun**

## 🚀 Installation

### Backend Setup

1. **Navigate to the backend directory:**
   ```bash
   cd backend
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Create environment file:**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Configure your database** in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cmc_project
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations:**
   ```bash
   php artisan migrate
   ```

7. **Seed the database (optional):**
   ```bash
   php artisan db:seed
   ```

### Frontend Setup

1. **Navigate to the frontend directory:**
   ```bash
   cd frontend
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```
   Or use your preferred package manager:
   - `pnpm install`
   - `yarn install`
   - `bun install`

3. **Create environment file:**
   ```bash
   cp .env.example .env.local
   ```

4. **Configure API endpoint** in `.env.local`:
   ```
   NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api
   ```

## 🎮 Running the Project

### Development Environment

#### Backend (Terminal 1)

```bash
cd backend
php artisan serve
```

The Laravel development server will run at: `http://localhost:8000`

#### Frontend (Terminal 2)

```bash
cd frontend
npm run dev
```

The Nuxt development server will run at: `http://localhost:3000`

### Production Build

#### Backend
```bash
cd backend
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Frontend
```bash
cd frontend
npm run build
npm run preview
```

## 📖 Project Structure Details

### Backend Structure

The backend follows Laravel's MVC architecture:

- **app/** - Application source code
  - **Http/Controllers/** - Request handlers
  - **Models/** - Eloquent models (database entities)
  - **Requests/** - Form request validation
  - **Resources/** - API response resources
- **routes/** - Define all application routes
- **database/** - Migrations and seeders
- **config/** - Application configuration files
- **storage/** - File storage (logs, cache, uploads)
- **tests/** - Unit and feature tests

### Frontend Structure

The frontend follows Nuxt 3 conventions:

- **pages/** - File-based routing (auto-creates routes)
- **components/** - Reusable Vue components
- **composables/** - Reusable composition functions
- **layouts/** - Page layouts
- **middleware/** - Route middleware
- **plugins/** - Global plugins and configurations
- **assets/** - Static assets (images, stylesheets)
- **public/** - Publicly served static files

## ⚙️ Configuration

### Backend Configuration

Key configuration files in `backend/config/`:
- `app.php` - Application settings
- `database.php` - Database connection settings
- `cors.php` - CORS settings for frontend access
- `auth.php` - Authentication configuration

### Frontend Configuration

Key configuration files in `frontend/`:
- `nuxt.config.ts` - Nuxt configuration
- `.env.local` - Environment variables
- `tsconfig.json` - TypeScript configuration (if using TS)

### API Communication

The frontend communicates with the backend via HTTP REST API. Ensure:

1. **CORS is properly configured** on the Laravel backend
2. **API endpoint** is correctly set in frontend environment variables
3. **Authentication tokens** are properly handled (if using authentication)

## 👨‍💻 Development

### Backend Development

- Create new routes in `backend/routes/api.php`
- Create controllers with: `php artisan make:controller YourController`
- Create models with: `php artisan make:model YourModel -m` (with migration)
- Run tests with: `php artisan test`

### Frontend Development

- Create pages in `frontend/pages/` directory
- Create components in `frontend/components/` directory
- Use composables for shared logic in `frontend/composables/`
- Hot module replacement works automatically in development

### Code Standards

- Follow PSR-12 coding standards for PHP
- Use Vue 3 Composition API for components
- Keep components small and focused
- Write tests for critical functionality

## 🚢 Deployment

### Backend Deployment

1. **Set environment to production:**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Install production dependencies:**
   ```bash
   composer install --no-dev
   ```

3. **Run migrations on production:**
   ```bash
   php artisan migrate --force
   ```

4. **Cache configuration:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Frontend Deployment

1. **Build for production:**
   ```bash
   npm run build
   ```

2. **Deploy the `.output/public` directory** to your hosting

3. **Configure server rewrites** to serve `index.html` for all routes

## 🤝 Contributing

1. Create a new branch: `git checkout -b feature/your-feature-name`
2. Make your changes
3. Commit: `git commit -m 'Add your feature'`
4. Push: `git push origin feature/your-feature-name`
5. Open a pull request

## 📄 License

This project is open-source software. Please check the LICENSE file for details.

---

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Nuxt 3 Documentation](https://nuxt.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [REST API Best Practices](https://restfulapi.net/)

## ❓ Troubleshooting

### Backend Issues
- **Port 8000 already in use:** Change with `php artisan serve --port=8001`
- **Database connection error:** Check `.env` database credentials
- **Missing migrations:** Run `php artisan migrate:fresh` (⚠️ resets database)

### Frontend Issues
- **Port 3000 already in use:** Nuxt will automatically use the next available port
- **API connection errors:** Check `NUXT_PUBLIC_API_BASE_URL` in `.env.local`
- **Dependencies issues:** Delete `node_modules` and `package-lock.json`, then run `npm install` again

---

For more information or support, please refer to the official documentation or open an issue in the repository.
