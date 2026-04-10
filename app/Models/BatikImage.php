<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatikImage extends Model
{
    protected $fillable = ['batik_id', 'image_path', 'is_main'];

    public function batik()
    {
        return $this->belongsTo(Batik::class);
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'batik_image_likes')->withTimestamps();
    }
}
