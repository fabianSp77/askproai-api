@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    
      <Button @click="{ setShowModal(true) "} class="mx-auto">
        Show delete confirmation
      </Button>
      <Modal
        onClose={{ setShowModal(false) }}
        popup
        size="lg"
        show={{ showModal }}
      >
        <Modal.Body class="relative rounded-lg bg-white p-0 shadow dark:bg-gray-800">
          <div class="flex items-center justify-between p-4 sm:px-5">
            <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">
              Are you sure?
            </h3>
            <button
              @click="{ setShowModal(false) "}
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
          </div>
          <p class="border-y border-orange-200 bg-orange-100 p-4 text-orange-700 dark:border-gray-600 dark:bg-gray-700 dark:text-orange-300 sm:px-5">
            Unexpected bad things can happen if you don’t read this!
          </p>
          <div class="p-4 sm:p-5">
            <p class="text-gray-500 dark:text-gray-400">
              This action&nbsp;
              <span class="font-semibold text-gray-900 dark:text-white">
                CANNOT
              </span>
              &nbsp;be undone. This will permanently delete the&nbsp;
              <span class="font-semibold text-gray-900 dark:text-white">
                bergside/flowbite-library
              </span>
              &nbsp;file, wiki, issues and comments, and remove all collaborator
              assosiations.
            </p>
            <form>
              <div class="mb-4 mt-2">
                <Label
                  htmlFor="repository-name-input"
                  class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                >
                  Please type in the name of the repository to confirm.
                </Label>
                <TextInput
                  id="repository-name-input"
                  placeholder="Ex. bergside/flowbite-library"
                  required
                ></TextInput>
              </div>
              <Button
                color="failure"
                type="submit"
                size="sm"
                class="w-full"
              >
                I understand, delete this repository
              </Button>
            </form>
          </div>
        </Modal.Body>
      </Modal>
    
  
@endsection