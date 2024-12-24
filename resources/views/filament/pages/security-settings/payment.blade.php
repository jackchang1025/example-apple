<div class="space-y-6">
    <div class="text-sm text-gray-600">
        管理您的支付方式和账单信息。
    </div>

    <div class="grid gap-6">
        <!-- 主要支付方式卡片 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium">主要支付方式</h3>
            </div>

            @if($account->payment)
                <div class="space-y-4">
                    <!-- 支付方式基本信息 -->
                    <div class="flex items-start space-x-4">
                        @if($account->payment->absolute_image_path)
                            <img src="{{ $account->payment->absolute_image_path }}"
                                 alt="支付方式图标"
                                 class="w-12 h-12 object-contain"/>
                        @else
                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                <x-heroicon-o-credit-card class="w-6 h-6 text-gray-400"/>
                            </div>
                        @endif

                        <div class="flex-1">
                            <div class="font-medium">{{ $account->payment->payment_method_name }}</div>
                            <div class="text-sm text-gray-500">{{ $account->payment->payment_method_detail }}</div>
                            @if($account->payment->is_primary)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                    主要支付方式
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- 持卡人信息 -->
                    @if($account->payment->owner_name)
                        <div class="border-t pt-4">
                            <h4 class="text-sm font-medium mb-2">持卡人信息</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">姓名：</span>
                                    <span>{{ $account->payment->owner_name['fullName'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- 联系电话 -->
                    @if($account->payment->phone_number)
                        <div class="border-t pt-4">
                            <h4 class="text-sm font-medium mb-2">联系电话</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">电话号码：</span>
                                    <span>
                                    +{{ $account->payment->phone_number['countryDialCode'] ?? '' }}
                                        {{ $account->payment->phone_number['number'] ?? '' }}
                                </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">国家/地区：</span>
                                    <span>{{ $account->payment->phone_number['countryCode'] ?? '' }}</span>
                                </div>
                                @if($account->payment->phone_number['trusted'] ?? false)
                                    <div class="col-span-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        已验证的可信电话
                                    </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- 支付相关信息 -->
                    <div class="border-t pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">支付账户国家/地区：</span>
                                <span>{{ $account->payment->payment_account_country_code }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">支付状态：</span>
                                <span
                                    class="capitalize">{{ $account->payment->payment_supported ? '正常' : '不可用' }}</span>
                            </div>
                            @if($account->payment->family_card)
                                <div class="col-span-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        家庭共享支付方式
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-6">

                    <h3 class="mt-2 text-sm font-medium text-gray-900">未设置支付方式</h3>
                </div>
            @endif
        </div>

        <!-- 账单寄送地址卡片 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium">账单寄送地址</h3>
            </div>

            @if($account->payment && $account->payment->billing_address)
                <div class="text-sm text-gray-600 space-y-1">
                    <!-- 地址行信息 -->
                    @if($account->payment->billing_address['line1'])
                        <div>{{ $account->payment->billing_address['line1'] }}</div>
                    @endif

                    @if(!empty($account->payment->billing_address['line2']))
                        <div>{{ $account->payment->billing_address['line2'] }}</div>
                    @endif

                    @if(!empty($account->payment->billing_address['line3']))
                        <div>{{ $account->payment->billing_address['line3'] }}</div>
                    @endif

                    <!-- 城市和区域信息 -->
                    <div>
                        {{ $account->payment->billing_address['suburb'] ?? '' }},
                        {{ $account->payment->billing_address['city'] ?? '' }}

                        {{ $account->payment->billing_address['county'] ?? '' }}
                    </div>

                    <!-- 州/省和邮编 -->
                    <div>
                        {{ $account->payment->billing_address['stateProvinceName'] ?? '' }}
                        {{ $account->payment->billing_address['postalCode'] ?? '' }}
                    </div>

                    <!-- 国家信息 -->
                    <div class="mt-2">
                        <span class="text-gray-500">国家/地区代码：</span>
                        <span>{{ $account->payment->billing_address['countryCode'] ?? '' }}</span>
                    </div>

                    <!-- 地址ID -->
                    <div class="mt-2 text-xs text-gray-400">
                        地址ID: {{ $account->payment->billing_address['id'] ?? '' }}
                    </div>
                </div>
            @else
                <div class="text-sm text-gray-500">未设置账单寄送地址</div>
            @endif
        </div>

        <!-- 配送地址卡片 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium">配送地址</h3>
            </div>

            @if($account->payment && $account->payment->default_shipping_address)
                <div class="text-sm text-gray-600">
                    {{ $account->payment->default_shipping_address['recipientFirstName'] ?? '' }}
                    {{ $account->payment->default_shipping_address['recipientLastName'] ?? '' }}<br>
                    {{ $account->payment->default_shipping_address['line1'] ?? '' }}<br>
                    @if($account->payment->default_shipping_address['line2'])
                        {{ $account->payment->default_shipping_address['line2'] }}<br>
                    @endif
                    {{ $account->payment->default_shipping_address['city'] ?? '' }}
                    {{ $account->payment->default_shipping_address['stateProvinceName'] ?? '' }}
                    {{ $account->payment->default_shipping_address['postalCode'] ?? '' }}<br>
                    {{ $account->payment->default_shipping_address['countryName'] ?? '' }}
                </div>
            @else
                <div class="text-sm text-gray-500">未设置配送地址</div>
            @endif
        </div>
    </div>
</div>
