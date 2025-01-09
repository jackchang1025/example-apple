<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecuritySettingResource\Pages\EditSecuritySetting;
use App\Models\SecuritySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SecuritySettingResource extends Resource
{
    protected static ?string $model = SecuritySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $label = '安全设置';
    protected static ?string $slug = 'security-settings';

    public static function form(Form $form): Form
    {
        // 获取 Laravel 支持的语言列表
        $languages = [
            'zh_CN' => '简体中文',
            'zh_TW' => '繁体中文',
            'en'    => '英语',
            'ja'    => '日语',
            'ko'    => '韩语',
            'fr'    => '法语',
            'de'    => '德语',
            'es'    => '西班牙语',
            'ru'    => '俄语',
            'ar'    => '阿拉伯语',
            'pt'    => '葡萄牙语',
            'it'    => '意大利语',
            'vi'    => '越南语',
            'th'    => '泰语',
            'id'    => '印尼语',
            'hi'    => '印地语',
            'tr'    => '土耳其语',
            'pl'    => '波兰语',
            'nl'    => '荷兰语',
            'sv'    => '瑞典语',
            'el'    => '希腊语',
            'ro'    => '罗马尼亚语',
            'cs'    => '捷克语',
            'hu'    => '匈牙利语',
            'he'    => '希伯来语',
            'fa'    => '波斯语',
            'ms'    => '马来语',
        ];

        return $form
            ->schema([
                Forms\Components\TagsInput::make('authorized_ips')
                    ->label('Authorized IP Addresses')
                    ->placeholder('示例: 1.1.1.1,2.2.2.1-2.2.2.2')
                    ->helperText('设置管理后台访问授权IP，可设置多个IP地址，注意：一旦设置授权IP，只有指定IP的电脑能访问管理后台'),

                Forms\Components\TagsInput::make('configuration.blacklist_ips')
                    ->label('blacklist IP Addresses')
                    ->placeholder('示例: 1.1.1.1,2.2.2.1-2.2.2.2')
                    ->helperText('设置整个网站黑名单 IP，可设置多个IP地址，注意：一旦设置黑名单 IP，则黑名单 IP 的电脑不能访问整个网站'),

                Forms\Components\TextInput::make('safe_entrance')
                    ->label('Safe Entrance URL')
                    ->prefix('/')
                    ->required()
                    ->default('admin')
                    ->placeholder('admin')
                    ->helperText('管理入口，设置后只能通过指定安全入口登录,如: /admin')
                    ->alphaDash()
                    ->maxLength(255),

                Forms\Components\Select::make('configuration.country_code')
                    ->label('国家区号')
                    ->options([
                        'AF' => '阿富汗 (AF) +93',
                        'AL' => '阿尔巴尼亚 (AL) +355',
                        'DZ' => '阿尔及利亚 (DZ) +213',
                        'AS' => '美属萨摩亚 (AS) +1684',
                        'AD' => '安道尔 (AD) +376',
                        'AO' => '安哥拉 (AO) +244',
                        'AI' => '安圭拉 (AI) +1264',
                        'AG' => '安提瓜和巴布达 (AG) +1268',
                        'AR' => '阿根廷 (AR) +54',
                        'AM' => '亚美尼亚 (AM) +374',
                        'AW' => '阿鲁巴 (AW) +297',
                        'AU' => '澳大利亚 (AU) +61',
                        'AT' => '奥地利 (AT) +43',
                        'AZ' => '阿塞拜疆 (AZ) +994',
                        'BS' => '巴哈马 (BS) +1242',
                        'BH' => '巴林 (BH) +973',
                        'BD' => '孟加拉国 (BD) +880',
                        'BB' => '巴巴多斯 (BB) +1246',
                        'BY' => '白俄罗斯 (BY) +375',
                        'BE' => '比利时 (BE) +32',
                        'BZ' => '伯利兹 (BZ) +501',
                        'BJ' => '贝宁 (BJ) +229',
                        'BM' => '百慕大 (BM) +1441',
                        'BT' => '不丹 (BT) +975',
                        'BO' => '玻利维亚 (BO) +591',
                        'BA' => '波斯尼亚和黑塞哥维那 (BA) +387',
                        'BW' => '博茨瓦纳 (BW) +267',
                        'BR' => '巴西 (BR) +55',
                        'BN' => '文莱 (BN) +673',
                        'BG' => '保加利亚 (BG) +359',
                        'BF' => '布基纳法索 (BF) +226',
                        'BI' => '布隆迪 (BI) +257',
                        'KH' => '柬埔寨 (KH) +855',
                        'CM' => '喀麦隆 (CM) +237',
                        'CA' => '加拿大 (CA) +1',
                        'CV' => '佛得角 (CV) +238',
                        'KY' => '开曼群岛 (KY) +1345',
                        'CF' => '中非共和国 (CF) +236',
                        'TD' => '乍得 (TD) +235',
                        'CL' => '智利 (CL) +56',
                        'CN' => '中国 (CN) +86',
                        'CO' => '哥伦比亚 (CO) +57',
                        'KM' => '科摩罗 (KM) +269',
                        'CG' => '刚果共和国 (CG) +242',
                        'CD' => '刚果民主共和国 (CD) +243',
                        'CK' => '库克群岛 (CK) +682',
                        'CR' => '哥斯达黎加 (CR) +506',
                        'HR' => '克罗地亚 (HR) +385',
                        'CU' => '古巴 (CU) +53',
                        'CY' => '塞浦路斯 (CY) +357',
                        'CZ' => '捷克共和国 (CZ) +420',
                        'DK' => '丹麦 (DK) +45',
                        'DJ' => '吉布提 (DJ) +253',
                        'DM' => '多米尼克 (DM) +1767',
                        'DO' => '多米尼加共和国 (DO) +1809',
                        'EC' => '厄瓜多尔 (EC) +593',
                        'EG' => '埃及 (EG) +20',
                        'SV' => '萨尔瓦多 (SV) +503',
                        'GQ' => '赤道几内亚 (GQ) +240',
                        'ER' => '厄立特里亚 (ER) +291',
                        'EE' => '爱沙尼亚 (EE) +372',
                        'ET' => '埃塞俄比亚 (ET) +251',
                        'FO' => '法罗群岛 (FO) +298',
                        'FJ' => '斐济 (FJ) +679',
                        'FI' => '芬兰 (FI) +358',
                        'FR' => '法国 (FR) +33',
                        'GA' => '加蓬 (GA) +241',
                        'GM' => '冈比亚 (GM) +220',
                        'GE' => '格鲁吉亚 (GE) +995',
                        'DE' => '德国 (DE) +49',
                        'GH' => '加纳 (GH) +233',
                        'GI' => '直布罗陀 (GI) +350',
                        'GR' => '希腊 (GR) +30',
                        'GL' => '格陵兰 (GL) +299',
                        'GT' => '危地马拉 (GT) +502',
                        'GN' => '几内亚 (GN) +224',
                        'GW' => '几内亚比绍 (GW) +245',
                        'GY' => '圭亚那 (GY) +592',
                        'HT' => '海地 (HT) +509',
                        'HN' => '洪都拉斯 (HN) +504',
                        'HK' => '香港 (HK) +852',
                        'HU' => '匈牙利 (HU) +36',
                        'IS' => '冰岛 (IS) +354',
                        'IN' => '印度 (IN) +91',
                        'ID' => '印度尼西亚 (ID) +62',
                        'IR' => '伊朗 (IR) +98',
                        'IQ' => '伊拉克 (IQ) +964',
                        'IE' => '爱尔兰 (IE) +353',
                        'IL' => '以色列 (IL) +972',
                        'IT' => '意大利 (IT) +39',
                        'JM' => '牙买加 (JM) +1876',
                        'JP' => '日本 (JP) +81',
                        'JO' => '约旦 (JO) +962',
                        'KZ' => '哈萨克斯坦 (KZ) +7',
                        'KE' => '肯尼亚 (KE) +254',
                        'KI' => '基里巴斯 (KI) +686',
                        'KW' => '科威特 (KW) +965',
                        'KG' => '吉尔吉斯斯坦 (KG) +996',
                        'LA' => '老挝 (LA) +856',
                        'LV' => '拉脱维亚 (LV) +371',
                        'LB' => '黎巴嫩 (LB) +961',
                        'LS' => '莱索托 (LS) +266',
                        'LR' => '利比里亚 (LR) +231',
                        'LY' => '利比亚 (LY) +218',
                        'LI' => '列支敦士登 (LI) +423',
                        'LT' => '立陶宛 (LT) +370',
                        'LU' => '卢森堡 (LU) +352',
                        'MO' => '澳门 (MO) +853',
                        'MK' => '北马其顿 (MK) +389',
                        'MG' => '马达加斯加 (MG) +261',
                        'MW' => '马拉维 (MW) +265',
                        'MY' => '马来西亚 (MY) +60',
                        'MV' => '马尔代夫 (MV) +960',
                        'ML' => '马里 (ML) +223',
                        'MT' => '马耳他 (MT) +356',
                        'MH' => '马绍尔群岛 (MH) +692',
                        'MR' => '毛里塔尼亚 (MR) +222',
                        'MU' => '毛里求斯 (MU) +230',
                        'MX' => '墨西哥 (MX) +52',
                        'FM' => '密克罗尼西亚 (FM) +691',
                        'MD' => '摩尔多瓦 (MD) +373',
                        'MC' => '摩纳哥 (MC) +377',
                        'MN' => '蒙古 (MN) +976',
                        'ME' => '黑山 (ME) +382',
                        'MA' => '摩洛哥 (MA) +212',
                        'MZ' => '莫桑比克 (MZ) +258',
                        'MM' => '缅甸 (MM) +95',
                        'NA' => '纳米比亚 (NA) +264',
                        'NR' => '瑙鲁 (NR) +674',
                        'NP' => '尼泊尔 (NP) +977',
                        'NL' => '荷兰 (NL) +31',
                        'NZ' => '新西兰 (NZ) +64',
                        'NI' => '尼加拉瓜 (NI) +505',
                        'NE' => '尼日尔 (NE) +227',
                        'NG' => '尼日利亚 (NG) +234',
                        'KP' => '朝鲜 (KP) +850',
                        'NO' => '挪威 (NO) +47',
                        'OM' => '阿曼 (OM) +968',
                        'PK' => '巴基斯坦 (PK) +92',
                        'PW' => '帕劳 (PW) +680',
                        'PS' => '巴勒斯坦 (PS) +970',
                        'PA' => '巴拿马 (PA) +507',
                        'PG' => '巴布亚新几内亚 (PG) +675',
                        'PY' => '巴拉圭 (PY) +595',
                        'PE' => '秘鲁 (PE) +51',
                        'PH' => '菲律宾 (PH) +63',
                        'PL' => '波兰 (PL) +48',
                        'PT' => '葡萄牙 (PT) +351',
                        'PR' => '波多黎各 (PR) +1787',
                        'QA' => '卡塔尔 (QA) +974',
                        'RO' => '罗马尼亚 (RO) +40',
                        'RU' => '俄罗斯 (RU) +7',
                        'RW' => '卢旺达 (RW) +250',
                        'WS' => '萨摩亚 (WS) +685',
                        'SM' => '圣马力诺 (SM) +378',
                        'ST' => '圣多美和普林西比 (ST) +239',
                        'SA' => '沙特阿拉伯 (SA) +966',
                        'SN' => '塞内加尔 (SN) +221',
                        'RS' => '塞尔维亚 (RS) +381',
                        'SC' => '塞舌尔 (SC) +248',
                        'SL' => '塞拉利昂 (SL) +232',
                        'SG' => '新加坡 (SG) +65',
                        'SK' => '斯洛伐克 (SK) +421',
                        'SI' => '斯洛文尼亚 (SI) +386',
                        'SB' => '所罗门群岛 (SB) +677',
                        'SO' => '索马里 (SO) +252',
                        'ZA' => '南非 (ZA) +27',
                        'KR' => '韩国 (KR) +82',
                        'SS' => '南苏丹 (SS) +211',
                        'ES' => '西班牙 (ES) +34',
                        'LK' => '斯里兰卡 (LK) +94',
                        'SD' => '苏丹 (SD) +249',
                        'SR' => '苏里南 (SR) +597',
                        'SZ' => '斯威士兰 (SZ) +268',
                        'SE' => '瑞典 (SE) +46',
                        'CH' => '瑞士 (CH) +41',
                        'SY' => '叙利亚 (SY) +963',
                        'TW' => '台湾 (TW) +886',
                        'TJ' => '塔吉克斯坦 (TJ) +992',
                        'TZ' => '坦桑尼亚 (TZ) +255',
                        'TH' => '泰国 (TH) +66',
                        'TL' => '东帝汶 (TL) +670',
                        'TG' => '多哥 (TG) +228',
                        'TO' => '汤加 (TO) +676',
                        'TT' => '特立尼达和多巴哥 (TT) +1868',
                        'TN' => '突尼斯 (TN) +216',
                        'TR' => '土耳其 (TR) +90',
                        'TM' => '土库曼斯坦 (TM) +993',
                        'TV' => '图瓦卢 (TV) +688',
                        'UG' => '乌干达 (UG) +256',
                        'UA' => '乌克兰 (UA) +380',
                        'AE' => '阿联酋 (AE) +971',
                        'GB' => '英国 (GB) +44',
                        'US' => '美国 (US) +1',
                        'UY' => '乌拉圭 (UY) +598',
                        'UZ' => '乌兹别克斯坦 (UZ) +998',
                        'VU' => '瓦努阿图 (VU) +678',
                        'VA' => '梵蒂冈 (VA) +379',
                        'VE' => '委内瑞拉 (VE) +58',
                        'VN' => '越南 (VN) +84',
                        'YE' => '也门 (YE) +967',
                        'ZM' => '赞比亚 (ZM) +260',
                        'ZW' => '津巴布韦 (ZW) +263',
                    ])
                    ->required()
                    ->default('CN')
                    ->searchable()
                    ->placeholder('选择国家区号')
                    ->helperText('选择国家的区号，如: CN (+86)'),

                Forms\Components\Select::make('configuration.language')
                    ->label('系统语言')
                    ->options($languages)
                    ->required()
                    ->default('zh_CN')  // 默认为简体中文
                    ->searchable()
                    ->placeholder('选择系统语言'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('authorized_ips'),
                Tables\Columns\TextColumn::make('safe_entrance'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSecuritySetting::route('/'),
        ];
    }
}
