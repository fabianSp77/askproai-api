{{-- Flowbite Component: invoice --}}
@props(['title' => 'Tailwind CSS Invoice Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="grid grid-cols-12 gap-4 border-b border-gray-200 bg-white pb-4 dark:border-gray-700 dark:bg-gray-800">
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
    <div class="justify-between sm:flex">
      <a href="#" class="mb-4 inline-flex items-center font-medium text-gray-900 hover:underline dark:text-white sm:mb-0">
        <svg class="me-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4 4 4" />
        </svg>
        Back to invoices
      </a>
      <div class="flex items-center space-x-4">
        <a
          href="../create-invoice"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z" clip-rule="evenodd" />
          </svg>
          Edit
        </a>
        <button
          id="downloadDropdownButton"
          data-dropdown-toggle="downloadDropdown"
          type="button"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          Download
          <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <div id="downloadDropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
          <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-2-dropdown-button">
            <li>
              <a href="#" class="flex items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Save as PDF
              </a>
            </li>
            <li>
              <a href="#" class="flex items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Save as Excel
              </a>
            </li>
            <li>
              <a href="#" class="flex items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm-1.02 4.804a1 1 0 1 0-1.96.392l1 5a1 1 0 0 0 1.838.319L12 15.61l1.143 1.905a1 1 0 0 0 1.838-.319l1-5a1 1 0 0 0-1.962-.392l-.492 2.463-.67-1.115a1 1 0 0 0-1.714 0l-.67 1.116-.492-2.464Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Save as Word
              </a>
            </li>
          </ul>
        </div>
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5c0 1.1.9 2 2 2h1v-4c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4c0 .6-.4 1-1 1H9Z"
              clip-rule="evenodd"
            />
          </svg>
          Print
        </button>
      </div>
    </div>
  </div>
</div>
<div class="grid grid-cols-12 gap-4 p-4">
  <div class="col-span-12 2xl:col-span-9">
    <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:space-y-8 md:p-6">
      <a href="https://flowbite.com" class="flex items-center">
        <img src="/images/logo.svg" class="mr-3 h-6 sm:h-8" alt="Flowbite Logo" />
        <span class="self-center whitespace-nowrap text-xl font-semibold dark:text-white sm:text-2xl">Flowbite</span>
      </a>
      <div class="flex items-center justify-between border-b border-t border-gray-100 py-4 dark:border-gray-700">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Invoice #1846325</h1>
        <time datetime="2025-07-05" class="text-lg text-gray-500 dark:text-gray-400">Date: 05/07/2025</time>
      </div>
      <!-- Payment Details -->
      <div class="flex items-start justify-between gap-8">
        <div class="md:w-64">
          <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Pay to:</h2>
          <address class="text-sm text-gray-500 dark:text-gray-400 md:text-base">
            <span class="font-medium text-gray-900 dark:text-white">Flowbite LLC,</span> LOUISVILLE, Selby 3864 Johnson Street, United States of America, <br />
            VAT Code: AA-1234567890
          </address>
        </div>
        <div class="md:w-64">
          <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Invoice to:</h2>
          <address class="text-sm text-gray-500 dark:text-gray-400 md:text-base">
            <span class="font-medium text-gray-900 dark:text-white">Bonnie Green,</span> Carolina, Selby 3864 Johnson Street, United States of America, <br />
            <span class="font-medium text-gray-900 dark:text-white">name@company.com</span>
          </address>
        </div>
      </div>
      <!-- Table -->
      <div class="relative overflow-x-auto">
        <table class="w-full text-left text-sm font-medium text-gray-900 dark:text-white rtl:text-right">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3 font-semibold">Product name</th>
              <th scope="col" class="px-6 py-3 font-semibold">Qty</th>
              <th scope="col" class="px-6 py-3 font-semibold">Price</th>
              <th scope="col" class="px-6 py-3 font-semibold">Discount</th>
              <th scope="col" class="text-nowrap px-6  py-3 font-semibold">Total price</th>
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
  <div class="col-span-12 2xl:col-span-3">
    <div class="relative h-full space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:space-y-6 md:p-6">
      <!-- Alert -->
      <div class="mb-4 flex items-center rounded-lg bg-green-50 p-4 text-base font-medium text-green-800 dark:bg-gray-700 dark:text-green-300" role="alert">
        <svg class="me-2 inline h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
        </svg>
        <span class="sr-only">Info</span>
        <div>Invoice paid</div>
      </div>
      <h2 class="text-lg text-gray-500 dark:text-gray-400">Details:</h2>
      <!-- Price & Input -->
      <div class="flex items-center justify-between border-b border-t border-gray-100 py-4 dark:border-gray-700">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">$2,999</h3>
        <label class="sr-only" for="payment-option" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Select an option</label>
        <select
          id="payment-option"
          class="block w-40 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected>Paid</option>
          <option value="PA">Unpaid</option>
          <option value="UN">Pending</option>
          <option value="OV">Overdue</option>
        </select>
      </div>
      <!-- List -->
      <ul class="max-w-md list-inside space-y-4 border-b border-gray-100 pb-4 text-gray-500 dark:border-gray-700 dark:text-gray-400 sm:pb-6">
        <li class="flex items-center">
          <svg class="me-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 8a4 4 0 1 1 7.8 1.3l-2.5 2.5A4 4 0 0 1 5 8Zm4 5H7a4 4 0 0 0-4 4v1c0 1.1.9 2 2 2h2.2a3 3 0 0 1-.1-1.6l.6-3.4a3 3 0 0 1 .9-1.5L9 13Zm9-5a3 3 0 0 0-2 .9l-6 6a1 1 0 0 0-.3.5L9 18.8a1 1 0 0 0 1.2 1.2l3.4-.7c.2 0 .3-.1.5-.3l6-6a3 3 0 0 0-2-5Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="me-2 font-medium text-gray-900 dark:text-white">Created by:</span> Jese Leos
        </li>
        <li class="flex items-center">
          <svg class="me-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7c0-1.1.9-2 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-7H7v2h2v-2Zm2 0h2v2h-2v-2Zm6 0h-2v2h2v-2ZM7 16h2v2H7v-2Zm6 0h-2v2h2v-2Zm2 0h2v2h-2v-2Z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="me-2 font-medium text-gray-900 dark:text-white">Due date:</span> 08 July 2025
        </li>
        <li class="flex items-center">
          <svg class="me-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M4 5a2 2 0 0 0-2 2v10c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4Zm0 6h16v6H4v-6Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M5 14c0-.6.4-1 1-1h2a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Zm5 0c0-.6.4-1 1-1h5a1 1 0 1 1 0 2h-5a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
          </svg>
          Pay by Bank Transfer
        </li>
        <li class="flex items-center">
          <svg class="me-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
            <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
          </svg>
          <span class="me-2 font-medium text-gray-900 dark:text-white">Currency:</span> American Dollar
        </li>
      </ul>
      <!-- Timeline -->
      <ol class="relative ms-2 border-s border-gray-200 dark:border-gray-700">
        <li class="mb-4 ms-6">
          <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-white dark:bg-gray-800">
            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
            </svg>
          </span>
          <div class="flex items-center justify-between">
            <h4 class="flex items-center font-medium text-gray-900 dark:text-white">Invoice created:</h4>
            <time datetime="2025-07-05" class="text-gray-500 dark:text-gray-400">05/07/2025</time>
          </div>
        </li>
        <li class="mb-4 ms-6">
          <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-white dark:bg-gray-800">
            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
            </svg>
          </span>
          <div class="flex items-center justify-between">
            <h4 class="flex items-center font-medium text-gray-900 dark:text-white">Invoice sent:</h4>
            <time datetime="2025-07-05" class="text-gray-500 dark:text-gray-400">06/07/2025</time>
          </div>
        </li>
        <li class="ms-6">
          <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-white dark:bg-gray-800">
            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
            </svg>
          </span>
          <div class="flex items-center justify-between">
            <h4 class="flex items-center font-medium text-gray-900 dark:text-white">Invoice paid:</h4>
            <time datetime="2025-07-05" class="text-gray-500 dark:text-gray-400">08/07/2025</time>
          </div>
        </li>
      </ol>
      <!-- Bottom Alert -->
      <div id="alert-update-2" class="bottom-6 left-6 right-6 w-auto rounded-lg bg-gray-50 p-4 dark:bg-gray-700 2xl:absolute" role="alert">
        <div class="mb-3 flex items-center justify-between">
          <span class="mr-2 rounded-sm bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-900 dark:bg-gray-600 dark:text-white">Version 2.0</span>
          <button
            type="button"
            class="inline-flex h-6 w-6 rounded-lg bg-gray-100 p-1 text-gray-500 hover:bg-gray-200 focus:ring-2 focus:ring-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
            data-dismiss-target="#alert-update-2"
            aria-label="Close"
          >
            <span class="sr-only">Dismiss</span>
            <svg aria-hidden="true" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
        <div class="mb-3 text-sm text-gray-500 dark:text-gray-400">Get paid 3x faster with our PRO plan for online payments!</div>
        <a href="#" class="text-sm font-medium text-primary-700 underline hover:no-underline dark:text-primary-500"> Upgrade now for free </a>
      </div>
    </div>
  </div>
</div>
</div>
