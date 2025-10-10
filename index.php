<?php

// --- CONFIGURACIÓN ---
// Lista blanca de tablas permitidas para evitar accesos no deseados.
$tablas_permitidas = ['empleados', 'computadoras', 'impresoras'];

// Determina la tabla a mostrar. Si no se especifica ninguna, se usa la primera de la lista.
// La función htmlspecialchars se usa por seguridad, para prevenir ataques XSS.
$tabla_seleccionada = isset($_GET['tabla']) ? htmlspecialchars($_GET['tabla']) : $tablas_permitidas[0];

// Verifica si la tabla seleccionada está en nuestra lista blanca.
if (!in_array($tabla_seleccionada, $tablas_permitidas)) {
    die("Error: Tabla no válida.");
}

// --- CONEXIÓN A LA BASE DE DATOS ---
// Render nos dará la URL de conexión a través de una variable de entorno.
// Si la variable no existe (ej. en nuestro entorno local), usamos un valor nulo.
$connection_string = getenv('DATABASE_URL');
$db_connection = null;
$mensaje_conexion = "";

if ($connection_string) {
    // pg_connect es la función de PHP para conectarse a PostgreSQL.
    $db_connection = pg_connect($connection_string);

    if ($db_connection) {
        $mensaje_conexion = "✅ Conexión a la base de datos exitosa.";
    } else {
        $mensaje_conexion = "❌ Error: No se pudo conectar a la base de datos.";
    }
} else {
    $mensaje_conexion = "⚠️ Advertencia: La variable de entorno DATABASE_URL no está configurada.";
}


// --- OBTENCIÓN DE DATOS ---
$columnas = [];
$filas = [];

if ($db_connection) {
    // Preparamos la consulta SQL de forma segura para obtener todos los datos de la tabla seleccionada.
    // Usar pg_escape_identifier es una buena práctica para asegurar el nombre de la tabla.
    $query = 'SELECT * FROM ' . pg_escape_identifier($db_connection, $tabla_seleccionada);
    
    // Ejecutamos la consulta.
    $resultado = pg_query($db_connection, $query);

    if ($resultado) {
        // Obtenemos los nombres de las columnas (encabezados de la tabla).
        $num_columnas = pg_num_fields($resultado);
        for ($i = 0; $i < $num_columnas; $i++) {
            $columnas[] = pg_field_name($resultado, $i);
        }

        // Obtenemos todas las filas del resultado.
        $filas = pg_fetch_all($resultado);
        // Si no hay filas, pg_fetch_all devuelve false. Lo convertimos a un array vacío.
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
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f9; color: #333; }
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        h1 { color: #2c3e50; }
        .status { padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .status.success { background-color: #e8f5e9; color: #2e7d32; }
        .status.error { background-color: #ffebee; color: #c62828; }
        .status.warning { background-color: #fff3e0; color: #ef6c00; }
        nav a { margin-right: 15px; text-decoration: none; color: #3498db; font-weight: bold; font-size: 1.1em; }
        nav a.active { color: #2980b9; border-bottom: 2px solid #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #ecf0f1; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Visor de Datos de la Empresa</h1>
            <p>Selecciona una tabla para ver sus registros.</p>
        </div>

        <?php
            $status_class = 'warning';
            if ($db_connection) $status_class = 'success';
            if (!$db_connection && $connection_string) $status_class = 'error';
        ?>
        <div class="status <?php echo $status_class; ?>">
            <?php echo $mensaje_conexion; ?>
        </div>

        <nav>
            <?php foreach ($tablas_permitidas as $tabla): ?>
                <a href="?tabla=<?php echo $tabla; ?>" class="<?php if ($tabla === $tabla_seleccionada) echo 'active'; ?>">
                    <?php echo ucfirst($tabla); // Pone la primera letra en mayúscula ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <h2>Registros de la tabla: "<?php echo $tabla_seleccionada; ?>"</h2>
        <?php if ($db_connection && !empty($filas)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columnas as $columna): ?>
                            <th><?php echo htmlspecialchars($columna); ?></th>
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

</body>
</html>