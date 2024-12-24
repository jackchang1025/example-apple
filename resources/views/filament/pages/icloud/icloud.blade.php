<x-filament::page>
    <div>
        @switch($activeTab)
            @case('home')
                @include('filament.pages.icloud.home')
                @break

            @case('family')
                @include('filament.pages.icloud.family')
                @break

            @default
                @include('filament.pages.icloud.home')
        @endswitch
    </div>
</x-filament::page> 