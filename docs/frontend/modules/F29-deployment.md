# Module F29 — Production Build & Deployment

| Field | Value |
|---|---|
| **Module** | F29 |
| **Name** | Production Build & Deployment |
| **Dependencies** | F28 |
| **Status** | Not Started |

---

## Objective

Configure the production build, Nginx reverse proxy setup, and deployment process. The React app is served as static files by Nginx, which also proxies `/api` to the Laravel backend.

---

## Tasks

### F29.1 Production build

- [ ] Verify `npm run build` produces optimized output in `dist/`
- [ ] Configure Vite for production:
  - Source map generation (for debugging, not served to users)
  - Asset hashing for cache busting
  - Gzip/brotli compression
- [ ] Verify TypeScript compilation has no errors
- [ ] Verify all environment variables are set

### F29.2 Nginx configuration

- [ ] Create `docker/nginx/frontend.conf`:
  - Serve static files from `/var/www/frontend/dist`
  - Proxy `/api` to Laravel backend (PHP-FPM or separate container)
  - Proxy `/sanctum` to Laravel backend
  - SPA fallback: `try_files $uri $uri/ /index.html`
  - Cache static assets (1 year for hashed assets)
  - Gzip compression for text-based assets
  - Security headers: CSP, X-Frame-Options, X-Content-Type-Options

### F29.3 Docker integration

- [ ] Create multi-stage Dockerfile for frontend:
  - Stage 1: Node — build the React app
  - Stage 2: Nginx — serve the built files
- [ ] Update `docker-compose.yml` to include frontend service
- [ ] Frontend container serves on port 80 (or 3000)
- [ ] API container serves on port 8000 (internal)
- [ ] Nginx proxies `/api` to the API container

### F29.4 Deployment runbook

- [ ] Update `docs/DEPLOYMENT.md` with frontend deployment steps:
  - Build the frontend
  - Copy `dist/` to the server
  - Configure Nginx
  - Restart Nginx
- [ ] Document rollback procedure

### F29.5 CI/CD for frontend

- [ ] Add frontend build to GitHub Actions CI:
  - Install dependencies
  - Run lint
  - Run tests
  - Build production bundle
  - Upload build artifact
- [ ] Optional: auto-deploy on merge to `main`

### F29.6 Performance optimization

- [ ] Code splitting per route (React.lazy + Suspense)
- [ ] Lazy load heavy components (rich text editor, charts)
- [ ] Optimize bundle size (analyze with `rollup-plugin-visualizer`)
- [ ] Image optimization (if any images used)
- [ ] Preload critical resources

---

## API Endpoints Used

None — this module is build/deployment only.

---

## Acceptance Criteria

- [ ] `npm run build` produces optimized production bundle
- [ ] Nginx serves the React app and proxies API requests
- [ ] Docker Compose runs frontend + backend together
- [ ] SPA routing works (direct URL access doesn't 404)
- [ ] Static assets are cached with hashed filenames
- [ ] Security headers are set (CSP, X-Frame-Options, etc.)
- [ ] CI pipeline builds and tests the frontend
- [ ] Deployment runbook is documented

---

## What This Module Does NOT Include

- SSL/TLS certificate setup (covered in backend deployment)
- CDN configuration (not needed for internal tool)
- Monitoring / alerting (Telescope handles backend)
