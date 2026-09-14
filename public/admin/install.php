<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
if (admin_configured()) { header('Location: /admin/'); exit; }
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        verify_csrf();
        $host = trim((string) ($_POST['host'] ?? 'localhost'));
        $port = trim((string) ($_POST['port'] ?? '3306'));
        $database = trim((string) ($_POST['database'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $adminName = trim((string) ($_POST['admin_name'] ?? ''));
        $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $database) || $username === '' || $adminName === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 10) throw new RuntimeException('Revisa los datos. La contraseña administrativa debe tener al menos 10 caracteres.');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
        $db = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        $db->exec((string) file_get_contents(dirname(__DIR__) . '/cms-config/schema.sql'));
        $db->beginTransaction();
        $user = $db->prepare('INSERT INTO admin_users (name,email,password_hash,role) VALUES (?,?,?,\'admin\')');
        $user->execute([$adminName,strtolower($adminEmail),password_hash($adminPassword,PASSWORD_DEFAULT)]);
        $lineId = (int) $db->query("SELECT id FROM product_lines WHERE slug='conventional'")->fetchColumn();
        $categories = [];
        foreach ($db->query('SELECT id,slug FROM product_categories') as $row) $categories[$row['slug']] = (int) $row['id'];
        $seed = json_decode((string) file_get_contents(dirname(__DIR__) . '/cms-config/products.seed.json'), true, 512, JSON_THROW_ON_ERROR);
        $insertProduct = $db->prepare('INSERT INTO products (line_id,category_id,image_path,is_active,featured_home,sort_order) VALUES (?,?,?,?,?,?)');
        $insertTranslation = $db->prepare('INSERT INTO product_translations (product_id,locale,name,slug) VALUES (?,?,?,?)');
        foreach ($seed as $index => $item) {
            $insertProduct->execute([$lineId,$categories[$item['category']],$item['image'],1,1,$index + 1]);
            $id = (int) $db->lastInsertId();
            foreach (['es','en'] as $locale) $insertTranslation->execute([$id,$locale,$item[$locale],admin_slug($item[$locale]) . '-' . $id]);
        }
        $db->commit();
        $local = "<?php\nreturn " . var_export(['host'=>$host,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password], true) . ";\n";
        if (file_put_contents(dirname(__DIR__) . '/cms-config/database.local.php', $local, LOCK_EX) === false) throw new RuntimeException('No se pudo guardar la configuración. Revisa los permisos de cms-config.');
        flash('success', 'Instalación completada. Inicia sesión con tu cuenta administrativa.');
        header('Location: /admin/?view=login'); exit;
    } catch (Throwable $exception) { if (isset($db) && $db->inTransaction()) $db->rollBack(); $error = $exception->getMessage(); }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar CMS | Royal Beans Perú</title><link rel="stylesheet" href="/admin/admin.css"></head><body class="auth-body"><main class="install-card"><img src="/images/logo.webp" alt="Royal Beans Perú"><p class="admin-eyebrow">Configuración inicial</p><h1>Instalar panel administrativo</h1><p>Ingresa las credenciales MySQL creadas en Hostinger y define la primera cuenta administradora.</p><?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?><form method="post" class="admin-form"><?=csrf_field()?><div class="form-grid"><label>Servidor MySQL<input name="host" value="<?=e($_POST['host'] ?? 'localhost')?>" required></label><label>Puerto<input name="port" value="<?=e($_POST['port'] ?? '3306')?>" required></label><label>Base de datos<input name="database" value="<?=e($_POST['database'] ?? '')?>" required></label><label>Usuario MySQL<input name="username" value="<?=e($_POST['username'] ?? '')?>" required></label><label class="wide">Contraseña MySQL<input type="password" name="password"></label></div><hr><div class="form-grid"><label>Nombre del administrador<input name="admin_name" required></label><label>Correo del administrador<input type="email" name="admin_email" required></label><label class="wide">Contraseña del administrador<input type="password" name="admin_password" minlength="10" required><small>Mínimo 10 caracteres.</small></label></div><button class="primary-button" type="submit">Instalar y cargar productos</button></form></main></body></html>
