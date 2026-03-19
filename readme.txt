=== RePostFlow - Republicado Automático en Redes Sociales ===
Contributors: joelchala
Tags: social media, auto share, twitter, facebook, instagram, linkedin, telegram, pinterest, threads, bluesky, discord, mastodon, repost, evergreen, redes sociales, automatico
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Republica automáticamente tu contenido evergreen de WordPress en múltiples redes sociales con un horario inteligente.

== Descripción ==

**RePostFlow** selecciona y republica automáticamente tu contenido existente de WordPress en múltiples redes sociales según un horario personalizable. Dale nueva vida a tu contenido antiguo y genera tráfico constante desde redes sociales — ¡en piloto automático!

Este plugin es ideal para sitios con contenido evergreen (juegos, tutoriales, recetas, artículos atemporales) que quieres mantener activos en redes sociales sin esfuerzo manual.

= Características Principales =

* **Selección Inteligente de Posts** — Elige posts publicados aleatoriamente con período de enfriamiento para evitar duplicados
* **10 Redes Sociales** — X/Twitter, Facebook, Instagram, LinkedIn, Telegram, Pinterest, Threads, Bluesky, Discord, Mastodon
* **Horarios Personalizables** — Desde cada2 horas hasta una vez por semana
* **Motor de Plantillas** — Mensajes personalizados por red con variables como {title}, {url}, {hashtags}
* **Soporte de Imágenes** — Adjunta automáticamente la imagen destacada del post
* **Seguimiento UTM** — Parámetros UTM opcionales para analíticas
* **Sistema de Cola** — Visualiza y administra los próximos compartidos
* **Historial de Compartidos** — Registro completo con estado de cada publicación
* **Widget en Dashboard** — Vista rápida desde el panel de WordPress
* **Extensible** — Hooks y filtros para desarrolladores
* **Seguro** — Credenciales API encriptadas, verificación nonce en todas las acciones

= Redes Sociales Soportadas =

* **X (Twitter)** — API v2 con OAuth 1.0a, soporta texto + imagen
* **Facebook** — Graph API, publica en Páginas con enlace o foto
* **Instagram** — Cuentas de negocio vía Graph API (imagen requerida)
* **LinkedIn** — Perfiles personales y páginas de empresa
* **Telegram** — Bot API para canales y grupos, soporta formato HTML
* **Pinterest** — Pinea imágenes en tableros (imagen requerida)
* **Threads** — Nueva red social de Meta vía Graph API
* **Bluesky** — Red del protocolo AT con soporte de texto e imagen
* **Discord** — Integración vía Webhook para canales (configuración más sencilla)
* **Mastodon** — Red social federada, soporta cualquier instancia

= Cómo Funciona =

1. Conecta tus cuentas de redes sociales en Ajustes → Redes
2. Configura tu horario de publicación y reglas de selección de posts
3. Personaliza las plantillas de mensaje para cada red
4. ¡Habilita el auto-compartido y RePostFlow hace el resto!

= Extensible =

Los desarrolladores pueden agregar redes personalizadas usando la acción `rpf_register_networks` y modificar el comportamiento con filtros como `rpf_post_query_args`, `rpf_template_variables`, y `rpf_should_share_post`.

= Casos de Uso =

* **Sitios de juegos** — Republicar juegos automáticamente cada día
* **Blogs de tutoriales** — Mantener contenido técnico siempre visible
* **Sitios de recetas** — Compartir recetas antiguas periódicamente
* **Portales de noticias** — Redirigir tráfico a archivos importantes
* **E-commerce** — Promocionar productos evergreen

== Instalación ==

1. Sube la carpeta `repostflow` a `/wp-content/plugins/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Ve a **RePostFlow → Ajustes** para configurar tus redes
4. ¡Habilita el auto-compartido y disfruta de presencia automática en redes sociales!

== Preguntas Frecuentes ==

= ¿Funciona con tipos de posts personalizados? =

¡Sí! Puedes seleccionar cualquier tipo de post público en la pestaña de Ajustes Generales.

= ¿Cómo funciona la selección inteligente? =

RePostFlow elige posts publicados aleatoriamente respetando un período de enfriamiento. No volverá a compartir el mismo post dentro del período configurado (por defecto: 7 días). También puedes establecer una antigüedad mínima del post y incluir/excluir categorías específicas.

= ¿Necesito claves API para cada red? =

Sí. Cada red social requiere credenciales API. RePostFlow proporciona instrucciones en el panel de ajustes para cada red. Las credenciales se encriptan antes de guardarse.

= ¿Compartirá el mismo post dos veces? =

No dentro del período de enfriamiento. El plugin rastrea todos los compartidos en un registro y excluye los posts compartidos recientemente de la selección.

= ¿Funciona con WordPress cron? =

Sí, utiliza WP-Cron. Para máxima confiabilidad, recomendamos configurar un cron job real en el servidor que ejecute `wp-cron.php` cada 15 minutos.

= ¿Qué pasa si una publicación falla? =

El error se registra en el historial con el mensaje de error. Puedes ver los detalles en RePostFlow → Historial.

= ¿Puedo usar mi propia plantilla de mensaje? =

¡Sí! Cada red tiene su propia plantilla que puedes personalizar con variables como `{title}`, `{url}`, `{excerpt}`, `{hashtags}`, `{author}`, etc.

== Capturas de Pantalla ==

1. Panel principal con estadísticas, redes conectadas y actividad reciente
2. Página de ajustes con paneles de configuración de redes
3. Historial de compartidos con filtros y paginación
4. Página de gestión de cola

== Registro de Cambios ==

= 1.1.0 =
* NUEVO: Soporte para Pinterest (requiere imagen, API v5)
* NUEVO: Soporte para Threads (red social de Meta)
* NUEVO: Soporte para Bluesky (red del protocolo AT)
* NUEVO: Soporte para Discord Webhook (configuración más sencilla)
* NUEVO: Soporte para Mastodon (red federada, cualquier instancia)
* CORRECCIÓN: Nombre de clase LinkedIn corregido
* CORRECCIÓN: Preparación de queries SQL para seguridad
* CORRECCIÓN: Validación de Board ID en Pinterest
* MEJORA: Límites de caracteres para todas las redes
* MEJORA: Plantillas por defecto para todas las redes
* MEJORA: Validación de credenciales mejorada

= 1.0.0 =
* Lanzamiento inicial
* Soporte para X/Twitter, Facebook, Instagram, LinkedIn y Telegram
* Selección inteligente de posts con enfriamiento y filtros de categoría
* Plantillas de mensaje personalizables por red
* Sistema de cola e historial de compartidos
* Widget en dashboard
* Soporte para seguimiento UTM
* Almacenamiento encriptado de credenciales

== Nota de Actualización ==

= 1.1.0 =
¡Actualización importante con 5 nuevas redes sociales! Pinterest, Threads, Bluesky, Discord y Mastodon ahora están soportados. Por favor vuelve a guardar los ajustes de tus redes después de actualizar.

= 1.0.0 =
Primera versión de RePostFlow. ¡Conecta tus cuentas sociales y comienza a compartir automáticamente!

== Soporte ==

Para reportar bugs o solicitar funciones, visita: https://github.com/joelchala/repostflow/issues