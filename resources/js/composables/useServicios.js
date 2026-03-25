import { ref, reactive, computed } from 'vue';

export function useServicios(token) {
    const mostrarModalServicios    = ref(false);
    const mostrarModalFormServicio = ref(false);
    const negocioActualServicios= ref(null);
    const servicios                = ref([]);
    const busquedaServicio         = ref('');
    const esEditarServicio         = ref(false);
    const minutosDisponibles       = ref([]);
    const limiteServicios      = ref(null);
    const totalServicios           = ref(0);
    const porcentajeAnticipo       = ref(0);

    // -> AGREGAMOS 'anticipo' AL FORMULARIO
    const formularioServicio = reactive({ id: '', nombre: '', precio: '', anticipo: '', duracion_minutos: '' });
    const erroresServicio    = reactive({});

    // ── COMPUTED ──────────────────────────────────────────────────────────────

    const serviciosFiltrados = computed(() => {
        if (!busquedaServicio.value) return servicios.value;
        return servicios.value.filter(s =>
            s.nombre.toLowerCase().includes(busquedaServicio.value.toLowerCase())
        );
    });

    // -> CALCULO MATEMÁTICO DEL MÍNIMO REQUERIDO
    const anticipoMinimo = computed(() => {
        if (!formularioServicio.precio || isNaN(formularioServicio.precio)) return 0;
        return (Number(formularioServicio.precio) * Number(porcentajeAnticipo.value)) / 100;
    });

    // ── HELPERS ───────────────────────────────────────────────────────────────

    const authHeaders = () => ({
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    });

    const limpiarErroresServicio = () =>
        Object.keys(erroresServicio).forEach(k => delete erroresServicio[k]);

    // -> FUNCIÓN PARA AUTO-RELLENAR EL ANTICIPO
    const setAnticipoMinimo = () => {
        formularioServicio.anticipo = anticipoMinimo.value.toFixed(2);
    };

    // ── API ───────────────────────────────────────────────────────────────────

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
                porcentajeAnticipo.value = data.porcentaje_anticipo; // -> GUARDAMOS EL % DEL PLAN
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

        // -> VALIDACIONES DE REGLA DE NEGOCIO (ANTICIPO)
        const precioTotal = Number(formularioServicio.precio);
        const anticipoIngresado = Number(formularioServicio.anticipo);

        if (formularioServicio.anticipo === '' || anticipoIngresado < anticipoMinimo.value) {
            erroresServicio.anticipo = `El anticipo mínimo debe ser de $${anticipoMinimo.value.toFixed(2)}`;
            esValido = false;
        }
        if (anticipoIngresado > precioTotal) {
            erroresServicio.anticipo = 'El anticipo no puede ser mayor al costo total del servicio.';
            esValido = false;
        }

        if (!esValido) return;

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
                    anticipo:          formularioServicio.anticipo, // -> ENVIAMOS PAYLOAD
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

    // ── MODALES ───────────────────────────────────────────────────────────────

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
            formularioServicio.anticipo         = servicio.anticipo; // -> MAPEAR AL EDITAR
            formularioServicio.duracion_minutos = servicio.duracion_minutos;
        } else {
            esEditarServicio.value              = false;
            formularioServicio.id               = '';
            formularioServicio.nombre           = '';
            formularioServicio.precio           = '';
            formularioServicio.anticipo         = '';
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
        porcentajeAnticipo, anticipoMinimo, // -> EXPORTAMOS A LA UI
        serviciosFiltrados,
        abrirServicios, abrirFormularioServicio, cerrarFormularioServicio,
        cargarServiciosNegocio, guardarServicio, eliminarServicio, setAnticipoMinimo
    };
}
