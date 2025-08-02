@extends('portal.layouts.unified')

@section('page-title', 'Teammitglied hinzufügen')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Neues Teammitglied</h3>
            </div>

            <form action="{{ route('business.team.store') }}" method="POST" class="px-6 py-4">
                @csrf

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="Max Mustermann">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                        <input type="email" name="email" id="email" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="max@example.com">
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Rolle</label>
                        <select name="role" id="role" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Rolle wählen</option>
                            <option value="Arzt">Arzt</option>
                            <option value="Empfang">Empfang</option>
                            <option value="Therapeut">Therapeut</option>
                            <option value="Verwaltung">Verwaltung</option>
                        </select>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700">Filiale</label>
                        <select name="branch_id" id="branch_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Filiale wählen</option>
                            <option value="1">Hauptfiliale</option>
                            <option value="2">Filiale Nord</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Telefon (optional)</label>
                        <input type="tel" name="phone" id="phone"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="+49 123 456789">
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('business.team.index') }}" 
                       class="text-gray-600 hover:text-gray-900">
                        Abbrechen
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        Teammitglied hinzufügen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection