@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Project Summary - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: project-management
page: project-summary
---

<div class="{{ $attributes->get('class', '') }} my-4 px-4">
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
          <a href="#" class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Projects</a>
        </div>
      </li>
      <li aria-current="page">
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Project summary</span>
        </div>
      </li>
    </ol>
  </nav>
  <h1 class="{{ $attributes->get('class', '') }} text-xl font-bold text-gray-900 dark:text-white">Project summary</h1>
</div>

<div class="{{ $attributes->get('class', '') }} mb-4 grid gap-4 px-4">
  <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <div>
      <h2 class="{{ $attributes->get('class', '') }} mb-2 text-xl font-bold text-gray-900 dark:text-white">Commits over time</h2>
      <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Number of commits for this project</p>
    </div>

    <div id="commits-chart"></div>
  </div>
  <div class="{{ $attributes->get('class', '') }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <h2 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white md:mb-6">Project information</h2>
    <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-2 sm:gap-8 2xl:gap-60">
      <dl>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Project name</dt>
        <dd class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">Flowbite Website</dd>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Description</dt>
        <dd class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">
          From sleek and modern designs to functional and user-friendly interfaces, we ensure you that your website not only captivates visitors but also delivers an exceptional browsing experience across all devices.
        </dd>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Team</dt>
        <dd class="{{ $attributes->get('class', '') }} mb-4 flex -space-x-4 sm:mb-5 rtl:space-x-reverse">
          <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/helene-engels.png" alt="User avatar" />
          <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/jese-leos.png" alt="User avatar" />
          <img class="{{ $attributes->get('class', '') }} h-10 w-10 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/bonnie-green.png" alt="User avatar" />
          <a class="{{ $attributes->get('class', '') }} flex h-10 w-10 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800" href="#">+2</a>
        </dd>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Duration</dt>
        <dd class="{{ $attributes->get('class', '') }} flex items-center text-gray-900 dark:text-white">
          <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4a1 1 0 1 0-2 0v4c0 .3.1.5.3.7l3 3a1 1 0 0 0 1.4-1.4L13 11.6V8Z" clip-rule="evenodd" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} font-medium">2 months</span>
        </dd>
      </dl>
      <dl>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Project budget</dt>
        <dd class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">$10,000 - $50,0000</dd>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Beneficiary of the project</dt>
        <dd class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">Flowbite LLC</dd>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Project priority</dt>
        <dd class="{{ $attributes->get('class', '') }} mb-4 sm:mb-5">
          <span class="{{ $attributes->get('class', '') }} me-2 inline-flex items-center rounded-sm bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
            <svg class="{{ $attributes->get('class', '') }} me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M8.6 3.2a1 1 0 0 0-1.6 1 3.5 3.5 0 0 1-.8 3.6c-.6.8-4 5.6-1 10.7A7.7 7.7 0 0 0 12 22a8 8 0 0 0 7-3.8 7.8 7.8 0 0 0 .6-6.5 8.7 8.7 0 0 0-2.6-4 1 1 0 0 0-1.6.7 10 10 0 0 1-.8 3.4 9.9 9.9 0 0 0-2.2-5.5A14.4 14.4 0 0 0 9 3.5l-.4-.3Z"
              />
            </svg>
            High
          </span>
        </dd>
        <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Tools</dt>
        <dd class="{{ $attributes->get('class', '') }} flex items-center space-x-2 text-gray-900 dark:text-white">
          <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
            <path fill="#DC395F" d="M15.156.098H1.51C.676.098 0 .776 0 1.612v13.671c0 .836.676 1.514 1.51 1.514h13.646c.834 0 1.51-.678 1.51-1.514V1.612c0-.836-.676-1.514-1.51-1.514" />
            <path
              fill="#fff"
              fill-rule="evenodd"
              d="M6.585 4.346c0 .57-.464 1.005-1.018 1.005a1.007 1.007 0 0 1-1.018-1.005c0-.57.464-1.005 1.018-1.005s1.018.435 1.018 1.005ZM3.366 11.49c0-.225.03-.5.09-.755L4.22 7.61H3.037l.36-1.329H6.24l-1.123 4.464a2.833 2.833 0 0 0-.105.686c0 .286.14.369.36.419.133.03 1.197.009 1.777-1.281l.737-2.96H6.69l.36-1.328h2.56l-.33 1.508c.45-.84 1.348-1.638 2.232-1.638.943 0 1.721.673 1.721 1.963 0 .33-.045.69-.164 1.094l-.48 1.725c-.044.18-.074.33-.074.465 0 .3.12.45.344.45.225 0 .51-.165.839-1.08l.658.255c-.389 1.365-1.093 1.935-1.976 1.935-1.033 0-1.527-.615-1.527-1.455 0-.24.03-.495.104-.75l.495-1.77a1.8 1.8 0 0 0 .074-.54c0-.57-.344-.916-.898-.916-.704 0-1.168.506-1.407 1.482l-.959 3.85H6.585l.3-1.21c-.492.812-1.177 1.314-2.021 1.314-1.018 0-1.498-.588-1.498-1.473Z"
              clip-rule="evenodd"
            />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="none" viewBox="0 0 18 17">
            <path fill="#FDB300" d="M4.297 1.301 8.985.798l4.688.503 3.629 4.952-8.317 9.844L.668 6.253l3.63-4.952Z" />
            <path fill="#EA6C00" d="m4.035 6.254 4.948 9.844L.667 6.254h3.368Z" />
            <path fill="#EA6C00" d="M13.929 6.254 8.98 16.098l8.316-9.844H13.93Z" />
            <path fill="#FDAD00" d="M4.034 6.254h9.896l-4.948 9.844-4.948-9.844Z" />
            <path fill="#FDD231" d="M8.984.798 4.296 1.3l-.26 4.952L8.984.798Z" />
            <path fill="#FDD231" d="m8.982.798 4.688.503.26 4.952L8.982.798Z" />
            <path fill="#FDAD00" d="M17.3 6.253 13.67 1.3l.26 4.952h3.37Zm-16.633 0L4.296 1.3l-.26 4.952H.665Z" />
            <path fill="#FEEEB7" d="M8.984.798 4.036 6.253h9.895L8.985.798Z" />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="none" viewBox="0 0 12 17">
            <path fill="#0ACF83" d="M3.08 16.795a2.781 2.781 0 0 0 2.777-2.783V11.23H3.08a2.781 2.781 0 0 0-2.778 2.782 2.781 2.781 0 0 0 2.778 2.783Z" />
            <path fill="#A259FF" d="M.302 8.448A2.781 2.781 0 0 1 3.08 5.665h2.777v5.565H3.08A2.781 2.781 0 0 1 .302 8.448Z" />
            <path fill="#F24E1E" d="M.302 2.883A2.781 2.781 0 0 1 3.08.1h2.777v5.565H3.08A2.781 2.781 0 0 1 .302 2.883Z" />
            <path fill="#FF7262" d="M5.858.1h2.778a2.781 2.781 0 0 1 2.778 2.783 2.781 2.781 0 0 1-2.778 2.782H5.858V.1Z" />
            <path fill="#1ABCFE" d="M11.414 8.448a2.781 2.781 0 0 1-2.778 2.782 2.781 2.781 0 0 1-2.778-2.782 2.781 2.781 0 0 1 2.778-2.783 2.781 2.781 0 0 1 2.778 2.783Z" />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="none" viewBox="0 0 13 19">
            <path
              fill="#000"
              fill-rule="evenodd"
              d="M4.123 1.631H4.8v1.544h.767V1.63h.681V.86H4.123v.771ZM2.31.86h-.767v2.315h.774v-.772h.7v.772h.768V.86h-.768v.765H2.31V.86Zm4.274 0h.804l.494.815.493-.815h.804v2.315h-.767V2.027l-.537.832-.537-.832v1.148h-.754V.86Zm3.744 0H9.56v2.315h1.854V2.41h-1.087V.86Z"
              clip-rule="evenodd"
            />
            <path fill="#E44D26" d="M1.515 16.66.415 4.234h12.102l-1.101 12.42-4.96 1.381" />
            <path fill="#F16529" d="M6.465 16.98V5.254h4.947l-.944 10.6" />
            <path fill="#EBEBEB" fill-rule="evenodd" d="M6.467 6.774H2.664l.414 4.603h3.39v-1.52H4.464l-.14-1.56h2.142V6.774Zm-1.803 5.369H3.143l.213 2.391 3.11.873v-1.59L4.77 13.36l-.107-1.217Z" clip-rule="evenodd" />
            <path fill="#fff" fill-rule="evenodd" d="M6.459 6.774h3.796l-.14 1.523H6.46V6.774Zm-.002 3.084h3.52l-.418 4.676-3.102.866v-1.584l1.691-.456.177-1.98H6.457V9.859Z" clip-rule="evenodd" />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" class="{{ $attributes->get('class', '') }} h-5 w-5" fill="none" viewBox="0 0 16 15">
            <path fill="#2E001F" d="M13.274 14.765H3.037c-1.388 0-2.52-1.115-2.52-2.482v-9.67C.517 1.245 1.649.13 3.037.13h10.23c1.395 0 2.52 1.115 2.52 2.483v9.663c.007 1.374-1.125 2.49-2.514 2.49Z" />
            <path
              fill="#FFD9F2"
              fill-rule="evenodd"
              d="m6.411 6.935 1.945 3.68c.035.054.014.109-.042.109H7.106c-.077 0-.111-.02-.146-.09l-.346-.7c-.333-.673-.67-1.356-1.015-2.076h-.014c-.417.917-.875 1.88-1.32 2.784-.034.054-.069.075-.124.075H2.995c-.07 0-.077-.055-.042-.096l1.903-3.57-1.84-3.61c-.042-.055 0-.097.041-.097h1.195c.07 0 .097.014.125.076.437.902.882 1.833 1.298 2.742h.014c.403-.903.847-1.84 1.278-2.735l.001-.002c.034-.054.055-.087.124-.087H8.21c.056 0 .076.04.042.095l-1.84 3.502ZM8.696 8.05c0-1.594 1.077-2.839 2.785-2.839.146 0 .222 0 .361.014V3.413a.07.07 0 0 1 .07-.069h1.097c.055 0 .07.021.07.055v6.278c0 .185 0 .417.034.67 0 .042-.014.055-.056.076a4.19 4.19 0 0 1-1.777.396c-1.507.007-2.584-.91-2.584-2.77Zm2.744-1.82c.167 0 .306.028.403.069v3.385a1.329 1.329 0 0 1-.48.075c-.784 0-1.409-.506-1.409-1.764 0-1.101.611-1.765 1.486-1.765Z"
              clip-rule="evenodd"
            />
          </svg>
        </dd>
      </dl>
    </div>
    <div class="{{ $attributes->get('class', '') }} mt-4 border-t border-gray-200 pt-4 dark:border-gray-700 sm:mt-5 sm:pt-5">
      <button
        type="button"
        id="editProjectButton"
        data-modal-target="editProjectModal"
        data-modal-toggle="editProjectModal"
        class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M5 8a4 4 0 1 1 7.8 1.3l-2.5 2.5A4 4 0 0 1 5 8Zm4 5H7a4 4 0 0 0-4 4v1c0 1.1.9 2 2 2h2.2a3 3 0 0 1-.1-1.6l.6-3.4a3 3 0 0 1 .9-1.5L9 13Zm9-5a3 3 0 0 0-2 .9l-6 6a1 1 0 0 0-.3.5L9 18.8a1 1 0 0 0 1.2 1.2l3.4-.7c.2 0 .3-.1.5-.3l6-6a3 3 0 0 0-2-5Z"
            clip-rule="evenodd"
          />
        </svg>
        Edit
      </button>
    </div>
  </div>
  <!-- Table -->
  <div class="{{ $attributes->get('class', '') }} relative overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
    <div class="{{ $attributes->get('class', '') }} flex-row items-start justify-between space-y-3 p-4 sm:flex sm:space-x-4 sm:space-y-0 sm:p-6">
      <div>
        <h2 class="{{ $attributes->get('class', '') }} me-3 text-xl font-bold dark:text-white">Files and assets</h2>
        <p class="{{ $attributes->get('class', '') }} text-gray-500 dark:text-gray-400">Manage all your existing users or add a new one</p>
      </div>
      <button
        type="button"
        id="uploadFileButton"
        data-modal-target="uploadFileModal"
        data-modal-toggle="uploadFileModal"
        class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v6.4A7.5 7.5 0 1 0 10.5 22H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd" />
          <path fill-rule="evenodd" d="M9 16a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm6-3c.6 0 1 .4 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1c0-.6.4-1 1-1Z" clip-rule="evenodd" />
        </svg>
        Add new file
      </button>
    </div>
    <div class="{{ $attributes->get('class', '') }} overflow-x-auto">
      <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold sm:px-6 whitespace-nowrap">File name</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold sm:px-6 whitespace-nowrap">Details</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold sm:px-6 whitespace-nowrap">User</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold sm:px-6 whitespace-nowrap">Date added</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold sm:px-6">
              <span class="{{ $attributes->get('class', '') }} sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7c0 1.1.9 2 2 2 0 1.1.9 2 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5c0 .6.4 1 1 1h1.4a2.6 2.6 0 0 0 2.6-2.6v-1.8a2.6 2.6 0 0 0-2.6-2.6H11Zm1 5v-3h.4a.6.6 0 0 1 .6.6v1.8a.6.6 0 0 1-.6.6H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Flowbite Pro Terms & Conditions
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>12 pages</span>
              <span>&#x2025;</span>
              <span>18 MB</span>
              <span>&#x2025;</span>
              <span>PDF</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-8.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Roberta Casas
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">Today</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-1-dropdown-button"
                type="button"
                data-dropdown-toggle="file-1-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-1-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-1-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton1"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.4 9.6a1 1 0 0 0-1.8 0l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .9-1.4l-2-4a1 1 0 0 0-1.7-.2l-.5.7-1.3-2.5ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
                HeroImage.jpg
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>5.6 MB</span>
              <span>&#x2025;</span>
              <span>JPG</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-1.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Bonnie Green
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">1 day ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-2-dropdown-button"
                type="button"
                data-dropdown-toggle="file-2-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-2-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-2-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton2"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2.3 0A1 1 0 0 0 12 8v5.3a4 4 0 0 0-1.5-.3C8.8 13 7 14.1 7 16s1.8 3 3.5 3 3.5-1.1 3.5-3V9.8a3 3 0 0 1 1 2.2 1 1 0 1 0 2 0 5 5 0 0 0-1.9-3.9 6.4 6.4 0 0 0-1.8-1ZM9 16c0-.4.5-1 1.5-1s1.5.6 1.5 1-.5 1-1.5 1S9 16.4 9 16Z"
                    clip-rule="evenodd"
                  />
                </svg>
                FlowbiteMusic.mp3
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>4.7 MB</span>
              <span>&#x2025;</span>
              <span>MP3</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-2.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Leslie Livingston
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">1 day ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-3-dropdown-button"
                type="button"
                data-dropdown-toggle="file-3-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-3-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-3-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton3"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7c0 1.1.9 2 2 2 0 1.1.9 2 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H11Zm1.5 3H12v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 1 0 0 2v4a1 1 0 1 0 2 0v-4a1 1 0 1 0 0-2h-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
                ProjectPresentation.ppt
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>47 Pages</span>
              <span>&#x2025;</span>
              <span>5 MB</span>
              <span>&#x2025;</span>
              <span>PPT</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-3.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Micheal Gough
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">3 days ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-4-dropdown-button"
                type="button"
                data-dropdown-toggle="file-4-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-4-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-4-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton4"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm-2 4a2 2 0 0 0-2 2v2c0 1.1.9 2 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2H9Zm0 2h2v2H9v-2Zm8-.6a1 1 0 0 0-1.7-.7L14 13a1 1 0 0 0-.3.8v.7c0 .3.1.6.4.8l1.2 1a1 1 0 0 0 1.7-.8v-3Z"
                    clip-rule="evenodd"
                  />
                </svg>
                HomepageVideo.mp4
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>123 MB</span>
              <span>&#x2025;</span>
              <span>MP4</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-4.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Joseph McFall
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">3 days ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-5-dropdown-button"
                type="button"
                data-dropdown-toggle="file-5-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-5-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-5-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton5"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7c0 1.1.9 2 2 2 0 1.1.9 2 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm1 8.8A2.3 2.3 0 0 0 9.6 13a2.3 2.3 0 0 0 2.1 2.5h.7a.3.3 0 0 1 .2.4l-.4.1h-1a1 1 0 1 0 0 2h1c.5 0 1 0 1.4-.3a2 2 0 0 0 1-1.1 2.3 2.3 0 0 0-2.2-3l-.5-.1a.3.3 0 0 1-.3-.4.3.3 0 0 1 .4-.3h1a1 1 0 1 0 0-2h-1Zm8 1.5a1 1 0 1 0-2-.6l-.5 1.7-.5-1.7a1 1 0 0 0-2 .6l1.5 4.8a1 1 0 0 0 1.9 0l1.6-4.8Zm-13.8.9.4-.2h1a1 1 0 1 0 0-2h-1A2.6 2.6 0 0 0 4 13.6v1.8A2.6 2.6 0 0 0 6.6 18h1a1 1 0 1 0 0-2h-1a.6.6 0 0 1-.6-.6v-1.8c0-.1 0-.3.2-.4Z"
                    clip-rule="evenodd"
                  />
                </svg>
                ProjectData.csv
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>1 MB</span>
              <span>&#x2025;</span>
              <span>CSV</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-5.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Robert Brown
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">3 days ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-6-dropdown-button"
                type="button"
                data-dropdown-toggle="file-6-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-6-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-6-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton6"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2.3 0A1 1 0 0 0 12 8v5.3a4 4 0 0 0-1.5-.3C8.8 13 7 14.1 7 16s1.8 3 3.5 3 3.5-1.1 3.5-3V9.8a3 3 0 0 1 1 2.2 1 1 0 1 0 2 0 5 5 0 0 0-1.9-3.9 6.4 6.4 0 0 0-1.8-1ZM9 16c0-.4.5-1 1.5-1s1.5.6 1.5 1-.5 1-1.5 1S9 16.4 9 16Z"
                    clip-rule="evenodd"
                  />
                </svg>
                FlowbiteMusic.mp4
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>18 MB</span>
              <span>&#x2025;</span>
              <span>MP4</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-6.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Karen Nelson
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">6 days ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-7-dropdown-button"
                type="button"
                data-dropdown-toggle="file-7-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-7-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-7-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton7"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm3 2h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v2h-2v-2h2v-2h-2v-2h2v-2h-2v-2h2V8h-2V6h2V4Z"
                    clip-rule="evenodd"
                  />
                </svg>
                PortfolioImages.zip
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>50 Files</span>
              <span>&#x2025;</span>
              <span>37 MB</span>
              <span>&#x2025;</span>
              <span>ZIP</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-7.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Helene Engels
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">1 week ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-8-dropdown-button"
                type="button"
                data-dropdown-toggle="file-8-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-8-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-8-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton8"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7c0 1.1.9 2 2 2 0 1.1.9 2 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5c0 .6.4 1 1 1h1.4a2.6 2.6 0 0 0 2.6-2.6v-1.8a2.6 2.6 0 0 0-2.6-2.6H11Zm1 5v-3h.4a.6.6 0 0 1 .6.6v1.8a.6.6 0 0 1-.6.6H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Flowbite Pro Terms & Conditions
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3 sm:px-6">
              <span>12 pages</span>
              <span>&#x2025;</span>
              <span>18 MB</span>
              <span>&#x2025;</span>
              <span>PDF</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-9.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Neils Sims
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">1 week ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-9-dropdown-button"
                type="button"
                data-dropdown-toggle="file-9-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-9-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-9-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton9"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
          <tr class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.4 9.6a1 1 0 0 0-1.8 0l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .9-1.4l-2-4a1 1 0 0 0-1.7-.2l-.5.7-1.3-2.5ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
                HeroImage.jpg
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <span>5.6 MB</span>
              <span>&#x2025;</span>
              <span>JPG</span>
            </td>
            <td class="{{ $attributes->get('class', '') }} text-nowrap px-4 py-3 sm:px-6">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center font-medium text-gray-900 dark:text-white">
                <img src="/images/users/avatar-1.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                Bonnie Green
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} max-w-48 whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">1 week ago</td>
            <td class="{{ $attributes->get('class', '') }} flex items-center justify-end px-4 py-3 sm:px-6">
              <button
                id="file-10-dropdown-button"
                type="button"
                data-dropdown-toggle="file-10-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="file-10-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="file-10-dropdown-button">
                  <li>
                    <button type="button" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M13 11.1V4a1 1 0 1 0-2 0v7.1L8.8 8.4a1 1 0 1 0-1.6 1.2l4 5a1 1 0 0 0 1.6 0l4-5a1 1 0 1 0-1.6-1.2L13 11Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.7 15.9 7.4 13H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.4l-2.3 2.9a3 3 0 0 1-4.6 0Zm7.3.1a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z" clip-rule="evenodd" />
                      </svg>
                      Download
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="archiveFileModalButton"
                      data-modal-target="archiveFileModal"
                      data-modal-toggle="archiveFileModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="{{ $attributes->get('class', '') }} p-2">
                  <button
                    type="button"
                    id="deleteFileButton10"
                    data-modal-target="deleteFileModal"
                    data-modal-toggle="deleteFileModal"
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
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <nav class="{{ $attributes->get('class', '') }} flex flex-col items-start justify-between space-y-3 p-4 sm:p-6 md:flex-row md:items-center md:space-y-0" aria-label="Table navigation">
      <span class="{{ $attributes->get('class', '') }} text-sm font-normal text-gray-500 dark:text-gray-400">
        Showing
        <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1-10</span>
        of
        <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">78</span>
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
            >7</a
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

<!-- Add file modal -->
<div id="uploadFileModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <h3 class="{{ $attributes->get('class', '') }} mb-4 text-xl font-bold text-gray-900 dark:text-white">File upload</h3>
      <label for="dropzone-file" class="{{ $attributes->get('class', '') }} sr-only">Product Images</label>
      <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center">
        <label
          for="dropzone-file"
          class="{{ $attributes->get('class', '') }} flex h-48 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-600"
        >
          <div class="{{ $attributes->get('class', '') }} flex flex-col items-center justify-center pb-6 pt-5">
            <svg class="{{ $attributes->get('class', '') }} mb-3 h-10 w-10 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v9m-5 0H5a1 1 0 0 0-1 1v4c0 .6.4 1 1 1h14c.6 0 1-.4 1-1v-4c0-.6-.4-1-1-1h-2M8 9l4-5 4 5m1 8h0" />
            </svg>
            <p class="{{ $attributes->get('class', '') }} mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="{{ $attributes->get('class', '') }} font-semibold">Click to upload</span> or drag and drop</p>
            <p class="{{ $attributes->get('class', '') }} text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
          </div>
          <input id="dropzone-file" type="file" class="{{ $attributes->get('class', '') }} hidden" />
        </label>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center justify-between sm:mt-6">
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-3">
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm3 2h2v2h-2v2h2v2h-2v2h2v2h-2v2h2v2h-2v2h-2v-2h2v-2h-2v-2h2v-2h-2v-2h2V8h-2V6h2V4Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h4 class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white">flowbite-pro-v.2.2.0.zip</h4>
            <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400">32 MB</p>
          </div>
        </div>
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
        </svg>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center justify-between">
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-3">
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Z" clip-rule="evenodd" />
          </svg>
          <div>
            <h4 class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white">General Information.txt</h4>
            <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400">41,5 MB</p>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between">
          <span class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-500 dark:text-gray-400 me-4">75%</span>
          <button type="button" data-tooltip-target="tooltip-cancel-1" class="{{ $attributes->get('class', '') }} text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm7.7-3.7a1 1 0 0 0-1.4 1.4l2.3 2.3-2.3 2.3a1 1 0 1 0 1.4 1.4l2.3-2.3 2.3 2.3a1 1 0 0 0 1.4-1.4L13.4 12l2.3-2.3a1 1 0 0 0-1.4-1.4L12 10.6 9.7 8.3Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
          <div id="tooltip-cancel-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Cancel
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="{{ $attributes->get('class', '') }} h-2 rounded-full bg-primary-700 dark:bg-primary-600" style="width: 75%"></div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center justify-between">
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-3">
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.4 9.6a1 1 0 0 0-1.8 0l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .9-1.4l-2-4a1 1 0 0 0-1.7-.2l-.5.7-1.3-2.5ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <h4 class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-900 dark:text-white">peoples-at-office.jpg</h4>
            <p class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400">2 MB</p>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center justify-between">
          <span class="{{ $attributes->get('class', '') }} text-sm font-medium text-gray-500 dark:text-gray-400 me-4">36%</span>
          <button type="button" data-tooltip-target="tooltip-cancel-2" class="{{ $attributes->get('class', '') }} text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm7.7-3.7a1 1 0 0 0-1.4 1.4l2.3 2.3-2.3 2.3a1 1 0 1 0 1.4 1.4l2.3-2.3 2.3 2.3a1 1 0 0 0 1.4-1.4L13.4 12l2.3-2.3a1 1 0 0 0-1.4-1.4L12 10.6 9.7 8.3Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
          <div id="tooltip-cancel-2" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Cancel
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="{{ $attributes->get('class', '') }} h-2 rounded-full bg-primary-700 dark:bg-primary-600" style="width: 36%"></div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mt-4 flex items-center justify-between sm:mt-6">
        <button
          data-modal-toggle="uploadFileModal"
          type="button"
          class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          Upload files
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete modal -->
<div id="deleteFileModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteFileModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <svg class="{{ $attributes->get('class', '') }} mx-auto mb-3.5 h-10 w-10 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
          clip-rule="evenodd"
        />
      </svg>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-300">Are you sure you want to delete this item?</p>
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="deleteFileModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button type="submit" class="{{ $attributes->get('class', '') }} rounded-lg bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
          Yes, I'm sure
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Project Modal -->
<div id="editProjectModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-5xl p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Edit task</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="editProjectModal"
        >
          <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="{{ $attributes->get('class', '') }} mb-4 grid gap-5 sm:mb-5 lg:grid-cols-2">
          <div class="{{ $attributes->get('class', '') }} space-y-4">
            <div>
              <label for="title" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Title</label>
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
              <label for="description" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Description</label>
              <textarea
                id="description"
                rows="15"
                class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-left text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Write your thoughts here..."
              >
Redesigning a homepage can be a powerful way to improve user experience, engagement, and overall brand perception.
  
Here are some tips for a successful homepage redesign: User-Centric Approach:
  
Prioritize the needs and expectations of your target audience. Conduct user research, analyze user behavior, and consider feedback to understand what elements are most valuable to them.
  
Clear and Concise Messaging: Craft a compelling and succinct headline that conveys your brand's value proposition. Use clear and engaging language to communicate the primary benefits of your product or service.</textarea
              >
            </div>
          </div>
          <!-- Right Content -->
          <div>
            <div class="{{ $attributes->get('class', '') }} mb-4">
              <div class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Assignee & Communication</div>
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
                <div class="{{ $attributes->get('class', '') }} flex -space-x-4">
                  <img class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/helene-engels.png" alt="Helene Engels" />
                  <img class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/robert-brown.png" alt="Robert Brown" />
                  <img class="{{ $attributes->get('class', '') }} h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/bonnie-green.png" alt="Bonnie Green" />
                  <a class="{{ $attributes->get('class', '') }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800" href="#">+9</a>
                </div>
                <button
                  type="button"
                  class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  <svg class="{{ $attributes->get('class', '') }} -ml-1 mr-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
                  </svg>
                  Add member
                </button>
              </div>
            </div>
            <button
              type="button"
              class="{{ $attributes->get('class', '') }} mb-4 inline-flex items-center rounded-lg bg-[#4285F4] px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-[#4285F4]/90 focus:outline-none focus:ring-4 focus:ring-[#4285F4]/50 dark:focus:ring-[#4285F4]/55"
            >
              <svg class="{{ $attributes->get('class', '') }} -ml-1 mr-2 h-4 w-4" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512">
                <path
                  fill="currentColor"
                  d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"
                />
              </svg>
              Add Google Meet video conference
            </button>
            <div class="{{ $attributes->get('class', '') }} mb-4">
              <label class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="priority">Priority</label>
              <div class="{{ $attributes->get('class', '') }} flex space-x-4">
                <div class="{{ $attributes->get('class', '') }} mr-4 flex items-center">
                  <input
                    id="priority-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label for="priority-checkbox-edit" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">High</label>
                </div>
                <div class="{{ $attributes->get('class', '') }} mr-4 flex items-center">
                  <input
                    id="priority-2-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label for="priority-2-checkbox-edit" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Medium</label>
                </div>
                <div class="{{ $attributes->get('class', '') }} mr-4 flex items-center">
                  <input
                    checked=""
                    id="priority-3-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label for="priority-3-checkbox-edit" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Low</label>
                </div>
                <div class="{{ $attributes->get('class', '') }} mr-4 flex items-center">
                  <input
                    id="priority-4-checkbox-edit"
                    type="checkbox"
                    value=""
                    class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  />
                  <label for="priority-4-checkbox-edit" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Lowest</label>
                </div>
              </div>
            </div>
            <div date-rangepicker class="{{ $attributes->get('class', '') }} mb-4 flex items-center space-x-4">
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label for="startDate" class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">Start date</label>
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
                    id="startDate"
                    name="start"
                    type="text"
                    class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="Start date"
                  />
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label for="dueDate" class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">Due date</label>
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
                <label for="project-category-2" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Category
                  <button type="button" data-tooltip-target="tooltip-project-category-2" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ms-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg aria-hidden="true" class="{{ $attributes->get('class', '') }} h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">User permission details</span>
                  </button>
                  <div id="tooltip-project-category-2" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block w-72 max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-700">
                    Select the category to which this task belongs, with its help you can more efficiently categorize the team's tasks.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="project-category-2"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected>Design</option>
                  <option value="seo">SEO</option>
                  <option value="marketing">Marketing</option>
                  <option value="programming">Programming</option>
                  <option value="sales">Sales</option>
                </select>
              </div>
              <div class="{{ $attributes->get('class', '') }} w-full">
                <label for="project-type-2" class="{{ $attributes->get('class', '') }} mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Project type
                  <button data-tooltip-target="tooltip-project-type-2" data-tooltip-style="dark" type="button" class="{{ $attributes->get('class', '') }} ms-1 text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Show information</span>
                  </button>
                  <div id="tooltip-project-type-2" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block w-72 max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-700">
                    Select the type of task, with its help you can more efficiently categorize the team's tasks.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="project-type-2"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected>Sprint</option>
                  <option value="decision">Decision</option>
                  <option value="finance">Finance</option>
                  <option value="problems">Problem-solving</option>
                </select>
              </div>
            </div>
            <div>
              <div class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Deadline notification</div>
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
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
          <button
            type="submit"
            class="{{ $attributes->get('class', '') }} inline-flex items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            Update project
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

<!-- Archive file modal -->
<div id="archiveFileModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="archiveFileModal"
      >
        <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <svg class="{{ $attributes->get('class', '') }} mx-auto mb-3.5 h-10 w-10 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
        <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
      </svg>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Do you want to archive this file?</p>
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="archiveFileModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} flex items-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          <svg class="{{ $attributes->get('class', '') }} -ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5" />
          </svg>
          Archive this file
        </button>
      </div>
    </div>
  </div>
</div>
