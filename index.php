<?php
/**
 * Este script PHP se conecta a una base de datos PostgreSQL (usando variables de entorno),
 * consulta una tabla o vista de una lista predefinida y segura, y muestra los resultados
 * en una tabla HTML. También maneja el estado de la conexión y los errores de consulta.
 *
 */


/**
 * $tablas_permitidas
 * Almacena una "lista blanca" (whitelist) de las únicas tablas y vistas
 * que este script tiene permitido consultar. Esto es una medida de seguridad
 * crucial para prevenir que un usuario intente acceder a otras tablas
 * (ej. 'usuarios') modificando la URL.
 */
$tablas_permitidas = [
    'v_empleados_completos',
    'v_departamentos_completos',
    'v_equipos_asignados',
    'computadora',
    'nobreak',
    'telefono',
    'impresora'
];

// --- MANEJO DE ENTRADA DEL USUARIO ---

/**
 * $tabla_seleccionada
 * Determina qué tabla/vista se debe consultar.
 * 1. isset($_GET['tabla']): Comprueba si el parámetro 'tabla' existe en la URL (ej. index.php?tabla=computadora).
 * 2. htmlspecialchars($_GET['tabla']): Si existe, obtiene su valor y lo "limpia" para prevenir
 * ataques XSS (Cross-Site Scripting). Esta función convierte caracteres especiales
 * como < y > en sus entidades HTML (&lt; y &gt;).
 * 3. $tablas_permitidas[0]: Si el parámetro 'tabla' no existe, usa la primera tabla
 * de la lista blanca como valor por defecto.
 */
$tabla_seleccionada = isset($_GET['tabla']) ? htmlspecialchars($_GET['tabla']) : $tablas_permitidas[0];

/**
 * Verificación de seguridad.
 * Comprueba si el valor de $tabla_seleccionada (ya sea de la URL o el
 * valor por defecto) realmente existe en nuestra lista blanca.
 *
 * - in_array(): Busca un valor dentro de un array.
 * - die(): Si no se encuentra, detiene la ejecución del script
 * inmediATAMENTE y muestra un mensaje de error. Esto previene
 * cualquier intento de consulta a una tabla no autorizada.
 */
if (!in_array($tabla_seleccionada, $tablas_permitidas)) {
    die("Error: Tabla no válida.");
}

// Se inicializan las variables que usaremos en esta sección.
$db_connection = null; // Almacenará el objeto de conexión
$mensaje_conexion = ""; // Almacenará un mensaje para el usuario

/**
 * Obtención de credenciales desde variables de entorno.
 * Lee las variables de configuración del sistema en lugar de escribirlas
 * directamente en el código.
 */
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Verificamos si alguna de las variables de entorno necesarias está vacía.
if (empty($host) || empty($dbname) || empty($user) || empty($pass)) {
    $mensaje_conexion = "⚠️ Advertencia: Faltan variables de entorno (DB_HOST, DB_NAME, DB_USER, DB_PASS).";
} else {
    // Si tenemos todas las variables, construimos el "string de conexión" de PostgreSQL.
    // 'sslmode=require' es comúnmente necesario para conexiones a bases de datos en la nube (como Render/Neon).
    $connection_string = "host={$host} dbname={$dbname} user={$user} password={$pass} sslmode=require";

    /**
     * Intenta la conexión a la base de datos.
     * pg_connect(): Establece una conexión con la base de datos PostgreSQL.
     * Devuelve un recurso de conexión si tiene éxito, o `false` si falla.
     */
    $db_connection = pg_connect($connection_string);

    if ($db_connection) {
        $mensaje_conexion = "✅ Conexión a la base de datos exitosa.";
    } else {
        /**
         * pg_last_error(): Obtiene el último mensaje de error de la conexión
         * de PostgreSQL.
         */
        $error = pg_last_error();
        // Usamos htmlspecialchars() de nuevo para mostrar el error de forma segura en HTML.
        $mensaje_conexion = "❌ Error: No se pudo conectar. Detalle: " . htmlspecialchars($error);
    }
}

// --- OBTENCIÓN DE DATOS ---

// Inicializamos los arrays que contendrán la estructura y los datos de la tabla.
$columnas = []; // Para los nombres de las columnas (ej. 'id', 'nombre')
$filas = [];    // Para las filas de datos (un array de arrays)

// Solo intentamos consultar la base de datos SI la conexión fue exitosa.
if ($db_connection) {

    $query = 'SELECT * FROM ' . pg_escape_identifier($db_connection, $tabla_seleccionada);

    /**
     * Ejecuta la consulta.
     * pg_query(): Envía la consulta a la base de datos conectada.
     * Devuelve un recurso de resultado si tiene éxito, o `false` si falla.
     */
    $resultado = pg_query($db_connection, $query);

    // Verificamos si la consulta se ejecutó correctamente.
    if ($resultado) {
        /**
         * Obtención de los nombres de las columnas.
         * pg_num_fields(): Devuelve el número de columnas en el resultado.
         */
        $num_columnas = pg_num_fields($resultado);
        for ($i = 0; $i < $num_columnas; $i++) {
            /**
             * pg_field_name(): Obtiene el nombre de una columna específica
             * por su índice (posición).
             */
            $columnas[] = pg_field_name($resultado, $i);
        }

        /**
         * Obtención de todas las filas de datos.
         * pg_fetch_all(): Obtiene TODAS las filas del resultado y las
         * devuelve como un array de arrays asociativos.
         */
        $filas = pg_fetch_all($resultado);

        // Si la consulta no devuelve filas (tabla vacía), pg_fetch_all() devuelve `false`.
        // Nos aseguramos de que $filas sea un array vacío en ese caso para
        // evitar errores en el HTML más adelante.
        if ($filas === false) {
            $filas = [];
        }
    } else {
        // Si pg_query() falló, añadimos el error al mensaje de conexión.
        $mensaje_conexion .= " | ❌ Error al consultar la vista/tabla: " . htmlspecialchars(pg_last_error($db_connection));
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
        .table-wrapper { overflow-x: auto; } /* Permite scroll horizontal en tablas muy anchas */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>De La Rosca</h1>
            <p>Selecciona una vista o tabla para ver sus registros.</p>
        </div>

        <div class="status <?php
            // Lógica para asignar una clase CSS basada en el mensaje de conexión
            $status_class = 'warning'; // Clase por defecto (si faltan variables de entorno)
            
            // strpos(): Busca la posición de una cadena dentro de otra.
            // Si $db_connection existe Y no encontramos la palabra 'Error' en el mensaje...
            if ($db_connection && strpos($mensaje_conexion, 'Error') === false) {
                $status_class = 'success';
            }
            // Si encontramos la palabra 'Error' en CUALQUIER parte del mensaje...
            if (strpos($mensaje_conexion, 'Error') !== false) {
                $status_class = 'error';
            }
            // Imprime la clase CSS decidida (ej. 'success', 'error', 'warning')
            echo $status_class;
        ?>">
            <?php echo $mensaje_conexion; ?>
        </div>

        <nav>
            <?php
            /**
             * Bucle para crear los botones de navegación.
             * Itera sobre el array de la lista blanca $tablas_permitidas.
             * Por cada tabla, crea un enlace <a>.
             */
            ?>
            <?php foreach ($tablas_permitidas as $tabla): ?>
                <a href="?tabla=<?php echo $tabla; ?>" class="<?php
                    // Añade la clase 'active' si esta tabla es la que
                    // está actualmente seleccionada.
                    if ($tabla === $tabla_seleccionada) echo 'active';
                ?>">
                    <?php
                        /**
                         * "Limpieza" del nombre para mostrarlo al usuario.
                         * str_replace(): Reemplaza partes de un string.
                         * - Quita el prefijo 'v_'
                         * - Reemplaza guiones bajos '_' con espacios ' '
                         * ucwords(): Convierte la primera letra de cada palabra a mayúscula.
                         * Ej. "v_empleados_completos" -> "Empleados Completos"
                         * Ej. "computadora" -> "Computadora"
                         */
                        $nombre_limpio = str_replace(['v_', '_'], ['', ' '], $tabla);
                        echo ucwords($nombre_limpio);
                    ?>
                </a>
            <?php endforeach; // Fin del bucle de navegación ?>
        </nav>

        <div class="table-wrapper">
            <h2>Registros de: "<?php echo ucwords(str_replace(['v_', '_'], ['', ' '], $tabla_seleccionada)); ?>"</h2>

            <?php
            /**
             * Lógica condicional para mostrar la tabla de datos.
             *
             * Condición 1: Si la conexión fue exitosa Y el array $filas NO está vacío
             * (es decir, tenemos datos que mostrar).
             */
            ?>
            <?php if ($db_connection && !empty($filas)): ?>
                <table>
                    <thead>
                        <tr>
                            <?php
                            /**
                             * Bucle para crear las celdas de cabecera (<th>).
                             * Itera sobre el array $columnas que obtuvimos de la BD.
                             */
                            ?>
                            <?php foreach ($columnas as $columna): ?>
                                <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $columna))); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        /**
                         * Bucle anidado para crear las filas de datos (<tr> y <td>).
                         * Bucle externo: Itera sobre $filas. Cada $fila es un array
                         * (ej. ['id' => 1, 'nombre' => 'Ana']).
                         */
                        ?>
                        <?php foreach ($filas as $fila): ?>
                            <tr>
                                <?php
                                /**
                                 * Bucle interno: Itera sobre los valores de la $fila actual.
                                 * $valor será '1', luego 'Ana', etc.
                                 */
                                ?>
                                <?php foreach ($fila as $valor): ?>
                                    <td><?php echo htmlspecialchars($valor ?? ''); ?></td>
                                <?php endforeach; // Fin del bucle interno (columnas) ?>
                            </tr>
                        <?php endforeach; // Fin del bucle externo (filas) ?>
                    </tbody>
                </table>
            <?php
            /**
             * Condición 2: Si la conexión fue exitosa PERO $filas está vacío.
             * (La tabla existe pero no tiene registros).
             */
            ?>
            <?php elseif ($db_connection && empty($filas)): ?>
                <p>La vista/tabla "<?php echo $tabla_seleccionada; ?>" no tiene registros.</p>
            <?php
            /**
             * Condición 3 (else): Si todo lo demás falló.
             * (Esto generalmente significa que $db_connection fue `false`).
             */
            ?>
            <?php else: ?>
                <p>No se pueden mostrar los datos. Verifica la conexión a la base de datos y la consulta.</p>
            <?php endif; // Fin del bloque condicional de la tabla ?>
        </div>
    </div>
</body>
</html>
