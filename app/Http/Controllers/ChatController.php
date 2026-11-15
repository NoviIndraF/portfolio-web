<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PortfolioAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * POST /chat — one chatbot question in, the answer out as plain text, sent piece by piece while the AI writes it.
 * "mode=jd": the message is a job description to compare with the portfolio (longer text allowed).
 * Refusals (offline, limits, validation) are JSON with a "reply".
 * Per-visitor limits are handled by the "chat" rate limiter (see AppServiceProvider).
 */
class ChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse|StreamedResponse
    {
        $jobMatch = $request->input('mode') === 'jd';
        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.config($jobMatch ? 'chatbot.jd_max_length' : 'chatbot.max_length')],
        ]);

        $agent = PortfolioAssistant::make(history: $request->session()->get('chat.history', []), jobMatch: $jobMatch);

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

        return response()->stream(function () use ($agent, $data, $request, $counter) {
            $reply = '';

            try {
                foreach ($agent->stream($data['message']) as $event) {
                    if ($event instanceof TextDelta) {
                        $reply .= $event->delta;
                        echo $event->delta;
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                }
            } catch (Throwable $e) {
                report($e);
            }

            // Failed or empty: the visitor gets the error text, and nothing is counted or remembered.
            if (isset($e) || trim($reply) === '') {
                echo ($reply === '' ? '' : "\n\n").__('site.chat.error');

                return;
            }

            Cache::add($counter, 0, now()->endOfDay());
            Cache::increment($counter);

            $history = [
                ...$agent->history,
                // A pasted job description is long: keep only its start, the answer already lists the requirements.
                ['role' => 'user', 'content' => Str::limit($data['message'], config('chatbot.max_length'))],
                ['role' => 'assistant', 'content' => trim($reply)],
            ];
            $request->session()->put('chat.history', array_slice($history, -config('chatbot.history')));
            // The session was already saved before the stream started, so save it again.
            $request->session()->save();
        }, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // nginx: pass each piece on at once
        ]);
    }

    private function reply(string $text, int $status = 200): JsonResponse
    {
        return response()->json(['reply' => $text], $status);
    }
}
