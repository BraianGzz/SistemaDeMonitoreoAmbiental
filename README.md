# Sistema de Monitoreo Ambiental IoT

Sistema de monitoreo en tiempo real con Arduino y visualización web.  
Web informativa del proyecto completo: https://braiangzz.github.io/SistemaDeMonitoreoAmbiental/

---

## Requisitos

- XAMPP (Apache + MySQL)  
- Arduino IDE  
- Navegador web moderno  
- Arduino UNO + Shield Ethernet W5100 + Sensores  

---

## Instalación

### 1. Instalar XAMPP

1. Descarga XAMPP  
2. Instala en `C:\xampp\`  
3. Abre el Panel de Control de XAMPP  
4. Inicia **Apache** y **MySQL**  

---

### 2. Crear Base de Datos

1. Abre: http://localhost/phpmyadmin  
2. Crear base de datos  
3. Nombre: `sistemamonitoreo`  
4. Crear tabla: `sensor`

```sql
CREATE TABLE sensor (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    temperatura FLOAT NULL,
    humedad FLOAT NULL,
    gas INT NULL,
    distancia FLOAT NULL,
    alerta INT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

### 3. Archivos Web

1. Ve a: `C:\xampp\htdocs\`  
2. Crea la carpeta: `sistema`  
3. Copia todos los archivos dentro:
   - index.html  
   - styles.css  
   - script.js  
   - guardar_datos.php  
   - obtener_datos.php  
   - actualizar_registro.php  
   - eliminar_registro.php  

---

### 4. Verificar Web

1. Abre: http://localhost/sistema/index.html  
2. Deberías ver la interfaz del sistema  

---

### 5. Configurar Arduino (archivo `.ino` en carpeta **sistemacompleto**)

A. Instalar librerías  
B. Configurar IP del servidor  
C. Configurar IP del Arduino  
D. Cargar código  

---

### 6. Conectar y Probar

Sistema listo.
