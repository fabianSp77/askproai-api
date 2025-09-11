@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS E-commerce Dashboard - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard-triple-navbar
group: dashboards
page: e-commerce
footer: true
---

<section class="{{ $attributes->get('class', '') }} bg-gray-50 antialiased dark:bg-gray-900">
  <div class="{{ $attributes->get('class', '') }} mx-auto max-w-screen-xl">
    <div class="{{ $attributes->get('class', '') }} gap-4 lg:flex">
      <!-- Sidenav -->
      <aside id="sidebar" class="{{ $attributes->get('class', '') }} hidden h-full w-64 shrink-0 overflow-y-auto border border-gray-200 bg-white p-3 shadow-xs dark:border-gray-700 dark:bg-gray-800 lg:block lg:rounded-lg">
        <button
          id="dropdownUserNameButton2"
          data-dropdown-toggle="dropdownUserName2"
          type="button"
          class="{{ $attributes->get('class', '') }} dark:hover-bg-gray-700 mb-3 flex w-full items-center justify-between rounded-lg bg-white p-2 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
          type="button"
        >
          <span class="{{ $attributes->get('class', '') }} sr-only">Open user menu</span>
          <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between">
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <img src="/images/users/jese-leos.png" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-8 rounded-md" alt="Bonnie avatar" />
              <div class="{{ $attributes->get('class', '') }} text-left">
                <div class="{{ $attributes->get('class', '') }} mb-0.5 font-semibold leading-none text-gray-900 dark:text-white">Jese Leos</div>
                <div class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400">jese@flowbite.com</div>
              </div>
            </div>
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </div>
        </button>
        <!-- Dropdown menu -->
        <div id="dropdownUserName2" class="{{ $attributes->get('class', '') }} z-10 hidden w-64 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700" data-popper-placement="bottom">
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center rounded-lg px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-600">
            <img src="/images/logo.svg" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-8 rounded-sm" alt="Michael avatar" />
            <div class="{{ $attributes->get('class', '') }} text-left">
              <div class="{{ $attributes->get('class', '') }} mb-0.5 font-semibold leading-none text-gray-900 dark:text-white">Flowbite LLC</div>
              <div class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400">company@flowbite.com</div>
            </div>
          </a>
        </div>
        <div class="{{ $attributes->get('class', '') }} mb-4 w-full border-y border-gray-100 py-4 dark:border-gray-700">
          <ul class="{{ $attributes->get('class', '') }} grid grid-cols-3 gap-2">
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} group flex flex-col items-center justify-center rounded-xl bg-primary-50 p-2.5 hover:bg-primary-100 dark:bg-primary-900 dark:hover:bg-primary-800">
                <span class="{{ $attributes->get('class', '') }} mb-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 group-hover:bg-primary-200 dark:bg-primary-800  dark:group-hover:bg-primary-700">
                  <svg class="{{ $attributes->get('class', '') }} h-5 w-5 text-primary-600 dark:text-primary-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="square"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h2m10 1a3 3 0 0 1-3 3m3-3a3 3 0 0 0-3-3m3 3h1m-4 3a3 3 0 0 1-3-3m3 3v1m-3-4a3 3 0 0 1 3-3m-3 3h-1m4-3v-1m-2.121 1.879-.707-.707m5.656 5.656-.707-.707m-4.242 0-.707.707m5.656-5.656-.707.707M12 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                    />
                  </svg>
                </span>
                <span class="{{ $attributes->get('class', '') }} text-sm font-medium text-primary-600 dark:text-primary-300">Profile</span>
              </a>
            </li>
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} group flex flex-col items-center justify-center rounded-xl bg-purple-50 p-2.5 hover:bg-purple-100 dark:bg-purple-900 dark:hover:bg-purple-800">
                <span class="{{ $attributes->get('class', '') }} mb-1 flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 group-hover:bg-purple-200 dark:bg-purple-800  dark:group-hover:bg-purple-700">
                  <svg class="{{ $attributes->get('class', '') }} h-5 w-5 text-purple-600 dark:text-purple-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10 21v-9m3-4H7.5a2.5 2.5 0 1 1 0-5c1.5 0 2.875 1.25 3.875 2.5M14 21v-9m-9 0h14v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8ZM4 8h16a1 1 0 0 1 1 1v3H3V9a1 1 0 0 1 1-1Zm12.155-5c-3 0-5.5 5-5.5 5h5.5a2.5 2.5 0 0 0 0-5Z"
                    />
                  </svg>
                </span>
                <span class="{{ $attributes->get('class', '') }} text-sm font-medium text-purple-600 dark:text-purple-300">Gifts</span>
              </a>
            </li>
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} group flex flex-col items-center justify-center rounded-xl bg-teal-50 p-2.5 hover:bg-teal-100 dark:bg-teal-900 dark:hover:bg-teal-800">
                <span class="{{ $attributes->get('class', '') }} mb-1 flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 group-hover:bg-teal-200 dark:bg-teal-800  dark:group-hover:bg-teal-700">
                  <svg class="{{ $attributes->get('class', '') }} h-5 w-5 text-teal-600 dark:text-teal-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 8H5m12 0a1 1 0 0 1 1 1v2.6M17 8l-4-4M5 8a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.6M5 8l4-4 4 4m6 4h-4a2 2 0 1 0 0 4h4a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"
                    />
                  </svg>
                </span>
                <span class="{{ $attributes->get('class', '') }} text-sm font-medium text-teal-600 dark:text-teal-300">Wallet</span>
              </a>
            </li>
          </ul>
        </div>

        <ul class="{{ $attributes->get('class', '') }} space-y-2">
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
              <svg
                class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"
                />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3">My orders</span>
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
              <svg
                class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path
                  stroke="currentColor"
                  stroke-width="2"
                  d="M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z"
                />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3 flex-1 whitespace-nowrap">Reviews</span>
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
              <svg
                class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3 flex-1 whitespace-nowrap">Delivery addresses</span>
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
              <svg
                class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z" />
                <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3 flex-1 whitespace-nowrap">Recently viewed</span>
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
              <svg
                class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.01 6.001C6.5 1 1 8 5.782 13.001L12.011 20l6.23-7C23 8 17.5 1 12.01 6.002Z" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3 flex-1 whitespace-nowrap">Favourite items</span>
            </a>
          </li>
        </ul>
        <ul class="{{ $attributes->get('class', '') }} mt-5 space-y-2 border-t border-gray-100 pt-5 dark:border-gray-700">
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
              <svg
                class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 13v-2a1 1 0 0 0-1-1h-.757l-.707-1.707.535-.536a1 1 0 0 0 0-1.414l-1.414-1.414a1 1 0 0 0-1.414 0l-.536.535L14 4.757V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.757l-1.707.707-.536-.535a1 1 0 0 0-1.414 0L4.929 6.343a1 1 0 0 0 0 1.414l.536.536L4.757 10H4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.757l.707 1.707-.535.536a1 1 0 0 0 0 1.414l1.414 1.414a1 1 0 0 0 1.414 0l.536-.535 1.707.707V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.757l1.707-.708.536.536a1 1 0 0 0 1.414 0l1.414-1.414a1 1 0 0 0 0-1.414l-.535-.536.707-1.707H20a1 1 0 0 0 1-1Z"
                />
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3 flex-1 whitespace-nowrap">Settings</span>
            </a>
          </li>
          <li>
            <a href="#" class="{{ $attributes->get('class', '') }} group flex items-center rounded-lg p-2 text-base font-medium text-red-600 hover:bg-red-100 dark:text-red-500 dark:hover:bg-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 text-red-600 transition duration-75 dark:text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ml-3 flex-1 whitespace-nowrap">Log out</span>
            </a>
          </li>
        </ul>
      </aside>
      <!-- Right content -->
      <div class="{{ $attributes->get('class', '') }} grid w-full grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800">
          <div class="{{ $attributes->get('class', '') }} divide-y divide-gray-200 dark:divide-gray-700">
            <div class="{{ $attributes->get('class', '') }} flex flex-col space-y-4 pb-4 md:flex-row md:items-start md:justify-between md:space-y-0">
              <div>
                <h2 class="{{ $attributes->get('class', '') }} mb-2 text-2xl font-bold text-gray-900 dark:text-white">$57.3k</h2>
                <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Total revenue</p>
              </div>
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <ul class="{{ $attributes->get('class', '') }} flex flex-wrap text-center text-sm font-medium text-gray-900 dark:text-gray-400">
                  <li class="{{ $attributes->get('class', '') }} mr-2 lg:mr-4">
                    <button type="button"
                      class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                      >Today</button>
                  </li>
                  <li class="{{ $attributes->get('class', '') }} mr-2 lg:mr-4">
                    <button type="button" class="{{ $attributes->get('class', '') }} active inline-block rounded-lg bg-primary-700 px-3 py-2 text-white dark:bg-primary-600">Weekly</button>
                  </li>
                  <li>
                    <button type="button"
                      class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                      aria-current="page"
                    >
                      Monthly
                  </button>
                  </li>
                </ul>
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex flex-col py-4 md:flex-row md:items-center md:justify-between  md:py-0">
              <select
                id="list-navigation"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 md:hidden"
              >
                <option selected>Expenses</option>
                <option value="income">Income</option>
                <option value="savings">Savings</option>
                <option value="deposits">Deposits</option>
              </select>

              <ul class="{{ $attributes->get('class', '') }} hidden flex-wrap space-x-5 text-center text-sm font-medium text-gray-500 dark:text-gray-400 md:flex">
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} group inline-flex items-center border-b border-transparent pb-3 pt-4 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">Expenses</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} active group inline-flex items-center border-b border-primary-700 pb-3 pt-4 text-primary-700 dark:border-primary-500 dark:text-primary-500" aria-current="page">Income</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} group inline-flex items-center border-b border-transparent pb-3 pt-4 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">Savings</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} group inline-flex items-center rounded-t-lg border-b border-transparent pb-3 pt-4 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">Deposits</button>
                </li>
              </ul>
            </div>
          </div>
          <div class="{{ $attributes->get('class', '') }} mt-4 md:mt-6" id="total-revenue"></div>
          <div class="{{ $attributes->get('class', '') }} border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-5">
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
            >
              View full report
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path>
              </svg>
            </button>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} col-span-2 grid grid-cols-3 gap-4">
          <div class="{{ $attributes->get('class', '') }} col-span-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:p-6 xl:col-span-1">
            <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
              <div class="{{ $attributes->get('class', '') }} w-full" id="products-small-chart"></div>
              <div class="{{ $attributes->get('class', '') }} w-full">
                <h5 class="{{ $attributes->get('class', '') }}  text-gray-500 dark:text-gray-400">New products</h5>
                <p class="{{ $attributes->get('class', '') }} my-2 text-xl font-bold leading-none text-gray-900 dark:text-white">2,340</p>
                <p class="{{ $attributes->get('class', '') }} flex items-center text-sm text-gray-500 dark:text-gray-400">
                  <svg class="{{ $attributes->get('class', '') }} me-1 h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="{{ $attributes->get('class', '') }} shrink-0 text-sm">Apr 30 - May 30</span>
                </p>
              </div>
            </div>
          </div>
          <div class="{{ $attributes->get('class', '') }} col-span-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:p-6 xl:col-span-1">
            <div class="{{ $attributes->get('class', '') }} flex items-start space-x-4">
              <div class="{{ $attributes->get('class', '') }} w-full">
                <button
                  id="balanceButton"
                  data-dropdown-toggle="balanceDropdown"
                  data-dropdown-placement="bottom"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                  type="button"
                >
                  USD Balance
                  <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                  </svg>
                </button>
                <!-- Dropdown menu -->
                <div id="balanceDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700" data-popper-placement="bottom">
                  <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="balanceButton">
                    <li>
                      <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Euro Balance</a>
                    </li>
                    <li>
                      <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">USD Balance</a>
                    </li>
                    <li>
                      <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Crypto Balance</a>
                    </li>
                  </ul>
                </div>
                <p class="{{ $attributes->get('class', '') }} my-2 text-xl font-bold leading-none text-gray-900 dark:text-white">$768.99</p>
                <p class="{{ $attributes->get('class', '') }} flex items-center text-sm text-gray-500 dark:text-gray-400">Available to payout</p>
              </div>
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
              >
                View more
              </button>
            </div>
          </div>
          <div class="{{ $attributes->get('class', '') }} col-span-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:p-6 xl:col-span-1">
            <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
              <div class="{{ $attributes->get('class', '') }} w-full" id="small-users-signups-chart"></div>
              <div class="{{ $attributes->get('class', '') }} w-full">
                <h5 class="{{ $attributes->get('class', '') }}  text-gray-500 dark:text-gray-400">User signups</h5>
                <p class="{{ $attributes->get('class', '') }} my-2 text-xl font-bold leading-none text-gray-900 dark:text-white">15,284</p>
                <p class="{{ $attributes->get('class', '') }} flex items-center text-sm text-gray-500 dark:text-gray-400">
                  <svg class="{{ $attributes->get('class', '') }} me-1 h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="{{ $attributes->get('class', '') }} shrink-0 text-sm">Apr 30 - May 30</span>
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:col-span-1 md:p-6">
          <h5 class="{{ $attributes->get('class', '') }} mb-4 pe-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Company stats</h5>
          <div class="{{ $attributes->get('class', '') }} mb-2 grid grid-cols-3 gap-3">
            <dl class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center rounded-lg bg-gray-50 py-6 text-sm font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
              <dt class="{{ $attributes->get('class', '') }} mb-1.5 text-base leading-none text-gray-900 dark:text-white">26.7k</dt>
              <dd>Users</dd>
            </dl>
            <dl class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center rounded-lg bg-gray-50 py-6 text-sm font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
              <dt class="{{ $attributes->get('class', '') }} mb-1.5 text-base leading-none text-gray-900 dark:text-white">956</dt>
              <dd>Customers</dd>
            </dl>
            <dl class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center rounded-lg bg-gray-50 py-6 text-sm font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
              <dt class="{{ $attributes->get('class', '') }} mb-1.5 text-base leading-none text-gray-900 dark:text-white">$8,267</dt>
              <dd>Revenue</dd>
            </dl>
          </div>
          <ul role="list" class="{{ $attributes->get('class', '') }} divide-y divide-gray-200 dark:divide-gray-700 md:my-2">
            <li class="{{ $attributes->get('class', '') }} py-3 sm:py-4">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
                <div class="{{ $attributes->get('class', '') }} shrink-0">
                  <div id="small-users-chart" class="{{ $attributes->get('class', '') }} h-20"></div>
                </div>
                <div>
                  <p class="{{ $attributes->get('class', '') }} truncate text-sm text-gray-500 dark:text-gray-400">Users</p>
                  <p class="{{ $attributes->get('class', '') }} my-0.5 text-xl font-bold text-gray-900 dark:text-white">26.7k</p>
                  <p class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-gray-500 dark:text-gray-400">
                    <span class="{{ $attributes->get('class', '') }} me-1.5 flex items-center text-green-500">
                      <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"></path>
                      </svg>
                      8.8%
                    </span>
                    vs last week
                  </p>
                </div>
              </div>
            </li>
            <li class="{{ $attributes->get('class', '') }} py-3 sm:py-4">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
                <div class="{{ $attributes->get('class', '') }} shrink-0">
                  <div id="small-customers-chart" class="{{ $attributes->get('class', '') }} h-20"></div>
                </div>
                <div>
                  <p class="{{ $attributes->get('class', '') }} truncate text-sm text-gray-500 dark:text-gray-400">Customers</p>
                  <p class="{{ $attributes->get('class', '') }} my-0.5 text-xl font-bold text-gray-900 dark:text-white">956</p>
                  <p class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-gray-500 dark:text-gray-400">
                    <span class="{{ $attributes->get('class', '') }} me-1.5 flex items-center text-red-600 dark:text-red-500">
                      <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                      </svg>
                      10.6%
                    </span>
                    vs last week
                  </p>
                </div>
              </div>
            </li>
            <li class="{{ $attributes->get('class', '') }} py-3 sm:py-4">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
                <div class="{{ $attributes->get('class', '') }} shrink-0">
                  <div id="small-revenue-chart" class="{{ $attributes->get('class', '') }} h-20"></div>
                </div>
                <div>
                  <p class="{{ $attributes->get('class', '') }} truncate text-sm text-gray-500 dark:text-gray-400">Revenue</p>
                  <p class="{{ $attributes->get('class', '') }} my-0.5 text-xl font-bold text-gray-900 dark:text-white">$8,267</p>
                  <p class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-gray-500 dark:text-gray-400">
                    <span class="{{ $attributes->get('class', '') }} me-1.5 flex items-center text-green-500">
                      <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"></path>
                      </svg>
                      4%
                    </span>
                    vs last week
                  </p>
                </div>
              </div>
            </li>
          </ul>
          <div class="{{ $attributes->get('class', '') }} border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
            >
              View full report
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path>
              </svg>
            </button>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:col-span-1 md:p-6">
          <div class="{{ $attributes->get('class', '') }} flex justify-between">
            <h5 class="{{ $attributes->get('class', '') }} mb-2 pe-1 text-xl font-bold leading-none text-gray-900 dark:text-white">Users by day</h5>
            <p class="{{ $attributes->get('class', '') }} shrink-0 text-gray-500 dark:text-gray-400">Apr 17-Apr 24</p>
          </div>
          <div class="{{ $attributes->get('class', '') }} items-center space-y-1 sm:flex sm:space-x-4 sm:space-y-0">
            <button type="button" data-dropdown-toggle="language-dropdown" class="{{ $attributes->get('class', '') }} inline-flex items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500">
              <svg class="{{ $attributes->get('class', '') }} mr-2 h-4 w-4 rounded-lg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 3900 3900">
                <path fill="#b22234" d="M0 0h7410v3900H0z" />
                <path d="M0 450h7410m0 600H0m0 600h7410m0 600H0m0 600h7410m0 600H0" stroke="#fff" stroke-width="300" />
                <path fill="#3c3b6e" d="M0 0h2964v2100H0z" />
                <g fill="#fff">
                  <g id="d">
                    <g id="c">
                      <g id="e">
                        <g id="b">
                          <path id="a" d="M247 90l70.534 217.082-184.66-134.164h228.253L176.466 307.082z" />
                          <use xlink:href="#a" y="420" />
                          <use xlink:href="#a" y="840" />
                          <use xlink:href="#a" y="1260" />
                        </g>
                        <use xlink:href="#a" y="1680" />
                      </g>
                      <use xlink:href="#b" x="247" y="210" />
                    </g>
                    <use xlink:href="#c" x="494" />
                  </g>
                  <use xlink:href="#d" x="988" />
                  <use xlink:href="#c" x="1976" />
                  <use xlink:href="#e" x="2470" />
                </g>
              </svg>
              United States
              <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <!-- Dropdown -->
            <div class="{{ $attributes->get('class', '') }} z-50 my-4 hidden w-40 list-none divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700" id="language-dropdown">
              <ul class="{{ $attributes->get('class', '') }} p-2 font-medium text-gray-500 dark:text-gray-400" role="none">
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-us" viewBox="0 0 512 512">
                        <g fill-rule="evenodd">
                          <g stroke-width="1pt">
                            <path fill="#bd3d44" d="M0 0h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
                            <path fill="#fff" d="M0 10h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
                          </g>
                          <path fill="#192f5d" d="M0 0h98.8v70H0z" transform="scale(3.9385)" />
                          <path
                            fill="#fff"
                            d="M8.2 3l1 2.8H12L9.7 7.5l.9 2.7-2.4-1.7L6 10.2l.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7L74 8.5l-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 7.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 24.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 21.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 38.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 35.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 52.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 49.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 66.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 63.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9z"
                            transform="scale(3.9385)"
                          />
                        </g>
                      </svg>
                      United States
                    </div>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-de" viewBox="0 0 512 512">
                        <path fill="#ffce00" d="M0 341.3h512V512H0z" />
                        <path d="M0 0h512v170.7H0z" />
                        <path fill="#d00" d="M0 170.7h512v170.6H0z" />
                      </svg>
                      Germany
                    </div>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-it" viewBox="0 0 512 512">
                        <g fill-rule="evenodd" stroke-width="1pt">
                          <path fill="#fff" d="M0 0h512v512H0z" />
                          <path fill="#009246" d="M0 0h170.7v512H0z" />
                          <path fill="#ce2b37" d="M341.3 0H512v512H341.3z" />
                        </g>
                      </svg>
                      Italy
                    </div>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="flag-icon-css-cn" viewBox="0 0 512 512">
                        <defs>
                          <path id="a" fill="#ffde00" d="M1-.3L-.7.8 0-1 .6.8-1-.3z" />
                        </defs>
                        <path fill="#de2910" d="M0 0h512v512H0z" />
                        <use width="30" height="20" transform="matrix(76.8 0 0 76.8 128 128)" xlink:href="#a" />
                        <use width="30" height="20" transform="rotate(-121 142.6 -47) scale(25.5827)" xlink:href="#a" />
                        <use width="30" height="20" transform="rotate(-98.1 198 -82) scale(25.6)" xlink:href="#a" />
                        <use width="30" height="20" transform="rotate(-74 272.4 -114) scale(25.6137)" xlink:href="#a" />
                        <use width="30" height="20" transform="matrix(16 -19.968 19.968 16 256 230.4)" xlink:href="#a" />
                      </svg>
                      China
                    </div>
                  </a>
                </li>
              </ul>
            </div>
            <button type="button" data-dropdown-toggle="devices-dropdown" class="{{ $attributes->get('class', '') }} inline-flex items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500">
              <svg class="{{ $attributes->get('class', '') }} mr-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v5h18V5a2 2 0 0 0-2-2H5ZM3 14v-2h18v2a2 2 0 0 1-2 2h-6v3h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-3H5a2 2 0 0 1-2-2Z" clip-rule="evenodd" />
              </svg>
              Desktop PC
              <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <!-- Dropdown -->
            <div class="{{ $attributes->get('class', '') }} z-50 my-4 hidden w-40 list-none divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700" id="devices-dropdown">
              <ul class="{{ $attributes->get('class', '') }} p-2 font-medium text-gray-500 dark:text-gray-400" role="none">
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v5h18V5a2 2 0 0 0-2-2H5ZM3 14v-2h18v2a2 2 0 0 1-2 2h-6v3h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-3H5a2 2 0 0 1-2-2Z" clip-rule="evenodd" />
                      </svg>
                      Desktop PC
                    </div>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M4 4c0-.975.718-2 1.875-2h12.25C19.282 2 20 3.025 20 4v16c0 .975-.718 2-1.875 2H5.875C4.718 22 4 20.975 4 20V4Zm7 13a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
                      </svg>
                      Tablet
                    </div>
                  </a>
                </li>
                <li>
                  <a href="#" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4Zm12 12V5H7v11h10Zm-5 1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                      </svg>
                      Mobile
                    </div>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <!-- Users Chart -->
          <div id="users-by-day"></div>

          <div class="{{ $attributes->get('class', '') }} border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
            >
              View full report
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path>
              </svg>
            </button>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:col-span-1 md:p-6">
          <h2 class="{{ $attributes->get('class', '') }} mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">Windows</h2>
          <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Sessions by OS</p>
          <div class="{{ $attributes->get('class', '') }} mx-auto my-4 md:my-6" id="sessions-chart"></div>
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
            <!-- Button -->
            <button
              id="sessionsByOsButton"
              data-dropdown-toggle="sessionsByOsDropdown"
              data-dropdown-placement="bottom"
              class="{{ $attributes->get('class', '') }} inline-flex items-center text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              type="button"
            >
              Last 7 days
              <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
              </svg>
            </button>
            <!-- Dropdown menu -->
            <div id="sessionsByOsDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700" data-popper-placement="bottom">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="sessionsByOsButton">
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Today</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 7 days</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 30 days</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 90 days</button>
                </li>
              </ul>
            </div>
            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold uppercase text-primary-700  hover:bg-gray-100 dark:border-gray-700 dark:text-primary-500 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
              Full report
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1 h-4 w-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 md:col-span-1 md:p-6">
          <h2 class="{{ $attributes->get('class', '') }} mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">Visits by country</h2>
          <div id="world-map" class="{{ $attributes->get('class', '') }} mb-4 md:mb-6"></div>
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
            <!-- Button -->
            <button
              id="visitsByCountriesButton"
              data-dropdown-toggle="visitsByCountriesDropdown"
              data-dropdown-placement="bottom"
              class="{{ $attributes->get('class', '') }} inline-flex items-center text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              type="button"
            >
              Last 7 days
              <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
              </svg>
            </button>
            <!-- Dropdown menu -->
            <div id="visitsByCountriesDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700" data-popper-placement="bottom">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="visitsByCountriesButton">
                <li>
                  <button type="button" href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Today</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 7 days</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 30 days</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Last 90 days</button>
                </li>
              </ul>
            </div>
            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold uppercase text-primary-700  hover:bg-gray-100 dark:border-gray-700 dark:text-primary-500 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
              Visits report
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1 h-4 w-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </div>

        <div class="{{ $attributes->get('class', '') }} relative col-span-2 overflow-hidden rounded-lg border border-gray-200 bg-white p-4 md:p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800">
          <div class="{{ $attributes->get('class', '') }} mb-4 flex flex-col space-y-3 md:mb-6 lg:flex-row lg:items-center lg:justify-between lg:space-x-4 lg:space-y-0">
            <div class="{{ $attributes->get('class', '') }} flex flex-1 items-center space-x-4">
              <h5>
                <span class="{{ $attributes->get('class', '') }} text-gray-500">All Products:</span>
                <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">123456</span>
              </h5>
              <h5>
                <span class="{{ $attributes->get('class', '') }} text-gray-500">Total sales:</span>
                <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">$88.4k</span>
              </h5>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex shrink-0 flex-col space-y-3 md:flex-row md:items-center md:space-x-3 md:space-y-0 lg:justify-end">
              <button
                type="button"
                id="createProductButton"
                data-drawer-target="drawer-create-product"
                data-drawer-show="drawer-create-product"
                aria-controls="drawer-create-product"
                class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
              >
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"></path>
                </svg>
                Add new product
              </button>
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
              >
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2"
                  />
                </svg>

                Export
              </button>
              <button
                id="actionsDropdownButton"
                data-dropdown-toggle="actionsDropdown"
                class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
                type="button"
              >
                Actions
                <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                </svg>
              </button>
              <div id="actionsDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="actionsDropdownButton">
                  <li>
                    <button
                      id="drawer-update-product-button"
                      data-drawer-target="drawer-update-product"
                      data-drawer-toggle="drawer-update-product"
                      type="button"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                        <path
                          fill-rule="evenodd"
                          d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      Edit
                    </button>
                  </li>
                  <li>
                    <button
                      id="archiveModalButton"
                      data-modal-target="archiveModal"
                      data-modal-toggle="archiveModal"
                      type="button"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      Archive
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="deleteProductModalButton"
                      data-modal-target="deleteProductModal"
                      data-modal-toggle="deleteProductModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
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
          <div class="{{ $attributes->get('class', '') }} overflow-x-auto relative">
            <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
              <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
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
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap min-w-[12rem] px-4 py-3">Product</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Category</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Stock</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Sales/Day</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Sales/Month</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Rating</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Sales</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Revenue</th>
                  <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Last Update</th>
                </tr>
              </thead>
              <tbody>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/imac-front-image.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Apple iMac 27&#34;
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Desktop PC</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-red-700"></div>
                      95
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">1.47</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.47</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">5.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      1.6M
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$3.2M</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Just now</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/imac-front-image.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Apple iMac 20&#34;
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Desktop PC</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-red-700"></div>
                      108
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">1.15</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.32</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">5.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      6M
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$785K</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">This week</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/apple-iphone-14.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Apple iPhone 15
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Phone</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-green-400"></div>
                      24
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">1.00</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.95</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-300 dark:text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">4.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      1.2M
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$3.2M</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Just now</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/apple-ipad-air.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Apple iPad Air
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Tablet</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-red-500"></div>
                      287
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.47</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">1.00</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-300 dark:text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">4.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      298K
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$425K</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Just now</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/xbox-series-s.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Xbox Series S
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Console</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-yellow-300"></div>
                      450
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">1.61</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.30</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">5.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      99
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$345K</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">This week</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/playstation-5.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      PlayStation 5
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Console</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-green-400"></div>
                      2435
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">1.41</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.11</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-300 dark:text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">4.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      2.1M
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$4.2M</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">This week</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/xbox-series-x.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Xbox Series X
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming/Console</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-orange-500"></div>
                      235
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">7.09</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">3.32</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">5.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      989K
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$2.27M</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">This week</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/apple-watch-se.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      Apple Watch SE
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Watch</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-yellow-300"></div>
                      433
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">4.96</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.74</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">5.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      102
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$45K</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">2 weeks ago</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/nikon-d850.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      NIKON D850
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Photo/Video</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-orange-400"></div>
                      351
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.20</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.74</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-300 dark:text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-300 dark:text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">3.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      1.2M
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$1.52M</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">3 weeks ago</td>
                </tr>
                <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 border-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
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
                  <th scope="row" class="{{ $attributes->get('class', '') }} flex items-center whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <img src="/images/e-commerce/benq-ex2710q.png" alt="iMac Front Image" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto" />
                    <button type="button" id="readProductAdvancedButton" data-drawer-target="drawer-read-product-advanced" data-drawer-show="drawer-read-product-advanced" aria-controls="drawer-read-product-advanced" class="{{ $attributes->get('class', '') }} hover:underline">
                      BenQ EX2710Q
                    </button>
                  </th>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">
                    <span class="{{ $attributes->get('class', '') }} rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">TV/Monitor</span>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <div class="{{ $attributes->get('class', '') }} me-1.5 inline-block h-3 w-3 rounded-full bg-green-500"></div>
                      1242
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">4.12</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">0.30</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-yellow-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-300 dark:text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} ml-1 text-gray-500 dark:text-gray-400">4.0</span>
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="{{ $attributes->get('class', '') }} flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
                          clip-rule="evenodd"
                        />
                      </svg>

                      211K
                    </div>
                  </td>
                  <td class="{{ $attributes->get('class', '') }} px-4 py-2">$1.2M</td>
                  <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Just now</td>
                </tr>
              </tbody>
            </table>
          </div>
          <nav class="{{ $attributes->get('class', '') }} mt-4 flex flex-col items-start justify-between space-y-3 md:mt-6 md:flex-row md:items-center md:space-y-0" aria-label="Table navigation">
            <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">
              Showing
              <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1-10</span>
              of
              <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1000</span>
            </span>
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
                  >1</a
                >
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                  >2</a
                >
              </li>
              <li>
                <a
                  href="#"
                  aria-current="page"
                  class="{{ $attributes->get('class', '') }} z-10 flex items-center justify-center border border-primary-300 bg-primary-50 px-3 py-2 text-sm leading-tight text-primary-600 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white"
                  >3</a
                >
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                  >...</a
                >
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                  >100</a
                >
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
</section>

<!-- Add new product drawer -->
<div
  id="drawer-create-product"
  class="{{ $attributes->get('class', '') }} fixed left-0 top-0 z-40 h-screen w-full max-w-xl -translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800"
  tabindex="-1"
  aria-labelledby="drawer-create-product-label"
  aria-hidden="true"
>
  <h5 id="drawer-label" class="{{ $attributes->get('class', '') }} mb-6 inline-flex items-center text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">Add New Product</h5>
  <button
    type="button"
    data-drawer-dismiss="drawer-create-product"
    aria-controls="drawer-create-product"
    class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
    </svg>

    <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
  </button>
  <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-3 sm:gap-6 ">
    <div class="{{ $attributes->get('class', '') }} space-y-4 sm:col-span-2 sm:space-y-6">
      <div>
        <label for="name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Product Name</label>
        <input
          type="text"
          name="name"
          id="name"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Type product name"
          required=""
        />
      </div>
      <div>
        <label for="product-description" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Description</label>
        <textarea
          id="product-description"
          rows="6"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Write your thoughts here..."
        ></textarea>
      </div>
      <div>
        <label for="dropzone-file" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Product Images</label>
        <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center">
          <label
            for="dropzone-file"
            class="{{ $attributes->get('class', '') }} dark:hover:bg-bray-800 flex h-48 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-600"
          >
            <div class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center pb-6 pt-5">
              <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} mb-3 h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              <p class="{{ $attributes->get('class', '') }} mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-semibold">Click to upload</span> or drag and drop</p>
              <p class="{{ $attributes->get('class', '') }} text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
            </div>
            <input id="dropzone-file" type="file" class="{{ $attributes->get('class', '') }} hidden" />
          </label>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} space-y-4 sm:space-y-6">
      <div>
        <label for="product-brand" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Brand</label>
        <input
          type="text"
          id="product-brand"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Product Brand"
          required=""
        />
      </div>
      <div>
        <label for="category" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Category</label>
        <select
          id="category"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Select category</option>
          <option value="TV">TV/Monitors</option>
          <option value="PC">PC</option>
          <option value="GA">Gaming/Console</option>
          <option value="PH">Phones</option>
        </select>
      </div>
      <div>
        <label for="item-weight" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Item Weight (kg)</label>
        <input
          type="number"
          name="item-weight"
          id="item-weight"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="12"
          required=""
        />
      </div>
      <div>
        <label for="length" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Length (cm)</label>
        <input
          type="number"
          name="length"
          id="lenght"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="105"
          required=""
        />
      </div>
      <div>
        <label for="breadth" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Breadth (cm)</label>
        <input
          type="number"
          name="breadth"
          id="breadth"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="15"
          required=""
        />
      </div>
      <div>
        <label for="width" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Width (cm)</label>
        <input
          type="number"
          name="width"
          id="width"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="23"
          required=""
        />
      </div>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} mt-6 grid grid-cols-2 gap-4 sm:w-1/2">
    <button
      type="submit"
      class="{{ $attributes->get('class', '') }} justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
    >
      Add product
    </button>
    <button
      type="button"
      type="button"
      data-drawer-dismiss="drawer-create-product"
      aria-controls="drawer-create-product"
      class="{{ $attributes->get('class', '') }} inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
    >
      <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
      Discard
    </button>
  </div>
</div>

<!-- Edit product drawer -->
<div
  id="drawer-update-product"
  class="{{ $attributes->get('class', '') }} fixed left-0 top-0 z-40 h-screen w-full max-w-xl -translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800"
  tabindex="-1"
  aria-labelledby="drawer-update-product-label"
  aria-hidden="true"
>
  <h5 id="drawer-label" class="{{ $attributes->get('class', '') }} mb-6 inline-flex items-center text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">New Product</h5>
  <button
    type="button"
    data-drawer-dismiss="drawer-update-product"
    aria-controls="drawer-update-product"
    class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
    </svg>

    <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
  </button>
  <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-3 sm:gap-6 ">
    <div class="{{ $attributes->get('class', '') }} space-y-4 sm:col-span-2 sm:space-y-6">
      <div>
        <label for="name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Product Name</label>
        <input
          type="text"
          name="name"
          id="name"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value="Apple iMac 27&ldquo;"
          placeholder="Type product name"
          required=""
        />
      </div>
      <div>
        <label for="product-description-edit" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Description</label>
        <textarea
          id="product-description-edit"
          rows="6"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Write your thoughts here..."
        >
Standard glass, 3.8GHz 8-core 10th-generation Intel Core i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6 memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse 2, Magic Keyboard - US</textarea
        >
      </div>
      <div class="{{ $attributes->get('class', '') }} mb-4">
        <span class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Product Images</span>
        <div class="{{ $attributes->get('class', '') }} mb-4 grid grid-cols-3 gap-4">
          <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-24 sm:w-24">
            <img src="/images/e-commerce/imac-side-image.png" alt="imac image" />
            <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400">
              <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="{{ $attributes->get('class', '') }} sr-only">Remove image</span>
            </button>
          </div>
          <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-24 sm:w-24">
            <img src="/images/e-commerce/imac-front-image.png" alt="imac image" />
            <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400">
              <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="{{ $attributes->get('class', '') }} sr-only">Remove image</span>
            </button>
          </div>
          <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-24 sm:w-24">
            <img src="/images/e-commerce/imac-back-image.png" alt="imac image" />
            <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400">
              <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="{{ $attributes->get('class', '') }} sr-only">Remove image</span>
            </button>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center">
          <label
            for="dropzone-file"
            class="{{ $attributes->get('class', '') }} dark:hover:bg-bray-800 flex h-48 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-600"
          >
            <div class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center pb-6 pt-5">
              <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} mb-3 h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              <p class="{{ $attributes->get('class', '') }} mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-semibold">Click to upload</span> or drag and drop</p>
              <p class="{{ $attributes->get('class', '') }} text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
            </div>
            <input id="dropzone-file" type="file" class="{{ $attributes->get('class', '') }} hidden" />
          </label>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} space-y-4 sm:space-y-6">
      <div>
        <label for="product-brand" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Brand</label>
        <input
          type="text"
          id="product-brand"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value="Apple"
          placeholder="Product Brand"
          required=""
        />
      </div>
      <div>
        <label for="category" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Category</label>
        <select
          id="category"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Electronics</option>
          <option value="TV">TV/Monitors</option>
          <option value="PC">PC</option>
          <option value="GA">Gaming/Console</option>
          <option value="PH">Phones</option>
        </select>
      </div>
      <div>
        <label for="item-weight" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Item Weight (kg)</label>
        <input
          type="number"
          name="item-weight"
          id="item-weight"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value="12"
          placeholder="Ex. 12"
          required=""
        />
      </div>
      <div>
        <label for="length" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Length (cm)</label>
        <input
          type="number"
          name="length"
          id="lenght"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value="105"
          placeholder="Ex. 105"
          required=""
        />
      </div>
      <div>
        <label for="breadth" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Breadth (cm)</label>
        <input
          type="number"
          name="breadth"
          id="breadth"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value="15"
          placeholder="Ex. 15"
          required=""
        />
      </div>
      <div>
        <label for="width" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Width (cm)</label>
        <input
          type="number"
          name="width"
          id="width"
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value="23"
          placeholder="Ex. 23"
          required=""
        />
      </div>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} mt-6 grid grid-cols-2 gap-4 sm:w-1/2">
    <button
      type="submit"
      class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
    >
      Update
    </button>
    <button
      type="button"
      class="{{ $attributes->get('class', '') }} inline-flex items-center justify-center rounded-lg border border-red-600 px-3 py-2 text-center text-sm font-medium text-red-600 hover:bg-red-600 hover:text-white focus:outline-none focus:ring-4 focus:ring-red-300 dark:border-red-500 dark:text-red-500 dark:hover:bg-red-600 dark:hover:text-white dark:focus:ring-red-900"
    >
      <svg class="{{ $attributes->get('class', '') }} -ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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

<!-- Delete product modal -->
<div id="deleteProductModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteProductModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-semibold text-gray-900 dark:text-white">Are you sure?</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">You are about to delete the following product, this cannot be undone:</p>
      <ul role="list" class="{{ $attributes->get('class', '') }} mb-4 space-y-2 text-left text-gray-500 dark:text-gray-400 sm:mb-5">
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg class="{{ $attributes->get('class', '') }} h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm9.4-5.5a1 1 0 1 0 0 2 1 1 0 1 0 0-2ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4c0-.6-.4-1-1-1h-2Z" clip-rule="evenodd" />
          </svg>
          <span>Apple iMac 27”</span>
        </li>
      </ul>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="deleteProductModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} flex items-center rounded-lg bg-red-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
              clip-rule="evenodd"
            />
          </svg>
          Yes, delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Archive product modal -->
<div id="archiveModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
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
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-300">Are you sure you want to archive <br /><span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">Apple iMac 27"</span>?</p>
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

<!-- View product drawer -->
<div
  id="drawer-read-product-advanced"
  class="{{ $attributes->get('class', '') }} fixed left-0 top-0 z-40 h-screen w-full max-w-lg -translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800"
  tabindex="-1"
  aria-labelledby="drawer-label"
  aria-hidden="true"
>
  <div>
    <h4 id="drawer-label" class="{{ $attributes->get('class', '') }} mb-1.5 text-xl font-semibold leading-none text-gray-900 dark:text-white">Apple iMac 25"</h4>
    <h5 class="{{ $attributes->get('class', '') }} mb-5 text-xl font-bold text-gray-900 dark:text-white">$2999</h5>
  </div>
  <button
    type="button"
    data-drawer-dismiss="drawer-read-product-advanced"
    aria-controls="drawer-read-product-advanced"
    class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
    </svg>

    <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
  </button>
  <div class="{{ $attributes->get('class', '') }} mb-4 grid grid-cols-3 gap-4 sm:mb-5">
    <div class="{{ $attributes->get('class', '') }} w-auto rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
      <img src="/images/e-commerce/imac-side-image.png" alt="iMac Side Image" />
    </div>
    <div class="{{ $attributes->get('class', '') }} w-auto rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
      <img src="/images/e-commerce/imac-front-image.png" alt="iMac Front Image" />
    </div>
    <div class="{{ $attributes->get('class', '') }} w-auto rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
      <img src="/images/e-commerce/imac-back-image.png" alt="iMac Back Image" />
    </div>
    <div class="{{ $attributes->get('class', '') }} w-auto rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
      <img src="/images/e-commerce/imac-side-image.png" alt="iMac Back Image" />
    </div>
    <div class="{{ $attributes->get('class', '') }} w-auto rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
      <img src="/images/e-commerce/imac-back-image.png" alt="iMac Front Image" />
    </div>
    <div class="{{ $attributes->get('class', '') }} w-auto rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
      <img src="/images/e-commerce/imac-side-image.png" alt="iMac Side Image" />
    </div>
  </div>
  <dl class="{{ $attributes->get('class', '') }} sm:mb-5">
    <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Details</dt>
    <dd class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">Standard glass ,3.8GHz 8-core 10th-generation Intel Core i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6 memory</dd>
  </dl>
  <dl class="{{ $attributes->get('class', '') }} mb-4 grid grid-cols-2 gap-4">
    <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700 sm:col-span-1">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Shipping</dt>
      <dd class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
        <svg class="{{ $attributes->get('class', '') }} mr-1.5 h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
        </svg>
        United States, Europe
      </dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} col-span-2 rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700 sm:col-span-1">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Colors</dt>
      <dd class="{{ $attributes->get('class', '') }} flex items-center space-x-2 text-gray-500 dark:text-gray-400">
        <div class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 rounded-full bg-purple-600"></div>
        <div class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 rounded-full bg-indigo-400"></div>
        <div class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 rounded-full bg-primary-600"></div>
        <div class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 rounded-full bg-pink-400"></div>
        <div class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 rounded-full bg-teal-300"></div>
        <div class="{{ $attributes->get('class', '') }} h-6 w-6 shrink-0 rounded-full bg-green-300"></div>
      </dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Product State</dt>
      <dd class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
        <span class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-200 dark:text-primary-800">
          <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
            ></path>
          </svg>
          New
        </span>
      </dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Sold by</dt>
      <dd class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Flowbite</dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Ships from</dt>
      <dd class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Flowbite</dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Brand</dt>
      <dd class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Apple</dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Dimensions (cm)</dt>
      <dd class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">105 x 15 x 23</dd>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-700">
      <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Item weight</dt>
      <dd class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">12kg</dd>
    </div>
  </dl>
  <div class="{{ $attributes->get('class', '') }} flex w-full justify-center space-x-4 pb-4">
    <button
      type="button"
      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
    >
      <svg class="{{ $attributes->get('class', '') }} -ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
    </button>
    <button
      type="button"
      class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
    >
      Preview
    </button>
    <button
      type="button"
      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
    >
      <svg class="{{ $attributes->get('class', '') }} -ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
