<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use function Config\db;

abstract class BaseRepository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }
}
