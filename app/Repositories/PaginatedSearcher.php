<?php
namespace App\Repositories;

interface PaginatedSearcher {
    /** @return array{items: array<int,array>, total:int} */
    public function search(string $q, int $offset, int $limit): array;
}
