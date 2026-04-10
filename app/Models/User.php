<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function likedBatikImages()
    {
        return $this->belongsToMany(BatikImage::class, 'batik_image_likes')->withTimestamps();
    }

    public function hasMenuAccess($code)
    {
        if (!$this->role) return false;
        if ($this->role->name === 'Admin') return true; 

        return $this->role->menus()->where('code', $code)->exists();
    }

    public function hasAdminAccess()
    {
        if (!$this->role) return false;
        if ($this->role->name === 'Admin') return true;
        
        return $this->role->menus()->where(function($q) {
            $q->where('code', 'LIKE', 'kelola-%')->orWhere('code', 'monitor-ai');
        })->exists();
    }

    public function getAdminMenus()
    {
        if (!$this->role) return collect();
        
        if ($this->role->name === 'Admin') {
            return \App\Models\Menu::where('code', 'LIKE', 'kelola-%')
                                   ->orWhere('code', 'monitor-ai')
                                   ->get();
        }
        
        return $this->role->menus()->where(function($q) {
            $q->where('code', 'LIKE', 'kelola-%')->orWhere('code', 'monitor-ai');
        })->get();
    }
}
