<div class="space-y-6">
    <!-- 头部标题和说明 -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-medium text-gray-900">购买历史</h2>
            <p class="mt-1 text-sm text-gray-500">
                查看您的 Apple ID 购买记录。
            </p>
        </div>

    </div>

    <!-- 购买历史列表 -->
    <div class="space-y-4">
        @forelse ($account->purchaseHistory as $purchase)
            <div
                x-data
                x-on:click="$dispatch('open-modal', { id: 'purchase-modal-{{ $purchase->id }}' })"
                class="bg-white rounded-lg border border-gray-100 shadow-sm hover:border-gray-200 transition-colors duration-200 cursor-pointer"
            >
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-medium text-gray-900">
                                    订单 #{{ $purchase->web_order_id }}
                                </h3>
                                @if($purchase->is_pending_purchase)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        待处理
                                    </span>
                                @endif
                            </div>
                            <div class="space-y-1 text-sm text-gray-500">
                                <p>购买ID：{{ $purchase->purchase_id }}</p>
                                <p>订单ID：{{ $purchase->web_order_id }}</p>
                                <p>DSID：{{ $purchase->dsid }}</p>
                                <p>
                                    购买日期：{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y年m月d日 H:i') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">
                                金额：{{ $purchase->invoice_amount ?? $purchase->estimated_total_amount ?? '未知' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 购买详情模态框 -->
            <x-filament::modal id="purchase-modal-{{ $purchase->id }}" width="4xl">
                <x-slot name="header">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium">订单详情 #{{ $purchase->web_order_id }}</h2>
                        <button
                            x-on:click="$dispatch('close-modal', { id: 'purchase-modal-{{ $purchase->id }}' })"
                            class="text-gray-400 hover:text-gray-500"
                        >
                            <x-heroicon-s-x-mark class="w-5 h-5"/>
                        </button>
                    </div>
                </x-slot>

                <!-- 购买基本信息 -->
                <div class="space-y-6 py-4">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">购买信息</h3>
                            <dl class="mt-2 space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">购买ID</dt>
                                    <dd class="text-sm text-gray-900">{{ $purchase->purchase_id }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">订单ID</dt>
                                    <dd class="text-sm text-gray-900">{{ $purchase->web_order_id }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">DSID</dt>
                                    <dd class="text-sm text-gray-900">{{ $purchase->dsid }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">发票金额</dt>
                                    <dd class="text-sm text-gray-900">{{ $purchase->invoice_amount }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">日期信息</h3>
                            <dl class="mt-2 space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">购买日期</dt>
                                    <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d H:i:s') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">发票日期</dt>
                                    <dd class="text-sm text-gray-900">{{ $purchase->invoice_date ? \Carbon\Carbon::parse($purchase->invoice_date)->format('Y-m-d H:i:s') : '未知' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">预计总金额</dt>
                                    <dd class="text-sm text-gray-900">{{ $purchase->estimated_total_amount }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">状态</dt>
                                    <dd class="text-sm">
                                        @if($purchase->is_pending_purchase)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">待处理</span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">已完成</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- 购买项目列表 -->
                    @if($purchase->plis->isNotEmpty())
                        <div class="border-t pt-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">购买项目明细</h3>
                            <div class="space-y-4">
                                @foreach($purchase->plis as $pli)
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <dl class="space-y-2">
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">项目名称</dt>
                                                        <dd class="text-sm text-gray-900">{{ $pli->title }}</dd>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">项目ID</dt>
                                                        <dd class="text-sm text-gray-900">{{ $pli->item_id }}</dd>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">支付金额</dt>
                                                        <dd class="text-sm text-gray-900">{{ $pli->amount_paid }}</dd>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">购买日期</dt>
                                                        <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($pli->pli_date)->format('Y-m-d H:i:s') }}</dd>
                                                    </div>
                                                </dl>
                                            </div>
                                            <div>
                                                <dl class="space-y-2">
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">类型</dt>
                                                        <dd class="text-sm text-gray-900">{{ $pli->line_item_type }}</dd>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">免费购买</dt>
                                                        <dd class="text-sm">
                                                            @if($pli->is_free_purchase)
                                                                <x-heroicon-s-check-circle
                                                                    class="w-5 h-5 text-green-500"/>
                                                            @else
                                                                <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <dt class="text-sm text-gray-500">信用</dt>
                                                        <dd class="text-sm">
                                                            @if($pli->is_credit)
                                                                <x-heroicon-s-check-circle
                                                                    class="w-5 h-5 text-green-500"/>
                                                            @else
                                                                <x-heroicon-s-x-circle class="w-5 h-5 text-red-500"/>
                                                            @endif
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>

                                        <!-- 本地化内容 -->
                                        @if($pli->localized_content)
                                            <div class="mt-4 border-t pt-4">
                                                <h4 class="text-sm font-medium text-gray-900 mb-3">本地化内容</h4>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="space-y-4">
                                                        @if(isset($pli->localized_content['artworkURL']))
                                                            <div>
                                                                <img
                                                                    src="{{ $pli->localized_content['artworkURL'] }}"
                                                                    alt="{{ $pli->localized_content['nameForDisplay'] ?? '艺术作品' }}"
                                                                    class="w-32 h-32 object-cover rounded-lg"
                                                                    onerror="this.src=`{{ asset('images/default-artwork.png') }}`"
                                                                    loading="lazy"
                                                                />
                                                            </div>
                                                        @endif
                                                        <dl class="space-y-2">
                                                            <div class="flex justify-between">
                                                                <dt class="text-sm text-gray-500">显示名称</dt>
                                                                <dd class="text-sm text-gray-900">{{ $pli->localized_content['nameForDisplay'] ?? '-' }}</dd>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <dt class="text-sm text-gray-500">显示详情</dt>
                                                                <dd class="text-sm text-gray-900">{{ $pli->localized_content['detailForDisplay'] ?? '-' }}</dd>
                                                            </div>
                                                        </dl>
                                                    </div>
                                                    <div>
                                                        <dl class="space-y-2">
                                                            <div class="flex justify-between">
                                                                <dt class="text-sm text-gray-500">媒体类型</dt>
                                                                <dd class="text-sm text-gray-900">{{ $pli->localized_content['mediaType'] ?? '-' }}</dd>
                                                            </div>
                                                            @if(isset($pli->localized_content['supportURL']))
                                                                <div class="flex justify-between">
                                                                    <dt class="text-sm text-gray-500">支持链接</dt>
                                                                    <dd class="text-sm text-blue-600 hover:text-blue-800">
                                                                        <a href="{{ $pli->localized_content['supportURL'] }}"
                                                                           target="_blank" rel="noopener">
                                                                            访问
                                                                            <x-heroicon-s-arrow-top-right-on-square
                                                                                class="inline-block w-4 h-4 ml-1"/>
                                                                        </a>
                                                                    </dd>
                                                                </div>
                                                            @endif
                                                            @if(isset($pli->localized_content['subscriptionCoverageDescription']))
                                                                <div class="flex justify-between">
                                                                    <dt class="text-sm text-gray-500">订阅覆盖说明</dt>
                                                                    <dd class="text-sm text-gray-900">{{ $pli->localized_content['subscriptionCoverageDescription'] }}</dd>
                                                                </div>
                                                            @endif
                                                            <div class="flex justify-between">
                                                                <dt class="text-sm text-gray-500">完成性</dt>
                                                                <dd class="text-sm">
                                                                    @if($pli->localized_content['complete'] ?? false)
                                                                        <x-heroicon-s-check-circle
                                                                            class="w-5 h-5 text-green-500"/>
                                                                    @else
                                                                        <x-heroicon-s-x-circle
                                                                            class="w-5 h-5 text-red-500"/>
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        </dl>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- 订阅信息 -->
                                        @if($pli->subscription_info)
                                            <div class="mt-4 border-t pt-4">
                                                <h4 class="text-sm font-medium text-gray-900 mb-3">订阅信息</h4>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        @if(!empty($pli->subscription_info['trunkPricings']))
                                                            <div class="space-y-2">
                                                                <h5 class="text-sm text-gray-700">主要定价</h5>
                                                                @foreach($pli->subscription_info['trunkPricings'] as $pricing)
                                                                    <p class="text-sm text-gray-600">
                                                                        {{ $pricing['price'] }} {{ $pricing['currency'] }}
                                                                        / {{ $pricing['period'] }}
                                                                    </p>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <dl class="space-y-2">
                                                            <div class="flex justify-between">
                                                                <dt class="text-sm text-gray-500">条件主要定价</dt>
                                                                <dd class="text-sm">
                                                                    @if($pli->subscription_info['isContingentPricingTrunk'] ?? false)
                                                                        <x-heroicon-s-check-circle
                                                                            class="w-5 h-5 text-green-500"/>
                                                                    @else
                                                                        <x-heroicon-s-x-circle
                                                                            class="w-5 h-5 text-red-500"/>
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <dt class="text-sm text-gray-500">显示影响报告</dt>
                                                                <dd class="text-sm">
                                                                    @if($pli->subscription_info['shouldDisplayImpactReport'] ?? false)
                                                                        <x-heroicon-s-check-circle
                                                                            class="w-5 h-5 text-green-500"/>
                                                                    @else
                                                                        <x-heroicon-s-x-circle
                                                                            class="w-5 h-5 text-red-500"/>
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        </dl>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::modal>
        @empty
            <div class="text-center py-12">
                <p class="mt-1 text-sm text-gray-500">当前没有任何购买历史记录。</p>
            </div>
        @endforelse
    </div>
</div>
