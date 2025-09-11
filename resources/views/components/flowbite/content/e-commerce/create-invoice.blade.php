{{-- Flowbite Component: create-invoice --}}
@props(['title' => 'Tailwind CSS Create Invoice Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
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
            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">New Invoice</span>
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
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>

          Discard
        </button>
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
    </div>
  </div>
</div>
<div class="grid grid-cols-12 gap-4 p-4">
  <div class="col-span-12 2xl:col-span-5">
    <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <!-- Inputs -->
      <form>
        <div class="mb-4 grid gap-4 md:grid-cols-2">
          <div>
            <label for="invoice_number" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Invoice Number*</label>
            <input
              type="text"
              id="invoice_number"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="FW-XXXXXX"
              required
            />
          </div>
          <div>
            <label for="customer" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Customer*</label>
            <input
              type="text"
              id="customer"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Bonnie Green"
              required
            />
          </div>
          <div>
            <label for="payment_condition" class="mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">
              Payment Condition
              <button data-popover-target="popover-conditions-description" class="ms-2 text-gray-400 hover:text-gray-900 dark:hover:text-white" type="button">
                <svg class="h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="sr-only">Show information</span>
              </button>
            </label>
            <select
              id="payment_condition"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            >
              <option selected>Select condition</option>
              <option value="CA">Cash in Advance</option>
              <option value="CD">Cash on Delivery</option>
              <option value="AP">Advance Payment:</option>
              <option value="CR">Credit</option>
            </select>
            <div
              id="popover-conditions-description"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              <div class="space-y-1.5 text-gray-300">
                <h3 class="font-semibold text-white">What are payment conditions?</h3>
                <p>Payment conditions generally refer to the terms and conditions under which a payment is expected to be made.</p>
              </div>
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
          <div>
            <label for="currency" class="mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">
              Currency
              <button data-popover-target="popover-currency-description" class="ms-2 text-gray-400 hover:text-gray-900 dark:hover:text-white" type="button">
                <svg class="h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="sr-only">Show information</span>
              </button>
            </label>
            <select
              id="currency"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            >
              <option selected>United States Dollar (USD)</option>
              <option value="EU">Euro (EUR)</option>
              <option value="GB">British Pound Sterling (GBP)</option>
              <option value="JP">Japanese Yen (JPY)</option>
              <option value="CH">Swiss Franc (CHF)</option>
              <option value="CA">Canadian Dollar (CAD)</option>
              <option value="CN">Chinese Yuan (CNY)</option>
              <option value="IN">Indian Rupee (INR)</option>
            </select>
            <div
              id="popover-currency-description"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              <div class="space-y-1.5 text-gray-300">
                <h3 class="font-semibold text-white">What are currencies?</h3>
                <p>Currencies are the official medium of exchange for transactions within a country.</p>
              </div>
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
          <div>
            <label for="issue_date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Issue Date*</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7c0-1.1.9-2 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-7H7v2h2v-2Zm2 0h2v2h-2v-2Zm6 0h-2v2h2v-2ZM7 16h2v2H7v-2Zm6 0h-2v2h2v-2Zm2 0h2v2h-2v-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                datepicker
                id="issue_date"
                type="text"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Select date"
              />
            </div>
          </div>
          <div>
            <label for="due_date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Due Date*</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7c0-1.1.9-2 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-7H7v2h2v-2Zm2 0h2v2h-2v-2Zm6 0h-2v2h2v-2ZM7 16h2v2H7v-2Zm6 0h-2v2h2v-2Zm2 0h2v2h-2v-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                datepicker
                id="due_date"
                type="text"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Select date"
              />
            </div>
          </div>
          <div>
            <label for="delivery_date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Delivery Date</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7c0-1.1.9-2 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-7H7v2h2v-2Zm2 0h2v2h-2v-2Zm6 0h-2v2h2v-2ZM7 16h2v2H7v-2Zm6 0h-2v2h2v-2Zm2 0h2v2h-2v-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                datepicker
                id="delivery_date"
                type="text"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="Select date"
              />
            </div>
          </div>
          <div>
            <label for="reference" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Reference of the Invoice</label>
            <input
              type="text"
              id="reference"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Invoice number"
              required
            />
          </div>
        </div>
        <div class="mb-4">
          <label for="terms" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Object</label>
          <input
            type="text"
            id="terms"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Payment terms"
            required
          />
        </div>
        <label for="editor" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Additional Info</label>
        <div class="mb-4 w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
          <div class="flex items-center justify-between border-b px-3 py-2 dark:border-gray-600 border-gray-200">
            <div class="flex flex-wrap items-center divide-gray-200 dark:divide-gray-600 sm:divide-x sm:rtl:divide-x-reverse">
              <div class="flex items-center space-x-1 sm:pe-4 rtl:space-x-reverse">
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8v8a5 5 0 1 0 10 0V6.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 0 4 0V8" />
                  </svg>
                  <span class="sr-only">Attach file</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 20">
                    <path
                      d="M8 0a7.992 7.992 0 0 0-6.583 12.535 1 1 0 0 0 .12.183l.12.146c.112.145.227.285.326.4l5.245 6.374a1 1 0 0 0 1.545-.003l5.092-6.205c.206-.222.4-.455.578-.7l.127-.155a.934.934 0 0 0 .122-.192A8.001 8.001 0 0 0 8 0Zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"
                    />
                  </svg>
                  <span class="sr-only">Embed map</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 20">
                    <path
                      d="M14.066 0H7v5a2 2 0 0 1-2 2H0v11a1.97 1.97 0 0 0 1.934 2h12.132A1.97 1.97 0 0 0 16 18V2a1.97 1.97 0 0 0-1.934-2ZM10.5 6a1.5 1.5 0 1 1 0 2.999A1.5 1.5 0 0 1 10.5 6Zm2.221 10.515a1 1 0 0 1-.858.485h-8a1 1 0 0 1-.9-1.43L5.6 10.039a.978.978 0 0 1 .936-.57 1 1 0 0 1 .9.632l1.181 2.981.541-1a.945.945 0 0 1 .883-.522 1 1 0 0 1 .879.529l1.832 3.438a1 1 0 0 1-.031.988Z"
                    />
                    <path d="M5 5V.13a2.96 2.96 0 0 0-1.293.749L.879 3.707A2.98 2.98 0 0 0 .13 5H5Z" />
                  </svg>
                  <span class="sr-only">Upload image</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 20">
                    <path d="M5 5V.13a2.96 2.96 0 0 0-1.293.749L.879 3.707A2.96 2.96 0 0 0 .13 5H5Z" />
                    <path
                      d="M14.067 0H7v5a2 2 0 0 1-2 2H0v11a1.969 1.969 0 0 0 1.933 2h12.134A1.97 1.97 0 0 0 16 18V2a1.97 1.97 0 0 0-1.933-2ZM6.709 13.809a1 1 0 1 1-1.418 1.409l-2-2.013a1 1 0 0 1 0-1.412l2-2a1 1 0 0 1 1.414 1.414L5.412 12.5l1.297 1.309Zm6-.6-2 2.013a1 1 0 1 1-1.418-1.409l1.3-1.307-1.295-1.295a1 1 0 0 1 1.414-1.414l2 2a1 1 0 0 1-.001 1.408v.004Z"
                    />
                  </svg>
                  <span class="sr-only">Format code</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM13.5 6a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm-7 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm3.5 9.5A5.5 5.5 0 0 1 4.6 11h10.81A5.5 5.5 0 0 1 10 15.5Z" />
                  </svg>
                  <span class="sr-only">Add emoji</span>
                </button>
              </div>
              <div class="flex flex-wrap items-center space-x-1 sm:ps-4 rtl:space-x-reverse">
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 21 18">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 3h9.563M9.5 9h9.563M9.5 15h9.563M1.5 13a2 2 0 1 1 3.321 1.5L1.5 17h5m-5-15 2-1v6m-2 0h4" />
                  </svg>
                  <span class="sr-only">Add list</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      d="M18 7.5h-.423l-.452-1.09.3-.3a1.5 1.5 0 0 0 0-2.121L16.01 2.575a1.5 1.5 0 0 0-2.121 0l-.3.3-1.089-.452V2A1.5 1.5 0 0 0 11 .5H9A1.5 1.5 0 0 0 7.5 2v.423l-1.09.452-.3-.3a1.5 1.5 0 0 0-2.121 0L2.576 3.99a1.5 1.5 0 0 0 0 2.121l.3.3L2.423 7.5H2A1.5 1.5 0 0 0 .5 9v2A1.5 1.5 0 0 0 2 12.5h.423l.452 1.09-.3.3a1.5 1.5 0 0 0 0 2.121l1.415 1.413a1.5 1.5 0 0 0 2.121 0l.3-.3 1.09.452V18A1.5 1.5 0 0 0 9 19.5h2a1.5 1.5 0 0 0 1.5-1.5v-.423l1.09-.452.3.3a1.5 1.5 0 0 0 2.121 0l1.415-1.414a1.5 1.5 0 0 0 0-2.121l-.3-.3.452-1.09H18a1.5 1.5 0 0 0 1.5-1.5V9A1.5 1.5 0 0 0 18 7.5Zm-8 6a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z"
                    />
                  </svg>
                  <span class="sr-only">Settings</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M18 2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2ZM2 18V7h6.7l.4-.409A4.309 4.309 0 0 1 15.753 7H18v11H2Z" />
                    <path d="M8.139 10.411 5.289 13.3A1 1 0 0 0 5 14v2a1 1 0 0 0 1 1h2a1 1 0 0 0 .7-.288l2.886-2.851-3.447-3.45ZM14 8a2.463 2.463 0 0 0-3.484 0l-.971.983 3.468 3.468.987-.971A2.463 2.463 0 0 0 14 8Z" />
                  </svg>
                  <span class="sr-only">Timeline</span>
                </button>
                <button type="button" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z" />
                    <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z" />
                  </svg>
                  <span class="sr-only">Download</span>
                </button>
              </div>
            </div>
            <button type="button" data-tooltip-target="tooltip-fullscreen" class="cursor-pointer rounded-sm p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:ms-auto">
              <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 19 19">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1h5m0 0v5m0-5-5 5M1.979 6V1H7m0 16.042H1.979V12M18 12v5.042h-5M13 12l5 5M2 1l5 5m0 6-5 5" />
              </svg>
              <span class="sr-only">Full screen</span>
            </button>
            <div
              id="tooltip-fullscreen"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700"
            >
              Show full screen
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
          <div class="rounded-b-lg bg-gray-50 px-4 py-2 dark:bg-gray-700">
            <textarea
              id="editor"
              rows="8"
              class="block w-full border-0 bg-gray-50 px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
              placeholder="Receipt Info (optional)"
              required
            ></textarea>
          </div>
        </div>
        <label class="relative inline-flex cursor-pointer items-center">
          <input type="checkbox" value="" class="peer sr-only" />
          <div
            class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
          ></div>
          <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">VAT Applicable</span>
        </label>
      </form>
      <!-- Table -->
      <div class="relative overflow-x-auto">
        <table class="w-full text-left text-sm font-medium text-gray-900 dark:text-white rtl:text-right">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Product name</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Price</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Quantity</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Discount</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Total Price</th>
              <th scope="col" class="sr-only">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <div class="text-sm">Flowbite Developer Edition</div>
              </th>
              <td class="h-16 px-3">$269</td>
              <td class="h-16 px-3">2</td>
              <td class="h-16 px-3">50%</td>
              <td class="h-16 px-3">$269</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <div class="text-sm">Flowbite Designer Edition</div>
              </th>
              <td class="h-16 px-3">$149</td>
              <td class="h-16 px-3">3</td>
              <td class="h-16 px-3">0%</td>
              <td class="h-16 px-3">$447</td>
            </tr>
            <form action="#">
              <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <th scope="row" class="h-16 whitespace-nowrap px-3 font-medium">
                  <label for="product_name" class="sr-only">Product Name</label>
                  <input
                    type="text"
                    id="product_name"
                    class="block rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="Product name"
                    required
                  />
                </th>
                <td class="h-16 px-3">
                  <label for="product_price" class="sr-only">Product Price</label>
                  <input
                    type="text"
                    id="product_price"
                    class="block w-24 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="Price"
                    required
                  />
                </td>
                <td class="h-16 px-3">
                  <label for="counter-input" class="sr-only">Choose quantity:</label>
                  <div class="relative flex items-center">
                    <button
                      type="button"
                      id="decrement-button"
                      data-input-counter-decrement="counter-input"
                      class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                    >
                      <svg class="h-2.5 w-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                      </svg>
                    </button>
                    <input
                      type="text"
                      id="counter-input"
                      data-input-counter
                      class="max-w-[2.5rem] shrink-0 border-0 bg-transparent text-center text-sm font-normal text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
                      placeholder=""
                      value="12"
                      required
                    />
                    <button
                      type="button"
                      id="increment-button"
                      data-input-counter-increment="counter-input"
                      class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                    >
                      <svg class="h-2.5 w-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                      </svg>
                    </button>
                  </div>
                </td>
                <td class="h-16 px-3">
                  <label for="discount" class="sr-only">Discount select</label>
                  <select
                    id="discount"
                    class="block rounded-lg border border-gray-300 bg-gray-50 py-2 !pe-6 ps-2 text-xs text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    required
                  >
                    <option selected value="0">0%</option>
                    <option selected value="10">10%</option>
                    <option value="25">25%</option>
                    <option value="75">75%</option>
                    <option value="100">100%</option>
                  </select>
                </td>
                <td class="h-16 px-3">$0</td>
                <td class="h-16 px-3">
                  <button
                    type="submit"
                    class="flex w-auto items-center justify-center whitespace-nowrap rounded-lg bg-primary-700 px-3 py-2 text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
                  >
                    Save product
                  </button>
                </td>
              </tr>
            </form>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <button
                  type="button"
                  class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
                >
                  <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
                  </svg>
                  Add new product
                </button>
              </th>
              <td class="h-16 px-3"></td>
              <td class="h-16 px-3"></td>
              <td class="h-16 px-3"></td>
              <td class="h-16 px-3"></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 max-w-xs md:ms-auto">
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
  <div class="col-span-12 rounded-lg bg-gray-100 p-4 dark:bg-gray-800 md:p-8 2xl:col-span-7">
    <div class="mb-4 space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-700 sm:space-y-8 md:p-6">
      <a href="https://flowbite.com" class="flex items-center">
        <img src="/images/logo.svg" class="mr-3 h-6 sm:h-8" alt="Flowbite Logo" />
        <span class="self-center whitespace-nowrap text-xl font-semibold dark:text-white sm:text-2xl">Flowbite</span>
      </a>
      <div class="flex items-center justify-between border-b border-t border-gray-100 py-4 dark:border-gray-700">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Invoice #1846325</h1>
        <time datetime="2025-07-05" class="text-lg text-gray-500 dark:text-gray-400">Date: 05/07/2025</time>
      </div>
      <!-- Payment Details -->
      <div class="items-center justify-between sm:flex">
        <div class="mb-6 sm:mb-0 md:w-64">
          <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Pay to:</h2>
          <address class="text-gray-500 dark:text-gray-400">
            <span class="font-medium text-gray-900 dark:text-white">Flowbite LLC,</span> LOUISVILLE, Selby 3864 Johnson Street, United States of America, <br />
            VAT Code: AA-1234567890
          </address>
        </div>
        <div class="md:w-64">
          <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Invoice to:</h2>
          <address class="text-gray-500 dark:text-gray-400">
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
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Product name</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Price</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Quantity</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Discount</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">Total price</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-600 dark:bg-gray-700">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <div class="text-sm">Flowbite Developer Edition</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">HTML, Figma, JS</div>
              </th>
              <td class="h-16 px-3">$269</td>
              <td class="h-16 px-3">2</td>
              <td class="h-16 px-3">50%</td>
              <td class="h-16 px-3">$269</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-600 dark:bg-gray-700">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <div class="text-sm">Flowbite Designer Edition</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">Figma Design System</div>
              </th>
              <td class="h-16 px-3">$149</td>
              <td class="h-16 px-3">3</td>
              <td class="h-16 px-3">0%</td>
              <td class="h-16 px-3">$447</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-600 dark:bg-gray-700">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium">
                <div class="text-sm">Flowbite Open Source</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">Open source components</div>
              </th>
              <td class="h-16 px-3">$0</td>
              <td class="h-16 px-3">1</td>
              <td class="h-16 px-3">0%</td>
              <td class="h-16 px-3">$0</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-600 dark:bg-gray-700">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <div class="text-sm">2 Years Support</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">Premium support</div>
              </th>
              <td class="h-16 px-3">$199</td>
              <td class="h-16 px-3">1</td>
              <td class="h-16 px-3">0%</td>
              <td class="h-16 px-3">$199</td>
            </tr>
            <tr class="border-b border-gray-100 bg-white dark:border-gray-600 dark:bg-gray-700">
              <th scope="row" class="h-16 space-y-2 whitespace-nowrap px-3 font-medium text-gray-900 dark:text-white">
                <div class="text-sm">Flowbite Developer (Team License)</div>
                <div class="font-normal leading-none text-gray-500 dark:text-gray-400">HTML, Figma, JS</div>
              </th>
              <td class="h-16 px-3">$799</td>
              <td class="h-16 px-3">2</td>
              <td class="h-16 px-3">0%</td>
              <td class="h-16 px-3">$1598</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 max-w-xs md:ms-auto">
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
    <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-700">
      <div class="flex items-center space-x-2 sm:space-x-4">
        <button data-tooltip-target="zoom-in-tooltip" type="button" class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M10 7v6m-3-3h6m4 0a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
          </svg>
        </button>
        <div id="zoom-in-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600">
          Zoom In
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
        <button data-tooltip-target="zoom-out-tooltip" type="button" class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M7 10h6m4 0a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
          </svg>
        </button>
        <div id="zoom-out-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600">
          Zoom Out
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
      <div class="flex items-center space-x-2 sm:space-x-4">
        <button data-tooltip-target="prev-page-tooltip" type="button" class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
          </svg>
        </button>
        <div id="prev-page-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600">
          Prev Page
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
        <span class="text-base font-medium leading-none text-gray-500 dark:text-gray-400 sm:text-lg">1/3</span>
        <button data-tooltip-target="next-page-tooltip" type="button" class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
        </button>
        <div id="next-page-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600">
          Next Page
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
      <div class="flex items-center space-x-2 sm:space-x-4">
        <button data-tooltip-target="full-screen-tooltip" type="button" class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H4m0 0v4m0-4 5 5m7-5h4m0 0v4m0-4-5 5M8 20H4m0 0v-4m0 4 5-5m7 5h4m0 0v-4m0 4-5-5" />
          </svg>
        </button>
        <div id="full-screen-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600">
          Full Screen
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
        <button data-tooltip-target="more-actions-tooltip" type="button" class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600">
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
          </svg>
        </button>
        <div id="more-actions-tooltip" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600">
          More Actions
          <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
