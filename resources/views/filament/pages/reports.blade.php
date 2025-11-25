<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Report Configuration
        </x-slot>

        <x-slot name="description">
            Select report type, date range, and export format to generate comprehensive analytics.
        </x-slot>

        <form wire:submit="generateReport">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" class="mt-6">
                Generate Report
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
