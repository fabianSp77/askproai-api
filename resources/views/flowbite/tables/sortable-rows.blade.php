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
          <div class="mx-4 border-b dark:border-gray-700">
            <div class="flex items-center justify-between space-x-4 pt-3">
              <div class="flex flex-1 items-center space-x-3">
                <h5 class="font-semibold dark:text-white">All products</h5>
              </div>
              <div class="flex shrink-0 flex-row items-center justify-end space-x-3">
                <Button color="gray">View JSON</Button>
                <Button color="gray">
                  <svg
                    class="mr-2 h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth="2"
                    stroke="currentColor"
                    aria-hidden
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                    ></path>
                  </svg>
                  Export
                </Button>
              </div>
            </div>
            <div class="flex flex-col-reverse items-center justify-between py-3 md:flex-row md:space-x-4">
              <div class="flex w-full flex-col space-y-3 md:flex-row md:items-center md:space-y-0 lg:w-2/3">
                <form class="w-full flex-1 md:mr-4 md:max-w-sm">
                  <Label
                    htmlFor="default-search"
                    class="sr-only text-sm font-medium text-gray-900 dark:text-white"
                  >
                    Search
                  </Label>
                  <div class="relative">
                    <TextInput
                      icon={{ (
                        <HiSearch class="h-4 w-4 text-gray-500 dark:text-gray-400" ></HiSearch>
                      ) }}
                      id="default-search"
                      name="default-search"
                      placeholder="Search..."
                      required
                      type="search"
                      class="[&_input]:py-2"
                    />
                    <Button
                      type="submit"
                      class="absolute inset-y-0 right-0 rounded-l-none rounded-r-lg"
                    >
                      Search
                    </Button>
                  </div>
                </form>
                <div class="flex items-center space-x-4">
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
                            clipRule="evenodd"
                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                          ></path>
                        </svg>
                        Filter
                      
                     }}
                    theme={{ {
                      floating: {
                        base: twMerge(
                          theme.dropdown.floating.base,
                          "w-48 rounded-xl",
                        ),
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
                  <Dropdown
                    color="gray"
                    label={{ 
                      
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="mr-2 h-4 w-4 text-gray-400"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a.798.798 0 01-.517.608 7.45 7.45 0 00-.478.198.798.798 0 01-.796-.064l-.453-.324a1.875 1.875 0 00-2.416.2l-.243.243a1.875 1.875 0 00-.2 2.416l.324.453a.798.798 0 01.064.796 7.448 7.448 0 00-.198.478.798.798 0 01-.608.517l-.55.092a1.875 1.875 0 00-1.566 1.849v.344c0 .916.663 1.699 1.567 1.85l.549.091c.281.047.508.25.608.517.06.162.127.321.198.478a.798.798 0 01-.064.796l-.324.453a1.875 1.875 0 00.2 2.416l.243.243c.648.648 1.67.733 2.416.2l.453-.324a.798.798 0 01.796-.064c.157.071.316.137.478.198.267.1.47.327.517.608l.092.55c.15.903.932 1.566 1.849 1.566h.344c.916 0 1.699-.663 1.85-1.567l.091-.549a.798.798 0 01.517-.608 7.52 7.52 0 00.478-.198.798.798 0 01.796.064l.453.324a1.875 1.875 0 002.416-.2l.243-.243c.648-.648.733-1.67.2-2.416l-.324-.453a.798.798 0 01-.064-.796c.071-.157.137-.316.198-.478.1-.267.327-.47.608-.517l.55-.091a1.875 1.875 0 001.566-1.85v-.344c0-.916-.663-1.699-1.567-1.85l-.549-.091a.798.798 0 01-.608-.517 7.507 7.507 0 00-.198-.478.798.798 0 01.064-.796l.324-.453a1.875 1.875 0 00-.2-2.416l-.243-.243a1.875 1.875 0 00-2.416-.2l-.453.324a.798.798 0 01-.796.064 7.462 7.462 0 00-.478-.198.798.798 0 01-.517-.608l-.091-.55a1.875 1.875 0 00-1.85-1.566h-.344zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        Configurations
                      
                     }}
                    theme={{ {
                      arrowIcon: "hidden",
                      floating: {
                        content: twMerge(theme.dropdown.content, "w-48"),
                       }},
                    }}
                  >
                    <Dropdown.Item>By Category</Dropdown.Item>
                    <Dropdown.Item>By Brand</Dropdown.Item>
                    <Dropdown.Divider ></Dropdown>
                    <Dropdown.Item>Reset</Dropdown.Item>
                  </Dropdown>
                </div>
              </div>
              <div class="mb-3 flex w-full shrink-0 flex-col items-stretch justify-end md:mb-0 md:w-auto md:flex-row md:items-center md:space-x-3">
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
                  Add new product
                </Button>
              </div>
            </div>
          </div>
          <div class="mx-4 flex flex-wrap pb-3">
            <div class="mr-4 mt-3 hidden items-center text-sm font-medium text-gray-900 dark:text-white md:flex">
              Show only:
            </div>
            <div class="flex flex-wrap">
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="inline-radio" name="inline-radio-group" ></Radio>
                <Label
                  htmlFor="inline-radio"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  All
                </Label>
              </div>
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="inline-2-radio" name="inline-radio-group" ></Radio>
                <Label
                  htmlFor="inline-2-radio"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Active products
                </Label>
              </div>
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="inline-3-radio" name="inline-radio-group" ></Radio>
                <Label
                  htmlFor="inline-3-radio"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Pending products
                </Label>
              </div>
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="inline-4-radio" name="inline-radio-group" ></Radio>
                <Label
                  htmlFor="inline-4-radio"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Inactive products
                </Label>
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
                <Table.HeadCell scope="col" class="p-4">
                  <div class="flex items-center">
                    <Checkbox id="checkbox-all" name="checkbox-all" ></Checkbox>
                    <Label htmlFor="checkbox-all" class="sr-only">
                      Select all
                    </Label>
                  </div>
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
                <Table.HeadCell scope="col" class="min-w-28 px-4 py-3">
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
                <Table.HeadCell scope="col" class="min-w-28 px-4 py-3">
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
                <Table.HeadCell scope="col" class="px-4 py-3">
                  <span class="sr-only">Actions</span>
                </Table.HeadCell>
              </Table.Head>
              <Table.Body>
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt="iMac Front"
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
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/products/imac-front-image.png"
                      alt="iMac Front"
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
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/apple-iphone-14.png"
                      alt="iMac Front"
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
                    <Badge color="gray" class="w-fit">
                      Inactive
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/apple-ipad-air.png"
                      alt="iMac Front"
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
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/xbox-series-s.png"
                      alt="iMac Front"
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
                    <Badge color="warning" class="w-fit">
                      Pending
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/playstation-5.png"
                      alt="iMac Front"
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
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/xbox-series-x.png"
                      alt="iMac Front"
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
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/apple-watch-se.png"
                      alt="iMac Front"
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
                    <Badge color="gray" class="w-fit">
                      Inactive
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/nikon-d850.png"
                      alt="iMac Front"
                      class="mr-3 h-8 w-auto"
                    ></img>
                    NIKON 2850
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
                    <Badge color="warning" class="w-fit">
                      Pending
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-3">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this product
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="flex items-center whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white"
                  >
                    <img
                      src="https://flowbite.s3.amazonaws.com/blocks/application-ui/devices/benq-ex2710q.png"
                      alt="iMac Front"
                      class="mr-3 h-8 w-auto"
                    ></img>
                    BenQ EX2710Q
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
                  <Table.Cell class="px-4 py-3">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit user</span>
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