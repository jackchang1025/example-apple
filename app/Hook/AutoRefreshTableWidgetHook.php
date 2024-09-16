<?php

namespace App\Hook;

use Illuminate\Support\Facades\Blade;

class AutoRefreshTableWidgetHook
{

    public static function render1(): string
    {
        $uniqueId = 'auto-refresh-' .uniqid(time(), true);

        return Blade::render(<<<BLADE
            <div id="{$uniqueId}" data-initialized="false"></div>
            <script>
                (function() {
                    console.log('IIFE executed');

                    function initAutoRefresh() {
                        console.log('initAutoRefresh called');
                        var element = document.getElementById('{$uniqueId}');
                        if (element.dataset.initialized === 'true') {
                            console.log('Already initialized');
                            return;
                        }
                        element.dataset.initialized = 'true';
                        console.log('Setting up interval');
                        setInterval(function() {
                            if (typeof Livewire !== 'undefined') {
                                console.log('Attempting to refresh table');
                                Livewire.emit('refreshTable');
                            } else {
                                console.log('Livewire not defined');
                            }
                        }, 5000); // 5秒
                    }

                    function ensureLivewireLoaded() {
                        if (typeof Livewire !== 'undefined') {
                            console.log('Livewire is loaded');
                            initAutoRefresh();
                        } else {
                            console.log('Livewire not loaded yet, retrying in 1 second');
                            setTimeout(ensureLivewireLoaded, 1000);
                        }
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('DOMContentLoaded fired');
                        ensureLivewireLoaded();
                    });

                    document.addEventListener('livewire:load', function() {
                        console.log('livewire:load event triggered');
                        ensureLivewireLoaded();
                    });
                })();
            </script>
        BLADE);
    }

    public static function render($refreshInterval = 5000): string
    {
        return Blade::render(<<<BLADE
            <div x-data="{ refreshInterval: {$refreshInterval} }" x-init="
                console.log('Alpine.js initialized with refresh interval:', refreshInterval);
                setInterval(() => {
                    console.log('Attempting to refresh table');
                    \$wire.\$refresh()
                }, refreshInterval)
            "></div>
        BLADE);
    }
}
