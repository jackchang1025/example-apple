<?php

namespace App\Http\Integrations\Proxy\HuaSheng\DTO;

use App\Http\Integrations\Proxy\BaseDto;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class ExtractDto extends BaseDto implements WithResponse
{
    use HasResponse;

    protected array $data = [
        'time'      => 10,// 提取的IP时长（分钟）
        'count'     => 1,// 提取的IP数量
        'only'      => 0,// 是否去重（1=去重，0=不去重）
        'province'  => '',// 省份编号
        'city'      => '',// 城市编号
        'iptype'    => 'tunnel',// IP类型（tunnel=隧道，direct=直连）
        'pw'        => 'no',// 是否需要账号密码（yes=是，no=否）
        'protocol'  => 'HTTP',// IP协议（HTTP=HTTP/HTTPS，s5=socks5）
        'separator' => 1,// 分隔符样式（1=换行，2=逗号，3=空格，4=分号）
        'type'      => 'json',// 返回类型（json=json，text=纯文本）
        'format'    => 'city,time',// 其他返回信息（all=全部，ip=IP，port=端口，user=账号，password=密码）
    ];

    public function __construct(array $data = [])
    {
        parent::__construct(array_merge($this->data, $data));
    }

    public function toQueryParameters(): array
    {
        return array_filter($this->data, fn($value) => $value !== null);
    }
}
