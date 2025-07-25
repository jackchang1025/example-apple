<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name'        => '角色名',
    'column.guard_name'  => '守卫',
    'column.roles'       => '角色',
    'column.permissions' => '权限',
    'column.updated_at'  => '更新时间',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name'               => '角色名',
    'field.guard_name'         => '守卫',
    'field.permissions'        => '权限',
    'field.select_all.name'    => '全选',
    'field.select_all.message' => '启用当前为该角色 <span class="text-primary font-medium">启用的</span> 所有权限',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group'            => 'Filament Shield',
    'nav.role.label'       => '角色',
    'nav.role.icon'        => 'heroicon-o-shield-check',
    'resource.label.role'  => '角色',
    'resource.label.roles' => '角色',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section'   => '实体',
    'resources' => '资源',
    'widgets'   => '小组件',
    'pages'     => '页面',
    'custom'    => '自定义',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => '无权访问',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view'             => '详情',
        'view_any'         => '列表',
        'create'           => '创建',
        'update'           => '编辑',
        'delete'           => '删除',
        'delete_any'       => '批量删除',
        'force_delete'     => '永久删除',
        'force_delete_any' => '批量永久删除',
        'restore'          => '恢复',
        'reorder'          => '重新排序',
        'restore_any'      => '批量恢复',
        'replicate'        => '复制',
        'view_trash'       => '查看回收站',
    ],
];
