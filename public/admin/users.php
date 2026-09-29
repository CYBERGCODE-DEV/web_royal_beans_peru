<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$user = require_permission('users', 'view');
$db = admin_db();
$error = '';
$editingId = (int) ($_GET['id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        require_permission('users', $id ? 'edit' : 'create');
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'editor';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Completa un nombre y correo válidos.');
        $permissions = [];
        if ($role !== 'admin') {
            foreach (permission_catalog() as $section => $definition) {
                foreach (array_keys($definition['actions']) as $action) {
                    if (isset($_POST['permission'][$section][$action])) $permissions[$section][] = $action;
                }
            }
        }
        $permissionsJson = $role === 'admin' ? null : json_encode($permissions, JSON_UNESCAPED_UNICODE);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $password = (string) ($_POST['password'] ?? '');
        if ($id) {
            if ($id === (int) $user['id'] && !$active) throw new RuntimeException('No puedes desactivar tu propia cuenta.');
            $values = [$name, $email, $role, $permissionsJson, $active];
            $sql = 'UPDATE admin_users SET name=?,email=?,role=?,permissions_json=?,is_active=?';
            if ($password !== '') {
                if (strlen($password) < 10) throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');
                $sql .= ',password_hash=?';
                $values[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=?';
            $values[] = $id;
            $db->prepare($sql)->execute($values);
            audit($db, (int) $user['id'], 'edit', 'user', $id, ['role' => $role]);
            flash('success', 'Usuario y permisos actualizados.');
        } else {
            if (strlen($password) < 10) throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');
            $statement = $db->prepare('INSERT INTO admin_users (name,email,password_hash,role,permissions_json,is_active) VALUES (?,?,?,?,?,?)');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $permissionsJson, $active]);
            audit($db, (int) $user['id'], 'create', 'user', $db->lastInsertId(), ['role' => $role]);
            flash('success', 'Usuario creado con sus permisos.');
        }
        header('Location: /admin/users.php'); exit;
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}

$editing = ['id'=>0,'name'=>'','email'=>'','role'=>'editor','permissions_json'=>'{}','is_active'=>1];
if ($editingId) { $statement=$db->prepare('SELECT id,name,email,role,permissions_json,is_active FROM admin_users WHERE id=?');$statement->execute([$editingId]);$editing=$statement->fetch()?:$editing; }
$selected = json_decode((string) ($editing['permissions_json'] ?? '{}'), true) ?: [];
$users = $db->query('SELECT id,name,email,role,is_active,last_login_at FROM admin_users ORDER BY is_active DESC,name')->fetchAll();
$flash = pull_flash();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios y permisos | Royal Beans Perú</title><link rel="stylesheet" href="/admin/admin.css"></head><body class="admin-body"><aside class="admin-sidebar"><a class="admin-brand" href="/admin/"><img src="/images/logo.webp" alt=""><span><strong>ROYAL BEANS</strong><small>ADMINISTRACIÓN</small></span></a><nav><a href="/admin/">Resumen</a><a href="/admin/?view=products">Productos</a><a href="/admin/editor.php">Editor visual</a><a href="/admin/?view=media">Imágenes</a><a href="/admin/?view=inquiries">Consultas</a><a href="/admin/?view=settings">Configuración</a><a class="active" href="/admin/users.php">Usuarios y permisos</a></nav><div class="admin-user"><strong><?=e($user['name'])?></strong><small><?=e($user['role'])?></small><a href="/admin/?view=logout">Cerrar sesión</a></div></aside><main class="admin-main"><header class="admin-top"><div><p class="admin-eyebrow">Control de acceso</p><h1>Usuarios y permisos</h1><p>Define qué sección puede usar cada persona y qué acciones tendrá disponibles.</p></div><a class="site-button" href="/admin/users.php">Nuevo usuario</a></header><?php if($flash):?><div class="alert <?=e($flash[0])?>"><?=e($flash[1])?></div><?php endif;?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?><div class="permission-layout"><form method="post" class="admin-panel admin-form permission-form"><?=csrf_field()?><input type="hidden" name="id" value="<?=e($editing['id'])?>"><div class="panel-heading"><div><p class="admin-eyebrow"><?=$editing['id']?'Editar cuenta':'Nueva cuenta'?></p><h2><?=$editing['id']?e($editing['name']):'Datos del usuario'?></h2></div><span class="status <?=$editing['is_active']?'published':'draft'?>"><?=$editing['is_active']?'Activo':'Inactivo'?></span></div><div class="form-grid"><label>Nombre<input name="name" value="<?=e($editing['name'])?>" required></label><label>Correo<input type="email" name="email" value="<?=e($editing['email'])?>" required></label><label>Rol<select name="role" id="role"><option value="editor" <?=$editing['role']==='editor'?'selected':''?>>Usuario personalizado</option><option value="admin" <?=$editing['role']==='admin'?'selected':''?>>Administrador total</option></select></label><label>Contraseña<input type="password" name="password" minlength="10" <?=$editing['id']?'':'required'?>><small><?=$editing['id']?'Déjala vacía para conservarla.':'Mínimo 10 caracteres.'?></small></label><label class="check wide"><input type="checkbox" name="is_active" <?=$editing['is_active']?'checked':''?>> Cuenta activa</label></div><section id="permission-matrix" class="permission-matrix"><header><h3>Acceso por sección</h3><p>Activa sólo lo necesario para este usuario.</p></header><?php foreach(permission_catalog() as $section=>$definition):?><article><strong><?=e($definition['label'])?></strong><div><?php foreach($definition['actions'] as $action=>$label):?><label><input type="checkbox" name="permission[<?=e($section)?>][<?=e($action)?>]" <?=in_array($action,$selected[$section]??[],true)?'checked':''?>><?=e($label)?></label><?php endforeach;?></div></article><?php endforeach;?></section><div class="form-actions"><a href="/admin/users.php">Cancelar</a><button class="primary-button">Guardar usuario</button></div></form><section class="admin-panel team-panel"><div class="panel-heading"><div><p class="admin-eyebrow">Equipo</p><h2>Cuentas del panel</h2></div><span><?=count($users)?> usuarios</span></div><?php foreach($users as $item):?><a class="user-access-row" href="/admin/users.php?id=<?=$item['id']?>"><span class="user-avatar"><?=e(mb_strtoupper(mb_substr($item['name'],0,1)))?></span><span><strong><?=e($item['name'])?></strong><small><?=e($item['email'])?></small></span><span class="user-role"><?=e($item['role']==='admin'?'Administrador':'Personalizado')?></span><i class="status-dot <?=$item['is_active']?'active':''?>"></i></a><?php endforeach;?></section></div></main><script>const role=document.querySelector('#role'),matrix=document.querySelector('#permission-matrix');function syncRole(){matrix.hidden=role.value==='admin'}role.addEventListener('change',syncRole);syncRole();</script></body></html>