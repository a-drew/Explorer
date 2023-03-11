<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Application;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Hit implements Arrayable
{
    protected array $hit;

    public function __construct(array $hit)
    {
        $this->hit = $hit;
    }

    public function toArray(): array
    {
        $document = $this->document();
        $highlight = $this->highlight();

        return [
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
