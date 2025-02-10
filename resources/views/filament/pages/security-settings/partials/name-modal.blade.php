<x-filament::modal id="edit-name-modal" width="md">
    <x-slot name="header">
        <h2 class="text-lg font-medium">更新姓名</h2>
        <p class="mt-1 text-sm text-gray-500">请输入您的新姓名。</p>
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