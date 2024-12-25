<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600">
            管理您的 iCloud 设备。
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
                            <p>系统版本：{{ $device->os_version }}</p>
                            <p>序列号：{{ $device->serial_number }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-500 space-y-1">
                        <p>IMEI：{{ $device->imei }}</p>
                        <p>UDID：{{ $device->udid }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <h3 class="mt-2 text-sm font-medium text-gray-900">暂无设备</h3>
                    <p class="mt-1 text-sm text-gray-500">点击上方的更新按钮获取设备信息</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
