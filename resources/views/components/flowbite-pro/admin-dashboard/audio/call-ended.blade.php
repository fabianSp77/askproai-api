@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Audio Call Ended Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
group: audio
page: call-ended
---

<div class="{{ $attributes->get('class', '') }} relative flex h-screen w-full flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
  <div class="{{ $attributes->get('class', '') }} max-h-auto relative mb-10 flex w-full flex-col items-center justify-center">
    <img src="/images/audio-call/call-image.png" alt="" class="{{ $attributes->get('class', '') }} max-h-auto mb-3 h-36 w-36 rounded-full" />
    <button type="button" class="{{ $attributes->get('class', '') }} mb-2 text-3xl font-bold text-gray-900 hover:underline dark:text-white">Robert Brown</button>
    <p class="{{ $attributes->get('class', '') }} mb-4 text-lg font-normal text-gray-500 dark:text-gray-400">Call ended (12:32 minutes)</p>
    <button
      type="button"
      data-modal-toggle="feedback-modal"
      data-modal-target="feedback-modal"
      class="{{ $attributes->get('class', '') }} mb-2 me-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
    >
      Submit feedback
    </button>
  </div>
</div>

<div id="feedback-modal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Rate your conversation</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="feedback-modal"
        >
          <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} space-y-4">
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <svg class="{{ $attributes->get('class', '') }} h-6 w-6 cursor-pointer text-yellow-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
              <path
                d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"
              />
            </svg>
            <svg class="{{ $attributes->get('class', '') }} ms-2 h-6 w-6 cursor-pointer text-yellow-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
              <path
                d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"
              />
            </svg>
            <svg class="{{ $attributes->get('class', '') }} ms-2 h-6 w-6 cursor-pointer text-yellow-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
              <path
                d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"
              />
            </svg>
            <svg class="{{ $attributes->get('class', '') }} ms-2 h-6 w-6 cursor-pointer text-gray-300 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
              <path
                d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"
              />
            </svg>
            <svg class="{{ $attributes->get('class', '') }} ms-2 h-6 w-6 cursor-pointer text-gray-300 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
              <path
                d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} ms-2 text-lg font-bold text-gray-900 dark:text-white">4.0</span>
          </div>
          <div>
            <label for="feedback" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Your feedback <span class="{{ $attributes->get('class', '') }} dark:text-gay-400 font-normal text-gray-500">(30 — 3000 characters)</span></label>
            <textarea
              id="feedback"
              rows="5"
              class="{{ $attributes->get('class', '') }} mb-2 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              required=""
            ></textarea>
            <p class="{{ $attributes->get('class', '') }} ms-auto text-xs text-gray-500 dark:text-gray-400">Describe your experience or suggestions in detail.</p>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex w-full justify-center space-x-3">
            <button
              type="submit"
              class="{{ $attributes->get('class', '') }} w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              Submit
            </button>
            <button
              type="button"
              data-modal-toggle="feedback-modal"
              aria-controls="feedback-modal"
              class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              Cancel
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
