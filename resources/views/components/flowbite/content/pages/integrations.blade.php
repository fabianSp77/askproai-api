{{-- Flowbite Component: integrations --}}
@props(['title' => 'Tailwind CSS Integrations - Flowbite', 'description' => 'Get started with a premium admin dashboard interface built with Tailwind CSS and Flowbite featuring over 50 example pages of charts, calendars, kanban boards, dashboards, CRUD pages, mailing systems, and more.'])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="items-center justify-between px-4 pt-4 sm:flex">
  <div class="mb-4 sm:mb-0">
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
            <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">My App</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="flex items-center">
            <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
            </svg>
            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Integrations</span>
          </div>
        </li>
      </ol>
    </nav>
    <h1 class="mb-4 sm:mb-0 text-xl font-semibold text-gray-900 dark:text-white">Integrations</h1>
  </div>
  <button
    class="inline-flex w-full items-center justify-center rounded-lg border border-primary-700 bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:border-primary-800 hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-600 dark:bg-primary-600 dark:hover:border-primary-700 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
    type="button"
    id="newIntegrationButton"
    data-modal-target="newIntegrationModal"
    data-modal-toggle="newIntegrationModal"
  >
    <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
    </svg>

    Add integration
  </button>
</div>
<div class="grid gap-4 p-4 sm:grid-cols-2 md:grid-cols-3 2xl:grid-cols-4">
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-7 text-gray-900 dark:text-white" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
          <path
            fill="currentColor"
            d="M 14.728516 4.0078125 C 10.197246 4.3417754 1.9458438 14.28525 5.1054688 16.953125 L 5.8945312 17.623047 C 5.6815312 18.194047 5.6067813 18.808062 5.6757812 19.414062 C 5.8557812 21.199063 7.5991562 22.644578 9.2851562 22.642578 C 12.371156 29.755578 23.605672 29.766734 26.513672 22.802734 C 26.605672 22.562734 27 21.485156 27 20.535156 C 27 19.585156 26.460234 19.185547 26.115234 19.185547 C 25.936234 18.558547 25.820516 18.486406 25.603516 18.191406 C 25.734516 17.995406 26.420453 16.907906 25.439453 15.878906 C 24.883453 15.294906 23.775625 14.892156 23.390625 14.785156 C 23.310625 14.176156 23.639234 11.646656 22.240234 10.347656 C 23.351234 9.1966563 24.002 7.9288437 24 6.8398438 C 23.997 4.7468438 21.470734 4.1128281 18.302734 5.4238281 L 17.630859 5.7089844 C 17.627859 5.7059844 16.416438 4.5199063 16.398438 4.5039062 C 15.947062 4.1101563 15.37584 3.9601035 14.728516 4.0078125 z M 15.140625 4.8730469 C 15.270432 4.8793362 15.393688 4.898875 15.507812 4.9296875 C 15.804813 5.0136875 16.787109 6.1523438 16.787109 6.1523438 C 16.787109 6.1523438 14.961531 7.1641719 13.269531 8.5761719 C 10.988531 10.333172 9.266375 12.885297 8.234375 15.654297 C 7.424375 15.812297 6.7084844 16.272203 6.2714844 16.908203 C 6.0104844 16.690203 5.5244531 16.266469 5.4394531 16.105469 C 4.7444531 14.777469 6.19975 12.203047 7.21875 10.748047 C 9.579375 7.3786719 13.193525 4.7787073 15.140625 4.8730469 z M 18.326172 8.1425781 C 18.349172 8.1405781 18.367141 8.1596406 18.369141 8.1816406 C 18.370141 8.1956406 18.364516 8.2077969 18.353516 8.2167969 C 18.199516 8.3357969 18.060406 8.4758594 17.941406 8.6308594 C 17.928406 8.6488594 17.933172 8.6725469 17.951172 8.6855469 C 17.958172 8.6905469 17.965609 8.6923594 17.974609 8.6933594 C 18.632609 8.6983594 19.559016 8.9285781 20.166016 9.2675781 C 20.207016 9.2905781 20.178813 9.369375 20.132812 9.359375 C 16.414813 8.507375 13.552406 10.351922 12.941406 10.794922 C 12.922406 10.806922 12.898719 10.80025 12.886719 10.78125 C 12.876719 10.76625 12.877672 10.748375 12.888672 10.734375 L 12.884766 10.734375 C 13.779766 9.700375 14.882141 8.801875 15.869141 8.296875 C 15.888141 8.285875 15.910875 8.2915469 15.921875 8.3105469 C 15.928875 8.3225469 15.928875 8.3366094 15.921875 8.3496094 C 15.843875 8.4916094 15.692531 8.7953906 15.644531 9.0253906 C 15.639531 9.0473906 15.653781 9.0692188 15.675781 9.0742188 C 15.686781 9.0772188 15.698031 9.0743594 15.707031 9.0683594 C 16.321031 8.6493594 17.390172 8.2005781 18.326172 8.1425781 z M 21.507812 11.361328 C 21.631563 11.381828 21.751375 11.427094 21.859375 11.496094 C 22.394375 11.851094 22.468094 12.710844 22.496094 13.339844 C 22.512094 13.698844 22.555313 14.568406 22.570312 14.816406 C 22.604312 15.386406 22.754594 15.467453 23.058594 15.564453 C 23.229594 15.620453 23.386141 15.662516 23.619141 15.728516 C 24.325141 15.926516 24.593328 16.231234 24.861328 16.490234 C 24.997328 16.624234 25.087234 16.797328 25.115234 16.986328 C 25.198234 17.594328 24.792266 18.241828 23.322266 18.923828 C 20.825266 20.082828 18.314172 19.695297 17.951172 19.654297 C 16.874172 19.509297 16.26025 20.902469 16.90625 21.855469 C 18.11625 23.641469 23.448094 22.924844 24.996094 20.714844 C 25.033094 20.661844 25.002031 20.628156 24.957031 20.660156 C 22.724031 22.188156 19.770797 22.702781 18.091797 22.050781 C 17.836797 21.951781 17.303281 21.707156 17.238281 21.160156 C 19.569281 21.881156 21.033203 21.199219 21.033203 21.199219 C 21.033203 21.199219 21.141906 21.0485 21.003906 21.0625 C 21.003906 21.0625 19.093109 21.344594 17.287109 20.683594 C 17.619109 19.605594 18.729453 21.009313 22.439453 19.945312 C 23.256453 19.711312 24.181578 19.354359 25.017578 18.693359 C 25.264578 18.950359 25.473875 19.442672 25.546875 19.888672 C 25.742875 19.853672 26.308203 19.859469 26.158203 20.855469 C 25.982203 21.917469 25.532437 22.780219 24.773438 23.574219 C 24.299437 24.088219 23.734422 24.510312 23.107422 24.820312 C 22.758422 25.003312 22.395484 25.156297 22.021484 25.279297 C 19.161484 26.213297 16.231156 25.186469 15.285156 22.980469 C 15.208156 22.812469 15.145703 22.637984 15.095703 22.458984 C 14.692703 21.004984 15.035516 19.260109 16.103516 18.162109 C 16.169516 18.092109 16.236328 18.01025 16.236328 17.90625 C 16.227328 17.81725 16.190812 17.733062 16.132812 17.664062 C 15.758812 17.123062 14.466563 16.199109 14.726562 14.412109 C 14.913562 13.129109 16.035031 12.225297 17.082031 12.279297 L 17.349609 12.294922 C 17.802609 12.321922 18.198266 12.379531 18.572266 12.394531 C 19.197266 12.421531 19.758828 12.331344 20.423828 11.777344 C 20.648828 11.590344 20.828812 11.427953 21.132812 11.376953 C 21.256813 11.345953 21.384062 11.340828 21.507812 11.361328 z M 21.306641 13.529297 C 20.973641 13.583297 20.961078 13.997359 21.080078 14.568359 C 21.147078 14.889359 21.266391 15.162031 21.400391 15.332031 C 21.565391 15.311031 21.731484 15.311031 21.896484 15.332031 C 21.985484 15.127031 22.001875 14.775578 21.921875 14.392578 C 21.802875 13.822578 21.639641 13.475297 21.306641 13.529297 z M 17.488281 15.083984 C 17.206156 15.117984 16.931812 15.222984 16.695312 15.396484 C 16.535312 15.513484 16.38425 15.675437 16.40625 15.773438 C 16.45125 15.973437 16.942234 15.628891 17.615234 15.587891 C 17.989234 15.564891 18.299109 15.681156 18.537109 15.785156 C 18.776109 15.892156 18.922469 15.960391 18.980469 15.900391 C 19.081469 15.796391 18.794031 15.397219 18.332031 15.199219 C 18.061031 15.086719 17.770406 15.049984 17.488281 15.083984 z M 21.789062 15.878906 L 21.789062 15.880859 C 21.380063 15.873859 21.364391 16.725422 21.775391 16.732422 C 22.186391 16.739422 22.201063 15.886906 21.789062 15.878906 z M 18.056641 16.060547 C 17.571641 16.136547 17.253937 16.408609 17.335938 16.599609 C 17.383937 16.617609 17.398281 16.642641 17.613281 16.556641 C 17.932281 16.436641 18.278234 16.401125 18.615234 16.453125 C 18.771234 16.471125 18.844906 16.481734 18.878906 16.427734 C 18.956906 16.308734 18.574641 15.999547 18.056641 16.060547 z M 20.410156 16.451172 C 20.315906 16.462172 20.23275 16.506859 20.1875 16.599609 C 20.005948 16.968698 20.767752 17.339063 20.951172 16.974609 C 21.086922 16.699359 20.692906 16.418172 20.410156 16.451172 z M 8.8964844 16.480469 C 9.8666833 16.511085 10.816984 17.196219 11.044922 18.667969 C 11.287922 20.251969 10.794703 21.794812 9.2207031 21.757812 L 9.2226562 21.759766 C 8.0026562 21.726766 6.6856875 20.628172 6.5546875 19.326172 C 6.3690625 17.482859 7.6490857 16.441105 8.8964844 16.480469 z M 8.8261719 17.400391 C 8.4667969 17.389641 8.1212344 17.549672 7.9277344 17.763672 C 7.4617344 18.276672 7.4611563 19.021781 7.6601562 19.050781 C 7.8881562 19.080781 7.8786875 18.705687 8.0546875 18.429688 C 8.2606875 18.111687 8.6859063 18.020563 9.0039062 18.226562 C 9.0059063 18.227563 9.0077656 18.228469 9.0097656 18.230469 C 9.6297656 18.636469 9.0818594 19.178766 9.1308594 19.759766 C 9.2048594 20.651766 10.116156 20.634234 10.285156 20.240234 C 10.307156 20.200234 10.302438 20.151234 10.273438 20.115234 C 10.275438 20.163234 10.310703 20.047703 10.095703 20.095703 L 10.09375 20.097656 C 10.02275 20.114656 9.772625 20.176656 9.640625 19.972656 C 9.362625 19.544656 10.234781 18.883547 9.8007812 18.060547 C 9.5577812 17.593547 9.1855469 17.411141 8.8261719 17.400391 z"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Mailchimp</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-1-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-1-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-1-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-1-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li>
            <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
          </li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="me-2 h-6" viewBox="0 0 1443.061 1249.993">
          <path fill="#3777e3" d="M240.525 1249.993l240.492-416.664h962.044l-240.514 416.664z" />
          <path fill="#ffcf63" d="M962.055 833.329h481.006L962.055 0H481.017z" />
          <path fill="#11a861" d="M0 833.329l240.525 416.664 481.006-833.328L481.017 0z" />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Google Drive</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-2-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-2-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-2-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-2-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li>
            <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
          </li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="me-2 h-6" viewBox="781.361 0 944.893 873.377">
          <radialGradient id="a" cx="943.992" cy="1221.416" r=".663" gradientTransform="matrix(944.8934 0 0 -873.3772 -890717.875 1067234.75)" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ffb900" />
            <stop offset=".6" stop-color="#f95d8f" />
            <stop offset=".999" stop-color="#f95353" />
          </radialGradient>
          <path
            fill="url(#a)"
            d="M1520.766 462.371c-113.508 0-205.508 92-205.508 205.488 0 113.499 92 205.518 205.508 205.518 113.489 0 205.488-92.019 205.488-205.518 0-113.488-91.999-205.488-205.488-205.488zm-533.907.01c-113.489.01-205.498 91.99-205.498 205.488 0 113.489 92.009 205.498 205.498 205.498 113.498 0 205.508-92.009 205.508-205.498 0-113.499-92.01-205.488-205.518-205.488h.01zm472.447-256.883c0 113.489-91.999 205.518-205.488 205.518-113.508 0-205.508-92.029-205.508-205.518S1140.31 0 1253.817 0c113.489 0 205.479 92.009 205.479 205.498h.01z"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Asana</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-3-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-3-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-3-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-3-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
          <path
            d="M221.867 140.748a8.534 8.534 0 0 1-17.067 0V64a8.534 8.534 0 0 1 17.067 0v76.748zm-2.978 53.413c-1.319 1.129-32.93 27.655-90.889 27.655-57.958 0-89.568-26.527-90.887-27.656a8.535 8.535 0 0 1-.925-12.033 8.53 8.53 0 0 1 12.013-.942c.501.42 28.729 23.563 79.8 23.563 51.712 0 79.503-23.31 79.778-23.545 3.571-3.067 8.968-2.655 12.033.925a8.534 8.534 0 0 1-.923 12.033zM34.133 64A8.534 8.534 0 0 1 51.2 64v76.748a8.534 8.534 0 0 1-17.067 0V64zm42.668-17.067a8.534 8.534 0 0 1 17.066 0v114.001a8.534 8.534 0 0 1-17.066 0v-114zm42.666-4.318A8.532 8.532 0 0 1 128 34.082a8.532 8.532 0 0 1 8.534 8.533v123.733a8.534 8.534 0 0 1-17.067 0V42.615zm42.667 4.318a8.534 8.534 0 0 1 17.066 0v114.001a8.534 8.534 0 0 1-17.066 0v-114zM224 0H32C14.327 0 0 14.327 0 32v192c0 17.672 14.327 32 32 32h192c17.673 0 32-14.328 32-32V32c0-17.673-14.327-32-32-32z"
            fill="#1F8DED"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Intercom</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-4-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-4-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-4-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-4-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 42.4 39.5">
          <path fill="#0062ff" d="M10.6 1.7L0 8.5l10.6 6.7 10.6-6.7zm21.2 0L21.2 8.5l10.6 6.7 10.6-6.7zM0 22l10.6 6.8L21.2 22l-10.6-6.8zm31.8-6.8L21.2 22l10.6 6.8L42.4 22zM10.6 31l10.6 6.8L31.8 31l-10.6-6.7z" />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Dropbox</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-5-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-5-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-5-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-5-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 256 292" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
          <path
            d="M223.774 57.34c-.201-1.46-1.48-2.268-2.537-2.357-1.055-.088-23.383-1.743-23.383-1.743s-15.507-15.395-17.209-17.099c-1.703-1.703-5.029-1.185-6.32-.805-.19.056-3.388 1.043-8.678 2.68-5.18-14.906-14.322-28.604-30.405-28.604-.444 0-.901.018-1.358.044C129.31 3.407 123.644.779 118.75.779c-37.465 0-55.364 46.835-60.976 70.635-14.558 4.511-24.9 7.718-26.221 8.133-8.126 2.549-8.383 2.805-9.45 10.462C21.3 95.806.038 260.235.038 260.235l165.678 31.042 89.77-19.42S223.973 58.8 223.775 57.34zM156.49 40.848l-14.019 4.339c.005-.988.01-1.96.01-3.023 0-9.264-1.286-16.723-3.349-22.636 8.287 1.04 13.806 10.469 17.358 21.32zm-27.638-19.483c2.304 5.773 3.802 14.058 3.802 25.238 0 .572-.005 1.095-.01 1.624-9.117 2.824-19.024 5.89-28.953 8.966 5.575-21.516 16.025-31.908 25.161-35.828zm-11.131-10.537c1.617 0 3.246.549 4.805 1.622-12.007 5.65-24.877 19.88-30.312 48.297l-22.886 7.088C75.694 46.16 90.81 10.828 117.72 10.828z"
            fill="#95BF46"
          />
          <path
            d="M221.237 54.983c-1.055-.088-23.383-1.743-23.383-1.743s-15.507-15.395-17.209-17.099c-.637-.634-1.496-.959-2.394-1.099l-12.527 256.233 89.762-19.418S223.972 58.8 223.774 57.34c-.201-1.46-1.48-2.268-2.537-2.357"
            fill="#5E8E3E"
          />
          <path
            d="M135.242 104.585l-11.069 32.926s-9.698-5.176-21.586-5.176c-17.428 0-18.305 10.937-18.305 13.693 0 15.038 39.2 20.8 39.2 56.024 0 27.713-17.577 45.558-41.277 45.558-28.44 0-42.984-17.7-42.984-17.7l7.615-25.16s14.95 12.835 27.565 12.835c8.243 0 11.596-6.49 11.596-11.232 0-19.616-32.16-20.491-32.16-52.724 0-27.129 19.472-53.382 58.778-53.382 15.145 0 22.627 4.338 22.627 4.338"
            fill="#FFF"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Shopify</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-6-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-6-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-6-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-6-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 256 153" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
          <path
            d="M23.759 0h208.378C245.325 0 256 10.675 256 23.863v79.541c0 13.188-10.675 23.863-23.863 23.863H157.41l10.257 25.118-45.109-25.118H23.863c-13.187 0-23.862-10.675-23.862-23.863V23.863C-.104 10.78 10.57 0 23.759 0z"
            fill="#9B5C8F"
          />
          <path
            d="M14.578 21.75c1.457-1.978 3.642-3.018 6.556-3.226 5.308-.417 8.326 2.08 9.054 7.492 3.226 21.75 6.764 40.17 10.51 55.259l22.79-43.395c2.082-3.955 4.684-6.036 7.806-6.244 4.579-.312 7.388 2.601 8.533 8.741 2.602 13.84 5.932 25.6 9.886 35.59 2.706-26.432 7.285-45.476 13.737-57.235 1.56-2.914 3.85-4.371 6.868-4.58 2.394-.207 4.579.521 6.556 2.082 1.977 1.561 3.018 3.538 3.226 5.932.104 1.873-.208 3.434-1.04 4.995-4.059 7.493-7.39 20.085-10.095 37.567-2.601 16.963-3.538 30.18-2.914 39.65.209 2.6-.208 4.89-1.248 6.868-1.25 2.289-3.122 3.538-5.516 3.746-2.706.208-5.515-1.04-8.221-3.85-9.678-9.887-17.379-24.664-22.998-44.332-6.765 13.32-11.76 23.31-14.986 29.97-6.14 11.76-11.343 17.796-15.714 18.108-2.81.208-5.203-2.186-7.284-7.18-5.307-13.633-11.031-39.962-17.17-78.986-.417-2.706.207-5.1 1.664-6.972zm223.636 16.338c-3.746-6.556-9.262-10.51-16.65-12.072-1.978-.416-3.85-.624-5.62-.624-9.99 0-18.107 5.203-24.455 15.61-5.412 8.845-8.117 18.627-8.117 29.346 0 8.013 1.665 14.881 4.995 20.605 3.746 6.556 9.262 10.51 16.65 12.071 1.977.417 3.85.625 5.62.625 10.094 0 18.211-5.203 24.455-15.61 5.411-8.95 8.117-18.732 8.117-29.45.104-8.117-1.665-14.882-4.995-20.501zm-13.112 28.826c-1.457 6.868-4.059 11.967-7.91 15.401-3.017 2.706-5.827 3.85-8.428 3.33-2.498-.52-4.58-2.705-6.14-6.764-1.25-3.226-1.873-6.452-1.873-9.47 0-2.601.208-5.203.728-7.596.937-4.267 2.706-8.43 5.515-12.384 3.435-5.1 7.077-7.18 10.823-6.452 2.498.52 4.58 2.706 6.14 6.764 1.249 3.226 1.873 6.452 1.873 9.47 0 2.706-.208 5.307-.728 7.7zm-52.033-28.826c-3.746-6.556-9.366-10.51-16.65-12.072-1.977-.416-3.85-.624-5.62-.624-9.99 0-18.107 5.203-24.455 15.61-5.411 8.845-8.117 18.627-8.117 29.346 0 8.013 1.665 14.881 4.995 20.605 3.746 6.556 9.262 10.51 16.65 12.071 1.978.417 3.85.625 5.62.625 10.094 0 18.211-5.203 24.455-15.61 5.412-8.95 8.117-18.732 8.117-29.45 0-8.117-1.665-14.882-4.995-20.501zm-13.216 28.826c-1.457 6.868-4.059 11.967-7.909 15.401-3.018 2.706-5.828 3.85-8.43 3.33-2.497-.52-4.578-2.705-6.14-6.764-1.248-3.226-1.872-6.452-1.872-9.47 0-2.601.208-5.203.728-7.596.937-4.267 2.706-8.43 5.516-12.384 3.434-5.1 7.076-7.18 10.822-6.452 2.498.52 4.58 2.706 6.14 6.764 1.25 3.226 1.873 6.452 1.873 9.47.105 2.706-.208 5.307-.728 7.7z"
            fill="#FFF"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Woocommerce</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-7-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-7-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-7-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-7-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-7" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g clip-path="url(#clip0_1684_75567)">
            <path
              d="M14 14C14 13.0717 14.3687 12.1815 15.0251 11.5251C15.6815 10.8687 16.5717 10.5 17.5 10.5C18.4283 10.5 19.3185 10.8687 19.9749 11.5251C20.6313 12.1815 21 13.0717 21 14C21 14.9283 20.6313 15.8185 19.9749 16.4749C19.3185 17.1313 18.4283 17.5 17.5 17.5C16.5717 17.5 15.6815 17.1313 15.0251 16.4749C14.3687 15.8185 14 14.9283 14 14V14Z"
              fill="#1ABCFE"
            />
            <path
              d="M7 21C7 20.0717 7.36875 19.1815 8.02513 18.5251C8.6815 17.8687 9.57174 17.5 10.5 17.5H14V21C14 21.9283 13.6313 22.8185 12.9749 23.4749C12.3185 24.1312 11.4283 24.5 10.5 24.5C9.57174 24.5 8.6815 24.1312 8.02513 23.4749C7.36875 22.8185 7 21.9283 7 21V21Z"
              fill="#0ACF83"
            />
            <path d="M14 3.5V10.5H17.5C18.4283 10.5 19.3185 10.1313 19.9749 9.47487C20.6313 8.8185 21 7.92826 21 7C21 6.07174 20.6313 5.1815 19.9749 4.52513C19.3185 3.86875 18.4283 3.5 17.5 3.5L14 3.5Z" fill="#FF7262" />
            <path d="M7 7C7 7.92826 7.36875 8.8185 8.02513 9.47487C8.6815 10.1313 9.57174 10.5 10.5 10.5H14V3.5H10.5C9.57174 3.5 8.6815 3.86875 8.02513 4.52513C7.36875 5.1815 7 6.07174 7 7V7Z" fill="#F24E1E" />
            <path d="M7 14C7 14.9283 7.36875 15.8185 8.02513 16.4749C8.6815 17.1313 9.57174 17.5 10.5 17.5H14V10.5H10.5C9.57174 10.5 8.6815 10.8687 8.02513 11.5251C7.36875 12.1815 7 13.0717 7 14V14Z" fill="#A259FF" />
          </g>
          <defs>
            <clipPath id="clip0_1684_75567">
              <rect width="14" height="21" fill="white" transform="translate(7 3.5)" />
            </clipPath>
          </defs>
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Figma</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-8-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-8-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-8-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-8-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-7" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M20.5457 11.771C20.4917 12.1432 20.2463 12.6489 19.9647 13.0885C19.6094 13.6432 19.0457 14.0317 18.4005 14.1667L15.5584 14.761C15.1016 14.8565 14.6874 15.0955 14.3764 15.4432L12.5129 17.5262C12.1627 17.9177 11.9179 17.8243 11.9179 17.2993C11.9137 17.3187 10.993 19.6929 13.4328 21.1002C14.3702 21.6409 15.7197 21.4466 16.6571 20.9059L21.6241 18.0409C23.4817 16.9695 24.7932 15.1569 25.2292 13.0587C25.2464 12.9757 25.2597 12.8924 25.2741 12.8092L20.5457 11.771Z"
            fill="url(#paint0_linear_1684_75606)"
          />
          <path
            d="M19.2573 8.90907C20.1947 9.44978 20.5777 10.2548 20.5777 11.3362C20.5777 11.483 20.5662 11.6281 20.5454 11.7709L22.5468 12.6306L25.2738 12.8091C25.623 10.7882 24.9387 8.70802 23.7119 7.05457C22.7887 5.81028 21.6007 4.73047 20.1726 3.90669C19.0124 3.23751 17.8068 2.79661 16.5955 2.55469L15.2346 4.3171L14.8047 6.34082L19.2573 8.90907Z"
            fill="url(#paint1_linear_1684_75606)"
          />
          <path
            d="M3.23839 10.3922C3.23788 10.3938 3.23929 10.3942 3.23982 10.3926C3.34484 10.0776 3.47652 9.72623 3.64092 9.35082C4.49967 7.38979 6.15454 6.08189 8.19043 5.41503C10.2263 4.74819 12.4492 4.98194 14.3047 6.05218L14.8052 6.34086L16.596 2.55474C10.9001 1.4171 5.07859 4.76349 3.24563 10.3702C3.24476 10.3729 3.24157 10.3823 3.23839 10.3922Z"
            fill="url(#paint2_linear_1684_75606)"
          />
          <path
            d="M16.4641 20.9057C15.5266 21.4464 14.3717 21.4464 13.4342 20.9057C13.307 20.8323 13.187 20.7497 13.0736 20.6604L11.4249 21.7939L10.0039 24.0901C11.5811 25.4027 13.6292 25.8246 15.6759 25.5899C17.2161 25.4133 18.7461 24.9254 20.1743 24.1016C21.3344 23.4325 22.3194 22.6098 23.1348 21.6828L22.2875 19.6241L20.9166 18.3374L16.4641 20.9057Z"
            fill="url(#paint3_linear_1684_75606)"
          />
          <path
            d="M13.0739 20.6603C12.3516 20.0914 11.9196 19.2188 11.9196 18.2842V18.191V10.4069C11.9196 9.96672 12.0493 9.8919 12.4309 10.112C11.8432 9.773 10.4854 8.60922 8.80899 9.5762C7.87156 10.1169 7.09961 11.3105 7.09961 12.3919V18.1219C7.09961 20.2648 8.20952 22.5001 9.81036 23.9264C9.87368 23.9828 9.93924 24.036 10.0042 24.09L13.0739 20.6603Z"
            fill="url(#paint4_linear_1684_75606)"
          />
          <path
            d="M23.0191 6.20711C23.018 6.20587 23.0169 6.20687 23.018 6.20811C23.2386 6.45647 23.4773 6.74611 23.7206 7.07606C24.9911 8.79957 25.4658 10.9825 25.026 13.0773C24.5861 15.1722 23.272 16.9786 21.4165 18.0489L20.916 18.3376L23.1342 21.6829C26.9683 17.3236 26.9782 10.6136 23.0346 6.22435C23.0327 6.22225 23.0261 6.21482 23.0191 6.20711Z"
            fill="url(#paint5_linear_1684_75606)"
          />
          <path
            d="M7.2935 12.3921C7.2935 11.3106 7.87098 10.3113 8.80842 9.77063C8.93567 9.69723 9.06719 9.6347 9.20136 9.58122L9.04315 7.58804L7.93158 5.30762C6.00518 6.01596 4.44693 7.47985 3.62703 9.36799C3.01002 10.7889 2.66798 12.3566 2.66797 14.0042C2.66797 15.3425 2.88858 16.6061 3.28451 17.7751L5.49272 18.0713L7.2935 17.5286V12.3921V12.3921Z"
            fill="url(#paint6_linear_1684_75606)"
          />
          <path
            d="M9.20143 9.58136C10.0557 9.24093 11.0281 9.30347 11.8383 9.77078L11.9191 9.81738L18.4054 13.5587C18.8604 13.8211 18.819 14.0794 18.3048 14.1869L18.6789 14.1087C19.1713 14.0057 19.6211 13.7541 19.9651 13.3874C20.5566 12.757 20.7737 11.9964 20.7737 11.3363C20.7737 10.2549 20.1962 9.25559 19.2588 8.71488L14.2917 5.84988C12.4342 4.77843 10.2072 4.54996 8.17042 5.22188C8.08985 5.24845 8.01098 5.27859 7.93164 5.30776L9.20143 9.58136Z"
            fill="url(#paint7_linear_1684_75606)"
          />
          <path
            d="M16.7578 25.4137C16.7594 25.4133 16.7591 25.4119 16.7575 25.4122C16.4319 25.4788 16.0615 25.5406 15.6539 25.5861C13.5246 25.8236 11.395 25.1429 9.79898 23.7149C8.20299 22.2868 7.29415 20.2467 7.29416 18.1062L7.29415 17.5288L3.28516 17.7753C5.14698 23.2723 10.9585 26.6359 16.7352 25.4184C16.7379 25.4178 16.7476 25.4159 16.7578 25.4137Z"
            fill="url(#paint8_linear_1684_75606)"
          />
          <defs>
            <linearGradient id="paint0_linear_1684_75606" x1="17.294" y1="19.6659" x2="19.5891" y2="11.6719" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1724C9" />
              <stop offset="1" stop-color="#1C64F2" />
            </linearGradient>
            <linearGradient id="paint1_linear_1684_75606" x1="22.5797" y1="9.91707" x2="16.5424" y2="3.90103" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1C64F2" />
              <stop offset="1" stop-color="#0092FF" />
            </linearGradient>
            <linearGradient id="paint2_linear_1684_75606" x1="14.4537" y1="5.99478" x2="5.34622" y2="6.57027" gradientUnits="userSpaceOnUse">
              <stop stop-color="#0092FF" />
              <stop offset="1" stop-color="#45B2FF" />
            </linearGradient>
            <linearGradient id="paint3_linear_1684_75606" x1="13.839" y1="23.2333" x2="22.018" y2="21.0263" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1C64F2" />
              <stop offset="1" stop-color="#0092FF" />
            </linearGradient>
            <linearGradient id="paint4_linear_1684_75606" x1="7.96872" y1="13.7686" x2="13.7756" y2="19.7858" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1724C9" />
              <stop offset="1" stop-color="#1C64F2" />
            </linearGradient>
            <linearGradient id="paint5_linear_1684_75606" x1="21.2254" y1="18.1096" x2="25.277" y2="9.94204" gradientUnits="userSpaceOnUse">
              <stop stop-color="#0092FF" />
              <stop offset="1" stop-color="#45B2FF" />
            </linearGradient>
            <linearGradient id="paint6_linear_1684_75606" x1="6.57915" y1="8.99144" x2="4.41108" y2="17.1404" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1C64F2" />
              <stop offset="1" stop-color="#0092FF" />
            </linearGradient>
            <linearGradient id="paint7_linear_1684_75606" x1="17.6475" y1="8.60399" x2="9.72471" y2="10.5766" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1724C9" />
              <stop offset="1" stop-color="#1C64F2" />
            </linearGradient>
            <linearGradient id="paint8_linear_1684_75606" x1="7.34023" y1="17.9063" x2="12.3903" y2="25.5028" gradientUnits="userSpaceOnUse">
              <stop stop-color="#0092FF" />
              <stop offset="1" stop-color="#45B2FF" />
            </linearGradient>
          </defs>
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Flowbite</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-9-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-9-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-9-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-9-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
          <path
            d="M165.964 15.838c-3.89-11.975-16.752-18.528-28.725-14.636-11.975 3.89-18.528 16.752-14.636 28.725l58.947 181.365c4.048 11.187 16.132 17.473 27.732 14.135 12.1-3.483 19.475-16.334 15.614-28.217L165.964 15.838"
            fill="#DFA22F"
          />
          <path
            d="M74.626 45.516C70.734 33.542 57.873 26.989 45.9 30.879 33.924 34.77 27.37 47.631 31.263 59.606l58.948 181.366c4.047 11.186 16.132 17.473 27.732 14.132 12.099-3.481 19.474-16.332 15.613-28.217L74.626 45.516"
            fill="#3CB187"
          />
          <path
            d="M240.162 166.045c11.975-3.89 18.526-16.75 14.636-28.726-3.89-11.973-16.752-18.527-28.725-14.636L44.708 181.632c-11.187 4.046-17.473 16.13-14.135 27.73 3.483 12.099 16.334 19.475 28.217 15.614l181.372-58.93"
            fill="#CE1E5B"
          />
          <path d="M82.508 217.27l43.347-14.084-14.086-43.352-43.35 14.09 14.089 43.347" fill="#392538" />
          <path d="M173.847 187.591c16.388-5.323 31.62-10.273 43.348-14.084l-14.088-43.36-43.35 14.09 14.09 43.354" fill="#BB242A" />
          <path
            d="M210.484 74.706c11.974-3.89 18.527-16.751 14.637-28.727-3.89-11.973-16.752-18.526-28.727-14.636L15.028 90.293C3.842 94.337-2.445 106.422.896 118.022c3.481 12.098 16.332 19.474 28.217 15.613l181.371-58.93"
            fill="#72C5CD"
          />
          <path d="M52.822 125.933c11.805-3.836 27.025-8.782 43.354-14.086-5.323-16.39-10.273-31.622-14.084-43.352l-43.36 14.092 14.09 43.346" fill="#248C73" />
          <path d="M144.16 96.256l43.356-14.088a546179.21 546179.21 0 0 0-14.089-43.36L130.07 52.9l14.09 43.356" fill="#62803A" />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Slack</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-10-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-10-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-10-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-10-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="-11.9 -2 1003.9 995.6" xmlns="http://www.w3.org/2000/svg">
          <path d="m12.1 353.9s-24-17.3 4.8-40.4l67.1-60s19.2-20.2 39.5-2.6l619.2 468.8v224.8s-.3 35.3-45.6 31.4z" fill="#2489ca" />
          <path d="m171.7 498.8-159.6 145.1s-16.4 12.2 0 34l74.1 67.4s17.6 18.9 43.6-2.6l169.2-128.3z" fill="#1070b3" />
          <path d="m451.9 500 292.7-223.5-1.9-223.6s-12.5-48.8-54.2-23.4l-389.5 354.5z" fill="#0877b9" />
          <path
            d="m697.1 976.2c17 17.4 37.6 11.7 37.6 11.7l228.1-112.4c29.2-19.9 25.1-44.6 25.1-44.6v-671.2c0-29.5-30.2-39.7-30.2-39.7l-197.7-95.3c-43.2-26.7-71.5 4.8-71.5 4.8s36.4-26.2 54.2 23.4v887.5c0 6.1-1.3 12.1-3.9 17.5-5.2 10.5-16.5 20.3-43.6 16.2z"
            fill="#3c99d4"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">VsCode</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-11-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-11-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-11-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-11-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" enable-background="new 0 0 2406.8 1923.8" viewBox="0 0 2406.8 1923.8" xmlns="http://www.w3.org/2000/svg">
          <path d="m371 1326.4-125.9 46-6.7 374.5 435.8-159.2c-149.6-50.1-258.7-138.4-303.2-261.3" fill="#fdbd39" />
          <path d="m436.9 930.3-436.9 159.6 245.1 282.6 125.9-46c-44.4-122.5-17-260.8 65.9-396.2" fill="#f69833" />
          <path d="m1172 389.8c-556.3 203.2-915 622.6-801 936.6l2014.5-735.9c-114-314-657.2-403.9-1213.5-200.7" fill="#fecf33" />
          <path d="m1584.4 1527.2c556.3-203.2 915.6-620.7 801-936.6l-2014.5 735.8c114.6 316 657.2 404 1213.5 200.8" fill="#ee6723" />
          <path d="m2385.4 590.5-2014.4 735.9c39.4 108.8 189.9 162.4 400.9 162.4 205.8 0 469.2-50.9 743.8-151.2 556.3-203.2 947.6-532.3 869.7-747.1" fill="#f69833" />
          <path d="m1984.5 428.2c-205.7 0-469.2 50.9-743.8 151.2-556.3 203.2-947.6 532.2-869.7 747l2014.5-735.9c-39.5-108.8-189.9-162.3-401-162.3" fill="#fdbd39" />
          <g fill="#ee6723">
            <path
              d="m1873.2 1766.8c-59.3 0-146.7-53.5-259.9-159.1-133.9-124.9-287.2-307.9-431.9-515.2-144.6-207.3-263.4-414.5-334.6-583.6-77.5-184.2-88.6-297.4-32.9-336.6 12.9-8.8 28.2-13.3 43.8-12.9 45.3 0 133.7 33.7 297 194.5l3.6 3.5-.5 5c-1.1 11.6-2.1 23.2-3 34.7l-1.8 22.7-16-16.1c-176-177.3-253.4-196.4-278.6-196.4-7.2 0-12.9 1.4-17 4.3-24.2 17-25.8 100.2 49.4 278.7 69.9 166 187 370.1 329.7 574.7 142.8 204.7 293.8 384.9 425.4 507.6 141.8 132.3 205.7 146.6 226.6 146.6 7.2 0 13-1.5 17.4-4.6 25.5-17.9 25.5-107-58.3-299.2l-3.9-8.8 8.4-4.6c8.4-4.6 16.6-9.1 24.2-13.5l10.1-5.7 4.7 10.6c59.4 136.2 115 309.3 42.1 360.5-12.9 8.8-28.3 13.3-44 12.9zm0-21v10.5z"
            />
            <path
              d="m1597.9 1521.4-.8 10.4c-14.1 174-51.1 383.2-147.7 391.7-2 .2-4 .2-5.9.2-65.6 0-127.7-94-184.4-279.3-53.8-175.4-95.5-410.8-117.4-662.9-22-252.1-21.7-491.2.9-673.3 24.6-198.4 71.4-302 139.2-308 2-.2 4.1-.3 6.1-.3 47.6 0 119.3 53.9 192.7 304.9l-47.9 9.2c-24.5-83.8-51.2-152-77.7-199-24.5-43.3-48.4-67.2-67.1-67.2-.6 0-1.2 0-1.9.1-29.4 2.6-72.2 73.8-96 266.2-22.2 178.8-22.5 414.4-.8 663.2 21.7 248.9 62.7 480.8 115.5 653 23.6 77.1 49.5 140.7 75 183.8 23.4 39.8 46.1 61.7 63.8 61.7.6 0 1.2 0 1.8-.1 28.1-2.4 81.1-76.6 103.4-337.5z"
            />
          </g>
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Zeplin</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-12-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-12-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-12-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-12-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
          <path
            d="M159.999 128.056a76.55 76.55 0 0 1-4.915 27.024 76.745 76.745 0 0 1-27.032 4.923h-.108c-9.508-.012-18.618-1.75-27.024-4.919A76.557 76.557 0 0 1 96 128.056v-.112a76.598 76.598 0 0 1 4.91-27.02A76.492 76.492 0 0 1 127.945 96h.108a76.475 76.475 0 0 1 27.032 4.923 76.51 76.51 0 0 1 4.915 27.02v.112zm94.223-21.389h-74.716l52.829-52.833a128.518 128.518 0 0 0-13.828-16.349v-.004a129 129 0 0 0-16.345-13.816l-52.833 52.833V1.782A128.606 128.606 0 0 0 128.064 0h-.132c-7.248.004-14.347.62-21.265 1.782v74.716L53.834 23.665A127.82 127.82 0 0 0 37.497 37.49l-.028.02A128.803 128.803 0 0 0 23.66 53.834l52.837 52.833H1.782S0 120.7 0 127.956v.088c0 7.256.615 14.367 1.782 21.289h74.716l-52.837 52.833a128.91 128.91 0 0 0 30.173 30.173l52.833-52.837v74.72a129.3 129.3 0 0 0 21.24 1.778h.181a129.15 129.15 0 0 0 21.24-1.778v-74.72l52.838 52.837a128.994 128.994 0 0 0 16.341-13.82l.012-.012a129.245 129.245 0 0 0 13.816-16.341l-52.837-52.833h74.724c1.163-6.91 1.77-14 1.778-21.24v-.186c-.008-7.24-.615-14.33-1.778-21.24z"
            fill="#FF4A00"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Zapier</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-13-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-13-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-13-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-13-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="-.1 1.1 304.9 179.8" xmlns="http://www.w3.org/2000/svg">
          <path
            d="m86.4 66.4c0 3.7.4 6.7 1.1 8.9.8 2.2 1.8 4.6 3.2 7.2.5.8.7 1.6.7 2.3 0 1-.6 2-1.9 3l-6.3 4.2c-.9.6-1.8.9-2.6.9-1 0-2-.5-3-1.4-1.4-1.5-2.6-3.1-3.6-4.7-1-1.7-2-3.6-3.1-5.9-7.8 9.2-17.6 13.8-29.4 13.8-8.4 0-15.1-2.4-20-7.2s-7.4-11.2-7.4-19.2c0-8.5 3-15.4 9.1-20.6s14.2-7.8 24.5-7.8c3.4 0 6.9.3 10.6.8s7.5 1.3 11.5 2.2v-7.3c0-7.6-1.6-12.9-4.7-16-3.2-3.1-8.6-4.6-16.3-4.6-3.5 0-7.1.4-10.8 1.3s-7.3 2-10.8 3.4c-1.6.7-2.8 1.1-3.5 1.3s-1.2.3-1.6.3c-1.4 0-2.1-1-2.1-3.1v-4.9c0-1.6.2-2.8.7-3.5s1.4-1.4 2.8-2.1c3.5-1.8 7.7-3.3 12.6-4.5 4.9-1.3 10.1-1.9 15.6-1.9 11.9 0 20.6 2.7 26.2 8.1 5.5 5.4 8.3 13.6 8.3 24.6v32.4zm-40.6 15.2c3.3 0 6.7-.6 10.3-1.8s6.8-3.4 9.5-6.4c1.6-1.9 2.8-4 3.4-6.4s1-5.3 1-8.7v-4.2c-2.9-.7-6-1.3-9.2-1.7s-6.3-.6-9.4-.6c-6.7 0-11.6 1.3-14.9 4s-4.9 6.5-4.9 11.5c0 4.7 1.2 8.2 3.7 10.6 2.4 2.5 5.9 3.7 10.5 3.7zm80.3 10.8c-1.8 0-3-.3-3.8-1-.8-.6-1.5-2-2.1-3.9l-23.5-77.3c-.6-2-.9-3.3-.9-4 0-1.6.8-2.5 2.4-2.5h9.8c1.9 0 3.2.3 3.9 1 .8.6 1.4 2 2 3.9l16.8 66.2 15.6-66.2c.5-2 1.1-3.3 1.9-3.9s2.2-1 4-1h8c1.9 0 3.2.3 4 1 .8.6 1.5 2 1.9 3.9l15.8 67 17.3-67c.6-2 1.3-3.3 2-3.9.8-.6 2.1-1 3.9-1h9.3c1.6 0 2.5.8 2.5 2.5 0 .5-.1 1-.2 1.6s-.3 1.4-.7 2.5l-24.1 77.3c-.6 2-1.3 3.3-2.1 3.9s-2.1 1-3.8 1h-8.6c-1.9 0-3.2-.3-4-1s-1.5-2-1.9-4l-15.5-64.5-15.4 64.4c-.5 2-1.1 3.3-1.9 4s-2.2 1-4 1zm128.5 2.7c-5.2 0-10.4-.6-15.4-1.8s-8.9-2.5-11.5-4c-1.6-.9-2.7-1.9-3.1-2.8s-.6-1.9-.6-2.8v-5.1c0-2.1.8-3.1 2.3-3.1.6 0 1.2.1 1.8.3s1.5.6 2.5 1c3.4 1.5 7.1 2.7 11 3.5 4 .8 7.9 1.2 11.9 1.2 6.3 0 11.2-1.1 14.6-3.3s5.2-5.4 5.2-9.5c0-2.8-.9-5.1-2.7-7s-5.2-3.6-10.1-5.2l-14.5-4.5c-7.3-2.3-12.7-5.7-16-10.2-3.3-4.4-5-9.3-5-14.5 0-4.2.9-7.9 2.7-11.1s4.2-6 7.2-8.2c3-2.3 6.4-4 10.4-5.2s8.2-1.7 12.6-1.7c2.2 0 4.5.1 6.7.4 2.3.3 4.4.7 6.5 1.1 2 .5 3.9 1 5.7 1.6s3.2 1.2 4.2 1.8c1.4.8 2.4 1.6 3 2.5.6.8.9 1.9.9 3.3v4.7c0 2.1-.8 3.2-2.3 3.2-.8 0-2.1-.4-3.8-1.2-5.7-2.6-12.1-3.9-19.2-3.9-5.7 0-10.2.9-13.3 2.8s-4.7 4.8-4.7 8.9c0 2.8 1 5.2 3 7.1s5.7 3.8 11 5.5l14.2 4.5c7.2 2.3 12.4 5.5 15.5 9.6s4.6 8.8 4.6 14c0 4.3-.9 8.2-2.6 11.6-1.8 3.4-4.2 6.4-7.3 8.8-3.1 2.5-6.8 4.3-11.1 5.6-4.5 1.4-9.2 2.1-14.3 2.1z"
            fill="#252f3e"
          />
          <g clip-rule="evenodd" fill="#f90" fill-rule="evenodd">
            <path d="m273.5 143.7c-32.9 24.3-80.7 37.2-121.8 37.2-57.6 0-109.5-21.3-148.7-56.7-3.1-2.8-.3-6.6 3.4-4.4 42.4 24.6 94.7 39.5 148.8 39.5 36.5 0 76.6-7.6 113.5-23.2 5.5-2.5 10.2 3.6 4.8 7.6z" />
            <path d="m287.2 128.1c-4.2-5.4-27.8-2.6-38.5-1.3-3.2.4-3.7-2.4-.8-4.5 18.8-13.2 49.7-9.4 53.3-5 3.6 4.5-1 35.4-18.6 50.2-2.7 2.3-5.3 1.1-4.1-1.9 4-9.9 12.9-32.2 8.7-37.5z" />
          </g>
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">AWS</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-14-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-14-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-14-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-14-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="me-2 h-6" viewBox="0 0 1024 1024">
          <path fill="#3a76f0" d="M0 0v1024h1024V0H0z" />
          <path
            fill="#fff"
            d="M427.5 170.3l7.9 32a319.6 319.6 0 0 0-88.4 36.6l-16.9-28.3a347.6 347.6 0 0 1 97.4-40.3zm169 0l-7.9 32a319.6 319.6 0 0 1 88.4 36.6l17.1-28.3a350.1 350.1 0 0 0-97.6-40.3zM210.6 330a349.5 349.5 0 0 0-40.3 97.5l32 7.9a319.6 319.6 0 0 1 36.6-88.4zM193 512a318.5 318.5 0 0 1 3.6-47.8l-32.6-5a352 352 0 0 0 0 105.5l32.6-4.9A319.5 319.5 0 0 1 193 512zm500.9 301.3L677 785.1a317.8 317.8 0 0 1-88.3 36.6l7.9 32a350.3 350.3 0 0 0 97.3-40.4zM831 512a319.5 319.5 0 0 1-3.6 47.8l32.6 4.9a352 352 0 0 0 0-105.5l-32.6 5A318.5 318.5 0 0 1 831 512zm22.7 84.4l-32-7.9a319 319 0 0 1-36.6 88.5l28.3 17a348.9 348.9 0 0 0 40.3-97.6zm-293.9 231a319.1 319.1 0 0 1-95.6 0l-4.9 32.6a351.3 351.3 0 0 0 105.4 0zm209-126.2a318.1 318.1 0 0 1-67.6 67.5l19.6 26.6a355.1 355.1 0 0 0 74.6-74.3zm-67.6-446a318.6 318.6 0 0 1 67.6 67.6l26.6-19.8a354.6 354.6 0 0 0-74.4-74.4zm-446 67.6a318.6 318.6 0 0 1 67.6-67.6L303 228.6a354.6 354.6 0 0 0-74.4 74.4zm558.2 7.2l-28.3 17a317.8 317.8 0 0 1 36.6 88.3l32-7.9a348.9 348.9 0 0 0-40.3-97.4zM464.2 196.6a319.1 319.1 0 0 1 95.6 0l4.9-32.6a351.3 351.3 0 0 0-105.4 0zM272.1 804.1L204 819.9l15.9-68.1-32.1-7.5-15.9 68.1a33 33 0 0 0 24.6 39.7 34.5 34.5 0 0 0 15 0l68.1-15.7zm-77.5-89.2l32.2 7.4 11-47.2a316.2 316.2 0 0 1-35.5-86.6l-32 7.9a353.3 353.3 0 0 0 32.4 83.7zm154 71.4l-47.2 11 7.5 32.2 34.7-8.1a349 349 0 0 0 83.7 32.4l7.9-32a316.7 316.7 0 0 1-86.3-35.7zM512 226c-158 .1-285.9 128.2-285.9 286.1a286.7 286.7 0 0 0 43.9 152l-27.5 117.4L359.8 754c133.7 84.1 310.3 44 394.4-89.6s44.1-310.2-89.5-394.4A286.7 286.7 0 0 0 512 226"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Signal</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-15-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-15-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-15-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-15-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 256 255" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMinYMin meet">
          <path
            d="M18.124 127.5c0 43.296 25.16 80.711 61.646 98.442L27.594 82.986a108.965 108.965 0 0 0-9.47 44.514zm183.221-5.52c0-13.517-4.856-22.879-9.02-30.165-5.545-9.01-10.742-16.64-10.742-25.65 0-10.055 7.626-19.415 18.368-19.415.485 0 .944.06 1.417.088-19.46-17.829-45.387-28.714-73.863-28.714-38.213 0-71.832 19.606-91.39 49.302 2.566.077 4.984.13 7.039.13 11.44 0 29.15-1.387 29.15-1.387 5.897-.348 6.592 8.312.702 9.01 0 0-5.926.697-12.52 1.042L100.32 194.7l23.937-71.79-17.042-46.692c-5.89-.345-11.47-1.042-11.47-1.042-5.894-.346-5.203-9.358.691-9.01 0 0 18.064 1.388 28.811 1.388 11.44 0 29.151-1.388 29.151-1.388 5.9-.348 6.594 8.312.702 9.01 0 0-5.938.697-12.52 1.042l39.529 117.581 10.91-36.458c4.728-15.129 8.327-25.995 8.327-35.36zm-71.921 15.088l-32.818 95.363a109.376 109.376 0 0 0 30.899 4.456c12.737 0 24.952-2.202 36.323-6.2a9.605 9.605 0 0 1-.779-1.507l-33.625-92.112zm94.058-62.045c.47 3.484.737 7.224.737 11.247 0 11.1-2.073 23.577-8.317 39.178l-33.411 96.6c32.518-18.963 54.39-54.193 54.39-94.545.002-19.017-4.856-36.9-13.4-52.48zM127.505 0C57.2 0 0 57.196 0 127.5c0 70.313 57.2 127.507 127.505 127.507 70.302 0 127.51-57.194 127.51-127.507C255.014 57.196 197.808 0 127.506 0zm0 249.163c-67.08 0-121.659-54.578-121.659-121.663 0-67.08 54.576-121.654 121.659-121.654 67.078 0 121.652 54.574 121.652 121.654 0 67.085-54.574 121.663-121.652 121.663z"
            fill="#464342"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Wordpress</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-16-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-16-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-16-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-16-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" viewBox="0 0 240 234" xmlns="http://www.w3.org/2000/svg">
          <path d="M42.5 0h155C221 0 240 19 240 42.5v149c0 23.5-19 42.5-42.5 42.5h-155C19 234 0 215 0 191.5v-149C0 19 19 0 42.5 0z" fill="#001e36" />
          <g fill="#31a8ff">
            <path
              d="M54 164.1V61.2c0-.7.3-1.1 1-1.1 1.7 0 3.3 0 5.6-.1 2.4-.1 4.9-.1 7.6-.2s5.6-.1 8.7-.2 6.1-.1 9.1-.1c8.2 0 15 1 20.6 3.1 5 1.7 9.6 4.5 13.4 8.2 3.2 3.2 5.7 7.1 7.3 11.4 1.5 4.2 2.3 8.5 2.3 13 0 8.6-2 15.7-6 21.3s-9.6 9.8-16.1 12.2c-6.8 2.5-14.3 3.4-22.5 3.4-2.4 0-4 0-5-.1s-2.4-.1-4.3-.1V164c.1.7-.4 1.3-1.1 1.4H55.2c-.8 0-1.2-.4-1.2-1.3zm21.8-84.7V113c1.4.1 2.7.2 3.9.2H85c3.9 0 7.8-.6 11.5-1.8 3.2-.9 6-2.8 8.2-5.3 2.1-2.5 3.1-5.9 3.1-10.3.1-3.1-.7-6.2-2.3-8.9-1.7-2.6-4.1-4.6-7-5.7-3.7-1.5-7.7-2.1-11.8-2-2.6 0-4.9 0-6.8.1-2-.1-3.4 0-4.1.1zM192 106.9c-3-1.6-6.2-2.7-9.6-3.4-3.7-.8-7.4-1.3-11.2-1.3-2-.1-4.1.2-6 .7-1.3.3-2.4 1-3.1 2-.5.8-.8 1.8-.8 2.7s.4 1.8 1 2.6c.9 1.1 2.1 2 3.4 2.7 2.3 1.2 4.7 2.3 7.1 3.3 5.4 1.8 10.6 4.3 15.4 7.3 3.3 2.1 6 4.9 7.9 8.3 1.6 3.2 2.4 6.7 2.3 10.3.1 4.7-1.3 9.4-3.9 13.3-2.8 4-6.7 7.1-11.2 8.9-4.9 2.1-10.9 3.2-18.1 3.2-4.6 0-9.1-.4-13.6-1.3-3.5-.6-7-1.7-10.2-3.2-.7-.4-1.2-1.1-1.1-1.9v-17.4c0-.3.1-.7.4-.9s.6-.1.9.1c3.9 2.3 8 3.9 12.4 4.9 3.8 1 7.8 1.5 11.8 1.5 3.8 0 6.5-.5 8.3-1.4 1.6-.7 2.7-2.4 2.7-4.2 0-1.4-.8-2.7-2.4-4s-4.9-2.8-9.8-4.7c-5.1-1.8-9.8-4.2-14.2-7.2-3.1-2.2-5.7-5.1-7.6-8.5-1.6-3.2-2.4-6.7-2.3-10.2 0-4.3 1.2-8.4 3.4-12.1 2.5-4 6.2-7.2 10.5-9.2 4.7-2.4 10.6-3.5 17.7-3.5 4.1 0 8.3.3 12.4.9 3 .4 5.9 1.2 8.6 2.3.4.1.8.5 1 .9.1.4.2.8.2 1.2v16.3c0 .4-.2.8-.5 1-.9.2-1.4.2-1.8 0z"
            />
          </g>
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Photoshop</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-17-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-17-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-17-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-17-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
  <!-- Card -->
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center">
        <svg class="me-2 h-6" preserveAspectRatio="xMidYMid" xmlns="http://www.w3.org/2000/svg" viewBox="-1.871 -0.4069999999999627 259.721 257.849">
          <linearGradient id="a" x1="-.828%" x2="57.636%" y1="7.652%" y2="78.411%">
            <stop offset="0" stop-color="#41d1ff" />
            <stop offset="1" stop-color="#bd34fe" />
          </linearGradient>
          <linearGradient id="b" x1="43.376%" x2="50.316%" y1="2.242%" y2="89.03%">
            <stop offset="0" stop-color="#ffea83" />
            <stop offset=".083" stop-color="#ffdd35" />
            <stop offset="1" stop-color="#ffa800" />
          </linearGradient>
          <path
            d="M255.153 37.938L134.897 252.976c-2.483 4.44-8.862 4.466-11.382.048L.875 37.958c-2.746-4.814 1.371-10.646 6.827-9.67l120.385 21.517a6.537 6.537 0 0 0 2.322-.004l117.867-21.483c5.438-.991 9.574 4.796 6.877 9.62z"
            fill="url(#a)"
          />
          <path
            d="M185.432.063L96.44 17.501a3.268 3.268 0 0 0-2.634 3.014l-5.474 92.456a3.268 3.268 0 0 0 3.997 3.378l24.777-5.718c2.318-.535 4.413 1.507 3.936 3.838l-7.361 36.047c-.495 2.426 1.782 4.5 4.151 3.78l15.304-4.649c2.372-.72 4.652 1.36 4.15 3.788l-11.698 56.621c-.732 3.542 3.979 5.473 5.943 2.437l1.313-2.028 72.516-144.72c1.215-2.423-.88-5.186-3.54-4.672l-25.505 4.922c-2.396.462-4.435-1.77-3.759-4.114l16.646-57.705c.677-2.35-1.37-4.583-3.769-4.113z"
            fill="url(#b)"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">Vite</span>
      </div>
      <!-- Dropdown button -->
      <button
        id="integration-18-dropdown-button"
        type="button"
        data-dropdown-toggle="integration-18-dropdown"
        class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path
            fill-rule="evenodd"
            d="M9.6 2.6A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2l.5.3a2 2 0 0 1 2.9 0l1.4 1.3a2 2 0 0 1 0 2.9l.1.5h.1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2l-.3.5a2 2 0 0 1 0 2.9l-1.3 1.4a2 2 0 0 1-2.9 0l-.5.1v.1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2l-.5-.3a2 2 0 0 1-2.9 0l-1.4-1.3a2 2 0 0 1 0-2.9l-.1-.5H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2l.3-.5a2 2 0 0 1 0-2.9l1.3-1.4a2 2 0 0 1 2.9 0l.5-.1V4c0-.5.2-1 .6-1.4ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
      <div id="integration-18-dropdown" class="z-10 hidden w-40 rounded-lg bg-white shadow-sm dark:bg-gray-700">
        <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="integration-18-dropdown-button">
          <li>
            <button type="button" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fill-rule="evenodd"
                  d="M9 2.2V7H4.2l.4-.5 3.9-4 .5-.3Zm2-.2v5a2 2 0 0 1-2 2H4v11c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16c0-.6.4-1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                  clip-rule="evenodd"
                />
              </svg>
              View details
            </button>
          </li>
          <li> 
          <button
            type="button"
            id="deleteToolButton"
            data-modal-target="deleteToolModal"
            data-modal-toggle="deleteToolModal"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
          >
            <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                clip-rule="evenodd"
              />
            </svg>
            Remove
          </button>
</li>
        </ul>
      </div>
    </div>
    <p class="text-gray-500 dark:text-gray-400">Online platform for sending professionals e-mails to mass recipients.</p>
    <div class="flex items-center space-x-4">
      <button
        type="button"
        id="deleteToolButton"
        data-modal-target="deleteToolModal"
        data-modal-toggle="deleteToolModal"
        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        Remove
      </button>
      <button
        type="button"
        id="successIntegrationButton"
        data-modal-target="successIntegrationModal"
        data-modal-toggle="successIntegrationModal"
        class="w-full rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Connect
      </button>
    </div>
  </div>
</div>

<!-- Remove Tool modal -->
<div id="deleteToolModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-md p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800 sm:p-5">
      <button
        type="button"
        class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        data-modal-toggle="deleteToolModal"
      >
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
        <span class="sr-only">Close modal</span>
      </button>
      <svg class="mx-auto mb-4 h-8 w-8 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm9.4-5.5a1 1 0 1 0 0 2 1 1 0 1 0 0-2ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4c0-.6-.4-1-1-1h-2Z" clip-rule="evenodd" />
      </svg>
      <p class="mb-4 text-gray-500 dark:text-gray-400">Are you sure you want to remove this integration?</p>
      <div class="flex items-center justify-center space-x-4">
        <button
          data-modal-toggle="deleteToolModal"
          type="button"
          class=" rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          No, cancel
        </button>
        <button type="submit" class="rounded-lg bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
          Yes, I'm sure
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Activate tool modal -->
<div id="successIntegrationModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-sm p-4">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-5">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">Awesome!</h3>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="successIntegrationModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>

          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <p class="text-gray-500 dark:text-gray-400">The following integration have been successfully activated:</p>
      <div class="mb-4 mt-2 flex items-center space-x-1.5 rounded-lg bg-green-50 p-4 text-green-800 dark:bg-gray-700 dark:text-green-400 sm:mb-5">
        <svg class="h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm13.7-1.3a1 1 0 0 0-1.4-1.4L11 12.6l-1.8-1.8a1 1 0 0 0-1.4 1.4l2.5 2.5c.4.4 1 .4 1.4 0l4-4Z" clip-rule="evenodd" />
        </svg>
        <p class="font-medium leading-none">Google Drive</p>
      </div>
      <button
        data-modal-toggle="successIntegrationModal"
        type="button"
        type="submit"
        class="w-full justify-center rounded-lg bg-primary-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-900"
      >
        Continue
      </button>
    </div>
  </div>
</div>

<!-- New integration modal -->
<div id="newIntegrationModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-md p-4 lg:p-0">
    <!-- Modal content -->
    <div class="relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <div class="flex items-center justify-between px-4 py-4 sm:px-5">
        <h3 class="text-lg font-semibold leading-none text-gray-900 dark:text-white">New integration</h3>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="newIntegrationModal"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <div class="px-4 pb-4 sm:px-5 sm:pb-5">
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400 sm:text-base">Connect an integration and write a short explanation for team reference.</p>
        <form action="#" class="space-y-4">
          <div>
            <label for="integration" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Select integration</label>
            <select
              id="integration"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            >
              <option selected>Amazon API Gateway</option>
              <option value="FB">Flowbite</option>
              <option value="ZP">Zeplin Integrations Directory</option>
              <option value="VS">VSCode</option>
              <option value="WC">Woocommerce</option>
            </select>
          </div>

          <div>
            <label for="client-id" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Client ID</label>
            <input
              type="text"
              id="client-id"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              required
              placeholder=""
            />
          </div>
          <div>
            <label for="client-secret" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Client secret</label>
            <input
              type="text"
              id="client-secret"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              required
              placeholder=""
            />
          </div>
          <div>
            <label for="authentication-base" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Authentication base URI</label>
            <input
              type="text"
              id="authentication-base"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              required
              placeholder=""
            />
            <p id="authentication-base-helper" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Enter the full URI, and we'll automatically extract and display just the subdomain for easier reference.</p>
          </div>
          <div class="flex items-center space-x-4">
            <button
              type="button"
              data-modal-toggle="newIntegrationModal"
              class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="w-full rounded-lg border border-primary-700 bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-600 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
              Add integration
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
