{{-- Flowbite Component: marketing --}}
@props(['title' => 'Tailwind CSS Marketing Dashboard - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<!-- Widget -->
<div class="mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
  <div class="items-start justify-between border-b border-gray-200 pb-4 dark:border-gray-700 lg:pb-6 xl:flex">
    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:mb-0 lg:grid-cols-6 lg:gap-8 xl:grid-cols-6 xl:gap-10">
      <div class="border-b border-gray-200 pb-4 lg:border-0 lg:pb-0 dark:border-gray-700">
        <h3 class="mb-2 text-gray-500 dark:text-gray-400">Live Users</h3>
        <span class="flex items-center text-2xl font-bold text-gray-900 dark:text-white"
          >475
          <span class="ms-2 inline-flex items-center rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
            <svg class="-ms-1 me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            1,4%
          </span>
        </span>
        <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:text-base">
          <svg class="me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207" />
          </svg>
          vs last day
        </p>
      </div>
      <div class="border-b border-gray-200 pb-4 lg:border-0 lg:pb-0 dark:border-gray-700">
        <h3 class="mb-2 text-gray-500 dark:text-gray-400">Visitors</h3>
        <span class="flex items-center text-2xl font-bold text-gray-900 dark:text-white"
          >657.8k
          <span class="ms-2 inline-flex items-center rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
            <svg class="-ms-1 me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
            </svg>
            2%
          </span>
        </span>
        <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:text-base">
          <svg class="me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207" />
          </svg>
          vs last month
        </p>
      </div>
      <div class="border-b border-gray-200 pb-4 lg:border-0 lg:pb-0 dark:border-gray-700">
        <h3 class="mb-2 text-gray-500 dark:text-gray-400">Views</h3>
        <span class="flex items-center text-2xl font-bold text-gray-900 dark:text-white"
          >849.7
          <span class="ms-2 inline-flex items-center rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
            <svg class="-ms-1 me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            4,2%
          </span>
        </span>
        <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:text-base">
          <svg class="me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207" />
          </svg>
          vs last month
        </p>
      </div>
      <div class="border-b border-gray-200 pb-4 lg:border-0 lg:pb-0 dark:border-gray-700">
        <h3 class="mb-2 text-gray-500 dark:text-gray-400">Avg time on site</h3>
        <span class="flex items-center text-2xl font-bold text-gray-900 dark:text-white"
          >04:14
          <span class="ms-2 inline-flex items-center rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
            <svg class="-ms-1 me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            5%
          </span>
        </span>
        <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:text-base">
          <svg class="me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207" />
          </svg>
          vs last month
        </p>
      </div>
      <div class="border-b border-gray-200 pb-4 lg:border-0 lg:pb-0 dark:border-gray-700">
        <h3 class="mb-2 text-gray-500 dark:text-gray-400">Bounce rate</h3>
        <span class="flex items-center text-2xl font-bold text-gray-900 dark:text-white"
          >40%
          <span class="ms-2 inline-flex items-center rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
            <svg class="-ms-1 me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            2.3%
          </span>
        </span>
        <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:text-base">
          <svg class="me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207" />
          </svg>
          vs last month
        </p>
      </div>
      <div class="border-b border-gray-200 pb-4 lg:border-0 lg:pb-0 dark:border-gray-700">
        <h3 class="mb-2 text-gray-500 dark:text-gray-400">Completions</h3>
        <span class="flex items-center text-2xl font-bold text-gray-900 dark:text-white"
          >20.5k
          <span class="ms-2 inline-flex items-center rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
            <svg class="-ms-1 me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            1%
          </span>
        </span>
        <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:text-base">
          <svg class="me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207" />
          </svg>
          vs last month
        </p>
      </div>
    </div>
    <button
      id="actionsDropdownButton"
      data-dropdown-toggle="actionsDropdown"
      data-dropdown-ignore-click-outside-class="datepicker"
      class="flex w-full items-center ms-auto justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
      type="button"
    >
      <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
          clip-rule="evenodd"
        />
      </svg>
      This month
      <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
      </svg>
    </button>
      <!-- Dropdown menu -->
      <div class="z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="actionsDropdown">
        <ul class="p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="actionsDropdown">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
          </li>
        </ul>
        <div class="p-5">
          <span class="text-gray-900 dark:text-white mb-2 block">Custom period:</span>
          <div id="date-range-picker" date-rangepicker class="flex w-full items-center gap-3">
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-start"
                name="start"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Start date"
              />
            </div>
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-end"
                name="end"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="End date"
              />
            </div>
          </div>
        </div>
      </div>
  </div>
  <div class="mb-4 grid items-center gap-4 divide-y pt-4 dark:divide-gray-700 sm:divide-y-0 md:mb-6 md:grid-cols-2 md:pt-6 lg:gap-8 xl:grid-cols-3 2xl:grid-cols-4 2xl:divide-x 2xl:divide-gray-200">
    <!-- Chart -->
    <div>
      <div class="flex items-start justify-between">
        <div>
          <p class="text-base font-normal text-gray-500 dark:text-gray-400">Revenue</p>
          <h5 class="pb-2 text-2xl font-bold leading-none text-gray-900 dark:text-white">$163.4k</h5>
        </div>
        <span class="rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">-2.4%</span>
      </div>
      <div id="revenue-chart"></div>
    </div>
    <!-- Chart -->
    <div class="pt-4 sm:pt-0 lg:ps-8">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-base font-normal text-gray-500 dark:text-gray-400">Customers</p>
          <h5 class="pb-2 text-2xl font-bold leading-none text-gray-900 dark:text-white">1.5M</h5>
        </div>
        <span class="rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">+1,24%</span>
      </div>
      <div id="customers-chart"></div>
    </div>
    <!-- Chart -->
    <div class="pt-4 sm:pt-0 lg:ps-8">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-base font-normal text-gray-500 dark:text-gray-400">Conversion rate</p>
          <h5 class="pb-2 text-2xl font-bold leading-none text-gray-900 dark:text-white">0.7%</h5>
        </div>
        <span class="rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">+2%</span>
      </div>
      <div id="conversion-chart"></div>
    </div>
    <!-- Chart -->
    <div class="pt-4 sm:pt-0 lg:col-span-3 2xl:col-span-1 2xl:ps-8">
      <div class="items-center justify-between space-y-4 sm:flex sm:space-y-0">
        <div class="text-gray-900 dark:text-white">
          <span class="flex items-center font-medium">
            <svg class="me-1 h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v5h18V5a2 2 0 0 0-2-2H5ZM3 14v-2h18v2a2 2 0 0 1-2 2h-6v3h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-3H5a2 2 0 0 1-2-2Z" clip-rule="evenodd" />
            </svg>
            Desktop PC
          </span>
          <h4 class="text-2xl font-bold">55%</h4>
          <span class="text-gray-500 dark:text-gray-400">753.6k Visits</span>
        </div>
        <div class="text-gray-900 dark:text-white">
          <span class="flex items-center font-medium">
            <svg class="me-1 h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M5 4c0-1.1.9-2 2-2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4Zm12 12V5H7v11h10Zm-5 1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
            </svg>
            Mobile
          </span>
          <h4 class="text-2xl font-bold">45%</h4>
          <span class="text-gray-500 dark:text-gray-400">56.2k Visits</span>
        </div>
        <div class="text-gray-900 dark:text-white">
          <span class="flex items-center font-medium">
            <svg class="me-1 h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M4 4c0-1 .7-2 1.9-2H18c1.3 0 2 1 2 2v16c0 1-.7 2-1.9 2H6a2 2 0 0 1-2-2V4Zm7 13a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
            </svg>
            Tablet
          </span>
          <h4 class="text-2xl font-bold">5%</h4>
          <span class="text-gray-500 dark:text-gray-400">6.5k Visits</span>
        </div>
      </div>
      <div id="visits-chart"></div>
    </div>
  </div>
  <div class="border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
    <button
      type="button"
      class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
    >
      View full report
      <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
      </svg>
    </button>
  </div>
</div>

<div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
  <!-- Left column -->
  <div class="space-y-4">
    <!-- Widget -->
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-4 items-start justify-between sm:flex">
        <div class="mb-4 sm:mb-0">
          <h2 class="mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">Website performance</h2>
          <p class="text-gray-500 dark:text-gray-400">Last month website stats.</p>
        </div>
        <button
          id="performanceActionsDropdownButton"
          data-dropdown-toggle="performanceActionsDropdown"
          data-dropdown-ignore-click-outside-class="datepicker"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
          type="button"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
          Dec 31 - Jan 31
          <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
      <!-- Dropdown menu -->
      <div class="z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="performanceActionsDropdown">
        <ul class="p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="actionsDropdown">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
          </li>
        </ul>
        <div class="p-5">
          <span class="text-gray-900 dark:text-white mb-2 block">Custom period:</span>
          <div id="date-range-picker-2" date-rangepicker class="flex w-full items-center gap-3">
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-start-2"
                name="start"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Start date"
              />
            </div>
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-end-2"
                name="end"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="End date"
              />
            </div>
          </div>
        </div>
      </div>
      </div>
      <div class="items-center justify-between gap-8 sm:flex xl:block 2xl:flex">
        <div class="w-full ">
          <ul role="list" class="mb-4 grid grid-cols-2 rounded-lg md:mb-6">
            <li class="border-b border-r border-gray-200 p-2.5 dark:border-gray-700">
              <div class="mb-2 inline-flex items-center text-sm font-bold text-green-500 dark:text-green-400">
                4,5%
                <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">163.4M</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Last month website visits</p>
            </li>
            <li class="border-b border-gray-200 p-2.5 dark:border-gray-700">
              <div class="mb-2 inline-flex items-center text-sm font-bold text-red-600 dark:text-red-500">
                1,12%
                <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">$768k</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Last month revenue</p>
            </li>
            <li class="border-b border-r border-gray-200 p-2.5 dark:border-gray-700">
              <div class="mb-2 inline-flex items-center text-sm font-bold text-green-500 dark:text-green-400">
                2%
                <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">6,567</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Avg transaction</p>
            </li>
            <li class="border-b border-gray-200 p-2.5 dark:border-gray-700">
              <div class="mb-2 inline-flex items-center text-sm font-bold text-red-600 dark:text-red-500">
                1,5%
                <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">$117</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Last month website visits</p>
            </li>
            <li class="border-r border-gray-200 p-2.5 dark:border-gray-700">
              <div class="mb-2 inline-flex items-center text-sm font-bold text-green-500 dark:text-green-400">
                4,5%
                <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">6,010</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Customers</p>
            </li>
            <li class="p-2.5">
              <div class="mb-2 inline-flex items-center text-sm font-bold text-green-500 dark:text-green-400">
                3%
                <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">$910</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Performance</p>
            </li>
          </ul>
          <a href="#" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
            View full report
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
          </a>
        </div>
        <div class="w-full pb-4" id="performance-chart"></div>
      </div>
    </div>
    <!-- Widget -->
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="flex w-full items-start justify-between">
        <div class="flex-col items-center">
          <div class="mb-1 flex items-center">
            <h5 class="me-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Website traffic</h5>
            <svg
              data-popover-target="traffic-info"
              data-popover-placement="bottom"
              class="ms-1 h-4 w-4 cursor-pointer text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm9-3a1.5 1.5 0 0 1 2.5 1.1 1.4 1.4 0 0 1-1.5 1.5 1 1 0 0 0-1 1V14a1 1 0 1 0 2 0v-.5a3.4 3.4 0 0 0 2.5-3.3 3.5 3.5 0 0 0-7-.3 1 1 0 0 0 2 .1c0-.4.2-.7.5-1Zm1 7a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
                clip-rule="evenodd"
              />
            </svg>

            <div
              data-popover
              id="traffic-info"
              role="tooltip"
              class="invisible absolute z-10 inline-block w-72 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
            >
              <div class="space-y-2 p-3">
                <h3 class="font-semibold text-gray-900 dark:text-white">Activity growth - Incremental</h3>
                <p>Report helps navigate cumulative growth of community activities. Ideally, the chart should have a growing trend, as stagnating chart signifies a significant decrease of community activity.</p>
              </div>
              <div data-popper-arrow></div>
            </div>
          </div>
          <button
            id="dateRangeButton"
            data-dropdown-toggle="dateRangeDropdown"
            data-dropdown-ignore-click-outside-class="datepicker"
            type="button"
            class="inline-flex items-center font-medium text-primary-700 hover:underline dark:text-primary-500"
          >
            31 Nov - 31 Dev
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <div id="dateRangeDropdown" class="z-10 hidden w-80 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700 lg:w-96">
            <div class="p-3" aria-labelledby="dateRangeButton">
              <div date-rangepicker datepicker-autohide class="flex items-center">
                <div class="relative">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </div>
                  <input
                    name="start"
                    type="text"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="Start date"
                  />
                </div>
                <span class="mx-2 text-gray-500 dark:text-gray-400">to</span>
                <div class="relative">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </div>
                  <input
                    name="end"
                    type="text"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="End date"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-end">
          <button
            id="widgetDropdownButton"
            data-dropdown-toggle="widgetDropdown"
            data-dropdown-placement="bottom"
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
          >
            <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
            </svg>
            <span class="sr-only">Open dropdown</span>
          </button>
          <div id="widgetDropdown" class="z-10 hidden w-48 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="widgetDropdownButton">
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  ><svg class="me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit widget
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  ><svg class="me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                  </svg>
                  Download data
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  ><svg class="me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.5 3A3.5 3.5 0 0 0 14 7L8.1 9.8A3.5 3.5 0 0 0 2 12a3.5 3.5 0 0 0 6.1 2.3l6 2.7-.1.5a3.5 3.5 0 1 0 1-2.3l-6-2.7a3.5 3.5 0 0 0 0-1L15 9a3.5 3.5 0 0 0 6-2.4c0-2-1.6-3.5-3.5-3.5Z" />
                  </svg>
                  Add to repository
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Line Chart -->
      <div class="py-6" id="traffic-chart"></div>

      <div class="flex justify-center border-t border-gray-200 pt-4 text-gray-500 dark:border-gray-700 dark:text-gray-400 md:pt-6">
        <span class="me-2 inline-flex items-center font-medium text-green-500"
          ><svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
          </svg>
          3.2
        </span>
        compared to last month
      </div>
    </div>
    <!-- Widget -->
    <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:space-y-6 md:p-6">
      <h2 class="mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">Ask Flowbite AI</h2>
      <div class="space-y-4 md:space-y-6">
        <a href="#" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-500 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 gap-4">
          <div>
            <span class="mb-2 inline-flex items-center font-medium uppercase text-gray-900 dark:text-white">
              <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M18 17v-2h1a1 1 0 0 0 0-2h-1a6 6 0 0 0-.4-1H17a2 2 0 0 0 2-2V8a1 1 0 0 0-2 0v2h-.5l-.5-.5V8a4 4 0 0 0-1-2.6l.7-.7c.2-.2.3-.4.3-.7V3a1 1 0 0 0-2 0v.6l-.7.6c-.8-.3-1.8-.3-2.6 0l-.7-.6V3a1 1 0 0 0-2 0v1c0 .3.1.5.3.7l.7.7A4 4 0 0 0 8 8v1.5l-.5.5H7V8a1 1 0 0 0-2 0v2a2 2 0 0 0 2 2h-.7a6 6 0 0 0-.2 1H5a1 1 0 0 0 0 2h1v2a2 2 0 0 0-2 2v2a1 1 0 1 0 2 0v-2h.8a6 6 0 0 0 4.2 3V12a1 1 0 0 1 2 0v10a6 6 0 0 0 4.2-3h.8v2a1 1 0 0 0 2 0v-2a2 2 0 0 0-2-2Zm-4-8.7a5.5 5.5 0 0 0-3-.3v.1a6 6 0 0 0-1 .3V8a2 2 0 1 1 4 0v.3Z"
                />
              </svg>
              Technical performance
            </span>
            <span class="block">What’s my average page load time?</span>
          </div>
          <svg class="h-6 w-6 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
          </svg>
        </a>
        <a href="#" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-500 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 gap-4">
          <div>
            <span class="mb-2 inline-flex items-center font-medium uppercase text-gray-900 dark:text-white">
              <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path d="M13.5 2a7 7 0 0 0-.5 0 1 1 0 0 0-1 1v8c0 .6.4 1 1 1h8c.5 0 1-.4 1-1v-.5A8.5 8.5 0 0 0 13.5 2Z" />
                <path d="M11 6a1 1 0 0 0-1-1 8.5 8.5 0 1 0 9 9 1 1 0 0 0-1-1h-7V6Z" />
              </svg>

              Product performance
            </span>
            <span class="block">What default channel groupings have the most revenue?</span>
          </div>
          <svg class="h-6 w-6 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
          </svg>
        </a>
        <a href="#" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-500 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 gap-4">
          <div>
            <span class="mb-2 inline-flex items-center font-medium uppercase text-gray-900 dark:text-white">
              <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M5 6a3 3 0 1 1 4 2.8V10a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V8.8a3 3 0 1 1 2 0V10a3 3 0 0 1-3 3h-1v2.2a3 3 0 1 1-2 0V13h-1a3 3 0 0 1-3-3V8.8A3 3 0 0 1 5 6Z" clip-rule="evenodd" />
              </svg>

              User channel
            </span>
            <span class="block">What are my top default channel groupings by user?</span>
          </div>
          <svg class="h-6 w-6 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
          </svg>
        </a>
      </div>
      <div class="border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
              clip-rule="evenodd"
            />
          </svg>
          More insights
        </button>
      </div>
    </div>
    <!-- Widget -->
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="w-full items-start justify-between sm:flex">
        <div class="mb-2 sm:mb-0">
          <h5 class="me-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Users by time of day</h5>
          <p class="text-gray-500 dark:text-gray-400">vs last day</p>
        </div>
        <button
          id="dateRangeUsersButton"
          data-dropdown-toggle="dateRangeUsersDropdown"
          data-dropdown-ignore-click-outside-class="datepicker"
          type="button"
          class="inline-flex items-center font-medium text-primary-700 hover:underline dark:text-primary-500"
        >
          Apr 16 - Apr 17, 2025
          <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <div id="dateRangeUsersDropdown" class="z-10 hidden w-80 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700 lg:w-96">
          <div class="p-3" aria-labelledby="dateRangeUsersButton">
            <div date-rangepicker datepicker-autohide class="flex items-center">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <input
                  name="start"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Start date"
                />
              </div>
              <span class="mx-2 text-gray-500 dark:text-gray-400">to</span>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <input
                  name="end"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="End date"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart -->
      <div id="user-count-chart"></div>

      <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-700 sm:pt-6">
        <div>
          <button class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" type="button" data-dropdown-toggle="top-products-dropdown">
            Last 7 days
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <!-- Dropdown menu -->
          <div class="z-50 my-4 w-40 hidden list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="top-products-dropdown">
            <ul class="p-2 text-gray-500 dark:text-gray-400" role="none">
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
              </li>
            </ul>
          </div>
        </div>
        <div class="shrink-0">
          <a href="#" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
            Users Report
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- Right column -->
  <div class="space-y-4">
    <!-- Widget -->
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-3 flex justify-between">
        <div class="flex items-center justify-center">
          <h2 class="pe-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Referrers</h2>
          <svg
            data-popover-target="chart-info"
            data-popover-placement="bottom"
            class="ms-1 h-4 w-4 cursor-pointer text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              fill-rule="evenodd"
              d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm9-3a1.5 1.5 0 0 1 2.5 1.1 1.4 1.4 0 0 1-1.5 1.5 1 1 0 0 0-1 1V14a1 1 0 1 0 2 0v-.5a3.4 3.4 0 0 0 2.5-3.3 3.5 3.5 0 0 0-7-.3 1 1 0 0 0 2 .1c0-.4.2-.7.5-1Zm1 7a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>

          <div
            data-popover
            id="chart-info"
            role="tooltip"
            class="invisible absolute z-10 inline-block w-72 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
          >
            <div class="space-y-2 p-3">
              <h3 class="font-semibold text-gray-900 dark:text-white">Activity growth - Incremental</h3>
              <p>Report helps navigate cumulative growth of community activities. Ideally, the chart should have a growing trend, as stagnating chart signifies a significant decrease of community activity.</p>
              <h3 class="font-semibold text-gray-900 dark:text-white">Calculation</h3>
              <p>For each date bucket, the all-time volume of activities is calculated. This means that activities in period n contain all activities up to period n, plus the activities generated by your community in period.</p>
              <a href="#" class="flex items-center font-medium leading-none text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-500 dark:hover:text-primary-600"
                >Read more
                <svg class="ms-1 h-3 w-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
              </a>
            </div>
            <div data-popper-arrow></div>
          </div>
        </div>
        <div>
          <button
            id="referrersActionsDropdownButton2"
            data-dropdown-toggle="referrersActionsDropdown2"
            data-dropdown-ignore-click-outside-class="datepicker"
            class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            type="button"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                clip-rule="evenodd"
              />
            </svg>
            Dec 31 - Jan 31
            <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
      <!-- Dropdown menu -->
      <div class="z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="referrersActionsDropdown2">
        <ul class="p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="actionsDropdown">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
          </li>
        </ul>
        <div class="p-5">
          <span class="text-gray-900 dark:text-white mb-2 block">Custom period:</span>
          <div id="date-range-picker-3" date-rangepicker class="flex w-full items-center gap-3">
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-start-3"
                name="start"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Start date"
              />
            </div>
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-end-3"
                name="end"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="End date"
              />
            </div>
          </div>
        </div>
      </div>
        </div>
      </div>

      <div>
        <div class="flex" id="devices">
          <div class="me-4 flex items-center">
            <input
              id="desktop"
              type="checkbox"
              value="desktop"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="desktop" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Desktop</label>
          </div>
          <div class="me-4 flex items-center">
            <input
              id="tablet"
              type="checkbox"
              value="tablet"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="tablet" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Tablet</label>
          </div>
          <div class="me-4 flex items-center">
            <input
              id="mobile"
              type="checkbox"
              value="mobile"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="mobile" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Mobile</label>
          </div>
        </div>
      </div>

      <!-- Donut Chart -->
      <div class="py-6" id="donut-chart"></div>

      <!-- Table -->

      <div class="relative mb-4 overflow-x-auto md:mb-6">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400 rtl:text-right">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="rounded-s-lg px-6 py-3"></th>
              <th scope="col" class="px-6 py-3 font-semibold">Websites</th>
              <th scope="col" class="px-6 py-3 font-semibold">Page views</th>
              <th scope="col" class="rounded-e-lg px-6 py-3 font-semibold">Visitors</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4"><div class="h-3 w-3 rounded-full bg-primary-700"></div></td>
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Direct</td>
              <td class="px-6 py-4">176.8k</td>
              <td class="px-6 py-4">76.8k</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4"><div class="h-3 w-3 rounded-full bg-teal-400"></div></td>
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Google</td>
              <td class="px-6 py-4">153.2k</td>
              <td class="px-6 py-4">15.2k</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4"><div class="h-3 w-3 rounded-full bg-orange-300"></div></td>
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Reddit</td>
              <td class="px-6 py-4">102.4k</td>
              <td class="px-6 py-4">10.4k</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4"><div class="h-3 w-3 rounded-full bg-pink-500"></div></td>
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Twitter/X</td>
              <td class="px-6 py-4">38.7k</td>
              <td class="px-6 py-4">8.7k</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4"><div class="h-3 w-3 rounded-full bg-purple-500"></div></td>
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Producthunt</td>
              <td class="px-6 py-4">44.1k</td>
              <td class="px-6 py-4">4.1k</td>
            </tr>
          </tbody>
        </table>
      </div>

      <a href="#" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
        View full report
        <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
        </svg>
      </a>
    </div>
    <!-- Widget -->
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="justify-between sm:mb-3 sm:flex">
        <div class="mb-4 sm:mb-0">
          <h2 class="mb-2 pe-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Sessions by countries</h2>
          <p class="text-gray-500 dark:text-gray-400">vs last month</p>
        </div>
        <div>
          <button
            id="sessionsActionsDropdownButton"
            data-dropdown-toggle="sessionsActionsDropdown"
            data-dropdown-ignore-click-outside-class="datepicker"
            class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            type="button"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                clip-rule="evenodd"
              />
            </svg>
            Dec 31 - Jan 31
            <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
      <!-- Dropdown menu -->
      <div class="z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="sessionsActionsDropdown">
        <ul class="p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="sessionsActionsDropdown">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
          </li>
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
          </li>
        </ul>
        <div class="p-5">
          <span class="text-gray-900 dark:text-white mb-2 block">Custom period:</span>
          <div id="date-range-picker-4" date-rangepicker class="flex w-full items-center gap-3">
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-start-4"
                name="start"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Start date"
              />
            </div>
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="datepicker-range-end-4"
                name="end"
                type="text"
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="End date"
              />
            </div>
          </div>
        </div>
      </div>
        </div>
      </div>

      <!-- USA Map -->
      <div class="py-6" id="map"></div>

      <!-- Table -->

      <div class="relative mb-4 overflow-x-auto md:mb-6">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400 rtl:text-right">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="rounded-s-lg px-6 py-3 font-semibold whitespace-nowrap">State</th>
              <th scope="col" class="px-6 py-3 font-semibold whitespace-nowrap">Visitors</th>
              <th scope="col" class="px-6 py-3 font-semibold whitespace-nowrap">Customers</th>
              <th scope="col" class="rounded-e-lg px-6 py-3 font-semibold whitespace-nowrap">Conversion rate</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">United States</td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  345,756
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  55,834
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  6,19
                </div>
              </td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">UK</td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  234,233
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                  </svg>
                  23,567
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                  </svg>
                  9,93
                </div>
              </td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">France</td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                  </svg>
                  200,400
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                  </svg>
                  11,345
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                  </svg>
                  2,93
                </div>
              </td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Australia</td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  160,283
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  6,422
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  12,80
                </div>
              </td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Germany</td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                  </svg>
                  134,935
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  10,203
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                  </svg>
                  5,80
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <a href="#" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
        View full report
        <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
        </svg>
      </a>
    </div>
    <!-- Widget -->
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <h2 class="mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">Windows</h2>
      <p class="text-gray-500 dark:text-gray-400">Sessions by OS</p>
      <div class="mx-auto" id="sessions-chart"></div>
    </div>
  </div>
</div>

<!-- Table -->
<div class="relative overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
  <!-- Start coding here -->
  <div class="flex-row items-center justify-between space-y-4 p-4 sm:flex sm:space-x-4 sm:space-y-0 md:p-6">
    <h2 class="text-xl font-bold leading-none text-gray-900 dark:text-white">Top pages</h2>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
      >
        <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.7 7.7A7.1 7.1 0 0 0 5 10.8M18 4v4h-4m-7.7 8.3A7.1 7.1 0 0 0 19 13.2M6 20v-4h4" />
        </svg>
        Refresh
      </button>
      <button
        type="button"
        class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
      >
        <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v9.3l-2-2a1 1 0 0 0-1.4 1.4l.3.3h-6.6a1 1 0 1 0 0 2h6.6l-.3.3a1 1 0 0 0 1.4 1.4l2-2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
            clip-rule="evenodd"
          />
        </svg>

        Export
      </button>
    </div>
  </div>

  <div class="overflow-x-auto relative">
    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
      <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
        <tr>
          <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold md:px-6">Page</th>
          <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold md:px-6">Pageviews</th>
          <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold md:px-6">Unique Pageviews</th>
          <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold md:px-6">Avg. time on page</th>
          <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold md:px-6">Entrances</th>
          <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold md:px-6">Bounce rate</th>
        </tr>
      </thead>
      <tbody>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/home</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">14,882</span>(22.26%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">10,723</span>(22.03%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:00:51</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">8,298</span>(53.69%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">47.51%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/store.html</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">3,809</span>(5.70%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">2,943</span>(6.05%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:02:16</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,152</span>(7.45%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">36.62%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/sale.html</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">3,309</span>(4.95%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,554</span>(3.17%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:01:41</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">236</span>(1.53%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">39.11%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/google+flowbite/apparel/mens/mens+t+shirts</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">2,566</span>(3.84%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,890</span>(3.88%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:00:36</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">919</span>(5.95%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">36%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/google+flowbite/shop+by+brand/youtube</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,718</span>(2.57%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,342</span>(2.76%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:01:22</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,109</span>(7.18%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">55.20%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/signin.html</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,606</span>(2.40%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,142</span>(2.35%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:00:57</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">183</span>(1.18%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">36.61%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/google+flowbite/bags/backpacks/home</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,479</span>(2.21%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,101</span>(2.26%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:00:49</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">168</span>(1.09%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">25.15%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/contact</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,325</span>(2.05%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">873</span>(2.18%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:01:29</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">395</span>(2.79%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">34.43%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/sport</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">1,098</span>(1.66%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">768</span>(1.57%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:01:02</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">86</span>(0.79%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">62.79%</td>
        </tr>
        <tr class="border-b dark:border-gray-700 border-gray-200">
          <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">/google+flowbite/apparel/mens/mens+t+shirts/quickview</th>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">996</span>(1.24%)</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">387</span>(0.77%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">00:00:22</td>
          <td class="px-4 py-3 md:px-6"><span class="me-1 font-medium text-gray-900 dark:text-white">9</span>(0.09%)</td>
          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white md:px-6">10.12%</td>
        </tr>
      </tbody>
    </table>
  </div>
  <nav class="flex flex-col items-start justify-between space-y-3 p-4 md:flex-row md:items-center md:space-y-0" aria-label="Table navigation">
    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
      Showing
      <span class="font-semibold text-gray-900 dark:text-white">1-10</span>
      of
      <span class="font-semibold text-gray-900 dark:text-white">1000</span>
    </span>
    <ul class="inline-flex items-stretch -space-x-px">
      <li>
        <a
          href="#"
          class="ml-0 flex h-full items-center justify-center rounded-l-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
        >
          <span class="sr-only">Previous</span>
          <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
          </svg>
        </a>
      </li>
      <li>
        <a
          href="#"
          class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >1</a
        >
      </li>
      <li>
        <a
          href="#"
          class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >2</a
        >
      </li>
      <li>
        <a
          href="#"
          aria-current="page"
          class="z-10 flex items-center justify-center border border-primary-300 bg-primary-50 px-3 py-2 text-sm leading-tight text-primary-600 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white"
          >3</a
        >
      </li>
      <li>
        <a
          href="#"
          class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >...</a
        >
      </li>
      <li>
        <a
          href="#"
          class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >100</a
        >
      </li>
      <li>
        <a
          href="#"
          class="flex h-full items-center justify-center rounded-r-lg border border-gray-300 bg-white px-3 py-1.5 leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
        >
          <span class="sr-only">Next</span>
          <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
        </a>
      </li>
    </ul>
  </nav>
</div>
</div>
