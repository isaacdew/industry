<?php

namespace Isaacdew\Industry;

use Illuminate\Support\Collection;

trait WithIndustry
{
    protected Industry $industry;

    /**
     * Create a new factory instance.
     *
     * @param  int|null  $count
     * @param  \UnitEnum|string|null  $connection
     */
    public function __construct(
        $count = null,
        ?Collection $states = null,
        ?Collection $has = null,
        ?Collection $for = null,
        ?Collection $afterMaking = null,
        ?Collection $afterCreating = null,
        $connection = null,
        ?Collection $recycle = null,
        ?bool $expandRelationships = null,
        array $excludeRelationships = [],
    ) {
        $this->industry = new Industry($this);

        $states ??= collect([]);

        $states->push(function (array $attributes) {
            return $this->industry->buildSchema($attributes)
                ->getState();
        });

        parent::__construct(
            $count,
            $states,
            $has,
            $for,
            $afterMaking,
            $afterCreating,
            $connection,
            $recycle,
            $expandRelationships,
            $excludeRelationships
        );
    }

    public function getPrompt()
    {
        return $this->prompt;
    }
}
