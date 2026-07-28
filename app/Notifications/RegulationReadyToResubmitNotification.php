<?php

namespace App\Notifications;

use App\Models\Regulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un reglamento rechazado se corrigió (se guardó una edición mientras approval_status seguía en
 * "rejected") — solo un admin puede reiniciar el flujo (RegulationApprovalController::resubmit()),
 * así que se les avisa a todos los admins con acceso a la empresa del reglamento, en vez de
 * esperar a que alguno entre por su cuenta a revisar si ya se corrigió.
 */
class RegulationReadyToResubmitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Regulation $regulation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Documento corregido, listo para reenviar a aprobación: ' . $this->regulation->name)
            ->view('emails.processes.regulation-ready-to-resubmit', [
                'notifiable' => $notifiable,
                'regulation' => $this->regulation,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'regulation_ready_to_resubmit',
            'regulation_id'   => $this->regulation->id,
            'regulation_name' => $this->regulation->name,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
