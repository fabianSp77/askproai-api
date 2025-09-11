{{-- Flowbite Component: transaction --}}
@props(['title' => 'Tailwind CSS Single Transaction Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="grid grid-cols-12 gap-4">
  <div class="col-span-full mx-4 mt-4 ">
    <nav class="mb-4 flex" aria-label="Breadcrumb">
      <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        <li class="inline-flex items-center">
          <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
            <svg class="me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd" />
            </svg>
            Home
          </a>
        </li>
        <li>
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Invoices</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Invoice #184325</span>
          </div>
        </li>
      </ol>
    </nav>
    <div class="justify-between md:flex">
      <div class="mb-4 items-center sm:flex sm:space-x-4 md:mb-0">
        <h1 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white sm:mb-0">Order #1846325</h1>
        <span class="me-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
        <div class="hidden h-6 w-px bg-gray-200 dark:bg-gray-800 sm:flex"></div>
        <time datetime="2025-07-05" class="text-gray-500 dark:text-gray-400"><span class="font-medium text-gray-900 dark:text-white">Due date:</span> 25 August 2025</time>
      </div>
      <div class="flex items-center space-x-4">
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          View receipt
        </button>
        <button
          id="refundDropdownButton"
          data-dropdown-toggle="refundDropdown"
          type="button"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          Refund
          <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <div id="refundDropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-2-dropdown-button">
            <li>
              <a href="#" class="block rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Full refund</a>
            </li>
            <li>
              <a href="#" class="block rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">Partial refund</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="grid grid-cols-12 gap-4 p-4">
  <div class="col-span-12 2xl:col-span-8">
    <div class="h-full space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:space-y-8 md:p-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Order details</h2>
      <!-- Table -->
      <div class="relative overflow-x-auto">
        <table class="w-full text-left text-sm font-medium text-gray-900 dark:text-white rtl:text-right">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3 font-semibold">Product name</th>
              <th scope="col" class="px-6 py-3 font-semibold">Qty</th>
              <th scope="col" class="px-6 py-3 font-semibold">Price</th>
              <th scope="col" class="px-6 py-3 font-semibold">Discount</th>
              <th scope="col" class="min-w-32 px-6 py-3 font-semibold">Total price</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-6 font-medium text-gray-900 dark:text-white">
                <div class="text-base">Flowbite Developer Edition</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">HTML, Figma, JS</div>
              </th>
              <td class="h-16 px-6">$269</td>
              <td class="h-16 px-6">2</td>
              <td class="h-16 px-6">50%</td>
              <td class="h-16 px-6">$269</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-6 font-medium text-gray-900 dark:text-white">
                <div class="text-base">Flowbite Designer Edition</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">Figma Design System</div>
              </th>
              <td class="h-16 px-6">$149</td>
              <td class="h-16 px-6">3</td>
              <td class="h-16 px-6">0%</td>
              <td class="h-16 px-6">$447</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-6 font-medium">
                <div class="text-base">Flowbite Open Source</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">Open source components</div>
              </th>
              <td class="h-16 px-6">$0</td>
              <td class="h-16 px-6">1</td>
              <td class="h-16 px-6">0%</td>
              <td class="h-16 px-6">$0</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-6 font-medium text-gray-900 dark:text-white">
                <div class="text-base">2 Years Support</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">Premium support</div>
              </th>
              <td class="h-16 px-6">$199</td>
              <td class="h-16 px-6">1</td>
              <td class="h-16 px-6">0%</td>
              <td class="h-16 px-6">$199</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-6 font-medium text-gray-900 dark:text-white">
                <div class="text-base">Flowbite Developer (Team License)</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">HTML, Figma, JS</div>
              </th>
              <td class="h-16 px-6">$799</td>
              <td class="h-16 px-6">2</td>
              <td class="h-16 px-6">0%</td>
              <td class="h-16 px-6">$1598</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="ms-auto mt-4 max-w-xs">
        <h3 class="font-semibold text-gray-900 dark:text-white">Order summary</h3>
        <ul class="mt-4 max-w-md">
          <li class="mb-2 flex items-center justify-between rtl:space-x-reverse">
            <p class="min-w-0 flex-1 truncate text-gray-500 dark:text-gray-400">Subtotal</p>
            <div class="font-medium text-gray-900 dark:text-white">$320</div>
          </li>
          <li class="mb-2 flex items-center justify-between rtl:space-x-reverse">
            <p class="min-w-0 flex-1 truncate text-gray-500 dark:text-gray-400">Tax</p>
            <div class="font-medium text-gray-900 dark:text-white">$477</div>
          </li>
          <li class="mb-4 flex items-center justify-between rtl:space-x-reverse">
            <p class="min-w-0 flex-1 truncate text-gray-500 dark:text-gray-400">Shipping estimate</p>
            <div class="font-medium text-gray-900 dark:text-white">$0</div>
          </li>
          <li class="flex items-center justify-between text-lg font-bold text-gray-900 dark:text-white rtl:space-x-reverse">
            <p class="min-w-0 flex-1 truncate">Order total</p>
            <div>$2990</div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-span-12 2xl:col-span-4">
    <div class="mb-4 space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:space-y-6 md:p-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Customer details</h2>
      <!-- List -->
      <ul class="list-inside text-gray-500 dark:text-gray-400">
        <li class="flex items-center justify-between gap-8 border-b border-gray-200 pb-4 dark:border-gray-700">
          <p class="font-semibold text-gray-900 dark:text-white">Name</p>
          <p class="text-end">Joseph McFall</p>
        </li>
        <li class="flex items-center justify-between gap-8 border-b border-gray-200 py-4 dark:border-gray-700">
          <p class="font-semibold text-gray-900 dark:text-white">Email</p>
          <p class="text-end">name@example.com</p>
        </li>
        <li class="flex items-center justify-between gap-8 border-b border-gray-200 py-4 dark:border-gray-700">
          <p class="font-semibold text-gray-900 dark:text-white">Phone</p>
          <p class="text-end">+123 456 7890</p>
        </li>
        <li class="flex items-center justify-between gap-8 border-b border-gray-200 py-4 dark:border-gray-700">
          <p class="font-semibold text-gray-900 dark:text-white">Country</p>
          <p class="flex items-center">
            <svg class="me-2 h-4" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect y="0.857147" width="20" height="14.2857" rx="2" fill="white" />
              <mask id="mask0_2992_9965" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="16">
                <rect y="0.857147" width="20" height="14.2857" rx="2" fill="white" />
              </mask>
              <g mask="url(#mask0_2992_9965)">
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M20 0.857147H0V1.80953H20V0.857147ZM20 2.76191H0V3.71429H20V2.76191ZM0 4.66667H20V5.61905H0V4.66667ZM20 6.57143H0V7.52381H20V6.57143ZM0 8.4762H20V9.42858H0V8.4762ZM20 10.381H0V11.3333H20V10.381ZM0 12.2857H20V13.2381H0V12.2857ZM20 14.1905H0V15.1429H20V14.1905Z"
                  fill="#D02F44"
                />
                <rect y="0.857147" width="8.57143" height="6.66667" fill="#46467F" />
                <g filter="url(#filter0_d_2992_9965)">
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M1.90477 2.28572C1.90477 2.54871 1.69158 2.76191 1.42858 2.76191C1.16559 2.76191 0.952393 2.54871 0.952393 2.28572C0.952393 2.02272 1.16559 1.80952 1.42858 1.80952C1.69158 1.80952 1.90477 2.02272 1.90477 2.28572ZM3.80954 2.28572C3.80954 2.54871 3.59634 2.76191 3.33334 2.76191C3.07035 2.76191 2.85715 2.54871 2.85715 2.28572C2.85715 2.02272 3.07035 1.80952 3.33334 1.80952C3.59634 1.80952 3.80954 2.02272 3.80954 2.28572ZM5.23811 2.76191C5.5011 2.76191 5.7143 2.54871 5.7143 2.28572C5.7143 2.02272 5.5011 1.80952 5.23811 1.80952C4.97511 1.80952 4.76192 2.02272 4.76192 2.28572C4.76192 2.54871 4.97511 2.76191 5.23811 2.76191ZM7.61906 2.28572C7.61906 2.54871 7.40586 2.76191 7.14287 2.76191C6.87988 2.76191 6.66668 2.54871 6.66668 2.28572C6.66668 2.02272 6.87988 1.80952 7.14287 1.80952C7.40586 1.80952 7.61906 2.02272 7.61906 2.28572ZM2.38096 3.71429C2.64396 3.71429 2.85715 3.50109 2.85715 3.2381C2.85715 2.9751 2.64396 2.76191 2.38096 2.76191C2.11797 2.76191 1.90477 2.9751 1.90477 3.2381C1.90477 3.50109 2.11797 3.71429 2.38096 3.71429ZM4.76192 3.2381C4.76192 3.50109 4.54872 3.71429 4.28573 3.71429C4.02273 3.71429 3.80954 3.50109 3.80954 3.2381C3.80954 2.9751 4.02273 2.76191 4.28573 2.76191C4.54872 2.76191 4.76192 2.9751 4.76192 3.2381ZM6.19049 3.71429C6.45348 3.71429 6.66668 3.50109 6.66668 3.2381C6.66668 2.9751 6.45348 2.76191 6.19049 2.76191C5.9275 2.76191 5.7143 2.9751 5.7143 3.2381C5.7143 3.50109 5.9275 3.71429 6.19049 3.71429ZM7.61906 4.19048C7.61906 4.45347 7.40586 4.66667 7.14287 4.66667C6.87988 4.66667 6.66668 4.45347 6.66668 4.19048C6.66668 3.92748 6.87988 3.71429 7.14287 3.71429C7.40586 3.71429 7.61906 3.92748 7.61906 4.19048ZM5.23811 4.66667C5.5011 4.66667 5.7143 4.45347 5.7143 4.19048C5.7143 3.92748 5.5011 3.71429 5.23811 3.71429C4.97511 3.71429 4.76192 3.92748 4.76192 4.19048C4.76192 4.45347 4.97511 4.66667 5.23811 4.66667ZM3.80954 4.19048C3.80954 4.45347 3.59634 4.66667 3.33334 4.66667C3.07035 4.66667 2.85715 4.45347 2.85715 4.19048C2.85715 3.92748 3.07035 3.71429 3.33334 3.71429C3.59634 3.71429 3.80954 3.92748 3.80954 4.19048ZM1.42858 4.66667C1.69158 4.66667 1.90477 4.45347 1.90477 4.19048C1.90477 3.92748 1.69158 3.71429 1.42858 3.71429C1.16559 3.71429 0.952393 3.92748 0.952393 4.19048C0.952393 4.45347 1.16559 4.66667 1.42858 4.66667ZM2.85715 5.14286C2.85715 5.40585 2.64396 5.61905 2.38096 5.61905C2.11797 5.61905 1.90477 5.40585 1.90477 5.14286C1.90477 4.87987 2.11797 4.66667 2.38096 4.66667C2.64396 4.66667 2.85715 4.87987 2.85715 5.14286ZM4.28573 5.61905C4.54872 5.61905 4.76192 5.40585 4.76192 5.14286C4.76192 4.87987 4.54872 4.66667 4.28573 4.66667C4.02273 4.66667 3.80954 4.87987 3.80954 5.14286C3.80954 5.40585 4.02273 5.61905 4.28573 5.61905ZM6.66668 5.14286C6.66668 5.40585 6.45348 5.61905 6.19049 5.61905C5.9275 5.61905 5.7143 5.40585 5.7143 5.14286C5.7143 4.87987 5.9275 4.66667 6.19049 4.66667C6.45348 4.66667 6.66668 4.87987 6.66668 5.14286ZM7.14287 6.57143C7.40586 6.57143 7.61906 6.35823 7.61906 6.09524C7.61906 5.83225 7.40586 5.61905 7.14287 5.61905C6.87988 5.61905 6.66668 5.83225 6.66668 6.09524C6.66668 6.35823 6.87988 6.57143 7.14287 6.57143ZM5.7143 6.09524C5.7143 6.35823 5.5011 6.57143 5.23811 6.57143C4.97511 6.57143 4.76192 6.35823 4.76192 6.09524C4.76192 5.83225 4.97511 5.61905 5.23811 5.61905C5.5011 5.61905 5.7143 5.83225 5.7143 6.09524ZM3.33334 6.57143C3.59634 6.57143 3.80954 6.35823 3.80954 6.09524C3.80954 5.83225 3.59634 5.61905 3.33334 5.61905C3.07035 5.61905 2.85715 5.83225 2.85715 6.09524C2.85715 6.35823 3.07035 6.57143 3.33334 6.57143ZM1.90477 6.09524C1.90477 6.35823 1.69158 6.57143 1.42858 6.57143C1.16559 6.57143 0.952393 6.35823 0.952393 6.09524C0.952393 5.83225 1.16559 5.61905 1.42858 5.61905C1.69158 5.61905 1.90477 5.83225 1.90477 6.09524Z"
                    fill="url(#paint0_linear_2992_9965)"
                  />
                </g>
              </g>
              <defs>
                <filter id="filter0_d_2992_9965" x="0.952393" y="1.80952" width="6.66675" height="5.7619" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                  <feFlood flood-opacity="0" result="BackgroundImageFix" />
                  <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha" />
                  <feOffset dy="1" />
                  <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0" />
                  <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2992_9965" />
                  <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2992_9965" result="shape" />
                </filter>
                <linearGradient id="paint0_linear_2992_9965" x1="0.952393" y1="1.80952" x2="0.952393" y2="6.57143" gradientUnits="userSpaceOnUse">
                  <stop stop-color="white" />
                  <stop offset="1" stop-color="#F0F0F0" />
                </linearGradient>
              </defs>
            </svg>
            United States
          </p>
        </li>
        <li class="flex items-center justify-between gap-8 pt-4">
          <p class="font-semibold text-gray-900 dark:text-white">Address</p>
          <p class="text-end">62 Miles Drive St, Newark, NJ 07103, California,</p>
        </li>
      </ul>
    </div>
    <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:space-y-6 md:p-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Order history</h2>
      <!-- Timeline -->
      <ol class="relative ms-3 border-s border-gray-200 dark:border-gray-700">
        <li class="mb-10 ms-6">
          <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 ring-8 ring-white dark:bg-primary-900 dark:ring-gray-800">
            <svg class="h-3.5 w-3.5 text-primary-800 dark:text-primary-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M4 4c0-.6.4-1 1-1h1.5c.5 0 .9.3 1 .8L7.9 6H19a1 1 0 0 1 1 1.2l-1.3 6a1 1 0 0 1-1 .8h-8l.2 1H17a3 3 0 1 1-2.8 2h-2.4a3 3 0 1 1-4-1.8L5.7 5H5a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
            </svg>
          </span>
          <h3 class="mb-1 block items-center font-semibold text-gray-900 dark:text-white sm:flex">
            Checkout started <span class="mx-2 text-sm font-normal text-gray-500 dark:text-gray-400">09:23</span>
            <div class="me-2 hidden h-2 w-2 rounded-full bg-gray-200 dark:bg-gray-700 sm:flex"></div>
            <div class="mt-1 text-sm font-normal text-gray-500 dark:text-gray-400 sm:mt-0">25th August 2025</div>
          </h3>
          <p class="text-base font-normal text-gray-500 dark:text-gray-400">via flowbite.com / www.google.com</p>
        </li>
        <li class="mb-10 ms-6">
          <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 ring-8 ring-white dark:bg-primary-900 dark:ring-gray-800">
            <svg class="h-3.5 w-3.5 text-primary-800 dark:text-primary-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
              <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
              <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
            </svg>
          </span>
          <h3 class="mb-1 block items-center font-semibold text-gray-900 dark:text-white sm:flex">
            Purchased <span class="mx-2 text-sm font-normal text-gray-500 dark:text-gray-400">09:27</span>
            <div class="me-2 hidden h-2 w-2 rounded-full bg-gray-200 dark:bg-gray-700 sm:flex"></div>
            <div class="mt-1 text-sm font-normal text-gray-500 dark:text-gray-400 sm:mt-0">25th August 2025</div>
          </h3>
          <p class="text-base font-normal text-gray-500 dark:text-gray-400">for $2990 via VISA card ending in 8262</p>
        </li>
        <li class="mb-10 ms-6">
          <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 ring-8 ring-white dark:bg-primary-900 dark:ring-gray-800">
            <svg class="h-3.5 w-3.5 text-primary-800 dark:text-primary-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
          <h3 class="mb-1 block items-center font-semibold text-gray-900 dark:text-white sm:flex">
            Receipt email sent <span class="mx-2 text-sm font-normal text-gray-500 dark:text-gray-400">09:29</span>
            <div class="me-2 hidden h-2 w-2 rounded-full bg-gray-200 dark:bg-gray-700 sm:flex"></div>
            <div class="mt-1 text-sm font-normal text-gray-500 dark:text-gray-400 sm:mt-0">25th August 2025</div>
          </h3>
          <p class="text-base font-normal text-gray-500 dark:text-gray-400">Receipt #648573</p>
        </li>
      </ol>
    </div>
  </div>
</div>
</div>
