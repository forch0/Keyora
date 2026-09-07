import { setupServer } from 'msw/node'
import { handlers } from './handlers'

/**
 * MSW server for Node.js (Vitest) tests.
 * Start in beforeAll, reset between tests, close in afterAll.
 */
export const server = setupServer(...handlers)
