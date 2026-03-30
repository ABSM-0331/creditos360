// // ===== DOM Elements =====
// const sidebar = document.querySelector(".sidebar");
// const menuToggle = document.getElementById("menuToggle");
// const navLinks = document.querySelectorAll(".nav-link");
// const contentSections = document.querySelectorAll(".content-section");
// const pageTitle = document.getElementById("pageTitle");

// // ===== Sidebar Toggle (Mobile) =====
// menuToggle.addEventListener("click", () => {
//     sidebar.classList.toggle("open");

//     // Toggle overlay
//     let overlay = document.querySelector(".sidebar-overlay");
//     if (!overlay) {
//         overlay = document.createElement("div");
//         overlay.className = "sidebar-overlay";
//         document.body.appendChild(overlay);

//         overlay.addEventListener("click", () => {
//             sidebar.classList.remove("open");
//             overlay.classList.remove("active");
//         });
//     }

//     overlay.classList.toggle("active");
// });

// // ===== Navigation =====
// navLinks.forEach((link) => {
//     link.addEventListener("click", (e) => {
//         e.preventDefault();

//         const section = link.dataset.section;

//         // Update active nav link
//         navLinks.forEach((l) => l.classList.remove("active"));
//         link.classList.add("active");

//         // Show corresponding section
//         contentSections.forEach((s) => s.classList.remove("active"));
//         document.getElementById(section).classList.add("active");

//         // Update page title
//         const titles = {
//             dashboard: "Dashboard",
//             clientes: "Catálogo de Clientes",
//             cobratarios: "Catálogo de Cobratarios",
//         };
//         pageTitle.textContent = titles[section] || "Dashboard";

//         // Close sidebar on mobile
//         if (window.innerWidth <= 768) {
//             sidebar.classList.remove("open");
//             const overlay = document.querySelector(".sidebar-overlay");
//             if (overlay) overlay.classList.remove("active");
//         }
//     });
// });

// // ===== Window Resize Handler =====
// window.addEventListener("resize", () => {
//     if (window.innerWidth > 768) {
//         sidebar.classList.remove("open");
//         const overlay = document.querySelector(".sidebar-overlay");
//         if (overlay) overlay.classList.remove("active");
//     }
// });

// // ===== Search Functionality (Placeholder) =====
// const searchInputs = document.querySelectorAll(".search-bar input");
// searchInputs.forEach((input) => {
//     input.addEventListener("input", (e) => {
//         const searchTerm = e.target.value.toLowerCase();
//         console.log("Buscando:", searchTerm);
//         // Aquí puedes implementar la lógica de búsqueda
//     });
// });

// // ===== Button Click Handlers (Placeholders) =====
// document.querySelectorAll(".btn-primary").forEach((btn) => {
//     btn.addEventListener("click", () => {
//         alert("Función de agregar nuevo registro - Por implementar");
//     });
// });

// document.querySelectorAll('.btn-icon[title="Editar"]').forEach((btn) => {
//     btn.addEventListener("click", () => {
//         alert("Función de editar - Por implementar");
//     });
// });

// document.querySelectorAll('.btn-icon[title="Eliminar"]').forEach((btn) => {
//     btn.addEventListener("click", () => {
//         if (confirm("¿Estás seguro de que deseas eliminar este registro?")) {
//             alert("Función de eliminar - Por implementar");
//         }
//     });
// });

// document.querySelectorAll(".person-actions .btn-secondary").forEach((btn) => {
//     btn.addEventListener("click", () => {
//         alert("Ver detalles - Por implementar");
//     });
// });

// // ===== Initialize =====
// console.log("Aplicación de Gestión inicializada");

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
