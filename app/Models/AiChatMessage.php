<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'session_id',
        'user_id',
        'role',
        'content',
        'widget_type',
        'widget_payload',
        'tokens_used',
        'tools_called',
    ];

    protected $casts = [
        'widget_payload' => 'array',
        'tools_called' => 'array',
        'tokens_used' => 'integer',
    ];

    /**
     * Get the session that this message belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }

    /**
     * Get the user that authored or triggered this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
