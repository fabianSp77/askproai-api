{{-- Flowbite Component: bank --}}
@props(['title' => 'Tailwind CSS Bank Dashboard - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="mx-auto w-full lg:max-w-7xl">
  <!-- Widget -->
  <div class="mb-4 items-center justify-between rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6 xl:flex">
    <div class="mb-4 grid gap-4 sm:grid-cols-2 md:mb-6 lg:grid-cols-4 lg:gap-8 xl:mb-0">
      <div class="flex items-center">
        <div class="me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
          <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
            <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
          </svg>
        </div>
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Total balance</h3>
          <p class="text-xl font-bold leading-none text-gray-900 dark:text-white">$857,967</p>
        </div>
      </div>
      <div class="flex items-center">
        <div class="me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
          <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Expenses</h3>
          <p class="text-xl font-bold leading-none text-gray-900 dark:text-white">-$7,534</p>
        </div>
      </div>
      <div class="flex items-center">
        <div class="me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
          <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M4 4a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2v14a1 1 0 1 1 0 2H5a1 1 0 1 1 0-2V5a1 1 0 0 1-1-1Zm5 2a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H9Zm5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1Zm-5 4a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1a1 1 0 0 0-1-1H9Zm5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1a1 1 0 0 0-1-1h-1Zm-3 4a2 2 0 0 0-2 2v3h2v-3h2v3h2v-3a2 2 0 0 0-2-2h-2Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Investment funds</h3>
          <p class="text-xl font-bold leading-none text-gray-900 dark:text-white">$65,000</p>
        </div>
      </div>
      <div class="flex items-center">
        <div class="me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
          <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 14a3 3 0 0 1 3-3h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4a3 3 0 0 1-3-3Zm3-1a1 1 0 1 0 0 2h4v-2h-4Z" clip-rule="evenodd" />
            <path
              fill-rule="evenodd"
              d="M12.293 3.293a1 1 0 0 1 1.414 0L16.414 6h-2.828l-1.293-1.293a1 1 0 0 1 0-1.414ZM12.414 6 9.707 3.293a1 1 0 0 0-1.414 0L5.586 6h6.828ZM4.586 7l-.056.055A2 2 0 0 0 3 9v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2h-4a5 5 0 0 1 0-10h4a2 2 0 0 0-1.53-1.945L17.414 7H4.586Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Savings</h3>
          <p class="text-xl font-bold leading-none text-gray-900 dark:text-white">$20,449</p>
        </div>
      </div>
    </div>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="addMoneyModalButton"
        data-modal-target="addMoneyModal"
        data-modal-toggle="addMoneyModal"
        class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
      >
        <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
        </svg>
        Add money
      </button>
      <button
        type="button"
        id="newPaymentModalButton"
        data-modal-target="newPaymentModal"
        data-modal-toggle="newPaymentModal"
        class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
          <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
          <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
        </svg>

        New payment
      </button>
    </div>
  </div>
  <!-- Widget -->
  <div class="mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
      <div class="flex flex-col space-y-4 pb-4 md:flex-row md:items-start md:justify-between md:space-y-0">
        <div>
          <h2 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">Revenue and expenses</h2>
          <p class="text-gray-500 dark:text-gray-400">You can see the movements of your money here</p>
        </div>
        <div class="flex items-center">
          <ul class="flex flex-wrap text-center text-sm font-medium text-gray-900 dark:text-gray-400">
            <li class="mr-2 lg:mr-4">
              <button type="button"
                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                >Today</button>
            </li>
            <li class="mr-2 lg:mr-4">
              <button type="button" class="active inline-block rounded-lg bg-primary-700 px-3 py-2 text-white dark:bg-primary-600">Weekly</button>
            </li>
            <li>
              <button type="button"
                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                aria-current="page"
              >
                Monthly
            </button>
            </li>
          </ul>
        </div>
      </div>
      <div class="flex flex-col py-4 md:flex-row md:items-center md:justify-between  md:py-0">
        <select
          id="list-navigation"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 md:hidden"
        >
          <option selected>Expenses</option>
          <option value="income">Income</option>
          <option value="savings">Savings</option>
          <option value="deposits">Deposits</option>
        </select>

        <ul class="hidden flex-wrap space-x-5 text-center text-sm font-medium text-gray-500 dark:text-gray-400 md:flex">
          <li>
            <button type="button" class="group inline-flex items-center border-b border-transparent pb-3 pt-4 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">Expenses</button>
          </li>
          <li>
            <button type="button" class="active group inline-flex items-center border-b border-primary-700 pb-3 pt-4 text-primary-700 dark:border-primary-500 dark:text-primary-500" aria-current="page">Income</button>
          </li>
          <li>
            <button type="button" class="group inline-flex items-center border-b border-transparent pb-3 pt-4 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">Savings</button>
          </li>
          <li>
            <button type="button" class="group inline-flex items-center rounded-t-lg border-b border-transparent pb-3 pt-4 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white">Deposits</button>
          </li>
        </ul>
      </div>
    </div>
    <!-- Chart -->
    <div id="expenses-chart"></div>

    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-700 sm:pt-6">
      <div>
        <button 
        id="totalExpensesDropdownButton"
        data-dropdown-toggle="totalExpensesDropdown"
        data-dropdown-ignore-click-outside-class="datepicker"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" 
        type="button">
          Last 7 days
          <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
          </svg>
        </button>
        <!-- Dropdown menu -->
        <div class="z-50 my-4 hidden list-none w-80 divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700" id="totalExpensesDropdown">
          <ul class="p-2 text-gray-500 dark:text-gray-400" role="none" aria-labelledby="totalExpensesDropdownButton">
            <li>
              <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Yesterday</button>
            </li>
            <li>
              <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Today</button>
            </li>
            <li>
              <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 7 days</button>
            </li>
            <li>
              <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 30 days</button>
            </li>
            <li>
              <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Last 90 days</button>
            </li>
          </ul>
          <div class="p-5">
            <span class="text-gray-900 dark:text-white mb-2 block">Custom period:</span>
            <div id="date-range-picker-3" date-rangepicker class="flex w-full items-center gap-3">
              <div class="relative w-full">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <input
                  id="datepicker-range-start-3"
                  name="start"
                  type="text"
                  class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Start date"
                />
              </div>
              <div class="relative w-full">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      fill-rule="evenodd"
                      d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <input
                  id="datepicker-range-end-3"
                  name="end"
                  type="text"
                  class=" w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="End date"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="shrink-0">
        <a href="#" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm">
          Users Report
          <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
        </a>
      </div>
    </div>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <p class="leading-tight text-gray-500 dark:text-gray-400">New house savings</p>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">$103,256</h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 ">
          <span class="mr-1.5 flex items-center text-sm font-medium text-green-500 dark:text-green-400 sm:text-base">
            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            7%
          </span>
          vs last year
        </p>
      </div>
      <div class="flex justify-between">
        <span class="sr-only">Savings percent</span>
        <span class="sr-only">75%</span>
      </div>
      <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-2.5 rounded-full bg-primary-700 dark:bg-primary-600" style="width: 75%"></div>
      </div>
    </div>
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <p class="leading-tight text-gray-500 dark:text-gray-400">Wedding savings</p>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">$32,131</h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 ">
          <span class="mr-1.5 flex items-center text-sm font-medium text-red-600 dark:text-red-500 sm:text-base">
            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
            </svg>
            5,4%
          </span>
          vs last year
        </p>
      </div>
      <div class="flex justify-between">
        <span class="sr-only">Savings percent</span>
        <span class="sr-only">45%</span>
      </div>
      <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-2.5 rounded-full bg-primary-700 dark:bg-primary-600" style="width: 45%"></div>
      </div>
    </div>
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <p class="leading-tight text-gray-500 dark:text-gray-400">Vacation savings</p>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">$12,657</h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 ">
          <span class="mr-1.5 flex items-center text-sm font-medium text-green-500 dark:text-green-400 sm:text-base">
            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4" />
            </svg>
            1,8%
          </span>
          vs last year
        </p>
      </div>
      <div class="flex justify-between">
        <span class="sr-only">Savings percent</span>
        <span class="sr-only">24%</span>
      </div>
      <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-2.5 rounded-full bg-primary-700 dark:bg-primary-600" style="width: 24%"></div>
      </div>
    </div>
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <p class="leading-tight text-gray-500 dark:text-gray-400">New car savings</p>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">$50,000</h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 ">
          <span class="mr-1.5 flex items-center text-sm font-medium text-red-600 dark:text-red-500 sm:text-base">
            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4" />
            </svg>
            90%
          </span>
          vs last year
        </p>
      </div>
      <div class="flex justify-between">
        <span class="sr-only">Savings percent</span>
        <span class="sr-only">75%</span>
      </div>
      <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-2.5 rounded-full bg-primary-700 dark:bg-primary-600" style="width: 100%"></div>
      </div>
    </div>
  </div>

  <div class="my-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6">
    <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white md:mb-6">My accounts</h2>
    <ul class="space-y-4">
      <li class="border-b border-gray-200 pb-4 dark:border-gray-700">
        <div class="items-center justify-between md:flex">
          <div class="items-center sm:flex">
            <div class="mb-4 me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 sm:mb-0 md:hidden lg:flex">
              <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 17.345a4.76 4.76 0 0 0 2.558 1.618c2.274.589 4.512-.446 4.999-2.31.487-1.866-1.273-3.9-3.546-4.49-2.273-.59-4.034-2.623-3.547-4.488.486-1.865 2.724-2.899 4.998-2.31.982.236 1.87.793 2.538 1.592m-3.879 12.171V21m0-18v2.2"
                />
              </svg>
            </div>
            <div>
              <h3 class="mb-2 text-gray-500 dark:text-gray-400">Your main account (USD)</h3>
              <p class="text-lg font-bold leading-none text-gray-900 dark:text-white">USD395768402049576GFH598</p>
            </div>
          </div>
          <div class="mt-4 items-center space-y-4 sm:flex sm:space-x-4 sm:space-y-0 md:mt-0">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Transactions
            </button>
            <button
              type="button"
              id="shareIbanModalButton"
              data-modal-target="shareIbanModal"
              data-modal-toggle="shareIbanModal"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Share IBAN
            </button>
            <button
              type="button"
              id="newPaymentModalButton"
              data-modal-target="newPaymentModal"
              data-modal-toggle="newPaymentModal"
              class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 md:w-auto"
            >
              <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
              </svg>

              New payment
            </button>
          </div>
        </div>
      </li>
      <li class="border-b border-gray-200 pb-4 dark:border-gray-700">
        <div class="items-center justify-between md:flex">
          <div class="items-center sm:flex">
            <div class="mb-4 me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 sm:mb-0 md:hidden lg:flex">
              <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 17.345a4.76 4.76 0 0 0 2.558 1.618c2.274.589 4.512-.446 4.999-2.31.487-1.866-1.273-3.9-3.546-4.49-2.273-.59-4.034-2.623-3.547-4.488.486-1.865 2.724-2.899 4.998-2.31.982.236 1.87.793 2.538 1.592m-3.879 12.171V21m0-18v2.2"
                />
              </svg>
            </div>
            <div>
              <h3 class="mb-2 text-gray-500 dark:text-gray-400">My spending account (USD)</h3>
              <p class="text-lg font-bold leading-none text-gray-900 dark:text-white">USD395768402049576GFH595</p>
            </div>
          </div>
          <div class="mt-4 items-center space-y-4 sm:flex sm:space-x-4 sm:space-y-0 md:mt-0">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Transactions
            </button>
            <button
              type="button"
              id="shareIbanModalButton"
              data-modal-target="shareIbanModal"
              data-modal-toggle="shareIbanModal"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Share IBAN
            </button>
            <button
              type="button"
              id="newPaymentModalButton"
              data-modal-target="newPaymentModal"
              data-modal-toggle="newPaymentModal"
              class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 md:w-auto"
            >
              <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
              </svg>

              New payment
            </button>
          </div>
        </div>
      </li>
      <li class="border-b border-gray-200 pb-4 dark:border-gray-700">
        <div class="items-center justify-between md:flex">
          <div class="items-center sm:flex">
            <div class="mb-4 me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 sm:mb-0 md:hidden lg:flex">
              <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 10h9.231M6 14h9.231M18 5.086A5.95 5.95 0 0 0 14.615 4c-3.738 0-6.769 3.582-6.769 8s3.031 8 6.769 8A5.94 5.94 0 0 0 18 18.916"
                />
              </svg>
            </div>
            <div>
              <h3 class="mb-2 text-gray-500 dark:text-gray-400">My EUR account</h3>
              <p class="text-lg font-bold leading-none text-gray-900 dark:text-white">EU395768402049576GFH590</p>
            </div>
          </div>
          <div class="mt-4 items-center space-y-4 sm:flex sm:space-x-4 sm:space-y-0 md:mt-0">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Transactions
            </button>
            <button
              type="button"
              id="shareIbanModalButton"
              data-modal-target="shareIbanModal"
              data-modal-toggle="shareIbanModal"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Share IBAN
            </button>
            <button
              type="button"
              id="newPaymentModalButton"
              data-modal-target="newPaymentModal"
              data-modal-toggle="newPaymentModal"
              class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 md:w-auto"
            >
              <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
              </svg>

              New payment
            </button>
          </div>
        </div>
      </li>
      <li class="pb-4">
        <div class="items-center justify-between md:flex">
          <div class="items-center sm:flex">
            <div class="mb-4 me-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 sm:mb-0 md:hidden lg:flex">
              <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 17.345a4.76 4.76 0 0 0 2.558 1.618c2.274.589 4.512-.446 4.999-2.31.487-1.866-1.273-3.9-3.546-4.49-2.273-.59-4.034-2.623-3.547-4.488.486-1.865 2.724-2.899 4.998-2.31.982.236 1.87.793 2.538 1.592m-3.879 12.171V21m0-18v2.2"
                />
              </svg>
            </div>
            <div>
              <h3 class="mb-2 text-gray-500 dark:text-gray-400">My savings account</h3>
              <p class="text-lg font-bold leading-none text-gray-900 dark:text-white">USD395768402049576GFH597</p>
            </div>
          </div>
          <div class="mt-4 items-center space-y-4 sm:flex sm:space-x-4 sm:space-y-0 md:mt-0">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Transactions
            </button>
            <button
              type="button"
              id="shareIbanModalButton"
              data-modal-target="shareIbanModal"
              data-modal-toggle="shareIbanModal"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 md:w-auto"
            >
              Share IBAN
            </button>
            <button
              type="button"
              id="newPaymentModalButton"
              data-modal-target="newPaymentModal"
              data-modal-toggle="newPaymentModal"
              class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 md:w-auto"
            >
              <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M7 6c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M2 11c0-1.1.9-2 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
              </svg>

              New payment
            </button>
          </div>
        </div>
      </li>
    </ul>
    <div class="border-t border-gray-200 pt-4 dark:border-gray-700 md:pt-6">
      <button
        type="button"
        class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
        </svg>
        Add new account
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-y-4 lg:mb-4 lg:grid-cols-2 lg:gap-x-4 xl:grid-cols-3">
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:col-span-2">
      <!-- Card Title -->
      <h2 class="mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">Transactions</h2>
      <p class="mb-4 text-gray-500 dark:text-gray-400 md:mb-6">View your transactions, gaining a comprehensive overview of all financial activities within your account.</p>
      <div class="mb-4 overflow-x-auto md:mb-6 relative">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="min-w-64 px-4 py-3 font-semibold">Product</th>
              <th scope="col" class="px-4 py-3 font-semibold">
                <div class="flex items-center">
                  Price
                  <svg class="ms-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path
                      clip-rule="evenodd"
                      fill-rule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    />
                  </svg>
                </div>
              </th>
              <th scope="col" class="min-w-28 px-4 py-3 font-semibold">Due date</th>
              <th scope="col" class="min-w-24 px-4 py-3 font-semibold">
                <div class="flex items-center">
                  Status
                  <svg class="ms-1 inline-block h-3 w-3" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path
                      clip-rule="evenodd"
                      fill-rule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    />
                  </svg>
                </div>
              </th>
              <th scope="col" class="px-4 py-3 font-semibold">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="w-4 px-4 py-3">
                <div class="flex items-center space-x-2">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-900 dark:text-white" aria-hidden="true" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path
                        d="M21.1227 18.1534C20.7996 18.9 20.417 19.5872 19.9738 20.2191C19.3697 21.0804 18.8751 21.6766 18.4938 22.0077C17.9029 22.5512 17.2698 22.8295 16.5917 22.8453C16.105 22.8453 15.518 22.7068 14.8348 22.4259C14.1492 22.1462 13.5192 22.0077 12.9432 22.0077C12.3391 22.0077 11.6911 22.1462 10.9981 22.4259C10.304 22.7068 9.74486 22.8532 9.31735 22.8678C8.66718 22.8955 8.01913 22.6092 7.37226 22.0077C6.95939 21.6476 6.44297 21.0303 5.82433 20.1557C5.16057 19.2218 4.61488 18.1389 4.18737 16.9042C3.72952 15.5707 3.5 14.2793 3.5 13.0291C3.5 11.597 3.80945 10.3618 4.42928 9.32671C4.91642 8.4953 5.56447 7.83946 6.37557 7.35799C7.18666 6.87653 8.06305 6.63119 9.00684 6.61549C9.52326 6.61549 10.2005 6.77523 11.042 7.08917C11.8812 7.40416 12.4201 7.5639 12.6563 7.5639C12.8329 7.5639 13.4315 7.37712 14.4463 7.00475C15.4059 6.65941 16.2158 6.51643 16.8793 6.57275C18.6772 6.71785 20.0279 7.42659 20.9262 8.70345C19.3183 9.67771 18.5229 11.0423 18.5387 12.7928C18.5532 14.1564 19.0479 15.291 20.02 16.1919C20.4606 16.6101 20.9526 16.9333 21.5 17.1628C21.3813 17.5071 21.256 17.8368 21.1227 18.1534ZM16.9993 1.92751C16.9993 2.99624 16.6089 3.9941 15.8306 4.91771C14.8915 6.01571 13.7555 6.65018 12.5236 6.55006C12.5079 6.42185 12.4988 6.28691 12.4988 6.14511C12.4988 5.11914 12.9454 4.02114 13.7386 3.12338C14.1346 2.66883 14.6382 2.29088 15.2489 1.98938C15.8583 1.69238 16.4348 1.52813 16.9769 1.5C16.9927 1.64287 16.9993 1.78575 16.9993 1.9275V1.92751Z"
                        fill="currentColor"
                      />
                    </svg>
                  </div>
                  <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">Iphone 15 PRO Max</h5>
                    <span>Apple Inc.</span>
                  </div>
                </div>
              </td>
              <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$899</th>
              <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">30 Mar 2025</td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="rounded-sm bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
              </td>
              <td class="px-4 py-3">
                <button
                  id="transaction-1-dropdown-button"
                  type="button"
                  data-dropdown-toggle="transaction-1-dropdown"
                  class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="transaction-1-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="transaction-1-dropdown-button">
                    <li>
                      <a href="/e-commerce/transaction/" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Details
                      </a>
                    </li>
                    <li>
                      <button
                        type="button"
                        id="deleteTransactionButton"
                        data-modal-target="deleteTransactionModal"
                        data-modal-toggle="deleteTransactionModal"
                        class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                      >
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Delete
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="w-4 px-4 py-3">
                <div class="flex items-center space-x-2">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path
                        d="M20.1239 4.89005C18.7217 4.24665 17.2181 3.77263 15.6459 3.50114C15.6173 3.4959 15.5887 3.50899 15.5739 3.53518C15.3805 3.87913 15.1663 4.32784 15.0163 4.68052C13.3254 4.42736 11.6431 4.42736 9.98679 4.68052C9.83676 4.32 9.61478 3.87913 9.42053 3.53518C9.40578 3.50987 9.37718 3.49677 9.34855 3.50114C7.77725 3.77176 6.2736 4.24579 4.87052 4.89005C4.85838 4.89528 4.84797 4.90402 4.84106 4.91536C1.98894 9.17636 1.20763 13.3326 1.59092 17.4374C1.59265 17.4574 1.60392 17.4766 1.61953 17.4889C3.50127 18.8708 5.32406 19.7097 7.11301 20.2658C7.14164 20.2745 7.17197 20.264 7.19019 20.2405C7.61337 19.6626 7.99059 19.0532 8.31402 18.4125C8.33311 18.3749 8.31489 18.3304 8.27588 18.3156C7.67754 18.0886 7.1078 17.8119 6.55975 17.4976C6.5164 17.4723 6.51293 17.4103 6.55281 17.3806C6.66814 17.2942 6.7835 17.2043 6.89363 17.1135C6.91355 17.0969 6.94131 17.0934 6.96474 17.1039C10.5652 18.7477 14.4631 18.7477 18.021 17.1039C18.0445 17.0925 18.0722 17.096 18.093 17.1126C18.2032 17.2034 18.3185 17.2942 18.4347 17.3806C18.4746 17.4103 18.472 17.4723 18.4286 17.4976C17.8806 17.818 17.3108 18.0886 16.7116 18.3147C16.6726 18.3295 16.6553 18.3749 16.6744 18.4125C17.0047 19.0523 17.382 19.6617 17.7973 20.2396C17.8147 20.264 17.8459 20.2745 17.8745 20.2658C19.6721 19.7097 21.4949 18.8708 23.3766 17.4889C23.3931 17.4766 23.4035 17.4583 23.4053 17.4382C23.864 12.6927 22.6369 8.57052 20.1525 4.91623C20.1465 4.90402 20.1361 4.89528 20.1239 4.89005ZM8.85169 14.938C7.76771 14.938 6.87454 13.9428 6.87454 12.7207C6.87454 11.4985 7.75039 10.5033 8.85169 10.5033C9.96163 10.5033 10.8462 11.5072 10.8288 12.7207C10.8288 13.9428 9.95296 14.938 8.85169 14.938ZM16.1619 14.938C15.0779 14.938 14.1847 13.9428 14.1847 12.7207C14.1847 11.4985 15.0606 10.5033 16.1619 10.5033C17.2718 10.5033 18.1563 11.5072 18.139 12.7207C18.139 13.9428 17.2718 14.938 16.1619 14.938Z"
                        fill="#5865F2"
                      />
                    </svg>
                  </div>
                  <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">Nitro Basic for 12 months</h5>
                    <span>Discord Inc.</span>
                  </div>
                </div>
              </td>
              <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$99</th>
              <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">27 Mar 2025</td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
              </td>
              <td class="px-4 py-3">
                <button
                  id="transaction-2-dropdown-button"
                  type="button"
                  data-dropdown-toggle="transaction-2-dropdown"
                  class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="transaction-2-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="transaction-2-dropdown-button">
                    <li>
                      <a href="/e-commerce/transaction/#" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Details
                      </a>
                    </li>
                    <li>
                      <button
                        type="button"
                        id="deleteTransactionButton"
                        data-modal-target="deleteTransactionModal"
                        data-modal-toggle="deleteTransactionModal"
                        class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                      >
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Delete
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="w-4 px-4 py-3">
                <div class="flex items-center space-x-2">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8.75006 23.5C10.8201 23.5 12.5001 21.82 12.5001 19.75V15.9999H8.75006C6.68004 15.9999 5 17.68 5 19.75C5 21.82 6.68004 23.5 8.75006 23.5Z" fill="#0ACF83" />
                      <path d="M5 12.25C5 10.18 6.68004 8.49996 8.75006 8.49996H12.5001V15.9999H8.75006C6.68004 16 5 14.32 5 12.25Z" fill="#A259FF" />
                      <path d="M5 4.75003C5 2.68003 6.68004 1 8.75006 1H12.5001V8.49996H8.75006C6.68004 8.49996 5 6.82003 5 4.75003Z" fill="#F24E1E" />
                      <path d="M12.5001 1H16.2499C18.32 1 20 2.68003 20 4.75003C20 6.82003 18.32 8.49997 16.2499 8.49997L12.5001 8.49996V1Z" fill="#FF7262" />
                      <path d="M20 12.25C20 14.32 18.32 16 16.2499 16C14.1799 16 12.4999 14.32 12.4999 12.25C12.4999 10.18 14.1799 8.49997 16.2499 8.49997C18.32 8.49997 20 10.18 20 12.25Z" fill="#1ABCFE" />
                    </svg>
                  </div>
                  <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">Figma PRO yearly plan</h5>
                    <span>Figma Inc.</span>
                  </div>
                </div>
              </td>
              <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$199</th>
              <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">10 Mar 2025</td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
              </td>
              <td class="px-4 py-3">
                <button
                  id="transaction-3-dropdown-button"
                  type="button"
                  data-dropdown-toggle="transaction-3-dropdown"
                  class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="transaction-3-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="transaction-3-dropdown-button">
                    <li>
                      <a href="/e-commerce/transaction/" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Details
                      </a>
                    </li>
                    <li>
                      <button
                        type="button"
                        id="deleteTransactionButton"
                        data-modal-target="deleteTransactionModal"
                        data-modal-toggle="deleteTransactionModal"
                        class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                      >
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Delete
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="w-4 px-4 py-3">
                <div class="flex items-center space-x-2">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path
                        d="M12.5 1C6.425 1 1.5 5.9245 1.5 12C1.5 18.0755 6.425 23 12.5 23C18.575 23 23.5 18.0755 23.5 12C23.5 5.9245 18.575 1 12.5 1ZM17.8667 16.8572C17.69 17.17 17.365 17.346 17.03 17.346C16.8733 17.346 16.7117 17.3068 16.5633 17.2243C15.19 16.4543 13.6933 15.9827 12.1117 15.8225C10.5267 15.6637 8.96167 15.8245 7.45833 16.3045C6.95667 16.4647 6.41667 16.1875 6.25667 15.685C6.09501 15.1817 6.37333 14.6435 6.87667 14.4825C8.63 13.9223 10.4583 13.7318 12.305 13.9195C14.1483 14.1072 15.895 14.6572 17.4983 15.5558C17.96 15.8128 18.125 16.3958 17.8667 16.8572ZM19.1833 13.516C19.0133 13.8432 18.68 14.0308 18.335 14.0308C18.185 14.0308 18.035 13.9965 17.8933 13.9223C16.205 13.043 14.3817 12.4985 12.47 12.3045C10.5433 12.1073 8.635 12.2792 6.795 12.8098C6.28667 12.9542 5.75667 12.6635 5.61167 12.1553C5.46501 11.648 5.75833 11.118 6.26499 10.9715C8.34166 10.3727 10.495 10.1808 12.6633 10.4008C14.8167 10.6202 16.8733 11.2335 18.7783 12.2262C19.245 12.4703 19.4283 13.0477 19.1833 13.516ZM19.66 10.7363C19.5167 10.7363 19.3717 10.704 19.2333 10.636C17.23 9.637 15.075 9.01483 12.8267 8.78667C10.575 8.55633 8.335 8.73433 6.16667 9.31333C5.65667 9.44867 5.13333 9.14617 4.99666 8.63533C4.86 8.12517 5.16334 7.60067 5.67501 7.4645C8.06668 6.82583 10.54 6.63133 13.0217 6.88367C15.5 7.1345 17.8767 7.82133 20.0867 8.9235C20.56 9.15917 20.7517 9.734 20.5167 10.2063C20.35 10.5425 20.0117 10.7363 19.66 10.7363Z"
                        fill="#1ED760"
                      />
                    </svg>
                  </div>
                  <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">Spotify Platinum</h5>
                    <span>Spotify Inc.</span>
                  </div>
                </div>
              </td>
              <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$29</th>
              <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">05 Mar 2025</td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="rounded-sm bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Failed</span>
              </td>
              <td class="px-4 py-3">
                <button
                  id="transaction-4-dropdown-button"
                  type="button"
                  data-dropdown-toggle="transaction-4-dropdown"
                  class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="transaction-4-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="transaction-4-dropdown-button">
                    <li>
                      <a href="/e-commerce/transaction/" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Details
                      </a>
                    </li>
                    <li>
                      <button
                        type="button"
                        id="deleteTransactionButton"
                        data-modal-target="deleteTransactionModal"
                        data-modal-toggle="deleteTransactionModal"
                        class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                      >
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Delete
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            <tr class="border-b hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 border-gray-200">
              <td class="w-4 px-4 py-3">
                <div class="flex items-center space-x-2">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-900 dark:text-white" aria-hidden="true" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path
                        d="M21.1227 18.1534C20.7996 18.9 20.417 19.5872 19.9738 20.2191C19.3697 21.0804 18.8751 21.6766 18.4938 22.0077C17.9029 22.5512 17.2698 22.8295 16.5917 22.8453C16.105 22.8453 15.518 22.7068 14.8348 22.4259C14.1492 22.1462 13.5192 22.0077 12.9432 22.0077C12.3391 22.0077 11.6911 22.1462 10.9981 22.4259C10.304 22.7068 9.74486 22.8532 9.31735 22.8678C8.66718 22.8955 8.01913 22.6092 7.37226 22.0077C6.95939 21.6476 6.44297 21.0303 5.82433 20.1557C5.16057 19.2218 4.61488 18.1389 4.18737 16.9042C3.72952 15.5707 3.5 14.2793 3.5 13.0291C3.5 11.597 3.80945 10.3618 4.42928 9.32671C4.91642 8.4953 5.56447 7.83946 6.37557 7.35799C7.18666 6.87653 8.06305 6.63119 9.00684 6.61549C9.52326 6.61549 10.2005 6.77523 11.042 7.08917C11.8812 7.40416 12.4201 7.5639 12.6563 7.5639C12.8329 7.5639 13.4315 7.37712 14.4463 7.00475C15.4059 6.65941 16.2158 6.51643 16.8793 6.57275C18.6772 6.71785 20.0279 7.42659 20.9262 8.70345C19.3183 9.67771 18.5229 11.0423 18.5387 12.7928C18.5532 14.1564 19.0479 15.291 20.02 16.1919C20.4606 16.6101 20.9526 16.9333 21.5 17.1628C21.3813 17.5071 21.256 17.8368 21.1227 18.1534ZM16.9993 1.92751C16.9993 2.99624 16.6089 3.9941 15.8306 4.91771C14.8915 6.01571 13.7555 6.65018 12.5236 6.55006C12.5079 6.42185 12.4988 6.28691 12.4988 6.14511C12.4988 5.11914 12.9454 4.02114 13.7386 3.12338C14.1346 2.66883 14.6382 2.29088 15.2489 1.98938C15.8583 1.69238 16.4348 1.52813 16.9769 1.5C16.9927 1.64287 16.9993 1.78575 16.9993 1.9275V1.92751Z"
                        fill="currentColor"
                      />
                    </svg>
                  </div>
                  <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">iMac 27 Inch</h5>
                    <span>Apple Inc.</span>
                  </div>
                </div>
              </td>
              <th scope="row" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">$1899</th>
              <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">01 Feb 2025</td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Completed</span>
              </td>
              <td class="px-4 py-3">
                <button
                  id="transaction-6-dropdown-button"
                  type="button"
                  data-dropdown-toggle="transaction-6-dropdown"
                  class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                  </svg>
                </button>
                <div id="transaction-6-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                  <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="transaction-6-dropdown-button">
                    <li>
                      <a href="/e-commerce/transaction/" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Details
                      </a>
                    </li>
                    <li>
                      <button
                        type="button"
                        id="deleteTransactionButton"
                        data-modal-target="deleteTransactionModal"
                        data-modal-toggle="deleteTransactionModal"
                        class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                      >
                        <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            fill-rule="evenodd"
                            d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Delete
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <button
        type="button"
        class="flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
      >
        View all transactions
        <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
        </svg>
      </button>
    </div>
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <h2 class="mb-4 text-xl font-bold leading-none text-gray-900 dark:text-white md:mb-6">Money transfer</h2>
      <form>
        <label for="card-number-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Card number:</label>
        <div class="mb-2 flex w-full -space-x-1">
          <button
            id="payment-method-button"
            data-dropdown-toggle="dropdown-payment-method"
            class="z-10 inline-flex shrink-0 items-center rounded-s-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700"
            type="button"
          >
            Credit card
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <div id="dropdown-payment-method" class="z-50 hidden w-32 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="states-button">
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">PayPal</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Bitcoin</button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">Stripe</button>
              </li>
            </ul>
          </div>
          <div class="relative w-full">
            <input
              type="number"
              id="card-number-input"
              class="w-full rounded-e-lg border border-gray-300 bg-gray-50 p-2.5 pe-10 ps-3 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="4242 4242 4242 4242"
              pattern="^4[0-9]{12}(?:[0-9]{3})?$"
              required
            />
            <div class="pointer-events-none absolute inset-y-0 end-0 top-0 flex items-center pe-3.5">
              <svg fill="none" class="h-6 text-[#1434CB] dark:text-white" viewBox="0 0 36 21">
                <path
                  fill="currentColor"
                  d="M23.315 4.773c-2.542 0-4.813 1.3-4.813 3.705 0 2.756 4.028 2.947 4.028 4.332 0 .583-.676 1.105-1.832 1.105-1.64 0-2.866-.73-2.866-.73l-.524 2.426s1.412.616 3.286.616c2.78 0 4.966-1.365 4.966-3.81 0-2.913-4.045-3.097-4.045-4.383 0-.457.555-.957 1.708-.957 1.3 0 2.36.53 2.36.53l.514-2.343s-1.154-.491-2.782-.491zM.062 4.95L0 5.303s1.07.193 2.032.579c1.24.442 1.329.7 1.537 1.499l2.276 8.664h3.05l4.7-11.095h-3.043l-3.02 7.543L6.3 6.1c-.113-.732-.686-1.15-1.386-1.15H.062zm14.757 0l-2.387 11.095h2.902l2.38-11.096h-2.895zm16.187 0c-.7 0-1.07.37-1.342 1.016L25.41 16.045h3.044l.589-1.68h3.708l.358 1.68h2.685L33.453 4.95h-2.447zm.396 2.997l.902 4.164h-2.417l1.515-4.164z"
                />
              </svg>
            </div>
          </div>
        </div>
        <button
          type="button"
          id="addCardButton"
          data-modal-target="addCardModal"
          data-modal-toggle="addCardModal"
          class="mb-4 inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
          </svg>
          Add new card
        </button>
        <label for="states" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Country & state</label>
        <div class="mb-4 flex -space-x-1">
          <button
            id="states-button"
            data-dropdown-toggle="dropdown-states"
            class="z-10 inline-flex shrink-0 items-center rounded-s-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700"
            type="button"
          >
            <svg aria-hidden="true" class="me-2 h-3" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="0.5" width="14" height="12" rx="2" fill="white" />
              <mask id="mask0_12694_49953" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="15" height="12"><rect x="0.5" width="14" height="12" rx="2" fill="white" /></mask>
              <g mask="url(#mask0_12694_49953)">
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M14.5 0H0.5V0.8H14.5V0ZM14.5 1.6H0.5V2.4H14.5V1.6ZM0.5 3.2H14.5V4H0.5V3.2ZM14.5 4.8H0.5V5.6H14.5V4.8ZM0.5 6.4H14.5V7.2H0.5V6.4ZM14.5 8H0.5V8.8H14.5V8ZM0.5 9.6H14.5V10.4H0.5V9.6ZM14.5 11.2H0.5V12H14.5V11.2Z"
                  fill="#D02F44"
                />
                <rect x="0.5" width="6" height="5.6" fill="#46467F" />
                <g filter="url(#filter0_d_12694_49953)">
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M1.83317 1.20005C1.83317 1.42096 1.68393 1.60005 1.49984 1.60005C1.31574 1.60005 1.1665 1.42096 1.1665 1.20005C1.1665 0.979135 1.31574 0.800049 1.49984 0.800049C1.68393 0.800049 1.83317 0.979135 1.83317 1.20005ZM3.1665 1.20005C3.1665 1.42096 3.01727 1.60005 2.83317 1.60005C2.64908 1.60005 2.49984 1.42096 2.49984 1.20005C2.49984 0.979135 2.64908 0.800049 2.83317 0.800049C3.01727 0.800049 3.1665 0.979135 3.1665 1.20005ZM4.1665 1.60005C4.3506 1.60005 4.49984 1.42096 4.49984 1.20005C4.49984 0.979135 4.3506 0.800049 4.1665 0.800049C3.98241 0.800049 3.83317 0.979135 3.83317 1.20005C3.83317 1.42096 3.98241 1.60005 4.1665 1.60005ZM5.83317 1.20005C5.83317 1.42096 5.68393 1.60005 5.49984 1.60005C5.31574 1.60005 5.1665 1.42096 5.1665 1.20005C5.1665 0.979135 5.31574 0.800049 5.49984 0.800049C5.68393 0.800049 5.83317 0.979135 5.83317 1.20005ZM2.1665 2.40005C2.3506 2.40005 2.49984 2.22096 2.49984 2.00005C2.49984 1.77913 2.3506 1.60005 2.1665 1.60005C1.98241 1.60005 1.83317 1.77913 1.83317 2.00005C1.83317 2.22096 1.98241 2.40005 2.1665 2.40005ZM3.83317 2.00005C3.83317 2.22096 3.68393 2.40005 3.49984 2.40005C3.31574 2.40005 3.1665 2.22096 3.1665 2.00005C3.1665 1.77913 3.31574 1.60005 3.49984 1.60005C3.68393 1.60005 3.83317 1.77913 3.83317 2.00005ZM4.83317 2.40005C5.01726 2.40005 5.1665 2.22096 5.1665 2.00005C5.1665 1.77913 5.01726 1.60005 4.83317 1.60005C4.64908 1.60005 4.49984 1.77913 4.49984 2.00005C4.49984 2.22096 4.64908 2.40005 4.83317 2.40005ZM5.83317 2.80005C5.83317 3.02096 5.68393 3.20005 5.49984 3.20005C5.31574 3.20005 5.1665 3.02096 5.1665 2.80005C5.1665 2.57914 5.31574 2.40005 5.49984 2.40005C5.68393 2.40005 5.83317 2.57914 5.83317 2.80005ZM4.1665 3.20005C4.3506 3.20005 4.49984 3.02096 4.49984 2.80005C4.49984 2.57914 4.3506 2.40005 4.1665 2.40005C3.98241 2.40005 3.83317 2.57914 3.83317 2.80005C3.83317 3.02096 3.98241 3.20005 4.1665 3.20005ZM3.1665 2.80005C3.1665 3.02096 3.01727 3.20005 2.83317 3.20005C2.64908 3.20005 2.49984 3.02096 2.49984 2.80005C2.49984 2.57914 2.64908 2.40005 2.83317 2.40005C3.01727 2.40005 3.1665 2.57914 3.1665 2.80005ZM1.49984 3.20005C1.68393 3.20005 1.83317 3.02096 1.83317 2.80005C1.83317 2.57914 1.68393 2.40005 1.49984 2.40005C1.31574 2.40005 1.1665 2.57914 1.1665 2.80005C1.1665 3.02096 1.31574 3.20005 1.49984 3.20005ZM2.49984 3.60005C2.49984 3.82096 2.3506 4.00005 2.1665 4.00005C1.98241 4.00005 1.83317 3.82096 1.83317 3.60005C1.83317 3.37913 1.98241 3.20005 2.1665 3.20005C2.3506 3.20005 2.49984 3.37913 2.49984 3.60005ZM3.49984 4.00005C3.68393 4.00005 3.83317 3.82096 3.83317 3.60005C3.83317 3.37913 3.68393 3.20005 3.49984 3.20005C3.31574 3.20005 3.1665 3.37913 3.1665 3.60005C3.1665 3.82096 3.31574 4.00005 3.49984 4.00005ZM5.1665 3.60005C5.1665 3.82096 5.01726 4.00005 4.83317 4.00005C4.64908 4.00005 4.49984 3.82096 4.49984 3.60005C4.49984 3.37913 4.64908 3.20005 4.83317 3.20005C5.01726 3.20005 5.1665 3.37913 5.1665 3.60005ZM5.49984 4.80005C5.68393 4.80005 5.83317 4.62096 5.83317 4.40005C5.83317 4.17913 5.68393 4.00005 5.49984 4.00005C5.31574 4.00005 5.1665 4.17913 5.1665 4.40005C5.1665 4.62096 5.31574 4.80005 5.49984 4.80005ZM4.49984 4.40005C4.49984 4.62096 4.3506 4.80005 4.1665 4.80005C3.98241 4.80005 3.83317 4.62096 3.83317 4.40005C3.83317 4.17913 3.98241 4.00005 4.1665 4.00005C4.3506 4.00005 4.49984 4.17913 4.49984 4.40005ZM2.83317 4.80005C3.01727 4.80005 3.1665 4.62096 3.1665 4.40005C3.1665 4.17913 3.01727 4.00005 2.83317 4.00005C2.64908 4.00005 2.49984 4.17913 2.49984 4.40005C2.49984 4.62096 2.64908 4.80005 2.83317 4.80005ZM1.83317 4.40005C1.83317 4.62096 1.68393 4.80005 1.49984 4.80005C1.31574 4.80005 1.1665 4.62096 1.1665 4.40005C1.1665 4.17913 1.31574 4.00005 1.49984 4.00005C1.68393 4.00005 1.83317 4.17913 1.83317 4.40005Z"
                    fill="url(#paint0_linear_12694_49953)"
                  />
                </g>
              </g>
              <defs>
                <filter id="filter0_d_12694_49953" x="1.1665" y="0.800049" width="4.6665" height="5" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                  <feFlood flood-opacity="0" result="BackgroundImageFix" />
                  <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha" />
                  <feOffset dy="1" />
                  <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0" />
                  <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_12694_49953" />
                  <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_12694_49953" result="shape" />
                </filter>
                <linearGradient id="paint0_linear_12694_49953" x1="1.1665" y1="0.800049" x2="1.1665" y2="4.80005" gradientUnits="userSpaceOnUse">
                  <stop stop-color="white" />
                  <stop offset="1" stop-color="#F0F0F0" />
                </linearGradient>
              </defs>
            </svg>
            USA
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <div id="dropdown-states" class="z-50 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="states-button">
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <div class="inline-flex items-center">
                    <svg aria-hidden="true" class="me-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-us" viewBox="0 0 512 512">
                      <g fill-rule="evenodd">
                        <g stroke-width="1pt">
                          <path fill="#bd3d44" d="M0 0h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
                          <path fill="#fff" d="M0 10h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
                        </g>
                        <path fill="#192f5d" d="M0 0h98.8v70H0z" transform="scale(3.9385)" />
                        <path
                          fill="#fff"
                          d="M8.2 3l1 2.8H12L9.7 7.5l.9 2.7-2.4-1.7L6 10.2l.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7L74 8.5l-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 7.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 24.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 21.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 38.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 35.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 52.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 49.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 66.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 63.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9z"
                          transform="scale(3.9385)"
                        />
                      </g>
                    </svg>
                    United States
                  </div>
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <div class="inline-flex items-center">
                    <svg aria-hidden="true" class="me-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-de" viewBox="0 0 512 512">
                      <path fill="#ffce00" d="M0 341.3h512V512H0z" />
                      <path d="M0 0h512v170.7H0z" />
                      <path fill="#d00" d="M0 170.7h512v170.6H0z" />
                    </svg>
                    Germany
                  </div>
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <div class="inline-flex items-center">
                    <svg aria-hidden="true" class="me-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-it" viewBox="0 0 512 512">
                      <g fill-rule="evenodd" stroke-width="1pt">
                        <path fill="#fff" d="M0 0h512v512H0z" />
                        <path fill="#009246" d="M0 0h170.7v512H0z" />
                        <path fill="#ce2b37" d="M341.3 0H512v512H341.3z" />
                      </g>
                    </svg>
                    Italy
                  </div>
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                  <div class="inline-flex items-center">
                    <svg aria-hidden="true" class="me-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="flag-icon-css-cn" viewBox="0 0 512 512">
                      <defs><path id="a" fill="#ffde00" d="M1-.3L-.7.8 0-1 .6.8-1-.3z" /></defs>
                      <path fill="#de2910" d="M0 0h512v512H0z" />
                      <use width="30" height="20" transform="matrix(76.8 0 0 76.8 128 128)" xlink:href="#a" />
                      <use width="30" height="20" transform="rotate(-121 142.6 -47) scale(25.5827)" xlink:href="#a" />
                      <use width="30" height="20" transform="rotate(-98.1 198 -82) scale(25.6)" xlink:href="#a" />
                      <use width="30" height="20" transform="rotate(-74 272.4 -114) scale(25.6137)" xlink:href="#a" />
                      <use width="30" height="20" transform="matrix(16 -19.968 19.968 16 256 230.4)" xlink:href="#a" />
                    </svg>
                    China
                  </div>
                </button>
              </li>
            </ul>
          </div>
          <select
            id="states"
            class="block w-full rounded-e-lg border border-s-2 border-gray-300 border-s-gray-100 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:border-s-gray-700 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          >
            <option selected>Choose a state</option>
            <option value="CA">California</option>
            <option value="TX">Texas</option>
            <option value="WH">Washinghton</option>
            <option value="FL">Florida</option>
            <option value="VG">Virginia</option>
            <option value="GE">Georgia</option>
            <option value="MI">Michigan</option>
          </select>
        </div>
        <label for="currency-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Amount to send</label>
        <div class="flex -space-x-1">
          <button
            id="dropdown-currency-button"
            data-dropdown-toggle="dropdown-currency"
            class="z-10 inline-flex w-[106px] shrink-0 items-center justify-center rounded-s-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
            type="button"
          >
            USD
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <div id="dropdown-currency" class="z-10 hidden w-32 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="dropdown-currency-button">
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                  <div class="inline-flex items-center">
                    <svg fill="none" aria-hidden="true" class="me-2 h-4 w-4" viewBox="0 0 20 15">
                      <rect width="19.6" height="14" y=".5" fill="#fff" rx="2" />
                      <mask id="a" style="mask-type:luminance" width="20" height="15" x="0" y="0" maskUnits="userSpaceOnUse"><rect width="19.6" height="14" y=".5" fill="#fff" rx="2" /></mask>
                      <g mask="url(#a)">
                        <path
                          fill="#D02F44"
                          fill-rule="evenodd"
                          d="M19.6.5H0v.933h19.6V.5zm0 1.867H0V3.3h19.6v-.933zM0 4.233h19.6v.934H0v-.934zM19.6 6.1H0v.933h19.6V6.1zM0 7.967h19.6V8.9H0v-.933zm19.6 1.866H0v.934h19.6v-.934zM0 11.7h19.6v.933H0V11.7zm19.6 1.867H0v.933h19.6v-.933z"
                          clip-rule="evenodd"
                        />
                        <path fill="#46467F" d="M0 .5h8.4v6.533H0z" />
                        <g filter="url(#filter0_d_343_121520)">
                          <path
                            fill="url(#paint0_linear_343_121520)"
                            fill-rule="evenodd"
                            d="M1.867 1.9a.467.467 0 11-.934 0 .467.467 0 01.934 0zm1.866 0a.467.467 0 11-.933 0 .467.467 0 01.933 0zm1.4.467a.467.467 0 100-.934.467.467 0 000 .934zM7.467 1.9a.467.467 0 11-.934 0 .467.467 0 01.934 0zM2.333 3.3a.467.467 0 100-.933.467.467 0 000 .933zm2.334-.467a.467.467 0 11-.934 0 .467.467 0 01.934 0zm1.4.467a.467.467 0 100-.933.467.467 0 000 .933zm1.4.467a.467.467 0 11-.934 0 .467.467 0 01.934 0zm-2.334.466a.467.467 0 100-.933.467.467 0 000 .933zm-1.4-.466a.467.467 0 11-.933 0 .467.467 0 01.933 0zM1.4 4.233a.467.467 0 100-.933.467.467 0 000 .933zm1.4.467a.467.467 0 11-.933 0 .467.467 0 01.933 0zm1.4.467a.467.467 0 100-.934.467.467 0 000 .934zM6.533 4.7a.467.467 0 11-.933 0 .467.467 0 01.933 0zM7 6.1a.467.467 0 100-.933.467.467 0 000 .933zm-1.4-.467a.467.467 0 11-.933 0 .467.467 0 01.933 0zM3.267 6.1a.467.467 0 100-.933.467.467 0 000 .933zm-1.4-.467a.467.467 0 11-.934 0 .467.467 0 01.934 0z"
                            clip-rule="evenodd"
                          />
                        </g>
                      </g>
                      <defs>
                        <linearGradient id="paint0_linear_343_121520" x1=".933" x2=".933" y1="1.433" y2="6.1" gradientUnits="userSpaceOnUse">
                          <stop stop-color="#fff" />
                          <stop offset="1" stop-color="#F0F0F0" />
                        </linearGradient>
                        <filter id="filter0_d_343_121520" width="6.533" height="5.667" x=".933" y="1.433" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse">
                          <feFlood flood-opacity="0" result="BackgroundImageFix" />
                          <feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" />
                          <feOffset dy="1" />
                          <feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0" />
                          <feBlend in2="BackgroundImageFix" result="effect1_dropShadow_343_121520" />
                          <feBlend in="SourceGraphic" in2="effect1_dropShadow_343_121520" result="shape" />
                        </filter>
                      </defs>
                    </svg>
                    USD
                  </div>
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                  <div class="inline-flex items-center">
                    <svg fill="none" aria-hidden="true" class="me-2 h-4 w-4" viewBox="0 0 20 15">
                      <rect width="19.6" height="14" y=".5" fill="#fff" rx="2" />
                      <mask id="a" style="mask-type:luminance" width="20" height="15" x="0" y="0" maskUnits="userSpaceOnUse"><rect width="19.6" height="14" y=".5" fill="#fff" rx="2" /></mask>
                      <g mask="url(#a)">
                        <path fill="#043CAE" d="M0 .5h19.6v14H0z" />
                        <path
                          fill="#FFD429"
                          fill-rule="evenodd"
                          d="M9.14 3.493L9.8 3.3l.66.193-.193-.66.193-.66-.66.194-.66-.194.193.66-.193.66zm0 9.334l.66-.194.66.194-.193-.66.193-.66-.66.193-.66-.193.193.66-.193.66zm5.327-4.86l-.66.193L14 7.5l-.193-.66.66.193.66-.193-.194.66.194.66-.66-.193zm-9.994.193l.66-.193.66.193L5.6 7.5l.193-.66-.66.193-.66-.193.194.66-.194.66zm9.369-2.527l-.66.194.193-.66-.194-.66.66.193.66-.193-.193.66.193.66-.66-.194zm-8.743 4.86l.66-.193.66.193-.194-.66.194-.66-.66.194-.66-.194.193.66-.193.66zm7.034-6.568l-.66.193.194-.66-.194-.66.66.194.66-.193-.193.66.193.66-.66-.194zm-5.326 8.276l.66-.193.66.193-.194-.66.194-.66-.66.194-.66-.193.193.66-.193.66zM13.84 10.3l-.66.193.194-.66-.194-.66.66.194.66-.194-.193.66.193.66-.66-.193zM5.1 5.827l.66-.194.66.194-.194-.66.194-.66-.66.193-.66-.193.193.66-.193.66zm7.034 6.181l-.66.193.194-.66-.194-.66.66.194.66-.193-.193.66.193.66-.66-.194zm-5.326-7.89l.66-.193.66.193-.194-.66.194-.66-.66.194-.66-.193.193.66-.193.66z"
                          clip-rule="evenodd"
                        />
                      </g>
                    </svg>
                    EUR
                  </div>
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                  <div class="inline-flex items-center">
                    <svg fill="none" aria-hidden="true" class="me-2 h-4 w-4" viewBox="0 0 20 15">
                      <rect width="19.1" height="13.5" x=".25" y=".75" fill="#fff" stroke="#F5F5F5" stroke-width=".5" rx="1.75" />
                      <mask id="a" style="mask-type:luminance" width="20" height="15" x="0" y="0" maskUnits="userSpaceOnUse"><rect width="19.1" height="13.5" x=".25" y=".75" fill="#fff" stroke="#fff" stroke-width=".5" rx="1.75" /></mask>
                      <g fill="#FF3131" mask="url(#a)">
                        <path d="M14 .5h5.6v14H14z" />
                        <path
                          fill-rule="evenodd"
                          d="M0 14.5h5.6V.5H0v14zM11.45 6.784a.307.307 0 01-.518-.277l.268-1.34-.933.466-.467-1.4-.467 1.4-.933-.466.268 1.34a.307.307 0 01-.518.277.307.307 0 00-.434 0l-.25.25-.933-.467L7 7.5l-.231.231a.333.333 0 000 .471l1.164 1.165h1.4l.234 1.4h.466l.234-1.4h1.4l1.164-1.165a.333.333 0 000-.471l-.231-.23.467-.934-.934.466-.25-.25a.307.307 0 00-.433 0z"
                          clip-rule="evenodd"
                        />
                      </g>
                    </svg>
                    CAD
                  </div>
                </button>
              </li>
              <li>
                <button type="button" class="inline-flex w-full rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                  <div class="inline-flex items-center">
                    <svg fill="none" aria-hidden="true" class="me-2 h-4 w-4" viewBox="0 0 20 15">
                      <rect width="19.6" height="14" y=".5" fill="#fff" rx="2" />
                      <mask id="a" style="mask-type:luminance" width="20" height="15" x="0" y="0" maskUnits="userSpaceOnUse"><rect width="19.6" height="14" y=".5" fill="#fff" rx="2" /></mask>
                      <g mask="url(#a)">
                        <path fill="#0A17A7" d="M0 .5h19.6v14H0z" />
                        <path
                          fill="#fff"
                          fill-rule="evenodd"
                          d="M-.898-.842L7.467 4.8V-.433h4.666V4.8l8.365-5.642L21.542.706l-6.614 4.46H19.6v4.667h-4.672l6.614 4.46-1.044 1.549-8.365-5.642v5.233H7.467V10.2l-8.365 5.642-1.044-1.548 6.614-4.46H0V5.166h4.672L-1.942.706-.898-.842z"
                          clip-rule="evenodd"
                        />
                        <path stroke="#DB1F35" stroke-linecap="round" stroke-width=".667" d="M13.068 4.933L21.933-.9M14.009 10.088l7.948 5.357M5.604 4.917L-2.686-.67M6.503 10.024l-9.19 6.093" />
                        <path fill="#E6273E" fill-rule="evenodd" d="M0 8.9h8.4v5.6h2.8V8.9h8.4V6.1h-8.4V.5H8.4v5.6H0v2.8z" clip-rule="evenodd" />
                      </g>
                    </svg>
                    GBP
                  </div>
                </button>
              </li>
            </ul>
          </div>
          <div class="relative w-full">
            <input
              type="number"
              id="currency-input"
              class="z-20 block w-full rounded-e-lg border border-s-2 border-gray-300 border-s-gray-50 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:border-s-gray-700  dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500"
              placeholder="Enter amount"
              value="1000"
              required
            />
          </div>
        </div>
        <p class="mt-2 block text-sm text-gray-500 dark:text-gray-400">1 USD ≈ 0,94 EUR</p>
        <div class="relative mb-12">
          <label for="price-range-input" class="sr-only">Default range</label>
          <input id="price-range-input" type="range" value="1000" min="100" max="1500" class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700" />
          <span class="absolute -bottom-6 start-0 text-sm text-gray-500 dark:text-gray-400">Min ($100)</span>
          <span class="absolute -bottom-6 start-1/3 -translate-x-1/2 text-sm text-gray-500 dark:text-gray-400 rtl:translate-x-1/2">$500</span>
          <span class="absolute -bottom-6 start-2/3 -translate-x-1/2 text-sm text-gray-500 dark:text-gray-400 rtl:translate-x-1/2">$1000</span>
          <span class="absolute -bottom-6 end-0 text-sm text-gray-500 dark:text-gray-400">Max ($1500)</span>
        </div>
        <div class="items-center space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
          >
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                clip-rule="evenodd"
              />
            </svg>

            Preview transfer
          </button>
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            Make transfer
          </button>
        </div>
      </form>
    </div>
  </div>
  <footer class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:p-6 xl:p-8">
    <div class="xl:flex xl:items-center xl:justify-between">
      <ul class="mb-4 flex flex-wrap items-center justify-center space-x-6 lg:mb-0">
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">About</a>
        </li>
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">Become an Author</a>
        </li>
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">Pricing</a>
        </li>
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">Help</a>
        </li>
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">Terms & Conditions</a>
        </li>
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">Cookies</a>
        </li>
        <li>
          <a href="#" class="inline-block text-gray-900 hover:underline dark:text-white">Contact</a>
        </li>
      </ul>
      <button
        id="dropdownLanguageButton"
        data-dropdown-toggle="dropdownLanguage"
        class="mx-auto flex items-center rounded-full p-1.5 text-sm font-medium text-gray-900 hover:text-primary-600 focus:ring-4 focus:ring-gray-100 dark:text-white dark:hover:text-primary-500 dark:focus:ring-gray-700 xl:mr-0"
        type="button"
      >
        <span class="sr-only">Open user menu</span>
        <svg aria-hidden="true" class="ml-1.5 mr-2 h-3 w-3.5" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-us" viewBox="0 0 512 512">
          <g fill-rule="evenodd">
            <g stroke-width="1pt">
              <path fill="#bd3d44" d="M0 0h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
              <path fill="#fff" d="M0 10h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
            </g>
            <path fill="#192f5d" d="M0 0h98.8v70H0z" transform="scale(3.9385)" />
            <path
              fill="#fff"
              d="M8.2 3l1 2.8H12L9.7 7.5l.9 2.7-2.4-1.7L6 10.2l.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7L74 8.5l-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 7.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 24.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 21.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 38.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 35.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 52.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 49.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 66.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 63.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9z"
              transform="scale(3.9385)"
            />
          </g>
        </svg>
        English (US)
        <svg class="mx-1.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
        </svg>
      </button>
      <!-- Dropdown menu -->
      <div id="dropdownLanguage" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700" data-popper-reference-hidden="" data-popper-escaped="" data-popper-placement="bottom">
        <ul class="py-1" role="none">
          <li>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
              <div class="inline-flex items-center">
                <svg aria-hidden="true" class="mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-us" viewBox="0 0 512 512">
                  <g fill-rule="evenodd">
                    <g stroke-width="1pt">
                      <path fill="#bd3d44" d="M0 0h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
                      <path fill="#fff" d="M0 10h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z" transform="scale(3.9385)" />
                    </g>
                    <path fill="#192f5d" d="M0 0h98.8v70H0z" transform="scale(3.9385)" />
                    <path
                      fill="#fff"
                      d="M8.2 3l1 2.8H12L9.7 7.5l.9 2.7-2.4-1.7L6 10.2l.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7L74 8.5l-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 7.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 24.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 21.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 38.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 35.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 52.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 49.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 66.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 63.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9z"
                      transform="scale(3.9385)"
                    />
                  </g>
                </svg>
                English (US)
              </div>
            </a>
          </li>
          <li>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
              <div class="inline-flex items-center">
                <svg aria-hidden="true" class="mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-de" viewBox="0 0 512 512">
                  <path fill="#ffce00" d="M0 341.3h512V512H0z" />
                  <path d="M0 0h512v170.7H0z" />
                  <path fill="#d00" d="M0 170.7h512v170.6H0z" />
                </svg>
                Deutsch
              </div>
            </a>
          </li>
          <li>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
              <div class="inline-flex items-center">
                <svg aria-hidden="true" class="mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" id="flag-icon-css-it" viewBox="0 0 512 512">
                  <g fill-rule="evenodd" stroke-width="1pt">
                    <path fill="#fff" d="M0 0h512v512H0z" />
                    <path fill="#009246" d="M0 0h170.7v512H0z" />
                    <path fill="#ce2b37" d="M341.3 0H512v512H341.3z" />
                  </g>
                </svg>
                Italiano
              </div>
            </a>
          </li>
          <li>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
              <div class="inline-flex items-center">
                <svg aria-hidden="true" class="mr-2 h-3.5 w-3.5 rounded-full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="flag-icon-css-cn" viewBox="0 0 512 512">
                  <defs>
                    <path id="a" fill="#ffde00" d="M1-.3L-.7.8 0-1 .6.8-1-.3z" />
                  </defs>
                  <path fill="#de2910" d="M0 0h512v512H0z" />
                  <use width="30" height="20" transform="matrix(76.8 0 0 76.8 128 128)" xlink:href="#a" />
                  <use width="30" height="20" transform="rotate(-121 142.6 -47) scale(25.5827)" xlink:href="#a" />
                  <use width="30" height="20" transform="rotate(-98.1 198 -82) scale(25.6)" xlink:href="#a" />
                  <use width="30" height="20" transform="rotate(-74 272.4 -114) scale(25.6137)" xlink:href="#a" />
                  <use width="30" height="20" transform="matrix(16 -19.968 19.968 16 256 230.4)" xlink:href="#a" />
                </svg>
                中文 (繁體)
              </div>
            </a>
          </li>
        </ul>
      </div>
    </div>
    <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400 xl:mt-6 xl:text-center 2xl:mt-8">Copyright &copy; 2025 <a href="https://flowbite.com/" class="hover:underline" target="_blank">Flowbite</a>. All rights reserved.</p>
  </footer>
</div>

<!-- New payment modal -->
<div id="newPaymentModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-xl p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5 border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New payment</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="newPaymentModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#" class="grid w-full grid-cols-2 gap-4">
        <div>
          <label for="name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Name</label>
          <input
            type="text"
            id="name"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Name"
            required
          />
        </div>
        <div>
          <label for="account" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Account number</label>
          <input
            type="text"
            id="account"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Account number"
            required
          />
        </div>
        <div>
          <label for="bank-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Bank name</label>
          <input
            type="text"
            id="bank-name"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="JPMorgan"
            required
          />
        </div>
        <div>
          <label for="holder-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Account holder name</label>
          <input
            type="text"
            id="holder-name"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Bonnnie Green"
            required
          />
        </div>
        <div class="col-span-2 flex items-center">
          <input
            id="save-name"
            type="checkbox"
            value=""
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label for="save-name" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Save contact as favourite</label>
        </div>
        <div class="col-span-2">
          <label for="ammount" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Ammount</label>
          <input
            type="number"
            id="ammount"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="$1999"
            required
          />
        </div>
        <div class="col-span-2">
          <label for="payment-description" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Payment description</label>
          <textarea
            id="payment-description"
            rows="4"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Write your thoughts here..."
          ></textarea>
        </div>
        <div class="col-span-2 flex items-center gap-4">
          <div class="flex items-center">
            <input
              id="one-time"
              type="radio"
              value=""
              checked
              name="default-radio"
              class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="one-time" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">One time</label>
          </div>
          <div class="flex items-center">
            <input
              id="recurrent"
              type="radio"
              value=""
              name="default-radio"
              class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="recurrent" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Recurrent</label>
          </div>
        </div>
        <div class="col-span-2">
          <label for="default-datepicker" class="text-sm font-medium text-gray-900 dark:text-gray-300">Transaction date</label>
          <div class="relative mt-2">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
              <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <input
              datepicker
              id="default-datepicker"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="14 November 2025"
            />
          </div>
        </div>
        <button
          type="submit"
          class="col-span-2 mt-2 flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4  focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Pay now
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Add money modal -->
<div id="addMoneyModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-xl p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5 border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add money</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="addMoneyModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#" class="w-full space-y-6">
        <div>
          <label for="ammount-2" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Ammount</label>
          <input
            type="number"
            id="ammount-2"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="$1999"
            required
          />
        </div>
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 ps-4 dark:border-gray-600 dark:bg-gray-700">
          <div>
            <div class="flex items-start">
              <div class="flex h-5 items-center">
                <input
                  id="visa"
                  aria-describedby="visa-text"
                  type="radio"
                  name="payment-method"
                  value=""
                  class="h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  checked
                />
              </div>

              <div class="ms-4 text-sm">
                <label for="visa" class="font-medium text-gray-900 dark:text-white"> Visa ending in 7658 </label>
                <p id="visa-text" class="text-xs font-normal text-gray-500 dark:text-gray-400">Expiry 10/2025</p>
              </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
              <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Delete</button>

              <div class="h-3 w-px shrink-0 bg-gray-200 dark:bg-gray-500"></div>

              <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Edit</button>
            </div>
          </div>

          <div class="shrink-0">
            <img class="h-8 w-auto dark:hidden" src="/images/e-commerce/brand-logos/visa.svg" alt="" />
            <img class="hidden h-8 w-auto dark:flex" src="/images/e-commerce/brand-logos/visa-dark.svg" alt="" />
          </div>
        </div>
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 ps-4 dark:border-gray-600 dark:bg-gray-700">
          <div>
            <div class="flex items-start">
              <div class="flex h-5 items-center">
                <input
                  id="paypal"
                  aria-describedby="paypal-text"
                  type="radio"
                  name="payment-method"
                  value=""
                  class="h-4 w-4 border-gray-300 bg-white text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
              </div>

              <div class="ms-4 text-sm">
                <label for="paypal" class="font-medium text-gray-900 dark:text-white"> Paypal account </label>
              </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
              <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Delete</button>

              <div class="h-3 w-px shrink-0 bg-gray-200 dark:bg-gray-500"></div>

              <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Edit</button>
            </div>
          </div>

          <div class="shrink-0">
            <img class="h-8 w-auto dark:hidden" src="/images/e-commerce/brand-logos/paypal.svg" alt="" />
            <img class="hidden h-8 w-auto dark:flex" src="/images/e-commerce/brand-logos/paypal-dark.svg" alt="" />
          </div>
        </div>
        <div class="flex items-center">
          <div class="h-px w-full bg-gray-200 dark:bg-gray-800"></div>
          <div class="px-5 text-center text-gray-500 dark:text-gray-400">or</div>
          <div class="h-px w-full bg-gray-200 dark:bg-gray-800"></div>
        </div>
        <h3 class="text-gray-500 dark:text-gray-400">Add a new payment method</h3>

        <div class="mb-6 grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label for="full_name-3" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"> Full name (as displayed on card)* </label>
            <input
              type="text"
              id="full_name-3"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Bonnie Green"
              required
            />
          </div>

          <div class="col-span-2 sm:col-span-1">
            <label for="card-number-input-3" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"> Card number* </label>
            <input
              type="text"
              id="card-number-input-3"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pe-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="xxxx-xxxx-xxxx-xxxx"
              pattern="^4[0-9]{12}(?:[0-9]{3})?$"
              required
            />
          </div>

          <div>
            <label for="card-expiration-input-3" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Card expiration* </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                datepicker
                datepicker-format="mm/yy"
                id="card-expiration-input-3"
                type="text"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="12/23"
                required
              />
            </div>
          </div>

          <div>
            <label for="cvv-input-3" class="mb-2 flex items-center gap-1 text-sm font-medium text-gray-900 dark:text-white">
              CVV*
              <button data-tooltip-target="cvv-desc-3" data-tooltip-trigger="hover" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
              <div id="cvv-desc-3" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                The last 3 digits on back of card
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
            </label>
            <div class="relative">
              <input
                type="number"
                id="cvv-input-3"
                aria-describedby="helper-text-explanation"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 [appearance:textfield] focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                placeholder="•••"
                required
              />
              <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3.5">
                <img class="z-10 h-4 dark:hidden" src="/images/e-commerce/cvv-image-light.svg" alt="cvv location image" />
                <img class="hidden h-4 dark:flex" src="/images/e-commerce/cvv-image-dark.svg" alt="cvv location image" />
              </div>
            </div>
          </div>
        </div>

        <button
          type="submit"
          class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4  focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Add money now
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Share IBAN -->
<div id="shareIbanModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5 border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Share your IBAN</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="shareIbanModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#" class="">
        <label for="account-id" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">IBAN</label>
        <div class="relative mb-4">
          <input
            id="account-id"
            type="text"
            class="col-span-6 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            value="USD395768402049576GFH598"
            disabled
            readonly
          />
          <button
            type="button"
            data-copy-to-clipboard-target="account-id"
            data-tooltip-target="tooltip-account-id"
            class="absolute end-2 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
          >
            <span id="default-icon-account-id">
              <svg class="h-3.5 w-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                <path
                  d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"
                />
              </svg>
            </span>
            <span id="success-icon-account-id" class="hidden">
              <svg class="h-3.5 w-3.5 text-primary-700 dark:text-primary-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
              </svg>
            </span>
          </button>
          <div id="tooltip-account-id" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
            <span id="default-tooltip-message-account-id">Copy to clipboard</span>
            <span id="success-tooltip-message-account-id" class="hidden">Copied!</span>
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </form>
      <div class="flex items-center space-x-4">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path d="M2.038 5.61A2.01 2.01 0 0 0 2 6v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6c0-.12-.01-.238-.03-.352l-.866.65-7.89 6.032a2 2 0 0 1-2.429 0L2.884 6.288l-.846-.677Z" />
            <path d="M20.677 4.117A1.996 1.996 0 0 0 20 4H4c-.225 0-.44.037-.642.105l.758.607L12 10.742 19.9 4.7l.777-.583Z" />
          </svg>

          Mail
        </button>
        <button
          type="button"
          class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z"
              clip-rule="evenodd"
            />
          </svg>
          Print
        </button>
        <button
          type="button"
          class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.414A2 2 0 0 0 20.414 6L18 3.586A2 2 0 0 0 16.586 3H5Zm3 11a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6H8v-6Zm1-7V5h6v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
            <path fill-rule="evenodd" d="M14 17h-4v-2h4v2Z" clip-rule="evenodd" />
          </svg>
          Save to files
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete transaction modal -->
<div id="deleteTransactionModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="absolute end-2.5 top-2.5 me-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteTransactionModal"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="sr-only">Close modal</span>
      </button>
      <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Are you sure?</h3>
      <p class="mb-4 font-light text-gray-500 dark:text-gray-400">You are about to delete this transaction, this cannot be undone:</p>
      <div class="mb-4 flex items-center space-x-2">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
          <svg class="h-6 w-6 text-gray-900 dark:text-white" aria-hidden="true" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M21.1227 18.1534C20.7996 18.9 20.417 19.5872 19.9738 20.2191C19.3697 21.0804 18.8751 21.6766 18.4938 22.0077C17.9029 22.5512 17.2698 22.8295 16.5917 22.8453C16.105 22.8453 15.518 22.7068 14.8348 22.4259C14.1492 22.1462 13.5192 22.0077 12.9432 22.0077C12.3391 22.0077 11.6911 22.1462 10.9981 22.4259C10.304 22.7068 9.74486 22.8532 9.31735 22.8678C8.66718 22.8955 8.01913 22.6092 7.37226 22.0077C6.95939 21.6476 6.44297 21.0303 5.82433 20.1557C5.16057 19.2218 4.61488 18.1389 4.18737 16.9042C3.72952 15.5707 3.5 14.2793 3.5 13.0291C3.5 11.597 3.80945 10.3618 4.42928 9.32671C4.91642 8.4953 5.56447 7.83946 6.37557 7.35799C7.18666 6.87653 8.06305 6.63119 9.00684 6.61549C9.52326 6.61549 10.2005 6.77523 11.042 7.08917C11.8812 7.40416 12.4201 7.5639 12.6563 7.5639C12.8329 7.5639 13.4315 7.37712 14.4463 7.00475C15.4059 6.65941 16.2158 6.51643 16.8793 6.57275C18.6772 6.71785 20.0279 7.42659 20.9262 8.70345C19.3183 9.67771 18.5229 11.0423 18.5387 12.7928C18.5532 14.1564 19.0479 15.291 20.02 16.1919C20.4606 16.6101 20.9526 16.9333 21.5 17.1628C21.3813 17.5071 21.256 17.8368 21.1227 18.1534ZM16.9993 1.92751C16.9993 2.99624 16.6089 3.9941 15.8306 4.91771C14.8915 6.01571 13.7555 6.65018 12.5236 6.55006C12.5079 6.42185 12.4988 6.28691 12.4988 6.14511C12.4988 5.11914 12.9454 4.02114 13.7386 3.12338C14.1346 2.66883 14.6382 2.29088 15.2489 1.98938C15.8583 1.69238 16.4348 1.52813 16.9769 1.5C16.9927 1.64287 16.9993 1.78575 16.9993 1.9275V1.92751Z"
              fill="currentColor"
            />
          </svg>
        </div>
        <div class="text-sm">
          <h5 class="font-medium text-gray-900 dark:text-white">Iphone 15 PRO Max</h5>
          <span class="text-gray-500 dark:text-gray-400">Apple Inc.</span>
        </div>
      </div>
      <div class="flex items-center space-x-4">
        <button
          data-modal-toggle="deleteTransactionModal"
          type="button"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Cancel
        </button>
        <button type="submit" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
          <svg aria-hidden="true" class="-ml-1 mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          Yes, delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Add new card modal -->
<div id="addCardModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-xl p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <!-- Modal header -->
      <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5 border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add new card</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="addCardModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <!-- Modal body -->
      <form action="#" class="w-full space-y-6">
        <div class="mb-6 grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label for="full_name-3" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"> Full name (as displayed on card)* </label>
            <input
              type="text"
              id="full_name-3"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Bonnie Green"
              required
            />
          </div>

          <div class="col-span-2 sm:col-span-1">
            <label for="card-number-input-3" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"> Card number* </label>
            <input
              type="text"
              id="card-number-input-3"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pe-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="xxxx-xxxx-xxxx-xxxx"
              pattern="^4[0-9]{12}(?:[0-9]{3})?$"
              required
            />
          </div>

          <div>
            <label for="card-expiration-input-3" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Card expiration* </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <input
                datepicker
                datepicker-format="mm/yy"
                id="card-expiration-input-3"
                type="text"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                placeholder="12/23"
                required
              />
            </div>
          </div>

          <div>
            <label for="cvv-input-4" class="mb-2 flex items-center gap-1 text-sm font-medium text-gray-900 dark:text-white">
              CVV*
              <button data-tooltip-target="cvv-desc-4" data-tooltip-trigger="hover" class="text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
              <div id="cvv-desc-4" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-700">
                The last 3 digits on back of card
                <div class="tooltip-arrow" data-popper-arrow></div>
              </div>
            </label>
            <div class="relative">
              <input
                type="number"
                id="cvv-input-3"
                aria-describedby="helper-text-explanation"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 [appearance:textfield] focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                placeholder="•••"
                required
              />
              <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3.5">
                <img class="z-10 h-4 dark:hidden" src="/images/e-commerce/cvv-image-light.svg" alt="cvv location image" />
                <img class="hidden h-4 dark:flex" src="/images/e-commerce/cvv-image-dark.svg" alt="cvv location image" />
              </div>
            </div>
          </div>
        </div>

        <button
          type="submit"
          class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4  focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Add this card
        </button>
      </form>
    </div>
  </div>
</div>
</div>
