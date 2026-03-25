import { ref, reactive, computed } from 'vue';

export function useNegocios() {
    const token = localStorage.getItem('token');

    const negocios          = ref([]);
    const limiteNegocios    = ref(1);
    const usuarioLoggeado   = ref(null);
    const planAdquirido     = ref(null);
    const planesDisponibles = ref([]);

    const mostrarModalUpgrade  = ref(false);
    const mostrarModalEdicion  = ref(false);
    const mostrarModalCreacion = ref(false);

    const negocioEditando = ref({ id: '', nombre: '', email: '', slug: '', telefonos: [] });
    const nuevoNegocio    = ref({ nombre: '', email: '', slug: '', telefonos: [{ id_tipo: 1, numero: '' }] });

    const errores = reactive({});

    const horariosDisponibles = [
        "00:00 - 01:00", "01:00 - 02:00", "02:00 - 03:00", "03:00 - 04:00",
        "04:00 - 05:00", "05:00 - 06:00", "06:00 - 07:00", "07:00 - 08:00",
        "08:00 - 09:00", "09:00 - 10:00", "10:00 - 11:00", "11:00 - 12:00",
        "12:00 - 13:00", "13:00 - 14:00", "14:00 - 15:00", "15:00 - 16:00",
        "16:00 - 17:00", "17:00 - 18:00", "18:00 - 19:00", "19:00 - 20:00",
        "20:00 - 21:00", "21:00 - 22:00", "22:00 - 23:00", "23:00 - 24:00"
    ];

    const horarios       = ref([]);
    const horariosEdicion = ref([]);

    // ── COMPUTED ──────────────────────────────────────────────────────────────

    const limiteTelefonos = computed(() => {
        if (planAdquirido.value?.id == 1) return 3;
        if (planAdquirido.value?.id == 2) return 5;
        if (planAdquirido.value?.id == 3) return 10;
        return 3;
    });

    // ── HELPERS ───────────────────────────────────────────────────────────────

    const limpiarErrores = () => Object.keys(errores).forEach(k => delete errores[k]);

    const authHeaders = () => ({
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    });

    // ── HORARIOS ──────────────────────────────────────────────────────────────

    const toggleHorario = (hora) => {
        const i = horarios.value.indexOf(hora);
        i === -1 ? horarios.value.push(hora) : horarios.value.splice(i, 1);
        horarios.value.sort();
        if (errores.horarios) delete errores.horarios;
    };

    const toggleHorarioEdicion = (hora) => {
        const i = horariosEdicion.value.indexOf(hora);
        i === -1 ? horariosEdicion.value.push(hora) : horariosEdicion.value.splice(i, 1);
        horariosEdicion.value.sort();
        if (errores.edicion_horarios) delete errores.edicion_horarios;
    };

    // ── MULTI-TELÉFONO ────────────────────────────────────────────────────────

    const agregarTelefonoNuevo = () => {
        if (nuevoNegocio.value.telefonos.length < limiteTelefonos.value) {
            nuevoNegocio.value.telefonos.push({ id_tipo: 1, numero: '' });
        } else {
            window.$toast.show(`Tu plan permite un máximo de ${limiteTelefonos.value} teléfonos.`, 'warning', 3000);
        }
    };

    const quitarTelefonoNuevo = (index) => {
        if (nuevoNegocio.value.telefonos.length > 1) nuevoNegocio.value.telefonos.splice(index, 1);
    };

    const agregarTelefonoEdicion = () => {
        if (negocioEditando.value.telefonos.length < limiteTelefonos.value) {
            negocioEditando.value.telefonos.push({ id_tipo: 1, numero: '' });
        } else {
            window.$toast.show(`Tu plan permite un máximo de ${limiteTelefonos.value} teléfonos.`, 'warning', 3000);
        }
    };

    const quitarTelefonoEdicion = (index) => {
        if (negocioEditando.value.telefonos.length > 1) negocioEditando.value.telefonos.splice(index, 1);
    };

    // ── API ───────────────────────────────────────────────────────────────────

    const cargarPlanes = async () => {
        try {
            const res = await fetch('/api/planes');
            planesDisponibles.value = await res.json();
        } catch (e) {
            console.error('Error al cargar planes', e);
        }
    };

    const cargarNegocios = async () => {
        try {
            const res  = await fetch('/api/mis-negocios', { method: 'GET', headers: authHeaders() });
            const data = await res.json();

            if (data.valid) {
                negocios.value       = data.negocios;
                usuarioLoggeado.value = data.usuarioLoggeado;
                planAdquirido.value  = data.planAdquirido;

                const limitesPorPlan   = { 1: 1, 2: 3, 3: 6 };
                limiteNegocios.value   = limitesPorPlan[data.planAdquirido.id] || 1;
            }
        } catch {
            window.$toast.show('Error al cargar la tabla de negocios', 'danger', 4000);
        }
    };

    const intentarAccesoPremium = (nivelRequerido) => {
        if (planAdquirido.value.id < nivelRequerido) {
            mostrarModalUpgrade.value = true;
            return false;
        }
        return true;
    };

    // ── MODAL CREACIÓN ────────────────────────────────────────────────────────

    const clickAgregarNegocio = () => {
        if (negocios.value.length >= limiteNegocios.value) {
            mostrarModalUpgrade.value = true;
            return;
        }

        nuevoNegocio.value = {
            nombre: '',
            email: usuarioLoggeado.value?.email || '',
            slug: '',
            telefonos: [{ id_tipo: 1, numero: '' }]
        };
        horarios.value = [];
        limpiarErrores();
        mostrarModalCreacion.value = true;
    };

    const guardarNuevoNegocio = async () => {
        limpiarErrores();
        let esValido = true;

        if (!nuevoNegocio.value.nombre.trim()) {
            errores.nombre = 'El nombre es obligatorio.';
            esValido = false;
        }

        nuevoNegocio.value.telefonos.forEach((tel, i) => {
            if (!tel.numero.trim() || tel.numero.length < 10) {
                errores[`telefono_${i}`] = 'El número debe tener 10 dígitos.';
                esValido = false;
            }
        });

        if (horarios.value.length === 0) {
            errores.horarios = 'Debes seleccionar al menos un horario.';
            esValido = false;
        }

        if (planAdquirido.value.id == 3 && !nuevoNegocio.value.slug.trim()) {
            errores.slug = 'La URL personalizada es obligatoria.';
            esValido = false;
        }

        if (!esValido) {
            window.$toast.show('Por favor revisa los campos en rojo', 'warning', 3000);
            return;
        }

        try {
            const res  = await fetch('/api/registrar-plan-negocio', {
                method: 'POST',
                headers: authHeaders(),
                body: JSON.stringify({
                    plan: planAdquirido.value.id,
                    nombre: nuevoNegocio.value.nombre,
                    email: nuevoNegocio.value.email,
                    telefonos: nuevoNegocio.value.telefonos,
                    slug: nuevoNegocio.value.slug,
                    horarios: horarios.value
                })
            });
            const data = await res.json();

            if (data.valid) {
                window.$toast.show(data.message, 'success', 4000);
                mostrarModalCreacion.value = false;
                await cargarNegocios();
            } else {
                window.$toast.show(data.message, 'warning', 4000);
            }
        } catch {
            window.$toast.show('Error al registrar el negocio', 'danger', 4000);
        }
    };

    // ── MODAL EDICIÓN ─────────────────────────────────────────────────────────

    const abrirModalEdicion = (negocio) => {
        limpiarErrores();

        negocioEditando.value = {
            id: negocio.id,
            nombre: negocio.nombre,
            email: negocio.email,
            slug: negocio.slug,
            telefonos: negocio.telefonos?.length
                ? JSON.parse(JSON.stringify(negocio.telefonos))
                : [{ id_tipo: 1, numero: '' }]
        };

        horariosEdicion.value    = negocio.horarios ? [...negocio.horarios] : [];
        mostrarModalEdicion.value = true;
    };

    const guardarEdicion = async () => {
        limpiarErrores();
        let esValido = true;

        if (!negocioEditando.value.nombre.trim()) {
            errores.edicion_nombre = 'El nombre es obligatorio.';
            esValido = false;
        }

        negocioEditando.value.telefonos.forEach((tel, i) => {
            if (!tel.numero.trim() || tel.numero.length < 10) {
                errores[`edicion_telefono_${i}`] = 'El número debe tener 10 dígitos.';
                esValido = false;
            }
        });

        if (horariosEdicion.value.length === 0) {
            errores.edicion_horarios = 'Debes seleccionar al menos un horario.';
            esValido = false;
        }

        if (!esValido) {
            window.$toast.show('Por favor revisa los campos en rojo', 'warning', 3000);
            return;
        }

        try {
            const res  = await fetch(`/api/negocios/${negocioEditando.value.id}`, {
                method: 'PUT',
                headers: authHeaders(),
                body: JSON.stringify({
                    nombre: negocioEditando.value.nombre,
                    telefonos: negocioEditando.value.telefonos,
                    horarios: horariosEdicion.value
                })
            });
            const data = await res.json();

            if (data.valid) {
                window.$toast.show(data.message, 'success', 4000);
                mostrarModalEdicion.value = false;
                await cargarNegocios();
            }
        } catch {
            window.$toast.show('Error al guardar los cambios', 'danger', 4000);
        }
    };

    return {
        // state
        token, negocios, limiteNegocios, usuarioLoggeado, planAdquirido,
        planesDisponibles, mostrarModalUpgrade, mostrarModalEdicion,
        mostrarModalCreacion, negocioEditando, nuevoNegocio, errores,
        horariosDisponibles, horarios, horariosEdicion,
        // computed
        limiteTelefonos,
        // methods
        cargarPlanes, cargarNegocios, intentarAccesoPremium,
        clickAgregarNegocio, guardarNuevoNegocio,
        abrirModalEdicion, guardarEdicion,
        toggleHorario, toggleHorarioEdicion,
        agregarTelefonoNuevo, quitarTelefonoNuevo,
        agregarTelefonoEdicion, quitarTelefonoEdicion
    };
}
