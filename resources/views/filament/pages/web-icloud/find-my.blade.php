<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600">
            查找和定位您的 Apple 设备。
        </div>
    </div>

    <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        @forelse($account->IcloudDevice as $device)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start space-x-4">
                    @if($device->model_large_photo_url_2x)
                        <img src="{{ $device->model_large_photo_url_2x }}"
                             alt="{{ $device->name }}"
                             class="w-24 h-24 object-contain"/>
                    @else
                        <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-device-phone-mobile class="w-12 h-12 text-gray-400"/>
                        </div>
                    @endif

                    <div class="flex-1">
                        <h3 class="font-medium">{{ $device->name }}</h3>
                        <div class="text-sm text-gray-500 space-y-1 mt-1">
                            <p>型号：{{ $device->model_display_name }}</p>
                            <p>状态：<span class="text-green-600">在线</span></p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4">
                        <x-filament::button size="sm" color="gray">
                            <x-heroicon-m-speaker-wave class="w-4 h-4 mr-1"/>
                            播放声音
                        </x-filament::button>

                        <x-filament::button size="sm" color="danger">
                            <x-heroicon-m-lock-closed class="w-4 h-4 mr-1"/>
                            锁定设备
                        </x-filament::button>

                        <x-filament::button size="sm" color="warning" class="col-span-2">
                            <x-heroicon-m-trash class="w-4 h-4 mr-1"/>
                            抹掉设备
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <h3 class="mt-2 text-sm font-medium text-gray-900">暂无可定位的设备</h3>
                    <p class="mt-1 text-sm text-gray-500">请先添加设备到您的 iCloud 账户</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
