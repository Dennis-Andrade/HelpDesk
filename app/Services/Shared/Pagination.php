<?php
namespace App\Services\Shared;

final class Pagination
{
    public int $page;
    public int $perPage;
    public int $total;

    private function __construct(int $page, int $perPage, int $total)
    {
        $this->page    = max(1, $page);
        $this->perPage = max(1, $perPage);
        $this->total   = max(0, $total);
    }

    public static function fromRequest(array $in, int $defaultPage = 1, int $defaultPerPage = 15, int $total = 0): self
    {
        $p  = isset($in['page'])    ? (int)$in['page']    : $defaultPage;
        $pp = isset($in['perPage']) ? (int)$in['perPage'] : $defaultPerPage;
        return new self($p, $pp, $total);
    }

    public function pages(): int
    {
        return (int)max(1, ceil($this->total / $this->perPage));
    }
}
