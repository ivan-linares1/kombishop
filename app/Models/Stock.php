<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'oitw';
    public $incrementing = false;
    protected $primaryKey = null;
    public $timestamps = false;

    protected $fillable = [
        'item_code',
        'whs_code',
        'on_hand',
    ];

    public function item()
    {
        return $this->belongsTo(Articulo::class, 'item_code', 'item_code');
    }
}
