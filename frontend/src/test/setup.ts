import '@testing-library/jest-dom/vitest'
import { afterAll, afterEach, beforeAll } from 'vitest'
import { server } from './server'

// Start MSW server before all tests
beforeAll(() => server.listen({ onUnhandledRequest: 'bypass' }))

// Reset handlers between tests so test-specific overrides don't leak
afterEach(() => {
  server.resetHandlers()
  localStorage.clear()
})

// Clean up after all tests
afterAll(() => server.close())

// jsdom doesn't implement matchMedia — mock it for components that use it
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  }),
})

// Mock clipboard API for jsdom
Object.assign(navigator, {
  clipboard: {
    writeText: () => Promise.resolve(),
    readText: () => Promise.resolve(''),
  },
})
