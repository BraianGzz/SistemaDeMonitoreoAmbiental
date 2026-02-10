<?php
/**
 * ARCHIVO: obtener_datos.php
 * 
 * OBJETIVO: Proporcionar una API para consultar los datos
 * almacenados en la base de datos. Usado por la interfaz web.
 * 
 * METODO: GET
 * PARÁMETROS OPCIONALES:
 * - limit: Numero de registros a devolver (por defecto: 10)
 * 
 * EJEMPLO DE USO:
 * obtener_datos.php?limit=20
 */


//CONFIGURACION DE CABECERAS HTTP
header('Content-Type: application/json');
// La respuesta sera JSON


header('Access-Control-Allow-Origin: *');
// Permitir peticiones desde cualquier origen (para la interfaz web)


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistemamonitoreo";

$conn = new mysqli($servername, $username, $password, $dbname);


// Verificar conexion
if ($conn->connect_error) {
    // Enviar error en formato JSON si falla la conexion
    die(json_encode([
        "status" => "error", 
        "message" => "Conexion fallida: " . $conn->connect_error
    ]));
}


//OBTENER PARAMETROS DE LA URL
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;


// Validar que el limite sea un numero razonable
if ($limit <= 0 || $limit > 1000) {
    $limit = 10; // Valor por defecto si el limite es invalido
}


$sql = "SELECT * FROM sensor ORDER BY fecha DESC LIMIT ?";

// Usar consulta preparada para seguridad
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit); // "i" significa que el parametro es integer
$stmt->execute();


// Obtener resultado de la consulta
$result = $stmt->get_result();


//PROCESAR RESULTADOS
$datos = []; // Array para almacenar los datos


// Recorrer cada fila del resultado
while ($row = $result->fetch_assoc()) {
    // Convertir cada fila a un array asociativo con tipos correctos
    $datos[] = [
        'id' => $row['id'],
        'temperatura' => floatval($row['temperatura']),     // Convertir a decimal
        'humedad' => floatval($row['humedad']),             // Convertir a decimal
        'gas' => intval($row['gas']),                       // Convertir a entero
        'distancia' => floatval($row['distancia']),         // Convertir a decimal
        'nivel_alerta' => intval($row['alerta']),     // Convertir a entero
        'fecha' => $row['fecha']                            // Mantener como string
    ];
}


//ENVIAR RESPUESTA JSON
echo json_encode([
    "status" => "success",
    "count" => count($datos),    // Numero de registros devueltos
    "data" => $datos            // Array con los datos
]);

$stmt->close();
$conn->close();


// ==================== EJEMPLO DE RESPUESTA JSON ====================
/*
{
    "status": "success",
    "count": 5,
    "data": [
        {
            "id": 15,
            "temperatura": 25.5,
            "humedad": 60,
            "gas": 250,
            "distancia": 30.5,
            "nivel_alerta": 0,
            "fecha": "2024-01-15 10:30:45"
        },
        {
            "id": 14,
            "temperatura": 25.3,
            "humedad": 59,
            "gas": 245,
            "distancia": 31.2,
            "nivel_alerta": 0,
            "fecha": "2024-01-15 10:29:45"
        }
    ]
}
*/
?>
