<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'document',
        'name',
        'address',
        'phone',
        'email',
        'user',
        'password',
        'role',
        'state',
        'deleted',
        'credit_manager_id'
    ];

    protected $hidden = [
        'password'
    ];

    public $timestamps = false;

    public function scopeSeller($query){
        return $query->where('role', 'seller');
    }

    public function scopeActive($query){
        return $query->where('deleted', 0);
    }

    public function hasRole($role){
        return $this->role == $role;
    }

    public function creditManager(){
        return $this->belongsTo(User::class, 'credit_manager_id');
    }

    public function sellers(){
        return $this->hasMany(User::class, 'credit_manager_id');
    }
}
