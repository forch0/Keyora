import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, fireEvent, waitFor } from '@testing-library/react'
import { renderWithProviders } from '@/test/utils'
import { CopyButton } from '@/components/shared/CopyButton'

describe('CopyButton', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('renders with default label', () => {
    renderWithProviders(<CopyButton value="test-value" />)
    expect(screen.getByText('Copy')).toBeInTheDocument()
  })

  it('renders with custom label', () => {
    renderWithProviders(<CopyButton value="test-value" label="Copy password" />)
    expect(screen.getByText('Copy password')).toBeInTheDocument()
  })

  it('copies value to clipboard when clicked', async () => {
    const writeTextSpy = vi.fn().mockResolvedValue(undefined)
    // Mock clipboard before rendering
    vi.stubGlobal('navigator', {
      ...navigator,
      clipboard: {
        writeText: writeTextSpy,
        readText: () => Promise.resolve(''),
      },
    })

    renderWithProviders(<CopyButton value="secret123" />)
    fireEvent.click(screen.getByRole('button'))

    await waitFor(() => {
      expect(writeTextSpy).toHaveBeenCalledWith('secret123')
    })

    vi.unstubAllGlobals()
  })
})
