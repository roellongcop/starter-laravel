<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app notification stored on the database channel. `data` carries the
 * type, message and an optional deep link consumed by the bell + list UI.
 */
class AdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public NotificationType $type = NotificationType::Info,
        public ?string $link = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
