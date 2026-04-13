import { ref, reactive, computed, nextTick } from 'vue';
import QRCode from 'qrcode';

export function useServicios(token) {
    const mostrarModalServicios    = ref(false);
    const mostrarModalFormServicio = ref(false);
    const mostrarModalVistaPrevia  = ref(false);
    const servicioEnVistaPrevia    = ref(null);
    const fechaActual              = ref('');
    const horaActual               = ref('');
    const canvasQRPreview          = ref(null);
    const negocioActualServicios   = ref(null);
    const servicios                = ref([]);
    const busquedaServicio         = ref('');
    const esEditarServicio         = ref(false);
    const minutosDisponibles       = ref([]);
    const limiteServicios          = ref(null);
    const totalServicios           = ref(0);
    const minimoAnticipoPlan       = ref(0);
    const minimoCancelacionPlan    = ref(0);


    const ejecutarCancelacion = () => {
        // 1. Obtener el límite del plan (si es null en Avanzado, lo tratamos como 0)
        const limiteMinutos = Number(minimoCancelacionPlan.value) || 0;

        if (limiteMinutos === 0) {
            window.$toast.show('Plan Avanzado: Cancelación permitida en cualquier momento.', 'success', 4000);
            return;
        }

        window.$toast.show(`La cancelacion debe ser realizada dentro de ${limiteMinutos} min antes de la hora.`, 'info', 5000);
    };

    const formularioServicio = reactive({
        id: '', nombre: '', precio: '', anticipo: '',
        tarjeta: '0', duracion_minutos: '', notas: ''
    });

    const erroresServicio = reactive({});

    const serviciosFiltrados = computed(() => {
        if (!busquedaServicio.value) return servicios.value;
        return servicios.value.filter(s =>
            s.nombre.toLowerCase().includes(busquedaServicio.value.toLowerCase())
        );
    });

    const anticipoMinimo = computed(() => Number(minimoAnticipoPlan.value));

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
            const data = await res.json(); // <-- Nace data

            if (data.valid) {
                servicios.value             = data.servicios;
                minutosDisponibles.value    = data.minutos_permitidos;
                limiteServicios.value       = data.limite_servicios;
                totalServicios.value        = data.total_servicios;
                minimoAnticipoPlan.value    = data.minimo_anticipo;
                minimoCancelacionPlan.value = data.minimo_cancelacion; // <-- Se asigna de forma segura dentro del bloque
            }
        } catch (error) {
            console.error("Error al cargar servicios:", error);
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
                window.$toast.show(`Tu plan permite un máximo de ${limiteServicios.value} servicios. Mejora tu plan.`, 'warning', 4000);
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
        } else {
            esEditarServicio.value              = false;
            formularioServicio.id               = '';
            formularioServicio.nombre           = '';
            formularioServicio.precio           = '';
            formularioServicio.tarjeta          = '1';
            formularioServicio.anticipo         = anticipoMinimo.value.toFixed(2);
            formularioServicio.duracion_minutos = '';
            formularioServicio.notas            = '';
        }

        mostrarModalServicios.value    = false;
        mostrarModalFormServicio.value = true;
    };

    const cerrarFormularioServicio = () => {
        mostrarModalFormServicio.value = false;
        mostrarModalServicios.value    = true;
    };

    // ── LÓGICA DE LA VISTA PREVIA DEL TICKET ──
    const abrirVistaPrevia = async (servicio) => {
        servicioEnVistaPrevia.value = servicio;

        const fechaCreacion = servicio.created_at ? new Date(servicio.created_at) : new Date();
        const opcionesFecha = { year: 'numeric', month: 'long', day: 'numeric' };

        fechaActual.value = fechaCreacion.toLocaleDateString('es-MX', opcionesFecha);
        horaActual.value = fechaCreacion.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

        mostrarModalServicios.value = false;
        mostrarModalVistaPrevia.value = true;

        await nextTick();
        generarQRVistaPrevia();
    };

    const cerrarVistaPrevia = () => {
        mostrarModalVistaPrevia.value = false;
        mostrarModalServicios.value = true;
        servicioEnVistaPrevia.value = null;
    };

    const generarQRVistaPrevia = async () => {
        if (!canvasQRPreview.value || !negocioActualServicios.value) return;

        const canvas = canvasQRPreview.value;
        const ctx = canvas.getContext('2d');
        const url = `https://${negocioActualServicios.value.slug}`;

        const size = 150;
        const paddingBottom = 25;

        canvas.width = size;
        canvas.height = size + paddingBottom;

        try {
            const qrDataUrl = await QRCode.toDataURL(url, {
                errorCorrectionLevel: 'H',
                margin: 1,
                width: size,
                color: { dark: '#000000', light: '#FFFFFF' }
            });

            const qrImg = new Image();
            qrImg.onload = () => {
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(qrImg, 0, 0);

                const logoSize = 30;
                const centerX = size / 2;
                const centerY = size / 2;

                ctx.beginPath();
                ctx.arc(centerX, centerY, logoSize / 2 + 4, 0, 2 * Math.PI);
                ctx.fillStyle = '#FFFFFF';
                ctx.fill();

                ctx.fillStyle = '#000000';
                ctx.font = 'bold 16px Arial, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('VB', centerX, centerY + 2);

                ctx.fillStyle = '#333333';
                ctx.font = 'bold 11px "Helvetica Neue", Helvetica, Arial, sans-serif';
                ctx.textAlign = 'center';

                let nombre = negocioActualServicios.value.nombre;
                if (nombre.length > 25) nombre = nombre.substring(0, 22) + '...';

                ctx.fillText(nombre, size / 2, size + 15);
            };
            qrImg.src = qrDataUrl;

        } catch (err) {
            console.error('Error generando QR Vista Previa', err);
        }
    };

    const tipoTelefono = (id_tipo) => {
        if (id_tipo == 1) return 'WhatsApp';
        if (id_tipo == 2) return 'Fijo';
        if (id_tipo == 3) return 'Telegram';
        return 'Contacto';
    };

    return {
        mostrarModalServicios, mostrarModalFormServicio, negocioActualServicios,
        servicios, busquedaServicio, esEditarServicio, minutosDisponibles,
        limiteServicios, totalServicios, formularioServicio, erroresServicio,
        minimoAnticipoPlan, anticipoMinimo, serviciosFiltrados, permiteNotas,
        mostrarModalVistaPrevia, servicioEnVistaPrevia, fechaActual, horaActual, canvasQRPreview,
        abrirServicios, abrirFormularioServicio, cerrarFormularioServicio,
        cargarServiciosNegocio, guardarServicio, eliminarServicio, setAnticipoMinimo,
        abrirVistaPrevia, cerrarVistaPrevia, tipoTelefono, ejecutarCancelacion, minimoCancelacionPlan,
    };
}
