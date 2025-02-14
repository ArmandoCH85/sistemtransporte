<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestTransporter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'request_transporters';

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'request_id', 'transporter_id', 'assignment_status',
        'assignment_date', 'response_date', 'comments'
    ];

    protected $casts = [
        'assignment_date' => 'timestamp',
        'response_date' => 'timestamp',
    ];

    public function scopeActive($query)
    {
        return $query->where('assignment_status', self::STATUS_ACCEPTED);
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class, 'request_id');
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transporter_id');
    }
}
