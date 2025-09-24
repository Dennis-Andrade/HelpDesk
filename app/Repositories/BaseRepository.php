<?php
declare(strict_types=1);

namespace App\Repositories;
use App\Support\Db\DbAdapterInterface;
use App\Support\Db\PdoAdapter;
use function Config\db;

abstract class BaseRepository
{
    /** @var DbAdapterInterface */
    protected $db;

    public function __construct(?DbAdapterInterface $adapter = null)
    {
        $this->db = $adapter ?: new PdoAdapter(db());
    }
}
