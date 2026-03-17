# CLAUDE.md — CodeVault Project Memory

## What is CodeVault?
A code snippet manager and knowledge base for developers. Users save, organize, search, and share code snippets. Think "personal searchable library of every useful piece of code you've ever written."

## Tech Stack
- **Backend:** PHP 8.2 (no frameworks)
- **Database:** Supabase (PostgreSQL) via PDO — credentials in `.env`
- **Frontend:** HTML5, CSS3, vanilla JavaScript only
- **Syntax Highlighting:** Prism.js (CDN)
- **Fonts:** Inter (UI), JetBrains Mono (code) — Google Fonts
- **Server:** Apache2 on WSL2 Ubuntu (local dev)
- **Deployment:** Railway
- **Version Control:** Git + GitHub (IManss-ai/codevault)

## Project Location
- Local: `/var/www/html/codevault/`
- GitHub: `https://github.com/IManss-ai/codevault`

## Design System
| Token             | Value     |
|-------------------|-----------|
| Background        | `#0d1117` |
| Card background   | `#161b22` |
| Border            | `#30363d` |
| Primary (blue)    | `#58a6ff` |
| Success (green)   | `#3fb950` |
| Danger (red)      | `#f85149` |
| Text primary      | `#e6edf3` |
| Text secondary    | `#8b949e` |
| UI font           | Inter     |
| Code font         | JetBrains Mono |

## Security Rules (non-negotiable)
- Passwords: `password_hash()` with BCRYPT
- Queries: PDO prepared statements only
- Output: `htmlspecialchars()` on all user data
- CSRF tokens on every form
- API keys hashed before DB storage
- Rate limit API: 100 req/hour/key
- Server-side validation on every field
- Session ID regenerated after login
- All secrets in `.env` — never hardcode
- Never expose DB errors to users

## Database Schema (Supabase)
### users
- id (UUID PK), username (VARCHAR 30 UNIQUE), email (VARCHAR 255 UNIQUE)
- password_hash (VARCHAR 255), bio (TEXT), website (VARCHAR 255)
- api_key (VARCHAR 64 UNIQUE), created_at (TIMESTAMP)

### snippets
- id (UUID PK), user_id (UUID FK→users), title (VARCHAR 255)
- description (TEXT), code (TEXT), language (VARCHAR 50)
- tags (TEXT comma-separated), is_public (BOOLEAN), view_count (INTEGER)
- forked_from (UUID self-ref FK), created_at (TIMESTAMP), updated_at (TIMESTAMP)

### stars
- id (UUID PK), user_id (UUID FK→users), snippet_id (UUID FK→snippets)
- created_at (TIMESTAMP), UNIQUE(user_id, snippet_id)

## URL Routing
| URL                | File                    | Auth     |
|--------------------|-------------------------|----------|
| `/`                | pages/home.php          | public   |
| `/register`        | pages/register.php      | public   |
| `/login`           | pages/login.php         | public   |
| `/logout`          | destroy session → `/`   | —        |
| `/dashboard`       | pages/dashboard.php     | required |
| `/new`             | pages/new-snippet.php   | required |
| `/edit/{id}`       | pages/edit-snippet.php  | required |
| `/snippet/{id}`    | pages/snippet.php       | public   |
| `/u/{username}`    | pages/profile.php       | public   |
| `/explore`         | pages/explore.php       | public   |
| `/settings`        | pages/settings.php      | required |
| `/api/v1/snippets` | api/v1/snippets.php     | API key  |

## Current Status
- [x] Planning documents (CLAUDE.md, requirements.md, architecture.md)
- [x] Core infrastructure (router, .htaccess, env loader, DB class, helpers, auth)
- [x] Design system (CSS, header, footer)
- [x] Authentication pages (register, login)
- [x] Landing page (home.php)
- [x] Dashboard (dashboard.php) — with pagination
- [x] Snippet CRUD (new, edit, single view) — bug-fixed
- [x] Explore page — with pagination
- [x] Public profiles — with pagination, fixed tag cap
- [x] Settings page — profile, password, API key, export JSON; Settings link added to navbar
- [x] REST API — ownership + UUID fixes applied
- [x] Embeddable widget — ?embed=1 on /snippet/{id}, standalone HTML with inline CSS, X-Frame-Options: ALLOWALL
- [x] Star system — UUID + AJAX base-url fix applied; icon text update fixed
- [x] GitHub Gist import — on /new page, client-side fetch from api.github.com, auto-fills form
- [x] Export as JSON — /settings?export=json triggers download of all snippets
- [x] **Complete design overhaul (2026-03-17)** — new dark color system, sidebar layout for auth pages, redesigned all 9 pages
- [x] **Professional polish + API docs (2026-03-17)** — micro-animations, mobile sidebar, visual depth, api-docs page

## Design System (v3 — 2026-03-17)
- Layout: public pages = header + main + footer; auth pages = header + app-layout (sidebar + app-main)
- Sidebar: 200px, sticky, shows languages/tags from user's snippets, + New button at top; gradient background; active item has 2px left border
- Navbar: 48px, guest shows Explore/Docs links + Log in/Sign up; auth shows hamburger + search + avatar
- Auth page navbar: context hint replaces Log in/Sign up buttons (set $authPage = 'login'|'register')
- Dashboard: vertical snippet list (not grid), "Your vault" title, Edit/Delete revealed on hover (.snippet-row-actions)
- Explore: 2-column grid (gap:12px), language filter pills, code previews
- home.php: hero-pill class, feature-icon-wrap circles, section-label, CTA section, stats strip
- Snippet page: line-numbers Prism plugin on code block
- /docs route → pages/api-docs.php (no auth required, sidebar if logged in)
- Mobile: hamburger (#sidebar-toggle) toggles .app-sidebar.open; overlay #sidebar-overlay dims content
- Animations: fadeIn on page load, slideDown on alerts, starPulse on star toggle, scale(0.98) on btn active
- CSS: brightness() hover on buttons, inset box-shadow on cards, 6px radius on code blocks

## Security & Bug Fixes Applied (2026-03-16)
- **BUG 1 (CRITICAL):** Replaced `bin2hex(random_bytes(16))` with `gen_random_uuid()` via `RETURNING id` in all INSERT statements (auth, new-snippet, snippet fork/star, API create/star)
- **BUG 2 (SECURITY):** `deleteSnippet()` in API now checks ownership before deleting stars
- **BUG 3 (SECURITY):** Added `<meta name="base-url">` to header.php for AJAX star toggle
- **BUG 4 (SECURITY):** Removed ineffective `session_set_cookie_params()` after session start; remember-me now uses `session_get_cookie_params()` + `setcookie()` directly
- **BUG 5 (LOGIC):** View count only incremented on GET requests
- **BUG 6 (LOGIC):** Forked snippets default to `is_public = false`
- **BUG 7 (LOGIC):** Delete in edit-snippet.php wrapped in `beginTransaction/commit`
- **BUG 8 (MINOR):** Star button now has `data-snippet-id` attribute for AJAX
- **BUG 9 (MINOR):** Hardcoded `/codevault/X` paths in index.php replaced with `BASE_URL . '/X'`
- **BUG 10 (MINOR):** `.htaccess` now blocks direct access to `.env` files
- **BUG 11 (MINOR):** Pagination added to dashboard (20/page) and explore (24/page)
- **BUG 12 (MINOR):** `logoutUser()` cookie expiry changed from `time() - 42000` to `time() - 3600`

## Commands
- Start Apache: `sudo service apache2 start`
- Local URL: `http://localhost/codevault`
- Restart Apache: `sudo service apache2 restart`
