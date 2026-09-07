import { describe, it, expect } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '@/test/utils'
import { PageLoader } from '@/components/shared/PageLoader'

describe('PageLoader', () => {
  it('renders with default label', () => {
    renderWithProviders(<PageLoader />)
    expect(screen.getByText('Loading...')).toBeInTheDocument()
  })

  it('renders with custom label', () => {
    renderWithProviders(<PageLoader label="Fetching data..." />)
    expect(screen.getByText('Fetching data...')).toBeInTheDocument()
  })
})
