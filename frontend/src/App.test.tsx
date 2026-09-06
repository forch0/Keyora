import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import App from '@/App'

describe('App', () => {
  it('renders the Keyora heading', () => {
    render(<App />)
    expect(screen.getByText('Keyora')).toBeInTheDocument()
  })
})
