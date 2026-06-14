# laravel/ai — Patched (fork of v0.8.1)

Base: upstream `laravel/ai` **v0.8.1**. This fork carries only the deltas Tania needs.
Prism is gone upstream (commit `b81ec13`/#414) — these patches target the native gateways.

## 1. TextGenerationOptions accepts HasProviderOptions without an Agent
`TextGenerationOptions::$agent` is typed `?Agent`, so passing provider options (e.g. thinking
mode) requires implementing the whole Agent interface. Tania runs its own loop and never builds
a laravel/ai Agent.

- `src/Gateway/TextGenerationOptions.php` — `$agent` widened from `?Agent` to
  `Agent|HasProviderOptions|null`. `providerOptions()` already gates on `instanceof HasProviderOptions`.

## 2. Per-message provider options (Anthropic prompt caching / cache_control)
Upstream's message → provider conversion drops per-message options, so `cache_control` can't be
set per message. (Re-home of the old Prism-era `0bcea96` patch onto the native gateways.)

- `src/Messages/Message.php` — added `$providerOptions` + `withProviderOptions()` (clone) /
  `getProviderOptions()`.
- `src/Gateway/Anthropic/Concerns/MapsMessages.php` — `applyMessageProviderOptions()` injects the
  options into the last content block of user/assistant/tool-result messages.
- `src/Gateway/OpenRouter/Concerns/MapsMessages.php` — same injection for user messages (string
  content is promoted to a content-block array when options are present).

## 3. Streaming tool-execution gate (consistent with non-streaming `maxSteps`)
Upstream's streaming path executes tools as soon as the model emits them, ignoring `maxSteps` on
the first round (the gate only stops the *next* round). The non-streaming path gates execution on
`$depth + 1 < maxSteps`. This made it impossible to get tool calls back **without executing them**
while streaming — which Tania needs to run its own per-user tool-approval loop.

This patch mirrors the non-streaming gate in every provider's streaming handler: when the step
budget is exhausted, the tool-call events are still emitted (so the caller has the calls) but the
tools are **not** executed — a `StreamEnd(tool_calls)` is yielded instead.

- `src/Gateway/{Anthropic,DeepSeek,Gemini,Groq,Mistral,Ollama,OpenAi,OpenRouter,Xai}/Concerns/HandlesTextStreaming.php`

With `maxSteps: 1` this yields zero gateway-side tool executions on every provider, both streaming
and non-streaming — execution/approval stays entirely with the caller.

Patches 1 and 3 are good upstream-PR candidates; if accepted, only patch 2 would remain.
