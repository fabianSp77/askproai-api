@props([
    'users' => [],
    'totalUsers' => 1356546,
    'totalProjects' => 884,
    'currentPage' => 1,
    'perPage' => 10,
    'searchPlaceholder' => 'Search users...',
    'columns' => [
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'email', 'label' => 'Email', 'sortable' => true],
        ['key' => 'role', 'label' => 'Role', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false]
    ]
])

<section class="bg-gray-50 py-3 dark:bg-gray-900 sm:py-5"
         x-data="{
             users: @js($users),
             selectedUsers: [],
             searchQuery: '',
             currentPage: {{ $currentPage }},
             perPage: {{ $perPage }},
             sortColumn: 'name',
             sortDirection: 'asc',
             showBulkActions: false,
             
             get filteredUsers() {
                 return this.users.filter(user => 
                     user.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                     user.email.toLowerCase().includes(this.searchQuery.toLowerCase())
                 );
             },
             
             get sortedUsers() {
                 return this.filteredUsers.sort((a, b) => {
                     let aVal = a[this.sortColumn];
                     let bVal = b[this.sortColumn];
                     
                     if (typeof aVal === 'string') {
                         aVal = aVal.toLowerCase();
                         bVal = bVal.toLowerCase();
                     }
                     
                     if (this.sortDirection === 'asc') {
                         return aVal > bVal ? 1 : -1;
                     }
                     return aVal < bVal ? 1 : -1;
                 });
             },
             
             get paginatedUsers() {
                 const start = (this.currentPage - 1) * this.perPage;
                 const end = start + this.perPage;
                 return this.sortedUsers.slice(start, end);
             },
             
             get totalPages() {
                 return Math.ceil(this.filteredUsers.length / this.perPage);
             },
             
             sort(column) {
                 if (this.sortColumn === column) {
                     this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                 } else {
                     this.sortColumn = column;
                     this.sortDirection = 'asc';
                 }
             },
             
             toggleSelectAll() {
                 if (this.selectedUsers.length === this.paginatedUsers.length) {
                     this.selectedUsers = [];
                 } else {
                     this.selectedUsers = this.paginatedUsers.map(user => user.id);
                 }
                 this.showBulkActions = this.selectedUsers.length > 0;
             },
             
             toggleSelectUser(userId) {
                 const index = this.selectedUsers.indexOf(userId);
                 if (index > -1) {
                     this.selectedUsers.splice(index, 1);
                 } else {
                     this.selectedUsers.push(userId);
                 }
                 this.showBulkActions = this.selectedUsers.length > 0;
             },
             
             isSelected(userId) {
                 return this.selectedUsers.includes(userId);
             },
             
             bulkAction(action) {
                 console.log(`Bulk ${action} for users:`, this.selectedUsers);
                 // Handle bulk actions
                 this.selectedUsers = [];
                 this.showBulkActions = false;
             },
             
             editUser(userId) {
                 console.log('Edit user:', userId);
                 // Handle edit action
             },
             
             deleteUser(userId) {
                 console.log('Delete user:', userId);
                 // Handle delete action
             },
             
             addNewUser() {
                 console.log('Add new user');
                 // Handle add new user
             },
             
             toggleUserStatus(userId) {
                 const user = this.users.find(u => u.id === userId);
                 if (user) {
                     user.status = user.status === 'active' ? 'inactive' : 'active';
                 }
             }
         }"
         x-init="
             // Sample data if no users provided
             if (users.length === 0) {
                 users = [
                     { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', status: 'active', created_at: '2024-01-15' },
                     { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', status: 'active', created_at: '2024-02-20' },
                     { id: 3, name: 'Mike Johnson', email: 'mike@example.com', role: 'Editor', status: 'inactive', created_at: '2024-03-10' }
                 ];
             }
         ">

    <div class="mx-auto max-w-screen-2xl px-4 lg:px-12">
        <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
            <div class="divide-y px-4 dark:divide-gray-700">
                
                <!-- Stats Header -->
                <div class="flex flex-col space-y-3 py-3 md:flex-row md:items-center md:justify-between md:space-x-4 md:space-y-0">
                    <div class="flex flex-1 items-center space-x-4">
                        <h5>
                            <span class="text-gray-500">All Users:</span>
                            <span class="dark:text-white">{{ number_format($totalUsers) }}</span>
                        </h5>
                        <h5>
                            <span class="text-gray-500">Projects:</span>
                            <span class="dark:text-white">{{ number_format($totalProjects) }}</span>
                        </h5>
                    </div>
                    <div class="flex shrink-0 flex-col items-start space-y-3 md:flex-row md:items-center md:space-x-3 md:space-y-0 lg:justify-end">
                        <button class="inline-flex items-center px-3 py-2 text-xs font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-700 dark:focus:ring-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                            </svg>
                            Table settings
                        </button>
                    </div>
                </div>
                
                <!-- Actions Bar -->
                <div class="flex flex-col items-stretch justify-between space-y-3 py-4 md:flex-row md:items-center md:space-y-0">
                    <div class="flex items-center space-x-3">
                        <button @click="addNewUser()"
                                class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                            <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                            </svg>
                            Add new user
                        </button>
                        
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" 
                                   x-model="searchQuery"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                   placeholder="{{ $searchPlaceholder }}">
                        </div>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div x-show="showBulkActions" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="flex items-center space-x-3">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400" 
                              x-text="selectedUsers.length + ' selected'"></span>
                        <div class="flex items-center divide-x divide-gray-200 dark:divide-gray-600">
                            <button @click="bulkAction('suspend')"
                                    class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Suspend all
                            </button>
                            <button @click="bulkAction('archive')"
                                    class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Archive all
                            </button>
                            <button @click="bulkAction('delete')"
                                    class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Delete all
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="p-4">
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           @change="toggleSelectAll()"
                                           :checked="selectedUsers.length === paginatedUsers.length && paginatedUsers.length > 0"
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                </div>
                            </th>
                            @foreach($columns as $column)
                                @if($column['key'] !== 'actions')
                                <th scope="col" class="px-4 py-3">
                                    @if($column['sortable'])
                                    <button @click="sort('{{ $column['key'] }}')"
                                            class="flex items-center hover:text-gray-900 dark:hover:text-white">
                                        {{ $column['label'] }}
                                        <svg class="w-4 h-4 ml-1" 
                                             :class="{ 'rotate-180': sortColumn === '{{ $column['key'] }}' && sortDirection === 'desc' }"
                                             fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                    @else
                                    {{ $column['label'] }}
                                    @endif
                                </th>
                                @else
                                <th scope="col" class="px-4 py-3">{{ $column['label'] }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="user in paginatedUsers" :key="user.id">
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="w-4 p-4">
                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               :checked="isSelected(user.id)"
                                               @change="toggleSelectUser(user.id)"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white" x-text="user.name"></td>
                                <td class="px-4 py-3" x-text="user.email"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-800': user.role === 'Admin',
                                              'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-800': user.role === 'User',
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-200 dark:text-yellow-800': user.role === 'Editor'
                                          }"
                                          x-text="user.role"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <label class="inline-flex relative items-center cursor-pointer">
                                        <input type="checkbox" 
                                               :checked="user.status === 'active'"
                                               @change="toggleUserStatus(user.id)"
                                               class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3" x-text="user.created_at"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button @click="editUser(user.id)"
                                                class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg hover:bg-gray-200 focus:ring-4 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:ring-gray-700">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                        </button>
                                        <button @click="deleteUser(user.id)"
                                                class="inline-flex items-center p-2 text-sm font-medium text-center text-red-500 hover:text-red-800 rounded-lg hover:bg-red-200 focus:ring-4 focus:outline-none dark:text-red-400 dark:hover:bg-red-700 dark:hover:text-red-300 dark:focus:ring-red-700">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    Showing
                    <span class="font-semibold text-gray-900 dark:text-white" x-text="((currentPage - 1) * perPage) + 1"></span>
                    -
                    <span class="font-semibold text-gray-900 dark:text-white" x-text="Math.min(currentPage * perPage, filteredUsers.length)"></span>
                    of
                    <span class="font-semibold text-gray-900 dark:text-white" x-text="filteredUsers.length"></span>
                </span>
                <ul class="inline-flex items-stretch -space-x-px">
                    <li>
                        <button @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                :class="{ 'opacity-50 cursor-not-allowed': currentPage === 1 }"
                                class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <span class="sr-only">Previous</span>
                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </li>
                    <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)" :key="page">
                        <li x-show="page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1">
                            <button @click="currentPage = page"
                                    :class="{
                                        'z-10 bg-blue-50 border-blue-300 text-blue-600 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white': page === currentPage,
                                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white': page !== currentPage
                                    }"
                                    class="flex items-center justify-center text-sm py-2 px-3 leading-tight border"
                                    x-text="page">
                            </button>
                        </li>
                    </template>
                    <li>
                        <button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                :class="{ 'opacity-50 cursor-not-allowed': currentPage === totalPages }"
                                class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <span class="sr-only">Next</span>
                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</section>