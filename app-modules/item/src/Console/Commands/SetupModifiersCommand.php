<?php

namespace Colame\Item\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SetupModifiersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modifiers:setup
                            {--fresh : Drop and recreate modifier tables before seeding}
                            {--seed-only : Only run the seeder without migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up comprehensive modifiers for the restaurant system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================');
        $this->info('   Modifier System Setup');
        $this->info('====================================');
        $this->newLine();

        try {
            if ($this->option('seed-only')) {
                $this->runSeeder();
            } else {
                // Check if we need to run migrations
                if ($this->option('fresh') || !$this->tablesExist()) {
                    $this->runMigrations();
                } else {
                    $this->info('✓ Modifier tables already exist');
                }

                // Ask to run seeder
                if ($this->confirm('Do you want to seed the modifier data?', true)) {
                    if ($this->hasExistingData() && !$this->option('fresh')) {
                        $this->warn('⚠ Warning: Existing modifier data detected!');
                        if (!$this->confirm('This will add to existing data. Continue?', false)) {
                            $this->info('Seeding cancelled.');
                            return Command::SUCCESS;
                        }
                    }
                    $this->runSeeder();
                }
            }

            $this->newLine();
            $this->info('====================================');
            $this->info('   Setup Complete!');
            $this->info('====================================');
            $this->displaySummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Setup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Check if modifier tables exist
     */
    protected function tablesExist(): bool
    {
        $tables = ['modifier_groups', 'item_modifiers', 'modifier_group_categories', 'modifier_rules', 'modifier_dependencies'];

        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if there's existing modifier data
     */
    protected function hasExistingData(): bool
    {
        if (!$this->tablesExist()) {
            return false;
        }

        return DB::table('modifier_groups')->exists() || DB::table('item_modifiers')->exists();
    }

    /**
     * Run migrations
     */
    protected function runMigrations(): void
    {
        $this->info('Running modifier migrations...');

        if ($this->option('fresh')) {
            // Drop tables in reverse order to avoid foreign key constraints
            $this->info('Dropping existing modifier tables...');

            $tables = [
                'modifier_dependencies',
                'modifier_rules',
                'item_modifier_groups',
                'item_modifiers',
                'modifier_group_categories',
                'modifier_groups'
            ];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::statement('DROP TABLE IF EXISTS ' . $table . ' CASCADE');
                }
            }
        }

        // Run migrations
        $this->call('migrate', [
            '--path' => 'app-modules/item/database/migrations',
            '--force' => true,
        ]);

        $this->info('✓ Migrations completed successfully');
    }

    /**
     * Run the modifier seeder
     */
    protected function runSeeder(): void
    {
        $this->info('Seeding modifier data...');

        // Show progress bar for better UX
        $this->output->progressStart(20);

        Artisan::call('db:seed', [
            '--class' => 'Colame\\Item\\Database\\Seeders\\ModifierSeeder',
            '--force' => true,
        ]);

        $this->output->progressFinish();
        $this->info('✓ Modifier data seeded successfully');
    }

    /**
     * Display summary of what was created
     */
    protected function displaySummary(): void
    {
        if (!$this->tablesExist()) {
            return;
        }

        $groupCount = DB::table('modifier_groups')->count();
        $modifierCount = DB::table('item_modifiers')->count();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Modifier Groups', $groupCount],
                ['Individual Modifiers', $modifierCount],
            ]
        );

        $this->newLine();
        $this->info('Available Modifier Groups:');
        $this->newLine();

        $groups = DB::table('modifier_groups')
            ->select('name', 'selection_type', 'category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(10)
            ->get();

        foreach ($groups as $group) {
            $this->line(sprintf(
                "  • %s (%s)%s",
                $group->name,
                $group->selection_type,
                $group->category ? ' - ' . $group->category : ''
            ));
        }

        if ($groupCount > 10) {
            $this->line("  ... and " . ($groupCount - 10) . " more");
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Assign modifier groups to items in the admin panel');
        $this->line('  2. Customize prices and options as needed');
        $this->line('  3. Set up modifier rules for complex dependencies');

        $this->newLine();
        $this->comment('Run "php artisan modifiers:setup --help" to see available options');
    }
}