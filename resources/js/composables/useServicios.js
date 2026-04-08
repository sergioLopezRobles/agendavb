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
    const minimoAnticipoPlan       = ref(0);
    const minimoCancelacionPlan    = ref(0); // -> NUEVO: Minutos de cancelación del plan

    // -> AGREGAMOS 'notas' y 'minutos_cancelacion' AL FORMULARIO
    const formularioServicio = reactive({ id: '', nombre: '', precio: '',
        anticipo: '', tarjeta: '0', duracion_minutos: '', notas: '', minutos_cancelacion: '' });

    const erroresServicio    = reactive({});

    const serviciosFiltrados = computed(() => {
        if (!busquedaServicio.value) return servicios.value;
        return servicios.value.filter(s =>
            s.nombre.toLowerCase().includes(busquedaServicio.value.toLowerCase())
        );
    });

    const anticipoMinimo = computed(() => {
        return Number(minimoAnticipoPlan.value);
    });

    // -> COMPUTED PARA SABER SI EL PLAN PERMITE NOTAS (Plan 2 y 3)
    const permiteNotas = computed(() => {
        return negocioActualServicios.value?.id_plan == 2 || negocioActualServicios.value?.id_plan == 3;
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
                servicios.value             = data.servicios;
                minutosDisponibles.value    = data.minutos_permitidos;
                limiteServicios.value       = data.limite_servicios;
                totalServicios.value        = data.total_servicios;
                minimoAnticipoPlan.value    = data.minimo_anticipo;
                minimoCancelacionPlan.value = data.minimo_cancelacion; // -> GUARDAMOS LOS MINUTOS DE CANCELACIÓN
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

        if (formularioServicio.tarjeta === '1') {
            if (anticipoIngresado < anticipoMinimo.value) {
                erroresServicio.anticipo = `Al cobrar con tarjeta, el anticipo mínimo debe ser de $${anticipoMinimo.value.toFixed(2)}`;
                esValido = false;
            }
        }

        if (anticipoIngresado > precioTotal) {
            erroresServicio.anticipo = 'El anticipo no puede ser mayor al costo total del servicio.';
            esValido = false;
        }

        // -> VALIDACIÓN ESTRICTA: MINUTOS DE CANCELACIÓN POR PLAN
        // Si la BD devuelve null (Avanzado), minCancelacion será 0.
        const minCancelacion = Number(minimoCancelacionPlan.value) || 0;
        const inputCancelacion = formularioServicio.minutos_cancelacion === '' ? -1 : Number(formularioServicio.minutos_cancelacion);

        if (minCancelacion > 0) {
            // Planes Básico (60) y Medio (15)
            if (inputCancelacion < minCancelacion) {
                erroresServicio.minutos_cancelacion = `Tu plan requiere un mínimo de ${minCancelacion} minutos de anticipación.`;
                esValido = false;
            }
        } else {
            // Plan Avanzado (null / 0) - Sin límite, pero no puede ser negativo
            if (inputCancelacion < 0) {
                formularioServicio.minutos_cancelacion = 0;
            }
        }

        if (!esValido) return;

        let anticipoFinal = anticipoIngresado > 0 ? anticipoIngresado : null;

        try {
            const endpoint = esEditarServicio.value ? `/api/servicios/${formularioServicio.id}` : `/api/servicios`;
            const method   = esEditarServicio.value ? 'PUT' : 'POST';

            const res  = await fetch(endpoint, {
                method,
                headers: authHeaders(),
                body: JSON.stringify({
                    id_negocio:          negocioActualServicios.value.id,
                    nombre:              formularioServicio.nombre,
                    precio:              formularioServicio.precio,
                    anticipo:            anticipoFinal,
                    tarjeta:             formularioServicio.tarjeta,
                    duracion_minutos:    formularioServicio.duracion_minutos,
                    notas:               permiteNotas.value ? formularioServicio.notas : null,
                    minutos_cancelacion: formularioServicio.minutos_cancelacion === '' ? 0 : formularioServicio.minutos_cancelacion
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
            formularioServicio.tarjeta          = servicio.tarjeta || '0';
            formularioServicio.duracion_minutos = servicio.duracion_minutos;
            formularioServicio.notas            = servicio.notas || '';
            formularioServicio.minutos_cancelacion = servicio.minutos_cancelacion; // -> CARGAR
        } else {
            esEditarServicio.value              = false;
            formularioServicio.id               = '';
            formularioServicio.nombre           = '';
            formularioServicio.precio           = '';
            formularioServicio.tarjeta          = '1';
            formularioServicio.anticipo         = anticipoMinimo.value.toFixed(2);
            formularioServicio.duracion_minutos = '';
            formularioServicio.notas            = '';
            formularioServicio.minutos_cancelacion = minimoCancelacionPlan.value || 0; // -> VALOR POR DEFECTO DEL PLAN
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
        minimoAnticipoPlan, anticipoMinimo, minimoCancelacionPlan,
        serviciosFiltrados, permiteNotas,
        abrirServicios, abrirFormularioServicio, cerrarFormularioServicio,
        cargarServiciosNegocio, guardarServicio, eliminarServicio, setAnticipoMinimo
    };
}
