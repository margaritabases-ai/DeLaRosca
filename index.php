<?php

// --- CONFIGURACIÓN ---
$tablas_permitidas = ['empleado', 'departamento', 'computadora', 'nobreak', 'impresora', 'telefono'];

$tabla_seleccionada = isset($_GET['tabla']) ? htmlspecialchars($_GET['tabla']) : $tablas_permitidas[0];

if (!in_array($tabla_seleccionada, $tablas_permitidas)) {
    die("Error: Tabla no válida.");
}

// --- CONEXIÓN A LA BASE DE DATOS (VERSIÓN MEJORADA) ---
$connection_string_original = getenv('DATABASE_URL');
$db_connection = null;
$mensaje_conexion = "";

if ($connection_string_original) {
    // Añadimos sslmode=require si no está presente en la URL de Neon.
    // Esto asegura la conexión segura que Neon necesita.
    $connection_string_final = $connection_string_original . "?sslmode=require";
    
    // pg_connect es la función de PHP para conectarse a PostgreSQL.
    $db_connection = pg_connect($connection_string_final);

    if ($db_connection) {
        $mensaje_conexion = "✅ Conexión a la base de datos exitosa.";
    } else {
        $mensaje_conexion = "❌ Error: No se pudo conectar a la base de datos. Verifica la DATABASE_URL.";
    }
} else {
    $mensaje_conexion = "⚠️ Advertencia: La variable de entorno DATABASE_URL no está configurada.";
}

// --- OBTENCIÓN DE DATOS ---
$columnas = [];
$filas = [];

if ($db_connection) {
    $query = 'SELECT * FROM ' . pg_escape_identifier($db_connection, $tabla_seleccionada);
    $resultado = pg_query($db_connection, $query);
    if ($resultado) {
        $num_columnas = pg_num_fields($resultado);
        for ($i = 0; $i < $num_columnas; $i++) {
            $columnas[] = pg_field_name($resultado, $i);
        }
        $filas = pg_fetch_all($resultado);
        if ($filas === false) {
            $filas = [];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Base de Datos</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; background-color: #f4f4f9; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; margin-bottom: 20px; }
        h1 { color: #2c3e50; }
        .status { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500;}
        .status.success { background-color: #e8f5e9; color: #2e7d32; }
        .status.error { background-color: #ffebee; color: #c62828; }
        .status.warning { background-color: #fff3e0; color: #ef6c00; }
        nav { margin-bottom: 20px; flex-wrap: wrap; display: flex; }
        nav a { padding: 8px 15px; margin-right: 10px; margin-bottom: 10px; text-decoration: none; color: #3498db; font-weight: 500; border-radius: 20px; background-color: #f0f8ff; transition: all 0.2s ease-in-out; }
        nav a:hover { background-color: #ddeeff; }
        nav a.active { color: #fff; background-color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; text-transform: capitalize; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Visor de Activos de la Empresa</h1>
            <p>Selecciona una tabla para ver sus registros.</p>
        </div>
        <div class="status <?php
            $status_class = 'warning';
            if ($db_connection) $status_class = 'success';
            if (!$db_connection && $connection_string_original) $status_class = 'error';
            echo $status_class;
        ?>">
            <?php echo $mensaje_conexion; ?>
        </div>
        <nav>
            <?php foreach ($tablas_permitidas as $tabla): ?>
                <a href="?tabla=<?php echo $tabla; ?>" class="<?php if ($tabla === $tabla_seleccionada) echo 'active'; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $tabla)); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div style="overflow-x:auto;">
            <h2>Registros de: "<?php echo ucfirst($tabla_seleccionada); ?>"</h2>
            <?php if ($db_connection && !empty($filas)): ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columnas as $columna): ?>
                                <th><?php echo htmlspecialchars(str_replace('_', ' ', $columna)); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filas as $fila): ?>
                            <tr>
                                <?php foreach ($fila as $valor): ?>
                                    <td><?php echo htmlspecialchars($valor); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($db_connection && empty($filas)): ?>
                <p>La tabla "<?php echo $tabla_seleccionada; ?>" no tiene registros.</p>
            <?php else: ?>
                <p>No se pueden mostrar los datos. Verifica la conexión a la base de datos.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
