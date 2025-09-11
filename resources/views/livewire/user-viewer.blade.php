<div class="fi-page-content">
    <div class="space-y-6">
        <!-- User Header -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                {{ $user->name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                User ID: #{{ $user->id }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($user->email_verified_at)
                                <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400">
                                    Verified
                                </span>
                            @else
                                <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 bg-warning-50 text-warning-600 dark:bg-warning-400/10 dark:text-warning-400">
                                    Unverified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Details -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">User Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                <a href="mailto:{{ $user->email }}" class="text-primary-600 hover:text-primary-500">
                                    {{ $user->email }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Verified</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if($user->email_verified_at)
                                    {{ $user->email_verified_at->format('d.m.Y H:i') }}
                                @else
                                    Not verified
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $user->created_at->format('d.m.Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $user->updated_at->format('d.m.Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->id }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>