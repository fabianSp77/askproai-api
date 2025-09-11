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
              <Button @click="{ setShowCreateModal(true) "}>
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
                        "w-56 rounded-xl",
                      ),
                      target: "w-full",
                     }},
                  }}
                >
                  <div class="p-3">
                    <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
                      Category
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
                          Fitbit (56)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="dell" name="dell" ></Checkbox>
                        <Label
                          htmlFor="dell"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Dell (56)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox defaultChecked id="asus" name="asus" ></Checkbox>
                        <Label
                          htmlFor="asus"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Asus (97)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox
                          defaultChecked
                          id="logitech"
                          name="logitech"
                        ></Checkbox>
                        <Label
                          htmlFor="logitech"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Logitech (97)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox defaultChecked id="razer" name="razer" ></Checkbox>
                        <Label
                          htmlFor="razer"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          MSI (97)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox defaultChecked id="bosch" name="bosch" ></Checkbox>
                        <Label
                          htmlFor="bosch"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Bosch (176)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="sony" name="sony" ></Checkbox>
                        <Label
                          htmlFor="sony"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Sony (234)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox defaultChecked id="samsung" name="samsung" ></Checkbox>
                        <Label
                          htmlFor="samsung"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Samsung (76)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="canon" name="canon" ></Checkbox>
                        <Label
                          htmlFor="canon"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Canon (49)
                        </Label>
                      </li>
                      <li class="flex items-center">
                        <Checkbox id="microsoft" name="microsoft" ></Checkbox>
                        <Label
                          htmlFor="microsoft"
                          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                          Microsoft (45)
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
                  wrapper: "static whitespace-nowrap",
                 }},
              }}
              class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
            >
              <Table.Head class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <Table.HeadCell scope="col" class="px-5 py-4">
                  Product name
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-5 py-4">
                  Category
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-5 py-4">
                  Brand
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-5 py-4">
                  Description
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-5 py-4">
                  Price
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-5 py-4">
                  <span class="sr-only">Actions</span>
                </Table.HeadCell>
              </Table.Head>
              <Table.Body>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iMac 27&quot;
                  </Table.Cell>
                  <Table.Cell class="p-5">PC</Table.Cell>
                  <Table.Cell class="p-5">Apple</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$2999</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iMac 20&quot;
                  </Table.Cell>
                  <Table.Cell class="p-5">PC</Table.Cell>
                  <Table.Cell class="p-5">Apple</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$1499</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iPhone 14&quot;
                  </Table.Cell>
                  <Table.Cell class="p-5">PC</Table.Cell>
                  <Table.Cell class="p-5">Apple</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$2999</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Apple iPad Air
                  </Table.Cell>
                  <Table.Cell class="p-5">PC</Table.Cell>
                  <Table.Cell class="p-5">Apple</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$1199</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Xbox Series S
                  </Table.Cell>
                  <Table.Cell class="p-5">Gaming/Console</Table.Cell>
                  <Table.Cell class="p-5">Microsoft</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$299</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    PlayStation 5
                  </Table.Cell>
                  <Table.Cell class="p-5">Gaming/Console</Table.Cell>
                  <Table.Cell class="p-5">Sony</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$799</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Xbox Series X
                  </Table.Cell>
                  <Table.Cell class="p-5">Gaming/Console</Table.Cell>
                  <Table.Cell class="p-5">Microsoft</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$699</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Apple Watch SE
                  </Table.Cell>
                  <Table.Cell class="p-5">Watch</Table.Cell>
                  <Table.Cell class="p-5">Apple</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$399</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    NIKON D850
                  </Table.Cell>
                  <Table.Cell class="p-5">Camera</Table.Cell>
                  <Table.Cell class="p-5">Nikon</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$599</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
                <Table.Row class="border-b dark:border-gray-700">
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap p-5 font-medium text-gray-900 dark:text-white"
                  >
                    Monitor BenQ EX2710Q
                  </Table.Cell>
                  <Table.Cell class="p-5">TV/Monitor</Table.Cell>
                  <Table.Cell class="p-5">BenQ</Table.Cell>
                  <Table.Cell class="p-5">
                    What is a product description?
                  </Table.Cell>
                  <Table.Cell class="p-5">$499</Table.Cell>
                  <Table.Cell class="flex items-center justify-end p-5">
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
                      <Dropdown.Divider ></Dropdown>
                      <Dropdown.Item
                        @click="{ setShowDeleteModal(true) "}
                        class="text-red-600"
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
                  </Table.Cell>
                </Table.Row>
              </Table.Body>
            </Table>
          </div>
          <nav
            class="flex flex-col items-start justify-between space-y-3 p-5 md:flex-row md:items-center md:space-y-0"
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
      <Modal
        onClose={{ setShowUpdateModal(false) }}
        popup
        show={{ isShowUpdateModal }}
        size="2xl"
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
                  Name
                </Label>
                <TextInput
                  defaultValue="iPad Air Gen 5th Wi-Fi"
                  id="name"
                  name="name"
                  placeholder="Ex. Apple iMac 27&ldquo;"
                ></TextInput>
              </div>
              <div>
                <Label htmlFor="brand" class="mb-2 block">
                  Brand
                </Label>
                <TextInput
                  defaultValue="Google"
                  id="brand"
                  name="brand"
                  placeholder="Ex. Apple"
                ></TextInput>
              </div>
              <div>
                <Label htmlFor="price" class="mb-2 block">
                  Price
                </Label>
                <TextInput
                  defaultValue="399"
                  id="price"
                  name="price"
                  placeholder="$299"
                  type="number"
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
              <div class="sm:col-span-2">
                <Label htmlFor="description" class="mb-2 block">
                  Description
                </Label>
                <Textarea
                  id="description"
                  name="description"
                  placeholder="Write a description..."
                  rows={{ 5 }}
                >
                  Standard glass, 3.8GHz 8-core 10th-generation Intel Core i7
                  processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz DDR4 memory,
                  Radeon Pro 5500 XT with 8GB of GDDR6 memory, 256GB SSD
                  storage, Gigabit Ethernet, Magic Mouse 2, Magic Keyboard - US
                </Textarea>
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
                class="enabled:hover:bg-red-600 dark:bg-transparent [&>span]:border-red-600 [&>span]:text-sm [&>span]:text-red-600 dark:[&>span]:bg-transparent dark:[&>span]:text-red-500"
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
        size="xl"
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
          <dl>
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Details
            </dt>
            <dd class="mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">
              Standard glass, 3.8GHz 8-core 10th-generation Intel Core i7
              processor, Turbo Boost up to 5.0GHz, 16GB 2666MHz DDR4 memory,
              Radeon Pro 5500 XT with 8GB of GDDR6 memory, 256GB SSD storage,
              Gigabit Ethernet, Magic Mouse 2, Magic Keyboard - US.
            </dd>
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Category
            </dt>
            <dd class="mb-4 text-gray-500 dark:text-gray-400 sm:mb-5">
              Electronics/PC
            </dd>
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
                class="[&>span]:text-sm dark:[&>span]:bg-transparent [&>span]:dark:text-gray-400"
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