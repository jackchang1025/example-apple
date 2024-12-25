<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600">
            管理您的 iCloud 存储空间。
        </div>
    </div>

    <div class="grid gap-6 grid-cols-1">
        <!-- 存储空间概览卡片 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">存储空间概览</h3>

            <div class="space-y-4">
                <!-- 总体存储进度条 -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>已使用 32.5GB，共 200GB</span>
                        <span class="text-gray-500">剩余 167.5GB</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary-600 h-2.5 rounded-full" style="width: 16%"></div>
                    </div>
                </div>

                <!-- 存储空间分类 -->
                <div class="space-y-3">
                    <!-- 照片和视频 -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="flex items-center">
                                <x-heroicon-s-photo class="w-4 h-4 mr-2 text-blue-500"/>
                                照片和视频
                            </span>
                            <span>15.2GB</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: 45%"></div>
                        </div>
                    </div>

                    <!-- 备份 -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="flex items-center">
                                <x-heroicon-s-device-phone-mobile class="w-4 h-4 mr-2 text-green-500"/>
                                设备备份
                            </span>
                            <span>10.8GB</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-green-500 h-1.5 rounded-full" style="width: 32%"></div>
                        </div>
                    </div>

                    <!-- 文档 -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="flex items-center">
                                <x-heroicon-s-document class="w-4 h-4 mr-2 text-yellow-500"/>
                                文档和数据
                            </span>
                            <span>6.5GB</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-yellow-500 h-1.5 rounded-full" style="width: 23%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 存储空间管理建议 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">存储空间管理建议</h3>

            <div class="space-y-4">
                <div class="flex items-start space-x-4 p-4 bg-blue-50 rounded-lg">
                    <x-heroicon-s-light-bulb class="w-6 h-6 text-blue-500 flex-shrink-0"/>
                    <div>
                        <h4 class="font-medium text-blue-900">查看大文件</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            查找并管理占用空间较大的文件，释放存储空间。
                        </p>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg">
                    <x-heroicon-s-archive-box class="w-6 h-6 text-green-500 flex-shrink-0"/>
                    <div>
                        <h4 class="font-medium text-green-900">优化照片存储</h4>
                        <p class="text-sm text-green-700 mt-1">
                            开启照片优化存储功能，在设备上保存较小的版本。
                        </p>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-yellow-50 rounded-lg">
                    <x-heroicon-s-trash class="w-6 h-6 text-yellow-500 flex-shrink-0"/>
                    <div>
                        <h4 class="font-medium text-yellow-900">清理旧备份</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            删除不再需要的设备备份，释放更多存储空间。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
