import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ErrorBoundary from '../../components/ErrorBoundary';

// Mock console.error to avoid noise in tests
const originalError = console.error;
beforeAll(() => {
  console.error = vi.fn();
});

afterAll(() => {
  console.error = originalError;
});

// Component that throws an error
const ThrowError = ({ shouldThrow = false }) => {
  if (shouldThrow) {
    throw new Error('Test error');
  }
  return <div>No error content</div>;
};

describe('ErrorBoundary', () => {
  it('renders children when there is no error', () => {
    render(
      <ErrorBoundary>
        <div>Test content</div>
      </ErrorBoundary>
    );

    expect(screen.getByText('Test content')).toBeInTheDocument();
  });

  it('renders error UI when child component throws', () => {
    render(
      <ErrorBoundary>
        <ThrowError shouldThrow={true} />
      </ErrorBoundary>
    );

    expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();
  });

  it('provides custom fallback UI when specified', () => {
    const CustomFallback = ({ error }) => (
      <div>Custom error: {error.message}</div>
    );

    render(
      <ErrorBoundary fallback={CustomFallback}>
        <ThrowError shouldThrow={true} />
      </ErrorBoundary>
    );

    expect(screen.getByText('Custom error: Test error')).toBeInTheDocument();
  });

  it('logs error to console in development', () => {
    render(
      <ErrorBoundary>
        <ThrowError shouldThrow={true} />
      </ErrorBoundary>
    );

    expect(console.error).toHaveBeenCalled();
  });

  it('allows retry functionality', () => {
    const { rerender } = render(
      <ErrorBoundary>
        <ThrowError shouldThrow={true} />
      </ErrorBoundary>
    );

    expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();

    // Simulate retry by re-rendering without error
    rerender(
      <ErrorBoundary>
        <ThrowError shouldThrow={false} />
      </ErrorBoundary>
    );

    expect(screen.getByText('No error content')).toBeInTheDocument();
  });

  it('isolates errors to boundary scope', () => {
    render(
      <div>
        <ErrorBoundary>
          <ThrowError shouldThrow={true} />
        </ErrorBoundary>
        <div>Outside content</div>
      </div>
    );

    expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();
    expect(screen.getByText('Outside content')).toBeInTheDocument();
  });

  it('handles async errors', async () => {
    const AsyncError = () => {
      React.useEffect(() => {
        throw new Error('Async error');
      }, []);
      return <div>Async content</div>;
    };

    render(
      <ErrorBoundary>
        <AsyncError />
      </ErrorBoundary>
    );

    // Note: React Error Boundaries don't catch async errors by default
    // This test demonstrates the limitation
    expect(screen.getByText('Async content')).toBeInTheDocument();
  });

  it('preserves error info for error reporting', () => {
    let capturedError;
    const ErrorCapture = ({ error, errorInfo }) => {
      capturedError = { error, errorInfo };
      return <div>Error captured</div>;
    };

    render(
      <ErrorBoundary fallback={ErrorCapture}>
        <ThrowError shouldThrow={true} />
      </ErrorBoundary>
    );

    expect(capturedError.error.message).toBe('Test error');
    expect(capturedError.errorInfo).toHaveProperty('componentStack');
  });
});