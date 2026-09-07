import { useEffect, useRef } from 'react'
import { useAuthStore } from '@/stores/auth-store'
import { useLockStore } from '@/stores/lock-store'

const THROTTLE_MS = 10_000 // don't reset more than once per 10 seconds
const ACTIVITY_EVENTS = ['mousemove', 'keydown', 'scroll', 'touchstart'] as const

/**
 * Tracks user activity and auto-locks the app after the configured
 * inactivity period. Only active when the user is authenticated and
 * the app is not already locked.
 */
export function useActivityTracker() {
  const { user } = useAuthStore()
  const { isLocked, timeoutMinutes, lock } = useLockStore()
  const lastReset = useRef<number>(Date.now())
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => {
    if (!user || isLocked) return

    const resetTimer = () => {
      const now = Date.now()
      // Throttle: don't reset more than once per THROTTLE_MS
      if (now - lastReset.current < THROTTLE_MS) return
      lastReset.current = now

      if (timeoutRef.current) clearTimeout(timeoutRef.current)
      timeoutRef.current = setTimeout(() => {
        lock()
      }, timeoutMinutes * 60 * 1000)
    }

    // Start the timer immediately
    resetTimer()
    lastReset.current = Date.now() // ensure first reset is not throttled

    ACTIVITY_EVENTS.forEach((event) => {
      window.addEventListener(event, resetTimer, { passive: true })
    })

    return () => {
      ACTIVITY_EVENTS.forEach((event) => {
        window.removeEventListener(event, resetTimer)
      })
      if (timeoutRef.current) clearTimeout(timeoutRef.current)
    }
  }, [user, isLocked, timeoutMinutes, lock])
}
