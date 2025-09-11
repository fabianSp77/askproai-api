@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

---
title: Tailwind CSS AI Chat Page - Flowbite
description: Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.
layout: dashboard
footer: true
group: pages
page: ai-chat
---

<div class="{{ $attributes->get('class', '') }} relative h-[calc(100vh-8rem)] w-full">
  <div class="{{ $attributes->get('class', '') }} border-b border-gray-200 bg-white px-4 py-2.5 pb-4 sm:pb-2.5 dark:border-gray-700 dark:bg-gray-800">
    <div class="{{ $attributes->get('class', '') }} flex w-full items-center justify-between mb-2.5 sm:mb-0">
      <div>
        <button
          data-tooltip-target="tooltip-chat-settings"
          data-modal-toggle="settings-modal"
          data-modal-target="settings-modal"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-600"
        >
          <span class="{{ $attributes->get('class', '') }} sr-only">Chat settings</span>
  
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 13v-2a1 1 0 0 0-1-1h-.757l-.707-1.707.535-.536a1 1 0 0 0 0-1.414l-1.414-1.414a1 1 0 0 0-1.414 0l-.536.535L14 4.757V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.757l-1.707.707-.536-.535a1 1 0 0 0-1.414 0L4.929 6.343a1 1 0 0 0 0 1.414l.536.536L4.757 10H4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.757l.707 1.707-.535.536a1 1 0 0 0 0 1.414l1.414 1.414a1 1 0 0 0 1.414 0l.536-.535 1.707.707V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.757l1.707-.708.536.536a1 1 0 0 0 1.414 0l1.414-1.414a1 1 0 0 0 0-1.414l-.535-.536.707-1.707H20a1 1 0 0 0 1-1Z"
            />
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
          </svg>
        </button>
  
        <div id="tooltip-chat-settings" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Chat settings
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
  
        <button
          data-tooltip-target="tooltip-chat-share"
          type="button"
          class="{{ $attributes->get('class', '') }} rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-600"
        >
          <span class="{{ $attributes->get('class', '') }} sr-only">Share conversation</span>
  
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2M12 4v12m0-12 4 4m-4-4L8 8" />
          </svg>
        </button>
  
        <div id="tooltip-chat-share" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Share conversation
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
      <div class="{{ $attributes->get('class', '') }} flex items-center">
        <button
          type="button"
          class="{{ $attributes->get('class', '') }} sm:flex w-full me-4 hidden items-center justify-center rounded-lg bg-primary-700 px-6 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="{{ $attributes->get('class', '') }} me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M17.44 3a1 1 0 0 1 .707.293l2.56 2.56a1 1 0 0 1 0 1.414L18.194 9.78 14.22 5.806l2.513-2.513A1 1 0 0 1 17.44 3Zm-4.634 4.22-9.513 9.513a1 1 0 0 0 0 1.414l2.56 2.56a1 1 0 0 0 1.414 0l9.513-9.513-3.974-3.974ZM6 6a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H7v1a1 1 0 1 1-2 0v-1H4a1 1 0 0 1 0-2h1V7a1 1 0 0 1 1-1Zm9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z"
              clip-rule="evenodd"
            />
            <path d="M19 13h-2v2h2v-2ZM13 3h-2v2h2V3Zm-2 2H9v2h2V5ZM9 3H7v2h2V3Zm12 8h-2v2h2v-2Zm0 4h-2v2h2v-2Z" />
          </svg>
  
          New chat
        </button>
        <button
          id="dropdownAlgorithmButton"
          data-dropdown-toggle="dropdownAlgorithm"
          class="{{ $attributes->get('class', '') }} flex items-center rounded-full p-1.5 me-2 text-sm font-medium text-gray-900 hover:text-primary-600 focus:ring-4 focus:ring-gray-100 dark:text-white dark:hover:text-primary-500 dark:focus:ring-gray-700"
          type="button"
        >
          <span class="{{ $attributes->get('class', '') }} sr-only">Open user menu</span>
          Default (GPT-3.5)
          <svg class="{{ $attributes->get('class', '') }} mx-1.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div id="dropdownAlgorithm" class="{{ $attributes->get('class', '') }} z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" role="none">
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem"> GPT-4o mini </a>
            </li>
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem"> GPT-4o mini </a>
            </li>
            <li>
              <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem"> GPT-4o </a>
            </li>
          </ul>
        </div>

        <button
          data-tooltip-target="tooltip-chat-history"
          type="button"
          data-drawer-target="drawer-chat-history" data-drawer-show="drawer-chat-history" data-drawer-placement="right" aria-controls="drawer-chat-history"
          class="{{ $attributes->get('class', '') }} rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-600"
        >
          <span class="{{ $attributes->get('class', '') }} sr-only">View history</span>
  
          <svg class="{{ $attributes->get('class', '') }} h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 9h6m-6 3h6m-6 3h6M6.996 9h.01m-.01 3h.01m-.01 3h.01M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" />
          </svg>
        </button>
  
        <div id="tooltip-chat-history" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          View chat history
          <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </div>
    <div>
      <button
        type="button"
        class="{{ $attributes->get('class', '') }} flex sm:hidden w-full items-center justify-center rounded-lg bg-primary-700 px-6 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="{{ $attributes->get('class', '') }} me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M17.44 3a1 1 0 0 1 .707.293l2.56 2.56a1 1 0 0 1 0 1.414L18.194 9.78 14.22 5.806l2.513-2.513A1 1 0 0 1 17.44 3Zm-4.634 4.22-9.513 9.513a1 1 0 0 0 0 1.414l2.56 2.56a1 1 0 0 0 1.414 0l9.513-9.513-3.974-3.974ZM6 6a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H7v1a1 1 0 1 1-2 0v-1H4a1 1 0 0 1 0-2h1V7a1 1 0 0 1 1-1Zm9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z"
            clip-rule="evenodd"
          />
          <path d="M19 13h-2v2h2v-2ZM13 3h-2v2h2V3Zm-2 2H9v2h2V5ZM9 3H7v2h2V3Zm12 8h-2v2h2v-2Zm0 4h-2v2h2v-2Z" />
        </svg>

        New chat
      </button>
    </div>
  </div>

  <div class="{{ $attributes->get('class', '') }} overflow-y-scroll h-[calc(100vh-18.7rem)] sm:h-[calc(100vh-15.4rem)]">
    <div class="{{ $attributes->get('class', '') }} max-w-4xl mx-auto py-4 lg:py-6 space-y-6 px-4 lg:px-6">
      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6 group relative pe-14">
          <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
          <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">
              <p>“Generate a list of potential essay topics for [design principles], along with a brief outline of main points to be discussed”</p>
          </div>
          <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip1" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                  <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
              </svg>
          </button>
          <div id="editChatTooltip1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Edit prompt
              <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
      </div>
  
      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6 group relative pe-14">
          <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/logo.svg" alt="Flowbite logo">
          <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">
              <p>Certainly! Here is a list of potential essay topics related to design principles, along with a brief outline of main points for each topic:</p>
              <p><strong>1. The Influence of Balance in Visual Design:</strong></p>
              <ul>
                  <li>Introduction to balance as a fundamental design principle.</li>
                  <li>Exploring symmetrical and asymmetrical balance.</li>
                  <li>Examples of how balance impacts the perception of visual elements.</li>
                  <li>The role of balance in various design disciplines (e.g., graphic design, architecture).</li>
              </ul>
              <p><strong>2. Contrast and Its Impact on Design Communication:</strong></p>
              <ul>
                  <li>Introduction to contrast and its significance in design.</li>
                  <li>Exploring the use of color, typography, and scale to create contrast.</li>
                  <li>How contrast guides the viewer's attention and conveys meaning.</li>
                  <li>Case studies demonstrating effective use of contrast in real-world designs.</li>
              </ul>
              <p>These essay topics provide a comprehensive range of subjects related to design principles, each with its own set of key points for discussion. Depending on your specific interests and requirements, you can choose one that resonates most with your academic goals.</p>
              <div class="{{ $attributes->get('class', '') }} space-x-2 flex items-center">
                  <button type="button" data-tooltip-target="tooltip-copy-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-6 8a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>
                        </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Copy text</span>
                  </button>
                  <div id="tooltip-copy-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                      Copy text
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button type="button" data-tooltip-target="tooltip-like-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M15.03 9.684h3.965c.322 0 .64.08.925.232.286.153.532.374.717.645a2.109 2.109 0 0 1 .242 1.883l-2.36 7.201c-.288.814-.48 1.355-1.884 1.355-2.072 0-4.276-.677-6.157-1.256-.472-.145-.924-.284-1.348-.404h-.115V9.478a25.485 25.485 0 0 0 4.238-5.514 1.8 1.8 0 0 1 .901-.83 1.74 1.74 0 0 1 1.21-.048c.396.13.736.397.96.757.225.36.32.788.269 1.211l-1.562 4.63ZM4.177 10H7v8a2 2 0 1 1-4 0v-6.823C3 10.527 3.527 10 4.176 10Z" clip-rule="evenodd"/>
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Like response</span>
                  </button>
                  <div id="tooltip-like-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                      Like
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
                  <button type="button" data-tooltip-target="tooltip-unline-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                          <path fill-rule="evenodd" d="M8.97 14.316H5.004c-.322 0-.64-.08-.925-.232a2.022 2.022 0 0 1-.717-.645 2.108 2.108 0 0 1-.242-1.883l2.36-7.201C5.769 3.54 5.96 3 7.365 3c2.072 0 4.276.678 6.156 1.256.473.145.925.284 1.35.404h.114v9.862a25.485 25.485 0 0 0-4.238 5.514c-.197.376-.516.67-.901.83a1.74 1.74 0 0 1-1.21.048 1.79 1.79 0 0 1-.96-.757 1.867 1.867 0 0 1-.269-1.211l1.562-4.63ZM19.822 14H17V6a2 2 0 1 1 4 0v6.823c0 .65-.527 1.177-1.177 1.177Z" clip-rule="evenodd"/>
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} sr-only">Unline response</span>
                  </button>
                  <div id="tooltip-unline-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                      Unlike
                      <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                  </div>
              </div>
          </div>
          <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip2" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                  <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
              </svg>
          </button>
          <div id="editChatTooltip2" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Edit prompt
              <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
      </div>
  
      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-center gap-6 group relative pe-14">
          <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
          <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">
              <p>“Generate a [Flowbite Navbar] code with 2 items”</p>
          </div>
          <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip3" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
              <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                  <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
              </svg>
          </button>
          <div id="editChatTooltip3" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              Edit prompt
              <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
          </div>
      </div>

      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6 group relative pe-14">
        <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/logo.svg" alt="Flowbite logo">
        <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue w-full max-w-auto">
            <p>Certainly! Below is an Flowbite Navbar with two items:</p>
            <div class="{{ $attributes->get('class', '') }} relative bg-gray-50 rounded-lg dark:bg-gray-700 p-4 h-64 not-format mb-4 max-w-auto me-2">
              <div class="{{ $attributes->get('class', '') }} overflow-scroll max-h-full">
                  <pre><code id="code-block" class="{{ $attributes->get('class', '') }} text-sm text-gray-500 dark:text-gray-400 whitespace-pre">&#x27;use client&#x27;;
      
      import Link from &#x27;next/link&#x27;;
      import { Navbar } from &#x27;flowbite-react&#x27;;
      
      function Component() {
        return (
          &#x3C;Navbar fluid rounded&#x3E;
            &#x3C;Navbar.Brand as={Link} href=&#x22;https://flowbite-react.com&#x22;&#x3E;
              &#x3C;img src=&#x22;/favicon.svg&#x22; className=&#x22;mr-3 h-6 sm:h-9&#x22; alt=&#x22;Flowbite React Logo&#x22; /&#x3E;
              &#x3C;span className=&#x22;self-center whitespace-nowrap text-xl font-semibold dark:text-white&#x22;&#x3E;Flowbite React&#x3C;/span&#x3E;
            &#x3C;/Navbar.Brand&#x3E;
            &#x3C;Navbar.Toggle /&#x3E;
            &#x3C;Navbar.Collapse&#x3E;
              &#x3C;Navbar.Link href=&#x22;#&#x22; active&#x3E;
                Home
              &#x3C;/Navbar.Link&#x3E;
              &#x3C;Navbar.Link as={Link} href=&#x22;#&#x22;&#x3E;
                About
              &#x3C;/Navbar.Link&#x3E;
              &#x3C;Navbar.Link href=&#x22;#&#x22;&#x3E;Services&#x3C;/Navbar.Link&#x3E;
              &#x3C;Navbar.Link href=&#x22;#&#x22;&#x3E;Pricing&#x3C;/Navbar.Link&#x3E;
              &#x3C;Navbar.Link href=&#x22;#&#x22;&#x3E;Contact&#x3C;/Navbar.Link&#x3E;
            &#x3C;/Navbar.Collapse&#x3E;
          &#x3C;/Navbar&#x3E;
        );
      }
      </code></pre>
              </div>
          <div class="{{ $attributes->get('class', '') }} absolute top-2 end-2 bg-gray-50 dark:bg-gray-700">
              <button data-copy-to-clipboard-target="code-block" data-copy-to-clipboard-content-type="innerHTML" data-copy-to-clipboard-html-entities="true" class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-gray-400 m-0.5 h-8 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 rounded-lg py-2 px-2.5 inline-flex items-center justify-center bg-white border-gray-200 border">
                  <span id="default-message">
                      <span class="{{ $attributes->get('class', '') }} inline-flex items-center">
                        <svg class="{{ $attributes->get('class', '') }} w-3 h-3 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                            <path d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"/>
                        </svg>
                        <span class="{{ $attributes->get('class', '') }} text-xs font-semibold">Copy code</span>
                      </span>
                  </span>
                  <span id="success-message" class="{{ $attributes->get('class', '') }} hidden">
                    <span class="{{ $attributes->get('class', '') }} inline-flex items-center">
                      <svg class="{{ $attributes->get('class', '') }} w-3 h-3 text-primary-700 dark:text-primary-500 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5"/>
                      </svg>
                      <span class="{{ $attributes->get('class', '') }} text-xs font-semibold text-primary-700 dark:text-primary-500">Copied</span>
                    </span>
                  </span>
              </button>
          </div>
          </div>
            <div class="{{ $attributes->get('class', '') }} space-x-2 flex items-center">
                <button type="button" data-tooltip-target="tooltip-copy-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-6 8a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>
                      </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Copy text</span>
                </button>
                <div id="tooltip-copy-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Copy text
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
                <button type="button" data-tooltip-target="tooltip-like-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M15.03 9.684h3.965c.322 0 .64.08.925.232.286.153.532.374.717.645a2.109 2.109 0 0 1 .242 1.883l-2.36 7.201c-.288.814-.48 1.355-1.884 1.355-2.072 0-4.276-.677-6.157-1.256-.472-.145-.924-.284-1.348-.404h-.115V9.478a25.485 25.485 0 0 0 4.238-5.514 1.8 1.8 0 0 1 .901-.83 1.74 1.74 0 0 1 1.21-.048c.396.13.736.397.96.757.225.36.32.788.269 1.211l-1.562 4.63ZM4.177 10H7v8a2 2 0 1 1-4 0v-6.823C3 10.527 3.527 10 4.176 10Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Like response</span>
                </button>
                <div id="tooltip-like-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Like
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
                <button type="button" data-tooltip-target="tooltip-unline-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.97 14.316H5.004c-.322 0-.64-.08-.925-.232a2.022 2.022 0 0 1-.717-.645 2.108 2.108 0 0 1-.242-1.883l2.36-7.201C5.769 3.54 5.96 3 7.365 3c2.072 0 4.276.678 6.156 1.256.473.145.925.284 1.35.404h.114v9.862a25.485 25.485 0 0 0-4.238 5.514c-.197.376-.516.67-.901.83a1.74 1.74 0 0 1-1.21.048 1.79 1.79 0 0 1-.96-.757 1.867 1.867 0 0 1-.269-1.211l1.562-4.63ZM19.822 14H17V6a2 2 0 1 1 4 0v6.823c0 .65-.527 1.177-1.177 1.177Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Unline response</span>
                </button>
                <div id="tooltip-unline-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Unlike
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
            </div>
        </div>
        <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip4" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <div id="editChatTooltip4" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Edit prompt
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>

      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-center gap-6 group relative pe-14">
        <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
        <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">
            <p>“Create a advanced list of potential influencers to collaborate with for social media and marketing campaigns”</p>
        </div>
        <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip5" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <div id="editChatTooltip5" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Edit prompt
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>

      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6 group relative pe-14">
        <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/logo.svg" alt="Flowbite logo">
        <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">
            <p>Certainly! When creating a list of potential influencers for social media and marketing campaigns, it's important to consider factors like their niche, follower demographics, engagement rate, and overall influence. Here's an advanced list with various niches:</p>
            <p><strong>1. Fitness and Wellness:</strong></p>
            <ul>
                <li><a href="#">@FitnessInfluencerX</a></li>
                <li><a href="#">@YogaGuruY</a></li>
                <li><a href="#">@NutritionExpertZ</a></li>
            </ul>
            <p><strong>2. Technology and Gadgets:</strong></p>
            <ul>
              <li><a href="#">@TechGeekJ</a></li>
              <li><a href="#">@GadgetReviewerK</a></li>
              <li><a href="#">@InnovationExpertL</a></li>
            </ul>
            <p><strong>3. Photography and Art</strong></p>
            <ul>
              <li><a href="#">@PhotographyProBB</a></li>
              <li><a href="#">@ArtisticCreatorCC</a></li>
              <li><a href="#">@IllustrationInfluencerDD</a></li>
            </ul>
            <p>Remember, it's important to research and vet these influencers thoroughly to ensure they align with your brand's values and target audience.</p>
            <div class="{{ $attributes->get('class', '') }} space-x-2 flex items-center">
                <button type="button" data-tooltip-target="tooltip-copy-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-6 8a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>
                      </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Copy text</span>
                </button>
                <div id="tooltip-copy-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Copy text
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
                <button type="button" data-tooltip-target="tooltip-like-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M15.03 9.684h3.965c.322 0 .64.08.925.232.286.153.532.374.717.645a2.109 2.109 0 0 1 .242 1.883l-2.36 7.201c-.288.814-.48 1.355-1.884 1.355-2.072 0-4.276-.677-6.157-1.256-.472-.145-.924-.284-1.348-.404h-.115V9.478a25.485 25.485 0 0 0 4.238-5.514 1.8 1.8 0 0 1 .901-.83 1.74 1.74 0 0 1 1.21-.048c.396.13.736.397.96.757.225.36.32.788.269 1.211l-1.562 4.63ZM4.177 10H7v8a2 2 0 1 1-4 0v-6.823C3 10.527 3.527 10 4.176 10Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Like response</span>
                </button>
                <div id="tooltip-like-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Like
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
                <button type="button" data-tooltip-target="tooltip-unline-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.97 14.316H5.004c-.322 0-.64-.08-.925-.232a2.022 2.022 0 0 1-.717-.645 2.108 2.108 0 0 1-.242-1.883l2.36-7.201C5.769 3.54 5.96 3 7.365 3c2.072 0 4.276.678 6.156 1.256.473.145.925.284 1.35.404h.114v9.862a25.485 25.485 0 0 0-4.238 5.514c-.197.376-.516.67-.901.83a1.74 1.74 0 0 1-1.21.048 1.79 1.79 0 0 1-.96-.757 1.867 1.867 0 0 1-.269-1.211l1.562-4.63ZM19.822 14H17V6a2 2 0 1 1 4 0v6.823c0 .65-.527 1.177-1.177 1.177Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Unline response</span>
                </button>
                <div id="tooltip-unline-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Unlike
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
            </div>
        </div>
        <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip6" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <div id="editChatTooltip6" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Edit prompt
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>

      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-center gap-6 group relative pe-14">
        <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image">
        <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">
            <p>“Please generate 6 surreal landscapes images with bright colors and organic shapes.” </p>
        </div>
        <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip7" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <div id="editChatTooltip7" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Edit prompt
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>

      <div class="{{ $attributes->get('class', '') }} p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6 group relative pe-14">
        <img class="{{ $attributes->get('class', '') }} h-6 w-6 rounded-full" src="/images/logo.svg" alt="Flowbite logo">
        <div class="{{ $attributes->get('class', '') }} format dark:format-invert format-blue">

            <div class="{{ $attributes->get('class', '') }} grid grid-cols-3 gap-3 not-format mb-4">
              <div class="{{ $attributes->get('class', '') }} relative group/image">
                  <img class="{{ $attributes->get('class', '') }} h-auto max-w-full rounded-lg" src="/images/ai/image-1.jpg" alt="">
                  <button type="button" data-tooltip-target="downloadImageTooltip1" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-2 right-2 group-hover/image:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-white hover:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 bg-opacity-5 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="downloadImageTooltip1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Download image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} relative group/image">
                  <img class="{{ $attributes->get('class', '') }} h-auto max-w-full rounded-lg" src="/images/ai/image-2.jpg" alt="">
                  <button type="button" data-tooltip-target="downloadImageTooltip2" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-2 right-2 group-hover/image:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-white hover:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 bg-opacity-5 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="downloadImageTooltip2" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Download image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} relative group/image">
                  <img class="{{ $attributes->get('class', '') }} h-auto max-w-full rounded-lg" src="/images/ai/image-3.jpg" alt="">
                  <button type="button" data-tooltip-target="downloadImageTooltip3" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-2 right-2 group-hover/image:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-white hover:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 bg-opacity-5 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="downloadImageTooltip3" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Download image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} relative group/image">
                  <img class="{{ $attributes->get('class', '') }} h-auto max-w-full rounded-lg" src="/images/ai/image-4.jpg" alt="">
                  <button type="button" data-tooltip-target="downloadImageTooltip4" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-2 right-2 group-hover/image:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-white hover:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 bg-opacity-5 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="downloadImageTooltip4" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Download image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} relative group/image">
                  <img class="{{ $attributes->get('class', '') }} h-auto max-w-full rounded-lg" src="/images/ai/image-5.jpg" alt="">
                  <button type="button" data-tooltip-target="downloadImageTooltip5" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-2 right-2 group-hover/image:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-white hover:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 bg-opacity-5 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="downloadImageTooltip5" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Download image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} relative group/image">
                  <img class="{{ $attributes->get('class', '') }} h-auto max-w-full rounded-lg" src="/images/ai/image-6.jpg" alt="">
                  <button type="button" data-tooltip-target="downloadImageTooltip6" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-2 right-2 group-hover/image:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-white hover:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 bg-opacity-5 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="downloadImageTooltip6" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Download image
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
            </div>

            <div class="{{ $attributes->get('class', '') }} space-x-2 flex items-center">
                <button type="button" data-tooltip-target="tooltip-copy-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-6 8a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>
                      </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Copy text</span>
                </button>
                <div id="tooltip-copy-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Copy text
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
                <button type="button" data-tooltip-target="tooltip-like-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M15.03 9.684h3.965c.322 0 .64.08.925.232.286.153.532.374.717.645a2.109 2.109 0 0 1 .242 1.883l-2.36 7.201c-.288.814-.48 1.355-1.884 1.355-2.072 0-4.276-.677-6.157-1.256-.472-.145-.924-.284-1.348-.404h-.115V9.478a25.485 25.485 0 0 0 4.238-5.514 1.8 1.8 0 0 1 .901-.83 1.74 1.74 0 0 1 1.21-.048c.396.13.736.397.96.757.225.36.32.788.269 1.211l-1.562 4.63ZM4.177 10H7v8a2 2 0 1 1-4 0v-6.823C3 10.527 3.527 10 4.176 10Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Like response</span>
                </button>
                <div id="tooltip-like-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Like
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
                <button type="button" data-tooltip-target="tooltip-unline-chat-1" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="{{ $attributes->get('class', '') }} w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.97 14.316H5.004c-.322 0-.64-.08-.925-.232a2.022 2.022 0 0 1-.717-.645 2.108 2.108 0 0 1-.242-1.883l2.36-7.201C5.769 3.54 5.96 3 7.365 3c2.072 0 4.276.678 6.156 1.256.473.145.925.284 1.35.404h.114v9.862a25.485 25.485 0 0 0-4.238 5.514c-.197.376-.516.67-.901.83a1.74 1.74 0 0 1-1.21.048 1.79 1.79 0 0 1-.96-.757 1.867 1.867 0 0 1-.269-1.211l1.562-4.63ZM19.822 14H17V6a2 2 0 1 1 4 0v6.823c0 .65-.527 1.177-1.177 1.177Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="{{ $attributes->get('class', '') }} sr-only">Unline response</span>
                </button>
                <div id="tooltip-unline-chat-1" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                    Unlike
                    <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
                </div>
            </div>
        </div>
        <button type="button" data-modal-target="editChatModal" data-modal-toggle="editChatModal" data-tooltip-target="editChatTooltip6" class="{{ $attributes->get('class', '') }} opacity-0 absolute top-4 right-4 group-hover:opacity-100 transition-opacity rounded-lg p-1.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            <svg class="{{ $attributes->get('class', '') }} h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd"></path>
                <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <div id="editChatTooltip6" role="tooltip" class="{{ $attributes->get('class', '') }} tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            Edit prompt
            <div class="{{ $attributes->get('class', '') }} tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
  
    </div>
  </div>

  <div class="{{ $attributes->get('class', '') }} absolute bottom-0 left-0 w-full">
    <div class="{{ $attributes->get('class', '') }} w-full border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-700">
      <div class="{{ $attributes->get('class', '') }} flex items-center gap-4 bg-white px-4 py-3 dark:bg-gray-800">
        <label for="ai-chat-input" class="{{ $attributes->get('class', '') }} sr-only">Write message</label>
        <input type="text" placeholder="Write a prompt..." autofocus name="" id="ai-chat-input" class="{{ $attributes->get('class', '') }} block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"></textarea>
        <button type="submit" class="{{ $attributes->get('class', '') }} inline-flex cursor-pointer justify-center rounded-full p-2 text-primary-600 hover:bg-primary-100 dark:text-primary-500 dark:hover:bg-gray-600">
          <svg class="{{ $attributes->get('class', '') }} h-4 w-4 rotate-90 rtl:-rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
            <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z" />
          </svg>
          <span class="{{ $attributes->get('class', '') }} sr-only">Send message</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div id="editChatModal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="{{ $attributes->get('class', '') }} relative p-4 w-full max-w-md max-h-full">
      <!-- Modal content -->
      <div class="{{ $attributes->get('class', '') }} relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
          <!-- Modal header -->
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between px-4 pt-4 pb-0">
              <h3 class="{{ $attributes->get('class', '') }} text-lg font-semibold text-gray-900 dark:text-white">
                  Edit prompt
              </h3>
              <button type="button" class="{{ $attributes->get('class', '') }} text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="editChatModal">
                  <svg class="{{ $attributes->get('class', '') }} w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                  </svg>
                  <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
              </button>
          </div>
          <!-- Modal body -->
          <form class="{{ $attributes->get('class', '') }} p-4 md:p-5">
              <div class="{{ $attributes->get('class', '') }} grid gap-4 mb-4 md:mb-5 grid-cols-2">
                  <div class="{{ $attributes->get('class', '') }} col-span-2">
                      <label for="prompt" class="{{ $attributes->get('class', '') }} sr-only block mb-2 text-sm font-medium text-gray-900 dark:text-white">Prompt content</label>
                      <textarea id="prompt" rows="4" class="{{ $attributes->get('class', '') }} block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Update the prompt content here" required></textarea>                    
                  </div>
              </div>
              <button type="submit" class="{{ $attributes->get('class', '') }} text-white inline-flex items-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                  Update prompt
              </button>
              <button data-modal-hide="editChatModal" type="button" class="{{ $attributes->get('class', '') }} py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Cancel</button>
          </form>
      </div>
  </div>
</div> 

<!-- drawer component -->
<div id="drawer-chat-history" class="{{ $attributes->get('class', '') }} fixed top-0 right-0 z-40 h-screen p-4 overflow-y-auto transition-transform translate-x-full bg-white w-80 dark:bg-gray-800" tabindex="-1" aria-labelledby="drawer-right-label">
  <div class="{{ $attributes->get('class', '') }} border-b border-gray-200 dark:border-gray-700">
    <h5 id="drawer-right-label" class="{{ $attributes->get('class', '') }} inline-flex items-center mb-4 text-base font-semibold text-gray-500 dark:text-gray-400">Chat History</h5>
    <button type="button" data-drawer-hide="drawer-chat-history" aria-controls="drawer-chat-history" class="{{ $attributes->get('class', '') }} text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 absolute top-2.5 end-2.5 inline-flex items-center justify-center dark:hover:bg-gray-600 dark:hover:text-white" >
        <svg class="{{ $attributes->get('class', '') }} w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
        </svg>
        <span class="{{ $attributes->get('class', '') }} sr-only">Close menu</span>
    </button>
  </div>
  
  <div class="{{ $attributes->get('class', '') }} my-5 space-y-5 h-[calc(100vh-16rem)] overflow-y-scroll">
    <div>
      <h6 class="{{ $attributes->get('class', '') }} inline-flex items-center mb-4 text-base font-medium text-gray-500 dark:text-gray-400">Today</h6>
      <ul class="{{ $attributes->get('class', '') }} space-y-2">
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Flowbite: Intuitive AI Chatroom</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Sample Daily Schedule</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Billing Page Content</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Redesign Homepage</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">DMCA Removal</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Figma: Variants vs Text</span>
          </a>
        </li>
      </ul>
    </div>
    <div>
      <h6 class="{{ $attributes->get('class', '') }} inline-flex items-center mb-4 text-base font-medium text-gray-500 dark:text-gray-400">Yesterday</h6>
      <ul class="{{ $attributes->get('class', '') }} space-y-2">
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Flowbite: Intuitive AI Chatroom</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Sample Daily Schedule</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Billing Page Content</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Redesign Homepage</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">DMCA Removal</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Figma: Variants vs Text</span>
          </a>
        </li>
      </ul>
    </div>
    <div>
      <h6 class="{{ $attributes->get('class', '') }} inline-flex items-center mb-4 text-base font-medium text-gray-500 dark:text-gray-400">August</h6>
      <ul class="{{ $attributes->get('class', '') }} space-y-2">
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Flowbite: Intuitive AI Chatroom</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Sample Daily Schedule</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Billing Page Content</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Redesign Homepage</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">DMCA Removal</span>
          </a>
        </li>
        <li>
          <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
            <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10.5h.01m-4.01 0h.01M8 10.5h.01M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-6.6a1 1 0 0 0-.69.275l-2.866 2.723A.5.5 0 0 1 8 18.635V17a1 1 0 0 0-1-1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Figma: Variants vs Text</span>
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div>
    <ul class="{{ $attributes->get('class', '') }} space-y-2 mb-4">
      <li>
        <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
          <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9.586 2.586A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2v.089l.473.196.063-.063a2.002 2.002 0 0 1 2.828 0l1.414 1.414a2 2 0 0 1 0 2.827l-.063.064.196.473H20a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-.089l-.196.473.063.063a2.002 2.002 0 0 1 0 2.828l-1.414 1.414a2 2 0 0 1-2.828 0l-.063-.063-.473.196V20a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-.089l-.473-.196-.063.063a2.002 2.002 0 0 1-2.828 0l-1.414-1.414a2 2 0 0 1 0-2.827l.063-.064L4.089 15H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h.09l.195-.473-.063-.063a2 2 0 0 1 0-2.828l1.414-1.414a2 2 0 0 1 2.827 0l.064.063L9 4.089V4a2 2 0 0 1 .586-1.414ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
          </svg>
          <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">User settings</span>
        </a>
      </li>
      <li>
        <a href="#" class="{{ $attributes->get('class', '') }} flex items-center p-1.5 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-700">
          <svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path d="m7.4 3.736 3.43 3.429A5.046 5.046 0 0 1 12.133 7c.356.01.71.06 1.056.147l3.41-3.412a2.32 2.32 0 0 1 .451-.344A9.89 9.89 0 0 0 12.268 2a10.022 10.022 0 0 0-5.322 1.392c.165.095.318.211.454.344Zm11.451 1.54-.127-.127a.5.5 0 0 0-.706 0l-2.932 2.932c.03.023.05.054.078.077.237.194.454.41.651.645.033.038.077.067.11.107l2.926-2.927a.5.5 0 0 0 0-.707Zm-2.931 9.81c-.025.03-.058.052-.082.082a4.97 4.97 0 0 1-.633.639c-.04.036-.072.083-.115.117l2.927 2.927a.5.5 0 0 0 .707 0l.127-.127a.5.5 0 0 0 0-.707l-2.932-2.931Zm-1.443-4.763a3.037 3.037 0 0 0-1.383-1.1l-.012-.007a2.956 2.956 0 0 0-1-.213H12a2.964 2.964 0 0 0-2.122.893c-.285.29-.509.634-.657 1.013l-.009.016a2.96 2.96 0 0 0-.21 1 2.99 2.99 0 0 0 .488 1.716l.032.04a3.04 3.04 0 0 0 1.384 1.1l.012.007c.319.129.657.2 1 .213.393.015.784-.05 1.15-.192.012-.005.021-.013.033-.018a3.01 3.01 0 0 0 1.676-1.7v-.007a2.89 2.89 0 0 0 0-2.207 2.868 2.868 0 0 0-.27-.515c-.007-.012-.02-.025-.03-.039Zm6.137-3.373a2.53 2.53 0 0 1-.349.447l-3.426 3.426c.112.428.166.869.161 1.311a4.954 4.954 0 0 1-.148 1.054l3.413 3.412c.133.134.249.283.347.444A9.88 9.88 0 0 0 22 12.269a9.913 9.913 0 0 0-1.386-5.319ZM16.6 20.264l-3.42-3.421c-.386.1-.782.152-1.18.157h-.135c-.356-.01-.71-.06-1.056-.147L7.4 20.265a2.503 2.503 0 0 1-.444.347A9.884 9.884 0 0 0 11.732 22H12a9.9 9.9 0 0 0 5.044-1.388 2.515 2.515 0 0 1-.444-.348ZM3.735 16.6l3.426-3.426a4.608 4.608 0 0 1-.013-2.367L3.735 7.4a2.508 2.508 0 0 1-.349-.447 9.889 9.889 0 0 0 0 10.1 2.48 2.48 0 0 1 .35-.453Zm5.101-.758a4.959 4.959 0 0 1-.65-.645c-.034-.038-.078-.067-.11-.107L5.15 18.017a.5.5 0 0 0 0 .707l.127.127a.5.5 0 0 0 .706 0l2.932-2.933c-.029-.018-.049-.053-.078-.076Zm-.755-6.928c.03-.037.07-.063.1-.1.183-.22.383-.423.6-.609.046-.04.081-.092.128-.13L5.983 5.149a.5.5 0 0 0-.707 0l-.127.127a.5.5 0 0 0 0 .707l2.932 2.931Z"/>
          </svg>
          <span class="{{ $attributes->get('class', '') }} dark:text-white text-base font-medium text-gray-900">Help & Getting Started</span>
        </a>
      </li>
    </ul>
    <a href="#" class="{{ $attributes->get('class', '') }} py-2.5 px-5 inline-flex items-center justify-center w-full text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"><svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
      <path fill-rule="evenodd" d="M20.337 3.664c.213.212.354.486.404.782.294 1.711.657 5.195-.906 6.76-1.77 1.768-8.485 5.517-10.611 6.683a.987.987 0 0 1-1.176-.173l-.882-.88-.877-.884a.988.988 0 0 1-.173-1.177c1.165-2.126 4.913-8.841 6.682-10.611 1.562-1.563 5.046-1.198 6.757-.904.296.05.57.191.782.404ZM5.407 7.576l4-.341-2.69 4.48-2.857-.334a.996.996 0 0 1-.565-1.694l2.112-2.111Zm11.357 7.02-.34 4-2.111 2.113a.996.996 0 0 1-1.69-.565l-.422-2.807 4.563-2.74Zm.84-6.21a1.99 1.99 0 1 1-3.98 0 1.99 1.99 0 0 1 3.98 0Z" clip-rule="evenodd"/>
    </svg>
    Upgrade to Pro</a>
  </div>
</div>

<div id="settings-modal" tabindex="-1" aria-hidden="true" class="{{ $attributes->get('class', '') }} hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="{{ $attributes->get('class', '') }} relative p-4 w-full max-w-3xl max-h-full">
      <!-- Modal content -->
      <div class="{{ $attributes->get('class', '') }} relative bg-white rounded-lg shadow-sm dark:bg-gray-800">
          <!-- Modal header -->
          <div class="{{ $attributes->get('class', '') }} flex items-center justify-between px-4 pt-4 md:px-5 md:pt-5 rounded-t">
              <h3 class="{{ $attributes->get('class', '') }} text-xl font-semibold text-gray-900 dark:text-white">
                  Chat settings
              </h3>
              <button type="button" class="{{ $attributes->get('class', '') }} text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="settings-modal">
                  <svg class="{{ $attributes->get('class', '') }} w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                  </svg>
                  <span class="{{ $attributes->get('class', '') }} sr-only">Close modal</span>
              </button>
          </div>
          <!-- Modal body -->
          <div class="{{ $attributes->get('class', '') }} p-4 md:p-5">
            <div class="{{ $attributes->get('class', '') }} border-y py-5 border-gray-200 dark:border-gray-700">
              <ul data-tabs-active-classes="!text-white !bg-primary-700 dark:!bg-primary-600" class="{{ $attributes->get('class', '') }} grid grid-cols-2 gap-4 text-sm font-medium  md:grid-cols-4" id="settings-tab" data-tabs-toggle="#settings-tab-content" role="tablist">
                <li role="presentation">
                  <button class="{{ $attributes->get('class', '') }} w-full inline-flex items-center justify-center rounded-lg bg-gray-50 px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white" id="general-tab" data-tabs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="false"><svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13v-2a1 1 0 0 0-1-1h-.757l-.707-1.707.535-.536a1 1 0 0 0 0-1.414l-1.414-1.414a1 1 0 0 0-1.414 0l-.536.535L14 4.757V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.757l-1.707.707-.536-.535a1 1 0 0 0-1.414 0L4.929 6.343a1 1 0 0 0 0 1.414l.536.536L4.757 10H4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.757l.707 1.707-.535.536a1 1 0 0 0 0 1.414l1.414 1.414a1 1 0 0 0 1.414 0l.536-.535 1.707.707V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.757l1.707-.708.536.536a1 1 0 0 0 1.414 0l1.414-1.414a1 1 0 0 0 0-1.414l-.535-.536.707-1.707H20a1 1 0 0 0 1-1Z"/>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                  </svg>
                  General</button>
                </li>
                <li role="presentation">
                  <button class="{{ $attributes->get('class', '') }} w-full inline-flex items-center justify-center rounded-lg bg-gray-50 px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white" id="data-controls-tab" data-tabs-target="#data-controls" type="button" role="tab" aria-controls="data-controls" aria-selected="false"><svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v14m6-8h-6m6 4h-6m-9-3h1.99093M4 19h16c.5523 0 1-.4477 1-1V6c0-.55228-.4477-1-1-1H4c-.55228 0-1 .44772-1 1v12c0 .5523.44772 1 1 1Zm8-7c0 1.1046-.8954 2-2 2-1.10457 0-2-.8954-2-2s.89543-2 2-2c1.1046 0 2 .8954 2 2Z"/>
                  </svg>
                  Data <span class="{{ $attributes->get('class', '') }} hidden sm:inline ms-1"> controls</span></button>
                </li>
                <li role="presentation">
                  <button class="{{ $attributes->get('class', '') }} w-full inline-flex items-center justify-center rounded-lg bg-gray-50 px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400  dark:hover:text-white" id="applications-tab" data-tabs-target="#applications" type="button" role="tab" aria-controls="applications" aria-selected="false"><svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.143 4H4.857A.857.857 0 0 0 4 4.857v4.286c0 .473.384.857.857.857h4.286A.857.857 0 0 0 10 9.143V4.857A.857.857 0 0 0 9.143 4Zm10 0h-4.286a.857.857 0 0 0-.857.857v4.286c0 .473.384.857.857.857h4.286A.857.857 0 0 0 20 9.143V4.857A.857.857 0 0 0 19.143 4Zm-10 10H4.857a.857.857 0 0 0-.857.857v4.286c0 .473.384.857.857.857h4.286a.857.857 0 0 0 .857-.857v-4.286A.857.857 0 0 0 9.143 14Zm10 0h-4.286a.857.857 0 0 0-.857.857v4.286c0 .473.384.857.857.857h4.286a.857.857 0 0 0 .857-.857v-4.286a.857.857 0 0 0-.857-.857Z"/>
                  </svg>
                  Applications</button>
                </li>
                <li role="presentation">
                  <button class="{{ $attributes->get('class', '') }} w-full inline-flex items-center justify-center rounded-lg bg-gray-50 px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white" id="security-tab" data-tabs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false"><svg class="{{ $attributes->get('class', '') }} w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a28.076 28.076 0 0 1-1.091 9M7.231 4.37a8.994 8.994 0 0 1 12.88 3.73M2.958 15S3 14.577 3 12a8.949 8.949 0 0 1 1.735-5.307m12.84 3.088A5.98 5.98 0 0 1 18 12a30 30 0 0 1-.464 6.232M6 12a6 6 0 0 1 9.352-4.974M4 21a5.964 5.964 0 0 1 1.01-3.328 5.15 5.15 0 0 0 .786-1.926m8.66 2.486a13.96 13.96 0 0 1-.962 2.683M7.5 19.336C9 17.092 9 14.845 9 12a3 3 0 1 1 6 0c0 .749 0 1.521-.031 2.311M12 12c0 3 0 6-2 9"/>
                  </svg>
                  Security</button>
                </li>
              </ul>
            </div>
            <div id="settings-tab-content">
              <div class="{{ $attributes->get('class', '') }} hidden" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                    <div>
                      <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Theme</h6>
                      <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Adapt your display with Light/Dark mode options</p>
                    </div>
                    <div class="{{ $attributes->get('class', '') }} shrink-0">
                      <button
                        id="dropdowThemeButton"
                        data-dropdown-toggle="dropdownTheme"
                        class="{{ $attributes->get('class', '') }} flex items-center rounded-full p-1.5 me-2 text-sm font-medium text-gray-900 hover:text-primary-600 focus:ring-4 focus:ring-gray-100 dark:text-white dark:hover:text-primary-500 dark:focus:ring-gray-700"
                        type="button"
                      >
                        <span class="{{ $attributes->get('class', '') }} sr-only">Open user menu</span>
                       System
                        <svg class="{{ $attributes->get('class', '') }} mx-1.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                      </button>
                      <!-- Dropdown menu -->
                      <div id="dropdownTheme" class="{{ $attributes->get('class', '') }} z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" role="none">
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">System settings</a>
                          </li>
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Light mode</a>
                          </li>
                          <li>
                            <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Dark mode</a>
                          </li>
                        </ul>
                      </div>
                    </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                    <div>
                      <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Show code</h6>
                      <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Always show code when using data analyst</p>
                    </div>
                    <div class="{{ $attributes->get('class', '') }} shrink-0">
                      <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer" checked>
                        <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                      </label>
                    </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Language</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Select a language to display content in your choice.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button
                      id="dropdowChatLanguageButton"
                      data-dropdown-toggle="dropdownChatLanguage"
                      class="{{ $attributes->get('class', '') }} flex items-center rounded-full p-1.5 me-2 text-sm font-medium text-gray-900 hover:text-primary-600 focus:ring-4 focus:ring-gray-100 dark:text-white dark:hover:text-primary-500 dark:focus:ring-gray-700"
                      type="button"
                    >
                      <span class="{{ $attributes->get('class', '') }} sr-only">Open user menu</span>
                      English (US)
                      <svg class="{{ $attributes->get('class', '') }} mx-1.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                      </svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="dropdownChatLanguage" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                      <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" role="none">
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">German</a>
                        </li>
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Spanish</a>
                        </li>
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">French</a>
                        </li>
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">English</a>
                        </li>
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Chinese</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                    <div>
                      <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Archive all chats</h6>
                      <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Store all chats in the archive without deleting them.</p>
                    </div>
                    <div class="{{ $attributes->get('class', '') }} shrink-0">
                      <label class="{{ $attributes->get('class', '') }} inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="{{ $attributes->get('class', '') }} sr-only peer">
                        <div class="{{ $attributes->get('class', '') }} relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                      </label>
                    </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                    <div>
                      <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Delete chats</h6>
                      <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Permanently delete all chats from your inbox.</p>
                    </div>
                    <div class="{{ $attributes->get('class', '') }} shrink-0">
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800"
                      >
                        Delete
                      </button>
                    </div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} hidden" id="data-controls" role="tabpanel" aria-labelledby="data-controls-tab">
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Improve model for everyone</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Allow your content to be used to train our models, which makes ChatGPT better for you and everyone who uses it.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button
                      id="dropdownModelSettingsButton"
                      data-dropdown-toggle="dropdownModelSettings"
                      class="{{ $attributes->get('class', '') }} flex items-center rounded-full p-1.5 me-2 text-sm font-medium text-gray-900 hover:text-primary-600 focus:ring-4 focus:ring-gray-100 dark:text-white dark:hover:text-primary-500 dark:focus:ring-gray-700"
                      type="button"
                    >
                      <span class="{{ $attributes->get('class', '') }} sr-only">Open user menu</span>
                     On
                      <svg class="{{ $attributes->get('class', '') }} mx-1.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                      </svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="dropdownModelSettings" class="{{ $attributes->get('class', '') }} z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                      <ul class="{{ $attributes->get('class', '') }} p-2 text-sm font-medium text-gray-500 dark:text-gray-400" role="none">
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Turn off</a>
                        </li>
                        <li>
                          <a href="#" class="{{ $attributes->get('class', '') }} inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Turn on</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Shared links</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Access the links exchanged in this conversation.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto">
                      Manage
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Export data</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Export your account data for external use.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto">
                      <svg class="{{ $attributes->get('class', '') }} w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2"/>
                      </svg>
                      Export
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                    <div>
                      <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Delete account</h6>
                      <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400">Choose to permanently delete your account and data.</p>
                    </div>
                    <div class="{{ $attributes->get('class', '') }} shrink-0">
                      <button
                        type="button"
                        class="{{ $attributes->get('class', '') }} rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800"
                      >
                        Delete
                      </button>
                    </div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} hidden" id="applications" role="tabpanel" aria-labelledby="applications-tab">
                <h6 class="{{ $attributes->get('class', '') }} text-sm text-gray-900 dark:text-white font-medium py-5 border-b border-gray-200 dark:border-gray-700">Connect apps to access their information in ChatGPT.</h6>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-3 flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="{{ $attributes->get('class', '') }} me-2 h-6" viewBox="0 0 1443.061 1249.993">
                      <path fill="#3777e3" d="M240.525 1249.993l240.492-416.664h962.044l-240.514 416.664z"></path>
                      <path fill="#ffcf63" d="M962.055 833.329h481.006L962.055 0H481.017z"></path>
                      <path fill="#11a861" d="M0 833.329l240.525 416.664 481.006-833.328L481.017 0z"></path>
                    </svg>Google Drive</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Access the links exchanged in this conversation.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-primary-700 bg-white px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-100 dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:bg-primary-600 dark:hover:border-primary-600 dark:hover:text-white dark:focus:ring-primary-700">
                      Connect
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-3 flex items-center"><svg class="{{ $attributes->get('class', '') }} me-2 h-8" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                      <path fill="currentColor" d="M 14.728516 4.0078125 C 10.197246 4.3417754 1.9458438 14.28525 5.1054688 16.953125 L 5.8945312 17.623047 C 5.6815312 18.194047 5.6067813 18.808062 5.6757812 19.414062 C 5.8557812 21.199063 7.5991562 22.644578 9.2851562 22.642578 C 12.371156 29.755578 23.605672 29.766734 26.513672 22.802734 C 26.605672 22.562734 27 21.485156 27 20.535156 C 27 19.585156 26.460234 19.185547 26.115234 19.185547 C 25.936234 18.558547 25.820516 18.486406 25.603516 18.191406 C 25.734516 17.995406 26.420453 16.907906 25.439453 15.878906 C 24.883453 15.294906 23.775625 14.892156 23.390625 14.785156 C 23.310625 14.176156 23.639234 11.646656 22.240234 10.347656 C 23.351234 9.1966563 24.002 7.9288437 24 6.8398438 C 23.997 4.7468438 21.470734 4.1128281 18.302734 5.4238281 L 17.630859 5.7089844 C 17.627859 5.7059844 16.416438 4.5199063 16.398438 4.5039062 C 15.947062 4.1101563 15.37584 3.9601035 14.728516 4.0078125 z M 15.140625 4.8730469 C 15.270432 4.8793362 15.393688 4.898875 15.507812 4.9296875 C 15.804813 5.0136875 16.787109 6.1523438 16.787109 6.1523438 C 16.787109 6.1523438 14.961531 7.1641719 13.269531 8.5761719 C 10.988531 10.333172 9.266375 12.885297 8.234375 15.654297 C 7.424375 15.812297 6.7084844 16.272203 6.2714844 16.908203 C 6.0104844 16.690203 5.5244531 16.266469 5.4394531 16.105469 C 4.7444531 14.777469 6.19975 12.203047 7.21875 10.748047 C 9.579375 7.3786719 13.193525 4.7787073 15.140625 4.8730469 z M 18.326172 8.1425781 C 18.349172 8.1405781 18.367141 8.1596406 18.369141 8.1816406 C 18.370141 8.1956406 18.364516 8.2077969 18.353516 8.2167969 C 18.199516 8.3357969 18.060406 8.4758594 17.941406 8.6308594 C 17.928406 8.6488594 17.933172 8.6725469 17.951172 8.6855469 C 17.958172 8.6905469 17.965609 8.6923594 17.974609 8.6933594 C 18.632609 8.6983594 19.559016 8.9285781 20.166016 9.2675781 C 20.207016 9.2905781 20.178813 9.369375 20.132812 9.359375 C 16.414813 8.507375 13.552406 10.351922 12.941406 10.794922 C 12.922406 10.806922 12.898719 10.80025 12.886719 10.78125 C 12.876719 10.76625 12.877672 10.748375 12.888672 10.734375 L 12.884766 10.734375 C 13.779766 9.700375 14.882141 8.801875 15.869141 8.296875 C 15.888141 8.285875 15.910875 8.2915469 15.921875 8.3105469 C 15.928875 8.3225469 15.928875 8.3366094 15.921875 8.3496094 C 15.843875 8.4916094 15.692531 8.7953906 15.644531 9.0253906 C 15.639531 9.0473906 15.653781 9.0692188 15.675781 9.0742188 C 15.686781 9.0772188 15.698031 9.0743594 15.707031 9.0683594 C 16.321031 8.6493594 17.390172 8.2005781 18.326172 8.1425781 z M 21.507812 11.361328 C 21.631563 11.381828 21.751375 11.427094 21.859375 11.496094 C 22.394375 11.851094 22.468094 12.710844 22.496094 13.339844 C 22.512094 13.698844 22.555313 14.568406 22.570312 14.816406 C 22.604312 15.386406 22.754594 15.467453 23.058594 15.564453 C 23.229594 15.620453 23.386141 15.662516 23.619141 15.728516 C 24.325141 15.926516 24.593328 16.231234 24.861328 16.490234 C 24.997328 16.624234 25.087234 16.797328 25.115234 16.986328 C 25.198234 17.594328 24.792266 18.241828 23.322266 18.923828 C 20.825266 20.082828 18.314172 19.695297 17.951172 19.654297 C 16.874172 19.509297 16.26025 20.902469 16.90625 21.855469 C 18.11625 23.641469 23.448094 22.924844 24.996094 20.714844 C 25.033094 20.661844 25.002031 20.628156 24.957031 20.660156 C 22.724031 22.188156 19.770797 22.702781 18.091797 22.050781 C 17.836797 21.951781 17.303281 21.707156 17.238281 21.160156 C 19.569281 21.881156 21.033203 21.199219 21.033203 21.199219 C 21.033203 21.199219 21.141906 21.0485 21.003906 21.0625 C 21.003906 21.0625 19.093109 21.344594 17.287109 20.683594 C 17.619109 19.605594 18.729453 21.009313 22.439453 19.945312 C 23.256453 19.711312 24.181578 19.354359 25.017578 18.693359 C 25.264578 18.950359 25.473875 19.442672 25.546875 19.888672 C 25.742875 19.853672 26.308203 19.859469 26.158203 20.855469 C 25.982203 21.917469 25.532437 22.780219 24.773438 23.574219 C 24.299437 24.088219 23.734422 24.510312 23.107422 24.820312 C 22.758422 25.003312 22.395484 25.156297 22.021484 25.279297 C 19.161484 26.213297 16.231156 25.186469 15.285156 22.980469 C 15.208156 22.812469 15.145703 22.637984 15.095703 22.458984 C 14.692703 21.004984 15.035516 19.260109 16.103516 18.162109 C 16.169516 18.092109 16.236328 18.01025 16.236328 17.90625 C 16.227328 17.81725 16.190812 17.733062 16.132812 17.664062 C 15.758812 17.123062 14.466563 16.199109 14.726562 14.412109 C 14.913562 13.129109 16.035031 12.225297 17.082031 12.279297 L 17.349609 12.294922 C 17.802609 12.321922 18.198266 12.379531 18.572266 12.394531 C 19.197266 12.421531 19.758828 12.331344 20.423828 11.777344 C 20.648828 11.590344 20.828812 11.427953 21.132812 11.376953 C 21.256813 11.345953 21.384062 11.340828 21.507812 11.361328 z M 21.306641 13.529297 C 20.973641 13.583297 20.961078 13.997359 21.080078 14.568359 C 21.147078 14.889359 21.266391 15.162031 21.400391 15.332031 C 21.565391 15.311031 21.731484 15.311031 21.896484 15.332031 C 21.985484 15.127031 22.001875 14.775578 21.921875 14.392578 C 21.802875 13.822578 21.639641 13.475297 21.306641 13.529297 z M 17.488281 15.083984 C 17.206156 15.117984 16.931812 15.222984 16.695312 15.396484 C 16.535312 15.513484 16.38425 15.675437 16.40625 15.773438 C 16.45125 15.973437 16.942234 15.628891 17.615234 15.587891 C 17.989234 15.564891 18.299109 15.681156 18.537109 15.785156 C 18.776109 15.892156 18.922469 15.960391 18.980469 15.900391 C 19.081469 15.796391 18.794031 15.397219 18.332031 15.199219 C 18.061031 15.086719 17.770406 15.049984 17.488281 15.083984 z M 21.789062 15.878906 L 21.789062 15.880859 C 21.380063 15.873859 21.364391 16.725422 21.775391 16.732422 C 22.186391 16.739422 22.201063 15.886906 21.789062 15.878906 z M 18.056641 16.060547 C 17.571641 16.136547 17.253937 16.408609 17.335938 16.599609 C 17.383937 16.617609 17.398281 16.642641 17.613281 16.556641 C 17.932281 16.436641 18.278234 16.401125 18.615234 16.453125 C 18.771234 16.471125 18.844906 16.481734 18.878906 16.427734 C 18.956906 16.308734 18.574641 15.999547 18.056641 16.060547 z M 20.410156 16.451172 C 20.315906 16.462172 20.23275 16.506859 20.1875 16.599609 C 20.005948 16.968698 20.767752 17.339063 20.951172 16.974609 C 21.086922 16.699359 20.692906 16.418172 20.410156 16.451172 z M 8.8964844 16.480469 C 9.8666833 16.511085 10.816984 17.196219 11.044922 18.667969 C 11.287922 20.251969 10.794703 21.794812 9.2207031 21.757812 L 9.2226562 21.759766 C 8.0026562 21.726766 6.6856875 20.628172 6.5546875 19.326172 C 6.3690625 17.482859 7.6490857 16.441105 8.8964844 16.480469 z M 8.8261719 17.400391 C 8.4667969 17.389641 8.1212344 17.549672 7.9277344 17.763672 C 7.4617344 18.276672 7.4611563 19.021781 7.6601562 19.050781 C 7.8881562 19.080781 7.8786875 18.705687 8.0546875 18.429688 C 8.2606875 18.111687 8.6859063 18.020563 9.0039062 18.226562 C 9.0059063 18.227563 9.0077656 18.228469 9.0097656 18.230469 C 9.6297656 18.636469 9.0818594 19.178766 9.1308594 19.759766 C 9.2048594 20.651766 10.116156 20.634234 10.285156 20.240234 C 10.307156 20.200234 10.302438 20.151234 10.273438 20.115234 C 10.275438 20.163234 10.310703 20.047703 10.095703 20.095703 L 10.09375 20.097656 C 10.02275 20.114656 9.772625 20.176656 9.640625 19.972656 C 9.362625 19.544656 10.234781 18.883547 9.8007812 18.060547 C 9.5577812 17.593547 9.1855469 17.411141 8.8261719 17.400391 z"></path>
                    </svg>Mailchimp</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Export your account data for external use.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-primary-700 bg-white px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-100 dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:bg-primary-600 dark:hover:border-primary-600 dark:hover:text-white dark:focus:ring-primary-700">
                      Connect
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-3 flex items-center"><svg class="{{ $attributes->get('class', '') }} me-2 h-8" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M20.5457 11.771C20.4917 12.1432 20.2463 12.6489 19.9647 13.0885C19.6094 13.6432 19.0457 14.0317 18.4005 14.1667L15.5584 14.761C15.1016 14.8565 14.6874 15.0955 14.3764 15.4432L12.5129 17.5262C12.1627 17.9177 11.9179 17.8243 11.9179 17.2993C11.9137 17.3187 10.993 19.6929 13.4328 21.1002C14.3702 21.6409 15.7197 21.4466 16.6571 20.9059L21.6241 18.0409C23.4817 16.9695 24.7932 15.1569 25.2292 13.0587C25.2464 12.9757 25.2597 12.8924 25.2741 12.8092L20.5457 11.771Z" fill="url(#paint0_linear_1684_75606)"></path>
                      <path d="M19.2573 8.90907C20.1947 9.44978 20.5777 10.2548 20.5777 11.3362C20.5777 11.483 20.5662 11.6281 20.5454 11.7709L22.5468 12.6306L25.2738 12.8091C25.623 10.7882 24.9387 8.70802 23.7119 7.05457C22.7887 5.81028 21.6007 4.73047 20.1726 3.90669C19.0124 3.23751 17.8068 2.79661 16.5955 2.55469L15.2346 4.3171L14.8047 6.34082L19.2573 8.90907Z" fill="url(#paint1_linear_1684_75606)"></path>
                      <path d="M3.23839 10.3922C3.23788 10.3938 3.23929 10.3942 3.23982 10.3926C3.34484 10.0776 3.47652 9.72623 3.64092 9.35082C4.49967 7.38979 6.15454 6.08189 8.19043 5.41503C10.2263 4.74819 12.4492 4.98194 14.3047 6.05218L14.8052 6.34086L16.596 2.55474C10.9001 1.4171 5.07859 4.76349 3.24563 10.3702C3.24476 10.3729 3.24157 10.3823 3.23839 10.3922Z" fill="url(#paint2_linear_1684_75606)"></path>
                      <path d="M16.4641 20.9057C15.5266 21.4464 14.3717 21.4464 13.4342 20.9057C13.307 20.8323 13.187 20.7497 13.0736 20.6604L11.4249 21.7939L10.0039 24.0901C11.5811 25.4027 13.6292 25.8246 15.6759 25.5899C17.2161 25.4133 18.7461 24.9254 20.1743 24.1016C21.3344 23.4325 22.3194 22.6098 23.1348 21.6828L22.2875 19.6241L20.9166 18.3374L16.4641 20.9057Z" fill="url(#paint3_linear_1684_75606)"></path>
                      <path d="M13.0739 20.6603C12.3516 20.0914 11.9196 19.2188 11.9196 18.2842V18.191V10.4069C11.9196 9.96672 12.0493 9.8919 12.4309 10.112C11.8432 9.773 10.4854 8.60922 8.80899 9.5762C7.87156 10.1169 7.09961 11.3105 7.09961 12.3919V18.1219C7.09961 20.2648 8.20952 22.5001 9.81036 23.9264C9.87368 23.9828 9.93924 24.036 10.0042 24.09L13.0739 20.6603Z" fill="url(#paint4_linear_1684_75606)"></path>
                      <path d="M23.0191 6.20711C23.018 6.20587 23.0169 6.20687 23.018 6.20811C23.2386 6.45647 23.4773 6.74611 23.7206 7.07606C24.9911 8.79957 25.4658 10.9825 25.026 13.0773C24.5861 15.1722 23.272 16.9786 21.4165 18.0489L20.916 18.3376L23.1342 21.6829C26.9683 17.3236 26.9782 10.6136 23.0346 6.22435C23.0327 6.22225 23.0261 6.21482 23.0191 6.20711Z" fill="url(#paint5_linear_1684_75606)"></path>
                      <path d="M7.2935 12.3921C7.2935 11.3106 7.87098 10.3113 8.80842 9.77063C8.93567 9.69723 9.06719 9.6347 9.20136 9.58122L9.04315 7.58804L7.93158 5.30762C6.00518 6.01596 4.44693 7.47985 3.62703 9.36799C3.01002 10.7889 2.66798 12.3566 2.66797 14.0042C2.66797 15.3425 2.88858 16.6061 3.28451 17.7751L5.49272 18.0713L7.2935 17.5286V12.3921V12.3921Z" fill="url(#paint6_linear_1684_75606)"></path>
                      <path d="M9.20143 9.58136C10.0557 9.24093 11.0281 9.30347 11.8383 9.77078L11.9191 9.81738L18.4054 13.5587C18.8604 13.8211 18.819 14.0794 18.3048 14.1869L18.6789 14.1087C19.1713 14.0057 19.6211 13.7541 19.9651 13.3874C20.5566 12.757 20.7737 11.9964 20.7737 11.3363C20.7737 10.2549 20.1962 9.25559 19.2588 8.71488L14.2917 5.84988C12.4342 4.77843 10.2072 4.54996 8.17042 5.22188C8.08985 5.24845 8.01098 5.27859 7.93164 5.30776L9.20143 9.58136Z" fill="url(#paint7_linear_1684_75606)"></path>
                      <path d="M16.7578 25.4137C16.7594 25.4133 16.7591 25.4119 16.7575 25.4122C16.4319 25.4788 16.0615 25.5406 15.6539 25.5861C13.5246 25.8236 11.395 25.1429 9.79898 23.7149C8.20299 22.2868 7.29415 20.2467 7.29416 18.1062L7.29415 17.5288L3.28516 17.7753C5.14698 23.2723 10.9585 26.6359 16.7352 25.4184C16.7379 25.4178 16.7476 25.4159 16.7578 25.4137Z" fill="url(#paint8_linear_1684_75606)"></path>
                      <defs>
                        <linearGradient id="paint0_linear_1684_75606" x1="17.294" y1="19.6659" x2="19.5891" y2="11.6719" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#1724C9"></stop>
                          <stop offset="1" stop-color="#1C64F2"></stop>
                        </linearGradient>
                        <linearGradient id="paint1_linear_1684_75606" x1="22.5797" y1="9.91707" x2="16.5424" y2="3.90103" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#1C64F2"></stop>
                          <stop offset="1" stop-color="#0092FF"></stop>
                        </linearGradient>
                        <linearGradient id="paint2_linear_1684_75606" x1="14.4537" y1="5.99478" x2="5.34622" y2="6.57027" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#0092FF"></stop>
                          <stop offset="1" stop-color="#45B2FF"></stop>
                        </linearGradient>
                        <linearGradient id="paint3_linear_1684_75606" x1="13.839" y1="23.2333" x2="22.018" y2="21.0263" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#1C64F2"></stop>
                          <stop offset="1" stop-color="#0092FF"></stop>
                        </linearGradient>
                        <linearGradient id="paint4_linear_1684_75606" x1="7.96872" y1="13.7686" x2="13.7756" y2="19.7858" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#1724C9"></stop>
                          <stop offset="1" stop-color="#1C64F2"></stop>
                        </linearGradient>
                        <linearGradient id="paint5_linear_1684_75606" x1="21.2254" y1="18.1096" x2="25.277" y2="9.94204" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#0092FF"></stop>
                          <stop offset="1" stop-color="#45B2FF"></stop>
                        </linearGradient>
                        <linearGradient id="paint6_linear_1684_75606" x1="6.57915" y1="8.99144" x2="4.41108" y2="17.1404" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#1C64F2"></stop>
                          <stop offset="1" stop-color="#0092FF"></stop>
                        </linearGradient>
                        <linearGradient id="paint7_linear_1684_75606" x1="17.6475" y1="8.60399" x2="9.72471" y2="10.5766" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#1724C9"></stop>
                          <stop offset="1" stop-color="#1C64F2"></stop>
                        </linearGradient>
                        <linearGradient id="paint8_linear_1684_75606" x1="7.34023" y1="17.9063" x2="12.3903" y2="25.5028" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#0092FF"></stop>
                          <stop offset="1" stop-color="#45B2FF"></stop>
                        </linearGradient>
                      </defs>
                    </svg>Flowbite</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Export your UI data to generate applications.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto">
                      Remove
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-3 flex items-center"><svg class="{{ $attributes->get('class', '') }} me-2 h-8" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <g clip-path="url(#clip0_1684_75567)">
                        <path d="M14 14C14 13.0717 14.3687 12.1815 15.0251 11.5251C15.6815 10.8687 16.5717 10.5 17.5 10.5C18.4283 10.5 19.3185 10.8687 19.9749 11.5251C20.6313 12.1815 21 13.0717 21 14C21 14.9283 20.6313 15.8185 19.9749 16.4749C19.3185 17.1313 18.4283 17.5 17.5 17.5C16.5717 17.5 15.6815 17.1313 15.0251 16.4749C14.3687 15.8185 14 14.9283 14 14V14Z" fill="#1ABCFE"></path>
                        <path d="M7 21C7 20.0717 7.36875 19.1815 8.02513 18.5251C8.6815 17.8687 9.57174 17.5 10.5 17.5H14V21C14 21.9283 13.6313 22.8185 12.9749 23.4749C12.3185 24.1312 11.4283 24.5 10.5 24.5C9.57174 24.5 8.6815 24.1312 8.02513 23.4749C7.36875 22.8185 7 21.9283 7 21V21Z" fill="#0ACF83"></path>
                        <path d="M14 3.5V10.5H17.5C18.4283 10.5 19.3185 10.1313 19.9749 9.47487C20.6313 8.8185 21 7.92826 21 7C21 6.07174 20.6313 5.1815 19.9749 4.52513C19.3185 3.86875 18.4283 3.5 17.5 3.5L14 3.5Z" fill="#FF7262"></path>
                        <path d="M7 7C7 7.92826 7.36875 8.8185 8.02513 9.47487C8.6815 10.1313 9.57174 10.5 10.5 10.5H14V3.5H10.5C9.57174 3.5 8.6815 3.86875 8.02513 4.52513C7.36875 5.1815 7 6.07174 7 7V7Z" fill="#F24E1E"></path>
                        <path d="M7 14C7 14.9283 7.36875 15.8185 8.02513 16.4749C8.6815 17.1313 9.57174 17.5 10.5 17.5H14V10.5H10.5C9.57174 10.5 8.6815 10.8687 8.02513 11.5251C7.36875 12.1815 7 13.0717 7 14V14Z" fill="#A259FF"></path>
                      </g>
                      <defs>
                        <clipPath id="clip0_1684_75567">
                          <rect width="14" height="21" fill="white" transform="translate(7 3.5)"></rect>
                        </clipPath>
                      </defs>
                    </svg>Figma</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Export your designs and generate code automatically.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-primary-700 bg-white px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-700 hover:text-white focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-100 dark:border-primary-500 dark:bg-gray-800 dark:text-primary-500 dark:hover:bg-primary-600 dark:hover:border-primary-600 dark:hover:text-white dark:focus:ring-primary-700">
                      Connect
                    </button>
                  </div>
                </div>
              </div>
              <div class="{{ $attributes->get('class', '') }} hidden" id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Multi-factor authentication</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Require an extra security challenge when logging in. If you are unable to pass this challenge, you will have the option to recover your account via email.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto">
                      Enable
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Log out of all devices</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Log out of all active sessions across all devices. It may take up to 30 minutes for other devices to be logged out.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto">
                      Sign out all
                    </button>
                  </div>
                </div>
                <div class="{{ $attributes->get('class', '') }} py-5 border-b border-gray-200 dark:border-gray-700 flex items-center gap-4 justify-between">
                  <div>
                    <h6 class="{{ $attributes->get('class', '') }} text-gray-900 dark:text-white text-base font-medium mb-1">Log out on this device</h6>
                    <p class="{{ $attributes->get('class', '') }} text-gray-500 text-sm font-normal dark:text-gray-400 max-w-md">Log out from this device to protect your privacy.</p>
                  </div>
                  <div class="{{ $attributes->get('class', '') }} shrink-0">
                    <button type="button" class="{{ $attributes->get('class', '') }} flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto">
                      Log out
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Modal footer -->
          <div class="{{ $attributes->get('class', '') }} flex items-center px-4 md:px-5 pb-4 md:pb-5 rounded-b">
              <button data-modal-hide="settings-modal" type="button" class="{{ $attributes->get('class', '') }} text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Save</button>
              <button data-modal-hide="settings-modal" type="button" class="{{ $attributes->get('class', '') }} py-2.5 px-5 ms-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Close</button>
          </div>
      </div>
  </div>
</div>