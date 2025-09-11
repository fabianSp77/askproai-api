@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Text Editor - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
group: pages
page: text-editor
footer: true
---

<form action="#">
  <div class="{{ $attributes->get('class', '') }} grid grid-cols-12 gap-4 border-b border-gray-200 bg-white pb-4 dark:border-gray-800 dark:bg-gray-900">
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
              <a href="#" class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Pages</a>
            </div>
          </li>
          <li aria-current="page">
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Text Editor</span>
            </div>
          </li>
        </ol>
      </nav>
      <div class="{{ $attributes->get('class', '') }} items-center justify-between sm:flex">
        <h5 class="{{ $attributes->get('class', '') }} mb-2 text-xl font-semibold text-gray-900 dark:text-white sm:mb-0">Add a new post</h5>
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-red-700 px-3 py-2 text-sm text-red-700 hover:border-red-800 hover:bg-red-800 hover:text-white focus:outline-none focus:ring-4 focus:ring-red-300 dark:border-red-500 dark:text-red-500 dark:hover:bg-red-600 dark:hover:text-white dark:focus:ring-red-900 sm:w-auto"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sm:hidden">Trash</span>
            <span class="{{ $attributes->get('class', '') }} hidden sm:block">Move to trash</span>
          </button>
          <button
            type="button"
            id="schedulePostButton"
            data-modal-target="schedulePostModal"
            data-modal-toggle="schedulePostModal"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                clip-rule="evenodd"
              />
            </svg>
            Schedule
          </button>
          <button
            type="submit"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                clip-rule="evenodd"
              />
            </svg>
            Publish
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="{{ $attributes->get('class', '') }} flex">
    <div class="{{ $attributes->get('class', '') }} w-full bg-white p-4 dark:bg-gray-900">
      <label for="article_title" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Title</label>
      <input
        type="text"
        id="article_title"
        class="{{ $attributes->get('class', '') }} mb-4 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        placeholder="Write your article title here"
        required
      />
      <label for="article_content" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Article content</label>
      <div class="{{ $attributes->get('class', '') }} mb-4 w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700 md:mb-6">
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
              <div id="tooltip-bold" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
              <div id="tooltip-italic" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
              <div id="tooltip-strike" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
              <div id="tooltip-code" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
              <div id="tooltip-link" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
                    <button data-text-size="16px" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">16px (Default)</button>
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
                    <button data-font-family="Inter, ui-sans-serif" type="button" class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between rounded-sm px-3 py-2 font-sans text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
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
            <div id="tooltip-image" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
            <div id="tooltip-video" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
            <div id="tooltip-list" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
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
            <div id="tooltip-hr-list" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Toggle Horizontal Rule
              <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} rounded-b-lg bg-white px-4 py-2 dark:bg-gray-800">
          <label for="wysiwyg-example" class="{{ $attributes->get('class', '') }} sr-only">Publish post</label>
          <div id="wysiwyg-example" class="{{ $attributes->get('class', '') }} block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"></div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex flex-wrap items-center gap-4 md:mb-6">
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            id="enable-notification"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            checked
          />
          <label for="enable-notification" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Enable email notification</label>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            id="disable-comments"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="disable-comments" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Disable comments</label>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <input
            id="disable-reactions"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="disable-reactions" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Disable reactions</label>
        </div>
      </div>
    </div>
    <aside id="sidebar-user" class="{{ $attributes->get('class', '') }} hidden w-80 min-w-80 border-s border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 md:flex" aria-label="Sidebar">
      <div class="{{ $attributes->get('class', '') }} h-full space-y-4 overflow-y-auto p-4">
        <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
          <h3 class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-3 font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M5 9a7 7 0 1 1 8 6.93V21a1 1 0 1 1-2 0v-5.07A7.001 7.001 0 0 1 5 9Zm5.94-1.06A1.5 1.5 0 0 1 12 7.5a1 1 0 1 0 0-2A3.5 3.5 0 0 0 8.5 9a1 1 0 0 0 2 0c0-.398.158-.78.44-1.06Z" clip-rule="evenodd" />
            </svg>
            Status
          </h3>
          <ul class="{{ $attributes->get('class', '') }} my-4 flex flex-wrap gap-3">
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="draft"
                type="radio"
                value=""
                name="status-radio"
                class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                checked
              />
              <label for="draft" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Draft</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="free"
                type="radio"
                value=""
                name="status-radio"
                class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="free" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Free</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="paid"
                type="radio"
                value=""
                name="status-radio"
                class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="paid" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Paid</label>
            </li>
          </ul>
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm3 11a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6H8v-6Zm1-7V5h6v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z"
                clip-rule="evenodd"
              />
              <path fill-rule="evenodd" d="M14 17h-4v-2h4v2Z" clip-rule="evenodd" />
            </svg>
            Save
          </button>
        </div>
        <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
          <h3 class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-3 font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            Visibility
          </h3>
          <ul class="{{ $attributes->get('class', '') }} my-4 flex flex-wrap gap-3">
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="public"
                type="radio"
                name="visibility-radio"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="public" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Public</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="password-protected"
                type="radio"
                value=""
                name="visibility-radio"
                class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="password-protected" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Protected</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="private"
                type="radio"
                name="visibility-radio"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                checked
              />
              <label for="private" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Private</label>
            </li>
          </ul>
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm3 11a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6H8v-6Zm1-7V5h6v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z"
                clip-rule="evenodd"
              />
              <path fill-rule="evenodd" d="M14 17h-4v-2h4v2Z" clip-rule="evenodd" />
            </svg>
            Save
          </button>
        </div>
        <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
          <h3 class="{{ $attributes->get('class', '') }} flex items-center border-b border-gray-200 pb-3 font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857Zm10 0A1.857 1.857 0 0 0 13 14.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 19.143v-4.286A1.857 1.857 0 0 0 19.143 13h-4.286Z"
                clip-rule="evenodd"
              />
            </svg>
            Category
          </h3>
          <ul class="{{ $attributes->get('class', '') }} my-4 space-y-3">
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="news"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="news" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">News</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="political"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="political" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Political</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="business"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                checked
              />
              <label for="business" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Business & Finance</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="sports"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="sports" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Sports</label>
            </li>
            <li class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="culture"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label for="culture" class="{{ $attributes->get('class', '') }} ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Culture & Lifestyle</label>
            </li>
          </ul>
          <div class="{{ $attributes->get('class', '') }} flex items-center space-x-3">
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm3 11a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6H8v-6Zm1-7V5h6v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z"
                  clip-rule="evenodd"
                />
                <path fill-rule="evenodd" d="M14 17h-4v-2h4v2Z" clip-rule="evenodd" />
              </svg>
              Save
            </button>
            <button class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500">
              <svg class="{{ $attributes->get('class', '') }} me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
              </svg>
              Add new category
            </button>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
          <h3 class="{{ $attributes->get('class', '') }} mb-3 flex items-center border-b border-gray-200 pb-3 font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M18.045 3.007 12.31 3a1.965 1.965 0 0 0-1.4.585l-7.33 7.394a2 2 0 0 0 0 2.805l6.573 6.631a1.957 1.957 0 0 0 1.4.585 1.965 1.965 0 0 0 1.4-.585l7.409-7.477A2 2 0 0 0 21 11.479v-5.5a2.972 2.972 0 0 0-2.955-2.972Zm-2.452 6.438a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"
              />
            </svg>

            Tags
          </h3>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <label for="tags-input" class="{{ $attributes->get('class', '') }} sr-only">Small input</label>
            <input
              type="text"
              id="tags-input"
              class="{{ $attributes->get('class', '') }} me-2 block w-full rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            />
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
            >
              Add
            </button>
          </div>
          <div class="{{ $attributes->get('class', '') }} mt-3 flex flex-wrap gap-2">
            <div class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-sm font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
              <button type="button" class="{{ $attributes->get('class', '') }} hover:text-primary-900 dark:hover:text-primary-100">
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
              </button>
              news
            </div>
            <div class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-sm font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
              <button type="button" class="{{ $attributes->get('class', '') }} hover:text-primary-900 dark:hover:text-primary-100">
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
              </button>
              startups
            </div>
            <div class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-sm font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
              <button type="button" class="{{ $attributes->get('class', '') }} hover:text-primary-900 dark:hover:text-primary-100">
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
              </button>
              finance
            </div>
            <div class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-sm font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
              <button type="button" class="{{ $attributes->get('class', '') }} hover:text-primary-900 dark:hover:text-primary-100">
                <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
              </button>
              innovation
            </div>
          </div>
        </div>
        <div>
          <span class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Featured Image</span>
          <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center">
            <label
              for="dropzone-file"
              class="{{ $attributes->get('class', '') }} flex h-36 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-gray-600 dark:hover:bg-gray-700"
            >
              <div class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center pb-6 pt-5">
                <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} mb-3 h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="{{ $attributes->get('class', '') }} mb-2 text-sm text-gray-500 dark:text-gray-400">
                  <span class="{{ $attributes->get('class', '') }} font-semibold">Click to upload</span>
                  or drag and drop
                </p>
                <p class="{{ $attributes->get('class', '') }} text-xs text-gray-500 dark:text-gray-400">PNG, JPG or GIF (MAX. 800x400px)</p>
              </div>
              <input id="dropzone-file" type="file" class="{{ $attributes->get('class', '') }} hidden" />
            </label>
          </div>
        </div>
      </div>
    </aside>
  </div>
</form>

<!-- Schedule modal -->
<div id="schedulePostModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-2xl p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex justify-between rounded-t sm:mb-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Schedule a post</h3>
        <div>
          <button type="button" class="{{ $attributes->get('class', '') }} inline-flex rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="schedulePostModal">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
            </svg>

            <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
          </button>
        </div>
      </div>
      <!-- Calendar -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex w-auto flex-col gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:space-x-5 rtl:space-x-reverse">
        <div inline-datepicker datepicker-buttons datepicker-autoselect-today class="{{ $attributes->get('class', '') }} mx-auto sm:mx-0"></div>
        <div class="{{ $attributes->get('class', '') }} mt-5 w-full border-gray-200 dark:border-gray-800 sm:ms-7 sm:mt-0 sm:max-w-60 sm:border-s sm:ps-5">
          <h3 class="{{ $attributes->get('class', '') }} mb-3 text-base font-medium text-gray-900 dark:text-white">Wednesday 30 June 2025</h3>
          <button
            type="button"
            data-collapse-toggle="timetable"
            class="{{ $attributes->get('class', '') }} me-2 inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="{{ $attributes->get('class', '') }} me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
            </svg>
            Pick a time
          </button>
          <label class="{{ $attributes->get('class', '') }} sr-only"> Pick a time </label>
          <ul id="timetable" class="{{ $attributes->get('class', '') }} mt-5 grid w-full grid-cols-2 gap-2">
            <li>
              <input type="radio" id="10-am" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="10-am"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                10:00 AM
              </label>
            </li>
            <li>
              <input type="radio" id="10-30-am" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="10-30-am"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                10:30 AM
              </label>
            </li>
            <li>
              <input type="radio" id="11-am" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="11-am"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                11:00 AM
              </label>
            </li>
            <li>
              <input type="radio" id="11-30-am" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="11-30-am"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                11:30 AM
              </label>
            </li>
            <li>
              <input type="radio" id="12-am" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" checked />
              <label
                for="12-am"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                12:00 AM
              </label>
            </li>
            <li>
              <input type="radio" id="12-30-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="12-30-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                12:30 PM
              </label>
            </li>
            <li>
              <input type="radio" id="1-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="1-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                01:00 PM
              </label>
            </li>
            <li>
              <input type="radio" id="1-30-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="1-30-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                01:30 PM
              </label>
            </li>
            <li>
              <input type="radio" id="2-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="2-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                02:00 PM
              </label>
            </li>
            <li>
              <input type="radio" id="2-30-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="2-30-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                02:30 PM
              </label>
            </li>
            <li>
              <input type="radio" id="3-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="3-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                03:00 PM
              </label>
            </li>
            <li>
              <input type="radio" id="3-30-pm" value="" class="{{ $attributes->get('class', '') }} peer hidden" name="timetable" />
              <label
                for="3-30-pm"
                class="{{ $attributes->get('class', '') }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-primary-700 bg-white p-2 text-center text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:border-primary-500 dark:hover:bg-primary-500 dark:hover:text-white dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-500"
              >
                03:30 PM
              </label>
            </li>
          </ul>
        </div>
      </div>
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        <svg class="{{ $attributes->get('class', '') }} -ms-1 me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11.5c.07 0 .14-.007.207-.021.095.014.193.021.293.021h2a2 2 0 0 0 2-2V7a1 1 0 0 0-1-1h-1a1 1 0 1 0 0 2v11h-2V5a2 2 0 0 0-2-2H5Zm7 4a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm-6 4a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1ZM7 6a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H7Zm1 3V8h1v1H8Z"
            clip-rule="evenodd"
          />
        </svg>

        Publish
      </button>
    </div>
  </div>
</div>
