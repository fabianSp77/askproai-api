{{-- Flowbite Component: two-factor --}}
@props(['title' => 'Tailwind CSS Two Factor Auth Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<section class="bg-gray-50 dark:bg-gray-900">
  <div class="mx-auto grid h-screen max-w-screen-xl justify-items-center px-4 py-8 lg:grid-cols-12 lg:gap-20 lg:py-16">
    <div class="mr-auto hidden place-self-center lg:col-span-6 lg:flex">
      <img class="mx-auto dark:hidden" src="../../images/girl-and-computer.svg" alt="illustration" />
      <img class="mx-auto hidden dark:flex" src="../../images/girl-and-computer-dark.svg" alt="illustration" />
    </div>
    <div class="place-self-center lg:col-span-6">
      <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:max-w-xl sm:p-6">
        <a href="#" class="mb-4 inline-flex items-center text-xl font-semibold text-gray-900 dark:text-white sm:mb-6">
          <img class="mr-2 h-8 w-8" src="/images/logo.svg" alt="logo" />
          Flowbite
        </a>
        <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">Two-factor authentication</h1>
        <p class=" text-gray-500 dark:text-gray-400">Open the two-factor authentication app on your device to view your authentication code and verify your identity.</p>
        <form action="#">
          <div class="my-4 flex space-x-2 sm:my-6 sm:space-x-4">
            <div>
              <label for="code-1" class="sr-only">First code</label>
              <input
                type="text"
                maxlength="1"
                onkeyup="focusNextInput(this, 'code-1', 'code-2')"
                id="code-1"
                class="block h-12 w-12 rounded-lg border border-gray-300 bg-white py-3 text-center text-2xl font-extrabold text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:h-16 sm:w-16 sm:py-4 sm:text-4xl"
                required
              />
            </div>
            <div>
              <label for="code-2" class="sr-only">Second code</label>
              <input
                type="text"
                maxlength="1"
                onkeyup="focusNextInput(this, 'code-1', 'code-3')"
                id="code-2"
                class="block h-12 w-12 rounded-lg border border-gray-300 bg-white py-3 text-center text-2xl font-extrabold text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:h-16 sm:w-16 sm:py-4 sm:text-4xl"
                required
              />
            </div>
            <div>
              <label for="code-3" class="sr-only">Third code</label>
              <input
                type="text"
                maxlength="1"
                onkeyup="focusNextInput(this, 'code-2', 'code-4')"
                id="code-3"
                class="block h-12 w-12 rounded-lg border border-gray-300 bg-white py-3 text-center text-2xl font-extrabold text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:h-16 sm:w-16 sm:py-4 sm:text-4xl"
                required
              />
            </div>
            <div>
              <label for="code-4" class="sr-only">Fourth code</label>
              <input
                type="text"
                maxlength="1"
                onkeyup="focusNextInput(this, 'code-3', 'code-5')"
                id="code-4"
                class="block h-12 w-12 rounded-lg border border-gray-300 bg-white py-3 text-center text-2xl font-extrabold text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:h-16 sm:w-16 sm:py-4 sm:text-4xl"
                required
              />
            </div>
            <div>
              <label for="code-5" class="sr-only">Fivth code</label>
              <input
                type="text"
                maxlength="1"
                onkeyup="focusNextInput(this, 'code-4', 'code-6')"
                id="code-5"
                class="block h-12 w-12 rounded-lg border border-gray-300 bg-white py-3 text-center text-2xl font-extrabold text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:h-16 sm:w-16 sm:py-4 sm:text-4xl"
                required
              />
            </div>
            <div>
              <label for="code-6" class="sr-only">Sixth code</label>
              <input
                type="text"
                maxlength="1"
                onkeyup="focusNextInput(this, 'code-5', 'code-6')"
                id="code-6"
                class="block h-12 w-12 rounded-lg border border-gray-300 bg-white py-3 text-center text-2xl font-extrabold text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:h-16 sm:w-16 sm:py-4 sm:text-4xl"
                required
              />
            </div>
          </div>
          <button
            type="submit"
            class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:py-3.5"
          >
            Verify
          </button>
          <p class="mt-4 rounded-lg bg-gray-100 p-4 text-sm text-gray-500 dark:bg-gray-700 dark:text-gray-400 sm:mt-6">
            Can’t access your two-factor device? <a class="font-medium text-primary-700 underline hover:no-underline dark:text-primary-500" href="#">Recover my account.</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
</div>
