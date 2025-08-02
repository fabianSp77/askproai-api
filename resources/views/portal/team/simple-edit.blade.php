@extends('portal.layouts.unified')

@section('page-title', 'Teammitglied bearbeiten')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Teammitglied bearbeiten</h3>
            </div>

            <form action="{{ route('business.team.update', $member['id']) }}" method="POST" class="px-6 py-4">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" required 
                               value="{{ $member['name'] }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                        <input type="email" name="email" id="email" required 
                               value="{{ $member['email'] }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Rolle</label>
                        <select name="role" id="role" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="Arzt" {{ $member['role'] == 'Arzt' ? 'selected' : '' }}>Arzt</option>
                            <option value="Empfang" {{ $member['role'] == 'Empfang' ? 'selected' : '' }}>Empfang</option>
                            <option value="Therapeut" {{ $member['role'] == 'Therapeut' ? 'selected' : '' }}>Therapeut</option>
                            <option value="Verwaltung" {{ $member['role'] == 'Verwaltung' ? 'selected' : '' }}>Verwaltung</option>
                        </select>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700">Filiale</label>
                        <select name="branch_id" id="branch_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="1" {{ $member['branch_id'] == 1 ? 'selected' : '' }}>Hauptfiliale</option>
                            <option value="2" {{ $member['branch_id'] == 2 ? 'selected' : '' }}>Filiale Nord</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="active">Aktiv</option>
                            <option value="inactive">Inaktiv</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('business.team.index') }}" 
                       class="text-gray-600 hover:text-gray-900">
                        Abbrechen
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection