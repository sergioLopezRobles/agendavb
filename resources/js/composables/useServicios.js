import { ref, reactive, computed } from 'vue';

export function useServicios(token) {
    const mostrarModalServicios    = ref(false);
    const mostrarModalFormServicio = ref(false);
    const negocioActualServicios   = ref(null);
    const servicios                = ref([]);
    const busquedaServicio         = ref('');
    const esEditarServicio         = ref(false);
    const minutosDisponibles       = ref([]);
    const limiteServicios          = ref(null);
    const totalServicios           = ref(0);
    const minimoAnticipoPlan       = ref(0); // -> AHORA ES UN VALOR FIJO (10, 25, 50)

    // -> AGREGAMOS 'tarjeta' AL FORMULARIO
    const formularioServicio = reactive({ id: '', nombre: '', precio: '', anticipo: '', tarjeta: '0', duracion_minutos: '' });
    const erroresServicio    = reactive({});

    const serviciosFiltrados = computed(() => {
        if (!busquedaServicio.value) return servicios.value;
        return servicios.value.filter(s =>
            s.nombre.toLowerCase().includes(busquedaServicio.value.toLowerCase())
        );
    });

    // -> EL ANTICIPO MÍNIMO AHORA ES DIRECTAMENTE EL VALOR DEL PLAN
    const anticipoMinimo = computed(() => {
        return Number(minimoAnticipoPlan.value);
    });

    const authHeaders = () => ({
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    });

    const limpiarErroresServicio = () =>
        Object.keys(erroresServicio).forEach(k => delete erroresServicio[k]);

    const setAnticipoMinimo = () => {
        formularioServicio.anticipo = anticipoMinimo.value.toFixed(2);
    };

    const cargarServiciosNegocio = async () => {
        try {
            const res  = await fetch(`/api/negocios/${negocioActualServicios.value.id}/servicios`, {
                method: 'GET',
                headers: authHeaders()
            });
            const data = await res.json();

            if (data.valid) {
                servicios.value          = data.servicios;
                minutosDisponibles.value = data.minutos_permitidos;
                limiteServicios.value    = data.limite_servicios;
                totalServicios.value     = data.total_servicios;
                minimoAnticipoPlan.value = data.minimo_anticipo; // -> GUARDAMOS EL MÍNIMO FIJO
            }
        } catch {
            window.$toast.show('Error al cargar servicios', 'danger', 3000);
        }
    };

    const guardarServicio = async () => {
        limpiarErroresServicio();
        let esValido = true;

        if (!formularioServicio.nombre.trim()) {
            erroresServicio.nombre = 'El nombre es obligatorio.';
            esValido = false;
        }
        if (!formularioServicio.precio || formularioServicio.precio <= 0) {
            erroresServicio.precio = 'Ingresa un precio válido.';
            esValido = false;
        }
        if (!formularioServicio.duracion_minutos || formularioServicio.duracion_minutos <= 0) {
            erroresServicio.duracion = 'Ingresa una duración válida.';
            esValido = false;
        }

        // -> VALIDACIONES DE ANTICIPO
        const precioTotal = Number(formularioServicio.precio);
        const anticipoIngresado = Number(formularioServicio.anticipo) || 0;

        // Si el cobro por tarjeta está activo, EXIGIMOS el mínimo (no se permite 0 ni vacío)
        if (formularioServicio.tarjeta === '1') {
            if (anticipoIngresado < anticipoMinimo.value) {
                erroresServicio.anticipo = `Al cobrar con tarjeta, el anticipo mínimo debe ser de $${anticipoMinimo.value.toFixed(2)}`;
                esValido = false;
            }
        }

        // No puede ser mayor al precio del servicio
        if (anticipoIngresado > precioTotal) {
            erroresServicio.anticipo = 'El anticipo no puede ser mayor al costo total del servicio.';
            esValido = false;
        }

        if (!esValido) return;

        // -> PREPARAR EL PAYLOAD (Convertir a nulo si es Efectivo y dejaron 0)
        let anticipoFinal = anticipoIngresado > 0 ? anticipoIngresado : null;

        try {
            const endpoint = esEditarServicio.value ? `/api/servicios/${formularioServicio.id}` : `/api/servicios`;
            const method   = esEditarServicio.value ? 'PUT' : 'POST';

            const res  = await fetch(endpoint, {
                method,
                headers: authHeaders(),
                body: JSON.stringify({
                    id_negocio:        negocioActualServicios.value.id,
                    nombre:            formularioServicio.nombre,
                    precio:            formularioServicio.precio,
                    anticipo:          anticipoFinal, // Se va nulo si es 0
                    tarjeta:           formularioServicio.tarjeta, // '1' o '0'
                    duracion_minutos:  formularioServicio.duracion_minutos
                })
            });
            const data = await res.json();

            if (data.valid) {
                window.$toast.show(data.message, 'success', 3000);
                cerrarFormularioServicio();
                await cargarServiciosNegocio();
            } else {
                window.$toast.show(data.message, 'warning', 3000);
            }
        } catch {
            window.$toast.show('Error al guardar el servicio', 'danger', 3000);
        }
    };

    const eliminarServicio = async (id) => {
        if (!confirm('¿Estás seguro de que deseas eliminar este servicio?')) return;
        try {
            const res  = await fetch(`/api/servicios/${id}`, {
                method: 'DELETE',
                headers: authHeaders()
            });
            const data = await res.json();

            if (data.valid) {
                window.$toast.show('Servicio eliminado', 'success', 3000);
                await cargarServiciosNegocio();
            }
        } catch {
            window.$toast.show('Error al eliminar el servicio', 'danger', 3000);
        }
    };

    const abrirServicios = (negocio) => {
        negocioActualServicios.value = negocio;
        busquedaServicio.value       = '';
        cargarServiciosNegocio();
        mostrarModalServicios.value  = true;
    };

    const abrirFormularioServicio = (servicio = null) => {
        limpiarErroresServicio();

        if (!servicio) {
            if (limiteServicios.value !== null && totalServicios.value >= Number(limiteServicios.value)) {
                window.$toast.show(
                    `Tu plan permite un máximo de ${limiteServicios.value} servicios. Mejora tu plan.`,
                    'warning',
                    4000
                );
                return;
            }
        }

        if (servicio) {
            esEditarServicio.value              = true;
            formularioServicio.id               = servicio.id;
            formularioServicio.nombre           = servicio.nombre;
            formularioServicio.precio           = servicio.precio;
            formularioServicio.anticipo         = servicio.anticipo || '';
            formularioServicio.tarjeta          = servicio.tarjeta || '0'; // -> MAPEAMOS TARJETA
            formularioServicio.duracion_minutos = servicio.duracion_minutos;
        } else {
            esEditarServicio.value              = false;
            formularioServicio.id               = '';
            formularioServicio.nombre           = '';
            formularioServicio.precio           = '';
            formularioServicio.anticipo         = '';
            formularioServicio.tarjeta          = '0'; // Por defecto Efectivo
            formularioServicio.duracion_minutos = '';
        }

        mostrarModalServicios.value    = false;
        mostrarModalFormServicio.value = true;
    };

    const cerrarFormularioServicio = () => {
        mostrarModalFormServicio.value = false;
        mostrarModalServicios.value    = true;
    };

    return {
        mostrarModalServicios, mostrarModalFormServicio, negocioActualServicios,
        servicios, busquedaServicio, esEditarServicio, minutosDisponibles,
        limiteServicios, totalServicios, formularioServicio, erroresServicio,
        minimoAnticipoPlan, anticipoMinimo,
        serviciosFiltrados,
        abrirServicios, abrirFormularioServicio, cerrarFormularioServicio,
        cargarServiciosNegocio, guardarServicio, eliminarServicio, setAnticipoMinimo
    };
}
