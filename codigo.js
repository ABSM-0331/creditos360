// ================== Config impresión (AGENTE LOCAL) ==================
const PRINT_BASE = "http://127.0.0.1:9666";
const PRINT_AGENT = PRINT_BASE + "/print";
const PRINT_STATUS = PRINT_BASE + "/status";
const PRINT_TOKEN = "secreto-123";

// ===== Helpers =====
function absUrl(maybe) {
    if (!maybe) return null;
    try {
        return new URL(maybe, window.location.href).href;
    } catch {
        return null;
    }
}

async function fetchWithTimeout(resource, options = {}) {
    const { timeout = 60000, ...opts } = options;
    const ctrl = new AbortController();
    const t = setTimeout(() => ctrl.abort(), timeout);
    try {
        return await fetch(resource, { ...opts, signal: ctrl.signal });
    } finally {
        clearTimeout(t);
    }
}

// Detecta si el agente local es B4A (expone btConnected/mac) o asumimos .NET
async function detectAgent() {
    try {
        const r = await fetchWithTimeout(PRINT_STATUS, {
            method: "GET",
            timeout: 2000,
        });
        if (!r.ok) return "dotnet";
        const j = await r.json().catch(() => ({}));
        if (j && typeof j === "object" && "btConnected" in j && "mac" in j)
            return "b4a";
        return "dotnet";
    } catch {
        return "dotnet";
    }
}

// ===== Impresión unificada =====
async function imprimirConGDI(accion, impresoraOpcional = "") {
    console.log("[PRINT] iniciar imprimirConGDI con accion:", accion);

    Swal.fire({
        title: "Imprimiendo ticket...",
        text: "Generando y enviando al agente local",
        icon: "info",
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const agentType = await detectAgent();
        console.log("[PRINT] agentType =", agentType);

        const urlTicket = absUrl(accion?.url);
        if (!urlTicket) throw new Error("URL de ticket inválida");

        // ============ RUTA 1: B4A -> URL-mode ============
        if (agentType === "b4a") {
            const bodyLite = {
                url: urlTicket,
                PrinterName: impresoraOpcional || undefined,
            };

            const rLite = await fetchWithTimeout(PRINT_AGENT, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-PRINT-TOKEN": PRINT_TOKEN,
                },
                body: JSON.stringify(bodyLite),
                timeout: 25000,
            });

            // Esperamos JSON { ok: true }. Si no, hacemos fallback.
            let okLite = false;
            if (rLite.ok) {
                try {
                    const j = await rLite.json();
                    okLite = !!(j && j.ok === true);
                } catch {
                    okLite = false;
                }
            }

            if (okLite) {
                Swal.close();
                await Swal.fire({
                    icon: "success",
                    title: "¡Ticket enviado!",
                    timer: 1400,
                    showConfirmButton: false,
                });
                return;
            }
            console.warn(
                "[PRINT] URL-mode no aceptado o sin ok:true -> fallback a modo clásico...",
            );
            // cae a modo clásico abajo
        }

        // ============ RUTA 2: .NET (o fallback) -> Modo clásico ============
        const resp = await fetchWithTimeout(urlTicket, {
            method: "GET",
            cache: "no-store",
            credentials: "include",
            timeout: 20000,
        });
        const raw = await resp.text();
        if (!resp.ok)
            throw new Error(
                "Ticket PHP devolvió " + resp.status + ": " + raw.slice(0, 300),
            );

        let payload;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            throw new Error(
                "Ticket no es JSON válido. Vista previa: " + raw.slice(0, 150),
            );
        }

        if (
            !Array.isArray(payload.Lines) ||
            typeof payload.Title !== "string"
        ) {
            if (
                payload &&
                payload.ok &&
                payload.fallback &&
                Array.isArray(payload.fallback.lines)
            ) {
                payload = {
                    Title:
                        payload.fallback.title ||
                        "Pedido No. " + (payload.idpedido || ""),
                    Lines: payload.fallback.lines,
                    Cut: true,
                    LogoBase64: payload.fallback.logo || null,
                    PaperWidthPx: 220,
                    FontName: "Consolas",
                    FontSize: 9,
                };
            } else {
                throw new Error(
                    "Formato de ticket inesperado (faltan Lines/Title).",
                );
            }
        }
        if (impresoraOpcional) payload.PrinterName = impresoraOpcional;

        const r2 = await fetchWithTimeout(PRINT_AGENT, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-PRINT-TOKEN": PRINT_TOKEN,
            },
            body: JSON.stringify(payload),
            timeout: 45000,
        });

        const txt2 = await r2.text();
        if (!r2.ok)
            throw new Error("Agente devolvió " + r2.status + ": " + txt2);

        Swal.close();
        await Swal.fire({
            icon: "success",
            title: "¡Ticket impreso!",
            timer: 1600,
            showConfirmButton: false,
        });
    } catch (err) {
        console.error("[PRINT] ERROR en imprimirConGDI:", err);
        Swal.close();
        await Swal.fire("❌ Ticket no impreso", String(err?.message || err));
        throw err;
    }
}

// ================== LISTENER DE TUS FORMULARIOS ==================
document.addEventListener("submit", async (ev) => {
    const form = ev.target.closest(".form-estado, .form-entregar");
    if (!form) return;

    ev.preventDefault();

    const submitter = ev.submitter || document.activeElement;

    //AGREGANDO EL TIEMPO DE ENTREGA CUANDO SE ACEPTA EL PEDIDO.

    // Razón de rechazo
    // Tiempo estimado de entrega
    /*
if (submitter && submitter.value === 'Aceptado') {
  const { value: tiempo } = await Swal.fire({
    title: 'Tiempo estimado de entrega',
    input: 'number',
    inputLabel: 'Ingrese el tiempo en minutos',
    inputPlaceholder: 'Ej: 30',
    inputAttributes: {
      min: 1,
      step: 1
    },
    showCancelButton: true,
    inputValidator: (value) => !value ? 'Debes ingresar un tiempo válido' : undefined
  });

  if (!tiempo) return;

  const inputTiempo = document.createElement('input');
  inputTiempo.type = 'hidden';
  inputTiempo.name = 'tiempo_estimado';
  inputTiempo.value = tiempo;
  form.appendChild(inputTiempo);
}*/
    if (
        submitter &&
        (submitter.value === "Aceptado" ||
            submitter.value === "AceptadoTransferencia")
    ) {
        // Si el valor es "AceptadoTransferencia", primero se confirma que el pago fue verificado
        if (submitter.value === "AceptadoTransferencia") {
            const { isConfirmed } = await Swal.fire({
                title: "Confirmar pago por transferencia",
                html: `
        <p style="margin:.25rem 0 .75rem">
          Antes de aceptar el pedido, <b>verifica que el pago se haya realizado</b> correctamente.<br>
          Confirma que el comprobante y los fondos sean válidos.
        </p>
      `,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, pago verificado",
                cancelButtonText: "Cancelar",
                reverseButtons: true,
            });

            if (!isConfirmed) {
                // Cancelar el flujo si no se confirma
                return;
            }
        }

        // Mostrar SweetAlert para el tiempo estimado
        const { value: tiempo } = await Swal.fire({
            title: "Tiempo estimado de entrega",
            input: "number",
            inputLabel: "Ingrese el tiempo en minutos",
            inputPlaceholder: "Ej: 30",
            inputAttributes: {
                min: 1,
                step: 1,
            },
            showCancelButton: true,
            inputValidator: (value) =>
                !value ? "Debes ingresar un tiempo válido" : undefined,
        });

        if (!tiempo) return;

        // Crear input oculto con el tiempo
        const inputTiempo = document.createElement("input");
        inputTiempo.type = "hidden";
        inputTiempo.name = "tiempo_estimado";
        inputTiempo.value = tiempo;
        form.appendChild(inputTiempo);

        // (Opcional) Enviar también si fue una transferencia
        const inputTipo = document.createElement("input");
        inputTipo.type = "hidden";
        inputTipo.name = "tipo_aceptacion";
        inputTipo.value =
            submitter.value === "AceptadoTransferencia"
                ? "transferencia"
                : "normal";
        form.appendChild(inputTipo);
    }

    //FIN DE TIEMPO DE ENTREGA

    // Razón de rechazo
    if (submitter && submitter.value === "Rechazado") {
        const { value: razon } = await Swal.fire({
            title: "¿Por qué rechazas este pedido?",
            input: "text",
            inputLabel: "Razón del rechazo",
            inputPlaceholder: "Ej: Producto agotado, información incompleta...",
            showCancelButton: true,
            inputValidator: (value) =>
                !value ? "Debes proporcionar una razón" : undefined,
        });
        if (!razon) return;
        const inputRazon = document.createElement("input");
        inputRazon.type = "hidden";
        inputRazon.name = "razon_rechazo";
        inputRazon.value = razon;
        form.appendChild(inputRazon);
    }

    // Confirmación para entregar
    if (form.classList.contains("form-entregar")) {
        const conf = await Swal.fire({
            title: "¿Entregar pedido?",
            text: "Confirma que el cliente ha recogido su pedido.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sí, entregar",
            cancelButtonText: "Cancelar",
        });
        if (!conf.isConfirmed) return;
    }

    const submitBtn =
        submitter ||
        form.querySelector('button[type="submit"], button[name="estado"]') ||
        form.querySelector("button");
    if (submitBtn) submitBtn.disabled = true;

    //se puso la validacion:
    const usarImpresoraVal = (
        document.getElementById("usar_impresora_txt")?.value || ""
    )
        .toString()
        .trim()
        .toUpperCase(); // Normaliza: "si", "Si", "SI" => "SI"

    const puedeImprimir = usarImpresoraVal === "SI";

    let leyenda = "";
    if (!puedeImprimir) {
        leyenda = "(Se omitirá la impresión de Ticket)";
    } else {
        leyenda = "(Se mandará la impresión de Ticket)";
    }
    //===fin

    // Loader de “Procesando...” (⚠️ sin await)
    Swal.fire({
        title: "Procesando..." + leyenda,
        html: `
      <div style="text-align:left;font-size:14px;margin-bottom:8px;">Actualizando estado del pedido</div>
      <div id="swal-progress" style="width:100%;background:#eee;border-radius:8px;overflow:hidden;height:10px;">
        <div id="swal-progress-bar" style="width:0%;height:100%;"></div>
      </div>
    `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            const bar = document.getElementById("swal-progress-bar");
            let p = 0;
            const t = setInterval(() => {
                p = Math.min(95, p + 5);
                if (bar) bar.style.width = p + "%";
            }, 120);
            Swal._progressInterval = t;
        },
    });

    try {
        const fd = new FormData(form);
        if (submitter && submitter.name) {
            fd.append(submitter.name, submitter.value);
        }

        console.log("[SUBMIT] POST actualizar_estado_pedido.php");
        const res = await fetch("actualizar_estado_pedido.php", {
            method: "POST",
            body: fd,
            cache: "no-store",
        });

        const textRaw = await res.text();
        console.log("[SUBMIT] respuesta cruda:", textRaw.slice(0, 300));
        let data;
        try {
            data = JSON.parse(textRaw);
        } catch (e) {
            throw new Error(
                "Respuesta no válida del servidor: " + textRaw.slice(0, 300),
            );
        }

        // Cerrar “Procesando…”
        const bar = document.getElementById("swal-progress-bar");
        if (bar) bar.style.width = "100%";
        if (Swal._progressInterval) {
            clearInterval(Swal._progressInterval);
            Swal._progressInterval = null;
        }
        Swal.close();

        if (!data.ok) {
            await Swal.fire({
                icon: "error",
                title: "Error",
                text: data.msg || "No se pudo actualizar.",
            });
            return;
        }

        console.log("[PRINT] data.accion:", data?.accion);

        // === IMPRESIÓN ===
        //SE COMENTO PARA OMITIR SI ES EL CASO DE NO USAR IMPRESORA
        /*
    if (data.accion && data.accion.imprimir && data.accion.url) {
      try {
        await imprimirConGDI(data.accion); // si quieres forzar impresora: pasar nombre como 2º arg
      } catch (e) {
        // imprimirConGDI ya muestra el error; aquí no hacemos nada extra
      }
    } else {
      await Swal.fire({ icon:'success', title:'¡Listo!', text:data.msg||'Estado actualizado', timer:1200, showConfirmButton:false });
    }
    */
        // === IMPRESIÓN ===
        // === IMPRESIÓN (con validación usar_impresora) ===
        const usarImpresoraVal = (
            document.getElementById("usar_impresora_txt")?.value || ""
        )
            .toString()
            .trim()
            .toUpperCase(); // Normaliza: "si", "Si", "SI" => "SI"

        const puedeImprimir = usarImpresoraVal === "SI";

        if (
            puedeImprimir &&
            data.accion &&
            data.accion.imprimir &&
            data.accion.url
        ) {
            try {
                await imprimirConGDI(data.accion); // si quieres forzar impresora: pasa nombre como 2º arg
            } catch (e) {
                // imprimirConGDI ya muestra el error; no hacemos nada extra
            }
        } else {
            // Omitimos la impresión (porque no hay permiso o faltan datos) y confirmamos la acción
            await Swal.fire({
                icon: "success",
                title: "¡Listo!",
                text: data.msg || "Estado actualizado",
                timer: 1200,
                showConfirmButton: false,
            });
        }

        // === IMPRESIÓN ===

        // === Refrescar sólo la fila afectada (tu lógica) ===
        const tr = form.closest("tr[data-id]");
        if (tr) {
            const celdas = tr.querySelectorAll("td");

            const celdaEstatus = celdas[7];
            if (celdaEstatus && data.estado)
                celdaEstatus.textContent = data.estado;

            const tdAcciones = celdas[9];
            if (tdAcciones && data.idpedido) {
                const r = await fetch(
                    `acciones_pedido.php?id=${data.idpedido}`,
                    { cache: "no-store" },
                );
                tdAcciones.innerHTML = await r.text();
            }
        }
    } catch (err) {
        console.error("[SUBMIT] ERROR:", err);
        if (Swal._progressInterval) {
            clearInterval(Swal._progressInterval);
            Swal._progressInterval = null;
        }
        Swal.close();
        await Swal.fire({
            icon: "error",
            title: "Error",
            text:
                err && err.message
                    ? err.message
                    : "No se pudo contactar al servidor.",
        });
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
});
