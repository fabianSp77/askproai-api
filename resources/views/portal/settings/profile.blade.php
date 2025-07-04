@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header with Back Button -->
        <div class="mb-8">
            <div class="flex items-center">
                <a href="{{ route('business.settings.index') }}" class="mr-4 text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Profil bearbeiten</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Aktualisieren Sie Ihre persönlichen Informationen
                    </p>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="bg-white shadow sm:rounded-lg">
            <form action="{{ route('business.settings.profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-6 gap-6">
                        <!-- Name -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Name
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('name') border-red-300 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                E-Mail-Adresse
                            </label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('email') border-red-300 @enderror">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Telefonnummer
                            </label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('phone') border-red-300 @enderror">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Language -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="language" class="block text-sm font-medium text-gray-700">
                                Sprache
                            </label>
                            <select id="language" name="language" 
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="de" {{ old('language', $user->language ?? 'de') == 'de' ? 'selected' : '' }}>Deutsch</option>
                                <option value="en" {{ old('language', $user->language ?? 'de') == 'en' ? 'selected' : '' }}>English</option>
                            </select>
                            @error('language')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Timezone -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="timezone" class="block text-sm font-medium text-gray-700">
                                Zeitzone
                            </label>
                            <select id="timezone" name="timezone" 
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Europe/Berlin" {{ old('timezone', $user->timezone ?? 'Europe/Berlin') == 'Europe/Berlin' ? 'selected' : '' }}>
                                    Berlin (GMT+1)
                                </option>
                                <option value="Europe/Vienna" {{ old('timezone', $user->timezone ?? 'Europe/Berlin') == 'Europe/Vienna' ? 'selected' : '' }}>
                                    Wien (GMT+1)
                                </option>
                                <option value="Europe/Zurich" {{ old('timezone', $user->timezone ?? 'Europe/Berlin') == 'Europe/Zurich' ? 'selected' : '' }}>
                                    Zürich (GMT+1)
                                </option>
                            </select>
                            @error('timezone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Position -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="position" class="block text-sm font-medium text-gray-700">
                                Position
                            </label>
                            <input type="text" name="position" id="position" value="{{ old('position', $user->position) }}"
                                   placeholder="z.B. Geschäftsführer"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="mt-6 border-t border-gray-200 pt-6">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Rolle</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ ucfirst($user->role) }}
                                </dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Registriert seit</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ $user->created_at->format('d.m.Y') }}
                                </dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Letzter Login</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ $user->last_login_at ? $user->last_login_at->format('d.m.Y H:i') : 'Noch nie' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <a href="{{ route('business.settings.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Abbrechen
                    </a>
                    <button type="submit" 
                            class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection