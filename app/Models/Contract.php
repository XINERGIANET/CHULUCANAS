<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'number_contract',
        'number_pagare',
        'district_id',
        'client_type',
        'group_name',
        'people',
        'document',
        'name',
        'phone',
        'address',
        'reference',
        'home_type',
        'business_line',
        'business_address',
        'business_start_date',
        'civil_status',
        'husband_name',
        'husband_document',
        'seller_id',
        'requested_amount',
        'months_number',
        'quotas_number',
        'percentage',
        'interest',
        'payable_amount',
        'quota_amount',
        'date',
        'first_payment_date',
        'last_payment_date',
        'paid',
        'deleted',
    ];

    protected $dates = ['date', 'first_payment_date', 'last_payment_date'];

    protected $appends = [
        'has_quota_overdue_1220'
    ];

    public $timestamps = false;

    public function scopeActive($query){
        return $query->where('deleted', 0);
    }

    public function client(){
        if($this->client_type == 'Personal'){
            return $this->name;
        }elseif($this->client_type == 'Grupo'){
            return $this->group_name;
        }
    }

    public function type(){
        if($this->client_type == 'Personal'){
            $contracts = Contract::where('document', $this->document)->count();

            if($contracts > 1){
                return 'Recurrente';
            }

            return 'Nuevo';
        }

        return 'Nuevo';

    }

    public function seller(){
        return $this->belongsTo(User::class);
    }

    public function quotas(){
        return $this->hasMany(Quota::class);
    }

    public function people(){
        $html = '';


        $people = $this->people ? json_decode($this->people) : [];

        foreach($people as $client){
            $html .= '- '.$client->document.' / '.$client->name.'<br>';
        }

        return $html;
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function getHasQuotaOverdue1220Attribute()
    {
        $cutoff = now()->subDays(1220)->startOfDay();
        // asume la relación quotas() existe y tiene campos 'paid' y 'date'
        $exists = $this->quotas()
            ->where('paid', 0)
            ->whereDate('date', '<', $cutoff)
            ->exists();

        return $exists ? 1 : 0;
    }

}
