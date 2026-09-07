/**
 * Re-auth manager — singleton that coordinates the re-authentication flow.
 *
 * When the API client receives a 423 Locked response, it calls
 * `waitForReauth()` which returns a promise. The promise resolves once the
 * user successfully re-authenticates (allowing the original request to be
 * retried) or rejects if the user cancels.
 *
 * The ReauthModal component subscribes to state changes via the listener
 * pattern and calls `resolveReauth()` or `cancelReauth()` based on user
 * action.
 */

type ReauthState = 'idle' | 'pending'

type StateListener = (state: ReauthState) => void

class ReauthManager {
  private state: ReauthState = 'idle'
  private listeners: Set<StateListener> = new Set()
  private resolver: (() => void) | null = null
  private rejecter: ((err: Error) => void) | null = null

  /** Subscribe to state changes. Returns an unsubscribe function. */
  subscribe(listener: StateListener): () => void {
    this.listeners.add(listener)
    listener(this.state)
    return () => this.listeners.delete(listener)
  }

  /** Get the current state. */
  getState(): ReauthState {
    return this.state
  }

  /** Called by the API client on 423. Returns a promise that resolves when re-auth succeeds. */
  waitForReauth(): Promise<void> {
    if (this.state === 'pending') {
      // Already waiting — return the existing promise
      return this.pendingPromise()
    }

    this.state = 'pending'
    this.notify()

    return this.pendingPromise()
  }

  private pendingPromise(): Promise<void> {
    return new Promise<void>((resolve, reject) => {
      this.resolver = resolve
      this.rejecter = reject
    })
  }

  /** Called by the ReauthModal when the user successfully re-authenticates. */
  resolveReauth(): void {
    if (this.resolver) {
      this.resolver()
      this.resolver = null
      this.rejecter = null
    }
    this.state = 'idle'
    this.notify()
  }

  /** Called by the ReauthModal when the user cancels. */
  cancelReauth(): void {
    if (this.rejecter) {
      this.rejecter(new Error('Re-authentication cancelled.'))
      this.resolver = null
      this.rejecter = null
    }
    this.state = 'idle'
    this.notify()
  }

  private notify(): void {
    this.listeners.forEach((l) => l(this.state))
  }
}

export const reauthManager = new ReauthManager()
