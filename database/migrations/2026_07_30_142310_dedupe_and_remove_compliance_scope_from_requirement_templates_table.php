<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dedupeTemplates();

        foreach ([
            'requirement_templates_unique',
            'requirement_templates_unique_name_asset_scope',
            'requirement_templates_name_asset_type_scope_unique',
        ] as $index) {
            if (Schema::hasIndex('requirement_templates', $index)) {
                Schema::table('requirement_templates', fn (Blueprint $t) => $t->dropUnique($index));
            }
        }

        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->dropColumn('compliance_scope');
        });

        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->unique(
                ['name', 'asset_type_id', 'category'],
                'requirement_templates_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->dropUnique('requirement_templates_unique');

            $table->string('compliance_scope')->default('project')->after('description');
        });

        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->unique(
                ['name', 'asset_type_id', 'compliance_scope', 'category'],
                'requirement_templates_unique'
            );
        });
    }

    /**
     * Documentos "CN y OP" quedaron duplicados como dos filas (una por scope)
     * con el mismo (asset_type_id, name, category). Al quitar compliance_scope
     * del índice único hay que dejar una sola fila por grupo — la de menor id —
     * reasignando cualquier asset_requirements que apuntara a las descartadas.
     */
    private function dedupeTemplates(): void
    {
        $rows = DB::table('requirement_templates')
            ->select('id', 'asset_type_id', 'name', 'category')
            ->orderBy('id')
            ->get();

        $groups = $rows->groupBy(fn ($row) => $row->asset_type_id . '|' . $row->name . '|' . $row->category);

        foreach ($groups as $group) {
            if ($group->count() <= 1) {
                continue;
            }

            $ids = $group->pluck('id')->all();
            $keeperId = array_shift($ids);

            DB::table('asset_requirements')
                ->whereIn('requirement_template_id', $ids)
                ->update(['requirement_template_id' => $keeperId]);

            DB::table('requirement_templates')->whereIn('id', $ids)->delete();
        }
    }
};
