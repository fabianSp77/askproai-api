<div class="fi-simple-page">
    <section class="grid auto-cols-fr gap-y-6">
        <div class="fi-simple-main-ctn">
            <main class="fi-simple-main my-16 w-full bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:mx-auto sm:max-w-lg sm:rounded-xl sm:px-12">
                <div class="fi-simple-page">
                    <h1 class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Anmelden
                    </h1>
                    
                    @if($errors->any())
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="list-disc list-inside text-sm text-red-600">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ url('/admin/login-process') }}" class="mt-8">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-950 dark:text-white">
                                    E-Mail
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', 'admin@example.com') }}"
                                    required
                                    autofocus
                                    class="mt-1 block w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-950 dark:text-white">
                                    Passwort
                                </label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    value="password"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            
                            <div>
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                                >
                                    Anmelden
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </section>
</div>