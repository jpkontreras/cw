<?php

declare(strict_types=1);

namespace Colame\Settings\Console\Commands;

use Colame\Settings\Contracts\SettingServiceInterface;
use Illuminate\Console\Command;

class InitializeSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:initialize 
                            {--reset : Reset existing settings to defaults}
                            {--seed : Run the setting seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize default settings for the application';

    public function __construct(
        private readonly SettingServiceInterface $settingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing application settings...');

        if ($this->option('reset')) {
            if ($this->confirm('This will reset all settings to their default values. Continue?')) {
                $this->settingService->resetAll();
                $this->info('All settings have been reset to defaults.');
            } else {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Initialize default settings
        $this->settingService->initializeDefaults();
        $this->info('Default settings initialized.');

        if ($this->option('seed')) {
            $this->call('db:seed', [
                '--class' => 'Colame\\Settings\\Database\\Seeders\\SettingSeeder',
            ]);
        }

        // Check if all required settings are configured
        if ($this->settingService->isFullyConfigured()) {
            $this->info('✓ All required settings are configured.');
        } else {
            $missing = $this->settingService->getMissingRequiredSettings();
            $this->warn("⚠ {$missing->count()} required settings are not configured:");
            
            foreach ($missing as $setting) {
                $this->line("  - {$setting->key}: {$setting->label}");
            }
            
            $this->info('');
            $this->info('Please configure these settings in the admin panel.');
        }

        return Command::SUCCESS;
    }
}