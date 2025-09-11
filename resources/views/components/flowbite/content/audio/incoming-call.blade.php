{{-- Flowbite Component: incoming-call --}}
@props(['title' => 'Tailwind CSS Audio Incoming Call Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="relative flex h-screen w-full flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
  <div class="max-h-auto relative mb-10 flex w-full flex-col items-center justify-center">
    <img src="/images/audio-call/call-image.png" alt="" class="max-h-auto mb-3 h-36 w-36 rounded-full" />
    <button type="button" class="mb-2 text-3xl font-bold text-gray-900 hover:underline dark:text-white">Robert Brown</button>
    <p class="text-lg font-normal text-gray-500 dark:text-gray-400">call from +123 456 7890</p>
  </div>

  <div class="mx-auto mb-12 grid w-full max-w-64 grid-cols-2 items-center justify-center gap-8 px-4 lg:hidden">
    <div class="flex flex-col items-center justify-center">
      <button data-tooltip-target="tooltip-reminder-call" type="button" class="group flex flex-col items-center justify-center rounded-full focus:outline-none focus:ring-4">
        <svg class="mb-2 h-6 w-6 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
        </svg>
        <p class="text-center text-sm text-gray-900 group-hover:underline dark:text-white">Remind me</p>
      </button>
      <div id="tooltip-reminder-call" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Set reminder
        <div class="tooltip-arrow" data-popper-arrow></div>
      </div>
    </div>

    <div class="flex flex-col items-center justify-center">
      <button data-tooltip-target="tooltip-message-call" type="button" class="group flex flex-col items-center justify-center rounded-full focus:outline-none focus:ring-4">
        <svg class="mb-2 h-6 w-6 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M4 3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h1v2a1 1 0 0 0 1.707.707L9.414 13H15a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H4Z" clip-rule="evenodd" />
          <path fill-rule="evenodd" d="M8.023 17.215c.033-.03.066-.062.098-.094L10.243 15H15a3 3 0 0 0 3-3V8h2a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1v2a1 1 0 0 1-1.707.707L14.586 18H9a1 1 0 0 1-.977-.785Z" clip-rule="evenodd" />
        </svg>
        <p class="text-center text-sm text-gray-900 group-hover:underline dark:text-white">Message</p>
      </button>
      <div id="tooltip-message-call" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Send message
        <div class="tooltip-arrow" data-popper-arrow></div>
      </div>
    </div>
  </div>
  <div class="mx-auto grid w-full max-w-64 grid-cols-2 items-center justify-center gap-8 px-4">
    <div class="flex flex-col items-center justify-center">
      <button
        data-tooltip-target="tooltip-decline-call"
        type="button"
        class="group mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200 dark:bg-red-600 dark:focus:ring-red-800 md:me-0"
      >
        <svg class="h-6 w-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="sr-only">Decline call</span>
      </button>
      <div id="tooltip-decline-call" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Decline call
        <div class="tooltip-arrow" data-popper-arrow></div>
      </div>
      <p class="text-center text-gray-900 dark:text-white">Decline</p>
    </div>
    <div class="flex flex-col items-center justify-center">
      <button
        data-tooltip-target="tooltip-accept-call"
        type="button"
        class="group mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-200 dark:bg-primary-600 dark:focus:ring-primary-800 md:me-0"
      >
        <svg class="h-6 w-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5" />
        </svg>
        <span class="sr-only">Accept call</span>
      </button>
      <div id="tooltip-accept-call" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Accept call
        <div class="tooltip-arrow" data-popper-arrow></div>
      </div>
      <p class="text-center text-gray-900 dark:text-white">Accept</p>
    </div>
  </div>
</div>
</div>
