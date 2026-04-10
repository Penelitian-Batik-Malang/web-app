<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingContent extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Helper mapping key to value
     */
    public static function getMap()
    {
        return self::pluck('value', 'key')->all();
    }
}
