<x-filament::page>
    <div>
        @switch($activeTab)
            @case('devices')
                @include('filament.pages.web-icloud.devices')
                @break

            @case('find-my')
                @include('filament.pages.web-icloud.find-my')
                @break

            @case('storage')
                @include('filament.pages.web-icloud.storage')
                @break

            @default
                @include('filament.pages.web-icloud.devices')
        @endswitch
    </div>
</x-filament::page> 