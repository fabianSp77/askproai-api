@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    
      <div class="m-5 flex justify-center">
        <Button @click="{ setShowModal(true) "}>Create product</Button>
      </div>
      <Modal onClose={{ setShowModal(false) }} show={{ showModal }}>
        <Modal.Body class="relative rounded-lg bg-white p-4 shadow dark:bg-gray-800 sm:p-5">
          <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Add new user
            </h3>
            <button
              @click="{ setShowModal(false) "}
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
                  htmlFor="first-name"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  First Name
                </Label>
                <TextInput
                  id="first-name"
                  name="first-name"
                  placeholder="John"
                  required
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="last-name"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Last Name
                </Label>
                <TextInput
                  id="last-name"
                  name="last-name"
                  placeholder="Doe"
                  required
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="email"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Email
                </Label>
                <TextInput
                  id="email"
                  name="email"
                  placeholder="name@company.com"
                  required
                  type="email"
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="user-permissions"
                  class="mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white"
                >
                  User Permissions&nbsp;
                  <Tooltip
                    content="User permissions, part of the overall user management process, are access granted to users to specific resources such as files, applications, networks, or devices."
                    theme={{ {
                      content: twMerge(theme.tooltip.content, "w-64"),
                     }}}
                  >
                    <HiInformationCircle class="h-4 w-4 cursor-pointer text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white" ></HiInformationCircle>
                    <span class="sr-only">Details</span>
                  </Tooltip>
                </Label>
                <Select id="user-permissions" name="user-permissions">
                  <option selected>Operational</option>
                  <option value="NO">Non Operational</option>
                </Select>
              </div>
              <div>
                <Label
                  htmlFor="password"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Password
                </Label>
                <TextInput
                  id="password"
                  name="password"
                  placeholder="•••••••••"
                  required
                  type="password"
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="confirm-password"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Confirm password
                </Label>
                <TextInput
                  id="confirm-password"
                  name="confirm-password"
                  placeholder="•••••••••"
                  required
                  type="password"
                ></TextInput>
              </div>
              <div class="sm:col-span-2">
                <Label
                  htmlFor="biography"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Biography
                </Label>
                <div class="w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                  <div class="flex items-center justify-between border-b px-3 py-2 dark:border-gray-600">
                    <div class="flex flex-wrap items-center divide-gray-200 dark:divide-gray-600 sm:divide-x">
                      <div class="flex items-center space-x-1 sm:pr-4">
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiPaperClip class="h-5 w-5" ></HiPaperClip>
                          <span class="sr-only">Attach file</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiLocationMarker class="h-5 w-5" ></HiLocationMarker>
                          <span class="sr-only">Embed map</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiPhotograph class="h-5 w-5" ></HiPhotograph>
                          <span class="sr-only">Upload image</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiCode class="h-5 w-5" ></HiCode>
                          <span class="sr-only">Format code</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiEmojiHappy class="h-5 w-5" ></HiEmojiHappy>
                          <span class="sr-only">Add emoji</span>
                        </button>
                      </div>
                      <div class="hidden flex-wrap items-center space-x-1 sm:flex sm:pl-4">
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <svg
                            aria-hidden
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                          >
                            <path
                              fillRule="evenodd"
                              d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                              clipRule="evenodd"
                            ></path>
                          </svg>
                          <span class="sr-only">Add list</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiCog class="h-5 w-5" ></HiCog>
                          <span class="sr-only">Settings</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiCalendar class="h-5 w-5" ></HiCalendar>
                          <span class="sr-only">Timeline</span>
                        </button>
                        <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                          <HiDownload class="h-5 w-5" ></HiDownload>
                          <span class="sr-only">Download</span>
                        </button>
                      </div>
                    </div>
                    <Tooltip content="Show full screen">
                      <button class="cursor-pointer rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white sm:ml-auto">
                        <svg
                          aria-hidden
                          fill="currentColor"
                          viewBox="0 0 20 20"
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-5 w-5"
                        >
                          <path
                            fillRule="evenodd"
                            d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z"
                            clipRule="evenodd"
                          ></path>
                        </svg>
                        <span class="sr-only">Full screen</span>
                      </button>
                    </Tooltip>
                  </div>
                  <div class="rounded-b-lg bg-white px-4 py-2 dark:bg-gray-800">
                    <Textarea
                      id="biography"
                      name="biography"
                      placeholder="Write a message here"
                      required
                      rows={{ 8 }}
                      class="block w-full border-0 bg-white px-0 text-sm text-gray-800 focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                    ></Textarea>
                  </div>
                </div>
              </div>
              <div class="sm:col-span-2">
                <Label
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                  htmlFor="file_input"
                >
                  Upload avatar
                </Label>
                <div class="w-full items-center sm:flex">
                  <Avatar
                    alt=""
                    img="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/helene-engels.png"
                    rounded
                    size="lg"
                    class="mb-4 sm:mb-0 sm:mr-4 [&_img]:max-w-none"
                  ></Avatar>
                  <div class="w-full">
                    <input
                      aria-describedby="file_input_help"
                      id="file_input"
                      name="file_input"
                      type="file"
                      class="w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder:text-gray-400"
                    ></input>
                    <p
                      class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-300"
                      id="file_input_help"
                    >
                      SVG, PNG, JPG or GIF (MAX. 800x400px).
                    </p>
                  </div>
                </div>
              </div>
              <div class="sm:col-span-2">
                <Label
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                  htmlFor="role"
                >
                  Assign Role
                </Label>
                <div class="space-y-4 sm:flex sm:space-y-0">
                  <div class="mr-4 flex items-center">
                    <Checkbox id="inline-checkbox" name="role" ></Checkbox>
                    <Label
                      htmlFor="inline-checkbox"
                      class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >
                      Administrator
                    </Label>
                  </div>
                  <div class="mr-4 flex items-center">
                    <Checkbox id="inline-2-checkbox" name="role" ></Checkbox>
                    <Label
                      htmlFor="inline-2-checkbox"
                      class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >
                      Member
                    </Label>
                  </div>
                  <div class="mr-4 flex items-center">
                    <Checkbox
                      defaultChecked
                      id="inline-checked-checkbox"
                      name="role"
                    ></Checkbox>
                    <Label
                      htmlFor="inline-checked-checkbox"
                      class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >
                      Viewer
                    </Label>
                  </div>
                </div>
              </div>
              <div>
                <div class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
                  Status
                </div>
                <ToggleSwitch
                  checked={{ isUserStatus }}
                  label={{ isUserStatus ? "Active" : "Inactive" }}
                  onChange={{ setUserStatus(!isUserStatus) }}
                />
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <Button
                size="lg"
                type="submit"
                class="inline-flex w-full [&>span]:text-sm"
              >
                <HiPlus class="h-4 w-4 sm:mr-2" ></HiPlus>
                Add new user
              </Button>
              <Button
                color="gray"
                @click="{ setShowModal(false) "}
                outline
                size="lg"
                class="inline-flex w-full [&>span]:text-sm [&>span]:text-gray-500 hover:[&>span]:text-gray-900 [&>span]:dark:bg-gray-700 dark:[&>span]:enabled:hover:bg-gray-600"
              >
                Discard
              </Button>
            </div>
          </form>
        </Modal.Body>
      </Modal>
    
  
@endsection