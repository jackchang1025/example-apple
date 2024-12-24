<div class="space-y-6 container">
    <!-- 头部标题和说明 -->
    <div>
        <h2 class="text-xl font-medium text-gray-900">Personal Information</h2>
        <p class="mt-1 text-sm text-gray-500">
            Manage your personal information, including phone numbers and email addresses where you can be reached.
        </p>
    </div>

    <div class="space-y-4">
        <!-- 第一行：Name 和 Birthday -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Name Card -->
            <div
                x-data
                x-on:click="$dispatch('open-modal', { id: 'edit-name-modal' })"
                class="rounded-lg border border-gray-100 shadow-sm hover:border-gray-200 cursor-pointer transition-colors duration-200"
            >
                <div class="p-4">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900">Name</h3>
                            <x-heroicon-s-user class="w-4 h-4 text-blue-500"/>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $account->accountManager?->config['name']['fullName'] ?? 'Not set' }}
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
                            <h3 class="text-sm font-medium text-gray-900">Birthday</h3>
                            <x-heroicon-s-calendar class="w-4 h-4 text-blue-500"/>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $account->accountManager?->config['birthday'] ?? 'Not set' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 第二行：Country/Region 和 Language -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Country/Region Card -->
            <div class="rounded-lg border border-gray-100 shadow-sm">
                <div class="p-4">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900">Country / Region</h3>
                            <x-heroicon-s-globe-alt class="w-4 h-4 text-blue-500"/>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $account->accountManager?->config['account']['preferences']['country'] ?? 'Not set' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Language Card -->
            <div class="rounded-lg border border-gray-100 shadow-sm">
                <div class="p-4">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900">Language</h3>
                            <x-heroicon-s-language class="w-4 h-4 text-blue-500"/>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $account->accountManager?->config['account']['preferences']['preferredLanguage'] ?? 'Not set' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Name Edit Modal -->
    <x-filament::modal id="edit-name-modal" width="md">
        <x-slot name="header">
            <h2 class="text-lg font-medium">
                更新姓名
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                请输入您的新姓名。
            </p>
        </x-slot>

        <form wire:submit="saveName" class="space-y-6">
            {{ $this->nameForm }}
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-x-4">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'edit-name-modal' })"
                    color="gray"
                >
                    取消
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    wire:click="saveName"
                    wire:loading.attr="disabled"
                >
                    保存更改
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    <!-- Birthday Edit Modal -->
    <x-filament::modal id="edit-birthday-modal" width="md">
        <x-slot name="header">
            <h2 class="text-lg font-medium">
                更新生日
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                请选择您的生日日期。
            </p>
        </x-slot>

        <form wire:submit="saveBirthday" class="space-y-6">
            {{ $this->birthdayForm }}
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-x-4">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'edit-birthday-modal' })"
                    color="gray"
                >
                    取消
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    wire:click="saveBirthday"
                    wire:loading.attr="disabled"
                >
                    保存更改
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
