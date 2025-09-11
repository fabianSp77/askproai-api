{{-- Flowbite Component: datatables --}}
@props(['title' => 'Tailwind CSS Datatables - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="grid grid-cols-12 gap-4 border-b border-gray-200 bg-white pb-4 dark:border-gray-700 dark:bg-gray-900">
  <div class="col-span-full mx-4 mt-4 ">
    <nav class="mb-4 flex" aria-label="Breadcrumb">
      <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        <li class="inline-flex items-center">
          <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
            <svg class="me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd"></path>
            </svg>
            Home
          </a>
        </li>
        <li>
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
            </svg>
            <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">E-commerce</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
            </svg>
            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Datatable</span>
          </div>
        </li>
      </ol>
    </nav>
    <h1 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Flowbite Datatable</h1>
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
      <div class="items-center space-x-0 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex sm:space-x-4 md:p-6">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-400 sm:mb-0">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
            <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400">Total products</p>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">6,043</h2>
        </div>
      </div>
      <div class="items-center space-x-0 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex sm:space-x-4 md:p-6">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-400 sm:mb-0">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M4 4a2 2 0 0 0-2 2v9a1 1 0 0 0 1 1h.535a3.5 3.5 0 1 0 6.93 0h3.07a3.5 3.5 0 1 0 6.93 0H21a1 1 0 0 0 1-1v-4a.999.999 0 0 0-.106-.447l-2-4A1 1 0 0 0 19 6h-5a2 2 0 0 0-2-2H4Zm14.192 11.59.016.02a1.5 1.5 0 1 1-.016-.021Zm-10 0 .016.02a1.5 1.5 0 1 1-.016-.021Zm5.806-5.572v-2.02h4.396l1 2.02h-5.396Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400">New products</p>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">978</h2>
        </div>
      </div>
      <div class="items-center space-x-0 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex sm:space-x-4 md:p-6">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-400 sm:mb-0">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M4 4a1 1 0 0 1 1-1h1.5a1 1 0 0 1 .979.796L7.939 6H19a1 1 0 0 1 .979 1.204l-1.25 6a1 1 0 0 1-.979.796H9.605l.208 1H17a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L5.686 5H5a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400">Sales</p>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">1,945</h2>
        </div>
      </div>
      <div class="items-center space-x-0 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex sm:space-x-4 md:p-6">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-400 sm:mb-0">
          <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M9 15a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm3.845-1.855a2.4 2.4 0 0 1 1.2-1.226 1 1 0 0 1 1.992-.026c.426.15.809.408 1.111.749a1 1 0 1 1-1.496 1.327.682.682 0 0 0-.36-.213.997.997 0 0 1-.113-.032.4.4 0 0 0-.394.074.93.93 0 0 0 .455.254 2.914 2.914 0 0 1 1.504.9c.373.433.669 1.092.464 1.823a.996.996 0 0 1-.046.129c-.226.519-.627.94-1.132 1.192a1 1 0 0 1-1.956.093 2.68 2.68 0 0 1-1.227-.798 1 1 0 1 1 1.506-1.315.682.682 0 0 0 .363.216c.038.009.075.02.111.032a.4.4 0 0 0 .395-.074.93.93 0 0 0-.455-.254 2.91 2.91 0 0 1-1.503-.9c-.375-.433-.666-1.089-.466-1.817a.994.994 0 0 1 .047-.134Zm1.884.573.003.008c-.003-.005-.003-.008-.003-.008Zm.55 2.613s-.002-.002-.003-.007a.032.032 0 0 1 .003.007ZM4 14a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0v-4a1 1 0 0 1 1-1Zm3-2a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm6.5-8a1 1 0 0 1 1-1H18a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-.796l-2.341 2.049a1 1 0 0 1-1.24.06l-2.894-2.066L6.614 9.29a1 1 0 1 1-1.228-1.578l4.5-3.5a1 1 0 0 1 1.195-.025l2.856 2.04L15.34 5h-.84a1 1 0 0 1-1-1Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400">Total Income</p>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">$1,657,856</h2>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="max-w-auto mx-auto w-full bg-white p-4 dark:bg-gray-900">
  <table id="filter-table">
    <thead>
      <tr>
        <th>
          <span class="flex items-center">
            Name
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
        <th>
          <span class="flex items-center">
            Category
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
        <th>
          <span class="flex items-center">
            Brand
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
        <th>
          <span class="flex items-center">
            Price
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
        <th>
          <span class="flex items-center">
            Stock
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
        <th>
          <span class="flex items-center">
            Total Sales
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
        <th>
          <span class="flex items-center">
            Status
            <svg class="ms-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
            </svg>
          </span>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">Apple iMac 27"</th>
        <td>
          <span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span>
        </td>
        <td>Apple</td>
        <td>$1,299</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>95</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          200
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple iPhone</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Mobile Phones</span></td>
        <td>Apple</td>
        <td>$999</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>342</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          300
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Samsung Galaxy</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Mobile Phones</span></td>
        <td>Samsung</td>
        <td>$899</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>127</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          150
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Dell XPS 13</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
        <td>Dell</td>
        <td>$1,099</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          120
        </td>
        <td>Out of Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">HP Spectre x360</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
        <td>HP</td>
        <td>$1,299</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>325</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          80
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Google Pixel 6</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Mobile Phones</span></td>
        <td>Google</td>
        <td>$799</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>100</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          200
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Sony WH-1000XM4</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Headphones</span></td>
        <td>Sony</td>
        <td>$349</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>60</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          150
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple AirPods Pro</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Headphones</span></td>
        <td>Apple</td>
        <td>$249</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>200</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          300
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Asus ROG Zephyrus</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
        <td>Asus</td>
        <td>$1,899</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>15</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          50
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Microsoft Surface Pro 7</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
        <td>Microsoft</td>
        <td>$899</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>224</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          100
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Samsung QLED TV</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Televisions</span></td>
        <td>Samsung</td>
        <td>$1,299</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          70
        </td>
        <td>Out of Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">LG OLED TV</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Televisions</span></td>
        <td>LG</td>
        <td>$1,499</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>204</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          50
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Canon EOS R5</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Cameras</span></td>
        <td>Canon</td>
        <td>$3,899</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>674</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          30
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Nikon Z7 II</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Cameras</span></td>
        <td>Nikon</td>
        <td>$3,299</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>164</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          25
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple Watch Series 7</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Wearables</span></td>
        <td>Apple</td>
        <td>$399</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>150</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          500
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Fitbit Charge 5</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Wearables</span></td>
        <td>Fitbit</td>
        <td>$179</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>444</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          250
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Dyson V11 Vacuum</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Home Appliances</span></td>
        <td>Dyson</td>
        <td>$599</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          90
        </td>
        <td>Out of Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">iRobot Roomba i7+</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Home Appliances</span></td>
        <td>iRobot</td>
        <td>$799</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>1043</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          70
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Bose SoundLink Revolve</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Speakers</span></td>
        <td>Bose</td>
        <td>$199</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>935</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          200
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Sonos One</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Sonos</span></td>
        <td>Sonos</td>
        <td>$90</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>180</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          67
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple iPad Pro</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Tablets</span></td>
        <td>Apple</td>
        <td>$1,099</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>98</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          150
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Samsung Galaxy Tab S7</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Tablets</span></td>
        <td>Samsung</td>
        <td>$649</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>70</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          130
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Amazon Echo Dot</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Smart Home</span></td>
        <td>Amazon</td>
        <td>$49</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>300</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          800
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Google Nest Hub</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Smart Home</span></td>
        <td>Google</td>
        <td>$89</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>150</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          400
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">PlayStation 5</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming Consoles</span></td>
        <td>Sony</td>
        <td>$499</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>10</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          500
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Xbox Series X</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming Consoles</span></td>
        <td>Microsoft</td>
        <td>$499</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>0</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          450
        </td>
        <td>Out of Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Nintendo Switch</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Gaming Consoles</span></td>
        <td>Nintendo</td>
        <td>$299</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>65</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          600
        </td>
        <td>In Stock</td>
      </tr>
      <tr>
        <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">Apple MacBook Pro</td>
        <td><span class="rounded-sm bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">Computers</span></td>
        <td>Apple</td>
        <td>$1,299</td>
        <td><span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>20</td>
        <td class="flex items-center">
          <svg class="me-1 h-5 w-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312" />
          </svg>
          100
        </td>
        <td>In Stock</td>
      </tr>
    </tbody>
  </table>
</div>
</div>
