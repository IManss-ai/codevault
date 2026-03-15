# CodeVault — Architecture

## System Overview

CodeVault is a monolithic PHP application using a front-controller pattern. All HTTP requests hit `index.php`, which parses the URL and routes to the correct page file. Apache's `.htaccess` rewrites clean URLs (no `.php` extensions) to the front controller.

```
Browser → Apache → .htaccess rewrite → index.php (router) → pages/*.php
                                                            → api/v1/*.php
```

## Folder Structure

```
codevault/
├── index.php              ← Front controller / router
├── .htaccess              ← URL rewriting rules
├── .env                   ← Secrets (never committed)
├── .env.example           ← Template for .env
├── .gitignore
├── README.md
├── CLAUDE.md              ← Project memory for Claude Code
├── requirements.md        ← Feature requirements
├── architecture.md        ← This file
│
├── config/
│   ├── env.php            ← Parses .env into $_ENV
│   └── database.php       ← Database class with PDO singleton
│
├── includes/
│   ├── header.php         ← HTML head, nav bar, opening <main>
│   ├── footer.php         ← Closing </main>, footer, scripts
│   ├── functions.php      ← Utility helpers (CSRF, sanitize, redirect)
│   └── auth.php           ← Register, login, logout logic
│
├── pages/
│   ├── home.php           ← Public landing page
│   ├── login.php          ← Login form + handler
│   ├── register.php       ← Registration form + handler
│   ├── dashboard.php      ← User's snippet library (auth required)
│   ├── new-snippet.php    ← Create snippet form (auth required)
│   ├── edit-snippet.php   ← Edit snippet form (auth required)
│   ├── snippet.php        ← Single public snippet view
│   ├── profile.php        ← Public profile at /u/{username}
│   ├── explore.php        ← Browse public snippets
│   └── settings.php       ← API keys, account settings (auth required)
│
├── api/
│   └── v1/
│       └── snippets.php   ← REST API endpoint
│
└── assets/
    ├── css/
    │   └── style.css      ← Complete design system
    └── js/
        └── main.js        ← Client-side interactions
```

## Request Lifecycle

1. Browser requests `/dashboard`
2. Apache `.htaccess` rewrites to `index.php?url=dashboard`
3. `index.php` starts session, loads `config/env.php` and `config/database.php`
4. Router parses the URL path and matches it to a route
5. For protected routes, `requireLogin()` checks the session
6. The matched page file is included (e.g., `pages/dashboard.php`)
7. Page file includes `header.php` at top and `footer.php` at bottom
8. Page queries the database via the `Database` class (PDO prepared statements)
9. HTML is rendered and sent to the browser

## URL Routing Table

| Method | URL Pattern        | Handler File             | Auth     |
|--------|--------------------|--------------------------|----------|
| GET    | `/`                | pages/home.php           | public   |
| GET    | `/register`        | pages/register.php       | public   |
| POST   | `/register`        | pages/register.php       | public   |
| GET    | `/login`           | pages/login.php          | public   |
| POST   | `/login`           | pages/login.php          | public   |
| GET    | `/logout`          | (inline in router)       | —        |
| GET    | `/dashboard`       | pages/dashboard.php      | required |
| GET    | `/new`             | pages/new-snippet.php    | required |
| POST   | `/new`             | pages/new-snippet.php    | required |
| GET    | `/edit/{id}`       | pages/edit-snippet.php   | required |
| POST   | `/edit/{id}`       | pages/edit-snippet.php   | required |
| GET    | `/snippet/{id}`    | pages/snippet.php        | public   |
| GET    | `/u/{username}`    | pages/profile.php        | public   |
| GET    | `/explore`         | pages/explore.php        | public   |
| GET    | `/settings`        | pages/settings.php       | required |
| POST   | `/settings`        | pages/settings.php       | required |
| *      | `/api/v1/snippets` | api/v1/snippets.php      | API key  |

## Database Schema (Supabase PostgreSQL)

### users
| Column        | Type         | Constraints              |
|---------------|--------------|--------------------------|
| id            | UUID         | PRIMARY KEY, DEFAULT gen |
| username      | VARCHAR(30)  | UNIQUE, NOT NULL         |
| email         | VARCHAR(255) | UNIQUE, NOT NULL         |
| password_hash | VARCHAR(255) | NOT NULL                 |
| bio           | TEXT         | NULLABLE                 |
| website       | VARCHAR(255) | NULLABLE                 |
| api_key       | VARCHAR(64)  | UNIQUE, NULLABLE         |
| created_at    | TIMESTAMP    | DEFAULT NOW()            |

### snippets
| Column      | Type         | Constraints                    |
|-------------|--------------|--------------------------------|
| id          | UUID         | PRIMARY KEY, DEFAULT gen       |
| user_id     | UUID         | FK → users(id), NOT NULL       |
| title       | VARCHAR(255) | NOT NULL                       |
| description | TEXT         | NULLABLE                       |
| code        | TEXT         | NOT NULL                       |
| language    | VARCHAR(50)  | NOT NULL                       |
| tags        | TEXT         | NULLABLE (comma-separated)     |
| is_public   | BOOLEAN      | DEFAULT false                  |
| view_count  | INTEGER      | DEFAULT 0                      |
| forked_from | UUID         | FK → snippets(id), NULLABLE    |
| created_at  | TIMESTAMP    | DEFAULT NOW()                  |
| updated_at  | TIMESTAMP    | DEFAULT NOW()                  |

### stars
| Column     | Type      | Constraints                          |
|------------|-----------|--------------------------------------|
| id         | UUID      | PRIMARY KEY, DEFAULT gen             |
| user_id    | UUID      | FK → users(id), NOT NULL             |
| snippet_id | UUID      | FK → snippets(id), NOT NULL          |
| created_at | TIMESTAMP | DEFAULT NOW()                        |
|            |           | UNIQUE(user_id, snippet_id)          |

## How Components Connect

- **index.php** is the single entry point. It loads config, starts sessions, and routes.
- **config/env.php** reads `.env` and populates `$_ENV`. Loaded first by the router.
- **config/database.php** provides a `Database::connect()` static method returning a PDO instance. Uses credentials from `$_ENV`.
- **includes/functions.php** provides stateless utility functions used everywhere.
- **includes/auth.php** provides `registerUser()`, `loginUser()`, and `logoutUser()` functions that interact with the database and session.
- **includes/header.php** outputs the `<head>`, opens `<body>`, renders the nav bar. Nav bar adapts based on login state.
- **includes/footer.php** closes `<main>`, renders the footer, loads JS.
- **pages/*.php** are the view+controller files. Each handles its own form processing (POST) and rendering (GET).
- **api/v1/snippets.php** is a standalone REST handler. It reads the API key from the `Authorization` header, validates it, and processes CRUD operations returning JSON.
- **assets/css/style.css** is the single CSS file containing the full design system.
- **assets/js/main.js** handles clipboard copy, search filtering, star toggling, and other client interactions.

## Security Architecture

- All form submissions include a CSRF token (stored in session, validated on POST)
- All database queries use PDO prepared statements — no string interpolation in SQL
- All rendered user data passes through `htmlspecialchars()`
- Passwords are hashed with `password_hash(BCRYPT)` and verified with `password_verify()`
- API keys are generated as random hex strings, hashed with SHA-256 before DB storage
- The raw API key is shown once to the user at generation time, never again
- Rate limiting checks a counter in the database keyed by hashed API key + hour window
- Session IDs are regenerated after login to prevent session fixation
- `.env` is in `.gitignore` and never committed
