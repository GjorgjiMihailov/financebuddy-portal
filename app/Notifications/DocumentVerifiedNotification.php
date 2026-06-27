<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentVerifiedNotification extends Notification
{
    public function __construct(private readonly Document $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $doc      = $this->document;
        $company  = $doc->company?->name ?? '—';
        $verifier = $doc->verifier?->name ?? '—';
        $type     = $doc->type->label();
        $url      = url("/documents/{$doc->id}");

        return (new MailMessage)
            ->subject("Верификуван документ: {$doc->original_filename}")
            ->greeting('Здраво!')
            ->line("Вашиот документ е прегледан и верификуван.")
            ->line("**Документ:** {$doc->original_filename}")
            ->line("**Тип:** {$type}")
            ->line("**Компанија:** {$company}")
            ->line("**Верификуван од:** {$verifier}")
            ->action('Отвори документ', $url)
            ->salutation('FinanceBuddy портал');
    }
}
