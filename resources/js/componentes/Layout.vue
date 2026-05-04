<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from "vue";
import { useRouter } from 'vue-router';

const router = useRouter();
const isSidebarExpanded = ref(true);

// recibir los datos de la vista que este usando layout
const props = defineProps({
    usuarioLoggeado: Object,
    planAdquirido: Object
});

const tienePlan = ref(localStorage.getItem('userHasPlan') === 'true');
const userRol = ref(parseInt(localStorage.getItem('userRol')) || null);

// --- LÓGICA DE PERFIL ---
const nombreEdit = ref('');
const archivoAvatar = ref(null);
const fotoPreview = ref(null);
const cargandoPerfil = ref(false);

const avatarLocal = ref(null);

// Escuchamos agresivamente cualquier cambio en el usuario (como cuando das F5 y carga de la BD)
watch(() => props.usuarioLoggeado, (nuevoValor) => {
    if (nuevoValor && nuevoValor.avatar) {
        avatarLocal.value = nuevoValor.avatar;
    }
}, { immediate: true, deep: true });

// El computed definitivo: Usa lo que acabas de subir O lo que trae la base de datos
const avatarUsuario = computed(() => {
    const rutaFinal = avatarLocal.value || props.usuarioLoggeado?.avatar;
    if (rutaFinal) {
        return `http://localhost/${rutaFinal}`;
    }
    return `https://ui-avatars.com/api/?name=${props.usuarioLoggeado?.name || 'User'}&background=0D6EFD&color=fff`;
});

// Cargar datos al abrir el modal
const abrirModalPerfil = () => {
    nombreEdit.value = props.usuarioLoggeado?.name || '';
    fotoPreview.value = null;
    archivoAvatar.value = null;
};

// Previsualizar la foto antes de subirla
const onAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        archivoAvatar.value = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

// Enviar datos al backend
const actualizarPerfil = async () => {
    cargandoPerfil.value = true;
    const formData = new FormData();
    formData.append('name', nombreEdit.value);
    if (archivoAvatar.value) {
        formData.append('avatar', archivoAvatar.value);
    }

    try {
        const response = await fetch('/api/perfil/actualizar', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: formData
        });

        const data = await response.json();
        if (data.valid) {
            // Actualizamos la foto en tiempo real
            avatarLocal.value = data.user.avatar;

            // Forzamos la actualización de las props para que no se borre
            if(props.usuarioLoggeado) {
                props.usuarioLoggeado.avatar = data.user.avatar;
                props.usuarioLoggeado.name = data.user.name;
            }

            document.getElementById('btnCerrarModalPerfil').click();
        }
    } catch (error) {
        console.error("Error al actualizar perfil:", error);
    } finally {
        cargandoPerfil.value = false;
    }
};
// --- FIN LÓGICA PERFIL ---

// Variable para controlar el puntito de notificación
const hayMensajesNuevos = ref(false);

// --- LÓGICA DE CHAT PRIVADO ---
const miId = computed(() => props.usuarioLoggeado ? Number(props.usuarioLoggeado.id) : 0);
const contactosAdmin = ref([]);
const adminSeleccionado = ref(null);
const mensajesAdmin = ref([]);
const nuevoMensaje = ref('');
const chatContainer = ref(null);
let chatInterval = null;

const formatHora = (fechaString) => {
    if(!fechaString) return '';
    return new Date(fechaString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const hacerScrollBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const cargarContactos = async () => {
    if (userRol.value !== 1) return;
    try {
        const response = await fetch('/api/admin/chat/contactos', {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        const data = await response.json();
        if (data.valid) contactosAdmin.value = data.contactos;
    } catch (error) { console.error(error); }
};

const cargarConversacion = async () => {
    if (!adminSeleccionado.value) return;
    try {
        const response = await fetch(`/api/admin/chat/conversacion/${adminSeleccionado.value.id}`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        const data = await response.json();

        if (data.valid) {
            const cantidadAnterior = mensajesAdmin.value.length;

            mensajesAdmin.value = data.mensajes.map(m => ({
                ...m,
                id_usuario: Number(m.id_usuario),
                receptor_id: Number(m.receptor_id)
            }));

            if (mensajesAdmin.value.length > cantidadAnterior) {
                hacerScrollBottom();
            }
        }
    } catch (error) { console.error(error); }
};

const seleccionarContacto = (contacto) => {
    adminSeleccionado.value = contacto;
    mensajesAdmin.value = [];
    cargarConversacion();
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(cargarConversacion, 3000);
};

const volverAContactos = () => {
    adminSeleccionado.value = null;
    mensajesAdmin.value = [];
    if (chatInterval) clearInterval(chatInterval);
};

const enviarMensaje = async () => {
    if (!nuevoMensaje.value.trim() || !adminSeleccionado.value) return;
    const msj = nuevoMensaje.value;
    nuevoMensaje.value = '';

    try {
        await fetch('/api/admin/chat/enviar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                mensaje: msj,
                receptor_id: adminSeleccionado.value.id
            })
        });
        cargarConversacion();
    } catch (error) { console.error(error); }
};

const abrirChat = () => {
    hayMensajesNuevos.value = false;
};

const obtenerUsuarioFresco = async () => {
    try {
        const response = await fetch('/api/user', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (data && data.avatar) {
            avatarLocal.value = data.avatar; // Fuerza la foto real
        }
    } catch (error) {
        console.error(error);
    }
};
// --- FIN LÓGICA CHAT ---

onMounted(() => {
    // ESTO OBLIGA A TRAER LA FOTO DE LA BD AL DAR F5
    obtenerUsuarioFresco();

    if (userRol.value === 1) {
        cargarContactos();
        const offcanvasElement = document.getElementById('chatAdminPanel');
        if(offcanvasElement) {
            offcanvasElement.addEventListener('hidden.bs.offcanvas', volverAContactos);
            offcanvasElement.addEventListener('shown.bs.offcanvas', abrirChat);
        }
    }
});

onUnmounted(() => {
    if (chatInterval) clearInterval(chatInterval);
});

watch(() => props.planAdquirido, (nuevoValor) => {
    if (nuevoValor && nuevoValor.id) {
        tienePlan.value = true;
        localStorage.setItem('userHasPlan', 'true');
    }
}, { immediate: true });

watch(() => props.usuarioLoggeado, (nuevoValor) => {
    if (nuevoValor && nuevoValor.id_rol) {
        userRol.value = nuevoValor.id_rol;
        localStorage.setItem('userRol', nuevoValor.id_rol.toString());
    }
}, { immediate: true });

const toggleSidebar = () => {
    isSidebarExpanded.value = !isSidebarExpanded.value;
};

const logout = async () => {
    const token = localStorage.getItem('token');
    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
    } catch (error) {
        console.log(error);
    }
    localStorage.removeItem('token');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('planSeleccionado');
    localStorage.removeItem('userHasPlan');
    localStorage.removeItem('userRol');
    router.push('/');
};
</script>

<template>
    <div class="d-flex bg-light min-vh-100">

        <div :class="['sidebar-wrapper bg-white shadow-sm d-flex flex-column sticky-top', { 'collapsed': !isSidebarExpanded }]">

            <div class="brand-header border-bottom d-flex align-items-center px-3">
                <div class="logo-container d-flex justify-content-center align-items-center flex-shrink-0">
                    <img src="../../images/logo-vb.jpg" alt="Vista Boreal" class="rounded shadow-sm brand-logo">
                </div>
                <h4 class="fw-bolder mb-0 text-dark menu-text ms-3 text-truncate" style="letter-spacing: -0.5px;">Vista Boreal</h4>
            </div>

            <ul class="nav flex-column mt-3 px-2 gap-3">
                <li class="nav-item">
                    <router-link to="/dashboard" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/dashboard' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                        <span class="fs-4 icon-menu me-3">📊</span>
                        <span class="menu-text fw-semibold">Dashboard</span>
                    </router-link>
                </li>

                <li v-if="userRol === 2 && tienePlan" class="nav-item mt-2 pt-2 border-top">
                    <small class="text-muted fw-bold ms-3 menu-text d-block mb-1" style="font-size: 0.75rem;">MÓDULOS</small>
                </li>

                <template v-if="userRol === 2 && tienePlan">
                    <li class="nav-item">
                        <router-link to="/negocios" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/negocios' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">🏪</span>
                            <span class="menu-text fw-medium">Mis Negocios</span>
                        </router-link>
                    </li>

                    <li class="nav-item">
                        <router-link to="/citas-negocios" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/citas-negocios' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">📆</span>
                            <span class="menu-text fw-medium">Agenda Digital</span>
                        </router-link>
                    </li>

                    <li v-if="userRol === 2" class="nav-item">
                        <router-link to="/soporte" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/soporte' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">🎧</span>
                            <span class="menu-text fw-medium">Mis Tickets</span>
                        </router-link>
                    </li>
                </template>

                <template v-if="userRol === 1">
                    <li class="nav-item mt-3 pt-3 border-top position-relative">
                        <div class="ms-3 menu-text mb-2">
                            <div class="badge bg-dark bg-gradient text-white shadow-sm rounded-pill py-2 px-3 d-inline-flex align-items-center border border-secondary border-opacity-50">
                                <span class="fs-6 me-2">🛡️</span>
                                <span class="fw-bold" style="letter-spacing: 1px; font-size: 0.70rem;">MODULOS ADMIN</span>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <router-link to="/admin-usuarios" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/admin-usuarios' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">👥</span>
                            <span class="menu-text fw-medium">Usuarios y Roles</span>
                        </router-link>
                    </li>

                    <li class="nav-item">
                        <router-link to="/admin-negocios" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/admin-negocios' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">🏢</span>
                            <span class="menu-text fw-medium">Negocios</span>
                        </router-link>
                    </li>

                    <li class="nav-item">
                        <router-link to="/admin-soporte" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/admin-soporte' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">🛠️</span>
                            <span class="menu-text fw-medium">Bandeja de Tickets</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin-auditoria" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/admin-auditoria' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                            <span class="fs-4 icon-menu me-3">🛡️</span>
                            <span class="menu-text fw-medium">Auditoría y Logs</span>
                        </router-link>
                    </li>
                </template>
            </ul>
        </div>

        <div class="flex-grow-1 d-flex flex-column overflow-hidden w-100">
            <nav class="navbar navbar-expand bg-white shadow-sm px-4 py-3">
                <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button @click="toggleSidebar" class="btn btn-light border-0 me-4 px-3 py-2 rounded-3 transition-all hover-shadow">
                            <span class="fs-5">☰</span>
                        </button>
                        <div class="d-none d-md-block">
                            <h4 class="mb-1 fw-bold text-dark">Buenos días, <span class="text-primary">{{ usuarioLoggeado?.name || 'Administrador' }}</span></h4>
                            <p class="text-muted small mb-0">Gestión de tu sistema</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button v-if="userRol === 1" class="btn btn-light border-0 rounded-circle position-relative p-2 hover-shadow" data-bs-toggle="offcanvas" data-bs-target="#chatAdminPanel">
                            <span class="fs-5">✉️</span>
                            <span v-if="hayMensajesNuevos" class="position-absolute top-25 start-75 translate-middle p-1 bg-primary border border-light rounded-circle"></span>
                        </button>

                        <button class="btn btn-light border-0 rounded-circle position-relative p-2 hover-shadow">
                            <span class="fs-5">🔔</span>
                            <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                        </button>

                        <div class="dropdown ms-2">
                            <button class="btn border-0 p-0 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img :src="avatarUsuario" alt="Perfil" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2 rounded-4" style="min-width: 200px;">
                                <li class="px-3 py-2 mb-1 border-bottom">
                                    <span class="fw-bold text-dark d-block">{{ usuarioLoggeado?.name || 'Usuario' }}</span>
                                    <small class="text-muted">{{ usuarioLoggeado?.email || '' }}</small>
                                </li>
                                <li><a class="dropdown-item rounded-3 py-2" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal" @click="abrirModalPerfil">👤 Mi Perfil</a></li>

                                <li v-if="userRol === 1">
                                    <button class="dropdown-item rounded-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="offcanvas" data-bs-target="#chatAdminPanel">
                                        <div>
                                            <span class="me-2">✉️</span> Mensajes
                                        </div>
                                        <span v-if="hayMensajesNuevos" class="bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>
                                    </button>
                                </li>

                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <button @click="logout" class="dropdown-item rounded-3 py-2 text-danger fw-bold d-flex align-items-center">
                                        <span class="me-2">🚪</span> Cerrar sesión
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </nav>
            <main class="p-4 p-lg-5 overflow-auto h-100">
                <slot></slot>
            </main>
        </div>
    </div>

    <!-- PANEL LATERAL: DIRECT MESSAGES -->
    <div class="offcanvas offcanvas-end shadow-lg border-0" tabindex="-1" id="chatAdminPanel" style="width: 380px;">
        <div class="offcanvas-header border-bottom bg-white px-3 py-3">
            <div v-if="!adminSeleccionado" class="d-flex align-items-center w-100">
                <span class="fs-4 me-2">🛡️</span>
                <div class="flex-grow-1">
                    <h6 class="fw-bold text-dark mb-0">Direct Messages</h6>
                    <small class="text-muted" style="font-size: 0.7rem;">Equipo Administrativo</small>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
            </div>

            <div v-else class="d-flex align-items-center w-100">
                <button @click="volverAContactos" class="btn btn-sm btn-light rounded-circle me-2 d-flex align-items-center justify-content-center p-0" style="width: 30px; height: 30px;">
                    <span>←</span>
                </button>
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        {{ adminSeleccionado.name.charAt(0).toUpperCase() }}
                    </div>
                    <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 180px;">{{ adminSeleccionado.name }}</h6>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
            </div>
        </div>

        <div class="offcanvas-body d-flex flex-column p-0 bg-light">
            <div v-if="!adminSeleccionado" class="p-2">
                <div v-if="contactosAdmin.length === 0" class="text-center py-5 text-muted small">
                    No hay otros administradores registrados.
                </div>

                <div class="list-group list-group-flush border-0">
                    <button v-for="contacto in contactosAdmin" :key="contacto.id" @click="seleccionarContacto(contacto)" class="list-group-item list-group-item-action border-0 rounded-3 mb-1 p-3 d-flex align-items-center hover-bg-light">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width: 42px; height: 42px;">
                            {{ contacto.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold text-dark">{{ contacto.name }}</h6>
                            <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ contacto.email }}</small>
                        </div>
                        <span class="text-primary fs-5">›</span>
                    </button>
                </div>
            </div>

            <template v-else>
                <div class="flex-grow-1 p-3 overflow-auto" ref="chatContainer">
                    <div class="text-center my-3 text-muted small">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1">Chat con {{ adminSeleccionado.name }}</span>
                    </div>

                    <div v-if="mensajesAdmin.length === 0" class="text-center py-4 text-muted small">
                        Envía el primer mensaje a {{ adminSeleccionado.name }}
                    </div>

                    <div v-for="(m, index) in mensajesAdmin" :key="m.id"
                         :class="['d-flex',
                                 m.id_usuario != adminSeleccionado.id ? 'justify-content-end' : '',
                                 (index === mensajesAdmin.length - 1 || mensajesAdmin[index + 1].id_usuario != m.id_usuario) ? 'mb-3' : 'mb-1'
                         ]">

                        <div v-if="m.id_usuario == adminSeleccionado.id" class="flex-shrink-0 me-2" style="width: 32px;">
                            <div v-if="index === 0 || mensajesAdmin[index - 1].id_usuario != m.id_usuario"
                                 class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ adminSeleccionado.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <div :class="[m.id_usuario != adminSeleccionado.id ? 'bg-primary bg-gradient text-white' : 'bg-white border', 'py-2 px-3 shadow-sm rounded-4']" style="max-width: 85%;">

                            <div v-if="m.id_usuario == adminSeleccionado.id && (index === 0 || mensajesAdmin[index - 1].id_usuario != m.id_usuario)"
                                 class="fw-bold mb-1 text-dark opacity-75" style="font-size: 0.65rem;">
                                {{ adminSeleccionado.name }}
                            </div>

                            <p :class="['mb-0 small', m.id_usuario != adminSeleccionado.id ? 'text-white' : 'text-dark']" style="word-break: break-word;">
                                {{ m.mensaje }}
                            </p>

                            <small :class="[m.id_usuario != adminSeleccionado.id ? 'text-white-50 text-end' : 'text-muted', 'd-block mt-1']" style="font-size: 0.65rem;">
                                {{ formatHora(m.created_at) }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white border-top">
                    <form @submit.prevent="enviarMensaje" class="d-flex gap-2">
                        <input type="text" v-model="nuevoMensaje" class="form-control bg-light border-0 shadow-none rounded-pill px-3" placeholder="Mensaje..." required autocomplete="off">
                        <button type="submit" :disabled="!nuevoMensaje.trim()" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <span>➤</span>
                        </button>
                    </form>
                </div>
            </template>

        </div>
    </div>

    <!-- MODAL DE PERFIL -->
    <div class="modal fade" id="perfilModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0 px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <span class="fs-4 me-2">👤</span> Configuración de Perfil
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" id="btnCerrarModalPerfil"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <form @submit.prevent="actualizarPerfil">

                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block mb-3">
                                <img :src="fotoPreview || avatarUsuario" alt="Tu Perfil" class="rounded-circle shadow border border-3 border-white" style="width: 120px; height: 120px; object-fit: cover;">

                                <label for="avatarInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow hover-shadow cursor-pointer" style="width: 35px; height: 35px; cursor: pointer; transform: translate(10%, 10%);">
                                    <span>📷</span>
                                </label>
                                <input type="file" id="avatarInput" class="d-none" accept="image/jpeg, image/png, image/jpg, image/webp" @change="onAvatarChange">
                            </div>
                            <p class="text-muted small mb-0">Formatos: JPG, PNG. Max: 2MB.</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Nombre a mostrar</label>
                            <input type="text" v-model="nombreEdit" class="form-control bg-light border-0 shadow-none rounded-3 px-3 py-2" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Correo Electrónico</label>
                            <input type="email" :value="usuarioLoggeado?.email" class="form-control border-0 shadow-none rounded-3 px-3 py-2 bg-secondary bg-opacity-10 text-muted" readonly disabled>
                            <small class="text-muted" style="font-size: 0.7rem;">El correo no se puede cambiar por motivos de seguridad.</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary fw-bold py-2 rounded-3 d-flex justify-content-center align-items-center" :disabled="cargandoPerfil">
                                <span v-if="cargandoPerfil" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ cargandoPerfil ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── CONTENEDOR PRINCIPAL ── */
.sidebar-wrapper {
    width: 270px;
    height: 100vh;
    transition: width 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    overflow-x: hidden;
    z-index: 1000;
}

/* ── CABECERA Y LOGO ── */
.brand-header {
    height: 87px;
    white-space: nowrap;
}

.logo-container {
    width: 48px;
    height: 48px;
    transition: all 0.3s ease;
}

.brand-logo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ── ANIMACIÓN DE TEXTOS (Menú y Cabecera) ── */
.menu-text {
    transition: opacity 0.2s ease, transform 0.3s ease, width 0.3s ease;
    opacity: 1;
    white-space: nowrap;
}

.icon-menu {
    transition: margin 0.3s ease;
}

/* ── ESTADOS COLAPSADOS ── */
.sidebar-wrapper.collapsed {
    width: 90px;
}

.sidebar-wrapper.collapsed .menu-text {
    opacity: 0;
    width: 0;
    overflow: hidden;
    transform: translateX(-10px);
    margin-left: 0 !important;
}

/* Centrar logo al colapsar */
.sidebar-wrapper.collapsed .brand-header {
    padding-left: 0 !important;
    padding-right: 0 !important;
    justify-content: center;
}

.sidebar-wrapper.collapsed .logo-container {
    width: 42px; /* Un poco más pequeño al colapsar */
    height: 42px;
}

/* Centrar iconos del menú al colapsar */
.sidebar-wrapper.collapsed .nav-link {
    justify-content: center;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.sidebar-wrapper.collapsed .icon-menu {
    margin-right: 0 !important;
}

/* ── UTILIDADES ── */
.hover-bg-light:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}

.hover-shadow:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
