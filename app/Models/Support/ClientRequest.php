<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClientRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'method',
        'url',
        'body',
        'ip_address',
        'headers',
    ];
}
