<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'name',
        'email',
        'phone',
        'category',
        'subject',
        'message',
        'status',
        'admin_notes',
    ];

    /**
     * Generate unique support ticket tracking number.
     */
    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));

        return "SUP-{$year}-{$random}";
    }

    /**
     * Get user associated with support ticket (if submitted by logged-in user).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
