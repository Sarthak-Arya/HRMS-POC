<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\AiMessage
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $content
 * @property string|null $tool_name
 * @property array|null $tool_payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AiConversation $conversation
 */
class AiMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_name',
        'tool_payload',
    ];

    protected $casts = [
        'tool_payload' => 'array',
    ];

    /**
     * Get the conversation that this message belongs to.
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
