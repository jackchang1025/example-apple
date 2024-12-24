<div class="space-y-6">
    <!-- 头部标题和操作按钮 -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-medium text-gray-900">家庭共享</h2>
            <p class="mt-1 text-sm text-gray-500">
                管理您的家庭共享设置和成员。
            </p>
        </div>

    </div>

    <!-- 家庭信息卡片 -->
    @if($account->belongToFamily)
        <div class="bg-white rounded-lg shadow">
            <!-- 家庭基本信息 -->
            <div class="p-6 border-b">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            家庭信息
                        </h3>
                    </div>
                    @if($account->belongToFamily->organizer === $account->dsid)
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            组织者
                        </span>
                    @endif
                </div>
                <!-- 详细家庭信息 -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">家庭组 ID</dt>
                                <dd class="text-sm text-gray-900">{{ $account->belongToFamily->family_id }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">组织者的 Apple ID</dt>
                                <dd class="text-sm text-gray-900">{{ $account->belongToFamily->organizer }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">家庭组 etag 标识</dt>
                                <dd class="text-sm text-gray-900">{{ $account->belongToFamily->etag }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">创建时间</dt>
                                <dd class="text-sm text-gray-900">{{ $account->belongToFamily->created_at->format('Y-m-d H:i:s') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- 家庭成员列表 -->
            <div class="p-6">
                <h4 class="text-base font-medium text-gray-900 mb-4">家庭成员</h4>
                <div class="space-y-3">
                    @forelse($account->familyMembers as $member)
                        <div
                            x-data
                            x-on:click="$dispatch('open-modal', { id: 'member-modal-{{ $member->id }}' })"
                            class="bg-white rounded-lg border border-gray-100 shadow-sm hover:border-gray-200 transition-colors duration-200 cursor-pointer p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <x-heroicon-o-user class="w-6 h-6 text-gray-500"/>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $member->full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ $member->apple_id }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($member->has_parental_privileges)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            家长权限
                                        </span>
                                    @endif
                                    <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400"/>
                                </div>
                            </div>
                        </div>

                        <!-- 成员详情模态框 -->
                        <x-filament::modal id="member-modal-{{ $member->id }}" width="2xl">
                            <x-slot name="header">
                                <div class="flex justify-between items-center">
                                    <h2 class="text-lg font-medium">成员详情</h2>
                                    <button
                                        x-on:click="$dispatch('close-modal', { id: 'member-modal-{{ $member->id }}' })"
                                        class="text-gray-400 hover:text-gray-500"
                                    >
                                        <x-heroicon-s-x-mark class="w-5 h-5"/>
                                    </button>
                                </div>
                            </x-slot>

                            <div class="space-y-6 py-4">
                                <!-- 基本信息 -->
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 mb-3">基本信息</h3>
                                    <dl class="grid grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">全名</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $member->full_name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Apple ID</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $member->apple_id }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">DSID</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $member->dsid }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">年龄分类</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $member->age_classification }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">加入时间</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $member->created_at->format('Y-m-d H:i:s') }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- 权限设置 -->
                                <div class="border-t pt-4">
                                    <h3 class="text-sm font-medium text-gray-900 mb-3">权限设置</h3>
                                    <dl class="grid grid-cols-2 gap-4">
                                        <div class="flex items-center justify-between">
                                            <dt class="text-sm text-gray-500">家长权限</dt>
                                            <dd>
                                                @if($member->has_parental_privileges)
                                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500"/>
                                                @else
                                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="text-sm text-gray-500">屏幕使用时间</dt>
                                            <dd>
                                                @if($member->has_screen_time_enabled)
                                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500"/>
                                                @else
                                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="text-sm text-gray-500">购买请求</dt>
                                            <dd>
                                                @if($member->has_ask_to_buy_enabled)
                                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500"/>
                                                @else
                                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="text-sm text-gray-500">购买项目共享</dt>
                                            <dd>
                                                @if($member->has_share_purchases_enabled)
                                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500"/>
                                                @else
                                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="text-sm text-gray-500">位置共享</dt>
                                            <dd>
                                                @if($member->has_share_my_location_enabled)
                                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500"/>
                                                @else
                                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- 组织者操作按钮 -->
                                @if($account->belongToFamily->organizer === $account->dsid && $member->dsid !== $account->dsid)
                                    <div class="border-t pt-4">
                                        <x-filament::button
                                            color="danger"
                                            wire:click="removeFromFamily({{ $member->id }})"
                                            wire:confirm="确定要将此成员从家庭中移除吗？"
                                            class="w-full justify-center"
                                        >
                                            从家庭中移除
                                        </x-filament::button>
                                    </div>
                                @endif
                            </div>
                        </x-filament::modal>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500">暂无家庭成员</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 家庭设置 -->
            @if($account->belongToFamily->organizer === $account->dsid)
                <div class="p-6 bg-gray-50 rounded-b-lg">
                    <h4 class="text-base font-medium text-gray-900 mb-4">家庭设置</h4>
                    <div class="space-y-4">
                        <!-- 邀请状态 -->
                        @if(!empty($account->belongToFamily->invitations))
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">待处理邀请</h5>
                                <div class="space-y-2">
                                    @foreach($account->belongToFamily->invitations as $invitation)
                                        <div class="flex items-center justify-between py-2 px-3 bg-white rounded-lg">
                                            <span
                                                class="text-sm text-gray-600">{{ $invitation['email'] ?? $invitation['phone'] }}</span>
                                            <span class="text-xs text-gray-500">等待接受</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- 空状态 -->
        <div class="text-center py-12">
            <p class="mt-1 text-sm text-gray-500">您当前未加入任何家庭组</p>
        </div>
    @endif
</div>
