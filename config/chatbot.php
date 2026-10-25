<?php

/**
 * Portfolio chatbot ("Ask about me"). The AI keys live in config/ai.php (GEMINI_API_KEY, GROQ_API_KEY).
 */
return [
    'enabled' => (bool) env('CHATBOT_ENABLED', true),

    // Tried in order (provider name from config/ai.php => model): if one is overloaded,
    // rate-limited or down, the next one answers. A provider without an API key is skipped.
    'providers' => [
        'gemini' => env('CHATBOT_GEMINI_MODEL', 'gemini-3.5-flash'),
        'gemini-backup' => env('CHATBOT_GEMINI_BACKUP_MODEL', 'gemini-3.1-flash-lite'),
        'groq' => env('CHATBOT_GROQ_MODEL'),
    ],

    // Max chat messages per visitor (IP) per hour.
    'per_hour' => (int) env('CHATBOT_PER_HOUR', 20),

    // Max chat messages for the whole site per day, to stay inside free quotas.
    'daily_limit' => (int) env('CHATBOT_DAILY_LIMIT', 300),

    // How many earlier messages are sent along so the AI remembers the conversation.
    'history' => 10,

    'max_length' => 500,
];
