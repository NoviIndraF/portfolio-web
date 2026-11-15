<?php

namespace Tests\Feature;

use App\Ai\Agents\PortfolioAssistant;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Services\PortfolioContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config([
            'ai.providers.gemini.key' => 'test-key',
            'ai.providers.gemini-backup.key' => 'test-key',
            'ai.providers.groq.key' => null,
        ]);

        Setting::create(['site_title' => 'Test Portfolio', 'toggle_3d' => true]);
        Profile::create([
            'name' => 'Novi Indra Fanani',
            'tagline' => 'Mobile App Developer',
            'email' => 'novi@example.com',
        ]);
        Project::create([
            'title' => 'Codelytic', 'slug' => 'codelytic', 'short_description' => 'Online learning app.',
            'type' => 'mobile+web', 'scene_type' => 'learning', 'is_published' => true,
        ]);
        Project::create([
            'title' => 'Secret Draft', 'slug' => 'draft', 'short_description' => 'Not public yet.',
            'type' => 'mobile', 'scene_type' => 'none', 'is_published' => false,
        ]);
    }

    public function test_context_contains_only_public_content(): void
    {
        $text = PortfolioContext::build();

        $this->assertStringContainsString('Novi Indra Fanani', $text);
        $this->assertStringContainsString('Codelytic', $text);
        $this->assertStringContainsString(route('projects.show', 'codelytic'), $text);
        $this->assertStringNotContainsString('Secret Draft', $text);
    }

    public function test_chat_returns_the_ai_answer_and_remembers_the_conversation(): void
    {
        PortfolioAssistant::fake(['Novi built Codelytic.', 'It uses Flutter.']);

        // The answer is streamed as plain text; reading it also runs the code that remembers the conversation.
        $first = $this->postJson('/chat', ['message' => 'What did Novi build?'])->assertOk();
        $this->assertSame('Novi built Codelytic.', $first->streamedContent());

        $second = $this->postJson('/chat', ['message' => 'Which tech?'])->assertOk();
        $this->assertSame('It uses Flutter.', $second->streamedContent());

        PortfolioAssistant::assertPrompted('What did Novi build?');
        // The second question is sent together with the first question and answer.
        PortfolioAssistant::assertPrompted(fn (AgentPrompt $prompt) => $prompt->prompt === 'Which tech?'
            && count($prompt->agent->history) === 2);
    }

    public function test_message_is_validated(): void
    {
        PortfolioAssistant::fake();

        $this->postJson('/chat', ['message' => ''])->assertUnprocessable();
        $this->postJson('/chat', ['message' => str_repeat('a', 501)])->assertUnprocessable();

        PortfolioAssistant::assertNeverPrompted();
    }

    public function test_chat_is_offline_without_an_api_key(): void
    {
        config(['ai.providers.gemini.key' => null, 'ai.providers.gemini-backup.key' => null]);
        PortfolioAssistant::fake();

        $this->postJson('/chat', ['message' => 'Hello'])->assertStatus(503);

        PortfolioAssistant::assertNeverPrompted();
    }

    public function test_daily_limit_stops_the_chat(): void
    {
        config(['chatbot.daily_limit' => 1]);
        PortfolioAssistant::fake(['First answer.']);

        $this->postJson('/chat', ['message' => 'One'])->assertOk()->streamedContent();
        $this->postJson('/chat', ['message' => 'Two'])->assertStatus(429);

        PortfolioAssistant::assertPromptedTimes(1);
    }

    public function test_visitor_rate_limit(): void
    {
        config(['chatbot.per_hour' => 2]);
        PortfolioAssistant::fake(['A', 'B', 'C']);

        $this->postJson('/chat', ['message' => 'One'])->assertOk();
        $this->postJson('/chat', ['message' => 'Two'])->assertOk();
        $this->postJson('/chat', ['message' => 'Three'])->assertStatus(429)->assertJsonStructure(['reply']);
    }

    public function test_visitor_daily_limit_applies_below_the_hourly_limit(): void
    {
        config(['chatbot.per_hour' => 10, 'chatbot.per_day' => 1]);
        PortfolioAssistant::fake(['A', 'B']);

        $this->postJson('/chat', ['message' => 'One'])->assertOk();
        $this->postJson('/chat', ['message' => 'Two'])->assertStatus(429)->assertJsonStructure(['reply']);
    }

    public function test_job_description_mode_takes_a_long_text_and_compares_it_with_the_portfolio(): void
    {
        PortfolioAssistant::fake(['Strong fit.', 'Short answer.']);
        $job = str_repeat('Flutter developer with Clean Architecture. ', 60); // far above the normal 500 characters

        $reply = $this->postJson('/chat', ['message' => $job, 'mode' => 'jd'])->assertOk();
        $this->assertSame('Strong fit.', $reply->streamedContent());

        PortfolioAssistant::assertPrompted(fn (AgentPrompt $prompt) => $prompt->agent->jobMatch
            && str_contains($prompt->agent->instructions(), 'JOB DESCRIPTION MODE'));

        // The next question gets only the start of the job description as history, and no job-description rules.
        $this->postJson('/chat', ['message' => 'Which project fits best?'])->assertOk()->streamedContent();

        PortfolioAssistant::assertPrompted(fn (AgentPrompt $prompt) => $prompt->prompt === 'Which project fits best?'
            && strlen($prompt->agent->history[0]['content']) < 600
            && ! str_contains($prompt->agent->instructions(), 'JOB DESCRIPTION MODE'));
    }

    public function test_a_long_text_needs_job_description_mode(): void
    {
        PortfolioAssistant::fake();

        $this->postJson('/chat', ['message' => str_repeat('a', 501)])->assertUnprocessable();
        $this->postJson('/chat', ['message' => str_repeat('a', 4001), 'mode' => 'jd'])->assertUnprocessable();

        PortfolioAssistant::assertNeverPrompted();
    }

    public function test_widget_is_shown_on_the_site(): void
    {
        $this->get('/')->assertOk()->assertSee('data-chat', false)->assertSee('Ask about me');
    }
}
