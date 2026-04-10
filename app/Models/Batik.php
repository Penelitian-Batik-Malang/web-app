<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batik extends Model
{
    protected $fillable = ['name', 'description', 'type', 'is_active'];

    public function images()
    {
        return $this->hasMany(BatikImage::class);
    }

    public function mainImage()
    {
        return $this->hasOne(BatikImage::class)->where('is_main', true);
    }
}
