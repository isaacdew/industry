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

        /**
         * Move this to a industry() method that is used to get the industry instance
         */
        $this->industry = new Industry($this, config('industry'), $count);
        
        if ($states) {
            $useTestValues = app()->runningUnitTests();

            $states->push(function (array $attributes) use ($useTestValues) {
                if ($useTestValues && ! $this->industry->getForceGeneration()) {
                    return array_map(function ($value) {
                        if ($value instanceof IndustryDefinition) {
                            return $value->getTestValue();
                        }
    
                        return $value;
                    }, $attributes);
                }
    
                return $this->industry
                    ->buildSchema($attributes)
                    ->getState();
            });
        }

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

    public function configureIndustry(Industry $industry)
    {
        return $this;
    }

    public function tapIndustry(callable $callback): static
    {
        $callback($this->industry);

        return $this;
    }
}
