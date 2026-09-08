import { create } from 'zustand'

export type LockTimeout = 5 | 15 | 30 | 60 // minutes

interface LockState {
  isLocked: boolean
  timeoutMinutes: LockTimeout
  lock: () => void
  unlock: () => void
  setTimeout: (minutes: LockTimeout) => void
}

const LOCK_TIMEOUT_KEY = 'zekura_lock_timeout'

function getStoredTimeout(): LockTimeout {
  try {
    const raw = localStorage.getItem(LOCK_TIMEOUT_KEY)
    if (raw) {
      const n = Number(raw)
      if ([5, 15, 30, 60].includes(n)) return n as LockTimeout
    }
  } catch {
    // ignore
  }
  return 15 // default 15 minutes
}

function storeTimeout(minutes: LockTimeout) {
  try {
    localStorage.setItem(LOCK_TIMEOUT_KEY, String(minutes))
  } catch {
    // ignore
  }
}

export const useLockStore = create<LockState>((set) => ({
  isLocked: false,
  timeoutMinutes: getStoredTimeout(),
  lock: () => set({ isLocked: true }),
  unlock: () => set({ isLocked: false }),
  setTimeout: (minutes) => {
    storeTimeout(minutes)
    set({ timeoutMinutes: minutes })
  },
}))
