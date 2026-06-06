<?php

use App\Mail\ContactMessage;
use App\Settings\EmailSettings;
use Illuminate\Support\Facades\Mail;

it('renders the contact page', function (): void {
    $this->get('/contact')->assertOk();
});

it('mails a contact submission to the site inbox with reply-to the visitor', function (): void {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => 'Jane Visitor',
        'email' => 'jane@visitor.test',
        'message' => 'Hello there',
    ])->assertRedirect()->assertSessionHas('success');

    Mail::assertSent(ContactMessage::class, function (ContactMessage $mail) {
        return $mail->hasTo(app(EmailSettings::class)->from_address)
            && $mail->hasReplyTo('jane@visitor.test')
            && $mail->senderName === 'Jane Visitor';
    });
});

it('validates the contact form', function (): void {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'message' => '',
    ])->assertSessionHasErrors(['name', 'email', 'message']);

    Mail::assertNothingSent();
});
