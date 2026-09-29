# Instalación de Royal Beans Perú en Hostinger con MySQL

## Archivos de publicación

- Sitio web: `royalbeans-hostinger-ficha-producto-refinada-2026-09-14.zip`
- Base completa: `royalbeans-base-datos-completa-2026-09-14.sql`

La base de datos local no es un archivo dentro de la web: está ejecutándose en MySQL. `schema.sql` contiene solamente la estructura instalable; el archivo `royalbeans-base-datos-completa-2026-09-14.sql` es la copia completa con las 16 tablas y los datos actuales.

## 1. Subir la web

1. Abre hPanel de Hostinger.
2. Entra en **Sitios web → Dashboard → Administrador de archivos**.
3. Abre `public_html`.
4. Sube el ZIP del sitio.
5. Extráelo directamente dentro de `public_html`.
6. Confirma que `index.html`, `.htaccess`, `product.php`, `admin/`, `api/` y `cms-config/` estén directamente dentro de `public_html`, no dentro de otra carpeta intermedia.

### Si la web ya está publicada

No sigas los pasos de creación e importación de base de datos de las secciones siguientes. Para actualizar únicamente el código:

1. Exporta la base publicada desde phpMyAdmin.
2. Conserva `public_html/cms-config/database.local.php` y `public_html/uploads/`.
3. No vacíes `public_html` y no importes ningún SQL.
4. Extrae el ZIP sobre `public_html`, sobrescribiendo los archivos incluidos pero sin borrar archivos existentes.
5. Purga la caché de Hostinger/CDN.

El paquete de producción excluye `database.local.php`, `schema.sql`, `products.seed.json` y `admin/install.php`; actualizar archivos no modifica la base MySQL.

## Sincronizar producción hacia la web local

Cloudflare R2 almacena imágenes, pero no replica MySQL. La base de Hostinger debe considerarse la fuente principal de productos, textos y configuración del CMS.

Después de descargar un respaldo nuevo desde phpMyAdmin, ejecuta desde `web_royalbeans`:

```powershell
php scripts/sync-hostinger-to-local.php "C:\ruta\al\respaldo.sql" "https://tu-dominio.com"
```

El comando:

- Solo acepta una conexión MySQL local (`localhost`, `127.0.0.1` o `::1`).
- Crea un respaldo previo en `_local_cache/db-backups/`.
- Reemplaza la base local con el dump de producción.
- Descarga las imágenes `/uploads/` que falten.
- Refleja esas imágenes en `out/` cuando el servidor local usa ese directorio.
- Informa la cantidad final de productos, medios e imágenes pendientes.

No publiques los respaldos locales ni los guardes dentro de `public/`.

## Flujo recomendado con Cloudflare R2

1. Configura R2 desde `/admin/configuration.php` en Hostinger.
2. Desde **Imágenes**, ejecuta una sola vez **Migrar imágenes locales a R2**.
3. Verifica que el panel indique que todo está sincronizado.
4. A partir de ese momento, las nuevas imágenes subidas desde el CMS se guardan directamente en R2 y su URL pública queda registrada en MySQL.
5. Para actualizar local, exporta la base de Hostinger y ejecuta el comando anterior. Las URLs R2 funcionarán también en local sin copiar las imágenes.

No existe sincronización automática de MySQL por activar Cloudflare. Una réplica bidireccional entre local y producción puede sobrescribir cambios recientes. Para evitar conflictos, edita contenidos en el CMS de Hostinger y usa local principalmente para cambios de código y diseño.

## 2. Crear la base de datos

1. En hPanel abre **Sitios web → Dashboard → Gestión de bases de datos**.
2. Crea una base MySQL y un usuario con una contraseña segura.
3. Copia los nombres completos que muestra Hostinger. Normalmente incluyen un prefijo de cuenta como `u123456789_`.
4. El servidor MySQL para una web alojada en Hostinger es `localhost`.

Referencia oficial: <https://support.hostinger.com/es/articles/1583552-como-encontrar-detalles-de-la-base-de-datos-mysql>

## 3. Importar la copia completa

1. En la base recién creada pulsa **Entrar a phpMyAdmin**.
2. Comprueba que la base esté vacía.
3. Pulsa **Importar**.
4. Selecciona `royalbeans-base-datos-completa-2026-09-14.sql`.
5. Mantén el formato SQL y la codificación UTF-8.
6. Pulsa **Importar** o **Continuar**.
7. Verifica que aparezcan 16 tablas, entre ellas `products`, `product_translations`, `product_specs`, `content_fields` y `admin_users`.

Referencia oficial: <https://support.hostinger.com/en/articles/1884149-how-to-import-a-database-with-phpmyadmin>

## 4. Conectar la web

Dentro de `public_html/cms-config/`:

1. Copia `database.local.example.php`.
2. Renombra la copia como `database.local.php`.
3. Edita únicamente los valores del arreglo:

```php
<?php
declare(strict_types=1);

return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'NOMBRE_COMPLETO_DE_LA_BASE',
    'username' => 'USUARIO_COMPLETO_DE_MYSQL',
    'password' => 'CONTRASENA_DE_MYSQL',
];
```

Usa exactamente el nombre de base y usuario mostrados por Hostinger, incluido el prefijo de la cuenta.

No añadas estas credenciales a GitHub ni compartas `database.local.php`. El proyecto ya lo excluye de Git y de los paquetes generados.

## 5. No ejecutar el instalador después de importar

El respaldo completo ya contiene el esquema, los 29 productos y los usuarios administrativos existentes. Después de importarlo y crear `database.local.php`, abre directamente:

- Sitio: `https://tu-dominio.com/`
- Productos: `https://tu-dominio.com/productos/linea-convencional/`
- Panel: `https://tu-dominio.com/admin/`

No uses `/admin/install.php` sobre esta base importada porque ese instalador está destinado a una base vacía y vuelve a cargar el esquema y el catálogo inicial.

## 6. Comprobación

Prueba en este orden:

1. `https://tu-dominio.com/api/products.php?line=conventional`
2. Una ficha individual desde el catálogo.
3. `https://tu-dominio.com/admin/`

Si el endpoint devuelve productos en JSON, la conexión está funcionando.

## 7. Si aparece “Contenido temporalmente no disponible”

Ese mensaje significa que PHP no pudo abrir la conexión PDO con MySQL. Revisa:

- El archivo se llama exactamente `database.local.php`.
- Está dentro de `public_html/cms-config/`.
- El host es `localhost`.
- La base y el usuario incluyen el prefijo asignado por Hostinger.
- La contraseña coincide con la del usuario MySQL.
- El usuario está asignado a esa base.
- Las 16 tablas fueron importadas.
- La versión PHP seleccionada tiene habilitado `PDO MySQL`.

El sitio local ya fue verificado con 16 tablas y 29 productos, por lo que el mensaje en Hostinger se resuelve configurando las credenciales de producción correctamente.
