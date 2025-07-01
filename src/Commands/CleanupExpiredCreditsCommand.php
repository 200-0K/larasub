<?php

namespace Err0r\Larasub\Commands;

use Err0r\Larasub\Models\SubscriptionFeatureCredit;
use Illuminate\Console\Command;

class CleanupExpiredCreditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larasub:cleanup-expired-credits
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--batch-size=1000 : Number of records to process at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired subscription feature credits';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('🧹 Starting cleanup of expired subscription feature credits...');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No records will be deleted');
        }

        /** @var class-string<SubscriptionFeatureCredit> */
        $creditModel = config('larasub.models.subscription_feature_credits');

        // Count expired credits
        $expiredCount = $creditModel::expired()->count();

        if ($expiredCount === 0) {
            $this->info('✅ No expired credits found. Nothing to clean up.');
            return self::SUCCESS;
        }

        $this->info("📊 Found {$expiredCount} expired credit records");

        if ($isDryRun) {
            $this->table(
                ['Feature', 'Subscription ID', 'Credits', 'Expired At', 'Reason'],
                $creditModel::expired()
                    ->with(['feature', 'subscription'])
                    ->limit(10)
                    ->get()
                    ->map(function ($credit) {
                        return [
                            $credit->feature->slug ?? 'N/A',
                            $credit->subscription_id,
                            $credit->credits,
                            $credit->expires_at?->format('Y-m-d H:i:s'),
                            $credit->reason ?? 'No reason',
                        ];
                    })
                    ->toArray()
            );

            if ($expiredCount > 10) {
                $this->info("... and " . ($expiredCount - 10) . " more records");
            }

            $this->info("💡 Run without --dry-run to actually delete these records");
            return self::SUCCESS;
        }

        if (!$this->confirm("Are you sure you want to delete {$expiredCount} expired credit records?")) {
            $this->info('❌ Operation cancelled');
            return self::SUCCESS;
        }

        $deleted = 0;
        $bar = $this->output->createProgressBar($expiredCount);
        $bar->start();

        // Process in batches to avoid memory issues
        do {
            $batch = $creditModel::expired()->limit($batchSize)->get();
            
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $credit) {
                $credit->delete();
                $deleted++;
                $bar->advance();
            }

        } while ($batch->count() === $batchSize);

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully deleted {$deleted} expired credit records");

        return self::SUCCESS;
    }
}