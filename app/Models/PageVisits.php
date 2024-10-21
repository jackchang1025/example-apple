<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $uri
 * @property string|null $name
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property string $visited_at
 * @property string|null $country
 * @property string|null $city
 * @property string $device_type
 * @property string|null $browser
 * @property string|null $platform
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits query()
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereVisitedAt($value)
 * @property float|null $latitude 经度
 * @property float|null $longitude 维度
 * @method static \Database\Factories\PageVisitsFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PageVisits whereLongitude($value)
 * @mixin \Eloquent
 */
class PageVisits extends Model
{
    use HasFactory;

    protected $table = 'page_visits';

    protected $fillable = [
        'uri',
        'name',
        'ip_address',
        'visited_at',
        'country',
        'city',
        'device_type',
        'browser',
        'platform',
        'latitude',
        'longitude',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
