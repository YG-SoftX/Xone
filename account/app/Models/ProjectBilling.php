<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'billing_account_id',
    ];

    public function project()
    {
        return $this->belongsTo(DeveloperProject::class);
    }

    public function billingAccount()
    {
        return $this->belongsTo(BillingAccount::class);
    }
}
