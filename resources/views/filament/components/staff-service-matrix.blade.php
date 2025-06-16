<div class="staff-service-matrix" x-data="staffServiceMatrix()">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <!-- Matrix Header -->
        <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Mitarbeiter-Service-Zuordnungen
                </h3>
                <div class="flex items-center space-x-2">
                    <button @click="toggleAllServices()" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        Alle umschalten
                    </button>
                    <button @click="saveMatrix()" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Matrix Content -->
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                                Mitarbeiter
                            </th>
                            <template x-for="service in services" :key="service.id">
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    <div class="flex flex-col items-center">
                                        <span x-text="service.name" class="font-semibold"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="service.duration_minutes"></span>min
                                        </span>
                                    </div>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="staff in staffMembers" :key="staff.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center text-white font-semibold">
                                                <span x-text="staff.name.charAt(0).toUpperCase()"></span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="staff.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="staff.title || 'Mitarbeiter'"></p>
                                        </div>
                                    </div>
                                </td>
                                <template x-for="service in services" :key="service.id">
                                    <td class="px-4 py-3 text-center">
                                        <div class="relative inline-block">
                                            <input 
                                                type="checkbox"
                                                :checked="isAssigned(staff.id, service.id)"
                                                @change="toggleAssignment(staff.id, service.id)"
                                                class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer"
                                            />
                                            <template x-if="getCustomPrice(staff.id, service.id)">
                                                <span class="absolute -top-2 -right-2 bg-yellow-100 text-yellow-800 text-xs px-1 rounded">
                                                    €<span x-text="getCustomPrice(staff.id, service.id)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <!-- Legende -->
            <div class="mt-4 flex items-center justify-end space-x-4 text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center">
                    <input type="checkbox" checked disabled class="h-4 w-4 text-indigo-600 mr-1" />
                    <span>Service zugeordnet</span>
                </div>
                <div class="flex items-center">
                    <div class="relative inline-block mr-1">
                        <input type="checkbox" checked disabled class="h-4 w-4 text-indigo-600" />
                        <span class="absolute -top-1 -right-1 bg-yellow-100 text-yellow-800 text-xs px-0.5 rounded">€</span>
                    </div>
                    <span>Mit individuellem Preis</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-700/50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span x-text="getTotalAssignments()"></span> Zuordnungen insgesamt
                </div>
                <div class="flex space-x-2">
                    <button @click="autoAssignByQualification()" class="px-3 py-1 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"></path>
                        </svg>
                        Auto-Zuordnung
                    </button>
                    <button @click="exportMatrix()" class="px-3 py-1 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function staffServiceMatrix() {
            return {
                staffMembers: @json($getRecord()?->staff ?? []),
                services: @json($getRecord()?->getEffectiveServices() ?? []),
                assignments: @json($getRecord()?->staffServiceAssignments ?? []),
                
                isAssigned(staffId, serviceId) {
                    return this.assignments.some(a => 
                        a.staff_id === staffId && a.master_service_id === serviceId
                    );
                },
                
                getCustomPrice(staffId, serviceId) {
                    const assignment = this.assignments.find(a => 
                        a.staff_id === staffId && a.master_service_id === serviceId
                    );
                    return assignment?.custom_price;
                },
                
                toggleAssignment(staffId, serviceId) {
                    const index = this.assignments.findIndex(a => 
                        a.staff_id === staffId && a.master_service_id === serviceId
                    );
                    
                    if (index > -1) {
                        this.assignments.splice(index, 1);
                    } else {
                        this.assignments.push({
                            staff_id: staffId,
                            master_service_id: serviceId,
                            branch_id: @json($getRecord()?->id)
                        });
                    }
                },
                
                toggleAllServices() {
                    const allAssigned = this.staffMembers.every(staff =>
                        this.services.every(service =>
                            this.isAssigned(staff.id, service.id)
                        )
                    );
                    
                    if (allAssigned) {
                        this.assignments = [];
                    } else {
                        this.assignments = [];
                        this.staffMembers.forEach(staff => {
                            this.services.forEach(service => {
                                this.assignments.push({
                                    staff_id: staff.id,
                                    master_service_id: service.id,
                                    branch_id: @json($getRecord()?->id)
                                });
                            });
                        });
                    }
                },
                
                getTotalAssignments() {
                    return this.assignments.length;
                },
                
                autoAssignByQualification() {
                    // Intelligente Auto-Zuordnung basierend auf Mitarbeiter-Qualifikationen
                    Livewire.emit('notify', 'Auto-Zuordnung wird durchgeführt...');
                    
                    // Hier würde die Logik für intelligente Zuordnung implementiert
                    this.$wire.autoAssignServices();
                },
                
                saveMatrix() {
                    this.$wire.saveServiceAssignments(this.assignments)
                        .then(() => {
                            Livewire.emit('notify', 'Matrix erfolgreich gespeichert!');
                        });
                },
                
                exportMatrix() {
                    // Export als CSV
                    let csv = 'Mitarbeiter,';
                    csv += this.services.map(s => s.name).join(',') + '\n';
                    
                    this.staffMembers.forEach(staff => {
                        csv += staff.name + ',';
                        csv += this.services.map(service => 
                            this.isAssigned(staff.id, service.id) ? 'X' : ''
                        ).join(',') + '\n';
                    });
                    
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'mitarbeiter-service-matrix.csv';
                    a.click();
                }
            }
        }
    </script>
</div>
