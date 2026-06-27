<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadedNotification extends Notification
{
    public function __construct(private readonly Document $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $doc     = $this->document;
        $company = $doc->company?->name ?? '—';
        $uploader = $doc->uploader?->name ?? '—';
        $type    = $doc->type->label();
        $url     = url("/documents/{$doc->id}");

        return (new MailMessage)
            ->subject("Нов документ: {$doc->original_filename}")
            ->greeting('Здраво!')
            ->line("Качен е нов документ кој чека AI обработка.")
            ->line("**Документ:** {$doc->original_filename}")
            ->line("**Тип:** {$type}")
            ->line("**Компанија:** {$company}")
            ->line("**Качил:** {$uploader}")
            ->action('Отвори документ', $url)
            ->salutation('FinanceBuddy портал');
    }
}
