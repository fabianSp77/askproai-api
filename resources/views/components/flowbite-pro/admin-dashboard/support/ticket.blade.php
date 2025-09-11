@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Support Ticket Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: support
page: ticket
---

<div class="{{ $attributes->get('class', '') }} grid grid-cols-12">
  <div class="{{ $attributes->get('class', '') }} col-span-full flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="{{ $attributes->get('class', '') }} flex items-center sm:mb-0 sm:space-x-4">
      <div class="{{ $attributes->get('class', '') }} flex items-center">
        <a
          href="#"
          data-tooltip-target="tooltip-prev-ticket"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4 4 4" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Prev ticket</span>
        </a>
        <div id="tooltip-prev-ticket" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Prev ticket
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a
          href="#"
          data-tooltip-target="tooltip-next-ticket"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Next ticket</span>
        </a>
        <div id="tooltip-next-ticket" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Next ticket
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
      <h1 class="{{ $attributes->get('class', '') }} ms:ms-0 ms-2 font-semibold text-gray-900 dark:text-white sm:text-xl">Ticket #1846325</h1>
      <span class="{{ $attributes->get('class', '') }} ms-4 hidden items-center rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 sm:ms-0 sm:inline-flex">
        <svg class="{{ $attributes->get('class', '') }} me-1.5 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M5.5 3a1 1 0 0 0 0 2H7v2.3c0 .7.2 1.3.6 1.8L9 11.9l.1.1v.1L7.5 15a3 3 0 0 0-.6 1.8V19H5.5a1 1 0 1 0 0 2h13a1 1 0 1 0 0-2H17v-2.3a3 3 0 0 0-.6-1.8l-1.6-2.8v-.2l1.6-2.8a3 3 0 0 0 .6-1.8V5h1.5a1 1 0 1 0 0-2h-13Z"
            clip-rule="evenodd"
          />
        </svg>
        Pending
      </span>
    </div>
    <!-- Buttons -->
    <div>
      <a href="#" data-tooltip-target="tooltip-filter" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path d="M5 3a2 2 0 0 0-1.5 3.3l5.4 6v5c0 .4.3.9.6 1.1l3.1 2.3c1 .7 2.5 0 2.5-1.2v-7.1l5.4-6C21.6 5 20.7 3 19 3H5Z" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Filter</span>
      </a>
      <div id="tooltip-filter" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
        Filter
        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
      </div>
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
    </div>
  </div>

  <div class="{{ $attributes->get('class', '') }} col-span-full p-4 xl:p-8">
    <div class="{{ $attributes->get('class', '') }} mb-4 items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800 sm:flex">
      <div>
        <h2 class="{{ $attributes->get('class', '') }} mb-1.5 text-xl font-medium leading-none text-gray-900 dark:text-white">I need help with my purchase</h2>
        <span class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Via email</span>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center border-t border-gray-200 pt-4 text-gray-500 dark:border-gray-800 dark:text-gray-400 sm:mt-0 sm:border-0 sm:pt-0">
        <p class="{{ $attributes->get('class', '') }} me-4 text-sm text-gray-500 dark:text-gray-400">Mon,<time datetime="2025-07-31 15:20"> 3:20 PM</time> (2 hrs ago)</p>
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
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">Dear Flowbite team,</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">I hope this message finds you well. I wanted to inform you that I'm currently experiencing a problem while trying to make a payment for the Flowbite PRO.</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">Could you please advise on the next steps or provide assistance in resolving this issue?</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">Thank you for your prompt attention to this matter.</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      Best regards,<br />
      Joseph McFall, CEO & Founder Digital Things LLC
    </p>
    <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center gap-4 border-t border-gray-200 pt-4 dark:border-gray-800 sm:mt-6 sm:pt-6">
      <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full" src="/images/users/avatar-2.png" alt="Avatar" />
      <div class="{{ $attributes->get('class', '') }} font-semibold dark:text-white">
        <div>Roberta Casas</div>
        <button
          id="responseTicketDetailsDropdownButton"
          type="button"
          data-dropdown-toggle="responseTicketDetailsDropdown"
          type="button"
          class="{{ $attributes->get('class', '') }} inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
        >
          to Joseph, me
          <svg class="{{ $attributes->get('class', '') }} ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M18.4 10.3A2 2 0 0 0 17 7H7a2 2 0 0 0-1.5 3.3l4.9 5.9a2 2 0 0 0 3 0l5-6Z" clip-rule="evenodd" />
          </svg>
        </button>
        <div id="responseTicketDetailsDropdown" class="{{ $attributes->get('class', '') }} z-50 hidden w-[360px] rounded-sm bg-white p-4 shadow-sm dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} space-y-2 text-sm font-normal" aria-labelledby="responseTicketDetailsDropdownButton">
            <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
              <div class="{{ $attributes->get('class', '') }} w-16">From:</div>
              <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">Roberta (name@example.com)</div>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
              <div class="{{ $attributes->get('class', '') }} w-16">To:</div>
              <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">name@company.com</div>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
              <div class="{{ $attributes->get('class', '') }} w-16">Date:</div>
              <time class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400" datetime="2025-02-06 11:30">Feb 6, 2025, 11:30AM </time>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
              <div class="{{ $attributes->get('class', '') }} w-16">Subject:</div>
              <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">RE: I need help with my purchase</div>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center text-gray-500 dark:text-gray-400">
              <div class="{{ $attributes->get('class', '') }} w-16">Security:</div>
              <div class="{{ $attributes->get('class', '') }} ms-1 text-gray-900 dark:text-gray-400">Standard encryption (TLS) <a class="{{ $attributes->get('class', '') }} font-medium text-gray-900 underline hover:no-underline dark:text-white" href="#">Learn more</a></div>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">Hello Joseph,</p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      I hope this message finds you well. My name is Robert Brown, and I'm writing to assist you with the recent purchase issue you've encountered. First and foremost, I want to express my sincere apologies for any inconvenience this may
      have caused you.
    </p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      I understand how frustrating it can be to experience difficulties with a purchase, and I want to assure you that we are committed to resolving this matter promptly and to your satisfaction.
    </p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      Could you please provide me with some more details about the issue you're facing? This will help us better understand the situation and find the best possible solution for you. Feel free to include any relevant order numbers or
      screenshots that might be helpful.
    </p>
    <p class="{{ $attributes->get('class', '') }} mt-6 text-gray-500 dark:text-gray-400">
      Cheers,<br />
      Roberta Casas, CTO Flowbite LLC
    </p>
    <form class="{{ $attributes->get('class', '') }} mt-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 sm:mt-6">
      <div class="{{ $attributes->get('class', '') }} w-full">
        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between px-4 py-3">
          <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
            <button
              id="messageDropdownButton"
              data-dropdown-toggle="messageDropdown"
              class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
              type="button"
            >
              Reply
              <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <!-- Dropdown menu -->
            <div id="messageDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-44 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
              <ul class="{{ $attributes->get('class', '') }} space-y-1 text-sm font-medium" aria-labelledby="messageDropdownButton">
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" href="#">Reply</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" href="#">Forward</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" href="#">Edit subject</button>
                </li>
                <li>
                  <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" href="#">Pop out reply</button>
                </li>
              </ul>
            </div>
            <p class="{{ $attributes->get('class', '') }} hidden text-sm text-gray-500 dark:text-gray-400 sm:flex">Joseph McFallen (name@example.com)</p>
          </div>
          <button type="button" data-tooltip-target="tooltip-fullscreen" class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:ms-auto">
            <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 19 19">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1h5m0 0v5m0-5-5 5M1.979 6V1H7m0 16.042H1.979V12M18 12v5.042h-5M13 12l5 5M2 1l5 5m0 6-5 5" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Full screen</span>
          </button>
          <div id="tooltip-fullscreen" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Show full screen
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} rounded-b-lg bg-white px-4 py-2 dark:bg-gray-800">
          <label for="editor" class="{{ $attributes->get('class', '') }} sr-only">Publish post</label>
          <textarea id="editor" rows="8" class="{{ $attributes->get('class', '') }} block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400" placeholder="" required></textarea>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex flex-wrap items-center divide-gray-200 rounded-b-lg border-t border-gray-200 px-4 py-3 dark:divide-gray-700 dark:border-gray-700 sm:divide-x sm:rtl:divide-x-reverse">
        <div class="{{ $attributes->get('class', '') }} flex flex-wrap items-center space-x-0.5 sm:pe-4 rtl:space-x-reverse">
          <a href="#" data-tooltip-target="tooltip-undo" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9h13a5 5 0 0 1 0 10H7M3 9l4-4M3 9l4 4" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Undo</span>
          </a>
          <div id="tooltip-undo" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Undo
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a href="#" data-tooltip-target="tooltip-redo" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 9H8a5 5 0 0 0 0 10h9m4-10-4-4m4 4-4 4" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Redo</span>
          </a>
          <div id="tooltip-redo" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Redo
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <button
            id="filterDropdownButton"
            data-dropdown-toggle="filterDropdown"
            data-dropdown-ignore-click-outside-class="{{ $attributes->get('class', '') }} datepicker"
            class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
            type="button"
          >
            Arial (Sans-serif)
            <svg class="{{ $attributes->get('class', '') }} -me-0.5 ms-1.5 h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <!-- Dropdown menu -->
          <div id="filterDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-72 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
            <ul class="{{ $attributes->get('class', '') }} space-y-1 text-sm font-medium" aria-labelledby="filterDropdownButton">
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Arial (Sans-serif)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Times New Roman (Serif)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Helvetica (Sans-serif)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Verdana (Sans-serif)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Georgia (Sans-serif)</button>
              </li>
              <li>
                <button type="button" class="{{ $attributes->get('class', '') }} flex w-full rounded-sm px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Roboto (Sans-serif)</button>
              </li>
            </ul>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-text-size"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6.2V5h11v1.2M8 5v14m-3 0h6m2-6.8V11h8v1.2M17 11v8m-1.5 0h3" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Text size</span>
          </a>
          <div id="tooltip-text-size" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Text size
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-font-bold"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5h4.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0-7H6m2 7h6.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0 0H6" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Bold</span>
          </a>
          <div id="tooltip-font-bold" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Bold
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-font-italic"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.9 19 15 5M6 19h6.3m-.6-14H18" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Italic</span>
          </a>
          <div
            id="tooltip-font-italic"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Italic
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-font-underline"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M6 19h12M8 5v9a4 4 0 0 0 8 0V5M6 5h4m4 0h4" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Underline</span>
          </a>
          <div
            id="tooltip-font-underline"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Underline
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-text-slash"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6.2V5h12v1.2M7 19h6m.2-14-1.7 6.5M9.6 19l1-4M5 5l6.5 6.5M19 19l-7.5-7.5" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Text slash</span>
          </a>
          <div id="tooltip-text-slash" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Text slash
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-paragraph"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v7m0 7v-7m4-7v14m3-14H8.5C6.5 5 5 6.6 5 8.5v0c0 2 1.6 3.5 3.5 3.5H12" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Paragraph</span>
          </a>
          <div id="tooltip-paragraph" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Paragraph
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} hidden flex-wrap items-center space-x-1 sm:ps-4 md:flex rtl:space-x-reverse">
          <a href="#" data-tooltip-target="tooltip-quote" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M6 6a2 2 0 0 0-2 2v3c0 1.1.9 2 2 2h3a3 3 0 0 1-3 3H5a1 1 0 1 0 0 2h1a5 5 0 0 0 5-5V8a2 2 0 0 0-2-2H6Zm9 0a2 2 0 0 0-2 2v3c0 1.1.9 2 2 2h3a3 3 0 0 1-3 3h-1a1 1 0 1 0 0 2h1a5 5 0 0 0 5-5V8a2 2 0 0 0-2-2h-3Z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Quote</span>
          </a>
          <div id="tooltip-quote" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Quote
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-text-center"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M6 10h12M8 14h8M6 18h12" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Text center</span>
          </a>
          <div
            id="tooltip-text-center"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Text center
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-ordered-list"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6h8m-8 6h8m-8 6h8M4 16a2 2 0 1 1 3.3 1.5L4 20h5M4 5l2-1v6m-2 0h4" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Ordered list</span>
          </a>
          <div
            id="tooltip-ordered-list"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Ordered list
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a href="#" data-tooltip-target="tooltip-list" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M9 8h10M9 12h10M9 16h10M5 8h0m0 4h0m0 4h0" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">List</span>
          </a>
          <div id="tooltip-list" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            List
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-text-indent"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h12M6 18h12m-5-8h5m-5 4h5M6 9v6l3.5-3L6 9Z" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Text indent</span>
          </a>
          <div
            id="tooltip-text-indent"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Text indent
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
          <a
            href="#"
            data-tooltip-target="tooltip-text-outdent"
            class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h12M6 18h12m-5-8h5m-5 4h5M9.5 9v6L6 12l3.5-3Z" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Text outdent</span>
          </a>
          <div
            id="tooltip-text-outdent"
            role="tooltip"
            class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Text outdent
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} items-center space-x-0.5 rounded-b-lg border-t border-gray-200 px-4 py-3 dark:border-gray-700 sm:flex">
        <button
          id="submitDropdownButton"
          data-dropdown-toggle="submitDropdown"
          type="button"
          class="{{ $attributes->get('class', '') }} mb-4 me-2 inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:mb-0 sm:w-auto"
        >
          Submit as <span class="{{ $attributes->get('class', '') }} ms-1 font-semibold">Open</span>
          <svg class="{{ $attributes->get('class', '') }} -me-1 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <div id="submitDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-44 rounded-sm bg-white shadow-sm dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="submitDropdownButton">
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Submit as Open</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Submit as Pending</button>
            </li>
            <li>
              <button type="button" class="{{ $attributes->get('class', '') }} block rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Submit as Solved</button>
            </li>
          </ul>
        </div>
        <a
          href="#"
          data-tooltip-target="tooltip-attach-file"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8v8a5 5 0 1 0 10 0V6.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 0 4 0V8" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Attach file</span>
        </a>
        <div id="tooltip-attach-file" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Attach file
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a href="#" data-tooltip-target="tooltip-location" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 2a8 8 0 0 1 6.6 12.6l-.1.1-.6.7-5.1 6.2a1 1 0 0 1-1.6 0L6 15.3l-.3-.4-.2-.2v-.2A8 8 0 0 1 11.8 2Zm3 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" clip-rule="evenodd" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Location</span>
        </a>
        <div id="tooltip-location" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Location
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a href="#" data-tooltip-target="tooltip-emoji" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm5.5 1a.5.5 0 0 0-1 0 5 5 0 0 0 1.6 3.4 5.5 5.5 0 0 0 7.8 0 5 5 0 0 0 1.6-3.4.5.5 0 0 0-1 0h-.2l-1 .3a18.9 18.9 0 0 1-7.8-.4ZM9 8a1 1 0 0 0 0 2 1 1 0 1 0 0-2Zm6 0a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Emoji</span>
        </a>
        <div id="tooltip-emoji" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Emoji
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a
          href="#"
          data-tooltip-target="tooltip-insert-photo"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.4 9.6a1 1 0 0 0-1.8 0l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .9-1.4l-2-4a1 1 0 0 0-1.7-.2l-.5.7-1.3-2.5ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Insert photo</span>
        </a>
        <div id="tooltip-insert-photo" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Insert photo
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a
          href="#"
          data-tooltip-target="tooltip-confidential-mode"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M10 5a2 2 0 0 0-2 2v3h2.4a7.5 7.5 0 0 0 0 11H5a2 2 0 0 1-2-2v-7c0-1.1.9-2 2-2h1V7a4 4 0 1 1 8 0v1.2c-.7 0-1.3.3-2 .6V7a2 2 0 0 0-2-2Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M10 15.5a5.5 5.5 0 1 1 11 0 5.5 5.5 0 0 1-11 0Zm6.5-1.5a1 1 0 1 0-2 0v1.5c0 .3.1.5.3.7l1 1a1 1 0 0 0 1.4-1.4l-.7-.7V14Z" clip-rule="evenodd" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Toggle confidential mode</span>
        </a>
        <div
          id="tooltip-confidential-mode"
          role="tooltip"
          class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Toggle confidential mode
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
        <a
          href="#"
          data-tooltip-target="tooltip-signature"
          class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M15.5 3.3a1 1 0 0 0-1.4 0l-2 2h.1l6.5 6.5 2-1.9c.4-.4.4-1 0-1.4l-5.2-5.2ZM7 8.3l3.9-1.5 6.3 6.3-1.5 3.9a1 1 0 0 1-.6.6l-9.5 3.3a1 1 0 0 1-1-.1l6.5-6.5A1 1 0 0 0 9.7 13l-6.5 6.4a1 1 0 0 1-.1-1L6.4 9c.1-.3.3-.5.6-.6Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Insert signature</span>
        </a>
        <div id="tooltip-signature" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Insert signature
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Delete Ticket modal -->
<div id="deleteTicketModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 me-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteTicketModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>

        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-1 text-lg font-semibold text-gray-900 dark:text-white">Are you sure?</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 font-light text-gray-500 dark:text-gray-400">You are about to delete this ticket, this cannot be undone.</p>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="deleteTicketModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
        >
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
