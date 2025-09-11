@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Notifications - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: pages
page: notifications
---

<div class="{{ $attributes->get('class', '') }} mx-4 mb-4 grid grid-cols-12">
  <div class="{{ $attributes->get('class', '') }} col-span-full mt-4">
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
            <a href="#" class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Account</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Notifications</span>
          </div>
        </li>
      </ol>
    </nav>
    <h1 class="{{ $attributes->get('class', '') }} text-xl font-bold text-gray-900 dark:text-white">Notifications</h1>
  </div>
  <div class="{{ $attributes->get('class', '') }} col-span-full my-4 space-y-4 md:mb-8">
    <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl text-gray-500 dark:text-gray-400">Today</h2>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-10.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">New message from <a data-popover-target="popover-jese-profile" class="{{ $attributes->get('class', '') }} cursor-pointer font-medium text-gray-900 underline hover:no-underline dark:text-white">Jese Leos:</a> "Hey, what's up? All set for the showcase"</p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-xs sm:text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            a few moments ago
          </div>
          <div
            data-popover
            id="popover-jese-profile"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
          >
            <div class="{{ $attributes->get('class', '') }} p-3">
              <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
                <a href="#">
                  <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/jese-leos.png" alt="Avatar" />
                </a>
                <div>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                  >
                    Follow
                  </button>
                </div>
              </div>
              <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
                <a href="#">Jese Leos</a>
              </p>
              <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@jesleos</a>
              </p>
              <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Open-source contributor. Designing <a href="#" class="{{ $attributes->get('class', '') }} text-primary-600 hover:underline dark:text-primary-500">flowbite.com</a>.</p>
              <ul class="{{ $attributes->get('class', '') }} flex text-sm">
                <li class="{{ $attributes->get('class', '') }} me-2">
                  <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                    <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">3,497</span>
                    <span>Following</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                    <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">758</span>
                    <span>Followers</span>
                  </a>
                </li>
              </ul>
            </div>
            <div data-popper-arrow></div>
          </div>
        </div>
      </div>
      <button
        id="notification-1-dropdown-button"
        type="button"
        data-dropdown-toggle="notification-1-dropdown"
        class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
        </svg>
      </button>
      <div id="notification-1-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
        <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-1-dropdown-button">
          <li>
            <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                  clip-rule="evenodd"
                />
              </svg>
              View
            </button>
          </li>
          <li>
            <button id="deleteNotificationButton1"
            data-modal-target="deleteNotificationModal"
            data-modal-toggle="deleteNotificationModal" type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                  clip-rule="evenodd"
                />
              </svg>
              Delete
            </button>
          </li>
        </ul>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-4.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-user-profile" class="{{ $attributes->get('class', '') }} font-medium text-gray-900 underline hover:no-underline dark:text-white">Joseph Mcfall</a> and
            <a href="#" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">5 others</a> started following you.
          </p>
          <div
            data-popover
            id="popover-user-profile"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
          >
            <div class="{{ $attributes->get('class', '') }} p-3">
              <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
                <a href="#">
                  <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/avatar-4.png" alt="Avatar" />
                </a>
                <div>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                  >
                    Follow
                  </button>
                </div>
              </div>
              <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
                <a href="#">Joseph Mcfall</a>
              </p>
              <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@josepmc</a>
              </p>
              <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Building <a href="#" class="{{ $attributes->get('class', '') }} text-primary-600 hover:underline dark:text-primary-500">flowbite.com</a>.</p>
              <ul class="{{ $attributes->get('class', '') }} flex text-sm">
                <li class="{{ $attributes->get('class', '') }} me-2">
                  <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                    <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">799</span>
                    <span>Following</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                    <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">3,758</span>
                    <span>Followers</span>
                  </a>
                </li>
              </ul>
            </div>
            <div data-popper-arrow></div>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            32 minutes ago
          </div>
        </div>
      </div>
      <div>
        <button
          id="notification-2-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-2-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-2-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-2-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button id="deleteNotificationButton2"
              data-modal-target="deleteNotificationModal"
              data-modal-toggle="deleteNotificationModal" type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} sm:items-cente rme-8 flex items-start">
        <img src="/images/users/avatar-1.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-bonnie-profile" class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white underline hover:no-underline cursor-pointer">Bonnie Green</a> and <a href="#" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">141 others</a> love your story. See it and view more stories.
          </p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            1 hour ago
          </div>
          <div
          data-popover
          id="popover-bonnie-profile"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/bonnie-green.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Bonnie Green</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@bonnie</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Designing <a href="#" class="{{ $attributes->get('class', '') }} text-primary-600 hover:underline dark:text-primary-500">flowbite.com</a>.</p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">497</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">956</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-3-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-3-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-3-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-3-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button id="deleteNotificationButton3"
              data-modal-target="deleteNotificationModal"
              data-modal-toggle="deleteNotificationModal" type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-2.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-leslie-profile" class="{{ $attributes->get('class', '') }} cursor-pointer font-medium underline hover:no-underline text-gray-900 dark:text-white">Leslie Livingstone</a> mentioned you in a comment: <a href="#" class="{{ $attributes->get('class', '') }} font-medium text-primary-700 hover:underline dark:text-primary-500">@bonnie.green</a> what do you
            say?
          </p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            4 hours ago
          </div>
        </div>
        <div
        data-popover
        id="popover-leslie-profile"
        role="tooltip"
        class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
      >
        <div class="{{ $attributes->get('class', '') }} p-3">
          <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
            <a href="#">
              <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/leslie-livingston.png" alt="Avatar" />
            </a>
            <div>
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
              >
                Follow
              </button>
            </div>
          </div>
          <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
            <a href="#">Leslie Livingston</a>
          </p>
          <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
            <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@leslieLi</a>
          </p>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">React Native Developer</p>
          <ul class="{{ $attributes->get('class', '') }} flex text-sm">
            <li class="{{ $attributes->get('class', '') }} me-2">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1,324</span>
                <span>Following</span>
              </a>
            </li>
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">201k</span>
                <span>Followers</span>
              </a>
            </li>
          </ul>
        </div>
        <div data-popper-arrow></div>
      </div>
      </div>
      <div>
        <button
          id="notification-4-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-4-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-4-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-4-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button id="deleteNotificationButton4"
              data-modal-target="deleteNotificationModal"
              data-modal-toggle="deleteNotificationModal" type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-10.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-robert-profile" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">Robert Brown</a> posted a new video:
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium text-primary-700 underline hover:no-underline dark:text-primary-500">Glassmorphism - learn how to implement the new design trend.</a>
          </p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            10 hours ago
          </div>
          <div
          data-popover
          id="popover-robert-profile"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/robert-brown.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Robert Brown</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@robert15</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Web Developer & Startup enthusiast. </p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">2,834</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">30.3k</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-5-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-5-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-5-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-5-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button id="deleteNotificationButton5"
              data-modal-target="deleteNotificationModal"
              data-modal-toggle="deleteNotificationModal" type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} col-span-full space-y-4">
    <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl text-gray-500 dark:text-gray-400">Yesterday</h2>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-6.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base"><a href="#" data-popover-target="popover-roberta-profile" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">Roberta Casas</a> liked your <a href="#" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">comment</a> “Welcome to Flowbite community”</p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            1 day ago
          </div>
          <div
          data-popover
          id="popover-roberta-profile"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/roberta-casas.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Roberta Casas</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@bonnie</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Designing <a href="#" class="{{ $attributes->get('class', '') }} text-primary-600 hover:underline dark:text-primary-500">flowbite.com</a>.</p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">104</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">2.4M</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-6-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-6-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-6-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-6-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button id="deleteNotificationButton6"
              data-modal-target="deleteNotificationModal"
              data-modal-toggle="deleteNotificationModal" type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-4.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-lana-profile" class="{{ $attributes->get('class', '') }} hover:no-underline underline font-medium text-gray-900 dark:text-white">Lana Byrd</a> tagged you in a <a href="#" data-popover-target="popover-image" class="{{ $attributes->get('class', '') }} text-primary-700 underline hover:no-underline dark:text-primary-500">photo</a>.
          </p>

          <div
            data-popover
            id="popover-image"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} dark:border-gray-00 invisible absolute z-10 inline-block w-48 rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
          >
            <img src="/images/notifications/fundraising-2.png" alt="Avatar" class="{{ $attributes->get('class', '') }} rounded-sm" />
            <div data-popper-arrow></div>
          </div>

          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            1 day ago
          </div>
          <div
          data-popover
          id="popover-lana-profile"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/lana-byrd.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Lana Byrd</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@bonnie</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Designing <a href="#" class="{{ $attributes->get('class', '') }} text-primary-600 hover:underline dark:text-primary-500">flowbite.com</a>.</p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">497</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">956</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-7-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-7-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-7-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-7-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button id="deleteNotificationButton7"
              data-modal-target="deleteNotificationModal"
              data-modal-toggle="deleteNotificationModal type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-1.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-bonnie-profile-2" class="{{ $attributes->get('class', '') }} hover:no-underline underline font-medium text-gray-900 dark:text-white">Bonnie Green</a> and <a href="#" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">141 others</a> love your story. See it and view more stories.
          </p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            1 day ago
          </div>
          <div
          data-popover
          id="popover-bonnie-profile-2"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/bonnie-green.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Bonnie Green</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@bonnie</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Designing <a href="#" class="{{ $attributes->get('class', '') }} text-primary-600 hover:underline dark:text-primary-500">flowbite.com</a>.</p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">497</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">956</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-8-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-8-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-8-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-8-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button
                id="deleteNotificationButton8"
                data-modal-target="deleteNotificationModal"
                data-modal-toggle="deleteNotificationModal"
                type="button"
                class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
              >
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-2.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-leslie-profile-2" class="{{ $attributes->get('class', '') }} hover-no-underline underline font-medium text-gray-900 dark:text-white">Leslie Livingstone</a> mentioned you in a comment: <a href="#" class="{{ $attributes->get('class', '') }} font-medium text-primary-700 hover:underline dark:text-primary-500">@bonnie.green</a> what do you
            say?
          </p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            1 day ago
          </div>
          <div
          data-popover
          id="popover-leslie-profile-2"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/leslie-livingston.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Leslie Livingston</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@leslieLi</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">React Native Developer</p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1,324</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">201k</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-9-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-9-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-9-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-9-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button
                id="deleteNotificationButton9"
                data-modal-target="deleteNotificationModal"
                data-modal-toggle="deleteNotificationModal"
                type="button"
                class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
              >
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
      <div class="{{ $attributes->get('class', '') }} me-8 flex items-start sm:items-center">
        <img src="/images/users/avatar-10.png" alt="Avatar" class="{{ $attributes->get('class', '') }} me-3 flex h-6 w-6 rounded-full sm:h-8 sm:w-8" />
        <div class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
          <p class="{{ $attributes->get('class', '') }} mb-1 text-sm sm:text-base">
            <a href="#" data-popover-target="popover-robert-profile-2" class="{{ $attributes->get('class', '') }} underline hover:no-underline font-medium text-gray-900 dark:text-white">Robert Brown</a> posted a new video:
            <a href="#" class="{{ $attributes->get('class', '') }} font-medium text-primary-700 underline hover:no-underline dark:text-primary-500">Glassmorphism - learn how to implement the new design trend.</a>
          </p>
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
            </svg>
            10 hours ago
          </div>
          <div
          data-popover
          id="popover-robert-profile-2"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} invisible absolute z-10 inline-block w-64 rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div class="{{ $attributes->get('class', '') }} p-3">
            <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
              <a href="#">
                <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/robert-brown.png" alt="Avatar" />
              </a>
              <div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
                  Follow
                </button>
              </div>
            </div>
            <p class="{{ $attributes->get('class', '') }} text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#">Robert Brown</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-3 text-sm font-normal">
              <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">@robert15</a>
            </p>
            <p class="{{ $attributes->get('class', '') }} mb-4 text-sm">Web Developer & Startup enthusiast. </p>
            <ul class="{{ $attributes->get('class', '') }} flex text-sm">
              <li class="{{ $attributes->get('class', '') }} me-2">
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">2,834</span>
                  <span>Following</span>
                </a>
              </li>
              <li>
                <a href="#" class="{{ $attributes->get('class', '') }} hover:underline">
                  <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">30.3k</span>
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </div>
          <div data-popper-arrow></div>
        </div>
        </div>
      </div>
      <div>
        <button
          id="notification-10-dropdown-button"
          type="button"
          data-dropdown-toggle="notification-10-dropdown"
          class="{{ $attributes->get('class', '') }} absolute end-1 top-1 inline-flex items-center rounded-md p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:relative sm:end-0 sm:top-0"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="notification-10-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="notification-10-dropdown-button">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                View
              </button>
            </li>
            <li>
              <button
                id="deleteNotificationButton10"
                data-modal-target="deleteNotificationModal"
                data-modal-toggle="deleteNotificationModal"
                type="button"
                class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
              >
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Ticket modal -->
<div id="deleteNotificationModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 me-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteNotificationModal"
      >
      <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
      </svg>
      
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-semibold text-gray-900 dark:text-white">Delete notification</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Do you want to delete this notification?</p>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="deleteNotificationModal"
          type="button"
          class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
        >
          <svg class="{{ $attributes->get('class', '') }} -ml-0.5 mr-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
          </svg>          
          Delete
        </button>
      </div>
    </div>
  </div>
</div>
