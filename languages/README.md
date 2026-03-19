# Traducciones de RePostFlow

Este directorio contiene los archivos de traducción del plugin RePostFlow.

## Archivos de traducción

### Plantilla (POT)
- `repostflow.pot` - Plantilla de traducción con todas las cadenas del plugin

### Traducciones disponibles

#### Español (España) - es_ES
- `repostflow-es_ES.po` - Archivo de traducción (texto editable)
- `repostflow-es_ES.mo` - Archivo compilado (binario, se genera a partir del .po)

#### Español (México) - es_MX
- `repostflow-es_MX.po` - Archivo de traducción
- `repostflow-es_MX.mo` - Archivo compilado

#### Español (Colombia) - es_CO
- `repostflow-es_CO.po` - Archivo de traducción
- `repostflow-es_CO.mo` - Archivo compilado

## Cómo compilar los archivos .mo

Los archivos .mo son versiones binarias compiladas que WordPress utiliza para cargar las traducciones eficientemente. Debes compilar los archivos .po a .mo usando una de estas opciones:

### Opción 1: Usar Poedit (Recomendado)

1. Descarga e instala Poedit desde https://poedit.net/
2. Abre cada archivo .po:
   - `repostflow-es_ES.po`
   - `repostflow-es_MX.po`
   - `repostflow-es_CO.po`
3. En Poedit, ve a **Archivo → Compilar como MO...**
4. Guarda el archivo .mo en el mismo directorio `languages/`

### Opción 2: Usar WP-CLI

Si tienes WP-CLI instalado, ejecuta desde el directorio del plugin:

```bash
wp i18n make-mo languages/
```

O para compilar un archivo específico:

```bash
wp i18n make-mo languages/repostflow-es_ES.po
```

### Opción 3: Usar msgfmt (gettext)

Si tienes gettext instalado:

```bash
cd languages/
msgfmt repostflow-es_ES.po -o repostflow-es_ES.mo
msgfmt repostflow-es_MX.po -o repostflow-es_MX.mo
msgfmt repostflow-es_CO.po -o repostflow-es_CO.mo
```

## Diferencias entre variantes de español

| Característica | es_ES (España) | es_MX (México) | es_CO (Colombia) |
|---------------|----------------|----------------|------------------|
| Formalidad | Tú (informal) | Tú (informal) | Usted (formal) |
| Ejemplo | "Guarda ajustes" | "Guarda ajustes" | "Guarde ajustes" |
| Vocabulario | Ajustes | Ajustes | Ajustes |

## Cómo agregar una nueva traducción

1. Copia el archivo `repostflow.pot` y renómbralo a `repostflow-[código de idioma].po`
   - Ejemplo: `repostflow-fr_FR.po` para Francés (Francia)
   - Ejemplo: `repostflow-de_DE.po` para Alemán (Alemania)

2. Edita el encabezado del archivo .po para actualizar:
   - `Language: [código]`
   - `Language-Team: [Nombre del equipo]`

3. Traduce todas las cadenas `msgstr ""

4. Compila el archivo .po a .mo usando uno de los métodos anteriores

5. Sube el plugin con los nuevos archivos de traducción

## Cómo funciona la carga de idiomas

WordPress carga automáticamente el idioma correcto según la configuración del sitio:

1. Si el sitio está en `es_ES` → carga `repostflow-es_ES.mo`
2. Si el sitio está en `es_MX` → carga `repostflow-es_MX.mo`
3. Si el sitio está en `es_CO` → carga `repostflow-es_CO.mo`
4. Si el sitio está en `en_US` → usa las cadenas originales del código

El archivo `repostflow.pot` es la plantilla que sirve como base para todas las traducciones.

## Términos técnicos sin traducir

Los siguientes términos se mantienen en inglés porque son técnicos:

- webhook
- API
- OAuth
- cron
- token
- endpoint
- JSON
- HTTP

## Soporte

Para reportar problemas con las traducciones, crea un issue en:
https://github.com/joelchala/repostflow/issues
