@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Mail Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
white_bg: true
group: mailing
page: read
---

<div class="{{ $attributes->get('class', '') }} flex items-center justify-between border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
  <div class="{{ $attributes->get('class', '') }} flex items-center">
    <div class="{{ $attributes->get('class', '') }} pe-3">
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
    <div class="{{ $attributes->get('class', '') }} flex space-x-1 px-0 sm:px-2">
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
        class="{{ $attributes->get('class', '') }} hidden cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:inline-flex"
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
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M12 6h0m0 6h0m0 6h0" />
        </svg>
      </button>
      <div id="mail-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-60 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
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
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-width="2"
                  d="M11 5.1a1 1 0 0 1 2 0l1.7 4c.1.4.4.6.8.6l4.5.4a1 1 0 0 1 .5 1.7l-3.3 2.8a1 1 0 0 0-.3 1l1 4a1 1 0 0 1-1.5 1.2l-3.9-2.3a1 1 0 0 0-1 0l-4 2.3a1 1 0 0 1-1.4-1.1l1-4.1c.1-.4 0-.8-.3-1l-3.3-2.8a1 1 0 0 1 .5-1.7l4.5-.4c.4 0 .7-.2.8-.6l1.8-4Z"
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
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1 sm:space-y-0">
    <span class="{{ $attributes->get('class', '') }} me-4 hidden text-sm text-gray-500 dark:text-gray-400 sm:flex"
      >Show <span class="{{ $attributes->get('class', '') }} mx-1 font-semibold text-gray-900 dark:text-white">1-25</span> of <span class="{{ $attributes->get('class', '') }} ms-1 font-semibold text-gray-900 dark:text-white">2290</span></span
    >
    <a href="#" data-tooltip-target="tooltip-prev-page" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Prev page</span>
    </a>
    <div id="tooltip-prev-page" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Prev page
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <a href="#" data-tooltip-target="tooltip-next-page" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
      <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Next page</span>
    </a>
    <div id="tooltip-next-page" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Next page
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
  </div>
</div>
<div class="{{ $attributes->get('class', '') }} p-4 lg:px-8">
  <div class="{{ $attributes->get('class', '') }} mb-4 items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
    <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
      <h2 class="{{ $attributes->get('class', '') }} text-base font-medium text-gray-900 dark:text-white sm:mb-1.5 sm:flex sm:text-xl sm:leading-none">
        Inquiry about design services <span class="{{ $attributes->get('class', '') }} me-2 ms-3 hidden rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 sm:flex">External</span>
      </h2>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1">
        <a href="#" data-tooltip-target="tooltip-print" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5c0 1.1.9 2 2 2h1v-4c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4c0 .6-.4 1-1 1H9Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Print</span>
        </a>
        <div id="tooltip-print" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Print
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a
          href="#"
          data-tooltip-target="tooltip-new-window"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M11.4 5H5a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2v-6.4a3 3 0 0 1-1.7-1.6l-3 3A3 3 0 1 1 10 9.8l3-3A3 3 0 0 1 11.4 5Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M13.2 4c0-.6.5-1 1-1H20c.6 0 1 .4 1 1v5.8a1 1 0 1 1-2 0V6.4l-6.2 6.2a1 1 0 0 1-1.4-1.4L17.6 5h-3.4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">In new window</span>
        </a>
        <div id="tooltip-new-window" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          In new window
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between">
      <div class="{{ $attributes->get('class', '') }} flex items-center gap-4">
        <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/avatar-4.png" alt="Avatar" />
        <div class="{{ $attributes->get('class', '') }} font-semibold dark:text-white">
          <div>Contact</div>
          <button
            id="mailDetailsDropdownButton"
            type="button"
            data-dropdown-toggle="mailDetailsDropdown"
            class="{{ $attributes->get('class', '') }} inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
          >
            to Flowbite Support, me
            <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M18.4 10.3A2 2 0 0 0 17 7H7a2 2 0 0 0-1.5 3.3l4.9 5.9a2 2 0 0 0 3 0l5-6Z" clip-rule="evenodd" />
            </svg>
          </button>
          <div id="mailDetailsDropdown" class="{{ $attributes->get('class', '') }} z-50 hidden w-[360px] rounded-sm bg-white p-4 shadow-sm dark:bg-gray-700">
            <ul class="{{ $attributes->get('class', '') }} space-y-2 text-sm font-normal" aria-labelledby="mailDetailsDropdownButton">
              <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
                <div class="{{ $attributes->get('class', '') }} w-16">From:</div>
                <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">Joseph (name@example.com)</div>
              </li>
              <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
                <div class="{{ $attributes->get('class', '') }} w-16">To:</div>
                <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">name@company.com</div>
              </li>
              <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
                <div class="{{ $attributes->get('class', '') }} w-16">Date:</div>
                <time class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400" datetime="2025-02-06 10:19">Feb 6, 2025, 10:19AM </time>
              </li>
              <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
                <div class="{{ $attributes->get('class', '') }} w-16">Subject:</div>
                <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">I need help with my purchase</div>
              </li>
              <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
                <div class="{{ $attributes->get('class', '') }} w-16">Security:</div>
                <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">Standard encryption (TLS) <a class="{{ $attributes->get('class', '') }} font-medium text-gray-900 underline hover:no-underline dark:text-white" href="#">Learn more</a></div>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex items-center">
        <p class="{{ $attributes->get('class', '') }} me-4 hidden text-xs text-gray-500 dark:text-gray-400 sm:flex sm:text-sm">Mon,<time datetime="2025-07-31 15:20">Jul 31, 3:20 PM</time> (2 hours ago)</p>
        <a
          href="#"
          data-tooltip-target="tooltip-favorites"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path
              stroke="currentColor"
              stroke-width="2"
              d="M11 5.1a1 1 0 0 1 2 0l1.7 4c.1.4.4.6.8.6l4.5.4a1 1 0 0 1 .5 1.7l-3.3 2.8a1 1 0 0 0-.3 1l1 4a1 1 0 0 1-1.5 1.2l-3.9-2.3a1 1 0 0 0-1 0l-4 2.3a1 1 0 0 1-1.4-1.1l1-4.1c.1-.4 0-.8-.3-1l-3.3-2.8a1 1 0 0 1 .5-1.7l4.5-.4c.4 0 .7-.2.8-.6l1.8-4Z"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Favorites</span>
        </a>
        <div id="tooltip-favorites" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Add to favorites
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a href="#" data-tooltip-target="tooltip-reply" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 8H11V6.1c0-.9-.9-1.4-1.5-.9L4.4 9.7a1.2 1.2 0 0 0 0 1.8l5 4.4c.7.6 1.6 0 1.6-.8v-2h2a3 3 0 0 1 3 3V19a5.6 5.6 0 0 0-1.5-11Z" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Reply</span>
        </a>
        <div id="tooltip-reply" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Reply
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </div>
  </div>
  <article>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">Dear Flowbite team,</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      I hope this email finds you well. My name is Jospeh McFallen, and I am reaching out to inquire about the design services offered by your esteemed company. I came across your portfolio and was truly impressed by the innovative and
      captivating designs showcased.
    </p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      I am currently in the process of launching a Flowbite APP and believe that a distinctive and creative design is essential for setting the right tone and establishing a strong brand presence. Your expertise in this field caught my
      attention, and I am keen to explore the possibility of collaborating with your talented team.
    </p>
    <p class="{{ $attributes->get('class', '') }} mb-4 mt-6 text-gray-500 dark:text-gray-400">Here are some specific points I would like to discuss:</p>
    <ol class="{{ $attributes->get('class', '') }} list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
      <li>
        <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Brand Identity:</span> I am interested in creating a unique brand identity that resonates with our target audience. This includes logo design, color palette selection, and
        other visual elements that can effectively communicate our mission and values.
      </li>
      <li>
        <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Website Design:</span> As our digital storefront, the website needs to be user-friendly, visually engaging, and reflective of our brand identity. I would like to explore your
        approach to web design and understand how we can create an immersive online experience for our visitors.
      </li>
      <li>
        <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Print Collateral:</span> We also require various print materials, such as business cards, brochures, and promotional materials. I would love to see examples of your print
        design work and discuss how we can make a lasting impact through these materials.
      </li>
      <li>
        <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Packaging Design:</span> Since our product(s) will be an integral part of our brand, we aim to have packaging that is not only aesthetically pleasing but also functional and
        environmentally friendly. I am curious to know about your experience in creating standout packaging designs.
      </li>
      <li>
        <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Packaging Design:</span> Since our product(s) will be an integral part of our brand, we aim to have packaging that is not only aesthetically pleasing but also functional and
        environmentally friendly. I am curious to know about your experience in creating standout packaging designs.
      </li>
      <li>
        <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Timeline and Pricing:</span> Understanding the timeline and cost of your services is crucial for our planning process. I would appreciate it if you could provide some insights
        into your typical project timelines and your pricing structure.
      </li>
    </ol>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      I am eager to explore the potential of working together and believe that your creative vision aligns perfectly with our project goals. Your portfolio speaks volumes about your talent, and I am excited about the prospect of bringing
      fresh, imaginative ideas to life with your team.
    </p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      Please let me know if you would be available for a brief call or meeting to further discuss our requirements and explore the possibilities of collaboration. I am confident that together, we can create something exceptional that leaves
      a lasting impression on our audience.
    </p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">Looking forward to your positive response.</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      Best regards,<br />
      Joseph McFall, CEO & Founder Digital Things LLC
    </p>
  </article>
</div>
<div class="{{ $attributes->get('class', '') }} mt-2 mb-4 flex items-center space-x-4 px-4 lg:px-8">
  <a
    href="@{{ < ref "mailing/reply" > }}"
    class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
  >
    <svg class="{{ $attributes->get('class', '') }} -ms-2 me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 8H11V6.1c0-.9-.9-1.4-1.5-.9L4.4 9.7a1.2 1.2 0 0 0 0 1.8l5 4.4c.7.6 1.6 0 1.6-.8v-2h2a3 3 0 0 1 3 3V19a5.6 5.6 0 0 0-1.5-11Z" />
    </svg>
    Reply
  </a>
  <a
    href="@{{ < ref "mailing/reply" > }}"
    class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
  >
    <svg class="{{ $attributes->get('class', '') }} -ms-2 me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <path
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="m8.8 6-5.6 5a1 1 0 0 0 0 1.5l5.7 5m5.7-3.1V16a1 1 0 0 1-1.5.8l-5.2-4.2a1.1 1.1 0 0 1 0-1.7l5.2-4.2a1 1 0 0 1 1.5.8v1.7c3.3 0 6 3 6 6.7v1.3a.7.7 0 0 1-1.4.4 5.2 5.2 0 0 0-4.6-3.2h0Z"
      />
    </svg>
    Reply all
  </a>
  <a
    href="@{{ < ref "mailing/reply" > }}"
    class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border  border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
  >
    <svg class="{{ $attributes->get('class', '') }} -ms-2 me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.2 19c-1-3.2 1-10.8 8.3-10.8V6.1a1 1 0 0 1 1.6-.9l5.5 4.3a1.1 1.1 0 0 1 0 1.7L14 15.6a1 1 0 0 1-1.6-1v-2c-7.2 1-8.3 6.4-8.3 6.4Z" />
    </svg>
    Forward
  </a>
</div>
