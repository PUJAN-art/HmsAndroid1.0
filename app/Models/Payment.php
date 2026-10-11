<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'fee_id',
        'amount',
        'transaction_id',
        'date',
        'method',
        'status',
        'proof_path',
        'student_remark',
        'admin_remark',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
