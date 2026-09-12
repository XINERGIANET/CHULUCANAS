<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document',
        'name',
        'phone',
        'email',
        'address',
        'position',
        'entry_date',
        'regime',
        'annual_vacation_days',
        'status',
        'termination_date',
        'deleted',
    ];

    protected $dates = [
        'entry_date',
        'termination_date',
    ];

    protected $appends = [
        'tenure_text',
        'vacation_days_generated',
        'vacation_days_taken',
        'vacation_balance',
        'is_on_vacation',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vacations()
    {
        return $this->hasMany(EmployeeVacation::class)->where('deleted', 0)->orderBy('start_date', 'desc');
    }

    /**
     * Días totales laborados
     */
    public function getTotalDaysWorkedAttribute()
    {
        if (!$this->entry_date) {
            return 0;
        }

        $startDate = Carbon::parse($this->entry_date)->startOfDay();
        $endDate = ($this->status == 0 && $this->termination_date)
            ? Carbon::parse($this->termination_date)->startOfDay()
            : now()->startOfDay();

        if ($startDate->gt($endDate)) {
            return 0;
        }

        return $startDate->diffInDays($endDate) + 1;
    }

    /**
     * Tiempo de servicio legible (ej: 1 año, 3 meses, 12 días)
     */
    public function getTenureTextAttribute()
    {
        if (!$this->entry_date) {
            return '-';
        }

        $startDate = Carbon::parse($this->entry_date);
        $endDate = ($this->status == 0 && $this->termination_date)
            ? Carbon::parse($this->termination_date)
            : now();

        $diff = $startDate->diff($endDate);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' ' . ($diff->y == 1 ? 'año' : 'años');
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' ' . ($diff->m == 1 ? 'mes' : 'meses');
        }
        $parts[] = $diff->d . ' ' . ($diff->d == 1 ? 'día' : 'días');

        return implode(', ', $parts);
    }

    /**
     * Total de días de vacaciones generados según régimen REMYPE (15 días anuales por defecto)
     */
    public function getVacationDaysGeneratedAttribute()
    {
        $daysWorked = $this->total_days_worked;
        $annualRate = $this->annual_vacation_days ?: 15;

        // Tasa diaria: 15 días / 365 días al año
        $generated = ($daysWorked * $annualRate) / 365;

        return round($generated, 2);
    }

    /**
     * Días de vacaciones gozados / tomados
     */
    public function getVacationDaysTakenAttribute()
    {
        return (float) $this->vacations()->sum('days');
    }

    /**
     * Saldo pendiente disponible de vacaciones
     */
    public function getVacationBalanceAttribute()
    {
        $balance = $this->vacation_days_generated - $this->vacation_days_taken;
        return round($balance, 2);
    }

    /**
     * Indica si el colaborador se encuentra actualmente en goce de vacaciones
     */
    public function getIsOnVacationAttribute()
    {
        $today = now()->format('Y-m-d');
        return $this->vacations()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();
    }
}
