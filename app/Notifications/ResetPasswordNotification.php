<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de recuperación de contraseña en español.
 * Con MAIL_MAILER=log el mensaje se escribe en storage/logs/laravel.log y NO se envía.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablecer contraseña — '.config('seguridad_escolar.sistema.nombre'))
            ->greeting('Hola, '.$notifiable->name)
            ->line('Recibimos una solicitud para restablecer la contraseña de su cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace vence en {$minutes} minutos.")
            ->line('Si usted no solicitó el cambio, ignore este mensaje: su contraseña actual seguirá funcionando.')
            ->salutation(config('seguridad_escolar.institucion.nombre'));
    }
}
