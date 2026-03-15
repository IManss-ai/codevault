# CodeVault — Requirements

## Core Features

### Authentication
- User should be able to register with username, email, and password
- User should be able to log in with email and password
- User should be able to log out from any page
- User should be able to check "Remember me" to stay logged in longer
- User should see specific error messages when registration or login fails
- User should have their session ID regenerated after login for security

### Snippet Management
- User should be able to create a new snippet with title, description, code, language, tags, and public/private toggle
- User should be able to edit any snippet they own
- User should be able to delete any snippet they own (with confirmation)
- User should see syntax highlighting for 20+ languages via Prism.js
- User should be able to copy snippet code to clipboard with one click
- User should see a character and line counter while editing code
- User should be able to add comma-separated tags to organize snippets

### Dashboard
- User should see a welcome message with their username
- User should see stats: total snippets, public snippets, total stars received
- User should be able to search/filter their snippets by title, tag, or language
- User should see snippet cards in a grid with title, language badge, tags, stars, date
- User should see edit and delete buttons on each snippet card
- User should see a friendly empty state with a "Create your first snippet" call to action

### Search
- User should be able to full-text search across title, tags, and code content
- Search should work in the dashboard (own snippets) and explore page (public snippets)

## Public Features

### Public Profiles
- Any visitor should be able to view a user's profile at `/u/username`
- Profile should show username, bio, website link, join date, and public snippets
- Profile should show the user's public snippet count and total stars received

### Explore Page
- Any visitor should be able to browse trending and recent public snippets
- Explore page should show snippet cards with title, language, author, stars, and date
- Visitor should be able to filter explore by language

### Snippet Pages
- Any visitor should be able to view a public snippet at `/snippet/{id}`
- Snippet page should show full syntax-highlighted code, description, tags, author, and date
- Snippet page should increment the view counter on each visit
- Logged-in user should be able to star/unstar a snippet with a live counter
- Logged-in user should be able to fork a public snippet into their own vault

### Embeddable Widget
- User should be able to get an embed code for any public snippet
- Embed code should be a single HTML line that renders the snippet with syntax highlighting
- Widget should show snippet title, code, language, and a "View on CodeVault" link

## Developer Features

### REST API
- Developer should be able to generate an API key from the settings page
- Developer should be able to list their snippets via `GET /api/v1/snippets`
- Developer should be able to create a snippet via `POST /api/v1/snippets`
- Developer should be able to update a snippet via `PUT /api/v1/snippets/{id}`
- Developer should be able to delete a snippet via `DELETE /api/v1/snippets/{id}`
- API should return JSON responses with appropriate HTTP status codes
- API should enforce rate limiting: 100 requests per hour per key
- API keys should be hashed before storage in the database

### Import / Export
- User should be able to import a snippet from a GitHub Gist URL
- User should be able to export their entire vault as a JSON file

### API Documentation
- Developer should be able to read API docs at a dedicated page
- Docs should show all endpoints, request/response formats, and authentication instructions

## Future Features (not building now)
- Stripe payment integration for Pro plan ($6/month)
- VS Code extension
- GitHub OAuth login
- Teams and shared snippet libraries
- Snippet version history
- Comments on public snippets

## Monetization Tiers (planned)

### Free
- Unlimited public snippets
- Up to 50 private snippets
- Basic search
- Public profile
- API access (100 req/hour)

### Pro ($6/month)
- Unlimited private snippets
- Advanced search and filters
- Snippet analytics
- Custom profile URL
- Higher API rate limits
- Snippet version history

### Teams ($18/month)
- Everything in Pro
- Shared team snippet library
- Team members and admin controls
- Private team explore page
