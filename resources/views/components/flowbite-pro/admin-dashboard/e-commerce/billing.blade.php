@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Billing Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
group: e-commerce
page: billing
footer: true
---

<div class="{{ $attributes->get('class', '') }} px-4 pt-4">
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
          <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Billing</span>
        </div>
      </li>
    </ol>
  </nav>
  <h1 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-semibold text-gray-900 dark:text-white">Billing</h1>
  <div id="alert-additional-content" class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-50 p-4 text-primary-800 dark:bg-gray-800 dark:text-primary-400" role="alert">
    <div class="{{ $attributes->get('class', '') }} mb-1.5 flex items-center">
      <svg class="{{ $attributes->get('class', '') }} me-2 h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4c0-.6.4-1 1-1h8c.6 0 1 .4 1 1v6c0 .6-.4 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z"
          clip-rule="evenodd"
        />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Info</span>
      <h3 class="{{ $attributes->get('class', '') }} font-semibold">Welcome to your Billing preview!</h3>
    </div>
    <p class="{{ $attributes->get('class', '') }} text-sm">Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that you can see how spacing within an alert works with this kind of content.</p>
  </div>
</div>
<div class="{{ $attributes->get('class', '') }} gap-4 p-4 xl:grid xl:grid-cols-2">
  <!-- Column -->
  <div class="{{ $attributes->get('class', '') }} mb-4">
    <!-- Widget -->
    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center sm:mb-6">
        <a href="https://flowbite.com" class="{{ $attributes->get('class', '') }} me-3 flex items-center">
          <img src="/images/logo.svg" class="{{ $attributes->get('class', '') }} me-2 sm:h-8" alt="Flowbite Logo" />
          <span class="{{ $attributes->get('class', '') }} self-center whitespace-nowrap text-2xl font-semibold dark:text-white">Flowbite</span>
        </a>
        <span class="{{ $attributes->get('class', '') }} me-2 rounded-sm bg-primary-100 px-2.5 py-0.5 text-sm font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Essentials</span>
      </div>
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between border-b border-t border-gray-100 py-4 dark:border-gray-700 sm:mb-6">
        <time datetime="2025-07-05" class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Renewal Date: 05/07/2025</time>
        <div class="{{ $attributes->get('class', '') }} text-xl font-semibold dark:text-white">$9,99/month</div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mb-2 flex justify-between text-base font-medium text-gray-900 dark:text-white">
        <span>Orders</span>
        <span>27,965 <span class="{{ $attributes->get('class', '') }} font-normal text-gray-500 dark:text-gray-400">of</span> 35,000 <span class="{{ $attributes->get('class', '') }} font-normal text-gray-500 dark:text-gray-400">orders used</span></span>
      </div>
      <div class="{{ $attributes->get('class', '') }} mb-2 h-3 w-full rounded-sm bg-gray-200 dark:bg-gray-700">
        <div class="{{ $attributes->get('class', '') }} h-3 rounded-sm bg-primary-600" style="width: 45%"></div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center space-x-4 sm:mb-6">
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <div class="{{ $attributes->get('class', '') }} me-2 h-2.5 w-2.5 rounded-full bg-primary-700"></div>
          <div class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">26,478</span> Sales orders</div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <div class="{{ $attributes->get('class', '') }} me-2 h-2.5 w-2.5 rounded-full bg-primary-500"></div>
          <div class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">1,478</span> API orders</div>
        </div>
      </div>
      <h4 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white">Current plan benefits</h4>
      <div class="{{ $attributes->get('class', '') }} mb-4 space-y-3 sm:mb-6 sm:flex sm:space-x-24 sm:space-y-0 xl:block xl:space-x-0 xl:space-y-3 2xl:flex 2xl:space-x-24 2xl:space-y-0">
        <!-- List -->
        <ul role="list" class="{{ $attributes->get('class', '') }} space-y-3">
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">35,000</span> orders per month</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400">Multivariate Testing</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400">Comparative Reporting</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400">Unlimited integrations</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400">Team size: <span class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">1-3 developers</span></span>
          </li>
        </ul>
        <!-- List -->
        <ul role="list" class="{{ $attributes->get('class', '') }} space-y-3">
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400">Customer Journey Builder</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight text-gray-500 dark:text-gray-400">Phone support</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5 text-gray-400 line-through dark:text-gray-500">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight">Custom Templates</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5 text-gray-400 line-through dark:text-gray-500">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight">Exclusive discounts</span>
          </li>
          <li class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5 text-gray-400 line-through dark:text-gray-500">
            <!-- Icon -->
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} font-light leading-tight">Multivariate Testing</span>
          </li>
        </ul>
      </div>
      <div class="{{ $attributes->get('class', '') }} items-center space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          Cancel subscription
        </button>
        <button
          type="button"
          id="upgradeModalButton"
          data-modal-target="upgradeModal"
          data-modal-toggle="upgradeModal"
          class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M20.337 3.664c.213.212.354.486.404.782.294 1.711.657 5.195-.906 6.76-1.77 1.768-8.485 5.517-10.611 6.683a.987.987 0 0 1-1.176-.173l-.882-.88-.877-.884a.988.988 0 0 1-.173-1.177c1.165-2.126 4.913-8.841 6.682-10.611 1.562-1.563 5.046-1.198 6.757-.904.296.05.57.191.782.404ZM5.407 7.576l4-.341-2.69 4.48-2.857-.334a.996.996 0 0 1-.565-1.694l2.112-2.111Zm11.357 7.02-.34 4-2.111 2.113a.996.996 0 0 1-1.69-.565l-.422-2.807 4.563-2.74Zm.84-6.21a1.99 1.99 0 1 1-3.98 0 1.99 1.99 0 0 1 3.98 0Z"
              clip-rule="evenodd"
            />
          </svg>
          Upgrade to PRO
        </button>
      </div>
    </div>
    <!-- Widget -->
    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white">Payment details</h2>
      <div class="{{ $attributes->get('class', '') }} mb-4 divide-y divide-gray-200 dark:divide-gray-700 sm:mb-6">
        <!-- Item 1 -->
        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between gap-8 pb-4">
          <div class="{{ $attributes->get('class', '') }} flex flex-grow flex-col">
            <div class="{{ $attributes->get('class', '') }} text-lg font-medium text-gray-900 dark:text-white">Recurring payment</div>
            <div class="{{ $attributes->get('class', '') }} text-base font-normal text-gray-500 dark:text-gray-400">Automatically charge your account at regular intervals Flowbite services.</div>
          </div>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
            <input id="recurring-payment" type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer">
            <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <!-- Item 2 -->
        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between gap-8 pt-4">
          <div class="{{ $attributes->get('class', '') }} flex flex-grow flex-col">
            <div class="{{ $attributes->get('class', '') }} text-lg font-medium text-gray-900 dark:text-white">Quick purchase</div>
            <div class="{{ $attributes->get('class', '') }} text-base font-normal text-gray-500 dark:text-gray-400">You will be asked to verify your account for all Flowbite purchases.</div>
          </div>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
            <input id="quick-purchase" type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" checked>
            <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
          </label>
        </div>
      </div>
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm10 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7V5h8v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1Z"
            clip-rule="evenodd"
          />
        </svg>
        Save changes
      </button>
    </div>
    <!-- Widget -->
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white">Billing history</h2>
      <div class="{{ $attributes->get('class', '') }} mb-4 overflow-x-auto sm:mb-6 max-w-auto relative">
        <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
          <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Invoice ID</th>
              <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  Date
                  <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                  </svg>
                </div>
              </th>
              <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  Amount
                  <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                  </svg>
                </div>
              </th>
              <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  Status
                  <svg class="{{ $attributes->get('class', '') }} ml-1 inline-block h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                  </svg>
                </div>
              </th>
              <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3">
                <span class="{{ $attributes->get('class', '') }} sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846325</a></td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 May 2025</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$9,99</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
              </td>
              <td class="{{ $attributes->get('class', '') }} px-4 py-3">
                <button
                  id="invoice-1-dropdown-button"
                  type="button"
                  data-dropdown-toggle="invoice-1-dropdown"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="invoice-1-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-1-dropdown-button">
                    <li>
                      <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                          <path
                            fill-rule="evenodd"
                            d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Edit
                      </a>
                    </li>
                    <li>
                      <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        View
                      </a>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846328</a></td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 Apr 2025</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$9,99</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
              </td>
              <td class="{{ $attributes->get('class', '') }} px-4 py-3">
                <button
                  id="invoice-2-dropdown-button"
                  type="button"
                  data-dropdown-toggle="invoice-2-dropdown"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="invoice-2-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-2-dropdown-button">
                    <li>
                      <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                          <path
                            fill-rule="evenodd"
                            d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Edit
                      </a>
                    </li>
                    <li>
                      <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        View
                      </a>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846329</a></td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 Mar 2025</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$9,99</td>
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
                  <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="invoice-3-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-3-dropdown-button">
                    <li>
                      <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                          <path
                            fill-rule="evenodd"
                            d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Edit
                      </a>
                    </li>
                    <li>
                      <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        View
                      </a>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846330</a></td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 Feb 2025</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">9,99</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
              </td>
              <td class="{{ $attributes->get('class', '') }} px-4 py-3">
                <button
                  id="invoice-4-dropdown-button"
                  type="button"
                  data-dropdown-toggle="invoice-4-dropdown"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="invoice-4-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-4-dropdown-button">
                    <li>
                      <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                          <path
                            fill-rule="evenodd"
                            d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Edit
                      </a>
                    </li>
                    <li>
                      <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        View
                      </a>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="../invoice" class="{{ $attributes->get('class', '') }} hover:underline">#1846331</a></td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 Jan 2025</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$9,99</td>
              <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
                <span class="{{ $attributes->get('class', '') }} mr-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
              </td>
              <td class="{{ $attributes->get('class', '') }} px-4 py-3">
                <button
                  id="invoice-5-dropdown-button"
                  type="button"
                  data-dropdown-toggle="invoice-5-dropdown"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="invoice-5-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-5-dropdown-button">
                    <li>
                      <a href="../create-invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                          <path
                            fill-rule="evenodd"
                            d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Edit
                      </a>
                    </li>
                    <li>
                      <a href="../invoice" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        View
                      </a>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} w-full rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        View all
      </button>
    </div>
  </div>
  <!-- Column -->
  <div>
    <!-- Widget -->
    <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white">Billing details</h2>
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} mb-4 grid gap-4 sm:mb-6 sm:grid-cols-2">
          <div>
            <label for="name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Your name*</label>
            <input
              type="text"
              name="name"
              id="name"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. Bonnie Green"
              required=""
            />
          </div>
          <div>
            <label for="email" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email*</label>
            <input
              type="email"
              name="email"
              id="email"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="name@example.com"
              required=""
            />
          </div>
          <div>
            <label for="company-name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Company name*</label>
            <input
              type="text"
              name="company-name"
              id="company-name"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Flowbite LLC"
              required=""
            />
          </div>
          <div>
            <label for="vat" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">VAT number*</label>
            <input
              type="number"
              name="vat"
              id="vat"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="DE42313253"
              required=""
            />
          </div>
          <div>
            <label for="country" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Country*</label>
            <input
              type="text"
              name="country"
              id="country"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="United States"
              required=""
            />
          </div>
          <div>
            <label for="address" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Address*</label>
            <input
              type="text"
              name="address"
              id="address"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Enter your address"
              required=""
            />
          </div>
          <div>
            <label for="street-number" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Street number</label>
            <input
              type="number"
              name="street-number"
              id="street-number"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Enter your street number"
              required=""
            />
          </div>
          <div>
            <label for="city" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Town/City*</label>
            <input
              type="text"
              name="city"
              id="city"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. Sacramento"
              required=""
            />
          </div>
          <div>
            <label for="state" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">State/County*</label>
            <input
              type="text"
              name="state"
              id="state"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. California"
              required=""
            />
          </div>
          <div>
            <label for="zip" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Zip/Postal code</label>
            <input
              type="number"
              name="zip"
              id="zip"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. 123456"
              required=""
            />
          </div>
          <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
            <label for="additional-content" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Additional info</label>
            <textarea
              id="additional-content"
              rows="8"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Receipt Info (optional)"
            ></textarea>
          </div>
        </div>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm10 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7V5h8v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
          Save changes
        </button>
      </form>
    </div>
    <!-- Widget -->
    <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white">Payment methods</h2>
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} mb-4 space-y-4 sm:mb-6 sm:space-y-6">
          <!-- Radio Input -->
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700">
            <div>
              <div class="{{ $attributes->get('class', '') }} mb-4 flex">
                <input
                  checked
                  id="bordered-radio-1"
                  type="radio"
                  aria-describedby="helper-radio-text-card"
                  value=""
                  name="bordered-radio"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <div class="{{ $attributes->get('class', '') }} ms-2 text-sm leading-none">
                  <label for="bordered-radio-1" class="{{ $attributes->get('class', '') }} w-full text-base font-medium leading-none text-gray-900 dark:text-gray-300 mb-1.5">Visa ending in 7658</label>
                  <p id="helper-radio-text-card" class="{{ $attributes->get('class', '') }} mt-1 text-sm font-normal text-gray-500 dark:text-gray-300">Expiry 10/2025</p>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} divide-x divide-gray-200 text-sm font-medium text-gray-500 dark:divide-gray-600 dark:text-gray-400">
                <a class="{{ $attributes->get('class', '') }} pe-1 hover:underline" href="#">Edit</a>
                <a class="{{ $attributes->get('class', '') }} ps-2 hover:underline" href="#">Delete</a>
              </div>
            </div>
            <img class="{{ $attributes->get('class', '') }} h-8 w-auto dark:hidden" src="/images/e-commerce/brand-logos/visa.svg" alt="" />
            <img class="{{ $attributes->get('class', '') }} hidden h-8 w-auto dark:flex" src="/images/e-commerce/brand-logos/visa-dark.svg" alt="" />
          </div>
          <!-- Radio Input -->
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700">
            <div>
              <div class="{{ $attributes->get('class', '') }} mb-4 flex">
                <input
                  id="bordered-radio-2"
                  type="radio"
                  aria-describedby="helper-radio-text-card-2"
                  value=""
                  name="bordered-radio"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <div class="{{ $attributes->get('class', '') }} ms-2 text-sm leading-none">
                  <label for="bordered-radio-2" class="{{ $attributes->get('class', '') }} w-full text-base font-medium leading-none text-gray-900 dark:text-gray-300 mb-1.5">Mastercard ending in 8429</label>
                  <p id="helper-radio-text-card-2" class="{{ $attributes->get('class', '') }} mt-1 text-sm font-normal text-gray-500 dark:text-gray-300">Expiry 10/2025</p>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} divide-x divide-gray-200 text-sm font-medium text-gray-500 dark:divide-gray-600 dark:text-gray-400">
                <a class="{{ $attributes->get('class', '') }} pe-1 hover:underline" href="#">Edit</a>
                <a class="{{ $attributes->get('class', '') }} ps-2 hover:underline" href="#">Delete</a>
              </div>
            </div>
            <img class="{{ $attributes->get('class', '') }} h-7 w-auto dark:hidden" src="/images/e-commerce/brand-logos/mastercard.svg" alt="" />
            <img class="{{ $attributes->get('class', '') }} hidden h-7 w-auto dark:flex" src="/images/e-commerce/brand-logos/mastercard-dark.svg" alt="" />
          </div>
          <!-- Radio Input -->
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700">
            <div>
              <div class="{{ $attributes->get('class', '') }} mb-4 flex">
                <input
                  id="bordered-radio-3"
                  type="radio"
                  aria-describedby="helper-radio-text-card-3"
                  value=""
                  name="bordered-radio"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <div class="{{ $attributes->get('class', '') }} ms-2 text-sm leading-none">
                  <label for="bordered-radio-3" class="{{ $attributes->get('class', '') }} w-full text-base font-medium leading-none text-gray-900 dark:text-gray-300 mb-1.5">Paypal account</label>
                  <p id="helper-radio-text-card-3" class="{{ $attributes->get('class', '') }} mt-1 text-sm font-normal text-gray-500 dark:text-gray-300">name@example.com</p>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} divide-x divide-gray-200 text-sm font-medium text-gray-500 dark:divide-gray-600 dark:text-gray-400">
                <a class="{{ $attributes->get('class', '') }} pe-1 hover:underline" href="#">Edit</a>
                <a class="{{ $attributes->get('class', '') }} ps-2 hover:underline" href="#">Delete</a>
              </div>
            </div>
            <img class="{{ $attributes->get('class', '') }} h-8 w-auto dark:hidden" src="/images/e-commerce/brand-logos/paypal.svg" alt="" />
            <img class="{{ $attributes->get('class', '') }} hidden h-8 w-auto dark:flex" src="/images/e-commerce/brand-logos/paypal-dark.svg" alt="" />
          </div>
        </div>
        <button
          id="add_paymentModalButton"
          data-modal-target="add_paymentModal"
          data-modal-toggle="add_paymentModal"
          type="button"
          class="{{ $attributes->get('class', '') }} mb-4 inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:mb-6"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-2 me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
          </svg>
          Add new payment method
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm10 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7V5h8v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
          Save changes
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Upgrade to PRO Modal -->
<div id="upgradeModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-modal w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0 md:h-full">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-4xl p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-8">
      <!-- Close Button -->
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute end-2 top-2 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="upgradeModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>

        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <!-- Modal body -->
      <div class="{{ $attributes->get('class', '') }} lg:grid lg:grid-cols-2 lg:gap-12">
        <div class="{{ $attributes->get('class', '') }} text-gray-500 sm:text-lg">
          <h2 class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Upgrade to Flowbite PRO</h2>
          <p class="{{ $attributes->get('class', '') }} mb-4 text-base dark:text-gray-400">Here at Flowbite we focus on markets where technology and passion can unlock long-term value.</p>
          <div class="{{ $attributes->get('class', '') }} hidden gap-8 border-t border-gray-200 py-4 dark:border-gray-700 sm:grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-1">
            <div class="{{ $attributes->get('class', '') }} lg:flex">
              <div class="{{ $attributes->get('class', '') }} mr-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-sm bg-white shadow-sm dark:bg-gray-700 sm:mb-4 lg:mb-0">
                <svg class="{{ $attributes->get('class', '') }} h-7 w-7 text-gray-900 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path
                    fill-rule="evenodd"
                    d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div>
                <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-bold text-gray-900 dark:text-white">Industry analysis</h3>
                <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 lg:text-base">Benchmark your company against the industry to inform your growth strategy.</p>
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} lg:flex">
              <div class="{{ $attributes->get('class', '') }} mr-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-sm bg-white shadow-sm dark:bg-gray-700 sm:mb-4 lg:mb-0">
                <svg class="{{ $attributes->get('class', '') }} h-7 w-7 text-gray-900 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path
                    fill-rule="evenodd"
                    d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div>
                <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-bold text-gray-900 dark:text-white">Company analysis</h3>
                <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 lg:text-base">Uncover competitor strategies and deep dive into any company’s digital footprint.</p>
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} hidden md:block lg:flex">
              <div class="{{ $attributes->get('class', '') }} mr-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-sm bg-white shadow-sm dark:bg-gray-700 sm:mb-4 lg:mb-0">
                <svg class="{{ $attributes->get('class', '') }} h-7 w-7 text-gray-900 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-bold text-gray-900 dark:text-white">App analysis</h3>
                <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 lg:text-base">Analyze industry and discover audience interests to learn which apps are trending.</p>
              </div>
            </div>
          </div>
        </div>
        <!-- Inputs & Button -->
        <div class="{{ $attributes->get('class', '') }} flex flex-col">
          <div class="{{ $attributes->get('class', '') }} mb-4 flex items-baseline border-b border-t border-gray-200 py-4 dark:border-gray-700 lg:mb-6 lg:border-0">
            <span class="{{ $attributes->get('class', '') }} mr-2 text-4xl font-extrabold text-gray-900 dark:text-white md:text-5xl">$9,99</span>
            <span class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">per month, inc. VAT</span>
          </div>
          <form action="#">
            <div class="{{ $attributes->get('class', '') }} mb-4">
              <label for="name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Your Name</label>
              <input
                type="text"
                id="name"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Enter your name"
                required
              />
            </div>
            <div class="{{ $attributes->get('class', '') }} mb-4">
              <label for="email" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Your email</label>
              <input
                type="email"
                id="email"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="name@flowbite.com"
                required
              />
            </div>
            <div class="{{ $attributes->get('class', '') }} mb-4 md:mb-6">
              <label for="countries" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Country</label>
              <select
                id="countries"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              >
                <option selected>Choose a country</option>
                <option value="US">United States</option>
                <option value="CA">Canada</option>
                <option value="FR">France</option>
                <option value="DE">Germany</option>
              </select>
            </div>
            <div>
              <button
                type="submit"
                class="{{ $attributes->get('class', '') }} focus:ring-bue-200 mb-4 flex w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:ring-4 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-900"
              >
                Continue to payment
              </button>
              <div class="{{ $attributes->get('class', '') }} flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                <svg class="{{ $attributes->get('class', '') }} me-2 h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M8 10V7a4 4 0 1 1 8 0v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7c0-1.1.9-2 2-2h1Zm2-3a2 2 0 1 1 4 0v3h-4V7Zm2 6c.6 0 1 .4 1 1v3a1 1 0 1 1-2 0v-3c0-.6.4-1 1-1Z" clip-rule="evenodd" />
                </svg>
                Secure payment with Flowbite.
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add payment method Modal -->
<div id="add_paymentModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-modal w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0 md:h-full">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-xl p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-700 sm:mb-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Add new card</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="add_paymentModal"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>

          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} mb-4 grid gap-4 sm:grid-cols-2">
          <div>
            <label for="name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Full name (as displayed on card) *</label>
            <input
              type="text"
              name="name"
              id="name"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. Bonnie Green"
              required=""
            />
          </div>
          <div>
            <label for="number" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Card number *</label>
            <input
              type="number"
              name="number"
              id="number"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="xxxx-xxxx-xxxx-xxxx"
              required=""
            />
          </div>
          <div>
            <label for="cvc" class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">
              CVC *
              <button data-popover-target="popover-cvc-description" type="button" class="{{ $attributes->get('class', '') }} ms-1 text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="{{ $attributes->get('class', '') }} sr-only">Show information</span>
              </button>
            </label>
            <input
              type="number"
              name="cvc"
              id="cvc"
              class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="xxx"
              required=""
            />
            <div
              id="popover-cvc-description"
              role="tooltip"
              class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              <h3 class="{{ $attributes->get('class', '') }} mb-2 font-semibold">What is CVC?</h3>
              <p class="{{ $attributes->get('class', '') }} mb-2">It is a three- or four-digit code that provides an additional layer of authentication during online transactions.</p>
              <p>It is also known as CVV (Card Verification Value) or CSC (Card Security Code).</p>
              <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
          <div>
            <label for="expiry" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Expiry date</label>
            <div class="{{ $attributes->get('class', '') }} relative">
              <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                datepicker
                type="text"
                id="expiry"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Select date"
              />
            </div>
          </div>
        </div>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
          </svg>
          Add new card
        </button>
      </form>
    </div>
  </div>
</div>
