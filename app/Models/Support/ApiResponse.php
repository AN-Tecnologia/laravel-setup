<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiResponse extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'api_responses';
    protected $fillable = [
        'method', //string 
        'url', //string
        'body', //longtext
        'status', //integer
        'api_request_id', //string
        'api', //string
    ];
}
