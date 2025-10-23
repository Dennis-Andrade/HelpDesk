<?php
$role = $_SESSION['auth']['role'] ?? null;

$menuSections = [
    'comercial' => [
        'title' => 'Comercial',
        'items' => [
            ['href' => '/comercial/dashboard', 'label' => 'Dashboard'],
            ['href' => '/comercial/entidades', 'label' => 'Entidades'],
            ['href' => '/comercial/contactos', 'label' => 'Agenda de Contactos'],
            ['href' => '/comercial/eventos', 'label' => 'Seguimiento'],
            ['href' => '/comercial/incidencias', 'label' => 'Incidencias'],
        ],
    ],
    'general' => [
        'title' => 'Herramientas',
        'items' => [
            ['href' => '/calendario', 'label' => 'Calendario unificado'],
        ],
    ],
    'contabilidad' => [
        'title' => 'Contabilidad',
        'items' => [
            ['href' => '/contabilidad/dashboard', 'label' => 'Dashboard'],
            ['href' => '/contabilidad/entidades', 'label' => 'Entidades'],
            ['href' => '/contabilidad/contratos', 'label' => 'Contratos digitales'],
            ['href' => '/contabilidad/seguimiento', 'label' => 'Seguimiento'],
            ['href' => '/contabilidad/tickets', 'label' => 'Tickets'],
            ['href' => '/contabilidad/inventario', 'label' => 'Inventario de equipos'],
            ['href' => '/contabilidad/facturacion', 'label' => 'Datos de facturación'],
            ['href' => '/contabilidad/switch', 'label' => 'Switch'],
        ],
    ],
];

$visibilityMap = [
    'comercial'     => ['general', 'comercial'],
    'contabilidad'  => ['general', 'contabilidad'],
    'administrador' => array_keys($menuSections),
];

$visibleSections = $visibilityMap[$role ?? ''] ?? array_keys($menuSections);
if (empty($visibleSections)) {
    $visibleSections = array_keys($menuSections);
}
?>
<aside class="sidebar">
  <img src="/img/logoblanco.png" alt="Logo">
  <br></br>
  <div class="brand">
    <span>Helpdesk</span>
  </div>
  <nav>
    <?php foreach ($visibleSections as $sectionKey): ?>
      <?php if (!isset($menuSections[$sectionKey])) { continue; } ?>
      <?php $section = $menuSections[$sectionKey]; ?>
      <div class="nav-group"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php foreach ($section['items'] as $navItem): ?>
        <a href="<?= htmlspecialchars($navItem['href'], ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
          <?= htmlspecialchars($navItem['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="nav-group">Sesión</div>
    <?php if (!empty($_SESSION['auth'])): ?>
      <div class="nav-user">Hola, <?= htmlspecialchars((string)($_SESSION['auth']['name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></div>
      <div class="btnlogout">
        <a href="/logout" class="nav-item">Salir</a>
      </div>
    <?php else: ?>
      <a href="/login" class="nav-item">Ingresar</a>
    <?php endif; ?>
  </nav>
</aside>
