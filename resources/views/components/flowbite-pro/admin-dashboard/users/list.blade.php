@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS Users Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: users
page: users
---

<div class="{{ $attributes->get('class', '') }} grid grid-cols-12 gap-4 bg-white dark:bg-gray-900">
  <div class="{{ $attributes->get('class', '') }} col-span-full mx-4 mt-4 items-center justify-between sm:flex">
    <div class="{{ $attributes->get('class', '') }} mb-4 sm:mb-0">
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
              <a href="#" class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Platform</a>
            </div>
          </li>
          <li aria-current="page">
            <div class="{{ $attributes->get('class', '') }} flex items-center">
              <svg class="{{ $attributes->get('class', '') }} mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
              </svg>
              <span class="{{ $attributes->get('class', '') }} ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Users</span>
            </div>
          </li>
        </ol>
      </nav>
      <h1 class="{{ $attributes->get('class', '') }} text-xl font-bold text-gray-900 dark:text-white">All Users</h1>
    </div>
    <button
      type="button"
      id="createUserButton"
      data-modal-target="createUserAccordionModal"
      data-modal-toggle="createUserAccordionModal"
      class="{{ $attributes->get('class', '') }} flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
    >
      <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
      </svg>
      Add new user
    </button>
  </div>
  <div class="{{ $attributes->get('class', '') }} relative col-span-full">
    <div class="{{ $attributes->get('class', '') }} px-4">
      <div class="{{ $attributes->get('class', '') }} grid w-full grid-cols-2 gap-4 pb-4 md:grid-cols-3 xl:grid-cols-6">
        <div class="{{ $attributes->get('class', '') }} relative">
          <div class="{{ $attributes->get('class', '') }} pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="{{ $attributes->get('class', '') }} h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
          </div>
          <input
            type="text"
            name="email"
            id="topbar-search"
            class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Search for users"
          />
        </div>
        <select
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Role</option>
          <option value="viewer">Viewer</option>
          <option value="moderator">Moderator</option>
          <option value="administrator">Administrator</option></select
        ><select
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option></select
        ><select
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Type</option>
          <option value="pro">PRO</option>
          <option value="basic">Basic</option></select
        ><select
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Rating</option>
          <option value="5">5</option>
          <option value="4">4+</option>
          <option value="3">3+</option>
          <option value="2">2+</option></select
        ><select
          class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 md:col-span-1"
        >
          <option selected="">Country</option>
          <option value="united-states">United States</option>
          <option value="germany">Germany</option>
          <option value="france">France</option>
          <option value="canada">Canada</option>
          <option value="england">England</option>
          <option value="australia">Australia</option>
        </select>
      </div>
      <div class="{{ $attributes->get('class', '') }} block w-full items-center justify-between border-t py-3 dark:border-gray-800 sm:flex border-gray-200">
        <div class="{{ $attributes->get('class', '') }} flex flex-wrap gap-4">
          <div class="{{ $attributes->get('class', '') }} flex items-center text-sm font-medium text-gray-900 dark:text-white">Show:</div>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <input
              id="all-users"
              type="radio"
              value=""
              name="show-only"
              checked=""
              class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="all-users" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">All</label>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <input
              id="sort-role"
              type="radio"
              value=""
              name="show-only"
              class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-role" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">User Role</label>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <input
              id="sort-type"
              type="radio"
              value=""
              name="show-only"
              class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-type" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Account Type</label>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <input
              id="sort-status"
              type="radio"
              value=""
              name="show-only"
              class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-status" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Status</label>
          </div>
          <div class="{{ $attributes->get('class', '') }} flex items-center">
            <input
              id="sort-rating"
              type="radio"
              value=""
              name="show-only"
              class="{{ $attributes->get('class', '') }} h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-rating" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Rating</label>
          </div>
        </div>
        <div class="{{ $attributes->get('class', '') }} mt-4 sm:mt-0">
          <button
            id="actionsDropdownButton"
            data-dropdown-toggle="actionsDropdown"
            class="{{ $attributes->get('class', '') }}  flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
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
                <button 
                type="button" 
                id="archiveAllUsersButton"
                data-modal-target="archiveAllUsersModal"
                data-modal-toggle="archiveAllUsersModal"
                class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="{{ $attributes->get('class', '') }} w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
                    <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z"/>
                  </svg>
                  Archive all
                </buttton>
              </li>
              <li>
                <button
                type="button"
                id="deleteAllUsersButton"
                data-modal-target="deleteAllUsersModal"
                data-modal-toggle="deleteAllUsersModal"
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
    <div class="{{ $attributes->get('class', '') }} overflow-x-auto">
      <table class="{{ $attributes->get('class', '') }} w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="{{ $attributes->get('class', '') }} bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
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
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">User</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">User Role</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">Email</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Account Type</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">Rating</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Country</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-semibold">Status</th>
            <th scope="col" class="{{ $attributes->get('class', '') }} px-4 py-3 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-10.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton16" data-modal-toggle="readUserModal">Jese Leos</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-1" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M8 10V7a4 4 0 1 1 8 0v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7c0-1.1.9-2 2-2h1Zm2-3a2 2 0 1 1 4 0v3h-4V7Zm2 6c.6 0 1 .4 1 1v3a1 1 0 1 1-2 0v-3c0-.6.4-1 1-1Z" clip-rule="evenodd" />
                </svg>
                Administrator
              </div>
              <div
                id="tooltip-role-1"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Administrator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Administrators are responsible for managing the entire system.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">jese@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                4.7
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">United States</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-1-dropdown-button"
                type="button"
                data-dropdown-toggle="user-1-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-1-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-1-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                    <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-1.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton17" data-modal-toggle="readUserModal">Bonnie Green</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-2" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-2"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">bonnie@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                3.9
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">United States</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-2-dropdown-button"
                type="button"
                data-dropdown-toggle="user-2-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-2-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-2-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton2"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton2"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton2"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-2.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton18" data-modal-toggle="readUserModal">Leslie Livingston</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-3" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M14 4.2a4.1 4.1 0 0 1 5.8 0 4 4 0 0 1 0 5.7l-1.3 1.3-5.8-5.7L14 4.2Zm-2.7 2.7-5.1 5.2 2.2 2.2 5-5.2-2.1-2.2ZM5 14l-2 5.8c0 .3 0 .7.3 1 .3.3.7.4 1 .2l6-1.9L5 13.8Zm7 4 5-5.2-2.1-2.2-5.1 5.2 2.2 2.1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Moderator
              </div>
              <div
                id="tooltip-role-3"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Moderator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Moderators are responsible for the facilitation, review, and guidance of a discussion or a debate.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">leslie@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                4.8
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">United States</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-3-dropdown-button"
                type="button"
                data-dropdown-toggle="user-3-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-3-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-3-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton3"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton3"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton3"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-3.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton18" data-modal-toggle="readUserModal">Micheal Gough</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-4" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M14 4.2a4.1 4.1 0 0 1 5.8 0 4 4 0 0 1 0 5.7l-1.3 1.3-5.8-5.7L14 4.2Zm-2.7 2.7-5.1 5.2 2.2 2.2 5-5.2-2.1-2.2ZM5 14l-2 5.8c0 .3 0 .7.3 1 .3.3.7.4 1 .2l6-1.9L5 13.8Zm7 4 5-5.2-2.1-2.2-5.1 5.2 2.2 2.1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Moderator
              </div>
              <div
                id="tooltip-role-4"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Moderator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Moderators are responsible for the facilitation, review, and guidance of a discussion or a debate.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">michael@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">Basic</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                5
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">France</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-4-dropdown-button"
                type="button"
                data-dropdown-toggle="user-4-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-4-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-4-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton4"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton4"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton4"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-4.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton19" data-modal-toggle="readUserModal">Joseph McFall</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-5" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-5"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">joseph@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">Basic</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                4.2
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">England</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-5-dropdown-button"
                type="button"
                data-dropdown-toggle="user-5-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-5-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-5-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton5"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton5"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton5"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-5.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton20" data-modal-toggle="readUserModal">Robert Brown</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-6" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-6"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">robert@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">Basic</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                4.5
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Canada</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-6-dropdown-button"
                type="button"
                data-dropdown-toggle="user-6-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-6-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-6-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton6"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton6"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton6"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-6.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton21" data-modal-toggle="readUserModal">Karen Nelson</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-7" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-7"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">karen@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                4.1
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Canada</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-7-dropdown-button"
                type="button"
                data-dropdown-toggle="user-7-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-7-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-7-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton7"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton7"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton7"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-8.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton22" data-modal-toggle="readUserModal">Roberta Casas</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-8" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M14 4.2a4.1 4.1 0 0 1 5.8 0 4 4 0 0 1 0 5.7l-1.3 1.3-5.8-5.7L14 4.2Zm-2.7 2.7-5.1 5.2 2.2 2.2 5-5.2-2.1-2.2ZM5 14l-2 5.8c0 .3 0 .7.3 1 .3.3.7.4 1 .2l6-1.9L5 13.8Zm7 4 5-5.2-2.1-2.2-5.1 5.2 2.2 2.1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Moderator
              </div>
              <div
                id="tooltip-role-8"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Moderator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Moderators are responsible for the facilitation, review, and guidance of a discussion or a debate.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">karen@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                2
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Australia</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-8-dropdown-button"
                type="button"
                data-dropdown-toggle="user-8-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-8-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-8-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton8"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton8"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton8"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-1.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton23" data-modal-toggle="readUserModal">Bonnie Green</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-9" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-9"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">bonnie@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                3.9
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">United States</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-9-dropdown-button"
                type="button"
                data-dropdown-toggle="user-9-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-9-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-9-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton9"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton9"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton9"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
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
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-2.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton24" data-modal-toggle="readUserModal">Leslie Livingston</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-10" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M14 4.2a4.1 4.1 0 0 1 5.8 0 4 4 0 0 1 0 5.7l-1.3 1.3-5.8-5.7L14 4.2Zm-2.7 2.7-5.1 5.2 2.2 2.2 5-5.2-2.1-2.2ZM5 14l-2 5.8c0 .3 0 .7.3 1 .3.3.7.4 1 .2l6-1.9L5 13.8Zm7 4 5-5.2-2.1-2.2-5.1 5.2 2.2 2.1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Moderator
              </div>
              <div
                id="tooltip-role-10"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Moderator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Moderators are responsible for the facilitation, review, and guidance of a discussion or a debate.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">leslie@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                4.8
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">United States</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-10-dropdown-button"
                type="button"
                data-dropdown-toggle="user-10-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-10-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-10-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton10"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton10"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton10"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-11"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-11" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-3.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton25" data-modal-toggle="readUserModal">Micheal Gough</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-11" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M14 4.2a4.1 4.1 0 0 1 5.8 0 4 4 0 0 1 0 5.7l-1.3 1.3-5.8-5.7L14 4.2Zm-2.7 2.7-5.1 5.2 2.2 2.2 5-5.2-2.1-2.2ZM5 14l-2 5.8c0 .3 0 .7.3 1 .3.3.7.4 1 .2l6-1.9L5 13.8Zm7 4 5-5.2-2.1-2.2-5.1 5.2 2.2 2.1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Moderator
              </div>
              <div
                id="tooltip-role-11"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Moderator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Moderators are responsible for the facilitation, review, and guidance of a discussion or a debate.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">michael@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">Basic</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                5
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">France</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-11-dropdown-button"
                type="button"
                data-dropdown-toggle="user-11-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-11-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-11-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton11"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton11"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton11"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-12"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-12" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-4.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton26" data-modal-toggle="readUserModal">Joseph McFall</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-12" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-12"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">joseph@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">Basic</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                4.2
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">England</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                Active
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-12-dropdown-button"
                type="button"
                data-dropdown-toggle="user-12-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-12-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-12-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton12"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton12"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton12"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-13"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-13" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-5.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton27" data-modal-toggle="readUserModal">Robert Brown</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-13" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-13"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">robert@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">Basic</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
                </svg>
                4.5
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Canada</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-13-dropdown-button"
                type="button"
                data-dropdown-toggle="user-13-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-13-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-13-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton13"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton13"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton13"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-14"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-14" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-6.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton28" data-modal-toggle="readUserModal">Karen Nelson</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-14" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Viewer
              </div>
              <div
                id="tooltip-role-14"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Viewer</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Viewers are responsible for observing the content of a discussion..</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">karen@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                4.1
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Canada</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-14-dropdown-button"
                type="button"
                data-dropdown-toggle="user-14-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-14-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-14-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton14"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton14"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton14"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
          <tr class="{{ $attributes->get('class', '') }} border-b hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 border-gray-200">
            <td class="{{ $attributes->get('class', '') }} w-4 px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <input
                  id="checkbox-table-search-15"
                  type="checkbox"
                  onclick="event.stopPropagation()"
                  class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-table-search-15" class="{{ $attributes->get('class', '') }} sr-only">checkbox</label>
              </div>
            </td>
            <th scope="row" class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} mr-3 flex items-center">
                <img src="/images/users/avatar-10.png" alt="User avatar" class="{{ $attributes->get('class', '') }} mr-3 h-8 w-auto rounded-full" />
                <a href="#readUserModal" class="{{ $attributes->get('class', '') }} hover:underline" id="viewUserButton29" data-modal-toggle="readUserModal">Jese Leos</a>
              </div>
            </th>
            <td class="{{ $attributes->get('class', '') }} px-4 py-3">
              <div data-tooltip-target="tooltip-role-15" class="{{ $attributes->get('class', '') }} mr-2 inline-flex items-center rounded-sm bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="{{ $attributes->get('class', '') }} me-1 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M14 4.2a4.1 4.1 0 0 1 5.8 0 4 4 0 0 1 0 5.7l-1.3 1.3-5.8-5.7L14 4.2Zm-2.7 2.7-5.1 5.2 2.2 2.2 5-5.2-2.1-2.2ZM5 14l-2 5.8c0 .3 0 .7.3 1 .3.3.7.4 1 .2l6-1.9L5 13.8Zm7 4 5-5.2-2.1-2.2-5.1 5.2 2.2 2.1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Moderator
              </div>
              <div
                id="tooltip-role-15"
                role="tooltip"
                class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-[14rem] rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 opacity-0 shadow-xs transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <h6 class="{{ $attributes->get('class', '') }} mb-1.5 text-sm leading-none text-gray-900 dark:text-white">Moderator</h6>
                <p class="{{ $attributes->get('class', '') }} text-xs font-normal">Moderators are responsible for the facilitation, review, and guidance of a discussion or a debate.</p>
                <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow=""></div>
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">
              <div class="{{ $attributes->get('class', '') }} flex items-center space-x-1.5">karen@example.com</div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">PRO</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <svg class="{{ $attributes->get('class', '') }} mr-1 h-5 w-5 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
                </svg>
                2
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3">Australia</td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <div class="{{ $attributes->get('class', '') }} flex items-center">
                <div class="{{ $attributes->get('class', '') }} mr-2 h-2.5 w-2.5 rounded-full bg-red-500"></div>
                Inactive
              </div>
            </td>
            <td class="{{ $attributes->get('class', '') }} whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
              <button
                id="user-15-dropdown-button"
                type="button"
                data-dropdown-toggle="user-15-dropdown"
                class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="user-15-dropdown" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-15-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton15"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
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
                      type="button"
                      id="viewUserButton15"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                   <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
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
                    id="deleteUserButton15"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
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
    <nav class="{{ $attributes->get('class', '') }} flex flex-row items-center justify-between p-4" aria-label="Table navigation">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Download CSV
      </button>
      <p class="{{ $attributes->get('class', '') }} text-sm">
        <span class="{{ $attributes->get('class', '') }} font-normal text-gray-500 dark:text-gray-400">Total users:</span>
        <span class="{{ $attributes->get('class', '') }} font-semibold text-gray-900 dark:text-white">1867</span>
      </p>
    </nav>
  </div>
</div>

<!-- Edit User Modal -->
<div id="updateUserModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-2xl p-4">
    <!-- Modal content -->
    <form action="#" class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-between rounded-t px-4 py-4 sm:px-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Update user</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="updateUserModal"
        >
        <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
        </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <div id="accordion-collapse-update-user" data-accordion="collapse">
        <h2 id="accordion-collapse-heading-1">
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between bg-gray-50 px-4 py-4 text-left font-medium leading-none text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 sm:px-5"
            data-accordion-target="#accordion-collapse-body-1"
            aria-expanded="true"
            aria-controls="accordion-collapse-body-1"
          >
            <span>General Information</span>
            <svg data-accordion-icon="" class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
            </svg>
          </button>
        </h2>
        <div id="accordion-collapse-body-1" class="{{ $attributes->get('class', '') }} " aria-labelledby="accordion-collapse-heading-1">
          <div class="{{ $attributes->get('class', '') }} border-gray-200 p-4 dark:border-gray-700 sm:p-5">
            <!-- Inputs -->
            <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-2">
              <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
                <label class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="file_input">Upload avatar</label>
                <div class="{{ $attributes->get('class', '') }} w-full items-center sm:flex">
                  <img class="{{ $attributes->get('class', '') }} mb-4 h-20 w-20 rounded-full sm:mb-0 sm:mr-4" src="/images/users/helene-engels.png" alt="Helene avatar" />
                  <div class="{{ $attributes->get('class', '') }} w-full">
                    <input
                      class="{{ $attributes->get('class', '') }} w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder-gray-400"
                      aria-describedby="file_input_help"
                      id="file_input"
                      type="file"
                    />
                    <p class="{{ $attributes->get('class', '') }} mb-3 mt-1 text-xs font-normal text-gray-500 dark:text-gray-300" id="file_input_help">SVG, PNG, JPG or GIF (MAX. 800x400px).</p>
                    <div class="{{ $attributes->get('class', '') }} flex items-center space-x-2.5">
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                      >
                        <svg class="{{ $attributes->get('class', '') }} -ml-0.5 mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                          <path
                            fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Upload new picture
                      </button>
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                      >
                        Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <label for="first-name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">First Name</label>
                <input
                  type="text"
                  name="first-name"
                  id="first-name"
                  value="Bonnie"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="John"
                  required=""
                />
              </div>
              <div>
                <label for="last-name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Last Name</label>
                <input
                  type="text"
                  name="last-name"
                  id="last-name"
                  value="Green"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Doe"
                  required=""
                />
              </div>
              <div>
                <label for="email" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email</label>
                <input
                  type="email"
                  name="email"
                  id="email"
                  value="bonnie.green@company.com"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="john@company.com"
                  required=""
                />
              </div>
              <div>
                <label for="user-permissions" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  User Permissions
                  <button type="button" data-tooltip-target="tooltip-dark" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">User permission details</span>
                  </button>
                  <div id="tooltip-dark" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    User permissions, part of the overall user management process, are access granted to users to specific resources such as files, applications, networks, or devices.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="user-permissions"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Operational</option>
                  <option value="NO">Non Operational</option>
                </select>
              </div>
              <div>
                <label for="job-title" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Job Title</label>
                <input
                  type="text"
                  name="job-title"
                  id="job-title"
                  value="Back-end software engineer"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. React Developer"
                  required=""
                />
              </div>
              <div>
                <label for="languages" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Languages</label>
                <input
                  type="text"
                  name="languages"
                  id="languages"
                  value="English, German"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. English, Spanish"
                  required=""
                />
              </div>
              <div>
                <label for="account" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Account
                  <button type="button" data-tooltip-target="tooltip-account" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Account details</span>
                  </button>
                  <div id="tooltip-account" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    Choose here your account type.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="account"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">PRO Account</option>
                  <option value="DF">Default Account</option>
                </select>
              </div>
              <div>
                <label for="user-role" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  User Role
                  <button type="button" data-tooltip-target="tooltip-user-role" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">User role details</span>
                  </button>
                  <div id="tooltip-user-role" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    Flowbite provides 7 predefined roles: Owner, Admin, Editor, Contributor and Viewer. Assign the most suitable role to each user, giving them the most appropriate level of control.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="user-role"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Owner</option>
                  <option value="AD">Admin</option>
                  <option value="ED">Editor</option>
                  <option value="CO">Contributor</option>
                  <option value="VI">Viewer</option>
                </select>
              </div>
              <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
                <label for="email-status" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Email Status
                  <button type="button" data-tooltip-target="tooltip-email-status" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 h-4 w-4 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Email status details</span>
                  </button>
                  <div id="tooltip-email-status" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    As an administrator, you can view the status of a user's email. The status indicates whether a user's email is verified or not.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="email-status"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Verified</option>
                  <option value="NV">Not verified</option>
                </select>
              </div>
              <div>
                <label for="password" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Password</label>
                <input
                  type="password"
                  name="password"
                  id="password"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="•••••••••"
                  required=""
                />
              </div>
              <div>
                <label for="confirm-password" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Confirm password</label>
                <input
                  type="password"
                  name="confirm-password"
                  id="confirm-password"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="•••••••••"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
                <label class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="role">Assign Role</label>
                <div class="{{ $attributes->get('class', '') }} flex space-x-4">
                  <div class="{{ $attributes->get('class', '') }} flex items-center">
                    <input
                      id="inline-checkbox"
                      type="checkbox"
                      value=""
                      name="role"
                      class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-checkbox" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Administrator</label>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} flex items-center">
                    <input
                      id="inline-2-checkbox"
                      type="checkbox"
                      value=""
                      name="role"
                      class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-2-checkbox" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Member</label>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} flex items-center">
                    <input
                      checked=""
                      id="inline-checked-checkbox"
                      type="checkbox"
                      value=""
                      name="role"
                      class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-checked-checkbox" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Viewer</label>
                  </div>
                </div>
              </div>
              <div>
                <div class="{{ $attributes->get('class', '') }} mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</div>
                <label for="status" class="{{ $attributes->get('class', '') }} relative inline-flex cursor-pointer items-center">
                  <input type="checkbox" value="" id="status" class="{{ $attributes->get('class', '') }} peer sr-only" />
                  <div
                    class="{{ $attributes->get('class', '') }} peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800"
                  ></div>
                  <span class="{{ $attributes->get('class', '') }} ml-3 text-sm font-medium text-gray-500 dark:text-gray-300">Inactive</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <h2 id="accordion-collapse-heading-2">
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-4 text-left font-medium leading-none text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 sm:px-5"
            data-accordion-target="#accordion-collapse-body-2"
            aria-expanded="false"
            aria-controls="accordion-collapse-body-2"
          >
            <span>Additional Information</span>
            <svg data-accordion-icon="" class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
            </svg>
          </button>
        </h2>
        <div id="accordion-collapse-body-2" class="{{ $attributes->get('class', '') }} hidden" aria-labelledby="accordion-collapse-heading-2">
          <div class="{{ $attributes->get('class', '') }} border-gray-200 px-4 pt-4 dark:border-gray-700 sm:px-5 sm:pt-5">
            <!-- Inputs -->
            <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-2">
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="skills" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Skills</label>
                <input
                  type="text"
                  name="skills"
                  id="skills"
                  value="Tailwind CSS, Flowbite, React"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. Figma, HTML, Javascript"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="phone-number" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Phone Number</label>
                <input
                  type="text"
                  name="phone-number"
                  id="phone-number"
                  value="+1631 442 978"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. +1234 567 890"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="linkedin" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Linkedin URL</label>
                <input
                  type="url"
                  name="linkedin"
                  id="linkedin"
                  value="https://www.linkedin.com/in/bonniegreen/"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://www.linkedin.com/in/example/"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="facebook" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Facebook</label>
                <input
                  type="url"
                  name="facebook"
                  id="facebook"
                  value="https://www.facebook.com/bonniegreen"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://www.facebook.com/example"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="twitter" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Twitter</label>
                <input
                  type="url"
                  name="twitter"
                  id="twitter"
                  value="https://twitter.com/bonniegreen"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://twitter.com/example"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="personal-website" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Personal Website</label>
                <input
                  type="url"
                  name="personal-website"
                  id="personal-website"
                  value="https://flowbite.com"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://website.com"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="country" class="{{ $attributes->get('class', '') }} mb-2 text-sm font-medium text-gray-900 dark:text-white">Country</label>
                <select
                  id="country"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">United States</option>
                  <option value="NO">Australia</option>
                  <option value="NO">United Kingdom</option>
                  <option value="NO">Italy</option>
                  <option value="NO">Germany</option>
                  <option value="NO">Spain</option>
                  <option value="NO">France</option>
                  <option value="NO">Canada</option>
                </select>
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="address" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Address</label>
                <input
                  type="text"
                  name="address"
                  id="address"
                  value="92 Milles Drive, Newark, NJ 07123"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. 92 Milles Drive, Newark, NJ 07123"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="timezone" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Timezone</label>
                <input
                  type="text"
                  name="timezone"
                  id="timezone"
                  value="GMT+3"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. GMT+2"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="biography" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Biography</label>
                <textarea
                  id="biography"
                  rows="4"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 placeholder:text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Write your biography..."
                >
Hello, I'm Helene Engels, USA Designer, Creating things that stand out, Featured by Adobe, Figma, Webflow and others, Daily design tips & resources, Exploring Web3.</textarea
                >
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4 px-4 py-4 sm:px-5 sm:py-5">
        <button
          type="submit"
          class="{{ $attributes->get('class', '') }} rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Update user
        </button>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} focus:outline-non inline-flex items-center rounded-lg bg-red-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
              clip-rule="evenodd"
            />
          </svg>
          Delete
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Preview User Modal -->
<div id="readUserModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-3xl p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} mb-4 flex justify-between rounded-t sm:mb-5">
        <div class="{{ $attributes->get('class', '') }} flex items-center">
          <img class="{{ $attributes->get('class', '') }} mr-2 h-12 w-12 rounded-full sm:mr-4" src="/images/users/helene-engels.png" alt="Helene avatar" />
          <div>
            <h3 class="{{ $attributes->get('class', '') }} text-lg font-bold text-gray-900 dark:text-white md:text-xl">Helene Engels</h3>
            <p class="{{ $attributes->get('class', '') }} text-base  text-gray-500 dark:text-gray-400">Moderator</p>
          </div>
        </div>
        <div>
          <button type="button" class="{{ $attributes->get('class', '') }} inline-flex rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="readUserModal">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
          </button>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} mb-4 grid gap-4 sm:mb-5 sm:grid-cols-2 sm:gap-6">
        <dl>
          <dt class="{{ $attributes->get('class', '') }} sr-only">Role</dt>
          <dd class="{{ $attributes->get('class', '') }} mb-2 flex items-center  text-gray-500 dark:text-gray-400">
            <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M10 2a3 3 0 0 0-3 3v1H5a3 3 0 0 0-3 3v2.4l1.4.7a7.7 7.7 0 0 0 .7.3 21 21 0 0 0 16.4-.3l1.5-.7V9a3 3 0 0 0-3-3h-2V5a3 3 0 0 0-3-3h-4Zm5 4V5c0-.6-.4-1-1-1h-4a1 1 0 0 0-1 1v1h6Zm6.4 7.9.6-.3V19a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-5.4l.6.3a10 10 0 0 0 .7.3 23 23 0 0 0 18-.3h.1L21 13l.4.9ZM12 10a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
                clip-rule="evenodd"
              />
            </svg>
            Frontend Developer
          </dd>
          <dt class="{{ $attributes->get('class', '') }} sr-only">Location</dt>
          <dd class="{{ $attributes->get('class', '') }} mb-4 flex items-center  text-gray-500 dark:text-gray-400 sm:mb-5">
            <svg class="{{ $attributes->get('class', '') }} me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M12 2a8 8 0 0 1 6.6 12.6l-.1.1-.6.7-5.1 6.2a1 1 0 0 1-1.6 0L6 15.3l-.3-.4-.2-.2v-.2A8 8 0 0 1 11.8 2Zm3 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" clip-rule="evenodd" />
            </svg>
            United States of America
          </dd>
          <dt class="{{ $attributes->get('class', '') }} mb-2 leading-none text-gray-500 dark:text-gray-400">Email Adress</dt>
          <dd class="{{ $attributes->get('class', '') }} mb-4 font-medium text-gray-900 dark:text-white sm:mb-5">helene@company.com</dd>
          <dt class="{{ $attributes->get('class', '') }} mb-2 leading-none text-gray-500 dark:text-gray-400">Home Adress</dt>
          <dd class="{{ $attributes->get('class', '') }} mb-4 font-medium text-gray-900 dark:text-white sm:mb-5">92 Miles Drive, Newark, NJ 07103, California, United States of America</dd>
          <dt class="{{ $attributes->get('class', '') }} mb-2 leading-none text-gray-500 dark:text-gray-400">Phone Number</dt>
          <dd class="{{ $attributes->get('class', '') }} font-medium text-gray-900 dark:text-white">+1234 567 890 / +12 345 678</dd>
        </dl>
        <dl>
          <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Biography</dt>
          <dd class="{{ $attributes->get('class', '') }} mb-4  text-gray-500 dark:text-gray-400 sm:mb-5">Hello, I'm Helene Engels, USA Designer, Creating things that stand out, Featured by Adobe, Figma, Webflow and others, Daily design tips & resources, Exploring Web3.</dd>
          <dt class="{{ $attributes->get('class', '') }} mb-2 font-semibold leading-none text-gray-900 dark:text-white">Social</dt>
          <dd class="{{ $attributes->get('class', '') }} inline-flex items-center space-x-1">
            <a href="#" class="{{ $attributes->get('class', '') }} rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewbox="0 0 24 24" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                  clip-rule="evenodd"
                />
              </svg>
            </a>
            <a href="#" class="{{ $attributes->get('class', '') }} rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewbox="0 0 24 24" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                  clip-rule="evenodd"
                />
              </svg>
            </a>
            <a href="#" class="{{ $attributes->get('class', '') }} rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewbox="0 0 24 24" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                  clip-rule="evenodd"
                />
              </svg>
            </a>
            <a href="#" class="{{ $attributes->get('class', '') }} rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" fill="currentColor" viewbox="0 0 24 24" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c5.51 0 10-4.48 10-10S17.51 2 12 2zm6.605 4.61a8.502 8.502 0 011.93 5.314c-.281-.054-3.101-.629-5.943-.271-.065-.141-.12-.293-.184-.445a25.416 25.416 0 00-.564-1.236c3.145-1.28 4.577-3.124 4.761-3.362zM12 3.475c2.17 0 4.154.813 5.662 2.148-.152.216-1.443 1.941-4.48 3.08-1.399-2.57-2.95-4.675-3.189-5A8.687 8.687 0 0112 3.475zm-3.633.803a53.896 53.896 0 013.167 4.935c-3.992 1.063-7.517 1.04-7.896 1.04a8.581 8.581 0 014.729-5.975zM3.453 12.01v-.26c.37.01 4.512.065 8.775-1.215.25.477.477.965.694 1.453-.109.033-.228.065-.336.098-4.404 1.42-6.747 5.303-6.942 5.629a8.522 8.522 0 01-2.19-5.705zM12 20.547a8.482 8.482 0 01-5.239-1.8c.152-.315 1.888-3.656 6.703-5.337.022-.01.033-.01.054-.022a35.318 35.318 0 011.823 6.475 8.4 8.4 0 01-3.341.684zm4.761-1.465c-.086-.52-.542-3.015-1.659-6.084 2.679-.423 5.022.271 5.314.369a8.468 8.468 0 01-3.655 5.715z"
                  clip-rule="evenodd"
                />
              </svg>
            </a>
          </dd>
        </dl>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex items-center gap-4">
        <div class="{{ $attributes->get('class', '') }} flex items-center space-x-3 sm:space-x-4">
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
              <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
            </svg>
            Edit
          </button>
        </div>
        <button type="button" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
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
</div>

<!-- Create User Modal -->
<div id="createUserAccordionModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-2xl p-4">
    <!-- Modal content -->
    <form action="#" class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <!-- Modal header -->
      <div class="{{ $attributes->get('class', '') }} flex items-center justify-between rounded-t px-4 py-4 sm:px-5">
        <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">Add new user</h3>
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="createUserAccordionModal"
        >
        <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
        </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
        </button>
      </div>
      <div id="accordion-collapse-create-user" data-accordion="collapse">
        <h2 id="accordion-collapse-heading-3">
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between bg-gray-50 px-4 py-4 text-left font-medium leading-none text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 sm:px-5"
            data-accordion-target="#accordion-collapse-body-3"
            aria-expanded="true"
            aria-controls="accordion-collapse-body-3"
          >
            <span>General Information</span>
            <svg data-accordion-icon="" class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
            </svg>
          </button>
        </h2>
        <div id="accordion-collapse-body-3" class="{{ $attributes->get('class', '') }} " aria-labelledby="accordion-collapse-heading-3">
          <div class="{{ $attributes->get('class', '') }} border-gray-200 p-4 dark:border-gray-700 sm:p-5">
            <!-- Inputs -->
            <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-2">
              <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
                <label class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="file_input">Upload avatar</label>
                <div class="{{ $attributes->get('class', '') }} w-full items-center sm:flex">
                  <img class="{{ $attributes->get('class', '') }} mb-4 h-20 w-20 rounded-full sm:mb-0 sm:mr-4" src="/images/users/helene-engels.png" alt="Helene avatar" />
                  <div class="{{ $attributes->get('class', '') }} w-full">
                    <input
                      class="{{ $attributes->get('class', '') }} w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder-gray-400"
                      aria-describedby="file_input_help"
                      id="file_input"
                      type="file"
                    />
                    <p class="{{ $attributes->get('class', '') }} mt-1 text-xs font-normal text-gray-500 dark:text-gray-300" id="file_input_help">SVG, PNG, JPG or GIF (MAX. 800x400px).</p>
                  </div>
                </div>
              </div>
              <div>
                <label for="first-name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">First Name</label>
                <input
                  type="text"
                  name="first-name"
                  id="first-name"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="John"
                  required=""
                />
              </div>
              <div>
                <label for="last-name" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Last Name</label>
                <input
                  type="text"
                  name="last-name"
                  id="last-name"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Doe"
                  required=""
                />
              </div>
              <div>
                <label for="email" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email</label>
                <input
                  type="email"
                  name="email"
                  id="email"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="name@company.com"
                  required=""
                />
              </div>
              <div>
                <label for="user-permissions" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  User Permissions
                  <button type="button" data-tooltip-target="tooltip-user-permissions" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">User permission details</span>
                  </button>
                  <div id="tooltip-user-permissions" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    User permissions, part of the overall user management process, are access granted to users to specific resources such as files, applications, networks, or devices.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="user-permissions"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Operational</option>
                  <option value="NO">Non Operational</option>
                </select>
              </div>
              <div>
                <label for="job-title" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Job Title</label>
                <input
                  type="text"
                  name="job-title"
                  id="job-title"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="e.g React Native Developer"
                  required=""
                />
              </div>
              <div>
                <label for="languages" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Languages</label>
                <input
                  type="text"
                  name="languages"
                  id="languages"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="e.g English"
                  required=""
                />
              </div>
              <div>
                <label for="account" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Account
                  <button type="button" data-tooltip-target="tooltip-account-1" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Account details</span>
                  </button>
                  <div id="tooltip-account-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    Choose here your account type.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="account"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Choose account type</option>
                  <option value="DF">Default Account</option>
                  <option value="PRO">PRO Account</option>
                </select>
              </div>
              <div>
                <label for="user-role" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  User Role
                  <button type="button" data-tooltip-target="tooltip-user-role-1" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 h-4 w-4 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">User role details</span>
                  </button>
                  <div id="tooltip-user-role-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    Flowbite provides 7 predefined roles: Owner, Admin, Editor, Contributor and Viewer. Assign the most suitable role to each user, giving them the most appropriate level of control.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="user-role"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Owner</option>
                  <option value="AD">Admin</option>
                  <option value="ED">Editor</option>
                  <option value="CO">Contributor</option>
                  <option value="VI">Viewer</option>
                </select>
              </div>
              <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
                <label for="email-status" class="{{ $attributes->get('class', '') }} mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Email Status
                  <button type="button" data-tooltip-target="tooltip-email-status-1" data-tooltip-style="dark" class="{{ $attributes->get('class', '') }} ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Email status details</span>
                  </button>
                  <div id="tooltip-email-status-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    As an administrator, you can view the status of a user's email. The status indicates whether a user's email is verified or not.
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="email-status"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Not verified</option>
                  <option value="VE">Verified</option>
                </select>
              </div>
              <div>
                <label for="password" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Password</label>
                <input
                  type="password"
                  name="password"
                  id="password"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="•••••••••"
                  required=""
                />
              </div>
              <div>
                <label for="confirm-password" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Confirm password</label>
                <input
                  type="password"
                  name="confirm-password"
                  id="confirm-password"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="•••••••••"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} sm:col-span-2">
                <label class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="role">Assign Role</label>
                <div class="{{ $attributes->get('class', '') }} flex space-x-4">
                  <div class="{{ $attributes->get('class', '') }} flex items-center">
                    <input
                      id="inline-checkbox-create-user"
                      type="checkbox"
                      value=""
                      name="role"
                      class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-checkbox-create-user" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Administrator</label>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} flex items-center">
                    <input
                      id="inline-2-checkbox-create-user"
                      type="checkbox"
                      value=""
                      name="role"
                      class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-2-checkbox-create-user" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Member</label>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} flex items-center">
                    <input
                      checked=""
                      id="inline-checked-checkbox-create-user"
                      type="checkbox"
                      value=""
                      name="role"
                      class="{{ $attributes->get('class', '') }} h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-checked-checkbox-create-user" class="{{ $attributes->get('class', '') }} ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Viewer</label>
                  </div>
                </div>
              </div>
              <div>
                <div class="{{ $attributes->get('class', '') }} mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</div>
                <label for="status-create-user" class="{{ $attributes->get('class', '') }} relative inline-flex cursor-pointer items-center">
                  <input type="checkbox" value="" id="status-create-user" class="{{ $attributes->get('class', '') }} peer sr-only" />
                  <div
                    class="{{ $attributes->get('class', '') }} peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800"
                  ></div>
                  <span class="{{ $attributes->get('class', '') }} ml-3 text-sm font-medium text-gray-500 dark:text-gray-300">Inactive</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <h2 id="accordion-collapse-heading-4">
          <button
            type="button"
            class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-4 text-left font-medium leading-none text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 sm:px-5"
            data-accordion-target="#accordion-collapse-body-4"
            aria-expanded="false"
            aria-controls="accordion-collapse-body-4"
          >
            <span>Additional Information</span>
            <svg data-accordion-icon="" class="{{ $attributes->get('class', '') }} h-5 w-5 shrink-0 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
            </svg>
          </button>
        </h2>
        <div id="accordion-collapse-body-4" class="{{ $attributes->get('class', '') }} hidden" aria-labelledby="accordion-collapse-heading-4">
          <div class="{{ $attributes->get('class', '') }} border-gray-200 p-4 dark:border-gray-700 sm:p-5">
            <!-- Inputs -->
            <div class="{{ $attributes->get('class', '') }} grid gap-4 sm:grid-cols-2">
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="skills" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Skills</label>
                <input
                  type="text"
                  name="skills"
                  id="skills"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="e.g Figma, HTML, Javascript"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="phone-number" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Phone Number</label>
                <input
                  type="number"
                  name="phone-number"
                  id="phone-number"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Add a phone number"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="linkedin" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Linkedin URL</label>
                <input
                  type="url"
                  name="linkedin"
                  id="linkedin"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Linkedin URL"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="facebook" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Facebook</label>
                <input
                  type="url"
                  name="facebook"
                  id="facebook"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Facebook Profile"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="instagram" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Instagram</label>
                <input
                  type="url"
                  name="instagram"
                  id="instagram"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Instagram Username"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="personal-website" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Personal Website</label>
                <input
                  type="url"
                  name="personal-website"
                  id="personal-website"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="www.example.com"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2">
                <label for="country" class="{{ $attributes->get('class', '') }} mb-2 text-sm font-medium text-gray-900 dark:text-white">Country</label>
                <select
                  id="country"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">United States</option>
                  <option value="NO">Australia</option>
                  <option value="NO">United Kingdom</option>
                  <option value="NO">Italy</option>
                  <option value="NO">Germany</option>
                  <option value="NO">Spain</option>
                  <option value="NO">France</option>
                  <option value="NO">Canada</option>
                </select>
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="address" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Address</label>
                <input
                  type="text"
                  name="address"
                  id="adress"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Your Location"
                  required=""
                />
              </div>
              <div class="{{ $attributes->get('class', '') }} col-span-2 sm:col-span-1">
                <label for="timezone" class="{{ $attributes->get('class', '') }} mb-2 block text-sm font-medium text-gray-900 dark:text-white">Timezone</label>
                <input
                  type="text"
                  name="timezone"
                  id="timezone"
                  class="{{ $attributes->get('class', '') }} block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="e.g GMT -6"
                  required=""
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4 px-4 py-4 sm:px-5">
        <button
          type="submit"
          data-modal-target="createUserAccordionModal"
          data-modal-toggle="createUserAccordionModal"
          class="{{ $attributes->get('class', '') }} inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
          </svg>
          Add new user
        </button>
        <button
          data-modal-toggle="createUserAccordionModal"
          type="button"
          class="{{ $attributes->get('class', '') }} w-full rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Discard
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteUserModal"
      >
<svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
</svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-2 text-lg font-semibold text-gray-900 dark:text-white">Delete user?</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Are you sure you want to delete this user from the Flowbite platform?</p>
      <div class="{{ $attributes->get('class', '') }} mb-4 rounded-lg bg-orange-100 p-4 text-left text-sm text-orange-700 dark:bg-gray-700 dark:text-orange-300 sm:mb-5" role="alert">
        <div class="{{ $attributes->get('class', '') }} mb-1 items-center">
          <svg class="{{ $attributes->get('class', '') }} mr-1 inline h-4 w-4 shrink-0" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Danger</span>
          <span class="{{ $attributes->get('class', '') }} font-semibold leading-none">Warning</span>
        </div>
        <p>By deleting this user 6 associated posts will also be permanently deleted.</p>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="deleteUserModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button type="submit" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
              clip-rule="evenodd"
            />
          </svg>
          Yes, confirm delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete all Users Modal -->
<div id="deleteAllUsersModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteAllUsersModal"
      >
<svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
</svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-2 text-lg font-semibold text-gray-900 dark:text-white">Delete users?</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Are you sure you want to delete the following users from the Flowbite platform?</p>
      <ul role="list" class="{{ $attributes->get('class', '') }} mb-4 space-y-2 text-left text-gray-500 sm:mb-5 dark:text-gray-400">
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg class="{{ $attributes->get('class', '') }} shrink-0 w-4 h-4 text-gray-400 dark:text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
          </svg>          
            <span>Bonnie Green</span>
        </li>
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg class="{{ $attributes->get('class', '') }} shrink-0 w-4 h-4 text-gray-400 dark:text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
          </svg>  
            <span>Jese Leos</span>
        </li>
    </ul>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="deleteAllUsersModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button type="submit" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
          <svg class="{{ $attributes->get('class', '') }} -ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
              clip-rule="evenodd"
            />
          </svg>
          Yes, confirm delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Archive all Users Modal -->
<div id="archiveAllUsersModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="archiveAllUsersModal"
      >
<svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
</svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-2 text-lg font-semibold text-gray-900 dark:text-white">Archive users?</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Are you sure you want to archive the following users?</p>
      <ul role="list" class="{{ $attributes->get('class', '') }} mb-4 space-y-2 text-left text-gray-500 sm:mb-5 dark:text-gray-400">
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg class="{{ $attributes->get('class', '') }} shrink-0 w-4 h-4 text-gray-400 dark:text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
          </svg>          
            <span>Bonnie Green</span>
        </li>
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg class="{{ $attributes->get('class', '') }} shrink-0 w-4 h-4 text-gray-400 dark:text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
          </svg>  
            <span>Jese Leos</span>
        </li>
    </ul>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="archiveAllUsersModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button type="button" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
          Yes, I do
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Archive User Modal -->
<div id="archiveUserModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="{{ $attributes->get('class', '') }} relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="{{ $attributes->get('class', '') }} relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="archiveUserModal"
      >
<svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
</svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
      </button>
      <h3 class="{{ $attributes->get('class', '') }} mb-2 text-lg font-semibold text-gray-900 dark:text-white">Archive user</h3>
      <p class="{{ $attributes->get('class', '') }} mb-4 text-gray-500 dark:text-gray-400">Do you want to continue? Archiving this user will move it to a separate section.</p>
      <ul role="list" class="{{ $attributes->get('class', '') }} mb-4 space-y-2 text-left text-gray-500 sm:mb-5 dark:text-gray-400">
        <li class="{{ $attributes->get('class', '') }} flex items-center space-x-2">
          <svg class="{{ $attributes->get('class', '') }} shrink-0 w-4 h-4 text-gray-400 dark:text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
          </svg>          
            <span>Bonnie Green</span>
        </li>
    </ul>
      <div class="{{ $attributes->get('class', '') }} flex items-center space-x-4">
        <button
          data-modal-toggle="archiveUserModal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button type="button" class="{{ $attributes->get('class', '') }} inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
          Yes, I do
        </button>
      </div>
    </div>
  </div>
</div>
