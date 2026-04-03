# laravel/ai — Patched

## providerOptions on messages
Laravel AI's message conversion layer (`PrismMessages::fromLaravelMessages`) recreated
Prism message objects from scratch, silently dropping any provider-specific options.
This made it impossible to use Anthropic prompt caching (cache_control) via `withProviderOptions`
on individual messages.

- `src/Messages/Message.php` — added `$providerOptions` property, `withProviderOptions()` setter, `getProviderOptions()` getter
- `src/Gateway/Prism/PrismMessages.php` — propagates `getProviderOptions()` to the resulting Prism message objects for UserMessage, AssistantMessage, and ToolResultMessage

## TextGenerationOptions accepts HasProviderOptions without Agent
`TextGenerationOptions::$agent` was typed as `?Agent`, requiring a full Agent implementation
just to pass provider-specific options (e.g. thinking mode). Since Tania uses its own agent
loop and never instantiates a Laravel AI Agent, this made it impossible to pass providerOptions
without implementing the entire Agent interface.

- `src/Gateway/TextGenerationOptions.php` — changed `$agent` type from `?Agent` to `Agent|HasProviderOptions|null`
