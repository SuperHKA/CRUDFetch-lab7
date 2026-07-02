document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('productoForm');
    const btnActualizar = document.getElementById('btnActualizar');
    const btnBuscar = document.getElementById('btnBuscar');
    const btnBuscarTexto = document.getElementById('btnBuscarTexto');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const tablaProductos = document.getElementById('tablaProductos');
    const contadorProductos = document.getElementById('contadorProductos');

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        enviarDatos('Guardar');
    });

    btnActualizar.addEventListener('click', (e) => {
        e.preventDefault();
        enviarDatos('Modificar');
    });

    btnBuscar.addEventListener('click', (e) => {
        e.preventDefault();
        BuscarProducto();
    });

    btnBuscarTexto.addEventListener('click', (e) => {
        e.preventDefault();
        BuscarProducto();
    });

    btnLimpiar.addEventListener('click', limpiarFormulario);

    document.getElementById('busqueda').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            BuscarProducto();
        }
    });

    ListarProductos();

    // Centraliza el envio de acciones al backend mediante FormData y Fetch API.
    async function enviarDatos(accion) {
        limpiarErrores();

        switch (accion) {
            case 'Guardar':
            case 'Modificar':
                if (!validarFormulario(accion)) {
                    mostrarAlerta('warning', 'Revise el formulario', 'Complete correctamente los campos obligatorios.');
                    return;
                }
                break;

            case 'Buscar':
            case 'Listar':
                break;

            default:
                mostrarAlerta('error', 'Acción no válida', 'La acción indicada no está disponible.');
                return;
        }

        const datos = new FormData(form);
        datos.append('Accion', accion);

        if (accion === 'Buscar') {
            datos.append('busqueda', document.getElementById('busqueda').value.trim() || document.getElementById('codigo').value.trim());
        }

        try {
            const response = await fetch('registrar.php', {
                method: 'POST',
                body: datos,
            });

            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }

            const resultado = await response.json();
            procesarRespuesta(resultado, accion);
        } catch (error) {
            mostrarAlerta('error', 'Error de comunicación', error.message);
        }
    }

    function procesarRespuesta(resultado, accion) {
        if (!resultado.success) {
            pintarErrores(resultado.errors || {});
            mostrarAlerta('error', 'Solicitud no completada', resultado.message);

            if (accion === 'Buscar') {
                renderTabla(resultado.productos || []);
            }
            return;
        }

        switch (accion) {
            case 'Guardar':
                mostrarAlerta('success', 'Producto registrado', resultado.message);
                limpiarFormulario();
                ListarProductos();
                break;

            case 'Modificar':
                mostrarAlerta('success', 'Producto actualizado', resultado.message);
                limpiarFormulario();
                ListarProductos();
                break;

            case 'Buscar':
                if (resultado.producto) {
                    cargarProducto(resultado.producto);
                }
                renderTabla(resultado.productos || []);
                mostrarAlerta('success', 'Búsqueda realizada', resultado.message);
                break;

            case 'Listar':
                renderTabla(resultado.productos || []);
                break;

            default:
                mostrarAlerta('info', 'Respuesta recibida', resultado.message);
        }
    }

    function ListarProductos() {
        const datos = new FormData();
        datos.append('Accion', 'Listar');

        fetch('registrar.php', {
            method: 'POST',
            body: datos,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Error HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((resultado) => procesarRespuesta(resultado, 'Listar'))
            .catch((error) => {
                tablaProductos.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">No se pudo cargar el listado.</td></tr>';
                mostrarAlerta('error', 'Error de listado', error.message);
            });
    }

    function BuscarProducto() {
        const busqueda = document.getElementById('busqueda').value.trim();
        const codigo = document.getElementById('codigo').value.trim();

        if (busqueda === '' && codigo === '') {
            mostrarAlerta('warning', 'Dato requerido', 'Ingrese un código, ID o nombre para buscar.');
            return;
        }

        enviarDatos('Buscar');
    }

    function validarFormulario(accion) {
        const errores = {};
        const codigo = document.getElementById('codigo').value.trim();
        const producto = document.getElementById('producto').value.trim();
        const precio = document.getElementById('precio').value;
        const cantidad = document.getElementById('cantidad').value;
        const id = document.getElementById('id').value;

        if (codigo === '') {
            errores.codigo = 'El código es obligatorio.';
        }

        if (producto === '') {
            errores.producto = 'El producto es obligatorio.';
        }

        if (precio === '' || Number.isNaN(Number(precio))) {
            errores.precio = 'El precio es obligatorio y debe ser numérico.';
        } else if (Number(precio) < 0) {
            errores.precio = 'El precio no debe ser negativo.';
        }

        if (cantidad === '' || Number.isNaN(Number(cantidad))) {
            errores.cantidad = 'La cantidad es obligatoria y debe ser numérica.';
        } else if (Number(cantidad) < 0) {
            errores.cantidad = 'La cantidad no debe ser negativa.';
        } else if (accion === 'Guardar' && Number(cantidad) < 1) {
            errores.cantidad = 'Para registrar, la cantidad mínima es 1.';
        }

        if (accion === 'Modificar' && id === '') {
            errores.id = 'Debe buscar o seleccionar un producto antes de actualizar.';
        }

        pintarErrores(errores);
        return Object.keys(errores).length === 0;
    }

    function cargarProducto(producto) {
        document.getElementById('id').value = producto.id;
        document.getElementById('codigo').value = producto.codigo;
        document.getElementById('producto').value = producto.producto;
        document.getElementById('precio').value = producto.precio;
        document.getElementById('cantidad').value = producto.cantidad;
        document.getElementById('cantidad').min = 0;
        btnActualizar.disabled = false;
    }

    function limpiarFormulario() {
        form.reset();
        document.getElementById('id').value = '';
        document.getElementById('cantidad').min = 1;
        btnActualizar.disabled = true;
        limpiarErrores();
        document.getElementById('codigo').focus();
    }

    function renderTabla(productos) {
        contadorProductos.textContent = `${productos.length} ${productos.length === 1 ? 'producto' : 'productos'}`;

        if (productos.length === 0) {
            tablaProductos.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay productos para mostrar.</td></tr>';
            return;
        }

        tablaProductos.innerHTML = productos.map((producto) => `
            <tr>
                <td>${producto.id}</td>
                <td><span class="code-pill">${escaparHtml(producto.codigo)}</span></td>
                <td>${escaparHtml(producto.producto)}</td>
                <td class="text-end">$${Number(producto.precio).toFixed(2)}</td>
                <td class="text-end">${producto.cantidad}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-success btn-seleccionar" type="button" data-producto="${encodeURIComponent(JSON.stringify(producto))}">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.btn-seleccionar').forEach((button) => {
            button.addEventListener('click', () => {
                cargarProducto(JSON.parse(decodeURIComponent(button.dataset.producto)));
                mostrarAlerta('info', 'Producto cargado', 'Los datos están listos para modificar.');
            });
        });
    }

    function pintarErrores(errores) {
        Object.entries(errores).forEach(([campo, mensaje]) => {
            const input = document.getElementById(campo);
            const feedback = document.getElementById(`error-${campo}`);

            if (input) {
                input.classList.add('is-invalid');
            }

            if (feedback) {
                feedback.textContent = mensaje;
            }
        });
    }

    function limpiarErrores() {
        form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach((feedback) => {
            feedback.textContent = '';
        });
    }

    function mostrarAlerta(icon, title, text) {
        Swal.fire({
            icon,
            title,
            text,
            confirmButtonColor: '#2563eb',
            timer: icon === 'success' ? 1800 : undefined,
            timerProgressBar: icon === 'success',
        });
    }

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    }
});
