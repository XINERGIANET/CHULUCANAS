<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active'
    ];

    public $timestamps = false;

    public function scopeActive($query){
        return $query->where('active', 1);
    }

    public function sales(){
        return $this->hasMany(Sale::class);
    }

    /**
     * Pagos de gastos que usan este método
     */
    public function expensePayments()
    {
        return $this->hasMany(ExpensePayment::class, 'payment_method_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class,  'payment_method_id');
    }
}
