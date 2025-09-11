{{-- Flowbite Component: calendar --}}
@props(['title' => 'Tailwind CSS Calendar - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="relative bg-white dark:bg-gray-900">
  <div id="calendar"></div>
</div>

<!-- drawer component -->
<div id="createEventDrawer" class="fixed right-0 top-0 z-40 h-screen w-80 translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800" tabindex="-1" aria-labelledby="createEventDrawerLabel" aria-hidden="true">
  <h5 id="createEventDrawerLabel" class="mb-6 inline-flex items-center text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">New Event</h5>
  <button
    type="button"
    data-drawer-dismiss="createEventDrawer"
    aria-controls="createEventDrawer"
    class="absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
      <path
        fill-rule="evenodd"
        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
        clip-rule="evenodd"
      ></path>
    </svg>
    <span class="sr-only">Close menu</span>
  </button>
  <form action="#" id="newEventForm">
    <div class="space-y-4">
      <div>
        <label for="title" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Title</label>
        <input
          type="text"
          name="new-title"
          id="new-title"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Add title here"
          required=""
        />
      </div>
      <div>
        <label for="description" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Description</label>
        <textarea
          id="new-description"
          name="new-description"
          rows="4"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Enter event description here"
        ></textarea>
      </div>
      <div>
        <label for="new-start-date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Start date</label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <input
            datepicker
            datepicker-format="yyyy-mm-dd"
            id="new-start-date"
            name="new-start-date"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Select date"
            required=""
            datepicker-autohide
          />
        </div>
      </div>
      <div>
        <label for="new-end-date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">End date</label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <input
            datepicker
            datepicker-format="yyyy-mm-dd"
            id="new-end-date"
            name="new-end-date"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Select date"
            required=""
            datepicker-autohide
          />
        </div>
      </div>
      <div>
        <div class="mr-4 flex items-center">
          <input
            data-collapse-toggle="new-time-range-container"
            id="select-new-time-range-container"
            type="checkbox"
            value=""
            name="select-new-time-range-container"
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="select-new-time-range-container" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Add time</label>
        </div>
        <div id="new-time-range-container" class="mt-2 grid hidden w-full grid-cols-2 gap-4">
          <div>
            <label for="new-start-time" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Start time:</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 end-0 top-0 flex items-center pe-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
                </svg>
              </div>
              <input
                type="time"
                id="new-start-time"
                name="new-start-time"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm leading-none text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                value="00:00"
                required
              />
            </div>
          </div>
          <div>
            <label for="new-end-time" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">End time:</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 end-0 top-0 flex items-center pe-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
                </svg>
              </div>
              <input
                type="time"
                id="new-end-time"
                name="new-end-time"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm leading-none text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                value="00:00"
                required
              />
            </div>
          </div>
        </div>
      </div>
      <div>
        <label for="new-location" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Location</label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M11.906 1.994a8.002 8.002 0 0 1 8.09 8.421 7.996 7.996 0 0 1-1.297 3.957.996.996 0 0 1-.133.204l-.108.129c-.178.243-.37.477-.573.699l-5.112 6.224a1 1 0 0 1-1.545 0L5.982 15.26l-.002-.002a18.146 18.146 0 0 1-.309-.38l-.133-.163a.999.999 0 0 1-.13-.202 7.995 7.995 0 0 1 6.498-12.518ZM15 9.997a3 3 0 1 1-5.999 0 3 3 0 0 1 5.999 0Z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <input
            type="text"
            id="new-location"
            name="new-location"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter location"
          />
        </div>
      </div>
      <div>
        <label for="add-guests" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Add guests</label>
        <div class="relative">
          <input
            type="search"
            id="add-guests"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-4 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Add guest email"
          />
          <button
            type="button"
            class="absolute bottom-2.5 right-2.5 inline-flex items-center rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg aria-hidden="true" class="-ml-0.5 mr-0.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
            </svg>
            Add
          </button>
        </div>
        <div class="mb-5 mt-2 flex space-x-1">
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/helene-engels.png" alt="helene avatar" />
            <a href="#" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </a>
          </div>
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/bonnie-green.png" alt="bonnie avatar" />
            <button type="button" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </button>
          </div>
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/robert-brown.png" alt="robert avatar" />
            <button type="button" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </button>
          </div>
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/jese-leos.png" alt="jese avatar" />
            <button type="button" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </button>
          </div>
          <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800 dark:bg-gray-600">+7</button>
        </div>
      </div>
      <div>
        <div class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tag Color</div>
        <div class="flex items-center space-x-2">
          <button type="button" class="h-6 w-6 rounded-xs bg-purple-500 ring-2 ring-transparent data-[color-selected=true]:ring-purple-300" data-color-picker-target="newColorsInput" data-color-picker-value="purple"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-indigo-500 ring-2 ring-transparent data-[color-selected=true]:ring-indigo-300" data-color-picker-target="newColorsInput" data-color-picker-value="indigo"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-primary-600 ring-2 ring-transparent data-[color-selected=true]:ring-primary-300" data-color-picker-target="newColorsInput" data-color-picker-value="primary"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-pink-500 ring-2 ring-transparent data-[color-selected=true]:ring-pink-300" data-color-picker-target="newColorsInput" data-color-picker-value="pink"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-teal-400 ring-2 ring-transparent data-[color-selected=true]:ring-teal-300" data-color-picker-target="newColorsInput" data-color-picker-value="teal"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-green-400 ring-2 ring-transparent data-[color-selected=true]:ring-green-300" data-color-picker-target="newColorsInput" data-color-picker-value="green"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-yellow-300 ring-2 ring-transparent data-[color-selected=true]:ring-yellow-300" data-color-picker-target="newColorsInput" data-color-picker-value="yellow"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-orange-400 ring-2 ring-transparent data-[color-selected=true]:ring-orange-300" data-color-picker-target="newColorsInput" data-color-picker-value="orange"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-red-500 ring-2 ring-transparent data-[color-selected=true]:ring-red-300" data-color-picker-target="newColorsInput" data-color-picker-value="red"></button>
        </div>
      </div>
      <input id="newColorsInput" name="new-color" type="hidden" value="primary-600" />
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="guest-permission-checkbox">Guest Permissions</label>
        <div class="space-y-3">
          <div class="mr-4 flex items-center">
            <input
              id="guest-permission-1-checkbox"
              type="checkbox"
              value=""
              name="guest-permission-checkbox"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="guest-permission-1-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Modify event</label>
          </div>
          <div class="mr-4 flex items-center">
            <input
              id="guest-permission-2-checkbox"
              type="checkbox"
              value=""
              name="guest-permission-checkbox"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="guest-permission-2-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Invite others</label>
          </div>
          <div class="mr-4 flex items-center">
            <input
              checked=""
              id="guest-permission-3-checkbox"
              type="checkbox"
              value=""
              name="guest-permission-checkbox"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="guest-permission-3-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">See guest list</label>
          </div>
        </div>
      </div>
    </div>
    <div class="flex w-full justify-center space-x-3 pt-4 sm:mt-0">
      <button
        type="submit"
        class="w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Add event
      </button>
      <button
        type="button"
        data-drawer-dismiss="createEventDrawer"
        aria-controls="createEventDrawer"
        class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
      >
        Close
      </button>
    </div>
  </form>
</div>

<div
  id="readEventModal"
  data-event-id=""
  data-modal-target="readEventModal"
  tabindex="-1"
  aria-hidden="true"
  class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0"
>
  <div class="relative max-h-full w-full max-w-4xl p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between rounded-t border-b border-gray-200 pb-4 dark:border-gray-700 sm:mb-5">
        <h3 class="font-semibold text-gray-900 dark:text-white" id="readEventTitle"></h3>
        <button type="button" class="inline-flex rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="readEventModal">
          <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            ></path>
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <div class="mb-4 grid gap-4 sm:mb-5 sm:grid-cols-2 sm:gap-6">
        <dl class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700">
          <dt class="sr-only">Date</dt>
          <dd class="mb-2 flex items-center font-light text-gray-500 dark:text-gray-400">
            <svg class="mr-1.5 h-4 w-4 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="font-medium text-gray-900 dark:text-white" id="readEventStartDate"></span>
            <span class="font-medium text-gray-900 dark:text-white" id="readEventEndDate"></span>
          </dd>
          <dt class="sr-only">Location</dt>
          <dd class="mb-4 flex items-center font-light text-gray-500 dark:text-gray-400 sm:mb-5">
            <svg class="me-1.5 h-4 w-4 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M11.906 1.994a8.002 8.002 0 0 1 8.09 8.421 7.996 7.996 0 0 1-1.297 3.957.996.996 0 0 1-.133.204l-.108.129c-.178.243-.37.477-.573.699l-5.112 6.224a1 1 0 0 1-1.545 0L5.982 15.26l-.002-.002a18.146 18.146 0 0 1-.309-.38l-.133-.163a.999.999 0 0 1-.13-.202 7.995 7.995 0 0 1 6.498-12.518ZM15 9.997a3 3 0 1 1-5.999 0 3 3 0 0 1 5.999 0Z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="font-medium text-gray-900 dark:text-white" id="readEventLocation">No location</span>
          </dd>
          <dt class="mb-2 leading-none text-gray-500 dark:text-gray-400">Participants</dt>
          <dd class="mb-4 flex -space-x-4 sm:mb-5">
            <img class="h-8 w-8 rounded-full border-2 border-gray-50 dark:border-gray-700" src="/images/users/helene-engels.png" alt="Helene Engels" />
            <img class="h-8 w-8 rounded-full border-2 border-gray-50 dark:border-gray-700" src="/images/users/robert-brown.png" alt="Robert Brown" />
            <img class="h-8 w-8 rounded-full border-2 border-gray-50 dark:border-gray-700" src="/images/users/bonnie-green.png" alt="Bonnie Green" />
            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-gray-50 bg-gray-600 text-xs font-medium text-white hover:bg-gray-500 dark:border-gray-700">+9</button>
          </dd>
          <dt class="mb-2 leading-none text-gray-500 dark:text-gray-400">Online</dt>
          <dd class="mb-4 font-medium text-gray-900 dark:text-white sm:mb-5">Google Meet</dd>
          <dt class="mb-2 leading-none text-gray-500 dark:text-gray-400">Duration</dt>
          <dd class="font-medium text-gray-900 dark:text-white" id="readEventTime">All day</dd>
        </dl>
        <dl>
          <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">Details</dt>
          <dd class="text-gray-500 dark:text-gray-400" id="readEventDescription">No description.</dd>
        </dl>
      </div>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 sm:space-x-4">
          <button
            type="button"
            id="updateEventDrawerButton"
            data-drawer-target="updateEventDrawer"
            data-drawer-show="updateEventDrawer"
            data-modal-hide="readEventModal"
            aria-controls="updateEventDrawer"
            data-drawer-placement="right"
            class="inline-flex items-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg class="-ms-0.5 me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
            class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            Preview
          </button>
        </div>
        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
          id="deleteEventButton"
          data-modal-target="deleteEventModal"
          data-modal-toggle="deleteEventModal"
        >
          <svg class="-ms-0.5 me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
  </div>
</div>

<div id="deleteEventModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteEventModal"
      >
        <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
          <path
            fill-rule="evenodd"
            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
            clip-rule="evenodd"
          ></path>
        </svg>
        <span class="sr-only">Close modal</span>
      </button>
      <svg class="mx-auto mb-3.5 h-11 w-11 text-gray-400 dark:text-gray-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path
          fill-rule="evenodd"
          d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
          clip-rule="evenodd"
        ></path>
      </svg>
      <p class="mb-4 text-gray-500 dark:text-gray-300">Are you sure you want to delete this item?</p>
      <div class="flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="deleteEventModal"
          type="button"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
        >
          No, cancel
        </button>
        <button
          id="confirmEventDeleteButton"
          class="rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
        >
          Yes, I'm sure
        </button>
      </div>
    </div>
  </div>
</div>

<!-- drawer component -->
<div id="updateEventDrawer" class="fixed right-0 top-0 z-40 h-screen w-80 translate-x-full overflow-y-auto bg-white p-4 transition-transform dark:bg-gray-800" tabindex="-1" aria-labelledby="updateEventDrawerLabel" aria-hidden="true">
  <h5 id="updateEventDrawerLabel" class="mb-6 inline-flex items-center text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">Update Event</h5>
  <button
    type="button"
    data-drawer-dismiss="updateEventDrawer"
    aria-controls="updateEventDrawer"
    class="absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
      <path
        fill-rule="evenodd"
        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
        clip-rule="evenodd"
      ></path>
    </svg>
    <span class="sr-only">Close menu</span>
  </button>
  <form action="#" id="updateEventForm">
    <div class="space-y-4">
      <div>
        <label for="update-title" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Title</label>
        <input
          type="text"
          name="update-title"
          id="update-title"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          value=""
          placeholder="Add title here"
          required=""
        />
      </div>
      <div>
        <label for="update-description" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Description</label>
        <textarea
          id="update-description"
          name="update-description"
          rows="6"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          placeholder="Enter event description here..."
        ></textarea>
      </div>
      <div>
        <label for="update-start-date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Start date</label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg aria-hidden="true" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <input
            datepicker
            id="update-start-date"
            name="update-start-date"
            datepicker-format="yyyy-mm-dd"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            value=""
            placeholder="Select date"
            required
            datepicker-autohide
          />
        </div>
      </div>
      <div>
        <label for="update-end-date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">End date</label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg aria-hidden="true" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <input
            datepicker
            id="update-end-date"
            name="update-end-date"
            datepicker-format="yyyy-mm-dd"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            value=""
            placeholder="Select date"
            required
            datepicker-autohide
          />
        </div>
      </div>
      <div>
        <div class="mr-4 flex items-center">
          <input
            data-collapse-toggle="update-time-range-container"
            id="select-update-time-range-container"
            type="checkbox"
            value=""
            name="select-update-time-range-container"
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="select-update-time-range-container" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Add time</label>
        </div>
        <div id="update-time-range-container" class="mt-2 grid hidden w-full grid-cols-2 gap-4">
          <div>
            <label for="update-start-time" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Start time:</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 end-0 top-0 flex items-center pe-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
                </svg>
              </div>
              <input
                type="time"
                id="update-start-time"
                name="update-start-time"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm leading-none text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                value="00:00"
                required
              />
            </div>
          </div>
          <div>
            <label for="update-end-time" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">End time:</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 end-0 top-0 flex items-center pe-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
                </svg>
              </div>
              <input
                type="time"
                id="update-end-time"
                name="update-end-time"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm leading-none text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                value="00:00"
                required
              />
            </div>
          </div>
        </div>
      </div>
      <div>
        <label for="location" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Location</label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg aria-hidden="true" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <input
            type="text"
            id="update-location"
            name="update-location"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            value=""
            placeholder="Enter location"
          />
        </div>
      </div>
      <div>
        <label for="add-guests" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Add guests</label>
        <div class="relative">
          <input
            type="search"
            id="update-add-guests"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-4 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Add guest email"
          />
          <button
            type="button"
            class="absolute bottom-2.5 right-2.5 inline-flex items-center rounded-lg bg-primary-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg aria-hidden="true" class="-ml-0.5 mr-0.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
            </svg>
            Add
          </button>
        </div>
        <div class="mb-5 mt-2 flex space-x-1">
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/helene-engels.png" alt="helene avatar" />
            <a href="#" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </a>
          </div>
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/bonnie-green.png" alt="bonnie avatar" />
            <button type="button" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </button>
          </div>
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/robert-brown.png" alt="robert avatar" />
            <button type="button" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </button>
          </div>
          <div class="relative">
            <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/jese-leos.png" alt="jese avatar" />
            <button type="button" class="absolute left-5 top-0 flex h-3.5 w-3.5  items-center justify-center rounded-full border-2 border-white bg-gray-400 hover:bg-gray-500 dark:border-gray-800">
              <svg aria-hidden="true" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Remove user</span>
            </button>
          </div>
          <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-medium text-white hover:bg-gray-600 dark:border-gray-800 dark:bg-gray-600">+7</button>
        </div>
      </div>
      <div>
        <div class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tag Color</div>
        <div class="flex items-center space-x-2">
          <button type="button" class="h-6 w-6 rounded-xs bg-purple-500 ring-2 ring-transparent data-[color-selected=true]:ring-purple-300" data-color-picker-target="editColorsInput" data-color-picker-value="purple"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-indigo-500 ring-2 ring-transparent data-[color-selected=true]:ring-indigo-300" data-color-picker-target="editColorsInput" data-color-picker-value="indigo"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-primary-600 ring-2 ring-transparent data-[color-selected=true]:ring-primary-300" data-color-picker-target="editColorsInput" data-color-picker-value="primary"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-pink-500 ring-2 ring-transparent data-[color-selected=true]:ring-pink-300" data-color-picker-target="editColorsInput" data-color-picker-value="pink"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-teal-400 ring-2 ring-transparent data-[color-selected=true]:ring-teal-300" data-color-picker-target="editColorsInput" data-color-picker-value="teal"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-green-400 ring-2 ring-transparent data-[color-selected=true]:ring-green-300" data-color-picker-target="editColorsInput" data-color-picker-value="green"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-yellow-300 ring-2 ring-transparent data-[color-selected=true]:ring-yellow-300" data-color-picker-target="editColorsInput" data-color-picker-value="yellow"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-orange-400 ring-2 ring-transparent data-[color-selected=true]:ring-orange-300" data-color-picker-target="editColorsInput" data-color-picker-value="orange"></button>
          <button type="button" class="h-6 w-6 rounded-xs bg-red-500 ring-2 ring-transparent data-[color-selected=true]:ring-red-300" data-color-picker-target="editColorsInput" data-color-picker-value="red"></button>
        </div>
      </div>
      <input id="editColorsInput" name="update-color" type="hidden" value="primary-600" />
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="guest-permission-checkbox">Guest Permissions</label>
        <div class="space-y-3">
          <div class="mr-4 flex items-center">
            <input
              id="guest-permission-1-checkbox"
              type="checkbox"
              value=""
              name="guest-permission-checkbox"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="guest-permission-1-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Modify event</label>
          </div>
          <div class="mr-4 flex items-center">
            <input
              id="guest-permission-2-checkbox"
              type="checkbox"
              value=""
              name="guest-permission-checkbox"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="guest-permission-2-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Invite others</label>
          </div>
          <div class="mr-4 flex items-center">
            <input
              checked=""
              id="guest-permission-3-checkbox"
              type="checkbox"
              value=""
              name="guest-permission-checkbox"
              class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="guest-permission-3-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">See guest list</label>
          </div>
        </div>
      </div>
    </div>
    <div class="flex w-full justify-center space-x-3 pt-4 sm:mt-0">
      <button
        type="submit"
        class="w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Update event
      </button>
      <button
        type="button"
        class="inline-flex w-full items-center justify-center rounded-lg border border-red-600 px-5 py-2.5 text-center text-sm font-medium text-red-600 hover:bg-red-600 hover:text-white focus:outline-none focus:ring-4 focus:ring-red-300 dark:border-red-500 dark:text-red-500 dark:hover:bg-red-600 dark:hover:text-white dark:focus:ring-red-900"
      >
        <svg aria-hidden="true" class="-ml-1 mr-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
          <path
            fill-rule="evenodd"
            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
            clip-rule="evenodd"
          ></path>
        </svg>
        Delete
      </button>
    </div>
  </form>
</div>
</div>
