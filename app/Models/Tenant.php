<?php

namespace App\Models;

use App\Enums\TenantType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    public $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_name',
        'tenant_type',
        'tenant_phone',
        'tenant_address',
        'booth_num',
        'area_sm',
    ];

    protected $casts = [
        'tenant_type' => TenantType::class,
    ];
}
