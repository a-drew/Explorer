<?php

namespace JeroenG\Explorer\Infrastructure\Scout;

use Illuminate\Database\Eloquent\Model;
use JeroenG\Explorer\Application\Paginator;
use JeroenG\Explorer\Domain\Aggregations\AggregationSyntaxInterface;
use JeroenG\Explorer\Domain\Query\QueryProperties\QueryProperty;

class Builder extends \Laravel\Scout\Builder
{
    public array $must;

    public array $should;

    public array $filter;

    public array $fields;

    public array $compound;

    public array $aggregations;

    public array $queryProperties;

    public function __construct($model, $query, $callback = null, $softDelete = false)
    {
        parent::__construct($model, $query, $callback, $softDelete);

        // set initial index so we can track joins
        if (method_exists($model, 'searchableAs')) {
            $this->index = $model->searchableAs();
        }
    }

    public function join($model): self
    {
        if (!$model instanceof Model) {
            $model = new $model;
        }

        if (method_exists($model, 'searchableAs')) {
            $index = $model->searchableAs();
            $this->index .= ',' . $index;
        }
        return $this;
    }

    public function joinMany($models): self
    {
        foreach ($models as $model) {
            $this->join($model);
        }
        return $this;
    }

    function must($must): self
    {
        $this->must[] = $must;
        return $this;
    }

    function should($should): self
    {
        $this->should[] = $should;
        return $this;
    }

    public function filter($filter): self
    {
        $this->filter[] = $filter;
        return $this;
    }

    public function field(string $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function newCompound($compound): self
    {
        $this->compound = $compound;
        return $this;
    }

    public function aggregation(string $name, AggregationSyntaxInterface $aggregation): self
    {
        $this->aggregations[$name] = $aggregation;
        return $this;
    }

    public function property(QueryProperty $queryProperty): self
    {
        $this->queryProperties[] = $queryProperty;
        return $this;
    }
}
