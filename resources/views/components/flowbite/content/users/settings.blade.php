{{-- Flowbite Component: settings --}}
@props(['title' => 'Tailwind CSS Settings Page - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="px-4 pt-4">
  <nav class="mb-4 flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
      <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
          <svg class="me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd" />
          </svg>
          Home
        </a>
      </li>
      <li>
        <div class="flex items-center">
          <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
          <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">User</a>
        </div>
      </li>
      <li aria-current="page">
        <div class="flex items-center">
          <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
          </svg>
          <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Settings</span>
        </div>
      </li>
    </ol>
  </nav>
  <h1 class="text-xl font-bold text-gray-900 dark:text-white">Settings</h1>
</div>
<div class="gap-4 p-4 xl:grid xl:grid-cols-2">
  <div class="mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:mb-0">
    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Account</h2>
    <div class="my-4 flex w-full items-center">
      <img class="mb-4 me-4 h-20 w-20 rounded-full sm:mb-0" src="/images/users/bonnie-green.png" alt="Bonnie avatar" />
      <div class="w-full items-center space-x-0 space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M12 3c.3 0 .6.1.8.4l4 5a1 1 0 1 1-1.6 1.2L13 7v7a1 1 0 1 1-2 0V6.9L8.8 9.6a1 1 0 1 1-1.6-1.2l4-5c.2-.3.5-.4.8-.4ZM9 14v-1H5a2 2 0 0 0-2 2v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-4v1a3 3 0 1 1-6 0Zm8 2a1 1 0 1 0 0 2 1 1 0 1 0 0-2Z"
              clip-rule="evenodd"
            />
          </svg>
          Upload
        </button>
        <button
          type="button"
          class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
        >
          Remove
        </button>
      </div>
    </div>
    <form action="#">
      <div class="mb-4 grid gap-4 sm:mb-6 sm:grid-cols-2">
        <div>
          <label for="username" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Username*</label>
          <input
            type="text"
            name="username"
            id="username"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Ex. BonnieG"
            required=""
          />
        </div>
        <div>
          <label for="fullname" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Full name*</label>
          <input
            type="text"
            name="fullname"
            id="fullname"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Ex. Bonnie Green"
            required=""
          />
        </div>
        <div>
          <label for="email" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Your email*</label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 top-0 flex items-center ps-3.5">
              <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path d="M2 5.6V18c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V5.6l-.9.7-7.9 6a2 2 0 0 1-2.4 0l-8-6-.8-.7Z" />
                <path d="M20.7 4.1A2 2 0 0 0 20 4H4a2 2 0 0 0-.6.1l.7.6 7.9 6 7.9-6 .8-.6Z" />
              </svg>
            </div>
            <input
              type="email"
              name="email"
              id="email"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="name@example.com"
              required=""
            />
          </div>
        </div>
        <div>
          <label for="phone-input" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Phone number</label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 top-0 flex items-center ps-3.5">
              <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M11 4a1 1 0 0 0-1 1v10h10.5l.5-3h-5a1 1 0 1 1 0-2h5.3l.2-1H16a1 1 0 1 1 0-2h5.9v-.7A2 2 0 0 0 20.2 4H11ZM9 18c0-.4 0-.7.2-1h11.6l.2 1v1c0 .6-.4 1-1 1H10a1 1 0 0 1-1-1v-1Zm-7-7c0 2 .3 4.1 1 6.1.2.5 1.6.9 2.2.9h1.3l.5-.1c.2 0 .3-.1.4-.3.2 0 .2-.2.3-.4v-.4l-.2-2.2a1 1 0 0 0-.4-.7c-.2-.2-.5-.3-.8-.3h-1L5 11c0-.9 0-1.7.2-2.6h1.2l.9-.3.4-.7.2-2.2v-.4a1 1 0 0 0-.3-.4c-.1-.2-.3-.2-.4-.3L6.8 4H4.3c-.3 0-.6 0-.8.2-.3.2-.5.4-.6.7-.6 2-1 4-.9 6.1Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <input
              type="text"
              id="phone-input"
              aria-describedby="helper-text-explanation"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500  dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}"
              placeholder="123-456-7890"
              required
            />
          </div>
        </div>
        <div>
          <label for="timezone" class="mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
            >Timezone<button data-tooltip-target="tooltip-timezone-description" class="ms-1 text-gray-400 hover:text-gray-900 dark:hover:text-white" type="button">
              <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                  clip-rule="evenodd"
                />
              </svg>

              <span class="sr-only">Show information</span>
            </button></label
          >
          <select
            id="timezone"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          >
            <option selected>UTC-08:00 - Pacific Standard Time (PST)</option>
            <option value="-12:00">(GMT -12:00) Eniwetok, Kwajalein</option>
            <option value="-11:00">(GMT -11:00) Midway Island, Samoa</option>
            <option value="-10:00">(GMT -10:00) Hawaii</option>
            <option value="-09:50">(GMT -9:30) Taiohae</option>
            <option value="-09:00">(GMT -9:00) Alaska</option>
            <option value="-08:00" selected="selected">(GMT -8:00) Pacific Time (US &amp; Canada)</option>
            <option value="-07:00">(GMT -7:00) Mountain Time (US &amp; Canada)</option>
            <option value="-06:00">(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>
            <option value="-05:00">(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>
            <option value="-04:50">(GMT -4:30) Caracas</option>
            <option value="-04:00">(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>
            <option value="-03:50">(GMT -3:30) Newfoundland</option>
            <option value="-03:00">(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>
            <option value="-02:00">(GMT -2:00) Mid-Atlantic</option>
            <option value="-01:00">(GMT -1:00) Azores, Cape Verde Islands</option>
            <option value="+00:00">(GMT) Western Europe Time, London, Lisbon, Casablanca</option>
            <option value="+01:00">(GMT +1:00) Brussels, Copenhagen, Madrid, Paris</option>
            <option value="+02:00">(GMT +2:00) Kaliningrad, South Africa</option>
            <option value="+03:00">(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>
            <option value="+03:50">(GMT +3:30) Tehran</option>
            <option value="+04:00">(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>
            <option value="+04:50">(GMT +4:30) Kabul</option>
            <option value="+05:00">(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>
            <option value="+05:50">(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>
            <option value="+05:75">(GMT +5:45) Kathmandu, Pokhara</option>
            <option value="+06:00">(GMT +6:00) Almaty, Dhaka, Colombo</option>
            <option value="+06:50">(GMT +6:30) Yangon, Mandalay</option>
            <option value="+07:00">(GMT +7:00) Bangkok, Hanoi, Jakarta</option>
            <option value="+08:00">(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>
            <option value="+08:75">(GMT +8:45) Eucla</option>
            <option value="+09:00">(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>
            <option value="+09:50">(GMT +9:30) Adelaide, Darwin</option>
            <option value="+10:00">(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>
            <option value="+10:50">(GMT +10:30) Lord Howe Island</option>
            <option value="+11:00">(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>
            <option value="+11:50">(GMT +11:30) Norfolk Island</option>
            <option value="+12:00">(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>
            <option value="+12:75">(GMT +12:45) Chatham Islands</option>
            <option value="+13:00">(GMT +13:00) Apia, Nukualofa</option>
            <option value="+14:00">(GMT +14:00) Line Islands, Tokelau</option>
          </select>
          <div
            id="tooltip-timezone-description"
            role="tooltip"
            class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600"
          >
            A timezone is a region of the Earth that has the same standard time.
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
        <div>
          <label for="account-type" class="mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
            >Account Type<button data-tooltip-target="tooltip-account-type-description-2" class="ms-1 text-gray-400 hover:text-gray-900 dark:hover:text-white" type="button">
              <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="sr-only">Show information</span>
            </button></label
          >
          <select
            id="account-type"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          >
            <option selected>Choose your account type</option>
            <option value="personal">Personal</option>
            <option value="business">Business</option>
            <option value="education">Education/University</option>
          </select>
          <div
            id="tooltip-account-type-description-2"
            role="tooltip"
            class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600"
          >
            This indicates the classification of your account, defining the set of privileges, and access rights associated with it.
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        </div>
      </div>
      <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Linked accounts</h3>
      <p class="mb-4 text-gray-500 dark:text-gray-400 sm:mb-6">We use this to let you sign in and populate your profile information</p>
      <!-- Toggle 1 -->
      <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-2 flex items-center justify-between">
          <svg class="h-8 text-gray-900 dark:text-white" viewBox="0 0 68 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M8.715 2.5C3.89996 2.5 0 6.39996 0 11.215C0 15.0714 2.49467 18.3286 5.95888 19.4834C6.39463 19.5596 6.55804 19.2982 6.55804 19.0694C6.55804 18.8624 6.54714 18.1761 6.54714 17.4462C4.3575 17.8493 3.79102 16.9124 3.61672 16.4222C3.51868 16.1717 3.09382 15.3982 2.72344 15.1912C2.41841 15.0278 1.98266 14.6247 2.71254 14.6138C3.39885 14.603 3.88907 15.2457 4.05247 15.5071C4.83682 16.8253 6.08961 16.4549 6.59072 16.2261C6.66697 15.6596 6.89574 15.2784 7.1463 15.0605C5.20721 14.8426 3.18097 14.0909 3.18097 10.7575C3.18097 9.8097 3.51868 9.02535 4.07426 8.4153C3.98711 8.19743 3.68209 7.30414 4.16141 6.10583C4.16141 6.10583 4.89129 5.87706 6.55804 6.99912C7.25524 6.80303 7.99601 6.70499 8.73679 6.70499C9.47756 6.70499 10.2183 6.80303 10.9155 6.99912C12.5823 5.86617 13.3122 6.10583 13.3122 6.10583C13.7915 7.30414 13.4865 8.19743 13.3993 8.4153C13.9549 9.02535 14.2926 9.79881 14.2926 10.7575C14.2926 14.1018 12.2555 14.8426 10.3164 15.0605C10.6323 15.3328 10.9046 15.8557 10.9046 16.6728C10.9046 17.8384 10.8937 18.7753 10.8937 19.0694C10.8937 19.2982 11.0572 19.5705 11.4929 19.4834C13.223 18.8993 14.7264 17.7874 15.7914 16.3041C16.8564 14.8209 17.4295 13.041 17.43 11.215C17.43 6.39996 13.53 2.5 8.715 2.5Z"
              fill="currentColor"
            />
            <path
              d="M40.6279 15.4215H40.6075C40.6167 15.4215 40.6228 15.4317 40.6319 15.4327H40.638L40.6279 15.4225V15.4215ZM40.6319 15.4327C40.5374 15.4337 40.2995 15.4835 40.0483 15.4835C39.2553 15.4835 38.9807 15.1175 38.9807 14.6396V11.4562H40.5974C40.6889 11.4562 40.76 11.3748 40.76 11.263V9.53453C40.76 9.44302 40.6787 9.36168 40.5974 9.36168H38.9807V7.21634C38.9807 7.135 38.9299 7.08416 38.8384 7.08416H36.6422C36.5507 7.08416 36.4999 7.135 36.4999 7.21634V9.42268C36.4999 9.42268 35.3916 9.69721 35.3204 9.70737C35.2391 9.72771 35.1883 9.79888 35.1883 9.88022V11.263C35.1883 11.3748 35.2696 11.4562 35.3611 11.4562H36.4897V14.7911C36.4897 17.272 38.2182 17.5262 39.3976 17.5262C39.9365 17.5262 40.5872 17.3533 40.6889 17.3025C40.7499 17.2822 40.7804 17.211 40.7804 17.1398V15.6147C40.7818 15.5714 40.7675 15.5291 40.7403 15.4955C40.713 15.4619 40.6746 15.4392 40.6319 15.4317V15.4327ZM64.7248 13.1948C64.7248 11.3545 63.9826 11.1105 63.1997 11.1918C62.5897 11.2325 62.1016 11.5375 62.1016 11.5375V15.1165C62.1016 15.1165 62.5998 15.4622 63.3421 15.4825C64.3893 15.513 64.7248 15.1368 64.7248 13.1948ZM67.1955 13.0321C67.1955 16.5196 66.067 17.516 64.0945 17.516C62.427 17.516 61.5322 16.6721 61.5322 16.6721C61.5322 16.6721 61.4916 17.1398 61.4407 17.2008C61.4102 17.2618 61.3594 17.2822 61.2984 17.2822H59.7936C59.6919 17.2822 59.6004 17.2008 59.6004 17.1093L59.6208 5.81322C59.6208 5.72171 59.7021 5.64038 59.7936 5.64038H61.9593C62.0508 5.64038 62.1321 5.72171 62.1321 5.81322V9.64637C62.1321 9.64637 62.9659 9.10749 64.186 9.10749L64.1758 9.08716C65.3959 9.08716 67.1955 9.54469 67.1955 13.0321ZM58.3295 9.36168H56.1943C56.0825 9.36168 56.0215 9.44302 56.0215 9.55486V15.086C56.0215 15.086 55.4622 15.4825 54.6997 15.4825C53.9371 15.4825 53.7134 15.1368 53.7134 14.3743V9.54469C53.7134 9.45319 53.6321 9.37185 53.5406 9.37185H51.3647C51.2732 9.37185 51.1919 9.45319 51.1919 9.54469V14.7403C51.1919 16.9771 52.4425 17.5363 54.1608 17.5363C55.5741 17.5363 56.723 16.7534 56.723 16.7534C56.723 16.7534 56.7739 17.15 56.8044 17.211C56.8247 17.2618 56.8959 17.3025 56.967 17.3025H58.3295C58.4413 17.3025 58.5023 17.2212 58.5023 17.1296L58.5227 9.53453C58.5227 9.44302 58.4413 9.36168 58.3295 9.36168ZM34.2325 9.35151H32.0668C31.9753 9.35151 31.894 9.44302 31.894 9.55486V17.0178C31.894 17.2212 32.0262 17.2923 32.199 17.2923H34.1512C34.3545 17.2923 34.4054 17.2008 34.4054 17.0178V9.52436C34.4054 9.43285 34.324 9.35151 34.2325 9.35151ZM33.1649 5.9149C32.382 5.9149 31.7618 6.53511 31.7618 7.31801C31.7618 8.10091 32.382 8.72113 33.1649 8.72113C33.9275 8.72113 34.5477 8.10091 34.5477 7.31801C34.5477 6.53511 33.9275 5.9149 33.1649 5.9149ZM49.9311 5.66071H47.7858C47.6943 5.66071 47.6129 5.74205 47.6129 5.83356V9.99206H44.2475V5.83356C44.2475 5.74205 44.1662 5.66071 44.0747 5.66071H41.909C41.8175 5.66071 41.7361 5.74205 41.7361 5.83356V17.1296C41.7361 17.2212 41.8276 17.3025 41.909 17.3025H44.0747C44.1662 17.3025 44.2475 17.2212 44.2475 17.1296V12.3001H47.6129L47.5926 17.1296C47.5926 17.2212 47.6739 17.3025 47.7654 17.3025H49.9311C50.0226 17.3025 50.104 17.2212 50.104 17.1296V5.83356C50.104 5.74205 50.0226 5.66071 49.9311 5.66071ZM30.7451 10.6631V16.4993C30.7451 16.5399 30.7349 16.6111 30.6841 16.6314C30.6841 16.6314 29.4131 17.5363 27.3186 17.5363C24.7869 17.5363 21.7875 16.7433 21.7875 11.5172C21.7875 6.29109 24.4107 5.21334 26.9729 5.22351C29.1894 5.22351 30.0842 5.72171 30.2265 5.81322C30.2672 5.86406 30.2875 5.90473 30.2875 5.95557L29.8605 7.76538C29.8605 7.85689 29.769 7.96873 29.6571 7.93823C29.2911 7.82639 28.7421 7.6027 27.4508 7.6027C25.9562 7.6027 24.3497 8.02974 24.3497 11.3952C24.3497 14.7606 25.8748 15.1572 26.9729 15.1572C27.9083 15.1572 28.2439 15.0453 28.2439 15.0453V12.7068H26.7492C26.6374 12.7068 26.5561 12.6254 26.5561 12.5339V10.6631C26.5561 10.5716 26.6374 10.4903 26.7492 10.4903H30.5519C30.6637 10.4903 30.7451 10.5716 30.7451 10.6631Z"
              fill="currentColor"
            />
            <defs>
              <clipPath id="clip0_1323_12291"><rect width="67.5" height="24" fill="currentColor" /></clipPath>
            </defs>
          </svg>
          <label class="inline-flex items-center cursor-pointer">
            <input id="github-connect" type="checkbox" value="" class="sr-only peer" checked>
            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="text-base font-normal text-gray-500 dark:text-gray-400">Integrate your knowledge base and customer success seamlessy with your app.</div>
      </div>
      <!-- Toggle 2 -->
      <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-2 flex items-center justify-between">
          <svg class="h-8" viewBox="0 0 57 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M24.1188 12.8251C24.1188 15.4867 22.0366 17.448 19.4813 17.448C16.926 17.448 14.8438 15.4867 14.8438 12.8251C14.8438 10.1447 16.926 8.20215 19.4813 8.20215C22.0366 8.20215 24.1188 10.1447 24.1188 12.8251ZM22.0887 12.8251C22.0887 11.1618 20.8819 10.0238 19.4813 10.0238C18.0807 10.0238 16.8739 11.1618 16.8739 12.8251C16.8739 14.4716 18.0807 15.6263 19.4813 15.6263C20.8819 15.6263 22.0887 14.4696 22.0887 12.8251Z"
              fill="#EA4335"
            />
            <path
              d="M34.1234 12.8251C34.1234 15.4867 32.0412 17.448 29.4859 17.448C26.9306 17.448 24.8484 15.4867 24.8484 12.8251C24.8484 10.1468 26.9306 8.20215 29.4859 8.20215C32.0412 8.20215 34.1234 10.1447 34.1234 12.8251ZM32.0933 12.8251C32.0933 11.1618 30.8865 10.0238 29.4859 10.0238C28.0853 10.0238 26.8785 11.1618 26.8785 12.8251C26.8785 14.4716 28.0853 15.6263 29.4859 15.6263C30.8865 15.6263 32.0933 14.4696 32.0933 12.8251Z"
              fill="#FBBC05"
            />
            <path
              d="M43.7112 8.48144V16.781C43.7112 20.1951 41.6978 21.5894 39.3176 21.5894C37.077 21.5894 35.7284 20.0908 35.2199 18.8653L36.9873 18.1295C37.3021 18.882 38.0733 19.7699 39.3155 19.7699C40.8391 19.7699 41.7833 18.8299 41.7833 17.0603V16.3954H41.7124C41.258 16.9561 40.3826 17.4459 39.278 17.4459C36.9665 17.4459 34.8489 15.4325 34.8489 12.8417C34.8489 10.2322 36.9665 8.20215 39.278 8.20215C40.3805 8.20215 41.2559 8.69195 41.7124 9.23595H41.7833V8.48353H43.7112V8.48144ZM41.9271 12.8417C41.9271 11.2139 40.8412 10.0238 39.4593 10.0238C38.0587 10.0238 36.8852 11.2139 36.8852 12.8417C36.8852 14.4529 38.0587 15.6263 39.4593 15.6263C40.8412 15.6263 41.9271 14.4529 41.9271 12.8417Z"
              fill="#4285F4"
            />
            <path d="M46.8895 3.61682V17.1646H44.9094V3.61682H46.8895Z" fill="#34A853" />
            <path
              d="M54.6057 14.3466L56.1814 15.3971C55.6728 16.1495 54.4473 17.4459 52.3297 17.4459C49.7035 17.4459 47.7422 15.4158 47.7422 12.823C47.7422 10.0738 49.7202 8.20007 52.1025 8.20007C54.5015 8.20007 55.6749 10.1093 56.0584 11.141L56.2689 11.6662L50.0891 14.2257C50.5622 15.1532 51.298 15.6263 52.3297 15.6263C53.3635 15.6263 54.0805 15.1178 54.6057 14.3466ZM49.7556 12.6833L53.8866 10.968C53.6594 10.3906 52.9758 9.98838 52.1713 9.98838C51.1396 9.98838 49.7035 10.8992 49.7556 12.6833Z"
              fill="#EA4335"
            />
            <path
              d="M7.34873 11.6224V9.66107H13.958C14.0226 10.0029 14.0559 10.4072 14.0559 10.8449C14.0559 12.3164 13.6537 14.136 12.3572 15.4324C11.0963 16.7455 9.48511 17.4458 7.35081 17.4458C3.39486 17.4458 0.0683594 14.2235 0.0683594 10.2676C0.0683594 6.31164 3.39486 3.08936 7.35081 3.08936C9.5393 3.08936 11.0983 3.94808 12.2697 5.06733L10.8857 6.45129C10.0458 5.66343 8.90776 5.05066 7.34873 5.05066C4.45992 5.05066 2.20057 7.37879 2.20057 10.2676C2.20057 13.1564 4.45992 15.4845 7.34873 15.4845C9.22249 15.4845 10.2896 14.7321 10.9733 14.0485C11.5277 13.4941 11.8924 12.702 12.0363 11.6203L7.34873 11.6224Z"
              fill="#4285F4"
            />
            <defs>
              <clipPath id="clip0_1323_12301"><rect width="56.5" height="24" fill="white" /></clipPath>
            </defs>
          </svg>
          <label class="inline-flex items-center cursor-pointer">
            <input id="google-connect" type="checkbox" value="" class="sr-only peer" checked>
            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="text-base font-normal text-gray-500 dark:text-gray-400">Integrate your knowledge base and customer success seamlessy with your app.</div>
      </div>
      <!-- Toggle 3 -->
      <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-2 flex items-center justify-between">
          <svg class="h-8 text-gray-900 dark:text-white" viewBox="0 0 84 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M38.2647 15.2192C37.6805 15.3539 37.3782 15.1742 37.3782 14.7872C37.3782 14.2569 37.9265 14.0447 38.7097 14.0447C39.0542 14.0447 39.3792 14.0744 39.3792 14.0744C39.3792 14.3014 38.886 15.0759 38.2647 15.2192ZM38.8592 9.91046C36.989 9.91046 35.6463 10.5982 35.6463 10.5982V12.6199C35.6463 12.6199 37.1287 11.7677 38.3315 11.7677C39.291 11.7677 39.409 12.2854 39.3655 12.7149C39.3655 12.7149 39.0885 12.6417 38.244 12.6417C36.2533 12.6417 35.2488 13.5464 35.2488 14.9969C35.2488 16.3729 36.3778 16.9479 37.3293 16.9479C38.7172 16.9479 39.3277 16.0149 39.515 15.5724C39.6452 15.2654 39.6692 15.0584 39.7857 15.0584C39.9195 15.0584 39.8745 15.2069 39.8677 15.5124C39.8567 16.0477 39.8822 16.4522 39.967 16.7932H41.8077V13.4822C41.8077 11.4149 41.0762 9.91046 38.8592 9.91046Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M50.9744 13.4325C50.9744 12.8278 51.5366 12.2771 52.5686 12.2771C53.6929 12.2771 54.5976 12.8185 54.8004 12.9415V10.6011C54.8004 10.6011 54.0859 9.91306 52.3296 9.91306C50.4784 9.91306 48.9366 10.9963 48.9366 13.328C48.9366 15.6595 50.3359 16.9525 52.3246 16.9525C53.8784 16.9525 54.8044 16.0988 54.8044 16.0988V13.876C54.5114 14.0398 53.6951 14.6065 52.5799 14.6065C51.3991 14.6065 50.9744 14.063 50.9744 13.4325ZM26.372 10.0663C26.3723 10.0678 26.4895 10.5708 26.335 11.2286C26.2563 11.5631 26.1115 11.8476 26.2863 11.8476C26.4068 11.8476 26.407 11.7476 26.5638 11.3891C26.793 10.8651 27.2763 9.91131 28.4055 9.91131C29.479 9.91131 29.9848 10.5636 30.1523 11.1743C30.2798 11.6383 30.1683 11.8476 30.3237 11.8476C30.4342 11.8476 30.4725 11.6068 30.6085 11.3023C30.8527 10.7568 31.384 9.91131 32.4585 9.91131C34.0797 9.91131 34.6987 11.0821 34.6987 12.8383V16.7955H32.2902V13.1473C32.2902 12.539 32.192 11.9491 31.6375 11.9491C31.1237 11.9491 30.8842 12.8595 30.8842 13.4473V16.797H28.4315V13.1475C28.4315 12.3693 28.3218 11.9493 27.7748 11.9493C27.3013 11.9493 27.025 12.787 27.025 13.4475V16.7958H24.582V10.0663H26.372ZM42.6094 10.0756C42.6104 10.0761 43.0262 10.3158 43.8332 10.3158C44.6417 10.3158 45.0522 10.0756 45.0522 10.0756V16.7973H42.6094V10.0756ZM62.7766 10.0756C62.7776 10.0761 63.1933 10.3158 64.0006 10.3158C64.8088 10.3158 65.2196 10.0756 65.2196 10.0756V16.7973H62.7766V10.0756ZM48.2857 8.02907V16.7973H45.8427V8.02907H48.2857ZM57.7036 8.02907C57.7039 8.02982 57.8049 8.28182 57.8049 9.03132C57.8049 10.1381 57.5641 11.0388 57.4504 11.3821C57.3901 11.5638 57.2826 11.8443 57.4369 11.8493C57.5259 11.8521 57.5794 11.7181 57.6884 11.4856C57.7969 11.2528 58.3348 9.91281 59.7488 9.91281C61.6966 9.91281 61.9831 11.5078 61.9831 13.235V16.797H59.5401V13.0888C59.5401 12.3283 59.3806 11.9506 58.8708 11.9506C58.2553 11.9506 58.0378 12.9035 58.0378 13.449V16.797H55.5949V8.02882L57.7036 8.02907ZM67.8348 10.0663C67.835 10.0678 67.9523 10.5708 67.7978 11.2286C67.719 11.5631 67.5743 11.8476 67.749 11.8476C67.8695 11.8476 67.8698 11.7476 68.0265 11.3891C68.2555 10.8651 68.739 9.91131 69.8683 9.91131C70.9908 9.91131 71.436 10.5666 71.615 11.1743C71.735 11.5816 71.631 11.8476 71.7865 11.8476C71.897 11.8476 71.9353 11.6068 72.0713 11.3023C72.315 10.7568 72.8468 9.91131 73.9213 9.91131C75.5425 9.91131 76.1615 11.0821 76.1615 12.8383V16.7955H73.753V13.1473C73.753 12.539 73.6548 11.9491 73.1003 11.9491C72.5865 11.9491 72.3468 12.8595 72.3468 13.4473V16.797H69.894V13.1475C69.894 12.3693 69.7848 11.9493 69.2375 11.9493C68.764 11.9493 68.4878 12.787 68.4878 13.4475V16.7958H66.0448V10.0663H67.8348ZM43.8234 7.96107C42.9979 7.96107 42.3282 8.39807 42.3282 8.93707C42.3282 9.47606 42.9977 9.91306 43.8234 9.91306C44.6494 9.91306 45.3189 9.47606 45.3189 8.93707C45.3189 8.39807 44.6497 7.96107 43.8234 7.96107ZM63.9908 7.96107C63.1651 7.96107 62.4953 8.39807 62.4953 8.93707C62.4953 9.47606 63.1648 9.91306 63.9908 9.91306C64.8166 9.91306 65.4863 9.47606 65.4863 8.93707C65.4863 8.39807 64.8168 7.96107 63.9908 7.96107Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M80.3229 15.3472C79.7629 15.3472 79.3509 14.6369 79.3509 13.6472C79.3509 12.6859 79.7739 11.9477 80.3036 11.9477C80.9829 11.9477 81.2729 12.5709 81.2729 13.6472C81.2729 14.7669 81.0056 15.3472 80.3229 15.3472ZM80.9931 9.90896C79.7379 9.90896 79.1621 10.8502 78.9121 11.3865C78.7469 11.7407 78.7556 11.8449 78.6346 11.8449C78.4604 11.8449 78.6051 11.5605 78.6836 11.226C78.8386 10.5672 78.7209 10.064 78.7209 10.064H76.9319V18.6497H79.3739V15.9277C79.6626 16.4179 80.1979 16.9417 81.0231 16.9417C82.7894 16.9417 83.6781 15.4474 83.6781 13.4287C83.6781 11.1407 82.6144 9.90896 80.9936 9.90896"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M15.9462 11.9405C16.1104 11.921 16.2677 11.92 16.4124 11.9405C16.4962 11.7485 16.5104 11.4177 16.4352 11.0575C16.3234 10.522 16.1719 10.198 15.8589 10.2485C15.5459 10.299 15.5344 10.687 15.6464 11.2223C15.7094 11.5235 15.8214 11.781 15.9462 11.9405Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M13.2609 12.3643C13.4847 12.4625 13.6224 12.5278 13.6762 12.4708C13.7109 12.4353 13.7004 12.3675 13.6469 12.2803C13.5364 12.1 13.3092 11.917 13.0679 11.8143C12.5749 11.602 11.9867 11.6725 11.5329 11.9985C11.3832 12.1083 11.2412 12.2603 11.2614 12.3525C11.2679 12.3823 11.2904 12.4048 11.3429 12.4123C11.4664 12.4263 11.8979 12.2083 12.3952 12.1778C12.7459 12.1563 13.0369 12.266 13.2609 12.3643Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M12.8115 12.6213C12.5198 12.6673 12.359 12.7635 12.2558 12.8528C12.1678 12.9298 12.1133 13.015 12.1138 13.0748C12.114 13.1033 12.1263 13.1198 12.136 13.128C12.1493 13.1398 12.165 13.1463 12.184 13.1463C12.2498 13.1463 12.3975 13.087 12.3975 13.087C12.8033 12.9418 13.071 12.9595 13.3363 12.9895C13.4828 13.006 13.5523 13.015 13.5843 12.9648C13.5938 12.9503 13.6053 12.919 13.576 12.8715C13.5078 12.7608 13.213 12.5735 12.8115 12.6213Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M15.04 13.5644C15.238 13.6616 15.456 13.6234 15.527 13.4791C15.598 13.3349 15.495 13.1389 15.297 13.0416C15.099 12.9444 14.881 12.9826 14.81 13.1269C14.739 13.2714 14.842 13.4671 15.04 13.5644Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M16.3132 12.4522C16.1525 12.4494 16.019 12.6259 16.0152 12.8467C16.0115 13.0674 16.139 13.2482 16.2997 13.2509C16.4605 13.2537 16.594 13.0772 16.5977 12.8564C16.6015 12.6359 16.474 12.4549 16.3132 12.4522Z"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M5.5083 16.4295C5.4683 16.3793 5.40255 16.3945 5.33881 16.4095C5.29431 16.42 5.24405 16.4315 5.1888 16.4308C5.07055 16.4285 4.97031 16.378 4.91406 16.2915C4.84081 16.179 4.84506 16.0113 4.92581 15.8193L4.96356 15.7333C5.09255 15.4438 5.3083 14.9595 5.06606 14.498C4.88356 14.1505 4.58606 13.9343 4.22831 13.8888C3.88481 13.8453 3.53131 13.9725 3.30581 14.2213C2.95007 14.6138 2.89432 15.148 2.96307 15.3368C2.98832 15.4058 3.02782 15.425 3.05632 15.429C3.11682 15.437 3.20631 15.393 3.26231 15.2423L3.27831 15.1933C3.30331 15.1135 3.34981 14.965 3.42606 14.846C3.51806 14.7025 3.66106 14.6038 3.82906 14.5675C4.00006 14.5308 4.17506 14.5635 4.32156 14.6595C4.57081 14.8225 4.66681 15.128 4.56031 15.4193C4.50531 15.57 4.41581 15.8583 4.43556 16.095C4.47556 16.5743 4.77031 16.7668 5.03505 16.7873C5.29255 16.797 5.47255 16.6523 5.51805 16.5468C5.5448 16.4843 5.5223 16.4463 5.50755 16.43"
              fill="currentColor"
            />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M7.95745 7.62714C8.79744 6.65665 9.83118 5.8129 10.7574 5.33916C10.7894 5.32266 10.8234 5.35741 10.8062 5.38891C10.7327 5.52216 10.5909 5.8074 10.5462 6.02365C10.5392 6.0574 10.5759 6.08265 10.6044 6.0634C11.1807 5.67065 12.1829 5.24965 13.0622 5.19566C13.0999 5.19341 13.1182 5.24166 13.0882 5.26491C12.9544 5.36741 12.8082 5.50941 12.7014 5.6529C12.6832 5.6774 12.7004 5.7124 12.7309 5.71265C13.3482 5.71715 14.2184 5.93315 14.7857 6.2514C14.8239 6.2729 14.7967 6.34715 14.7537 6.3374C13.8954 6.14065 12.4907 5.9914 11.0312 6.3474C9.72843 6.66515 8.73394 7.1559 8.00869 7.68339C7.97194 7.71014 7.9277 7.66139 7.95745 7.62714ZM12.1404 17.0301L12.1407 17.0306L12.1412 17.0316L12.1404 17.0301ZM15.6039 17.4388C15.6289 17.4283 15.6459 17.4001 15.6432 17.3718C15.6397 17.3371 15.6086 17.3118 15.5739 17.3153C15.5739 17.3153 13.7817 17.5806 12.0884 16.9608C12.2729 16.3613 12.7632 16.5778 13.5044 16.6376C14.8404 16.7173 16.0379 16.5221 16.9229 16.2681C17.6896 16.0481 18.6969 15.6141 19.4791 14.9966C19.7429 15.5761 19.8361 16.2141 19.8361 16.2141C19.8361 16.2141 20.0404 16.1776 20.2111 16.2826C20.3724 16.3818 20.4906 16.5881 20.4099 17.1216C20.2454 18.1181 19.8219 18.9271 19.1099 19.6713C18.6764 20.1378 18.1504 20.5433 17.5484 20.8383C17.2286 21.0063 16.8884 21.1516 16.5286 21.2688C13.8447 22.1456 11.0972 21.1818 10.2117 19.1123C10.1409 18.9568 10.0812 18.7938 10.0342 18.6236C9.65693 17.2598 9.97718 15.6236 10.9787 14.5936C11.0404 14.5281 11.1034 14.4504 11.1034 14.3531C11.1034 14.2719 11.0517 14.1861 11.0069 14.1251C10.6564 13.6169 9.44269 12.7509 9.68644 11.0749C9.86143 9.87088 10.9144 9.02289 11.8962 9.07314L12.1449 9.08738C12.5704 9.11263 12.9414 9.16714 13.2917 9.18189C13.8779 9.20739 14.4049 9.12213 15.0294 8.60189C15.2399 8.42639 15.4089 8.27414 15.6946 8.22589C15.7246 8.22064 15.7994 8.19389 15.9486 8.20089C16.1011 8.20914 16.2461 8.25089 16.3764 8.33764C16.8769 8.67064 16.9479 9.47738 16.9739 10.0674C16.9886 10.4041 17.0294 11.2191 17.0434 11.4531C17.0751 11.9881 17.2159 12.0634 17.5004 12.1571C17.6604 12.2099 17.8089 12.2491 18.0279 12.3106C18.6904 12.4966 19.0831 12.6854 19.3309 12.9279C19.4786 13.0794 19.5474 13.2404 19.5686 13.3939C19.6466 13.9639 19.1261 14.6679 17.7479 15.3076C16.2414 16.0068 14.4137 16.1838 13.1509 16.0431L12.7084 15.9931C11.6984 15.8571 11.1222 17.1623 11.7284 18.0568C12.1192 18.6333 13.1834 19.0083 14.2482 19.0086C16.6894 19.0088 18.5659 17.9663 19.2639 17.0661L19.3196 16.9866C19.3541 16.9348 19.3256 16.9063 19.2829 16.9356C18.7126 17.3258 16.1796 18.8751 13.4702 18.4088C13.4702 18.4088 13.1409 18.3546 12.8404 18.2378C12.6017 18.1448 12.1017 17.9148 12.0409 17.4021C14.2274 18.0783 15.6039 17.4388 15.6039 17.4388ZM3.59422 12.2426C2.83372 12.3904 2.16348 12.8214 1.75373 13.4169C1.50873 13.2126 1.05248 12.8171 0.971733 12.6629C0.317487 11.4204 1.68573 9.00488 2.64172 7.64064C5.00421 4.26941 8.70419 1.71767 10.4169 2.18067C10.6954 2.25942 11.6177 3.32866 11.6177 3.32866C11.6177 3.32866 9.90543 4.27866 8.31769 5.6029C6.17821 7.25014 4.56246 9.64438 3.59422 12.2426ZM4.87196 17.9438C4.75696 17.9636 4.63946 17.9713 4.52096 17.9683C3.37672 17.9376 2.14098 16.9076 2.01823 15.6861C1.88248 14.3359 2.57247 13.2969 3.79397 13.0504C3.93997 13.0209 4.11646 13.0039 4.30671 13.0139C4.99121 13.0514 5.99971 13.5769 6.22995 15.0679C6.43395 16.3883 6.10995 17.7328 4.87196 17.9438ZM20.3709 15.5529C20.3611 15.5181 20.2971 15.2849 20.2094 15.0036C20.1216 14.7221 20.0309 14.5244 20.0309 14.5244C20.3829 13.9976 20.3891 13.5264 20.3421 13.2596C20.2921 12.9289 20.1544 12.6471 19.8769 12.3556C19.5994 12.0644 19.0316 11.7659 18.2339 11.5419L17.8154 11.4256C17.8134 11.4084 17.7934 10.4389 17.7754 10.0226C17.7621 9.72163 17.7364 9.25188 17.5906 8.78939C17.4169 8.16314 17.1144 7.61514 16.7366 7.26464C17.7791 6.18415 18.4299 4.99366 18.4281 3.97266C18.4251 2.00892 16.0134 1.41468 13.0412 2.64542L12.4114 2.91267C12.4087 2.90992 11.2729 1.79568 11.2559 1.78068C7.8672 -1.17506 -2.72775 10.6016 0.659485 13.4619L1.39973 14.0891C1.20773 14.5866 1.13223 15.1566 1.19398 15.7693C1.27298 16.5566 1.67898 17.3111 2.33723 17.8941C2.96197 18.4476 3.78347 18.7981 4.58046 18.7973C5.8987 21.8351 8.91069 23.6986 12.4422 23.8036C16.2304 23.9161 19.4104 22.1386 20.7429 18.9456C20.8301 18.7216 21.1999 17.7118 21.1999 16.8203C21.1999 15.9246 20.6934 15.5529 20.3709 15.5529Z"
              fill="currentColor"
            />
          </svg>
          <label class="inline-flex items-center cursor-pointer">
            <input id="mailchimp-connect" type="checkbox" value="" class="sr-only peer">
            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="text-base font-normal text-gray-500 dark:text-gray-400">Messages that you share with your customers, such as emails or advertising.</div>
      </div>
      <button
        type="submit"
        class="mt-4 w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:mt-6 sm:w-auto"
      >
        Save changes
      </button>
    </form>
  </div>
  <div class="mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:mb-0">
    <h2 class="mb-4 md:mb-6 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4">Notifications</h2>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">Alerts & Notifications</h3>
      <a href="#" class="text-base font-medium text-primary-700 hover:underline dark:text-primary-500">Select all</a>
    </div>
    <div class="mb-4 sm:mb-6">
      <label class="relative mb-4 flex cursor-pointer">
        <input type="checkbox" value="" class="peer sr-only" />
        <div
          class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
        ></div>
        <div class="ms-3">
          <span class="font-medium text-gray-900 dark:text-gray-300">Flowbite Communication</span>
          <p id="helper-toggle-text" class="text-sm font-normal text-gray-500 dark:text-gray-300">Get Flowbite news, announcements, and product updates</p>
        </div>
      </label>
      <label class="relative mb-4 flex cursor-pointer">
        <input type="checkbox" value="" class="peer sr-only" checked />
        <div
          class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
        ></div>
        <div class="ms-3">
          <span class="font-medium text-gray-900 dark:text-gray-300">Account Activity</span>
          <p id="helper-toggle-text" class="text-sm font-normal text-gray-500 dark:text-gray-300">Get important notifications about you or activity you've missed</p>
        </div>
      </label>
      <label class="relative mb-4 flex cursor-pointer">
        <input type="checkbox" value="" class="peer sr-only" checked />
        <div
          class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
        ></div>
        <div class="ms-3">
          <span class="font-medium text-gray-900 dark:text-gray-300">Mobile push notifications</span>
          <p id="helper-toggle-text" class="text-sm font-normal text-gray-500 dark:text-gray-300">Receive push notifications whenever your company requires your attention</p>
        </div>
      </label>
      <label class="relative mb-4 flex cursor-pointer">
        <input type="checkbox" value="" class="peer sr-only" />
        <div
          class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
        ></div>
        <div class="ms-3">
          <span class="font-medium text-gray-900 dark:text-gray-300">Email notification</span>
          <p id="helper-toggle-text" class="text-sm font-normal text-gray-500 dark:text-gray-300">Receive email notifications whenever your company requires your attention</p>
        </div>
      </label>
      <label class="relative mb-4 flex cursor-pointer">
        <input type="checkbox" value="" class="peer sr-only" checked />
        <div
          class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
        ></div>
        <div class="ms-3">
          <span class="font-medium text-gray-900 dark:text-gray-300">Meetups near me</span>
          <p id="helper-toggle-text" class="text-sm font-normal text-gray-500 dark:text-gray-300">Get an email when a Flowbite Meetup is posted close to my location</p>
        </div>
      </label>
    </div>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">Email me when:</h3>
      <a href="#" class="text-base font-medium text-primary-700 hover:underline dark:text-primary-500">Select all</a>
    </div>
    <div class="mb-4 sm:mb-6">
      <div class="mb-4 flex items-center">
        <input
          checked
          id="mention-checkbox"
          type="checkbox"
          value=""
          class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <label for="mention-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-white">Someone mentions me</label>
      </div>
      <div class="mb-4 flex items-center">
        <input
          checked
          id="ask-checkbox"
          type="checkbox"
          value=""
          class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <label for="ask-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-white">Someone asks me to be a collaborator on one of their projects</label>
      </div>
      <div class="mb-4 flex items-center">
        <input
          id="invitation-checkbox"
          type="checkbox"
          value=""
          class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <label for="invitation-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-white">I receive invitations to invite new team members</label>
      </div>
      <div class="mb-4 flex items-center">
        <input
          id="follow-checkbox"
          type="checkbox"
          value=""
          class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <label for="follow-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-white">Anyone follows me</label>
      </div>
      <div class="mb-4 flex items-center">
        <input
          id="invitation-accept-checkbox"
          type="checkbox"
          value=""
          class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <label for="invitation-accept-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-white">Someone accepts my invitation</label>
      </div>
    </div>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">Subscribe me to:</h3>
      <a href="#" class="text-base font-medium text-primary-700 hover:underline dark:text-primary-500">Select all</a>
    </div>
    <div class="mb-4 sm:mb-6">
      <div class="mb-4 flex">
        <div class="flex h-5 items-center">
          <input
            checked
            id="newsletter-checkbox"
            aria-describedby="newsletter-checkbox-helper"
            type="checkbox"
            value=""
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
        </div>
        <div class="ms-2 text-sm">
          <label for="newsletter-checkbox" class="font-medium text-gray-900 dark:text-gray-300">Weekly newsletter</label>
          <p id="newsletter-checkbox-helper" class="text-sm font-normal text-gray-500 dark:text-gray-400">Get the latest news for Flowbite and Tailwind CSS</p>
        </div>
      </div>
      <div class="mb-4 flex">
        <div class="flex h-5 items-center">
          <input
            checked
            id="products-checkbox"
            aria-describedby="products-checkbox-helper"
            type="checkbox"
            value=""
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
        </div>
        <div class="ms-2 text-sm">
          <label for="products-checkbox" class="font-medium text-gray-900 dark:text-gray-300">Products</label>
          <p id="products-checkbox-helper" class="text-sm font-normal text-gray-500 dark:text-gray-400">Get notified when a product has been updated or launched</p>
        </div>
      </div>
      <div class="mb-4 flex">
        <div class="flex h-5 items-center">
          <input
            id="community-checkbox"
            aria-describedby="community-checkbox-helper"
            type="checkbox"
            value=""
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
        </div>
        <div class="ms-2 text-sm">
          <label for="community-checkbox" class="font-medium text-gray-900 dark:text-gray-300">Flowbite community</label>
          <p id="community-checkbox-helper" class="text-sm font-normal text-gray-500 dark:text-gray-400">Monday: Latest design news & community highlights</p>
        </div>
      </div>
      <div class="mb-4 flex">
        <div class="flex h-5 items-center">
          <input
            id="jobs-checkbox"
            aria-describedby="jobs-checkbox-helper"
            type="checkbox"
            value=""
            class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
        </div>
        <div class="ms-2 text-sm">
          <label for="jobs-checkbox" class="font-medium text-gray-900 dark:text-gray-300">Flowbite jobs</label>
          <p id="jobs-checkbox-helper" class="text-sm font-normal text-gray-500 dark:text-gray-400">Friday: New career and freelance project opportunities</p>
        </div>
      </div>
    </div>
    <button
      type="button"
      class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
    >
      Save changes
    </button>
  </div>
  <div class="col-span-2 mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:mb-0">
    <h2 class="mb-4 md:mb-6 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4">General information</h2>
    <form action="#">
      <div class="mb-4 grid gap-4 sm:mb-6 sm:grid-cols-2 md:grid-cols-3">
        <div class="col-span-1 space-y-4">
          <div>
            <label for="name" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Your name*</label>
            <input
              type="text"
              name="name"
              id="fullname"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. Bonnie Green"
              required=""
            />
          </div>
          <div>
            <label for="zip" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">ZIP/Postal code</label>
            <input
              type="number"
              name="zip"
              id="zip"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. 123456"
              required=""
            />
          </div>
          <div>
            <label for="country" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Country*</label>
            <input
              type="text"
              name="country"
              id="country"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="United States"
              required=""
            />
          </div>
          <div>
            <label for="city" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Town/City*</label>
            <input
              type="text"
              name="city"
              id="city"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. Sacramento"
              required=""
            />
          </div>
        </div>
        <div class="col-span-1 space-y-4">
          <div>
            <label for="address" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Full address*</label>
            <input
              type="text"
              name="address"
              id="address"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="174 Cayuga Ave, San Francisco, CA 94112"
              required=""
            />
          </div>
          <div>
            <label for="organization" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Your organization</label>
            <input
              type="text"
              name="organization"
              id="organization"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Ex. Flowbite LLC"
              required=""
            />
          </div>
          <div>
            <label for="department" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Department</label>
            <input
              type="text"
              name="department"
              id="department"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Marketing"
              required=""
            />
          </div>
          <div>
            <label for="account-type" class="mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white"
              >Account Type<button data-tooltip-target="tooltip-account-type-description" class="ms-1 text-gray-400 hover:text-gray-900 dark:hover:text-white" type="button">
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="sr-only">Show information</span>
              </button></label
            >
            <select
              id="account-type"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            >
              <option selected>Choose your account type</option>
              <option value="personal">Personal</option>
              <option value="business">Business</option>
              <option value="education">Education/University</option>
            </select>
            <div
              id="tooltip-account-type-description"
              role="tooltip"
              class="tooltip invisible absolute z-10 inline-block w-72 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity duration-300 dark:bg-gray-600"
            >
              This indicates the classification of your account, defining the set of features, and access rights associated with it.
              <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
          </div>
        </div>
        <div class="col-span-1 sm:col-span-2 md:col-span-1">
          <label for="message" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Biography</label>
          <textarea
            id="message"
            rows="14"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Hello, my name is Bonnie Green..."
          ></textarea>
        </div>
      </div>
      <button
        type="submit"
        class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        Save changes
      </button>
    </form>
  </div>
  <div class="col-span-2 mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:mb-0">
    <h2 class="mb-4 md:mb-6 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4">Social</h2>
    <form action="#">
      <div class="mb-4 grid gap-4 sm:mb-6 sm:grid-cols-2">
        <div>
          <label for="facebook" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Facebook</label>
          <input
            type="text"
            name="facebook"
            id="facebook"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your facebook account"
            required=""
          />
        </div>
        <div>
          <label for="dribbble" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Dribbble</label>
          <input
            type="text"
            name="dribbble"
            id="dribbble"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your dribbble account"
            required=""
          />
        </div>
        <div>
          <label for="twitter" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Twitter</label>
          <input
            type="text"
            name="twitter"
            id="twitter"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your twitter account"
            required=""
          />
        </div>
        <div>
          <label for="behance" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Behance</label>
          <input
            type="text"
            name="behance"
            id="behance"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your behance account"
            required=""
          />
        </div>
        <div>
          <label for="github" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Github</label>
          <input
            type="text"
            name="github"
            id="github"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your github account"
            required=""
          />
        </div>
        <div>
          <label for="pinterest" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Pinterest</label>
          <input
            type="text"
            name="pinterest"
            id="pinterest"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your pinterest account"
            required=""
          />
        </div>
        <div>
          <label for="instagram" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Instagram</label>
          <input
            type="text"
            name="instagram"
            id="instagram"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your instagram account"
            required=""
          />
        </div>
        <div>
          <label for="tiktok" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">TikTok</label>
          <input
            type="text"
            name="tiktok"
            id="tiktok"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Enter your tiktok account"
            required=""
          />
        </div>
      </div>
      <button
        type="submit"
        class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        Save changes
      </button>
    </form>
  </div>
  <div class="col-span-2 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
    <h2 class="mb-4 md:mb-6 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4">Password</h2>
    <form action="#">
      <div class="mb-4 grid gap-4 sm:mb-6 sm:grid-cols-2">
        <div class="col-span-1 space-y-4">
          <div>
            <label for="current-password" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Current password</label>
            <input
              type="password"
              name="current-password"
              id="current-password"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Enter your current password"
              required=""
            />
          </div>
          <div>
            <label for="new-password" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Your new password</label>
            <input
              type="password"
              name="new-password"
              id="new-password"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Enter your new password"
              required=""
            />
          </div>
          <div>
            <label for="confirm-password" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Confirm new password</label>
            <input
              type="password"
              name="confirm-password"
              id="confirm-password"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Confirm new password"
              required=""
            />
          </div>
        </div>
        <div class="col-span-1 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
          <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-white">Password requirements:</h3>
          <p class="mb-4 text-sm font-normal text-gray-500 dark:text-gray-400">Ensure that these requirements are met:</p>
          <ul class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
            <li class="flex items-center">
              <svg class="me-2 h-4 w-4 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
              </svg>
              At least 10 characters (and up to 100 characters)
            </li>
            <li class="flex items-center">
              <svg class="me-2 h-4 w-4 shrink-0 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
              </svg>
              At least one lowercase character
            </li>
            <li class="flex items-center">
              <svg class="me-2 h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm7.7-3.7a1 1 0 0 0-1.4 1.4l2.3 2.3-2.3 2.3a1 1 0 1 0 1.4 1.4l2.3-2.3 2.3 2.3a1 1 0 0 0 1.4-1.4L13.4 12l2.3-2.3a1 1 0 0 0-1.4-1.4L12 10.6 9.7 8.3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Inclusion of at least one special character, e.g., ! @ # ?
            </li>
            <li class="flex items-center">
              <svg class="me-2 h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm7.7-3.7a1 1 0 0 0-1.4 1.4l2.3 2.3-2.3 2.3a1 1 0 1 0 1.4 1.4l2.3-2.3 2.3 2.3a1 1 0 0 0 1.4-1.4L13.4 12l2.3-2.3a1 1 0 0 0-1.4-1.4L12 10.6 9.7 8.3Z"
                  clip-rule="evenodd"
                />
              </svg>
              Significantly different from your previous passwords
            </li>
          </ul>
        </div>
      </div>
      <button
        type="submit"
        class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
      >
        Save changes
      </button>
    </form>
  </div>
</div>
</div>
