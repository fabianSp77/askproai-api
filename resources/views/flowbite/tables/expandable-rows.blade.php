@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    <section class="bg-gray-50 py-3 dark:bg-gray-900 sm:py-5">
      <div class="mx-auto max-w-screen-2xl px-4 lg:px-12">
        <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
          <div class="flex flex-col items-center justify-between space-y-3 border-b p-4 dark:border-gray-700 md:flex-row md:space-x-4 md:space-y-0">
            <div class="flex w-full items-center space-x-3">
              <h5 class="font-semibold dark:text-white">
                Flowbite Products
              </h5>
              <div class="font-medium text-gray-400">6,560 results</div>
              <Tooltip content="Showing 1-10 of 6,560 results">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4 text-gray-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden
                >
                  <path
                    fillRule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clipRule="evenodd"
                  ></path>
                </svg>
                <span class="sr-only">More info</span>
              </Tooltip>
            </div>
            <div class="flex w-full flex-row items-center justify-end space-x-3 md:w-fit">
              <Button class="w-full whitespace-nowrap">
                <svg
                  class="mr-2 h-3.5 w-3.5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-hidden
                >
                  <path
                    clipRule="evenodd"
                    fillRule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                  ></path>
                </svg>
                Add new product
              </Button>
              <Button color="gray" class="w-full whitespace-nowrap">
                <svg
                  class="mr-2 h-3 w-3"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="currentColor"
                  stroke="currentColor"
                  viewBox="0 0 12 13"
                  aria-hidden
                >
                  <path d="M1 2V1h10v3H1V2Zm0 4h5v6H1V6Zm8 0h2v6H9V6Z" ></path>
                </svg>
                Manage Columns
              </Button>
            </div>
          </div>
          <div class="flex flex-col-reverse items-start justify-between border-b p-4 dark:border-gray-700 md:flex-row md:items-center md:space-x-4">
            <div class="mt-3 md:mt-0">
              <Dropdown
                color="gray"
                label={{ 
                  
                    <svg
                      class="-ml-1 mr-1.5 h-5 w-5"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                      aria-hidden
                    >
                      <path
                        clipRule="evenodd"
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      ></path>
                    </svg>
                    Actions
                  
                 }}
                theme={{ {
                  arrowIcon: "hidden",
                  floating: {
                    base: twMerge(theme.dropdown.floating.base, "w-40"),
                   }},
                }}
              >
                <Dropdown.Item>Mass Edit</Dropdown.Item>
                <Dropdown.Divider ></Dropdown>
                <Dropdown.Item>Delete All</Dropdown.Item>
              </Dropdown>
            </div>
            <div class="grid w-full grid-cols-1 md:grid-cols-4 md:gap-4 lg:w-2/3">
              <div class="w-full">
                <Label htmlFor="brand" class="sr-only">
                  Brand
                </Label>
                <Select
                  id="brand"
                  name="brand"
                  theme={{ {
                    field: {
                      select: {
                        base: "peer block w-full appearance-none border-0 border-b-2 border-gray-200 focus:border-blue-600 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white dark:focus:border-blue-500",
                        colors: {
                          gray: "bg-transparent text-gray-500",
                         }},
                        sizes: {{ 
                          md: "px-0 py-2.5 text-sm",
                         }},
                        withAddon: {{ 
                          off: "rounded-none",
                         }},
                      },
                    },
                  }}
                >
                  <option selected>Brand</option>
                  <option value="purple">Samsung</option>
                  <option value="primary">Apple</option>
                  <option value="pink">Pink</option>
                  <option value="green">Green</option>
                </Select>
              </div>
              <div class="w-full">
                <Label htmlFor="price" class="sr-only">
                  Price
                </Label>
                <Select
                  id="price"
                  name="price"
                  theme={{ {
                    field: {
                      select: {
                        base: "peer block w-full appearance-none border-0 border-b-2 border-gray-200 focus:border-blue-600 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white dark:focus:border-blue-500",
                        colors: {
                          gray: "bg-transparent text-gray-500",
                         }},
                        sizes: {{ 
                          md: "px-0 py-2.5 text-sm",
                         }},
                        withAddon: {{ 
                          off: "rounded-none",
                         }},
                      },
                    },
                  }}
                >
                  <option selected>Price</option>
                  <option value="below-100">$ 1-100</option>
                  <option value="below-500">$ 101-500</option>
                  <option value="below-1000">$ 501-1000</option>
                  <option value="over-1000">$ 1001+</option>
                </Select>
              </div>
              <div class="w-full">
                <Label htmlFor="category" class="sr-only">
                  Category
                </Label>
                <Select
                  id="category"
                  name="category"
                  theme={{ {
                    field: {
                      select: {
                        base: "peer block w-full appearance-none border-0 border-b-2 border-gray-200 focus:border-blue-600 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white dark:focus:border-blue-500",
                        colors: {
                          gray: "bg-transparent text-gray-500",
                         }},
                        sizes: {{ 
                          md: "px-0 py-2.5 text-sm",
                         }},
                        withAddon: {{ 
                          off: "rounded-none",
                         }},
                      },
                    },
                  }}
                >
                  <option selected>Category</option>
                  <option value="pc">PC</option>
                  <option value="phone">Phone</option>
                  <option value="tablet">Tablet</option>
                  <option value="console">Gaming/Console</option>
                </Select>
              </div>
              <div class="w-full">
                <Label htmlFor="color" class="sr-only">
                  Color
                </Label>
                <Select
                  id="color"
                  name="color"
                  theme={{ {
                    field: {
                      select: {
                        base: "peer block w-full appearance-none border-0 border-b-2 border-gray-200 focus:border-blue-600 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white dark:focus:border-blue-500",
                        colors: {
                          gray: "bg-transparent text-gray-500",
                         }},
                        sizes: {{ 
                          md: "px-0 py-2.5 text-sm",
                         }},
                        withAddon: {{ 
                          off: "rounded-none",
                         }},
                      },
                    },
                  }}
                >
                  <option selected>Color</option>
                  <option value="purple">Purple</option>
                  <option value="primary">primary</option>
                  <option value="pink">Pink</option>
                  <option value="green">Green</option>
                </Select>
              </div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <Table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
              <Table.Head class="bg-gray-50 text-xs uppercase dark:bg-gray-700">
                <Table.HeadCell scope="col" class="p-4">
                  <div class="flex items-center">
                    <Checkbox
                      id="checkbox-all"
                      name="checkbox-all"
                      class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    ></Checkbox>
                    <Label htmlFor="checkbox-all" class="sr-only">
                      Select all products
                    </Label>
                  </div>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  <span class="sr-only">Expand/Collapse Row</span>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-56 px-4 py-3">
                  Product
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-40 px-4 py-3">
                  Category
                  <svg
                    class="ml-1 inline-block h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    ></path>
                  </svg>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-24 px-4 py-3">
                  Brand
                  <svg
                    class="ml-1 inline-block h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    ></path>
                  </svg>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-24 px-4 py-3">
                  Price
                  <svg
                    class="ml-1 inline-block h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    ></path>
                  </svg>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-24 px-4 py-3">
                  Stock
                  <svg
                    class="ml-1 inline-block h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    ></path>
                  </svg>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-48 px-4 py-3">
                  Total Sales
                  <svg
                    class="ml-1 inline-block h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    ></path>
                  </svg>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-28 px-4 py-3">
                  Status
                  <svg
                    class="ml-1 inline-block h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                  >
                    <path
                      clipRule="evenodd"
                      fillRule="evenodd"
                      d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    ></path>
                  </svg>
                </Table.HeadCell>
              </Table.Head>
              <Table.Body data-accordion="table-column">
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-0"
                  @click="{ document
                      .querySelector("#table-column-body-0")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-0"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Apple iMac 27&#34;
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">PC</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Apple
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $2999
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    200
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    245
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-0"
                  aria-labelledby="table-column-header-0"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-1"
                  @click="{ document
                      .querySelector("#table-column-body-1")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-1"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Apple iMac 20&quot;
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">PC</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Apple
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $1499
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    1237
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    2000
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-1"
                  aria-labelledby="table-column-header-1"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-2"
                  @click="{ document
                      .querySelector("#table-column-body-2")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-2"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Apple iPhone 14
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Phone</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Apple
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $999
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    300
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    466
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-2"
                  aria-labelledby="table-column-header-2"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-3"
                  @click="{ document
                      .querySelector("#table-column-body-3")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-3"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Apple iPad Air
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Tablet</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Apple
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $1199
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    4576
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    90
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-3"
                  aria-labelledby="table-column-header-3"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-4"
                  @click="{ document
                      .querySelector("#table-column-body-4")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-4"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Xbox Series S
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Gaming/Console</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Microsoft
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $299
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    56
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    3087
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-4"
                  aria-labelledby="table-column-header-4"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-5"
                  @click="{ document
                      .querySelector("#table-column-body-5")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-5"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    PlayStation 5
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Gaming/Console</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Sony
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $799
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    78
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    2999
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-5"
                  aria-labelledby="table-column-header-5"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-6"
                  @click="{ document
                      .querySelector("#table-column-body-6")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-6"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Xbox Series X
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Gaming/Console</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Microsoft
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $699
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    200
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    1870
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-6"
                  aria-labelledby="table-column-header-6"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-7"
                  @click="{ document
                      .querySelector("#table-column-body-7")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-7"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Apple Watch SE
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Watch</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Apple
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $399
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    657
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    5067
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-7"
                  aria-labelledby="table-column-header-7"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-8"
                  @click="{ document
                      .querySelector("#table-column-body-8")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-8"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    NIKON D850
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Photo</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    Nikon
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $599
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    465
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    1870
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-8"
                  aria-labelledby="table-column-header-8"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="cursor-pointer border-b transition hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-700"
                  id="table-column-header-9"
                  @click="{ document
                      .querySelector("#table-column-body-9")
                      ?.classList.toggle("hidden")
                   "}
                  aria-controls="table-column-body-9"
                >
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                        @click="{ (event) => event.stopPropagation() "}
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                      />
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="w-4 p-3">
                    <svg
                      data-accordion-icon=""
                      class="h-6 w-6 shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      ></path>
                    </svg>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt=""
                      class="mr-3 h-8 w-auto"
                    ></img>
                    Monitor BenQ EX2710Q
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">TV/Monitor</Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    BenQ
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    $499
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    354
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                    76
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-3">
                    <Badge color="success" class="w-fit">
                      Active
                    </Badge>
                  </Table.Cell>
                </Table.Row>
                <Table.Row
                  class="hidden w-full flex-1 overflow-x-auto"
                  id="table-column-body-9"
                  aria-labelledby="table-column-header-9"
                >
                  <Table.Cell
                    class="border-b p-4 dark:border-gray-700"
                    colSpan={{ 9 }}
                  >
                    <div class="mb-4 grid grid-cols-4 gap-4">
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                          alt="iMac Front"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-side-image.png"
                          alt="iMac Side"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                      <div class="relative flex h-32 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-700 sm:h-36 sm:w-full">
                        <img
                          src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-back-image.png"
                          alt="iMac Back"
                          class="h-full w-auto"
                        ></img>
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                        Details
                      </h6>
                      <div class="max-w-screen-md text-base text-gray-500 dark:text-gray-400">
                        Standard glass, 3.8GHz 8-core 10th-generation Intel Core
                        i7 processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz
                        DDR4 memory, Radeon Pro 5500 XT with 8GB of GDDR6
                        memory, 256GB SSD storage, Gigabit Ethernet, Magic Mouse
                        2, Magic Keyboard - US.
                      </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4">
                      <div class="relative flex flex-col items-start justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Product State
                        </h6>
                        <Badge class="flex">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="-mt-0.5 mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" ></path>
                          </svg>
                          New
                        </Badge>
                      </div>
                      <div class="relative flex flex-col justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Shipping
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          Worldwide
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Colors
                        </h6>
                        <div class="flex items-center space-x-2">
                          <div class="h-6 w-6 rounded-full bg-purple-600"></div>
                          <div class="h-6 w-6 rounded-full bg-indigo-400"></div>
                          <div class="h-6 w-6 rounded-full bg-primary-600"></div>
                          <div class="h-6 w-6 rounded-full bg-pink-400"></div>
                          <div class="h-6 w-6 rounded-full bg-teal-300"></div>
                          <div class="h-6 w-6 rounded-full bg-green-300"></div>
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Brand
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Apple
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Sold by
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Ships from
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          Flowbite
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Dimensions (cm)
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          105 x 15 x 23
                        </div>
                      </div>
                      <div class="relative rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                        <h6 class="mb-2 text-base font-medium leading-none text-gray-900 dark:text-white">
                          Item weight
                        </h6>
                        <div class="flex items-center text-gray-500 dark:text-gray-400">
                          12kg
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-3">
                      <Button>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
                        >
                          <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" ></path>
                          <path
                            fillRule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Edit
                      </Button>
                      <Button color="gray">Preview</Button>
                      <Button color="failure">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden
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
                  </Table.Cell>
                </Table.Row>
              </Table.Body>
            </Table>
          </div>
          <div
            class="flex flex-col items-start justify-between space-y-3 px-4 pb-4 pt-3 md:flex-row md:items-center md:space-y-0"
            aria-label="Table navigation"
          >
            <div class="flex items-center space-x-5 text-xs">
              <div>
                <div class="mb-1 text-gray-500 dark:text-gray-400">
                  Purchase price
                </div>
                <div class="font-medium dark:text-white">$ 3,567,890</div>
              </div>
              <div>
                <div class="mb-1 text-gray-500 dark:text-gray-400">
                  Total selling price
                </div>
                <div class="font-medium dark:text-white">$ 8,489,400</div>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <button class="flex items-center rounded-lg py-1.5 text-center text-sm font-medium text-primary-700 hover:text-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:text-primary-500 dark:hover:text-primary-600 dark:focus:ring-primary-800">
                Print barcodes
              </button>
              <button class="flex items-center rounded-lg py-1.5 text-center text-sm font-medium text-primary-700 hover:text-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:text-primary-500 dark:hover:text-primary-600 dark:focus:ring-primary-800">
                Duplicate
              </button>
              <Button size="sm" class="[&_span]:text-xs">
                Export CSV
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  
@endsection