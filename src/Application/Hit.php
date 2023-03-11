<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Application;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationRepositoryInterface;

class Hit implements Arrayable
{
    protected array $hit;

    protected ?string $class = null;

    public function __construct(array $hit)
    {
        $this->hit = $hit;
    }

    public function toArray(): array
    {
        $model = $this->model();
        $document = $this->document();
        $highlight = $this->highlight();

        return [
            'model' => $model?->toArray(),
            'index_name' => $this->indexName(),
            'document' => $document->toArray(),
            'highlight' => $highlight?->raw(),
            'score' => $this->score(),
        ];
    }

    public function id(): string
    {
        return $this->hit['_id'];
    }

    public function indexName(): string
    {
        return $this->hit['_index'];
    }

    public function getIndexAliases(): array
    {
        return resolve(IndexAdapterInterface::class)->getAliases($this->indexName());
    }

    /**
     * Identify the result's matching model class using its index
     */
    public function modelClass(): ?string
    {
        if ($this->class !== null) {
            return $this->class;
        }

        $repository = resolve(IndexConfigurationRepositoryInterface::class);
        $indexes = [$this->indexName(), ...$this->getIndexAliases()];

        foreach ($indexes as $index) {
            try {
                $this->class = $repository->findForIndex($index)->getModel();
                break;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $this->class;
    }

    public function model(): ?Model
    {
        $class = $this->modelClass();
        return $class !== null ? $class::find($this->id()) : null;
    }

    public function score(): ?float
    {
        return $this->hit['_score'];
    }

    public function source($key = null)
    {
        return Arr::get($this->hit['_source'], $key);
    }

    public function document(): Document
    {
        return new Document($this->hit['_id'], $this->hit['_source'] ?? []);
    }

    public function highlight(): ?Highlight
    {
        return isset($this->hit['highlight']) ? new Highlight($this->hit['highlight']) : null;
    }

    public function innerHits(): Collection
    {
        $innerHits = $this->hit['inner_hits'] ?? [];

        return collect($innerHits)->map(static function (array $hits) {
            return collect($hits['hits']['hits'])->map(static function (array $hit) {
                return new self($hit);
            });
        });
    }

    public function raw(): array
    {
        return $this->hit;
    }

}
