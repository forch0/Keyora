import { Component, type ReactNode } from 'react'
import { AlertTriangle, RotateCcw } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface ErrorBoundaryProps {
  children: ReactNode
}

interface ErrorBoundaryState {
  hasError: boolean
  error: Error | null
}

/**
 * Catches React render errors and shows a fallback UI with a reload button.
 * Logs the error to the console for debugging.
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, info: React.ErrorInfo) {
    console.error('ErrorBoundary caught an error:', error, info)
  }

  handleReload = () => {
    this.setState({ hasError: false, error: null })
    window.location.reload()
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="flex min-h-screen flex-col items-center justify-center p-6">
          <div className="max-w-md text-center">
            <AlertTriangle className="mx-auto mb-4 h-12 w-12 text-destructive" />
            <h1 className="text-xl font-semibold">Something went wrong</h1>
            <p className="mt-2 text-muted-foreground text-sm">
              An unexpected error occurred. Try reloading the page. If the
              problem persists, contact your administrator.
            </p>
            {this.state.error && (
              <pre className="mt-4 max-h-32 overflow-auto rounded bg-muted p-3 text-left text-xs text-muted-foreground">
                {this.state.error.message}
              </pre>
            )}
            <Button onClick={this.handleReload} className="mt-4">
              <RotateCcw className="mr-2 h-4 w-4" />
              Reload Page
            </Button>
          </div>
        </div>
      )
    }

    return this.props.children
  }
}
