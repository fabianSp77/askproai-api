@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Einstellungen</h1>
            <p class="mt-1 text-sm text-gray-600">
                Verwalten Sie Ihr Profil und Ihre Kontoeinstellungen
            </p>
        </div>

        <!-- Settings Navigation -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Profile Settings -->
            <a href="{{ route('business.settings.profile') }}" 
               class="group relative rounded-lg p-6 bg-white hover:bg-gray-50 ring-1 ring-gray-200 hover:ring-gray-300 transition-all">
                <div>
                    <span class="rounded-lg inline-flex p-3 bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-lg font-medium">
                        <span class="absolute inset-0" aria-hidden="true"></span>
                        Profil
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Aktualisieren Sie Ihre persönlichen Informationen
                    </p>
                </div>
            </a>

            <!-- Password Settings -->
            <a href="{{ route('business.settings.password') }}" 
               class="group relative rounded-lg p-6 bg-white hover:bg-gray-50 ring-1 ring-gray-200 hover:ring-gray-300 transition-all">
                <div>
                    <span class="rounded-lg inline-flex p-3 bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-lg font-medium">
                        <span class="absolute inset-0" aria-hidden="true"></span>
                        Passwort
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Ändern Sie Ihr Passwort
                    </p>
                </div>
            </a>

            <!-- Notification Settings -->
            <a href="{{ route('business.settings.notifications') }}" 
               class="group relative rounded-lg p-6 bg-white hover:bg-gray-50 ring-1 ring-gray-200 hover:ring-gray-300 transition-all">
                <div>
                    <span class="rounded-lg inline-flex p-3 bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-lg font-medium">
                        <span class="absolute inset-0" aria-hidden="true"></span>
                        Benachrichtigungen
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Verwalten Sie Ihre E-Mail-Benachrichtigungen
                    </p>
                </div>
            </a>
        </div>

        <!-- Quick Info -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Kontoinformationen
                </h3>
                <div class="mt-3 max-w-xl text-sm text-gray-600">
                    <dl class="space-y-2">
                        <div>
                            <dt class="inline font-medium">Name:</dt>
                            <dd class="inline ml-1">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="inline font-medium">E-Mail:</dt>
                            <dd class="inline ml-1">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="inline font-medium">Rolle:</dt>
                            <dd class="inline ml-1">{{ $user->role_display }}</dd>
                        </div>
                        <div>
                            <dt class="inline font-medium">Mitglied seit:</dt>
                            <dd class="inline ml-1">{{ $user->created_at->format('d.m.Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection