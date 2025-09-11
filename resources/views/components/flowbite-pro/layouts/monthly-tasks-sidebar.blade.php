@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

<!-- Content here -->
<aside class="{{ $attributes->get('class', '') }} fixed right-0 h-svh w-96 translate-x-full border-l border-gray-200 bg-white py-20 transition-transform dark:border-gray-700 dark:bg-gray-800 lg:pt-0 xl:!translate-x-0" aria-label="Profilebar" id="user-drawer">
  <div class="{{ $attributes->get('class', '') }} h-full overflow-y-auto bg-white px-3 py-5 dark:bg-gray-800">
    <div class="{{ $attributes->get('class', '') }} flex items-center justify-between">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white p-1.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
        </svg>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">August 2025</h3>
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white p-1.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
        </svg>
      </button>
    </div>
    <ol class="{{ $attributes->get('class', '') }} space-y-2 py-6">
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-01" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">1 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">4 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-02" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">2 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">1 task</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-03" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">3 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">10 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-04" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">4 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-center rounded-lg bg-gray-100 p-2 text-gray-900 dark:bg-gray-700 dark:text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p>No tasks for today</p>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-05" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">5 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">2 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-06" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">6 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">14 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-07" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">7 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">8 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-08" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">8 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-center rounded-lg bg-gray-100 p-2 text-gray-900 dark:bg-gray-700 dark:text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p>No tasks for today</p>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-09" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">9 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">6 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-10" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">10 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">8 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-11" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">11 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">5 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-12" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">12 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-center rounded-lg bg-gray-100 p-2 text-gray-900 dark:bg-gray-700 dark:text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p>No tasks for today</p>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-13" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">13 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">8 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-14" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">14 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">5 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-15" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">15 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-center rounded-lg bg-gray-100 p-2 text-gray-900 dark:bg-gray-700 dark:text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p>No tasks for today</p>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-16" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">16 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-start rounded-lg bg-primary-600 p-2 text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p class="{{ $attributes->get('class', '') }} leading-none">You have <span class="{{ $attributes->get('class', '') }} font-semibold">3 tasks</span> today</p>
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium hover:underline inline-block">See all</a>
          </div>
        </div>
      </li>
      <li class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-2 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} me-2 w-20 rounded-lg bg-gray-100 py-1 dark:bg-gray-700">
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025" class="{{ $attributes->get('class', '') }} w-20 text-sm font-normal text-gray-500 dark:text-gray-400">2025</time></div>
          <div class="{{ $attributes->get('class', '') }} text-center"><time datetime="2025-08-17" class="{{ $attributes->get('class', '') }} w-20 text-sm font-medium text-gray-900 dark:text-white">17 Aug</time></div>
        </div>
        <div href="#" class="{{ $attributes->get('class', '') }} flex grow items-center rounded-lg bg-gray-100 p-2 text-gray-900 dark:bg-gray-700 dark:text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3c0-.6.4-1 1-1h6c.6 0 1 .4 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Zm2 5c0-.6.4-1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <div class="{{ $attributes->get('class', '') }} text-sm space-y-1.5">
            <p>No tasks for today</p>
          </div>
        </div>
      </li>
    </ol>
  </div>
</aside>
