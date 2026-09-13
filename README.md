# Royal Beans Perú

Sitio web corporativo bilingüe de Royal Beans Perú. Está construido con Next.js y se exporta como HTML, CSS y JavaScript estáticos para funcionar en Hostinger sin un proceso Node.js en producción.

## Requisitos

- Node.js 20 o superior.
- npm 10 o superior.

## Desarrollo local

```bash
npm ci
npm run dev
```

La aplicación estará disponible en `http://localhost:3000`.

## Compilación

```bash
npm run build
```

Next.js genera el sitio estático en la carpeta `out`. Para publicarlo en Hostinger, se copia el contenido de `out` dentro de `public_html`.

## Estructura

```text
app/                 Rutas, estilos globales y metadatos
components/          Componentes, contenido y catálogo
public/              Imágenes optimizadas, fuentes, SVG y .htaccess
next.config.ts       Configuración de exportación estática
package.json         Dependencias y comandos
```

## Páginas

| Página | Español | Inglés |
| --- | --- | --- |
| Inicio | `/` | `/en/` |
| Nosotros | `/nosotros/` | `/en/about-us/` |
| Productos | `/productos/` | `/en/products/` |
| Participación | `/participacion/` | `/en/events/` |
| Presencia | `/presencia/` | `/en/presence/` |
| Impacto | `/impacto/` | `/en/impact/` |
| Contacto | `/contacto/` | `/en/contact/` |

Cada sección es un documento independiente con su propio `h1`, título SEO, descripción, URL canónica, `hreflang` y datos para redes sociales. El proyecto también genera `sitemap.xml`, `robots.txt` y JSON-LD de la organización.

## Diseño e interacción

- Paleta institucional basada en `#052F26`, tonos verdes complementarios y textos plomo oscuro.
- Bricolage Grotesque para navegación, títulos e identidad de marca.
- Carrusel circular con 29 productos, navegación mediante flechas y gesto táctil.
- Animaciones CSS que respetan `prefers-reduced-motion`.
- Iconos vectoriales SVG mediante Lucide y SVG propios para redes sociales y WhatsApp.
- Diseño adaptable desde 320 hasta 1920 px.

## Contacto

El formulario valida la información y prepara una consulta en WhatsApp. No almacena datos ni necesita PHP, MySQL o variables secretas.

## Verificación

La última revisión incluyó la compilación de producción, TypeScript, las 20 páginas estáticas, navegación bilingüe, menú accesible, catálogo, formulario, carrusel circular y 160 combinaciones de ruta y tamaño de pantalla sin desbordamiento horizontal.
