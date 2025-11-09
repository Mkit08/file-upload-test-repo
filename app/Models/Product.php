<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // use HasFactory;

    protected $fillable = [
        'unique_key', 'product_title', 'product_description', 'style', 'name', 'sanmar_mainframe_color', 'size', 'piece_price', 'color_name'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public $timestamps = true;

    const FILE_COLUMNS = [
        'unique_key', 
        'product_title', 
        'product_description', 
        'style', 
        'name', 
        'sanmar_mainframe_color', 
        'size', 
        'piece_price', 
        'color_name'
    ];
}
