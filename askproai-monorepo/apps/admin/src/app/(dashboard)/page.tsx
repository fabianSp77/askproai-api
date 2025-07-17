'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@askproai/ui'
import { TrendingUp, Users, Phone, Calendar, Euro, Clock, CheckCircle, XCircle } from 'lucide-react'
import { useEffect, useState } from 'react'
import { createAskProAIClient } from '@askproai/api-client'

const api = createAskProAIClient({
  baseUrl: process.env.NEXT_PUBLIC_API_URL || 'https://api.askproai.de',
})

export default function DashboardPage() {
  const [stats, setStats] = useState({
    todayAppointments: 0,
    todayCalls: 0,
    newCustomers: 0,
    revenue: 0,
    successRate: 0,
    avgCallDuration: 0,
  })

  const [recentActivity, setRecentActivity] = useState<any[]>([])

  useEffect(() => {
    // Fetch dashboard data
    fetchDashboardData()
  }, [])

  const fetchDashboardData = async () => {
    try {
      // Fetch today's stats
      const today = new Date().toISOString().split('T')[0]
      
      const [appointments, calls, analytics] = await Promise.all([
        api.appointments.list({
          from: today,
          to: today,
          limit: 100,
        }),
        api.calls.list({
          from: today,
          to: today,
          limit: 100,
        }),
        api.calls.getAnalytics({
          from: today,
          to: today,
        }),
      ])

      setStats({
        todayAppointments: appointments.total,
        todayCalls: calls.total,
        newCustomers: 12, // Mock data
        revenue: 3456.78, // Mock data
        successRate: analytics.conversionRate,
        avgCallDuration: analytics.averageDuration,
      })

      // Mock recent activity
      setRecentActivity([
        {
          id: '1',
          type: 'call',
          description: 'Anruf von +49 123 456789',
          detail: 'Termin für morgen 14:00 Uhr gebucht',
          time: 'vor 5 Minuten',
          icon: Phone,
          status: 'success',
        },
        {
          id: '2',
          type: 'appointment',
          description: 'Neuer Termin erstellt',
          detail: 'Max Mustermann - Beratung',
          time: 'vor 12 Minuten',
          icon: Calendar,
          status: 'success',
        },
        {
          id: '3',
          type: 'call',
          description: 'Verpasster Anruf',
          detail: '+49 987 654321',
          time: 'vor 18 Minuten',
          icon: Phone,
          status: 'error',
        },
      ])
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error)
    }
  }

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <p className="text-gray-600 dark:text-gray-400">
          Willkommen zurück! Hier ist Ihre Übersicht für heute.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Termine heute
            </CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.todayAppointments}</div>
            <p className="text-xs text-muted-foreground">
              <span className="text-green-600">+12%</span> seit gestern
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
            <div className="text-2xl font-bold">{stats.todayCalls}</div>
            <p className="text-xs text-muted-foreground">
              <span className="text-green-600">+8%</span> seit gestern
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
            <div className="text-2xl font-bold">{stats.newCustomers}</div>
            <p className="text-xs text-muted-foreground">
              <span className="text-green-600">+3</span> diese Woche
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Umsatz heute
            </CardTitle>
            <Euro className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              €{stats.revenue.toFixed(2)}
            </div>
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
            <div className="text-2xl font-bold">
              {(stats.successRate * 100).toFixed(0)}%
            </div>
            <p className="text-xs text-muted-foreground">
              der Anrufe erfolgreich
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Ø Anrufdauer
            </CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {Math.floor(stats.avgCallDuration / 60)}:{(stats.avgCallDuration % 60).toString().padStart(2, '0')}
            </div>
            <p className="text-xs text-muted-foreground">
              Minuten
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Recent Activity & Charts */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle>Letzte Aktivitäten</CardTitle>
            <CardDescription>
              Übersicht der letzten Kundeninteraktionen
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentActivity.map((activity) => (
                <div key={activity.id} className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className={`
                      h-10 w-10 rounded-full flex items-center justify-center
                      ${activity.status === 'success' 
                        ? 'bg-green-100 dark:bg-green-900/20' 
                        : 'bg-red-100 dark:bg-red-900/20'
                      }
                    `}>
                      <activity.icon className={`
                        h-5 w-5
                        ${activity.status === 'success'
                          ? 'text-green-600 dark:text-green-400'
                          : 'text-red-600 dark:text-red-400'
                        }
                      `} />
                    </div>
                    <div>
                      <p className="text-sm font-medium">
                        {activity.description}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {activity.detail}
                      </p>
                    </div>
                  </div>
                  <span className="text-xs text-muted-foreground">
                    {activity.time}
                  </span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Performance Chart Placeholder */}
        <Card>
          <CardHeader>
            <CardTitle>Anrufstatistik</CardTitle>
            <CardDescription>
              Anrufvolumen der letzten 7 Tage
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-[300px] flex items-center justify-center text-muted-foreground">
              Chart wird hier angezeigt
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}