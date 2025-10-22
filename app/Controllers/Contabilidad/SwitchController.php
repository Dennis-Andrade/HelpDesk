<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Services\Shared\Breadcrumbs;
use function view;

final class SwitchController
{
    public function index(): void
    {
        view('contabilidad/switch/index', [
            'layout' => 'layout',
            'title'  => 'Switch',
            'crumbs' => Breadcrumbs::make([
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['label' => 'Switch'],
            ]),
        ]);
    }
}
