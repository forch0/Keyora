import { useMutation } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { ApiError } from '@/types/api-error'

// ─── Types ──────────────────────────────────────────────────────────────────

export interface GeneratePasswordOptions {
  length?: number | null
  uppercase?: boolean | null
  lowercase?: boolean | null
  numbers?: boolean | null
  symbols?: boolean | null
  exclude_similar?: boolean | null
  exclude_ambiguous?: boolean | null
  min_uppercase?: number | null
  min_lowercase?: number | null
  min_numbers?: number | null
  min_symbols?: number | null
}

interface GeneratePasswordResponse {
  data: {
    password: string
    options: {
      length: number
      uppercase: string
      lowercase: string
      numbers: string
      symbols: string
    }
  }
}

export type PasswordStrength = 'very_weak' | 'weak' | 'fair' | 'strong' | 'very_strong'

export interface StrengthResult {
  score: Record<string, never> | null
  strength: PasswordStrength
  entropy: number
  criteria: {
    length: boolean
    uppercase: boolean
    lowercase: boolean
    numbers: boolean
    symbols: boolean
    no_common_patterns: boolean
  }
  suggestions: string[]
}

interface StrengthResponse {
  data: StrengthResult
}

// ─── useGeneratePassword ────────────────────────────────────────────────────

export function useGeneratePassword() {
  return useMutation<GeneratePasswordResponse, ApiError, GeneratePasswordOptions>({
    mutationFn: (options) =>
      api.post<GeneratePasswordResponse>('/api/v1/tools/password/generate', options),
  })
}

// ─── useCheckStrength ───────────────────────────────────────────────────────

export function useCheckStrength() {
  return useMutation<StrengthResponse, ApiError, { password: string }>({
    mutationFn: ({ password }) =>
      api.post<StrengthResponse>('/api/v1/tools/password/strength', { password }),
  })
}
