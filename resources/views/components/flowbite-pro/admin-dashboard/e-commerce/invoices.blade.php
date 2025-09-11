@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS All Invoices Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: e-commerce
page: invoices
---

<div class="{{ $attributes->get('class', '') }} grid grid-cols-12 gap-4 bg-white dark:bg-gray-900">
  <div class="{{ $attributes->get('class', '') }} col-span-full mx-4 mt-4 ">
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
            <a href="#" class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">E-commerce</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Invoices</span>
          </div>
        </li>
      </ol>
    </nav>
    <h1 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-semibold text-gray-900 dark:text-white">My Invoices</h1>
    <div class="{{ $attributes->get('class', '') }} grid grid-cols-2 gap-4 lg:grid-cols-4">
      <div class="{{ $attributes->get('class', '') }} space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
        <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
        <h2 class="{{ $attributes->get('class', '') }} text-2xl font-bold leading-none text-gray-900 dark:text-white">$76,940</h2>
        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">350 invoices</p>
      </div>
      <div class="{{ $attributes->get('class', '') }} space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
        <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Unpaid</span>
        <h2 class="{{ $attributes->get('class', '') }} text-2xl font-bold leading-none text-gray-900 dark:text-white">$23,145</h2>
        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">64 invoices</p>
      </div>
      <div class="{{ $attributes->get('class', '') }} space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
        <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
        <h2 class="{{ $attributes->get('class', '') }} text-2xl font-bold leading-none text-gray-900 dark:text-white">$7,431</h2>
        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">14 invoices</p>
      </div>
      <div class="{{ $attributes->get('class', '') }} space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
        <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Overdue</span>
        <h2 class="{{ $attributes->get('class', '') }} text-2xl font-bold leading-none text-gray-900 dark:text-white">$2,826</h2>
        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">10 invoices</p>
      </div>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} relative col-span-full">
    <div class="{{ $attributes->get('class', '') }} mx-4 border-b dark:border-gray-700 border-gray-200">
      <div class="{{ $attributes->get('class', '') }} flex flex-col-reverse items-center justify-between pb-4 md:flex-row md:space-x-4">
        <div class="{{ $attributes->get('class', '') }} flex w-full flex-col space-y-3 md:flex-row md:items-center md:space-y-0 lg:w-2/3">
          <form class="{{ $attributes->get('class', '') }} w-full flex-1 md:mr-4 md:max-w-sm">
            <label for="default-search" class="{{ $attributes->get('class', '') }} sr-only text-sm font-medium text-gray-900 dark:text-white">Search</label>
            <div class="{{ $attributes->get('class', '') }} relative">
              <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                type="search"
                id="default-search"
                class="{{ $attributes->get('class', '') }} block w-full min-w-64 rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Search invoice"
                required=""
              />
              <button
                type="submit"
                class="{{ $attributes->get('class', '') }} absolute bottom-0 right-0 top-0 rounded-r-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
              >
                Search
              </button>
            </div>
          </form>
          <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
            <div>
              <button
                id="filterDropdownButton"
                data-dropdown-toggle="filterDropdown"
                data-dropdown-ignore-click-outside-class="{{ $attributes->get('class', '') }} datepicker"
                class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
                type="button"
              >
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M5.05 3C3.291 3 2.352 5.024 3.51 6.317l5.422 6.059v4.874c0 .472.227.917.613 1.2l3.069 2.25c1.01.742 2.454.036 2.454-1.2v-7.124l5.422-6.059C21.647 5.024 20.708 3 18.95 3H5.05Z" />
                </svg>
                Filter
                <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                </svg>
              </button>
              <!-- Dropdown menu -->
              <form action="#" method="get" id="filterDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-80 space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-700" aria-labelledby="dropdownDefault">
                <h5 id="drawer-label" class="{{ $attributes->get('class', '') }} inline-flex items-center font-medium text-gray-500 dark:text-gray-400">Filter</h5>

                <div date-rangepicker class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
                  <div class="{{ $attributes->get('class', '') }} relative">
                    <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
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
                      class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                      placeholder="Start date"
                    />
                  </div>
                  <div class="{{ $attributes->get('class', '') }} relative">
                    <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
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
                      class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                      placeholder="End date"
                    />
                  </div>
                </div>

                <!-- Status -->
                <div>
                  <label class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"> Status </label>
                  <ul class="{{ $attributes->get('class', '') }} grid w-full grid-cols-2 gap-3">
                    <li>
                      <input type="checkbox" id="gaming" name="category" value="" class="{{ $attributes->get('class', '') }} peer hidden" />
                      <label
                        for="gaming"
                        class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-50 p-2 text-center text-sm font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:border-primary-600 dark:peer-checked:bg-primary-600"
                      >
                        Paid
                      </label>
                    </li>
                    <li>
                      <input type="checkbox" id="unpaid" name="category" value="" class="{{ $attributes->get('class', '') }} peer hidden" checked />
                      <label
                        for="unpaid"
                        class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-50 p-2 text-center text-sm font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:border-primary-600 dark:peer-checked:bg-primary-600"
                      >
                        Unpaid
                      </label>
                    </li>
                    <li>
                      <input type="checkbox" id="phone" name="category" value="" class="{{ $attributes->get('class', '') }} peer hidden" />
                      <label
                        for="phone"
                        class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-50 p-2 text-center text-sm font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:border-primary-600 dark:peer-checked:bg-primary-600"
                      >
                        Pending
                      </label>
                    </li>
                    <li>
                      <input type="checkbox" id="tv-monitor" name="category" value="" class="{{ $attributes->get('class', '') }} peer hidden" />
                      <label
                        for="tv-monitor"
                        class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-50 p-2 text-center text-sm font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:border-primary-600 dark:peer-checked:bg-primary-600"
                      >
                        Overdue
                      </label>
                    </li>
                  </ul>
                </div>

                <div class="{{ $attributes->get('class', '') }} mt-6 flex space-x-4">
                  <button
                    type="submit"
                    class="{{ $attributes->get('class', '') }} dark: rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                  >
                    Show 32 Results
                  </button>
                  <button
                    type="reset"
                    class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  >
                    Reset
                  </button>
                </div>
              </form>
            </div>
            <div>
              <button
                id="configurationDropdownButton"
                data-dropdown-toggle="configurationDropdown"
                type="button"
                class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 24 24" fill="currentColor" class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true">
                  <path
                    fill-rule="evenodd"
                    d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a.798.798 0 01-.517.608 7.45 7.45 0 00-.478.198.798.798 0 01-.796-.064l-.453-.324a1.875 1.875 0 00-2.416.2l-.243.243a1.875 1.875 0 00-.2 2.416l.324.453a.798.798 0 01.064.796 7.448 7.448 0 00-.198.478.798.798 0 01-.608.517l-.55.092a1.875 1.875 0 00-1.566 1.849v.344c0 .916.663 1.699 1.567 1.85l.549.091c.281.047.508.25.608.517.06.162.127.321.198.478a.798.798 0 01-.064.796l-.324.453a1.875 1.875 0 00.2 2.416l.243.243c.648.648 1.67.733 2.416.2l.453-.324a.798.798 0 01.796-.064c.157.071.316.137.478.198.267.1.47.327.517.608l.092.55c.15.903.932 1.566 1.849 1.566h.344c.916 0 1.699-.663 1.85-1.567l.091-.549a.798.798 0 01.517-.608 7.52 7.52 0 00.478-.198.798.798 0 01.796.064l.453.324a1.875 1.875 0 002.416-.2l.243-.243c.648-.648.733-1.67.2-2.416l-.324-.453a.798.798 0 01-.064-.796c.071-.157.137-.316.198-.478.1-.267.327-.47.608-.517l.55-.091a1.875 1.875 0 001.566-1.85v-.344c0-.916-.663-1.699-1.567-1.85l-.549-.091a.798.798 0 01-.608-.517 7.507 7.507 0 00-.198-.478.798.798 0 01.064-.796l.324-.453a1.875 1.875 0 00-.2-2.416l-.243-.243a1.875 1.875 0 00-2.416-.2l-.453.324a.798.798 0 01-.796.064 7.462 7.462 0 00-.478-.198.798.798 0 01-.517-.608l-.091-.55a1.875 1.875 0 00-1.85-1.566h-.344zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"
                    clip-rule="evenodd"
                  />
                </svg>
                Configurations
                <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                </svg>
              </button>
              <div id="configurationDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-52 rounded-lg bg-white p-2 shadow-sm dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} space-y-1 pb-3 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="configurationDropdownButton">
                  <li>
                    <div class="{{ $attributes->get('class', '') }} flex rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                      <label class="{{ $attributes->get('class', '') }} relative inline-flex w-full cursor-pointer items-center">
                        <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} peer sr-only" />
                        <div
                          class="{{ $attributes->get('class', '') }} peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-500 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:translate-x-[-100%]"
                        ></div>
                        <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Show ammount</span>
                      </label>
                    </div>
                  </li>
                  <li>
                    <div class="{{ $attributes->get('class', '') }} flex rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                      <label class="{{ $attributes->get('class', '') }} relative inline-flex w-full cursor-pointer items-center">
                        <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} peer sr-only" />
                        <div
                          class="{{ $attributes->get('class', '') }} peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-500 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:translate-x-[-100%]"
                        ></div>
                        <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Show status</span>
                      </label>
                    </div>
                  </li>
                  <li>
                    <div class="{{ $attributes->get('class', '') }} flex rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                      <label class="{{ $attributes->get('class', '') }} relative inline-flex w-full cursor-pointer items-center">
                        <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} peer sr-only" />
                        <div
                          class="{{ $attributes->get('class', '') }} peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-500 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:translate-x-[-100%]"
                        ></div>
                        <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Show due date</span>
                      </label>
                    </div>
                  </li>
                  <li>
                    <div class="{{ $attributes->get('class', '') }} flex rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                      <label class="{{ $attributes->get('class', '') }} relative inline-flex w-full cursor-pointer items-center">
                        <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} peer sr-only" />
                        <div
                          class="{{ $attributes->get('class', '') }} peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-500 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:translate-x-[-100%]"
                        ></div>
                        <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Show start date</span>
                      </label>
                    </div>
                  </li>
                </ul>
                <div>
                  <button
                    type="reset"
                    class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                  >
                    Reset
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} mb-3 flex w-full shrink-0 flex-col items-stretch justify-end md:mb-0 md:w-auto md:flex-row md:items-center md:space-x-3">
          <a
            href="../create-invoice"
            class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v6.41A7.5 7.5 0 1 0 10.5 22H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd" />
              <path fill-rule="evenodd" d="M9 16a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm6-3a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z" clip-rule="evenodd" />
            </svg>
            Create an invoice
          </a>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} mx-4 flex flex-wrap py-4">
      <div class="{{ $attributes->get('class', '') }} mr-4 hidden items-center text-sm font-medium text-gray-900 dark:text-white md:flex">Show only:</div>
      <div class="{{ $attributes->get('class', '') }} flex flex-wrap gap-4">
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            checked
            id="inline-radio"
            type="radio"
            value=""
            name="inline-radio-group"
            class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="inline-radio" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">All</label>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            id="inline-2-radio"
            type="radio"
            value=""
            name="inline-radio-group"
            class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="inline-2-radio" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Unpaid</label>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            id="inline-3-radio"
            type="radio"
            value=""
            name="inline-radio-group"
            class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="inline-3-radio" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Paid</label>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            id="inline-4-radio"
            type="radio"
            value=""
            name="inline-radio-group"
            class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="inline-4-radio" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Pending</label>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} overflow-x-auto relative">
      <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="{{ $attributes->get('class', '') }} p-4">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-all"
                  type="checkbox"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-all" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Invoice ID</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Client
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Email</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Start Date
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Due Date
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Amount
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Status
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">
              <span class="{{ $attributes->get('class', '') }} sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846325</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Flowbite LLC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">flowbite@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">02 Mar 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">09 Mar 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$466</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-1-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-1-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-1-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-1-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <li>
                    <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </a>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveModalButton"
                      data-modal-target="archiveModal"
                      data-modal-toggle="archiveModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846326</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Jese Leos</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">06 Mar 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">07 Mar 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$2000</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Unpaid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-2-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-2-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-2-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-2-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846327</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Bonnie Green</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@company.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">08 Mar 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">12 Mar 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$245</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-3-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-3-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-3-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-3-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846328</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Themesberg LLC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">company@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">15 Apr 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">18 Apr 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$90</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Overdue</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-4-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-4-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-4-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-4-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846329</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Micheal Gough</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">18 Apr 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">21 Apr 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$3040</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-5-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-5-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-5-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-5-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846330</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Lana Byrd</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">21 Apr 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">24 Apr 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$2999</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-6-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-6-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-6-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-6-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846331</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Netflix LLC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">company@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">02 May 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">05 May 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$1870</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-7-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-7-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-7-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-7-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846332</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Leslie Livingston</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">05 May 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">08 May 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$5067</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Overdue</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-8-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-8-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-8-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-8-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846333</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Bergside LLC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">company@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">29 May 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">02 May 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$60</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-9-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-9-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-9-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-9-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846334</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Robert Brown</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">17 Jun 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">20 Jun 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$499</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-10-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-10-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-10-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-10-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846335</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Joseph McFall</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">19 Jun 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">22 Jun 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$76</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-11-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-11-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-11-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-11-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846336</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Neil Sims</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">28 Jun 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">31 Jul 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$284</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-12-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-12-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-12-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-12-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846337</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Roberta Casas</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">29 Jun 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 Jul 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$7675</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Unpaid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-13-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-13-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-13-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-13-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846338</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Pixel LLC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">company@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">02 Jul 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">05 Jul 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$657</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-14-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-14-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-14-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-14-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846339</a></th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">Neil Sims</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">name@example.com</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">07 Jul 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">10 Jul 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$999</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <button
                id="invoice-15-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-15-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-15-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-15-dropdown-button">
                  <li>
                    <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="{{ $attributes->get('class', '') }} w-full">
      <!-- Table Footer -->
      <div class="{{ $attributes->get('class', '') }} relative overflow-hidden">
        <nav class="{{ $attributes->get('class', '') }} flex flex-col items-start justify-between space-y-3 p-4 md:flex-row md:items-center md:space-y-0" aria-label="Table navigation">
          <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">Showing <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1-15</span> of <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1000</span></span>
          <ul class="{{ $attributes->get('class', '') }} inline-flex items-stretch -space-x-px">
            <li>
              <a
                href="#"
                class="{{ $attributes->get('class', '') }} ml-0 flex h-full items-center justify-center rounded-l-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <span class="{{ $attributes->get('class', '') }} sr-only">Previous</span>
                <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                </svg>
              </a>
            </li>
            <li>
              <a
                href="#"
                class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                1
              </a>
            </li>
            <li>
              <a
                href="#"
                class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                2
              </a>
            </li>
            <li>
              <a
                href="#"
                aria-current="page"
                class="{{ $attributes->get('class', '') }} z-10 flex items-center justify-center border border-primary-300 bg-primary-50 px-3 py-2 text-sm leading-tight text-primary-600 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white"
              >
                3
              </a>
            </li>
            <li>
              <a
                href="#"
                class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                ...
              </a>
            </li>
            <li>
              <a
                href="#"
                class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                100
              </a>
            </li>
            <li>
              <a
                href="#"
                class="{{ $attributes->get('class', '') }} flex h-full items-center justify-center rounded-r-lg border border-gray-300 bg-white px-3 py-1.5 leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <span class="{{ $attributes->get('class', '') }} sr-only">Next</span>
                <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- Delete Invoice modal -->
<div id="deleteInvoiceModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteInvoiceModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <svg class="{{ $attributes->get('class', '') }} mx-auto mb-3.5 h-11 w-11 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
          clip-rule="evenodd"
        />
      </svg>

      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">You are about to delete this invoice, this cannot be undone.</p>
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="deleteInvoiceModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
        >
          Cancel
        </button>
        <button type="submit" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
          <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} -ml-1 mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          Delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Archive invoice modal -->
<div id="archiveModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="archiveModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <svg class="{{ $attributes->get('class', '') }} mx-auto mb-3.5 h-11 w-11 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
      </svg>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-300">You've chosen to archive this invoice. Do you want to continue?</p>
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="archiveModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Yes, I do
        </button>
      </div>
    </div>
  </div>
</div>
