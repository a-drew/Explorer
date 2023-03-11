<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Application;

use Countable;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

class Results implements Countable
{
    private array $rawResults;

    public function __construct(array $rawResults)
    {
        $this->rawResults = $rawResults;
    }

    /**
     * @return Collection|Hit[]
     */
    public function hits(): Collection|array
    {
        return collect($this->rawResults['hits']['hits'])->map(
            fn($hit) => Container::getInstance()->makeWith(Hit::class, ['hit' => $hit])
        );
    }

    public function documents()
    {
        return $this->hits()->map(fn(Hit $hit) => $hit->document());
    }

    /** @return AggregationResult[] */
    public function aggregations(): array
    {
        if (!isset($this->rawResults['aggregations'])) {
            return [];
        }

        $aggregations = [];

        foreach ($this->rawResults['aggregations'] as $name => $rawAggregation) {
            $aggregations[] = new AggregationResult($name, $rawAggregation['buckets']);
        }

        return $aggregations;
    }

    public function count(): int
    {
        return $this->rawResults['hits']['total']['value'];
    }
}
