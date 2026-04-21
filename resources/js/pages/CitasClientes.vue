<script setup>
// IMPORTACIÓN DE FUNCIONES REACTIVAS DE VUE Y COMPONENTES DE FULLCALENDAR
import {computed, onMounted, ref, watch} from "vue";
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import {useRoute} from "vue-router";
import {loadStripe} from "@stripe/stripe-js"; //LIBRERIA DE STRIPE
import html2pdf from 'html2pdf.js';

// CONFIGURACIÓN DE LA RUTA PARA OBTENER EL SLUG DEL NEGOCIO DESDE LA URL
const route = useRoute();
const slug = route.params.slug;

//STRIPE
const stripePromise = loadStripe('pk_test_51SdC6n2Kyc20Semo6gAyq0TTApvii42va2XGLdeOL3njGMLJ6eU2eTNJvsnWV21gQRCjFqUWAdY08LJLF8DimTTm00s87OKbJ3');
let stripe = null;
let cardElement = null;
const errorTarjeta = ref('');

// ALMACENAR SERVICIOS
const servicios = ref([]);
const horariosDisponibles = ref([]);
const nombreNegocio = ref('');

// CONTROL DE VISIBILIDAD DEL MODAL DE REGISTRO
const mostrarModalCitaCliente = ref(false);

// OBJETO PARA ALMACENAR LOS DATOS DEL FORMULARIO DE NUEVA CITA
const formularioCitaCliente = ref({
    fecha: "",
    cliente_nombre: "",
    cliente_telefono: "",
    cliente_email: "",
    id_servicio: "",
    hora: ""
})

// PROPIEDAD COMPUTADA PARA OBTENER EL NOMBRE DEL SERVICIO SELECCIONADO
const nombreServicioSeleccionado = computed(() => {
    // SI AUN NO HAN SELECCIONADO UN SERVICIO, DEVOLVEMOS UN TEXTO VACIO
    if (!formularioCitaCliente.value.id_servicio) {
        return 'Sin asignar';
    }

    // BUSCAMOS DENTRO DE TU ARREGLO 'SERVICIOS' EL QUE COINCIDA CON EL ID SELECCIONADO
    const servicio = servicios.value.find(
        (s) => s.id === formularioCitaCliente.value.id_servicio
    );

    // SI LO ENCUENTRA, DEVUELVE EL NOMBRE
    return servicio ? servicio.nombre : 'Servicio desconocido';
});

const anticipoServicioSeleccionado = computed(() => {
    // SI AUN NO HAN SELECCIONADO UN SERVICIO, DEVOLVEMOS UN TEXTO VACIO
    if (!formularioCitaCliente.value.id_servicio) {
        return 'Sin asignar';
    }

    // BUSCAMOS DENTRO DE TU ARREGLO 'SERVICIOS' EL QUE COINCIDA CON EL ID SELECCIONADO
    const servicio = servicios.value.find(
        (s) => s.id === formularioCitaCliente.value.id_servicio
    );

    // SI LO ENCUENTRA, DEVUELVE EL ANTICIPO
    return servicio ? servicio.anticipo : 'Servicio desconocido';
});

// DETECTA SI EL SERVICIO SELECCIONADO TIENE UN ANTICIPO VALIDO (DIFERENTE DE NULL O 0)
const requiereAnticipo = computed(() => {
    if (!formularioCitaCliente.value.id_servicio) return false;

    const servicio = servicios.value.find(s => s.id === formularioCitaCliente.value.id_servicio);
    // RETORNA TRUE SI EL SERVICIO EXISTE Y SU ANTICIPO NO ES NULO NI CERO
    return servicio && servicio.anticipo !== null && servicio.anticipo > 0;
});

const requiereTarjeta = computed(() => {
    if (!formularioCitaCliente.value.id_servicio) return false;

    const servicio = servicios.value.find(s => s.id === formularioCitaCliente.value.id_servicio);
    // RETORNA TRUE SI EL SERVICIO EXISTE Y SU ANTICIPO NO ES NULO NI CERO
    return servicio && Number(servicio.tarjeta) === 1;
});

// OBJETO PARA GESTIONAR LOS MENSAJES DE ERROR DE VALIDACIÓN
const erroresFormularioCitaCliente = ref({
    cliente_nombre: "",
    cliente_telefono: "",
    cliente_email: "",
    id_servicio: "",
    hora: ""
})

// CONFIGURACIÓN DE LAS OPCIONES DEL CALENDARIO (VISTA, IDIOMA, EVENTOS Y HORARIOS)
const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: 'es',
    dateClick: handleDateClick,
    aspectRatio: 1.5, // Ajusta esto para controlar la altura (1.8 es más bajito)
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '' // Quitamos botones innecesarios para que se vea más limpio
    },
    businessHours: [
        {
            daysOfWeek: [1,2,3,4,5,6] // lunes a sábado
        }
    ]
})

// FUNCIÓN QUE SE DISPARA AL HACER CLIC EN UNA FECHA DEL CALENDARIO
function handleDateClick(info){
    // 0 = DOMINGO, 1 = LUNES, 2 = MARTES ... 6 = SABADO
    const dia = info.date.getDay();

    // REINICIO DE LOS VALORES DEL FORMULARIO AL ABRIR EL MODAL
    formularioCitaCliente.value = {
        fecha: "",
        cliente_nombre: "",
        cliente_telefono: "",
        cliente_email: "",
        id_servicio: "",
        hora: ""
    }

    horariosDisponibles.value = [];

    // VALIDACIÓN PARA EVITAR REGISTROS EN DOMINGOS
    if(dia === 0){
        window.$toast.show('Día inabil', 'danger', 5000);
        return;
    }

    // ASIGNA LA FECHA SELECCIONADA Y MUESTRA EL MODAL
    formularioCitaCliente.value.fecha = info.dateStr;
    mostrarModalCitaCliente.value = true;

    //CARD ELEMENT DE STRIPE
    setTimeout(async () => {
        stripe = await stripePromise;
        const elements = stripe.elements();

        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif','::placeholder': { color: '#aab7c4' },
                },
                invalid: { color: '#fa755a', iconColor: '#fa755a' }
            }
        });

        cardElement.mount('#card-element');

        cardElement.on('change', (event) => {
            errorTarjeta.value = event.error ? event.error.message : '';
        });
    }, 200);
}

// CIERRA EL MODAL DE REGISTRO
const cerrarModalCitaCliente = () => {
    mostrarModalCitaCliente.value = false;
}

// HOOK QUE SE EJECUTA CUANDO EL COMPONENTE SE CARGA EN EL NAVEGADOR
onMounted( () => {
    cargarCitas();
})

// VIGILANTE (WATCHER) QUE DETECTA CAMBIOS EN EL SERVICIO SELECCIONADO PARA BUSCAR HORARIOS
watch(() => formularioCitaCliente.value.id_servicio,async (nuevoServicio) => {
    horariosDisponibles.value = [];
    formularioCitaCliente.value.hora = "";

    // SI NO HAY SERVICIO O FECHA SELECCIONADA, NO REALIZA LA BÚSQUEDA
    if (!nuevoServicio || !formularioCitaCliente.value.fecha) return

    obtenerHorariosDisponibles();
})

// PETICIÓN PARA OBTENER LOS HORARIOS LIBRES SEGÚN SERVICIO, FECHA Y NEGOCIO (SLUG)
const obtenerHorariosDisponibles = async () => {
    try{
        const response = await fetch('/api/horarios-disponibles', {
            method: 'POST',
            headers: {
                'Content-Type' : 'application/json'
            },
            body: JSON.stringify({
                id_servicio: formularioCitaCliente.value.id_servicio,
                fecha: formularioCitaCliente.value.fecha,
                slug: slug
            })
        });
        const data = await response.json();

        if (data.valid){
            horariosDisponibles.value = data.horariosDisponibles;
        }
    }catch (error){
        window.$toast.show('Error al cargar horarios disponibles', 'danger', 5000);
    }
}

// FUNCIÓN ASÍNCRONA PARA OBTENER LOS SERVICIOS DESDE LA BD
const cargarCitas = async () => {
    try{
        // SE AGREGO EL SLUG DEL NEGOCIO
        const response = await fetch(`/api/citasclientes/${slug}`, {
           method: 'GET',
           headers: {
               'Content-Type' : 'application/json'
           }
        });
        const data = await response.json();

        if (data.valid){
            servicios.value = data.servicios;
            nombreNegocio.value = data.nombre_negocio;

            console.log(slug);
            //window.$toast.show('Se cargaron las citas correctamente', 'success', 5000);
        }
    }catch (error){
        window.$toast.show('Error al cargar servicios', 'danger', 5000);
    }
}

// ENVÍA LOS DATOS DEL FORMULARIO AL SERVIDOR PARA REGISTRAR LA CITA
const guardarCitaCliente = async () => {
    // PRIMERO SE VALIDA QUE EL FORMULARIO ESTÉ COMPLETO
    if(!validarFormularioCitaCliente()){
        window.$toast.show('Completar todos los campos', 'warning', 5000);
        return;
    }

    let stripePaymentMethodId = null;
    // SOLO VALIDAMOS STRIPE SI HAY ANTICIPO
    if (requiereAnticipo.value && requiereTarjeta.value) {
        // 1. PEDIRLE A STRIPE QUE POCESE LA TARJETA ANTES DE GUARDAR EN TU BD
        const {paymentMethod, error} = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: formularioCitaCliente.value.cliente_nombre,
                email: formularioCitaCliente.value.cliente_email,
            },
        });

        // SI LA TARJETA FALLA (FONDOS INSUFICIENTES, NUMERO MAL, ETC), DETENEMOS TODO
        if (error) {
            errorTarjeta.value = error.message;
            return;
        }
        stripePaymentMethodId = paymentMethod.id;
    }

    try{
        const response = await fetch('/api/registrar-cita-cliente', {
            method: 'POST',
            headers: {
                'Content-Type' : 'application/json'
            },
            body: JSON.stringify({
                cliente_nombre: formularioCitaCliente.value.cliente_nombre,
                cliente_telefono: formularioCitaCliente.value.cliente_telefono,
                cliente_email: formularioCitaCliente.value.cliente_email,
                id_servicio: formularioCitaCliente.value.id_servicio,
                fecha: formularioCitaCliente.value.fecha,
                slug: slug,
                hora: formularioCitaCliente.value.hora,
                payment_method_id: stripePaymentMethodId // <-- MANDAMOS EL CÓDIGO SEGURO DE STRIPE (SERÁ NULL SI NO HAY ANTICIPO)
            })
        });

        const data = await response.json();

        if (data.requires_action) {
            // 🔐 STRIPE PIDE AUTENTICACION (3D SECURE)
            const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret);
            if (error) {
                window.$toast.show(error.message, 'danger', 5000);
                return;
            }
            if (paymentIntent.status === 'succeeded') {
                window.$toast.show('¡Pago confirmado!', 'success', 5000);
            }
            return;
        }

        if(data.valid){
            generarPDF();
            // SI EL REGISTRO ES EXITOSO, SE RECARGA EL CALENDARIO Y SE CIERRA EL MODAL
            // SE INSERTO CORRECTAMENTE LA CITA
            cerrarModalCitaCliente();
            cargarCitas();
            mostrarModalCitaCliente.value = false;
            window.$toast.show(data.message, 'success', 5000);
        } else {
            window.$toast.show(data.message, 'warning', 5000);
        }
    }catch (error){
        window.$toast.show('Error al registrar la cita', 'danger', 5000);
    }
}

const generarPDF = () => {
    // 1. OBTENEMOS EL ELEMENTO HTML QUE CREAMOS
    const elemento = document.getElementById('comprobantePdf');

    // 2. CONFIGURAMOS LAS OPCIONES DEL PDF
    const opciones = {
        margin:       1, // MARGEN DE 1 PULGADA
        filename:     `Cita_${formularioCitaCliente.value.cliente_nombre}-${formularioCitaCliente.value.fecha}.pdf`, // NOMBRE DEL ARCHIVO DINAMICO
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 }, // MEJORA LA CALIDAD DEL TEXTO
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    // 3. GENERAMOS Y DESCARGAMOS EL PDF
    html2pdf().set(opciones).from(elemento).save();
}

// FUNCIÓN DE VALIDACIÓN LÓGICA DE CAMPOS OBLIGATORIOS
const validarFormularioCitaCliente = () => {
    // REINICIA LOS MENSAJES DE ERROR
    erroresFormularioCitaCliente.value = {
        cliente_nombre: "",
        cliente_telefono: "",
        cliente_email: "",
        id_servicio: "",
        hora: ""
    }

    let valido = true;

    // VERIFICACIÓN CAMPO POR CAMPO
    if(!formularioCitaCliente.value.cliente_nombre){
        erroresFormularioCitaCliente.value.cliente_nombre = "El nombre es obligatorio";
        valido = false;
    }
    const regexTelefono = /^[0-9]{10}$/;
    if(!formularioCitaCliente.value.cliente_telefono){
        erroresFormularioCitaCliente.value.cliente_telefono = "El telefono es obligatorio";
        valido = false;
    } else if (!regexTelefono.test(formularioCitaCliente.value.cliente_telefono)) {
        erroresFormularioCitaCliente.value.cliente_telefono = "El teléfono debe tener exactamente 10 dígitos";
        valido = false;
    }
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!formularioCitaCliente.value.cliente_email) {
        erroresFormularioCitaCliente.value.cliente_email = "El email es obligatorio";
        valido = false;
    } else if (!regexEmail.test(formularioCitaCliente.value.cliente_email)) {
        erroresFormularioCitaCliente.value.cliente_email = "El formato de correo no es válido";
        valido = false;
    }
    if(!formularioCitaCliente.value.id_servicio){
        erroresFormularioCitaCliente.value.id_servicio = "El servicio es obligatorio";
        valido = false;
    }
    if(!formularioCitaCliente.value.hora){
        erroresFormularioCitaCliente.value.hora = "El horario es obligatorio";
        valido = false;
    }
    return valido;
}

</script>
<template>
    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Calendario de Citas</h4>
                <p class="text-muted small mb-0">Selecciona una fecha para agendar tu servicio en {{ nombreNegocio }}</p>
            </div>
            <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                Modo Cliente Activo
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="mx-auto" style="max-width: 900px;">
                    <FullCalendar :options="calendarOptions"/>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-3">
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-white rounded-3 shadow-sm">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">🟢</div>
                    <div>
                        <small class="text-muted d-block">Días Disponibles</small>
                        <span class="fw-bold">Lunes a Sábado</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-if="mostrarModalCitaCliente" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold text-dark">Nueva Cita</h5>
                    <button type="button" class="btn-close shadow-none" @click="cerrarModalCitaCliente"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Nombre del cliente</label>
                        <input class="form-control form-control-lg bg-light border-0 shadow-sm" v-model="formularioCitaCliente.cliente_nombre">
                        <small class="text-danger text-muted mt-1 d-block">
                            {{ erroresFormularioCitaCliente.cliente_nombre }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Telefono</label>
                        <input class="form-control form-control-lg bg-light border-0 shadow-sm" v-model="formularioCitaCliente.cliente_telefono" maxlength="10">
                        <small class="text-danger text-muted mt-1 d-block">
                            {{ erroresFormularioCitaCliente.cliente_telefono }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Email</label>
                        <input type="email" class="form-control form-control-lg bg-light border-0 shadow-sm" v-model="formularioCitaCliente.cliente_email">
                        <small class="text-danger text-muted mt-1 d-block">
                            {{ erroresFormularioCitaCliente.cliente_email }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Servicio</label>
                        <select class="form-select form-select-lg bg-light border-0 shadow-sm" v-model="formularioCitaCliente.id_servicio">
                            <option value="">Seleccionar servicio</option>
                            <option v-for="servicio in servicios" :value="servicio.id">
                                {{ servicio.nombre + " - Duración: " + servicio.duracion_minutos + " minutos - Costo: $" + servicio.precio }} {{ servicio.anticipo ? ' - Anticipo: $' + servicio.anticipo : '' }}
                            </option>
                        </select>
                        <small class="text-danger text-muted mt-1 d-block">
                            {{ erroresFormularioCitaCliente.id_servicio }}
                        </small>
                    </div>
                    <div class="mb-3" v-if="horariosDisponibles.length">
                        <label class="form-label fw-semibold text-secondary">Horario disponible</label>
                        <select v-model="formularioCitaCliente.hora" class="form-select form-select-lg bg-light border-0 shadow-sm">
                            <option value="">Seleccionar horario</option>
                            <option v-for="hora in horariosDisponibles" :value="hora.inicio">
                                {{ hora.label }}
                            </option>
                        </select>
                        <small class="text-danger text-muted mt-1 d-block">
                            {{ erroresFormularioCitaCliente.hora }}
                        </small>
                    </div>
                    <div class="mb-3 pt-2 border-top" v-show="requiereAnticipo && requiereTarjeta">
                        <label class="form-label fw-semibold text-secondary">
                            💳 Detalles de pago (Modo Prueba)
                        </label>
                        <div class="p-3 bg-light border" style="border-radius: 0.75rem;">
                            <div id="card-element" class="w-100"></div>
                        </div>
                        <div class="text-danger small fw-medium mt-2 px-2" v-if="errorTarjeta">
                            {{ errorTarjeta }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary w-100 py-3 fw-bold fs-6 rounded-3" @click="cerrarModalCitaCliente">Cancelar</button>
                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="guardarCitaCliente">Guardar cita</button>
                </div>
            </div>
        </div>
    </div>
    <div v-show="false">
        <div id="comprobantePdf" style="padding: 30px; font-family: sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px;">
                <h1>Comprobante de Reservación: {{ nombreNegocio }}</h1>
            </div>
            <div style="margin-top: 20px;">
                <div style="margin-bottom: 10px;"><b>SERVICIO:</b> {{ nombreServicioSeleccionado }}</div>
                <div style="margin-bottom: 10px;"><b>CLIENTE:</b> {{ formularioCitaCliente.cliente_nombre }}</div>
                <div style="margin-bottom: 10px;"><b>TELÉFONO:</b> {{ formularioCitaCliente.cliente_telefono }}</div>
                <div style="margin-bottom: 10px;"><b>CORREO:</b> {{ formularioCitaCliente.cliente_email }}</div>
                <hr>
                <div style="margin-bottom: 10px;"><b>FECHA:</b> {{ formularioCitaCliente.fecha }}</div>
                <div style="margin-bottom: 10px;"><b>HORA:</b> {{ formularioCitaCliente.hora }}</div>
                <div style="margin-bottom: 10px;" v-if="requiereAnticipo"><b>ANTICIPO PAGADO:</b> ${{ anticipoServicioSeleccionado }}</div>
                <hr>
                <div style="margin-bottom: 10px;"><p style="text-align:center;">¡Gracias por su preferencia!</p></div>
            </div>
        </div>
    </div>
</template>
<style>
/* Personalización de FullCalendar para que combine con el estilo "Ticket" */
:deep(.fc) {
    --fc-border-color: #f0f0f0;
    --fc-button-bg-color: #0d6efd;
    --fc-button-border-color: #0d6efd;
    --fc-button-hover-bg-color: #0b5ed7;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Redondear botones de la cabecera del calendario */
:deep(.fc .fc-button) {
    border-radius: 8px;
    font-weight: 600;
    text-transform: capitalize;
    padding: 8px 16px;
}

/* Estilo para los números de los días */
:deep(.fc .fc-daygrid-day-number) {
    padding: 10px;
    font-weight: 500;
    color: #495057;
    text-decoration: none;
}

/* Resaltar el día actual */
:deep(.fc .fc-day-today) {
    background-color: rgba(13, 110, 253, 0.05) !important;
}

/* Estilo de los eventos (Citas existentes) */
:deep(.fc-event) {
    border-radius: 4px;
    padding: 2px 5px;
    font-size: 0.85em;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Quitar bordes exteriores pesados */
:deep(.fc-theme-standard td, .fc-theme-standard th) {
    border: 1px solid #f8f9fa;
}

:deep(.fc-col-header-cell) {
    background-color: #f8f9fa;
    padding: 12px 0 !important;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}
</style>
