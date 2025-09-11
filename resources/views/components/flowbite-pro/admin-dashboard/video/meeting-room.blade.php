@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Video Meeting - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard-no-sidebar
group: video
page: meeting-room
---

<div class="{{ $attributes->get('class', '') }} relative flex h-[calc(100vh-7rem)] w-full items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
  <div class="{{ $attributes->get('class', '') }} max-h-auto relative w-full max-w-6xl rounded-lg shadow-sm">
    <img src="/images/video-call/chat-main-image.png" alt="" class="{{ $attributes->get('class', '') }} max-h-auto w-full max-w-7xl rounded-lg shadow-sm" />
    <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-3 left-4 text-sm font-medium text-white hover:underline">Michael Gough</button>
  </div>
  <div class="{{ $attributes->get('class', '') }} absolute bottom-4 end-4 flex flex-row gap-4 sm:justify-end w-[calc(100%-2rem)] overflow-x-scroll">
    <div class="{{ $attributes->get('class', '') }} min-w-40 w-40 lg:w-60 min-h-25 h-25 lg:h-40 rounded-lg shadow-sm relative shrink-0">
        <img src="/images/video-call/chat-image-1.png" class="{{ $attributes->get('class', '') }} max-h-auto w-full rounded-lg shadow-sm" alt="">
        <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-1 left-2 text-sm font-medium text-white hover:underline">Jese Leos</button>
        <button type="button" data-tooltip-target="tooltip-talking-1" class="{{ $attributes->get('class', '') }} absolute bottom-1 end-2 w-6 h-6 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center hover:text-white text-gray-300 focus:ring-2 focus:outline-none focus:ring-gray-300">
            <span class="{{ $attributes->get('class', '') }} sr-only">Talking</span>
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4 rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M6 10h12M8 14h8M6 18h12"/>
            </svg>
        </button>
        <div id="tooltip-talking-1" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
          Currently talking
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} min-w-40 w-40 lg:w-60 min-h-25 h-25 lg:h-40 rounded-lg shadow-sm relative shrink-0">
        <img src="/images/video-call/chat-image-2.png" class="{{ $attributes->get('class', '') }} max-h-auto w-full rounded-lg shadow-sm" alt="">
        <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-1 left-2 text-sm font-medium text-white hover:underline">Bonnie Green</button>
        <button type="button" data-tooltip-target="tooltip-talking-2" class="{{ $attributes->get('class', '') }} absolute bottom-1 end-2 w-6 h-6 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center hover:text-white text-gray-300 focus:ring-2 focus:outline-none focus:ring-gray-300">
            <span class="{{ $attributes->get('class', '') }} sr-only">Unmuted</span>
            <svg class="{{ $attributes->get('class', '') }} w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M5 8a1 1 0 0 1 1 1v3a4.006 4.006 0 0 0 4 4h4a4.006 4.006 0 0 0 4-4V9a1 1 0 1 1 2 0v3.001A6.006 6.006 0 0 1 14.001 18H13v2h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2H9.999A6.006 6.006 0 0 1 4 12.001V9a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                <path d="M7 6a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4V6Z"/>
            </svg>
        </button>
        <div id="tooltip-talking-2" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
          Voice available
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
    <div class="{{ $attributes->get('class', '') }} min-w-40 w-40 lg:w-60 min-h-25 h-25 lg:h-40 rounded-lg shadow-sm relative shrink-0">
        <img src="/images/video-call/chat-image-3.png" class="{{ $attributes->get('class', '') }} max-h-auto w-full rounded-lg shadow-sm" alt="">
        <button type="button" class="{{ $attributes->get('class', '') }} absolute bottom-1 left-2 text-sm font-medium text-white hover:underline">Thomas Lean</button>
        <button type="button" data-tooltip-target="tooltip-talking-3" class="{{ $attributes->get('class', '') }} absolute bottom-1 end-2 w-6 h-6 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center hover:text-white text-gray-300 focus:ring-2 focus:outline-none focus:ring-gray-300">
            <span class="{{ $attributes->get('class', '') }} sr-only">Unmuted</span>
            <svg class="{{ $attributes->get('class', '') }} w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M5 8a1 1 0 0 1 1 1v3a4.006 4.006 0 0 0 4 4h4a4.006 4.006 0 0 0 4-4V9a1 1 0 1 1 2 0v3.001A6.006 6.006 0 0 1 14.001 18H13v2h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2H9.999A6.006 6.006 0 0 1 4 12.001V9a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                <path d="M7 6a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4V6Z"/>
            </svg>
        </button>
        <div id="tooltip-talking-3" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
          Voice available
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
  </div>
</div>

<div class="{{ $attributes->get('class', '') }} fixed bottom-0 left-0 z-50 grid h-16 w-full grid-cols-1 bg-gray-50 px-4 dark:bg-gray-900 md:grid-cols-3">
  <div class="{{ $attributes->get('class', '') }} me-auto hidden items-center justify-center text-gray-500 dark:text-gray-400 md:flex">
    <svg class="{{ $attributes->get('class', '') }} me-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
      <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z" />
    </svg>
    <span class="{{ $attributes->get('class', '') }} text-sm">Running Time: 02:46:34</span>
  </div>
  <div class="{{ $attributes->get('class', '') }} mx-auto flex items-center justify-center">
    <button
      data-tooltip-target="tooltip-microphone"
      type="button"
      class="{{ $attributes->get('class', '') }} group me-4 rounded-full bg-gray-100 p-2.5 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 19">
        <path d="M15 5a1 1 0 0 0-1 1v3a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V6a1 1 0 0 0-2 0v3a6.006 6.006 0 0 0 6 6h1v2H5a1 1 0 0 0 0 2h6a1 1 0 0 0 0-2H9v-2h1a6.006 6.006 0 0 0 6-6V6a1 1 0 0 0-1-1Z" />
        <path d="M9 0H7a3 3 0 0 0-3 3v5a3 3 0 0 0 3 3h2a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3Z" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Mute microphone</span>
    </button>
    <div id="tooltip-microphone" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Mute microphone
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      data-tooltip-target="tooltip-volume"
      type="button"
      class="{{ $attributes->get('class', '') }} group me-4 rounded-full bg-gray-100 p-2.5 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
        <path
          d="M10.836.357a1.978 1.978 0 0 0-2.138.3L3.63 5H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h1.63l5.07 4.344a1.985 1.985 0 0 0 2.142.299A1.98 1.98 0 0 0 12 15.826V2.174A1.98 1.98 0 0 0 10.836.357Zm2.728 4.695a1.001 1.001 0 0 0-.29 1.385 4.887 4.887 0 0 1 0 5.126 1 1 0 0 0 1.674 1.095A6.645 6.645 0 0 0 16 9a6.65 6.65 0 0 0-1.052-3.658 1 1 0 0 0-1.384-.29Zm4.441-2.904a1 1 0 0 0-1.664 1.11A10.429 10.429 0 0 1 18 9a10.465 10.465 0 0 1-1.614 5.675 1 1 0 1 0 1.674 1.095A12.325 12.325 0 0 0 20 9a12.457 12.457 0 0 0-1.995-6.852Z"
        />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Adjust volume</span>
    </button>
    <div id="tooltip-volume" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Adjust volume
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      data-tooltip-target="tooltip-camera"
      type="button"
      class="{{ $attributes->get('class', '') }} group me-4 rounded-full bg-gray-100 p-2.5 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 14">
        <path
          d="M11 0H2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm8.585 1.189a.994.994 0 0 0-.9-.138l-2.965.983a1 1 0 0 0-.685.949v8a1 1 0 0 0 .675.946l2.965 1.02a1.013 1.013 0 0 0 1.032-.242A1 1 0 0 0 20 12V2a1 1 0 0 0-.415-.811Z"
        />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Hide camera</span>
    </button>
    <div id="tooltip-camera" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Hide camera
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      data-tooltip-target="tooltip-settings"
      data-drawer-target="settings-drawer"
      data-drawer-show="settings-drawer"
      data-drawer-placement="right"
      type="button"
      class="{{ $attributes->get('class', '') }} group me-4 rounded-full bg-gray-100 p-2.5 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 12.25V1m0 11.25a2.25 2.25 0 0 0 0 4.5m0-4.5a2.25 2.25 0 0 1 0 4.5M4 19v-2.25m6-13.5V1m0 2.25a2.25 2.25 0 0 0 0 4.5m0-4.5a2.25 2.25 0 0 1 0 4.5M10 19V7.75m6 4.5V1m0 11.25a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5ZM16 19v-2"
        />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Video settings</span>
    </button>
    <div id="tooltip-settings" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Video settings
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      data-tooltip-target="tooltip-end-call"
      type="button"
      class="{{ $attributes->get('class', '') }} group me-4 rounded-full bg-red-600 p-2.5 hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200 dark:bg-red-600 dark:hover:bg-red-600 dark:focus:ring-red-800 md:me-0"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
        />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">End call</span>
    </button>
    <div id="tooltip-end-call" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      End call
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      id="moreOptionsDropdownButton"
      data-dropdown-toggle="moreOptionsDropdown"
      type="button"
      class="{{ $attributes->get('class', '') }} group rounded-full bg-gray-100 p-2.5 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-800 md:hidden"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
        <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Show options</span>
    </button>
    <div id="moreOptionsDropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
      <ul class="{{ $attributes->get('class', '') }} py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="moreOptionsDropdownButton">
        <li>
            <button type="button" data-drawer-target="drawer-participants" data-drawer-show="drawer-participants" data-drawer-placement="right" class="{{ $attributes->get('class', '') }} flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} w-4 h-4 text-gray-500 dark:text-gray-400 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z" clip-rule="evenodd"/>
              </svg>
              Show participants
            </button>
        </li>
        <li>
          <button type="button" data-drawer-target="drawer-messages" data-drawer-show="drawer-messages" data-drawer-placement="right" class="{{ $attributes->get('class', '') }} flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4 text-gray-500 dark:text-gray-400 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M4 3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h1v2a1 1 0 0 0 1.707.707L9.414 13H15a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H4Z" clip-rule="evenodd"/>
              <path fill-rule="evenodd" d="M8.023 17.215c.033-.03.066-.062.098-.094L10.243 15H15a3 3 0 0 0 3-3V8h2a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1v2a1 1 0 0 1-1.707.707L14.586 18H9a1 1 0 0 1-.977-.785Z" clip-rule="evenodd"/>
            </svg>
            Send messages
          </button>
        </li>
        <li>
          <button type="button" data-drawer-target="feedback-drawer" data-drawer-show="feedback-drawer" data-drawer-placement="right" class="{{ $attributes->get('class', '') }} flex items-center w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4 text-gray-500 dark:text-gray-400 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2ZM7.99 9a1 1 0 0 1 1-1H9a1 1 0 0 1 0 2h-.01a1 1 0 0 1-1-1ZM14 9a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H15a1 1 0 0 1-1-1Zm-5.506 7.216A5.5 5.5 0 0 1 6.6 13h10.81a5.5 5.5 0 0 1-8.916 3.216Z" clip-rule="evenodd"/>
            </svg>
            Share feedback</button>
        </li>
        <li>
          <button type="button" data-modal-toggle="details-modal" class="{{ $attributes->get('class', '') }} flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4 text-gray-500 dark:text-gray-400 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z" clip-rule="evenodd"/>
            </svg>
            Meeting details</button>
        </li>
      </ul>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} ms-auto hidden items-center justify-center md:flex">
    <button data-tooltip-target="tooltip-participants" data-drawer-show="drawer-participants" type="button" class="{{ $attributes->get('class', '') }} group me-1 rounded-full p-2.5 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:hover:bg-gray-600 dark:focus:ring-gray-600">
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
        <path
          d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"
        />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Show participants</span>
    </button>
    <div id="tooltip-participants" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Show participants
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      data-tooltip-target="tooltip-messages"
      data-drawer-init="drawer-messages"
      data-drawer-show="drawer-messages"
      type="button"
      class="{{ $attributes->get('class', '') }} group me-1 rounded-full p-2.5 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:hover:bg-gray-600 dark:focus:ring-gray-600"
    >
      <svg class="{{ $attributes->get('class', '') }} h-5 w-5 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M4 3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h1v2a1 1 0 0 0 1.707.707L9.414 13H15a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H4Z" clip-rule="evenodd" />
        <path fill-rule="evenodd" d="M8.023 17.215c.033-.03.066-.062.098-.094L10.243 15H15a3 3 0 0 0 3-3V8h2a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1v2a1 1 0 0 1-1.707.707L14.586 18H9a1 1 0 0 1-.977-.785Z" clip-rule="evenodd" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Messages</span>
    </button>
    <div id="tooltip-messages" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Messages
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button data-tooltip-target="tooltip-feedback" data-drawer-show="feedback-drawer" type="button" class="{{ $attributes->get('class', '') }} group me-1 rounded-full p-2.5 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:hover:bg-gray-600 dark:focus:ring-gray-600">
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM13.5 6a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm-7 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm3.5 9.5A5.5 5.5 0 0 1 4.6 11h10.81A5.5 5.5 0 0 1 10 15.5Z" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Share feedback</span>
    </button>
    <div id="tooltip-feedback" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Share feedback
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
    <button
      data-tooltip-target="tooltip-information"
      data-modal-target="details-modal"
      data-modal-toggle="details-modal"
      type="button"
      class="{{ $attributes->get('class', '') }} group rounded-full p-2.5 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:hover:bg-gray-600 dark:focus:ring-gray-600"
    >
      <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
      </svg>
      <span class="{{ $attributes->get('class', '') }} sr-only">Show information</span>
    </button>
    <div id="tooltip-information" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
      Meeting details
      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
    </div>
  </div>
</div>

<!-- Meeting details modal -->
<div id="details-modal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-lg p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-between p-4 md:p-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Meeting details</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
          data-modal-toggle="details-modal"
        >
          <svg class="{{ $attributes->get('class', '') }} h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <div class="{{ $attributes->get('class', '') }} px-4 pb-4 md:px-5 md:pb-5">
        <label for="link-url" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Share the following link for others to join:</label>
        <div class="{{ $attributes->get('class', '') }} relative mb-4">
          <input
            id="link-url"
            type="text"
            class="{{ $attributes->get('class', '') }} col-span-6 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            value="https://meet.flowbite.com/ght-uzuq-akq"
            disabled
            readonly
          />
          <button
            data-copy-to-clipboard-target="link-url"
            data-tooltip-target="tooltip-link-url"
            class="{{ $attributes->get('class', '') }} absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
          >
            <span id="default-icon-link-url">
              <svg class="{{ $attributes->get('class', '') }} h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                <path
                  d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                />
              </svg>
            </span>
            <span id="success-icon-link-url" class="{{ $attributes->get('class', '') }} hidden">
              <svg class="{{ $attributes->get('class', '') }} h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
              </svg>
            </span>
          </button>
          <div id="tooltip-link-url" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            <span id="default-tooltip-message-link-url">Copy to clipboard</span>
            <span id="success-tooltip-message-link-url" class="{{ $attributes->get('class', '') }} hidden">Copied!</span>
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
        <button
          type="button"
          data-modal-hide="details-modal"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</div>

<!-- messages drawer -->
<div id="drawer-messages" class="{{ $attributes->get('class', '') }} fixed right-0 top-0 z-40 h-[calc(100vh-4rem)] w-80 translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800 flex justify-between flex-col" tabindex="-1" aria-labelledby="drawer-messages-label">
    <div>
        <h5 id="drawer-messages-label" class="{{ $attributes->get('class', '') }} mb-4 inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">Messages</h5>
        <button
            type="button"
            data-drawer-hide="drawer-messages"
            aria-controls="drawer-messages"
            class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        >
            <svg class="{{ $attributes->get('class', '') }} h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
        </button>
    
        <div class="{{ $attributes->get('class', '') }} overflow-scroll h-[calc(100vh-13rem)] mb-2">
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Bonnie Green</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> That's awesome. I think our users will really appreciate the improvements.</p>
                </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Bonnie Green</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> That's awesome. I think our users will really appreciate the improvements.</p>
                </div>
            </div>
        
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/jese-leos.png" alt="Jese image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Jese Leos</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:48</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> I will immediately send you a calendar where you will see the times when I would be available for a call.</p>
                </div>
            </div>
        
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/neil-sims.png" alt="Neil image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Neil Sims</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:52</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> We've been doing this meeting for well over an hour. Let's close this and get to working.</p>
                </div>
            </div>
        
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Bonnie Green</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> I agree with Neil. Thanks all for joining and have a good one. Until next time.</p>
                </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Bonnie Green</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> That's awesome. I think our users will really appreciate the improvements.</p>
                </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Bonnie Green</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> That's awesome. I think our users will really appreciate the improvements.</p>
                </div>
            </div>
        
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/jese-leos.png" alt="Jese image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Jese Leos</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:48</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> I will immediately send you a calendar where you will see the times when I would be available for a call.</p>
                </div>
            </div>
        
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5 mb-4">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/neil-sims.png" alt="Neil image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Neil Sims</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:52</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> We've been doing this meeting for well over an hour. Let's close this and get to working.</p>
                </div>
            </div>
        
            <div class="{{ $attributes->get('class', '') }} flex items-start gap-2.5">
                <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2 rtl:space-x-reverse">
                        <span class="{{ $attributes->get('class', '') }} text-sm font-semibold text-gray-900 dark:text-white">Bonnie Green</span>
                        <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
                    </div>
                    <p class="{{ $attributes->get('class', '') }} text-sm font-normal py-2 text-gray-900 dark:text-white"> I agree with Neil. Thanks all for joining and have a good one. Until next time.</p>
                    <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">Seen</span>
                </div>
            </div>
        </div>
    </div>

    <form>
        <label for="chat" class="{{ $attributes->get('class', '') }} sr-only">Your message</label>
        <div class="{{ $attributes->get('class', '') }} flex items-center px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700">
            <textarea id="chat" rows="1" class="{{ $attributes->get('class', '') }} block p-2.5 w-full me-2 text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Your message..." required></textarea>
                <button type="submit" class="{{ $attributes->get('class', '') }} inline-flex justify-center p-2 text-primary-600 rounded-full cursor-pointer hover:bg-primary-100 dark:text-primary-500 dark:hover:bg-gray-600">
                <svg class="{{ $attributes->get('class', '') }} w-5 h-5 rotate-90 rtl:-rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                    <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z"/>
                </svg>
                <span class="{{ $attributes->get('class', '') }} sr-only">Send message</span>
            </button>
        </div>
    </form>

  </div>
</div>

<!-- participants drawer -->
<div id="drawer-participants" class="{{ $attributes->get('class', '') }} fixed right-0 top-0 z-40 h-[calc(100vh-4rem)] w-80 translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800" tabindex="-1" aria-labelledby="drawer-participants-label">
    <div>
        <h5 id="drawer-participants-label" class="{{ $attributes->get('class', '') }} mb-4 inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">Participants</h5>
        <button
            type="button"
            data-drawer-hide="drawer-participants"
            aria-controls="drawer-participants"
            class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        >
            <svg class="{{ $attributes->get('class', '') }} h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
        </button>

        <label for="search-input-participants" class="{{ $attributes->get('class', '') }} sr-only">Your Email</label>
        <div class="{{ $attributes->get('class', '') }} relative mb-6">
        <div class="{{ $attributes->get('class', '') }} absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
            </svg>
        </div>
        <input type="text" id="search-input-participants" class="{{ $attributes->get('class', '') }} bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search for people">
        </div>
    
        <div class="{{ $attributes->get('class', '') }} overflow-y-scroll mb-2 space-y-4">        
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between">
                <div class="{{ $attributes->get('class', '') }} flex items-center gap-2.5">
                    <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
                    <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                        <span class="{{ $attributes->get('class', '') }} text-base font-medium text-gray-900 dark:text-white">Bonnie Green</span>
                        <p class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400"> Meeting host</p>
                    </div>
                </div>

                <div class="{{ $attributes->get('class', '') }} flex items-center gap-1">
                    <button type="button" data-tooltip-target="tooltip-status-1" class="{{ $attributes->get('class', '') }} shrink-0 w-5 h-5 rounded-full bg-primary-700 hover:bg-primary-800 flex items-center justify-center text-white focus:ring-2 focus:outline-none focus:ring-primary-300">
                        <span class="{{ $attributes->get('class', '') }} sr-only">Talking</span>
                        <svg class="{{ $attributes->get('class', '') }} w-3 h-3 rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M6 10h12M8 14h8M6 18h12"/>
                        </svg>
                    </button>
                    <div id="tooltip-status-1" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                        Currently talking
                        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                        id="participant-dropdown-button-1"
                        type="button"
                        data-dropdown-toggle="participant-dropdown-1"
                        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        >
                        <svg class="{{ $attributes->get('class', '') }} h-5 w-5 rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                        </svg>
                    </button>
                    <div id="participant-dropdown-1" class="{{ $attributes->get('class', '') }} z-10 hidden w-48 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="participant-dropdown-1-button">
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M5 8a4 4 0 1 1 8 0 4 4 0 0 1-8 0Zm-2 9a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1Zm13-6a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-4Z" clip-rule="evenodd"/>
                              </svg>
                              Remove from call
                            </a>
                          </li>
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5.707 4.293a1 1 0 0 0-1.414 1.414l14 14a1 1 0 0 0 1.414-1.414l-.004-.005C21.57 16.498 22 13.938 22 12a9.972 9.972 0 0 0-2.929-7.071 1 1 0 1 0-1.414 1.414A7.972 7.972 0 0 1 20 12c0 1.752-.403 3.636-1.712 4.873l-1.433-1.433C17.616 14.37 18 13.107 18 12c0-1.678-.69-3.197-1.8-4.285a1 1 0 1 0-1.4 1.428A3.985 3.985 0 0 1 16 12c0 .606-.195 1.335-.59 1.996L13 11.586V6.135c0-1.696-1.978-2.622-3.28-1.536L7.698 6.284l-1.99-1.991ZM4 8h.586L13 16.414v1.451c0 1.696-1.978 2.622-3.28 1.536L5.638 16H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2Z"/>
                                </svg>
                                Mute participant
                            </a>
                          </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between">
                <div class="{{ $attributes->get('class', '') }} flex items-center gap-2.5">
                    <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/michael-gough.png" alt="Michael image">
                    <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                        <span class="{{ $attributes->get('class', '') }} text-base font-medium text-gray-900 dark:text-white">Michael Gough</span>
                        <p class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400"> Participant</p>
                    </div>
                </div>

                <div class="{{ $attributes->get('class', '') }} flex items-center gap-1">
                    <button type="button" data-tooltip-target="tooltip-status-2" class="{{ $attributes->get('class', '') }} shrink-0 w-5 h-5 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white flex items-center justify-center text-gray-900 focus:ring-2 focus:outline-none focus:ring-gray-300 dark:focus:ring-gray-600">
                        <span class="{{ $attributes->get('class', '') }} sr-only">Not talking</span>
                        <svg class="{{ $attributes->get('class', '') }} w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M5 8a1 1 0 0 1 1 1v3a4.006 4.006 0 0 0 4 4h4a4.006 4.006 0 0 0 4-4V9a1 1 0 1 1 2 0v3.001A6.006 6.006 0 0 1 14.001 18H13v2h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2H9.999A6.006 6.006 0 0 1 4 12.001V9a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                            <path d="M7 6a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4V6Z"/>
                        </svg>
                    </button>
                    <div id="tooltip-status-2" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                        Voice available
                        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                        id="participant-dropdown-button-2"
                        type="button"
                        data-dropdown-toggle="participant-dropdown-2"
                        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        >
                        <svg class="{{ $attributes->get('class', '') }} h-5 w-5 rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                        </svg>
                    </button>
                    <div id="participant-dropdown-2" class="{{ $attributes->get('class', '') }} z-10 hidden w-48 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="participant-dropdown-2-button">
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M5 8a4 4 0 1 1 8 0 4 4 0 0 1-8 0Zm-2 9a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1Zm13-6a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-4Z" clip-rule="evenodd"/>
                              </svg>
                              Remove from call
                            </a>
                          </li>
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5.707 4.293a1 1 0 0 0-1.414 1.414l14 14a1 1 0 0 0 1.414-1.414l-.004-.005C21.57 16.498 22 13.938 22 12a9.972 9.972 0 0 0-2.929-7.071 1 1 0 1 0-1.414 1.414A7.972 7.972 0 0 1 20 12c0 1.752-.403 3.636-1.712 4.873l-1.433-1.433C17.616 14.37 18 13.107 18 12c0-1.678-.69-3.197-1.8-4.285a1 1 0 1 0-1.4 1.428A3.985 3.985 0 0 1 16 12c0 .606-.195 1.335-.59 1.996L13 11.586V6.135c0-1.696-1.978-2.622-3.28-1.536L7.698 6.284l-1.99-1.991ZM4 8h.586L13 16.414v1.451c0 1.696-1.978 2.622-3.28 1.536L5.638 16H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2Z"/>
                                </svg>
                                Mute participant
                            </a>
                          </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center justify-between">
                <div class="{{ $attributes->get('class', '') }} flex items-center gap-2.5">
                    <img class="{{ $attributes->get('class', '') }} w-8 h-8 rounded-full" src="/images/users/thomas-lean.png" alt="Thomas image">
                    <div class="{{ $attributes->get('class', '') }} flex flex-col w-full max-w-[320px] leading-1.5">
                        <span class="{{ $attributes->get('class', '') }} text-base font-medium text-gray-900 dark:text-white">Thomas Lean</span>
                        <p class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400"> Participant</p>
                    </div>
                </div>

                <div class="{{ $attributes->get('class', '') }} flex items-center gap-1">
                    <button type="button" data-tooltip-target="tooltip-status-3" class="{{ $attributes->get('class', '') }} shrink-0 w-5 h-5 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white flex items-center justify-center text-gray-900 focus:ring-2 focus:outline-none focus:ring-gray-300 dark:focus:ring-gray-600">
                        <span class="{{ $attributes->get('class', '') }} sr-only">Not talking</span>
                        <svg class="{{ $attributes->get('class', '') }} w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M5 8a1 1 0 0 1 1 1v3a4.006 4.006 0 0 0 4 4h4a4.006 4.006 0 0 0 4-4V9a1 1 0 1 1 2 0v3.001A6.006 6.006 0 0 1 14.001 18H13v2h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2H9.999A6.006 6.006 0 0 1 4 12.001V9a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                            <path d="M7 6a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4V6Z"/>
                        </svg>
                    </button>
                    <div id="tooltip-status-3" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                        Voice available
                        <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                    </div>
                    <button
                        id="participant-dropdown-button-3"
                        type="button"
                        data-dropdown-toggle="participant-dropdown-3"
                        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        >
                        <svg class="{{ $attributes->get('class', '') }} h-5 w-5 rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h0m6 0h0m6 0h0" />
                        </svg>
                    </button>
                    <div id="participant-dropdown-3" class="{{ $attributes->get('class', '') }} z-10 hidden w-48 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="participant-dropdown-3-button">
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                              <svg class="{{ $attributes->get('class', '') }} me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M5 8a4 4 0 1 1 8 0 4 4 0 0 1-8 0Zm-2 9a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1Zm13-6a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-4Z" clip-rule="evenodd"/>
                              </svg>
                              Remove from call
                            </a>
                          </li>
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5.707 4.293a1 1 0 0 0-1.414 1.414l14 14a1 1 0 0 0 1.414-1.414l-.004-.005C21.57 16.498 22 13.938 22 12a9.972 9.972 0 0 0-2.929-7.071 1 1 0 1 0-1.414 1.414A7.972 7.972 0 0 1 20 12c0 1.752-.403 3.636-1.712 4.873l-1.433-1.433C17.616 14.37 18 13.107 18 12c0-1.678-.69-3.197-1.8-4.285a1 1 0 1 0-1.4 1.428A3.985 3.985 0 0 1 16 12c0 .606-.195 1.335-.59 1.996L13 11.586V6.135c0-1.696-1.978-2.622-3.28-1.536L7.698 6.284l-1.99-1.991ZM4 8h.586L13 16.414v1.451c0 1.696-1.978 2.622-3.28 1.536L5.638 16H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2Z"/>
                                </svg>
                                Mute participant
                            </a>
                          </li>
                        </ul>
                    </div>
                </div>
            </div>
            <button type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto">
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M9 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Zm8-1a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
                </svg>
                Invite others
            </button>
        </div>
    </div>

</div>

<!-- settings drawer -->
<div id="settings-drawer" class="{{ $attributes->get('class', '') }} fixed right-0 top-0 z-40 h-[calc(100vh-4rem)] w-80 translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800" tabindex="-1" aria-labelledby="settings-drawer-label">
    <div>
        <h5 id="settings-drawer-label" class="{{ $attributes->get('class', '') }} mb-4 inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">Host controls</h5>
        <button
            type="button"
            data-drawer-hide="settings-drawer"
            aria-controls="settings-drawer"
            class="{{ $attributes->get('class', '') }} absolute end-2.5 top-2.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        >
            <svg class="{{ $attributes->get('class', '') }} h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
        </button>

        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between mb-2">
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <p class="{{ $attributes->get('class', '') }} text-gray-900 text-sm font-bold dark:text-white me-1">Meeting moderation</p>
            <button type="button" data-tooltip-target="tooltip-info-meet" class="{{ $attributes->get('class', '') }} cursor-pointer text-gray-400 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z" clip-rule="evenodd"/>
              </svg>
            </button>
            <div id="tooltip-info-meet" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                Use moderation tools in Flowbite Meet to manage participants effectively.
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
          <button type="button" data-moderation-checkbox-toggle="false" class="{{ $attributes->get('class', '') }} text-primary-700 dark:text-primary-600 hover:underline text-sm font-medium">Toggle all</button>
        </div>

        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400 text-sm mb-5">Lets you restrict what participants can do in the meeting by checking the toggles.</p>
        
        <div class="{{ $attributes->get('class', '') }} space-y-2 mb-5">
          <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" data-moderation-checkbox>
            <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Share their screen</span>
          </label>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" data-moderation-checkbox>
            <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Send chat messages</span>
          </label>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" data-moderation-checkbox checked>
            <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Send reactions</span>
          </label>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-start cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" data-moderation-checkbox>
            <div class="{{ $attributes->get('class', '') }} shrink-0 relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <div class="{{ $attributes->get('class', '') }} flex flex-col">
              <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Turn on their microphone</span>
              <span class="{{ $attributes->get('class', '') }} ms-3 text-xs text-gray-500 dark:text-gray-400">Turning this off might remove people using an outdated Meet app or non-Flowbite meeting hardware.</span>
            </div>
          </label>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-start cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" data-moderation-checkbox>
            <div class="{{ $attributes->get('class', '') }} shrink-0 relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <div class="{{ $attributes->get('class', '') }} flex flex-col">
              <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Turn on their video</span>
              <span class="{{ $attributes->get('class', '') }} ms-3 text-xs text-gray-500 dark:text-gray-400">Turning this off might remove people using an outdated Meet app or non-Flowbite meeting hardware.</span>
            </div>
          </label>
        </div>

        <div class="{{ $attributes->get('class', '') }} flex items-center mb-5">
          <p class="{{ $attributes->get('class', '') }} text-gray-900 text-sm font-bold dark:text-white me-1">Meeting access type</p>
          <button type="button" data-tooltip-target="tooltip-access-type" class="{{ $attributes->get('class', '') }} cursor-pointer text-gray-400 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z" clip-rule="evenodd"/>
            </svg>
          </button>
          <div id="tooltip-access-type" role="tooltip" class="{{ $attributes->get('class', '') }} absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
              Use moderation tools in Flowbite Meet to manage participants effectively.
              <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>

        <div class="{{ $attributes->get('class', '') }} space-y-2 mb-5">
          <label class="{{ $attributes->get('class', '') }} inline-flex items-start cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer">
            <div class="{{ $attributes->get('class', '') }} shrink-0 relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <div class="{{ $attributes->get('class', '') }} flex flex-col">
              <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Open</span>
              <span class="{{ $attributes->get('class', '') }} ms-3 text-xs text-gray-500 dark:text-gray-400">No one has to ask to join. Anyone can dial in.</span>
            </div>
          </label>
          <label class="{{ $attributes->get('class', '') }} inline-flex items-start cursor-pointer">
            <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer">
            <div class="{{ $attributes->get('class', '') }} shrink-0 relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            <div class="{{ $attributes->get('class', '') }} flex flex-col">
              <span class="{{ $attributes->get('class', '') }} ms-3 text-sm font-medium text-gray-900 dark:text-white">Trusted</span>
              <span class="{{ $attributes->get('class', '') }} ms-3 text-xs text-gray-500 dark:text-gray-400">People can join without asking if they’re invited using their Flowbite account. Everyone else must ask to join. Anyone can dial in by phone.</span>
            </div>
          </label>
        </div>

    </div>

</div>
</div>

<!-- submit feedback drawer -->
<div
  id="feedback-drawer"
  class="{{ $attributes->get('class', '') }} fixed right-0 top-0 z-40 h-screen w-full max-w-md translate-x-full overflow-y-auto bg-white p-4 antialiased transition-transform dark:bg-gray-800"
  tabindex="-1"
  aria-labelledby="feedback-drawer-label"
  aria-hidden="true"
>
  <h5 id="feedback-drawer-label" class="{{ $attributes->get('class', '') }} mb-6 inline-flex items-center text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">Submit feedback</h5>
  <button
    type="button"
    data-drawer-dismiss="feedback-drawer"
    aria-controls="feedback-drawer"
    class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
      <path
        fill-rule="evenodd"
        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
        clip-rule="evenodd"
      ></path>
    </svg>
    <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
  </button>
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
        <span class="{{ $attributes->get('class', '') }} ms-2 text-lg font-bold text-gray-900 dark:text-white">Happy</span>
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
      <div>
        <p class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Add real photos of the issue <span class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">(optional)</span></p>
        <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center">
          <label
            for="dropzone-file"
            class="{{ $attributes->get('class', '') }} dark:hover:bg-bray-800 flex h-48 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-600"
          >
            <div class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center pb-6 pt-5">
              <svg class="{{ $attributes->get('class', '') }} mb-4 h-8 w-8 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                />
              </svg>
              <p class="{{ $attributes->get('class', '') }} mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-semibold">Click to upload</span> or drag and drop</p>
              <p class="{{ $attributes->get('class', '') }} text-xs text-gray-500 dark:text-gray-400">Max file 30MB</p>
            </div>
            <input id="dropzone-file" type="file" class="{{ $attributes->get('class', '') }} hidden" />
          </label>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex w-full justify-center space-x-3 pb-4">
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Submit
        </button>
        <button
          type="button"
          data-drawer-dismiss="feedback-drawer"
          aria-controls="feedback-drawer"
          class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Cancel
        </button>
      </div>
    </div>
  </form>
</div>
