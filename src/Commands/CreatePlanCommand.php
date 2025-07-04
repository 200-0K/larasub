<?php

namespace Err0r\Larasub\Commands;

use Err0r\Larasub\Core\Models\Plan;
use Illuminate\Console\Command;

class CreatePlanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larasub:create-plan
                            {name : The name of the plan}
                            {price : The price of the plan}
                            {--slug= : The slug for the plan (auto-generated if not provided)}
                            {--currency=USD : The currency for the plan}
                            {--period=month : The billing period (day, week, month, year)}
                            {--period-count=1 : The number of periods}
                            {--description= : Description of the plan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new subscription plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = [
            'name' => $this->argument('name'),
            'price' => $this->argument('price'),
            'slug' => $this->option('slug') ?: \Illuminate\Support\Str::slug($this->argument('name')),
            'currency' => $this->option('currency'),
            'period' => $this->option('period'),
            'period_count' => $this->option('period-count'),
            'description' => $this->option('description'),
        ];

        try {
            $plan = Plan::create($data);

            $this->info("✅ Plan created successfully!");
            $this->line("ID: {$plan->id}");
            $this->line("Name: {$plan->name}");
            $this->line("Slug: {$plan->slug}");
            $this->line("Price: {$plan->currency} {$plan->price}");
            $this->line("Period: {$plan->period_count} {$plan->period}(s)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to create plan: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}