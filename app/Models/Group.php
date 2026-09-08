<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = [
        'codigo_grupo',
        'name',
    ];

    /**
     * Relationship with contracts.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'group_id');
    }

    /**
     * Relationship with the initial/first contract of the group.
     */
    public function initialContract()
    {
        return $this->hasOne(Contract::class, 'group_id')->oldestOfMany('date');
    }
}
