import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import { Button } from '../../../components/ui/button';

describe('Button Component', () => {
  it('renders with text content', () => {
    render(<Button>Click me</Button>);
    expect(screen.getByRole('button', { name: 'Click me' })).toBeInTheDocument();
  });

  it('handles click events', async () => {
    const handleClick = vi.fn();
    const user = userEvent.setup();
    
    render(<Button onClick={handleClick}>Click me</Button>);
    
    await user.click(screen.getByRole('button'));
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('can be disabled', () => {
    render(<Button disabled>Disabled button</Button>);
    
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
    expect(button).toHaveAttribute('aria-disabled', 'true');
  });

  it('prevents click when disabled', async () => {
    const handleClick = vi.fn();
    const user = userEvent.setup();
    
    render(
      <Button disabled onClick={handleClick}>
        Disabled button
      </Button>
    );
    
    await user.click(screen.getByRole('button'));
    expect(handleClick).not.toHaveBeenCalled();
  });

  it('renders with different variants', () => {
    const { rerender } = render(<Button variant="primary">Primary</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-primary');
    
    rerender(<Button variant="secondary">Secondary</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-secondary');
    
    rerender(<Button variant="destructive">Destructive</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-destructive');
    
    rerender(<Button variant="outline">Outline</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-outline');
    
    rerender(<Button variant="ghost">Ghost</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-ghost');
    
    rerender(<Button variant="link">Link</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-link');
  });

  it('renders with different sizes', () => {
    const { rerender } = render(<Button size="sm">Small</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-sm');
    
    rerender(<Button size="md">Medium</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-md');
    
    rerender(<Button size="lg">Large</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-lg');
  });

  it('renders with icon', () => {
    const Icon = () => <svg data-testid="icon" />;
    
    render(
      <Button>
        <Icon />
        With Icon
      </Button>
    );
    
    expect(screen.getByTestId('icon')).toBeInTheDocument();
    expect(screen.getByText('With Icon')).toBeInTheDocument();
  });

  it('renders as a different element when asChild is true', () => {
    render(
      <Button asChild>
        <a href="/link">Link Button</a>
      </Button>
    );
    
    const link = screen.getByRole('link', { name: 'Link Button' });
    expect(link).toBeInTheDocument();
    expect(link).toHaveAttribute('href', '/link');
  });

  it('shows loading state', () => {
    render(
      <Button loading>
        Loading
      </Button>
    );
    
    const button = screen.getByRole('button');
    expect(button).toHaveClass('btn-loading');
    expect(button).toBeDisabled();
    expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
  });

  it('handles keyboard navigation', async () => {
    const handleClick = vi.fn();
    const user = userEvent.setup();
    
    render(<Button onClick={handleClick}>Keyboard Test</Button>);
    
    const button = screen.getByRole('button');
    
    // Focus the button
    await user.tab();
    expect(button).toHaveFocus();
    
    // Press Enter
    await user.keyboard('{Enter}');
    expect(handleClick).toHaveBeenCalledTimes(1);
    
    // Press Space
    await user.keyboard(' ');
    expect(handleClick).toHaveBeenCalledTimes(2);
  });

  it('applies custom className', () => {
    render(
      <Button className="custom-class">
        Custom Button
      </Button>
    );
    
    expect(screen.getByRole('button')).toHaveClass('custom-class');
  });

  it('forwards ref correctly', () => {
    const ref = React.createRef();
    
    render(
      <Button ref={ref}>
        Button with ref
      </Button>
    );
    
    expect(ref.current).toBeInstanceOf(HTMLButtonElement);
    expect(ref.current).toHaveTextContent('Button with ref');
  });

  it('handles form submission', async () => {
    const handleSubmit = vi.fn((e) => e.preventDefault());
    const user = userEvent.setup();
    
    render(
      <form onSubmit={handleSubmit}>
        <Button type="submit">Submit</Button>
      </form>
    );
    
    await user.click(screen.getByRole('button'));
    expect(handleSubmit).toHaveBeenCalledTimes(1);
  });

  it('renders with full width', () => {
    render(<Button fullWidth>Full Width</Button>);
    
    expect(screen.getByRole('button')).toHaveClass('w-full');
  });

  it('handles async onClick', async () => {
    const asyncClick = vi.fn(async () => {
      await new Promise(resolve => setTimeout(resolve, 100));
    });
    
    const user = userEvent.setup();
    
    render(
      <Button onClick={asyncClick}>
        Async Button
      </Button>
    );
    
    await user.click(screen.getByRole('button'));
    
    await waitFor(() => {
      expect(asyncClick).toHaveBeenCalled();
    });
  });

  it('supports data attributes', () => {
    render(
      <Button data-testid="custom-button" data-action="submit">
        Data Attributes
      </Button>
    );
    
    const button = screen.getByTestId('custom-button');
    expect(button).toHaveAttribute('data-action', 'submit');
  });

  it('handles focus and blur events', async () => {
    const handleFocus = vi.fn();
    const handleBlur = vi.fn();
    const user = userEvent.setup();
    
    render(
      <Button onFocus={handleFocus} onBlur={handleBlur}>
        Focus Test
      </Button>
    );
    
    const button = screen.getByRole('button');
    
    // Focus
    await user.tab();
    expect(handleFocus).toHaveBeenCalledTimes(1);
    expect(button).toHaveFocus();
    
    // Blur
    await user.tab();
    expect(handleBlur).toHaveBeenCalledTimes(1);
    expect(button).not.toHaveFocus();
  });

  it('renders with tooltip', async () => {
    const user = userEvent.setup();
    
    render(
      <Button title="This is a tooltip">
        Hover me
      </Button>
    );
    
    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('title', 'This is a tooltip');
    
    // Hover to show tooltip
    await user.hover(button);
    
    // Tooltip implementation would show here
    // This is a placeholder for actual tooltip testing
  });

  it('supports ARIA attributes', () => {
    render(
      <Button
        aria-label="Custom label"
        aria-pressed="true"
        aria-describedby="description"
      >
        ARIA Button
      </Button>
    );
    
    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('aria-label', 'Custom label');
    expect(button).toHaveAttribute('aria-pressed', 'true');
    expect(button).toHaveAttribute('aria-describedby', 'description');
  });

  it('handles ripple effect on click', async () => {
    const user = userEvent.setup();
    
    render(<Button ripple>Ripple Button</Button>);
    
    const button = screen.getByRole('button');
    await user.click(button);
    
    // Check if ripple element is created
    const ripple = button.querySelector('.ripple');
    expect(ripple).toBeInTheDocument();
    
    // Ripple should be removed after animation
    await waitFor(() => {
      expect(button.querySelector('.ripple')).not.toBeInTheDocument();
    }, { timeout: 1000 });
  });
});