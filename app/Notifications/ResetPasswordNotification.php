<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $minutos = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->greeting('¡Hola!')
            ->line('Recibiste este correo porque se solicitó restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace para restablecer la contraseña vence en {$minutos} minutos.")
            ->line('Si no solicitaste este cambio, podés ignorar este correo.')
            ->salutation('Saludos, ' . config('app.name'));
    }
}
