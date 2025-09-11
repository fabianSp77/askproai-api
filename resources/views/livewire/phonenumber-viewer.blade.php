<div class="fi-page-content">
    <div class="space-y-6">
        <!-- Header -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $phonenumber->name ?? $phonenumber->id }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Phone Number Details
                    </p>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($phonenumber->toArray() as $key => $value)
                        @if(!in_array($key, ['password', 'remember_token']) && !is_array($value) && !is_object($value))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $value ?? 'Not provided' }}
                            </dd>
                        </div>
                        @endif
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
