<x-filament::modal id="edit-birthday-modal" width="md">
    <x-slot name="header">
        <h2 class="text-lg font-medium">更新生日</h2>
        <p class="mt-1 text-sm text-gray-500">请选择您的生日日期。</p>
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