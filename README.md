# Royal Beans Perú

Web corporativa bilingüe y catálogo administrable de [Royal Beans Perú](https://royalbeansperu.com/). El proyecto combina un frente desarrollado con **Next.js 16** y exportado como archivos estáticos con **PHP y MySQL** para contenido editable, productos, consultas, SEO dinámico y el panel administrativo. En Hostinger no se necesita un proceso Node.js: Node se usa para desarrollar y generar los archivos que se publican.

## Qué incluye

- Sitio público en español e inglés, adaptable a móvil, tablet y escritorio.
- Catálogos **Línea a Granel** y **Línea Retail**, categorías y fichas de producto administrables.
- Panel `/admin/` para productos, imágenes, edición visual, anuncios, contenido, consultas, configuración y usuarios según sus permisos.
- Formulario comercial que envía las consultas a `/api/inquiries.php` y ofrece continuación por WhatsApp.
- Biblioteca de medios con Cloudflare R2, compresión de imágenes compatibles a WebP, detección de duplicados y actualización de referencias al reemplazarlas.
- SEO con rutas bilingües, metadatos, datos estructurados, `robots.txt`, sitemap y respuestas PHP para contenido dinámico.
- Animaciones de carga y desplazamiento con soporte para `prefers-reduced-motion`.

## Arquitectura

| Componente | Función |
| --- | --- |
| `app/` y `components/` | Rutas, páginas, catálogo y componentes React/Next.js. |
| `public/` | Recursos estáticos y código PHP publicado en Hostinger. |
| `public/admin/` | Panel administrativo y editores. |
| `public/api/` | API PHP para productos, contenido, anuncios y consultas. |
| `public/cms-config/` | Esquema, migraciones y conexión a MySQL. |
| `public/.htaccess` | Rutas, redirecciones y conexión con las páginas PHP dinámicas. |
| `scripts/build-hostinger.ps1` | Generación y validación de paquetes completos para Hostinger. |

El HTML inicial se genera con Next.js (`output: "export"`). En Hostinger, Apache y PHP sirven las rutas administrables y consultan MySQL; el navegador también solicita datos a las API PHP. Por eso, **publicar únicamente `out/` o únicamente el código de GitHub no equivale a actualizar toda la web**.

## Rutas principales

| Sección | Español | Inglés |
| --- | --- | --- |
| Inicio | `/` | `/en/` |
| Nosotros | `/nosotros/` | `/en/about-us/` |
| Línea a Granel | `/productos/linea-a-granel/` | `/en/products/bulk-line/` |
| Línea Retail | `/productos/linea-retail/` | `/en/products/retail/` |
| Participación | `/participacion/` | `/en/events/` |
| Impacto | `/impacto/` | `/en/impact/` |
| Contacto | `/contacto/` | `/en/contact/` |
| Términos y Condiciones | `/terminos-y-condiciones/` | `/en/terms-and-conditions/` |
| Política de Privacidad | `/politica-de-privacidad/` | `/en/privacy-policy/` |

`/productos/` y `/en/products/` redirigen a sus respectivas líneas a granel. Las fichas de productos tienen URLs propias generadas con sus identificadores de ruta.

## Desarrollo local

Se necesita Node.js compatible con Next.js 16 y npm. Para probar el panel y los datos dinámicos también se necesita PHP con PDO MySQL, cURL y GD con WebP, además de una base MySQL. Las cargas AVIF requieren soporte AVIF en GD.

```powershell
npm ci
npm run dev
```

La web Next.js se abre en `http://localhost:3000`. Ese servidor por sí solo no ejecuta PHP: las funciones de CMS, API y administración deben probarse con un servidor PHP/Apache configurado para este proyecto (por ejemplo, XAMPP) y con MySQL conectado.

La conexión local o de Hostinger se configura copiando `public/cms-config/database.local.example.php` a `public/cms-config/database.local.php` y completando las credenciales correspondientes. **Nunca subas ese archivo ni contraseñas a GitHub.** Las variables `NEXT_PUBLIC_DEPLOYMENT_ENV`, `NEXT_PUBLIC_SITE_URL` y `NEXT_PUBLIC_INDEXABLE` controlan el destino y la indexación de la compilación; `.env.example` muestra un ejemplo para staging.

## Compilación y publicación en Hostinger

Los comandos de empaquetado están definidos para PowerShell en Windows:

```powershell
npm run build                 # Exportación estática en out/
npm run dist:staging          # dist-staging/ y dist-staging.zip
npm run dist:production       # dist-production/ y dist-production.zip
```

`dist:production` valida el dominio, `robots.txt`, sitemap y metadatos antes de crear el paquete completo. Staging se genera con `noindex`; producción, para `https://royalbeansperu.com`, es indexable. Los directorios `out/`, `dist-*` y los ZIP generados no se publican en Git.

Para una instalación completa, extrae **el contenido** de `dist-production.zip` directamente en `public_html/`, de modo que `index.html`, `.htaccess`, `admin/`, `api/` y `cms-config/` queden en la raíz pública. El paquete excluye el instalador y archivos de credenciales o semillas que no deben sobrescribirse en producción.

Para una actualización **parcial**, coloca en `public_html/` solamente los archivos corregidos conservando exactamente sus carpetas relativas. Un `dist` parcial entregado para una corrección no sustituye al paquete completo ni debe usarse para vaciar `public_html/`. Antes de sobrescribir, respalda el sitio y la base de datos. Conserva `public_html/cms-config/database.local.php`, `public_html/uploads/` y los contenidos de MySQL; no vuelvas a importar la base por una actualización de diseño. Después, purga la caché de Hostinger/CDN y comprueba las páginas afectadas.

Cloudflare R2 almacena los medios configurados en el CMS, pero **no sincroniza MySQL**. La base publicada sigue siendo la fuente de los productos y textos editados desde el panel.

## Verificación

```powershell
npm run build
php -l public/admin/_bootstrap.php
php -l public/admin/index.php
php scripts/test-admin-media.php
```

La prueba PHP comprueba referencias de medios y compresión WebP; necesita las extensiones PHP correspondientes. El proyecto también contiene comprobaciones especializadas en `scripts/` para contenido CMS y catálogos.

Para detalles de instalación de MySQL y sincronización local, consulta [la guía de Hostinger](docs/INSTALACION-HOSTINGER-Y-MYSQL.md). Algunos nombres de paquetes y rutas históricas de esa guía pueden diferir del estado actual; para generar paquetes usa los comandos de este README y `scripts/build-hostinger.ps1`.
