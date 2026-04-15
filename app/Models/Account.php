<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'dtAccountJS';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'AccountNo',
        'AccountName',
        'AccountPass',
        'Mail',
        'PhoneNo',
        'Country',
        'ActiveBool',
        'LoginToken',
        'TokenExpiredAt',
        'CreatedAt',
        'UpdatedAt'
    ];

    protected $hidden = [
        'AccountPass',
        'LoginToken'
    ];
}