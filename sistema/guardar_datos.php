<?php
/**
 * ARCHIVO: guardar_datos.php
 * 
 * OBJETIVO: Recibir datos enviados por Arduino via HTTP POST
 * y almacenarlos en la base de datos MySQL.
 * 
 * FLUJO DE DATOS:
 * Arduino → HTTP POST → este archivo PHP → Base de datos MySQL
 * 
 * METODO: POST
 * FORMATO DATOS: application/x-www-form-urlencoded
 * 
 * PARÁMETROS ESPERADOS:
 * - temperatura: Valor decimal (ej: 25.5)
 * - humedad: Valor decimal (ej: 60.0)
 * - gas: Valor entero (ej: 250)
 * - distancia: Valor decimal (ej: 15.5)
 * - alerta: Valor entero (0=normal, 1=advertencia, 2=peligro)
 */


// CONFIGURACIÓN DE CABECERAS HTTP
// Estas lineas configuran como el servidor responde al cliente (Arduino)


header('Content-Type: application/json');
// Indica que la respuesta sera en formato JSON


header('Access-Control-Allow-Origin: *');
// Permite CORS (Cross-Origin Resource Sharing)
// El * significa que cualquier dominio puede hacer peticiones a este script


header('Access-Control-Allow-Methods: POST');
// Especifica que solo se permite el metodo POST


header('Access-Control-Allow-Headers: Content-Type');
// Permite la cabecera Content-Type en las peticiones


$servername = "localhost";    // Servidor de base de datos (usualment localhost)
$username = "root";           // Usuario de MySQL (por defecto 'root' en XAMPP)
$password = "";               // Contraseña de MySQL (vacía por defecto en XAMPP)
$dbname = "sistemamonitoreo";          // Nombre de la base de datos que creamos

$conn = new mysqli($servername, $username, $password, $dbname);


// Verificar si hubo error en la conexion
if ($conn->connect_error) {
    // Si hay error, enviar respuesta JSON de error y terminar ejecucion
    die(json_encode([
        "status" => "error", 
        "message" => "Conexión fallida: " . $conn->connect_error
    ]));
}


// Verificar que la peticion sea mediante metodo POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // OBTENER Y VALIDAR DATOS DEL POST
    // isset() verifica si el parametro existe
    // floatval() convierte a numero decimal, intval() a entero
    
    $temperatura = isset($_POST['temperatura']) ? floatval($_POST['temperatura']) : null;
    $humedad = isset($_POST['humedad']) ? floatval($_POST['humedad']) : null;
    $gas = isset($_POST['gas']) ? intval($_POST['gas']) : null;
    $distancia = isset($_POST['distancia']) ? floatval($_POST['distancia']) : null;
    $alerta = isset($_POST['alerta']) ? intval($_POST['alerta']) : 0;
    
    // Verificar que todos los datos necesarios estan presentes
    if ($temperatura === null || $humedad === null || $gas === null || $distancia === null) {
        // Si falta algun dato, enviar error y terminar
        echo json_encode([
            "status" => "error", 
            "message" => "Datos incompletos. Se esperaban: temperatura, humedad, gas, distancia"
        ]);
        exit; 
    }
    

    // Usar consultas preparadas para prevenir inyección SQL 
    // Preparar la consulta SQL con placeholders (?)
    $stmt = $conn->prepare("INSERT INTO sensor (temperatura, humedad, gas, distancia, alerta) VALUES (?, ?, ?, ?, ?)");
    
    // Vincular parametros a los placeholders
    $stmt->bind_param("ddidi", $temperatura, $humedad, $gas, $distancia, $alerta);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // EXITO: Datos insertados correctamente
        echo json_encode([
            "status" => "success", 
            "message" => "Datos guardados correctamente",
            "id_insertado" => $stmt->insert_id  // ID autoincremental generado
        ]);
    } else {
        // ERROR: Fallo en la insercion
        echo json_encode([
            "status" => "error", 
            "message" => "Error al guardar datos: " . $stmt->error
        ]);
    }
    
    $stmt->close();
    
} else {
    //MANEJO DE METODOS NO PERMITIDOS
    echo json_encode([
        "status" => "error", 
        "message" => "Metodo no permitido. Use POST."
    ]);
}

$conn->close();


//EJEMPLO DE RESPUESTAS JSON
/*
RESPUESTA DE EXITO:
{
    "status": "success",
    "message": "Datos guardados correctamente",
    "id_insertado": 15
}


RESPUESTA DE ERROR:
{
    "status": "error",
    "message": "Datos incompletos. Se esperaban: temperatura, humedad, gas, distancia, alerta"
}
*/
?>
