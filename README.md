# CodeVault

> Your personal, searchable code snippet library.

CodeVault is a web-based code snippet manager and knowledge base for developers. Save useful code, organize it with tags, search instantly, and share with the world.

## Features

- **20+ Languages** — Syntax highlighting via Prism.js
- **Instant Search** — Full-text search across titles, tags, and code
- **Public Profiles** — Share your best snippets at `/u/username`
- **Explore Page** — Discover trending and recent public snippets
- **REST API** — Full CRUD API with key-based authentication
- **Embeddable Widget** — Embed snippets on blogs and Stack Overflow
- **Fork Snippets** — Copy any public snippet into your own vault
- **Stars** — Star your favorite snippets

## Tech Stack

| Layer     | Technology                      |
|-----------|---------------------------------|
| Backend   | PHP 8.2 (no frameworks)         |
| Database  | Supabase (PostgreSQL)           |
| Frontend  | HTML5, CSS3, vanilla JavaScript |
| Syntax    | Prism.js (CDN)                  |
| Fonts     | Inter + JetBrains Mono          |
| Deploy    | Railway                         |

## Getting Started

1. Clone the repo:
   ```bash
   git clone https://github.com/IManss-ai/codevault.git
   cd codevault
   ```

2. Copy `.env.example` to `.env` and add your Supabase credentials:
   ```bash
   cp .env.example .env
   ```

3. Create the database tables in Supabase (see `architecture.md` for the full schema).

4. Point your Apache document root to the project folder, or place it under `/var/www/html/`.

5. Visit `http://localhost/codevault` in your browser.

## Project Structure

```
codevault/
├── index.php          ← Router (all requests go here)
├── config/            ← Environment + database config
├── includes/          ← Header, footer, helpers, auth
├── pages/             ← All page views
├── api/v1/            ← REST API endpoints
└── assets/            ← CSS + JavaScript
```

## License

MIT
