# Zekura Frontend

React SPA for the Zekura password and secrets management platform.

## Quick Start

```bash
# Install dependencies
npm install

# Start the dev server (proxies /api to localhost:8000)
npm run dev

# Build for production
npm run build

# Run tests
npm run test

# Lint
npm run lint

# Generate API types from backend OpenAPI spec
npm run gen:api
```

## Prerequisites

- Node.js 22+
- The Zekura Laravel API running on `localhost:8000`

## Tech Stack

- Vite + React 19 + TypeScript
- TanStack Query (server state)
- Zustand (client state)
- React Router (routing)
- Tailwind CSS v4 + shadcn/ui (UI)
- React Hook Form + Zod (forms)
- Vitest + Testing Library (tests)

## Project Structure

See `docs/frontend/CONVENTIONS.md` for the full project structure and coding standards.
