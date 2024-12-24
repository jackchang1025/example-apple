<div class="space-y-6">
    <!-- 头部标题和说明 -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-medium text-gray-900">设备管理</h2>
            <p class="mt-1 text-sm text-gray-500">
                查看和管理与您的 Apple ID 关联的所有设备。
            </p>
        </div>

        <!-- 更新设备按钮 -->
        <div>
            <x-filament::button
                wire:click="handleUpdateDevices"
                class="flex items-center gap-2"
            >
                <span wire:loading.remove>更新设备</span>
                <span wire:loading>更新中...</span>
            </x-filament::button>
        </div>
    </div>

    <!-- 设备列表 -->
    <div class="space-y-4">
        @forelse ($account->devices as $device)
            <div
                x-data
                x-on:click="$dispatch('open-modal', { id: 'device-modal-{{ $device->id }}' })"
                class="rounded-lg border border-gray-100 shadow-sm hover:border-gray-200 cursor-pointer transition-colors duration-200"
            >
                <div class="p-4">
                    <div class="flex items-start space-x-4">
                        <!-- 设备图片 -->
                        <div class="flex-shrink-0">
                            @if ($device->list_image_location)
                                <img
                                    src="{{ $device->list_image_location }}"
                                    alt="{{ $device->name }}"
                                    class="w-12 h-12 object-contain"
                                />
                            @else
                                @if ($device->device_class === 'iPhone')
                                    <x-heroicon-s-device-phone-mobile class="w-12 h-12 text-gray-400"/>
                                @elseif ($device->device_class === 'iPad')
                                    <x-heroicon-s-device-tablet class="w-12 h-12 text-gray-400"/>
                                @elseif ($device->device_class === 'Mac')
                                    <x-heroicon-s-computer-desktop class="w-12 h-12 text-gray-400"/>
                                @else
                                    <x-heroicon-s-device-phone-mobile class="w-12 h-12 text-gray-400"/>
                                @endif
                            @endif
                        </div>

                        <!-- 设备信息 -->
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $device->name ?? $device->model_name }}
                                    @if($device->current_device)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            当前设备
                                        </span>
                                    @endif
                                </h3>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-500">型号</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $device->model_name ?? 'Unknown' }}</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-sm text-gray-500">操作系统</p>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $device->os }} {{ $device->os_version }}
                                    </p>
                                </div>

                                @if($device->serial_number)
                                    <div class="space-y-1">
                                        <p class="text-sm text-gray-500">序列号</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $device->serial_number }}</p>
                                    </div>
                                @endif

                                @if($device->imei)
                                    <div class="space-y-1">
                                        <p class="text-sm text-gray-500">IMEI</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $device->imei }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- 设备状态标签 -->
                            <div class="flex flex-wrap gap-2 mt-2">
                                @if($device->supports_verification_codes)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        支持验证码
                                    </span>
                                @endif

                                @if($device->has_apple_pay_cards)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        Apple Pay
                                    </span>
                                @endif

                                @if($device->removal_pending)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        待移除
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 设备详情模态框 -->
            <x-filament::modal id="device-modal-{{ $device->id }}" width="md">
                <x-slot name="header">
                    <div class="flex justify-end">
                        <button
                            x-on:click="$dispatch('close-modal', { id: 'device-modal-{{ $device->id }}' })"
                            class="text-gray-400 hover:text-gray-500"
                        >
                            <x-heroicon-s-x-mark class="w-5 h-5"/>
                        </button>
                    </div>
                </x-slot>

                <!-- 设备详细信息 -->
                <div class="space-y-8">
                    <!-- 设备标识信息 -->
                    <div class="text-center space-y-2">
                        <!-- 设备图片 -->
                        <div class="flex justify-center">
                            @if ($device->list_image_location)
                                <img
                                    src="{{ $device->list_image_location }}"
                                    alt="{{ $device->name }}"
                                    class="w-20 h-20 object-contain"
                                />
                            @else
                                @if ($device->device_class === 'iPhone')
                                    <x-heroicon-s-device-phone-mobile class="w-20 h-20 text-gray-400"/>
                                @elseif ($device->device_class === 'iPad')
                                    <x-heroicon-s-device-tablet class="w-20 h-20 text-gray-400"/>
                                @elseif ($device->device_class === 'Mac')
                                    <x-heroicon-s-computer-desktop class="w-20 h-20 text-gray-400"/>
                                @endif
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-medium">{{ $device->name ?? $device->model_name }}</h3>
                            <p class="text-sm text-gray-500">序列号：{{ $device->serial_number }}</p>
                        </div>
                    </div>

                    <!-- 备份和安全 -->
                    <div class="space-y-4">
                        <h4 class="text-base font-medium">备份和安全</h4>

                        <!-- iCloud 备份 -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-x-3">
                                <x-heroicon-o-cloud class="w-5 h-5 text-gray-400"/>
                                <span>iCloud 备份</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                上次备份：暂无信息
                                <button type="button" class="ml-1 text-gray-400 hover:text-gray-500">
                                    <x-heroicon-m-question-mark-circle class="w-4 h-4"/>
                                </button>
                            </div>
                        </div>

                        <!-- 查找我的 iPhone -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-x-3">
                                <x-heroicon-o-map-pin class="w-5 h-5 text-green-500"/>
                                <span>查找我的 {{ $device->device_class }}</span>
                            </div>
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-700">
                                在 iCloud.com 上打开
                                <x-heroicon-s-arrow-top-right-on-square class="inline-block w-4 h-4 ml-1"/>
                            </a>
                        </div>

                        <!-- 可信设备 -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-x-3">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-green-500"/>
                                <span>可信设备</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                可接收 Apple ID 验证码
                            </div>
                        </div>
                    </div>

                    <!-- 关于 -->
                    <div class="space-y-4">
                        <h4 class="text-base font-medium">关于</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500">型号</dt>
                                <dd class="mt-1">{{ $device->model_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">系统版本</dt>
                                <dd class="mt-1">{{ $device->os }} {{ $device->os_version }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">序列号</dt>
                                <dd class="mt-1">{{ $device->serial_number }}</dd>
                            </div>
                            @if($device->imei)
                                <div>
                                    <dt class="text-sm text-gray-500">IMEI</dt>
                                    <dd class="mt-1">{{ $device->imei }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- 备份和安全设置说明 -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h5 class="text-sm font-medium">如何更改备份和安全设置</h5>
                        <p class="mt-1 text-sm text-gray-500">
                            您可以在设备的"设置"中更改备份和安全偏好设置。
                        </p>
                    </div>

                    <!-- 移除设备按钮 -->
                    @unless($device->current_device)
                        <div class="pt-4">
                            <x-filament::button
                                color="danger"
                                wire:click="removeDevice({{ $device->id }})"
                                wire:confirm="确定要从此 Apple ID 移除这台设备吗？"
                                wire:loading.attr="disabled"
                                class="w-full justify-center"
                            >
                                从帐户中移除
                            </x-filament::button>
                        </div>
                    @endunless
                </div>
            </x-filament::modal>
        @empty
            <div class="text-center py-12">
                <p class="mt-1 text-sm text-gray-500">当前没有与此 Apple ID 关联的设备。</p>
            </div>
        @endforelse
    </div>

</div>
