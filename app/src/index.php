<?php
require_once 'db.php';


// 1. Manejar Eliminación
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM inventario WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: index.php");
    exit();
}

// 2. Manejar Agregar Nuevo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $nombre = $_POST['nombre'];
    $categoria = $_POST['categoria'];
    $stock = $_POST['stock'];

    if (!empty($nombre) && !empty($stock)) {
        $stmt = $pdo->prepare("INSERT INTO inventario (nombre, categoria, stock) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $categoria, $stock]);
        header("Location: index.php");
        exit();
    }
}

// 3. Leer Datos (Read)
$stmt = $pdo->query("SELECT * FROM inventario ORDER BY id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LOGICA DE KUBERNETES ---
$podName = getenv('POD_NAME') ?: 'Localhost (XAMPP)';
$nodeName = getenv('NODE_NAME') ?: 'Mi PC';
$banner = getenv('BANNER') ?: 'Sistema de Inventario ITM';
$serverIp = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';

// Color de estado: Verde si es K8s (tiene pod), Azul si es local
$isK8s = getenv('POD_NAME') ? true : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Multipod - Examen</title>
    <link rel="stylesheet" href="style.css">
    <!-- Iconos simples (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>


    <header class="k8s-header <?php echo $isK8s ? 'active-pod' : ''; ?>">
        <div class="header-content">
            <div class="brand">
                <i class="fa-solid fa-server"></i>

                <h1><?php echo htmlspecialchars($banner); ?></h1>
            </div>
            <div class="pod-info">
                <!-- Punto 3.1 del Examen: Variables POD_NAME y NODE_NAME -->
                <div class="badge">
                    <small>POD:</small> <span><?php echo htmlspecialchars($podName); ?></span>
                </div>
                <div class="badge">
                    <small>NODO:</small> <span><?php echo htmlspecialchars($nodeName); ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        
        <!-- SECCIÓN IZQUIERDA: FORMULARIO (CREATE) -->
        <aside class="sidebar">
            <div class="card form-card">
                <h3><i class="fa-solid fa-plus-circle"></i> Nuevo Item</h3>
                <form method="POST" action="index.php">
                    <div class="form-group">
                        <label>Nombre del Producto</label>
                        <input type="text" name="nombre" placeholder="Ej. Laptop HP" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="categoria">
                            <option value="Hardware">Hardware</option>
                            <option value="Software">Software</option>
                            <option value="Redes">Redes</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Stock Inicial</label>
                        <input type="number" name="stock" value="1" min="0" required>
                    </div>

                    <button type="submit" name="add_item" class="btn-primary">
                        Agregar al Inventario
                    </button>
                </form>
            </div>
            
            <!-- Widget decorativo de estado -->
            <div class="card status-card">
                <h4>Estado del Sistema</h4>
                <p><i class="fa-solid fa-check-circle success"></i> Base de Datos: <strong>Conectada</strong></p>
                <p><i class="fa-solid fa-network-wired"></i> IP Servidor: <?php echo $serverIp; ?></p>
            </div>
        </aside>

        <!-- SECCIÓN DERECHA: TABLA (READ & DELETE) -->
        <main class="content-area">
            <div class="card table-card">
                <div class="card-header">
                    <h2>Listado Actual</h2>
                    <span class="count-badge"><?php echo count($items); ?> registros</span>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>#<?php echo $item['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['nombre']); ?></strong></td>
                                    <td><span class="tag <?php echo strtolower($item['categoria']); ?>"><?php echo $item['categoria']; ?></span></td>
                                    <td><?php echo $item['stock']; ?> un.</td>
                                    <td class="date"><?php echo date('d/m H:i', strtotime($item['fecha_registro'])); ?></td>
                                    <td>
                                        <!-- Botón Eliminar -->
                                        <a href="index.php?action=delete&id=<?php echo $item['id']; ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('¿Estás seguro de eliminar este ítem?');">
                                           <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fa-solid fa-box-open"></i>
                                        <p>No hay items en el inventario.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>