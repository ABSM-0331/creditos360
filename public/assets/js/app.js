const THEME_STORAGE_KEY = "gp_theme";

function aplicarTema(theme) {
    const resolvedTheme = theme === "light" ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", resolvedTheme);

    const toggle = document.getElementById("themeToggle");
    if (toggle) {
        const siguiente = resolvedTheme === "light" ? "oscuro" : "claro";
        toggle.setAttribute("aria-label", `Cambiar a modo ${siguiente}`);
        toggle.setAttribute("title", `Cambiar a modo ${siguiente}`);
    }
}

function inicializarTema() {
    let storedTheme = "dark";
    try {
        const saved = localStorage.getItem(THEME_STORAGE_KEY);
        if (saved === "light" || saved === "dark") {
            storedTheme = saved;
        }
    } catch (error) {
        storedTheme = "dark";
    }

    aplicarTema(storedTheme);

    const toggle = document.getElementById("themeToggle");
    if (!toggle) {
        return;
    }

    toggle.addEventListener("click", function () {
        const actual =
            document.documentElement.getAttribute("data-theme") === "light"
                ? "light"
                : "dark";
        const siguiente = actual === "light" ? "dark" : "light";

        aplicarTema(siguiente);
        try {
            localStorage.setItem(THEME_STORAGE_KEY, siguiente);
        } catch (error) {}
    });
}

document.addEventListener("DOMContentLoaded", inicializarTema);

// ===== Sidebar responsive (tablet/mobile) =====
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const menuToggle = document.getElementById("menuToggle");

    if (!sidebar || !menuToggle) {
        return;
    }

    let overlay = document.querySelector(".sidebar-overlay");
    if (!overlay) {
        overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        document.body.appendChild(overlay);
    }

    const closeSidebar = () => {
        sidebar.classList.remove("open");
        overlay.classList.remove("active");
    };

    menuToggle.addEventListener("click", function () {
        const isOpen = sidebar.classList.toggle("open");
        overlay.classList.toggle("active", isOpen);
    });

    overlay.addEventListener("click", closeSidebar);

    document.querySelectorAll(".sidebar .nav-link").forEach((link) => {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
});

const fechas = document.querySelectorAll(".fecha_nacimiento");
fechas.forEach((fechaNacimiento) => {
    fechaNacimiento.addEventListener("change", function () {
        const edadInput = this.closest("form").querySelector(".edad");

        const edad = calcularEdad(this.value);
        if (edad < 0 || edad < 18) {
            Swal.fire({
                icon: "error",
                title: "Edad no válida",
                text: "La fecha de nacimiento ingresada no es válida o la persona es menor de edad.",
                confirmButtonText: "Aceptar",
            });
            edadInput.value = "";
            this.value = "";
            return;
        }

        edadInput.value = edad;
    });
});

function calcularEdad(fecha) {
    const hoy = new Date();
    const nacimiento = new Date(fecha);
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    return edad;
}

// ===== Vista previa de fotos =====
// Vista previa para formulario de clientes
const fotoCliente = document.getElementById("foto_ruta");
if (fotoCliente) {
    fotoCliente.addEventListener("change", function (e) {
        mostrarVistaPrevia(e.target, "fotoPreview");
    });
}

// Vista previa para formulario de cobratarios
const fotoCobratario = document.getElementById("cob_foto_ruta");
if (fotoCobratario) {
    fotoCobratario.addEventListener("change", function (e) {
        mostrarVistaPrevia(e.target, "cobFotoPreview");
    });
}

// Vista previa para formulario de empresa
const fotoEmpresa = document.getElementById("empresa_logo");
if (fotoEmpresa) {
    fotoEmpresa.addEventListener("change", function (e) {
        mostrarVistaPrevia(e.target, "empresaLogoPreview");
    });
}

function mostrarVistaPrevia(input, previewId) {
    const previewDiv = document.getElementById(previewId);
    const file = input.files[0];

    if (file) {
        // Validar tamaño (máximo 2MB)
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: "error",
                title: "Archivo muy grande",
                text: "La imagen no debe superar los 2MB.",
                confirmButtonText: "Aceptar",
            });
            input.value = "";
            return;
        }

        // Validar tipo de archivo
        if (!file.type.match("image.*")) {
            Swal.fire({
                icon: "error",
                title: "Formato no válido",
                text: "Por favor selecciona una imagen válida (JPG, PNG).",
                confirmButtonText: "Aceptar",
            });
            input.value = "";
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            previewDiv.innerHTML = `<img src="${e.target.result}" alt="Vista previa" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">`;
        };
        reader.readAsDataURL(file);
    }
}

// ===== Cargar Estados y Municipios =====
// Cargar estados al cargar la página
document.addEventListener("DOMContentLoaded", function () {
    cargarEstados();
});

function cargarEstados() {
    fetch("/proyecto-residencia/public/api/estados")
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                const selectEstadoCliente = document.getElementById("idestado");
                const selectEstadoCobratario =
                    document.getElementById("cob_idestado");

                if (selectEstadoCliente) {
                    llenarSelectEstados(selectEstadoCliente, result.data);
                }

                if (selectEstadoCobratario) {
                    llenarSelectEstados(selectEstadoCobratario, result.data);
                }
            }
        })
        .catch((error) => {
            console.error("Error al cargar estados:", error);
        });
}

function llenarSelectEstados(selectElement, estados) {
    // Limpiar opciones existentes excepto la primera
    selectElement.innerHTML =
        '<option value="" disabled selected>Seleccionar estado</option>';

    // Agregar estados
    estados.forEach((estado) => {
        const option = document.createElement("option");
        option.value = estado.idestado;
        option.textContent = estado.nombre;
        selectElement.appendChild(option);
    });
}

// Eventos para cargar municipios cuando se selecciona un estado
const selectEstadoCliente = document.getElementById("idestado");
const selectMunicipioCliente = document.getElementById("idmunicipio");

if (selectEstadoCliente && selectMunicipioCliente) {
    // Deshabilitar municipio inicialmente
    selectMunicipioCliente.disabled = true;

    selectEstadoCliente.addEventListener("change", function () {
        const idestado = this.value;
        cargarMunicipios(idestado, selectMunicipioCliente);
    });
}

const selectEstadoCobratario = document.getElementById("cob_idestado");
const selectMunicipioCobratario = document.getElementById("cob_idmunicipio");

if (selectEstadoCobratario && selectMunicipioCobratario) {
    // Deshabilitar municipio inicialmente
    selectMunicipioCobratario.disabled = true;

    selectEstadoCobratario.addEventListener("change", function () {
        const idestado = this.value;
        cargarMunicipios(idestado, selectMunicipioCobratario);
    });
}

function cargarMunicipios(idestado, selectElement) {
    // Limpiar y deshabilitar mientras carga
    selectElement.innerHTML =
        '<option value="" disabled selected>Cargando...</option>';
    selectElement.disabled = true;

    fetch(`/proyecto-residencia/public/api/municipios?idestado=${idestado}`)
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                selectElement.innerHTML =
                    '<option value="" disabled selected>Seleccionar municipio</option>';

                result.data.forEach((municipio) => {
                    const option = document.createElement("option");
                    option.value = municipio.idmunicipio;
                    option.textContent = municipio.nombre;
                    selectElement.appendChild(option);
                });

                selectElement.disabled = false;
            } else {
                selectElement.innerHTML =
                    '<option value="" disabled selected>Error al cargar</option>';
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudieron cargar los municipios.",
                    confirmButtonText: "Aceptar",
                });
            }
        })
        .catch((error) => {
            console.error("Error al cargar municipios:", error);
            selectElement.innerHTML =
                '<option value="" disabled selected>Error al cargar</option>';
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los municipios.",
                confirmButtonText: "Aceptar",
            });
        });
}
