<?php

// --- CONFIGURACIÓN ---
// Mantenemos esta lista solo para poder crear los botones de navegación.
$tablas_permitidas = [
    'v_empleados_completos', 
    'v_departamentos_completos', 
    'v_equipos_asignados',
    'computadora', 
    'nobreak', 
    'telefono', 
    'impresora'
];

// Tomamos el valor 'tabla' de la URL. Si no existe, usamos el primero de la lista.
// A propósito, no lo estamos "limpiando" ni validando.
$tabla_seleccionada = isset($_GET['tabla']) ? $_GET['tabla'] : $tablas_permitidas[0];

// --- SE HA QUITADO LA VALIDACIÓN DE SEGURIDAD ---
// La comprobación 'if (!in_array(...))' se eliminó para simplificar.


// --- CONEXIÓN A LA BASE DE DATOS (VERSIÓN SIMPLE) ---
$db_connection = null;
$mensaje_conexion = "";

// Para un ejemplo simple, ponemos los datos de conexión directamente aquí.
// (Recuerda cambiarlos por los de tu base de datos)
$host = "localhost"; // O la IP de tu servidor
$dbname = "mi_base_de_datos";
$user = "mi_usuario";
$pass = "mi_contraseña";

$connection_string = "host={$host} dbname={$dbname} user={$user} password={$pass} sslmode=require";
$db_connection = pg_connect($connection_string);

if ($db_connection) {
    $mensaje_conexion = "✅ Conexión a la base de datos exitosa.";
} else {
    $error = pg_last_error();
    // Usamos htmlspecialchars aquí solo para mostrar el error correctamente
    $mensaje_conexion = "❌ Error: No se pudo conectar. Detalle: " . htmlspecialchars($error);
}

// --- OBTENCIÓN DE DATOS (VERSIÓN SIMPLE Y VULNERABLE) ---
$columnas = [];
$filas = [];

if ($db_connection) {
    
    // ¡ADVERTENCIA DE SEGURIDAD!
    // Esta es la parte vulnerable, como en tu ejemplo.
    // Tomamos el valor de la URL ($tabla_seleccionada)
    // y lo "pegamos" directamente en la consulta SQL.
    // Esto es inseguro y permite una "Inyección SQL".
    $query = "SELECT * FROM {$tabla_seleccionada}";
    
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
    } else {
         // Si la consulta falla (por ejemplo, por una inyección), mostramos el error
         $mensaje_conexion .= " | ❌ Error al consultar la tabla: " . htmlspecialchars(pg_last_error($db_connection));
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De La Rosca</title>
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
        .table-wrapper { overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>De La Rosca</h1>
            <p>Selecciona una vista o tabla para ver sus registros.</p>
        </div>
        <div class="status <?php
            // Esta lógica de clases se mantiene igual
            $status_class = 'warning';
            if ($db_connection && strpos($mensaje_conexion, 'Error') === false) $status_class = 'success';
            if (strpos($mensaje_conexion, 'Error') !== false) $status_class = 'error';
            echo $status_class;
        ?>">
            <?php echo $mensaje_conexion; ?>
        </div>
        
        <nav>
            <?php foreach ($tablas_permitidas as $tabla): ?>
                <a href="?tabla=<?php echo $tabla; ?>" class="<?php if ($tabla === $tabla_seleccionada) echo 'active'; ?>">
                    <?php 
                        // Limpiamos el nombre para mostrarlo (ej. v_empleados_completos -> Empleados Completos)
                        $nombre_limpio = str_replace(['v_', '_'], ['', ' '], $tabla);
                        echo ucwords($nombre_limpio); 
                    ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="table-wrapper">
            <h2>Registros de: "<?php echo ucwords(str_replace(['v_', '_'], ['', ' '], $tabla_seleccionada)); ?>"</h2>
            
            <?php if ($db_connection && !empty($filas)): ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columnas as $columna): ?>
                                <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $columna))); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filas as $fila): ?>
                            <tr>
                                <?php foreach ($fila as $valor): ?>
                                    <td><?php echo htmlspecialchars($valor ?? ''); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($db_connection && empty($filas)): ?>
                <p>La vista/tabla "<?php echo htmlspecialchars($tabla_seleccionada); ?>" no tiene registros.</p>
            <?php else: ?>
                <p>No se pueden mostrar los datos. Verifica la conexión a la base de datos y la consulta.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
