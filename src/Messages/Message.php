<?php

namespace Laravel\Ai\Messages;

use InvalidArgumentException;

class Message
{
    /**
     * The message role.
     */
    public MessageRole $role;

    /**
     * The message content.
     */
    public ?string $content;

    /**
     * Provider-specific options for this message (e.g. Anthropic cache_control).
     *
     * @var array<string, mixed>
     */
    protected array $providerOptions = [];

    /**
     * Create a new text conversation message instance.
     */
    public function __construct(MessageRole|string $role, ?string $content = '')
    {
        $this->content = $content;

        $this->role = $role instanceof MessageRole
            ? $role
            : (MessageRole::tryFrom($role) ?? throw new InvalidArgumentException('Invalid message role.'));
    }

    /**
     * Set provider-specific options on the message (returns a clone).
     *
     * @param  array<string, mixed>  $options
     */
    public function withProviderOptions(array $options): static
    {
        $clone = clone $this;
        $clone->providerOptions = $options;

        return $clone;
    }

    /**
     * Get the provider-specific options for this message.
     *
     * @return array<string, mixed>
     */
    public function getProviderOptions(): array
    {
        return $this->providerOptions;
    }

    /**
     * Attempt to create a new message instance from the given value.
     */
    public static function tryFrom(mixed $message): self
    {
        return match (true) {
            $message instanceof self => $message,
            is_array($message) => new self($message['role'], $message['content']),
            is_object($message) => new self($message->role, $message->content),
            default => throw new InvalidArgumentException('Unable to create message from given value.'),
        };
    }
}
