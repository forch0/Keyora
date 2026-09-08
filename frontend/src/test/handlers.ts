import { http, HttpResponse } from 'msw'

/**
 * Default MSW handlers for testing.
 * These mock the Zekura API endpoints used in tests.
 */
export const handlers = [
  // ─── CSRF ────────────────────────────────────────────────────────────────

  http.get('/sanctum/csrf-cookie', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // ─── Auth ────────────────────────────────────────────────────────────────

  http.post('/api/v1/auth/login', async ({ request }) => {
    const body = await request.json() as { email: string; password: string }
    if (body.email === 'test@example.com' && body.password === 'password123') {
      return HttpResponse.json({
        data: {
          id: 1,
          name: 'Test User',
          email: 'test@example.com',
          email_verified_at: null,
          two_factor_enabled: false,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z',
        },
        token: 'test-token-123',
      })
    }
    return HttpResponse.json(
      { error: { code: 'AUTH_INVALID_CREDENTIALS', message: 'Invalid email or password.' } },
      { status: 422 },
    )
  }),

  http.get('/api/v1/auth/me', () => {
    return HttpResponse.json({
      data: {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        email_verified_at: '2024-01-01T00:00:00Z',
        two_factor_enabled: false,
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    })
  }),

  http.post('/api/v1/auth/logout', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('/api/v1/auth/reauthenticate', async ({ request }) => {
    const body = await request.json() as { password: string }
    if (body.password === 'password123') {
      return HttpResponse.json({ data: { reauthenticated: true } })
    }
    return HttpResponse.json(
      { error: { code: 'INVALID_PASSWORD', message: 'Invalid password.' } },
      { status: 422 },
    )
  }),

  http.get('/api/v1/auth/reauthenticate/status', () => {
    return HttpResponse.json({ data: { reauth_required: false } })
  }),

  // ─── Vault ───────────────────────────────────────────────────────────────

  http.get('/api/v1/vault/items', () => {
    return HttpResponse.json({
      data: [
        {
          id: 1,
          team_id: null,
          user_id: 1,
          name: 'GitHub Login',
          type: 'password',
          username: 'testuser',
          password: 'secret123',
          url: 'https://github.com',
          notes: null,
          metadata: null,
          custom_fields: [],
          folder_id: null,
          is_favorite: false,
          is_archived: false,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z',
        },
      ],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 1,
      from: 1,
      to: 1,
    })
  }),

  http.get('/api/v1/vault/items/:id', () => {
    return HttpResponse.json({
      data: {
        id: 1,
        team_id: null,
        user_id: 1,
        name: 'GitHub Login',
        type: 'password',
        username: 'testuser',
        password: 'secret123',
        url: 'https://github.com',
        notes: null,
        metadata: null,
        custom_fields: [],
        folder_id: null,
        is_favorite: false,
        is_archived: false,
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    })
  }),

  http.post('/api/v1/vault/items', async ({ request }) => {
    const body = await request.json() as { name: string }
    return HttpResponse.json({
      data: {
        id: 2,
        team_id: null,
        user_id: 1,
        name: body.name,
        type: 'password',
        username: null,
        password: null,
        url: null,
        notes: null,
        metadata: null,
        custom_fields: [],
        folder_id: null,
        is_favorite: false,
        is_archived: false,
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    })
  }),

  // ─── Security alerts ────────────────────────────────────────────────────

  http.get('/api/v1/security-alerts/unread-count', () => {
    return HttpResponse.json({ count: 3 })
  }),

  http.get('/api/v1/security-alerts', () => {
    return HttpResponse.json({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
      from: null,
      to: null,
    })
  }),

  // ─── Devices ─────────────────────────────────────────────────────────────

  http.get('/api/v1/devices', () => {
    return HttpResponse.json({ data: [] })
  }),

  // ─── Tenants ─────────────────────────────────────────────────────────────

  http.get('/api/v1/tenants', () => {
    return HttpResponse.json({
      data: [
        {
          id: 1,
          name: 'Test Workspace',
          slug: 'test-workspace',
          plan: 'standard',
          settings: null,
          trial_ends_at: null,
          role: 'admin',
          created_at: '2024-01-01T00:00:00Z',
        },
      ],
    })
  }),
]
