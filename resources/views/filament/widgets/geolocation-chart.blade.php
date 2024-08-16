<x-filament::widget>
    <x-filament::card>
        <div id="geolocation-chart" style="width: 100%; height: 600px;"></div>
    </x-filament::card>
</x-filament::widget>

@assets
<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js"></script>
@endassets

@script
<script>
    // 加载世界地图和中国地图的 JSON 数据
    Promise.all([
        fetch('/js/filament/chatr/world.json').then(response => response.json()),
        fetch('/js/filament/chatr/china-area.json').then(response => response.json())
    ]).then(([worldJson, chinaJson]) => {
        echarts.registerMap('world', worldJson);
        echarts.registerMap('china', chinaJson);

        const chartDom = document.getElementById('geolocation-chart');
        const chart = echarts.init(chartDom);
        const data = {!! $chartData !!};

        let currentMap = 'world';

        function createWorldMapOption() {
            return {
                title: {
                    text: '全球访客分布',
                    left: 'center'
                },
                tooltip: {
                    trigger: 'item',
                    formatter: function(params) {
                        if (params.seriesType === 'map') {
                            return params.name + ': ' + params.value + ' 位访客';
                        } else {
                            return params.name + ', ' + params.data.country + ': ' + params.value[2] + ' 位访客';
                        }
                    }
                },
                visualMap: {
                    min: 0,
                    max: Math.max(...data.countries.map(item => item.value)),
                    text: ['高', '低'],
                    realtime: false,
                    calculable: true,
                    inRange: {
                        color: ['lightskyblue', 'yellow', 'orangered']
                    }
                },
                series: [
                    {
                        name: '国家',
                        type: 'map',
                        map: 'world',
                        roam: true,
                        emphasis: {
                            label: {
                                show: true
                            }
                        },
                        data: data.countries
                    },
                    {
                        name: '城市',
                        type: 'effectScatter',
                        coordinateSystem: 'geo',
                        data: data.cities,
                        symbolSize: function (val) {
                            return Math.min(val[2] / 10, 20);
                        },
                        encode: {
                            value: 2
                        },
                        label: {
                            formatter: '{b}',
                            position: 'right',
                            show: false
                        },
                        emphasis: {
                            label: {
                                show: true
                            }
                        }
                    }
                ]
            };
        }

        function createChinaMapOption() {
            const chinaCities = data.cities.filter(city => city.country === '中国');

            // 过滤掉明显不合理的数据
            const validChinaCities = chinaCities.filter(city => {
                const [lng, lat] = city.value;
                return lng >= 73 && lng <= 135 && lat >= 18 && lat <= 54;
            });

            // 生成省份数据
            const provinceData = validChinaCities.reduce((acc, city) => {
                const provinceName = city.name.split(',')[0].trim(); // 假设省份名在城市名之后
                const existingProvince = acc.find(item => item.name === provinceName);
                if (existingProvince) {
                    existingProvince.value += city.value[2];
                } else {
                    acc.push({name: provinceName, value: city.value[2]});
                }
                return acc;
            }, []);


            return {
                title: {
                    text: '中国访客分布',
                    left: 'center'
                },
                tooltip: {
                    trigger: 'item',
                    formatter: function(params) {
                        if (params.seriesType === 'map') {
                            return params.name + ': ' + (params.value || 'N/A') + ' 位访客';
                        } else {
                            return params.name + ': ' + params.value[2] + ' 位访客';
                        }
                    }
                },
                visualMap: {
                    min: 0,
                    max: Math.max(...validChinaCities.map(item => item.value[2])),
                    text: ['高', '低'],
                    realtime: false,
                    calculable: true,
                    inRange: {
                        color: ['lightskyblue', 'yellow', 'orangered']
                    }
                },
                series: [
                    {
                        name: '省份',
                        type: 'map',
                        map: 'china',
                        roam: true,
                        emphasis: {
                            label: {
                                show: true
                            }
                        },
                        data: provinceData
                    },
                    {
                        name: '城市',
                        type: 'effectScatter',
                        coordinateSystem: 'geo',
                        data: validChinaCities,
                        symbolSize: function (val) {
                            return Math.min(val[2] / 5, 20);
                        },
                        encode: {
                            value: 2
                        },
                        label: {
                            formatter: '{b}',
                            position: 'right',
                            show: true
                        }
                    }
                ]
            };
        }

        function updateChart(mapType) {
            try {
                let option = mapType === 'world' ? createWorldMapOption() : createChinaMapOption();
                console.log('Chart option:', option); // 添加日志
                chart.setOption(option, true);
                currentMap = mapType;
            } catch (error) {
                console.error('更新图表时出错:', error);
                console.log('当前数据:', data); // 添加数据日志
            }
        }

        // 初始化为世界地图
        updateChart('world');

        // 点击事件监听器
        chart.on('click', function(params) {
            if (currentMap === 'world' && params.name === 'China') {
                updateChart('china');
            }
        });

        // 双击事件监听器，返回世界地图
        chartDom.addEventListener('dblclick', function() {
            if (currentMap === 'china') {
                updateChart('world');
            }
        });

        // 窗口大小变化时，调整图表大小
        window.addEventListener('resize', function() {
            chart.resize();
        });
    })
        .catch(error => console.error('加载地图数据时出错:', error));
</script>
@endscript
