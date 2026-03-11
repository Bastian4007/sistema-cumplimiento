<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_type_requirement_templates', function (Blueprint $table) {
            $table->dropUnique('atr_unique');
        });

        $duplicateGroups = DB::table('asset_type_requirement_templates')
            ->select(
                'asset_type_id',
                'requirement_template_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('asset_type_id', 'requirement_template_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table('asset_type_requirement_templates')
                ->where('asset_type_id', $group->asset_type_id)
                ->where('requirement_template_id', $group->requirement_template_id)
                ->orderBy('id')
                ->get();

            $keep = $rows->first();

            if (! $keep) {
                continue;
            }

            $dropIds = $rows->slice(1)->pluck('id')->all();

            $appliesToRequirements = $rows->contains(
                fn ($row) => (bool) $row->applies_to_requirements
            );

            $appliesToObligations = $rows->contains(
                fn ($row) => (bool) $row->applies_to_obligations
            );

            $requirementType = $rows
                ->pluck('requirement_type')
                ->first(fn ($value) => ! is_null($value) && $value !== '');

            $defaultDays = $rows->max('default_days');
            $sortOrder = $rows->min('sort_order');

            DB::table('asset_type_requirement_templates')
                ->where('id', $keep->id)
                ->update([
                    'applies_to_requirements' => $appliesToRequirements,
                    'applies_to_obligations' => $appliesToObligations,
                    'requirement_type' => $requirementType,
                    'default_days' => $defaultDays,
                    'sort_order' => $sortOrder,
                    'updated_at' => now(),
                ]);

            if (! empty($dropIds)) {
                DB::table('asset_type_requirement_templates')
                    ->whereIn('id', $dropIds)
                    ->delete();
            }
        }

        Schema::table('asset_type_requirement_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('asset_type_requirement_templates', function (Blueprint $table) {
            $table->unique(
                ['asset_type_id', 'requirement_template_id'],
                'atr_global_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('asset_type_requirement_templates', function (Blueprint $table) {
            $table->dropUnique('atr_global_unique');
        });

        Schema::table('asset_type_requirement_templates', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('asset_type_requirement_templates', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'asset_type_id', 'requirement_template_id'],
                'atr_unique'
            );
        });
    }
};