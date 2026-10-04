<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiChatSession extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_sessions';

    protected $fillable = [
        'session_uuid',
        'user_id',
        'title',
        'total_tokens',
        'last_interaction_at',
    ];

    protected $casts = [
        'total_tokens' => 'integer',
        'last_interaction_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($session) {
            if (empty($session->session_uuid)) {
                $session->session_uuid = (string) Str::uuid();
            }
            if (empty($session->last_interaction_at)) {
                $session->last_interaction_at = Carbon::now();
            }
        });
    }

    /**
     * Get the user that owns this chat session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the chat session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'session_id');
    }

    /**
     * Record tokens and refresh last interaction timestamp.
     */
    public function recordInteraction(int $tokens = 0): void
    {
        $this->last_interaction_at = Carbon::now();
        if ($tokens > 0) {
            $this->total_tokens += $tokens;
        }
        $this->save();
    }
}
