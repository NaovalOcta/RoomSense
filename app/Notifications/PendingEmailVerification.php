<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingEmailVerification extends Notification
{
    public function __construct(private string $verificationUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi perubahan email RoomSense')
            ->line('Konfirmasi alamat email pengganti untuk akun RoomSense Anda.')
            ->action('Verifikasi email baru', $this->verificationUrl)
            ->line('Tautan berlaku selama 60 menit dan hanya dapat digunakan sekali. Email lama tetap aktif sampai verifikasi berhasil.')
            ->line('Jika Anda tidak meminta perubahan ini, abaikan email ini.');
    }
}
