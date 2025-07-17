import type { Meta, StoryObj } from '@storybook/react'
import { Button } from './button'
import { Loader2, Send, Check, X, Plus, ArrowRight } from 'lucide-react'

const meta = {
  title: 'Components/Button',
  component: Button,
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: 'Moderne, animierte Button-Komponente mit verschiedenen Varianten und Zuständen.',
      },
    },
  },
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['default', 'primary', 'secondary', 'destructive', 'ghost', 'outline'],
      description: 'Visual style variant des Buttons',
    },
    size: {
      control: 'select',
      options: ['xs', 'sm', 'md', 'lg', 'xl'],
      description: 'Größe des Buttons',
    },
    loading: {
      control: 'boolean',
      description: 'Zeigt Ladeanimation',
    },
    disabled: {
      control: 'boolean',
      description: 'Deaktiviert den Button',
    },
  },
} satisfies Meta<typeof Button>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {
  args: {
    children: 'Button',
  },
}

export const Variants: Story = {
  render: () => (
    <div className="flex flex-wrap gap-4">
      <Button variant="default">Default</Button>
      <Button variant="primary">Primary</Button>
      <Button variant="secondary">Secondary</Button>
      <Button variant="destructive">Destructive</Button>
      <Button variant="ghost">Ghost</Button>
      <Button variant="outline">Outline</Button>
    </div>
  ),
}

export const Sizes: Story = {
  render: () => (
    <div className="flex items-center gap-4">
      <Button size="xs">Extra Small</Button>
      <Button size="sm">Small</Button>
      <Button size="md">Medium</Button>
      <Button size="lg">Large</Button>
      <Button size="xl">Extra Large</Button>
    </div>
  ),
}

export const WithIcons: Story = {
  render: () => (
    <div className="flex flex-wrap gap-4">
      <Button variant="primary">
        <Send className="mr-2 h-4 w-4" />
        Send Email
      </Button>
      <Button variant="secondary">
        <Plus className="mr-2 h-4 w-4" />
        Add Item
      </Button>
      <Button variant="outline">
        Continue
        <ArrowRight className="ml-2 h-4 w-4" />
      </Button>
      <Button variant="destructive" size="sm">
        <X className="h-4 w-4" />
      </Button>
      <Button variant="ghost" size="lg">
        <Check className="mr-2 h-5 w-5" />
        Confirm
      </Button>
    </div>
  ),
}

export const Loading: Story = {
  render: () => (
    <div className="flex gap-4">
      <Button loading>Loading...</Button>
      <Button variant="primary" loading>
        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
        Processing
      </Button>
      <Button variant="secondary" loading size="lg">
        Please wait
      </Button>
    </div>
  ),
}

export const Disabled: Story = {
  render: () => (
    <div className="flex gap-4">
      <Button disabled>Disabled</Button>
      <Button variant="primary" disabled>
        Primary Disabled
      </Button>
      <Button variant="destructive" disabled>
        Delete Disabled
      </Button>
    </div>
  ),
}

export const FullWidth: Story = {
  render: () => (
    <div className="w-full max-w-md space-y-4">
      <Button className="w-full" variant="primary" size="lg">
        Sign In
      </Button>
      <Button className="w-full" variant="outline">
        Create Account
      </Button>
    </div>
  ),
}

export const CustomStyling: Story = {
  render: () => (
    <div className="flex gap-4">
      <Button className="bg-gradient-to-r from-pink-500 to-violet-500 text-white hover:from-pink-600 hover:to-violet-600">
        Gradient Button
      </Button>
      <Button className="rounded-full" variant="primary">
        Rounded Button
      </Button>
      <Button className="shadow-lg" variant="secondary">
        Shadow Button
      </Button>
    </div>
  ),
}

export const Interactive: Story = {
  args: {
    children: 'Click me!',
    variant: 'primary',
    size: 'md',
    onClick: () => alert('Button clicked!'),
  },
}