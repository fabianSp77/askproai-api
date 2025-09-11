{{-- Flowbite Component: forgot-password --}}
@props(['title' => 'Tailwind CSS Forgot Password Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<section class="bg-gray-50 dark:bg-gray-900">
  <div class="mx-auto grid h-screen max-w-screen-xl justify-items-center px-4 py-8 lg:grid-cols-12 lg:gap-20 lg:py-16">
    <div class="place-self-center lg:col-span-6">
      <div class="max-w-lg rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
        <a href="#" class="mb-4 inline-flex items-center text-xl font-semibold text-gray-900 dark:text-white sm:mb-6">
          <img class="mr-2 h-8 w-8" src="/images/logo.svg" alt="logo" />
          Flowbite
        </a>
        <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">Reset your password</h1>
        <p class=" text-gray-500 dark:text-gray-400">
          We’ll email you instructions to reset your password. If you don’t have access to your email anymore, you can try
          <a class="font-medium text-primary-700 underline hover:no-underline dark:text-primary-500" href="#">account recovery</a>.
        </p>
        <form action="#">
          <div class="my-4 sm:my-6">
            <label for="email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email address</label>
            <input
              type="email"
              id="email"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="john.doe@company.com"
              required
            />
          </div>
          <div class="my-4 flex items-start sm:my-6">
            <div class="flex h-5 items-center">
              <input
                id="terms"
                aria-describedby="terms"
                type="checkbox"
                class="h-4 w-4 rounded-sm border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                required=""
              />
            </div>
            <div class="ml-3 text-sm">
              <label for="terms" class="text-gray-500 dark:text-gray-300"
                >I agree to Flowbite’s <a class="font-medium text-primary-700 hover:underline dark:text-primary-500" href="#">Terms of Use</a> and
                <a class="font-medium text-primary-700 hover:underline dark:text-primary-500" href="#">Privacy Policy</a>.</label
              >
            </div>
          </div>
          <button
            type="submit"
            class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            Send confirmation email
          </button>
          <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 sm:mt-6 sm:text-center">
            If you still need help, contact <a class="font-medium text-primary-700 underline hover:no-underline dark:text-primary-500" href="#">Flowbite Support.</a>
          </p>
        </form>
      </div>
    </div>
    <div class="mr-auto hidden place-self-center lg:col-span-6 lg:flex">
      <img class="mx-auto dark:hidden" src="../../images/girl-and-computer.svg" alt="illustration" />
      <img class="mx-auto hidden dark:flex" src="../../images/girl-and-computer-dark.svg" alt="illustration" />
    </div>
  </div>
</section>
</div>
