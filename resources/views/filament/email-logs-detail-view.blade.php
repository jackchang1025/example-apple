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
            <div>
                <!-- Restored left border colors -->
                <div class="pl-5 border-l-2 border-gray-200 >
                    <!-- Box for JSON/request string: Added w-fit, restored background -->
                    <div class="w-fit bg-gray-50 dark:bg-gray-700 rounded-lg p-2 overflow-x-auto h-auto">
                        @if(is_array($getRecord()->request))
                        <!-- Restored pre tag styling: dark mode text, background, border, rounded -->
                        <pre class="whitespace-pre-wrap text-xs text-gray-700 dark:text-gray-200 p-1 rounded  border-gray-200 dark:border-gray-600">{{ json_encode($getRecord()->request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
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