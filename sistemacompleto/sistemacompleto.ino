#include <SPI.h>                 // Para comunicacion Ethernet (SPI)
#include <Ethernet.h>            // Para funcionalidad Ethernet
#include <DHT.h>                 // Para sensor DHT11

#include <Wire.h>                // Para comunicacion I2C del LCD
#include <U8x8lib.h>

U8X8_SSD1306_128X64_NONAME_HW_I2C display(U8X8_PIN_NONE);


byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };  // Direccion MAC unica
IPAddress ip(192, 168, 10, 150);                       // definir IP estatica de Arduino
char server[] = "192.168.10.100";                      // IP del servidor XAMPP, tu ip(PC)
EthernetClient client;                                // Cliente para conexiones salientes
EthernetServer webServer(80);  // Servidor web local en puerto 80



#define DHTPIN 2                  // Pin digital para DHT11
#define DHTTYPE DHT11             // Tipo de sensor DHT
DHT dht(DHTPIN, DHTTYPE);         // Objeto sensor DHT


const int pinSensorGas = A0;      // Pin analogico para sensor MQ-2
const int pinTrigger = 9;         // Pin Trigger sensor ultrasonico
const int pinEcho = 8;           // Pin Echo sensor ultrasonico
const int pinBuzzer = 3;          // Pin digital para buzzer activo



unsigned long lastConnectionTime = 0;        // ultimo tiempo de conexion al servidor
const unsigned long postingInterval = 10000; // Intervalo de envio (10 segundos)
unsigned long lastBuzzerTime = 0;            // Control de timing del buzzer
bool buzzerState = false;                    // Estado actual del buzzer
int alertLevel = 0;                          // Nivel de alerta: 0=normal, 1=adv, 2=peligro


void setup() {
  Serial.begin(9600);
  Serial.println(F("Iniciando Sistema Integrado de Monitoreo"));

  webServer.begin();
Serial.println(F("Servidor web local iniciado"));

  
  dht.begin();                              // Iniciar sensor DHT11
  pinMode(pinTrigger, OUTPUT);              // Configurar Trigger como salida
  pinMode(pinEcho, INPUT);                  // Configurar Echo como entrada
  pinMode(pinBuzzer, OUTPUT);               // Configurar buzzer como salida
  digitalWrite(pinBuzzer, LOW);             // Asegurar buzzer apagado al inicio
  
 // INICIALIZAR PANTALLA OLED
  display.begin();
display.setPowerSave(0);
display.setFont(u8x8_font_chroma48medium8_r);
display.clear();

  // Mensajes de prueba
  display.setCursor(0, 0);
  display.print("OLED OK");
  
  
  Serial.println(F("Configurando conexión Ethernet..."));
  if (Ethernet.begin(mac) == 0) {
    // Si DHCP falla, usar IP estatica configurada
    Serial.println(F("DHCP falló, usando IP estática"));
    Ethernet.begin(mac, ip);
  } else {
    Serial.println(F("DHCP configurado exitosamente"));
  }
  
  // MOSTRAR INFORMACIÓN DE CONEXIÓN
  Serial.print(F(" IP del sistema: "));
  Serial.println(Ethernet.localIP());
  Serial.print(F("Servidor destino: "));
  Serial.println(server);
  
  delay(2000);  // Pausa para ver mensajes iniciales
  Serial.println(F("Sistema completamente inicializado"));
}


void loop() {
  float humedad = dht.readHumidity();           // Leer humedad (%)
  float temperatura = dht.readTemperature();    // Leer temperatura (°C)
  int valorGas = analogRead(pinSensorGas);      // Leer nivel de gas (0-1023)
  float distancia = medirDistancia();           // Medir distancia (cm)
  
  alertLevel = determinarNivelAlerta(valorGas, distancia);
  
  mostrarOLED(temperatura, humedad, valorGas, distancia, alertLevel);
  
  controlarBuzzer(alertLevel);
  
  //enviar cada 10 segundos para no saturar
  if (millis() - lastConnectionTime > postingInterval) {
    enviarDatosServidor(temperatura, humedad, valorGas, distancia, alertLevel);
    lastConnectionTime = millis();  // Actualizar tiempo de ultimo envio
  }
  
  atenderClientesWeb();
  
  delay(1000);  // Esperar 1 segundo entre ciclos de lectura
}


float medirDistancia() {
  // Secuencia estandar para activar medicion ultrasonica
  digitalWrite(pinTrigger, LOW);           // Asegurar Trigger en LOW
  delayMicroseconds(2);                    // Pequeña pausa
  digitalWrite(pinTrigger, HIGH);          // Enviar pulso de 10μs
  delayMicroseconds(10);
  digitalWrite(pinTrigger, LOW);
  
  // Medir el tiempo que tarda en llegar el eco
  long duracion = pulseIn(pinEcho, HIGH); 
  
  // Calcular distancia: (tiempo × velocidad sonido) / 2
  return duracion * 0.034 / 2;             // Devolver distancia en cm
}


int determinarNivelAlerta(int gas, float distancia) {
  if (gas > 400 || distancia < 5) {
    return 2;  // PELIGRO: Gas muy alto u objeto muy cerca
  } else if (gas > 250 || distancia < 15) {
    return 1;  // ADVERTENCIA: Gas moderado u objeto cercano
  } else {
    return 0;  // NORMAL: Todo dentro de parametros seguros
  }
}


//SCL-A5,SDA-A4
void mostrarOLED(float temp, float hum, int gas, float dist, int alerta) {
  display.clear();

  display.setCursor(0, 0);
  display.print("T:");
  display.print(temp, 1);
  display.print("C H:");
  display.print(hum, 0);
  display.print("%");

  display.setCursor(0, 2);
  display.print("Gas:");
  display.print(gas);

  display.setCursor(0, 4);
  display.print("Dist:");
  display.print(dist, 1);
  display.print("cm");

  display.setCursor(12, 0);
  if (alerta == 2) display.print("PEL");
  else if (alerta == 1) display.print("ADV");
}


void controlarBuzzer(int nivelAlerta) {
  unsigned long currentTime = millis();  // Obtener tiempo actual
  
  switch (nivelAlerta) {
    case 0: // NORMAL - Sin sonido
      digitalWrite(pinBuzzer, LOW);
      break;
      
    case 1: // ADVERTENCIA - Pitido lento (cada 2 segundos)
      if (currentTime - lastBuzzerTime > 2000) {
        buzzerState = !buzzerState;              // Alternar estado
        digitalWrite(pinBuzzer, buzzerState ? HIGH : LOW);
        if (buzzerState) {
          Serial.println(F("Alerta de advertencia: Pitido"));
        }
        lastBuzzerTime = currentTime;            // Actualizar tiempo
      }
      break;
      
    case 2: // PELIGRO - Pitido rapido continuo
      if (currentTime - lastBuzzerTime > 300) {
        buzzerState = !buzzerState;              // Alternar estado rapidamente
        digitalWrite(pinBuzzer, buzzerState ? HIGH : LOW);
        lastBuzzerTime = currentTime;
        
        if (buzzerState) {
          Serial.println(F("ALERTA PELIGRO: Pitido rápido"));
        }
      }
      break;
  }
}


void enviarDatosServidor(float temp, float hum, int gas, float dist, int alerta) {
  // Intentar conectar al servidor web (puerto 80)
  if (client.connect(server, 80)) {
    Serial.println(F(" Conectando al servidor web..."));
    
    // Crear cadena con los datos en formato URL-encoded
    String datos = "temperatura=" + String(temp) + 
                   "&humedad=" + String(hum) + 
                   "&gas=" + String(gas) + 
                   "&distancia=" + String(dist) +
                   "&alerta=" + String(alerta);
    
    // Enviar solicitud HTTP POST
    client.println("POST /sistema/guardar_datos.php HTTP/1.1");
    client.println("Host: 192.168.10.102");
    client.println("Content-Type: application/x-www-form-urlencoded");
    client.print("Content-Length: ");
    client.println(datos.length());  // Longitud del contenido
    client.println();                // Línea en blanco (fin de headers)
    client.println(datos);           // Enviar los datos
    
    Serial.println(" Datos enviados: " + datos);
    
  } else {
    Serial.println(F("Error de conexión al servidor web"));
  }
  
  // Esperar y leer respuesta del servidor
  unsigned long timeout = millis();
  while (client.available() == 0) {
    if (millis() - timeout > 5000) {
      Serial.println(F("Timeout de conexión - servidor no responde"));
      client.stop();
      return;
    }
  }
  
  // Leer y mostrar respuesta del servidor
  Serial.print(F("Respuesta del servidor: "));
  while (client.available()) {
    String line = client.readStringUntil('\r');
    Serial.print(line);
  }
  
  client.stop();  // Cerrar conexion
  Serial.println(F("\n Conexión cerrada"));
}


void atenderClientesWeb() {
  // Escuchar clientes conectados al servidor web local
  EthernetClient client = webServer.available();

  
  if (client) {
    Serial.println(F("Nuevo cliente web conectado localmente"));
    
    boolean currentLineIsBlank = true;
    
    while (client.connected()) {
      if (client.available()) {
        char c = client.read();
        
        // Detectar fin de headers HTTP
        if (c == '\n' && currentLineIsBlank) {
          // Enviar pagina web al cliente
          client.println("HTTP/1.1 200 OK");
          client.println("Content-Type: text/html");
          client.println("Connection: close");
          client.println();
          
          // Pagina HTML simple
          client.println("<!DOCTYPE HTML>");
          client.println("<html>");
          client.println("<head><title>Sistema Monitoreo</title></head>");
          client.println("<body>");
          client.println("<h1>Sistema de Monitoreo Local</h1>");
          client.println("<p>Esta es la interfaz local del sistema.</p>");
          client.println("<p>Los datos se envían automáticamente al servidor central.</p>");
          client.println("</body>");
          client.println("</html>");
          break;
        }
        
        if (c == '\n') {
          currentLineIsBlank = true;
        } else if (c != '\r') {
          currentLineIsBlank = false;
        }
      }
    }
    
    delay(1);
    client.stop();
    Serial.println(F(" Cliente web desconectado"));
  }
}
