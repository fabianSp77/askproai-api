@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    <section class="bg-gray-50 py-3 antialiased dark:bg-gray-900 sm:py-5">
      <div class="mx-auto max-w-screen-2xl px-4 lg:px-12">
        <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
          <div class="flex flex-col justify-between space-y-3 p-4 md:flex-row md:items-center md:space-x-4 md:space-y-0">
            <div class="w-full md:w-1/2">
              <form class="flex items-center">
                <Label htmlFor="simple-search" class="sr-only">
                  Search
                </Label>
                <div class="relative w-full">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg
                      aria-hidden="true"
                      class="h-5 w-5 text-gray-500 dark:text-gray-400"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </div>
                  <input
                    type="text"
                    id="simple-search"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    placeholder="Search"
                    required
                  ></input>
                </div>
              </form>
            </div>
            <div class="flex shrink-0 flex-row items-center justify-between md:justify-end md:space-x-3">
              <div class="flex items-center space-x-3">
                <Button
                  @click="{ setShowCreateModal(true) "}
                  class="whitespace-nowrap"
                >
                  <svg
                    class="mr-2 hidden h-3.5 w-3.5 shrink-0 md:inline"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    ></path>
                  </svg>
                  Add product
                </Button>
                <Dropdown
                  color="gray"
                  label={{ 
                    
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                        class="mr-2 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                        ></path>
                      </svg>
                      Filter
                    
                   }}
                  theme={{ {
                    floating: {
                      base: twMerge(theme.dropdown.floating.base, "rounded-lg"),
                     }},
                  }}
                >
                  <form class="space-y-4 p-3">
                    <p class="inline-flex items-center text-gray-500 dark:text-gray-400">
                      Filter
                    </p>
                    <div class="mt-4">
                      <span class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                        Price Range
                      </span>
                      <div class="grid grid-cols-2 gap-3">
                        <RangeSlider
                          defaultValue={{ 300 }}
                          id="min-price"
                          max={{ 7000 }}
                          min={{ 0 }}
                          name="min-price"
                          class="[&_input]:dark:bg-gray-600"
                        ></RangeSlider>
                        <RangeSlider
                          defaultValue={{ 3500 }}
                          id="max-price"
                          max={{ 7000 }}
                          min={{ 0 }}
                          name="max-price"
                          class="[&_input]:dark:bg-gray-600"
                        ></RangeSlider>
                        <div class="flex items-center justify-between space-x-2 md:col-span-2">
                          <div class="w-full">
                            <Label
                              htmlFor="min-price-input"
                              class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                            >
                              From
                            </Label>
                            <TextInput
                              defaultValue="300"
                              id="min-price-input"
                              name="min-price-input"
                              max="7000"
                              min="0"
                              type="number"
                              class="[&_input]:py-2 [&_input]:sm:text-xs"
                            ></TextInput>
                          </div>
                          <div class="w-full">
                            <Label
                              htmlFor="max-price-input"
                              class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                            >
                              To
                            </Label>
                            <TextInput
                              defaultValue="3500"
                              id="max-price-input"
                              name="max-price-input"
                              max="7000"
                              min="0"
                              type="number"
                              class="[&_input]:py-2 [&_input]:sm:text-xs"
                            ></TextInput>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div>
                      <span class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                        Sales
                      </span>
                      <div class="grid grid-cols-2 gap-3">
                        <RangeSlider
                          defaultValue={{ 300 }}
                          id="min-sales"
                          max={{ 7000 }}
                          min={{ 0 }}
                          name="min-sales"
                          step={{ 1 }}
                          class="[&_input]:dark:bg-gray-600"
                        ></RangeSlider>
                        <RangeSlider
                          defaultValue={{ 3500 }}
                          id="max-sales"
                          max={{ 7000 }}
                          min={{ 0 }}
                          name="max-sales"
                          step={{ 1 }}
                          class="[&_input]:dark:bg-gray-600"
                        ></RangeSlider>
                      </div>
                      <div class="flex items-center justify-between space-x-2 md:col-span-2">
                        <div class="w-full">
                          <Label
                            htmlFor="min-sales-input"
                            class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                          >
                            From
                          </Label>
                          <TextInput
                            defaultValue="1"
                            id="min-sales-input"
                            max="300"
                            min="0"
                            name="min-sales-input"
                            type="number"
                            class="[&_input]:py-2 [&_input]:sm:text-xs"
                          ></TextInput>
                        </div>
                        <div class="w-full">
                          <Label
                            htmlFor="max-sales-input"
                            class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                          >
                            To
                          </Label>
                          <TextInput
                            defaultValue="100"
                            id="max-sales-input"
                            max="300"
                            min="0"
                            name="max-sales-input"
                            type="number"
                            class="[&_input]:py-2 [&_input]:sm:text-xs"
                          ></TextInput>
                        </div>
                      </div>
                    </div>
                    <div>
                      <span class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                        Category
                      </span>
                      <ul class="grid w-full grid-cols-2 gap-3">
                        <li>
                          <Checkbox
                            id="gaming"
                            name="gaming"
                            class="peer hidden"
                          ></Checkbox>
                          <Label
                            htmlFor="gaming"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-100 p-2 text-center text-sm font-medium text-gray-500 hover:bg-primary-500 hover:text-white peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:bg-primary-600"
                          >
                            Gaming
                          </Label>
                        </li>
                        <li>
                          <Checkbox
                            id="electronics"
                            name="electronics"
                            class="peer hidden"
                          ></Checkbox>
                          <Label
                            htmlFor="electronics"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-100 p-2 text-center text-sm font-medium text-gray-500 hover:bg-primary-500 hover:text-white peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:bg-primary-600"
                          >
                            Electronics
                          </Label>
                        </li>
                        <li>
                          <Checkbox
                            defaultChecked
                            id="phone"
                            name="phone"
                            class="peer hidden"
                          ></Checkbox>
                          <Label
                            htmlFor="phone"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-100 p-2 text-center text-sm font-medium text-gray-500 hover:bg-primary-500 hover:text-white peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:bg-primary-600"
                          >
                            Phone
                          </Label>
                        </li>
                        <li>
                          <Checkbox
                            id="tv-monitor"
                            name="tv-monitor"
                            class="peer hidden"
                          ></Checkbox>
                          <Label
                            htmlFor="tv-monitor"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-100 p-2 text-center text-sm font-medium text-gray-500 hover:bg-primary-500 hover:text-white peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:bg-primary-600"
                          >
                            TV/Monitor
                          </Label>
                        </li>
                        <li>
                          <Checkbox
                            id="laptop"
                            name="laptop"
                            class="peer hidden"
                          ></Checkbox>
                          <Label
                            htmlFor="laptop"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-100 p-2 text-center text-sm font-medium text-gray-500 hover:bg-primary-500 hover:text-white peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:bg-primary-600"
                          >
                            Laptop
                          </Label>
                        </li>
                        <li>
                          <Checkbox
                            defaultChecked
                            id="watch"
                            name="watch"
                            class="peer hidden"
                          ></Checkbox>
                          <Label
                            htmlFor="watch"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-100 p-2 text-center text-sm font-medium text-gray-500 hover:bg-primary-500 hover:text-white peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:text-white dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:text-white dark:peer-checked:bg-primary-600"
                          >
                            Watch
                          </Label>
                        </li>
                      </ul>
                    </div>
                    <div>
                      <span class="mb-2 block text-sm font-medium text-black dark:text-white">
                        State
                      </span>
                      <ul class="flex w-full flex-col items-center rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <li class="w-full border-b border-gray-200 dark:border-gray-600">
                          <div class="flex items-center pl-3">
                            <Radio defaultChecked id="state-all" name="state" ></Radio>
                            <Label
                              htmlFor="state-all"
                              class="ml-2 w-full py-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                            >
                              All
                            </Label>
                          </div>
                        </li>
                        <li class="w-full border-b border-gray-200 dark:border-gray-600">
                          <div class="flex items-center pl-3">
                            <Radio id="state-new" name="state" ></Radio>
                            <Label
                              htmlFor="state-new"
                              class="ml-2 w-full py-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                            >
                              New
                            </Label>
                          </div>
                        </li>
                        <li class="w-full">
                          <div class="flex items-center pl-3">
                            <Radio id="state-used" name="state" ></Radio>
                            <Label
                              htmlFor="state-used"
                              class="ml-2 w-full py-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                            >
                              Refurbished
                            </Label>
                          </div>
                        </li>
                      </ul>
                    </div>
                    <div class="mt-4 flex space-x-4">
                      <Button type="submit">Show 32 Results</Button>
                      <Button type="reset">Reset</Button>
                    </div>
                  </form>
                </Dropdown>
              </div>
              <div class="flex items-center space-x-1">
                <button
                  class="flex items-center justify-center rounded-lg bg-white p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fillRule="evenodd"
                      d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                      clipRule="evenodd"
                    ></path>
                  </svg>
                </button>
                <button
                  class="flex items-center justify-center rounded-lg bg-white p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                  type="button"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" ></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div class="mx-4 space-y-4 overflow-x-auto">
            <div class="relative flex w-full cursor-pointer flex-col rounded-lg border border-gray-200 bg-white p-3 shadow hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 md:flex-row md:items-center md:space-x-6">
              <img
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                alt=""
                class="h-auto w-20"
              ></img>
              <div class="flex flex-col md:justify-between">
                <div class="grid w-full grid-cols-2 gap-2 md:grid-cols-6">
                  <div class="col-span-3 flex h-full flex-col justify-between">
                    <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-white md:mb-3">
                      Apple iMac 27&#34;
                    </h3>
                    <div>
                      <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        Category
                      </h6>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        PC/Desktop PC
                      </p>
                    </div>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Price
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      $2999
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Stock
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      300
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Total Sales
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      466
                    </p>
                  </div>
                </div>
              </div>
              <div class="absolute right-3 top-3 dark:text-gray-400">
                <Dropdown
                  dismiss@click="{ false "}
                  inline
                  label={{ 
                    
                      <span class="sr-only">Manage entry</span>
                      <svg
                        class="h-5 w-5"
                        aria-hidden
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      </svg>
                    
                   }}
                  theme={{ {
                    arrowIcon: "hidden",
                    floating: {
                      base: twMerge(theme.dropdown.floating.base, "w-40"),
                     }},
                  }}
                >
                  <Dropdown.Item @click="{ setShowUpdateModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                      ></path>
                    </svg>
                    Edit
                  </Dropdown.Item>
                  <Dropdown.Item @click="{ setShowReadModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      ></path>
                    </svg>
                    Preview
                  </Dropdown.Item>
                  <Dropdown.Item
                    @click="{ setShowDeleteModal(true) "}
                    class="text-red-600 dark:text-red-600"
                  >
                    <svg
                      class="mr-2 h-4 w-4"
                      viewBox="0 0 14 15"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                      aria-hidden
                    >
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        fill="currentColor"
                        d="M6.09922 0.300781C5.93212 0.30087 5.76835 0.347476 5.62625 0.435378C5.48414 0.523281 5.36931 0.649009 5.29462 0.798481L4.64302 2.10078H1.59922C1.36052 2.10078 1.13161 2.1956 0.962823 2.36439C0.79404 2.53317 0.699219 2.76209 0.699219 3.00078C0.699219 3.23948 0.79404 3.46839 0.962823 3.63718C1.13161 3.80596 1.36052 3.90078 1.59922 3.90078V12.9008C1.59922 13.3782 1.78886 13.836 2.12643 14.1736C2.46399 14.5111 2.92183 14.7008 3.39922 14.7008H10.5992C11.0766 14.7008 11.5344 14.5111 11.872 14.1736C12.2096 13.836 12.3992 13.3782 12.3992 12.9008V3.90078C12.6379 3.90078 12.8668 3.80596 13.0356 3.63718C13.2044 3.46839 13.2992 3.23948 13.2992 3.00078C13.2992 2.76209 13.2044 2.53317 13.0356 2.36439C12.8668 2.1956 12.6379 2.10078 12.3992 2.10078H9.35542L8.70382 0.798481C8.62913 0.649009 8.5143 0.523281 8.37219 0.435378C8.23009 0.347476 8.06631 0.30087 7.89922 0.300781H6.09922ZM4.29922 5.70078C4.29922 5.46209 4.39404 5.23317 4.56282 5.06439C4.73161 4.8956 4.96052 4.80078 5.19922 4.80078C5.43791 4.80078 5.66683 4.8956 5.83561 5.06439C6.0044 5.23317 6.09922 5.46209 6.09922 5.70078V11.1008C6.09922 11.3395 6.0044 11.5684 5.83561 11.7372C5.66683 11.906 5.43791 12.0008 5.19922 12.0008C4.96052 12.0008 4.73161 11.906 4.56282 11.7372C4.39404 11.5684 4.29922 11.3395 4.29922 11.1008V5.70078ZM8.79922 4.80078C8.56052 4.80078 8.33161 4.8956 8.16282 5.06439C7.99404 5.23317 7.89922 5.46209 7.89922 5.70078V11.1008C7.89922 11.3395 7.99404 11.5684 8.16282 11.7372C8.33161 11.906 8.56052 12.0008 8.79922 12.0008C9.03791 12.0008 9.26683 11.906 9.43561 11.7372C9.6044 11.5684 9.69922 11.3395 9.69922 11.1008V5.70078C9.69922 5.46209 9.6044 5.23317 9.43561 5.06439C9.26683 4.8956 9.03791 4.80078 8.79922 4.80078Z"
                      ></path>
                    </svg>
                    Delete
                  </Dropdown.Item>
                </Dropdown>
              </div>
            </div>
            <div class="relative flex w-full cursor-pointer flex-col rounded-lg border border-gray-200 bg-white p-3 shadow hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 md:flex-row md:items-center md:space-x-6">
              <img
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/xbox-series-s.png"
                alt=""
                class="h-auto w-20"
              ></img>
              <div class="flex flex-col md:justify-between">
                <div class="grid w-full grid-cols-2 gap-2 md:grid-cols-6">
                  <div class="col-span-3 flex h-full flex-col justify-between">
                    <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-white md:mb-3">
                      Xbox Series S
                    </h3>
                    <div>
                      <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        Category
                      </h6>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        Gaming/Console
                      </p>
                    </div>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Price
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      $299
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Stock
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      56
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Total Sales
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      3040
                    </p>
                  </div>
                </div>
              </div>
              <div class="absolute right-3 top-3 dark:text-gray-400">
                <Dropdown
                  dismiss@click="{ false "}
                  inline
                  label={{ 
                    
                      <span class="sr-only">Manage entry</span>
                      <svg
                        class="h-5 w-5"
                        aria-hidden
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      </svg>
                    
                   }}
                  theme={{ {
                    arrowIcon: "hidden",
                    floating: {
                      base: twMerge(theme.dropdown.floating.base, "w-40"),
                     }},
                  }}
                >
                  <Dropdown.Item @click="{ setShowUpdateModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                      ></path>
                    </svg>
                    Edit
                  </Dropdown.Item>
                  <Dropdown.Item @click="{ setShowReadModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      ></path>
                    </svg>
                    Preview
                  </Dropdown.Item>
                  <Dropdown.Item
                    @click="{ setShowDeleteModal(true) "}
                    class="text-red-600 dark:text-red-600"
                  >
                    <svg
                      class="mr-2 h-4 w-4"
                      viewBox="0 0 14 15"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                      aria-hidden
                    >
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        fill="currentColor"
                        d="M6.09922 0.300781C5.93212 0.30087 5.76835 0.347476 5.62625 0.435378C5.48414 0.523281 5.36931 0.649009 5.29462 0.798481L4.64302 2.10078H1.59922C1.36052 2.10078 1.13161 2.1956 0.962823 2.36439C0.79404 2.53317 0.699219 2.76209 0.699219 3.00078C0.699219 3.23948 0.79404 3.46839 0.962823 3.63718C1.13161 3.80596 1.36052 3.90078 1.59922 3.90078V12.9008C1.59922 13.3782 1.78886 13.836 2.12643 14.1736C2.46399 14.5111 2.92183 14.7008 3.39922 14.7008H10.5992C11.0766 14.7008 11.5344 14.5111 11.872 14.1736C12.2096 13.836 12.3992 13.3782 12.3992 12.9008V3.90078C12.6379 3.90078 12.8668 3.80596 13.0356 3.63718C13.2044 3.46839 13.2992 3.23948 13.2992 3.00078C13.2992 2.76209 13.2044 2.53317 13.0356 2.36439C12.8668 2.1956 12.6379 2.10078 12.3992 2.10078H9.35542L8.70382 0.798481C8.62913 0.649009 8.5143 0.523281 8.37219 0.435378C8.23009 0.347476 8.06631 0.30087 7.89922 0.300781H6.09922ZM4.29922 5.70078C4.29922 5.46209 4.39404 5.23317 4.56282 5.06439C4.73161 4.8956 4.96052 4.80078 5.19922 4.80078C5.43791 4.80078 5.66683 4.8956 5.83561 5.06439C6.0044 5.23317 6.09922 5.46209 6.09922 5.70078V11.1008C6.09922 11.3395 6.0044 11.5684 5.83561 11.7372C5.66683 11.906 5.43791 12.0008 5.19922 12.0008C4.96052 12.0008 4.73161 11.906 4.56282 11.7372C4.39404 11.5684 4.29922 11.3395 4.29922 11.1008V5.70078ZM8.79922 4.80078C8.56052 4.80078 8.33161 4.8956 8.16282 5.06439C7.99404 5.23317 7.89922 5.46209 7.89922 5.70078V11.1008C7.89922 11.3395 7.99404 11.5684 8.16282 11.7372C8.33161 11.906 8.56052 12.0008 8.79922 12.0008C9.03791 12.0008 9.26683 11.906 9.43561 11.7372C9.6044 11.5684 9.69922 11.3395 9.69922 11.1008V5.70078C9.69922 5.46209 9.6044 5.23317 9.43561 5.06439C9.26683 4.8956 9.03791 4.80078 8.79922 4.80078Z"
                      ></path>
                    </svg>
                    Delete
                  </Dropdown.Item>
                </Dropdown>
              </div>
            </div>
            <div class="relative flex w-full cursor-pointer flex-col rounded-lg border border-gray-200 bg-white p-3 shadow hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 md:flex-row md:items-center md:space-x-6">
              <img
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/playstation-5.png"
                alt=""
                class="h-auto w-20"
              ></img>
              <div class="flex flex-col md:justify-between">
                <div class="grid w-full grid-cols-2 gap-2 md:grid-cols-6">
                  <div class="col-span-3 flex h-full flex-col justify-between">
                    <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-white md:mb-3">
                      PlayStation 5
                    </h3>
                    <div>
                      <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        Category
                      </h6>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        Gaming/Console
                      </p>
                    </div>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Price
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      $799
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Stock
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      78
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Total Sales
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      2999
                    </p>
                  </div>
                </div>
              </div>
              <div class="absolute right-3 top-3 dark:text-gray-400">
                <Dropdown
                  dismiss@click="{ false "}
                  inline
                  label={{ 
                    
                      <span class="sr-only">Manage entry</span>
                      <svg
                        class="h-5 w-5"
                        aria-hidden
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      </svg>
                    
                   }}
                  theme={{ {
                    arrowIcon: "hidden",
                    floating: {
                      base: twMerge(theme.dropdown.floating.base, "w-40"),
                     }},
                  }}
                >
                  <Dropdown.Item @click="{ setShowUpdateModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                      ></path>
                    </svg>
                    Edit
                  </Dropdown.Item>
                  <Dropdown.Item @click="{ setShowReadModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      ></path>
                    </svg>
                    Preview
                  </Dropdown.Item>
                  <Dropdown.Item
                    @click="{ setShowDeleteModal(true) "}
                    class="text-red-600 dark:text-red-600"
                  >
                    <svg
                      class="mr-2 h-4 w-4"
                      viewBox="0 0 14 15"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                      aria-hidden
                    >
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        fill="currentColor"
                        d="M6.09922 0.300781C5.93212 0.30087 5.76835 0.347476 5.62625 0.435378C5.48414 0.523281 5.36931 0.649009 5.29462 0.798481L4.64302 2.10078H1.59922C1.36052 2.10078 1.13161 2.1956 0.962823 2.36439C0.79404 2.53317 0.699219 2.76209 0.699219 3.00078C0.699219 3.23948 0.79404 3.46839 0.962823 3.63718C1.13161 3.80596 1.36052 3.90078 1.59922 3.90078V12.9008C1.59922 13.3782 1.78886 13.836 2.12643 14.1736C2.46399 14.5111 2.92183 14.7008 3.39922 14.7008H10.5992C11.0766 14.7008 11.5344 14.5111 11.872 14.1736C12.2096 13.836 12.3992 13.3782 12.3992 12.9008V3.90078C12.6379 3.90078 12.8668 3.80596 13.0356 3.63718C13.2044 3.46839 13.2992 3.23948 13.2992 3.00078C13.2992 2.76209 13.2044 2.53317 13.0356 2.36439C12.8668 2.1956 12.6379 2.10078 12.3992 2.10078H9.35542L8.70382 0.798481C8.62913 0.649009 8.5143 0.523281 8.37219 0.435378C8.23009 0.347476 8.06631 0.30087 7.89922 0.300781H6.09922ZM4.29922 5.70078C4.29922 5.46209 4.39404 5.23317 4.56282 5.06439C4.73161 4.8956 4.96052 4.80078 5.19922 4.80078C5.43791 4.80078 5.66683 4.8956 5.83561 5.06439C6.0044 5.23317 6.09922 5.46209 6.09922 5.70078V11.1008C6.09922 11.3395 6.0044 11.5684 5.83561 11.7372C5.66683 11.906 5.43791 12.0008 5.19922 12.0008C4.96052 12.0008 4.73161 11.906 4.56282 11.7372C4.39404 11.5684 4.29922 11.3395 4.29922 11.1008V5.70078ZM8.79922 4.80078C8.56052 4.80078 8.33161 4.8956 8.16282 5.06439C7.99404 5.23317 7.89922 5.46209 7.89922 5.70078V11.1008C7.89922 11.3395 7.99404 11.5684 8.16282 11.7372C8.33161 11.906 8.56052 12.0008 8.79922 12.0008C9.03791 12.0008 9.26683 11.906 9.43561 11.7372C9.6044 11.5684 9.69922 11.3395 9.69922 11.1008V5.70078C9.69922 5.46209 9.6044 5.23317 9.43561 5.06439C9.26683 4.8956 9.03791 4.80078 8.79922 4.80078Z"
                      ></path>
                    </svg>
                    Delete
                  </Dropdown.Item>
                </Dropdown>
              </div>
            </div>
            <div class="relative flex w-full cursor-pointer flex-col rounded-lg border border-gray-200 bg-white p-3 shadow hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 md:flex-row md:items-center md:space-x-6">
              <img
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/benq-ex2710q.png"
                alt=""
                class="h-auto w-20"
              ></img>
              <div class="flex flex-col md:justify-between">
                <div class="grid w-full grid-cols-2 gap-2 md:grid-cols-6">
                  <div class="col-span-3 flex h-full flex-col justify-between">
                    <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-white md:mb-3">
                      Monitor BenQ EX2710Q
                    </h3>
                    <div>
                      <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        Category
                      </h6>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        TV/Monitor
                      </p>
                    </div>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Price
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      $499
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Stock
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      354
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Total Sales
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      76
                    </p>
                  </div>
                </div>
              </div>
              <div class="absolute right-3 top-3 dark:text-gray-400">
                <Dropdown
                  dismiss@click="{ false "}
                  inline
                  label={{ 
                    
                      <span class="sr-only">Manage entry</span>
                      <svg
                        class="h-5 w-5"
                        aria-hidden
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      </svg>
                    
                   }}
                  theme={{ {
                    arrowIcon: "hidden",
                    floating: {
                      base: twMerge(theme.dropdown.floating.base, "w-40"),
                     }},
                  }}
                >
                  <Dropdown.Item @click="{ setShowUpdateModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                      ></path>
                    </svg>
                    Edit
                  </Dropdown.Item>
                  <Dropdown.Item @click="{ setShowReadModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      ></path>
                    </svg>
                    Preview
                  </Dropdown.Item>
                  <Dropdown.Item
                    @click="{ setShowDeleteModal(true) "}
                    class="text-red-600 dark:text-red-600"
                  >
                    <svg
                      class="mr-2 h-4 w-4"
                      viewBox="0 0 14 15"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                      aria-hidden
                    >
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        fill="currentColor"
                        d="M6.09922 0.300781C5.93212 0.30087 5.76835 0.347476 5.62625 0.435378C5.48414 0.523281 5.36931 0.649009 5.29462 0.798481L4.64302 2.10078H1.59922C1.36052 2.10078 1.13161 2.1956 0.962823 2.36439C0.79404 2.53317 0.699219 2.76209 0.699219 3.00078C0.699219 3.23948 0.79404 3.46839 0.962823 3.63718C1.13161 3.80596 1.36052 3.90078 1.59922 3.90078V12.9008C1.59922 13.3782 1.78886 13.836 2.12643 14.1736C2.46399 14.5111 2.92183 14.7008 3.39922 14.7008H10.5992C11.0766 14.7008 11.5344 14.5111 11.872 14.1736C12.2096 13.836 12.3992 13.3782 12.3992 12.9008V3.90078C12.6379 3.90078 12.8668 3.80596 13.0356 3.63718C13.2044 3.46839 13.2992 3.23948 13.2992 3.00078C13.2992 2.76209 13.2044 2.53317 13.0356 2.36439C12.8668 2.1956 12.6379 2.10078 12.3992 2.10078H9.35542L8.70382 0.798481C8.62913 0.649009 8.5143 0.523281 8.37219 0.435378C8.23009 0.347476 8.06631 0.30087 7.89922 0.300781H6.09922ZM4.29922 5.70078C4.29922 5.46209 4.39404 5.23317 4.56282 5.06439C4.73161 4.8956 4.96052 4.80078 5.19922 4.80078C5.43791 4.80078 5.66683 4.8956 5.83561 5.06439C6.0044 5.23317 6.09922 5.46209 6.09922 5.70078V11.1008C6.09922 11.3395 6.0044 11.5684 5.83561 11.7372C5.66683 11.906 5.43791 12.0008 5.19922 12.0008C4.96052 12.0008 4.73161 11.906 4.56282 11.7372C4.39404 11.5684 4.29922 11.3395 4.29922 11.1008V5.70078ZM8.79922 4.80078C8.56052 4.80078 8.33161 4.8956 8.16282 5.06439C7.99404 5.23317 7.89922 5.46209 7.89922 5.70078V11.1008C7.89922 11.3395 7.99404 11.5684 8.16282 11.7372C8.33161 11.906 8.56052 12.0008 8.79922 12.0008C9.03791 12.0008 9.26683 11.906 9.43561 11.7372C9.6044 11.5684 9.69922 11.3395 9.69922 11.1008V5.70078C9.69922 5.46209 9.6044 5.23317 9.43561 5.06439C9.26683 4.8956 9.03791 4.80078 8.79922 4.80078Z"
                      ></path>
                    </svg>
                    Delete
                  </Dropdown.Item>
                </Dropdown>
              </div>
            </div>
            <div class="relative flex w-full cursor-pointer flex-col rounded-lg border border-gray-200 bg-white p-3 shadow hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 md:flex-row md:items-center md:space-x-6">
              <img
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/apple-iphone-14.png"
                alt=""
                class="h-auto w-20"
              ></img>
              <div class="flex flex-col md:justify-between">
                <div class="grid w-full grid-cols-2 gap-2 md:grid-cols-6">
                  <div class="col-span-3 flex h-full flex-col justify-between">
                    <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-white md:mb-3">
                      Apple iPhone 14
                    </h3>
                    <div>
                      <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        Category
                      </h6>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        Phone
                      </p>
                    </div>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Price
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      $999
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Stock
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      1237
                    </p>
                  </div>
                  <div class="col-span-1">
                    <h6 class="text-sm font-normal text-gray-500 dark:text-gray-400">
                      Total Sales
                    </h6>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      2000
                    </p>
                  </div>
                </div>
              </div>
              <div class="absolute right-3 top-3 dark:text-gray-400">
                <Dropdown
                  dismiss@click="{ false "}
                  inline
                  label={{ 
                    
                      <span class="sr-only">Manage entry</span>
                      <svg
                        class="h-5 w-5"
                        aria-hidden
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      </svg>
                    
                   }}
                  theme={{ {
                    arrowIcon: "hidden",
                    floating: {
                      base: twMerge(theme.dropdown.floating.base, "w-40"),
                     }},
                  }}
                >
                  <Dropdown.Item @click="{ setShowUpdateModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                      ></path>
                    </svg>
                    Edit
                  </Dropdown.Item>
                  <Dropdown.Item @click="{ setShowReadModal(true) "}>
                    <svg
                      class="mr-2 h-4 w-4"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                      aria-hidden
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" ></path>
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      ></path>
                    </svg>
                    Preview
                  </Dropdown.Item>
                  <Dropdown.Item
                    @click="{ setShowDeleteModal(true) "}
                    class="text-red-600 dark:text-red-600"
                  >
                    <svg
                      class="mr-2 h-4 w-4"
                      viewBox="0 0 14 15"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                      aria-hidden
                    >
                      <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        fill="currentColor"
                        d="M6.09922 0.300781C5.93212 0.30087 5.76835 0.347476 5.62625 0.435378C5.48414 0.523281 5.36931 0.649009 5.29462 0.798481L4.64302 2.10078H1.59922C1.36052 2.10078 1.13161 2.1956 0.962823 2.36439C0.79404 2.53317 0.699219 2.76209 0.699219 3.00078C0.699219 3.23948 0.79404 3.46839 0.962823 3.63718C1.13161 3.80596 1.36052 3.90078 1.59922 3.90078V12.9008C1.59922 13.3782 1.78886 13.836 2.12643 14.1736C2.46399 14.5111 2.92183 14.7008 3.39922 14.7008H10.5992C11.0766 14.7008 11.5344 14.5111 11.872 14.1736C12.2096 13.836 12.3992 13.3782 12.3992 12.9008V3.90078C12.6379 3.90078 12.8668 3.80596 13.0356 3.63718C13.2044 3.46839 13.2992 3.23948 13.2992 3.00078C13.2992 2.76209 13.2044 2.53317 13.0356 2.36439C12.8668 2.1956 12.6379 2.10078 12.3992 2.10078H9.35542L8.70382 0.798481C8.62913 0.649009 8.5143 0.523281 8.37219 0.435378C8.23009 0.347476 8.06631 0.30087 7.89922 0.300781H6.09922ZM4.29922 5.70078C4.29922 5.46209 4.39404 5.23317 4.56282 5.06439C4.73161 4.8956 4.96052 4.80078 5.19922 4.80078C5.43791 4.80078 5.66683 4.8956 5.83561 5.06439C6.0044 5.23317 6.09922 5.46209 6.09922 5.70078V11.1008C6.09922 11.3395 6.0044 11.5684 5.83561 11.7372C5.66683 11.906 5.43791 12.0008 5.19922 12.0008C4.96052 12.0008 4.73161 11.906 4.56282 11.7372C4.39404 11.5684 4.29922 11.3395 4.29922 11.1008V5.70078ZM8.79922 4.80078C8.56052 4.80078 8.33161 4.8956 8.16282 5.06439C7.99404 5.23317 7.89922 5.46209 7.89922 5.70078V11.1008C7.89922 11.3395 7.99404 11.5684 8.16282 11.7372C8.33161 11.906 8.56052 12.0008 8.79922 12.0008C9.03791 12.0008 9.26683 11.906 9.43561 11.7372C9.6044 11.5684 9.69922 11.3395 9.69922 11.1008V5.70078C9.69922 5.46209 9.6044 5.23317 9.43561 5.06439C9.26683 4.8956 9.03791 4.80078 8.79922 4.80078Z"
                      ></path>
                    </svg>
                    Delete
                  </Dropdown.Item>
                </Dropdown>
              </div>
            </div>
            <div class="flex items-center justify-center py-6">
              <Pagination
                currentPage={{ currentPage }}
                layout="table"
                onPageChange={{ (page) => setCurrentPage(page) }}
                totalPages={{ 100 }}
                previousLabel="Prev"
                theme={{ {
                  layout: {
                    table: {
                      base: twMerge(
                        theme.pagination.layout.table.base,
                        "text-xs",
                      ),
                     }},
                  },
                  pages: {{ 
                    next: {
                      base: twMerge(
                        theme.pagination.pages.next.base,
                        "w-20 py-1.5 text-sm",
                      ),
                     }},
                    previous: {{ 
                      base: twMerge(
                        theme.pagination.pages.previous.base,
                        "w-20 py-1.5 text-sm",
                      ),
                     }},
                  },
                }}
              />
            </div>
          </div>
        </div>
      </div>
      <Modal
        onClose={{ setShowUpdateModal(false) }}
        popup
        show={{ isShowUpdateModal }}
        size="3xl"
      >
        <Modal.Body class="relative rounded-lg bg-white p-4 shadow dark:bg-gray-800 sm:p-5">
          <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Update Product
            </h3>
            <button
              @click="{ setShowUpdateModal(false) "}
              class="absolute right-5 top-[18px] ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                aria-hidden
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
              >
                <path
                  fillRule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clipRule="evenodd"
                ></path>
              </svg>
              <span class="sr-only">Close modal</span>
            </button>
          </div>
          <form action="#">
            <div class="mb-4 grid gap-4 sm:grid-cols-2">
              <div>
                <Label htmlFor="name" class="mb-2 block">
                  Product Name
                </Label>
                <TextInput
                  defaultValue="Apple iPad 5th Gen Wi-Fi"
                  id="name"
                  name="name"
                  placeholder="Ex. Apple iMac 27&ldquo;"
                  required
                ></TextInput>
              </div>
              <div>
                <Label htmlFor="category" class="mb-2 block">
                  Category
                </Label>
                <Select id="category" name="category">
                  <option selected>Electronics</option>
                  <option value="TV">TV/Monitors</option>
                  <option value="PC">PC</option>
                  <option value="GA">Gaming/Console</option>
                  <option value="PH">Phones</option>
                </Select>
              </div>
              <div>
                <Label htmlFor="brand" class="mb-2 block">
                  Brand
                </Label>
                <TextInput
                  defaultValue="Tesla"
                  id="brand"
                  name="brand"
                  placeholder="Ex. Apple"
                  required
                ></TextInput>
              </div>
              <div>
                <Label htmlFor="price" class="mb-2 block">
                  Price
                </Label>
                <TextInput
                  defaultValue="259"
                  id="price"
                  name="price"
                  placeholder="$2999"
                  required
                  type="number"
                ></TextInput>
              </div>
              <div class="grid gap-4 sm:col-span-2 sm:grid-cols-4 md:gap-6">
                <div>
                  <Label htmlFor="weight" class="mb-2 block">
                    Item weight (kg)
                  </Label>
                  <TextInput
                    defaultValue="32"
                    id="weight"
                    name="weight"
                    placeholder="Ex. 12"
                    required
                    type="number"
                  ></TextInput>
                </div>
                <div>
                  <Label htmlFor="length" class="mb-2 block">
                    Lenght (cm)
                  </Label>
                  <TextInput
                    defaultValue="126"
                    id="length"
                    name="length"
                    placeholder="Ex. 105"
                    required
                    type="number"
                  ></TextInput>
                </div>
                <div>
                  <Label htmlFor="breadth" class="mb-2 block">
                    Breadth (cm)
                  </Label>
                  <TextInput
                    defaultValue="121"
                    id="breadth"
                    name="breadth"
                    placeholder="Ex. 15"
                    required
                    type="number"
                  ></TextInput>
                </div>
                <div>
                  <Label htmlFor="width" class="mb-2 block">
                    Width (cm)
                  </Label>
                  <TextInput
                    defaultValue="953"
                    id="width"
                    name="width"
                    placeholder="Ex. 23"
                    required
                    type="number"
                  ></TextInput>
                </div>
              </div>
              <div class="sm:col-span-2">
                <Label htmlFor="description" class="mb-2 block">
                  Description
                </Label>
                <Textarea
                  id="description"
                  name="description"
                  rows={{ 4 }}
                  placeholder="Write your description..."
                >
                  Standard glass, 3.8GHz 8-core 10th-generation Intel Core i7
                  processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz DDR4 memory,
                  Radeon Pro 5500 XT with 8GB of GDDR6 memory, 256GB SSD
                  storage, Gigabit Ethernet, Magic Mouse 2, Magic Keyboard - US
                </Textarea>
              </div>
            </div>
            <div class="mb-4 space-y-4 sm:flex sm:space-y-0">
              <div class="mr-4 flex items-center">
                <Checkbox id="inline-checkbox" name="sellingType" ></Checkbox>
                <Label
                  htmlFor="inline-checkbox"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  In-store only
                </Label>
              </div>
              <div class="mr-4 flex items-center">
                <Checkbox
                  id="inline-2-checkbox"
                  name="sellingType"
                  class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                ></Checkbox>
                <Label
                  htmlFor="inline-2-checkbox"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Online selling only
                </Label>
              </div>
              <div class="mr-4 flex items-center">
                <Checkbox
                  defaultChecked
                  id="inline-checked-checkbox"
                  name="sellingType"
                ></Checkbox>
                <Label
                  htmlFor="inline-checked-checkbox"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Both in-store and online
                </Label>
              </div>
            </div>
            <div class="mb-4">
              <span class="mb-2 block dark:text-white">Product Images</span>
              <div class="mb-4 grid grid-cols-3 gap-4 sm:grid-cols-4">
                <div class="relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-36">
                  <img
                    alt="iMac Side"
                    src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                  ></img>
                  <button
                    type="button"
                    class="absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400"
                  >
                    <svg
                      aria-hidden
                      class="h-5 w-5"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                    <span class="sr-only">Delete image</span>
                  </button>
                </div>
                <div class="relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-36">
                  <img
                    alt="iMac Front"
                    src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                  ></img>
                  <button
                    type="button"
                    class="absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400"
                  >
                    <svg
                      aria-hidden
                      class="h-5 w-5"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                    <span class="sr-only">Delete image</span>
                  </button>
                </div>
                <div class="relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-36">
                  <img
                    alt="iMac Back"
                    src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                  ></img>
                  <button
                    type="button"
                    class="absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400"
                  >
                    <svg
                      aria-hidden
                      class="h-5 w-5"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                    <span class="sr-only">Delete image</span>
                  </button>
                </div>
                <div class="relative rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-36">
                  <img
                    alt="iMac Back"
                    src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                  ></img>
                  <button
                    type="button"
                    class="absolute bottom-1 left-1 text-red-600 hover:text-red-500 dark:text-red-500 dark:hover:text-red-400"
                  >
                    <svg
                      aria-hidden
                      class="h-5 w-5"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                    <span class="sr-only">Delete image</span>
                  </button>
                </div>
              </div>
              <div class="flex w-full items-center justify-center">
                <Label
                  htmlFor="dropzone-file"
                  class="flex h-64 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-600"
                >
                  <div class="flex flex-col items-center justify-center pb-6 pt-5">
                    <svg
                      aria-hidden
                      class="mb-3 h-10 w-10 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                      ></path>
                    </svg>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                      <span class="font-semibold">Click to upload</span> or
                      drag and drop
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      SVG, PNG, JPG or GIF (MAX. 800x400px)
                    </p>
                  </div>
                  <input id="dropzone-file" type="file" class="hidden" ></input>
                </Label>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <Button size="lg" type="submit" class="[&>span]:text-sm">
                Update product
              </Button>
              <Button
                color="failure"
                outline
                size="lg"
                class="enabled:hover:bg-red-600 [&>span]:border-red-600 [&>span]:text-sm [&>span]:text-red-600"
              >
                <svg
                  class="-ml-1 mr-1 h-5 w-5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    fillRule="evenodd"
                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                    clipRule="evenodd"
                  ></path>
                </svg>
                Delete
              </Button>
            </div>
          </form>
        </Modal.Body>
      </Modal>
      <Modal
        onClose={{ setShowReadModal(false) }}
        popup
        size="3xl"
        show={{ isShowReadModal }}
      >
        <Modal.Body class="relative rounded-lg bg-white p-4 shadow dark:bg-gray-800 sm:p-5">
          <div class="mb-4 flex justify-between rounded-t sm:mb-5">
            <div class="text-lg text-gray-900 dark:text-white md:text-xl">
              <h3 class="font-semibold ">Apple iMac 27”</h3>
              <p class="font-bold">$2999</p>
            </div>
            <div>
              <button
                @click="{ setShowReadModal(false) "}
                class="absolute right-5 top-[18px] ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg
                  aria-hidden
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                >
                  <path
                    fillRule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clipRule="evenodd"
                  ></path>
                </svg>
                <span class="sr-only">Close modal</span>
              </button>
            </div>
          </div>
          <div class="mb-4 grid grid-cols-3 gap-4 sm:mb-5 sm:grid-cols-4">
            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700 md:h-36 md:w-36">
              <img
                alt="iMac Side"
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
              ></img>
            </div>
            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700 md:h-36 md:w-36">
              <img
                alt="iMac Front"
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
              ></img>
            </div>
            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700 md:h-36 md:w-36">
              <img
                alt="iMac Back"
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
              ></img>
            </div>
            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700 md:h-36 md:w-36">
              <img
                alt="iMac Back"
                src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
              ></img>
            </div>
          </div>
          <dl class="sm:mb-5">
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Details
            </dt>
            <dd class="mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">
              Standard glass ,3.8GHz 8-core 10th-generation Intel Core i7
              processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz DDR4 memory,
              Radeon Pro 5500 XT with 8GB of GDDR6 memory, 256GB SSD storage,
              Gigabit Ethernet, Magic Mouse 2, Magic Keyboard - US.
            </dd>
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Colors
            </dt>
            <dd class="mb-4 flex items-center space-x-2 text-gray-500 dark:text-gray-400">
              <div class="h-6 w-6 rounded-full bg-purple-600"></div>
              <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
              <div class="h-6 w-6 rounded-full bg-primary-600"></div>
              <div class="h-6 w-6 rounded-full bg-pink-400"></div>
              <div class="h-6 w-6 rounded-full bg-teal-300"></div>
              <div class="h-6 w-6 rounded-full bg-green-300"></div>
            </dd>
          </dl>
          <dl class="mb-4 grid grid-cols-2 gap-4 sm:mb-5 sm:grid-cols-3">
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
              <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
                Sold by
              </dt>
              <dd class="text-gray-500 dark:text-gray-400">Flowbite</dd>
            </div>
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
              <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
                Ships from
              </dt>
              <dd class="text-gray-500 dark:text-gray-400">Flowbite</dd>
            </div>
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
              <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
                Product State
              </dt>
              <dd class="text-gray-500 dark:text-gray-400">
                <Badge class="inline-flex [&>span]:flex [&>span]:items-center">
                  <svg
                    aria-hidden
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    class="mr-1 h-3 w-3"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                  </svg>
                  New
                </Badge>
              </dd>
            </div>
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
              <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
                Shipping
              </dt>
              <dd class="flex items-center text-gray-500 dark:text-gray-400">
                <svg
                  class="mr-1.5 h-4 w-4"
                  aria-hidden="true"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    fillRule="evenodd"
                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                    clipRule="evenodd"
                  ></path>
                </svg>
                Worldwide
              </dd>
            </div>
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
              <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
                Dimensions (cm)
              </dt>
              <dd class="text-gray-500 dark:text-gray-400">
                105 x 15 x 23
              </dd>
            </div>
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
              <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
                Item weight
              </dt>
              <dd class="text-gray-500 dark:text-gray-400">12kg</dd>
            </div>
          </dl>
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 sm:space-x-4">
              <Button size="lg" class="[&>span]:text-sm">
                <svg
                  aria-hidden
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                  class="-ml-1 mr-1 h-5 w-5"
                >
                  <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                  <path
                    fillRule="evenodd"
                    d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                    clipRule="evenodd"
                  ></path>
                </svg>
                Edit
              </Button>
              <Button
                color="gray"
                outline
                size="lg"
                class="dark:border-gray-600 [&>span]:text-sm dark:[&>span]:bg-gray-800 dark:[&>span]:text-gray-400"
              >
                Preview
              </Button>
            </div>
            <Button
              color="failure"
              size="lg"
              class="inline-flex [&>span]:text-sm"
            >
              <svg
                aria-hidden
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
                class="-ml-1 mr-1.5 h-5 w-5"
              >
                <path
                  fillRule="evenodd"
                  d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                  clipRule="evenodd"
                ></path>
              </svg>
              Delete
            </Button>
          </div>
        </Modal.Body>
      </Modal>
      <Modal
        onClose={{ setShowDeleteModal(false) }}
        popup
        size="md"
        show={{ isShowDeleteModal }}
      >
        <Modal.Body class="relative rounded-lg bg-white p-4 text-center shadow dark:bg-gray-800 sm:p-5">
          <button
            @click="{ setShowDeleteModal(false) "}
            class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg
              aria-hidden
              fill="currentColor"
              viewBox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
            >
              <path
                fillRule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clipRule="evenodd"
              ></path>
            </svg>
            <span class="sr-only">Close modal</span>
          </button>
          <svg
            aria-hidden
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
            class="mx-auto mb-3.5 h-11 w-11 text-gray-400 dark:text-gray-500"
          >
            <path
              fillRule="evenodd"
              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
              clipRule="evenodd"
            ></path>
          </svg>
          <p class="mb-4 text-gray-500 dark:text-gray-300">
            Are you sure you want to delete this item?
          </p>
          <div class="flex items-center justify-center space-x-4">
            <Button
              color="gray"
              @click="{ setShowDeleteModal(false) "}
              outline
              class="hover:text-gray-900 focus:ring-blue-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600 [&>span]:text-gray-500 dark:[&>span]:bg-gray-700 dark:[&>span]:text-gray-300"
            >
              No, cancel
            </Button>
            <Button color="failure" type="submit">
              Yes, I'm sure
            </Button>
          </div>
        </Modal.Body>
      </Modal>
      <Modal
        onClose={{ setShowCreateModal(false) }}
        popup
        show={{ isShowCreateModal }}
      >
        <Modal.Body class="relative rounded-lg bg-white p-4 shadow dark:bg-gray-800 sm:p-5">
          <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Add Product
            </h3>
            <button
              @click="{ setShowCreateModal(false) "}
              class="absolute right-5 top-[18px] ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <HiX class="h-5 w-5" ></HiX>
              <span class="sr-only">Close modal</span>
            </button>
          </div>
          <form action="#">
            <div class="mb-4 grid gap-4 sm:grid-cols-2">
              <div>
                <Label
                  htmlFor="name"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Name
                </Label>
                <TextInput
                  id="name"
                  name="name"
                  placeholder="Type product name"
                  required
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="brand"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Brand
                </Label>
                <TextInput
                  id="brand"
                  name="brand"
                  placeholder="Product brand"
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="price"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Price
                </Label>
                <TextInput
                  id="price"
                  name="price"
                  placeholder="$2999"
                  type="number"
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="category"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Category
                </Label>
                <Select id="category">
                  <option selected>Select category</option>
                  <option value="TV">TV/Monitors</option>
                  <option value="PC">PC</option>
                  <option value="GA">Gaming/Console</option>
                  <option value="PH">Phones</option>
                </Select>
              </div>
              <div class="sm:col-span-2">
                <Label
                  htmlFor="description"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Description
                </Label>
                <Textarea
                  id="description"
                  name="description"
                  placeholder="Write product description here"
                  rows={{ 4 }}
                ></Textarea>
              </div>
            </div>
            <Button size="lg" class="[&>span]:text-sm">
              <HiPlus class="-ml-1 mr-2 h-4 w-4" ></HiPlus>
              Add new product
            </Button>
          </form>
        </Modal.Body>
      </Modal>
    </section>
  
@endsection