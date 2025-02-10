<div class="space-y-6 container">
    <!-- 头部标题和说明 -->
    <div>
        <h2 class="text-xl font-medium text-gray-900">个人信息</h2>
        <p class="mt-1 text-sm text-gray-500">
            管理您的个人信息，包括姓名、生日、联系方式等基本信息。{{now()->format('Y-m-d H:i:s') }}
        </p>
    </div>

    <div class="space-y-6">
        <!-- 基本信息卡片组 -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">基本信息</h3>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <!-- Apple ID Card -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">

                            <!-- Apple ID 详细信息 -->
                            <div class="space-y-2 border-t pt-2">
                                <!-- 账户名称 -->
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">账户名称</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->apple_id['accountName'] ?? '未设置' }}</span>
                                </div>

                                <!-- 格式化的账户名称 -->
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">显示名称</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->apple_id['formattedAccountName'] ?? '未设置' }}</span>
                                </div>

                                <!-- 域名信息 -->
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">域名</span>
                                    <span class="text-gray-900">
                                        {{ $account->accountManager?->apple_id['domain'] ?? '未设置' }}
                                        @if($account->accountManager?->apple_id['appleOwnedDomain'])
                                            <span class="ml-1 text-xs text-blue-500">(Apple 官方域名)</span>
                                        @endif
                                    </span>
                                </div>

                                <!-- 账户状态 -->
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @if($account->accountManager?->apple_id['editable'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <x-heroicon-s-pencil class="w-3 h-3 mr-1"/> 可编辑
                                        </span>
                                    @endif
                                    @if($account->accountManager?->apple_id['nonFTEUEnabled'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                            <x-heroicon-s-check-circle class="w-3 h-3 mr-1"/> 非FTEU已启用
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Name Card -->
                <div
                    x-data
                    x-on:click="$dispatch('open-modal', { id: 'edit-name-modal' })"
                    class="rounded-lg border border-gray-100 shadow-sm hover:border-gray-200 cursor-pointer transition-colors duration-200"
                >

                    <div class="p-4">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">姓名</h3>
                                <x-heroicon-s-user class="w-4 h-4 text-blue-500"/>
                            </div>
                            <p class="text-sm text-gray-500">
                                {{ $account->accountManager?->name['fullName'] ?? '未设置' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Birthday Card -->
                <div
                    x-data
                    x-on:click="$dispatch('open-modal', { id: 'edit-birthday-modal' })"
                    class="rounded-lg border border-gray-100 shadow-sm hover:border-gray-200 cursor-pointer transition-colors duration-200"
                >
                    <div class="p-4">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">生日</h3>
                                <x-heroicon-s-calendar class="w-4 h-4 text-blue-500"/>
                            </div>

                            <p class="text-sm text-gray-500">
                                {{ $account->accountManager?->localized_birthday ?? '未设置' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 个人详细信息卡片组 -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">个人详细信息</h3>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <!-- 姓名详情卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">姓名详情</h3>
                                <x-heroicon-s-user-circle class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">名字</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->name['firstName'] ?? '未设置' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">姓氏</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->name['lastName'] ?? '未设置' }}</span>
                                </div>
                                @if($account->accountManager?->name['suffix'] ?? null)
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-600">后缀</span>
                                        <span
                                            class="text-gray-900">{{ $account->accountManager?->name['suffix'] }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($account->accountManager?->pronounce_names_required)
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                        <x-heroicon-s-speaker-wave class="w-3 h-3 mr-1"/> 需要名字发音
                                    </span>
                                @endif
                                @if($account->accountManager?->middle_name_required)
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                        <x-heroicon-s-information-circle class="w-3 h-3 mr-1"/> 需要中间名
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 偏好设置卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">偏好设置</h3>
                                <x-heroicon-s-cog class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">首选语言</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->account['preferences']['preferredLanguage'] ?? '未设置' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">时区</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->account['preferences']['timeZone'] ?? '未设置' }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($account->accountManager?->enable_right_to_left_display)
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">
                                        <x-heroicon-s-arrow-left-circle class="w-3 h-3 mr-1"/> 从右到左显示
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 安全状态卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">安全状态</h3>
                                <x-heroicon-s-shield-exclamation class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    @if($account->accountManager?->account['security']['twoFactorAuthEnabled'] ?? null)
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <x-heroicon-s-key class="w-3 h-3 mr-1"/> 双重认证已启用
                                        </span>
                                    @endif
                                    @if($account->accountManager?->account['security']['securityQuestionsExist'] ?? null)
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <x-heroicon-s-question-mark-circle class="w-3 h-3 mr-1"/> 已设置安全问题
                                        </span>
                                    @endif
                                    @if($account->accountManager?->account['security']['passwordResetEligible'] ?? null)
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                            <x-heroicon-s-arrow-path class="w-3 h-3 mr-1"/> 可重置密码
                                        </span>
                                    @endif
                                </div>
                                @if($account->accountManager?->account['security']['trustedPhoneNumbers'] ?? null)
                                    <div class="mt-2">
                                        <span
                                            class="text-xs text-gray-500">已验证的手机号码：{{ count($account->accountManager?->account['security']['trustedPhoneNumbers']) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 联系方式卡片组 -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">联系方式</h3>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <!-- Primary Email Card -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">主要邮箱</h3>
                                <x-heroicon-s-envelope class="w-4 h-4 text-blue-500"/>
                            </div>
                            <p class="text-sm text-gray-500">
                                {{ $account->accountManager?->primary_email_address['emailAddress'] ?? '未设置' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Alternate Emails Card -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">备用邮箱</h3>
                                <x-heroicon-s-envelope-open class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="text-sm text-gray-500">
                                @if(!empty($account->accountManager?->alternate_email_addresses ?? null))
                                    @foreach($account->accountManager->alternate_email_addresses as $email)
                                        <div>{{ $email }}</div>
                                    @endforeach
                                @else
                                    <p>未设置备用邮箱</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 账号信息卡片组 -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">账号信息</h3>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <!-- 账号状态卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">账号状态</h3>
                                <x-heroicon-s-shield-check class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($account->accountManager?->account['paidaccount'])
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                        <x-heroicon-s-credit-card class="w-3 h-3 mr-1"/> 付费账户
                                    </span>
                                @endif
                                @if($account->accountManager?->account['federated'])
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                        <x-heroicon-s-user-group class="w-3 h-3 mr-1"/> 联合账户
                                    </span>
                                @endif
                                @if($account->accountManager?->account['internalAccount'])
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">
                                        <x-heroicon-s-identification class="w-3 h-3 mr-1"/> 内部账户
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 安全信息卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">安全信息</h3>
                                <x-heroicon-s-lock-closed class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">最后更改密码</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->account['localizedLastPasswordChangedDate'] }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if($account->accountManager?->account['recoveryKeyEnabled'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <x-heroicon-s-key class="w-3 h-3 mr-1"/> 已启用恢复密钥
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 家庭共享卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">家庭共享</h3>
                                <x-heroicon-s-users class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">受益人</span>
                                        <div
                                            class="mt-1 text-gray-900">{{ $account->accountManager?->account['beneficiaryCount'] }}
                                            人
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">监护人</span>
                                        <div
                                            class="mt-1 text-gray-900">{{ $account->accountManager?->account['custodianCount'] }}
                                            人
                                        </div>
                                    </div>
                                </div>
                                @if($account->accountManager?->account['hasFamilyPaymentMethod'])
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                        <x-heroicon-s-credit-card class="w-3 h-3 mr-1"/> 已设置家庭支付方式
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 区域设置卡片组 -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">区域设置</h3>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <!-- 地址信息卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">地址信息</h3>
                                <x-heroicon-s-map-pin class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">
                                    {{ $account->accountManager?->account['person']['primaryAddress']['fullAddress'] ?? '未设置' }}
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if($account->accountManager?->account['person']['primaryAddress']['defaultAddress'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                            <x-heroicon-s-star class="w-3 h-3 mr-1"/> 默认地址
                                        </span>
                                    @endif
                                    @if($account->accountManager?->account['person']['primaryAddress']['shipping'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <x-heroicon-s-truck class="w-3 h-3 mr-1"/> 配送地址
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 国家/地区卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">国家/地区</h3>
                                <x-heroicon-s-globe-alt class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">当前国家</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->account['person']['primaryAddress']['countryName'] ?? '未设置' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">国家代码</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->account['person']['primaryAddress']['countryCode'] ?? '未设置' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 语言设置卡片 -->
                <div class="rounded-lg border border-gray-100 shadow-sm">
                    <div class="p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">语言设置</h3>
                                <x-heroicon-s-language class="w-4 h-4 text-blue-500"/>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">首选语言</span>
                                    <span
                                        class="text-gray-900">{{ $account->accountManager?->account['preferences']['preferredLanguage'] ?? '未设置' }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if($account->accountManager?->account['person']['primaryAddress']['japanese'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                            日语支持
                                        </span>
                                    @endif
                                    @if($account->accountManager?->account['person']['primaryAddress']['korean'])
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                            韩语支持
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($account->accountManager?->account['person']['primaryAddress']['stateProvinces'])
                    <div class="mt-2">
                        <span class="text-xs text-gray-500">可选州/省：</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($account->accountManager?->account['person']['primaryAddress']['stateProvinces'] as $state)
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('filament.pages.security-settings.partials.name-modal')
    @include('filament.pages.security-settings.partials.birthday-modal')
</div>
