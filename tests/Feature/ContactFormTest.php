<?php

namespace Tests\Feature;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'subject' => 'general',
            'message' => 'Hello, this is a test message.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_valid_submission_is_stored_and_redirects_with_success(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'jane@example.com',
            'subject' => 'general',
        ]);
        Mail::assertSent(NewContactMessage::class);
    }

    public function test_missing_required_fields_fail_validation(): void
    {
        $response = $this->post(route('contact.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message', 'consent']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_consent_must_be_accepted(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload(['consent' => '0']));

        $response->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_invalid_subject_is_rejected(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload(['subject' => 'not-a-real-option']));

        $response->assertSessionHasErrors('subject');
    }

    public function test_message_still_saves_even_if_mail_delivery_throws(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP down'));

        $response = $this->post(route('contact.store'), $this->validPayload());

        // A mail outage must never lose the submission or break the form.
        $response->assertRedirect(route('contact'));
        $this->assertDatabaseHas('contact_messages', ['email' => 'jane@example.com']);
    }

    public function test_submissions_beyond_the_rate_limit_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $this->validPayload(['email' => "jane{$i}@example.com"]))
                ->assertRedirect(route('contact'));
        }

        $this->post(route('contact.store'), $this->validPayload(['email' => 'jane6@example.com']))
            ->assertStatus(429);
    }
}
