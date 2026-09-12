<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeVacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'days',
        'period_year',
        'comments',
        'created_by',
        'deleted',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
