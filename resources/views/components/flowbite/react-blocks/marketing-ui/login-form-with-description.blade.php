@props([
    'title' => 'Welcome back',
    'siteUrl' => '#',
    'logoUrl' => 'https://flowbite.s3.amazonaws.com/blocks/marketing-ui/logo.svg',
    'logoAlt' => 'logo',
    'siteName' => 'Flowbite',
    'heroTitle' => "Explore the world's leading design portfolios.",
    'heroDescription' => "Millions of designers and agencies around the world showcase their portfolio work on Flowbite - the home to the world's best design and creative professionals.",
    'customerCount' => '15.7k',
    'avatars' => [
        'https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/bonnie-green.png',
        'https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/jese-leos.png',
        'https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/roberta-casas.png',
        'https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/thomas-lean.png'
    ]
])

<section class="bg-white dark:bg-gray-900"
         x-data="{
             formData: {
                 email: '',
                 password: '',
                 remember: false
             },
             isSubmitting: false,
             showPassword: false,
             
             submitForm() {
                 this.isSubmitting = true;
                 // Form submission logic
                 console.log('Submitting:', this.formData);
                 
                 // Simulate API call
                 setTimeout(() => {
                     this.isSubmitting = false;
                     // Handle success/error
                 }, 2000);
             },
             
             socialLogin(provider) {
                 console.log('Social login with:', provider);
                 // Handle social authentication
             }
         }">
    <div class="grid lg:h-screen lg:grid-cols-2">
        <!-- Login Form Section -->
        <div class="flex items-center justify-center px-4 py-6 sm:px-0 lg:py-0">
            <form @submit.prevent="submitForm()" 
                  class="w-full max-w-md space-y-4 md:space-y-6 xl:max-w-xl">
                
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $title }}
                </h1>
                
                <!-- Social Login Buttons -->
                <div class="items-center space-x-0 space-y-3 sm:flex sm:space-x-4 sm:space-y-0">
                    <button type="button"
                            @click="socialLogin('google')"
                            class="w-full md:w-1/2 inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                        <svg aria-hidden="true" class="w-5 h-5 mr-2" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_13183_10121)">
                                <path d="M20.3081 10.2303C20.3081 9.55056 20.253 8.86711 20.1354 8.19836H10.7031V12.0492H16.1046C15.8804 13.2911 15.1602 14.3898 14.1057 15.0879V17.5866H17.3282C19.2205 15.8449 20.3081 13.2728 20.3081 10.2303Z" fill="#3F83F8"/>
                                <path d="M10.7019 20.0006C13.3989 20.0006 15.6734 19.1151 17.3306 17.5865L14.1081 15.0879C13.2115 15.6979 12.0541 16.0433 10.7056 16.0433C8.09669 16.0433 5.88468 14.2832 5.091 11.9169H1.76562V14.4927C3.46322 17.8695 6.92087 20.0006 10.7019 20.0006V20.0006Z" fill="#34A853"/>
                                <path d="M5.08857 11.9169C4.66969 10.6749 4.66969 9.33008 5.08857 8.08811V5.51233H1.76688C0.348541 8.33798 0.348541 11.667 1.76688 14.4927L5.08857 11.9169V11.9169Z" fill="#FBBC04"/>
                                <path d="M10.7019 3.95805C12.1276 3.936 13.5055 4.47247 14.538 5.45722L17.393 2.60218C15.5852 0.904587 13.1858 -0.0287217 10.7019 0.000673888C6.92087 0.000673888 3.46322 2.13185 1.76562 5.51234L5.08732 8.08813C5.87733 5.71811 8.09302 3.95805 10.7019 3.95805V3.95805Z" fill="#EA4335"/>
                            </g>
                        </svg>
                        Sign in with Google
                    </button>
                    
                    <button type="button"
                            @click="socialLogin('apple')"
                            class="w-full md:w-1/2 inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                        <svg aria-hidden="true" class="w-5 h-5 mr-2 text-gray-900 dark:text-white" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_13183_29163)">
                                <path d="M18.6574 15.5863C18.3549 16.2851 17.9969 16.9283 17.5821 17.5196C17.0167 18.3257 16.5537 18.8838 16.1969 19.1936C15.6439 19.7022 15.0513 19.9627 14.4168 19.9775C13.9612 19.9775 13.4119 19.8479 12.7724 19.585C12.1308 19.3232 11.5412 19.1936 11.0021 19.1936C10.4366 19.1936 9.83024 19.3232 9.18162 19.585C8.53201 19.8479 8.00869 19.985 7.60858 19.9985C7.00008 20.0245 6.39356 19.7566 5.78814 19.1936C5.40174 18.8566 4.91842 18.2788 4.33942 17.4603C3.71821 16.5863 3.20749 15.5727 2.80738 14.4172C2.37887 13.1691 2.16406 11.9605 2.16406 10.7904C2.16406 9.45009 2.45368 8.29407 3.03379 7.32534C3.4897 6.54721 4.09622 5.9334 4.85533 5.4828C5.61445 5.03219 6.43467 4.80257 7.31797 4.78788C7.80129 4.78788 8.4351 4.93738 9.22273 5.2312C10.0081 5.52601 10.5124 5.67551 10.7335 5.67551C10.8988 5.67551 11.4591 5.5007 12.4088 5.15219C13.3069 4.82899 14.0649 4.69517 14.6859 4.74788C16.3685 4.88368 17.6327 5.54699 18.4734 6.74202C16.9685 7.65384 16.2241 8.93097 16.2389 10.5693C16.2525 11.8454 16.7154 12.9074 17.6253 13.7506C18.0376 14.1419 18.4981 14.4444 19.0104 14.6592C18.8993 14.9814 18.7821 15.29 18.6574 15.5863V15.5863ZM14.7982 0.400358C14.7982 1.40059 14.4328 2.3345 13.7044 3.19892C12.8254 4.22654 11.7623 4.82035 10.6093 4.72665C10.5947 4.60665 10.5861 4.48036 10.5861 4.34765C10.5861 3.38743 11.0041 2.3598 11.7465 1.51958C12.1171 1.09416 12.5884 0.740434 13.16 0.458257C13.7304 0.18029 14.2698 0.0265683 14.7772 0.000244141C14.7921 0.133959 14.7982 0.267682 14.7982 0.400345V0.400358Z" fill="currentColor"/>
                            </g>
                        </svg>
                        Sign in with Apple
                    </button>
                </div>
                
                <!-- Divider -->
                <div class="flex items-center">
                    <div class="h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="px-5 text-center text-gray-500 dark:text-gray-400">or</div>
                    <div class="h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                </div>
                
                <!-- Email Field -->
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           x-model="formData.email"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                           placeholder="Enter your email" 
                           required>
                </div>
                
                <!-- Password Field -->
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Password
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               id="password"
                               x-model="formData.password" 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-10 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                               placeholder="••••••••" 
                               required>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-600 dark:text-gray-400">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="remember" 
                                   x-model="formData.remember"
                                   type="checkbox" 
                                   class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700">
                        </div>
                        <label for="remember" class="ml-2 text-sm font-medium text-gray-500 dark:text-gray-300">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-500">
                        Forgot password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        :disabled="isSubmitting"
                        :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                        class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <span x-show="!isSubmitting">Sign in to your account</span>
                    <span x-show="isSubmitting" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Signing in...
                    </span>
                </button>
                
                <!-- Sign Up Link -->
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Don't have an account?
                    <a href="#" class="font-medium text-blue-600 hover:underline dark:text-blue-500">
                        Sign up
                    </a>
                </p>
            </form>
        </div>
        
        <!-- Hero Section -->
        <div class="flex items-center justify-center bg-blue-600 px-4 py-6 sm:px-0 lg:py-0">
            <div class="max-w-md xl:max-w-xl">
                <a href="{{ $siteUrl }}" class="flex items-center mb-4 text-2xl font-semibold text-white">
                    <img class="w-8 h-8 mr-2" src="{{ $logoUrl }}" alt="{{ $logoAlt }}">
                    {{ $siteName }}
                </a>
                
                <h1 class="mb-4 text-3xl font-extrabold leading-none tracking-tight text-white xl:text-5xl">
                    {{ $heroTitle }}
                </h1>
                
                <p class="mb-4 text-blue-200 lg:mb-8">
                    {{ $heroDescription }}
                </p>
                
                <div class="flex items-center divide-x divide-blue-500">
                    <div class="flex -space-x-3">
                        @foreach($avatars as $avatar)
                        <img class="w-8 h-8 border-2 border-white rounded-full" src="{{ $avatar }}" alt="Avatar">
                        @endforeach
                    </div>
                    <a href="#" class="pl-3 text-white dark:text-white sm:pl-5">
                        <span class="text-sm text-blue-200">
                            Over <span class="font-medium text-white">{{ $customerCount }}</span> Happy Customers
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>