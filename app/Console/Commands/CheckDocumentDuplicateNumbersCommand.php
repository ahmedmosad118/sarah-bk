<?php

namespace App\Console\Commands;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDocumentDuplicateNumbersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:check-duplicate-numbers
                            {--tenant= : Specific tenant slug or ID to check}';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['estimations:check-duplicate-numbers'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan tenant databases for duplicate document numbers across measurements, scopes, boqs, estimations, and quotations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 SARH ERP — Checking Document Numbers for Duplicates...');

        TenantDatabaseManager::switchToCentral();

        $tenantOption = $this->option('tenant');
        $query = Tenant::query();

        if ($tenantOption) {
            $query->where('slug', $tenantOption)->orWhere('id', $tenantOption);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('⚠️ No matching tenants found.');

            return self::SUCCESS;
        }

        $tablesToCheck = [
            'estimations' => 'estimation_number',
            'quotations' => 'quotation_number',
            'boqs' => 'boq_number',
            'scopes' => 'scope_number',
            'measurements' => 'measurement_number',
        ];

        $totalDuplicates = 0;
        $findings = [];

        foreach ($tenants as $tenant) {
            $this->line('<fg=cyan>--------------------------------------------------</>');
            $this->line("<fg=yellow>🏢 Tenant:</> <fg=white;options=bold>{$tenant->name}</> (<fg=green>{$tenant->slug}</>) [DB: <fg=magenta>{$tenant->database_name}</>]");

            try {
                TenantDatabaseManager::switchToTenant($tenant);

                foreach ($tablesToCheck as $table => $column) {
                    if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    $dupes = DB::table($table)
                        ->select($column, DB::raw('COUNT(*) as occurrences'), DB::raw('GROUP_CONCAT(opportunity_id) as opportunity_ids'), DB::raw('GROUP_CONCAT(id) as record_ids'))
                        ->groupBy($column)
                        ->having('occurrences', '>', 1)
                        ->get();

                    if ($dupes->isNotEmpty()) {
                        foreach ($dupes as $dupe) {
                            $totalDuplicates++;
                            $findings[] = [
                                'tenant_id' => $tenant->id,
                                'tenant_slug' => $tenant->slug,
                                'table' => $table,
                                'column' => $column,
                                'number' => $dupe->{$column},
                                'occurrences' => $dupe->occurrences,
                                'opportunity_ids' => $dupe->opportunity_ids,
                                'record_ids' => $dupe->record_ids,
                            ];

                            $this->error("  ❌ Duplicate in [{$table}.{$column}]: '{$dupe->{$column}}' (Count: {$dupe->occurrences}, Opp IDs: {$dupe->opportunity_ids}, Record IDs: {$dupe->record_ids})");
                        }
                    } else {
                        $this->line("  ✓ {$table}.{$column}: Clean (no duplicates)");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("  ⚠️ Error inspecting tenant {$tenant->slug}: {$e->getMessage()}");
            } finally {
                TenantDatabaseManager::switchToCentral();
            }
        }

        $this->line('<fg=cyan>==================================================</>');
        if ($totalDuplicates > 0) {
            $this->error("⚠️ Total duplicate document numbers found across tenants: {$totalDuplicates}");
            $this->table(
                ['Tenant Slug', 'Table', 'Column', 'Duplicate Value', 'Count', 'Opp IDs', 'Record IDs'],
                collect($findings)->map(fn ($f) => [
                    $f['tenant_slug'],
                    $f['table'],
                    $f['column'],
                    $f['number'],
                    $f['occurrences'],
                    $f['opportunity_ids'],
                    $f['record_ids'],
                ])->toArray()
            );
            $this->warn('NOTE: No tenant data was modified or removed.');

            return self::FAILURE;
        }

        $this->info('✅ All tenant databases are completely clean! Zero duplicate document numbers found.');

        return self::SUCCESS;
    }
}
