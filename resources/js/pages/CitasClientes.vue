<script setup>
// IMPORTACIÓN DE FUNCIONES REACTIVAS DE VUE Y COMPONENTES DE FULLCALENDAR
import {computed, onMounted, ref, watch} from "vue";
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import {useRoute} from "vue-router";
import {loadStripe} from "@stripe/stripe-js"; //LIBRERIA DE STRIPE

// CONFIGURACIÓN DE LA RUTA PARA OBTENER EL SLUG DEL NEGOCIO DESDE LA URL
const route = useRoute();
const slug = route.params.slug;

//STRIPE
const stripePromise = loadStripe('pk_test_51SdC6n2Kyc20Semo6gAyq0TTApvii42va2XGLdeOL3njGMLJ6eU2eTNJvsnWV21gQRCjFqUWAdY08LJLF8DimTTm00s87OKbJ3');
let stripe = null;
let cardElement = null;
const errorTarjeta = ref('');

// ALMACENAR CITAS Y SERVICIOS
const citas = ref([]);
const servicios = ref([]);
const horariosDisponibles = ref([]);

// CONTROL DE VISIBILIDAD DEL MODAL DE REGISTRO
const mostrarModalCitaCliente = ref(false);

// OBJETO PARA ALMACENAR LOS DATOS DEL FORMULARIO DE NUEVA CITA
const formularioCitaCliente = ref({
    fecha: "",
    cliente_nombre: "",
    cliente_telefono: "",
    cliente_email: "",
    id_servicio: "",
    anticipo: "",
    hora: ""
})

// OBJETO PARA GESTIONAR LOS MENSAJES DE ERROR DE VALIDACIÓN
const erroresFormularioCitaCliente = ref({
    cliente_nombre: "",
    cliente_telefono: "",
    cliente_email: "",
    id_servicio: "",
    anticipo: "",
    hora: ""
})

// PROPIEDAD COMPUTADA QUE TRANSFORMA LOS DATOS DE LAS CITAS AL FORMATO QUE REQUIERE EL CALENDARIO
const eventos = computed(() => {
    return citas.value.map(cita => ({
        title: cita.cliente_nombre + "->" + cita.cliente_telefono,
        start: cita.fecha,
        color: '#0d6efd' // azul bootstrap
    }))
})

// CONFIGURACIÓN DE LAS OPCIONES DEL CALENDARIO (VISTA, IDIOMA, EVENTOS Y HORARIOS)
const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: 'es',
    events: eventos,
    dateClick: handleDateClick,
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
        anticipo: "",
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

// FUNCIÓN ASÍNCRONA PARA OBTENER LAS CITAS DESDE LA BD
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
            citas.value = data.citas;
            servicios.value = data.servicios;
            console.log(slug);
            //window.$toast.show('Se cargaron las citas correctamente', 'success', 5000);
        }
    }catch (error){
        window.$toast.show('Error al cargar citas', 'danger', 5000);
    }
}

// ENVÍA LOS DATOS DEL FORMULARIO AL SERVIDOR PARA REGISTRAR LA CITA
const guardarCitaCliente = async () => {
    // PRIMERO SE VALIDA QUE EL FORMULARIO ESTÉ COMPLETO
    if(!validarFormularioCitaCliente()){
        window.$toast.show('Completar todos los campos', 'warning', 5000);
        return;
    }

    // 1. Pedirle a Stripe que procese la tarjeta antes de guardar en tu BD
    const { paymentMethod, error } = await stripe.createPaymentMethod({
        type: 'card',
        card: cardElement,
        billing_details: {
            name: formularioCitaCliente.value.cliente_nombre,
            email: formularioCitaCliente.value.cliente_email,
        },
    });

    // Si la tarjeta falla (fondos insuficientes, numero mal, etc), detenemos todo
    if (error) {
        errorTarjeta.value = error.message;
        return;
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
                anticipo: formularioCitaCliente.value.anticipo,
                slug: slug,
                hora: formularioCitaCliente.value.hora,
                payment_method_id: paymentMethod.id // <-- MANDAMOS EL CÓDIGO SEGURO DE STRIPE
            })
        });

        const data = await response.json();

        if (data.requires_action) {
            // 🔐 Stripe pide autenticación (3D Secure)
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
            // SI EL REGISTRO ES EXITOSO, SE RECARGA EL CALENDARIO Y SE CIERRA EL MODAL
            // SE INSERTO CORRECTAMENTE LA CITA
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

// FUNCIÓN DE VALIDACIÓN LÓGICA DE CAMPOS OBLIGATORIOS
const validarFormularioCitaCliente = () => {
    // REINICIA LOS MENSAJES DE ERROR
    erroresFormularioCitaCliente.value = {
        cliente_nombre: "",
        cliente_telefono: "",
        cliente_email: "",
        id_servicio: "",
        anticipo: "",
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
    if(!formularioCitaCliente.value.anticipo){
        erroresFormularioCitaCliente.value.anticipo = "El anticipo es obligatorio";
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
    <h1>Calendario de Citas de cliente</h1>

    <FullCalendar :options="calendarOptions"/>
    <div v-if="mostrarModalCitaCliente" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Cita</h5>
                    <button type="button" class="btn-close" @click="cerrarModalCitaCliente"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre del cliente</label>
                        <input class="form-control" v-model="formularioCitaCliente.cliente_nombre">
                        <small class="text-danger">
                            {{ erroresFormularioCitaCliente.cliente_nombre }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label>Telefono</label>
                        <input class="form-control" v-model="formularioCitaCliente.cliente_telefono" maxlength="10">
                        <small class="text-danger">
                            {{ erroresFormularioCitaCliente.cliente_telefono }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label >Email</label>
                        <input class="form-control" v-model="formularioCitaCliente.cliente_email">
                        <small class="text-danger">
                            {{ erroresFormularioCitaCliente.cliente_email }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label>Servicio</label>
                        <select class="form-select" v-model="formularioCitaCliente.id_servicio">
                            <option value="">Seleccionar servicio</option>
                            <option v-for="servicio in servicios" :value="servicio.id">
                                {{ servicio.nombre + " - Duración: " + servicio.duracion_minutos + " minutos - Costo: $" + servicio.precio }}
                            </option>
                        </select>
                        <small class="text-danger">
                            {{ erroresFormularioCitaCliente.id_servicio }}
                        </small>
                    </div>
                    <div class="mb-3" v-if="horariosDisponibles.length">
                        <label>Horario disponible</label>
                        <select v-model="formularioCitaCliente.hora" class="form-select">
                            <option value="">Seleccionar horario</option>
                            <option v-for="hora in horariosDisponibles" :value="hora.inicio">
                                {{ hora.label }}
                            </option>
                        </select>
                        <small class="text-danger">
                            {{ erroresFormularioCitaCliente.hora }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label >Anticipo</label>
                        <input class="form-control" v-model="formularioCitaCliente.anticipo">
                        <small class="text-danger">
                            {{ erroresFormularioCitaCliente.anticipo }}
                        </small>
                    </div>
                    <div class="mb-4 pt-2 border-top">
                        <label class="form-label fw-bold text-dark small text-uppercase tracking-wide mb-3">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalCitaCliente">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarCitaCliente">Guardar cita</button>
                </div>
            </div>
        </div>
    </div>
</template>
