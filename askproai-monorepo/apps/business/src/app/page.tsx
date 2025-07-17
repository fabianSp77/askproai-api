'use client'

import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle } from '@askproai/ui'
import { Phone, Calendar, Users, TrendingUp, Plus, ArrowRight } from 'lucide-react'

export default function BusinessDashboard() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-950">
      {/* Header */}
      <header className="border-b bg-white/80 backdrop-blur-xl dark:bg-gray-900/80">
        <div className="container mx-auto flex h-16 items-center justify-between px-4">
          <div className="flex items-center space-x-4">
            <h1 className="text-xl font-bold">AskProAI</h1>
            <nav className="hidden md:flex items-center space-x-6">
              <a href="/" className="text-sm font-medium text-primary">Dashboard</a>
              <a href="/calls" className="text-sm font-medium text-muted-foreground hover:text-foreground">Anrufe</a>
              <a href="/appointments" className="text-sm font-medium text-muted-foreground hover:text-foreground">Termine</a>
              <a href="/analytics" className="text-sm font-medium text-muted-foreground hover:text-foreground">Analytics</a>
            </nav>
          </div>
          <div className="flex items-center space-x-4">
            <Button variant="ghost" size="sm">
              <Users className="mr-2 h-4 w-4" />
              Team
            </Button>
            <Button variant="primary" size="sm">
              <Plus className="mr-2 h-4 w-4" />
              Neuer Termin
            </Button>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="container mx-auto px-4 py-8">
        <div className="business-hero">
          <div className="relative z-10">
            <h2 className="text-3xl font-bold mb-4">
              Willkommen zurück, Max!
            </h2>
            <p className="text-lg opacity-90 mb-6">
              Ihr AI-Assistent hat heute bereits 24 Anrufe bearbeitet und 8 neue Termine gebucht.
            </p>
            <div className="flex flex-wrap gap-4">
              <Button variant="secondary" className="bg-white/20 hover:bg-white/30 text-white border-white/30">
                <Phone className="mr-2 h-4 w-4" />
                Testanruf starten
              </Button>
              <Button variant="ghost" className="text-white hover:bg-white/20">
                Einstellungen
                <ArrowRight className="ml-2 h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Grid */}
      <section className="container mx-auto px-4 py-6">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card className="business-card">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                Anrufe heute
              </CardTitle>
              <Phone className="h-4 w-4 text-business-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">24</div>
              <div className="flex items-center text-xs text-muted-foreground">
                <TrendingUp className="mr-1 h-3 w-3 text-green-600" />
                <span className="text-green-600">+12%</span>
                <span className="ml-1">seit gestern</span>
              </div>
            </CardContent>
          </Card>

          <Card className="business-card">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                Neue Termine
              </CardTitle>
              <Calendar className="h-4 w-4 text-business-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">8</div>
              <div className="text-xs text-muted-foreground">
                Ø Wartezeit: <span className="font-medium">2.3 Sek</span>
              </div>
            </CardContent>
          </Card>

          <Card className="business-card">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                Erfolgsrate
              </CardTitle>
              <TrendingUp className="h-4 w-4 text-business-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">94%</div>
              <div className="text-xs text-muted-foreground">
                der Anrufe erfolgreich
              </div>
            </CardContent>
          </Card>

          <Card className="business-card">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                Gesparte Zeit
              </CardTitle>
              <Users className="h-4 w-4 text-business-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">3.2h</div>
              <div className="text-xs text-muted-foreground">
                heute eingespart
              </div>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Recent Calls */}
      <section className="container mx-auto px-4 py-6">
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Letzte Anrufe</CardTitle>
                <CardDescription>
                  Übersicht der kürzlich bearbeiteten Anrufe
                </CardDescription>
              </div>
              <Button variant="outline" size="sm">
                Alle anzeigen
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {[
                { status: 'active', result: 'Termin gebucht', time: 'vor 5 Minuten' },
                { status: 'ended', result: 'Information gegeben', time: 'vor 12 Minuten' },
                { status: 'ended', result: 'Termin gebucht', time: 'vor 18 Minuten' },
                { status: 'missed', result: 'Verpasst', time: 'vor 25 Minuten' },
                { status: 'ended', result: 'Rückruf vereinbart', time: 'vor 32 Minuten' },
              ].map((call, i) => (
                <div key={i} className="call-card">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <div className={`call-status-indicator call-${call.status}`} />
                      <div>
                        <p className="text-sm font-medium">+49 123 456 {7890 + i}</p>
                        <p className="text-xs text-muted-foreground">{call.result}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-xs text-muted-foreground">{call.time}</p>
                      <Button variant="ghost" size="sm" className="h-7 text-xs">
                        Details
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </section>

      {/* Quick Actions */}
      <section className="container mx-auto px-4 py-6 pb-12">
        <h3 className="text-lg font-semibold mb-4">Schnellaktionen</h3>
        <div className="grid gap-4 md:grid-cols-3">
          <Card className="feature-card cursor-pointer hover:shadow-md transition-shadow">
            <CardContent className="p-6">
              <div className="feature-icon mb-4">
                <Phone className="h-6 w-6" />
              </div>
              <h4 className="font-semibold mb-2">Testanruf durchführen</h4>
              <p className="text-sm text-muted-foreground">
                Testen Sie Ihren AI-Assistenten mit einem Probeanruf
              </p>
            </CardContent>
          </Card>

          <Card className="feature-card cursor-pointer hover:shadow-md transition-shadow">
            <CardContent className="p-6">
              <div className="feature-icon mb-4">
                <Calendar className="h-6 w-6" />
              </div>
              <h4 className="font-semibold mb-2">Verfügbarkeit anpassen</h4>
              <p className="text-sm text-muted-foreground">
                Passen Sie Ihre Terminverfügbarkeit an
              </p>
            </CardContent>
          </Card>

          <Card className="feature-card cursor-pointer hover:shadow-md transition-shadow">
            <CardContent className="p-6">
              <div className="feature-icon mb-4">
                <Users className="h-6 w-6" />
              </div>
              <h4 className="font-semibold mb-2">Team verwalten</h4>
              <p className="text-sm text-muted-foreground">
                Fügen Sie Teammitglieder hinzu oder verwalten Sie Berechtigungen
              </p>
            </CardContent>
          </Card>
        </div>
      </section>
    </div>
  )
}