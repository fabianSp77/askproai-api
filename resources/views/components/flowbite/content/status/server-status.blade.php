{{-- Flowbite Component: server-status --}}
@props(['title' => 'Tailwind CSS Server Status Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<section class="bg-gray-50 dark:bg-gray-900">
  <div class="mx-auto max-w-screen-lg px-4 py-4 md:py-6 lg:px-0">
    <nav class="mb-4 flex" aria-label="Breadcrumb">
      <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        <li class="inline-flex items-center">
          <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
            <svg class="me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd" />
            </svg>
            Home
          </a>
        </li>
        <li>
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Status</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">flowbite.com</span>
          </div>
        </li>
      </ol>
    </nav>
    <h1 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white md:mb-6">Current status: Flowbite.com</h1>
    <div id="alert-additional-content-3" class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-gray-800 dark:text-green-400" role="alert">
      <div class="flex items-center">
        <svg class="me-2 h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="sr-only">Info</span>
        <h2 class="text-lg font-medium">All systems operational</h2>
      </div>
      <div class="mt-2 text-sm">
        All systems are functioning seamlessly, with no disruptions or downtimes reported. Every component, from critical infrastructure to auxiliary services, is running at full capacity, ensuring optimal performance and reliability.
      </div>
    </div>
    <div class="mb-4 grid gap-4 md:mb-6 md:grid-cols-2">
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">flowbite.com website</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div id="status-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          The ability for users to navigate to or interact with Flowbite website
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Security Audit</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-2" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-2"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          The ability for users to get output from running Flowbite audit
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-yellow-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd"/>
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Data Services</h3>
            <p class="text-gray-500 dark:text-gray-400">2 known issues reported</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-3" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-3"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Check for regional outages that could affect the accessibility of this website services or data.
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">API Requests</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-4" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-4"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Requests for Flowbite APIs
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Issues</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-5" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-5"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Requests for Issues on Flowbite.com
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Flowbite Downloads</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-6" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-6"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          The ability for users to download assets on Flowbite website
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Apps and runtime</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-7" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-7"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Allow users to interact with Flowbite software for productivity.
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Actions</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-8" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-8"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Workflow, compute and orchestration for Flowbite actions
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
          <svg class="h-6 w-6 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Package installation</h3>
            <p class="text-gray-500 dark:text-gray-400">No known issues at this time</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-9" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-9"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Allow users to read from the registry so that they can install packages
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
      <!-- Card -->
      <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
        <div class="flex items-center space-x-4">
            <svg class="h-6 w-6 shrink-0 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
            </svg>
          </svg>
          <div>
            <h3 class="mb-1 text-lg font-semibold leading-none text-gray-900 dark:text-white md:text-xl">Pages</h3>
            <p class="text-gray-500 dark:text-gray-400">1 unknown issue reported</p>
          </div>
        </div>
        <button type="button" data-tooltip-target="status-tooltip-10" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span class="sr-only">More info</span>
        </button>
        <div
          id="status-tooltip-10"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Frontend application and API servers for pages builds
          <div class="tooltip-arrow" data-popper-arrow=""></div>
        </div>
      </div>
    </div>
    <div class="mb-8 mt-4 items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-800 sm:flex md:mt-6 lg:mb-12">
      <p class="mb-4 text-gray-500 dark:text-gray-400 sm:mb-0">No major downtime reported</p>
      <a href="/status/uptime/" class="flex items-center font-medium text-primary-700 hover:underline dark:text-primary-500">
        View uptime chart
        <svg class="ms-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
        </svg>
      </a>
    </div>
    <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white md:mb-6">Past incidents</h3>
    <div class="mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">Nov 28, 2025</h4>
      <p class="text-sm text-gray-500 dark:text-gray-400 md:text-base">No incidents reported today.</p>
    </div>
    <div class="mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">Nov 27, 2025</h4>
      <p class="mb-4 font-medium text-primary-700 dark:text-primary-500">Scheduled maintenance for data migrations</p>

      <ol class="relative ms-1 border-s border-gray-200 dark:border-gray-700">
        <li class="mb-10 ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 23, 23:00 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">Completed</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">The scheduled maintenance has been completed.</p>
          <a
            href="#"
            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View status
          </a>
        </li>
        <li class="mb-10 ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 23, 23:00 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">In progress</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">Scheduled maintenance is currently in progress. We will provide updates as necessary.</p>
        </li>
        <li class="ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 23, 23:00 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">Scheduled</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">
            We will be conducting scheduled maintenance for data migration, during which the package publishing functionality will be temporarily unavailable. All other operations, including package installation, and search are expected to
            function as usual.
          </p>
        </li>
      </ol>
    </div>
    <div class="mb-4 rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">Nov 26, 2025</h4>
      <p class="text-sm text-gray-500 dark:text-gray-400 md:text-base">No incidents reported today.</p>
    </div>
    <div class="rounded-lg bg-white p-4 shadow-xs dark:bg-gray-800 md:p-6">
      <h4 class="mb-4 border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-white">Nov 25, 2025</h4>
      <p class="mb-4 font-medium text-yellow-500 dark:text-yellow-400">Intermittent issues with package install</p>

      <ol class="relative ms-1 border-s border-gray-200 dark:border-gray-700">
        <li class="mb-10 ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 25, 21:34 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">Resolved</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">The scheduled maintenance has been completed.</p>
        </li>
        <li class="mb-10 ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 25, 23:00 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">Monitoring</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">A fix has been implemented and we are monitoring the results.</p>
          <a
            href="#"
            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            View more
          </a>
        </li>
        <li class="mb-10 ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 25, 13:38 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">Identified</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">The issue has been identified and a fix is being implemented.</p>
        </li>
        <li class="ms-4">
          <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
          <time class="text-sm leading-none text-gray-500 dark:text-gray-400">Nov 25, 13:31 UTC</time>
          <h3 class="my-2 text-lg font-semibold text-gray-900 dark:text-white">Investigating</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 md:text-base">We are currently investigating this issue.</p>
        </li>
      </ol>
    </div>
    <div class="mt-4 items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-800 sm:flex md:mt-6">
      <p class="mb-4 text-gray-500 dark:text-gray-400 sm:mb-0">No incidents in the last month</p>
      <a href="/status/status-history" class="flex items-center font-medium text-primary-700 hover:underline dark:text-primary-500">
        Incident history
        <svg class="ms-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
        </svg>
      </a>
    </div>
  </div>
</section>
</div>
