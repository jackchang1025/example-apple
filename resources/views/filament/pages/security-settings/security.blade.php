<div class="space-y-6">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        管理与登录到您的账户、账户安全相关的设置，以及在登录遇到问题时如何恢复您的数据。
    </div>

    <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <!-- Email & Phone Numbers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium dark:text-white">电子邮件和电话号码</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->account }}</p>
                    @if($account->bind_phone)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $account->bind_phone }}</p>
                    @endif
                </div>
                <div class="text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium dark:text-white">密码</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">上次更新：{{ $lastPasswordUpdate }}</p>
                </div>
                <div class="text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Account Security -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium dark:text-white">账户安全</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        双重认证
                        <span class="ml-2 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-xs dark:text-gray-300">
                            {{ empty($account->accountManager?->config['twoFactorEnabled']) ? '未开启' : '已开启' }}
                        </span>
                    </p>
                </div>
                <div class="text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Account Recovery -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium dark:text-white">账户恢复</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">未设置</p>
                </div>
                <div class="text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </div>
            </div>
        </div>
    </div>
</div>