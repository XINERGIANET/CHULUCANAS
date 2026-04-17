<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class, 'department_id', 'id');
    }

    public function districts(): HasManyThrough
    {
        // departments.id -> provinces.department_id
        // provinces.id   -> districts.province_id
        return $this->hasManyThrough(
            District::class,
            Province::class,
            'department_id', // Foreign key on provinces table...
            'province_id',   // Foreign key on districts table...
            'id',            // Local key on departments table...
            'id'             // Local key on provinces table...
        );
    }
}