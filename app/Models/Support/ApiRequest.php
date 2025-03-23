<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'api_requests';
    protected $fillable = [
        'body', //longtext
        'method', //string
        'headers', //text
        'url', //string
        'api', //string
    ];
}
