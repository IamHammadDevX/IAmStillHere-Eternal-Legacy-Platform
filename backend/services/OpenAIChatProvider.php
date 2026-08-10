<?php

require_once __DIR__ . '/ChatProviderInterface.php';

class OpenAIChatProvider implements ChatProviderInterface
{
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = trim((string) ($apiKey ?? getenv('OPENAI_API_KEY')));
        $this->model = trim((string) ($model ?? getenv('OPENAI_CHAT_MODEL') ?: 'gpt-5-mini'));
    }

    public function chat(array $messages, array $options = []): array
    {
        if ($this->apiKey === '') throw new RuntimeException('ai_api_key_missing');
        if (!function_exists('curl_init')) throw new RuntimeException('ai_http_unavailable');

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'max_completion_tokens' => (int) ($options['max_tokens'] ?? 1600),
        ];

        $curl = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($curl);
        curl_close($curl);

        if ($body === false || $curlError !== 0) throw new RuntimeException('ai_provider_unavailable');
        if ($status === 429) throw new RuntimeException('ai_rate_limited');
        if ($status < 200 || $status >= 300) throw new RuntimeException('ai_provider_error');

        $decoded = json_decode($body, true);
        $answer = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
        if ($answer === '') throw new RuntimeException('ai_provider_response_invalid');

        return [
            'answer' => $answer,
            'model' => (string) ($decoded['model'] ?? $this->model),
            'usage' => [
                'prompt_tokens' => isset($decoded['usage']['prompt_tokens']) ? (int) $decoded['usage']['prompt_tokens'] : null,
                'completion_tokens' => isset($decoded['usage']['completion_tokens']) ? (int) $decoded['usage']['completion_tokens'] : null,
                'total_tokens' => isset($decoded['usage']['total_tokens']) ? (int) $decoded['usage']['total_tokens'] : null,
            ],
        ];
    }
}
