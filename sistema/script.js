/* 
    SCRIPT JAVASCRIPT PARA FUNCIONALIDAD DINAMICA
    - Cargar datos actuales
    - Editar y borrar registros
    - Actualizar dashboard
    - Manejar actualizaciones automaticas
*/

/**
 * FUNCIoN: cargarDatosRecientes()
 * OBJETIVO: Obtener el ultimo registro de la base de datos
 * y actualizar los valores en el dashboard
 */
async function cargarDatosRecientes() {
    try {
        console.log("Cargando datos recientes...");
        
        // Hacer peticion GET al archivo PHP que obtiene datos
        const response = await fetch('obtener_datos.php?limit=1');
        
        // Convertir respuesta a JSON
        const data = await response.json();
        
        // Verificar si la respuesta fue exitosa y hay datos
        if (data.status === 'success' && data.data.length > 0) {
            const ultimoDato = data.data[0];
            
            // ACTUALIZAR VALORES EN EL DASHBOARD
            document.getElementById('temp-value').textContent = ultimoDato.temperatura;
            document.getElementById('hum-value').textContent = ultimoDato.humedad;
            document.getElementById('gas-value').textContent = ultimoDato.gas;
            document.getElementById('dist-value').textContent = ultimoDato.distancia;
            
            
            // ACTUALIZAR FECHA DE ULTIMA ACTUALIZACION
            const fecha = new Date(ultimoDato.fecha);
            document.getElementById('last-update').textContent = 
                `Ultima actualizacion: ${fecha.toLocaleString()}`;
                
            console.log("Datos actualizados correctamente");
        }
    } catch (error) {
        // Manejar errores de conexion o procesamiento
        console.error('Error cargando datos recientes:', error);
    }
}

/**
 * FUNCION: cargarHistorial()
 * OBJETIVO: Obtener multiples registros historicos y
 * llenar la tabla con los datos
 */
async function cargarHistorial() {
    try {
        console.log("Cargando historial de datos...");
        
        // Obtener ultimos 20 registros
        const response = await fetch('obtener_datos.php?limit=20');
        const data = await response.json();
        
        if (data.status === 'success') {
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = ''; // Limpiar tabla antes de llenar
            
            // RECORRER CADA REGISTRO Y CREAR FILA EN LA TABLA
            data.data.forEach(item => {
                const fila = document.createElement('tr');
                
                let estado = 'Normal';
                let claseEstado = 'status-normal';
                
                if (item.nivel_alerta == 2) {
                    estado = 'Peligro';
                    claseEstado = 'status-danger';
                } else if (item.nivel_alerta == 1) {
                    estado = 'Advertencia';
                    claseEstado = 'status-warning';
                }
                
                const fecha = new Date(item.fecha);
                
                //CONTENIDO DE LA FILA
                fila.innerHTML = `
                    <td>${fecha.toLocaleString()}</td>
                    <td>${item.temperatura}</td>
                    <td>${item.humedad}</td>
                    <td>${item.gas}</td>
                    <td>${item.distancia}</td>
                    <td><span class="status-indicator ${claseEstado}"></span>${estado}</td>
                    <td><button class="btn-edit" onclick="editarRegistro(${item.id})">
                    <i class="fa-solid fa-pen-to-square"></i></button>
                    <button class="btn-delete" onclick="eliminarRegistro(${item.id})">
                    <i class="fa-solid fa-trash"></i></button></td>
                `;
                
                //AGREGAR FILA
                tbody.appendChild(fila);
            });
            
            console.log(`${data.data.length} registros cargados en la tabla`);
        }
    } catch (error) {
        console.error('Error cargando historial:', error);
    }
}


/**
* FUNCION: editarRegistro()
* OBJETIVO: Abrir modal y cargar datos del registro a editar
*/
function editarRegistro(id) {
console.log("Editando registro ID:", id);

// Obtener datos del registro especifico
fetch(`obtener_datos.php?limit=1000`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Buscar el registro con el ID especifico
            const registro = data.data.find(item => item.id === id);
            
            if (registro) {
                document.getElementById('edit-id').value = registro.id;
                document.getElementById('edit-temperatura').value = registro.temperatura;
                document.getElementById('edit-humedad').value = registro.humedad;
                document.getElementById('edit-gas').value = registro.gas;
                document.getElementById('edit-distancia').value = registro.distancia;
                document.getElementById('edit-alerta').value = registro.nivel_alerta;
                
                // Mostrar el modal
                document.getElementById('modal-editar').style.display = 'block';
            }
        }
    })
    .catch(error => console.error('Error cargando registro:', error));
}

/**
* FUNCION: cerrarModal()
* OBJETIVO: Ocultar el modal de edicion
*/
function cerrarModal() {
document.getElementById('modal-editar').style.display = 'none';
}

/**
* FUNCION: guardarCambios()
* OBJETIVO: Enviar los datos actualizados al servidor
*/
function guardarCambios() {
const id = document.getElementById('edit-id').value;
const temperatura = document.getElementById('edit-temperatura').value;
const humedad = document.getElementById('edit-humedad').value;
const gas = document.getElementById('edit-gas').value;
const distancia = document.getElementById('edit-distancia').value;
const alerta = document.getElementById('edit-alerta').value;

const datos = {
    id: id,
    temperatura: temperatura,
    humedad: humedad,
    gas: gas,
    distancia: distancia,
    alerta: alerta
};

console.log("Guardando cambios:", datos);

// Enviar datos al servidor
fetch('actualizar_registro.php', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(datos)
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        alert('Registro actualizado correctamente');
        cerrarModal();
        cargarDatosRecientes();
        cargarHistorial();
    } else {
        alert('Error: ' + data.message);
    }
})
.catch(error => {
    console.error('Error guardando cambios:', error);
    alert('Error al guardar los cambios');
});
}

/**
* FUNCION: eliminarRegistro()
* OBJETIVO: Eliminar un registro de la base de datos
*/
function eliminarRegistro(id) {
console.log("Eliminando registro ID:", id);

if (confirm('¿Estas seguro de eliminar este registro?')) {
    fetch('eliminar_registro.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Registro eliminado correctamente');
            cargarDatosRecientes();
            cargarHistorial();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error eliminando registro:', error);
        alert('Error al eliminar el registro');
    });
}
}

// Cerrar modal al hacer clic fuera de el
window.onclick = function(event) {
const modal = document.getElementById('modal-editar');
if (event.target == modal) {
    cerrarModal();
}
}

//INICIALIZACION AL CARGAR LA PAGINA

document.addEventListener('DOMContentLoaded', function() {
    console.log("Pagina cargada - Iniciando sistema...");
    
    cargarDatosRecientes();
    cargarHistorial();
    
    // CONFIGURAR ACTUALIZACION AUTOMATICA CADA 5 SEGUNDOS
    setInterval(() => {
        cargarDatosRecientes();
        cargarHistorial();
    }, 5000); 
    
    console.log("Sistema iniciado - Actualizando cada 5 segundos");
});
