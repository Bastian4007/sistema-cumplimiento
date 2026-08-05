<?php

namespace App\Notifications;

use App\Models\Regulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio periódico (cada 7 días, hasta 3 veces — ver NotifyPendingApprovalReminders) para
 * quien tiene una aprobación pendiente y todavía no ha decidido. $reminderNumber (1, 2 o 3) y
 * $daysOverdue se calculan en el comando a partir de cuándo el aprobador realmente quedó en
 * "pending" (no siempre coincide con la creación del reglamento — ver ApprovalFlowService).
 */
class ApprovalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Regulation $regulation,
        public readonly int $daysOverdue,
        public readonly int $reminderNumber,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Recordatorio ({$this->daysOverdue} días): aprobación pendiente — {$this->regulation->name}")
            ->view('emails.processes.approval-reminder', [
                'notifiable'      => $notifiable,
                'regulation'      => $this->regulation,
                'daysOverdue'     => $this->daysOverdue,
                'reminderNumber'  => $this->reminderNumber,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'approval_reminder',
            'regulation_id'   => $this->regulation->id,
            'regulation_name' => $this->regulation->name,
            'days_overdue'    => $this->daysOverdue,
            'reminder_number' => $this->reminderNumber,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
