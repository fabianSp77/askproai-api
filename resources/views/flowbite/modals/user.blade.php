@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    
      <Button @click="{ setShowModal(true) "} class="mx-auto">
        Update user
      </Button>
      <Modal
        onClose={{ setShowModal(false) }}
        popup
        show={{ showModal }}
        size="xl"
      >
        <Modal.Body class="relative rounded-lg bg-white p-4 shadow dark:bg-gray-800 sm:p-5">
          <div class="mb-4 flex items-center justify-between rounded-t border-b pb-4 dark:border-gray-600 sm:mb-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Update user
            </h3>
            <button
              @click="{ setShowModal(false) "}
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
                <Label htmlFor="username" class="mb-2 block">
                  Username
                </Label>
                <TextInput
                  defaultValue="leslie.linvingston"
                  id="username"
                  name="username"
                  placeholder="Ex. bonnie.green"
                  required
                ></TextInput>
              </div>
              <div>
                <Label htmlFor="email" class="mb-2 block">
                  Email
                </Label>
                <TextInput
                  defaultValue="leslie@company.com"
                  id="email"
                  name="email"
                  placeholder="bonnie@company.com"
                  required
                  type="email"
                ></TextInput>
              </div>
              <div>
                <Label htmlFor="password" class="mb-2 block">
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
                <Label htmlFor="confirm-password" class="mb-2 block">
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
            </div>
            <Button
              @click="{ setShowModal(false) "}
              size="lg"
              type="submit"
              class="mt-4 [&>span]:text-sm"
            >
              Update user
            </Button>
          </form>
        </Modal.Body>
      </Modal>
    
  
@endsection