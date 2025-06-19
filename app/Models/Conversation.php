<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get conversations for a specific session ordered by creation time
     */
    public static function getSessionHistory(string $sessionId): array
    {
        return self::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Add a message to the conversation
     */
    public static function addMessage(string $sessionId, string $role, string $content, array $metadata = []): self
    {
        return self::create([
            'session_id' => $sessionId,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }
}
