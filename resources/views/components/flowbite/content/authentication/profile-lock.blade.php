{{-- Flowbite Component: profile-lock --}}
@props(['title' => 'Tailwind CSS Profile Lock Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<section class="bg-gray-50 dark:bg-gray-900">
  <div class="mx-auto grid h-screen max-w-screen-xl place-self-center px-4 py-8 lg:grid-cols-12 lg:gap-24 lg:py-16">
    <!-- Card -->
    <div class="w-full place-self-center lg:col-span-5">
      <a href="" class="mb-4 flex items-center text-2xl font-semibold text-gray-900 dark:text-white md:mb-8">
        <img class="mr-2 h-9 w-9" src="/images/logo.svg" alt="logo" />
        Flowbite
      </a>
      <div class="mb-4 w-full rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 md:mb-8 md:p-6">
        <div class="flex space-x-4">
          <img class="h-8 w-8 rounded-full" src="/images/users/bonnie-green.png" alt="Bonnie image" />
          <h2 class="mb-3 text-2xl font-bold text-gray-900 dark:text-white">Bonnie Green</h2>
        </div>
        <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">Better to be safe than sorry.</p>
        <form action="#">
          <div class="mb-4">
            <label for="profile-lock" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Your password</label>
            <input
              type="password"
              name="profile-lock"
              id="profile-lock"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:text-sm"
              placeholder="••••••••"
              required
            />
          </div>
          <div class="mb-6 flex items-start">
            <div class="flex h-5 items-center">
              <input
                id="remember"
                aria-describedby="remember"
                name="remember"
                type="checkbox"
                class="focus:ring-3 h-4 w-4 rounded-sm border-gray-300 bg-gray-50 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                required
              />
            </div>
            <div class="ml-3 text-sm">
              <label for="remember" class="font-medium text-gray-900 dark:text-white">I accept the <a href="#" class="text-primary-700 hover:underline dark:text-primary-500">Terms and Conditions</a></label>
            </div>
          </div>
          <button
            type="submit"
            class="me-2 inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <svg class="-ms-0.5 me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M15 7a2 2 0 1 1 4 0v4a1 1 0 1 0 2 0V7a4 4 0 0 0-8 0v3H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V7Zm-5 6a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-3a1 1 0 0 1 1-1Z"
                clip-rule="evenodd"
              />
            </svg>
            Unlock
          </button>
        </form>
      </div>
      <nav>
        <ul class="flex space-x-4">
          <li>
            <a href="#" class="text-sm text-gray-500 hover:text-gray-900 hover:underline dark:text-gray-400 dark:hover:text-white">About</a>
          </li>
          <li>
            <a href="#" class="text-sm text-gray-500 hover:text-gray-900 hover:underline dark:text-gray-400 dark:hover:text-white">Term &amp; Conditions</a>
          </li>
          <li>
            <a href="#" class="text-sm text-gray-500 hover:text-gray-900 hover:underline dark:text-gray-400 dark:hover:text-white">Contact</a>
          </li>
        </ul>
      </nav>
    </div>
    <div class="mx-auto mr-auto hidden place-self-center lg:col-span-7 lg:grid">
      <img class="mx-auto dark:hidden" src="../../images/lock-password.svg" alt="illustration" />
      <img class="mx-auto hidden dark:flex" src="../../images/lock-password-dark.svg" alt="illustration" />
    </div>
  </div>
</section>
</div>
