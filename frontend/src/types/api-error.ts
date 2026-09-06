/** Normalized API error shape matching the backend's error envelope. */
export interface ApiError {
  code: string
  message: string
  status: number
  errors?: Record<string, string[]>
}

/** Field-validation error shape from 422 responses. */
export interface ValidationErrors {
  [field: string]: string[]
}
