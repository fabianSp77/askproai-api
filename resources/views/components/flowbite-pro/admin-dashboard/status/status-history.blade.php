@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Status History Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard-no-sidebar
group: status
page: status-history
navigation: true
footer: true
---

<section class="{{ $attributes->get('class', '') }} bg-gray-50 dark:bg-gray-900">
  <div class="{{ $attributes->get('class', '') }} mx-auto max-w-screen-lg px-4 py-4 md:py-12 lg:px-0">
    <div class="{{ $attributes->get('class', '') }} mb-4 items-end justify-between sm:flex md:mb-6">
      <div>
        <nav class="{{ $attributes->get('class', '') }} mb-4 flex" aria-label="Breadcrumb">
          <ol class="{{ $attributes->get('class', '') }} inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
            <li class="{{ $attributes->get('class', '') }} inline-flex items-center">
              <a href="/" class="{{ $attributes->get('class', '') }} inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd" />
                </svg>
                Home
              </a>
            </li>
            <li>
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
                <a href="#" class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Status</a>
              </div>
            </li>
            <li aria-current="page">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
                <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">flowbite.com</span>
              </div>
            </li>
          </ol>
        </nav>
        <h1 class="{{ $attributes->get('class', '') }} text-xl font-semibold text-gray-900 dark:text-white">Incident History</h1>
      </div>

      <div class="{{ $attributes->get('class', '') }} relative mb-4 w-full sm:mb-0 sm:w-auto">
        <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
          <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <input
          id="datepicker-autohide"
          datepicker
          datepicker-autohide
          type="text"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-white p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600  dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:w-auto"
          placeholder="Select date"
        />
      </div>
    </div>

    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="{{ $attributes->get('class', '') }} mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">December, 2025</h4>
      <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 md:text-base">No incidents reported until today.</p>
    </div>
    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="{{ $attributes->get('class', '') }} mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">November 2025</h4>

      <ol class="{{ $attributes->get('class', '') }} relative ms-1 border-s border-gray-200 dark:border-gray-700">
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">November 25</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-primary-700 dark:text-primary-500">Scheduled maintenance for data migrations</h3>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">The scheduled maintenance has been completed.</p>
          <a
            href="#"
            class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View more
          </a>
        </li>
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">November 21</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-yellow-500 dark:text-yellow-400">Intermittent issues with package install</h3>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">This incident has been resolved.</p>
          <a
            href="#"
            class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View more
          </a>
        </li>
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">November 14</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-green-500 dark:text-yellow-400">No concerns</h3>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">No known issues at this time.</p>
        </li>
      </ol>
    </div>
    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="{{ $attributes->get('class', '') }} mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">October 2025</h4>

      <ol class="{{ $attributes->get('class', '') }} relative ms-1 border-s border-gray-200 dark:border-gray-700">
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">October 30</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-primary-700 dark:text-primary-500">Scheduled maintenance for data migrations</h3>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">The scheduled maintenance has been completed.</p>
          <a
            href="#"
            class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View more
          </a>
        </li>
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">October 18</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-yellow-500 dark:text-yellow-400">Disruption with Flowbite Search</h3>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">
            Between <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">13:30 and 15:00 UTC</span>, repository searches were timing out for most users. The ongoing efforts from the similar incident last week helped uncover the main
            contributing factors. We have deployed short-term mitigations and identified longer term work to proactively identify and limit resource-intensive searches.
          </p>
          <a
            href="#"
            class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View more
          </a>
        </li>
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">October 08</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-red-600 dark:text-red-500">Disruption with some Flowbite services</h3>
          <p class="{{ $attributes->get('class', '') }} mb-2 text-sm text-gray-500 dark:text-gray-400 md:text-base">
            On <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">October 02th, 2025 from 10:43 UTC to 11:26 UTC</span> Flowbite customers in the Central India region were unable to create new Flowbite. Resumes were not impacted.
            Additionally, there was no impact to customers in other regions.
          </p>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">
            The cause was traced to storage capacity constraints in the region and was mitigated by temporarily redirecting create requests to other regions. Afterwards, additional storage capacity was added to the region and traffic was
            routed back.
          </p>
          <a
            href="#"
            class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View more
          </a>
        </li>
        <li class="{{ $attributes->get('class', '') }} mb-10 ms-4">
          <div class="{{ $attributes->get('class', '') }} absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="{{ $attributes->get('class', '') }} text-sm leading-none text-gray-500 dark:text-gray-400">October 03</time>
          <h3 class="{{ $attributes->get('class', '') }} my-2 font-medium text-green-500 dark:text-yellow-400">No concerns</h3>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">No known issues at this time.</p>
        </li>
      </ol>
    </div>
    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="{{ $attributes->get('class', '') }} mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">September, 2025</h4>
      <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 md:text-base">No incidents reported until today.</p>
    </div>
    <div class="{{ $attributes->get('class', '') }} mt-4 items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-800 sm:flex md:mt-6">
      <p class="{{ $attributes->get('class', '') }} dar:text-gray-400 mb-4 text-gray-500 sm:mb-0">Check the <a href="/status/uptime/" class="{{ $attributes->get('class', '') }} font-medium text-primary-700 hover:underline dark:text-primary-500">uptime page</a> for a more comprehensive view.</p>
      <a href="/status/server-status/" class="{{ $attributes->get('class', '') }} flex items-center font-medium text-primary-700 hover:underline dark:text-primary-500">
        Current status
        <svg class="{{ $attributes->get('class', '') }} ms-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
        </svg>
      </a>
    </div>
  </div>
</section>
