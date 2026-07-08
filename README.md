# ControlPresta — Sistema de Control de Préstamos

Aplicación web para administrar el negocio de un prestamista: clientes, préstamos, pagos, intereses, multas por atraso y reportes. Uso interno (no público).

> **Este README es el contexto maestro del proyecto.** Sirve para que Claude Code (o cualquier desarrollador) entienda TODO el negocio y las reglas sin tener que re-explicarlas. Mantenelo actualizado y dejalo en la raíz del proyecto.

---

## 1. Stack tecnológico

| Capa | Tecnología |
|---|---|
| Framework | **Laravel 11** (PHP 8.3) |
| Vistas | **Blade** |
| Estilos / UI | **Bootstrap 5** + CSS propio (paleta WhatsApp: verde oscuro `#0E8C4F`, `#075E54`, beige `#ECE5DD`) |
| Base de datos | **MySQL** (migraciones de Laravel) |
| Móvil | **PWA** (instalable con ícono; requiere internet) |
| Entorno local | **Laragon** (Windows) · PHP 8.3 · Composer |
| Editor | **VS Code** (+ Claude Code) |
| Control de versiones | **Git + GitHub** |
| Hosting producción | **Namecheap Stellar** (shared, cPanel, PHP 8.3) |
| Dominio | **controlpresta.com** |

**Despliegue:** hosting compartido; el dominio se apunta a la carpeta `/public` de Laravel vía subdominio/addon domain o `.htaccess`. SSH y Composer disponibles en el plan Stellar.

---

## 2. Usuarios

Todos los usuarios tienen **acceso total** (mismas opciones y permisos). No hay roles con permisos distintos. Cada persona entra con su propio usuario y contraseña.

- Vienen dos usuarios de base: el **dueño** y la **esposa** (ambos con acceso completo).
- Cualquier usuario puede **crear usuarios nuevos**, que también tienen todas las opciones.

---

## 3. Regla de oro: MONTOS EN NÚMEROS ENTEROS

**Todo el dinero se maneja en colones ENTEROS. Nada de decimales.**

- En la base de datos, los campos de dinero son **enteros** (`unsignedInteger`), NO `decimal`.
- En el código, redondear siempre con `round()` / `intval()` antes de guardar o mostrar.
- En pantalla se muestran con separador de miles y el símbolo ₡ (ej: `₡150.000`), pero internamente son enteros.
- Las tasas de interés NO se guardan como decimal: se derivan de la frecuencia (ver 5.x) o se guardan como entero de porcentaje (20, 15, 5).

---

## 4. Módulos (basados en el prototipo final `index.html`)

1. **Login** — usuario y contraseña. Todos con acceso total. Se pueden crear usuarios nuevos.
2. **Panel / Dashboard** — tarjetas resumen (total prestado en la calle, clientes al día, clientes atrasados) y lista de pagos de la semana.
3. **Clientes**
   - Lista con buscador y filtros (Todos / Al día / Atrasados).
   - Ficha individual: datos, préstamo activo, panel de multa, panel de intereses atrasados, panel para saldar cuenta, historial de pagos.
   - Crear, editar y **desactivar** cliente (no se borra; se inactiva).
   - **Un solo botón** de "Nuevo cliente".
   - Al crear un cliente, **interruptor "¿Ya tiene un préstamo activo?"** para migrar (ver sección 8).
4. **Clientes desactivados (Inactivos)** — pantalla aparte que lista inactivos para **reactivarlos** si vuelven a pedir (conserva su historial).
5. **Préstamos** — botón "Nuevo préstamo" en la ficha (se habilita cuando el saldo llega a ₡0). Define monto, frecuencia y fecha de cobro.
6. **Pagos** — botón "Registrar pago". Separa **interés (obligatorio)** del **abono (opcional)**. El abono baja la deuda; el interés no.
7. **Intereses atrasados** — panel que acumula los períodos de interés NO pagados, con su fecha.
8. **Multas por atraso** — panel que muestra la multa acumulada (por día), con la fecha de cada día.
9. **Saldar cuenta completa** — muestra el desglose (deuda + intereses atrasados + multa) y un total para liquidar todo de una vez.

---

## 5. Reglas de negocio (EXACTAS — así está en el prototipo)

### 5.1 Tres cuentas SEPARADAS (nunca se mezclan)
| Cuenta | Qué es | Cómo se mueve |
|---|---|---|
| **Deuda (capital)** | Lo que se prestó | Solo baja con **abonos** |
| **Interés** | El % del período (obligatorio) | NO baja la deuda |
| **Multa** | Si se atrasa | Sube por día |

### 5.2 Interés según frecuencia
- **Mensual:** 20% del saldo
- **Quincenal:** 15% del saldo
- **Semanal:** 5% del saldo

**Día de cobro:** lo acuerdan con cada cliente y se ingresa como fecha. Las fechas siguientes se calculan sumando el período (mensual +1 mes, quincenal +15 días, semanal +7 días / miércoles). Como referencia, lo típico es mensual/quincenal el 15 y el 30, y semanal los miércoles.

### 5.3 Cálculo del interés (recálculo ÚNICO a la mitad)
El interés es el % sobre el monto, pero se recalcula **una sola vez** cuando la deuda llega a la **mitad del préstamo original**; de ahí en adelante queda **fijo** sobre esa mitad.

```
base    = (saldo > monto/2) ? monto : (monto/2)
interes = round(base * tasa)     // siempre entero
```
**Ejemplo ₡400.000 mensual (20%):** ₡80.000 → al llegar a ₡200.000 baja a ₡40.000 → de ahí abajo se queda fijo en ₡40.000.
**Ejemplo ₡200.000 mensual (20%):** ₡40.000 → al llegar a ₡100.000 baja a ₡20.000 → fijo en ₡20.000.

### 5.4 Interés vs. abono al registrar un pago
- **Interés:** obligatorio cada período. Se paga para estar al día. NO baja la deuda.
- **Abono:** opcional. Si el cliente quiere, lo da y **sí** baja la deuda. Si no da abono, la deuda queda igual.
- En el formulario de pago: el interés viene calculado (solo lectura) y el abono es un campo aparte que arranca en ₡0.

### 5.5 Multa por atraso (se acumula por día)
```
monto <  100.000  → ₡2.000/día   (ej. 50k, 80k, 99k)
monto de 100.000 a 149.999 → ₡3.000/día
monto >= 150.000  → ₡5.000/día
multa = tarifa * dias_atraso
```
**Cuándo empieza:** la multa arranca el **día siguiente** a la fecha de cobro (pasada la medianoche de ese día). El día de cobro NO cuenta como atraso.
- Ejemplo: debía pagar el **30** y hoy es el **5** del mes siguiente → lleva **5 días** de multa (días 1, 2, 3, 4, 5).
- Fórmula del conteo: `dias_atraso = días transcurridos desde (fecha_de_cobro + 1 día) hasta hoy`.

La multa es un cobro aparte (no se suma a la deuda ni al interés). **Sigue creciendo cada día** que pase sin pagar, hasta que se registre el pago.

### 5.6 Intereses atrasados (se acumulan por período, NO por día)
A diferencia de la multa (que es diaria), el interés atrasado se acumula **por cada fecha de cobro que pasa sin pagar el interés**:
- Llega la fecha de cobro (ej. el 30), no paga el interés → ese interés del período se acumula como "atrasado" (con su fecha).
- Llega la **siguiente** fecha de cobro y tampoco paga → se acumula **otro** interés atrasado.
- Y así sucesivamente, un interés atrasado por cada período (mes/quincena/semana) vencido sin pagar.

Cada interés atrasado se guarda en una lista con su fecha. Se pueden pagar después.

> **Resumen de la diferencia:** la **multa** cuenta **días** (sube todos los días); el **interés atrasado** cuenta **períodos** (sube cada vez que pasa una fecha de cobro sin pagar).

### 5.7 Saldar cuenta
```
total_a_pagar = saldo (deuda) + intereses_atrasados + multa_acumulada
```
Al saldar, todo queda en cero.

### 5.8 Estados del cliente
`al-dia`, `atrasado` o `sin-prestamo`. Pasa a `al-dia` cuando se pone al día con el interés y los atrasos; `sin-prestamo` cuando no tiene ningún préstamo activo.

### 5.9 Pagos parciales (versión simple)
El prestamista puede cobrar de cada cuenta lo que el cliente pague, no está obligado a cobrar todo completo. En el formulario de pago, cada concepto (multa, interés del período, intereses atrasados) muestra cuánto se debe + un campo editable para cuánto se paga hoy. Los campos vienen precargados con el monto completo (el caso normal es rápido: confirmar y ya).

Enfoque "guardar lo que falta" (NO créditos): al registrar el pago, de cada concepto se resta lo pagado y **queda guardado lo que falta** como el nuevo monto pendiente. Lo que no se pagó sigue debiéndose.

Reglas clave:
- **Interés del período:** si se paga COMPLETO → la fecha `proximo` avanza y el atraso termina. Si se paga PARCIAL → `proximo` NO avanza, el resto del interés sigue debiéndose, el cliente sigue atrasado.
- **Multa:** mientras el cliente deba interés del período, la multa **sigue creciendo por día** (días × tarifa). Un pago parcial de multa resta lo pagado, pero al día siguiente vuelve a subir por el día nuevo. **Cuando paga el interés completo**, el atraso termina (`dias_atraso = 0`) y la multa **se detiene/congela** en el valor de ese día; si quedó multa sin pagar, ese resto sigue debiéndose pero ya NO crece.
- **Intereses atrasados:** se pueden pagar parcial; lo que falta sigue debiéndose.
- **Estado del cliente:** `al-dia` solo si no queda nada debiendo (ni interés del período, ni multa, ni intereses atrasados).

### 5.10 Filosofía del sistema (decisión de diseño clave)
El prestamista tiene **control total y manual** sobre los montos que cobra. El sistema NO bloquea, NO obliga a cobrar todo, NO pelea con las decisiones del dueño (él conoce a sus clientes, negocia, hace excepciones). Todos los campos de pago son editables manualmente.

El rol del sistema es ser un **asistente que lleva las cuentas**, no un policía. En concreto, el sistema se encarga de:
1. **Llevar el control de la plata** — saldos, cuánto se pagó de cada concepto, cuánto falta.
2. **Alertar de los atrasos** — quién se atrasó y desde cuándo (vía el estado del cliente y las fechas de cobro).
3. **Guardar los historiales** — registro completo de todos los pagos (fecha, desglose, método) y de los préstamos saldados.

La única "regla automática" que el sistema aplica sin pedir permiso es el avance de la fecha de cobro: solo avanza cuando el interés del período se paga completo. Eso es lo que le permite al sistema saber, solo, si un cliente está al día o atrasado. Todo lo demás lo decide el dueño ingresando los montos.

---

## 6. Modelo de base de datos

> **Recordatorio: todo el dinero es entero (`unsignedInteger`), nunca `decimal`.**
> Para migrar préstamos del cuaderno, los valores se ingresan a mano (ver sección 8). Por eso varios campos (saldo, dias_atraso, multa_acumulada, intereses_pagados, intereses atrasados) deben aceptar valores ingresados manualmente.

### Tabla `users`
| Campo | Tipo Laravel | Notas |
|---|---|---|
| id | id() | PK |
| name | string | |
| email | string unique | usuario de acceso |
| password | string | hash |
| timestamps | | |

> Todos los usuarios tienen acceso total; no hay campo de rol.

### Tabla `clientes`
| Campo | Tipo Laravel | Notas |
|---|---|---|
| id | id() | PK |
| nombre | string | |
| apellidos | string | |
| telefono | string nullable | |
| direccion | string nullable | |
| trabajo | string nullable | |
| cedula | string nullable | |
| cedula_foto | string nullable | ruta de la foto de la cédula |
| estado | enum('al-dia','atrasado') default 'al-dia' | |
| activo | boolean default true | false = desactivado |
| timestamps | | |

### Tabla `prestamos`
| Campo | Tipo Laravel | Notas |
|---|---|---|
| id | id() | PK |
| cliente_id | foreignId → clientes | |
| monto | unsignedInteger | monto original prestado (entero) |
| saldo | unsignedInteger | deuda actual / capital (entero) |
| frecuencia | enum('mensual','quincenal','semanal') | la tasa se deriva: 20/15/5 |
| interes_pagados | unsignedInteger default 0 | intereses que ya pagó (acumulado) |
| multa_acumulada | unsignedInteger default 0 | multa actual (manual al migrar; si no, se calcula desde dias_atraso) |
| dias_atraso | unsignedInteger default 0 | |
| inicio | date nullable | fecha en que se le prestó |
| proximo | date nullable | fecha del próximo cobro |
| vencido | boolean default false | |
| estado | enum('activo','saldado') default 'activo' | |
| timestamps | | |

> **Derivados (NO se guardan, se calculan):**
> - `tasa` = según frecuencia (mensual 0.20, quincenal 0.15, semanal 0.05).
> - `abonado` = `monto - saldo` (lo que el cliente ha abonado a la deuda).
> - `interes_periodo` = fórmula 5.3.

### Tabla `pagos`
| Campo | Tipo Laravel | Notas |
|---|---|---|
| id | id() | PK |
| prestamo_id | foreignId → prestamos | |
| fecha | date | |
| monto_total | unsignedInteger | interés + abono + atrasados + multa pagados |
| interes | unsignedInteger default 0 | interés del período pagado |
| abono | unsignedInteger default 0 | lo que bajó la deuda |
| interes_atrasado_pagado | unsignedInteger default 0 | |
| multa_pagada | unsignedInteger default 0 | |
| metodo | enum('efectivo','sinpe','transferencia') | |
| recibido_por | foreignId → users nullable | quién registró el pago |
| es_saldo | boolean default false | true si fue "saldar cuenta" |
| timestamps | | |

### Tabla `intereses_atrasados`
| Campo | Tipo Laravel | Notas |
|---|---|---|
| id | id() | PK |
| prestamo_id | foreignId → prestamos | |
| fecha | date | período al que corresponde |
| monto | unsignedInteger | entero |
| pagado | boolean default false | |
| timestamps | | |

### Relaciones (Eloquent)
- `Cliente` hasMany `Prestamo`.
- `Prestamo` belongsTo `Cliente`; hasMany `Pago`; hasMany `InteresAtrasado`.
- `Pago` belongsTo `Prestamo`; belongsTo `User` (recibido_por).
- `InteresAtrasado` belongsTo `Prestamo`.

---

## 7. Estado actual del proyecto

- ✅ Entorno completo: Laragon, MySQL `controlpresta`, Laravel, Git/GitHub (`github.com/AndreMoreZu/controlPresta`).
- ✅ Migraciones, modelos Eloquent y relaciones.
- ✅ Login (email + contraseña, sin roles) + dashboard, tema del prototipo.
- ✅ Módulo **Clientes**: lista, ficha, `PrestamoService` (cálculos centralizados), formulario de crear cliente con migración manual (incluye `atraso_desde` como fuente de verdad), 2 fotos de cédula, responsive.
- ⏳ Pendiente: Clientes Parte 3 (editar/desactivar + inactivos), módulo de Pagos, Préstamos, PWA, despliegue.

### Notas para el módulo de Pagos (pendientes confirmados, NO son huecos en la base)
Al revisar la migración de clientes se confirmó que **ningún dato crítico falta**; lo que falta es la lógica viva que se construye en Pagos. Tener en cuenta:
1. **`dias_atraso` debe recalcularse en vivo** desde `atraso_desde` con `PrestamoService::calcularDiasAtraso()` (hoy es un snapshot del día de la migración; Pagos debe refrescarlo, no confiar en el valor guardado).
2. **`multa_acumulada` manual queda fija** mientras sea >0. Pagos debe decidir cuándo "soltarla" (ej. ponerla en 0 tras el primer pago) para que de ahí en adelante se calcule sola con `dias_atraso × tarifa`.
3. **El interés atrasado migrado es un monto consolidado** (un solo registro con el total del cuaderno, no uno por período). Pagos lo trata como deuda heredada y genera los nuevos período por período de ahí en adelante.
4. **Día de cobro por cliente:** cada cliente tiene su propio día acordado (unos pagan los 15, otros los 30, etc.), definido por la fecha que se ingresa al crearlo. El sistema usa esa fecha como referencia y suma el período. **CONFIRMADO con la dueña (6 jul 2026):** el **quincenal es los días 15 y 30 de cada mes** (fijo, no "cada 15 días exactos"). Reglas de avance del próximo cobro: mensual → mismo día del mes siguiente; quincenal → alterna entre el 15 y el 30 (del 15 salta al 30 del mismo mes; del 30 salta al 15 del mes siguiente); semanal → +7 días (mismo día de semana). Nota: para fin de mes en meses de 28/29/31 días, el "30" se ajusta al último día disponible del mes.

---

## 8. Estándares de código (OBLIGATORIO seguirlos)

Todo el código debe ser ordenado, modular y consistente. Mismo estilo en todo el proyecto. Estas reglas son obligatorias:

**Arquitectura MVC + servicios**
- **Controladores delgados:** solo reciben la petición, llaman a la lógica y devuelven la vista/respuesta. NADA de cálculos de negocio dentro del controlador.
- **Lógica de negocio en su lugar:** los cálculos (interés, multa, saldo, recálculo a la mitad, total a saldar, etc.) van en **clases de servicio** dedicadas (carpeta `app/Services`, ej. `PrestamoService`, `PagoService`) o en métodos del modelo. NUNCA repetir lógica en varios lados (principio DRY: una sola fuente de verdad por cada regla).
- **Modelos Eloquent:** con sus relaciones bien definidas, `$fillable`, y `casts` correctos. Los montos son enteros.
- **Validación con Form Requests:** cada formulario valida con su clase `FormRequest` (ej. `StoreClienteRequest`), no validaciones sueltas en el controlador.

**Organización y nombres**
- Nombres de variables, métodos y comentarios en **español** (coherente con el negocio: `cliente`, `prestamo`, `saldo`, `interesPeriodo`).
- Clases en PascalCase (`PrestamoController`), métodos en camelCase (`registrarPago`), tablas/columnas en snake_case (`intereses_atrasados`).
- Una responsabilidad por clase/método. Métodos cortos y claros.
- Rutas con nombre (`->name('clientes.index')`) y agrupadas con `Route::resource` donde aplique.

**Vistas (Blade + Bootstrap 5)**
- **Bootstrap 5** para todos los estilos (calza con el prototipo `index.html`: verde `#0E8C4F`, `#075E54`, beige `#ECE5DD`).
- Vistas modulares: un **layout base** (`layouts.app`) y **componentes Blade** reutilizables (tarjetas de cliente, paneles, modales, botones). Nada de copiar y pegar HTML repetido.
- La lógica NO va en las vistas; las vistas solo muestran datos ya preparados por el controlador/servicio.

**Reglas de negocio centralizadas**
- Las constantes del negocio (tasas 20/15/5, tarifas de multa 2000/3000/5000, umbrales) se definen UNA vez (config o constantes de clase), no se escriben "a mano" repetidas por el código.
- Los montos SIEMPRE enteros (ver sección 3). Redondear con `round()`/`intval()` antes de guardar.

**Antes de cada avance**
- Código limpio, sin archivos basura ni código comentado muerto.
- Commits de Git pequeños y con mensaje claro por cada funcionalidad terminada.

---

## 9. Guía de estilo y colores (tema WhatsApp)

> El diseño debe verse **idéntico al prototipo** `_prototipo/index.html`. Leé ese archivo para los estilos, espaciados y formas exactas. Estos son los colores y reglas base:

**Paleta de colores (CSS variables):**
```css
--green:#075E54;        /* verde oscuro: header, sidebar, barras superiores */
--green-dark:#054640;   /* verde muy oscuro: acentos */
--accent:#0E8C4F;       /* verde principal: botones, acciones (NO el verde chillón) */
--accent-dark:#0A6E3D;  /* hover de botones */
--beige:#ECE5DD;        /* fondo general */
--white:#FFFFFF;        /* tarjetas, paneles */
--text:#111B21;         /* texto principal */
--muted:#8696a0;        /* texto secundario / etiquetas */
--line:#E9E3DA;         /* bordes suaves */
--red:#D9534F;          /* atrasos, multas, vencidos */
--avatar:#D7E7DE;       /* fondo de iniciales/avatares */
```

**Reglas de estilo:**
- Botones de acción: fondo `--accent` (#0E8C4F) con texto **blanco**; hover a `--accent-dark`.
- Header/sidebar/barras superiores: fondo `--green` (#075E54) con texto blanco.
- Fondo de las pantallas: `--beige`. Tarjetas y paneles: blanco con bordes `--line` y esquinas redondeadas (~14px).
- Estados: "Al día" en verde, "Atrasado"/multas/vencidos en `--red`.
- Tipografía tipo sistema (system-ui / Segoe UI / Roboto), limpia y legible.
- Bootstrap 5 como base, **personalizado con estos colores** (sobreescribir variables de Bootstrap o usar clases propias). El resultado debe verse como el prototipo, no como Bootstrap por defecto.
- Símbolo de moneda: ₡ con separador de miles (ej. `₡150.000`).
- Versión móvil instalable (PWA), con diseño responsive como el prototipo (vista escritorio + celular).

---

## 10. Migración de clientes existentes (TODO A MANO)

Los datos están **en papel (cuaderno)**, así que se digitan **a mano, uno por uno**. No hay importación por Excel y **el sistema NO calcula nada en la migración**: cada número se ingresa tal como está en el cuaderno. De ahí en adelante, el sistema sí toma el control (pagos nuevos, historial, estados).

**Mecanismo:** al crear un cliente, el interruptor **"¿Ya tiene un préstamo activo?"** despliega los campos del préstamo:
- Monto original del préstamo
- **Saldo que debe hoy** (de aquí se deriva lo abonado = monto − saldo; no se pide el abono por separado)
- Intereses que ya ha pagado
- Frecuencia (mensual / quincenal / semanal)
- Fecha en que se le prestó
- Fecha del próximo cobro

Y un **interruptor "¿Está atrasado?"** que, solo si se activa, pide a mano:
- Días de atraso
- Multa acumulada
- Intereses atrasados (sin pagar)

> En la ficha, el préstamo migrado muestra: monto, **ha abonado** (monto − saldo), saldo actual, interés del período, intereses ya pagados, próximo cobro, y los paneles de multa/intereses atrasados si está atrasado.

---

## 11. Subir el proyecto a GitHub (paso a paso)

Desde la **terminal de Laragon**, dentro de la carpeta del proyecto:

```bash
cd C:\laragon\www\control-prestamos
```

**1. Confirmá que el `.gitignore` excluya lo sensible** (Laravel ya lo trae):
```
/vendor
/node_modules
.env
.env.backup
/storage/*.key
public/storage
```
> ⚠️ El `.env` (claves de la base de datos) **NUNCA** se sube a GitHub.

**2. Git init y primer commit:**
```bash
git init
git add .
git commit -m "Primer commit - proyecto Laravel base"
```

**3. Creá un repositorio VACÍO en GitHub:**
- github.com → New repository → nombre `controlpresta`.
- **NO** marqués "Add README" ni "Add .gitignore" (el proyecto ya los trae).
- Create repository.

**4. Conectá y subí** (reemplazá la URL por la tuya):
```bash
git branch -M main
git remote add origin https://github.com/TU-USUARIO/controlpresta.git
git push -u origin main
```
> La primera vez te pide autenticarte: usá un **Personal Access Token** (GitHub → Settings → Developer settings → Tokens) como contraseña, o instalá **GitHub CLI** (`gh auth login`).

**5. De ahí en adelante:**
```bash
git add .
git commit -m "Descripción del cambio"
git push
```

---

## 12. Cómo trabajar con Claude Code (VS Code)

1. Abrí la carpeta `C:\laragon\www\control-prestamos` en VS Code.
2. Dejá este `README.md` en la raíz → Claude Code lo lee como contexto.
3. Pedile tareas concretas y por pasos. Orden sugerido:
   1. "Configurá la conexión a MySQL en `.env` y creá la base de datos `controlpresta`."
   2. "Creá las migraciones según el modelo de datos del README (sección 6). Recordá: montos en `unsignedInteger`, nada de decimales."
   3. "Generá los modelos Eloquent con sus relaciones (sección 6)."
   4. "Hacé el login con usuario y contraseña (todos con acceso total, sin roles)."
   5. "Creá los seeders con los 2 usuarios de base (dueño y esposa)."
   6. "Construí la vista Blade de la lista de clientes según el prototipo `index.html`."
4. Mantené este README actualizado conforme avance el proyecto.

**Comandos útiles:**
```bash
php artisan make:migration crear_tabla_clientes
php artisan make:model Cliente -mcr     # modelo + migración + controlador resource
php artisan migrate                     # corre las migraciones
php artisan migrate:fresh --seed        # recrea todo y siembra datos
php artisan serve                       # arranca el servidor local
```

---

## 13. Acuerdo comercial (resumen)

- **Desarrollo:** ₡150.000 (una sola vez). Incluye el sistema descrito en este README.
- **Hosting + dominio:** se cobra aparte / anual (sube en la renovación).
- **Garantía:** 1 año para corregir fallos. Funciones nuevas se cotizan aparte.
- **Migración de datos:** se hace a mano con la opción de "préstamo activo" al crear el cliente. No requiere desarrollo extra más allá de ese formulario.