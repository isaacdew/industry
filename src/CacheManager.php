<?php

namespace Isaacdew\Industry;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Prism\Prism\Contracts\Schema;

class CacheManager
{
    protected ?Connection $connection = null;

    public function __construct(protected array $config = ['enabled' => true, 'strategy' => 'recycle', 'lazy_load_until' => null])
    {
        $this->runMigrationsIfNotExists();

        if (! in_array($this->config['strategy'], ['recycle', 'lazy_load'])) {
            throw new \InvalidArgumentException('Invalid cache strategy: '.$this->config['strategy']);
        }

        if ($this->config['strategy'] === 'lazy_load' && ! is_null($this->config['lazy_load_until']) && ! is_int($this->config['lazy_load_until'])) {
            throw new \InvalidArgumentException('Invalid lazy_load_until value: '.$this->config['lazy_load_until']);
        }
    }

    public function get(string $factory, string $objectSignature, int $count, callable $requestCallback): array|bool
    {
        $connection = $this->getConnection();

        $metaId = $connection->table('cache_meta')
            ->where('object_signature', $objectSignature)
            ->value('id');

        if (! $metaId) {
            $data = $requestCallback($count);

            $this->store($factory, $objectSignature, $data);
        } else {
            $data = $connection->table('cache_data')
                ->where('meta_id', $metaId)
                ->limit($count)
                ->inRandomOrder()
                ->pluck('content')
                ->map(fn ($content) => json_decode($content, true))
                ->toArray();
        }

        $dataCount = count($data);

        // If we have enough cached data or the recycle strategy is on, return it
        if ($dataCount >= $count || $this->config['strategy'] === 'recycle') {
            return $data;
        }

        // If using lazy_load, ask for more data until we get enough
        $neededCount = $this->config['lazy_load_until']
            ? max(0, $this->config['lazy_load_until'] - $dataCount)
            : $count - $dataCount;

        if ($neededCount <= 0) {
            return $data;
        }

        $newData = [];
        while (count($newData) < $neededCount) {
            $newData = array_merge(
                $newData,
                $requestCallback($neededCount - count($newData))
            );
        }

        // Save the new data to the cache
        $this->store($factory, $objectSignature, $newData);

        return array_merge(
            $data,
            $newData,
        );
    }

    public function store(string $factory, string $objectSignature, array $contents): void
    {
        $connection = $this->getConnection();
        $timestamp = now();

        $metaId = $connection->table('cache_meta')->where([
            'factory' => $factory,
            'object_signature' => $objectSignature,
        ])->value('id');

        if (! $metaId) {
            // Delete any existing cache entries for this factory to avoid stale data
            $connection->table('cache_meta')->where('factory', $factory)->delete();

            $metaId = $connection->table('cache_meta')->insertGetId([
                'factory' => $factory,
                'object_signature' => $objectSignature,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $data = [];
        foreach ($contents as $content) {
            $data[] = [
                'meta_id' => $metaId,
                'content' => json_encode($content),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }
        $connection->table('cache_data')->insert($data);
    }

    public function objectSignature(string $model, string $prompt, Schema $schema): string
    {
        $payload = json_encode([
            'p' => $prompt,
            's' => $schema,
        ]);

        return $model.'.'.md5($payload);
    }

    public function getConnection(): Connection
    {
        if ($this->connection) {
            return $this->connection;
        }

        $factory = app(ConnectionFactory::class);

        $this->connection = $factory->make([
            'driver' => 'sqlite',
            'database' => config('industry.cache.database_path', database_path('industry_cache.sqlite')),
            'prefix' => '',
        ]);

        return $this->connection;
    }

    public function runMigrationsIfNotExists(): void
    {
        if (! file_exists(config('industry.cache.database_path', database_path('industry_cache.sqlite')))) {
            touch(config('industry.cache.database_path', database_path('industry_cache.sqlite')));
        }

        if ($this->getConnection()->getSchemaBuilder()->hasTable('cache_meta')) {
            return;
        }

        $this->getConnection()->getSchemaBuilder()->create('cache_meta', function ($table) {
            $table->id();
            $table->string('factory');
            $table->string('object_signature')->unique();
            $table->timestamps();
        });

        $this->getConnection()->getSchemaBuilder()->create('cache_data', function ($table) {
            $table->id();
            $table->foreignId('meta_id')
                ->references('id')
                ->on('cache_meta')
                ->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function clearCache(): void
    {
        $connection = $this->getConnection();
        $connection->table('cache_data')->delete();
        $connection->table('cache_meta')->delete();
    }
}
