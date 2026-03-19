# RePostFlow — Instrucciones para GLM-5

## Identidad del Agente
Soy **GLM-5**, el agente de desarrollo dedicado exclusivamente al plugin WordPress **RePostFlow**. Mi responsabilidad es todo el desarrollo, corrección de bugs, nuevas funcionalidades, pruebas y optimización del plugin.

## Ubicación del Proyecto
- **Raíz del plugin**: `C:\xampp\htdocs\wordpress\wp-content\plugins\repostflow\`
- **Raíz de WordPress**: `C:\xampp\htdocs\wordpress\`
- **Sitio de producción**: https://juegosonlinerd.com/ (despliegue via FTP a Hostinger)
- **Repositorio GitHub**: https://github.com/joelchala/repostflow
- **Autor**: Joel Chala (https://kaggchala.com/)

## REGLAS CRÍTICAS
1. **NUNCA crear archivos .zip** — el usuario trabaja directo con carpetas
2. **NUNCA modificar archivos fuera del directorio del plugin**
3. **SIEMPRE seguir WordPress Coding Standards (WPCS)**
4. **SIEMPRE usar seguridad**: nonces, capability checks, sanitización, escaping
5. **TODAS las cadenas visibles deben usar funciones i18n** (`__()`, `_e()`, `esc_html__()`, etc.) con text domain `repostflow`
6. **Prefijar todas las funciones, clases y hooks con `rpf_` o `RPF_`**
7. **NUNCA guardar credenciales API en texto plano** — usar clase `RPF_Encryption`
8. **SIEMPRE usar `$wpdb->prepare()` para consultas de base de datos**
9. **Idioma de comunicación**: Español (Latinoamericano) con el usuario, inglés en código/comentarios

## Arquitectura del Plugin

### Patrón de Diseño Principal
- **Singleton** para clase principal `RePostFlow`
- **Interface + Abstract + Concrete** para adaptadores de redes sociales
- **Hooks de WordPress** para todas las integraciones
- **Settings en** `wp_options` como array serializado bajo clave `rpf_settings`
- **Log de compartidos en** tabla personalizada `{prefix}rpf_share_log`
- **WP-Cron** para ejecución programada

### Estructura de Directorios
```
repostflow/
├── repostflow.php                    # Bootstrap principal
├── uninstall.php                     # Limpieza al desinstalar
├── CLAUDE.md                         # Instrucciones originales
├── GLM-5.md                          # Este archivo
│
├── includes/
│   ├── class-repostflow.php          # Orquestador Singleton
│   ├── class-rpf-activator.php       # Activación
│   ├── class-rpf-deactivator.php     # Desactivación
│   ├── class-rpf-encryption.php      # Encriptación AES-256-CBC
│   ├── class-rpf-logger.php          # CRUD tabla share_log
│   ├── class-rpf-template-engine.php # Motor de variables
│   ├── class-rpf-post-selector.php   # Selección inteligente
│   ├── class-rpf-queue-manager.php   # Gestión de cola
│   ├── class-rpf-scheduler.php       # WP-Cron + pipeline
│   │
│   ├── networks/
│   │   ├── interface-rpf-network.php   # Contrato
│   │   ├── abstract-rpf-network.php    # Métodos compartidos
│   │   ├── class-rpf-network-registry.php # Registro
│   │   ├── class-rpf-twitter.php       # X/Twitter API v2
│   │   ├── class-rpf-facebook.php      # Facebook Graph API
│   │   ├── class-rpf-instagram.php     # Instagram Graph API
│   │   ├── class-rpf-linkedin.php      # LinkedIn v2 API
│   │   ├── class-rpf-telegram.php      # Telegram Bot API
│   │   ├── class-rpf-pinterest.php    # Pinterest API v5 (NUEVO)
│   │   ├── class-rpf-threads.php       # Threads Meta API (NUEVO)
│   │   ├── class-rpf-bluesky.php       # Bluesky AT Protocol (NUEVO)
│   │   ├── class-rpf-discord.php      # Discord Webhook (NUEVO)
│   │   └── class-rpf-mastodon.php      # Mastodon API (NUEVO)
│   │
│   └── admin/
│       ├── class-rpf-admin.php         # Menú + renderizado
│       ├── class-rpf-settings.php      # Settings API
│       ├── class-rpf-ajax-handler.php  # Handlers AJAX
│       ├── class-rpf-dashboard-widget.php # Widget dashboard
│       └── class-rpf-list-table.php    # Placeholder futuro
│
├── templates/admin/
│   ├── dashboard-page.php      # Dashboard
│   ├── settings-page.php       # Configuración (4 tabs)
│   ├── queue-page.php          # Cola pendiente
│   └── history-page.php        # Historial
│
├── assets/
│   ├── css/rpf-admin.css       # Estilos admin
│   └── js/rpf-admin.js         # JavaScript admin
│
└── languages/                  # Archivos i18n
```

### Esquema de Base de Datos
**Tabla: `{prefix}rpf_share_log`**
| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | BIGINT AUTO_INCREMENT | PK |
| post_id | BIGINT | ID del post WordPress |
| network | VARCHAR(50) | Slug de red (twitter, facebook, etc.) |
| status | VARCHAR(20) | queued, processing, published, failed, skipped |
| message | TEXT | Mensaje enviado |
| image_url | VARCHAR(500) | URL imagen destacada |
| response | TEXT | Respuesta JSON de API |
| external_id | VARCHAR(255) | ID en red externa |
| external_url | VARCHAR(500) | URL en red externa |
| scheduled_at | DATETIME | Programado para |
| shared_at | DATETIME | Compartido en |
| created_at | DATETIME | Creado en |

### Variables de Plantilla Disponibles
`{title}`, `{url}`, `{raw_url}`, `{excerpt}`, `{author}`, `{date}`, `{categories}`, `{tags}`, `{hashtags}`, `{site_name}`, `{site_url}`, `{newline}`

### Estado Actual (v1.1.0)

#### Completado ✅
- Arquitectura OOP completa
- **10 adaptadores de red**:
  - X/Twitter (API v2, OAuth 1.0a)
  - Facebook (Graph API)
  - Instagram (Graph API, imagen requerida)
  - LinkedIn (v2 API)
  - Telegram (Bot API, HTML formatting)
  - Pinterest (API v5, imagen requerida) — **NUEVO v1.1.0**
  - Threads (Meta Graph API) — **NUEVO v1.1.0**
  - Bluesky (AT Protocol) — **NUEVO v1.1.0**
  - Discord (Webhook, setup sencillo) — **NUEVO v1.1.0**
  - Mastodon (Federado, cualquier instancia) — **NUEVO v1.1.0**
- Selector de posts con cooldown y filtros de categoría
- Motor de plantillas con 12 variables
- Scheduler WP-Cron con 9 frecuencias
- Gestor de cola con tracking de estado
- UI Admin: Dashboard, Settings (4 tabs), Queue, History
- Handlers AJAX: test connection, share now, clear queue, delete log, toggle
- Widget de dashboard
- Encriptación de credenciales API (AES-256-CBC)
- CSS responsive
- Limpieza en uninstall
- readme.txt para WordPress.org
- Límites de caracteres por red en template engine
- Templates por defecto para todas las redes
- Queries SQL preparadas con `$wpdb->prepare()`

#### Correcciones Aplicadas v1.1.0 🔧
- Nombre de clase `RPF_LinkedIn` corregido
- Validación de Board ID en Pinterest
- Campo `password` agregado a lista de encriptados
- Campo `webhook_url` agregado a lista de encriptados

#### Roadmap Pendiente 🔜
- **TikTok adapter** — complejo, enfocado en video
- **Flujo OAuth in-plugin** — actualmente tokens manuales
- **Vista previa antes de publicar**
- **Reintento masivo de fallidos**
- **Notificaciones por email**
- **Implementación WP_List_Table**
- **Endpoints REST API**
- **Soporte multisitio**
- **Tracking de rate limits**
- **A/B testing de plantillas**
- **Integración de analytics**
- **Shortcodes frontend**
- **Archivo POT de traducción**
- **Tests unitarios PHPUnit**
- **Contador de caracteres en tiempo real**
- **Protección de container ID duplicado**

## Cómo Agregar una Nueva Red

1. Crear `includes/networks/class-rpf-{network}.php`
2. Extender `RPF_Network_Abstract` e implementar `RPF_Network_Interface`
3. Implementar todos los métodos de la interfaz
4. Registrar en `RePostFlow::register_networks()` en `class-repostflow.php`
5. Agregar `require_once` en `repostflow.php`

## Hooks de Extensibilidad

### Filtros
- `rpf_registered_networks` — agregar redes personalizadas
- `rpf_post_query_args` — modificar WP_Query de selección
- `rpf_template_variables` — agregar variables de plantilla
- `rpf_message_before_publish` — modificar mensaje antes de enviar
- `rpf_should_share_post` — permitir/denegar shares específicos

### Acciones
- `rpf_register_networks` — registrar adaptadores adicionales
- `rpf_after_process` — después de ejecutar cron
- `rpf_after_share_success` — después de share exitoso
- `rpf_after_share_failure` — después de share fallido

## Pipeline de Procesamiento (cron rpf_process_queue)
```
1. Verificar si habilitado → salir si no
2. Procesar items en cola existente primero
3. Obtener redes configuradas
4. Usar RPF_Post_Selector para elegir posts aleatorios
5. Por cada post × cada red:
   a. Obtener plantilla de red
   b. Renderizar mensaje con RPF_Template_Engine
   c. Obtener URL de imagen destacada
   d. Encolar via RPF_Queue_Manager
6. Procesar cola:
   a. Desencolar siguiente item
   b. Marcar como processing
   c. Llamar network->publish()
   d. Marcar published o failed
   e. Disparar hook success o failure
```

## Flujo de Trabajo para el Agente

Al recibir una tarea:
1. **Leer** el archivo relevante primero con la herramienta `read`
2. **Entender** el contexto y la arquitectura existente
3. **Planificar** los cambios necesarios
4. **Implementar** siguiendo WPCS y convenciones del plugin
5. **Verificar** sintaxis PHP si es necesario
6. **Reportar** cambios de forma concisa en español

## Comandos Útiles
```bash
# Verificar sintaxis PHP
php -l archivo.php

# Buscar en código
grep -r "función" includes/

# Listar archivos del plugin
ls -la includes/networks/
```

---
*Este archivo define las instrucciones específicas para GLM-5 como agente dedicado de desarrollo de RePostFlow.*