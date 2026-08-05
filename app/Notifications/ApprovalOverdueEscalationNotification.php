<?php

namespace App\Notifications;

use App\Models\Regulation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un aprobador lleva 21 días (3 recordatorios) sin decidir — se escala a los admins con acceso al
 * módulo de Procesos (mismo filtro que ApprovalFlowService::notifyIfCorrectedAfterRejection: no a
 * todos los admins del portal, solo a quienes de verdad pueden actuar sobre este flujo) para que
 * puedan dar seguimiento manual, ya que a partir de aquí el aprobador deja de recibir recordatorios.
 */
class ApprovalOverdueEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Regulation $regulation,
        public readonly User $pendingUser,
        public readonly int $daysOverdue,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Aprobación sin resolver hace {$this->daysOverdue} días: {$this->regulation->name}")
            ->view('emails.processes.approval-overdue-escalation', [
                'notifiable'   => $notifiable,
                'regulation'   => $this->regulation,
                'pendingUser'  => $this->pendingUser,
                'daysOverdue'  => $this->daysOverdue,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'approval_overdue_escalation',
            'regulation_id'   => $this->regulation->id,
            'regulation_name' => $this->regulation->name,
            'pending_user_id' => $this->pendingUser->id,
            'pending_user_name' => $this->pendingUser->name,
            'days_overdue'    => $this->daysOverdue,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
