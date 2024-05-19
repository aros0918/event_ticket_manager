<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;


class ForgotPasswordNotification extends Notification
{
    public function __construct($token)
    {
        $this->token = $token;
    }


    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $reset_link = route('password_reset_show', ['token' => $this->token]);
        return (new MailMessage)
            ->line(__('em.reset_password'))
            ->action(__('em.reset_password'), $reset_link);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'notification' => 'Forgot Password',
            'n_type' => 'forgot_password',
        ];

    }
}
