{{-- Flowbite Component: -index --}}
@props(['title' => 'Tailwind CSS Dashboard - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="px-4 pt-4">
  <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <div class="items-start justify-between flex">
      <div>
        <h2 class="text-gray-500 dark:text-gray-400">Total sales</h2>
        <span class="text-2xl font-bold leading-none text-gray-900 dark:text-white sm:text-3xl">$47,867</span>
      </div>
      <div class="items-center flex space-x-4">
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
        <ul class="p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="referrersActionsDropdownButton2">
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
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
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
                class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="End date"
              />
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
    <div id="main-chart"></div>
  </div>

  <div class="mt-4 grid w-full grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="mb-1 text-2xl font-bold leading-none text-gray-900 dark:text-white">7,564</h3>
          <p class="text-gray-500 dark:text-gray-400">New products this week</p>
        </div>
        <span class="flex items-center font-semibold text-green-500 dark:text-green-400">
          <svg class="h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"></path>
          </svg>
          7%
        </span>
      </div>
      <div id="new-products-chart"></div>
      <!-- Card Footer -->
      <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700 sm:pt-6">
        <button class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" type="button" data-dropdown-toggle="new-products-dropdown">
          Last 7 days
          <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div class="z-50 my-4 w-40 hidden list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="new-products-dropdown" aria-hidden="true" data-popper-placement="bottom">
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
        <div class="shrink-0">
          <a href="{{< ref "e-commerce/products" >}}" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
            Products Report
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
            </svg>
          </a>
        </div>
      </div>
    </div>
    <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:space-y-6 md:p-6">
      <div class="flex w-full items-start justify-between">
        <div class="flex-col items-center">
          <div class="mb-1 flex items-center">
            <h3 class="me-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Website traffic</h3>
            <svg
              data-popover-target="chart-info"
              data-popover-placement="bottom"
              class="ms-0.5 h-4 w-4 cursor-pointer text-gray-400 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
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
                <a href="#" class="flex items-center font-medium text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-500 dark:hover:text-primary-600"
                  >Read more
                  <svg class="ms-1.5 h-2 w-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                  </svg>
                </a>
              </div>
              <div data-popper-arrow></div>
            </div>
          </div>
          <button
            id="dateRangeButton"
            data-dropdown-toggle="dateRangeDropdown"
            data-dropdown-ignore-click-outside-class="datepicker"
            type="button"
            class="inline-flex items-center font-medium text-primary-700 hover:underline dark:text-primary-600"
          >
            31 Nov - 31 Dev
            <svg class="ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>

          <div id="dateRangeDropdown" date-rangepicker class="z-10 hidden w-80 divide-y divide-gray-100 rounded-lg bg-white p-2 shadow-sm dark:divide-gray-600 dark:bg-gray-700 lg:w-96">
            <div class="flex items-center">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Start date"
                />
              </div>
              <span class="mx-4 text-gray-500">to</span>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="End date"
                />
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
            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h.01m6 0h.01m5.99 0h.01" />
            </svg>
            <span class="sr-only">Open dropdown</span>
          </button>
          <div id="widgetDropdown" class="z-10 hidden w-48 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="widgetDropdownButton">
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z"
                      clip-rule="evenodd"
                    />
                    <path
                      fill-rule="evenodd"
                      d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Edit widget
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd" />
                  </svg>
                  Download data
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M12.006 2a9.847 9.847 0 0 0-6.484 2.44 10.32 10.32 0 0 0-3.393 6.17 10.48 10.48 0 0 0 1.317 6.955 10.045 10.045 0 0 0 5.4 4.418c.504.095.683-.223.683-.494 0-.245-.01-1.052-.014-1.908-2.78.62-3.366-1.21-3.366-1.21a2.711 2.711 0 0 0-1.11-1.5c-.907-.637.07-.621.07-.621.317.044.62.163.885.346.266.183.487.426.647.71.135.253.318.476.538.655a2.079 2.079 0 0 0 2.37.196c.045-.52.27-1.006.635-1.37-2.219-.259-4.554-1.138-4.554-5.07a4.022 4.022 0 0 1 1.031-2.75 3.77 3.77 0 0 1 .096-2.713s.839-.275 2.749 1.05a9.26 9.26 0 0 1 5.004 0c1.906-1.325 2.74-1.05 2.74-1.05.37.858.406 1.828.101 2.713a4.017 4.017 0 0 1 1.029 2.75c0 3.939-2.339 4.805-4.564 5.058a2.471 2.471 0 0 1 .679 1.897c0 1.372-.012 2.477-.012 2.814 0 .272.18.592.687.492a10.05 10.05 0 0 0 5.388-4.421 10.473 10.473 0 0 0 1.313-6.948 10.32 10.32 0 0 0-3.39-6.165A9.847 9.847 0 0 0 12.007 2Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Add to repository
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Delete widget
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Line Chart -->
      <div id="website-traffic-chart"></div>

      <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700 sm:pt-6">
        <button class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" type="button" data-dropdown-toggle="website-traffic-dropdown">
          Last 7 days
          <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div class="z-50 my-4 w-40 hidden list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="website-traffic-dropdown" aria-hidden="true" data-popper-placement="bottom">
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
        <div class="shrink-0">
          <a href="{{< ref "homepages/marketing" >}}" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
            Traffic Report
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
            </svg>
          </a>
        </div>
      </div>
    </div>
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-5 flex justify-between">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <h3 class="mb-2 inline-flex items-center font-normal leading-none text-gray-500 dark:text-gray-400">
              Clicks
              <svg
                data-popover-placement="bottom"
                data-popover-target="clicks-info"
                class="ms-1 h-4 w-4 cursor-pointer text-gray-400 hover:text-gray-900 dark:hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z"
                  clip-rule="evenodd"
                />
              </svg>
              <div
                data-popover
                id="clicks-info"
                role="tooltip"
                class="invisible absolute z-10 inline-block w-72 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
              >
                <div class="space-y-2 p-3">
                  <h3 class="font-semibold text-gray-900 dark:text-white">Clicks growth - Incremental</h3>
                  <p>Report helps navigate cumulative growth of community activities. Ideally, the chart should have a growing trend, as stagnating chart signifies a significant decrease of community activity.</p>
                  <h3 class="font-semibold text-gray-900 dark:text-white">Calculation</h3>
                  <p>For each date bucket, the all-time volume of activities is calculated. This means that activities in period n contain all activities up to period n, plus the activities generated by your community in period.</p>
                  <a href="#" class="flex items-center font-medium text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-500 dark:hover:text-primary-600"
                    >Read more
                    <svg class="ms-1.5 h-2 w-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                    </svg>
                  </a>
                </div>
                <div data-popper-arrow></div>
              </div>
            </h3>
            <p class="text-2xl font-bold leading-none text-gray-900 dark:text-white">42,3k</p>
          </div>
          <div>
            <h3 class="mb-2 inline-flex items-center font-normal leading-none text-gray-500 dark:text-gray-400">
              CPC
              <svg
                data-popover-target="cpc-info"
                data-popover-placement="bottom"
                class="ms-1 h-4 w-4 cursor-pointer text-gray-400 hover:text-gray-900 dark:hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z"
                  clip-rule="evenodd"
                />
              </svg>
              <div
                data-popover
                id="cpc-info"
                role="tooltip"
                class="invisible absolute z-10 inline-block w-72 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
              >
                <div class="space-y-2 p-3">
                  <h3 class="font-semibold text-gray-900 dark:text-white">CPC growth - Incremental</h3>
                  <p>Report helps navigate cumulative growth of community activities. Ideally, the chart should have a growing trend, as stagnating chart signifies a significant decrease of community activity.</p>
                  <h3 class="font-semibold text-gray-900 dark:text-white">Calculation</h3>
                  <p>For each date bucket, the all-time volume of activities is calculated. This means that activities in period n contain all activities up to period n, plus the activities generated by your community in period.</p>
                  <a href="#" class="flex items-center font-medium text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-500 dark:hover:text-primary-600"
                    >Read more
                    <svg class="ms-1.5 h-2 w-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                    </svg>
                  </a>
                </div>
                <div data-popper-arrow></div>
              </div>
            </h3>
            <p class="text-2xl font-bold leading-none text-gray-900 dark:text-white">$5.40</p>
          </div>
        </div>
        <div>
          <button
            id="dropdownDefaultButton"
            data-dropdown-toggle="lastDaysdropdown"
            data-dropdown-placement="bottom"
            type="button"
            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            Last week
            <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
            </svg>
          </button>
          <div id="lastDaysdropdown" class="z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownDefaultButton">
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Today</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 7 days</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 30 days</button>
              </li>
              <li>
                <button type="button"class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 90 days</button>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div id="cost-per-click-chart"></div>
      <div class="mt-2.5 grid grid-cols-1 items-center justify-between border-t border-gray-200 dark:border-gray-700">
        <div class="pt-5">
          <a
            href="{{< ref "homepages/marketing" >}}"
            class="inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm-1 9a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0v-2Zm2-5a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm4 4a1 1 0 1 0-2 0v3a1 1 0 1 0 2 0v-3Z"
                clip-rule="evenodd"
              />
            </svg>

            View full report
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="my-4 grid grid-cols-1 xl:gap-4 2xl:grid-cols-3">
    <div class="col-span-2 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700 md:mb-6 md:pb-6">
        <h5 class="me-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Smart Chat</h5>
        <a href="#" class="inline-flex items-center rounded-lg px-3 py-2 font-medium text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"> View all </a>
      </div>
      <!-- Chat -->
      <form class="overflow-y-auto lg:max-h-[60rem] 2xl:max-h-fit mb-4">
        <article class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700 md:p-6">
          <footer class="mb-2 flex items-center justify-between">
            <div class="flex items-center">
              <a href="#" class="me-3 inline-flex items-center text-sm font-semibold text-gray-900 hover:underline dark:text-white">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie avatar" />Bonnie Green
              </a>
              <p class="text-sm text-gray-600 dark:text-gray-400"><time pubdate="" datetime="2025-02-08" title="February 8th, 2025"> 01/03/2025 4:15 PM</time></p>
            </div>
            <button
              id="dropdownComment2Button"
              data-dropdown-toggle="dropdownComment2"
              class="inline-flex items-center rounded-lg bg-gray-50 p-1.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-gray-300 dark:focus:ring-gray-600"
              type="button"
            >
              <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h.01m6 0h.01m5.99 0h.01" />
              </svg>

              <span class="sr-only">Comment settings</span>
            </button>
            <!-- Dropdown menu -->
            <div id="dropdownComment2" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm  dark:bg-gray-600" data-popper-placement="bottom">
              <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconHorizontalButton">
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                    </svg>                    
                    Edit
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"/>
                    </svg>                    
                    Report
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                    </svg>
                    Remove
                  </button>
                </li>
              </ul>
            </div>
          </footer>
          <p class="mb-3 text-gray-900 dark:text-white">Hello everyone,</p>
          <p class="mb-2 text-gray-900 dark:text-white">
            Thank you for the workshop, it was very productive meeting. I can't wait to start working on this new project with you guys. But first things first, I'am waiting for the offer and pitch deck from you. It would be great to get it
            by the end o the month.
          </p>
          <p class="mb-3 text-gray-900 dark:text-white">Cheers!</p>
          <div class="flex items-center space-x-2">
            <button type="button" class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
              <svg aria-hidden="true" class="me-2 h-5" viewBox="0 0 24 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M24 14.1907C24 12.7352 23.7409 11.3397 23.2659 10.0486C22.9412 13.8526 20.9132 15.8065 18.7941 14.8966C16.8092 14.0439 18.1468 10.7199 18.2456 9.13377C18.4122 6.44506 18.2372 3.36742 13.3532 0.808594C15.3826 4.69095 13.5882 7.10295 11.7064 7.24977C9.61835 7.41283 7.70612 5.45542 8.412 2.27895C6.12635 3.96318 6.06 6.79801 6.76518 8.63189C7.50071 10.5434 6.73553 12.1317 4.94188 12.3081C2.93718 12.5058 1.82329 10.1615 2.85035 6.42601C1.07294 8.51895 0 11.2295 0 14.1907C0 20.8182 5.37247 26.1907 12 26.1907C18.6275 26.1907 24 20.8182 24 14.1907Z"
                  fill="#F4900C"
                ></path>
                <path
                  d="M19.3349 17.7211C19.4393 19.8981 17.5271 20.7515 16.4979 20.3393C15.0113 19.7442 15.4102 18.7221 15.0276 16.6044C14.645 14.4868 13.1746 13.0164 10.9984 12.3691C12.5866 16.8395 10.1182 18.487 8.82428 18.7814C7.50287 19.0821 6.17511 18.7807 6.02334 15.9529C4.4817 17.4875 3.52734 19.6108 3.52734 21.9571C3.52734 22.2169 3.54358 22.4724 3.56617 22.7266C5.73323 24.8682 8.70993 26.1924 11.9979 26.1924C15.2859 26.1924 18.2626 24.8682 20.4297 22.7266C20.4523 22.4724 20.4685 22.2169 20.4685 21.9571C20.4685 20.4134 20.0563 18.967 19.3349 17.7211Z"
                  fill="#FFCC4D"
                ></path>
              </svg>
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">14</span>
            </button>
            <button type="button" class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
              <svg aria-hidden="true" class="me-2 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M3.24467 6.07733C3.87207 5.63882 4.81784 5.72291 5.38116 6.18812L4.73508 5.24903C4.21514 4.5075 4.40135 3.70523 5.14355 3.18462C5.88575 2.66601 7.98887 4.06031 7.98887 4.06031C7.46426 3.31143 7.56238 2.36233 8.31125 1.83705C9.06012 1.31377 10.0933 1.49465 10.6179 2.24486L17.5727 12.0697L16.6864 20.663L9.28906 17.9652L2.83686 8.3987C2.30758 7.64516 2.49046 6.60594 3.24467 6.07733Z"
                  fill="#EF9645"
                ></path>
                <path
                  d="M1.79857 11.5433C1.79857 11.5433 1.04302 10.442 2.14497 9.68715C3.24559 8.93226 4.00047 10.0329 4.00047 10.0329L7.50523 15.1442C7.62603 14.9426 7.75819 14.7437 7.90569 14.5475L3.04135 7.45454C3.04135 7.45454 2.28647 6.35392 3.38775 5.59904C4.48837 4.84416 5.24325 5.94478 5.24325 5.94478L9.81859 12.6172C9.98879 12.4784 10.163 12.3389 10.3425 12.2021L5.03835 4.46572C5.03835 4.46572 4.28347 3.3651 5.38475 2.61022C6.48537 1.85534 7.24025 2.95596 7.24025 2.95596L12.5444 10.691C12.7393 10.5715 12.9322 10.4681 13.1258 10.3586L8.168 3.12883C8.168 3.12883 7.41312 2.02821 8.51373 1.27333C9.61435 0.518448 10.3692 1.61907 10.3692 1.61907L15.6113 9.26398L16.4083 10.4267C13.1058 12.692 12.7914 16.9536 14.6783 19.7055C15.0554 20.2561 15.606 19.879 15.606 19.879C13.3414 16.5758 14.0328 12.8642 17.336 10.5995L16.3622 5.72586C16.3622 5.72586 15.9985 4.44169 17.282 4.07727C18.5661 3.71351 18.9306 4.99767 18.9306 4.99767L20.0552 8.33757C20.5011 9.66178 20.9756 10.9813 21.6037 12.2294C23.3771 15.7536 22.3178 20.1333 18.9739 22.4273C15.3263 24.9283 10.3399 23.9985 7.83828 20.3516L1.79857 11.5433Z"
                  fill="#FFDC5D"
                ></path>
                <path
                  d="M8.00913 21.3583C5.33934 21.3583 2.64153 18.6605 2.64153 15.9907C2.64153 15.6216 2.37122 15.3232 2.00212 15.3232C1.63302 15.3232 1.30664 15.6216 1.30664 15.9907C1.30664 19.9954 4.00445 22.6932 8.00913 22.6932C8.37822 22.6932 8.67657 22.3668 8.67657 21.9977C8.67657 21.6286 8.37822 21.3583 8.00913 21.3583Z"
                  fill="#5DADEC"
                ></path>
                <path
                  d="M4.67212 22.6649C2.66978 22.6649 1.33489 21.33 1.33489 19.3277C1.33489 18.9586 1.03654 18.6602 0.667445 18.6602C0.298348 18.6602 0 18.9586 0 19.3277C0 21.9974 2.00234 23.9998 4.67212 23.9998C5.04121 23.9998 5.33956 23.7014 5.33956 23.3323C5.33956 22.9632 5.04121 22.6649 4.67212 22.6649ZM16.0187 1.30664C15.6503 1.30664 15.3512 1.60566 15.3512 1.97409C15.3512 2.34252 15.6503 2.64153 16.0187 2.64153C18.6885 2.64153 21.3583 5.03699 21.3583 7.98109C21.3583 8.34952 21.6573 8.64854 22.0257 8.64854C22.3941 8.64854 22.6931 8.34952 22.6931 7.98109C22.6931 4.3008 20.0234 1.30664 16.0187 1.30664Z"
                  fill="#5DADEC"
                ></path>
                <path
                  d="M19.3559 0C18.9875 0 18.6885 0.270983 18.6885 0.639413C18.6885 1.00784 18.9875 1.33489 19.3559 1.33489C21.3583 1.33489 22.6651 2.81996 22.6651 4.64408C22.6651 5.01251 22.9915 5.31153 23.3606 5.31153C23.7297 5.31153 24 5.01251 24 4.64408C24 2.0831 22.0257 0 19.3559 0Z"
                  fill="#5DADEC"
                ></path>
              </svg>
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">8</span>
            </button>
            <button type="button" class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
              <svg aria-hidden="true" class="me-2 h-5" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M7.75255 5.29787C7.67789 5.37254 7.62122 5.46254 7.57388 5.56121L7.56855 5.55587L0.0910439 22.4003L0.0983774 22.4076C-0.0402924 22.6763 0.191713 23.223 0.667057 23.699C1.1424 24.1743 1.68908 24.4063 1.95775 24.2676L1.96442 24.2743L18.8088 16.7961L18.8035 16.7901C18.9015 16.7435 18.9915 16.6868 19.0668 16.6108C20.1082 15.5694 18.4195 12.1927 15.2961 9.06862C12.1713 5.94455 8.79458 4.25651 7.75255 5.29787Z"
                  fill="#DD2E44"
                ></path>
                <path
                  d="M8.66858 8.30273L0.279048 21.9737L0.0910439 22.3971L0.0983774 22.4044C-0.0402924 22.6731 0.191713 23.2197 0.667057 23.6958C0.821728 23.8504 0.982398 23.9678 1.13973 24.0671L11.3353 11.6361L8.66858 8.30273Z"
                  fill="#EA596E"
                ></path>
                <path
                  d="M15.3439 9.01304C18.4573 12.1278 20.186 15.4479 19.2033 16.4292C18.2213 17.4119 14.9012 15.6839 11.7858 12.5705C8.67174 9.45572 6.9437 6.13431 7.92573 5.15228C8.90841 4.17026 12.2285 5.8983 15.3439 9.01304Z"
                  fill="#A0041E"
                ></path>
                <path
                  d="M12.3913 9.37694C12.2587 9.48427 12.0853 9.54028 11.902 9.52028C11.3233 9.45761 10.8366 9.25627 10.496 8.93826C10.1353 8.60159 9.95727 8.14958 10.0059 7.6969C10.0913 6.90221 10.8886 6.17286 12.248 6.31953C12.7767 6.3762 13.0127 6.2062 13.0207 6.12486C13.03 6.04419 12.836 5.82752 12.3073 5.77019C11.7286 5.70752 11.242 5.50618 10.9006 5.18817C10.54 4.8515 10.3613 4.39949 10.4106 3.94681C10.4973 3.15213 11.294 2.42278 12.652 2.57011C13.0373 2.61145 13.2407 2.53211 13.3267 2.48078C13.3954 2.43878 13.4227 2.39878 13.4254 2.37544C13.4334 2.29477 13.242 2.0781 12.712 2.02077C12.346 1.98077 12.0807 1.65276 12.1213 1.28608C12.1607 0.920076 12.488 0.655404 12.8553 0.695405C14.2134 0.841408 14.8374 1.72343 14.7514 2.51878C14.6647 3.3148 13.868 4.04281 12.5087 3.89681C12.1233 3.85481 11.922 3.93481 11.8353 3.98615C11.7666 4.02748 11.7386 4.06815 11.736 4.09082C11.7273 4.17215 11.92 4.38816 12.45 4.44549C13.808 4.59216 14.432 5.47351 14.346 6.26887C14.26 7.06355 13.4634 7.7929 12.1047 7.64557C11.7193 7.60423 11.5166 7.68423 11.43 7.7349C11.3606 7.77757 11.334 7.81757 11.3313 7.84024C11.3226 7.9209 11.5153 8.13758 12.0447 8.19491C12.41 8.23491 12.676 8.56359 12.6353 8.92959C12.6167 9.11226 12.524 9.27027 12.3913 9.37694Z"
                  fill="#AA8DD8"
                ></path>
                <path
                  d="M20.4411 15.5411C21.7565 15.1698 22.6638 15.7565 22.8798 16.5265C23.0958 17.2958 22.6278 18.2699 21.3131 18.6399C20.7998 18.7839 20.6458 19.0292 20.6665 19.1072C20.6891 19.1859 20.9498 19.3152 21.4618 19.1706C22.7765 18.8005 23.6839 19.3872 23.8999 20.1566C24.1172 20.9266 23.6479 21.8993 22.3325 22.27C21.8198 22.414 21.6651 22.66 21.6878 22.738C21.7098 22.816 21.9698 22.9453 22.4825 22.8013C22.8358 22.702 23.2052 22.908 23.3045 23.262C23.4032 23.6167 23.1972 23.9847 22.8425 24.0847C21.5285 24.4547 20.6205 23.8693 20.4031 23.0986C20.1871 22.3293 20.6558 21.3566 21.9718 20.9859C22.4852 20.8413 22.6392 20.5966 22.6165 20.5179C22.5952 20.4399 22.3352 20.3099 21.8232 20.4539C20.5071 20.8246 19.6004 20.2392 19.3838 19.4679C19.1671 18.6985 19.6358 17.7259 20.9511 17.3545C21.4631 17.2112 21.6171 16.9645 21.5958 16.8872C21.5731 16.8085 21.3138 16.6792 20.8011 16.8232C20.4465 16.9232 20.0791 16.7165 19.9791 16.3625C19.8798 16.0092 20.0864 15.6411 20.4411 15.5411Z"
                  fill="#77B255"
                ></path>
                <path
                  d="M15.3333 13.7449C15.1373 13.7449 14.9439 13.6589 14.8119 13.4949C14.5819 13.2069 14.6292 12.7875 14.9159 12.5575C15.0612 12.4409 18.528 9.71812 23.4274 10.4188C23.7921 10.4708 24.0455 10.8081 23.9935 11.1728C23.9415 11.5368 23.6068 11.7928 23.2388 11.7382C18.91 11.1235 15.7806 13.5742 15.7499 13.5989C15.6259 13.6975 15.4793 13.7449 15.3333 13.7449Z"
                  fill="#AA8DD8"
                ></path>
                <path
                  d="M3.83539 10.9697C3.77205 10.9697 3.70739 10.9604 3.64338 10.9417C3.29071 10.8357 3.0907 10.4643 3.19671 10.1117C3.95206 7.59628 4.63674 3.58219 3.79539 2.5355C3.70138 2.41683 3.55938 2.30016 3.23404 2.32483C2.60869 2.37283 2.66803 3.69219 2.66869 3.70552C2.69669 4.07287 2.42069 4.39287 2.05401 4.42021C1.68134 4.44287 1.36666 4.1722 1.33933 3.80486C1.27066 2.8855 1.55667 1.1148 3.13404 0.995461C3.83805 0.942127 4.42273 1.1868 4.83541 1.70014C6.41611 3.66752 4.81141 9.37099 4.47407 10.495C4.3874 10.7837 4.12206 10.9697 3.83539 10.9697Z"
                  fill="#77B255"
                ></path>
                <path
                  d="M16.999 7.63774C17.5513 7.63774 17.9991 7.19002 17.9991 6.63772C17.9991 6.08542 17.5513 5.6377 16.999 5.6377C16.4467 5.6377 15.999 6.08542 15.999 6.63772C15.999 7.19002 16.4467 7.63774 16.999 7.63774Z"
                  fill="#5C913B"
                ></path>
                <path
                  d="M1.33336 13.6355C2.06976 13.6355 2.66673 13.0385 2.66673 12.3021C2.66673 11.5657 2.06976 10.9688 1.33336 10.9688C0.596967 10.9688 0 11.5657 0 12.3021C0 13.0385 0.596967 13.6355 1.33336 13.6355Z"
                  fill="#9266CC"
                ></path>
                <path
                  d="M21.666 14.3047C22.2183 14.3047 22.6661 13.857 22.6661 13.3047C22.6661 12.7524 22.2183 12.3047 21.666 12.3047C21.1137 12.3047 20.666 12.7524 20.666 13.3047C20.666 13.857 21.1137 14.3047 21.666 14.3047Z"
                  fill="#5C913B"
                ></path>
                <path
                  d="M15.666 22.3038C16.2183 22.3038 16.6661 21.856 16.6661 21.3037C16.6661 20.7514 16.2183 20.3037 15.666 20.3037C15.1137 20.3037 14.666 20.7514 14.666 21.3037C14.666 21.856 15.1137 22.3038 15.666 22.3038Z"
                  fill="#5C913B"
                ></path>
                <path
                  d="M18.6683 4.30052C19.4047 4.30052 20.0017 3.70355 20.0017 2.96715C20.0017 2.23076 19.4047 1.63379 18.6683 1.63379C17.9319 1.63379 17.335 2.23076 17.335 2.96715C17.335 3.70355 17.9319 4.30052 18.6683 4.30052Z"
                  fill="#FFCC4D"
                ></path>
                <path
                  d="M21.6699 6.9688C22.2222 6.9688 22.67 6.52107 22.67 5.96877C22.67 5.41648 22.2222 4.96875 21.6699 4.96875C21.1176 4.96875 20.6699 5.41648 20.6699 5.96877C20.6699 6.52107 21.1176 6.9688 21.6699 6.9688Z"
                  fill="#FFCC4D"
                ></path>
                <path
                  d="M19.668 9.63384C20.2203 9.63384 20.668 9.18611 20.668 8.63381C20.668 8.08151 20.2203 7.63379 19.668 7.63379C19.1157 7.63379 18.668 8.08151 18.668 8.63381C18.668 9.18611 19.1157 9.63384 19.668 9.63384Z"
                  fill="#FFCC4D"
                ></path>
                <path
                  d="M5.00198 16.9668C5.55427 16.9668 6.002 16.5191 6.002 15.9668C6.002 15.4145 5.55427 14.9668 5.00198 14.9668C4.44968 14.9668 4.00195 15.4145 4.00195 15.9668C4.00195 16.5191 4.44968 16.9668 5.00198 16.9668Z"
                  fill="#FFCC4D"
                ></path>
              </svg>
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">3</span>
            </button>
          </div>
        </article>
        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700 md:p-6">
          <article class="mb-4">
            <footer class="mb-2 flex items-center justify-between">
              <div class="flex items-center">
                <a href="#" class="me-3 inline-flex items-center text-sm font-semibold text-gray-900 hover:underline dark:text-white">
                  <img class="me-2 h-6 w-6 rounded-full" src="/images/users/jese-leos.png" alt="Jese avatar" />Jese Leos
                </a>
                <p class="text-sm text-gray-600 dark:text-gray-400"><time pubdate="" datetime="2025-02-08" title="February 8th, 2025"> 01/03/2025 4:15 PM</time></p>
              </div>
              <button
                id="dropdownComment3Button"
                data-dropdown-toggle="dropdownComment3"
                class="inline-flex items-center rounded-lg bg-gray-50 p-1.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-gray-300 dark:focus:ring-gray-600"
                type="button"
              >
                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h.01m6 0h.01m5.99 0h.01" />
                </svg>

                <span class="sr-only">Comment settings</span>
              </button>
              <!-- Dropdown menu -->
              <div id="dropdownComment3" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm  dark:bg-gray-600" data-popper-placement="bottom">
                <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconHorizontalButton">
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                    </svg>                    
                    Edit
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"/>
                    </svg>                    
                    Report
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                    </svg>
                    Remove
                  </button>
                </li>
                </ul>
              </div>
            </footer>
            <p class="mb-2 text-gray-900 dark:text-white">
              Ok <a href="#" class="font-medium text-primary-600 hover:underline dark:text-primary-500">@team</a> I'am attaching our offer and pitch deck. Take your time to review everything. I'am looking forward to the next steps! Thank
              you.
            </p>
            <p class="mb-3 text-gray-900 dark:text-white">Looking forward to it! Thanks.</p>
            <div class="items-center 2xl:flex 2xl:space-x-4">
              <!-- Item -->
              <div class="mb-3.5 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-600">
                <div class="me-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                  <svg class="h-5 w-5 text-primary-700 dark:text-primary-300 lg:h-6 lg:w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="me-4">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">flowbite_offer_345</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">PDF, 2.3 MB</p>
                </div>
                <div class="me-auto flex items-center">
                  <button type="button" data-tooltip-target="tooltip-download" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-white">
                    <svg class="h-5 w-5 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd" />
                      <path
                        fill-rule="evenodd"
                        d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="sr-only">Download</span>
                  </button>
                  <div
                    id="tooltip-download"
                    role="tooltip"
                    class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Download file
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button type="button" data-tooltip-target="tooltip-report" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-white">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                      />
                    </svg>
                    <span class="sr-only">Report</span>
                  </button>
                  <div
                    id="tooltip-report"
                    role="tooltip"
                    class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Report this file
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                </div>
              </div>
              <!-- Item -->
              <div class="mb-3.5 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-600">
                <div class="me-3 flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900">
                  <svg class="h-5 w-5 text-teal-700 dark:text-teal-300 lg:h-6 lg:w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm-2 4a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2H9Zm0 2h2v2H9v-2Zm7.965-.557a1 1 0 0 0-1.692-.72l-1.268 1.218a1 1 0 0 0-.308.721v.733a1 1 0 0 0 .37.776l1.267 1.032a1 1 0 0 0 1.631-.776v-2.984Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="me-4">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">flowbite_showcase</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">MP4, 100.56 MB</p>
                </div>
                <div class="me-auto flex items-center">
                  <button type="button" data-tooltip-target="tooltip-download-2" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-white">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd" />
                      <path
                        fill-rule="evenodd"
                        d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="sr-only">Download</span>
                  </button>
                  <div
                    id="tooltip-download-2"
                    role="tooltip"
                    class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Download file
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button type="button" data-tooltip-target="tooltip-report-2" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-white">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                      />
                    </svg>
                    <span class="sr-only">Report</span>
                  </button>
                  <div
                    id="tooltip-report-2"
                    role="tooltip"
                    class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Report this file
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                </div>
              </div>
            </div>
          </article>
          <article class="mb-4">
            <footer class="mb-2 flex items-center justify-between">
              <div class="flex items-center">
                <a href="#" class="me-3 inline-flex items-center text-sm font-semibold text-gray-900 hover:underline dark:text-white">
                  <img class="me-2 h-6 w-6 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph avatar" />Joseph McFallen
                </a>
                <p class="text-sm text-gray-600 dark:text-gray-400"><time pubdate="" datetime="2025-02-08" title="February 8th, 2025"> 01/03/2025 4:15 PM</time></p>
              </div>
              <button
                id="dropdownComment4Button"
                data-dropdown-toggle="dropdownComment4"
                class="inline-flex items-center rounded-lg bg-gray-50 p-1.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-gray-300 dark:focus:ring-gray-600"
                type="button"
              >
                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h.01m6 0h.01m5.99 0h.01" />
                </svg>

                <span class="sr-only">Comment settings</span>
              </button>
              <!-- Dropdown menu -->
              <div id="dropdownComment4" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm  dark:bg-gray-600" data-popper-placement="bottom">
                <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconHorizontalButton">
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                    </svg>                    
                    Edit
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"/>
                    </svg>                    
                    Report
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                    </svg>
                    Remove
                  </button>
                </li>
                </ul>
              </div>
            </footer>
            <p class="mb-2 text-gray-900 dark:text-white">Hello <a href="#" class="font-medium text-primary-600 hover:underline dark:text-primary-500">@jeseleos</a> I need some informations about flowbite react version.</p>
          </article>
          <article class="mb-4">
            <footer class="mb-2 flex items-center justify-between">
              <div class="flex items-center">
                <a href="#" class="me-3 inline-flex items-center text-sm font-semibold text-gray-900 hover:underline dark:text-white">
                  <img class="me-2 h-6 w-6 rounded-full" src="/images/users/jese-leos.png" alt="Jese avatar" />Jese Leos
                </a>
                <p class="text-sm text-gray-600 dark:text-gray-400"><time pubdate="" datetime="2025-02-08" title="February 8th, 2025"> 01/03/2025 4:15 PM</time></p>
              </div>
              <button
                id="dropdownComment5Button"
                data-dropdown-toggle="dropdownComment5"
                class="inline-flex items-center rounded-lg bg-gray-50 p-1.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-gray-300 dark:focus:ring-gray-600"
                type="button"
              >
                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h.01m6 0h.01m5.99 0h.01" />
                </svg>

                <span class="sr-only">Comment settings</span>
              </button>
              <!-- Dropdown menu -->
              <div id="dropdownComment5" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm  dark:bg-gray-600" data-popper-placement="bottom">
                <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconHorizontalButton">
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                    </svg>                    
                    Edit
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"/>
                    </svg>                    
                    Report
                  </button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                    </svg>
                    Remove
                  </button>
                </li>
                </ul>
              </div>
            </footer>
            <p class="mb-4 text-gray-900 dark:text-white">Hi <a href="#" class="font-medium text-primary-600 hover:underline dark:text-primary-500">@josephh</a> Sure, just let me know whean you are available and we can speak.</p>

            <label for="chat" class="sr-only">Your message</label>
            <div class="mb-5 flex items-center">
              <textarea
                id="chat"
                rows="1"
                class="me-4 block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Reply in thread..."
              ></textarea>
              <button type="submit" class="inline-flex cursor-pointer justify-center rounded-lg p-2 text-primary-600 hover:bg-primary-100 dark:text-primary-500 dark:hover:bg-gray-600">
                <svg aria-hidden="true" class="h-6 w-6 rotate-90" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                </svg>
                <span class="sr-only">Send message</span>
              </button>
            </div>

            <span class="inline-flex cursor-pointer items-center text-xs font-medium text-primary-700 hover:underline dark:text-primary-500 sm:text-sm">
              Hide thread
              <svg class="me-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path clip-rule="evenodd" fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
              </svg>
            </span>
          </article>
        </div>
      </form>

      <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2 block">Write a reply</h3>

      <div class="mb-4 w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
        <div class="border-b px-3 py-2 dark:border-gray-600 border-gray-200">
          <div class="flex flex-wrap items-center">
            <div class="flex flex-wrap items-center space-x-1 rtl:space-x-reverse">
              <button
                id="toggleBoldButton"
                data-tooltip-target="tooltip-bold"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5h4.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0-7H6m2 7h6.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0 0H6" />
                </svg>
                <span class="sr-only">Bold</span>
              </button>
              <div id="tooltip-bold" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Toggle bold
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleItalicButton"
                data-tooltip-target="tooltip-italic"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.874 19 6.143-14M6 19h6.33m-.66-14H18" />
                </svg>
                <span class="sr-only">Italic</span>
              </button>
              <div id="tooltip-italic" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Toggle italic
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleUnderlineButton"
                data-tooltip-target="tooltip-underline"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M6 19h12M8 5v9a4 4 0 0 0 8 0V5M6 5h4m4 0h4" />
                </svg>
                <span class="sr-only">Underline</span>
              </button>
              <div
                id="tooltip-underline"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Toggle underline
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleStrikeButton"
                data-tooltip-target="tooltip-strike"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6.2V5h12v1.2M7 19h6m.2-14-1.677 6.523M9.6 19l1.029-4M5 5l6.523 6.523M19 19l-7.477-7.477" />
                </svg>
                <span class="sr-only">Strike</span>
              </button>
              <div id="tooltip-strike" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Toggle strike
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleHighlightButton"
                data-tooltip-target="tooltip-highlight"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="2"
                    d="M9 19.2H5.5c-.3 0-.5-.2-.5-.5V16c0-.2.2-.4.5-.4h13c.3 0 .5.2.5.4v2.7c0 .3-.2.5-.5.5H18m-6-1 1.4 1.8h.2l1.4-1.7m-7-5.4L12 4c0-.1 0-.1 0 0l4 8.8m-6-2.7h4m-7 2.7h2.5m5 0H17"
                  />
                </svg>
                <span class="sr-only">Highlight</span>
              </button>
              <div
                id="tooltip-highlight"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Toggle highlight
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleCodeButton"
                type="button"
                data-tooltip-target="tooltip-code"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 8-4 4 4 4m8 0 4-4-4-4m-2-3-4 14" />
                </svg>
                <span class="sr-only">Code</span>
              </button>
              <div id="tooltip-code" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Format code
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleLinkButton"
                data-tooltip-target="tooltip-link"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.213 9.787a3.391 3.391 0 0 0-4.795 0l-3.425 3.426a3.39 3.39 0 0 0 4.795 4.794l.321-.304m-.321-4.49a3.39 3.39 0 0 0 4.795 0l3.424-3.426a3.39 3.39 0 0 0-4.794-4.795l-1.028.961"
                  />
                </svg>
                <span class="sr-only">Link</span>
              </button>
              <div id="tooltip-link" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Add link
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="removeLinkButton"
                data-tooltip-target="tooltip-remove-link"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="2"
                    d="M13.2 9.8a3.4 3.4 0 0 0-4.8 0L5 13.2A3.4 3.4 0 0 0 9.8 18l.3-.3m-.3-4.5a3.4 3.4 0 0 0 4.8 0L18 9.8A3.4 3.4 0 0 0 13.2 5l-1 1m7.4 14-1.8-1.8m0 0L16 16.4m1.8 1.8 1.8-1.8m-1.8 1.8L16 20"
                  />
                </svg>
                <span class="sr-only">Remove link</span>
              </button>
              <div
                id="tooltip-remove-link"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Remove link
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleTextSizeButton"
                data-dropdown-toggle="textSizeDropdown"
                type="button"
                data-tooltip-target="tooltip-text-size"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6.2V5h11v1.2M8 5v14m-3 0h6m2-6.8V11h8v1.2M17 11v8m-1.5 0h3" />
                </svg>
                <span class="sr-only">Text size</span>
              </button>
              <div
                id="tooltip-text-size"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Text size
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <div id="textSizeDropdown" class="z-10 hidden w-72 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                <ul class="space-y-1 text-sm font-medium" aria-labelledby="toggleTextSizeButton">
                  <li>
                    <button data-text-size="16px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">16px (Default)</button>
                  </li>
                  <li>
                    <button data-text-size="12px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-xs text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">12px (Tiny)</button>
                  </li>
                  <li>
                    <button data-text-size="14px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">14px (Small)</button>
                  </li>
                  <li>
                    <button data-text-size="18px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-lg text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">18px (Lead)</button>
                  </li>
                  <li>
                    <button data-text-size="24px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-2xl text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">24px (Large)</button>
                  </li>
                  <li>
                    <button data-text-size="36px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-4xl text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">36px (Huge)</button>
                  </li>
                </ul>
              </div>
              <button
                id="toggleTextColorButton"
                data-dropdown-toggle="textColorDropdown"
                type="button"
                data-tooltip-target="tooltip-text-color"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="25" height="24" fill="none" viewBox="0 0 25 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="2"
                    d="m6.532 15.982 1.573-4m-1.573 4h-1.1m1.1 0h1.65m-.077-4 2.725-6.93a.11.11 0 0 1 .204 0l2.725 6.93m-5.654 0H8.1m.006 0h5.654m0 0 .617 1.569m5.11 4.453c0 1.102-.854 1.996-1.908 1.996-1.053 0-1.907-.894-1.907-1.996 0-1.103 1.907-4.128 1.907-4.128s1.909 3.025 1.909 4.128Z"
                  />
                </svg>
                <span class="sr-only">Text color</span>
              </button>
              <div
                id="tooltip-text-color"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Text color
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <div id="textColorDropdown" class="z-10 hidden w-48 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                <div class="group mb-3 grid grid-cols-6 items-center gap-2 rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-600">
                  <input
                    type="color"
                    id="color"
                    value="#e66465"
                    class="col-span-3 h-8 w-full rounded-md border border-gray-200 bg-gray-50 p-px px-1 hover:bg-gray-50 group-hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:group-hover:bg-gray-700"
                  />
                  <label for="color" class="col-span-3 text-sm font-medium text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white">Pick a color</label>
                </div>
                <div class="mb-3 grid grid-cols-6 gap-1">
                  <button type="button" data-hex-color="#1A56DB" style="background-color: #1A56DB" class="h-6 w-6 rounded-md"><span class="sr-only">Blue</span></button>
                  <button type="button" data-hex-color="#0E9F6E" style="background-color: #0E9F6E" class="h-6 w-6 rounded-md"><span class="sr-only">Green</span></button>
                  <button type="button" data-hex-color="#FACA15" style="background-color: #FACA15" class="h-6 w-6 rounded-md"><span class="sr-only">Yellow</span></button>
                  <button type="button" data-hex-color="#F05252" style="background-color: #F05252" class="h-6 w-6 rounded-md"><span class="sr-only">Red</span></button>
                  <button type="button" data-hex-color="#FF8A4C" style="background-color: #FF8A4C" class="h-6 w-6 rounded-md"><span class="sr-only">Orange</span></button>
                  <button type="button" data-hex-color="#0694A2" style="background-color: #0694A2" class="h-6 w-6 rounded-md"><span class="sr-only">Teal</span></button>
                  <button type="button" data-hex-color="#B4C6FC" style="background-color: #B4C6FC" class="h-6 w-6 rounded-md"><span class="sr-only">Light indigo</span></button>
                  <button type="button" data-hex-color="#8DA2FB" style="background-color: #8DA2FB" class="h-6 w-6 rounded-md"><span class="sr-only">Indigo</span></button>
                  <button type="button" data-hex-color="#5145CD" style="background-color: #5145CD" class="h-6 w-6 rounded-md"><span class="sr-only">Purple</span></button>
                  <button type="button" data-hex-color="#771D1D" style="background-color: #771D1D" class="h-6 w-6 rounded-md"><span class="sr-only">Brown</span></button>
                  <button type="button" data-hex-color="#FCD9BD" style="background-color: #FCD9BD" class="h-6 w-6 rounded-md"><span class="sr-only">Light orange</span></button>
                  <button type="button" data-hex-color="#99154B" style="background-color: #99154B" class="h-6 w-6 rounded-md"><span class="sr-only">Bordo</span></button>
                  <button type="button" data-hex-color="#7E3AF2" style="background-color: #7E3AF2" class="h-6 w-6 rounded-md"><span class="sr-only">Dark Purple</span></button>
                  <button type="button" data-hex-color="#CABFFD" style="background-color: #CABFFD" class="h-6 w-6 rounded-md"><span class="sr-only">Light</span></button>
                  <button type="button" data-hex-color="#D61F69" style="background-color: #D61F69" class="h-6 w-6 rounded-md"><span class="sr-only">Dark Pink</span></button>
                  <button type="button" data-hex-color="#F8B4D9" style="background-color: #F8B4D9" class="h-6 w-6 rounded-md"><span class="sr-only">Pink</span></button>
                  <button type="button" data-hex-color="#F6C196" style="background-color: #F6C196" class="h-6 w-6 rounded-md"><span class="sr-only">Cream</span></button>
                  <button type="button" data-hex-color="#A4CAFE" style="background-color: #A4CAFE" class="h-6 w-6 rounded-md"><span class="sr-only">Light Blue</span></button>
                  <button type="button" data-hex-color="#5145CD" style="background-color: #5145CD" class="h-6 w-6 rounded-md"><span class="sr-only">Dark Blue</span></button>
                  <button type="button" data-hex-color="#B43403" style="background-color: #B43403" class="h-6 w-6 rounded-md"><span class="sr-only">Orange Brown</span></button>
                  <button type="button" data-hex-color="#FCE96A" style="background-color: #FCE96A" class="h-6 w-6 rounded-md"><span class="sr-only">Light Yellow</span></button>
                  <button type="button" data-hex-color="#1E429F" style="background-color: #1E429F" class="h-6 w-6 rounded-md"><span class="sr-only">Navy Blue</span></button>
                  <button type="button" data-hex-color="#768FFD" style="background-color: #768FFD" class="h-6 w-6 rounded-md"><span class="sr-only">Light Purple</span></button>
                  <button type="button" data-hex-color="#BCF0DA" style="background-color: #BCF0DA" class="h-6 w-6 rounded-md"><span class="sr-only">Light Green</span></button>
                  <button type="button" data-hex-color="#EBF5FF" style="background-color: #EBF5FF" class="h-6 w-6 rounded-md"><span class="sr-only">Sky Blue</span></button>
                  <button type="button" data-hex-color="#16BDCA" style="background-color: #16BDCA" class="h-6 w-6 rounded-md"><span class="sr-only">Cyan</span></button>
                  <button type="button" data-hex-color="#E74694" style="background-color: #E74694" class="h-6 w-6 rounded-md"><span class="sr-only">Pink</span></button>
                  <button type="button" data-hex-color="#83B0ED" style="background-color: #83B0ED" class="h-6 w-6 rounded-md"><span class="sr-only">Darker Sky Blue</span></button>
                  <button type="button" data-hex-color="#03543F" style="background-color: #03543F" class="h-6 w-6 rounded-md"><span class="sr-only">Forest Green</span></button>
                  <button type="button" data-hex-color="#111928" style="background-color: #111928" class="h-6 w-6 rounded-md"><span class="sr-only">Black</span></button>
                  <button type="button" data-hex-color="#4B5563" style="background-color: #4B5563" class="h-6 w-6 rounded-md"><span class="sr-only">Stone</span></button>
                  <button type="button" data-hex-color="#6B7280" style="background-color: #6B7280" class="h-6 w-6 rounded-md"><span class="sr-only">Gray</span></button>
                  <button type="button" data-hex-color="#D1D5DB" style="background-color: #D1D5DB" class="h-6 w-6 rounded-md"><span class="sr-only">Light Gray</span></button>
                  <button type="button" data-hex-color="#F3F4F6" style="background-color: #F3F4F6" class="h-6 w-6 rounded-md"><span class="sr-only">Cloud Gray</span></button>
                  <button type="button" data-hex-color="#F3F4F6" style="background-color: #F3F4F6" class="h-6 w-6 rounded-md"><span class="sr-only">Cloud Gray</span></button>
                  <button type="button" data-hex-color="#F9FAFB" style="background-color: #F9FAFB" class="h-6 w-6 rounded-md"><span class="sr-only">Heaven Gray</span></button>
                </div>
                <button
                  type="button"
                  id="reset-color"
                  class="w-full rounded-lg bg-white py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Reset color
                </button>
              </div>
              <button
                id="toggleFontFamilyButton"
                data-dropdown-toggle="fontFamilyDropdown"
                type="button"
                data-tooltip-target="tooltip-font-family"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="m10.6 19 4.298-10.93a.11.11 0 0 1 .204 0L19.4 19m-8.8 0H9.5m1.1 0h1.65m7.15 0h-1.65m1.65 0h1.1m-7.7-3.985h4.4M3.021 16l1.567-3.985m0 0L7.32 5.07a.11.11 0 0 1 .205 0l2.503 6.945h-5.44Z"
                  />
                </svg>
                <span class="sr-only">Font family</span>
              </button>
              <div
                id="tooltip-font-family"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Font Family
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <div id="fontFamilyDropdown" class="z-10 hidden w-48 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                <ul class="space-y-1 text-sm font-medium" aria-labelledby="toggleFontFamilyButton">
                  <li>
                    <button data-font-family="Inter, ui-sans-serif" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 font-sans text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                      Default
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Arial, sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Arial, sans-serif;"
                    >
                      Arial
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Courier New', monospace"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Courier New', monospace;"
                    >
                      Courier New
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Georgia, serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Georgia, serif;"
                    >
                      Georgia
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Lucida Sans Unicode', sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Lucida Sans Unicode', sans-serif;"
                    >
                      Lucida Sans Unicode
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Tahoma, sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Tahoma, sans-serif;"
                    >
                      Tahoma
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Times New Roman', serif;"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Times New Roman', serif;"
                    >
                      Times New Roman
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Trebuchet MS', sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Trebuchet MS', sans-serif;"
                    >
                      Trebuchet MS
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Verdana, sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Verdana, sans-serif;"
                    >
                      Verdana
                    </button>
                  </li>
                </ul>
              </div>
              <div class="px-1">
                <span class="block h-4 w-px bg-gray-300 dark:bg-gray-600"></span>
              </div>
              <button
                id="toggleLeftAlignButton"
                type="button"
                data-tooltip-target="tooltip-left-align"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h8m-8 4h12M6 14h8m-8 4h12" />
                </svg>
                <span class="sr-only">Align left</span>
              </button>
              <div
                id="tooltip-left-align"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Align left
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleCenterAlignButton"
                type="button"
                data-tooltip-target="tooltip-center-align"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M6 10h12M8 14h8M6 18h12" />
                </svg>
                <span class="sr-only">Align center</span>
              </button>
              <div
                id="tooltip-center-align"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Align center
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleRightAlignButton"
                type="button"
                data-tooltip-target="tooltip-right-align"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6h-8m8 4H6m12 4h-8m8 4H6" />
                </svg>
                <span class="sr-only">Align right</span>
              </button>
              <div
                id="tooltip-right-align"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Align right
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2 pt-2">
            <button
              id="typographyDropdownButton"
              data-dropdown-toggle="typographyDropdown"
              class="flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-600 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-white dark:focus:ring-gray-600"
              type="button"
            >
              Format
              <svg class="-me-0.5 ms-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <div class="ps-1.5">
              <span class="block h-4 w-px bg-gray-300 dark:bg-gray-600"></span>
            </div>
            <!-- Heading Dropdown -->
            <div id="typographyDropdown" class="z-10 hidden w-72 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
              <ul class="space-y-1 text-sm font-medium" aria-labelledby="typographyDropdownButton">
                <li>
                  <button id="toggleParagraphButton" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Paragraph
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">0</kbd>
                    </div>
                  </button>
                </li>
                <li>
                  <button data-heading-level="1" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Heading 1
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">1</kbd>
                    </div>
                  </button>
                </li>
                <li>
                  <button data-heading-level="2" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Heading 2
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">2</kbd>
                    </div>
                  </button>
                </li>
                <li>
                  <button data-heading-level="3" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Heading 3
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">3</kbd>
                    </div>
                  </button>
                </li>
                <li>
                  <button data-heading-level="4" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Heading 4
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">4</kbd>
                    </div>
                  </button>
                </li>
                <li>
                  <button data-heading-level="5" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Heading 5
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">5</kbd>
                    </div>
                  </button>
                </li>
                <li>
                  <button data-heading-level="6" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                    Heading 6
                    <div class="space-x-1.5">
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                      <kbd class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">6</kbd>
                    </div>
                  </button>
                </li>
              </ul>
            </div>
            <button
              id="addImageButton"
              type="button"
              data-tooltip-target="tooltip-image"
              class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M13 10a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H14a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                <path
                  fill-rule="evenodd"
                  d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12c0 .556-.227 1.06-.593 1.422A.999.999 0 0 1 20.5 20H4a2.002 2.002 0 0 1-2-2V6Zm6.892 12 3.833-5.356-3.99-4.322a1 1 0 0 0-1.549.097L4 12.879V6h16v9.95l-3.257-3.619a1 1 0 0 0-1.557.088L11.2 18H8.892Z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="sr-only">Add image</span>
            </button>
            <div id="tooltip-image" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Add image
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <button
              id="addVideoButton"
              type="button"
              data-tooltip-target="tooltip-video"
              class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm-2 4a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2H9Zm0 2h2v2H9v-2Zm7.965-.557a1 1 0 0 0-1.692-.72l-1.268 1.218a1 1 0 0 0-.308.721v.733a1 1 0 0 0 .37.776l1.267 1.032a1 1 0 0 0 1.631-.776v-2.984Z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="sr-only">Add video</span>
            </button>
            <div id="tooltip-video" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Add video
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <button
              id="toggleListButton"
              type="button"
              data-tooltip-target="tooltip-list"
              class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5" />
              </svg>
              <span class="sr-only">Toggle list</span>
            </button>
            <div id="tooltip-list" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Toggle list
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <button
              id="toggleOrderedListButton"
              type="button"
              data-tooltip-target="tooltip-ordered-list"
              class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6h8m-8 6h8m-8 6h8M4 16a2 2 0 1 1 3.321 1.5L4 20h5M4 5l2-1v6m-2 0h4" />
              </svg>
              <span class="sr-only">Toggle ordered list</span>
            </button>
            <div
              id="tooltip-ordered-list"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              Toggle ordered list
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <button
              id="toggleBlockquoteButton"
              type="button"
              data-tooltip-target="tooltip-blockquote-list"
              class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M6 6a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3a3 3 0 0 1-3 3H5a1 1 0 1 0 0 2h1a5 5 0 0 0 5-5V8a2 2 0 0 0-2-2H6Zm9 0a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3a3 3 0 0 1-3 3h-1a1 1 0 1 0 0 2h1a5 5 0 0 0 5-5V8a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="sr-only">Toggle blockquote</span>
            </button>
            <div
              id="tooltip-blockquote-list"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              Toggle blockquote
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <button
              id="toggleHRButton"
              type="button"
              data-tooltip-target="tooltip-hr-list"
              class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 12h14" />
                <path stroke="currentColor" stroke-linecap="round" d="M6 9.5h12m-12 9h12M6 7.5h12m-12 9h12M6 5.5h12m-12 9h12" />
              </svg>
              <span class="sr-only">Toggle Horizontal Rule</span>
            </button>
            <div id="tooltip-hr-list" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Toggle Horizontal Rule
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
        </div>
        <div class="rounded-b-lg bg-white px-4 py-2 dark:bg-gray-800">
          <label for="wysiwyg-example" class="sr-only">Publish post</label>
          <div id="wysiwyg-example" class="block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"></div>
        </div>
      </div>
      <button
        type="button"
        class="inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Send your message
      </button>
    </div>
    <!-- Right Content -->
    <div class="grid grid-cols-1 gap-4">
      <!-- Timeline -->
      <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
        <h3 class="mb-4 me-1 border-b border-gray-200 pb-4 text-xl font-bold leading-none text-gray-900 dark:border-gray-700 dark:text-white md:mb-6 md:pb-6">Latest activity</h3>
        <div class="mb-4">
          <time class="text-lg font-semibold text-gray-900 dark:text-white">December 16th, 2025</time>
          <ol class="divide-gray-200 mt-3 divide-y dark:divide-gray-700">
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/jese-leos.png" alt="Jese Leos image" />
                <div class="text-gray-600 dark:text-gray-400">
                  <div class="text-base font-normal">
                    <span class="font-medium text-gray-900 dark:text-white">Jese Leos</span> likes <span class="font-medium text-gray-900 dark:text-white">Bonnie Green's</span> post in
                    <span class="font-medium text-gray-900 dark:text-white"> How to start with Flowbite library</span>
                  </div>
                  <div class="text-sm font-normal">"I wanted to share a webinar zeroheight."</div>
                  <span class="inline-flex items-center text-xs font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        d="M10 .5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19ZM8.374 17.4a7.6 7.6 0 0 1-5.9-7.4c0-.83.137-1.655.406-2.441l.239.019a3.887 3.887 0 0 1 2.082 2.5 4.1 4.1 0 0 0 2.441 2.8c1.148.522 1.389 2.007.732 4.522Zm3.6-8.829a.997.997 0 0 0-.027-.225 5.456 5.456 0 0 0-2.811-3.662c-.832-.527-1.347-.854-1.486-1.89a7.584 7.584 0 0 1 8.364 2.47c-1.387.208-2.14 2.237-2.14 3.307a1.187 1.187 0 0 1-1.9 0Zm1.626 8.053-.671-2.013a1.9 1.9 0 0 1 1.771-1.757l2.032.619a7.553 7.553 0 0 1-3.132 3.151Z"
                      />
                    </svg>
                    Public
                  </span>
                </div>
              </a>
            </li>
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/neil-sims.png" alt="Neil Sims image" />
                <div class="text-gray-600 dark:text-gray-400">
                  <div class="text-base font-normal"><span class="font-medium text-gray-900 dark:text-white">Neil Sims</span> is requesting access to the Flowbite database.</div>
                  <div class="mt-2 flex items-center space-x-2">
                    <button
                      type="button"
                      class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
                    >
                      Accept
                    </button>
                    <button
                      type="button"
                      class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
                    >
                      Decline
                    </button>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/bonnie-green.png" alt="Bonnie Green image" />
                <div>
                  <div class="text-base font-normal text-gray-600 dark:text-gray-400">
                    <span class="font-medium text-gray-900 dark:text-white">Bonnie Green</span> react to <span class="font-medium text-gray-900 dark:text-white">Thomas Lean's</span> comment
                  </div>
                  <span class="inline-flex items-center text-xs font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path d="m2 13.587 3.055-3.055A4.913 4.913 0 0 1 5 10a5.006 5.006 0 0 1 5-5c.178.008.356.026.532.054l1.744-1.744A8.973 8.973 0 0 0 10 3C4.612 3 0 8.336 0 10a6.49 6.49 0 0 0 2 3.587Z" />
                      <path
                        d="m12.7 8.714 6.007-6.007a1 1 0 1 0-1.414-1.414L11.286 7.3a2.98 2.98 0 0 0-.588-.21l-.035-.01a2.981 2.981 0 0 0-3.584 3.583c0 .012.008.022.01.033.05.204.12.401.211.59l-6.007 6.007a1 1 0 1 0 1.414 1.414L8.714 12.7c.189.091.386.162.59.211.011 0 .021.007.033.01a2.981 2.981 0 0 0 3.584-3.584c0-.012-.008-.023-.011-.035a3.05 3.05 0 0 0-.21-.588Z"
                      />
                      <path d="M17.821 6.593 14.964 9.45a4.952 4.952 0 0 1-5.514 5.514L7.665 16.75c.767.165 1.55.25 2.335.251 6.453 0 10-5.258 10-7 0-1.166-1.637-2.874-2.179-3.407Z" />
                    </svg>
                    Private
                  </span>
                </div>
              </a>
            </li>
          </ol>
        </div>
        <div class="mb-4">
          <time class="text-lg font-semibold text-gray-900 dark:text-white">December 12th, 2025</time>
          <ol class="divide-gray-200 mt-3 divide-y dark:divide-gray-700">
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/lana-byrd.png" alt="Lana Byrd image" />
                <div class="text-gray-600 dark:text-gray-400">
                  <div class="text-base font-normal">
                    <span class="font-medium text-gray-900 dark:text-white">Lana Byrd</span> likes <span class="font-medium text-gray-900 dark:text-white">Bonnie Green's</span> post in
                    <span class="font-medium text-gray-900 dark:text-white"> How to start with Flowbite library</span>
                  </div>
                  <div class="text-sm font-normal">"I wanted to share a webinar zeroheight."</div>
                  <span class="inline-flex items-center text-xs font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path d="m2 13.587 3.055-3.055A4.913 4.913 0 0 1 5 10a5.006 5.006 0 0 1 5-5c.178.008.356.026.532.054l1.744-1.744A8.973 8.973 0 0 0 10 3C4.612 3 0 8.336 0 10a6.49 6.49 0 0 0 2 3.587Z" />
                      <path
                        d="m12.7 8.714 6.007-6.007a1 1 0 1 0-1.414-1.414L11.286 7.3a2.98 2.98 0 0 0-.588-.21l-.035-.01a2.981 2.981 0 0 0-3.584 3.583c0 .012.008.022.01.033.05.204.12.401.211.59l-6.007 6.007a1 1 0 1 0 1.414 1.414L8.714 12.7c.189.091.386.162.59.211.011 0 .021.007.033.01a2.981 2.981 0 0 0 3.584-3.584c0-.012-.008-.023-.011-.035a3.05 3.05 0 0 0-.21-.588Z"
                      />
                      <path d="M17.821 6.593 14.964 9.45a4.952 4.952 0 0 1-5.514 5.514L7.665 16.75c.767.165 1.55.25 2.335.251 6.453 0 10-5.258 10-7 0-1.166-1.637-2.874-2.179-3.407Z" />
                    </svg>
                    Private
                  </span>
                </div>
              </a>
            </li>
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
                <div>
                  <div class="text-base font-normal text-gray-600 dark:text-gray-400">
                    <span class="font-medium text-gray-900 dark:text-white">Joseph Mcfall</span> removed <span class="font-medium text-gray-900 dark:text-white">Lana Byrd account</span>
                  </div>
                  <span class="inline-flex items-center text-xs font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        d="M10 .5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19ZM8.374 17.4a7.6 7.6 0 0 1-5.9-7.4c0-.83.137-1.655.406-2.441l.239.019a3.887 3.887 0 0 1 2.082 2.5 4.1 4.1 0 0 0 2.441 2.8c1.148.522 1.389 2.007.732 4.522Zm3.6-8.829a.997.997 0 0 0-.027-.225 5.456 5.456 0 0 0-2.811-3.662c-.832-.527-1.347-.854-1.486-1.89a7.584 7.584 0 0 1 8.364 2.47c-1.387.208-2.14 2.237-2.14 3.307a1.187 1.187 0 0 1-1.9 0Zm1.626 8.053-.671-2.013a1.9 1.9 0 0 1 1.771-1.757l2.032.619a7.553 7.553 0 0 1-3.132 3.151Z"
                      />
                    </svg>
                    Public
                  </span>
                </div>
              </a>
            </li>
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/thomas-lean.png" alt="Thomas Lean image" />
                <div class="text-gray-600 dark:text-gray-400">
                  <div class="text-base font-normal">
                    <span class="font-medium text-gray-900 dark:text-white">Thomas Lean</span> likes <span class="font-medium text-gray-900 dark:text-white">Bonnie Green's</span> post in
                    <span class="font-medium text-gray-900 dark:text-white"> How to start with Flowbite library</span>
                  </div>
                  <div class="text-sm font-normal">"I wanted to share a webinar zeroheight."</div>
                  <span class="inline-flex items-center text-xs font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        d="M10 .5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19ZM8.374 17.4a7.6 7.6 0 0 1-5.9-7.4c0-.83.137-1.655.406-2.441l.239.019a3.887 3.887 0 0 1 2.082 2.5 4.1 4.1 0 0 0 2.441 2.8c1.148.522 1.389 2.007.732 4.522Zm3.6-8.829a.997.997 0 0 0-.027-.225 5.456 5.456 0 0 0-2.811-3.662c-.832-.527-1.347-.854-1.486-1.89a7.584 7.584 0 0 1 8.364 2.47c-1.387.208-2.14 2.237-2.14 3.307a1.187 1.187 0 0 1-1.9 0Zm1.626 8.053-.671-2.013a1.9 1.9 0 0 1 1.771-1.757l2.032.619a7.553 7.553 0 0 1-3.132 3.151Z"
                      />
                    </svg>
                    Public
                  </span>
                </div>
              </a>
            </li>
            <li>
              <a href="#" class="block items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex">
                <img class="mb-3 me-3 h-12 w-12 rounded-full sm:mb-0" src="/images/users/leslie-livingston.png" alt="Bonnie Green image" />
                <div class="text-gray-600 dark:text-gray-400">
                  <div class="text-base font-normal">
                    <span class="font-medium text-gray-900 dark:text-white">Leslie Livingston Green</span> likes <span class="font-medium text-gray-900 dark:text-white">Bonnie Green's</span> post in
                    <span class="font-medium text-gray-900 dark:text-white"> Top figma designs</span>
                  </div>
                  <div class="text-sm font-normal">"I wanted to share a webinar zeroheight."</div>
                  <span class="inline-flex items-center text-xs font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path d="m2 13.587 3.055-3.055A4.913 4.913 0 0 1 5 10a5.006 5.006 0 0 1 5-5c.178.008.356.026.532.054l1.744-1.744A8.973 8.973 0 0 0 10 3C4.612 3 0 8.336 0 10a6.49 6.49 0 0 0 2 3.587Z" />
                      <path
                        d="m12.7 8.714 6.007-6.007a1 1 0 1 0-1.414-1.414L11.286 7.3a2.98 2.98 0 0 0-.588-.21l-.035-.01a2.981 2.981 0 0 0-3.584 3.583c0 .012.008.022.01.033.05.204.12.401.211.59l-6.007 6.007a1 1 0 1 0 1.414 1.414L8.714 12.7c.189.091.386.162.59.211.011 0 .021.007.033.01a2.981 2.981 0 0 0 3.584-3.584c0-.012-.008-.023-.011-.035a3.05 3.05 0 0 0-.21-.588Z"
                      />
                      <path d="M17.821 6.593 14.964 9.45a4.952 4.952 0 0 1-5.514 5.514L7.665 16.75c.767.165 1.55.25 2.335.251 6.453 0 10-5.258 10-7 0-1.166-1.637-2.874-2.179-3.407Z" />
                    </svg>
                    Private
                  </span>
                </div>
              </a>
            </li>
          </ol>
        </div>
        <a
          href="{{< ref "pages/notifications" >}}"
          class="inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
              clip-rule="evenodd"
            />
          </svg>

          View all
        </a>
      </div>
      <!-- Top Sales Card -->
      <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-xl font-bold leading-none text-gray-900 dark:border-gray-700 dark:text-white">Customers</h3>
          <a href="{{< ref "e-commerce/transactions" >}}" class="inline-flex items-center rounded-lg px-3 py-2 font-medium text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"> View transactions </a>
        </div>
        <div class="flow-root">
          <ul role="list" class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:divide-gray-600 dark:border-gray-600 dark:bg-gray-700">
            <li class="pb-4">
              <div class="flex items-center space-x-4">
                <div class="shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/neil-sims.png" alt="Neil image" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate font-semibold text-gray-900 dark:text-white">Neil Sims</p>
                  <p class="truncate text-sm text-gray-500 dark:text-gray-400">email@flowbite.com</p>
                </div>
                <div class="inline-flex items-center font-semibold text-gray-900 dark:text-white">$320</div>
              </div>
            </li>
            <li class="py-4">
              <div class="flex items-center space-x-4">
                <div class="shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/bonnie-green.png" alt="Neil image" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate font-semibold text-gray-900 dark:text-white">Bonnie Green</p>
                  <p class="truncate text-sm text-gray-500 dark:text-gray-400">email@flowbite.com</p>
                </div>
                <div class="inline-flex items-center font-semibold text-gray-900 dark:text-white">$3467</div>
              </div>
            </li>
            <li class="py-4">
              <div class="flex items-center space-x-4">
                <div class="shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/michael-gough.png" alt="Neil image" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate font-semibold text-gray-900 dark:text-white">Michael Gough</p>
                  <p class="truncate text-sm text-gray-500 dark:text-gray-400">email@flowbite.com</p>
                </div>
                <div class="inline-flex items-center font-semibold text-gray-900 dark:text-white">$67</div>
              </div>
            </li>
            <li class="py-4">
              <div class="flex items-center space-x-4">
                <div class="shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/thomas-lean.png" alt="Neil image" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate font-semibold text-gray-900 dark:text-white">Thomes Lean</p>
                  <p class="truncate text-sm text-gray-500 dark:text-gray-400">email@flowbite.com</p>
                </div>
                <div class="inline-flex items-center font-semibold text-gray-900 dark:text-white">$2367</div>
              </div>
            </li>
            <li class="pt-4">
              <div class="flex items-center space-x-4">
                <div class="shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/lana-byrd.png" alt="Neil image" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate font-semibold text-gray-900 dark:text-white">Lana Byrd</p>
                  <p class="truncate text-sm text-gray-500 dark:text-gray-400">email@flowbite.com</p>
                </div>
                <div class="inline-flex items-center font-semibold text-gray-900 dark:text-white">$367</div>
              </div>
            </li>
          </ul>
        </div>
        <!-- Card Footer -->
        <div class="0 flex items-center justify-between pt-4 sm:pt-6">
          <button class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" type="button" data-dropdown-ignore-click-outside-class="datepicker" data-dropdown-toggle="latest-customers-dropdown">
            Last 7 days
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
            </svg>
          </button>
        <!-- Dropdown menu -->
        <div class="z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="latest-customers-dropdown">
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
          <div class="shrink-0">
            <a href="{{< ref "users/list" >}}" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
              Customers Report
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="mx-auto mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <table id="filter-table">
      <thead>
        <tr>
          <th>
            <span class="flex items-center">
              Name
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
          <th>
            <span class="flex items-center">
              Category
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
          <th>
            <span class="flex items-center">
              Brand
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
          <th>
            <span class="flex items-center">
              Price
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
          <th>
            <span class="flex items-center">
              Stock
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
          <th>
            <span class="flex items-center">
              Total Sales
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
          <th>
            <span class="flex items-center">
              Status
              <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
              </svg>
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Apple iMac 27"</th>
          <td>
            <span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span>
          </td>
          <td>Apple</td>
          <td>$1,299</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>95</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            200
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple iPhone</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Mobile Phones</span></td>
          <td>Apple</td>
          <td>$999</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>342</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            300
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Samsung Galaxy</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Mobile Phones</span></td>
          <td>Samsung</td>
          <td>$899</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>127</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            150
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Dell XPS 13</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
          <td>Dell</td>
          <td>$1,099</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            120
          </td>
          <td>Out of Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">HP Spectre x360</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
          <td>HP</td>
          <td>$1,299</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>325</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            80
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Google Pixel 6</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Mobile Phones</span></td>
          <td>Google</td>
          <td>$799</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>100</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            200
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Sony WH-1000XM4</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Headphones</span></td>
          <td>Sony</td>
          <td>$349</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>60</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            150
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple AirPods Pro</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Headphones</span></td>
          <td>Apple</td>
          <td>$249</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>200</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            300
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Asus ROG Zephyrus</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
          <td>Asus</td>
          <td>$1,899</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>15</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            50
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Microsoft Surface Pro 7</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
          <td>Microsoft</td>
          <td>$899</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>224</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            100
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Samsung QLED TV</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Televisions</span></td>
          <td>Samsung</td>
          <td>$1,299</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            70
          </td>
          <td>Out of Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">LG OLED TV</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Televisions</span></td>
          <td>LG</td>
          <td>$1,499</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>204</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            50
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Canon EOS R5</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Cameras</span></td>
          <td>Canon</td>
          <td>$3,899</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>674</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            30
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Nikon Z7 II</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Cameras</span></td>
          <td>Nikon</td>
          <td>$3,299</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>164</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            25
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple Watch Series 7</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Wearables</span></td>
          <td>Apple</td>
          <td>$399</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>150</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            500
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Fitbit Charge 5</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Wearables</span></td>
          <td>Fitbit</td>
          <td>$179</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>444</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            250
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Dyson V11 Vacuum</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Home Appliances</span></td>
          <td>Dyson</td>
          <td>$599</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            90
          </td>
          <td>Out of Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">iRobot Roomba i7+</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Home Appliances</span></td>
          <td>iRobot</td>
          <td>$799</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>1043</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            70
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Bose SoundLink Revolve</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Speakers</span></td>
          <td>Bose</td>
          <td>$199</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>935</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            200
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Sonos One</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Sonos</span></td>
          <td>Sonos</td>
          <td>$90</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>180</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            67
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple iPad Pro</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Tablets</span></td>
          <td>Apple</td>
          <td>$1,099</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>98</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            150
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Samsung Galaxy Tab S7</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Tablets</span></td>
          <td>Samsung</td>
          <td>$649</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>70</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            130
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Amazon Echo Dot</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Smart Home</span></td>
          <td>Amazon</td>
          <td>$49</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>300</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            800
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Google Nest Hub</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Smart Home</span></td>
          <td>Google</td>
          <td>$89</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>150</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            400
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">PlayStation 5</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming Consoles</span></td>
          <td>Sony</td>
          <td>$499</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>10</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            500
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Xbox Series X</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming Consoles</span></td>
          <td>Microsoft</td>
          <td>$499</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            450
          </td>
          <td>Out of Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Nintendo Switch</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming Consoles</span></td>
          <td>Nintendo</td>
          <td>$299</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>65</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            600
          </td>
          <td>In Stock</td>
        </tr>
        <tr>
          <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple MacBook Pro</td>
          <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
          <td>Apple</td>
          <td>$1,299</td>
          <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>20</td>
          <td class="flex items-center">
            <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
            </svg>
            100
          </td>
          <td>In Stock</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
</div>
