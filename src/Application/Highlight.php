<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Application;

use Illuminate\Support\Collection;

class Highlight
{

    private array $highlight;

    public function __construct(array $highlight)
    {
        $this->highlight = $highlight;
    }

    /**
     * @return Collection|string[]
     */
    public function snippets(string $field): Collection
    {
        return collect($this->highlight[$field] ?? []);
    }

    public function raw(): array
    {
        return $this->highlight;
    }
}
