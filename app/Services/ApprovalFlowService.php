<?php

namespace App\Services;

use App\Models\JobPosition;
use App\Models\Regulation;
use App\Models\RegulationApproval;
use App\Models\User;
use App\Notifications\ApprovalFlowMemberNotification;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\RegulationApprovedNotification;
use App\Notifications\RegulationReadyToResubmitNotification;
use App\Notifications\RegulationRejectedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApprovalFlowService
{
    /**
     * Flujos definidos por nivel de impacto.
     * Cada paso es un array de posición-slug => lógica.
     * 'requires_all' => true  = AND: todos los usuarios de TODOS los puestos deben aprobar.
     * 'requires_all' => false = OR:  cualquier usuario de cualquier puesto en el paso basta.
     */
    /**
     * Flujos bottom-up: cada paso espera a que el anterior se complete.
     * Jerarquía: lider (1) → jefe (2) → gerente (3) → direccion (4)
     */
    private const FLOWS = [
        'bajo' => [
            1 => ['requires_all' => true, 'positions' => ['lider']],
        ],
        'medio' => [
            1 => ['requires_all' => true,  'positions' => ['lider']],
            2 => ['requires_all' => false, 'positions' => ['jefe', 'gerente']],
        ],
        'medio_alto' => [
            1 => ['requires_all' => true, 'positions' => ['lider']],
            2 => ['requires_all' => true, 'positions' => ['gerente']],
            3 => ['requires_all' => true, 'positions' => ['direccion']],
        ],
        'alto' => [
            1 => ['requires_all' => true, 'positions' => ['lider']],
            2 => ['requires_all' => true, 'positions' => ['jefe']],
            3 => ['requires_all' => true, 'positions' => ['gerente']],
            4 => ['requires_all' => true, 'positions' => ['direccion']],
        ],
    ];

    public static function getFlowSteps(string $level): array
    {
        return self::FLOWS[$level] ?? [];
    }

    public static function getAllFlows(): array
    {
        return self::FLOWS;
    }

    /**
     * Inicializa el flujo de aprobación al crear un reglamento.
     */
    public function initFlow(Regulation $regulation, array $userMap = []): void
    {
        DB::transaction(function () use ($regulation, $userMap) {
            $regulation->approvals()->delete();
            $this->createStepRecords($regulation, 1, $userMap);
        });

        $this->notifyPendingApprovers($regulation, 1);
        $this->notifyFutureFlowMembers($regulation, $userMap);
    }

    /**
     * Procesa la decisión de un aprobador (approve / reject).
     */
    public function processApproval(RegulationApproval $approval, string $status, ?string $comments = null): void
    {
        DB::transaction(function () use ($approval, $status, $comments) {
            $approval->update([
                'status'     => $status,
                'comments'   => $comments,
                'decided_at' => now(),
            ]);

            $regulation = $approval->regulation;
            $userMap = $regulation->flow_user_map ?? [];

            if ($status === 'rejected') {
                $regulation->pendingApprovals()->update(['status' => 'cancelled']);
                $regulation->update(['approval_status' => 'rejected']);
                $this->notifyCreator($regulation, 'rejected', $comments, $approval->user);
                return;
            }

            if (! $approval->requires_all) {
                $regulation->approvalStep($approval->step_number)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }

            if ($this->isStepComplete($regulation, $approval->step_number)) {
                $flow = self::FLOWS[$regulation->impact_level] ?? [];
                $nextStep = $approval->step_number + 1;

                if (isset($flow[$nextStep])) {
                    $this->createStepRecords($regulation, $nextStep, $userMap);
                    $regulation->update(['approval_status' => 'pending_authorization']);
                    $this->notifyPendingApprovers($regulation, $nextStep);
                } else {
                    $regulation->update(['approval_status' => 'approved']);

                    // La vigencia real (1 año) se asigna hasta este momento — la "fecha de
                    // elaboración" capturada en el wizard ya no determina el vencimiento.
                    $regulation->currentVersion?->update([
                        'valid_until' => now()->addYear(),
                    ]);

                    $this->notifyCreator($regulation, 'approved');
                }
            }
        });
    }

    /**
     * Reinicia el flujo desde el paso 1 (usado tras un rechazo).
     */
    public function resubmit(Regulation $regulation): void
    {
        $userMap = $regulation->flow_user_map ?? [];

        DB::transaction(function () use ($regulation, $userMap) {
            $regulation->approvals()->delete();
            $regulation->update(['approval_status' => 'pending_review']);
            $this->createStepRecords($regulation, 1, $userMap);
        });

        $this->notifyPendingApprovers($regulation, 1);
        $this->notifyFutureFlowMembers($regulation, $userMap);
    }

    /**
     * Devuelve true si el usuario tiene algún registro pending en el reglamento.
     */
    public function userHasPendingApproval(Regulation $regulation, int $userId): bool
    {
        return $regulation->pendingApprovals()->where('user_id', $userId)->exists();
    }

    /**
     * Devuelve el registro pending del usuario en el reglamento, o null.
     */
    public function getPendingApprovalForUser(Regulation $regulation, int $userId): ?RegulationApproval
    {
        return $regulation->pendingApprovals()->where('user_id', $userId)->first();
    }

    /**
     * Si el reglamento estaba rechazado y se acaba de guardar una corrección (desde el wizard de
     * IA o desde el editor libre), avisa a los admins con acceso a la empresa que ya pueden
     * reiniciar el flujo — solo un admin puede hacerlo (RegulationApprovalController::resubmit()),
     * y sin este aviso nadie se entera de que ya se corrigió hasta que alguien entra a revisar el
     * reglamento por su cuenta. No hace nada si el reglamento no estaba rechazado.
     */
    public function notifyIfCorrectedAfterRejection(Regulation $regulation): void
    {
        if ($regulation->approval_status !== 'rejected') {
            return;
        }

        $admins = User::where('group_id', $regulation->group_id)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'superadmin']))
            ->get()
            ->filter(fn (User $u) => $u->canAccessCompany($regulation->company));

        foreach ($admins as $admin) {
            $admin->notify(new RegulationReadyToResubmitNotification($regulation));
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function createStepRecords(Regulation $regulation, int $step, array $userMap = []): void
    {
        $flow = self::FLOWS[$regulation->impact_level] ?? [];
        $stepDef = $flow[$step] ?? null;

        if (! $stepDef) {
            return;
        }

        foreach ($this->resolveStepUsers($regulation, $stepDef, $userMap) as $user) {
            RegulationApproval::create([
                'regulation_id'   => $regulation->id,
                'step_number'     => $step,
                'job_position_id' => $user->pivot_job_position_id,
                'user_id'         => $user->id,
                'requires_all'    => $stepDef['requires_all'],
                'status'          => 'pending',
            ]);
        }
    }

    /**
     * Resuelve qué usuarios corresponden a un paso del flujo: si el admin asignó usuarios
     * específicos por puesto (flow_user_map) se usan solo esos, si no, todos los usuarios
     * activos de cada puesto involucrado. Compartido entre createStepRecords() (que sí crea el
     * registro de aprobación) y notifyFutureFlowMembers() (que solo necesita saber a quién avisar
     * de un paso que todavía no existe como registro en BD).
     *
     * @return Collection<int, User>  cada User trae "pivot_job_position_id" con el puesto resuelto.
     */
    private function resolveStepUsers(Regulation $regulation, array $stepDef, array $userMap): Collection
    {
        $users = collect();

        foreach ($stepDef['positions'] as $slug) {
            $position = JobPosition::where('group_id', $regulation->group_id)
                ->where('slug', $slug)
                ->first();

            if (! $position) {
                continue;
            }

            // Si se asignaron usuarios específicos para este puesto, usar solo esos.
            if (isset($userMap[$slug])) {
                $userIds = is_array($userMap[$slug])
                    ? array_map('intval', $userMap[$slug])
                    : [(int) $userMap[$slug]];

                foreach (User::whereIn('id', array_filter($userIds))->get() as $user) {
                    $user->pivot_job_position_id = $position->id;
                    $users->push($user);
                }
                continue;
            }

            // Si no, todos los usuarios asignados al puesto.
            foreach ($position->users as $user) {
                $user->pivot_job_position_id = $position->id;
                $users->push($user);
            }
        }

        return $users->unique('id');
    }

    private function isStepComplete(Regulation $regulation, int $step): bool
    {
        return ! $regulation->approvalStep($step)
            ->where('status', 'pending')
            ->exists();
    }

    private function notifyPendingApprovers(Regulation $regulation, int $step): void
    {
        $users = $regulation->approvalStep($step)
            ->where('status', 'pending')
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id');

        foreach ($users as $user) {
            $user->notify(new ApprovalRequestedNotification($regulation));
        }
    }

    /**
     * Avisa, de forma solo informativa, a quienes participan en pasos POSTERIORES al primero —
     * el primer paso ya recibe el correo accionable de notifyPendingApprovers(). Se manda una
     * sola vez, al iniciar o reiniciar el flujo completo (no en cada avance de paso: ahí el paso
     * que se desbloquea ya recibe el correo accionable, no el informativo).
     */
    private function notifyFutureFlowMembers(Regulation $regulation, array $userMap): void
    {
        $flow = self::FLOWS[$regulation->impact_level] ?? [];

        // Quien ya participa en el paso 1 recibió el correo accionable — si esa misma persona
        // también está asignada a un puesto de un paso posterior (ej. es líder Y gerente), no
        // tiene caso mandarle además el informativo de "tu voto se pedirá más adelante".
        $notifiedIds = isset($flow[1])
            ? $this->resolveStepUsers($regulation, $flow[1], $userMap)->pluck('id')->all()
            : [];

        foreach ($flow as $step => $stepDef) {
            if ($step === 1) {
                continue;
            }

            foreach ($this->resolveStepUsers($regulation, $stepDef, $userMap) as $user) {
                if (in_array($user->id, $notifiedIds, true)) {
                    continue;
                }

                $notifiedIds[] = $user->id;
                $user->notify(new ApprovalFlowMemberNotification($regulation, $step));
            }
        }
    }

    private function notifyCreator(Regulation $regulation, string $outcome, ?string $comments = null, $rejectedBy = null): void
    {
        $creator = $regulation->creator;

        if (! $creator) {
            return;
        }

        match ($outcome) {
            'approved' => $creator->notify(new RegulationApprovedNotification($regulation)),
            'rejected' => $creator->notify(new RegulationRejectedNotification($regulation, $comments ?? '', $rejectedBy)),
            default => null,
        };
    }
}
