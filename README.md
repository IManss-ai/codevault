# CodeVault

> Your personal code library. Save, organize, search, and share code snippets — built for developers who are tired of losing useful code.

🔗 **Live demo:** https://codevault-production-86f2.up.railway.app

![CodeVault Screenshot](https://codevault-production-86f2.up.railway.app/assets/screenshots/landing.png)

## Features

- **Snippet management** — Create, edit, delete snippets with syntax highlighting for 25+ languages
- **Full-text search** — Search across title, tags, and code content instantly
- **Public & private snippets** — Share what's useful, keep the rest private
- **Explore page** — Browse public snippets from all developers
- **Stars & forks** — Star snippets you like, fork ones you want to modify
- **Public profiles** — Every user gets a profile at `/u/username`
- **REST API** — Full API at `/api/v1/snippets` with Bearer token auth
- **Embeddable widgets** — Embed any public snippet on any website via iframe
- **GitHub Gist import** — Import snippets directly from a Gist URL
- **JSON export** — Export your entire vault as a JSON file
- **Rate limiting** — 100 API requests per hour per key

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 (no frameworks) |
| Database | Supabase (PostgreSQL) |
| Frontend | Vanilla HTML, CSS, JavaScript |
| Syntax highlighting | Prism.js |
| Deployment | Railway + Docker |

## Running Locally

**Requirements:** PHP 8.2+ with `pdo_pgsql`, Apache2 with `mod_rewrite`, Supabase account

**1. Clone the repo**
```bash
git clone https://github.com/IManss-ai/codevault.git
cd codevault
```

**2. Set up environment**
```bash
cp .env.example .env
```

**3. Fill in `.env`**
DB_HOST=your-supabase-host
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres.your-project-id
DB_PASS=your-password
BASE_URL=/codevault

**4.** Place the project in your Apache web root, enable `AllowOverride All`, and visit `http://localhost/codevault`

## Deploy to Railway

1. Fork this repo
2. Create a new project on [Railway](https://railway.app) → Deploy from GitHub
3. Add these environment variables: `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS` `BASE_URL`
4. Set `BASE_URL` to your Railway domain (e.g. `https://yourapp.up.railway.app`)
5. Railway builds and deploys automatically via Dockerfile

## API

Base URL: `/api/v1/snippets` — Authenticate with `Authorization: Bearer YOUR_API_KEY`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/snippets` | List your snippets |
| GET | `/api/v1/snippets/:id` | Get a snippet |
| POST | `/api/v1/snippets` | Create a snippet |
| PUT | `/api/v1/snippets/:id` | Update a snippet |
| DELETE | `/api/v1/snippets/:id` | Delete a snippet |

Full docs at `/docs` on the live site.

## License

MIT
