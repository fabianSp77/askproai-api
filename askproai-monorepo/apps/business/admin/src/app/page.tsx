'use client'

import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle } from '@askproai/ui'
import { TrendingUp, Users, Phone, Calendar } from 'lucide-react'

export default function DashboardPage() {
  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className="w-64 border-r bg-sidebar">
        <div className="flex h-16 items-center border-b px-6">
          <h1 className="text-xl font-bold">AskProAI Admin</h1>
        </div>
        <nav className="space-y-1 p-4">
          <a href="/" className="admin-nav-item active">
            Dashboard
          </a>
          <a href="/appointments" className="admin-nav-item">
            Termine
          </a>
          <a href="/customers" className="admin-nav-item">
            Kunden
          </a>
          <a href="/calls" className="admin-nav-item">
            Anrufe
          </a>
          <a href="/settings" className="admin-nav-item">
            Einstellungen
          </a>
        </nav>
      </aside>

      {/* Main Content */}
      <main className="flex-1">
        {/* Header */}
        <header className="flex h-16 items-center justify-between border-b px-6">
          <h2 className="text-2xl font-semibold">Dashboard</h2>
          <Button variant="primary">Neuer Termin</Button>
        </header>

        {/* Content */}
        <div className="p-6">
          {/* Stats Grid */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                  Termine heute
                </CardTitle>
                <Calendar className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">24</div>
                <p className="text-xs text-muted-foreground">
                  <span className="text-green-600">+12%</span> seit gestern
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                  Neue Kunden
                </CardTitle>
                <Users className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">145</div>
                <p className="text-xs text-muted-foreground">
                  <span className="text-green-600">+20%</span> diese Woche
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                  Anrufe heute
                </CardTitle>
                <Phone className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">89</div>
                <p className="text-xs text-muted-foreground">
                  <span className="text-green-600">+15%</span> seit gestern
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                  Erfolgsrate
                </CardTitle>
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">92%</div>
                <p className="text-xs text-muted-foreground">
                  <span className="text-green-600">+4%</span> diese Woche
                </p>
              </CardContent>
            </Card>
          </div>

          {/* Recent Activity */}
          <Card className="mt-6">
            <CardHeader>
              <CardTitle>Letzte Aktivitäten</CardTitle>
              <CardDescription>
                Übersicht der letzten Kundeninteraktionen
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {[1, 2, 3, 4, 5].map((i) => (
                  <div key={i} className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <Phone className="h-5 w-5 text-primary" />
                      </div>
                      <div>
                        <p className="text-sm font-medium">
                          Anruf von +49 123 456789
                        </p>
                        <p className="text-xs text-muted-foreground">
                          Termin für morgen 14:00 Uhr gebucht
                        </p>
                      </div>
                    </div>
                    <span className="text-xs text-muted-foreground">
                      vor {i * 5} Minuten
                    </span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  )
}