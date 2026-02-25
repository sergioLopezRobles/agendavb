<template>
    <div class="toast-container">
        <transition-group name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="toast-item shadow"
                :class="'toast-' + toast.type"
            >
                <div class="toast-body">

                    <!-- icono -->
                    <div class="toast-icon">
                        <span v-if="toast.type==='success'">✓</span>
                        <span v-if="toast.type==='danger'">✕</span>
                        <span v-if="toast.type==='warning'">⚠</span>
                        <span v-if="toast.type==='info'">ℹ</span>
                    </div>

                    <!-- mensaje -->
                    <div class="toast-message">
                        {{ toast.message }}
                    </div>

                    <!-- cerrar -->
                    <button
                        class="toast-close"
                        @click="removeToast(toast.id)"
                    >
                        ×
                    </button>

                </div>

                <!-- barra tiempo -->
                <div
                    class="toast-progress"
                    :style="{ animationDuration: toast.duration + 'ms' }"
                ></div>

            </div>
        </transition-group>
    </div>
</template>

<script>

//TIPOS DE MENSAJE
/*
window.$toast.show("Login correcto", "success")

window.$toast.show("Error al iniciar sesión", "danger")

window.$toast.show("Campo requerido", "warning")

window.$toast.show("Información", "info")
 */

export default {

    data() {
        return {
            toasts: []
        }
    },

    methods: {

        show(message, type = "info", duration = 5000) {

            const id = Date.now()

            this.toasts.push({
                id,
                message,
                type,
                duration
            })

            setTimeout(() => {
                this.removeToast(id)
            }, duration)

        },

        removeToast(id) {

           this.toasts = this.toasts.filter(t=>t.id!==id)

        }

    }

}
</script>

<style scoped>

.toast-container {

    position: fixed;
    top: 20px;
    right: 20px;

    z-index: 9999;

    display: flex;
    flex-direction: column;
    gap: 12px;

    pointer-events: none;

}

.toast-item {

    min-width: 280px;
    max-width: 400px;

    border-radius: 12px;

    color: white;

    overflow: hidden;

    animation: slideIn 0.3s ease;

    pointer-events: auto;

}

.toast-container .toast-item {
    pointer-events: auto;
}

.toast-body {

    display: flex;
    align-items: center;

    padding: 14px;

}

.toast-icon {

    font-size: 20px;
    margin-right: 10px;

}

.toast-message {

    flex: 1;
    font-weight: 500;

    word-break: break-word;

}

.toast-close {

    background: none;
    border: none;

    color: white;

    font-size: 18px;

    cursor: pointer;

    opacity: 0.7;

}

.toast-close:hover {

    opacity: 1;

}

.toast-progress {

    height: 3px;

    background: rgba(255,255,255,0.7);

    animation-name: progress;
    animation-timing-function: linear;
    animation-fill-mode: forwards;

}

/* colores */

.toast-success {
    background: #16a34a;
}

.toast-danger {
    background: #dc2626;
}

.toast-warning {
    background: #f59e0b;
}

.toast-info {
    background: #2563eb;
}

/* animaciones */

@keyframes progress {

    from {
        width: 100%;
    }

    to {
        width: 0%;
    }

}

@keyframes slideIn {

    from {
        transform: translateX(100%);
        opacity: 0;
    }

    to {
        transform: translateX(0);
        opacity: 1;
    }

}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

</style>
