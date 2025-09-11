{{-- Flowbite Component: api --}}
@props(['title' => 'Tailwind CSS API Keys - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="grid grid-cols-12 gap-4 bg-white dark:bg-gray-900">
  <div class="relative col-span-full">
    <div class="px-4 pt-4">
      <div class="flex flex-col space-y-4 border-b border-gray-200 pb-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:space-x-4 sm:space-y-0">
        <div>
          <nav class="mb-4 flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
              <li class="inline-flex items-center">
                <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
                  <svg class="me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
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
                <div class="flex items-center">
                  <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                  </svg>
                  <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">API</a>
                </div>
              </li>
              <li aria-current="page">
                <div class="flex items-center">
                  <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                  </svg>
                  <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">API Keys</span>
                </div>
              </li>
            </ol>
          </nav>
          <div class="flex flex-1 items-center space-x-2">
            <h5 class="text-xl font-semibold text-gray-900 dark:text-white">My API Keys</h5>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <button
            type="button"
            id="viewSerpButton"
            data-modal-target="viewSerpModal"
            data-modal-toggle="viewSerpModal"
            class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>

            View SERP
          </button>
          <button
            class="inline-flex w-full items-center justify-center rounded-lg border border-primary-700 bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:border-primary-800 hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-600 dark:bg-primary-600 dark:hover:border-primary-700 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
            type="button"
            id="generateKeyButton"
            data-modal-target="generateKeyModal"
            data-modal-toggle="generateKeyModal"
          >
            Generate Key
          </button>
        </div>
      </div>
      <div class="flex flex-col items-stretch justify-between space-y-3 py-4 sm:flex-row sm:items-center sm:space-y-0">
        <div class="block items-center space-y-2 sm:flex sm:space-y-0">
          <div class="shrink-0 font-semibold dark:text-white">Filter by:</div>
          <div class="flex items-center">
            <select
              id="compare"
              class="mr-4 block w-full min-w-40 rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:mx-4 lg:hidden"
            >
              <option selected>Active</option>
              <option value="inactive">Inactive</option>
              <option value="renewal_date">Renewal Date</option>
            </select>
            <div class="mx-4 hidden w-full flex-col rounded-md shadow-xs md:w-auto md:flex-row lg:inline-flex" role="group">
              <button
                type="button"
                class="rounded-t-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:ring-2 focus:ring-primary-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-primary-500 md:rounded-l-lg md:rounded-tr-none"
              >
                Active
              </button>
              <button
                type="button"
                class="border-x border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:ring-2 focus:ring-primary-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-primary-500 md:border-x-0 md:border-b md:border-t"
              >
                Inactive
              </button>
              <button
                type="button"
                class="rounded-r-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:text-primary-700 focus:ring-2 focus:ring-primary-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-primary-500"
              >
                Renewal Date
              </button>
            </div>
            <a href="#" class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500">
              <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd" />
              </svg>
              More options
            </a>
          </div>
        </div>
        <button
          id="actionsDropdownButton"
          data-dropdown-toggle="actionsDropdown"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
          type="button"
        >
          Bulk Actions
          <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <div id="actionsDropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="actionsDropdownButton">
            <li>
              <button class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Activate all
              </button>
            </li>
            <li>
              <button class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
                </svg>
                Deactivate all
              </button>
            </li>
            <li>
              <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
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
    <div class="overflow-x-auto relative">
      <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
          <tr>
            <th scope="col" class="p-4">
              <div class="flex items-center">
                <input
                  id="checkbox-all"
                  type="checkbox"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-all" class="sr-only">checkbox</label>
              </div>
            </th>
            <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">Platform</th>
            <th scope="col" class="w-96 px-4 py-3 font-semibold whitespace-nowrap">Api Key</th>
            <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">Renewal date</th>
            <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Status</th>
            <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">Disable/Enable</th>
            <th scope="col" class="px-4 py-3 font-semibold">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-1"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-1" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Amazon AWS</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="amazon-key-copy-button" class="sr-only">Label</label>
                <input
                  id="amazon-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="amazon-key-copy-button"
                  data-tooltip-target="tooltip-copy-amazon-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-amazon-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">20 Feb 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-2"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-2" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Mailchimp</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="mailchimp-key-copy-button" class="sr-only">Label</label>
                <input
                  id="mailchimp-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="mailchimp-key-copy-button"
                  data-tooltip-target="tooltip-copy-mailchimp-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-mailchimp-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">20 Feb 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" checked />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-3"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-3" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Upwork</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="upwork-key-copy-button" class="sr-only">Label</label>
                <input
                  id="upwork-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="upwork-key-copy-button"
                  data-tooltip-target="tooltip-copy-upwork-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-upwork-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">31 Mar 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-4"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-4" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Google</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="google-key-copy-button" class="sr-only">Label</label>
                <input
                  id="google-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="google-key-copy-button"
                  data-tooltip-target="tooltip-copy-google-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-google-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">07 Apr 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14c-.5-.6-.9-1.3-1-2 0-1 4-6 9-6m7.6 3.8A5 5 0 0 1 21 12c0 1-3 6-9 6h-1m-6 1L19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Inactive
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" checked />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-5"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-5" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Facebook</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="facebook-key-copy-button" class="sr-only">Label</label>
                <input
                  id="facebook-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="facebook-key-copy-button"
                  data-tooltip-target="tooltip-copy-facebook-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-facebook-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">15 Apr 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" checked />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-6"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-6" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Twitter</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="twitter-key-copy-button" class="sr-only">Label</label>
                <input
                  id="twitter-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="twitter-key-copy-button"
                  data-tooltip-target="tooltip-copy-twitter-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-twitter-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">01 May 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-7"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-7" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Zapier</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="zapier-key-copy-button" class="sr-only">Label</label>
                <input
                  id="zapier-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="zapier-key-copy-button"
                  data-tooltip-target="tooltip-copy-zapier-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-zapier-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">15 May 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-8"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-8" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Algolia</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="algolia-key-copy-button" class="sr-only">Label</label>
                <input
                  id="algolia-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="algolia-key-copy-button"
                  data-tooltip-target="tooltip-copy-algolia-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-algolia-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">30 May 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14c-.5-.6-.9-1.3-1-2 0-1 4-6 9-6m7.6 3.8A5 5 0 0 1 21 12c0 1-3 6-9 6h-1m-6 1L19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Inactive
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-9"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-9" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Node JS</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="node-key-copy-button" class="sr-only">Label</label>
                <input
                  id="node-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="node-key-copy-button"
                  data-tooltip-target="tooltip-copy-node-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-node-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">30 May 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14c-.5-.6-.9-1.3-1-2 0-1 4-6 9-6m7.6 3.8A5 5 0 0 1 21 12c0 1-3 6-9 6h-1m-6 1L19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Inactive
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" checked />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-10"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-10" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Flowbite</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="flowbite-key-copy-button" class="sr-only">Label</label>
                <input
                  id="flowbite-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="flowbite-key-copy-button"
                  data-tooltip-target="tooltip-copy-flowbite-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-flowbite-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">01 Jul 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" checked />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-11"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-11" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Asana</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="asana-key-copy-button" class="sr-only">Label</label>
                <input
                  id="asana-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="asana-key-copy-button"
                  data-tooltip-target="tooltip-copy-asana-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-asana-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">15 Jul 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-12"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-12" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Salesforce</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="salesforce-key-copy-button" class="sr-only">Label</label>
                <input
                  id="salesforce-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="salesforce-key-copy-button"
                  data-tooltip-target="tooltip-copy-salesforce-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-salesforce-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">30 Jul 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" checked />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-13"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-13" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Bitbucket</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="bitbucket-key-copy-button" class="sr-only">Label</label>
                <input
                  id="bitbucket-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="bitbucket-key-copy-button"
                  data-tooltip-target="tooltip-copy-bitbucket-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-bitbucket-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">01 Aug 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-14"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-14" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Jira</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="jira-key-copy-button" class="sr-only">Label</label>
                <input
                  id="jira-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="jira-key-copy-button"
                  data-tooltip-target="tooltip-copy-jira-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-jira-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">01 Sep 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14c-.5-.6-.9-1.3-1-2 0-1 4-6 9-6m7.6 3.8A5 5 0 0 1 21 12c0 1-3 6-9 6h-1m-6 1L19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Inactive
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="w-4 px-4 py-3">
              <div class="flex items-center">
                <input
                  id="checkbox-table-search-15"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-15" class="sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Instagram</th>
            <td class="px-4 py-2">
              <div class="relative">
                <label for="instagram-key-copy-button" class="sr-only">Label</label>
                <input
                  id="instagram-key-copy-button"
                  type="text"
                  class="col-span-6 block w-[356px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  value="3aec8277-da8a-44cd-9249-031519c5a521"
                  disabled
                  readonly
                />
                <button
                  data-copy-to-clipboard-target="instagram-key-copy-button"
                  data-tooltip-target="tooltip-copy-instagram-key-button"
                  class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                  <span data-tooltip-default-icon>
                    <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                      <path
                        d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                      />
                    </svg>
                  </span>
                  <span data-tooltip-success-icon class="hidden">
                    <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                    </svg>
                  </span>
                </button>
                <div
                  id="tooltip-copy-instagram-key-button"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  <span data-tooltip-default-message>Copy to clipboard</span>
                  <span data-tooltip-success-message class="hidden">Copied!</span>
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-500 dark:text-gray-400">15 Sep 2025</td>
            <td class="whitespace-nowrap px-4 py-2">
              <span class="inline-flex items-center rounded-sm bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9" />
                </svg>
                Active
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" value="" class="peer sr-only" name="promote" />
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-600 dark:peer-focus:ring-primary-800"
                ></div>
              </label>
            </td>
            <td class="space-x-2 whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Regenerate
                </button>
                <button
                  class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                  id="editKeyButton"
                  data-modal-target="editKeyModal"
                  data-modal-toggle="editKeyModal"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
                  </svg>
                  Edit
                </button>
                <button
                  type="button"
                  id="deleteButton"
                  data-modal-target="deleteModal"
                  data-modal-toggle="deleteModal"
                  class="inline-flex rounded-lg bg-red-700 px-3 py-2 text-xs font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <nav class="flex flex-col items-start justify-between space-y-3 p-4 md:flex-row md:items-center md:space-y-0" aria-label="Table navigation">
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
          Showing
          <span class="font-semibold text-gray-900 dark:text-white">1-15</span>
          of
          <span class="font-semibold text-gray-900 dark:text-white">235</span>
        </span>
        <ul class="inline-flex items-stretch -space-x-px">
          <li>
            <a
              href="#"
              class="ml-0 flex h-full items-center justify-center rounded-l-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <span class="sr-only">Previous</span>
              <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </a>
          </li>
          <li>
            <a
              href="#"
              class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              1
            </a>
          </li>
          <li>
            <a
              href="#"
              class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              2
            </a>
          </li>
          <li>
            <a
              href="#"
              aria-current="page"
              class="z-10 flex items-center justify-center border border-primary-300 bg-primary-50 px-3 py-2 text-sm leading-tight text-primary-600 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white"
            >
              3
            </a>
          </li>
          <li>
            <a
              href="#"
              class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              ...
            </a>
          </li>
          <li>
            <a
              href="#"
              class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              10
            </a>
          </li>
          <li>
            <a
              href="#"
              class="flex h-full items-center justify-center rounded-r-lg border border-gray-300 bg-white px-3 py-1.5 leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <span class="sr-only">Next</span>
              <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<!-- Generate key modal -->
<div id="generateKeyModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-lg p-4 lg:p-0">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <div class="flex items-center justify-between px-4 py-4 sm:px-5">
        <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">Generate API key</h3>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="generateKeyModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <div class="px-4 pb-4 sm:px-5 sm:pb-5">
        <p class="text-sm text-gray-500 dark:text-gray-400 sm:text-base">To connect securely to Flowbite Web Services, your tool or application needs an API key with permission to access resources such as Flowbite S3 bucket.</p>
        <form action="#">
          <div class="my-4">
            <label for="app-name-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Name your application</label>
            <input
              type="text"
              id="app-name-input"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              required
              placeholder="Application name"
            />
            <p id="helper-app-name" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Providing the application name will help you identify you API key later.</p>
          </div>
          <div class="flex items-center space-x-4">
            <button
              type="button"
              data-modal-toggle="generateKeyModal"
              class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="w-full rounded-lg border border-primary-700 bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-600 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              Generate API key
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit key modal -->
<div id="editKeyModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-lg p-4 lg:p-0">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <div class="flex items-center justify-between px-4 py-4 sm:px-5">
        <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">Edit API key</h3>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="editKeyModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <div class="px-4 pb-4 sm:px-5 sm:pb-5">
        <p class="text-sm text-gray-500 dark:text-gray-400 sm:text-base">To connect securely to Flowbite Web Services, your tool or application needs an API key with permission to access resources such as Flowbite S3 bucket.</p>
        <form action="#">
          <div class="mb-4 mt-2">
            <label for="app-key-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Your Amazon AWS API key</label>
            <div class="flex items-center">
              <input
                type="text"
                id="app-key-input"
                class="block w-full rounded-s-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                required
                value="3aec8277-da8a-44cd-9249-031519c5a521"
                placeholder="Application name"
              />
              <button
                type="submit"
                class="end-0 top-0 h-full rounded-e-lg border border-primary-700 bg-primary-700 p-3 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
              >
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.7 7.7A7.1 7.1 0 0 0 5 10.8M18 4v4h-4m-7.7 8.3A7.1 7.1 0 0 0 19 13.2M6 20v-4h4" />
                </svg>
              </button>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <button
              type="button"
              data-modal-toggle="editKeyModal"
              class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              Cancel
            </button>
            <button
              type="button"
              class="w-full rounded-lg border border-primary-700 bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:border-primary-800 hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-600 dark:bg-primary-600 dark:hover:border-primary-700 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- View SERP -->
<div id="viewSerpModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-4xl p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-5">
      <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700 md:mb-5 md:pb-5">
        <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">SERP</h3>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="viewSerpModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>

      <div class="relative overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400 rtl:text-right">
          <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="rounded-l-md px-6 py-3"><span class="sr-only">Position</span></th>
              <th scope="col" class="px-6 py-3">Results</th>
              <th scope="col" class="px-6 py-3">DA</th>
              <th scope="col" class="px-6 py-3">PA</th>
              <th scope="col" class="whitespace-nowrap rounded-r-md px-6 py-3">ED</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b bg-white text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
              <td class="px-6 py-4">1</td>
              <th scope="row" class="whitespace-nowrap px-6 py-4">
                <div class="space-y-0.5">
                  <a href="#" class="block font-medium text-primary-700 hover:underline dark:text-primary-500">Top 10 Tailwind CSS Libraries for 2025 & Design Assets</a>
                  <a href="#" class="block text-sm font-normal text-gray-500 hover:underline dark:text-gray-400">https://flowbite.com/blocks/marketing/hero/</a>
                </div>
              </th>
              <td class="px-6 py-4">95</td>
              <td class="px-6 py-4">54</td>
              <td class="px-6 py-4">14,535</td>
            </tr>
            <tr class="border-b bg-white text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
              <td class="px-6 py-4">2</td>
              <th scope="row" class="whitespace-nowrap px-6 py-4">
                <div class="space-y-0.5">
                  <a href="#" class="block font-medium text-primary-700 hover:underline dark:text-primary-500">Flowbite CSS - A Responsive Frontend Framework</a>
                  <a href="#" class="block text-sm font-normal text-gray-500 hover:underline dark:text-gray-400">https://flowbite.com/docs/components/bottom-navigation/</a>
                </div>
              </th>
              <td class="px-6 py-4">93</td>
              <td class="px-6 py-4">59</td>
              <td class="px-6 py-4">12,950</td>
            </tr>
            <tr class="border-b bg-white text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
              <td class="px-6 py-4">3</td>
              <th scope="row" class="whitespace-nowrap px-6 py-4">
                <div class="space-y-0.5">
                  <a href="#" class="block font-medium text-primary-700 hover:underline dark:text-primary-500">Best Practices for Integrating CSS Libraries into Your Project</a>
                  <a href="#" class="block text-sm font-normal text-gray-500 hover:underline dark:text-gray-400">https://flowbite.com/docs/getting-started/react/</a>
                </div>
              </th>
              <td class="px-6 py-4">90</td>
              <td class="px-6 py-4">41</td>
              <td class="px-6 py-4">8,946</td>
            </tr>
            <tr class="border-b bg-white text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
              <td class="px-6 py-4">4</td>
              <th scope="row" class="whitespace-nowrap px-6 py-4">
                <div class="space-y-0.5">
                  <a href="#" class="block font-medium text-primary-700 hover:underline dark:text-primary-500">Most Popular UI Libraries for Modern Web Design</a>
                  <a href="#" class="block text-sm font-normal text-gray-500 hover:underline dark:text-gray-400">https://flowbite.com/blog/e-commerce-ui-has-been-launched/</a>
                </div>
              </th>
              <td class="px-6 py-4">89</td>
              <td class="px-6 py-4">52</td>
              <td class="px-6 py-4">5,394</td>
            </tr>
            <tr class="border-b bg-white text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
              <td class="px-6 py-4">5</td>
              <th scope="row" class="whitespace-nowrap px-6 py-4">
                <div class="space-y-0.5">
                  <a href="#" class="block font-medium text-primary-700 hover:underline dark:text-primary-500">Best CSS Frameworks and Libraries for Responsive Design</a>
                  <a href="#" class="block text-sm font-normal text-gray-500 hover:underline dark:text-gray-400">https://flowbite.com/blocks/marketing/hero/</a>
                </div>
              </th>
              <td class="px-6 py-4">87</td>
              <td class="px-6 py-4">48</td>
              <td class="px-6 py-4">1,027</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex items-center space-x-4 md:mt-5">
        <button
          type="button"
          data-modal-toggle="viewSerpModal"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Close
        </button>
        <button
          type="button"
          class="flex items-center rounded-lg border border-primary-700 bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-600 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v9.293l-2-2a1 1 0 0 0-1.414 1.414l.293.293h-6.586a1 1 0 1 0 0 2h6.586l-.293.293A1 1 0 0 0 18 16.707l2-2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
              clip-rule="evenodd"
            />
          </svg>

          Export to Excel
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete key modal -->
<div id="deleteModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-lg p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <div class="flex items-center justify-between px-4 py-4 sm:px-5">
        <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">Are you sure?</h3>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="deleteModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <p class="border-b border-t border-orange-200 bg-orange-100 px-4 py-4 font-light text-orange-700 dark:border-gray-600 dark:bg-gray-700 dark:text-orange-300 sm:px-5">This action <span class="font-semibold">CANNOT</span> be undone.</p>
      <div class="px-4 py-4 sm:px-5 sm:py-5">
        <p class="mb-4 font-light text-gray-500 dark:text-gray-400 sm:mb-5">
          This will permanently delete the <span class="font-semibold text-gray-900 dark:text-white">bergside/amazon-aws key</span> and everything associated with this key will be permanently deleteled.
        </p>
        <button
          type="submit"
          class="w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
        >
          I understand, delete this key
        </button>
      </div>
    </div>
  </div>
</div>
</div>
