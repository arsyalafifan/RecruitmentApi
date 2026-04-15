<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobseeker extends Model
{
    protected $table = 'dtJobseeker';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'AccountID',
        'NIK',
        'Name',
        'Gender',
        'Birth_date',
        'Birth_place',
        'marital_status',
        'Religion',
        'Race',
        'HP1',
        'HP2',
        'Email',
        'AddsKTP',
        'AddsKTP_Prov',
        'AddsKTP_Kota',
        'AddsKTP_Kec',
        'AddsKTP_Kel',
        'sameKTP',
        'AddsDOM',
        'AddsDOM_Prov',
        'AddsDOM_Kota',
        'AddsDOM_Kec',
        'AddsDOM_Kel',
        'LastEducLevel',
        'LastEducInstitu',
        'LastEducMajor',
        'LastEducFinish',
        'LastEducScore',
        'DataAgreeBool',
        'Activebool',
        'Insertby',
        'InsertDate',
        'UpdateBy',
        'UpdateDate',
        'LastUserActivity',
    ];
}
