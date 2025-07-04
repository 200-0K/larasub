<?php

namespace Err0r\Larasub\Core\Commands;

use Err0r\Larasub\Core\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreatePlanCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'larasub:create-plan 
                            {name : The name of the plan}
                            {price : The price of the plan}
                            {--period=month : The billing period (day/week/month/year)}
                            {--period-count=1 : The number of periods}
                            {--currency=USD : The currency code}
                            {--description= : Plan description}
                            {--active : Whether the plan should be active}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new subscription plan';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = Str::slug($name);

        // Check if plan already exists
        if (Plan::where('slug', $slug)->exists()) {
            $this->error("A plan with slug '{$slug}' already exists!");
            return Command::FAILURE;
        }

        $plan = Plan::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $this->option('description'),
            'price' => (float) $this->argument('price'),
            'currency' => $this->option('currency'),
            'period' => $this->option('period'),
            'period_count' => (int) $this->option('period-count'),
            'is_active' => $this->option('active') ?? true,
        ]);

        $this->info("Plan '{$plan->name}' created successfully!");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $plan->id],
                ['Name', $plan->name],
                ['Slug', $plan->slug],
                ['Price', $plan->getFormattedPrice()],
                ['Period', $plan->getHumanReadablePeriod()],
                ['Active', $plan->is_active ? 'Yes' : 'No'],
            ]
        );

        return Command::SUCCESS;
    }
}