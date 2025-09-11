@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Mailing Inbox Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
group: mailing
page: inbox
---

<div class="{{ $attributes->get('class', '') }} flex items-center justify-between border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
  <div class="{{ $attributes->get('class', '') }} flex items-center">
    <div class="{{ $attributes->get('class', '') }} pe-4">
      <input
        id="checkbox-all"
        aria-describedby="checkbox-1"
        type="checkbox"
        class="{{ $attributes->get('class', '') }} focus:ring-3 h-4 w-4 rounded-sm border-gray-300 bg-gray-50 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
      />
      <label for="checkbox-all" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
    </div>
    <div class="{{ $attributes->get('class', '') }} h-5 w-px bg-gray-100 dark:bg-gray-700"></div>
    <div class="{{ $attributes->get('class', '') }} flex space-x-1 px-2">
      <a href="#" data-tooltip-target="tooltip-archive" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M4 4a2 2 0 1 0 0 4h16a2 2 0 1 0 0-4H4Zm0 6h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8Zm10.7 5.7a1 1 0 0 0-1.4-1.4l-.3.3V12a1 1 0 1 0-2 0v2.6l-.3-.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l2-2Z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Archive</span>
      </a>
      <div id="tooltip-archive" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Archive
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <a href="#" data-tooltip-target="tooltip-spam" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Report Spam</span>
      </a>
      <div id="tooltip-spam" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Report Spam
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <a href="#" data-tooltip-target="tooltip-delete" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Delete</span>
      </a>
      <div id="tooltip-delete" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Delete
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} h-5 w-px bg-gray-100 dark:bg-gray-700"></div>
    <div class="{{ $attributes->get('class', '') }} flex space-x-1 sm:px-2">
      <a
        href="#"
        data-tooltip-target="tooltip-unread"
        class="{{ $attributes->get('class', '') }} hidden cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:inline-flex"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path d="M2 5.6V18c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V5.6l-.9.7-7.9 6a2 2 0 0 1-2.4 0l-8-6-.8-.7Z" />
          <path d="M20.7 4.1A2 2 0 0 0 20 4H4a2 2 0 0 0-.6.1l.7.6 7.9 6 7.9-6 .8-.6Z" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Mark as unread</span>
      </a>
      <div id="tooltip-unread" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Mark as unread
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <a
        href="#"
        data-tooltip-target="tooltip-snooze"
        class="{{ $attributes->get('class', '') }} hidden cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:inline-flex"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Snooze</span>
      </a>
      <div id="tooltip-snooze" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Snooze
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <a
        href="#"
        data-tooltip-target="tooltip-add-to-tasks"
        class="{{ $attributes->get('class', '') }} hidden cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:inline-flex"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M18 14a1 1 0 1 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 1 0 2 0v-2h2a1 1 0 1 0 0-2h-2v-2Z" clip-rule="evenodd" />
          <path
            fill-rule="evenodd"
            d="M15 21.5a10 10 0 1 1 3.6-17L10.9 12 7.7 8.9a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.3 0L20 5.8a10 10 0 0 1 1.6 9.1c-.4-.3-1-.5-1.5-.5h-.5V14a2.5 2.5 0 0 0-5 0v.5H14a2.5 2.5 0 0 0 0 5h.5v.5c0 .6.2 1.1.5 1.5Z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Add to tasks</span>
      </a>
      <div id="tooltip-add-to-tasks" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Add to tasks
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <a
        href="#"
        data-tooltip-target="tooltip-move"
        class="{{ $attributes->get('class', '') }} hidden cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:inline-flex"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M5 4a2 2 0 0 0-2 2v1h11l-2-2.3a2 2 0 0 0-1.5-.7H5ZM3 19V9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm11.7-7.7a1 1 0 0 0-1.4 1.4l.3.3H8a1 1 0 1 0 0 2h5.6l-.3.3a1 1 0 0 0 1.4 1.4l2-2c.4-.4.4-1 0-1.4l-2-2Z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Move to</span>
      </a>
      <div id="tooltip-move" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Move to
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <a
        href="#"
        data-tooltip-target="tooltip-labels"
        class="{{ $attributes->get('class', '') }} hidden cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white md:inline-flex"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M3 4a1 1 0 0 0-.822 1.57L6.632 12l-4.454 6.43A1 1 0 0 0 3 20h13.153a1 1 0 0 0 .822-.43l4.847-7a1 1 0 0 0 0-1.14l-4.847-7a1 1 0 0 0-.822-.43H3Z" clip-rule="evenodd" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Labels</span>
      </a>
      <div id="tooltip-labels" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Labels
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
      <button
        id="mail-dropdown-button"
        type="button"
        data-dropdown-toggle="mail-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M12 6h0m0 6h0m0 6h0" />
        </svg>
      </button>
      <div id="mail-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-60 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
        <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="mail-dropdown-button">
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M3 4a1 1 0 0 0-.822 1.57L6.632 12l-4.454 6.43A1 1 0 0 0 3 20h13.153a1 1 0 0 0 .822-.43l4.847-7a1 1 0 0 0 0-1.14l-4.847-7a1 1 0 0 0-.822-.43H3Z" clip-rule="evenodd" />
              </svg>
              Mark as Important
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-width="2"
                  d="M11.083 4.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.557 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.029 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 19.8c-.764.446-1.688-.248-1.473-1.106l1.029-4.119a1 1 0 0 0-.337-1.016l-3.33-2.723c-.699-.571-.343-1.702.556-1.771l4.463-.342a1 1 0 0 0 .84-.597l1.752-4.022Z"
                />
              </svg>
              Add star
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path d="M5 3a2 2 0 0 0-1.5 3.3l5.4 6v5c0 .4.3.9.6 1.1l3.1 2.3c1 .7 2.5 0 2.5-1.2v-7.1l5.4-6C21.6 5 20.7 3 19 3H5Z" />
              </svg>
              Filter messages like these
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8v8a5 5 0 1 0 10 0V6.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 0 4 0V8" />
              </svg>
              Forward as attachment
            </a>
          </li>
        </ul>
      </div>
      <button
        type="button"
        id="composeButton"
        data-modal-target="composeModal"
        data-modal-toggle="composeModal"
        class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"></path>
        </svg>
        Compose
      </button>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} hidden items-center space-x-1 sm:flex sm:space-y-0">
    <span class="{{ $attributes->get('class', '') }} me-4 hidden text-sm text-gray-500 dark:text-gray-400 md:flex"
      >Show <span class="{{ $attributes->get('class', '') }} mx-1  font-semibold text-gray-900 dark:text-white">1-25</span> of <span class="{{ $attributes->get('class', '') }} ms-1 font-semibold text-gray-900 dark:text-white">2290</span></span
    >
    <button
      tyoe="button"
      data-tooltip-target="tooltip-prev-page"
      class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
    >
      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Prev page</span>
    </button>
    <div id="tooltip-prev-page" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Prev page
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      type="button"
      data-tooltip-target="tooltip-next-page"
      class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
    >
      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Next page</span>
    </button>
    <div id="tooltip-next-page" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Next page
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
  </div>
</div>
<div class="{{ $attributes->get('class', '') }} flex flex-col">
  <div class="{{ $attributes->get('class', '') }} overflow-x-auto">
    <div class="{{ $attributes->get('class', '') }} inline-block min-w-full align-middle">
      <div class="{{ $attributes->get('class', '') }} overflow-hidden shadow-sm">
        <table class="{{ $attributes->get('class', '') }} min-w-full table-fixed divide-y divide-gray-200">
          <tbody class="{{ $attributes->get('class', '') }} divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @{{ < messages.inline > }}
            @{{ - range  (index $.Site.Data "messages")  }}
              <tr class="{{ $attributes->get('class', '') }} cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" onClick="location.href='@{{  "mailing/read/" | relURL  }}';">
                <td class="{{ $attributes->get('class', '') }} w-4 p-4">
                  <div class="{{ $attributes->get('class', '') }} inline-flex items-center space-x-4">
                    <div>
                      <input
                        id="checkbox-@{{  .id  }}"
                        aria-describedby="checkbox-1"
                        type="checkbox"
                        class="{{ $attributes->get('class', '') }} focus:ring-3 h-4 w-4 rounded-sm border-gray-300 bg-gray-50 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                        onClick="event.stopPropagation();"
                      />
                      <label for="checkbox-@{{  .id  }}" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
                    </div>
                    <svg
                      onClick="event.stopPropagation();"
                      data-tooltip-target="tooltip-starred"
                      class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-500 hover:text-yellow-400 dark:text-gray-400 dark:hover:text-yellow-400"
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke="currentColor"
                        stroke-width="2"
                        d="M11.083 4.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.557 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.029 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 19.8c-.764.446-1.688-.248-1.473-1.106l1.029-4.119a1 1 0 0 0-.337-1.016l-3.33-2.723c-.699-.571-.343-1.702.556-1.771l4.463-.342a1 1 0 0 0 .84-.597l1.752-4.022Z"
                      />
                    </svg>
                    <div
                      id="tooltip-starred"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Not starred
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <svg onClick="event.stopPropagation();" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.2 19 4.8-7-4.8-7H3l4.8 7L3 19h13.2Z" />
                    </svg>
                  </div>
                </td>
                <td class="{{ $attributes->get('class', '') }} flex items-center space-x-4 whitespace-nowrap p-4">
                  <div class="{{ $attributes->get('class', '') }} @{{  if eq .read true  }}font-normal text-gray-700 dark:text-gray-400@{{  else  }}font-semibold text-gray-900 dark:text-white@{{  end  }} text-base">@{{  .name  }}</div>
                </td>
                <td
                  class="{{ $attributes->get('class', '') }} @{{  if eq .read true  }} font-normal text-gray-500 dark:text-gray-400 @{{  else  }} font-semibold text-gray-900 dark:text-white @{{  end  }} max-w-sm overflow-hidden truncate p-4 text-base xl:max-w-screen-md 2xl:max-w-screen-lg">
                  @{{  .text  }}
                </td>
                <td class="{{ $attributes->get('class', '') }} @{{  if eq .read true  }}font-normal text-gray-500 dark:text-gray-400@{{  else  }}font-medium text-gray-900 dark:text-white@{{  end  }} whitespace-nowrap p-4 text-sm">@{{  .time  }}</td>
              </tr>
              <!-- Unread -->
            @{{  end - }}
            @{{ < /messages.inline > }}
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="{{ $attributes->get('class', '') }} border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
  <div class="{{ $attributes->get('class', '') }} flex flex-col justify-between lg:flex-row lg:items-center xl:mb-0">
    <div class="{{ $attributes->get('class', '') }} order-1 mb-4 w-full sm:w-96 lg:mb-0">
      <div class="{{ $attributes->get('class', '') }} mb-1 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="{{ $attributes->get('class', '') }} h-1.5 rounded-full bg-primary-600 dark:bg-primary-500" style="width: 45%"></div>
      </div>
      <div class="{{ $attributes->get('class', '') }} text-xs font-medium text-gray-500 dark:text-white">7.2 GB of 15 GB used</div>
    </div>
    <p class="{{ $attributes->get('class', '') }} order-3 text-sm text-gray-500 dark:text-gray-400 lg:order-2 xl:flex">Copyright &copy; 2025 <a href="https://flowbite.com/" class="{{ $attributes->get('class', '') }} ml-1 hover:underline" target="_blank">Flowbite</a>. All rights reserved.</p>
    <div class="{{ $attributes->get('class', '') }} order-2 mb-4 text-sm text-gray-500 dark:text-gray-400 lg:order-3 lg:mb-0 lg:text-right">
      <p>Last account activity: 12 hours ago</p>
      <a href="#" class="{{ $attributes->get('class', '') }} font-medium text-gray-900 underline hover:no-underline dark:text-white">Details</a>
    </div>
  </div>
</div>

<!-- Compose Modal -->
<div id="composeModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-3xl p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Compose an email</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="composeModal"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>

          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} mb-4 space-y-4">
          <div>
            <label for="name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">To</label>
            <input
              type="email"
              name="name"
              id="name"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Add recipients"
              required=""
            />
          </div>
          <div>
            <label for="subject" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Subject</label>
            <input
              type="text"
              name="subject"
              id="subject"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Type your subject here"
              required=""
            />
          </div>
          <div>
            <label for="message" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Message</label>
            <div class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
              <div class="{{ $attributes->get('class', '') }} border-b px-3 py-2 dark:border-gray-600 border-gray-200">
                <div class="{{ $attributes->get('class', '') }} flex flex-wrap items-center">
                  <div class="{{ $attributes->get('class', '') }} flex flex-wrap items-center space-x-1 rtl:space-x-reverse">
                    <button
                      id="toggleBoldButton"
                      data-tooltip-target="tooltip-bold"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5h4.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0-7H6m2 7h6.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0 0H6" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Bold</span>
                    </button>
                    <div
                      id="tooltip-bold"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Toggle bold
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleItalicButton"
                      data-tooltip-target="tooltip-italic"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.874 19 6.143-14M6 19h6.33m-.66-14H18" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Italic</span>
                    </button>
                    <div
                      id="tooltip-italic"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Toggle italic
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleUnderlineButton"
                      data-tooltip-target="tooltip-underline"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M6 19h12M8 5v9a4 4 0 0 0 8 0V5M6 5h4m4 0h4" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Underline</span>
                    </button>
                    <div
                      id="tooltip-underline"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Toggle underline
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleStrikeButton"
                      data-tooltip-target="tooltip-strike"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6.2V5h12v1.2M7 19h6m.2-14-1.677 6.523M9.6 19l1.029-4M5 5l6.523 6.523M19 19l-7.477-7.477" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Strike</span>
                    </button>
                    <div
                      id="tooltip-strike"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Toggle strike
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleHighlightButton"
                      data-tooltip-target="tooltip-highlight"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-width="2"
                          d="M9 19.2H5.5c-.3 0-.5-.2-.5-.5V16c0-.2.2-.4.5-.4h13c.3 0 .5.2.5.4v2.7c0 .3-.2.5-.5.5H18m-6-1 1.4 1.8h.2l1.4-1.7m-7-5.4L12 4c0-.1 0-.1 0 0l4 8.8m-6-2.7h4m-7 2.7h2.5m5 0H17"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Highlight</span>
                    </button>
                    <div
                      id="tooltip-highlight"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Toggle highlight
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleCodeButton"
                      type="button"
                      data-tooltip-target="tooltip-code"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 8-4 4 4 4m8 0 4-4-4-4m-2-3-4 14" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Code</span>
                    </button>
                    <div
                      id="tooltip-code"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Format code
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleLinkButton"
                      data-tooltip-target="tooltip-link"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M13.213 9.787a3.391 3.391 0 0 0-4.795 0l-3.425 3.426a3.39 3.39 0 0 0 4.795 4.794l.321-.304m-.321-4.49a3.39 3.39 0 0 0 4.795 0l3.424-3.426a3.39 3.39 0 0 0-4.794-4.795l-1.028.961"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Link</span>
                    </button>
                    <div
                      id="tooltip-link"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Add link
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="removeLinkButton"
                      data-tooltip-target="tooltip-remove-link"
                      type="button"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-width="2"
                          d="M13.2 9.8a3.4 3.4 0 0 0-4.8 0L5 13.2A3.4 3.4 0 0 0 9.8 18l.3-.3m-.3-4.5a3.4 3.4 0 0 0 4.8 0L18 9.8A3.4 3.4 0 0 0 13.2 5l-1 1m7.4 14-1.8-1.8m0 0L16 16.4m1.8 1.8 1.8-1.8m-1.8 1.8L16 20"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Remove link</span>
                    </button>
                    <div
                      id="tooltip-remove-link"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Remove link
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleTextSizeButton"
                      data-dropdown-toggle="textSizeDropdown"
                      type="button"
                      data-tooltip-target="tooltip-text-size"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6.2V5h11v1.2M8 5v14m-3 0h6m2-6.8V11h8v1.2M17 11v8m-1.5 0h3" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Text size</span>
                    </button>
                    <div
                      id="tooltip-text-size"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Text size
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <div id="textSizeDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-72 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                      <ul class="{{ $attributes->get('class', '') }} space-y-1 text-sm font-medium" aria-labelledby="toggleTextSizeButton">
                        <li>
                          <button data-text-size="16px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                            16px (Default)
                          </button>
                        </li>
                        <li>
                          <button data-text-size="12px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-xs text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">12px (Tiny)</button>
                        </li>
                        <li>
                          <button data-text-size="14px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">14px (Small)</button>
                        </li>
                        <li>
                          <button data-text-size="18px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-lg text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">18px (Lead)</button>
                        </li>
                        <li>
                          <button data-text-size="24px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-2xl text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">24px (Large)</button>
                        </li>
                        <li>
                          <button data-text-size="36px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-4xl text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">36px (Huge)</button>
                        </li>
                      </ul>
                    </div>
                    <button
                      id="toggleTextColorButton"
                      data-dropdown-toggle="textColorDropdown"
                      type="button"
                      data-tooltip-target="tooltip-text-color"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="25" height="24" fill="none" viewBox="0 0 25 24">
                        <path
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-width="2"
                          d="m6.532 15.982 1.573-4m-1.573 4h-1.1m1.1 0h1.65m-.077-4 2.725-6.93a.11.11 0 0 1 .204 0l2.725 6.93m-5.654 0H8.1m.006 0h5.654m0 0 .617 1.569m5.11 4.453c0 1.102-.854 1.996-1.908 1.996-1.053 0-1.907-.894-1.907-1.996 0-1.103 1.907-4.128 1.907-4.128s1.909 3.025 1.909 4.128Z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Text color</span>
                    </button>
                    <div
                      id="tooltip-text-color"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Text color
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <div id="textColorDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-48 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                      <div class="{{ $attributes->get('class', '') }} group mb-3 grid grid-cols-6 items-center gap-2 rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input
                          type="color"
                          id="color"
                          value="#e66465"
                          class="{{ $attributes->get('class', '') }} col-span-3 h-8 w-full rounded-md border border-gray-200 bg-gray-50 p-px px-1 hover:bg-gray-50 group-hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:group-hover:bg-gray-700"
                        />
                        <label for="color" class="{{ $attributes->get('class', '') }} col-span-3 text-sm font-medium text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white">Pick a color</label>
                      </div>
                      <div class="{{ $attributes->get('class', '') }} mb-3 grid grid-cols-6 gap-1">
                        <button type="button" data-hex-color="#1A56DB" style="background-color: #1A56DB" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Blue</span></button>
                        <button type="button" data-hex-color="#0E9F6E" style="background-color: #0E9F6E" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Green</span></button>
                        <button type="button" data-hex-color="#FACA15" style="background-color: #FACA15" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Yellow</span></button>
                        <button type="button" data-hex-color="#F05252" style="background-color: #F05252" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Red</span></button>
                        <button type="button" data-hex-color="#FF8A4C" style="background-color: #FF8A4C" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Orange</span></button>
                        <button type="button" data-hex-color="#0694A2" style="background-color: #0694A2" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Teal</span></button>
                        <button type="button" data-hex-color="#B4C6FC" style="background-color: #B4C6FC" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light indigo</span></button>
                        <button type="button" data-hex-color="#8DA2FB" style="background-color: #8DA2FB" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Indigo</span></button>
                        <button type="button" data-hex-color="#5145CD" style="background-color: #5145CD" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Purple</span></button>
                        <button type="button" data-hex-color="#771D1D" style="background-color: #771D1D" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Brown</span></button>
                        <button type="button" data-hex-color="#FCD9BD" style="background-color: #FCD9BD" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light orange</span></button>
                        <button type="button" data-hex-color="#99154B" style="background-color: #99154B" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Bordo</span></button>
                        <button type="button" data-hex-color="#7E3AF2" style="background-color: #7E3AF2" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Dark Purple</span></button>
                        <button type="button" data-hex-color="#CABFFD" style="background-color: #CABFFD" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light</span></button>
                        <button type="button" data-hex-color="#D61F69" style="background-color: #D61F69" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Dark Pink</span></button>
                        <button type="button" data-hex-color="#F8B4D9" style="background-color: #F8B4D9" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Pink</span></button>
                        <button type="button" data-hex-color="#F6C196" style="background-color: #F6C196" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Cream</span></button>
                        <button type="button" data-hex-color="#A4CAFE" style="background-color: #A4CAFE" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light Blue</span></button>
                        <button type="button" data-hex-color="#5145CD" style="background-color: #5145CD" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Dark Blue</span></button>
                        <button type="button" data-hex-color="#B43403" style="background-color: #B43403" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Orange Brown</span></button>
                        <button type="button" data-hex-color="#FCE96A" style="background-color: #FCE96A" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light Yellow</span></button>
                        <button type="button" data-hex-color="#1E429F" style="background-color: #1E429F" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Navy Blue</span></button>
                        <button type="button" data-hex-color="#768FFD" style="background-color: #768FFD" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light Purple</span></button>
                        <button type="button" data-hex-color="#BCF0DA" style="background-color: #BCF0DA" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light Green</span></button>
                        <button type="button" data-hex-color="#EBF5FF" style="background-color: #EBF5FF" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Sky Blue</span></button>
                        <button type="button" data-hex-color="#16BDCA" style="background-color: #16BDCA" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Cyan</span></button>
                        <button type="button" data-hex-color="#E74694" style="background-color: #E74694" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Pink</span></button>
                        <button type="button" data-hex-color="#83B0ED" style="background-color: #83B0ED" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Darker Sky Blue</span></button>
                        <button type="button" data-hex-color="#03543F" style="background-color: #03543F" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Forest Green</span></button>
                        <button type="button" data-hex-color="#111928" style="background-color: #111928" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Black</span></button>
                        <button type="button" data-hex-color="#4B5563" style="background-color: #4B5563" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Stone</span></button>
                        <button type="button" data-hex-color="#6B7280" style="background-color: #6B7280" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Gray</span></button>
                        <button type="button" data-hex-color="#D1D5DB" style="background-color: #D1D5DB" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Light Gray</span></button>
                        <button type="button" data-hex-color="#F3F4F6" style="background-color: #F3F4F6" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Cloud Gray</span></button>
                        <button type="button" data-hex-color="#F3F4F6" style="background-color: #F3F4F6" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Cloud Gray</span></button>
                        <button type="button" data-hex-color="#F9FAFB" style="background-color: #F9FAFB" class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-md"><span class="{{ $attributes->get('class', '') }} sr-only">Heaven Gray</span></button>
                      </div>
                      <button
                        type="button"
                        id="reset-color"
                        class="{{ $attributes->get('class', '') }} w-full rounded-lg bg-white py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-700"
                      >
                        Reset color
                      </button>
                    </div>
                    <button
                      id="toggleFontFamilyButton"
                      data-dropdown-toggle="fontFamilyDropdown"
                      type="button"
                      data-tooltip-target="tooltip-font-family"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="m10.6 19 4.298-10.93a.11.11 0 0 1 .204 0L19.4 19m-8.8 0H9.5m1.1 0h1.65m7.15 0h-1.65m1.65 0h1.1m-7.7-3.985h4.4M3.021 16l1.567-3.985m0 0L7.32 5.07a.11.11 0 0 1 .205 0l2.503 6.945h-5.44Z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Font family</span>
                    </button>
                    <div
                      id="tooltip-font-family"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Font Family
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <div id="fontFamilyDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-48 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                      <ul class="{{ $attributes->get('class', '') }} space-y-1 text-sm font-medium" aria-labelledby="toggleFontFamilyButton">
                        <li>
                          <button
                            data-font-family="Inter, ui-sans-serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 font-sans text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                          >
                            Default
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="Arial, sans-serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: Arial, sans-serif;"
                          >
                            Arial
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="'Courier New', monospace"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: 'Courier New', monospace;"
                          >
                            Courier New
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="Georgia, serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: Georgia, serif;"
                          >
                            Georgia
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="'Lucida Sans Unicode', sans-serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: 'Lucida Sans Unicode', sans-serif;"
                          >
                            Lucida Sans Unicode
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="Tahoma, sans-serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: Tahoma, sans-serif;"
                          >
                            Tahoma
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="'Times New Roman', serif;"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: 'Times New Roman', serif;"
                          >
                            Times New Roman
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="'Trebuchet MS', sans-serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: 'Trebuchet MS', sans-serif;"
                          >
                            Trebuchet MS
                          </button>
                        </li>
                        <li>
                          <button
                            data-font-family="Verdana, sans-serif"
                            type="button"
                            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                            style="font-family: Verdana, sans-serif;"
                          >
                            Verdana
                          </button>
                        </li>
                      </ul>
                    </div>
                    <div class="{{ $attributes->get('class', '') }} px-1">
                      <span class="{{ $attributes->get('class', '') }} block h-4 w-px bg-gray-300 dark:bg-gray-600"></span>
                    </div>
                    <button
                      id="toggleLeftAlignButton"
                      type="button"
                      data-tooltip-target="tooltip-left-align"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h8m-8 4h12M6 14h8m-8 4h12" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Align left</span>
                    </button>
                    <div
                      id="tooltip-left-align"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Align left
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleCenterAlignButton"
                      type="button"
                      data-tooltip-target="tooltip-center-align"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M6 10h12M8 14h8M6 18h12" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Align center</span>
                    </button>
                    <div
                      id="tooltip-center-align"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Align center
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                      id="toggleRightAlignButton"
                      type="button"
                      data-tooltip-target="tooltip-right-align"
                      class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6h-8m8 4H6m12 4h-8m8 4H6" />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Align right</span>
                    </button>
                    <div
                      id="tooltip-right-align"
                      role="tooltip"
                      class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    >
                      Align right
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} flex flex-wrap items-center gap-2 pt-2">
                  <button
                    id="typographyDropdownButton"
                    data-dropdown-toggle="typographyDropdown"
                    class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-600 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-white dark:focus:ring-gray-600"
                    type="button"
                  >
                    Format
                    <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                    </svg>
                  </button>
                  <div class="{{ $attributes->get('class', '') }} ps-1.5">
                    <span class="{{ $attributes->get('class', '') }} block h-4 w-px bg-gray-300 dark:bg-gray-600"></span>
                  </div>
                  <!-- Heading Dropdown -->
                  <div id="typographyDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-72 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                    <ul class="{{ $attributes->get('class', '') }} space-y-1 text-sm font-medium" aria-labelledby="typographyDropdownButton">
                      <li>
                        <button id="toggleParagraphButton" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Paragraph
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">0</kbd>
                          </div>
                        </button>
                      </li>
                      <li>
                        <button data-heading-level="1" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Heading 1
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">1</kbd>
                          </div>
                        </button>
                      </li>
                      <li>
                        <button data-heading-level="2" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Heading 2
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">2</kbd>
                          </div>
                        </button>
                      </li>
                      <li>
                        <button data-heading-level="3" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Heading 3
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">3</kbd>
                          </div>
                        </button>
                      </li>
                      <li>
                        <button data-heading-level="4" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Heading 4
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">4</kbd>
                          </div>
                        </button>
                      </li>
                      <li>
                        <button data-heading-level="5" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Heading 5
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">5</kbd>
                          </div>
                        </button>
                      </li>
                      <li>
                        <button data-heading-level="6" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                          Heading 6
                          <div class="{{ $attributes->get('class', '') }} space-x-1.5">
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Cmd</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">Alt</kbd>
                            <kbd class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">6</kbd>
                          </div>
                        </button>
                      </li>
                    </ul>
                  </div>
                  <button
                    id="addImageButton"
                    type="button"
                    data-tooltip-target="tooltip-image"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 10a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H14a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path
                        fill-rule="evenodd"
                        d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12c0 .556-.227 1.06-.593 1.422A.999.999 0 0 1 20.5 20H4a2.002 2.002 0 0 1-2-2V6Zm6.892 12 3.833-5.356-3.99-4.322a1 1 0 0 0-1.549.097L4 12.879V6h16v9.95l-3.257-3.619a1 1 0 0 0-1.557.088L11.2 18H8.892Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Add image</span>
                  </button>
                  <div
                    id="tooltip-image"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Add image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button
                    id="addVideoButton"
                    type="button"
                    data-tooltip-target="tooltip-video"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm-2 4a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2H9Zm0 2h2v2H9v-2Zm7.965-.557a1 1 0 0 0-1.692-.72l-1.268 1.218a1 1 0 0 0-.308.721v.733a1 1 0 0 0 .37.776l1.267 1.032a1 1 0 0 0 1.631-.776v-2.984Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Add video</span>
                  </button>
                  <div
                    id="tooltip-video"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Add video
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button
                    id="toggleListButton"
                    type="button"
                    data-tooltip-target="tooltip-list"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5" />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Toggle list</span>
                  </button>
                  <div
                    id="tooltip-list"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Toggle list
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button
                    id="toggleOrderedListButton"
                    type="button"
                    data-tooltip-target="tooltip-ordered-list"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6h8m-8 6h8m-8 6h8M4 16a2 2 0 1 1 3.321 1.5L4 20h5M4 5l2-1v6m-2 0h4" />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Toggle ordered list</span>
                  </button>
                  <div
                    id="tooltip-ordered-list"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Toggle ordered list
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button
                    id="toggleBlockquoteButton"
                    type="button"
                    data-tooltip-target="tooltip-blockquote-list"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M6 6a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3a3 3 0 0 1-3 3H5a1 1 0 1 0 0 2h1a5 5 0 0 0 5-5V8a2 2 0 0 0-2-2H6Zm9 0a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3a3 3 0 0 1-3 3h-1a1 1 0 1 0 0 2h1a5 5 0 0 0 5-5V8a2 2 0 0 0-2-2h-3Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Toggle blockquote</span>
                  </button>
                  <div
                    id="tooltip-blockquote-list"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Toggle blockquote
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button
                    id="toggleHRButton"
                    type="button"
                    data-tooltip-target="tooltip-hr-list"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 12h14" />
                      <path stroke="currentColor" stroke-linecap="round" d="M6 9.5h12m-12 9h12M6 7.5h12m-12 9h12M6 5.5h12m-12 9h12" />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Toggle Horizontal Rule</span>
                  </button>
                  <div
                    id="tooltip-hr-list"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                  >
                    Toggle Horizontal Rule
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} rounded-b-lg bg-white px-4 py-2 dark:bg-gray-800">
                <label for="wysiwyg-example" class="{{ $attributes->get('class', '') }} sr-only">Publish post</label>
                <div id="wysiwyg-example" class="{{ $attributes->get('class', '') }} block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
          <button
            type="submit"
            class="{{ $attributes->get('class', '') }} inline-flex items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            Send message
            <span class="{{ $attributes->get('class', '') }} sr-only">Add event</span>
          </button>
          <button
            data-modal-toggle="composeModal"
            type="button"
            class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
