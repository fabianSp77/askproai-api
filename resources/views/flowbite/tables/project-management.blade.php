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
                <h5 class="font-semibold dark:text-white">All Tasks</h5>
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
                  >
                    <div class="p-3">
                      <h6 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        By status
                      </h6>
                      <ul class="space-y-2 text-sm">
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="in-progress"
                              name="in-progress"
                              class="mr-2"
                            ></Checkbox>
                            In progress
                          </Label>
                        </li>
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="in-review"
                              name="in-review"
                              class="mr-2"
                            ></Checkbox>
                            In review
                          </Label>
                        </li>
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="completed"
                              name="completed"
                              class="mr-2"
                            ></Checkbox>
                            Completed
                          </Label>
                        </li>
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="cancelled"
                              name="cancelled"
                              class="mr-2"
                            ></Checkbox>
                            Cancelled
                          </Label>
                        </li>
                      </ul>
                      <h6 class="mb-2 mt-4 text-sm font-medium text-gray-900 dark:text-white">
                        By number of users
                      </h6>
                      <ul class="space-y-2 text-sm">
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="people-1"
                              name="people-1"
                              class="mr-2"
                            ></Checkbox>
                            5-10 people
                          </Label>
                        </li>
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="people-2"
                              name="people-2"
                              class="mr-2"
                            ></Checkbox>
                            10+ people
                          </Label>
                        </li>
                        <li>
                          <Label class="flex w-full items-center rounded-md px-1.5 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                            <Checkbox
                              id="people-3"
                              name="people-3"
                              class="mr-2"
                            ></Checkbox>
                            More than 10 people
                          </Label>
                        </li>
                      </ul>
                      <a
                        href="#"
                        class="ml-1.5 mt-4 flex items-center text-sm font-medium text-primary-600 hover:underline dark:text-primary-500"
                      >
                        Apply to all projects
                      </a>
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
                <button
                  type="button"
                  class="flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                >
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
                  Add new task
                </button>
              </div>
            </div>
          </div>
          <div class="mx-4 flex flex-wrap pb-3">
            <div class="mr-4 mt-3 hidden items-center text-sm font-medium text-gray-900 dark:text-white md:flex">
              Show only:
            </div>
            <div class="flex flex-wrap">
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="all-tasks" name="show-only" ></Radio>
                <Label
                  htmlFor="all-tasks"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  All
                </Label>
              </div>
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="completed" name="show-only" ></Radio>
                <Label
                  htmlFor="completed"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Completed tasks
                </Label>
              </div>
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="in-progress" name="show-only" ></Radio>
                <Label
                  htmlFor="in-progress"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Tasks in progress
                </Label>
              </div>
              <div class="mr-4 mt-3 flex items-center">
                <Radio id="in-review" name="show-only" ></Radio>
                <Label
                  htmlFor="in-review"
                  class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                >
                  Tasks in review
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
                      Check all
                    </Label>
                  </div>
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Task
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Status
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Users
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="min-w-56 px-4 py-3">
                  Progress
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Preview
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Time Tracking
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  Due Date
                </Table.HeadCell>
                <Table.HeadCell scope="col" class="px-4 py-3">
                  <span class="sr-only">Actions</span>
                </Table.HeadCell>
              </Table.Head>
              <Table.Body>
                <Table.Row class="border-b hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Upload feed and Reels in Instagram
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge class="w-fit">In progress</Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar.Counter
                          total={{ 5 }}
                          class="ring-white dark:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        75%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress progress={{ 75 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      <span class="text-green-500">6:47</span>
                      /8:00
                      <button class="ml-3 rounded-md bg-orange-500 p-1 text-white hover:bg-orange-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    23 Nov 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Crossplatform analysis
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge color="success" class="w-fit">
                      Completed
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar.Counter
                          total={{ 2 }}
                          class="ring-white dark:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        100%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress color="green" progress={{ 100 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      7:00
                      <button class="ml-3 rounded-md bg-green-500 p-1 text-white hover:bg-green-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    03 Nov 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Product features analysis
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge class="w-fit">In progress</Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        50%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress progress={{ 50 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      <span class="text-green-500">3:25</span>
                      /8:00
                      <button class="ml-3 rounded-md bg-orange-500 p-1 text-white hover:bg-orange-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    Yesterday
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Create use story
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge color="success" class="w-fit">
                      Completed
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        100%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress color="green" progress={{ 75 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      8:00
                      <button class="ml-3 rounded-md bg-green-500 p-1 text-white hover:bg-green-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    23 Nov 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Users profile update
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge class="w-fit">In progress</Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar.Counter
                          total={{ 2 }}
                          class="ring-white dark:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        20%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress progress={{ 20 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      <span class="text-green-500">4:21</span>
                      /8:00
                      <button class="ml-3 rounded-md bg-orange-500 p-1 text-white hover:bg-orange-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    Yesterday
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    User flow update
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge color="success" class="w-fit">
                      Completed
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        100%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress color="green" progress={{ 100 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      7:00
                      <button class="ml-3 rounded-md bg-green-500 p-1 text-white hover:bg-green-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    23 Oct 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Update design system
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge color="warning" class="w-fit">
                      In review
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar.Counter
                          total={{ 5 }}
                          class="ring-white dark:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        100%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress color="yellow" progress={{ 100 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      7:00
                      <button class="ml-3 rounded-md bg-green-500 p-1 text-white hover:bg-green-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    02 Nov 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Create a new logo
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge color="success" class="w-fit">
                      Completed
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar.Counter
                          total={{ 2 }}
                          class="ring-white dark:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        100%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress color="green" progress={{ 100 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      5:00
                      <button class="ml-3 rounded-md bg-green-500 p-1 text-white hover:bg-green-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    30 Oct 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Screen structure
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge color="warning" class="w-fit">
                      In review
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        100%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress color="yellow" progress={{ 100 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      2:00
                      <button class="ml-3 rounded-md bg-green-500 p-1 text-white hover:bg-green-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    23 Nov 2022
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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
                  <Table.Cell class="w-4 px-4 py-2">
                    <div class="flex items-center">
                      <Checkbox
                        id="checkbox-table-search-1"
                        name="checkbox-table-search-1"
                      ></Checkbox>
                      <Label
                        htmlFor="checkbox-table-search-1"
                        class="sr-only"
                      >
                        Select this project
                      </Label>
                    </div>
                  </Table.Cell>
                  <Table.Cell
                    scope="row"
                    class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white"
                  >
                    Implement GPT 3
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <Badge class="w-fit">In progress</Badge>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2">
                    <div class="flex w-28 -space-x-4">
                      <Avatar.Group>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-10.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-1.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar
                          img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/avatar-3.png"
                          rounded
                          size="sm"
                          stacked
                          class="[&_img]:ring-white dark:[&_img]:ring-gray-800"
                        ></Avatar>
                        <Avatar.Counter
                          total={{ 2 }}
                          class="ring-white dark:ring-gray-800"
                        ></Avatar>
                      </Avatar.Group>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium">
                    <div class="mb-1 flex justify-end">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        25%
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                      <Progress progress={{ 25 }} size="sm" ></Progress>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <a
                      href="#"
                      class="inline-flex items-center font-medium text-primary-600 hover:underline dark:text-primary-500"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mr-1 h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden
                      >
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" ></path>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" ></path>
                      </svg>
                      Website
                    </a>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-gray-900 dark:text-white">
                    <div class="inline-flex items-center rounded-lg border bg-white px-2 py-1 text-xs font-medium dark:border-gray-600 dark:bg-gray-700">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        aria-hidden
                        class="mr-1 h-4 w-4 text-gray-400"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        ></path>
                      </svg>
                      <span class="text-green-500">3:11</span>
                      /8:00
                      <button class="ml-3 rounded-md bg-orange-500 p-1 text-white hover:bg-orange-700">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="h-4 w-4"
                          aria-hidden
                        >
                          <path
                            fillRule="evenodd"
                            d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </Table.Cell>
                  <Table.Cell class="whitespace-nowrap px-4 py-2 text-xs font-medium text-gray-900 dark:text-white">
                    Today
                  </Table.Cell>
                  <Table.Cell class="px-4 py-2">
                    <Dropdown
                      inline
                      label={{ 
                        
                          <span class="sr-only">Edit project</span>
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