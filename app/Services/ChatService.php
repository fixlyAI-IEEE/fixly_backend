<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\JobType;
use App\Models\Worker;
use Illuminate\Support\Facades\Http;

class ChatService
{
    private const SYSTEM_PROMPT = <<<PROMPT
You are Fixly Bot, a helpful assistant for a home services platform in Egypt.
You help users diagnose and fix common household and vehicle problems.

Your behavior:
1. Always respond in the SAME language the user writes in (Arabic or English).
2. First, suggest simple DIY steps the user can try themselves.
3. If the problem is complex or dangerous, recommend the appropriate worker type from this list ONLY:
   - Carpentry (furniture and doors)
   - Plumbing (pipe and water repairs)
   - Electrical work (wiring and maintenance)
   - Blacksmithing (metalwork and doors)
   - Tailoring (clothing alterations and repairs)
   - Mobile phones and device repair
   - Mechanics (car repairs)
   - Painting and finishing
   - Tiles and ceramics
4. When recommending a worker type, end your response with exactly this format on a new line:
   RECOMMEND_WORKER:job_type_name
   Example: RECOMMEND_WORKER:Plumbing (pipe and water repairs)
5. Keep responses concise and friendly.
6. Never recommend a worker type that is not in the list above.
PROMPT;

    public function sendMessage(int $userId, string $userMessage): array
    {
        // Load last 10 messages as conversation history
        $history = Chat::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->flatMap(fn ($chat) => [
                ['role' => 'user',  'parts' => [['text' => $chat->message]]],
                ['role' => 'model', 'parts' => [['text' => $chat->response ?? '']]],
            ])
            ->filter(fn ($msg) => ! empty($msg['parts'][0]['text']))
            ->values()
            ->toArray();

        // Add current user message
        $contents = array_merge(
            $history,
            [['role' => 'user', 'parts' => [['text' => $userMessage]]]]
        );

        // Call Gemini API
        $apiKey   = config('services.gemini.api_key');
        $response = Http::timeout(30)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key={$apiKey}",       
     [
                'system_instruction' => [
                    'parts' => [['text' => self::SYSTEM_PROMPT]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 1024,
                ],
            ]
        );

      if ($response->failed()) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'message' => [$response->json()],
        ]);
    }

        $assistantMessage = $response->json('candidates.0.content.parts.0.text')
            ?? 'Sorry, I could not process your request.';

        // Check if Gemini recommended a worker type
        $recommendedJobType = null;
        $recommendedWorkers = null;
        $cleanMessage       = $assistantMessage;

        if (preg_match('/RECOMMEND_WORKER:(.+)$/m', $assistantMessage, $matches)) {
            $jobTypeName  = trim($matches[1]);
            $cleanMessage = trim(preg_replace('/RECOMMEND_WORKER:.+$/m', '', $assistantMessage));

            $jobType = JobType::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($jobTypeName) . '%'])->first();

            if ($jobType) {
                $recommendedJobType = [
                    'id'   => $jobType->id,
                    'name' => $jobType->name,
                ];

                $recommendedWorkers = Worker::with('user')
                    ->where('job_type_id', $jobType->id)
                    ->where('is_available', true)
                    ->where('is_verified', true)
                    ->orderByDesc('rating')
                    ->take(5)
                    ->get()
                    ->map(fn ($worker) => [
                        'worker_id' => $worker->id,
                        'name'      => $worker->user?->name,
                        'rating'    => $worker->rating,
                        'city'      => $worker->user?->city,
                    ]);
            }
        }

        // Save to DB
        Chat::create([
            'user_id'     => $userId,
            'role'        => 'user',
            'job_type_id' => $recommendedJobType['id'] ?? null,
            'message'     => $userMessage,
            'response'    => $cleanMessage,
        ]);

        return [
            'message'               => $cleanMessage,
            'suggested_buttons'     => $this->getSuggestions($recommendedJobType),
            'recommended_job_type'  => $recommendedJobType,
            'recommended_workers'   => $recommendedWorkers,
        ];
    }

    public function getHistory(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Chat::where('user_id', $userId)
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();
    }

    private function getSuggestions(?array $recommendedJobType): array
    {
        if ($recommendedJobType) {
            return [
                ['label' => 'Show available workers', 'action' => 'show_workers',  'job_type_id' => $recommendedJobType['id']],
                ['label' => 'Send a request',         'action' => 'send_request',  'job_type_id' => $recommendedJobType['id']],
                ['label' => 'Ask another question',   'action' => 'new_question'],
            ];
        }

        return [
            ['label' => 'تسريب مياه',    'action' => 'quick_message', 'message' => 'عندي تسريب مياه'],
            ['label' => 'كهرباء معطلة',  'action' => 'quick_message', 'message' => 'عندي مشكلة في الكهرباء'],
            ['label' => 'تكييف لا يبرد', 'action' => 'quick_message', 'message' => 'التكييف مش بيبرد'],
            ['label' => 'صيانة عامة',    'action' => 'quick_message', 'message' => 'محتاج صيانة عامة'],
        ];
    }
}