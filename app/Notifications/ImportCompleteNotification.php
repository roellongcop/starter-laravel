<?php

namespace App\Notifications;

use App\Models\UserImport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompleteNotification extends Notification
{
    use Queueable;

    public function __construct(public UserImport $import) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your import finished')
            ->line("Import of {$this->import->resource}: {$this->import->success} succeeded, {$this->import->failed} failed (of {$this->import->total}).")
            ->action('View imports', url(route('imports.index')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->import->failed > 0 ? 'Warning' : 'Success',
            'message' => "Import complete: {$this->import->success}/{$this->import->total} users imported",
            'link' => route('imports.index', absolute: false),
        ];
    }
}
