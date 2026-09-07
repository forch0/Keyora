import { describe, it, expect } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '@/test/utils'
import { EmptyState } from '@/components/shared/EmptyState'
import { Lock } from 'lucide-react'

describe('EmptyState', () => {
  it('renders title and description', () => {
    renderWithProviders(
      <EmptyState
        icon={Lock}
        title="No items"
        description="Items will appear here when created."
      />,
    )
    expect(screen.getByText('No items')).toBeInTheDocument()
    expect(screen.getByText('Items will appear here when created.')).toBeInTheDocument()
  })

  it('renders action button when provided', () => {
    renderWithProviders(
      <EmptyState
        icon={Lock}
        title="No items"
        description="Create one now."
        action={<button>Create Item</button>}
      />,
    )
    expect(screen.getByText('Create Item')).toBeInTheDocument()
  })

  it('renders without action when not provided', () => {
    renderWithProviders(
      <EmptyState icon={Lock} title="Empty" description="Nothing here." />,
    )
    expect(screen.queryByRole('button')).not.toBeInTheDocument()
  })
})
