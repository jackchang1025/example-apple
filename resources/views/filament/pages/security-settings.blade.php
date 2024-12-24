<x-filament::page>
    <div>
        @switch($activeTab)
            @case('personal')
                @include('filament.pages.security-settings.personal')
                @break

            @case('security')
                @include('filament.pages.security-settings.security')
                @break

            @case('payment')
                @include('filament.pages.security-settings.payment')
                @break

            @case('purchase-history')
                @include('filament.pages.security-settings.purchase-history')
                @break

            @case('subscriptions')
                @include('filament.pages.security-settings.subscriptions')
                @break

            @case('family')
                @include('filament.pages.security-settings.family')
                @break

            @case('devices')
                @include('filament.pages.security-settings.devices')
                @break

            @case('privacy')
                @include('filament.pages.security-settings.privacy')
                @break

            @default
                @include('filament.pages.security-settings.personal')
        @endswitch
    </div>
</x-filament::page> 