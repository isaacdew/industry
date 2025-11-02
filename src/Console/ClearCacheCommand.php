<?php

namespace Isaacdew\Industry\Console;

use Illuminate\Console\Command;
use Isaacdew\Industry\CacheManager;
use function Laravel\Prompts\select;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'industry:clear-cache {--all : Clear all cached data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the Industry cache.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cacheManager = (new CacheManager());

        if ($this->option('all')) {
            $cacheManager->clearCache();

            $this->info('All cached data has been cleared.');
            return;
        }

        $factores = $cacheManager->getConnection()
            ->table('cache_meta')
            ->distinct()
            ->pluck('factory')
            ->toArray();

        if (empty($factores)) {
            $this->info('No cached data found.');
            return;
        }

        $factory = select(
            label: 'Select the factory whose cache you want to clear',
            options: $factores
        );

        $cacheManager->getConnection()
            ->table('cache_meta')
            ->where('factory', $factory)
            ->delete();

        $this->info("Cache for factory '{$factory}' has been cleared.");
    }
}
