<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'signer_id',
        'signed_at',
        'ip_address',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signer_id');
    }
}