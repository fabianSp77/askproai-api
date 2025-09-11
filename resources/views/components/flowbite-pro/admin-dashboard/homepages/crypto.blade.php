@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Crypto Dashboard - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: dashboards
page: crypto
---

<div class="{{ $attributes->get('class', '') }} px-4">
  <div class="{{ $attributes->get('class', '') }} my-4 grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M9 15a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm3.845-1.855a2.4 2.4 0 0 1 1.2-1.226 1 1 0 0 1 1.992-.026c.426.15.809.408 1.111.749a1 1 0 1 1-1.496 1.327.682.682 0 0 0-.36-.213.997.997 0 0 1-.113-.032.4.4 0 0 0-.394.074.93.93 0 0 0 .455.254 2.914 2.914 0 0 1 1.504.9c.373.433.669 1.092.464 1.823a.996.996 0 0 1-.046.129c-.226.519-.627.94-1.132 1.192a1 1 0 0 1-1.956.093 2.68 2.68 0 0 1-1.227-.798 1 1 0 1 1 1.506-1.315.682.682 0 0 0 .363.216c.038.009.075.02.111.032a.4.4 0 0 0 .395-.074.93.93 0 0 0-.455-.254 2.91 2.91 0 0 1-1.503-.9c-.375-.433-.666-1.089-.466-1.817a.994.994 0 0 1 .047-.134Zm1.884.573.003.008c-.003-.005-.003-.008-.003-.008Zm.55 2.613s-.002-.002-.003-.007a.032.032 0 0 1 .003.007ZM4 14a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0v-4a1 1 0 0 1 1-1Zm3-2a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm6.5-8a1 1 0 0 1 1-1H18a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-.796l-2.341 2.049a1 1 0 0 1-1.24.06l-2.894-2.066L6.614 9.29a1 1 0 1 1-1.228-1.578l4.5-3.5a1 1 0 0 1 1.195-.025l2.856 2.04L15.34 5h-.84a1 1 0 0 1-1-1Z"
          clip-rule="evenodd"
        />
      </svg>
      <h3 class="{{ $attributes->get('class', '') }} mt-2 text-gray-500 dark:text-gray-400">Total balance</h3>
      <span class="{{ $attributes->get('class', '') }} mb-2 text-2xl font-bold text-gray-900 dark:text-white">$739.9k</span>
      <p class="{{ $attributes->get('class', '') }} mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400 ">
        <span class="{{ $attributes->get('class', '') }} mr-1.5 flex items-center text-sm font-medium text-green-500 dark:text-green-400 sm:text-base">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
          </svg>
          7%
        </span>
        vs last 24h
      </p>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 10l4 4 4-4 5 5m0 0h-3.207M20 15v-3.207" />
      </svg>

      <h3 class="{{ $attributes->get('class', '') }} mt-2 text-gray-500 dark:text-gray-400">24h portfolio change</h3>
      <span class="{{ $attributes->get('class', '') }} mb-2 text-2xl font-bold text-gray-900 dark:text-white">$82.1k</span>
      <p class="{{ $attributes->get('class', '') }} mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400 ">
        <span class="{{ $attributes->get('class', '') }} mr-1.5 flex items-center text-sm font-medium text-green-500 dark:text-green-400 sm:text-base">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
          </svg>
          8,8%
        </span>
        vs last 24h
      </p>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M8 17.345a4.76 4.76 0 0 0 2.558 1.618c2.274.589 4.512-.446 4.999-2.31.487-1.866-1.273-3.9-3.546-4.49-2.273-.59-4.034-2.623-3.547-4.488.486-1.865 2.724-2.899 4.998-2.31.982.236 1.87.793 2.538 1.592m-3.879 12.171V21m0-18v2.2"
        />
      </svg>

      <h3 class="{{ $attributes->get('class', '') }} mt-2 text-gray-500 dark:text-gray-400">Total Profit/Loss</h3>
      <span class="{{ $attributes->get('class', '') }} mb-2 text-2xl font-bold text-gray-900 dark:text-white">$81.3k</span>
      <p class="{{ $attributes->get('class', '') }} mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400 ">
        <span class="{{ $attributes->get('class', '') }} mr-1.5 flex items-center text-sm font-medium text-red-600 dark:text-red-500 sm:text-base">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
          </svg>
          2,5%
        </span>
        vs last month
      </p>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"
        />
      </svg>
      <h3 class="{{ $attributes->get('class', '') }} mt-2 text-gray-500 dark:text-gray-400">No. of profit days</h3>
      <span class="{{ $attributes->get('class', '') }} mb-2 text-2xl font-bold text-gray-900 dark:text-white">67</span>
      <p class="{{ $attributes->get('class', '') }} mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400 ">
        <span class="{{ $attributes->get('class', '') }} mr-1.5 flex items-center text-sm font-medium text-green-500 dark:text-green-400 sm:text-base">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
          </svg>
          5%
        </span>
        vs last month
      </p>
    </div>
  </div>
  <!-- Widget -->
  <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <div class="{{ $attributes->get('class', '') }} justify-between sm:mb-3 sm:flex">
      <div class="{{ $attributes->get('class', '') }} mb-4 sm:mb-0">
        <h2 class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-xl font-bold leading-none text-gray-900 dark:text-white">$739,992 <span class="{{ $attributes->get('class', '') }} ms-2 text-base font-semibold text-green-500 dark:text-green-400">+$7,452 (8,85%)</span></h2>
        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Your portfolio balance</p>
      </div>
      <div>
        <button
          id="balanceActionsDropdownButton"
          data-dropdown-toggle="balanceActionsDropdown"
          data-dropdown-ignore-click-outside-class="{{ $attributes->get('class', '') }} datepicker"
          class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
          type="button"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
          Dec 31 - Jan 31
          <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div class="{{ $attributes->get('class', '') }} z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="balanceActionsDropdown">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="referrersActionsDropdown">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
            </li>
          </ul>
          <div class="{{ $attributes->get('class', '') }} p-5">
            <span class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white mb-2 block">Custom period:</span>
            <div id="date-range-picker" date-rangepicker class="{{ $attributes->get('class', '') }} flex w-full items-center gap-3">
              <div class="{{ $attributes->get('class', '') }} relative w-full">
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
                  id="datepicker-range-start"
                  name="start"
                  type="text"
                  class="{{ $attributes->get('class', '') }}  w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Start date"
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} relative w-full">
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
                  id="datepicker-range-end"
                  name="end"
                  type="text"
                  class="{{ $attributes->get('class', '') }}  w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="End date"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Chart -->
    <div id="portfolio-chart"></div>

    <div class="{{ $attributes->get('class', '') }} flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-700 sm:pt-6">
      <div>
        <button class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" type="button" data-dropdown-toggle="top-products-dropdown">
          Last 7 days
          <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div class="{{ $attributes->get('class', '') }} z-50 my-4 hidden list-none divide-y divide-gray-100 rounded-sm bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="top-products-dropdown">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-gray-500 dark:text-gray-400" role="none">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
            </li>
          </ul>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} shrink-0">
        <a href="#" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
          Users Report
          <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
        </a>
      </div>
    </div>
  </div>
  <!-- Table Widget -->
  <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
    <div class="{{ $attributes->get('class', '') }} relative mb-4 md:mb-6">
      <div class="{{ $attributes->get('class', '') }} flex pb-4 items-center justify-between">
        <div class="{{ $attributes->get('class', '') }} flex w-full items-center">
          <h2 class="{{ $attributes->get('class', '') }} me-1.5 text-xl font-semibold text-gray-900 dark:text-white flex items-center">My portfolio: <span class="{{ $attributes->get('class', '') }} ms-2 font-bold">$756,879</span></h2>
          <button type="button" class="{{ $attributes->get('class', '') }} hidden sm:flex text-gray-400 hover:text-gray-900 dark:hover:text-white" data-tooltip-target="results-tooltip">
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z" clip-rule="evenodd"/>
            </svg>            
            <span class="{{ $attributes->get('class', '') }} sr-only">More info</span>
          </button>
          <div id="results-tooltip" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute w-72 z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Your total portfolio balance, updated in real-time, reflects the current market value of all your holdings.
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>

        <button
          id="currenciesActionDropdownButton"
          data-dropdown-toggle="currenciesActionDropdown"
          class="{{ $attributes->get('class', '') }} flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          type="button"
        >
          USD
          <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div class="{{ $attributes->get('class', '') }} z-50 my-4 w-72 hidden list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="currenciesActionDropdown">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="currenciesActionDropdownButton">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">United States Dollar (USD)</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Euro (EUR)</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">British Pound Sterling (GBP)</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Japanese Yen (JPY)</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Chinese Yuan Renminbi (CNY)</button>
            </li>
          </ul>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} grid w-full grid-cols-2 gap-4 md:grid-cols-4 xl:w-2/3">
        <div class="{{ $attributes->get('class', '') }} w-full">
          <label for="market-cap" class="{{ $attributes->get('class', '') }} sr-only">Market Cap</label>
          <select
            id="market-cap"
            class="{{ $attributes->get('class', '') }} peer block w-full appearance-none border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2.5 text-sm text-gray-500 focus:border-gray-200 focus:outline-none focus:ring-0 dark:border-gray-700 dark:text-gray-400"
          >
            <option selected>Market Cap</option>
            <option value="high">High to Low</option>
            <option value="low">Low to High</option>
            <option value="pink">Custom</option>
          </select>
        </div>
        <div class="{{ $attributes->get('class', '') }} w-full">
          <label for="price" class="{{ $attributes->get('class', '') }} sr-only">Price</label>
          <select
            id="price"
            class="{{ $attributes->get('class', '') }} peer block w-full appearance-none border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2.5 text-sm text-gray-500 focus:border-gray-200 focus:outline-none focus:ring-0 dark:border-gray-700 dark:text-gray-400"
          >
            <option selected>Price</option>
            <option value="high">High to Low</option>
            <option value="low">Low to High</option>
            <option value="pink">Custom</option>
          </select>
        </div>
        <div class="{{ $attributes->get('class', '') }} w-full">
          <label for="volume" class="{{ $attributes->get('class', '') }} sr-only">Volume</label>
          <select
            id="volume"
            class="{{ $attributes->get('class', '') }} peer block w-full appearance-none border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2.5 text-sm text-gray-500 focus:border-gray-200 focus:outline-none focus:ring-0 dark:border-gray-700 dark:text-gray-400"
          >
            <option selected>Volume</option>
            <option value="high">High to Low</option>
            <option value="low">Low to High</option>
            <option value="pink">Custom</option>
          </select>
        </div>
        <div class="{{ $attributes->get('class', '') }} w-full">
          <label for="change" class="{{ $attributes->get('class', '') }} sr-only">Change</label>
          <select
            id="change"
            class="{{ $attributes->get('class', '') }} peer block w-full appearance-none border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2.5 text-sm text-gray-500 focus:border-gray-200 focus:outline-none focus:ring-0 dark:border-gray-700 dark:text-gray-400"
          >
            <option selected>Change</option>
            <option value="high">High to Low (Growth)</option>
            <option value="low">Low to High (Growth)</option>
            <option value="high-down">High to Low (Down)</option>
            <option value="low-down">Low to High (Down)</option>
            <option value="pink">Custom</option>
          </select>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} overflow-x-auto relative">
      <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Name</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Price
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Market Cap</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                24h volume
                <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                    d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                  />
                </svg>
              </div>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                24h change 
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M31.5211 19.8706C29.3839 28.442 20.7014 33.6585 12.128 31.521C3.55811 29.384 -1.65895 20.7021 0.479281 12.1312C2.61551 3.55876 11.2979 -1.65819 19.8688 0.478793C28.4418 2.61577 33.6583 11.2987 31.5211 19.8706Z"
                    fill="#F7931A"
                  />
                  <path
                    d="M23.0552 13.7206C23.3737 11.5916 21.7525 10.4471 19.5358 9.68361L20.2549 6.79963L18.4992 6.36214L17.7991 9.17011C17.3376 9.05511 16.8635 8.94662 16.3924 8.83912L17.0975 6.01264L15.3428 5.57514L14.6233 8.45811C14.2412 8.37111 13.8662 8.28512 13.5021 8.19462L13.5041 8.18562L11.0829 7.58112L10.6158 9.4561C10.6158 9.4561 11.9185 9.7546 11.891 9.7731C12.602 9.95059 12.7306 10.4211 12.7091 10.7941L11.89 14.0796C11.939 14.0921 12.0025 14.1101 12.0725 14.1381C12.014 14.1236 11.9515 14.1076 11.887 14.0921L10.7388 18.6945C10.6518 18.9105 10.4313 19.2345 9.93427 19.1115C9.95177 19.137 8.65813 18.793 8.65813 18.793L7.78654 20.8025L10.0713 21.372C10.4963 21.4785 10.9129 21.59 11.3229 21.695L10.5963 24.612L12.35 25.0494L13.0696 22.1635C13.5486 22.2935 14.0137 22.4135 14.4687 22.5265L13.7517 25.3989L15.5074 25.8364L16.2339 22.925C19.2278 23.4915 21.479 23.263 22.4266 20.5555C23.1902 18.3755 22.3886 17.118 20.8134 16.298C21.9605 16.0335 22.8246 15.2791 23.0552 13.7206ZM19.0437 19.345C18.5012 21.525 14.8303 20.3465 13.6402 20.051L14.6043 16.1865C15.7944 16.4835 19.6108 17.0715 19.0437 19.345ZM19.5868 13.6891C19.0917 15.672 16.0364 14.6645 15.0453 14.4176L15.9194 10.9126C16.9105 11.1596 20.1023 11.6206 19.5868 13.6891Z"
                    fill="white"
                  />
                </svg>
                Bitcoin <span class="{{ $attributes->get('class', '') }} ms-1 text-gray-500 dark:text-gray-400">BTC</span>
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$65,759</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$7,118,022,957</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$72,796,784</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+1,21%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0C24.8366 0 32 7.16344 32 16Z" fill="#F6F6F6" />
                  <path d="M15.9989 5.71429L15.8574 6.18309V19.7854L15.9989 19.9231L22.4722 16.1909L15.9989 5.71429Z" fill="#343434" />
                  <path d="M15.9973 5.71429L9.52381 16.1909L15.9973 19.9231V13.3209V5.71429Z" fill="#8C8C8C" />
                  <path d="M15.9989 21.1184L15.9192 21.2133V26.0586L15.9989 26.2857L22.4762 17.3882L15.9989 21.1184Z" fill="#3C3C3B" />
                  <path d="M15.9973 26.2857V21.1184L9.52381 17.3882L15.9973 26.2857Z" fill="#8C8C8C" />
                  <path d="M15.9964 19.9228L22.4697 16.1906L15.9964 13.3207V19.9228Z" fill="#141414" />
                  <path d="M9.52381 16.1909L15.9973 19.9231V13.3209L9.52381 16.1909Z" fill="#393939" />
                </svg>
                Ethereum <span class="{{ $attributes->get('class', '') }} ms-1 text-gray-500 dark:text-gray-400">ETH</span>
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$4,356</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$845,638,365</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$21,510,606</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">-3,6%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g clip-path="url(#clip0_1_93514)">
                    <path d="M31.9998 15.9999C31.9998 24.8365 24.8365 32 16 32C7.1634 32 0 24.8365 0 15.9999C0 7.1634 7.1634 0 16 0C24.8364 0 31.9998 7.1634 31.9998 15.9999Z" fill="#BEBEBE" />
                    <path
                      d="M28.6029 16.0001C28.6029 22.9601 22.9604 28.603 16 28.603C9.03973 28.603 3.39685 22.9601 3.39685 16.0001C3.39685 9.03951 9.03973 3.39693 16 3.39693C22.9605 3.39693 28.6029 9.03942 28.6029 16.0001Z"
                      fill="#BEBEBE"
                    />
                    <path
                      d="M15.0651 20.2743L16.1043 16.3611L18.5647 15.4622L19.1767 13.1624L19.1558 13.1054L16.7339 13.9902L18.4789 7.41943H13.53L11.2479 15.9944L9.34248 16.6905L8.71289 19.0614L10.6168 18.3659L9.27182 23.4195H22.4429L23.2873 20.2743H15.0651Z"
                      fill="white"
                    />
                  </g>
                  <defs>
                    <clipPath id="clip0_1_93514">
                      <rect width="32" height="32" fill="white" />
                    </clipPath>
                  </defs>
                </svg>
                Litecoin <span class="{{ $attributes->get('class', '') }} ms-1 text-gray-500 dark:text-gray-400">LTC</span>
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$30,82</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$163,746,003</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$680,335</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+0,38%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g clip-path="url(#clip0_1_93520)">
                    <path d="M16 32C24.8366 32 32 24.8366 32 16C32 7.16344 24.8366 0 16 0C7.16344 0 0 7.16344 0 16C0 24.8366 7.16344 32 16 32Z" fill="#2573C2" />
                    <path
                      d="M26.4416 11.7888C26.4201 11.4303 26.3149 11.0817 26.1344 10.7712C25.9744 10.4512 25.6896 10.2016 25.3568 10.0736C25.0174 9.90446 24.6414 9.822 24.2624 9.8336H10.0608L9.0432 12.8832H21.9104L19.8784 19.1168H7.0144L5.9968 22.1664H20.2656C20.6908 22.1541 21.1111 22.073 21.5104 21.9264C21.9104 21.6992 22.368 21.4688 22.7552 21.1488C23.1332 20.8508 23.4748 20.5092 23.7728 20.1312C24.0192 19.7608 24.2292 19.3675 24.4 18.9568L26.2848 13.0304C26.4563 12.6405 26.5107 12.2091 26.4416 11.7888Z"
                      fill="white"
                    />
                    <path d="M14.1152 14.5856H6.544L5.5264 17.4048H13.1744L14.1152 14.5856Z" fill="white" />
                  </g>
                  <defs>
                    <clipPath id="clip0_1_93520">
                      <rect width="32" height="32" fill="white" />
                    </clipPath>
                  </defs>
                </svg>

                Dash
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$634.77</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$45,756,182</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$1,899,061</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+2,31%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0C24.8366 0 32 7.16344 32 16Z" fill="#C2A633" />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M10.7091 24.3699H16.2329C16.2329 24.3699 24 25.0313 24 16.1348C24 7.58322 16.9073 7.61355 15.5365 7.61941C15.5044 7.61955 15.4755 7.61967 15.4498 7.61967H10.7089V15.0812H8.7619V16.9088H10.7091V24.3699ZM13.8204 10.7158H15.9997C16.8148 10.7158 20.9157 11.0491 20.9223 16.1951C20.9288 21.2798 16.7941 21.2745 16.129 21.2737C16.121 21.2736 16.1135 21.2736 16.1065 21.2736H13.8204V16.9087H17.2491V15.081H13.8204V10.7158Z"
                    fill="white"
                  />
                </svg>
                Dogecoin
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$0,0002345</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$145,358,445</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$215,622</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">-0,03%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect width="32" height="32" rx="16" fill="#0D022B" />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M10.4944 10.4855C10.566 10.4141 10.609 10.3426 10.609 10.2426C10.609 10.1426 10.566 10.0712 10.4944 9.99979C10.3512 9.87122 10.1221 9.87122 9.96462 9.99979C9.89303 10.0712 9.85007 10.1426 9.85007 10.2426C9.85007 10.3426 9.89303 10.4141 9.96462 10.4855C10.0362 10.5426 10.1365 10.5855 10.2224 10.5855C10.3226 10.5998 10.4228 10.5712 10.4944 10.4855Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M11.4109 11.4712C11.4681 11.3998 11.5111 11.3284 11.5111 11.2284C11.5111 11.1284 11.4681 11.0569 11.4109 10.9855C11.282 10.8569 11.0529 10.8569 10.924 10.9855C10.8667 11.0569 10.8238 11.1284 10.8238 11.2284C10.8238 11.3284 10.8667 11.3998 10.924 11.4712C10.9956 11.5284 11.0672 11.5712 11.1674 11.5712C11.2677 11.5712 11.3393 11.5284 11.4109 11.4712Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M12.4132 12.4141C12.4705 12.3569 12.5135 12.2712 12.5135 12.1712C12.5135 12.0712 12.4705 11.9998 12.4132 11.9284C12.2843 11.7998 12.0552 11.7998 11.9264 11.9284C11.8691 11.9855 11.8261 12.0712 11.8261 12.1712C11.8261 12.2712 11.8691 12.3426 11.9264 12.4141C11.998 12.4855 12.0696 12.5141 12.1698 12.5141C12.27 12.5141 12.3559 12.4712 12.4132 12.4141Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M13.3726 13.3712C13.4442 13.3141 13.4872 13.2284 13.4872 13.1284C13.4872 13.0284 13.4442 12.9569 13.3726 12.8855C13.2294 12.7569 13.0003 12.7569 12.8428 12.8855C12.7712 12.9426 12.7282 13.0284 12.7282 13.1284C12.7282 13.2284 12.7712 13.3141 12.8428 13.3712C12.9144 13.4426 13.0146 13.4712 13.1005 13.4712C13.2008 13.4712 13.2867 13.4569 13.3726 13.3712Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M14.289 14.3284C14.3606 14.2712 14.3893 14.1855 14.3893 14.0855C14.3893 13.9855 14.3463 13.9141 14.289 13.8426C14.1602 13.7141 13.9311 13.7141 13.8022 13.8426C13.7306 13.8998 13.702 13.9855 13.702 14.0855C13.702 14.1855 13.7449 14.2712 13.8022 14.3284C13.8595 14.3998 13.9454 14.4284 14.0456 14.4284C14.1315 14.4426 14.2318 14.4141 14.289 14.3284Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M15.3916 15.0426C15.3916 14.9426 15.3487 14.8712 15.2914 14.7998C15.1625 14.6712 14.9334 14.6712 14.8045 14.7998C14.7329 14.8712 14.7043 14.9426 14.7043 15.0426C14.7043 15.1426 14.7473 15.2141 14.8045 15.2855C14.8618 15.3426 14.9477 15.3855 15.048 15.3855C15.1482 15.3855 15.2198 15.3426 15.2914 15.2855C15.363 15.2284 15.3916 15.1426 15.3916 15.0426Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M17.1815 17.1569C17.2388 17.0998 17.2818 17.0141 17.2818 16.9141C17.2818 16.8141 17.2388 16.7426 17.1815 16.6712C17.0527 16.5426 16.8236 16.5426 16.6947 16.6712C16.6231 16.7426 16.5944 16.8141 16.5944 16.9141C16.5944 17.0141 16.6374 17.0855 16.6947 17.1569C16.752 17.2284 16.8379 17.2569 16.9381 17.2569C17.024 17.2712 17.1099 17.2426 17.1815 17.1569Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M18.1696 18.1141C18.2412 18.0569 18.2841 17.9712 18.2841 17.8712C18.2841 17.7712 18.2412 17.6998 18.1696 17.6284C18.0264 17.4998 17.7973 17.4998 17.6398 17.6284C17.5682 17.6998 17.5252 17.7712 17.5252 17.8712C17.5252 17.9712 17.5682 18.0426 17.6398 18.1141C17.7113 18.1855 17.8116 18.2141 17.8975 18.2141C18.0121 18.2284 18.098 18.1855 18.1696 18.1141Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M19.1003 19.0855C19.1576 19.0284 19.2006 18.9426 19.2006 18.8426C19.2006 18.7426 19.1576 18.6569 19.1003 18.5998C18.9714 18.4712 18.7423 18.4712 18.6135 18.5998C18.5562 18.6569 18.5132 18.7426 18.5132 18.8426C18.5132 18.9426 18.5562 19.0141 18.6135 19.0855C18.6851 19.1569 18.7567 19.1855 18.8569 19.1855C18.9571 19.1855 19.0144 19.1426 19.1003 19.0855Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M20.0597 20.0426C20.1313 19.9855 20.1599 19.8998 20.1599 19.7998C20.1599 19.6998 20.117 19.6284 20.0597 19.5569C19.9308 19.4284 19.7017 19.4284 19.5729 19.5569C19.5156 19.6284 19.4726 19.6998 19.4726 19.7998C19.4726 19.8998 19.5156 19.9855 19.5729 20.0426C19.6445 20.1141 19.716 20.1426 19.8163 20.1426C19.9022 20.1426 20.0024 20.1141 20.0597 20.0426Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M21.0477 20.9998C21.1193 20.9426 21.1623 20.8569 21.1623 20.7569C21.1623 20.6569 21.1193 20.5855 21.0477 20.5141C20.9045 20.3855 20.6754 20.3855 20.5466 20.5141C20.475 20.5855 20.432 20.6569 20.432 20.7569C20.432 20.8569 20.475 20.9426 20.5466 20.9998C20.6182 21.0569 20.7184 21.0998 20.8043 21.0998C20.9045 21.0998 20.9905 21.0712 21.0477 20.9998Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M21.9928 21.9569C22.0644 21.8855 22.093 21.8141 22.093 21.7141C22.093 21.6141 22.0501 21.5426 21.9928 21.4712C21.8639 21.3426 21.6348 21.3426 21.506 21.4712C21.4344 21.5426 21.4057 21.6141 21.4057 21.7141C21.4057 21.8141 21.4487 21.8855 21.506 21.9569C21.5632 22.0141 21.6492 22.0569 21.7494 22.0569C21.821 22.0712 21.9069 22.0284 21.9928 21.9569Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M21.506 10.4855C21.4487 10.4141 21.4057 10.3426 21.4057 10.2426C21.4057 10.1426 21.4487 10.0712 21.506 9.99979C21.6348 9.87122 21.8639 9.87122 21.9928 9.99979C22.0644 10.0712 22.093 10.1426 22.093 10.2426C22.093 10.3426 22.0501 10.4141 21.9928 10.4855C21.9355 10.5426 21.8496 10.5855 21.7494 10.5855C21.6492 10.5998 21.5632 10.5712 21.506 10.4855Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M20.5466 11.4712C20.475 11.3998 20.432 11.3284 20.432 11.2284C20.432 11.1284 20.475 11.0569 20.5466 10.9855C20.6898 10.8569 20.9189 10.8569 21.0764 10.9855C21.148 11.0569 21.1909 11.1284 21.1909 11.2284C21.1909 11.3284 21.148 11.3998 21.0764 11.4712C21.0048 11.5284 20.9045 11.5712 20.8186 11.5712C20.7184 11.5569 20.6182 11.5284 20.5466 11.4712Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M19.5729 12.4141C19.5156 12.3569 19.4726 12.2712 19.4726 12.1712C19.4726 12.0712 19.5156 11.9998 19.5729 11.9284C19.7017 11.7998 19.9308 11.7998 20.0597 11.9284C20.117 11.9855 20.1599 12.0712 20.1599 12.1712C20.1599 12.2712 20.117 12.3426 20.0597 12.4141C19.9881 12.4855 19.9165 12.5141 19.8163 12.5141C19.7304 12.5141 19.6445 12.4712 19.5729 12.4141Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M18.6135 13.3712C18.5562 13.3141 18.5132 13.2284 18.5132 13.1284C18.5132 13.0284 18.5562 12.9569 18.6135 12.8855C18.7423 12.7569 18.9714 12.7569 19.1003 12.8855C19.1576 12.9426 19.2006 13.0284 19.2006 13.1284C19.2006 13.2284 19.1576 13.3141 19.1003 13.3712C19.0287 13.4426 18.9571 13.4712 18.8569 13.4712C18.771 13.4712 18.6851 13.4569 18.6135 13.3712Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M17.6684 14.3284C17.5968 14.2712 17.5538 14.1855 17.5538 14.0855C17.5538 13.9855 17.5968 13.9141 17.6684 13.8426C17.7973 13.7141 18.0407 13.7141 18.1696 13.8426C18.2412 13.8998 18.2841 13.9855 18.2841 14.0855C18.2841 14.1855 18.2412 14.2712 18.1696 14.3284C18.098 14.3998 17.9977 14.4284 17.9118 14.4284C17.8259 14.4426 17.7257 14.4141 17.6684 14.3284Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M16.5944 15.0426C16.5944 14.9426 16.6374 14.8712 16.6947 14.7998C16.8236 14.6712 17.0527 14.6712 17.1815 14.7998C17.2531 14.8712 17.2818 14.9426 17.2818 15.0426C17.2818 15.1426 17.2388 15.2141 17.1815 15.2855C17.1243 15.3426 17.0383 15.3855 16.9381 15.3855C16.8379 15.3855 16.7663 15.3426 16.6947 15.2855C16.6231 15.2284 16.5944 15.1426 16.5944 15.0426Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M15.7353 16.2569C15.6637 16.1855 15.6351 16.1141 15.6351 16.0141C15.6351 15.9141 15.678 15.8426 15.7353 15.7712C15.8642 15.6426 16.0933 15.6426 16.2221 15.7712C16.2937 15.8426 16.3224 15.9141 16.3224 16.0141C16.3224 16.1141 16.2794 16.1855 16.2221 16.2569C16.1649 16.3141 16.079 16.3569 15.9787 16.3569C15.8785 16.3569 15.7926 16.3141 15.7353 16.2569Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M14.7759 17.1569C14.7043 17.0998 14.6613 17.0141 14.6613 16.9141C14.6613 16.8141 14.7043 16.7426 14.7759 16.6712C14.9191 16.5426 15.1482 16.5426 15.2771 16.6712C15.3487 16.7426 15.3916 16.8141 15.3916 16.9141C15.3916 17.0141 15.3487 17.0855 15.2771 17.1569C15.2055 17.2284 15.1052 17.2569 15.0193 17.2569C14.9477 17.2712 14.8475 17.2426 14.7759 17.1569Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M13.8022 18.1141C13.7449 18.0569 13.702 17.9712 13.702 17.8712C13.702 17.7712 13.7449 17.6998 13.8022 17.6284C13.9311 17.4998 14.1602 17.4998 14.289 17.6284C14.3463 17.6998 14.3893 17.7712 14.3893 17.8712C14.3893 17.9712 14.3463 18.0426 14.289 18.1141C14.2174 18.1855 14.1459 18.2141 14.0456 18.2141C13.974 18.2284 13.8738 18.1855 13.8022 18.1141Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M12.8428 19.0855C12.7855 19.0284 12.7426 18.9426 12.7426 18.8426C12.7426 18.7426 12.7855 18.6569 12.8428 18.5998C12.9717 18.4712 13.2008 18.4712 13.3297 18.5998C13.3869 18.6569 13.4299 18.7426 13.4299 18.8426C13.4299 18.9426 13.3869 19.0141 13.3297 19.0855C13.2581 19.1569 13.1865 19.1855 13.0862 19.1855C12.986 19.1855 12.9144 19.1426 12.8428 19.0855Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M11.9407 20.0426C11.8691 19.9855 11.8404 19.8998 11.8404 19.7998C11.8404 19.6998 11.8834 19.6284 11.9407 19.5569C12.0696 19.4284 12.2987 19.4284 12.4275 19.5569C12.4848 19.6284 12.5278 19.6998 12.5278 19.7998C12.5278 19.8998 12.4848 19.9855 12.4275 20.0426C12.3559 20.1141 12.2843 20.1426 12.1841 20.1426C12.0982 20.1426 11.998 20.1141 11.9407 20.0426Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M10.924 20.9998C10.8524 20.9426 10.8238 20.8569 10.8238 20.7569C10.8238 20.6569 10.8667 20.5855 10.924 20.5141C11.0529 20.3855 11.282 20.3855 11.4109 20.5141C11.4825 20.5855 11.5111 20.6569 11.5111 20.7569C11.5111 20.8569 11.4681 20.9426 11.4109 20.9998C11.3536 21.0569 11.2677 21.0998 11.1674 21.0998C11.0672 21.0998 10.9813 21.0712 10.924 20.9998Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M9.96462 21.9569C9.89303 21.8855 9.85007 21.8141 9.85007 21.7141C9.85007 21.6141 9.89303 21.5426 9.96462 21.4712C10.1078 21.3426 10.3369 21.3426 10.4658 21.4712C10.5374 21.5426 10.5803 21.6141 10.5803 21.7141C10.5803 21.8141 10.5374 21.8855 10.4658 21.9569C10.3942 22.0141 10.294 22.0569 10.208 22.0569C10.1365 22.0712 10.0505 22.0284 9.96462 21.9569Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M19.2292 10.7569C18.2555 10.1141 17.1815 9.81408 15.993 9.79979C14.8332 9.79979 13.7592 10.1141 12.7569 10.7569L10.6949 8.69979C12.313 7.47122 14.0599 6.85693 15.993 6.85693C17.9261 6.85693 19.7017 7.47122 21.2768 8.69979L19.2292 10.7569Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M21.2912 19.1998C21.9499 18.1998 22.2649 17.1426 22.2649 15.9998C22.2649 14.8426 21.9499 13.7712 21.2912 12.7998L23.3388 10.7569C24.5416 12.3284 25.1431 14.0712 25.1431 16.0141C25.1431 17.9284 24.5416 19.6855 23.3245 21.2855L21.2912 19.1998Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M12.7712 21.2426C13.7449 21.8998 14.8189 22.2426 15.9787 22.2569C17.1672 22.2569 18.2555 21.9426 19.2292 21.2569L21.2912 23.2998C19.7017 24.5426 17.9261 25.1569 15.9787 25.1426C14.0456 25.1284 12.2987 24.4998 10.7235 23.2998L12.7712 21.2426Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M10.7522 12.7712C10.0935 13.7712 9.74983 14.8569 9.77847 16.0284C9.79279 17.1712 10.1221 18.2141 10.7522 19.1712L8.69021 21.2141C7.47307 19.6426 6.84302 17.8998 6.84302 15.9855C6.8287 14.0426 7.44443 12.2712 8.69021 10.6855L10.7522 12.7712Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M8.60429 21.8855C7.81673 21.8855 7.15804 22.5284 7.15804 23.3284C7.15804 24.1141 7.80241 24.7712 8.60429 24.7712C9.39185 24.7712 10.0505 24.1284 10.0505 23.3284C10.0505 22.5284 9.40617 21.8855 8.60429 21.8855Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M23.3531 21.8855C22.5656 21.8855 21.9212 22.5284 21.9212 23.3284C21.9212 24.1141 22.5656 24.7712 23.3531 24.7712C24.1407 24.7712 24.7851 24.1284 24.7851 23.3284C24.7851 22.5284 24.1407 21.8855 23.3531 21.8855Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M8.57565 7.17122C7.78809 7.17122 7.14373 7.81408 7.14373 8.61408C7.14373 9.41408 7.78809 10.0569 8.57565 10.0569C9.36321 10.0569 10.0076 9.41408 10.0076 8.61408C10.0076 7.81408 9.36321 7.17122 8.57565 7.17122Z"
                    fill="white"
                  />
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M23.3818 7.17122C22.5942 7.17122 21.9355 7.81408 21.9355 8.59979C21.9355 9.3855 22.5799 10.0284 23.3818 10.0284C24.1693 10.0284 24.828 9.3855 24.828 8.59979C24.828 7.81408 24.1837 7.17122 23.3818 7.17122Z"
                    fill="white"
                  />
                </svg>

                Elrond
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$13.04</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$26,835,600</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$1,899,033</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+1,64%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect width="32" height="32" rx="16" fill="#50AF95" />
                  <path
                    d="M16.0785 6.76678C18.5536 6.76678 20.9297 6.76678 23.4048 6.76678C23.6028 6.76678 23.7018 6.86705 23.7018 6.96733C25.0879 10.0758 26.4739 13.0841 27.959 16.0923C28.058 16.1926 27.959 16.2928 27.86 16.3931C26.1769 18.0978 24.4939 19.8024 22.7118 21.5071C20.5337 23.6128 18.2566 25.8189 16.0785 27.9246C15.8805 28.0249 15.8805 28.0249 15.7815 27.9246C13.6034 25.8189 11.4253 23.7131 9.24722 21.5071C7.56415 19.8024 5.78207 18.0978 4.099 16.3931C4 16.2928 4 16.1926 4 16.0923C4.69303 14.4879 5.48506 12.9838 6.17809 11.3794C6.87112 9.87528 7.56415 8.47144 8.25718 6.96733C8.35618 6.76678 8.45518 6.6665 8.7522 6.6665C11.2273 6.76678 13.6034 6.6665 16.0785 6.76678ZM16.1775 9.47418C14.3954 9.47418 12.5144 9.47418 10.7323 9.47418C10.6333 9.47418 10.6333 9.47418 10.5343 9.47418C10.4353 9.47418 10.4353 9.47418 10.4353 9.57446C10.4353 10.4769 10.4353 11.3794 10.3363 12.3821C10.3363 12.4824 10.3363 12.4824 10.4353 12.4824C10.7323 12.4824 11.0293 12.4824 11.3263 12.4824C12.3163 12.4824 13.3064 12.4824 14.2964 12.4824C14.3954 12.4824 14.3954 12.4824 14.4944 12.4824C14.5934 12.4824 14.5934 12.5827 14.5934 12.683C14.5934 13.0841 14.5934 13.5854 14.5934 13.9865C14.5934 14.0868 14.5934 14.0868 14.4944 14.1871C13.8014 14.2873 13.1084 14.2873 12.4153 14.3876C11.5243 14.4879 10.6333 14.6884 9.84124 14.9893C9.44523 15.0895 9.14821 15.2901 8.8512 15.4906C8.55419 15.7915 8.55419 16.1926 8.8512 16.3931C9.04921 16.5936 9.24722 16.6939 9.44523 16.7942C10.1383 17.095 10.8313 17.1953 11.5243 17.3958C12.5144 17.5964 13.5044 17.6967 14.4944 17.7969C14.6924 17.7969 14.6924 17.7969 14.6924 17.9975C14.6924 19.8024 14.6924 21.7076 14.6924 23.5126C14.6924 23.7131 14.6924 23.7131 14.8905 23.7131C15.7815 23.7131 16.7715 23.7131 17.6626 23.7131C17.9596 23.7131 17.9596 23.7131 17.9596 23.4123C17.9596 21.9082 17.9596 20.5043 17.9596 19.0002C17.9596 18.5991 17.9596 18.198 17.9596 17.7969C17.9596 17.5964 18.0586 17.5964 18.1576 17.5964C19.1476 17.5964 20.0387 17.4961 21.0287 17.2956C21.8207 17.1953 22.5138 16.9947 23.2068 16.6939C23.4048 16.5936 23.6028 16.3931 23.8008 16.2928C23.9988 16.0923 23.9988 15.8917 23.8008 15.6912C23.6028 15.5909 23.5038 15.3904 23.3058 15.2901C22.1177 14.5882 20.7317 14.2873 19.3456 14.1871C18.9496 14.1871 18.5536 14.1871 18.1576 14.0868C18.0586 14.0868 18.0586 14.0868 18.0586 13.8862C18.0586 13.4851 18.0586 12.9838 18.0586 12.5827C18.0586 12.4824 18.0586 12.3821 18.2566 12.3821C18.5536 12.3821 18.8506 12.3821 19.1476 12.3821C20.1377 12.3821 21.0287 12.3821 22.0187 12.3821C22.2168 12.3821 22.2168 12.2819 22.2168 12.1816C22.2168 12.0813 22.2168 12.0813 22.2168 11.981C22.2168 11.1788 22.2168 10.3766 22.2168 9.47418C22.2168 9.27363 22.2168 9.27363 22.0187 9.27363C19.8407 9.47418 17.9596 9.47418 16.1775 9.47418Z"
                    fill="white"
                  />
                  <path
                    d="M15.6821 17.0949C14.9891 17.0949 14.395 17.0949 13.702 16.9947C12.613 16.8944 11.4249 16.7941 10.3359 16.393C10.1379 16.2927 9.93986 16.1925 9.64284 16.0922C9.34583 15.8916 9.34583 15.6911 9.64284 15.5908C10.0389 15.3903 10.4349 15.29 10.8309 15.1897C11.6229 14.9892 12.415 14.8889 13.306 14.7886C13.603 14.7886 13.999 14.6884 14.296 14.6884C14.395 14.6884 14.494 14.6884 14.494 14.8889C14.494 15.4906 14.494 15.9919 14.494 16.5936C14.494 16.7941 14.5931 16.7941 14.6921 16.7941C15.4841 16.7941 16.2761 16.7941 16.9692 16.7941C17.1672 16.7941 17.3652 16.7941 17.5632 16.7941C17.7612 16.7941 17.7612 16.7941 17.7612 16.5936C17.7612 16.0922 17.7612 15.5908 17.7612 15.0895C17.7612 14.7886 17.7612 14.7886 18.0582 14.7886C19.3453 14.8889 20.6323 14.9892 21.9194 15.3903C22.1174 15.4906 22.4144 15.5908 22.6124 15.6911C22.8104 15.7914 22.8104 15.9919 22.6124 16.0922C22.2164 16.393 21.8204 16.4933 21.3253 16.5936C20.3353 16.7941 19.3453 16.9947 18.3552 16.9947C17.5632 16.9947 16.5731 17.0949 15.6821 17.0949Z"
                    fill="white"
                  />
                </svg>
                USDT <span class="{{ $attributes->get('class', '') }} ms-1 text-gray-500 dark:text-gray-400">ThetherUS</span>
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$0.293</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$10,857,204</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$150,293</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+0,25%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect width="32" height="32" rx="16" fill="#F3BA2F" />
                  <path
                    d="M18.2226 23.931V26.6207L15.8778 28L13.6019 26.6207V23.931L15.8778 25.3103L18.2226 23.931ZM5.6709 14.6207L7.94676 16V20.6207L11.8778 22.9655V25.6552L5.6709 22V14.6207ZM26.0847 14.6207V22L19.8088 25.6552V22.9655L23.7399 20.6207V16L26.0847 14.6207ZM19.8088 10.9655L22.1537 12.3448V15.0345L18.2226 17.3793V22.069L15.9468 23.4483L13.6709 22.069V17.3793L9.60193 15.0345V12.3448L11.9468 10.9655L15.8778 13.3103L19.8088 10.9655ZM9.60193 16.9655L11.8778 18.3448V21.0345L9.60193 19.6552V16.9655ZM22.1537 16.9655V19.6552L19.8778 21.0345V18.3448L22.1537 16.9655ZM7.94676 8.62069L10.2916 10L7.94676 11.3793V14.069L5.6709 12.6897V10L7.94676 8.62069ZM23.8088 8.62069L26.1537 10V12.6897L23.8088 14.069V11.3793L21.533 10L23.8088 8.62069ZM15.8778 8.62069L18.2226 10L15.8778 11.3793L13.6019 10L15.8778 8.62069ZM15.8778 4L22.1537 7.65517L19.8778 9.03448L15.9468 6.68965L11.9468 9.03448L9.6709 7.65517L15.8778 4Z"
                    fill="white"
                  />
                </svg>
                BNB
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$103</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$5,203,391</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$15,903</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+0.023%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect width="32" height="32" rx="16" fill="black" />
                  <path
                    d="M10.5995 20.2105C10.696 20.0873 10.8288 20.0155 10.9696 20.0155H23.7377C23.971 20.0155 24.0877 20.3747 23.9228 20.5852L21.4005 23.8033C21.304 23.9265 21.1712 23.9984 21.0304 23.9984H8.26229C8.02897 23.9984 7.91231 23.6391 8.07724 23.4287L10.5995 20.2105Z"
                    fill="url(#paint0_linear_1_93545)"
                  />
                  <path
                    d="M10.5995 8.19504C10.7001 8.07186 10.8328 8 10.9696 8H23.7377C23.971 8 24.0877 8.35928 23.9228 8.56972L21.4005 11.7879C21.304 11.9111 21.1712 11.9829 21.0304 11.9829H8.26229C8.02897 11.9829 7.91231 11.6236 8.07724 11.4132L10.5995 8.19504Z"
                    fill="url(#paint1_linear_1_93545)"
                  />
                  <path
                    d="M21.4005 14.1643C21.304 14.0411 21.1712 13.9692 21.0304 13.9692H8.26229C8.02897 13.9692 7.91231 14.3285 8.07724 14.539L10.5995 17.7571C10.696 17.8803 10.8288 17.9522 10.9696 17.9522H23.7377C23.971 17.9522 24.0877 17.5929 23.9228 17.3824L21.4005 14.1643Z"
                    fill="url(#paint2_linear_1_93545)"
                  />
                  <defs>
                    <linearGradient id="paint0_linear_1_93545" x1="22.518" y1="-47.9851" x2="-13.6815" y2="-34.456" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#00FFA3" />
                      <stop offset="1" stop-color="#DC1FFF" />
                    </linearGradient>
                    <linearGradient id="paint1_linear_1_93545" x1="18.6542" y1="-10.0603" x2="-17.5454" y2="3.46879" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#00FFA3" />
                      <stop offset="1" stop-color="#DC1FFF" />
                    </linearGradient>
                    <linearGradient id="paint2_linear_1_93545" x1="20.5738" y1="4.78253" x2="7.89382" y2="23.8178" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#00FFA3" />
                      <stop offset="1" stop-color="#DC1FFF" />
                    </linearGradient>
                  </defs>
                </svg>
                Solana
              </div>
            </th>

            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$24.15</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$60,968,229</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$92,161</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">-0.0002%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} hover:bg-gray-100 dark:hover:bg-gray-800">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect width="32" height="32" rx="16" fill="#F5F5F5" />
                  <path
                    d="M21.6562 8H23.9688L19.1562 13.754C17.4134 15.8366 14.5878 15.8366 12.8438 13.754L8.02938 8H10.3438L14 12.3709C14.5327 13.0049 15.2506 13.3604 15.9986 13.3604C16.7466 13.3604 17.4645 13.0049 17.9972 12.3709L21.6562 8Z"
                    fill="#23292F"
                  />
                  <path
                    d="M10.3141 24H8L12.8438 18.2106C14.5866 16.1279 17.4122 16.1279 19.1562 18.2106L24 24H21.6875L18 19.5936C17.4673 18.9596 16.7494 18.6042 16.0014 18.6042C15.2534 18.6042 14.5355 18.9596 14.0028 19.5936L10.3141 24Z"
                    fill="#23292F"
                  />
                </svg>

                XPR Ripple
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$0.6399</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$3,023,948</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-primary-700 dark:text-primary-500">$2,796,784</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">+75,44%</td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Details
                </button>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                    <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                  </svg>

                  Trade
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative overflow-hidden rounded-b-lg border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
      <nav class="{{ $attributes->get('class', '') }} flex flex-col items-start justify-between space-y-3 sm:flex-row sm:items-center sm:space-y-0" aria-label="Table navigation">
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-5 text-sm">
          <div>
            <div class="{{ $attributes->get('class', '') }} mb-1 text-gray-500 dark:text-gray-400">Purchase price</div>
            <div class="{{ $attributes->get('class', '') }} font-medium dark:text-white">$ 3,567,890</div>
          </div>
          <div>
            <div class="{{ $attributes->get('class', '') }} mb-1 text-gray-500 dark:text-gray-400">Total selling price</div>
            <div class="{{ $attributes->get('class', '') }} font-medium dark:text-white">$ 8,489,400</div>
          </div>
        </div>
        <ul class="{{ $attributes->get('class', '') }} inline-flex items-stretch -space-x-px">
          <li>
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} ml-0 flex h-full items-center justify-center rounded-l-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <span class="{{ $attributes->get('class', '') }} sr-only">Previous</span>
              <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
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
              <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
              </svg>              
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} my-4 grid grid-cols-1 gap-4 2xl:grid-cols-3">
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white md:mb-6">Recent transactions</h2>
      <ul>
        <li class="{{ $attributes->get('class', '') }} border-b border-gray-200 pb-4 dark:border-gray-700">
          <div class="{{ $attributes->get('class', '') }} items-center space-y-3 sm:flex sm:space-x-4 sm:space-y-0">
            <div class="{{ $attributes->get('class', '') }} flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4" />
              </svg>
            </div>
            <div class="{{ $attributes->get('class', '') }} min-w-0 flex-1">
              <p class="{{ $attributes->get('class', '') }} truncate font-semibold text-gray-900 dark:text-white">Deposit</p>
              <p class="{{ $attributes->get('class', '') }} truncate text-sm font-normal text-gray-500 dark:text-gray-400">02-09-2025 17:43</p>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between sm:block">
              <p class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">+5.01885337 EGLD</p>
              <div class="{{ $attributes->get('class', '') }} flex items-center justify-end text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} me-1 h-2.5 w-2.5 rounded-full bg-green-500"></span>Completed</div>
            </div>
          </div>
        </li>
        <li class="{{ $attributes->get('class', '') }} border-b border-gray-200 py-4 dark:border-gray-700">
          <div class="{{ $attributes->get('class', '') }} items-center space-y-3 sm:flex sm:space-x-4 sm:space-y-0">
            <div class="{{ $attributes->get('class', '') }} flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-6 w-6 rotate-90 text-gray-500 dark:text-gray-400" aria-hidden="true" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4" />
              </svg>
            </div>
            <div class="{{ $attributes->get('class', '') }} min-w-0 flex-1">
              <p class="{{ $attributes->get('class', '') }} truncate font-semibold text-gray-900 dark:text-white">Transfer</p>
              <p class="{{ $attributes->get('class', '') }} truncate text-sm font-normal text-gray-500 dark:text-gray-400">02-09-2025 17:43</p>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between sm:block">
              <p class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">0.85337 BTC</p>
              <div class="{{ $attributes->get('class', '') }} flex items-center justify-end text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} me-1 h-2.5 w-2.5 rounded-full bg-orange-400"></span>Waiting</div>
            </div>
          </div>
        </li>
        <li class="{{ $attributes->get('class', '') }} border-b border-gray-200 py-4 dark:border-gray-700">
          <div class="{{ $attributes->get('class', '') }} items-center space-y-3 sm:flex sm:space-x-4 sm:space-y-0">
            <div class="{{ $attributes->get('class', '') }} flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4" />
              </svg>
            </div>
            <div class="{{ $attributes->get('class', '') }} min-w-0 flex-1">
              <p class="{{ $attributes->get('class', '') }} truncate font-semibold text-gray-900 dark:text-white">Deposit</p>
              <p class="{{ $attributes->get('class', '') }} truncate text-sm font-normal text-gray-500 dark:text-gray-400">15-07-2025 12:03</p>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between sm:block">
              <p class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">+10 ETH</p>
              <div class="{{ $attributes->get('class', '') }} flex items-center justify-end text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} me-1 h-2.5 w-2.5 rounded-full bg-green-500"></span>Completed</div>
            </div>
          </div>
        </li>
        <li class="{{ $attributes->get('class', '') }} border-b border-gray-200 py-4 dark:border-gray-700">
          <div class="{{ $attributes->get('class', '') }} items-center space-y-3 sm:flex sm:space-x-4 sm:space-y-0">
            <div class="{{ $attributes->get('class', '') }} flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4" />
              </svg>
            </div>
            <div class="{{ $attributes->get('class', '') }} min-w-0 flex-1">
              <p class="{{ $attributes->get('class', '') }} truncate font-semibold text-gray-900 dark:text-white">Deposit</p>
              <p class="{{ $attributes->get('class', '') }} truncate text-sm font-normal text-gray-500 dark:text-gray-400">18-07-2025 22:17</p>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between sm:block">
              <p class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">+5.2253 EGLD</p>
              <div class="{{ $attributes->get('class', '') }} flex items-center justify-end text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} me-1 h-2.5 w-2.5 rounded-full bg-red-600 dark:bg-red-500"></span>Cancelled</div>
            </div>
          </div>
        </li>
        <li class="{{ $attributes->get('class', '') }} py-4">
          <div class="{{ $attributes->get('class', '') }} items-center space-y-3 sm:flex sm:space-x-4 sm:space-y-0">
            <div class="{{ $attributes->get('class', '') }} flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M5.535 7.677c.313-.98.687-2.023.926-2.677H17.46c.253.63.646 1.64.977 2.61.166.487.312.953.416 1.347.11.42.148.675.148.779 0 .18-.032.355-.09.515-.06.161-.144.3-.243.412-.1.111-.21.192-.324.245a.809.809 0 0 1-.686 0 1.004 1.004 0 0 1-.324-.245c-.1-.112-.183-.25-.242-.412a1.473 1.473 0 0 1-.091-.515 1 1 0 1 0-2 0 1.4 1.4 0 0 1-.333.927.896.896 0 0 1-.667.323.896.896 0 0 1-.667-.323A1.401 1.401 0 0 1 13 9.736a1 1 0 1 0-2 0 1.4 1.4 0 0 1-.333.927.896.896 0 0 1-.667.323.896.896 0 0 1-.667-.323A1.4 1.4 0 0 1 9 9.74v-.008a1 1 0 0 0-2 .003v.008a1.504 1.504 0 0 1-.18.712 1.22 1.22 0 0 1-.146.209l-.007.007a1.01 1.01 0 0 1-.325.248.82.82 0 0 1-.316.08.973.973 0 0 1-.563-.256 1.224 1.224 0 0 1-.102-.103A1.518 1.518 0 0 1 5 9.724v-.006a2.543 2.543 0 0 1 .029-.207c.024-.132.06-.296.11-.49.098-.385.237-.85.395-1.344ZM4 12.112a3.521 3.521 0 0 1-1-2.376c0-.349.098-.8.202-1.208.112-.441.264-.95.428-1.46.327-1.024.715-2.104.958-2.767A1.985 1.985 0 0 1 6.456 3h11.01c.803 0 1.539.481 1.844 1.243.258.641.67 1.697 1.019 2.72a22.3 22.3 0 0 1 .457 1.487c.114.433.214.903.214 1.286 0 .412-.072.821-.214 1.207A3.288 3.288 0 0 1 20 12.16V19a2 2 0 0 1-2 2h-6a1 1 0 0 1-1-1v-4H8v4a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2v-6.888ZM13 15a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="{{ $attributes->get('class', '') }} min-w-0 flex-1">
              <p class="{{ $attributes->get('class', '') }} truncate font-semibold text-gray-900 dark:text-white">Sell</p>
              <p class="{{ $attributes->get('class', '') }} truncate text-sm font-normal text-gray-500 dark:text-gray-400">10-06-2025 19:09</p>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between sm:block">
              <p class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">-4,1846 BTC</p>
              <div class="{{ $attributes->get('class', '') }} flex items-center justify-end text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} me-1 h-2.5 w-2.5 rounded-full bg-green-500"></span>Completed</div>
            </div>
          </div>
        </li>
      </ul>
      <div class="{{ $attributes->get('class', '') }} border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg w-full sm:w-auto border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          View all
        </button>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="{{ $attributes->get('class', '') }} flex justify-between items-center mb-4 md:mb-6">
        <h2 class="{{ $attributes->get('class', '') }} text-xl font-bold text-gray-900 dark:text-white">Exchange</h2>
        <div class="{{ $attributes->get('class', '') }} ms-auto w-fit border-b border-gray-100 dark:border-gray-700">
          <ul class="{{ $attributes->get('class', '') }} -mb-px flex space-x-2 flex-wrap text-center text-sm font-medium" id="myTab" data-tabs-toggle="#myTabContent" role="tablist"  data-tabs-active-classes="text-primary-600 dark:text-primary-500 bg-gray-100 dark:bg-gray-700 border-primary-600" data-tabs-inactive-classes="text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700 dark:hover:border-gray-600 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">
            <li role="presentation">
              <button
                class="{{ $attributes->get('class', '') }} inline-block bg-gray-100 rounded-t-lg px-3 py-2 dark:hover:bg-gray-700 hover:bg-gray-100"
                id="buy-tab"
                data-tabs-target="#buy"
                type="button"
                role="tab"
                aria-controls="buy"
                aria-selected="true"
              >
                Buy
              </button>
            </li>
            <li class="{{ $attributes->get('class', '') }} mr-2" role="presentation">
              <button
                class="{{ $attributes->get('class', '') }} inline-block px-3 py-2 rounded-t-lg text-gray-500 dark:text-gray-400 sm:border-transparent dark:hover:bg-gray-700 hover:bg-gray-100"
                id="dashboard-tab"
                data-tabs-target="#dashboard"
                type="button"
                role="tab"
                aria-controls="dashboard"
                aria-selected="false"
              >
                Sell
              </button>
            </li>
          </ul>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mx-auto mb-4 md:mb-6 max-w-3xl" id="myTabContent">
        <div id="buy" role="tabpanel" aria-labelledby="buy-tab">
          <div class="{{ $attributes->get('class', '') }} block items-center space-y-4">
            <div class="{{ $attributes->get('class', '') }} mx-auto w-full max-w-md">
              <label for="from-input" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">From</label>
              <div class="{{ $attributes->get('class', '') }} flex">
                <span class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400">
                  <svg class="{{ $attributes->get('class', '') }} w-4 text-gray-900 dark:text-white" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_12679_7638)">
                      <path
                        d="M13.8102 12.4801C13.6532 12.0964 13.4933 11.7126 13.3189 11.3347C13.1648 10.9975 12.9671 10.8986 12.5979 10.9829C12.1298 11.0905 11.6705 11.2329 11.2025 11.3289C10.2983 11.5178 9.39129 11.5353 8.49879 11.2533C7.37953 10.9015 6.78356 10.0904 6.41726 9.04384H9.45234C9.69072 9.04384 9.8826 8.85196 9.8826 8.61358V7.65712C9.8826 7.41873 9.69072 7.22686 9.45234 7.22686H6.12654C6.12654 6.9594 6.12363 6.70357 6.12654 6.44774H9.45234C9.69072 6.44774 9.8826 6.25586 9.8826 6.01748V5.06102C9.8826 4.82263 9.69072 4.63076 9.45234 4.63076H6.54517C6.54517 4.61913 6.54517 4.6075 6.55099 4.60169C6.89985 3.80803 7.4086 3.17136 8.24296 2.83994C9.21395 2.4562 10.2053 2.46492 11.2054 2.67133C11.6792 2.76726 12.1444 2.91262 12.6182 3.02019C12.9642 3.09868 13.1648 2.99693 13.313 2.67424C13.4846 2.30212 13.6416 1.92419 13.7985 1.54335C13.9468 1.18577 13.8596 0.932843 13.5224 0.735156C13.4381 0.685734 13.3508 0.645033 13.2607 0.610147C11.8595 0.0636002 10.4146 -0.139901 8.92323 0.0984862C7.87375 0.267102 6.89403 0.62759 6.04805 1.28461C4.98112 2.11025 4.28921 3.20043 3.88803 4.48249L3.8386 4.63076H2.34432C2.10594 4.63076 1.91406 4.82263 1.91406 5.06102V6.01748C1.91406 6.25586 2.10594 6.44774 2.34432 6.44774H3.52172C3.52172 6.70938 3.52172 6.96231 3.52172 7.22686H2.34432C2.10594 7.22686 1.91406 7.41873 1.91406 7.65712V8.61358C1.91406 8.85196 2.10594 9.04384 2.34432 9.04384H3.76302C3.87058 9.3927 3.96071 9.74737 4.09734 10.0817C4.81541 11.8144 6.01898 13.047 7.83014 13.6343C9.32733 14.1197 10.8361 14.1052 12.345 13.6895C12.7374 13.5819 13.127 13.4424 13.4991 13.2709C13.8596 13.1051 13.9526 12.8261 13.8102 12.4801Z"
                        fill="currentColor"
                      />
                    </g>
                    <defs>
                      <clipPath id="clip0_12679_7638"><rect width="14" height="14" fill="white" transform="translate(0.894531)" /></clipPath>
                    </defs>
                  </svg>
                </span>
                <input
                  type="number"
                  id="from-input"
                  class="{{ $attributes->get('class', '') }} block w-full min-w-0 flex-1 rounded-none rounded-r-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="1000"
                />
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} mx-auto w-full max-w-md">
              <label for="to-form" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">To</label>
              <div class="{{ $attributes->get('class', '') }} flex">
                <span class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400">
                  <svg class="{{ $attributes->get('class', '') }} h-4 w-4" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_12679_13602)">
                      <path
                        d="M14.6837 8.69347C13.7487 12.4435 9.95037 14.7255 6.19989 13.7906C2.45117 12.8556 0.168953 9.05725 1.10411 5.30744C2.03861 1.55697 5.83677 -0.725463 9.58615 0.209474C13.3364 1.14441 15.6184 4.94322 14.6835 8.69347H14.6837Z"
                        fill="#F7931A"
                      />
                      <path
                        d="M10.9803 6.00311C11.1194 5.07167 10.4102 4.57095 9.44053 4.23692L9.75509 2.97517L8.98684 2.78377L8.68059 4.01227C8.47891 3.96195 8.27153 3.91448 8.06547 3.86745L8.37391 2.63086L7.60631 2.43945L7.29153 3.70077C7.12441 3.6627 6.96034 3.62508 6.80109 3.58548L6.80197 3.58155L5.74278 3.31708L5.53847 4.13739C5.53847 4.13739 6.10831 4.26798 6.09628 4.27608C6.40734 4.35373 6.46378 4.55958 6.45416 4.72277L6.09584 6.16017C6.11728 6.16564 6.14506 6.17352 6.17569 6.18577L6.09475 6.16564L5.59228 8.17923C5.55422 8.27373 5.45775 8.41548 5.24031 8.36167C5.24797 8.37283 4.68206 8.22233 4.68206 8.22233L4.30078 9.1017L5.30047 9.35086C5.48641 9.39745 5.66862 9.44623 5.84778 9.49217L5.52994 10.7686L6.29709 10.96L6.61209 9.69736C6.82144 9.75423 7.02487 9.80673 7.22394 9.85617L6.91025 11.1129L7.67828 11.3043L7.99612 10.0305C9.30578 10.2784 10.2908 10.1784 10.7049 8.99408C11.0389 8.04033 10.6885 7.49017 9.99944 7.1312C10.5012 7.01505 10.8792 6.68495 10.9801 6.00311H10.9803ZM9.22528 8.46383C8.98772 9.41758 7.38209 8.9022 6.86125 8.7727L7.283 7.08198C7.80362 7.21192 9.47291 7.46917 9.2255 8.46383H9.22528ZM9.46262 5.98933C9.24606 6.85689 7.9095 6.41611 7.47572 6.30805L7.85809 4.77461C8.29187 4.88267 9.68837 5.08436 9.46262 5.98933Z"
                        fill="white"
                      />
                    </g>
                    <defs>
                      <clipPath id="clip0_12679_13602"><rect width="14" height="14" fill="white" transform="translate(0.894531)" /></clipPath>
                    </defs>
                  </svg>
                </span>
                <input
                  type="number"
                  id="to-form"
                  class="{{ $attributes->get('class', '') }} block w-full min-w-0 flex-1 rounded-none rounded-r-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="1000"
                />
              </div>
              <p class="{{ $attributes->get('class', '') }} mt-4 text-gray-500 dark:text-gray-400">1 BTC ≈ 37,104.85 EUR</p>
            </div>
          </div>
        </div>
        <div id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
          <div class="{{ $attributes->get('class', '') }} block items-center space-y-4">
            <div class="{{ $attributes->get('class', '') }} mx-auto w-full max-w-md">
              <label for="from-form" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">From</label>
              <div class="{{ $attributes->get('class', '') }} flex">
                <span class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400">
                  <svg class="{{ $attributes->get('class', '') }} h-4 w-4" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_12679_13602)">
                      <path
                        d="M14.6837 8.69347C13.7487 12.4435 9.95037 14.7255 6.19989 13.7906C2.45117 12.8556 0.168953 9.05725 1.10411 5.30744C2.03861 1.55697 5.83677 -0.725463 9.58615 0.209474C13.3364 1.14441 15.6184 4.94322 14.6835 8.69347H14.6837Z"
                        fill="#F7931A"
                      />
                      <path
                        d="M10.9803 6.00311C11.1194 5.07167 10.4102 4.57095 9.44053 4.23692L9.75509 2.97517L8.98684 2.78377L8.68059 4.01227C8.47891 3.96195 8.27153 3.91448 8.06547 3.86745L8.37391 2.63086L7.60631 2.43945L7.29153 3.70077C7.12441 3.6627 6.96034 3.62508 6.80109 3.58548L6.80197 3.58155L5.74278 3.31708L5.53847 4.13739C5.53847 4.13739 6.10831 4.26798 6.09628 4.27608C6.40734 4.35373 6.46378 4.55958 6.45416 4.72277L6.09584 6.16017C6.11728 6.16564 6.14506 6.17352 6.17569 6.18577L6.09475 6.16564L5.59228 8.17923C5.55422 8.27373 5.45775 8.41548 5.24031 8.36167C5.24797 8.37283 4.68206 8.22233 4.68206 8.22233L4.30078 9.1017L5.30047 9.35086C5.48641 9.39745 5.66862 9.44623 5.84778 9.49217L5.52994 10.7686L6.29709 10.96L6.61209 9.69736C6.82144 9.75423 7.02487 9.80673 7.22394 9.85617L6.91025 11.1129L7.67828 11.3043L7.99612 10.0305C9.30578 10.2784 10.2908 10.1784 10.7049 8.99408C11.0389 8.04033 10.6885 7.49017 9.99944 7.1312C10.5012 7.01505 10.8792 6.68495 10.9801 6.00311H10.9803ZM9.22528 8.46383C8.98772 9.41758 7.38209 8.9022 6.86125 8.7727L7.283 7.08198C7.80362 7.21192 9.47291 7.46917 9.2255 8.46383H9.22528ZM9.46262 5.98933C9.24606 6.85689 7.9095 6.41611 7.47572 6.30805L7.85809 4.77461C8.29187 4.88267 9.68837 5.08436 9.46262 5.98933Z"
                        fill="white"
                      />
                    </g>
                    <defs>
                      <clipPath id="clip0_12679_13602"><rect width="14" height="14" fill="white" transform="translate(0.894531)" /></clipPath>
                    </defs>
                  </svg>
                </span>
                <input
                  type="number"
                  id="from-form"
                  class="{{ $attributes->get('class', '') }} block w-full min-w-0 flex-1 rounded-none rounded-r-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="1000"
                />
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} mx-auto w-full max-w-md">
              <label for="to-form" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">To</label>
              <div class="{{ $attributes->get('class', '') }} flex">
                <span class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-l-md border border-r-0 border-gray-200 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400">
                  <svg class="{{ $attributes->get('class', '') }} w-4 text-gray-900 dark:text-white" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_12679_7638)">
                      <path
                        d="M13.8102 12.4801C13.6532 12.0964 13.4933 11.7126 13.3189 11.3347C13.1648 10.9975 12.9671 10.8986 12.5979 10.9829C12.1298 11.0905 11.6705 11.2329 11.2025 11.3289C10.2983 11.5178 9.39129 11.5353 8.49879 11.2533C7.37953 10.9015 6.78356 10.0904 6.41726 9.04384H9.45234C9.69072 9.04384 9.8826 8.85196 9.8826 8.61358V7.65712C9.8826 7.41873 9.69072 7.22686 9.45234 7.22686H6.12654C6.12654 6.9594 6.12363 6.70357 6.12654 6.44774H9.45234C9.69072 6.44774 9.8826 6.25586 9.8826 6.01748V5.06102C9.8826 4.82263 9.69072 4.63076 9.45234 4.63076H6.54517C6.54517 4.61913 6.54517 4.6075 6.55099 4.60169C6.89985 3.80803 7.4086 3.17136 8.24296 2.83994C9.21395 2.4562 10.2053 2.46492 11.2054 2.67133C11.6792 2.76726 12.1444 2.91262 12.6182 3.02019C12.9642 3.09868 13.1648 2.99693 13.313 2.67424C13.4846 2.30212 13.6416 1.92419 13.7985 1.54335C13.9468 1.18577 13.8596 0.932843 13.5224 0.735156C13.4381 0.685734 13.3508 0.645033 13.2607 0.610147C11.8595 0.0636002 10.4146 -0.139901 8.92323 0.0984862C7.87375 0.267102 6.89403 0.62759 6.04805 1.28461C4.98112 2.11025 4.28921 3.20043 3.88803 4.48249L3.8386 4.63076H2.34432C2.10594 4.63076 1.91406 4.82263 1.91406 5.06102V6.01748C1.91406 6.25586 2.10594 6.44774 2.34432 6.44774H3.52172C3.52172 6.70938 3.52172 6.96231 3.52172 7.22686H2.34432C2.10594 7.22686 1.91406 7.41873 1.91406 7.65712V8.61358C1.91406 8.85196 2.10594 9.04384 2.34432 9.04384H3.76302C3.87058 9.3927 3.96071 9.74737 4.09734 10.0817C4.81541 11.8144 6.01898 13.047 7.83014 13.6343C9.32733 14.1197 10.8361 14.1052 12.345 13.6895C12.7374 13.5819 13.127 13.4424 13.4991 13.2709C13.8596 13.1051 13.9526 12.8261 13.8102 12.4801Z"
                        fill="currentColor"
                      />
                    </g>
                    <defs>
                      <clipPath id="clip0_12679_7638"><rect width="14" height="14" fill="white" transform="translate(0.894531)" /></clipPath>
                    </defs>
                  </svg>
                </span>
                <input
                  type="number"
                  id="to-form"
                  class="{{ $attributes->get('class', '') }} block w-full min-w-0 flex-1 rounded-none rounded-r-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="1000"
                />
              </div>
              <p class="{{ $attributes->get('class', '') }} mt-4 text-gray-500 dark:text-gray-400">1 BTC ≈ 37,104.85 EUR</p>
            </div>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} sm:flex items-center justify-center space-y-4 sm:space-y-0 sm:space-x-4 border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} inline-flex w-full sm:w-auto justify-center items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
              clip-rule="evenodd"
            />
          </svg>
          Preview Conversion
        </button>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          Exchange
        </button>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-start justify-between">
        <div>
          <button
            id="coinSelectButton"
            data-dropdown-toggle="coinSelectDropdown"
            data-dropdown-placement="bottom"
            class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-center font-medium text-gray-900 hover:text-gray-900 dark:text-white dark:hover:text-white"
            type="button"
          >
            Bitcoin
            <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <!-- Dropdown menu -->
          <div id="coinSelectDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-64 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="coinSelectButton">
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Bitcoin (BTC)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Ethereum (ETH)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Binance Coin (BNB)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Solana (SOL)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Dodge Coin (DODGE)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Avalanche (AVAX)</button>
              </li>
            </ul>
          </div>
          <h2 class="{{ $attributes->get('class', '') }} text-xl font-bold text-gray-900 dark:text-white">$66,756</h2>
        </div>
        <span class="{{ $attributes->get('class', '') }} mr-1.5 flex items-center font-semibold text-green-500 dark:text-green-400">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
          </svg>
          7%
        </span>
      </div>
      <div id="coin-chart"></div>
      <div class="{{ $attributes->get('class', '') }} grid grid-cols-1 items-center justify-between border-t border-gray-200 dark:border-gray-700">
        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between pt-5">
          <!-- Button -->
          <button
            id="totalDeliveriesButton"
            data-dropdown-toggle="totalDeliveriesDropdown"
            data-dropdown-placement="bottom"
            class="{{ $attributes->get('class', '') }} inline-flex items-center text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
            type="button"
          >
            Last 7 days
            <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <!-- Dropdown menu -->
          <div id="totalDeliveriesDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-32 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="totalTrucksButton">
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
          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold uppercase text-primary-700  hover:bg-gray-100 dark:border-gray-700 dark:text-primary-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            Full report
            <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1 h-4 w-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- Table Widget -->
  <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <div class="{{ $attributes->get('class', '') }} relative mb-4 md:mb-6">
      <div class="{{ $attributes->get('class', '') }} divide-y divide-gray-200 dark:divide-gray-700">
        <div class="{{ $attributes->get('class', '') }} flex pb-4 items-center justify-between">
          <h5 class="{{ $attributes->get('class', '') }} text-xl font-bold leading-none text-gray-900 dark:text-white">Open orders</h5>
          <div class="{{ $attributes->get('class', '') }} flex shrink-0 flex-col items-start space-y-3 sm:flex-row sm:items-center sm:space-x-3 sm:space-y-0 lg:justify-end">
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 24 24" fill="currentColor" class="{{ $attributes->get('class', '') }} me-2 h-4 w-4" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a.798.798 0 01-.517.608 7.45 7.45 0 00-.478.198.798.798 0 01-.796-.064l-.453-.324a1.875 1.875 0 00-2.416.2l-.243.243a1.875 1.875 0 00-.2 2.416l.324.453a.798.798 0 01.064.796 7.448 7.448 0 00-.198.478.798.798 0 01-.608.517l-.55.092a1.875 1.875 0 00-1.566 1.849v.344c0 .916.663 1.699 1.567 1.85l.549.091c.281.047.508.25.608.517.06.162.127.321.198.478a.798.798 0 01-.064.796l-.324.453a1.875 1.875 0 00.2 2.416l.243.243c.648.648 1.67.733 2.416.2l.453-.324a.798.798 0 01.796-.064c.157.071.316.137.478.198.267.1.47.327.517.608l.092.55c.15.903.932 1.566 1.849 1.566h.344c.916 0 1.699-.663 1.85-1.567l.091-.549a.798.798 0 01.517-.608 7.52 7.52 0 00.478-.198.798.798 0 01.796.064l.453.324a1.875 1.875 0 002.416-.2l.243-.243c.648-.648.733-1.67.2-2.416l-.324-.453a.798.798 0 01-.064-.796c.071-.157.137-.316.198-.478.1-.267.327-.47.608-.517l.55-.091a1.875 1.875 0 001.566-1.85v-.344c0-.916-.663-1.699-1.567-1.85l-.549-.091a.798.798 0 01-.608-.517 7.507 7.507 0 00-.198-.478.798.798 0 01.064-.796l.324-.453a1.875 1.875 0 00-.2-2.416l-.243-.243a1.875 1.875 0 00-2.416-.2l-.453.324a.798.798 0 01-.796.064 7.462 7.462 0 00-.478-.198.798.798 0 01-.517-.608l-.091-.55a1.875 1.875 0 00-1.85-1.566h-.344zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"
                />
              </svg>
              Table settings
            </button>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex flex-col items-stretch justify-between space-y-4 pt-4 md:flex-row md:items-center md:space-x-4 md:space-y-0">
          <div class="{{ $attributes->get('class', '') }} w-full">
            <form class="{{ $attributes->get('class', '') }} flex items-center">
              <label for="simple-search" class="{{ $attributes->get('class', '') }} sr-only">Search</label>
              <div class="{{ $attributes->get('class', '') }} relative w-full">
                <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 left-0 flex items-center ps-3">
                  <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                  </svg>
                </div>
                <input
                  type="text"
                  id="simple-search"
                  placeholder="Search for orders"
                  required=""
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 md:max-w-md"
                />
              </div>
            </form>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex w-full shrink-0 flex-col items-stretch space-y-2 sm:w-auto sm:flex-row sm:items-center sm:space-x-4 sm:space-y-0 md:justify-end">
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} flex w-full shrink-0 items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
            >
              <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
              </svg>              
              Add new order
            </button>
            <button
              id="filterDropdownButton"
              data-dropdown-toggle="filterDropdown"
              class="{{ $attributes->get('class', '') }} flex w-full shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto md:w-auto"
              type="button"
            >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M5.05 3C3.291 3 2.352 5.024 3.51 6.317l5.422 6.059v4.874c0 .472.227.917.613 1.2l3.069 2.25c1.01.742 2.454.036 2.454-1.2v-7.124l5.422-6.059C21.647 5.024 20.708 3 18.95 3H5.05Z"/>
            </svg>            
              Filter options
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <div id="filterDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 rounded-lg bg-white p-3 shadow-sm dark:bg-gray-700">
              <h6 class="{{ $attributes->get('class', '') }} mb-3 text-sm font-medium text-gray-900 dark:text-white">Status</h6>
              <ul class="{{ $attributes->get('class', '') }} space-y-2 text-sm" aria-labelledby="dropdownDefault">
                <li class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    id="completed"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-700 dark:focus:ring-primary-600"
                  />
                  <label for="completed" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">Completed</label>
                </li>
                <li class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    id="cancelled"
                    type="checkbox"
                    value=""
                    checked
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-700 dark:focus:ring-primary-600"
                  />
                  <label for="cancelled" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">Cancelled</label>
                </li>
                <li class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    id="pending"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-700 dark:focus:ring-primary-600"
                  />
                  <label for="pending" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">In progress</label>
                </li>
              </ul>
            </div>
            <button
              id="actionsDropdownButton"
              data-dropdown-toggle="actionsDropdown"
              class="{{ $attributes->get('class', '') }} flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto md:w-auto"
              type="button"
            >
              Actions
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <div id="actionsDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="actionsDropdownButton">
                <li>
                  <button type="button"                   
                  id="changeStatusButton"
                  data-modal-target="changeStatusModal"
                  data-modal-toggle="changeStatusModal"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd"/>
                    </svg>
                    Change status
                  </button>
                </li>
                <li>
                  <button
                  type="button"
                  id="deleteOrderButton"
                  data-modal-target="deleteOrderModal"
                  data-modal-toggle="deleteOrderModal"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                >
                  <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Delete all
                </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} overflow-x-auto relative">
      <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Transaction</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Date & Time
              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Amount

              <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                />
              </svg>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                Order Type
                <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                    d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                  />
                </svg>
              </div>
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">
              Status
            </th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">
              <span class="{{ $attributes->get('class', '') }} sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
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
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">Binance</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Oct 31 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">0.2746593BTC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">Buy</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-300">In progress</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-1-dropdown-button"
              type="button"
              data-dropdown-toggle="order-1-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-1-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-1-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-2"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-2" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">ExchangeBTC</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Oct 23 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">1.33467 BTC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">Buy</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-2-dropdown-button"
              type="button"
              data-dropdown-toggle="order-2-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-2-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-2-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-3"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-3" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">Binance</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Aug 18, 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">3.274635ETH</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">Sell</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Cancelled</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-3-dropdown-button"
              type="button"
              data-dropdown-toggle="order-3-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-3-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-3-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-4"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-4" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">ExchangeBTC</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Aug 18, 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">12.7344459 ETH</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">Buy</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-4-dropdown-button"
              type="button"
              data-dropdown-toggle="order-4-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-4-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-4-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-5"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-5" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">Binance</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Aug 18, 2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">40 DOGE</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">Sell</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Cancelled</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-5-dropdown-button"
              type="button"
              data-dropdown-toggle="order-5-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-5-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-5-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-6"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-6" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">ExchangeBTC</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Aug 15 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">0.0375693 BTC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">Buy</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-6-dropdown-button"
              type="button"
              data-dropdown-toggle="order-6-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-6-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-6-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-7"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-7" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">MetaverseExchange</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Jun 05 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">8.64 BTC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">Buy</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-7-dropdown-button"
              type="button"
              data-dropdown-toggle="order-7-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-7-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-7-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-8"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-8" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">Binance</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">May 27 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">2.0375 DAI</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-500 dark:text-green-400">Buy</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-8-dropdown-button"
              type="button"
              data-dropdown-toggle="order-8-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-8-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-8-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-9"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-9" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">BinanceExc</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">May 27 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">0.0375693 BTC</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">Sell</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-9-dropdown-button"
              type="button"
              data-dropdown-toggle="order-9-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-9-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-9-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} hover:bg-gray-100 dark:hover:bg-gray-800">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-10"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-10" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-normal text-gray-900 dark:text-white">Order sent to <span class="{{ $attributes->get('class', '') }} font-medium">Binance</span></td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">Apr 11 ,2025</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">0.8375 DAI</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-red-600 dark:text-red-500">Sell</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-green-600 dark:text-green-500">
              <span class="{{ $attributes->get('class', '') }} rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <button
              id="order-10-dropdown-button"
              type="button"
              data-dropdown-toggle="order-10-dropdown"
              class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
              </svg>
            </button>
            <div id="order-10-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="order-10-dropdown-button">
                <li>
                  <a href="/e-commerce/transaction/" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Details
                  </a>
                </li>
                <li>
                  <button
                    type="button"
                    id="deleteOrderButton"
                    data-modal-target="deleteOrderModal"
                    data-modal-toggle="deleteOrderModal"
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
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="{{ $attributes->get('class', '') }} relative overflow-hidden rounded-b-lg border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
      <nav class="{{ $attributes->get('class', '') }} flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0" aria-label="Table navigation">
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
              <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
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
              <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
              </svg>              
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<!-- Delete order modal -->
<div id="deleteOrderModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 me-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteOrderModal"
      >
      <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
      </svg>  
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-semibold text-gray-900 dark:text-white">Are you sure?</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">The following order will be permanently deleted and cannot be restored:</p>
      <ul role="list" class="{{ $attributes->get('class', '') }} mb-4 space-y-2 text-left font-medium text-gray-900 dark:text-white sm:mb-5">
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>Order to Binance - 0.2746593BTC</span>
        </li>
      </ul>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="deleteTransactionModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
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

<!-- Change status modal -->
<div id="changeStatusModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 me-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="changeStatusModal"
      >
      <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
      </svg>      
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-semibold text-gray-900 dark:text-white">Change status</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Select a new status and apply the update to these orders to keep them up to date.</p>
      <ul role="list" class="{{ $attributes->get('class', '') }} my-4 space-y-2 text-left font-medium text-gray-900 dark:text-white">
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>Order to Binance - 0.2746593BTC</span>
        </li>
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>Order to ExchangeBTC - 1.33467 BTC</span>
        </li>
      </ul>
      <form class="{{ $attributes->get('class', '') }} max-w-sm mx-auto mb-4">
        <label for="status" class="{{ $attributes->get('class', '') }} block mb-2 text-sm font-medium text-gray-900 dark:text-white">Order status</label>
        <select id="status" class="{{ $attributes->get('class', '') }} bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
          <option selected>In progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </form>
      
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="changeStatusModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Cancel
        </button>
        <button type="submit" class="{{ $attributes->get('class', '') }} px-3 py-2 text-white inline-flex items-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800">
          Apply 
        </button>
      </div>
    </div>
  </div>
</div>