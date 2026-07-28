<?php

namespace App\Notifications;

use App\Models\Regulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso informativo (sin acción inmediata) para quienes forman parte de un paso POSTERIOR al
 * primero dentro del flujo de aprobación de un reglamento — a diferencia de
 * ApprovalRequestedNotification (que sí pide actuar ya), esta solo avisa que su voto se
 * necesitará más adelante, cuando el flujo llegue a su paso. Se manda una sola vez, al
 * iniciar/reiniciar el flujo completo (ApprovalFlowService::initFlow()/resubmit()).
 */
class ApprovalFlowMemberNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Regulation $regulation,
        public readonly int $step,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Formarás parte del flujo de aprobación: ' . $this->regulation->name)
            ->view('emails.processes.approval-flow-member', [
                'notifiable' => $notifiable,
                'regulation' => $this->regulation,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'approval_flow_member',
            'regulation_id'   => $this->regulation->id,
            'regulation_name' => $this->regulation->name,
            'step'            => $this->step,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
