<?php
/**
 * admin/acceso_denegado.php
 * Página mostrada cuando un usuario sin rol Administrador
 * intenta acceder al panel de administración.
 */
define('BASE_URL', 'http://localhost/siem');
require_once __DIR__ . '/../config.php';

// Si no hay sesión, directamente al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
$rolUsuario    = htmlspecialchars($_SESSION['rol']    ?? 'Sin rol');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso denegado — SIEM Académico</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <style>
    .denied-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-base);
      position: relative;
      overflow: hidden;
    }
    .denied-bg-grid {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(var(--border) 1px, transparent 1px),
        linear-gradient(90deg, var(--border) 1px, transparent 1px);
      background-size: 40px 40px;
      opacity: .3;
    }
    .denied-card {
      position: relative; z-index: 1;
      background: var(--bg-card);
      border: 1px solid var(--red);
      border-radius: 16px;
      padding: 48px 52px;
      width: 480px;
      max-width: calc(100vw - 32px);
      text-align: center;
      box-shadow: 0 0 60px rgba(255,71,87,.12), 0 24px 80px rgba(0,0,0,.5);
    }
    .denied-icon {
      width: 72px; height: 72px;
      background: var(--red-dim);
      border: 1px solid rgba(255,71,87,.3);
      border-radius: 50%;
      display: inline-flex;
      align-items: center; justify-content: center;
      font-size: 28px;
      color: var(--red);
      margin-bottom: 20px;
    }
    .denied-code {
      font-family: var(--font-mono);
      font-size: 11px;
      letter-spacing: 2px;
      color: var(--red);
      text-transform: uppercase;
      margin-bottom: 8px;
      opacity: .7;
    }
    .denied-title {
      font-family: var(--font-display);
      font-size: 24px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 10px;
    }
    .denied-desc {
      font-size: 13px;
      color: var(--text-secondary);
      line-height: 1.7;
      margin-bottom: 28px;
    }
    .denied-user-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 6px 14px;
      font-size: 12px;
      color: var(--text-secondary);
      margin-bottom: 28px;
    }
    .denied-user-badge .dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--amber);
    }
    .denied-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }
  </style>
</head>
<body>
<div class="denied-page">
  <div class="denied-bg-grid"></div>

  <div class="denied-card fade-in">
    <div class="denied-icon">
      <i class="fa-solid fa-shield-halved"></i>
    </div>

    <div class="denied-code">Error 403 — Acceso restringido</div>
    <div class="denied-title">Panel de Administración</div>
    <div class="denied-desc">
      No tienes permisos para acceder a esta sección.<br>
      Esta área está reservada exclusivamente para usuarios con rol <strong style="color:var(--accent)">Administrador</strong>.
    </div>

    <div class="denied-user-badge">
      <span class="dot"></span>
      Sesión activa como: <strong><?= $nombreUsuario ?></strong>
      &nbsp;·&nbsp; Rol: <strong><?= $rolUsuario ?></strong>
    </div>

    <div class="denied-actions">
      <a href="<?= BASE_URL ?>/dashboard.php" class="btn-siem btn-primary">
        <i class="fa-solid fa-gauge"></i> Ir al Dashboard
      </a>
      <a href="<?= BASE_URL ?>/logout.php" class="btn-siem btn-ghost">
        <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/scripts.js"></script>
</body>
</html>
