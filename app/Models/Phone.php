<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 *
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Phone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Phone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Phone query()
 * @mixin \Eloquent
 */
class Phone extends Model
{
    use HasFactory;

    protected $table = 'phone';

    protected $fillable = ['phone','phone_address','country_code','country_dial_code','status'];

}
