@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    <section class="bg-gray-50 p-3 dark:bg-gray-900 sm:p-5">
      <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
        <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
          <div class="flex flex-col items-center justify-between space-y-3 p-4 md:flex-row md:space-x-4 md:space-y-0">
            <div class="w-full md:w-1/2">
              <form class="flex items-center">
                <Label htmlFor="simple-search" class="sr-only">
                  Search
                </Label>
                <TextInput
                  icon={{ (
                    <svg
                      aria-hidden
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
                  ) }}
                  id="simple-search"
                  name="simple-search"
                  placeholder="Search"
                  required
                  type="search"
                  class="w-full [&_input]:py-2"
                />
              </form>
            </div>
            <div class="flex w-full shrink-0 flex-col items-stretch justify-end space-y-2 md:w-auto md:flex-row md:items-center md:space-x-3 md:space-y-0">
              <Button>
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
                Add product
              </Button>
              <div class="flex w-full items-center space-x-3 md:w-auto">
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
                      base: twMerge(theme.dropdown.floating.base, "w-48"),
                      target: "w-full",
                     }},
                  }}
                >
                  <Dropdown.Item>Mass Edit</Dropdown.Item>
                  <Dropdown.Divider ></Dropdown>
                  <Dropdown.Item>Delete All</Dropdown.Item>
                </Dropdown>
                <Dropdown
                  color="gray"
                  label={{ 
                    
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden
                        class="mr-2 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fillRule="evenodd"
                          d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                          clipRule="evenodd"
                        ></path>
                      </svg>
                      <span>Filter</span>
                    
                   }}
                  theme={{ {
                    floating: {
                      base: twMerge(
                        theme.dropdown.floating.base,
                        "w-48 rounded-xl",
                      ),
                      target: "w-full",
                     }},
                  }}
                >
                  <div class="p-3">
                    <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
                      Choose brand
                    </h6>
                    <ul class="space-y-2 text-sm">
                      <li class="flex items-center">
                        <Checkbox id="apple" name="apple" ></Checkbox>
                        <Label
                          htmlFor="apple"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Apple (56)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="fitbit" name="fitbit" ></Checkbox>
                        <Label
                          htmlFor="fitbit"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Microsoft (16)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="razer" name="razer" ></Checkbox>
                        <Label
                          htmlFor="razer"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Razer (49)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="nikon" name="nikon" ></Checkbox>
                        <Label
                          htmlFor="nikon"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Nikon (12)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="benq" name="benq" ></Checkbox>
                        <Label
                          htmlFor="benq"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          BenQ (74)
                        </Label>
                      </li>
                    </ul>
                  </div>
                </Dropdown>
              </div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <Table
              theme={{ {
                root: {
                  wrapper: "static",
                 }},
              }}
              class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
            >
              <Table.Head class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Product name
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Category
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Brand
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Description
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Price
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  <span class="sr-only">Actions</span>
                </Table.HeadCell>
              </Table.Head>
              <Table.Body>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iMac 27&quot;
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">PC</Table.Cell>
                  <Table.Cell class="px-4 py-3">Apple</Table.Cell>
                  <Table.Cell class="px-4 py-3">300</Table.Cell>
                  <Table.Cell class="px-4 py-3">$2999</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iMac 20&quot;
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">PC</Table.Cell>
                  <Table.Cell class="px-4 py-3">Apple</Table.Cell>
                  <Table.Cell class="px-4 py-3">200</Table.Cell>
                  <Table.Cell class="px-4 py-3">$1499</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iPhone 14&quot;
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">PC</Table.Cell>
                  <Table.Cell class="px-4 py-3">Apple</Table.Cell>
                  <Table.Cell class="px-4 py-3">1237</Table.Cell>
                  <Table.Cell class="px-4 py-3">$2999</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iPad Air
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">PC</Table.Cell>
                  <Table.Cell class="px-4 py-3">Apple</Table.Cell>
                  <Table.Cell class="px-4 py-3">4578</Table.Cell>
                  <Table.Cell class="px-4 py-3">$1199</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Xbox Series S
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Gaming/Console</Table.Cell>
                  <Table.Cell class="px-4 py-3">Microsoft</Table.Cell>
                  <Table.Cell class="px-4 py-3">256</Table.Cell>
                  <Table.Cell class="px-4 py-3">$299</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    PlayStation 5
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Gaming/Console</Table.Cell>
                  <Table.Cell class="px-4 py-3">Sony</Table.Cell>
                  <Table.Cell class="px-4 py-3">78</Table.Cell>
                  <Table.Cell class="px-4 py-3">$799</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Xbox Series X
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Gaming/Console</Table.Cell>
                  <Table.Cell class="px-4 py-3">Microsoft</Table.Cell>
                  <Table.Cell class="px-4 py-3">200</Table.Cell>
                  <Table.Cell class="px-4 py-3">$699</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Apple Watch SE
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Watch</Table.Cell>
                  <Table.Cell class="px-4 py-3">Apple</Table.Cell>
                  <Table.Cell class="px-4 py-3">657</Table.Cell>
                  <Table.Cell class="px-4 py-3">$399</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    NIKON D850
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">Camera</Table.Cell>
                  <Table.Cell class="px-4 py-3">Nikon</Table.Cell>
                  <Table.Cell class="px-4 py-3">465</Table.Cell>
                  <Table.Cell class="px-4 py-3">$599</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    Monitor BenQ EX2710Q
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">TV/Monitor</Table.Cell>
                  <Table.Cell class="px-4 py-3">BenQ</Table.Cell>
                  <Table.Cell class="px-4 py-3">354</Table.Cell>
                  <Table.Cell class="px-4 py-3">$499</Table.Cell>
                  <Table.Cell class="flex items-center justify-end px-4 py-3">
                    <Dropdown
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
                      <Dropdown.Item>Show</Dropdown.Item>
                      <Dropdown.Item>Edit</Dropdown.Item>
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item>Delete</Dropdown.Item>
                    </Dropdown>
                  </Table.Cell>
                </Table.Row>
              </Table.Body>
            </Table>
          </div>
          <nav
            class="flex flex-col items-start justify-between space-y-3 p-4 md:flex-row md:items-center md:space-y-0"
            aria-label="Table navigation"
          >
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
              Showing&nbsp;
              <span class="font-semibold text-gray-900 dark:text-white">
                1-10
              </span>
              &nbsp;of&nbsp;
              <span class="font-semibold text-gray-900 dark:text-white">
                1000
              </span>
            </span>
            <Pagination
              currentPage={{ currentPage }}
              nextLabel=""
              onPageChange={{ (page) => setCurrentPage(page) }}
              previousLabel=""
              showIcons
              totalPages={{ 100 }}
              theme={{ {
                pages: {
                  base: twMerge(theme.pagination.pages.base, "mt-0"),
                  next: {
                    base: twMerge(
                      theme.pagination.pages.next.base,
                      "w-10 px-1.5 py-1.5",
                    ),
                    icon: "h-6 w-6",
                   }},
                  previous: {{ 
                    base: twMerge(
                      theme.pagination.pages.previous.base,
                      "w-10 px-1.5 py-1.5",
                    ),
                    icon: "h-6 w-6",
                   }},
                  selector: {{ 
                    base: twMerge(
                      theme.pagination.pages.selector.base,
                      "w-9 py-2 text-sm focus:border-blue-300",
                    ),
                   }},
                },
              }}
            />
          </nav>
        </div>
      </div>
    </section>
  
@endsection