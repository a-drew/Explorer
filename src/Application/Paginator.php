<?php declare(strict_types=1);

namespace JeroenG\Explorer\Application;

use Illuminate\Pagination\LengthAwarePaginator;
use JeroenG\Explorer\Application\Results;

/**
 * @mixin Results
 */
class Paginator extends LengthAwarePaginator
{
    protected Results $results;

    public function __construct(
        Results $results,
        int $perPage,
        ?int $currentPage = null,
        array $options = []
    ) {

        parent::__construct($results->hits(), $results->count(), $perPage, $currentPage, $options);

        $this->results = $results;
    }

    public function asModels(): self
    {
        $models = $this->models();
        return $this->setCollection($models);
    }

    public function asDocuments(): self
    {
        $documents = $this->documents();
        return $this->setCollection($documents);
    }

    public function asHits(): self
    {
        $hits = $this->hits();
        return $this->setCollection($hits);
    }

    /**
     * @{@inheritDoc}
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->getCollection(), $method)) {
            return $this->forwardCallTo($this->getCollection(), $method, $parameters);
        }

        return $this->forwardCallTo($this->results, $method, $parameters);
    }
}
