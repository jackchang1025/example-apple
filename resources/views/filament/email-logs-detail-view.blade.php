<!-- Main card for a single log entry -->
<div class="overflow-hidden rounded-lg dark:border-gray-700">
    <!-- Card Content Area: Restored top border colors -->
    <div class="px-4 py-2 sm:px-6 dark:border-gray-700">
        <!-- Container for message and request_data sections -->
        <div class="space-y-2 w-full">
            <!-- Message Section -->
            <div>
                <!-- Restored left border colors -->
                <div class="pl-5 border-l-2 border-gray-200 dark:border-gray-600">
                    <!-- Restored message box styling: dark mode text, background -->
                    <div class="text-sm text-gray-800 dark:text-gray-100   p-3 rounded">
                        {{ $getRecord()->action }} {{ $getRecord()->created_at }}
                    </div>
                </div>
            </div>
            <!-- Request Data Section (conditional) -->
            @if(!empty($getRecord()->request))
            <div x-data="{ open: true }">
                <!-- Restored left border colors and fixed syntax -->
                <div class="pl-5">
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="inline-flex items-center justify-center mb-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 focus:outline-none">
                        <svg x-show="!open" class="w-4 h-4 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <svg x-show="open" class="w-4 h-4 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span x-text="open ? '折叠' : '展开'"></span>
                    </button>
                    <!-- Box for JSON/request string: Added w-fit, restored background -->
                    <div x-show="open" x-transition class="  rounded-lg p-2 overflow-x-auto h-auto">
                        @if(is_array($getRecord()->request))
                        <!-- Restored pre tag styling: dark mode text, background, border, rounded -->
                        <pre class="whitespace-pre-wrap text-xs text-gray-700 dark:text-gray-200 p-1 rounded border-gray-200 dark:border-gray-600">{{ json_encode($getRecord()->request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                        <!-- Ensured consistent padding and dark mode text for span -->
                        <span class="text-sm text-gray-700 dark:text-gray-200 block p-1">{{ $getRecord()->request }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>