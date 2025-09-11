@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Upcoming To-Do Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: monthly-tasks-layout
footer: true
group: project-management
page: to-do-upcoming
---

<div
  class="{{ $attributes->get('class', '') }} mb-4 items-center justify-between border-b border-gray-200 bg-white px-4 py-4 dark:border-gray-700 dark:bg-gray-800 sm:flex"
>
  <div>
    <nav class="{{ $attributes->get('class', '') }} mb-4 flex" aria-label="Breadcrumb">
      <ol
        class="{{ $attributes->get('class', '') }} inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse"
      >
        <li class="{{ $attributes->get('class', '') }} inline-flex items-center">
          <a
            href="/"
            class="{{ $attributes->get('class', '') }} inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-2.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z"
                clip-rule="evenodd"
              />
            </svg>
            Home
          </a>
        </li>
        <li>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <svg
              class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m9 5 7 7-7 7"
              />
            </svg>
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2"
              >Projects</a
            >
          </div>
        </li>
        <li aria-current="page">
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <svg
              class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m9 5 7 7-7 7"
              />
            </svg>
            <span
              class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2"
              >Upcoming tasks</span
            >
          </div>
        </li>
      </ol>
    </nav>
    <h1
      class="{{ $attributes->get('class', '') }} mb-4 text-xl font-semibold text-gray-900 dark:text-white sm:mb-0"
    >
      Upcoming tasks
    </h1>
  </div>
  <button
    id="newTaskButton"
    data-modal-target="newTaskModal"
    data-modal-toggle="newTaskModal"
    type="button"
    class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
  >
    <svg
      class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <path
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M5 12h14m-7 7V5"
      />
    </svg>
    Add new task
  </button>
</div>

<div class="{{ $attributes->get('class', '') }} space-y-4 px-4 xl:me-96">
  <!-- High Tasks Block -->
  <div>
    <h2 class="{{ $attributes->get('class', '') }} mb-4 text-lg font-semibold text-gray-900 dark:text-white">
      Tuesday, 1 August 2025
    </h2>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-red-600 dark:text-red-500 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Change the homepage design for the Flowbite website.
        </h3>
      </div>
      <button
        id="task-dropdown-button"
        type="button"
        data-dropdown-toggle="task-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-red-600 dark:text-red-500 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Redesign the company's website to enhance user experience.
        </h3>
      </div>
      <button
        id="task-1-dropdown-button"
        type="button"
        data-dropdown-toggle="task-1-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-1-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-1-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-orange-400 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Design the packaging for a new product, considering both physical
          packaging and any digital representations.
        </h3>
      </div>
      <button
        id="task-dropdown-2-button"
        type="button"
        data-dropdown-toggle="task-2-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-2-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-2-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-orange-400 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Develop a series of graphics and visual content for a social media
          campaign.
        </h3>
      </div>
      <button
        id="task-3-dropdown-button"
        type="button"
        data-dropdown-toggle="task-3-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-3-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-3-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-green-500 dark:text-green-400 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Review and enhance the user interface of Flowbite chat.
        </h3>
      </div>
      <button
        id="task-4-dropdown-button"
        type="button"
        data-dropdown-toggle="task-4-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-4-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-4-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <button
      id="newTaskButton"
      data-modal-target="newTaskModal"
      data-modal-toggle="newTaskModal"
      class="{{ $attributes->get('class', '') }} mt-4 flex w-full items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-100 py-2 font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white"
    >
      <svg
        class="{{ $attributes->get('class', '') }} me-2 h-5 w-5"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 12h14m-7 7V5"
        />
      </svg>
      Add new task
    </button>
  </div>
  <!-- Medium Tasks Block -->
  <div>
    <h2 class="{{ $attributes->get('class', '') }} mb-4 text-lg font-semibold text-gray-900 dark:text-white">
      Wednesday, 2 August 2025
    </h2>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-orange-400 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Change the homepage design for the Flowbite website.
        </h3>
      </div>
      <button
        id="task-5-dropdown-button"
        type="button"
        data-dropdown-toggle="task-5-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-5-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-5-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-red-600 dark:text-red-500 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Redesign the company's website to enhance user experience.
        </h3>
      </div>
      <button
        id="task-6-dropdown-button"
        type="button"
        data-dropdown-toggle="task-6-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-6-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-6-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <button
      id="newTaskButton"
      data-modal-target="newTaskModal"
      data-modal-toggle="newTaskModal"
      class="{{ $attributes->get('class', '') }} mt-4 flex w-full items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-100 py-2 font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white"
    >
      <svg
        class="{{ $attributes->get('class', '') }} me-2 h-5 w-5"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 12h14m-7 7V5"
        />
      </svg>
      Add new task
    </button>
  </div>
  <!-- Low Tasks Block -->
  <div>
    <h2 class="{{ $attributes->get('class', '') }} mb-4 text-lg font-semibold text-gray-900 dark:text-white">
      Thursday, 3 August 2025
    </h2>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-red-600 dark:text-red-500 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Change the homepage design for the Flowbite website.
        </h3>
      </div>
      <button
        id="task-7-dropdown-button"
        type="button"
        data-dropdown-toggle="task-7-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-7-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-7-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-green-500 dark:text-green-400 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Redesign the company's website to enhance user experience.
        </h3>
      </div>
      <button
        id="task-8-dropdown-button"
        type="button"
        data-dropdown-toggle="task-8-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-8-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-8-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Task Card -->
    <div
      class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6"
    >
      <div class="{{ $attributes->get('class', '') }} me-4 flex items-center sm:me-0">
        <div class="{{ $attributes->get('class', '') }} me-4 flex items-center">
          <input
            id="default-checkbox"
            type="checkbox"
            value=""
            class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="default-checkbox" class="{{ $attributes->get('class', '') }} sr-only">Default checkbox</label>
        </div>
        <svg
          class="{{ $attributes->get('class', '') }} me-1.5 hidden h-6 w-6 shrink-0 text-green-500 dark:text-green-400 sm:flex"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 14v7M5 5v9.5c5.6-5.5 8.4 2.7 14 0V4.8c-5.6 2.7-8.4-5.5-14 0Z"
          />
        </svg>
        <h3
          class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white sm:text-base"
        >
          Design the packaging for a new product, considering both physical
          packaging and any digital representations.
        </h3>
      </div>
      <button
        id="task-9-dropdown-button"
        type="button"
        data-dropdown-toggle="task-9-dropdown"
        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-6 w-6"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="3"
            d="M6 12h0m6 0h0m6 0h0"
          />
        </svg>
      </button>
      <div
        id="task-9-dropdown"
        class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-sm bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
      >
        <ul
          class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
          aria-labelledby="task-9-dropdown-button"
        >
          <li>
            <button
              type="button"
              data-modal-toggle="editTaskModal"
              data-modal-target="editTaskModal"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z"
                  clip-rule="evenodd"
                />
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
            <a
              href="#"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Duplicate
            </a>
          </li>
          <li>
            <a
              href="../project-summary"
              class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 6c0-1.1.9-2 2-2h5.5a2 2 0 0 1 1.6.7L14 7H3V6Zm0 3v10c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V9H3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Go to project
            </a>
          </li>
        </ul>
        <div class="{{ $attributes->get('class', '') }} p-2">
          <button
            type="button"
            id="deleteTaskButton"
            data-modal-target="deleteTaskModal"
            data-modal-toggle="deleteTaskModal"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <button
      id="newTaskButton"
      data-modal-target="newTaskModal"
      data-modal-toggle="newTaskModal"
      class="{{ $attributes->get('class', '') }} mt-4 flex w-full items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-100 py-2 font-medium text-gray-500 hover:border-primary-700 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white"
    >
      <svg
        class="{{ $attributes->get('class', '') }} me-2 h-5 w-5"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 12h14m-7 7V5"
        />
      </svg>
      Add new task
    </button>
  </div>
</div>

<!-- Delete task modal -->
<div
  id="deleteTaskModal"
  tabindex="-1"
  aria-hidden="true"
  class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0"
>
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div
      class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5"
    >
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteTaskModal"
      >
        <svg
          class="{{ $attributes->get('class', '') }} h-5 w-5"
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
            d="M6 18 17.94 6M18 18 6.06 6"
          />
        </svg>

        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <svg
        class="{{ $attributes->get('class', '') }} mx-auto mb-3.5 h-11 w-11 text-gray-400 dark:text-gray-500"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          fill-rule="evenodd"
          d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
          clip-rule="evenodd"
        />
      </svg>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-300">
        Are you sure you want to delete this task?
      </p>
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="deleteTaskModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
        >
          No, cancel
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
        >
          Yes, I'm sure
        </button>
      </div>
    </div>
  </div>
</div>

<!-- New Task Modal -->
<div
  id="newTaskModal"
  tabindex="-1"
  aria-hidden="true"
  class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0"
>
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-xl p-4">
    <!-- Modal content -->
    <div
      class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5"
    >
      <!-- Modal header -->
      <div
        class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5"
      >
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">
          Add new task
        </h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="newTaskModal"
        >
          <svg
            class="{{ $attributes->get('class', '') }} h-5 w-5"
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
              d="M6 18 17.94 6M18 18 6.06 6"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#" class="{{ $attributes->get('class', '') }} space-y-4">
        <div>
          <label
            for="title"
            class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
            >Title</label
          >
          <input
            type="text"
            name="title"
            id="title"
            class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Add title here"
            required=""
          />
        </div>
        <div>
          <label
            for="description"
            class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
            >Description</label
          >
          <div
            class="{{ $attributes->get('class', '') }} mb-4 w-full rounded-lg border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"
          >
            <div
              class="{{ $attributes->get('class', '') }} flex items-center justify-between border-b p-1 dark:border-gray-600 border-gray-300"
            >
              <div
                class="{{ $attributes->get('class', '') }} flex flex-wrap items-center divide-gray-200 dark:divide-gray-600 sm:divide-x"
              >
                <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1 sm:pr-2">
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 8v8a5 5 0 1 0 10 0V6.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 0 4 0V8"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Attach file</span>
                  </button>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M12 2a8 8 0 0 1 6.6 12.6l-.1.1-.6.7-5.1 6.2a1 1 0 0 1-1.6 0L6 15.3l-.3-.4-.2-.2v-.2A8 8 0 0 1 11.8 2Zm3 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Embed map</span>
                  </button>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.4 9.6a1 1 0 0 0-1.8 0l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .9-1.4l-2-4a1 1 0 0 0-1.7-.2l-.5.7-1.3-2.5ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Upload image</span>
                  </button>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm-.3 9.3c.4.4.4 1 0 1.4L9.4 14l1.3 1.3a1 1 0 0 1-1.4 1.4l-2-2a1 1 0 0 1 0-1.4l2-2a1 1 0 0 1 1.4 0Zm2.6 1.4a1 1 0 0 1 1.4-1.4l2 2c.4.4.4 1 0 1.4l-2 2a1 1 0 0 1-1.4-1.4l1.3-1.3-1.3-1.3Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Format code</span>
                  </button>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM8 9c0-.6.4-1 1-1a1 1 0 0 1 0 2 1 1 0 0 1-1-1Zm6 0c0-.6.4-1 1-1a1 1 0 1 1 0 2 1 1 0 0 1-1-1Zm-5.5 7.2c-1-.8-1.7-2-1.9-3.2h10.8a5.5 5.5 0 0 1-9 3.2Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Add emoji</span>
                  </button>
                </div>
                <div
                  class="{{ $attributes->get('class', '') }} hidden flex-wrap items-center space-x-1 sm:flex sm:pl-2"
                >
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Timeline</span>
                  </button>
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z"
                        clip-rule="evenodd"
                      />
                      <path
                        fill-rule="evenodd"
                        d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Download</span>
                  </button>
                </div>
              </div>
              <button
                type="button"
                data-tooltip-target="tooltip-fullscreen-modal"
                class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg
                  class="{{ $attributes->get('class', '') }} h-5 w-5"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 4H4m0 0v4m0-4 5 5m7-5h4m0 0v4m0-4-5 5M8 20H4m0 0v-4m0 4 5-5m7 5h4m0 0v-4m0 4-5-5"
                  />
                </svg>
                <span class="{{ $attributes->get('class', '') }} sr-only">Full screen</span>
              </button>
              <div
                id="tooltip-fullscreen-modal"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                data-popper-reference-hidden=""
                data-popper-escaped=""
                data-popper-placement="bottom"
              >
                Show full screen
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} rounded-b-lg bg-gray-50 px-4 py-2 dark:bg-gray-700">
              <textarea
                id="description"
                rows="6"
                class="{{ $attributes->get('class', '') }} block w-full border-0 bg-gray-50 px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                placeholder="Write a description here"
                required=""
              ></textarea>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} items-start justify-between sm:flex">
          <div>
            <div
              class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
            >
              Assignee & Communication
            </div>
            <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center space-x-4 sm:mb-0">
              <div class="{{ $attributes->get('class', '') }} flex -space-x-4 sm:mb-0">
                <img
                  class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                  src="/images/users/helene-engels.png"
                  alt="Helene Engels"
                />
                <img
                  class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                  src="/images/users/robert-brown.png"
                  alt="Robert Brown"
                />
                <img
                  class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                  src="/images/users/bonnie-green.png"
                  alt="Bonnie Green"
                />
                <a
                  class="{{ $attributes->get('class', '') }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800"
                  href="#"
                  >+9</a
                >
              </div>
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
              >
                <svg
                  class="{{ $attributes->get('class', '') }} -ml-1 mr-1 h-4 w-4"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 12h14m-7 7V5"
                  />
                </svg>
                Add member
              </button>
            </div>
          </div>
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-[#4285F4] px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-[#4285F4]/90 focus:outline-none focus:ring-4 focus:ring-[#4285F4]/50 dark:focus:ring-[#4285F4]/55 sm:w-auto"
          >
            <svg
              class="{{ $attributes->get('class', '') }} -ml-1 mr-2 h-4 w-4"
              aria-hidden="true"
              focusable="false"
              data-prefix="fab"
              data-icon="google"
              role="img"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 488 512"
            >
              <path
                fill="currentColor"
                d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"
              />
            </svg>
            Add to calendar
          </button>
        </div>
        <div class="{{ $attributes->get('class', '') }} mb-4">
          <label
            class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
            for="priority"
            >Priority</label
          >
          <div class="{{ $attributes->get('class', '') }} flex space-x-4">
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="priority-checkbox"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label
                for="priority-checkbox"
                class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >High</label
              >
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="priority-2-checkbox"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label
                for="priority-2-checkbox"
                class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >Medium</label
              >
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                checked=""
                id="priority-3-checkbox"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label
                for="priority-3-checkbox"
                class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >Low</label
              >
            </div>
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <input
                id="priority-4-checkbox"
                type="checkbox"
                value=""
                class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              <label
                for="priority-4-checkbox"
                class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >Lowest</label
              >
            </div>
          </div>
        </div>
        <div date-rangepicker class="{{ $attributes->get('class', '') }} mb-4 flex items-center space-x-4">
          <div class="{{ $attributes->get('class', '') }} w-full">
            <label
              for="startDate"
              class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
              >Start date</label
            >
            <div class="{{ $attributes->get('class', '') }} relative">
              <div
                class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
              >
                <svg
                  class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill-rule="evenodd"
                    d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="startDate"
                name="start"
                type="text"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Start date"
              />
            </div>
          </div>
          <div class="{{ $attributes->get('class', '') }} w-full">
            <label
              for="dueDate"
              class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
              >Due date</label
            >
            <div class="{{ $attributes->get('class', '') }} relative">
              <div
                class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
              >
                <svg
                  class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill-rule="evenodd"
                    d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                id="dueDate"
                name="end"
                type="text"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="End date"
              />
            </div>
          </div>
        </div>
        <div>
          <div
            class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
          >
            Deadline notification
          </div>
          <div class="{{ $attributes->get('class', '') }} space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
            <div class="{{ $attributes->get('class', '') }} w-full">
              <label for="reminder-type" class="{{ $attributes->get('class', '') }} sr-only">Reminder type</label>
              <select
                id="reminder-type"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              >
                <option selected="">Notification</option>
                <option value="AL">Alarm</option>
                <option value="EM">Email</option>
                <option value="SM">SMS</option>
              </select>
            </div>
            <div class="{{ $attributes->get('class', '') }} w-full">
              <label for="reminder-counter" class="{{ $attributes->get('class', '') }} sr-only">Counter</label>
              <input
                type="number"
                name="reminder-counter"
                id="reminder-counter-days"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="1"
                required=""
              />
            </div>
            <div class="{{ $attributes->get('class', '') }} w-full">
              <label for="reminder-length-type" class="{{ $attributes->get('class', '') }} sr-only">Length</label>
              <select
                id="reminder-length-type"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              >
                <option selected="">Days</option>
                <option value="WE">Weeks</option>
                <option value="MO">Months</option>
              </select>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center space-x-4 sm:mt-5">
          <button
            type="submit"
            class="{{ $attributes->get('class', '') }} inline-flex items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg
              class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
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
                d="M5 12h14m-7 7V5"
              />
            </svg>

            Add new task
            <span class="{{ $attributes->get('class', '') }} sr-only">Add event</span>
          </button>
          <button
            data-modal-toggle="newTaskModal"
            type="button"
            class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Task Modal -->
<div
  id="editTaskModal"
  tabindex="-1"
  aria-hidden="true"
  class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0"
>
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-4xl p-4">
    <!-- Modal content -->
    <div
      class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5"
    >
      <!-- Modal header -->
      <div
        class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5"
      >
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">
          Edit task
        </h3>
        <div>
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg
              class="{{ $attributes->get('class', '') }} h-5 w-5"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Watch</span>
          </button>
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg
              class="{{ $attributes->get('class', '') }} h-5 w-5"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                d="M17.5 3A3.5 3.5 0 0 0 14 7L8.1 9.8A3.5 3.5 0 0 0 2 12a3.5 3.5 0 0 0 6.1 2.3l6 2.7-.1.5a3.5 3.5 0 1 0 1-2.3l-6-2.7a3.5 3.5 0 0 0 0-1L15 9a3.5 3.5 0 0 0 6-2.4c0-2-1.6-3.5-3.5-3.5Z"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Share</span>
          </button>
          <button
            id="taskActionsButton"
            data-dropdown-toggle="taskActionsDropdown"
            type="button"
            class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
          >
            <svg
              class="{{ $attributes->get('class', '') }} h-5 w-5"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-width="3"
                d="M6 12h0m6 0h0m6 0h0"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">More actions</span>
          </button>
          <!-- Dropdown menu -->
          <div
            id="taskActionsDropdown"
            class="{{ $attributes->get('class', '') }} z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
          >
            <ul
              class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
              aria-labelledby="taskActionsDropdownButton"
            >
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="m8 15 4 4 4-4m0-6-4-4-4 4"
                    />
                  </svg>
                  Move
                </a>
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M8 12.7a2 2 0 0 1-1 .3H3v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2h-2a4 4 0 0 1-4-4v-2.3ZM7 11V7a2 2 0 0 0-1 .7l-2.5 2.9a2 2 0 0 0-.3.4H7Z"
                      clip-rule="evenodd"
                    />
                    <path
                      fill-rule="evenodd"
                      d="M14 3v4h-3.8c0-.2.2-.3.3-.4l2.4-2.9a2 2 0 0 1 1.1-.6Zm2 0v4a2 2 0 0 1-2 2h-4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Clone
                </a>
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a2 2 0 0 0-1 .3V5c0-1.1.9-2 2-2h7Z"
                      clip-rule="evenodd"
                    />
                    <path
                      fill-rule="evenodd"
                      d="M8 7v4H4.2c0-.2.2-.3.3-.4l2.4-2.9A2 2 0 0 1 8 7.1Zm2 0v4a2 2 0 0 1-2 2H4v6c0 1.1.9 2 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Copy
                </a>
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5c0 1.1.9 2 2 2h1v-4c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4c0 .6-.4 1-1 1H9Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Print
                </a>
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v9.3l-2-2a1 1 0 0 0-1.4 1.4l.3.3h-6.6a1 1 0 1 0 0 2h6.6l-.3.3a1 1 0 0 0 1.4 1.4l2-2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Export XML
                </a>
              </li>
              <li>
                <a
                  href="#"
                  class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Configure
                </a>
              </li>
            </ul>
            <div class="{{ $attributes->get('class', '') }} p-2">
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
              >
                Delete
              </button>
            </div>
          </div>
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            data-modal-toggle="editTaskModal"
          >
            <svg
              class="{{ $attributes->get('class', '') }} h-5 w-5"
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
                d="M6 18 17.94 6M18 18 6.06 6"
              />
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
          </button>
        </div>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} mb-4 grid gap-5 sm:mb-5 lg:grid-cols-2">
          <div class="{{ $attributes->get('class', '') }} space-y-4">
            <div>
              <label
                for="title"
                class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >Title</label
              >
              <input
                type="text"
                name="title"
                id="title"
                value="Redesign Flowbite homepage "
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Add title here"
                required=""
              />
            </div>
            <div>
              <label
                for="description"
                class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >Description</label
              >
              <textarea
                id="description"
                rows="15"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Write your thoughts here..."
              >
Redesigning a homepage can be a powerful way to improve user experience, engagement, and overall brand perception.

Here are some tips for a successful homepage redesign: User-Centric Approach:

Prioritize the needs and expectations of your target audience. Conduct user research, analyze user behavior, and consider feedback to understand what elements are most valuable to them.

              </textarea>
            </div>
          </div>
          <!-- Right Content -->
          <div>
            <div class="{{ $attributes->get('class', '') }} mb-4">
              <div
                class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
              >
                Assignee & Communication
              </div>
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
                <div class="{{ $attributes->get('class', '') }} flex -space-x-4 sm:mb-0">
                  <img
                    class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                    src="/images/users/helene-engels.png"
                    alt="Helene Engels"
                  />
                  <img
                    class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                    src="/images/users/robert-brown.png"
                    alt="Robert Brown"
                  />
                  <img
                    class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                    src="/images/users/bonnie-green.png"
                    alt="Bonnie Green"
                  />
                  <a
                    class="{{ $attributes->get('class', '') }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800"
                    href="#"
                    >+9</a
                  >
                </div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg
                    class="{{ $attributes->get('class', '') }} -ml-1 mr-1 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 12h14m-7 7V5"
                    />
                  </svg>
                  Add member
                </button>
              </div>
            </div>
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} mb-4 inline-flex items-center rounded-lg bg-[#4285F4] px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-[#4285F4]/90 focus:outline-none focus:ring-4 focus:ring-[#4285F4]/50 dark:focus:ring-[#4285F4]/55"
            >
              <svg
                class="{{ $attributes->get('class', '') }} -ml-1 mr-2 h-4 w-4"
                aria-hidden="true"
                focusable="false"
                data-prefix="fab"
                data-icon="google"
                role="img"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 488 512"
              >
                <path
                  fill="currentColor"
                  d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"
                />
              </svg>
              Add Google Meet video conference
            </button>
            <div class="{{ $attributes->get('class', '') }} mb-4">
              <label
                class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                for="priority"
                >Priority</label
              >
              <div class="{{ $attributes->get('class', '') }} flex space-x-4">
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    id="priority-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label
                    for="priority-checkbox-edit"
                    class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >High</label
                  >
                </div>
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    id="priority-2-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label
                    for="priority-2-checkbox-edit"
                    class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Medium</label
                  >
                </div>
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    checked=""
                    id="priority-3-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label
                    for="priority-3-checkbox-edit"
                    class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Low</label
                  >
                </div>
                <div class="{{ $attributes->get('class', '') }} flex items-center">
                  <input
                    id="priority-4-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label
                    for="priority-4-checkbox-edit"
                    class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Lowest</label
                  >
                </div>
              </div>
            </div>
            <div date-rangepicker class="{{ $attributes->get('class', '') }} mb-4 flex items-center space-x-4">
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label
                  for="startDate"
                  class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
                  >Start date</label
                >
                <div class="{{ $attributes->get('class', '') }} relative">
                  <div
                    class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </div>
                  <input
                    id="startDate"
                    name="start"
                    type="text"
                    class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="Start date"
                  />
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label
                  for="dueDate"
                  class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
                  >Due date</label
                >
                <div class="{{ $attributes->get('class', '') }} relative">
                  <div
                    class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </div>
                  <input
                    id="dueDate"
                    name="end"
                    type="text"
                    class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="End date"
                  />
                </div>
              </div>
            </div>
            <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center space-x-4">
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label
                  for="category"
                  class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
                >
                  Category
                  <button
                    data-popover-target="popover-category-modal"
                    class="{{ $attributes->get('class', '') }} ms-2"
                    type="button"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-400 hover:text-gray-900 dark:hover:text-white"
                      aria-hidden="true"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Show information</span>
                  </button>
                </label>
                <select
                  id="category"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected>Design</option>
                  <option value="seo">SEO</option>
                  <option value="marketing">Marketing</option>
                  <option value="programming">Programming</option>
                  <option value="sales">Sales</option>
                </select>
                <div
                  id="popover-category-modal"
                  role="tooltip"
                  class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600"
                >
                  Select the category to which this task belongs, with its help
                  you can more efficiently categorize the team's tasks.
                  <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label
                  for="task-type"
                  class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
                >
                  Type
                  <button
                    data-popover-target="popover-task-type-modal"
                    class="{{ $attributes->get('class', '') }} ms-2"
                    type="button"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-400 hover:text-gray-900 dark:hover:text-white"
                      aria-hidden="true"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Show information</span>
                  </button>
                </label>
                <select
                  id="task-type"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected>Sprint</option>
                  <option value="decision">Decision</option>
                  <option value="finance">Finance</option>
                  <option value="problems">Problem-solving</option>
                </select>
                <div
                  id="popover-task-type-modal"
                  role="tooltip"
                  class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600"
                >
                  Select the type of task, with its help you can more
                  efficiently categorize the team's tasks.
                  <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </div>
            <div>
              <div
                class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white"
              >
                Deadline notification
              </div>
              <div class="{{ $attributes->get('class', '') }} space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
                <div class="{{ $attributes->get('class', '') }} w-full">
                  <label for="reminder-type" class="{{ $attributes->get('class', '') }} sr-only"
                    >Reminder type</label
                  >
                  <select
                    id="reminder-type"
                    class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  >
                    <option selected="">Notification</option>
                    <option value="AL">Alarm</option>
                    <option value="EM">Email</option>
                    <option value="SM">SMS</option>
                  </select>
                </div>
                <div class="{{ $attributes->get('class', '') }} w-full">
                  <label for="reminder-counter" class="{{ $attributes->get('class', '') }} sr-only">Counter</label>
                  <input
                    type="number"
                    name="reminder-counter"
                    id="reminder-counter-days"
                    class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="1"
                    required=""
                  />
                </div>
                <div class="{{ $attributes->get('class', '') }} w-full">
                  <label for="reminder-length-type" class="{{ $attributes->get('class', '') }} sr-only"
                    >Length</label
                  >
                  <select
                    id="reminder-length-type"
                    class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  >
                    <option selected="">Days</option>
                    <option value="WE">Weeks</option>
                    <option value="MO">Months</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} mt-4">
          <h3 class="{{ $attributes->get('class', '') }} mb-5 font-medium text-gray-900 dark:text-white">
            Activity
          </h3>
          <div class="{{ $attributes->get('class', '') }} flex">
            <div class="{{ $attributes->get('class', '') }} me-3 hidden shrink-0 sm:flex">
              <img
                class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full"
                src="/images/users/roberta-casas.png"
                alt="Roberta Casas"
              />
            </div>
            <div class="{{ $attributes->get('class', '') }} mb-6 w-full">
              <div class="{{ $attributes->get('class', '') }} mb-2 flex items-center justify-between">
                <label
                  for="description"
                  class="{{ $attributes->get('class', '') }} block text-sm font-medium text-gray-900 dark:text-white"
                  >Add a comment</label
                >
                <span class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400"
                  >Premm M to comment</span
                >
              </div>
              <div
                class="{{ $attributes->get('class', '') }} mb-4 w-full rounded-lg border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"
              >
                <div
                  class="{{ $attributes->get('class', '') }} flex items-center justify-between border-b p-1 dark:border-gray-600 border-gray-300"
                >
                  <div
                    class="{{ $attributes->get('class', '') }} flex flex-wrap items-center divide-gray-200 dark:divide-gray-600 sm:divide-x"
                  >
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1 sm:pr-2">
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 8v8a5 5 0 1 0 10 0V6.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 0 4 0V8"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Attach file</span>
                      </button>
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M12 2a8 8 0 0 1 6.6 12.6l-.1.1-.6.7-5.1 6.2a1 1 0 0 1-1.6 0L6 15.3l-.3-.4-.2-.2v-.2A8 8 0 0 1 11.8 2Zm3 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Embed map</span>
                      </button>
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.4 9.6a1 1 0 0 0-1.8 0l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .9-1.4l-2-4a1 1 0 0 0-1.7-.2l-.5.7-1.3-2.5ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Upload image</span>
                      </button>
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm-.3 9.3c.4.4.4 1 0 1.4L9.4 14l1.3 1.3a1 1 0 0 1-1.4 1.4l-2-2a1 1 0 0 1 0-1.4l2-2a1 1 0 0 1 1.4 0Zm2.6 1.4a1 1 0 0 1 1.4-1.4l2 2c.4.4.4 1 0 1.4l-2 2a1 1 0 0 1-1.4-1.4l1.3-1.3-1.3-1.3Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Format code</span>
                      </button>
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM8 9c0-.6.4-1 1-1a1 1 0 0 1 0 2 1 1 0 0 1-1-1Zm6 0c0-.6.4-1 1-1a1 1 0 1 1 0 2 1 1 0 0 1-1-1Zm-5.5 7.2c-1-.8-1.7-2-1.9-3.2h10.8a5.5 5.5 0 0 1-9 3.2Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Add emoji</span>
                      </button>
                    </div>
                    <div
                      class="{{ $attributes->get('class', '') }} hidden flex-wrap items-center space-x-1 sm:flex sm:pl-2"
                    >
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Timeline</span>
                      </button>
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        <svg
                          class="{{ $attributes->get('class', '') }} h-5 w-5"
                          aria-hidden="true"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z"
                            clip-rule="evenodd"
                          />
                          <path
                            fill-rule="evenodd"
                            d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} sr-only">Download</span>
                      </button>
                    </div>
                  </div>
                  <button
                    type="button"
                    data-tooltip-target="tooltip-fullscreen-2"
                    class="{{ $attributes->get('class', '') }} cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg
                      class="{{ $attributes->get('class', '') }} h-5 w-5"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 4H4m0 0v4m0-4 5 5m7-5h4m0 0v4m0-4-5 5M8 20H4m0 0v-4m0 4 5-5m7 5h4m0 0v-4m0 4-5-5"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Full screen</span>
                  </button>
                  <div
                    id="tooltip-fullscreen-2"
                    role="tooltip"
                    class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                    data-popper-reference-hidden=""
                    data-popper-escaped=""
                    data-popper-placement="bottom"
                  >
                    Show full screen
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} rounded-b-lg bg-gray-50 px-4 py-2 dark:bg-gray-700">
                  <textarea
                    id="description"
                    rows="6"
                    class="{{ $attributes->get('class', '') }} block w-full border-0 bg-gray-50 px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                    placeholder="Write a description here"
                    required=""
                  ></textarea>
                </div>
              </div>
              <button
                type="submit"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-primary-700 px-4 py-2.5 text-center text-xs font-medium text-white hover:bg-primary-800 focus:ring-4 focus:ring-primary-200 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
              >
                Post comment
              </button>
            </div>
          </div>
          <article>
            <footer class="{{ $attributes->get('class', '') }} mb-2 flex w-full items-center justify-between">
              <a href="#" class="{{ $attributes->get('class', '') }} flex items-center">
                <img
                  class="{{ $attributes->get('class', '') }} mr-2 h-6 w-6 rounded-full"
                  src="/images/users/roberta-casas.png"
                  alt="Roberta Casas profile picture"
                />
                <p
                  class="{{ $attributes->get('class', '') }} mr-3 inline-flex flex-col items-start text-sm text-gray-900 dark:text-white md:flex-row"
                >
                  <span class="{{ $attributes->get('class', '') }} font-semibold">Roberta Casas</span>
                  <time
                    class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 md:ml-2"
                    pubdate
                    datetime="2025-03-15"
                    title="March 15th, 2025"
                    >Mar. 15, 2025</time
                  >
                  <span
                    class="{{ $attributes->get('class', '') }} ms-2 hidden text-gray-500 dark:text-gray-400 md:flex"
                    >Edited on Aug 18</span
                  >
                </p>
              </a>
              <button
                id="commentDropdownButton"
                data-dropdown-toggle="commentDropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-white p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                type="button"
              >
                <svg
                  class="{{ $attributes->get('class', '') }} h-5 w-5"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="3"
                    d="M6 12h0m6 0h0m6 0h0"
                  />
                </svg>
                <span class="{{ $attributes->get('class', '') }} sr-only">Comment settings</span>
              </button>
              <!-- Dropdown menu -->
              <div
                id="commentDropdown"
                class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
              >
                <ul
                  class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400"
                  aria-labelledby="commentDropdownButton"
                >
                  <li>
                    <a
                      href="#"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                      >Edit</a
                    >
                  </li>
                  <li>
                    <a
                      href="#"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                      >Archive</a
                    >
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </footer>
            <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">
              Any frictions in the user journey hinder customers from reaching
              their goals. Before starting redesign, it’s crucial to detect the
              obstacles users face when interacting with your website.
            </p>
            <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center space-x-4">
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-gray-500 hover:underline dark:text-gray-400"
              >
                Edit
              </button>
              <button
                type="button"
                class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-gray-500 hover:underline dark:text-gray-400"
              >
                <svg
                  class="{{ $attributes->get('class', '') }} me-1 h-4 w-4"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 9h5m3 0h2M7 12h2m3 0h5M5 5h14c.6 0 1 .4 1 1v9c0 .6-.4 1-1 1h-6.6a1 1 0 0 0-.7.3l-2.9 2.5c-.3.3-.8.1-.8-.3V17c0-.6-.4-1-1-1H5a1 1 0 0 1-1-1V6c0-.6.4-1 1-1Z"
                  />
                </svg>
                Reply
              </button>
            </div>
          </article>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
          <button
            type="submit"
            class="{{ $attributes->get('class', '') }} inline-flex items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg
              class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-5 w-5"
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
                d="M5 12h14m-7 7V5"
              />
            </svg>
            Add new task
            <span class="{{ $attributes->get('class', '') }} sr-only">Add event</span>
          </button>
          <button
            data-modal-toggle="editTaskModal"
            type="button"
            class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
