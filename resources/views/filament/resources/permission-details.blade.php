<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->name }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Guard</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                    {{ $permission->guard_name === 'web' ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20' : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20' }}">
                    {{ $permission->guard_name }}
                </span>
            </p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Modul</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->module ?? '-' }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktion</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->action ?? '-' }}</p>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Beschreibung</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->description ?? 'Keine Beschreibung verfügbar' }}</p>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Zugewiesene Rollen</h3>
        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $roles }}</p>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Benutzer mit dieser Berechtigung</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $userCount }} Benutzer</p>
    </div>

    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Erstellt am</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->created_at->format('d.m.Y H:i') }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktualisiert am</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->updated_at->format('d.m.Y H:i') }}</p>
        </div>
    </div>
</div>