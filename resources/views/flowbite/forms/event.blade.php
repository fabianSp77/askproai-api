@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    <section class="bg-white dark:bg-gray-900">
      <div class="mx-auto px-4 py-8 md:max-w-6xl lg:py-16">
        <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">
          Update event
        </h2>
        <form>
          <div class="gap-4 sm:mb-2 sm:grid sm:grid-cols-2 sm:gap-6 xl:grid-cols-3">
            <div class="mb-4 space-y-4 xl:col-span-2">
              <div>
                <Label htmlFor="title" class="mb-2 block dark:text-white">
                  Title
                </Label>
                <TextInput
                  defaultValue="The 4th Digital Transformation"
                  id="title"
                  name="title"
                  required
                ></TextInput>
              </div>
              <div>
                <Label
                  htmlFor="date_start"
                  class="mb-2 block dark:text-white"
                >
                  Select Date
                </Label>
                <div class="items-center space-y-4 md:flex md:space-y-0">
                  <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                      <svg
                        aria-hidden
                        class="h-5 w-5 text-gray-500 dark:text-gray-400"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fillRule="evenodd"
                          d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                          clipRule="evenodd"
                        ></path>
                      </svg>
                    </div>
                    <Datepicker
                      id="date_start"
                      name="start"
                      onSelectedDateChanged={(date) => {
                        setDateStart(date.toLocaleDateString()
@endsection