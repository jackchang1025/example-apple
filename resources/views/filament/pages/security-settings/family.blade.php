<div class="space-y-6">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        管理您的家庭共享设置和成员。
    </div>

    <div class="grid gap-6">
        <!-- Family Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium dark:text-white">家庭状态</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $account->belongToFamily ? '已加入家庭组' : '未加入家庭组' }}
                    </p>
                </div>
                <div class="text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </div>
            </div>
        </div>

        @if($account->belongToFamily)
        <!-- Family Members -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium dark:text-white">家庭成员</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">管理庭成员</p>
                </div>
                <div class="text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </div>
            </div>
        </div>
        @endif
    </div>
</div>