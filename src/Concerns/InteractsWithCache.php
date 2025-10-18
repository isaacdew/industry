<?php

namespace Isaacdew\Industry\Concerns;

use Isaacdew\Industry\CacheManager;

trait InteractsWithCache
{
    protected ?CacheManager $cache = null;

    protected function getCache(): CacheManager
    {
        if (! $this->cache) {
            $this->cache = new CacheManager($this->config['cache'] ?? []);
        }

        return $this->cache;
    }

    public function useCache(bool $bool = true): static
    {
        $this->config['cache']['enabled'] = $bool;

        return $this;
    }
}
