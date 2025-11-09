<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    // use HasFactory;

    protected $fillable = [
        'user_id', 'file_name', 'file_path', 'file_hash', 'status', 'processed_rows',
    ];

    protected $uniqueColumn = [
        'UNIQUE_KEY'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
