<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PortfolioAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * POST /chat — one chatbot question in, one answer out (JSON).
 * Per-visitor limits are handled by the "chat" rate limiter (see AppServiceProvider).
 */
class ChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.config('chatbot.max_length')],
        ]);

        $agent = PortfolioAssistant::make(history: $request->session()->get('chat.history', []));

        if (! config('chatbot.enabled') || $agent->provider() === []) {
            return $this->reply(__('site.chat.offline'), 503);
        }

        // Site-wide daily cap, so free AI quotas are never used up.
        $counter = 'chatbot.count.'.now()->toDateString();
        if (Cache::get($counter, 0) >= config('chatbot.daily_limit')) {
            return $this->reply(__('site.chat.daily_limit'), 429);
        }

        // Each provider may take up to its #[Timeout] (15 s) before failover, so the 30 s PHP default is too short.
        set_time_limit(count($agent->provider()) * 15 + 15);

        try {
            $reply = trim((string) $agent->prompt($data['message']));
        } catch (Throwable $e) {
            report($e);

            return $this->reply(__('site.chat.error'), 503);
        }

        Cache::add($counter, 0, now()->endOfDay());
        Cache::increment($counter);

        $history = [
            ...$agent->history,
            ['role' => 'user', 'content' => $data['message']],
            ['role' => 'assistant', 'content' => $reply],
        ];
        $request->session()->put('chat.history', array_slice($history, -config('chatbot.history')));

        return $this->reply($reply);
    }

    private function reply(string $text, int $status = 200): JsonResponse
    {
        return response()->json(['reply' => $text], $status);
    }
}
