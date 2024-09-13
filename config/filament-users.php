<?php

return [
    /*
     * The resource that will be used for the user management.
     * If you want to use your own resource, you can set this to true.
     * and use `php artisan filament-user:publish` to publish the resource.
     */
    "publish_resource" => false,

    /*
     * The Group name of the resource.
     */
    "group" => '用户管理',

    /*
     * User Filament Impersonate
     */
    "impersonate" => false,

    /*
     * User Filament Shield
     */
    "shield" => true,
];
