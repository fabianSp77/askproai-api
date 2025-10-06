<x-filament-panels::page>
    <x-filament-widgets::widgets
        :columns="$this->getColumns() ?? 1"
        :data="$this->getWidgetData() ?? []"
        :widgets="$this->getVisibleWidgets() ?? []"
    />
</x-filament-panels::page>
