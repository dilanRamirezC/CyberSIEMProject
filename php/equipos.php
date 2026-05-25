<?php
session_start();
include("../php/conexion.php");

/* ── ASIGNAR EQUIPO A USUARIO ── */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'crear') {
        $nombre_equipo     = mysqli_real_escape_string($conexion, $_POST['nombre_equipo']);
        $sistema_operativo = mysqli_real_escape_string($conexion, $_POST['sistema_operativo']);
        $direccion_ip      = mysqli_real_escape_string($conexion, $_POST['direccion_ip']);
        $ubicacion         = mysqli_real_escape_string($conexion, $_POST['ubicacion']);
        $estado            = mysqli_real_escape_string($conexion, $_POST['estado']);
        $id_usuario        = $_POST['id_usuario'] !== '' ? (int)$_POST['id_usuario'] : "NULL";

        $q = "INSERT INTO equipos (nombre_equipo, sistema_operativo, direccion_ip, ubicacion, estado, id_usuario)
              VALUES ('$nombre_equipo','$sistema_operativo','$direccion_ip','$ubicacion','$estado',$id_usuario)";
        mysqli_query($conexion, $q);
    }

    if ($_POST['accion'] === 'asignar') {
        $id_equipo  = (int)$_POST['id_equipo'];
        $id_usuario = $_POST['id_usuario'] !== '' ? (int)$_POST['id_usuario'] : "NULL";
        $ubicacion  = mysqli_real_escape_string($conexion, $_POST['ubicacion']);
        mysqli_query($conexion, "UPDATE equipos SET id_usuario=$id_usuario, ubicacion='$ubicacion' WHERE id_equipo=$id_equipo");
    }

    header("Location: equipos.php");
    exit;
}

/* ── CONSULTAS ── */
$usuarios  = mysqli_query($conexion, "SELECT id_usuario, nombre, apellido, id_rol FROM usuarios WHERE estado='ACTIVO' ORDER BY nombre");
$equipos_q = "
    SELECT
        e.id_equipo, e.nombre_equipo, e.sistema_operativo,
        e.direccion_ip, e.estado, e.ubicacion,
        u.nombre, u.apellido, u.id_rol
    FROM equipos e
    LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
    ORDER BY e.id_equipo DESC
";
$equipos = mysqli_query($conexion, $equipos_q);

// Guardar usuarios en array para reusar
$lista_usuarios = [];
while ($u = mysqli_fetch_assoc($usuarios)) $lista_usuarios[] = $u;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Equipos · CyberSIEM</title>
<link rel="stylesheet" href="../css/equipos.css">
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
    <h1>Gestión de <span>Equipos</span></h1>
    <span class="badge-total">CyberSIEM</span>
  </div>
  <a href="../index.php" class="btn-back">← Volver al inicio</a>
</div>

<div class="layout">

  <!-- ══ PANEL IZQUIERDO ══ -->
  <div class="panel">
    <div class="panel-tabs">
      <button class="tab-btn active" onclick="switchTab('crear')">＋ Nuevo Equipo</button>
      <button class="tab-btn" onclick="switchTab('asignar')">⇄ Asignar</button>
    </div>

    <!-- TAB: CREAR -->
    <div class="tab-content active" id="tab-crear">
      <form method="POST">
        <input type="hidden" name="accion" value="crear">

        <div class="form-group">
          <label>Nombre del Equipo</label>
          <input type="text" name="nombre_equipo" placeholder="PC-CONTABILIDAD-01" required>
        </div>
        <div class="form-group">
          <label>Sistema Operativo</label>
          <select name="sistema_operativo" required>
            <option value="">Seleccionar SO</option>
            <option>Windows 11</option>
            <option>Windows 10</option>
            <option>Windows Server 2022</option>
            <option>Windows Server 2019</option>
            <option>Ubuntu 22.04</option>
            <option>Ubuntu 20.04</option>
            <option>Debian 12</option>
            <option>CentOS 7</option>
            <option>macOS Ventura</option>
            <option>macOS Sonoma</option>
            <option>Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Dirección IP</label>
          <input type="text" name="direccion_ip" placeholder="192.168.1.100" pattern="^(\d{1,3}\.){3}\d{1,3}$" required>
        </div>
        <div class="form-group">
          <label>Ubicación</label>
          <input type="text" name="ubicacion" placeholder="Oficina 3B / Piso 2" required>
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select name="estado" required>
            <option value="ACTIVO">ACTIVO</option>
            <option value="INACTIVO">INACTIVO</option>
            <option value="MANTENIMIENTO">MANTENIMIENTO</option>
          </select>
        </div>
        <div class="form-group">
          <label>Asignar Usuario (opcional)</label>
          <select name="id_usuario">
            <option value="">Sin usuario asignado</option>
            <?php foreach ($lista_usuarios as $u): ?>
            <option value="<?= $u['id_usuario'] ?>">
              <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?> — Rol <?= $u['id_rol'] ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn-submit">Registrar Equipo</button>
      </form>
    </div>

    <!-- TAB: ASIGNAR RÁPIDO -->
    <div class="tab-content" id="tab-asignar">
      <p style="font-size:.8rem;color:var(--muted);margin-bottom:1.25rem;line-height:1.5">
        Reasigna rápidamente un equipo existente a otro usuario o cambia su ubicación.
      </p>
      <form method="POST">
        <input type="hidden" name="accion" value="asignar">
        <div class="form-group">
          <label>Equipo</label>
          <select name="id_equipo" required>
            <option value="">Seleccionar equipo</option>
            <?php
            mysqli_data_seek($equipos, 0);
            while ($eq = mysqli_fetch_assoc($equipos)):
            ?>
            <option value="<?= $eq['id_equipo'] ?>">
              <?= htmlspecialchars($eq['nombre_equipo']) ?> — <?= $eq['direccion_ip'] ?>
            </option>
            <?php endwhile; mysqli_data_seek($equipos, 0); ?>
          </select>
        </div>
        <div class="form-group">
          <label>Nuevo Usuario</label>
          <select name="id_usuario">
            <option value="">Sin usuario</option>
            <?php foreach ($lista_usuarios as $u): ?>
            <option value="<?= $u['id_usuario'] ?>">
              <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?> — Rol <?= $u['id_rol'] ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Nueva Ubicación</label>
          <input type="text" name="ubicacion" placeholder="Sala de servidores" required>
        </div>
        <button type="submit" class="btn-submit">Guardar Asignación</button>
      </form>
    </div>
  </div>

  <!-- ══ TABLA ══ -->
  <div class="table-wrap">
    <div class="table-header">
      <h2>Equipos Registrados</h2>
      <input class="search-box" type="text" id="buscador" placeholder="Buscar equipo..." oninput="filtrarTabla()">
    </div>
    <div class="table-scroll">
      <table id="tablaEquipos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Equipo</th>
            <th>SO</th>
            <th>IP</th>
            <th>Ubicación</th>
            <th>Estado</th>
            <th>Usuario Asignado</th>
            <th>Rol</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $count = 0;
          mysqli_data_seek($equipos, 0);
          while ($fila = mysqli_fetch_assoc($equipos)):
            $count++;
            $estado = $fila['estado'];
            $chipClass = match($estado) {
              'ACTIVO'        => 'chip-ok',
              'INACTIVO'      => 'chip-off',
              'MANTENIMIENTO' => 'chip-warn',
              default         => 'chip-warn'
            };
          ?>
          <tr>
            <td style="color:var(--muted)">#<?= $fila['id_equipo'] ?></td>
            <td style="font-weight:700;color:var(--text)"><?= htmlspecialchars($fila['nombre_equipo']) ?></td>
            <td><?= htmlspecialchars($fila['sistema_operativo']) ?></td>
            <td style="color:var(--accent)"><?= htmlspecialchars($fila['direccion_ip']) ?></td>
            <td><?= htmlspecialchars($fila['ubicacion']) ?></td>
            <td><span class="chip <?= $chipClass ?>"><?= $estado ?></span></td>
            <td>
              <?php if ($fila['nombre']): ?>
                <?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellido']) ?>
              <?php else: ?>
                <span class="no-user">Sin asignar</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($fila['id_rol']): ?>
                <span class="rol-chip">ROL <?= $fila['id_rol'] ?></span>
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn-asignar"
                onclick="abrirModal(<?= $fila['id_equipo'] ?>, '<?= htmlspecialchars($fila['nombre_equipo']) ?>', '<?= htmlspecialchars($fila['ubicacion']) ?>')">
                Reasignar
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if ($count === 0): ?>
          <tr>
            <td colspan="9">
              <div class="empty">
                <span>🖥️</span>
                No hay equipos registrados aún.
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /layout -->

<!-- ══ MODAL REASIGNAR ══ -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <h3>Reasignar <span id="modalNombre"></span></h3>
    <form method="POST">
      <input type="hidden" name="accion" value="asignar">
      <input type="hidden" name="id_equipo" id="modalIdEquipo">

      <div class="form-group">
        <label>Usuario</label>
        <select name="id_usuario">
          <option value="">Sin usuario</option>
          <?php foreach ($lista_usuarios as $u): ?>
          <option value="<?= $u['id_usuario'] ?>">
            <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?> — Rol <?= $u['id_rol'] ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Ubicación</label>
        <input type="text" name="ubicacion" id="modalUbicacion" required>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn-submit" style="flex:1;margin:0">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── TABS
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active', (i===0&&tab==='crear')||(i===1&&tab==='asignar')));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
}

// ── MODAL
function abrirModal(id, nombre, ubicacion) {
  document.getElementById('modalIdEquipo').value  = id;
  document.getElementById('modalNombre').textContent = nombre;
  document.getElementById('modalUbicacion').value = ubicacion;
  document.getElementById('modalOverlay').classList.add('open');
}
function cerrarModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}
document.getElementById('modalOverlay').addEventListener('click', function(e){
  if (e.target === this) cerrarModal();
});

// ── BÚSQUEDA EN TABLA
function filtrarTabla() {
  const filtro = document.getElementById('buscador').value.toLowerCase();
  document.querySelectorAll('#tablaEquipos tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(filtro) ? '' : 'none';
  });
}
</script>
</body>
</html>
