<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Expense;
use App\Models\PaymentMethod;

class ExpensePhoto extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    protected $table = 'expenses_photos';

    protected $fillable = [
        'expense_id',
        'url',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

}
