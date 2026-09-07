import { describe, it, expect, beforeEach } from 'vitest'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'
import { server } from '@/test/server'
import { http, HttpResponse } from 'msw'

describe('api client', () => {
  beforeEach(() => {
    useAuthStore.getState().clear()
  })

  it('makes a GET request and returns JSON', async () => {
    const res = await api.get<{ data: { id: number } }>('/api/v1/auth/me')
    expect(res.data.id).toBe(1)
  })

  it('throws an ApiError on 404', async () => {
    server.use(
      http.get('/api/v1/test-404', () => {
        return HttpResponse.json(
          { error: { code: 'NOT_FOUND', message: 'Resource not found.' } },
          { status: 404 },
        )
      }),
    )

    await expect(api.get('/api/v1/test-404')).rejects.toMatchObject({
      status: 404,
      code: 'NOT_FOUND',
    })
  })

  it('throws an ApiError on 422 validation error', async () => {
    server.use(
      http.post('/api/v1/test-422', () => {
        return HttpResponse.json(
          {
            error: {
              code: 'VALIDATION_EXCEPTION',
              message: 'The given data was invalid.',
              errors: { email: ['The email field is required.'] },
            },
          },
          { status: 422 },
        )
      }),
    )

    await expect(api.post('/api/v1/test-422', {})).rejects.toMatchObject({
      status: 422,
      code: 'VALIDATION_EXCEPTION',
    })
  })

  it('throws an ApiError on 500', async () => {
    server.use(
      http.get('/api/v1/test-500', () => {
        return new HttpResponse('Server error', { status: 500 })
      }),
    )

    await expect(api.get('/api/v1/test-500')).rejects.toMatchObject({
      status: 500,
    })
  })

  it('returns undefined for 204 No Content', async () => {
    server.use(
      http.delete('/api/v1/test-204', () => {
        return new HttpResponse(null, { status: 204 })
      }),
    )

    const res = await api.delete('/api/v1/test-204')
    expect(res).toBeUndefined()
  })

  it('attaches bearer token when authenticated', async () => {
    useAuthStore.getState().setToken('my-test-token')
    let capturedAuth: string | null = null

    server.use(
      http.get('/api/v1/test-auth', ({ request }) => {
        capturedAuth = request.headers.get('authorization')
        return HttpResponse.json({ data: { ok: true } })
      }),
    )

    await api.get('/api/v1/test-auth')
    expect(capturedAuth).toBe('Bearer my-test-token')
  })
})
