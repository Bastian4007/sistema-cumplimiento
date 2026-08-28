<?php

namespace App\Notifications;

use App\Models\Regulation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un operativo que no es responsable de un reglamento (y por lo tanto no puede editarlo) pidió
 * acceso — se avisa a los admins con acceso al módulo de Procesos para que lo agreguen como
 * responsable desde "Info básica" si corresponde.
 */
class RegulationAccessRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Regulation $regulation,
        public readonly User $requester,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Solicitud de acceso a reglamento: ' . $this->regulation->name)
            ->view('emails.processes.regulation-access-requested', [
                'notifiable' => $notifiable,
                'regulation' => $this->regulation,
                'requester'  => $this->requester,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'regulation_access_requested',
            'regulation_id'   => $this->regulation->id,
            'regulation_name' => $this->regulation->name,
            'requester_id'    => $this->requester->id,
            'requester_name'  => $this->requester->name,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
