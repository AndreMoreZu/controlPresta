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
monto <= 50.000   → ₡2.000/día
monto <= 150.000  → ₡3.000/día
monto >  150.000  → ₡5.000/día
multa = tarifa * dias_atraso
```
Está **atrasado** quien no pagó el interés de su período. La multa es un cobro aparte (no se suma a la deuda ni al interés).

### 5.6 Intereses atrasados
Cada período de interés no pagado se guarda en una lista y se va acumulando aparte (con su fecha). Se pueden pagar después.

### 5.7 Saldar cuenta
```
total_a_pagar = saldo (deuda) + intereses_atrasados + multa_acumulada
```
Al saldar, todo queda en cero.

### 5.8 Estados del cliente
`al-dia` o `atrasado`. Pasa a `al-dia` cuando se pone al día con el interés y los atrasos.

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

- ✅ Proyecto Laravel **ya creado** localmente con Laragon en `C:\laragon\www\control-prestamos`.
- ✅ Laravel corre con `php artisan serve`.
- ✅ Prototipo visual final terminado (`index.html`) — define diseño y lógica.
- ⏳ Pendiente: subir a GitHub, crear migraciones, modelos, controladores, vistas Blade, login, PWA.

---

## 8. Migración de clientes existentes (TODO A MANO)

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

## 9. Subir el proyecto a GitHub (paso a paso)

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

## 10. Cómo trabajar con Claude Code (VS Code)

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

## 11. Acuerdo comercial (resumen)

- **Desarrollo:** ₡150.000 (una sola vez). Incluye el sistema descrito en este README.
- **Hosting + dominio:** se cobra aparte / anual (sube en la renovación).
- **Garantía:** 1 año para corregir fallos. Funciones nuevas se cotizan aparte.
- **Migración de datos:** se hace a mano con la opción de "préstamo activo" al crear el cliente. No requiere desarrollo extra más allá de ese formulario.