<?php
/**
 * includes/header.php
 * Incluir al inicio de cada página autenticada.
 * Requiere que $pageTitle y $activeNav estén definidos.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
$rolUsuario    = htmlspecialchars($_SESSION['rol']    ?? 'Sin rol');
$inicialesUser = strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1) . substr($_SESSION['apellido'] ?? '', 0, 1));

// Contar alertas nuevas para el badge
require_once __DIR__ . '/../conexion.php';
$resAlertas = $conn->query("SELECT COUNT(*) FROM Alertas WHERE estado = 'nueva'");
$alertasNuevas = $resAlertas ? (int)$resAlertas->fetch_row()[0] : 0;

$resInc = $conn->query("SELECT COUNT(*) FROM Incidentes WHERE estado IN ('abierto','en_progreso')");
$incAbiertos = $resInc ? (int)$resInc->fetch_row()[0] : 0;

$pageTitle  = $pageTitle  ?? 'SIEM';
$activeNav  = $activeNav  ?? '';
$breadcrumb = $breadcrumb ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pageTitle ?> — SIEM Académico</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">
        <div class="logo-icon">S</div>
        <div>
          <div class="logo-text">SIEM</div>
          <div class="logo-sub">Académico</div>
        </div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">Principal</div>

      <a href="<?= BASE_URL ?>/dashboard.php"
         class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-gauge"></i></span>
        Dashboard
      </a>

      <div class="nav-section">Gestión</div>

      <a href="<?= BASE_URL ?>/usuarios/index.php"
         class="nav-item <?= $activeNav === 'usuarios' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
        Usuarios
      </a>

      <a href="<?= BASE_URL ?>/equipos/index.php"
         class="nav-item <?= $activeNav === 'equipos' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-server"></i></span>
        Equipos
      </a>

      <div class="nav-section">Seguridad</div>

      <a href="<?= BASE_URL ?>/logs/index.php"
         class="nav-item <?= $activeNav === 'logs' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-terminal"></i></span>
        Logs
      </a>

      <a href="<?= BASE_URL ?>/alertas/index.php"
         class="nav-item <?= $activeNav === 'alertas' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-bell"></i></span>
        Alertas
        <?php if ($alertasNuevas > 0): ?>
          <span class="nav-badge"><?= $alertasNuevas ?></span>
        <?php endif; ?>
      </a>

      <a href="<?= BASE_URL ?>/incidentes/index.php"
         class="nav-item <?= $activeNav === 'incidentes' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        Incidentes
        <?php if ($incAbiertos > 0): ?>
          <span class="nav-badge"><?= $incAbiertos ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section">Análisis</div>

      <a href="<?= BASE_URL ?>/reportes/index.php"
         class="nav-item <?= $activeNav === 'reportes' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
        Reportes
      </a>

      <?php if (strtolower($_SESSION['rol'] ?? '') === 'administrador'): ?>
      <div class="nav-section">Administración</div>
      <a href="<?= BASE_URL ?>/admin/index.php"
         class="nav-item <?= $activeNav === 'admin' ? 'active' : '' ?>"
         style="<?= $activeNav === 'admin' ? '' : 'color:var(--amber)' ?>">
        <span class="nav-icon"><i class="fa-solid fa-shield-halved"></i></span>
        Panel Admin
        <span style="margin-left:auto;font-size:9px;letter-spacing:1px;text-transform:uppercase;
               background:var(--amber-dim);color:var(--amber);padding:2px 6px;border-radius:4px;
               font-family:var(--font-display);font-weight:700">ADMIN</span>
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-widget">
        <div class="user-avatar"><?= $inicialesUser ?></div>
        <div class="user-info">
          <div class="user-name"><?= $nombreUsuario ?></div>
          <div class="user-role"><?= $rolUsuario ?></div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout" title="Cerrar sesión">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- ── Main ── -->
  <div class="main-content">
    <header class="topbar">
      <button id="menu-toggle" class="btn-ghost btn-siem d-lg-none">
        <i class="fa-solid fa-bars"></i>
      </button>

      <div>
        <div class="topbar-title"><?= $pageTitle ?></div>
        <?php if (!empty($breadcrumb)): ?>
        <div class="topbar-breadcrumb">
          <span>SIEM</span>
          <?php foreach ($breadcrumb as $bc): ?>
            <span class="sep">›</span>
            <span><?= htmlspecialchars($bc) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="topbar-actions ms-auto">
        <span class="badge-siem badge-success">
          <span class="pulse-dot" style="margin-right:4px"></span>
          <?= date('d/m/Y H:i') ?>
        </span>
      </div>
    </header>

    <div class="page-body fade-in">
