<?php

namespace Colame\Onboarding\Database\Seeders;

use Colame\Onboarding\Models\OnboardingConfiguration;
use Illuminate\Database\Seeder;

class OnboardingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed onboarding configuration steps
        $steps = [
            [
                'key' => 'account_setup',
                'step_identifier' => 'account',
                'title' => 'Account Setup',
                'description' => 'Create your account and set up basic information',
                'order' => 1,
                'is_required' => true,
                'is_active' => true,
                'validation_rules' => [
                    'firstName' => 'required|string|min:2|max:100',
                    'lastName' => 'required|string|min:2|max:100',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:8|confirmed',
                    'phone' => 'required|string|max:20',
                ],
            ],
            [
                'key' => 'business_setup',
                'step_identifier' => 'business',
                'title' => 'Business Information',
                'description' => 'Tell us about your restaurant business',
                'order' => 2,
                'is_required' => true,
                'is_active' => true,
                'validation_rules' => [
                    'businessName' => 'required|string|max:255',
                    'legalName' => 'nullable|string|max:255',
                    'taxId' => 'required|string|max:50',
                    'businessType' => 'required|in:restaurant,franchise,chain,food_truck,catering',
                ],
            ],
            [
                'key' => 'location_setup',
                'step_identifier' => 'location',
                'title' => 'Location Details',
                'description' => 'Set up your primary restaurant location',
                'order' => 3,
                'is_required' => true,
                'is_active' => true,
                'validation_rules' => [
                    'name' => 'required|string|max:255',
                    'type' => 'required|in:restaurant,kitchen,warehouse,central_kitchen',
                    'address' => 'required|string|max:255',
                    'city' => 'required|string|max:100',
                    'phone' => 'required|string|max:50',
                ],
            ],
            [
                'key' => 'configuration_setup',
                'step_identifier' => 'configuration',
                'title' => 'Configuration',
                'description' => 'Customize your preferences and settings',
                'order' => 4,
                'is_required' => true,
                'is_active' => true,
                'validation_rules' => [
                    'dateFormat' => 'required|in:d/m/Y,m/d/Y,Y-m-d',
                    'timeFormat' => 'required|in:H:i,h:i A,h:i a',
                    'language' => 'required|in:es,en',
                    'currency' => 'required|string|max:5',
                    'timezone' => 'required|string|max:50',
                ],
            ],
        ];
        
        foreach ($steps as $step) {
            OnboardingConfiguration::updateOrCreate(
                ['key' => $step['key']],
                $step
            );
        }
    }
}