<template>
    <footer class="bg-white border-top pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row gy-4 mb-4">

                <div class="col-lg-4 col-md-6 pe-lg-5 text-center text-md-start">

                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-3">
                        <img src="../../images/logo-vb.jpg" alt="Vista Boreal" class="rounded shadow-sm me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <h4 class="fw-bolder text-dark mb-0 tracking-tight">Vista Boreal</h4>
                    </div>

                    <p class="text-muted small mb-4 pe-lg-4" style="line-height: 1.6;">
                        Plataforma integral orientada a la gestión de sucursales, automatización de citas y administración de tu agenda digital en la nube.
                    </p>

                    <div class="d-flex gap-2 justify-content-center justify-content-md-start">
                        <a href="#" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center text-muted social-btn" style="width: 38px; height: 38px;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center text-muted social-btn" style="width: 38px; height: 38px;">
                            <i class="fab fa-x"></i>
                        </a>
                        <a href="#" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center text-muted social-btn" style="width: 38px; height: 38px;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center text-muted social-btn" style="width: 38px; height: 38px;">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-3 col-6 text-center text-md-start mt-4 mt-md-0">
                    <h6 class="text-uppercase fw-bold text-dark mb-4 tracking-wide" style="font-size: 0.8rem;">Producto</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Agenda Digital</a></li>
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Membresías</a></li>
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Sucursales</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-3 col-6 text-center text-md-start mt-4 mt-md-0">
                    <h6 class="text-uppercase fw-bold text-dark mb-4 tracking-wide" style="font-size: 0.8rem;">Recursos</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Centro de Soporte</a></li>
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Blog de Negocios</a></li>
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Contacto</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-3 col-6 text-center text-md-start mt-4 mt-md-0">
                    <h6 class="text-uppercase fw-bold text-dark mb-4 tracking-wide" style="font-size: 0.8rem;">Compañía</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Sobre Nosotros</a></li>
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Términos de Uso</a></li>
                        <li class="mb-3"><a href="#" class="text-decoration-none text-muted footer-link small">Políticas de Privacidad</a></li>
                    </ul>
                </div>
            </div>

            <div class="row align-items-center border-top pt-4 mt-2">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <span class="text-muted small">
                        © 2026 <span class="fw-bold text-primary">AgendaVB</span>. Creado por Vista Boreal. Todos los derechos reservados.
                    </span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-normal small shadow-sm d-inline-flex align-items-center">
                        <span class="bg-success rounded-circle me-2" style="width: 8px; height: 8px; display: inline-block;"></span>
                        Sistemas 100% Operativos
                    </span>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.tracking-tight {
    letter-spacing: -0.03em;
}
.tracking-wide {
    letter-spacing: 0.05em;
}
/* --> Efecto de desplazamiento y color en los enlaces */
.footer-link {
    transition: color 0.2s ease, transform 0.2s ease;
    display: inline-block;
}
.footer-link:hover {
    color: #0d6efd !important; /* Azul primario */
    transform: translateX(4px);
}
/* --> Efecto de elevación en botones sociales */
.social-btn {
    transition: all 0.2s ease;
}
.social-btn:hover {
    background-color: #0d6efd !important;
    color: white !important;
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
}
</style>
