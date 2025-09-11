{{-- Flowbite Component: chat-room --}}
@props(['title' => 'Tailwind CSS Chat Room - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="flex h-[calc(100vh-8rem)]">
  <!-- conversations sidebar -->
  <div class="hidden w-full max-w-80 border-e border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 md:block">
    <div class="mb-4 w-full border-b border-gray-200 dark:border-gray-700">
      <ul
        data-tabs-active-classes="text-primary-600 dark:text-primary-500 border-primary-600"
        data-tabs-inactive-classes="text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700 dark:hover:border-gray-600 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white"
        class="-mb-px grid grid-cols-2 text-center text-sm font-medium"
        id="contacts-tab"
        data-tabs-toggle="#contacts-tab-content"
        role="tablist"
      >
        <li role="presentation">
          <button
            class="flex w-full items-center justify-center rounded-t-lg border-b px-4 py-5 text-gray-500 dark:text-gray-400"
            id="chats-tab"
            data-tabs-target="#chats"
            type="button"
            role="tab"
            aria-controls="chats"
            aria-selected="false"
          >
            <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M4 3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h1v2a1 1 0 0 0 1.707.707L9.414 13H15a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H4Z" clip-rule="evenodd" />
              <path fill-rule="evenodd" d="M8.023 17.215c.033-.03.066-.062.098-.094L10.243 15H15a3 3 0 0 0 3-3V8h2a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1v2a1 1 0 0 1-1.707.707L14.586 18H9a1 1 0 0 1-.977-.785Z" clip-rule="evenodd" />
            </svg>
            Chats
          </button>
        </li>
        <li role="presentation">
          <button
            class="flex w-full items-center justify-center rounded-t-lg border-b px-4 py-5 text-gray-500 dark:text-gray-400"
            id="calls-tab"
            data-tabs-target="#calls"
            type="button"
            role="tab"
            aria-controls="calls"
            aria-selected="false"
          >
            <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
              />
            </svg>
            Latest calls
          </button>
        </li>
      </ul>
    </div>
    <div id="contacts-tab-content">
      <div class="hidden" id="chats" role="tabpanel" aria-labelledby="chats-tab">
        <div class="flex items-center justify-between px-4 pb-4">
          <h2 class="font-medium text-gray-900 dark:text-white">Latest chats</h2>
          <div class="flex items-center gap-1">
            <button
              type="button"
              data-tooltip-target="tooltip-options-chat"
              id="chat-options-button"
              data-dropdown-toggle="chat-options"
              class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h.01m6 0h.01m5.99 0h.01" />
              </svg>

              <span class="sr-only">Options chat</span>
            </button>
            <div
              id="tooltip-options-chat"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              Show chat options
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <div id="chat-options" class="z-10 hidden w-52 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="chat-options-button">
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Manage conversations</button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Messaging settings</button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Message request inbox</button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Set away message</button>
                </li>
              </ul>
            </div>
            <button
              type="button"
              data-tooltip-target="tooltip-new-chat"
              data-modal-toggle="new-conversation-modal"
              data-modal-target="new-conversation-modal"
              class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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

              <span class="sr-only">New conversation</span>
            </button>
            <div id="tooltip-new-chat" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              New conversation
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
        </div>
        <div class="mb-4 px-4">
          <label for="search-input-contacts" class="sr-only">All contacts</label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
              <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
              </svg>
            </div>
            <input
              type="text"
              id="search-input-contacts"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Search for messages or contacts"
            />
          </div>
        </div>
        <div class="h-[calc(100vh-19.4rem)] overflow-y-scroll">
          <ul>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Roberta Casas</span>
                  <p class="max-w-52 truncate text-sm font-normal text-primary-600 dark:text-primary-500">Typing...</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">18:05</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/leslie-livingston.png" alt="Leslie image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Leslie Livingston</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">Yes, we can do this! 🔥</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">14:23</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/neil-sims.png" alt="Neil image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-red-500 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Neil Sims</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 8a1 1 0 0 1 1 1v3a4.006 4.006 0 0 0 4 4h4a4.006 4.006 0 0 0 4-4V9a1 1 0 1 1 2 0v3.001A6.006 6.006 0 0 1 14.001 18H13v2h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2H9.999A6.006 6.006 0 0 1 4 12.001V9a1 1 0 0 1 1-1Z"
                        clip-rule="evenodd"
                      />
                      <path d="M7 6a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4V6Z" />
                    </svg>
                    <span class="max-w-52 truncate font-medium text-gray-900 dark:text-white">Voice message</span>
                  </p>
                </div>
              </div>

              <div class="flex shrink-0 flex-col items-end">
                <span class="mb-1 text-xs text-gray-500 dark:text-gray-400">10:02</span>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 p-1 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">4</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/michael-gough.png" alt="Michael image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Michael Gough</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">Nevermind, I will grab the items from the station and bring it back to the office.</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">07:45</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-red-500 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Bonnie Green</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M13 10a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H14a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path
                        fill-rule="evenodd"
                        d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12c0 .556-.227 1.06-.593 1.422A.999.999 0 0 1 20.5 20H4a2.002 2.002 0 0 1-2-2V6Zm6.892 12 3.833-5.356-3.99-4.322a1 1 0 0 0-1.549.097L4 12.879V6h16v9.95l-3.257-3.619a1 1 0 0 0-1.557.088L11.2 18H8.892Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Sent a photo</span>
                  </p>
                </div>
              </div>

              <div class="flex shrink-0 flex-col items-end">
                <span class="mb-1 text-xs text-gray-500 dark:text-gray-400">15h</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/lana-byrd.png" alt="Lana image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Lana Byrd</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">🎉 Awesome, let’s go!</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">16h</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/helene-engels.png" alt="Helene image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-red-500 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Helene Engels</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">Yes, we can do this! 🔥</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">18h</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/karen-nelson.png" alt="Karen image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Karen Nelson</span>
                  <p class="max-w-52 truncate text-sm font-medium text-gray-900 dark:text-white">Are we still up for that coffee tomorrow?</p>
                </div>
              </div>

              <div class="flex shrink-0 flex-col items-end">
                <span class="mb-1 text-xs text-gray-500 dark:text-gray-400">yesterday</span>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 p-1 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">2</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/thomas-lean.png" alt="Thomas image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-red-500 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Thomas Lean</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M5 8a1 1 0 0 1 1 1v3a4.006 4.006 0 0 0 4 4h4a4.006 4.006 0 0 0 4-4V9a1 1 0 1 1 2 0v3.001A6.006 6.006 0 0 1 14.001 18H13v2h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2H9.999A6.006 6.006 0 0 1 4 12.001V9a1 1 0 0 1 1-1Z"
                        clip-rule="evenodd"
                      />
                      <path d="M7 6a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4V6Z" />
                    </svg>
                    <span class="max-w-52 truncate">Voice message</span>
                  </p>
                </div>
              </div>

              <div class="flex shrink-0 flex-col items-end">
                <span class="mb-1 text-xs text-gray-500 dark:text-gray-400">2d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/robert-brown.png" alt="Robert image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-red-500 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Robert Brown</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">Long time no talk, what's up brother?</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">1w</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Joseph McFall</span>
                  <p class="max-w-52 truncate text-sm font-medium text-gray-900 dark:text-white">Let's do it next month</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">04.03.2025</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/jese-leos.png" alt="Jese image" />
                  <span class="absolute start-6 top-0  h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-800"></span>
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Jese Leos</span>
                  <p class="max-w-52 truncate text-sm font-medium text-gray-900 dark:text-white">You hear about Flowbite? 👀</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">04.03.2025</span>
              </div>
            </li>
          </ul>
          <div class="flex items-center justify-between border-t border-gray-200 px-4 py-4 dark:border-gray-700">
            <h3 class="font-medium text-gray-500 dark:text-gray-400">Group conversation</h3>
            <div>
              <button
                type="button"
                data-modal-toggle="new-group-conversation-modal"
                data-modal-target="new-group-conversation-modal"
                data-tooltip-target="tooltip-new-group-conversation"
                class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
                </svg>
                <span class="sr-only">New group conversation</span>
              </button>
              <div
                id="tooltip-new-group-conversation"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                New group conversation
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
            </div>
          </div>
          <ul>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="flex shrink-0 -space-x-4 rtl:space-x-reverse">
                  <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/roberta-casas.png" alt="Roberta image" />
                  <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/bonnie-green.png" alt="Bonnie image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Business group</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">Bonnie: Wait! What test? 😱</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">18:05</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="flex shrink-0 -space-x-4 rtl:space-x-reverse">
                  <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/jese-leos.png" alt="Jese image" />
                  <img class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800" src="/images/users/helene-engels.png" alt="Helene image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Family group</span>
                  <p class="max-w-52 truncate text-sm font-normal text-gray-500 dark:text-gray-400">Jese: Pizza is here! 🍕</p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">18:05</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
      <div class="hidden" id="calls" role="tabpanel" aria-labelledby="calls-tab">
        <div class="flex items-center justify-between px-4 pb-4">
          <h2 class="font-medium text-gray-900 dark:text-white">Latest calls</h2>
          <div class="flex items-center gap-1">
            <button
              type="button"
              id="call-options-button"
              data-dropdown-toggle="call-options-dropdown"
              data-tooltip-target="tooltip-options-call"
              class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M6 12h.01m6 0h.01m5.99 0h.01" />
              </svg>

              <span class="sr-only">Options Call</span>
            </button>
            <div
              id="tooltip-options-call"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              Show call options
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <div id="call-options-dropdown" class="z-10 hidden w-52 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
              <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="call-options-button">
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Manage calls</button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Calling settings</button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Call request inbox</button>
                </li>
                <li>
                  <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Set away voice message</button>
                </li>
              </ul>
            </div>
            <button
              type="button"
              data-tooltip-target="tooltip-new-call"
              data-modal-toggle="dial-phone-modal"
              data-modal-target="dial-phone-modal"
              class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
                />
              </svg>

              <span class="sr-only">New call</span>
            </button>
            <div id="tooltip-new-call" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
              New call
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
        </div>
        <div class="h-[calc(100vh-15.8rem)] overflow-y-scroll">
          <ul>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-red-600 dark:text-red-500">Roberta Casas</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Missed</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">17:03</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/leslie-livingston.png" alt="Lesie image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Leslie Livingston</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Outgoing</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">16:27</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Bonnie Green</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Outgoing</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">15:42</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/michael-gough.png" alt="Michael image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Michael Gough</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">10:15</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/helene-engels.png" alt="Helene image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Helene Engels</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Outgoing</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">08:42</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/lana-byrd.png" alt="Lana image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Lana Byrd</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">Yesterday</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/robert-brown.png" alt="Robert image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-red-600 dark:text-red-500">Robert Brown</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Missed</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">Yesterday</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/thomas-lean.png" alt="Thomas image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Thomas Lean</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">2d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/neil-sims.png" alt="Neil image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-red-600 dark:text-red-500">Neil Sims</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Missed</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">2d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/karen-nelson.png" alt="Karen image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Karen Nelson</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">3d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/michael-gough.png" alt="Michael image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Michael Gough</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">4d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/helene-engels.png" alt="Helene image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Helene Engels</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Outgoing</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">4d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/lana-byrd.png" alt="Lana image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Lana Byrd</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">5d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/robert-brown.png" alt="Robert image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-red-600 dark:text-red-500">Robert Brown</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Missed</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">6d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/thomas-lean.png" alt="Thomas image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Thomas Lean</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">6d</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/neil-sims.png" alt="Neil image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-red-600 dark:text-red-500">Neil Sims</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Missed</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">1w</span>
              </div>
            </li>
            <li class="flex items-start justify-between px-4 py-2 hover:cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <img class="h-8 w-8 rounded-full" src="/images/users/karen-nelson.png" alt="Karen image" />
                </div>
                <div class="leading-1.5 flex w-full flex-col">
                  <span class="text-base font-medium text-gray-900 dark:text-white">Karen Nelson</span>
                  <p class="flex items-center text-sm font-normal text-gray-500 dark:text-gray-400">
                    <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M12.017 6.995c-2.306 0-4.534.408-6.215 1.507-1.737 1.135-2.788 2.944-2.797 5.451a4.8 4.8 0 0 0 .01.62c.015.193.047.512.138.763a2.557 2.557 0 0 0 2.579 1.677H7.31a2.685 2.685 0 0 0 2.685-2.684v-.645a.684.684 0 0 1 .684-.684h2.647a.686.686 0 0 1 .686.687v.645c0 .712.284 1.395.787 1.898.478.478 1.101.787 1.847.787h1.647a2.555 2.555 0 0 0 2.575-1.674c.09-.25.123-.57.137-.763.015-.2.022-.433.01-.617-.002-2.508-1.049-4.32-2.785-5.458-1.68-1.1-3.907-1.51-6.213-1.51Z"
                      />
                    </svg>
                    <span class="max-w-52 truncate">Incoming</span>
                  </p>
                </div>
              </div>

              <div class="shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">2w</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- main chat area -->
  <div class="relative w-full">
    <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center gap-3">
        <div class="relative shrink-0">
          <img class="h-8 w-8 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
        </div>
        <div class="leading-1.5 flex w-full flex-col">
          <span class="text-base font-medium text-gray-900 dark:text-white">Joseph McFall</span>
          <div class="flex items-center text-xs font-normal text-green-500">
            <div class="me-1 h-2.5 w-2.5 rounded-full bg-green-400"></div>
            <span>Online</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a
          href="/audio/outgoing-call/"
          data-tooltip-target="tooltip-new-individual-call"
          class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-primary-700 hover:bg-primary-50 dark:text-primary-500 dark:hover:bg-primary-900 dark:hover:text-primary-300"
        >
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
            />
          </svg>
          <span class="sr-only">Make a phone call</span>
        </a>
        <div
          id="tooltip-new-individual-call"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Make a phone call
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
        <a
          href="/video/meeting-room/"
          data-tooltip-target="tooltip-new-individual-video"
          class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-primary-700 hover:bg-primary-50 dark:text-primary-500 dark:hover:bg-primary-900 dark:hover:text-primary-300"
        >
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M14 7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7Zm2 9.387 4.684 1.562A1 1 0 0 0 22 17V7a1 1 0 0 0-1.316-.949L16 7.613v8.774Z" clip-rule="evenodd" />
          </svg>
          <span class="sr-only">Make a video call</span>
        </a>
        <div
          id="tooltip-new-individual-video"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Make a video call
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
        <button
          type="button"
          data-tooltip-target="tooltip-show-contact-info"
          data-drawer-target="readUserDrawerAdvanced"
          data-drawer-show="readUserDrawerAdvanced"
          data-drawer-placement="right"
          data-drawer
          class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-primary-700 hover:bg-primary-50 dark:text-primary-500 dark:hover:bg-primary-900 dark:hover:text-primary-300"
        >
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="sr-only">Show contact information</span>
        </button>
        <div
          id="tooltip-show-contact-info"
          role="tooltip"
          class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Show contact information
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </div>
    <div class="h-[calc(100vh-18.5rem)] space-y-4 overflow-y-scroll p-4">
      <div class="group flex max-w-[404px] items-start gap-2.5">
        <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
        <div class="flex flex-col gap-1">
          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:cursor-pointer hover:underline dark:text-white">Roberta Casas</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
          </div>
          <div class="space-y-1 text-start">
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-e-xl rounded-es-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">That's awesome. I think our users will really appreciate the improvements.</p>
            </div>
          </div>
        </div>
        <button
          id="dropdownMenuIconButton"
          data-dropdown-toggle="dropdownDots"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg   bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="group flex max-w-[404px] items-start gap-2.5">
        <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
        <div class="flex flex-col gap-1">
          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:cursor-pointer hover:underline dark:text-white">Roberta Casas</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">11:48</span>
          </div>
          <div class="leading-1.5 flex-col rounded-e-xl rounded-es-xl border-gray-200 bg-gray-100 p-4 dark:bg-gray-700">
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
              <button
                class="inline-flex items-center self-center rounded-lg bg-gray-100 p-2 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                type="button"
              >
                <svg class="h-4 w-4 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 12 16">
                  <path d="M3 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm7 0H9a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Z" />
                </svg>
              </button>
              <svg class="w-[145px] md:h-[40px] md:w-[185px]" aria-hidden="true" viewBox="0 0 185 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect y="17" width="3" height="6" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="7" y="15.5" width="3" height="9" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="21" y="6.5" width="3" height="27" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="14" y="6.5" width="3" height="27" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="28" y="3" width="3" height="34" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="35" y="3" width="3" height="34" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="42" y="5.5" width="3" height="29" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="49" y="10" width="3" height="20" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="56" y="13.5" width="3" height="13" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="63" y="16" width="3" height="8" rx="1.5" fill="#6B7280" class="dark:fill-white" />
                <rect x="70" y="12.5" width="3" height="15" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="77" y="3" width="3" height="34" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="84" y="3" width="3" height="34" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="91" y="0.5" width="3" height="39" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="98" y="0.5" width="3" height="39" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="105" y="2" width="3" height="36" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="112" y="6.5" width="3" height="27" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="119" y="9" width="3" height="22" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="126" y="11.5" width="3" height="17" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="133" y="2" width="3" height="36" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="140" y="2" width="3" height="36" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="147" y="7" width="3" height="26" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="154" y="9" width="3" height="22" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="161" y="9" width="3" height="22" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="168" y="13.5" width="3" height="13" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="175" y="16" width="3" height="8" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="182" y="17.5" width="3" height="5" rx="1.5" fill="#E5E7EB" class="dark:fill-gray-500" />
                <rect x="66" y="16" width="8" height="8" rx="4" fill="#1C64F2" />
              </svg>
              <span class="inline-flex items-center self-center p-2 text-sm font-medium text-gray-900 dark:text-white">3:42</span>
            </div>
          </div>
        </div>
        <button
          id="dropdownMenuIconButton2"
          data-dropdown-toggle="dropdownDots2"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots2" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton2">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="group ms-auto flex max-w-[404px] items-start justify-end gap-2.5">
        <button
          id="dropdownMenuIconButton3"
          data-dropdown-toggle="dropdownDots3"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots3" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton3">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
        <div class="flex flex-col gap-1">
          <div class="flex items-center justify-end space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:cursor-pointer hover:underline dark:text-white">Joseph McFall</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">11:48</span>
          </div>
          <div class="space-y-1 text-end">
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-s-xl rounded-ee-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">I agree on this one and we should add it to the next sprint so that we can roll it out as soon as possible.</p>
            </div>
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">Hey Roberta... forgot to ask, but can you please send me a pic of the new office?</p>
            </div>
            <div class="leading-1.5 ms-auto inline-flex rounded-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">Thanks! 🙏</p>
            </div>
          </div>
        </div>
        <img class="h-8 w-8 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
      </div>

      <div class="group flex max-w-[404px] items-start gap-2.5">
        <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
        <div class="flex flex-col gap-1">
          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:underline dark:text-white">Roberta Casas</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
          </div>
          <div class="leading-1.5 flex w-full max-w-[320px] flex-col rounded-e-xl rounded-es-xl border-gray-200 bg-gray-100 p-4 dark:bg-gray-700">
            <p class="text-sm font-normal text-gray-900 dark:text-white">Here's the new office! 💙</p>
            <div class="group relative my-2.5">
              <div class="absolute flex h-full w-full items-center justify-center rounded-lg bg-gray-900/50 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                <button data-tooltip-target="download-image" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/30 hover:bg-white/50 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:text-white">
                  <svg class="h-5 w-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 18">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 1v11m0 0 4-4m-4 4L4 8m11 4v3a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-3" />
                  </svg>
                </button>
                <div
                  id="download-image"
                  role="tooltip"
                  class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
                >
                  Download image
                  <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
              </div>
              <img src="/images/blog/image-1.jpg" class="rounded-lg" />
            </div>
          </div>
        </div>
        <button
          id="dropdownMenuIconButton4"
          data-dropdown-toggle="dropdownDots4"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots4" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton4">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="group ms-auto flex max-w-[404px] items-start justify-end gap-2.5">
        <button
          id="dropdownMenuIconButton5"
          data-dropdown-toggle="dropdownDots5"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots5" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton5">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
        <div class="flex flex-col gap-1">
          <div class="flex items-center justify-end space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:cursor-pointer hover:underline dark:text-white">Joseph McFall</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">12:56</span>
          </div>
          <div class="space-y-1 text-end">
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-s-xl rounded-ee-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">We've been looking for a design system for a while now, I think that I have found a pretty good candidate.</p>
            </div>
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">I'll send a link in a bit.</p>
            </div>
          </div>
        </div>
        <img class="h-8 w-8 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
      </div>

      <div class="group flex max-w-[404px] items-start gap-2.5">
        <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
        <div class="flex w-full flex-col gap-1">
          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:underline dark:text-white">Roberta Casas</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">11:46</span>
          </div>
          <div class="leading-1.5 flex flex-col rounded-e-xl rounded-es-xl border-gray-200 bg-gray-100 p-4 dark:bg-gray-700">
            <p class="pb-2.5 text-sm font-normal text-gray-900 dark:text-white">You mean this one? 🙃</p>
            <p class="pb-2.5 text-sm font-normal text-gray-900 dark:text-white">
              <a href="https://github.com/themesberg/flowbite" class="break-all font-medium text-primary-700 underline hover:no-underline dark:text-primary-500">https://github.com/themesberg/flowbite</a>
            </p>
            <a href="#" class="mb-2 rounded-xl bg-gray-50 p-4 hover:bg-gray-100 dark:bg-gray-600 dark:hover:bg-gray-500">
              <img src="https://flowbite.com/docs/images/og-image.png" class="mb-2 rounded-lg" />
              <span class="mb-2 text-sm font-medium text-gray-900 dark:text-white">GitHub - themesberg/flowbite: The most popular and open source libra ...</span>
              <span class="text-xs font-normal text-gray-500 dark:text-gray-400">github.com</span>
            </a>
          </div>
        </div>
        <button
          id="dropdownMenuIconButton6"
          data-dropdown-toggle="dropdownDots6"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots6" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton6">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="group ms-auto flex max-w-[404px] items-start justify-end gap-2.5">
        <button
          id="dropdownMenuIconButton7"
          data-dropdown-toggle="dropdownDots7"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots7" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton7">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
        <div class="flex flex-col gap-1">
          <div class="flex items-center justify-end space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:cursor-pointer hover:underline dark:text-white">Joseph McFall</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">13:02</span>
          </div>
          <div class="space-y-1 text-end">
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-s-xl rounded-ee-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">Wait... how did you know? 😱</p>
            </div>
          </div>
        </div>
        <img class="h-8 w-8 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
      </div>

      <div class="group flex max-w-[404px] items-start gap-2.5">
        <img class="h-8 w-8 rounded-full" src="/images/users/roberta-casas.png" alt="Roberta image" />
        <div class="flex flex-col gap-1">
          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <a href="#" class="text-sm font-semibold text-gray-900 hover:cursor-pointer hover:underline dark:text-white">Roberta Casas</a>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">13:04</span>
          </div>
          <div class="space-y-1 text-start">
            <div class="leading-1.5 ms-auto inline-flex flex-col rounded-e-xl rounded-es-xl border-gray-200 bg-gray-100 p-4 text-start dark:bg-gray-700">
              <p class="text-sm font-normal text-gray-900 dark:text-white">I have been using it forever!</p>
            </div>
          </div>
        </div>
        <button
          id="dropdownMenuIconButton8"
          data-dropdown-toggle="dropdownDots8"
          data-dropdown-placement="bottom-start"
          class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 opacity-0 transition-opacity hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 group-hover:opacity-100 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800 dark:focus:ring-gray-600"
          type="button"
        >
          <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
          </svg>
        </button>
        <div id="dropdownDots8" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdownMenuIconButton8">
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"
                  />
                </svg>
                Reply
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M5.027 10.9a8.729 8.729 0 0 1 6.422-3.62v-1.2A2.061 2.061 0 0 1 12.61 4.2a1.986 1.986 0 0 1 2.104.23l5.491 4.308a2.11 2.11 0 0 1 .588 2.566 2.109 2.109 0 0 1-.588.734l-5.489 4.308a1.983 1.983 0 0 1-2.104.228 2.065 2.065 0 0 1-1.16-1.876v-.942c-5.33 1.284-6.212 5.251-6.25 5.441a1 1 0 0 1-.923.806h-.06a1.003 1.003 0 0 1-.955-.7A10.221 10.221 0 0 1 5.027 10.9Z"
                  />
                </svg>
                Forward
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd" />
                </svg>
                Copy
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M13.09 3.294c1.924.95 3.422 1.69 5.472.692a1 1 0 0 1 1.438.9v9.54a1 1 0 0 1-.562.9c-2.981 1.45-5.382.24-7.25-.701a38.739 38.739 0 0 0-.622-.31c-1.033-.497-1.887-.812-2.756-.77-.76.036-1.672.357-2.81 1.396V21a1 1 0 1 1-2 0V4.971a1 1 0 0 1 .297-.71c1.522-1.506 2.967-2.185 4.417-2.255 1.407-.068 2.653.453 3.72.967.225.108.443.216.655.32Z"
                  />
                </svg>
                Report
              </a>
            </li>
            <li>
              <a href="#" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="absolute bottom-0 left-0 w-full">
      <div class="w-full border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-700">
        <div class="flex items-center gap-2 bg-white px-4 py-3 dark:bg-gray-800">
          <label for="wysiwyg-chat-example" class="sr-only">Write message</label>
          <div id="wysiwyg-chat-example" class="block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"></div>
          <button type="submit" class="inline-flex cursor-pointer justify-center rounded-full p-2 text-primary-600 hover:bg-primary-100 dark:text-primary-500 dark:hover:bg-gray-600">
            <svg class="h-4 w-4 rotate-90 rtl:-rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
              <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z" />
            </svg>
            <span class="sr-only">Send message</span>
          </button>
        </div>
        <div class="align-center flex justify-between border-t px-2 py-2 dark:border-gray-600 border-gray-200">
          <div class="flex flex-wrap items-center">
            <div class="flex flex-wrap items-center">
              <button
                id="toggleBoldButton"
                data-tooltip-target="tooltip-bold"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5h4.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0-7H6m2 7h6.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0 0H6" />
                </svg>
                <span class="sr-only">Bold</span>
              </button>
              <div id="tooltip-bold" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Toggle bold
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleItalicButton"
                data-tooltip-target="tooltip-italic"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.874 19 6.143-14M6 19h6.33m-.66-14H18" />
                </svg>
                <span class="sr-only">Italic</span>
              </button>
              <div id="tooltip-italic" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Toggle italic
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleUnderlineButton"
                data-tooltip-target="tooltip-underline"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M6 19h12M8 5v9a4 4 0 0 0 8 0V5M6 5h4m4 0h4" />
                </svg>
                <span class="sr-only">Underline</span>
              </button>
              <div
                id="tooltip-underline"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Toggle underline
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleStrikeButton"
                data-tooltip-target="tooltip-strike"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6.2V5h12v1.2M7 19h6m.2-14-1.677 6.523M9.6 19l1.029-4M5 5l6.523 6.523M19 19l-7.477-7.477" />
                </svg>
                <span class="sr-only">Strike</span>
              </button>
              <div id="tooltip-strike" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Toggle strike
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleSubscriptButton"
                data-tooltip-target="tooltip-subscript"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19.9999 21h-4v-.5c1.0989-1.0329 3.75-2.5 3.75-3.5v-1.0001c0-.5523-.4477-.9999-1-.9999h-1.75c-.5523 0-1 .4477-1 1M3.99986 6l9.26894 11.5765M13.1219 6 3.85303 17.5765"
                  />
                </svg>
                <span class="sr-only">Subscript</span>
              </button>
              <div
                id="tooltip-subscript"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Toggle subscript
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleSuperscriptButton"
                data-tooltip-target="tooltip-superscript"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21.0002 11h-4l-.0001-.5C18.099 9.46711 20.7502 8 20.7502 7V5.99989c0-.55228-.4478-.99989-1-.99989h-1.75c-.5523 0-1 .44772-1 1M5.37837 7.98274 14.6473 19.5593m-.5251-11.25583L4.85547 19.8773"
                  />
                </svg>
                <span class="sr-only">Superscript</span>
              </button>
              <div
                id="tooltip-superscript"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Toggle superscript
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleHighlightButton"
                data-tooltip-target="tooltip-highlight"
                type="button"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="2"
                    d="M9 19.2H5.5c-.3 0-.5-.2-.5-.5V16c0-.2.2-.4.5-.4h13c.3 0 .5.2.5.4v2.7c0 .3-.2.5-.5.5H18m-6-1 1.4 1.8h.2l1.4-1.7m-7-5.4L12 4c0-.1 0-.1 0 0l4 8.8m-6-2.7h4m-7 2.7h2.5m5 0H17"
                  />
                </svg>
                <span class="sr-only">Highlight</span>
              </button>
              <div
                id="tooltip-highlight"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Toggle highlight
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleCodeButton"
                type="button"
                data-tooltip-target="tooltip-code"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 8-4 4 4 4m8 0 4-4-4-4m-2-3-4 14" />
                </svg>
                <span class="sr-only">Code</span>
              </button>
              <div id="tooltip-code" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                Format code
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <button
                id="toggleTextSizeButton"
                data-dropdown-toggle="textSizeDropdown"
                type="button"
                data-tooltip-target="tooltip-text-size"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6.2V5h11v1.2M8 5v14m-3 0h6m2-6.8V11h8v1.2M17 11v8m-1.5 0h3" />
                </svg>
                <span class="sr-only">Text size</span>
              </button>
              <div
                id="tooltip-text-size"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Text size
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <div id="textSizeDropdown" class="z-10 hidden w-72 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                <ul class="space-y-1 text-sm font-medium" aria-labelledby="toggleTextSizeButton">
                  <li>
                    <button data-text-size="16px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-base text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">16px (Default)</button>
                  </li>
                  <li>
                    <button data-text-size="12px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-xs text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">12px (Tiny)</button>
                  </li>
                  <li>
                    <button data-text-size="14px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">14px (Small)</button>
                  </li>
                  <li>
                    <button data-text-size="18px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-lg text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">18px (Lead)</button>
                  </li>
                  <li>
                    <button data-text-size="24px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-2xl text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">24px (Large)</button>
                  </li>
                  <li>
                    <button data-text-size="36px" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-4xl text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">36px (Huge)</button>
                  </li>
                </ul>
              </div>
              <button
                id="toggleTextColorButton"
                data-dropdown-toggle="textColorDropdown"
                type="button"
                data-tooltip-target="tooltip-text-color"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="25" height="24" fill="none" viewBox="0 0 25 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="2"
                    d="m6.532 15.982 1.573-4m-1.573 4h-1.1m1.1 0h1.65m-.077-4 2.725-6.93a.11.11 0 0 1 .204 0l2.725 6.93m-5.654 0H8.1m.006 0h5.654m0 0 .617 1.569m5.11 4.453c0 1.102-.854 1.996-1.908 1.996-1.053 0-1.907-.894-1.907-1.996 0-1.103 1.907-4.128 1.907-4.128s1.909 3.025 1.909 4.128Z"
                  />
                </svg>
                <span class="sr-only">Text color</span>
              </button>
              <div
                id="tooltip-text-color"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Text color
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <div id="textColorDropdown" class="z-10 hidden w-48 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                <div class="group mb-3 grid grid-cols-6 items-center gap-2 rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-600">
                  <input
                    type="color"
                    id="color"
                    value="#e66465"
                    class="col-span-3 h-8 w-full rounded-md border border-gray-200 bg-gray-50 p-px px-1 hover:bg-gray-50 group-hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:group-hover:bg-gray-700"
                  />
                  <label for="color" class="col-span-3 text-sm font-medium text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white">Pick a color</label>
                </div>
                <div class="mb-3 grid grid-cols-6 gap-1">
                  <button type="button" data-hex-color="#1A56DB" style="background-color: #1A56DB" class="h-6 w-6 rounded-md"><span class="sr-only">Blue</span></button>
                  <button type="button" data-hex-color="#0E9F6E" style="background-color: #0E9F6E" class="h-6 w-6 rounded-md"><span class="sr-only">Green</span></button>
                  <button type="button" data-hex-color="#FACA15" style="background-color: #FACA15" class="h-6 w-6 rounded-md"><span class="sr-only">Yellow</span></button>
                  <button type="button" data-hex-color="#F05252" style="background-color: #F05252" class="h-6 w-6 rounded-md"><span class="sr-only">Red</span></button>
                  <button type="button" data-hex-color="#FF8A4C" style="background-color: #FF8A4C" class="h-6 w-6 rounded-md"><span class="sr-only">Orange</span></button>
                  <button type="button" data-hex-color="#0694A2" style="background-color: #0694A2" class="h-6 w-6 rounded-md"><span class="sr-only">Teal</span></button>
                  <button type="button" data-hex-color="#B4C6FC" style="background-color: #B4C6FC" class="h-6 w-6 rounded-md"><span class="sr-only">Light indigo</span></button>
                  <button type="button" data-hex-color="#8DA2FB" style="background-color: #8DA2FB" class="h-6 w-6 rounded-md"><span class="sr-only">Indigo</span></button>
                  <button type="button" data-hex-color="#5145CD" style="background-color: #5145CD" class="h-6 w-6 rounded-md"><span class="sr-only">Purple</span></button>
                  <button type="button" data-hex-color="#771D1D" style="background-color: #771D1D" class="h-6 w-6 rounded-md"><span class="sr-only">Brown</span></button>
                  <button type="button" data-hex-color="#FCD9BD" style="background-color: #FCD9BD" class="h-6 w-6 rounded-md"><span class="sr-only">Light orange</span></button>
                  <button type="button" data-hex-color="#99154B" style="background-color: #99154B" class="h-6 w-6 rounded-md"><span class="sr-only">Bordo</span></button>
                  <button type="button" data-hex-color="#7E3AF2" style="background-color: #7E3AF2" class="h-6 w-6 rounded-md"><span class="sr-only">Dark Purple</span></button>
                  <button type="button" data-hex-color="#CABFFD" style="background-color: #CABFFD" class="h-6 w-6 rounded-md"><span class="sr-only">Light</span></button>
                  <button type="button" data-hex-color="#D61F69" style="background-color: #D61F69" class="h-6 w-6 rounded-md"><span class="sr-only">Dark Pink</span></button>
                  <button type="button" data-hex-color="#F8B4D9" style="background-color: #F8B4D9" class="h-6 w-6 rounded-md"><span class="sr-only">Pink</span></button>
                  <button type="button" data-hex-color="#F6C196" style="background-color: #F6C196" class="h-6 w-6 rounded-md"><span class="sr-only">Cream</span></button>
                  <button type="button" data-hex-color="#A4CAFE" style="background-color: #A4CAFE" class="h-6 w-6 rounded-md"><span class="sr-only">Light Blue</span></button>
                  <button type="button" data-hex-color="#5145CD" style="background-color: #5145CD" class="h-6 w-6 rounded-md"><span class="sr-only">Dark Blue</span></button>
                  <button type="button" data-hex-color="#B43403" style="background-color: #B43403" class="h-6 w-6 rounded-md"><span class="sr-only">Orange Brown</span></button>
                  <button type="button" data-hex-color="#FCE96A" style="background-color: #FCE96A" class="h-6 w-6 rounded-md"><span class="sr-only">Light Yellow</span></button>
                  <button type="button" data-hex-color="#1E429F" style="background-color: #1E429F" class="h-6 w-6 rounded-md"><span class="sr-only">Navy Blue</span></button>
                  <button type="button" data-hex-color="#768FFD" style="background-color: #768FFD" class="h-6 w-6 rounded-md"><span class="sr-only">Light Purple</span></button>
                  <button type="button" data-hex-color="#BCF0DA" style="background-color: #BCF0DA" class="h-6 w-6 rounded-md"><span class="sr-only">Light Green</span></button>
                  <button type="button" data-hex-color="#EBF5FF" style="background-color: #EBF5FF" class="h-6 w-6 rounded-md"><span class="sr-only">Sky Blue</span></button>
                  <button type="button" data-hex-color="#16BDCA" style="background-color: #16BDCA" class="h-6 w-6 rounded-md"><span class="sr-only">Cyan</span></button>
                  <button type="button" data-hex-color="#E74694" style="background-color: #E74694" class="h-6 w-6 rounded-md"><span class="sr-only">Pink</span></button>
                  <button type="button" data-hex-color="#83B0ED" style="background-color: #83B0ED" class="h-6 w-6 rounded-md"><span class="sr-only">Darker Sky Blue</span></button>
                  <button type="button" data-hex-color="#03543F" style="background-color: #03543F" class="h-6 w-6 rounded-md"><span class="sr-only">Forest Green</span></button>
                  <button type="button" data-hex-color="#111928" style="background-color: #111928" class="h-6 w-6 rounded-md"><span class="sr-only">Black</span></button>
                  <button type="button" data-hex-color="#4B5563" style="background-color: #4B5563" class="h-6 w-6 rounded-md"><span class="sr-only">Stone</span></button>
                  <button type="button" data-hex-color="#6B7280" style="background-color: #6B7280" class="h-6 w-6 rounded-md"><span class="sr-only">Gray</span></button>
                  <button type="button" data-hex-color="#D1D5DB" style="background-color: #D1D5DB" class="h-6 w-6 rounded-md"><span class="sr-only">Light Gray</span></button>
                  <button type="button" data-hex-color="#F3F4F6" style="background-color: #F3F4F6" class="h-6 w-6 rounded-md"><span class="sr-only">Cloud Gray</span></button>
                  <button type="button" data-hex-color="#F3F4F6" style="background-color: #F3F4F6" class="h-6 w-6 rounded-md"><span class="sr-only">Cloud Gray</span></button>
                  <button type="button" data-hex-color="#F9FAFB" style="background-color: #F9FAFB" class="h-6 w-6 rounded-md"><span class="sr-only">Heaven Gray</span></button>
                </div>
                <button
                  type="button"
                  id="reset-color"
                  class="w-full rounded-lg bg-white py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-700"
                >
                  Reset color
                </button>
              </div>
              <button
                id="toggleFontFamilyButton"
                data-dropdown-toggle="fontFamilyDropdown"
                type="button"
                data-tooltip-target="tooltip-font-family"
                class="cursor-pointer rounded-sm p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="m10.6 19 4.298-10.93a.11.11 0 0 1 .204 0L19.4 19m-8.8 0H9.5m1.1 0h1.65m7.15 0h-1.65m1.65 0h1.1m-7.7-3.985h4.4M3.021 16l1.567-3.985m0 0L7.32 5.07a.11.11 0 0 1 .205 0l2.503 6.945h-5.44Z"
                  />
                </svg>
                <span class="sr-only">Font family</span>
              </button>
              <div
                id="tooltip-font-family"
                role="tooltip"
                class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
              >
                Font Family
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
              <div id="fontFamilyDropdown" class="z-10 hidden w-48 rounded-sm bg-white p-2 shadow-sm dark:bg-gray-700">
                <ul class="space-y-1 text-sm font-medium" aria-labelledby="toggleFontFamilyButton">
                  <li>
                    <button data-font-family="Inter, ui-sans-serif" type="button" class="flex w-full items-center justify-between rounded-sm px-3 py-2 font-sans text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
                      Default
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Arial, sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Arial, sans-serif;"
                    >
                      Arial
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Courier New', monospace"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Courier New', monospace;"
                    >
                      Courier New
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Georgia, serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Georgia, serif;"
                    >
                      Georgia
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Lucida Sans Unicode', sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Lucida Sans Unicode', sans-serif;"
                    >
                      Lucida Sans Unicode
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Tahoma, sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Tahoma, sans-serif;"
                    >
                      Tahoma
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Times New Roman', serif;"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Times New Roman', serif;"
                    >
                      Times New Roman
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="'Trebuchet MS', sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: 'Trebuchet MS', sans-serif;"
                    >
                      Trebuchet MS
                    </button>
                  </li>
                  <li>
                    <button
                      data-font-family="Verdana, sans-serif"
                      type="button"
                      class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                      style="font-family: Verdana, sans-serif;"
                    >
                      Verdana
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="new-conversation-modal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New message</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="new-conversation-modal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="space-y-4">
          <div class="mb-4">
            <label for="search-conversation-contacts" class="sr-only">Contacts</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
              </div>
              <input
                type="text"
                id="search-conversation-contacts"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Search for contacts"
              />
            </div>
          </div>
          <ul class="max-h-48 space-y-1 overflow-y-scroll">
            <li>
              <button type="button" class="flex w-full items-center rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/jese-leos.png" alt="Jese image" />
                <span class="text-gray-900 dark:text-white">Jese Leos</span>
              </button>
            </li>
            <li>
              <button type="button" class="flex w-full items-center rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image" />
                <span class="text-gray-900 dark:text-white">Bonnie Green</span>
              </button>
            </li>
            <li>
              <button type="button" class="flex w-full items-center rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
                <span class="text-gray-900 dark:text-white">Joseph McFall</span>
              </button>
            </li>
            <li>
              <button type="button" class="flex w-full items-center rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/robert-brown.png" alt="Robert image" />
                <span class="text-gray-900 dark:text-white">Robert Brown</span>
              </button>
            </li>
            <li>
              <button type="button" class="flex w-full items-center rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/leslie-livingston.png" alt="Leslie image" />
                <span class="text-gray-900 dark:text-white">Leslie Livingston</span>
              </button>
            </li>
            <li>
              <button type="button" class="flex w-full items-center rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/lana-byrd.png" alt="Lana image" />
                <span class="text-gray-900 dark:text-white">Lana Byrd</span>
              </button>
            </li>
          </ul>
          <div class="flex space-x-3">
            <button
              type="submit"
              class="w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              New chat
            </button>
            <button
              type="button"
              data-modal-toggle="new-conversation-modal"
              aria-controls="new-conversation-modal"
              class="w-full rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              Cancel
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="new-group-conversation-modal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between dark:border-gray-600 sm:mb-5">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create new group</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="new-group-conversation-modal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#">
        <div class="space-y-4">
          <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
              <label for="search-group-conversation-contacts" class="sr-only">Contacts</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                  <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                  </svg>
                </div>
                <input
                  type="text"
                  id="search-group-conversation-contacts"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Search by name"
                />
              </div>
            </div>
            <button
              type="button"
              class="col-span-1 w-full justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              Add member
            </button>
          </div>
          <ul class="max-h-48 space-y-3 overflow-x-auto overflow-y-scroll">
            <li class="flex items-center justify-between">
              <div class="flex items-center">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/jese-leos.png" alt="Jese image" />
                <span class="text-gray-900 dark:text-white">Jese Leos</span>
              </div>
              <div class="flex items-center">
                <button
                  id="userTypeDropdownButton1"
                  data-dropdown-toggle="userTypeDropdown1"
                  class="me-2 flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  Admin
                  <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                  </svg>
                </button>
                <div id="userTypeDropdown1" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="userTypeDropdownButton1">
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Admin</button>
                    </li>
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Member</button>
                    </li>
                  </ul>
                </div>
                <button
                  class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-red-500 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                  type="button"
                >
                  Remove
                </button>
              </div>
            </li>
            <li class="flex items-center justify-between">
              <div class="flex items-center">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image" />
                <span class="text-gray-900 dark:text-white">Bonnie Green</span>
              </div>
              <div class="flex items-center">
                <button
                  id="userTypeDropdownButton2"
                  data-dropdown-toggle="userTypeDropdown2"
                  class="me-2 flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  Member
                  <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                  </svg>
                </button>
                <div id="userTypeDropdown2" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="userTypeDropdownButton2">
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Admin</button>
                    </li>
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Member</button>
                    </li>
                  </ul>
                </div>
                <button
                  class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-red-500 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                  type="button"
                >
                  Remove
                </button>
              </div>
            </li>
            <li class="flex items-center justify-between">
              <div class="flex items-center">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph image" />
                <span class="text-gray-900 dark:text-white">Joseph McFall</span>
              </div>
              <div class="flex items-center">
                <button
                  id="userTypeDropdownButton3"
                  data-dropdown-toggle="userTypeDropdown3"
                  class="me-2 flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  Member
                  <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                  </svg>
                </button>
                <div id="userTypeDropdown3" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="userTypeDropdownButton3">
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Admin</button>
                    </li>
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Member</button>
                    </li>
                  </ul>
                </div>
                <button
                  class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-red-500 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                  type="button"
                >
                  Remove
                </button>
              </div>
            </li>
            <li class="flex items-center justify-between">
              <div class="flex items-center">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/robert-brown.png" alt="Robert image" />
                <span class="text-gray-900 dark:text-white">Robert Brown</span>
              </div>
              <div class="flex items-center">
                <button
                  id="userTypeDropdownButton4"
                  data-dropdown-toggle="userTypeDropdown4"
                  class="me-2 flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  Member
                  <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                  </svg>
                </button>
                <div id="userTypeDropdown4" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="userTypeDropdownButton4">
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Admin</button>
                    </li>
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Member</button>
                    </li>
                  </ul>
                </div>
                <button
                  class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-red-500 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                  type="button"
                >
                  Remove
                </button>
              </div>
            </li>
            <li class="flex items-center justify-between">
              <div class="flex items-center">
                <img class="me-2 h-6 w-6 rounded-full" src="/images/users/leslie-livingston.png" alt="Leslie image" />
                <span class="text-gray-900 dark:text-white">Leslie Livingston</span>
              </div>
              <div class="flex items-center">
                <button
                  id="userTypeDropdownButton5"
                  data-dropdown-toggle="userTypeDropdown5"
                  class="me-2 flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  Member
                  <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                  </svg>
                </button>
                <div id="userTypeDropdown5" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="userTypeDropdownButton5">
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Admin</button>
                    </li>
                    <li>
                      <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Member</button>
                    </li>
                  </ul>
                </div>
                <button
                  class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-red-500 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                  type="button"
                >
                  Remove
                </button>
              </div>
            </li>
          </ul>
          <div class="flex w-full items-center space-x-3">
            <button
              type="submit"
              class="justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              Create group
            </button>
            <button
              type="button"
              data-modal-toggle="new-group-conversation-modal"
              aria-controls="new-group-conversation-modal"
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              Cancel
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="dial-phone-modal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="relative flex flex-col rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <span class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">(+12) 3456 789</span>
      <button type="button" class="mb-6 text-sm font-medium text-primary-700 hover:underline dark:text-primary-500">Add number</button>
      <div class="mx-auto mb-4 grid max-w-sm grid-cols-3 gap-4">
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">1</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">2</span><span class="text-xs font-medium">ABC</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">3</span><span class="text-xs font-medium">DEF</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">4</span><span class="text-xs font-medium">GHI</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">5</span><span class="text-xs font-medium">JKL</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">6</span><span class="text-xs font-medium">MNO</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">7</span><span class="text-xs font-medium">PQRS</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">8</span><span class="text-xs font-medium">TUV</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">9</span><span class="text-xs font-medium">WXYZ</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">*</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">0</span><span class="text-xs font-medium">+</span>
        </button>
        <button
          type="button"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-gray-100 text-lg text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-700"
        >
          <span class="leading-none">#</span>
        </button>
      </div>
      <div class="flex items-center justify-center">
        <button
          type="button"
          data-tooltip-target="call-number-tooltip"
          class="flex h-14 w-14 flex-col items-center justify-center rounded-full bg-green-500 text-lg text-white hover:bg-green-600 focus:ring-4 focus:ring-green-100 dark:bg-green-500 dark:text-white dark:hover:bg-green-400 dark:focus:ring-green-700"
        >
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
            />
          </svg>
          <span class="sr-only">Call number</span>
        </button>
        <div
          id="call-number-tooltip"
          role="tooltip"
          class="tooltip invisible absolute z-10 ms-2 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
        >
          Call number
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
        <button
          type="button"
          data-tooltip-target="remove-digit-tooltip"
          class="ms-2 flex h-10 w-10 flex-col items-center justify-center rounded-full text-lg text-red-600 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-red-500 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Remove digit</span>
        </button>
        <div id="remove-digit-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
          Remove digit
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- drawer component -->
<div id="readUserDrawerAdvanced" class="fixed right-0 top-0 z-40 h-screen w-80 translate-x-full overflow-y-auto bg-white p-4 px-3 transition-transform dark:bg-gray-800" tabindex="-1" aria-labelledby="drawer-label" aria-hidden="true">
  <div class="mb-6 flex flex-col items-center justify-center">
    <img class="mb-4 h-20 w-20 rounded-full" src="/images/users/joseph-mcfall.png" alt="Joseph avatar" />
    <div class="text-center">
      <a href="#" class="group">
        <h4 id="drawer-label" class="mb-1 text-xl font-bold leading-none text-gray-900 group-hover:underline dark:text-white">Joseph McFall</h4>
      </a>
      <p class="text-gray-500 dark:text-gray-400">joseph@example.com</p>
    </div>
  </div>
  <div class="mb-6 grid grid-cols-2 gap-3">
    <a
      href="/audio/outgoing-call/"
      class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
    >
      <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"
        />
      </svg>
      Audio call
    </a>
    <a
      href="/video/meeting-room/"
      class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
    >
      <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M14 7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7Zm2 9.387 4.684 1.562A1 1 0 0 0 22 17V7a1 1 0 0 0-1.316-.949L16 7.613v8.774Z" clip-rule="evenodd" />
      </svg>

      Video call
    </a>
  </div>
  <div class="mb-6">
    <h3 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Media</h3>
    <div class="grid grid-cols-3 gap-2">
      <button type="button" class="h-24 w-24">
        <img src="/images/chat/image-1.png" class="rounded-lg" alt="user image 1" />
      </button>
      <button type="button" class="h-24 w-24">
        <img src="/images/chat/image-2.png" class="rounded-lg" alt="user image 1" />
      </button>
      <button type="button" class="flex h-24 w-24 items-center justify-center rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">
        <span class="text-sm font-semibold">+32</span>
      </button>
    </div>
  </div>
  <div class="mb-6">
    <h3 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Files</h3>
    <div class="space-y-2">
      <div class="my-2.5 flex items-start justify-between rounded-xl bg-gray-50 p-2 dark:bg-gray-600">
        <div class="me-2">
          <button type="button" class="flex items-center gap-2 pb-2 text-sm font-medium text-gray-900 hover:underline dark:text-white">
            <svg class="h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                clip-rule="evenodd"
              />
            </svg>
            flowbite-presentation.pdf
          </button>
          <span class="flex gap-2 text-xs font-normal text-gray-500 dark:text-gray-400">
            12 Pages
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="self-center" width="3" height="4" viewBox="0 0 3 4" fill="none">
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280"></circle>
            </svg>
            18 MB
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="self-center" width="3" height="4" viewBox="0 0 3 4" fill="none">
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280"></circle>
            </svg>
            PDF
          </span>
        </div>
        <div class="inline-flex items-center self-center">
          <button
            data-tooltip-target="download-file-1-tooltip"
            class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-600"
            type="button"
          >
            <svg class="h-4 w-4 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
              <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path>
              <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path>
            </svg>
            <span class="sr-only">Download file</span>
          </button>
          <div
            id="download-file-1-tooltip"
            role="tooltip"
            class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Download file
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
      <div class="my-2.5 flex items-start justify-between rounded-xl bg-gray-50 p-2 dark:bg-gray-600">
        <div class="me-2">
          <button type="button" class="flex items-center gap-2 pb-2 text-sm font-medium text-gray-900 hover:underline dark:text-white">
            <svg class="h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.394 9.553a1 1 0 0 0-1.817.062l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .894-1.447l-2-4A1 1 0 0 0 13.2 13.4l-.53.706-1.276-2.553ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"
                clip-rule="evenodd"
              />
            </svg>
            hero-image.jpg
          </button>
          <span class="flex gap-2 text-xs font-normal text-gray-500 dark:text-gray-400">
            35 kB
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="self-center" width="3" height="4" viewBox="0 0 3 4" fill="none">
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280"></circle>
            </svg>
            JPG
          </span>
        </div>
        <div class="inline-flex items-center self-center">
          <button
            data-tooltip-target="download-file-2-tooltip"
            class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-600"
            type="button"
          >
            <svg class="h-4 w-4 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
              <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path>
              <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path>
            </svg>
            <span class="sr-only">Download file</span>
          </button>
          <div
            id="download-file-2-tooltip"
            role="tooltip"
            class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Download file
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
      <div class="my-2.5 flex items-start justify-between rounded-xl bg-gray-50 p-2 dark:bg-gray-600">
        <div class="me-2">
          <button type="button" class="flex items-center gap-2 pb-2 text-sm font-medium text-gray-900 hover:underline dark:text-white">
            <svg class="h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2.318.052h-.002A1 1 0 0 0 12 8v5.293A4.033 4.033 0 0 0 10.5 13C8.787 13 7 14.146 7 16s1.787 3 3.5 3 3.5-1.146 3.5-3c0-.107-.006-.211-.017-.313A1.04 1.04 0 0 0 14 15.5V9.766c.538.493 1 1.204 1 2.234a1 1 0 1 0 2 0c0-1.881-.956-3.14-1.86-3.893a6.4 6.4 0 0 0-1.636-.985 4.009 4.009 0 0 0-.165-.063l-.014-.005-.005-.001-.002-.001ZM9 16c0-.356.452-1 1.5-1s1.5.644 1.5 1-.452 1-1.5 1S9 16.356 9 16Z"
                clip-rule="evenodd"
              />
            </svg>
            pink-floyd.mp3
          </button>
          <span class="flex gap-2 text-xs font-normal text-gray-500 dark:text-gray-400">
            3.3 MB
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="self-center" width="3" height="4" viewBox="0 0 3 4" fill="none">
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280"></circle>
            </svg>
            MP3
          </span>
        </div>
        <div class="inline-flex items-center self-center">
          <button
            data-tooltip-target="download-file-3-tooltip"
            class="inline-flex items-center self-center rounded-lg bg-gray-50 p-2 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-50 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 dark:focus:ring-gray-600"
            type="button"
          >
            <svg class="h-4 w-4 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
              <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path>
              <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path>
            </svg>
            <span class="sr-only">Download file</span>
          </button>
          <div
            id="download-file-3-tooltip"
            role="tooltip"
            class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
          >
            Download file
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="mb-4 space-y-2">
    <label class="inline-flex cursor-pointer items-center">
      <input type="checkbox" value="" class="peer sr-only" />
      <div
        class="peer relative h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
      ></div>
      <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">Mute notifications</span>
    </label>
    <label class="inline-flex cursor-pointer items-center">
      <input type="checkbox" value="" class="peer sr-only" data-moderation-checkbox />
      <div
        class="peer relative h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
      ></div>
      <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">Disappearing messages</span>
    </label>
  </div>
  <div class="mb-6">
    <button type="button" class="flex w-full items-start gap-2 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-gray-600">
      <svg class="h-6 w-6 shrink-0 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M10 5a2 2 0 0 0-2 2v3h2.4A7.48 7.48 0 0 0 8 15.5a7.48 7.48 0 0 0 2.4 5.5H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V7a4 4 0 1 1 8 0v1.15a7.446 7.446 0 0 0-1.943.685A.999.999 0 0 1 12 8.5V7a2 2 0 0 0-2-2Z"
          clip-rule="evenodd"
        />
        <path fill-rule="evenodd" d="M10 15.5a5.5 5.5 0 1 1 11 0 5.5 5.5 0 0 1-11 0Zm6.5-1.5a1 1 0 1 0-2 0v1.5a1 1 0 0 0 .293.707l1 1a1 1 0 0 0 1.414-1.414l-.707-.707V14Z" clip-rule="evenodd" />
      </svg>
      <div class="flex flex-col text-left">
        <span class="mb-1 text-sm font-medium text-gray-900 dark:text-white">Encryption</span>
        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Messages and calls are end-to-end encrypted. Click to verify</span>
      </div>
      <div class="my-auto">
        <svg class="h-6 w-6 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10 16 4-4-4-4" />
        </svg>
      </div>
    </button>
  </div>
  <div class="space-y-4">
    <button type="button" class="flex items-center text-sm font-medium text-red-700 hover:underline dark:text-red-500">
      <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z"
          clip-rule="evenodd"
        />
      </svg>
      Block user
    </button>
    <button type="button" class="flex items-center text-sm font-medium text-red-700 hover:underline dark:text-red-500">
      <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
      </svg>
      Report user
    </button>
    <button type="button" class="flex items-center text-sm font-medium text-red-700 hover:underline dark:text-red-500">
      <svg class="me-2 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
          fill-rule="evenodd"
          d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
          clip-rule="evenodd"
        />
      </svg>
      Delete chat
    </button>
  </div>
  <button
    type="button"
    data-drawer-dismiss="readUserDrawerAdvanced"
    aria-controls="readUserDrawerAdvanced"
    class="absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
  >
    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
    </svg>
    <span class="sr-only">Close menu</span>
  </button>
</div>
</div>
