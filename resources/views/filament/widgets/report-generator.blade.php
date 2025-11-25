<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Generate Reports</x-slot>
        <x-slot name="description">
            Create comprehensive analytics and compliance reports.
        </x-slot>

        <form wire:submit="generateReport">
            {{-- All fields in a single 2-column grid --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                
                {{-- Report Type --}}
                <div class="space-y-1.5">
                    <label for="reportType" class="block text-sm font-medium text-gray-950 dark:text-white">
                        Report Type
                    </label>
                    <select 
                        id="reportType"
                        wire:model="reportType"
                        class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                    >
                        <option value="visitor_activity">Visitor Activity Report</option>
                        <option value="staff_visits">Staff Visit Report</option>
                        <option value="location_analytics">Location Analytics Report</option>
                        <option value="time_based">Time-Based Report</option>
                        <option value="compliance">Compliance Report</option>
                    </select>
                </div>

                {{-- Export Format --}}
                <div class="space-y-1.5">
                    <label for="exportFormat" class="block text-sm font-medium text-gray-950 dark:text-white">
                        Export Format
                    </label>
                    <select 
                        id="exportFormat"
                        wire:model="exportFormat"
                        class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                    >
                        <option value="pdf">PDF Document</option>
                        <option value="csv">CSV / Excel Spreadsheet</option>
                        <option value="html">HTML (Print-Friendly)</option>
                    </select>
                </div>

                {{-- From Date --}}
                <div class="space-y-1.5">
                    <label for="dateFrom" class="block text-sm font-medium text-gray-950 dark:text-white">
                        From Date
                    </label>
                    <input 
                        id="dateFrom"
                        type="date"
                        wire:model="dateFrom"
                        class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                    />
                </div>

                {{-- To Date --}}
                <div class="space-y-1.5">
                    <label for="dateTo" class="block text-sm font-medium text-gray-950 dark:text-white">
                        To Date
                    </label>
                    <input 
                        id="dateTo"
                        type="date"
                        wire:model="dateTo"
                        class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                    />
                </div>

                {{-- Submit Button - spans both columns --}}
                <div class="sm:col-span-2 flex justify-end pt-2">
                    <button 
                        type="submit"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action"
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    >
                        <span class="fi-btn-label">Generate Report</span>
                    </button>
                </div>

            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
