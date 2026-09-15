# Royal Beans Perú — historial y estado consolidado

**Última actualización:** 14 de septiembre de 2026  
**Proyecto:** sitio web corporativo bilingüe, catálogo administrable y panel de gestión de Royal Beans Perú.

## 1. Objetivo actual

El proyecto evolucionó de un sitio corporativo estático a una solución híbrida preparada para Hostinger:

- El frente corporativo se desarrolla con Next.js y se exporta como archivos estáticos.
- PHP resuelve las fichas dinámicas de productos, las API y el panel administrativo.
- MySQL almacena productos, traducciones, fichas técnicas, contenido editable, consultas, usuarios y configuración.
- El catálogo de Inicio se alimenta de la misma fuente que la Línea Convencional. Al añadir, editar, ocultar o eliminar un producto convencional, el cambio se refleja también en Inicio.
- La web funciona en español e inglés.

## 2. Reglas visuales acordadas

Estas decisiones deben conservarse durante los siguientes cambios:

1. No modificar la tipografía institucional ni la paleta existente.
2. El verde principal es `#052F26`.
3. El rediseño debe ser sutil, profesional, moderno y adaptable.
4. El menú y el footer son componentes globales fijos. No deben rediseñarse ni alterarse al modificar una página o sección.
5. La cabecera y el footer comparten la hoja protegida `public/shared-chrome.css`.
6. El selector activo del menú utiliza una pastilla verde clara y una línea superior centrada.
7. La cabecera utiliza transparencia, desenfoque y un degradado verde visible.
8. El logo se muestra sin recuadros añadidos, acompañado por “ROYAL BEANS” y “PERÚ”.
9. Los fondos botánicos usan hojas y trigo como marcas de agua sutiles, presentes a la izquierda y a la derecha.
10. Las animaciones deben ser suaves y respetar `prefers-reduced-motion`.
11. Todo el contenido debe funcionar desde 320 px hasta monitores grandes.
12. Los SVG deben representar claramente el concepto de cada bloque y mantener un trazo visualmente equilibrado.

## 3. Cabecera global

La cabecera actual incluye:

- Logo corporativo y denominación de marca.
- Navegación: Inicio, Nosotros, Productos, Participación, Presencia, Impacto y Contáctanos.
- Selector ES/EN con icono de idioma.
- Selector activo animado tipo “gooey” ligero.
- Menú móvil con bloqueo del desplazamiento, cierre visual, tecla `Escape` y devolución del foco al botón.
- Cambio de transparencia al desplazarse.

Medidas verificadas:

| Elemento | Escritorio | Móvil |
| --- | ---: | ---: |
| Altura de cabecera | 96 px | 76 px |
| Logo | 62 × 68 px | 50 × 55 px |

La ficha dinámica de producto usa exactamente la misma estructura y CSS que las páginas generales. Ya no contiene una imitación independiente del menú.

## 4. Footer global

El footer quedó organizado en cuatro áreas:

- Marca y descripción institucional.
- Navegación del sitio.
- Información de contacto.
- Redes sociales.

Incluye una franja inferior con derechos, lema y política de privacidad. En móvil se reorganiza en una columna legible. La estructura y las reglas visuales también están centralizadas en `public/shared-chrome.css`.

## 5. Página Inicio

### Hero

- Imagen optimizada con variantes WebP y AVIF para móvil, tablet y escritorio.
- Degradado verde aplicado sobre toda la superficie de la imagen.
- Contenido centrado, llamadas a la acción y jerarquía responsive.
- Carga prioritaria de la imagen principal.

### Cuatro características

Siempre se mantienen cuatro conceptos:

| Característica | Representación SVG |
| --- | --- |
| Origen peruano | Mapa del Perú |
| Selección cuidadosa | Selección o control |
| Vocación exportadora | Barco |
| Cerca del agricultor | Avatar de usuario |

En escritorio se muestran en una sola fila. En tamaños pequeños se adaptan sin cortar el texto ni generar desbordamientos.

### Productos destacados

- Carrusel circular conectado con la Línea Convencional.
- Navegación por flechas y gesto táctil.
- Tarjetas con nombre compacto y acceso a la ficha individual.
- Contador accesible del producto activo.
- Carga diferida de imágenes cuando corresponde.
- Movimiento reducido para usuarios que lo solicitan desde el sistema.

### Bloques adicionales

- Sección institucional “¿Quiénes somos?”.
- Contador compacto de productos.
- CTA comercial rediseñada para evitar un botón excesivamente ancho.
- Footer corporativo completo.
- Datos SEO, canonical, `hreflang`, sitemap, robots y JSON-LD.

## 6. Página Nosotros

El menú Nosotros fue rediseñado para diferenciarlo del hero de Inicio, manteniendo la identidad general.

Secciones construidas:

- Hero editorial interior con título y descripción.
- Nuestra Esencia.
- Nuestra Historia.
- Nuestro Propósito.
- Misión.
- Visión.
- Lo que nos define.
- Video corporativo en español: `https://youtu.be/PaMhqpNjbPE`.
- Video en inglés pendiente de definición.
- Trazabilidad del producto en siete etapas.
- Estándares de calidad.
- CTA final.

La trazabilidad contiene siete pasos en una secuencia visual:

1. Acopio.
2. Proceso.
3. Control de calidad.
4. Inspección.
5. Carga.
6. Documentación.
7. Despacho.

Cada paso usa su propio SVG y una animación direccional hacia el siguiente. Los estándares FDA, SENASA y HACCP Interno se mantienen en una fila cuando existe espacio suficiente y utilizan un SVG de certificado coherente.

## 7. Páginas interiores restantes

Se rediseñaron los heroes y secciones de:

- Productos.
- Participación.
- Presencia.
- Impacto.
- Contacto.

Cada hero interior tiene composición propia para evitar que todas las páginas parezcan una copia de Inicio, pero conserva cabecera, tipografía, colores, espaciado general y lenguaje de componentes.

## 8. Nueva lógica de Productos

La entrada de Productos ya no presenta directamente todos los artículos. Primero muestra dos líneas comerciales:

1. **Línea Convencional**, con botón “Ver detalles”.
2. **Línea Retail**, con botón “Ver detalles”.

Cada línea tiene su propia página, hero, filtros y catálogo. La Línea Retail utiliza la misma estructura funcional que la Convencional y queda preparada para recibir productos desde el panel.

Rutas principales:

| Contenido | Español | Inglés |
| --- | --- | --- |
| Productos | `/productos/` | `/en/products/` |
| Línea Convencional | `/productos/linea-convencional/` | `/en/products/conventional-line/` |
| Línea Retail | `/productos/linea-retail/` | `/en/products/retail-line/` |
| Ficha dinámica | `/productos/{línea}/{slug}/` | `/en/products/{line}/{slug}/` |

## 9. Ficha individual de producto

La ficha dinámica mantiene el lenguaje visual de todo el sitio y actualmente contiene:

### Hero de producto

- Galería principal amplia a la izquierda.
- Miniaturas y soporte para video.
- Categoría, nombre y descripción a la derecha.
- Cuadrícula compacta con seis datos:
  - Origen comercial.
  - Presentación.
  - Nombre científico.
  - Partida arancelaria.
  - Calibre.
  - Destinos principales.
- Certificaciones o documentación por destino.
- Botones “Solicitar cotización” y “Ficha técnica”.

Se eliminó el bloque redundante que repetía:

- Origen peruano / Procedencia identificada.
- Selección de calidad / Control según requerimiento.
- Trazabilidad / Seguimiento por operación.

También se eliminó el conjunto posterior de tarjetas que repetía información comercial, datos generales, seguimiento y contacto.

### Empaque y cosecha

Ambos contenidos forman una experiencia visual unificada:

- Tarjetas numeradas para 25 kg, 50 kg y Big Bag.
- Peso secundario, material y tipo de empaque.
- Panel operativo futurista para los doce meses.
- Estados: cosecha, disponible, limitado y por confirmar.
- Indicadores luminosos, cuadrícula técnica y estado escrito por mes.
- Diseño responsive de doce, seis o cuatro columnas según el espacio disponible.

### Resto de la ficha

- Preguntas frecuentes desplegables.
- Hasta cuatro productos relacionados.
- CTA comercial final.
- Cabecera y footer globales compartidos.
- Datos estructurados `Product` mediante JSON-LD.

Recomendaciones de imagen para la ficha:

| Uso | Tamaño recomendado | Proporción |
| --- | ---: | ---: |
| Imagen principal de catálogo | 1200 × 1200 px | 1:1 |
| Galería de ficha | 1400 × 1050 px | 4:3 |
| Miniaturas | Se generan desde la misma imagen | 4:3 |

## 10. Base de datos MySQL

El esquema está en `public/cms-config/schema.sql` e incluye estas tablas:

| Tabla | Finalidad |
| --- | --- |
| `admin_users` | Usuarios, roles, contraseñas y último acceso |
| `product_lines` | Línea Convencional y Línea Retail |
| `product_categories` | Categorías del catálogo |
| `media` | Biblioteca de archivos |
| `products` | Registro principal, estado, orden e imagen |
| `product_translations` | Nombre, slug, textos y SEO por idioma |
| `product_specs` | Datos científicos, arancelarios y comerciales |
| `product_gallery` | Imágenes y videos de la ficha |
| `product_packages` | Pesos, materiales y tipos de empaque |
| `product_harvest` | Disponibilidad mensual |
| `product_certifications` | Certificaciones por producto |
| `product_relations` | Productos relacionados |
| `content_fields` | Textos e imágenes editables por página y sección |
| `settings` | Contacto, redes y datos globales |
| `inquiries` | Consultas recibidas desde la web |
| `audit_log` | Historial de acciones administrativas |

La configuración admite variables de entorno y un archivo local opcional `database.local.php`. Ese archivo está ignorado por Git y excluido de los ZIP para no publicar credenciales.

## 11. Panel administrativo

El panel se encuentra en `/admin/` e incluye:

- Instalación inicial.
- Inicio de sesión seguro.
- Roles `admin` y `editor`.
- Resumen con métricas.
- Gestión de productos.
- Filtros por Línea Convencional y Retail.
- Publicar u ocultar productos.
- Elegir qué productos aparecen en Inicio.
- Edición bilingüe de nombres, descripciones y SEO.
- Biblioteca de imágenes.
- Gestión de consultas.
- Configuración de correo, teléfono, WhatsApp, redes y direcciones.
- Creación de usuarios por un administrador.
- Registro de auditoría.
- Eliminación lógica de productos mediante `deleted_at`.
- Protección CSRF, sesiones regeneradas y contraseñas con hash.

### Editor de ficha completa

Desde “Ficha” se gestionan:

- Nombre científico.
- Partida arancelaria.
- Calibre en español e inglés.
- Destinos en español e inglés.
- PDF de ficha técnica.
- Galería múltiple.
- Video de YouTube o Vimeo.
- Empaques.
- Certificaciones.
- Calendario mensual.
- Hasta cuatro productos relacionados.

## 12. Editor visual

El editor visual se encuentra en `/admin/editor.php`.

Comportamiento implementado:

- Barra lateral administrativa persistente.
- Selector de página.
- Selector de sección.
- Lista de elementos de la sección activa.
- Vista previa en monitor, tablet y celular.
- Selección directa de textos e imágenes dentro de la vista previa.
- La navegación y las acciones públicas se bloquean dentro del editor para evitar abandonar la edición.
- Inspector lateral con formulario del elemento seleccionado.
- En móvil, el inspector puede cerrarse y abrirse nuevamente.
- Los campos se hidratan desde `/api/content.php` mediante atributos `data-cms` deterministas.
- La edición se organiza por página y sección para evitar campos visuales repetidos.
- Las fichas de producto se derivan a su editor estructurado específico.

## 13. API PHP

Endpoints actuales:

| Endpoint | Función |
| --- | --- |
| `/api/products.php` | Catálogo por línea y productos destacados de Inicio |
| `/api/content.php` | Lectura del contenido editable |
| `/api/inquiries.php` | Registro de consultas comerciales |
| `/admin/editor-api.php` | Operaciones del editor visual |

`public/router.php` resuelve las rutas dinámicas de productos en español e inglés durante el desarrollo local y en la exportación compatible con PHP.

## 14. SEO, accesibilidad y rendimiento

Se implementó:

- Un solo `h1` por página.
- Títulos y descripciones SEO.
- Canonical y `hreflang`.
- Open Graph.
- `sitemap.xml` y `robots.txt`.
- JSON-LD de organización y productos.
- Etiquetas accesibles en controles.
- Navegación con teclado.
- Menú móvil con control de foco y `Escape`.
- Carrusel con navegación táctil.
- Soporte para movimiento reducido.
- Imágenes hero en AVIF y WebP.
- Carga diferida de imágenes secundarias.
- Prevención de desbordamiento horizontal.

Queda como mejora futura añadir analítica de conversiones y eventos para WhatsApp, consultas y productos.

## 15. Verificación realizada

La revisión más reciente incluye:

- Compilación de producción con Next.js 16.3.5.
- Validación TypeScript.
- Generación de 24 rutas estáticas.
- Sintaxis PHP válida.
- Comparación calculada entre la cabecera de Inicio y la ficha dinámica.
- Igualdad de altura, padding, columnas, tamaño del logo y degradado.
- Menú móvil abierto y cerrado correctamente.
- Bloqueo del scroll y cierre con `Escape`.
- Ausencia de errores de consola en la ficha.
- 220 comprobaciones responsive sin fallos.
- Pruebas desde 320 px hasta escritorio amplio.

## 16. Desarrollo y publicación

### Desarrollo del frente

```bash
npm ci
npm run dev
```

### Exportación

```bash
npm run build
```

La salida estática se genera en `out/`. El contenido de esa carpeta se coloca en `public_html` de Hostinger junto con los archivos PHP, configuración del CMS y recursos dinámicos preparados para el servidor.

Node.js se utiliza para desarrollar y compilar. El sitio exportado no necesita mantener un proceso Node.js ejecutándose en Hostinger. Las funciones editables requieren PHP y MySQL.

### Seguridad de la publicación

- No subir `database.local.php`.
- No subir archivos `.env`.
- Crear la base de datos y usuario MySQL desde Hostinger.
- Ejecutar el instalador administrativo una sola vez.
- Usar una contraseña administrativa de al menos diez caracteres.
- Verificar permisos de `uploads/`.
- Cambiar a HTTPS antes de usar el panel en producción.

## 17. Archivos principales

```text
app/                         Rutas y estilos del frente estático
components/HomePage.tsx      Estructura de páginas y footer
components/InteractiveShell.tsx
                             Cabecera, carrusel, catálogo e hidratación CMS
public/shared-chrome.css     Cabecera y footer globales protegidos
public/product.php           Ficha dinámica de producto
public/product-detail.css    Base visual de la ficha
public/product-detail-complete.css
                             Componentes completos de la ficha
public/admin/                Panel administrativo y editor
public/api/                  API del catálogo, contenido y consultas
public/cms-config/schema.sql Esquema MySQL
public/router.php            Rutas dinámicas locales
scripts/seed-products.php    Carga inicial del catálogo
out/                         Exportación preparada para servidor
```

## 18. Estado de continuidad

El proyecto queda preparado para continuar el rediseño de las secciones internas sin modificar la cabecera ni el footer. Los próximos cambios deben concentrarse dentro de `main` y conservar los componentes compartidos. Toda nueva sección editable debe recibir una clave estable `data-cms` o un formulario estructurado en el administrador, evitando duplicar campos que ya existen en otra parte de la página.
