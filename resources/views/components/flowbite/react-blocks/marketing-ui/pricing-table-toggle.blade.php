@props([
    'title' => 'Designed for business teams like yours',
    'description' => 'Here at Flowbite we focus on markets where technology, innovation, and capital can unlock long-term value and drive economic growth.',
    'plans' => [
        [
            'name' => 'Freelancer',
            'monthly_price' => 49,
            'yearly_price' => 39,
            'description' => 'Best option for personal use and for your next side projects.',
            'features' => [
                'All tools you need to manage payments',
                'No setup, monthly, or hidden fees',
                'Team of 2-5 full stack developers',
                'Over 200+ integrations',
                'Premium support'
            ],
            'button_text' => 'Get started',
            'button_class' => 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:text-white dark:focus:ring-blue-900'
        ],
        [
            'name' => 'Company',
            'monthly_price' => 199,
            'yearly_price' => 159,
            'description' => 'Relevant for multiple users, extended & premium support.',
            'features' => [
                'All tools you need to manage payments',
                'No setup, monthly, or hidden fees',
                'Team of 2-5 full stack developers',
                'Over 200+ integrations',
                'Premium support'
            ],
            'button_text' => 'Get started',
            'button_class' => 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:text-white dark:focus:ring-blue-900',
            'featured' => true
        ],
        [
            'name' => 'Enterprise',
            'monthly_price' => 499,
            'yearly_price' => 399,
            'description' => 'Best for large scale uses and extended redistribution rights.',
            'features' => [
                'All tools you need to manage payments',
                'No setup, monthly, or hidden fees',
                'Team of 2-5 full stack developers',
                'Over 200+ integrations',
                'Premium support'
            ],
            'button_text' => 'Get started',
            'button_class' => 'text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700'
        ]
    ]
])

<section class="bg-white dark:bg-gray-900"
         x-data="{
             isYearly: false,
             
             toggleBilling() {
                 this.isYearly = !this.isYearly;
             },
             
             getPrice(plan) {
                 return this.isYearly ? plan.yearly_price : plan.monthly_price;
             },
             
             getSavings(plan) {
                 if (!this.isYearly) return 0;
                 return ((plan.monthly_price * 12) - (plan.yearly_price * 12)) / (plan.monthly_price * 12) * 100;
             },
             
             selectPlan(planName) {
                 console.log('Selected plan:', planName, 'Billing:', this.isYearly ? 'yearly' : 'monthly');
                 // Handle plan selection
             }
         }">
    
    <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
        <div class="mx-auto max-w-screen-md text-center mb-8 lg:mb-16">
            <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white">
                {{ $title }}
            </h2>
            <p class="mb-5 font-light text-gray-500 sm:text-xl dark:text-gray-400">
                {{ $description }}
            </p>
            
            <!-- Billing Toggle -->
            <div class="flex items-center justify-center">
                <span class="text-base font-medium text-gray-900 dark:text-white"
                      :class="{ 'text-gray-500 dark:text-gray-400': isYearly }">
                    Monthly
                </span>
                <label class="inline-flex relative items-center mx-4 cursor-pointer">
                    <input type="checkbox" 
                           x-model="isYearly"
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
                <span class="text-base font-medium text-gray-900 dark:text-white"
                      :class="{ 'text-gray-500 dark:text-gray-400': !isYearly }">
                    Yearly
                    <span class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-green-800 bg-green-200 rounded-full dark:bg-green-900 dark:text-green-200">
                        Save up to 20%
                    </span>
                </span>
            </div>
        </div>
        
        <div class="space-y-8 lg:grid lg:grid-cols-3 sm:gap-6 xl:gap-10 lg:space-y-0">
            @foreach($plans as $index => $plan)
            <div class="flex flex-col p-6 mx-auto max-w-lg text-center text-gray-900 bg-white rounded-lg border border-gray-100 shadow dark:border-gray-600 xl:p-8 dark:bg-gray-800 dark:text-white"
                 :class="{ 
                     'relative border-blue-500 ring-4 ring-blue-200 dark:ring-blue-800': {{ json_encode($plan['featured'] ?? false) }}
                 }">
                
                @if($plan['featured'] ?? false)
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800">
                        Most Popular
                    </span>
                </div>
                @endif
                
                <h3 class="mb-4 text-2xl font-semibold">{{ $plan['name'] }}</h3>
                
                <p class="font-light text-gray-500 sm:text-lg dark:text-gray-400">
                    {{ $plan['description'] }}
                </p>
                
                <div class="flex justify-center items-baseline my-8">
                    <span class="mr-2 text-5xl font-extrabold" x-text="'$' + getPrice({{ json_encode($plan) }})"></span>
                    <span class="text-gray-500 dark:text-gray-400" x-text="isYearly ? '/year' : '/month'"></span>
                </div>
                
                <!-- Savings Badge -->
                <div x-show="isYearly && getSavings({{ json_encode($plan) }}) > 0" 
                     class="mb-4">
                    <span class="bg-green-100 text-green-800 text-sm font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-green-200 dark:text-green-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span x-text="'Save ' + Math.round(getSavings({{ json_encode($plan) }})) + '%'"></span>
                    </span>
                </div>
                
                <ul class="mb-8 space-y-4 text-left">
                    @foreach($plan['features'] as $feature)
                    <li class="flex items-center space-x-3">
                        <svg class="flex-shrink-0 w-5 h-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
                
                <button @click="selectPlan('{{ $plan['name'] }}')"
                        class="w-full {{ $plan['button_class'] }}">
                    {{ $plan['button_text'] }}
                </button>
            </div>
            @endforeach
        </div>
    </div>
</section>