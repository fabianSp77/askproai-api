import type { Meta, StoryObj } from '@storybook/react'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from './card'
import { Button } from './button'
import { TrendingUp, Users, Euro, Phone } from 'lucide-react'

const meta = {
  title: 'Components/Card',
  component: Card,
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: 'Flexible Card-Komponente mit Hover-Effekten und Gradient-Optionen.',
      },
    },
  },
  tags: ['autodocs'],
  argTypes: {
    hover: {
      control: 'boolean',
      description: 'Aktiviert Hover-Animationen',
    },
    gradient: {
      control: 'boolean',
      description: 'Fügt einen subtilen Gradient-Hintergrund hinzu',
    },
  },
} satisfies Meta<typeof Card>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {
  render: () => (
    <Card className="w-[350px]">
      <CardHeader>
        <CardTitle>Card Title</CardTitle>
        <CardDescription>Card description goes here</CardDescription>
      </CardHeader>
      <CardContent>
        <p>This is the card content. You can put any content here.</p>
      </CardContent>
      <CardFooter>
        <Button>Action</Button>
      </CardFooter>
    </Card>
  ),
}

export const WithHover: Story = {
  render: () => (
    <Card className="w-[350px]" hover>
      <CardHeader>
        <CardTitle>Interactive Card</CardTitle>
        <CardDescription>Hover over me to see the effect</CardDescription>
      </CardHeader>
      <CardContent>
        <p>This card lifts up slightly when you hover over it.</p>
      </CardContent>
    </Card>
  ),
}

export const WithGradient: Story = {
  render: () => (
    <Card className="w-[350px]" gradient>
      <CardHeader>
        <CardTitle>Gradient Card</CardTitle>
        <CardDescription>Subtle gradient background</CardDescription>
      </CardHeader>
      <CardContent>
        <p>This card has a beautiful gradient background.</p>
      </CardContent>
    </Card>
  ),
}

export const StatCard: Story = {
  render: () => (
    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card hover>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
          <Euro className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">€45,231.89</div>
          <p className="text-xs text-muted-foreground">
            <span className="text-green-600">+20.1%</span> from last month
          </p>
        </CardContent>
      </Card>
      
      <Card hover>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Active Users</CardTitle>
          <Users className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">+2,350</div>
          <p className="text-xs text-muted-foreground">
            <span className="text-green-600">+180</span> since last hour
          </p>
        </CardContent>
      </Card>
      
      <Card hover>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Calls</CardTitle>
          <Phone className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">12,234</div>
          <p className="text-xs text-muted-foreground">
            <span className="text-green-600">+19%</span> from last month
          </p>
        </CardContent>
      </Card>
      
      <Card hover>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Growth</CardTitle>
          <TrendingUp className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">+573</div>
          <p className="text-xs text-muted-foreground">
            <span className="text-green-600">+201</span> since yesterday
          </p>
        </CardContent>
      </Card>
    </div>
  ),
}

export const ProfileCard: Story = {
  render: () => (
    <Card className="w-[350px]" hover gradient>
      <CardHeader>
        <div className="flex items-center space-x-4">
          <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <span className="text-xl font-bold text-primary">JD</span>
          </div>
          <div>
            <CardTitle>John Doe</CardTitle>
            <CardDescription>john.doe@example.com</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          <div className="flex justify-between">
            <span className="text-sm text-muted-foreground">Role</span>
            <span className="text-sm font-medium">Administrator</span>
          </div>
          <div className="flex justify-between">
            <span className="text-sm text-muted-foreground">Status</span>
            <span className="text-sm font-medium text-green-600">Active</span>
          </div>
          <div className="flex justify-between">
            <span className="text-sm text-muted-foreground">Last Login</span>
            <span className="text-sm font-medium">2 hours ago</span>
          </div>
        </div>
      </CardContent>
      <CardFooter className="flex gap-2">
        <Button size="sm" variant="outline" className="flex-1">
          View Profile
        </Button>
        <Button size="sm" variant="primary" className="flex-1">
          Edit
        </Button>
      </CardFooter>
    </Card>
  ),
}

export const ContentCard: Story = {
  render: () => (
    <Card className="max-w-2xl">
      <CardHeader>
        <CardTitle>Getting Started with AskProAI</CardTitle>
        <CardDescription>
          Learn how to set up and configure your AI phone assistant
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div>
          <h3 className="font-semibold mb-2">1. Configure Your Agent</h3>
          <p className="text-sm text-muted-foreground">
            Start by setting up your AI agent with your business information, greeting message, and available services.
          </p>
        </div>
        <div>
          <h3 className="font-semibold mb-2">2. Connect Your Calendar</h3>
          <p className="text-sm text-muted-foreground">
            Link your Cal.com or Google Calendar to enable automatic appointment scheduling.
          </p>
        </div>
        <div>
          <h3 className="font-semibold mb-2">3. Test Your Setup</h3>
          <p className="text-sm text-muted-foreground">
            Make a test call to ensure everything is working correctly before going live.
          </p>
        </div>
      </CardContent>
      <CardFooter>
        <Button variant="primary">Start Configuration</Button>
      </CardFooter>
    </Card>
  ),
}