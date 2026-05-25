<?php

 
session_start();
require_once "php/conexion.php";   
if (!isset($_SESSION['correo'])) {
    header("Location: login.php");
    exit;
}
 
$correoSesion = $_SESSION['correo'];
 

$sqlGuard = "SELECT u.id_usuario, u.nombre, u.apellido,
                    u.estado, r.nombre_rol, u.id_rol
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id_rol
             WHERE u.correo = '$correoSesion'
             LIMIT 1";
 
$resGuard   = mysqli_query($conexion, $sqlGuard);
$usuarioSesion = mysqli_fetch_assoc($resGuard);
 
// No existe en BD
if (!$usuarioSesion) {
    session_destroy();
    header("Location: login.php");
    exit;
}
 
// Cuenta suspendida o inactiva
if ($usuarioSesion['estado'] !== 'activo') {
    session_destroy();
    header("Location: login.php?msg=cuenta_suspendida");
    exit;
}
 
// No es Administrador → página de acceso denegado (inline)
if (strtolower($usuarioSesion['nombre_rol']) !== 'administrador') {
    mostrarAccesoDenegado($usuarioSesion);
    exit;
}
 
// Variables del administrador en sesión
$adminId     = (int)$usuarioSesion['id_usuario'];
$adminNombre = htmlspecialchars($usuarioSesion['nombre'] . ' ' . $usuarioSesion['apellido']);
$adminIniciales = strtoupper(
    substr($usuarioSesion['nombre'],   0, 1) .
    substr($usuarioSesion['apellido'], 0, 1)
);
 
// ============================================================
// ACCIONES POST
// ============================================================
$flashMsg  = '';
$flashTipo = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
 
    // ── Cambiar rol ─────────────────────────────────────────
    if ($accion === 'cambiar_rol') {
        $idObjetivo = (int)($_POST['id_usuario'] ?? 0);
        $nuevoRol   = (int)($_POST['id_rol']     ?? 0);
 
        if ($idObjetivo === $adminId) {
            $flashTipo = 'warning';
            $flashMsg  = 'No puedes cambiar tu propio rol desde aquí.';
        } elseif ($idObjetivo <= 0 || $nuevoRol <= 0) {
            $flashTipo = 'error';
            $flashMsg  = 'Datos inválidos.';
        } else {
            // Verificar que el rol existe
            $resRol = mysqli_query($conexion,
                "SELECT id_rol FROM roles WHERE id_rol = $nuevoRol LIMIT 1"
            );
            if (mysqli_num_rows($resRol) === 0) {
                $flashTipo = 'error';
                $flashMsg  = 'El rol seleccionado no existe.';
            } else {
                $stmtRol = mysqli_prepare($conexion,
                    "UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?"
                );
                mysqli_stmt_bind_param($stmtRol, 'ii', $nuevoRol, $idObjetivo);
                mysqli_stmt_execute($stmtRol);
                if (mysqli_stmt_affected_rows($stmtRol) > 0) {
                    $flashTipo = 'success';
                    $flashMsg  = 'Rol actualizado correctamente.';
                } else {
                    $flashTipo = 'warning';
                    $flashMsg  = 'Sin cambios (el usuario ya tenía ese rol).';
                }
                mysqli_stmt_close($stmtRol);
            }
        }
    }
 
    // ── Cambiar estado ───────────────────────────────────────
    if ($accion === 'cambiar_estado') {
        $idObjetivo  = (int)($_POST['id_usuario'] ?? 0);
        $nuevoEstado = trim($_POST['estado']       ?? '');
        $estadosOK   = ['activo', 'inactivo', 'suspendido'];
 
        if ($idObjetivo === $adminId) {
            $flashTipo = 'warning';
            $flashMsg  = 'No puedes cambiar tu propio estado.';
        } elseif ($idObjetivo <= 0 || !in_array($nuevoEstado, $estadosOK, true)) {
            $flashTipo = 'error';
            $flashMsg  = 'Datos inválidos.';
        } else {
            $stmtEst = mysqli_prepare($conexion,
                "UPDATE usuarios SET estado = ? WHERE id_usuario = ?"
            );
            mysqli_stmt_bind_param($stmtEst, 'si', $nuevoEstado, $idObjetivo);
            mysqli_stmt_execute($stmtEst);
            if (mysqli_stmt_affected_rows($stmtEst) > 0) {
                $flashTipo = 'success';
                $flashMsg  = 'Estado actualizado a "' . htmlspecialchars($nuevoEstado) . '".';
            } else {
                $flashTipo = 'warning';
                $flashMsg  = 'Sin cambios (el estado ya era el mismo).';
            }
            mysqli_stmt_close($stmtEst);
        }
    }
}
 
// ============================================================
// DATOS PARA LA VISTA
// ============================================================
 
// Lista de roles
$resRoles = mysqli_query($conexion,
    "SELECT id_rol, nombre_rol, descripcion FROM roles ORDER BY id_rol"
);
$roles = [];
while ($r = mysqli_fetch_assoc($resRoles)) $roles[] = $r;
 
// Filtros GET
$buscar    = trim($_GET['buscar']  ?? '');
$filtroRol = (int)($_GET['rol']   ?? 0);
$filtroEst = trim($_GET['estado'] ?? '');
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$limite    = 12;
$offset    = ($pagina - 1) * $limite;
 
// WHERE dinámico con prepared statement
$wherePartes = ["1=1"];
$tiposStr    = "";
$bindVals    = [];
 
if ($buscar !== '') {
    $wherePartes[] = "(u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ?)";
    $like = "%{$buscar}%";
    $tiposStr .= "sss";
    $bindVals[] = $like; $bindVals[] = $like; $bindVals[] = $like;
}
if ($filtroRol > 0) {
    $wherePartes[] = "u.id_rol = ?";
    $tiposStr .= "i"; $bindVals[] = $filtroRol;
}
if ($filtroEst !== '') {
    $wherePartes[] = "u.estado = ?";
    $tiposStr .= "s"; $bindVals[] = $filtroEst;
}
$whereStr = implode(' AND ', $wherePartes);
 
// Contar total
$sqlCount = "SELECT COUNT(*) FROM usuarios u WHERE $whereStr";
$stmtCnt  = mysqli_prepare($conexion, $sqlCount);
if (!empty($bindVals)) {
    mysqli_stmt_bind_param($stmtCnt, $tiposStr, ...$bindVals);
}
mysqli_stmt_execute($stmtCnt);
mysqli_stmt_bind_result($stmtCnt, $totalUsuariosFiltro);
mysqli_stmt_fetch($stmtCnt);
mysqli_stmt_close($stmtCnt);
 
// Listado paginado
$sqlLista = "
    SELECT u.id_usuario, u.nombre, u.apellido, u.correo,
           u.estado, u.fecha_registro, u.id_rol, r.nombre_rol,
           (SELECT COUNT(*) FROM historial_sesiones hs
            WHERE hs.id_usuario = u.id_usuario) AS total_sesiones,
           (SELECT COUNT(*) FROM incidentes i
            WHERE i.id_usuario = u.id_usuario) AS total_incidentes,
           (SELECT MAX(hs2.fecha_inicio) FROM historial_sesiones hs2
            WHERE hs2.id_usuario = u.id_usuario) AS ultimo_acceso
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id_rol
    WHERE $whereStr
    ORDER BY u.fecha_registro DESC
    LIMIT ? OFFSET ?
";
$stmtLista = mysqli_prepare($conexion, $sqlLista);
$tiposLista = $tiposStr . "ii";
$valsLista  = array_merge($bindVals, [$limite, $offset]);
mysqli_stmt_bind_param($stmtLista, $tiposLista, ...$valsLista);
mysqli_stmt_execute($stmtLista);
$resLista = mysqli_stmt_get_result($stmtLista);
$usuarios = [];
while ($u = mysqli_fetch_assoc($resLista)) $usuarios[] = $u;
mysqli_stmt_close($stmtLista);
 
// Estadísticas por rol
$resStats = mysqli_query($conexion, "
    SELECT r.nombre_rol,
           COUNT(u.id_usuario)              AS total,
           SUM(u.estado = 'activo')         AS activos,
           SUM(u.estado = 'inactivo')       AS inactivos,
           SUM(u.estado = 'suspendido')     AS suspendidos
    FROM roles r
    LEFT JOIN usuarios u ON r.id_rol = u.id_rol
    GROUP BY r.id_rol, r.nombre_rol
    ORDER BY total DESC
");
$statsRoles = [];
while ($s = mysqli_fetch_assoc($resStats)) $statsRoles[] = $s;
 
// Sesiones recientes
$resSes = mysqli_query($conexion, "
    SELECT hs.id_sesion,
           CONCAT(u.nombre,' ',u.apellido) AS usuario,
           r.nombre_rol, hs.ip_acceso,
           hs.fecha_inicio, hs.fecha_fin
    FROM historial_sesiones hs
    INNER JOIN usuarios u ON hs.id_usuario = u.id_usuario
    INNER JOIN roles    r ON u.id_rol      = r.id_rol
    ORDER BY hs.fecha_inicio DESC
    LIMIT 8
");
$sesiones = [];
while ($s = mysqli_fetch_assoc($resSes)) $sesiones[] = $s;
 
// Totales rápidos
$totalUsuarios = (int)mysqli_fetch_row(mysqli_query($conexion,
    "SELECT COUNT(*) FROM usuarios"))[0];
$totalActivos  = (int)mysqli_fetch_row(mysqli_query($conexion,
    "SELECT COUNT(*) FROM usuarios WHERE estado='activo'"))[0];
$totalInact    = (int)mysqli_fetch_row(mysqli_query($conexion,
    "SELECT COUNT(*) FROM usuarios WHERE estado='inactivo'"))[0];
$totalSuspend  = (int)mysqli_fetch_row(mysqli_query($conexion,
    "SELECT COUNT(*) FROM usuarios WHERE estado='suspendido'"))[0];
$totalRoles    = (int)mysqli_fetch_row(mysqli_query($conexion,
    "SELECT COUNT(*) FROM roles"))[0];
 
// URL base para paginador
$urlBase = '?' . http_build_query([
    'buscar' => $buscar, 'rol' => $filtroRol, 'estado' => $filtroEst
]);
 
// ============================================================
// FUNCIÓN: Página de acceso denegado (inline)
// ============================================================
function mostrarAccesoDenegado(array $u): void {
    $nombre = htmlspecialchars($u['nombre'] . ' ' . $u['apellido']);
    $rol    = htmlspecialchars($u['nombre_rol']);
    echo <<<HTML
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Acceso denegado</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@600;700&display=swap');
  :root{--bg:#0d0f14;--card:#181d28;--border:#252d3d;--red:#ff4757;--red-dim:rgba(255,71,87,.1);--accent:#00d4ff;--text:#e8ecf4;--muted:#8892a4}
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);font-family:'JetBrains Mono',monospace;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden}
  .grid-bg{position:fixed;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:40px 40px;opacity:.35;pointer-events:none}
  .card{position:relative;z-index:1;background:var(--card);border:1px solid var(--red);border-radius:16px;padding:48px 52px;width:460px;max-width:calc(100vw - 32px);text-align:center;box-shadow:0 0 60px rgba(255,71,87,.1),0 24px 80px rgba(0,0,0,.5)}
  .icon-wrap{width:72px;height:72px;background:var(--red-dim);border:1px solid rgba(255,71,87,.3);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px;color:var(--red);margin-bottom:20px}
  .code{font-size:11px;letter-spacing:2px;color:var(--red);text-transform:uppercase;margin-bottom:8px;opacity:.7}
  .title{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--text);margin-bottom:10px}
  .desc{font-size:13px;color:var(--muted);line-height:1.7;margin-bottom:24px}
  .chip{display:inline-flex;align-items:center;gap:8px;background:#1e2535;border:1px solid var(--border);border-radius:20px;padding:6px 14px;font-size:12px;color:var(--muted);margin-bottom:28px}
  .chip .dot{width:6px;height:6px;border-radius:50%;background:#ffb020}
  .btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
  .btn-p{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--accent);color:#0d0f14;border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none}
  .btn-g{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:transparent;color:var(--muted);border:1px solid var(--border);border-radius:8px;font-family:'Syne',sans-serif;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none}
  .btn-p:hover,.btn-g:hover{opacity:.8}
</style></head><body>
<div class="grid-bg"></div>
<div class="card">
  <div class="icon-wrap"><i class="fa-solid fa-shield-halved"></i></div>
  <div class="code">Error 403 — Acceso restringido</div>
  <div class="title">Panel de Administración</div>
  <div class="desc">No tienes permisos para acceder a esta sección.<br>
    Esta área es exclusiva para usuarios con rol <strong style="color:var(--accent)">Administrador</strong>.</div>
  <div class="chip"><span class="dot"></span>Sesión: <strong style="color:var(--text)">{$nombre}</strong>&nbsp;·&nbsp;Rol: <strong style="color:var(--text)">{$rol}</strong></div>
  <div class="btns">
    <a href="dashboard.php" class="btn-p"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="logout.php"    class="btn-g"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
HTML;
}
 
// ============================================================
// HTML — INICIO DE LA PÁGINA
// ============================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel de Administración — SIEM</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Syne:wght@400;500;600;700&display=swap" rel="stylesheet">
 
  <style>
  /* ── Variables ── */
  :root {
    --bg:        #0d0f14;
    --surface:   #131720;
    --card:      #181d28;
    --hover:     #1e2535;
    --border:    #252d3d;
    --border2:   #2e3a52;
    --accent:    #00d4ff;
    --acc-dim:   rgba(0,212,255,.12);
    --acc-glow:  rgba(0,212,255,.25);
    --green:     #00e5a0;
    --grn-dim:   rgba(0,229,160,.12);
    --amber:     #ffb020;
    --amb-dim:   rgba(255,176,32,.12);
    --red:       #ff4757;
    --red-dim:   rgba(255,71,87,.12);
    --purple:    #a78bfa;
    --pur-dim:   rgba(167,139,250,.12);
    --text:      #e8ecf4;
    --text2:     #8892a4;
    --muted:     #4a5568;
    --mono:      'JetBrains Mono', monospace;
    --display:   'Syne', sans-serif;
    --r:         8px;
    --rl:        12px;
    --sidebar:   230px;
    --topbar:    58px;
    --ease:      .18s ease;
  }
 
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: var(--mono);
    font-size: 13.5px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
  }
 
  a { color: var(--accent); text-decoration: none; }
  a:hover { opacity: .75; }
 
  /* ── Scrollbar ── */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: var(--bg); }
  ::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }
 
  /* ── Layout ── */
  .layout { display: flex; min-height: 100vh; }
 
  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar);
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    position: fixed; top:0; left:0; bottom:0; z-index:100;
    transition: transform var(--ease);
  }
 
  .sb-logo {
    padding: 18px 18px 14px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
  }
  .sb-logo-icon {
    width: 32px; height: 32px; background: var(--accent);
    border-radius: 6px; display: flex; align-items: center;
    justify-content: center; font-family: var(--display);
    font-size: 14px; font-weight: 800; color: var(--bg); flex-shrink: 0;
  }
  .sb-logo-text  { font-family: var(--display); font-size: 14px; font-weight: 700; }
  .sb-logo-sub   { font-size: 10px; color: var(--muted); letter-spacing: 1.5px; text-transform: uppercase; }
 
  .sb-nav { flex: 1; overflow-y: auto; padding: 10px 0; }
  .sb-section {
    padding: 14px 14px 4px; font-size: 10px; letter-spacing: 1.8px;
    text-transform: uppercase; color: var(--muted); font-family: var(--display);
  }
  .sb-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 14px; color: var(--text2); cursor: pointer;
    border-left: 2px solid transparent; font-size: 13px;
    transition: all var(--ease); text-decoration: none;
  }
  .sb-item:hover { color: var(--text); background: var(--hover); border-left-color: var(--border2); }
  .sb-item.active { color: var(--amber); background: var(--amb-dim); border-left-color: var(--amber); font-weight: 500; }
  .sb-item .ico { font-size: 14px; width: 17px; text-align: center; flex-shrink: 0; }
  .admin-pill {
    margin-left: auto; font-size: 9px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    background: var(--amb-dim); color: var(--amber);
    padding: 2px 6px; border-radius: 4px; font-family: var(--display);
  }
 
  .sb-footer {
    padding: 14px; border-top: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
  }
  .sb-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--amb-dim); border: 1px solid rgba(255,176,32,.3);
    display: flex; align-items: center; justify-content: center;
    font-family: var(--display); font-size: 11px; font-weight: 700;
    color: var(--amber); flex-shrink: 0;
  }
  .sb-uname { font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .sb-urole { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; }
  .btn-logout {
    margin-left: auto; width: 28px; height: 28px; background: none;
    border: 1px solid var(--border); border-radius: var(--r); color: var(--muted);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 13px; transition: all var(--ease); flex-shrink: 0;
  }
  .btn-logout:hover { color: var(--red); border-color: var(--red); }
 
  /* ── Main ── */
  .main { margin-left: var(--sidebar); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
 
  /* ── Topbar ── */
  .topbar {
    height: var(--topbar); background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; padding: 0 22px; gap: 14px;
    position: sticky; top: 0; z-index: 50;
  }
  .topbar-title { font-family: var(--display); font-size: 15px; font-weight: 600; }
  .topbar-bc    { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 5px; }
  .topbar-bc .sep { color: var(--border2); }
 
  /* ── Page body ── */
  .body { padding: 22px; flex: 1; }
 
  /* ── Banner admin ── */
  .admin-banner {
    display: flex; align-items: center; gap: 14px;
    background: var(--amb-dim);
    border: 1px solid rgba(255,176,32,.25);
    border-radius: var(--rl); padding: 13px 18px; margin-bottom: 20px;
  }
  .banner-icon {
    width: 40px; height: 40px; background: rgba(255,176,32,.12);
    border: 1px solid rgba(255,176,32,.3); border-radius: var(--r);
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; color: var(--amber); flex-shrink: 0;
  }
  .banner-title { font-family: var(--display); font-size: 13px; font-weight: 700; color: var(--amber); }
  .banner-sub   { font-size: 11px; color: var(--text2); }
 
  /* ── Stat cards ── */
  .stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    gap: 14px; margin-bottom: 22px;
  }
  .stat-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--rl); padding: 16px 18px;
    position: relative; overflow: hidden;
    transition: all var(--ease);
  }
  .stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; }
  .stat-card.c-accent::before { background: var(--accent); }
  .stat-card.c-green ::before { background: var(--green);  }
  .stat-card.c-amber ::before { background: var(--amber);  }
  .stat-card.c-red   ::before { background: var(--red);    }
  .stat-card:hover { border-color: var(--border2); transform: translateY(-1px); }
  .stat-label { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); font-family: var(--display); margin-bottom: 8px; }
  .stat-value { font-family: var(--display); font-size: 26px; font-weight: 700; line-height: 1; margin-bottom: 3px; }
  .stat-sub   { font-size: 11px; color: var(--text2); }
  .stat-ico   { position:absolute; right:14px; top:50%; transform:translateY(-50%); font-size:30px; opacity:.07; }
 
  /* ── Card ── */
  .card-s {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--rl); padding: 18px 20px;
    transition: border-color var(--ease);
  }
  .card-s:hover { border-color: var(--border2); }
  .card-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);
  }
  .card-title {
    font-family: var(--display); font-size: 13px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
  }
  .card-title i { color: var(--accent); }
 
  /* ── Tabs ── */
  .tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 18px; }
  .tab-btn {
    padding: 9px 18px; font-size: 13px; font-family: var(--display);
    font-weight: 500; color: var(--text2); cursor: pointer;
    border: none; border-bottom: 2px solid transparent;
    background: none; margin-bottom: -1px; transition: all var(--ease);
  }
  .tab-btn:hover  { color: var(--text); }
  .tab-btn.active { color: var(--amber); border-bottom-color: var(--amber); }
 
  /* ── Badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 3px 8px; border-radius: 20px; font-size: 11px;
    font-weight: 600; font-family: var(--display); white-space: nowrap;
  }
  .b-info    { background: var(--acc-dim);  color: var(--accent); border: 1px solid var(--acc-glow); }
  .b-success { background: var(--grn-dim);  color: var(--green);  border: 1px solid rgba(0,229,160,.2); }
  .b-warning { background: var(--amb-dim);  color: var(--amber);  border: 1px solid rgba(255,176,32,.2); }
  .b-danger  { background: var(--red-dim);  color: var(--red);    border: 1px solid rgba(255,71,87,.2); }
  .b-muted   { background: rgba(255,255,255,.04); color: var(--text2); border: 1px solid var(--border); }
  .b-purple  { background: var(--pur-dim);  color: var(--purple); border: 1px solid rgba(167,139,250,.2); }
 
  /* ── Buttons ── */
  .btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: var(--r); font-size: 12px;
    font-weight: 600; font-family: var(--display); cursor: pointer;
    border: 1px solid transparent; transition: all var(--ease);
    text-decoration: none; line-height: 1; white-space: nowrap;
  }
  .btn:hover { opacity: .85; transform: translateY(-1px); }
  .btn-prim  { background: var(--accent); color: var(--bg); border-color: var(--accent); }
  .btn-succ  { background: var(--green);  color: var(--bg); border-color: var(--green);  }
  .btn-ghost { background: transparent;   color: var(--text2); border-color: var(--border); }
  .btn-ghost:hover { color: var(--text); border-color: var(--border2); background: var(--hover); }
  .btn-sm    { padding: 4px 10px; font-size: 11px; }
  .btn-xs    { padding: 3px 8px;  font-size: 11px; }
 
  /* ── Tabla ── */
  .tbl { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
  .tbl thead th {
    padding: 9px 13px; background: var(--surface); color: var(--muted);
    font-size: 10px; letter-spacing: 1.2px; text-transform: uppercase;
    font-family: var(--display); font-weight: 500; border-bottom: 1px solid var(--border);
    white-space: nowrap;
  }
  .tbl tbody tr { transition: background var(--ease); }
  .tbl tbody tr:hover { background: var(--hover); }
  .tbl tbody td {
    padding: 10px 13px; color: var(--text2);
    border-bottom: 1px solid var(--border); vertical-align: middle;
  }
  .tbl tbody td:first-child { color: var(--text); font-weight: 500; }
  .tbl tbody tr:last-child td { border-bottom: none; }
 
  /* ── User cards grid ── */
  .users-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px,1fr)); gap: 14px; }
  .ucard {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--rl); padding: 16px 18px;
    transition: all var(--ease); position: relative; overflow: hidden;
  }
  .ucard:hover { border-color: var(--border2); transform: translateY(-1px); }
  .ucard.self  { border-color: rgba(0,212,255,.3); background: linear-gradient(135deg,rgba(0,212,255,.04),var(--card)); }
  .ucard-head  { display: flex; align-items: flex-start; gap: 11px; margin-bottom: 13px; }
  .ucard-av    { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center;
                 justify-content: center; font-family: var(--display); font-size: 14px; font-weight: 700;
                 flex-shrink: 0; border: 2px solid var(--border); }
  .av-admin  { background: var(--amb-dim); color: var(--amber);  border-color: rgba(255,176,32,.3); }
  .av-analista{ background: var(--acc-dim); color: var(--accent); border-color: var(--acc-glow); }
  .av-auditor { background: var(--grn-dim); color: var(--green);  border-color: rgba(0,229,160,.3); }
  .av-default { background: var(--pur-dim); color: var(--purple); border-color: rgba(167,139,250,.2); }
  .ucard-name  { font-family: var(--display); font-size: 13px; font-weight: 600; line-height: 1.3; margin-bottom: 2px; }
  .ucard-email { font-size: 11px; color: var(--muted); font-family: var(--mono); }
  .self-tag    { position: absolute; top: 11px; right: 11px; background: var(--acc-dim);
                 color: var(--accent); font-size: 9px; font-weight: 700; letter-spacing: 1px;
                 text-transform: uppercase; padding: 2px 7px; border-radius: 10px;
                 font-family: var(--display); border: 1px solid var(--acc-glow); }
  .ucard-meta  { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; margin-bottom: 13px; }
  .meta-lbl    { font-size: 10px; color: var(--muted); margin-bottom: 1px; letter-spacing: .4px; }
  .meta-val    { font-size: 12px; color: var(--text2); font-family: var(--mono); }
  .ucard-actions { border-top: 1px solid var(--border); padding-top: 12px; display: flex; flex-direction: column; gap: 8px; }
 
  /* ── Inline form (select + button) ── */
  .inline-form { display: flex; align-items: center; gap: 6px; }
  .iselect {
    flex: 1; min-width: 0;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); color: var(--text); padding: 5px 8px;
    font-family: var(--mono); font-size: 12px; cursor: pointer;
    transition: border-color var(--ease);
  }
  .iselect:focus  { border-color: var(--accent); outline: none; }
  .iselect:hover  { border-color: var(--border2); }
 
  /* ── Role stat card ── */
  .role-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--rl); padding: 16px 18px; }
  .role-name { font-family: var(--display); font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
  .role-bar  { height: 4px; background: var(--border); border-radius: 2px; margin: 8px 0 3px; overflow: hidden; }
  .role-fill { height: 100%; border-radius: 2px; transition: width .6s ease; }
  .role-row  { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; }
  .role-key  { color: var(--text2); }
  .role-val  { font-family: var(--display); font-weight: 600; }
 
  /* ── IP chip ── */
  .ip-chip { display: inline-block; font-family: var(--mono); font-size: 11px;
             background: var(--surface); border: 1px solid var(--border);
             border-radius: var(--r); padding: 2px 7px; color: var(--text2); }
 
  /* ── Flash ── */
  .flash { padding: 10px 15px; border-radius: var(--r); font-size: 13px;
           display: flex; align-items: flex-start; gap: 9px; margin-bottom: 16px; border: 1px solid; }
  .f-success { background: var(--grn-dim); color: var(--green);  border-color: rgba(0,229,160,.25); }
  .f-error   { background: var(--red-dim); color: var(--red);    border-color: rgba(255,71,87,.25); }
  .f-warning { background: var(--amb-dim); color: var(--amber);  border-color: rgba(255,176,32,.25); }
 
  /* ── Filter row ── */
  .filter-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
  .finput {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--r);
    color: var(--text); padding: 7px 11px; font-family: var(--mono); font-size: 13px;
    transition: border-color var(--ease); outline: none;
  }
  .finput:focus { border-color: var(--accent); }
  .finput::placeholder { color: var(--muted); }
 
  /* ── Paginador ── */
  .pager { display: flex; gap: 4px; margin-top: 16px; justify-content: flex-end; flex-wrap: wrap; }
  .pager a, .pager span {
    display: inline-flex; align-items: center; justify-content: center;
    width: 31px; height: 31px; border-radius: var(--r); font-size: 12px;
    font-family: var(--display); border: 1px solid var(--border); color: var(--text2);
    text-decoration: none; transition: all var(--ease);
  }
  .pager a:hover { background: var(--hover); color: var(--text); border-color: var(--border2); }
  .pager span.on { background: var(--amber); color: var(--bg); border-color: var(--amber); font-weight: 700; }
 
  /* ── Empty state ── */
  .empty { text-align: center; padding: 40px 24px; color: var(--muted); }
  .empty .eico { font-size: 36px; margin-bottom: 10px; opacity: .4; }
 
  /* ── Animation ── */
  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
  .fade-in { animation: fadeUp .22s ease forwards; }
 
  /* ── Pulse dot ── */
  .pulse { width: 7px; height: 7px; border-radius: 50%; background: var(--green); display: inline-block; position: relative; }
  .pulse::after { content:''; position:absolute; inset:-3px; border-radius:50%; background:var(--green); opacity:.3; animation: pulse 2s ease infinite; }
  @keyframes pulse { 0%,100%{transform:scale(1);opacity:.3}50%{transform:scale(1.5);opacity:0} }
 
  /* ── Responsive ── */
  @media(max-width:900px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0)}
    .main{margin-left:0}
    .body{padding:14px}
    .stat-grid{grid-template-columns:repeat(2,1fr)}
  }
  @media(max-width:560px){
    .stat-grid{grid-template-columns:1fr}
    .users-grid{grid-template-columns:1fr}
    .ucard-meta{grid-template-columns:1fr}
  }
  </style>
</head>
<body>
<div class="layout">
 
<!-- ════════════════════════════════════════════════════════
     SIDEBAR
═════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon">S</div>
    <div>
      <div class="sb-logo-text">SIEM</div>
      <div class="sb-logo-sub">Académico</div>
    </div>
  </div>
 
  <nav class="sb-nav">
    <div class="sb-section">Principal</div>
    <a href="dashboard.php"  class="sb-item"><span class="ico"><i class="fa-solid fa-gauge"></i></span>Dashboard</a>
 
    <div class="sb-section">Gestión</div>
    <a href="usuarios/index.php" class="sb-item"><span class="ico"><i class="fa-solid fa-users"></i></span>Usuarios</a>
    <a href="php/equipos.php"  class="sb-item"><span class="ico"><i class="fa-solid fa-server"></i></span>Equipos</a>
 
    <div class="sb-section">Seguridad</div>
    <a href="php/logs.php"       class="sb-item"><span class="ico"><i class="fa-solid fa-terminal"></i></span>Logs</a>
    <a href="php/alertas.php"    class="sb-item"><span class="ico"><i class="fa-solid fa-bell"></i></span>Alertas</a>
    <a href="php/incidentes.php" class="sb-item"><span class="ico"><i class="fa-solid fa-triangle-exclamation"></i></span>Incidentes</a>
 
    <div class="sb-section">Análisis</div>
    <a href="php/reportes.php" class="sb-item"><span class="ico"><i class="fa-solid fa-chart-bar"></i></span>Reportes</a>
 
    <div class="sb-section">Administración</div>
    <a href="panel_admin.php" class="sb-item active">
      <span class="ico"><i class="fa-solid fa-shield-halved"></i></span>
      Panel Admin
      <span class="admin-pill">ADMIN</span>
    </a>
  </nav>
 
  <div class="sb-footer">
    <div class="sb-avatar"><?= $adminIniciales ?></div>
    <div style="flex:1;min-width:0">
      <div class="sb-uname"><?= $adminNombre ?></div>
      <div class="sb-urole">Administrador</div>
    </div>
    <a href="logout.php" class="btn-logout" title="Cerrar sesión">
      <i class="fa-solid fa-right-from-bracket"></i>
    </a>
  </div>
</aside>
 
<!-- ════════════════════════════════════════════════════════
     MAIN
═════════════════════════════════════════════════════════ -->
<div class="main">
 
  <!-- Topbar -->
  <header class="topbar">
    <button id="menu-btn"
      style="display:none;background:none;border:1px solid var(--border);border-radius:var(--r);
             color:var(--text2);padding:6px 9px;cursor:pointer;font-size:14px"
      onclick="document.getElementById('sidebar').classList.toggle('open')">
      <i class="fa-solid fa-bars"></i>
    </button>
 
    <div>
      <div class="topbar-title">Panel de Administración</div>
      <div class="topbar-bc">
        <span>SIEM</span><span class="sep">›</span>
        <span>Administración</span><span class="sep">›</span>
        <span>Panel Principal</span>
      </div>
    </div>
 
    <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
      <span class="badge b-success" style="font-size:11px">
        <span class="pulse" style="margin-right:4px"></span>
        <?= date('d/m/Y H:i') ?>
      </span>
      <span class="badge b-warning">
        <i class="fa-solid fa-shield-halved"></i> ADMIN
      </span>
    </div>
  </header>
 
  <!-- Body -->
  <div class="body fade-in">
 
    <!-- Flash message -->
    <?php if ($flashMsg !== ''): ?>
    <div class="flash f-<?= $flashTipo ?>">
      <i class="fa-solid fa-<?= $flashTipo==='success'?'circle-check':($flashTipo==='error'?'circle-xmark':'triangle-exclamation') ?>"></i>
      <?= $flashMsg ?>
    </div>
    <?php endif; ?>
 
    <!-- Banner zona restringida -->
    <div class="admin-banner">
      <div class="banner-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div style="flex:1">
        <div class="banner-title">Panel de Administración — Zona Restringida</div>
        <div class="banner-sub">
          Acceso exclusivo para administradores ·
          Sesión: <strong style="color:var(--text)"><?= $adminNombre ?></strong>
          · IP: <span class="ip-chip"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '—') ?></span>
        </div>
      </div>
      <span class="badge b-warning" style="flex-shrink:0">
        <i class="fa-solid fa-lock"></i> ADMIN ONLY
      </span>
    </div>
 
    <!-- Stats rápidas -->
    <div class="stat-grid">
      <div class="stat-card c-accent">
        <div class="stat-label">Total usuarios</div>
        <div class="stat-value"><?= $totalUsuarios ?></div>
        <div class="stat-sub"><?= $totalRoles ?> roles</div>
        <i class="fa-solid fa-users stat-ico"></i>
      </div>
      <div class="stat-card c-green">
        <div class="stat-label">Activos</div>
        <div class="stat-value"><?= $totalActivos ?></div>
        <div class="stat-sub">con acceso</div>
        <i class="fa-solid fa-circle-check stat-ico"></i>
      </div>
      <div class="stat-card c-amber">
        <div class="stat-label">Inactivos</div>
        <div class="stat-value"><?= $totalInact ?></div>
        <div class="stat-sub">sin acceso</div>
        <i class="fa-solid fa-user-slash stat-ico"></i>
      </div>
      <div class="stat-card c-red">
        <div class="stat-label">Suspendidos</div>
        <div class="stat-value"><?= $totalSuspend ?></div>
        <div class="stat-sub">bloqueados</div>
        <i class="fa-solid fa-ban stat-ico"></i>
      </div>
    </div>
 
    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('usuarios',this)">
        <i class="fa-solid fa-users me-1"></i>
        Usuarios (<?= $totalUsuariosFiltro ?>)
      </button>
      <button class="tab-btn" onclick="showTab('roles',this)">
        <i class="fa-solid fa-id-badge me-1"></i>
        Estadísticas de roles
      </button>
      <button class="tab-btn" onclick="showTab('sesiones',this)">
        <i class="fa-solid fa-clock-rotate-left me-1"></i>
        Sesiones recientes
      </button>
    </div>
 
    <!-- ══════════════════════════════════════
         TAB 1 — USUARIOS
    ══════════════════════════════════════ -->
    <div id="tab-usuarios">
 
      <!-- Filtros -->
      <div class="card-s" style="margin-bottom:16px">
        <form method="GET" class="filter-row" style="margin-bottom:0">
 
          <input type="text" name="buscar" class="finput"
            placeholder="Buscar nombre, correo…"
            value="<?= htmlspecialchars($buscar) ?>"
            style="flex:1;min-width:160px;max-width:260px">
 
          <select name="rol" class="finput" style="max-width:155px">
            <option value="0">Todos los roles</option>
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id_rol'] ?>" <?= $filtroRol===$r['id_rol']?'selected':'' ?>>
              <?= htmlspecialchars($r['nombre_rol']) ?>
            </option>
            <?php endforeach; ?>
          </select>
 
          <select name="estado" class="finput" style="max-width:155px">
            <option value="">Todos los estados</option>
            <option value="activo"     <?= $filtroEst==='activo'    ?'selected':'' ?>>Activo</option>
            <option value="inactivo"   <?= $filtroEst==='inactivo'  ?'selected':'' ?>>Inactivo</option>
            <option value="suspendido" <?= $filtroEst==='suspendido'?'selected':'' ?>>Suspendido</option>
          </select>
 
          <button type="submit" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-magnifying-glass"></i> Filtrar
          </button>
 
          <?php if ($buscar || $filtroRol || $filtroEst): ?>
          <a href="panel_admin.php" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-xmark"></i> Limpiar
          </a>
          <?php endif; ?>
 
          <a href="usuarios/crear.php" class="btn btn-succ btn-sm" style="margin-left:auto">
            <i class="fa-solid fa-user-plus"></i> Nuevo usuario
          </a>
 
        </form>
      </div>
 
      <!-- Grid de tarjetas -->
      <?php if (empty($usuarios)): ?>
        <div class="card-s">
          <div class="empty"><div class="eico"><i class="fa-solid fa-users"></i></div>
          <p>No se encontraron usuarios.</p></div>
        </div>
      <?php else: ?>
 
      <div class="users-grid">
        <?php foreach ($usuarios as $u):
          $esMio    = ((int)$u['id_usuario'] === $adminId);
          $rolLower = strtolower($u['nombre_rol']);
          $avClass  = str_contains($rolLower,'admin')   ? 'av-admin'
                    :(str_contains($rolLower,'analista') ? 'av-analista'
                    :(str_contains($rolLower,'auditor')  ? 'av-auditor'  : 'av-default'));
          $iniciales = strtoupper(substr($u['nombre'],0,1).substr($u['apellido'],0,1));
 
          // Badge estado
          $badgeEst = match($u['estado']) {
            'activo'     => '<span class="badge b-success">● Activo</span>',
            'inactivo'   => '<span class="badge b-muted">○ Inactivo</span>',
            'suspendido' => '<span class="badge b-danger">✖ Suspendido</span>',
            default      => '<span class="badge b-muted">'.$u['estado'].'</span>',
          };
          // Badge rol
          $badgeRol = match(true) {
            str_contains($rolLower,'admin')    => '<span class="badge b-warning">',
            str_contains($rolLower,'analista') => '<span class="badge b-info">',
            str_contains($rolLower,'auditor')  => '<span class="badge b-success">',
            default                            => '<span class="badge b-muted">',
          };
          $badgeRol .= htmlspecialchars($u['nombre_rol']).'</span>';
        ?>
        <div class="ucard <?= $esMio ? 'self' : '' ?>">
 
          <?php if ($esMio): ?><div class="self-tag">TÚ</div><?php endif; ?>
 
          <!-- Cabecera -->
          <div class="ucard-head">
            <div class="ucard-av <?= $avClass ?>"><?= $iniciales ?></div>
            <div style="flex:1;min-width:0">
              <div class="ucard-name"><?= htmlspecialchars($u['nombre'].' '.$u['apellido']) ?></div>
              <div class="ucard-email"><?= htmlspecialchars($u['correo']) ?></div>
            </div>
          </div>
 
          <!-- Metadatos -->
          <div class="ucard-meta">
            <div>
              <div class="meta-lbl">Estado</div>
              <div class="meta-val"><?= $badgeEst ?></div>
            </div>
            <div>
              <div class="meta-lbl">Rol actual</div>
              <div class="meta-val"><?= $badgeRol ?></div>
            </div>
            <div>
              <div class="meta-lbl">Sesiones</div>
              <div class="meta-val"><?= (int)$u['total_sesiones'] ?></div>
            </div>
            <div>
              <div class="meta-lbl">Incidentes</div>
              <div class="meta-val"><?= (int)$u['total_incidentes'] ?></div>
            </div>
            <div style="grid-column:1/-1">
              <div class="meta-lbl">Último acceso</div>
              <div class="meta-val"><?= $u['ultimo_acceso']
                  ? htmlspecialchars(substr($u['ultimo_acceso'],0,16))
                  : 'Sin accesos' ?></div>
            </div>
            <div style="grid-column:1/-1">
              <div class="meta-lbl">Registrado</div>
              <div class="meta-val"><?= htmlspecialchars(substr($u['fecha_registro'],0,10)) ?></div>
            </div>
          </div>
 
          <!-- Controles -->
          <?php if (!$esMio): ?>
          <div class="ucard-actions">
 
            <!-- Cambiar rol -->
            <form method="POST" class="inline-form">
              <input type="hidden" name="accion"     value="cambiar_rol">
              <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
              <select name="id_rol" class="iselect">
                <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol']==$u['id_rol']?'selected':'' ?>>
                  <?= htmlspecialchars($r['nombre_rol']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-prim btn-xs"
                onclick="return confirm('¿Cambiar rol de <?= htmlspecialchars(addslashes($u['nombre'])) ?>?')">
                <i class="fa-solid fa-check"></i> Rol
              </button>
            </form>
 
            <!-- Cambiar estado -->
            <form method="POST" class="inline-form">
              <input type="hidden" name="accion"     value="cambiar_estado">
              <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
              <select name="estado" class="iselect">
                <?php foreach (['activo','inactivo','suspendido'] as $est): ?>
                <option value="<?= $est ?>" <?= $u['estado']===$est?'selected':'' ?>>
                  <?= ucfirst($est) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-ghost btn-xs"
                onclick="return confirm('¿Cambiar estado de <?= htmlspecialchars(addslashes($u['nombre'])) ?>?')">
                <i class="fa-solid fa-sliders"></i> Estado
              </button>
            </form>
 
            <!-- Editar -->
            <a href="usuarios/editar.php?id=<?= $u['id_usuario'] ?>"
               class="btn btn-ghost btn-xs" style="justify-content:center">
              <i class="fa-solid fa-pen-to-square"></i> Edición completa
            </a>
 
          </div>
          <?php else: ?>
          <div style="border-top:1px solid var(--border);padding-top:10px;text-align:center;font-size:11px;color:var(--muted)">
            <i class="fa-solid fa-lock me-1"></i> Esta es tu cuenta — no puedes modificarla aquí
          </div>
          <?php endif; ?>
 
        </div><!-- /ucard -->
        <?php endforeach; ?>
      </div><!-- /users-grid -->
 
      <!-- Paginador -->
      <?php
        $totalPags = (int)ceil($totalUsuariosFiltro / $limite);
        if ($totalPags > 1):
      ?>
      <div class="pager">
        <?php for ($i = 1; $i <= $totalPags; $i++): ?>
          <?php if ($i === $pagina): ?>
            <span class="on"><?= $i ?></span>
          <?php else: ?>
            <a href="panel_admin.php<?= $urlBase ?>&pagina=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
 
      <?php endif; ?>
    </div><!-- /tab-usuarios -->
 
 
    <!-- ══════════════════════════════════════
         TAB 2 — ESTADÍSTICAS DE ROLES
    ══════════════════════════════════════ -->
    <div id="tab-roles" style="display:none">
      <div class="row g-3 mb-4">
        <?php
        $coloresRol = [
            'Administrador' => ['var(--amber)', 'fa-shield-halved'],
            'Analista'      => ['var(--accent)','fa-magnifying-glass'],
            'Auditor'       => ['var(--green)', 'fa-file-alt'],
        ];
        $totalGlobal = max(1, $totalUsuarios);
        foreach ($statsRoles as $sr):
          [$color, $icon] = $coloresRol[$sr['nombre_rol']] ?? ['var(--purple)','fa-user'];
          $pct = round(($sr['total'] / $totalGlobal) * 100);
        ?>
        <div class="col-12 col-md-6 col-xl-4">
          <div class="role-card">
            <div class="role-name">
              <span style="width:28px;height:28px;background:rgba(255,255,255,.05);border-radius:6px;
                           display:inline-flex;align-items:center;justify-content:center;color:<?= $color ?>;font-size:13px">
                <i class="fa-solid <?= $icon ?>"></i>
              </span>
              <?= htmlspecialchars($sr['nombre_rol']) ?>
              <span style="margin-left:auto;font-family:var(--display);font-size:22px;font-weight:700;color:<?= $color ?>">
                <?= $sr['total'] ?>
              </span>
            </div>
            <div class="role-bar">
              <div class="role-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
            </div>
            <div style="font-size:10px;color:var(--muted);text-align:right;margin-bottom:10px"><?= $pct ?>% del total</div>
            <div class="role-row"><span class="role-key">✅ Activos</span>    <span class="role-val" style="color:var(--green)"><?= $sr['activos']    ?? 0 ?></span></div>
            <div class="role-row"><span class="role-key">⚪ Inactivos</span>  <span class="role-val" style="color:var(--text2)"><?= $sr['inactivos']  ?? 0 ?></span></div>
            <div class="role-row"><span class="role-key">🚫 Suspendidos</span><span class="role-val" style="color:var(--red)"  ><?= $sr['suspendidos']?? 0 ?></span></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
 
      <!-- Tabla de roles -->
      <div class="card-s">
        <div class="card-head">
          <div class="card-title"><i class="fa-solid fa-id-badge"></i> Roles definidos en el sistema</div>
        </div>
        <div class="table-responsive">
          <table class="tbl">
            <thead><tr><th>#</th><th>Nombre del rol</th><th>Descripción</th><th>Usuarios</th></tr></thead>
            <tbody>
              <?php foreach ($statsRoles as $sr):
                [$color,] = $coloresRol[$sr['nombre_rol']] ?? ['var(--purple)',''];
                $descRes  = mysqli_query($conexion,
                    "SELECT descripcion FROM roles WHERE nombre_rol='" .
                    mysqli_real_escape_string($conexion, $sr['nombre_rol']) . "' LIMIT 1");
                $descRow  = mysqli_fetch_row($descRes);
              ?>
              <tr>
                <td class="font-mono" style="font-size:11px;color:var(--muted)"><?= $sr['nombre_rol'][0] ?></td>
                <td>
                  <span class="badge"
                    style="background:rgba(255,255,255,.05);color:<?= $color ?>;border:1px solid rgba(255,255,255,.1)">
                    <?= htmlspecialchars($sr['nombre_rol']) ?>
                  </span>
                </td>
                <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($descRow[0] ?? '—') ?></td>
                <td><span class="badge b-info"><?= $sr['total'] ?> usuarios</span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /tab-roles -->
 
 
    <!-- ══════════════════════════════════════
         TAB 3 — SESIONES RECIENTES
    ══════════════════════════════════════ -->
    <div id="tab-sesiones" style="display:none">
      <div class="card-s">
        <div class="card-head">
          <div class="card-title">
            <i class="fa-solid fa-clock-rotate-left"></i> Últimas 8 sesiones
          </div>
          <span class="badge b-muted">Solo lectura</span>
        </div>
 
        <?php if (empty($sesiones)): ?>
          <div class="empty"><div class="eico"><i class="fa-solid fa-clock"></i></div><p>Sin sesiones registradas.</p></div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="tbl">
            <thead>
              <tr><th>#</th><th>Usuario</th><th>Rol</th><th>IP</th><th>Inicio</th><th>Fin</th><th>Duración</th></tr>
            </thead>
            <tbody>
              <?php foreach ($sesiones as $ses):
                $ini  = new DateTime($ses['fecha_inicio']);
                $fin  = $ses['fecha_fin'] ? new DateTime($ses['fecha_fin']) : null;
                $dur  = $fin ? $ini->diff($fin) : null;
                $durStr = $fin
                  ? ($dur->h . 'h ' . $dur->i . 'm')
                  : '<span class="badge b-success">● Activa</span>';
                [$clr,] = $coloresRol[$ses['nombre_rol']] ?? ['var(--purple)',''];
              ?>
              <tr>
                <td class="font-mono" style="font-size:11px;color:var(--muted)">#<?= $ses['id_sesion'] ?></td>
                <td><?= htmlspecialchars($ses['usuario']) ?></td>
                <td>
                  <span class="badge" style="background:rgba(255,255,255,.05);color:<?= $clr ?>;border:1px solid rgba(255,255,255,.1)">
                    <?= htmlspecialchars($ses['nombre_rol']) ?>
                  </span>
                </td>
                <td><span class="ip-chip"><?= htmlspecialchars($ses['ip_acceso']) ?></span></td>
                <td class="font-mono" style="font-size:11px;color:var(--muted)"><?= htmlspecialchars(substr($ses['fecha_inicio'],0,16)) ?></td>
                <td class="font-mono" style="font-size:11px;color:var(--muted)"><?= $ses['fecha_fin'] ? htmlspecialchars(substr($ses['fecha_fin'],0,16)) : '—' ?></td>
                <td><?= $durStr ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="padding-top:10px;font-size:11px;color:var(--muted);text-align:right">
          <i class="fa-solid fa-circle-info me-1"></i>
          Historial completo en la tabla <code style="color:var(--accent)">historial_sesiones</code>.
        </div>
        <?php endif; ?>
      </div>
    </div><!-- /tab-sesiones -->
 
  </div><!-- /body -->
</div><!-- /main -->
</div><!-- /layout -->
 
<!-- ── Scripts ── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Tabs */
function showTab(id, btn) {
  ['usuarios','roles','sesiones'].forEach(t =>
    document.getElementById('tab-' + t).style.display = 'none'
  );
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).style.display = 'block';
  btn.classList.add('active');
}
 
/* Animar barras de progreso al cargar */
document.querySelectorAll('.role-fill').forEach(bar => {
  const w = bar.style.width;
  bar.style.width = '0';
  setTimeout(() => { bar.style.width = w; }, 150);
});
 
/* Mostrar botón de menú en móvil */
if (window.innerWidth < 900) {
  document.getElementById('menu-btn').style.display = 'inline-flex';
}
window.addEventListener('resize', () => {
  document.getElementById('menu-btn').style.display =
    window.innerWidth < 900 ? 'inline-flex' : 'none';
});
 
/* Auto-ocultar flash */
const flash = document.querySelector('.flash');
if (flash) setTimeout(() => {
  flash.style.transition = 'opacity .4s';
  flash.style.opacity = '0';
  setTimeout(() => flash.remove(), 400);
}, 4000);
</script>
 
</body>
</html>