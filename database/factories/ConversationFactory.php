<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => fake()->uuid(),
            'role' => fake()->randomElement(['user', 'assistant', 'system']),
            'content' => fake()->sentence(10),
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the conversation is from a user.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
            'content' => fake()->sentence(5).'?',
        ]);
    }

    /**
     * Indicate that the conversation is from an assistant.
     */
    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
            'content' => fake()->sentence(15),
        ]);
    }

    /**
     * Indicate that the conversation belongs to a specific session.
     */
    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
        ]);
    }
}
