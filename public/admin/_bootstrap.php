<?php
declare(strict_types=1);

session_name('ROYALBEANS_ADMIN');
$adminHost=strtolower((string)($_SERVER['HTTP_HOST']??''));
$adminSecure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||preg_match('/^(?:www\.)?royalbeansperu\.com(?::\d+)?$/',$adminHost)===1;
session_set_cookie_params(['httponly' => true, 'secure' => $adminSecure, 'samesite' => 'Lax', 'path' => '/admin']);
session_cache_limiter('');
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
$isDocumentGet = $requestMethod === 'GET' && str_contains($acceptHeader, 'text/html');
header($isDocumentGet
    ? 'Cache-Control: private, max-age=45, must-revalidate'
    : 'Cache-Control: no-store, max-age=0');
header('Vary: Cookie, Accept');
if (PHP_SAPI !== 'cli') {
    ob_start(static function (string $output): string {
        $output = str_replace('</head>', '<link rel="stylesheet" href="/admin/admin-modern.css?v=20260924-orientation"><script src="/admin/admin-performance.js?v=20260921-2" defer></script></head>', $output);
        $user = admin_user();
        if (!$user) header('Cache-Control: no-store, max-age=0');
        if (!$user || !str_contains($output, '</body>')) return $output;
        $access = [
            'dashboard' => can('dashboard', 'view', $user),
            'products' => can('products_conventional', 'view', $user) || can('products_retail', 'view', $user),
            'editor' => can('editor', 'view', $user),
            'announcements' => can('announcements', 'view', $user),
            'presentation' => can('presentation', 'view', $user),
            'impact' => can('impact', 'view', $user),
            'contacts' => can('contacts', 'view', $user),
            'standards' => can('editor', 'view', $user),
            'media' => can('media', 'view', $user),
            'inquiries' => can('inquiries', 'view', $user),
            'settings' => can('settings', 'view', $user),
            'users' => can('users', 'view', $user),
        ];
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/', PHP_URL_PATH) ?: '/admin/';
        parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY), $query);
        $view = (string) ($query['view'] ?? 'dashboard');
        $active = match (true) {
            str_ends_with($path, '/editor.php') => 'editor',
            str_ends_with($path, '/announcements.php') => 'announcements',
            str_ends_with($path, '/sections.php') => (string) ($query['module'] ?? 'presentation'),
            str_ends_with($path, '/configuration.php') => 'web-configuration',
            str_ends_with($path, '/users.php') || $view === 'users' => 'users',
            str_ends_with($path, '/product-import.php'), str_ends_with($path, '/product-editor.php'), str_ends_with($path, '/product-details.php'), $view === 'product-edit' => 'products',
            default => $view,
        };
        $menu = [
            ['dashboard', 'dashboard', '/admin/', 'Resumen', 'General'],
            ['products', 'products', '/admin/?view=products', 'Productos', 'Catálogo'],
            ['media', 'media', '/admin/?view=media', 'Imágenes', 'Catálogo'],
            ['editor', 'editor', '/admin/editor.php', 'Edición Visual', 'Contenido'],
            ['announcements', 'announcements', '/admin/announcements.php', 'Anuncios de inicio', 'Contenido'],
            ['presentation', 'presentation', '/admin/sections.php?module=presentation', 'Participación', 'Contenido'],
            ['impact', 'impact', '/admin/sections.php?module=impact', 'Impacto', 'Contenido'],
            ['standards', 'standards', '/admin/sections.php?module=standards', 'Certificados', 'Contenido'],
            ['contacts', 'contacts', '/admin/sections.php?module=contacts', 'Contactos', 'Comunicación'],
            ['inquiries', 'inquiries', '/admin/?view=inquiries', 'Consultas', 'Comunicación'],
            ['web-configuration', 'settings', '/admin/configuration.php', 'Configuración Web', 'Sistema'],
            ['users', 'users', '/admin/users.php', 'Usuarios y permisos', 'Sistema'],
        ];
        $navigation = '';
        $currentGroup = '';
        foreach ($menu as [$key, $permission, $href, $label, $group]) {
            if (!$access[$permission]) continue;
            if ($group !== $currentGroup) { $navigation .= '<span class="admin-nav-group">' . $group . '</span>'; $currentGroup = $group; }
            $navigation .= '<a' . ($active === $key ? ' class="active"' : '') . ' href="' . $href . '">' . $label . '</a>';
        }
        $output = preg_replace('#(<aside class="admin-sidebar">.*?<nav>).*?(</nav>)#s', '$1' . $navigation . '$2', $output, 1) ?? $output;
        $output = preg_replace('#<a class="admin-return"[^>]*>.*?</a>#s', '', $output, 1) ?? $output;
        $output = str_replace('/admin/?view=product-edit"', '/admin/product-editor.php?line=conventional"', $output);
        $output = str_replace('/admin/?view=product-edit&amp;line=', '/admin/product-editor.php?line=', $output);
        $output = str_replace('/admin/?view=product-edit&line=', '/admin/product-editor.php?line=', $output);
        $output = str_replace('/admin/?view=product-edit&amp;id=', '/admin/product-editor.php?id=', $output);
        $output = str_replace('/admin/?view=product-edit&id=', '/admin/product-editor.php?id=', $output);
        $output = str_replace('/admin/product-details.php?id=', '/admin/product-editor.php?id=', $output);
        $output = preg_replace('#<a href="/admin/product-editor\.php\?id=\d+">Ficha</a>#', '', $output) ?? $output;
        $output = preg_replace('#<a class="[^"]*" href="/admin/\?view=products">Todos</a>#', '', $output) ?? $output;
        $script = '';
        if (str_ends_with($path, '/sections.php')) {
            $editorOpen = isset($query['create']) || isset($query['edit']) || str_contains($output, '<div class="alert error">');
            $output = str_replace('<body class="admin-body">', '<body class="admin-body section-manager-page' . ($editorOpen ? ' editor-modal-open' : '') . '">', $output);
            $output = str_replace('class="admin-panel admin-form sticky-editor"', 'class="admin-panel admin-form sticky-editor admin-modal-editor" role="dialog" aria-modal="true"', $output);
            $output = str_replace('class="admin-panel admin-form sticky-editor" id="content-editor"', 'class="admin-panel admin-form sticky-editor admin-modal-editor" id="content-editor" role="dialog" aria-modal="true"', $output);
            $module = rawurlencode((string) ($query['module'] ?? 'presentation'));
            $output = str_replace('?module=' . $module . '#content-editor', '?module=' . $module . '&create=1', $output);
            $output = preg_replace('~(&edit=\d+)#content-editor~', '$1', $output) ?? $output;
            $output = str_replace('>Limpiar</a>', '>Cancelar</a>', $output);
            $output = str_replace('>Nuevo</a><button class="primary-button">', '>Cancelar</a><button class="primary-button">', $output);
            if (($query['module'] ?? '') === 'contacts') {
                $output = str_replace('section-manager-page', 'section-manager-page contacts-manager-page', $output);
                $output = str_replace('Correos, teléfonos y WhatsApp', 'Contacto, WhatsApp y redes sociales', $output);
                $output = preg_replace('#(</header>)#', '$1<a class="primary-button section-create-button" href="/admin/sections.php?module=contacts&amp;create=1">Añadir canal o red social</a>', $output, 1) ?? $output;
            }
            if (($query['module'] ?? '') === 'standards') {
                $output = str_replace('section-manager-page', 'section-manager-page standards-manager-page', $output);
                $output = str_replace('Contenido guardado y actualizado en la web.', 'Certificado guardado y actualizado en la web.', $output);
                $output = preg_replace('#(</header>)#', '$1<a class="primary-button section-create-button" href="/admin/sections.php?module=standards&amp;create=1">Añadir certificado</a>', $output, 1) ?? $output;
            }
            $closeUrl = '/admin/sections.php?module=' . $module;
            $currentContactType = '';
            if (($query['module'] ?? '') === 'contacts' && isset($query['edit'])) {
                $contactStatement = admin_db()->prepare('SELECT channel_type FROM contact_channels WHERE id=?');
                $contactStatement->execute([(int) $query['edit']]);
                $currentContactType = (string) ($contactStatement->fetchColumn() ?: '');
            }
            $script = '<script>const iconSvg={whatsapp:`<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 0 0-8.46 14.85L2 22l5.28-1.53A9.92 9.92 0 1 0 12.04 2Zm0 17.93a8.06 8.06 0 0 1-4.1-1.12l-.29-.17-3.13.91.92-3.05-.19-.31a8.09 8.09 0 1 1 6.79 3.74Zm4.44-6.06c-.24-.12-1.44-.71-1.66-.79-.22-.08-.39-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-2.09-1.04-3.46-3.71-3.56-3.89-.14-.24-.01-.37.11-.49.28-.27.52-.63.68-.95.08-.16.04-.3-.02-.42-.06-.12-.55-1.33-.75-1.82-.2-.48-.4-.41-.55-.42-.63-.02-1.12.08-1.42.4-.22.24-.85.83-.85 2.02s.87 2.34.99 2.5c.12.16 1.71 2.61 4.14 3.66 2.02.87 2.43.7 2.87.66.47-.07 1.44-.59 1.64-1.16.2-.57.2-1.07.14-1.17-.06-.1-.22-.16-.47-.28Z"/></svg>`,email:`<svg viewBox="0 0 24 24"><path d="M3 5h18v14H3zM3 6l9 7 9-7"/></svg>`,phone:`<svg viewBox="0 0 24 24"><path d="M7 3l3 4-2 2c1 3 4 6 7 7l2-2 4 3-2 4C10 21 3 14 3 5z"/></svg>`,address:`<svg viewBox="0 0 24 24"><path d="M12 22s7-6 7-13A7 7 0 1 0 5 9c0 7 7 13 7 13zm0-10a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>`};document.querySelectorAll("[data-contact-icon]").forEach(item=>item.innerHTML=iconSvg[item.dataset.contactIcon]||"•");const contactType=document.querySelector("select[name=channel_type]");if(contactType){const types={facebook:"Facebook",instagram:"Instagram",youtube:"YouTube",linkedin:"LinkedIn",tiktok:"TikTok"};Object.entries(types).forEach(([value,label])=>{if(!contactType.querySelector(`option[value=${value}]`))contactType.add(new Option(label,value))});contactType.querySelector("option[value=address]")?.replaceChildren("Lugar / dirección");if(' . json_encode($currentContactType) . ')contactType.value=' . json_encode($currentContactType) . '}if(document.body.classList.contains("standards-manager-page")){document.querySelectorAll("select[name=entry_type],input[name=event_date],input[name=location_es],input[name=location_en],input[name=cover_path],input[name=cover],input[name^=gallery]").forEach(input=>input.closest("label")?.remove());document.querySelectorAll(".album-editor").forEach(item=>item.remove());document.querySelectorAll(".content-summary article:last-child").forEach(item=>item.remove());document.querySelectorAll(".content-card-copy .entry-type").forEach(item=>item.textContent="Certificado");document.querySelectorAll(".content-card-copy small").forEach(item=>item.textContent=item.textContent.replace(/^.*?· /,""));document.querySelectorAll("label").forEach(label=>{if(label.firstChild?.textContent?.trim()==="Logo actual")label.firstChild.textContent="Logo del certificado"});}const modal=document.querySelector(".admin-modal-editor");if(modal&&document.body.classList.contains("editor-modal-open")){const close=()=>location.href=' . json_encode($closeUrl) . ';document.addEventListener("keydown",event=>{if(event.key==="Escape")close()});document.body.addEventListener("click",event=>{if(event.target===document.body)close()});modal.querySelector("input:not([type=hidden]),select,textarea,button")?.focus();}</script>';
        }
        if (str_ends_with($path, '/sections.php') && ($query['module'] ?? '') === 'contacts') {
            $script .= '<script>const inferContactType=text=>/whatsapp/i.test(text)?"whatsapp":/correo|email/i.test(text)?"email":/tel[eé]fono|phone/i.test(text)?"phone":/direcci[oó]n|lugar|address/i.test(text)?"address":"";document.querySelectorAll(".contacts-manager-page .content-card-list article").forEach(card=>{const type=inferContactType(card.querySelector("h3")?.textContent||"");if(!type)return;const icon=document.createElement("span");icon.className="contact-type-icon";icon.innerHTML=iconSvg[type];card.prepend(icon)});if(contactType){const preview=document.createElement("span");preview.className="contact-type-icon contact-type-preview";const render=()=>preview.innerHTML=iconSvg[contactType.value]||"•";contactType.closest("label")?.prepend(preview);contactType.addEventListener("change",render);render();}</script>';
        }
        if (str_ends_with($path, '/users.php')) {
            $editorOpen = isset($query['create']) || isset($query['id']) || str_contains($output, '<div class="alert error">');
            $output = str_replace('<body class="admin-body">', '<body class="admin-body user-manager-page' . ($editorOpen ? ' editor-modal-open' : '') . '">', $output);
            $output = str_replace('class="admin-panel admin-form permission-form"', 'class="admin-panel admin-form permission-form admin-modal-editor" role="dialog" aria-modal="true"', $output);
            $output = str_replace('href="/admin/users.php">Nuevo usuario</a>', 'href="/admin/users.php?create=1">Nuevo usuario</a>', $output);
            $script .= '<script>const userModal=document.querySelector(".user-manager-page .admin-modal-editor");if(userModal&&document.body.classList.contains("editor-modal-open")){const close=()=>location.href="/admin/users.php";document.addEventListener("keydown",event=>{if(event.key==="Escape")close()});document.body.addEventListener("click",event=>{if(event.target===document.body)close()});userModal.querySelector("input:not([type=hidden]),select,button")?.focus();}</script>';
        }
        if (str_ends_with($path, '/announcements.php')) {
            $output = str_replace('<body class="admin-body">', '<body class="admin-body announcement-manager-page">', $output);
        }
        if ($path === '/admin/' && $view === 'media') {
            $output = str_replace('<body class="admin-body">', '<body class="admin-body media-manager-page">', $output);
        }
        if (str_ends_with($path, '/product-editor.php')) {
            $output = str_replace('<body class="admin-body">', '<body class="admin-body product-editor-page editor-modal-open">', $output);
            $output = str_replace('class="admin-form product-studio-form"', 'class="admin-form product-studio-form admin-modal-editor" role="dialog" aria-modal="true"', $output);
            $script .= '<script>const productModal=document.querySelector(".product-editor-page .admin-modal-editor");if(productModal){const close=()=>location.href="/admin/?view=products";document.addEventListener("keydown",event=>{if(event.key==="Escape")close()});document.body.addEventListener("click",event=>{if(event.target===document.body)close()});productModal.querySelector("input:not([type=hidden]),select,button")?.focus();}</script>';
        }
        if ($view === 'inquiries' && str_contains($output, '<section class="inquiry-grid"></section>')) {
            $output = str_replace('<section class="inquiry-grid"></section>', '<section class="admin-panel content-empty"><strong>Aún no hay consultas</strong><p>Los mensajes enviados desde el formulario Contáctanos aparecerán aquí automáticamente para su seguimiento.</p></section>', $output);
        }
        $packageScript = '';
        if (str_contains($output, 'product-studio-form')) {
            $packageScript = <<<'HTML'
<script>
const packageEditor=document.querySelector('#package-editor');
const positionLabel=position=>position+'.º — '+(position===1?'Primero':position===2?'Segundo':position===3?'Tercero':position===4?'Cuarto':position===5?'Quinto':'Posición '+position);
const reindexPackageCards=()=>{
    const cards=[...document.querySelectorAll('.package-editor-card')],count=cards.length;
    cards.forEach((card,index)=>{
        card.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/package(?:_image)?\[\d+\]/,match=>match.startsWith('package_image')?'package_image['+index+']':'package['+index+']'));
        const order=card.querySelector('select[name$="[sort_order]"]');
        if(order){
            order.replaceChildren(...Array.from({length:count},(_,position)=>new Option(positionLabel(position+1),String(position+1))));
            order.value=String(index+1);
        }
    });
};
const movePackageCard=(card,position)=>{
    const cards=[...document.querySelectorAll('.package-editor-card')].filter(item=>item!==card);
    const target=Math.min(Math.max(position-1,0),cards.length);
    if(target>=cards.length)packageEditor.append(card);else packageEditor.insertBefore(card,cards[target]);
    reindexPackageCards();
};
const enhancePackageCards=()=>{
    const line=document.querySelector('input[name="line_slug"]')?.value||'conventional';
    const cards=[...document.querySelectorAll('.package-editor-card')];
    const hasConfiguredPackages=cards.some(card=>card.querySelector('input[name$="[weight_primary]"]')?.value.trim());
    cards.forEach((card,index)=>{
        const availableInput=card.querySelector('input[name$="[is_available]"]');
        if(availableInput){
            const label=availableInput.closest('label'),select=document.createElement('select');
            select.name=availableInput.name;
            select.innerHTML='<option value="1">Sí, mostrar en la web</option><option value="0">No, ocultar en la web</option>';
            select.value=availableInput.checked?'1':'0';
            label.classList.remove('check','availability');
            label.replaceChildren(document.createTextNode('Disponible'),select);
        }
        const availability=card.querySelector('select[name$="[is_available]"]');
        if(availability&&!card.querySelector('select[name$="[sort_order]"]')){
            const orderLabel=document.createElement('label'),order=document.createElement('select');
            const packageName=availability.name.replace('[is_available]','[sort_order]');
            order.name=packageName;
            order.value=String(index+1);
            order.addEventListener('change',()=>movePackageCard(card,Number(order.value)));
            orderLabel.append('Posición',order);
            availability.closest('label').before(orderLabel);
        }
        if(!card.querySelector('.remove-package')){
            const remove=document.createElement('button');
            remove.type='button';remove.className='remove-package';remove.textContent='Eliminar empaque';
            remove.addEventListener('click',()=>{
                const name=card.querySelector('input[name$="[weight_primary]"]')?.value.trim()||'este empaque';
                if(!confirm('¿Eliminar '+name+'? El cambio será definitivo al guardar el producto.'))return;
                card.remove();reindexPackageCards();
            });
            card.append(remove);
        }
        const weight=card.querySelector('input[name$="[weight_primary]"]'),secondary=card.querySelector('input[name$="[weight_secondary]"]'),typeSelect=card.querySelector('select[name$="[package_type]"]');
        if(!hasConfiguredPackages&&weight){
            const defaults=line==='retail'?[['500 g','','bag'],['1 kg','','bag'],['Bolsa institucional','','other']]:[['25 kg','50 lb','sack'],['50 kg','100 lb','sack'],['Big Bag','','big-bag']];
            [weight.value,secondary.value,typeSelect.value]=defaults[index]||['','','sack'];
        }
        const type=typeSelect?.value;
        const textEs=card.querySelector('textarea[name$="[material_es]"]'),textEn=card.querySelector('textarea[name$="[material_en]"]');
        if(textEs&&!textEs.value.trim())textEs.value=type==='big-bag'?'Solución ideal para grandes volúmenes.':(line==='retail'?'Ideal para el consumo familiar.':'Saco de polipropileno para exportación.');
        if(textEn&&!textEn.value.trim())textEn.value=type==='big-bag'?'Ideal solution for large volumes.':(line==='retail'?'Ideal for family use.':'Polypropylene sack for export.');
    });
    reindexPackageCards();
};
enhancePackageCards();
document.querySelector('#add-package')?.addEventListener('click',()=>setTimeout(enhancePackageCards));
packageEditor?.closest('form')?.addEventListener('submit',reindexPackageCards);
</script>
HTML;
        }
        return str_replace('</body>', $script . $packageScript . '</body>', $output);
    });
}

function mark_public_content_changed(string $scope = 'all'): void
{
    $directory = dirname(__DIR__) . '/cms-config';
    if (!is_dir($directory)) @mkdir($directory, 0775, true);
    $payload = json_encode([
        'revision' => sprintf('%.6f', microtime(true)) . '-' . bin2hex(random_bytes(4)),
        'scope' => $scope,
        'updated_at' => gmdate('c'),
    ], JSON_UNESCAPED_SLASHES);
    if ($payload === false) return;
    $temporary = $directory . '/content-version.tmp';
    if (@file_put_contents($temporary, $payload, LOCK_EX) !== false) {
        $destination = $directory . '/content-version.json';
        if (!@rename($temporary, $destination)) {
            @file_put_contents($destination, $payload, LOCK_EX);
            @unlink($temporary);
        }
    }
}

function admin_configured(): bool {
    $config = require dirname(__DIR__) . '/cms-config/database.php';
    return is_array($config) && !empty($config['database']) && !empty($config['username']);
}
function admin_install_enabled(): bool { return getenv('RB_ALLOW_INSTALL') === '1'; }
function admin_db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $config = require dirname(__DIR__) . '/cms-config/database.php';
    if (empty($config['database']) || empty($config['username'])) throw new RuntimeException('CMS no instalado');
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    return $pdo;
}
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(24)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) throw new RuntimeException('La sesión del formulario expiró. Recarga la página.'); }
function admin_login_rate_limit(string $email,bool $failed=false,bool $succeeded=false): void {
    $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
    $key=hash('sha256',$ip.'|'.strtolower(trim($email)));
    $file=sys_get_temp_dir().DIRECTORY_SEPARATOR.'rb-login-'.$key.'.json';
    $handle=@fopen($file,'c+');if(!$handle)throw new RuntimeException('Acceso temporalmente no disponible.');
    try{
        if(!flock($handle,LOCK_EX))throw new RuntimeException('Acceso temporalmente no disponible.');
        $state=json_decode((string)stream_get_contents($handle),true);if(!is_array($state))$state=[];
        $now=time();$attempts=array_values(array_filter((array)($state['attempts']??[]),static fn($when):bool=>is_int($when)&&$when>$now-900));
        if($succeeded)$attempts=[];
        elseif($failed)$attempts[]=$now;
        ftruncate($handle,0);rewind($handle);fwrite($handle,json_encode(['attempts'=>$attempts]));fflush($handle);
        if(count($attempts)>=6)throw new RuntimeException('Demasiados intentos. Inténtalo de nuevo en 15 minutos.');
    }finally{flock($handle,LOCK_UN);fclose($handle);}
}
function admin_user(): ?array { return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null; }
function require_admin(): array {
    $user = admin_user();
    if (!$user) { header('Location: /admin/?view=login'); exit; }
    if((int)($_SESSION['admin_last_activity']??0)<time()-1800){$_SESSION=[];session_destroy();header('Location: /admin/?view=login');exit;}
    $_SESSION['admin_last_activity']=time();
    $statement = admin_db()->prepare('SELECT id,name,email,role,permissions_json,is_active FROM admin_users WHERE id=? LIMIT 1');
    $statement->execute([(int) $user['id']]);
    $fresh = $statement->fetch();
    if (!$fresh || !$fresh['is_active']) { session_destroy(); header('Location: /admin/?view=login'); exit; }
    unset($fresh['is_active']);
    $_SESSION['admin_checked_at'] = time();
    return $_SESSION['admin'] = $fresh;
}
function permission_catalog(): array {
    return [
        'dashboard' => ['label' => 'Resumen', 'actions' => ['view' => 'Ver']],
        'products_conventional' => ['label' => 'Línea a Granel', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar']],
        'products_retail' => ['label' => 'Línea Retail', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar']],
        'editor' => ['label' => 'Editor visual', 'actions' => ['view' => 'Ver', 'edit' => 'Editar']],
        'announcements' => ['label' => 'Anuncios de inicio', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar']],
        'presentation' => ['label' => 'Participación', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar']],
        'impact' => ['label' => 'Impacto', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar']],
        'contacts' => ['label' => 'Contactos y redes sociales', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar']],
        'media' => ['label' => 'Biblioteca de imágenes', 'actions' => ['view' => 'Ver', 'upload' => 'Subir', 'delete' => 'Eliminar', 'sync' => 'Sincronizar R2']],
        'inquiries' => ['label' => 'Consultas', 'actions' => ['view' => 'Ver', 'edit' => 'Cambiar estado']],
        'settings' => ['label' => 'Configuración y diseño', 'actions' => ['view' => 'Ver', 'edit' => 'Editar']],
        'users' => ['label' => 'Usuarios y permisos', 'actions' => ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar']],
        'audit' => ['label' => 'Registro de actividad', 'actions' => ['view' => 'Ver']],
    ];
}
function user_permissions(?array $user = null): array {
    $user ??= admin_user();
    if (!$user) return [];
    if (($user['role'] ?? '') === 'admin') return ['*' => ['*']];
    $value = $user['permissions_json'] ?? null;
    if (is_array($value)) return $value;
    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : [];
}
function can(string $section, string $action = 'view', ?array $user = null): bool {
    $permissions = user_permissions($user);
    $legacySection = ['presentation'=>'editor','impact'=>'editor','contacts'=>'settings'][$section] ?? null;
    return in_array('*', $permissions['*'] ?? [], true) || in_array('*', $permissions[$section] ?? [], true) || in_array($action, $permissions[$section] ?? [], true) || ($legacySection !== null && in_array($action === 'create' || $action === 'delete' ? 'edit' : $action, $permissions[$legacySection] ?? [], true));
}
function require_permission(string $section, string $action = 'view'): array {
    $user = require_admin();
    if (!can($section, $action, $user)) {
        http_response_code(403);
        exit('No tienes permiso para realizar esta acción.');
    }
    return $user;
}
function product_permission_section(PDO $db, int $productId): string {
    $statement = $db->prepare('SELECT l.slug FROM products p JOIN product_lines l ON l.id=p.line_id WHERE p.id=? LIMIT 1');
    $statement->execute([$productId]);
    return $statement->fetchColumn() === 'retail' ? 'products_retail' : 'products_conventional';
}
function line_permission_section(PDO $db, int $lineId): string {
    $statement = $db->prepare('SELECT slug FROM product_lines WHERE id=? LIMIT 1');
    $statement->execute([$lineId]);
    return $statement->fetchColumn() === 'retail' ? 'products_retail' : 'products_conventional';
}
function admin_slug(string $value): string { $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value)) ?: $value; return trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii)), '-') ?: 'producto'; }
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function pull_flash(): ?array { $value = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $value; }
function redirect_admin(string $view): never { header('Location: /admin/?view=' . rawurlencode($view)); exit; }
function audit(PDO $db, int $userId, string $action, string $entity, string|int $entityId = '', array $details = []): void {
    $s = $db->prepare('INSERT INTO audit_log (user_id,action,entity_type,entity_id,details_json) VALUES (?,?,?,?,?)');
    $s->execute([$userId,$action,$entity,(string) $entityId,$details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null]);
}
function admin_secret_key(): string {
    $config = require dirname(__DIR__) . '/cms-config/database.php';
    return hash('sha256', implode('|', [$config['host'] ?? '', $config['database'] ?? '', $config['username'] ?? '', $config['password'] ?? '']), true);
}
function encrypt_setting(string $value): string {
    if ($value === '') return '';
    $iv = random_bytes(12); $tag = '';
    $encrypted = openssl_encrypt($value, 'aes-256-gcm', admin_secret_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($encrypted === false) throw new RuntimeException('No se pudo proteger la credencial.');
    return 'enc:' . base64_encode($iv . $tag . $encrypted);
}
function decrypt_setting(string $value): string {
    if (!str_starts_with($value, 'enc:')) return $value;
    $payload = base64_decode(substr($value, 4), true);
    if ($payload === false || strlen($payload) < 29) return '';
    $decrypted = openssl_decrypt(substr($payload, 28), 'aes-256-gcm', admin_secret_key(), OPENSSL_RAW_DATA, substr($payload, 0, 12), substr($payload, 12, 16));
    return $decrypted === false ? '' : $decrypted;
}
function r2_settings(PDO $db): array {
    static $cached = null;
    if (is_array($cached)) return $cached;
    $settings=[];foreach($db->query("SELECT setting_key,value_text FROM settings WHERE setting_key IN ('r2_account_id','r2_access_key_id','r2_secret_access_key','r2_bucket','r2_public_url')") as $item)$settings[$item['setting_key']]=$item['value_text'];
    $settings['r2_access_key_id']=decrypt_setting((string)($settings['r2_access_key_id']??''));$settings['r2_secret_access_key']=decrypt_setting((string)($settings['r2_secret_access_key']??''));
    foreach(['r2_account_id','r2_access_key_id','r2_secret_access_key','r2_bucket','r2_public_url'] as $key)if(trim((string)($settings[$key]??''))==='')throw new RuntimeException('Completa la configuración de Cloudflare R2 antes de continuar.');
    $settings['r2_public_url']=rtrim(trim((string)$settings['r2_public_url']),'/');return $cached=$settings;
}
function r2_request(array $settings,string $method,string $key,string $payload='',string $mime=''): int {
    $account=trim((string)$settings['r2_account_id']);$access=(string)$settings['r2_access_key_id'];$secret=(string)$settings['r2_secret_access_key'];$bucket=trim((string)$settings['r2_bucket']);
    $hash=hash('sha256',$payload);$date=gmdate('Ymd');$amzDate=gmdate('Ymd\THis\Z');$host=$account.'.r2.cloudflarestorage.com';$uri='/'.implode('/',array_map('rawurlencode',explode('/',$bucket.'/'.ltrim($key,'/'))));
    $canonical=['host'=>$host,'x-amz-content-sha256'=>$hash,'x-amz-date'=>$amzDate];if($mime!=='')$canonical['content-type']=$mime;
    $immutable=$method==='PUT'&&(str_starts_with($key,'royalbeans/by-hash/')||str_starts_with($key,'royalbeans/products/by-hash/'));
    if($immutable)$canonical['cache-control']='public, max-age=31536000, immutable';
    ksort($canonical);$headers='';foreach($canonical as $name=>$value)$headers.=$name.':'.$value."\n";$signed=implode(';',array_keys($canonical));
    $request=$method."\n$uri\n\n$headers\n$signed\n$hash";$scope="$date/auto/s3/aws4_request";$string="AWS4-HMAC-SHA256\n$amzDate\n$scope\n".hash('sha256',$request);$h=static fn($secretKey,$data)=>hash_hmac('sha256',$data,$secretKey,true);$signing=$h($h($h($h('AWS4'.$secret,$date),'auto'),'s3'),'aws4_request');$signature=hash_hmac('sha256',$string,$signing);$authorization="AWS4-HMAC-SHA256 Credential=$access/$scope, SignedHeaders=$signed, Signature=$signature";
    $httpHeaders=['Authorization: '.$authorization,'Host: '.$host,'X-Amz-Content-Sha256: '.$hash,'X-Amz-Date: '.$amzDate];if($mime!=='')$httpHeaders[]='Content-Type: '.$mime;if($immutable)$httpHeaders[]='Cache-Control: public, max-age=31536000, immutable';
    $curl=curl_init('https://'.$host.$uri);$options=[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$httpHeaders,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>60,CURLOPT_NOSIGNAL=>true];if(defined('CURL_HTTP_VERSION_2TLS'))$options[CURLOPT_HTTP_VERSION]=CURL_HTTP_VERSION_2TLS;if($method==='PUT')$options[CURLOPT_POSTFIELDS]=$payload;if($method==='HEAD')$options[CURLOPT_NOBODY]=true;curl_setopt_array($curl,$options);curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);if($status<200||$status>=300)throw new RuntimeException('Cloudflare R2 rechazó la operación (HTTP '.$status.')'.($error?' '.$error:''));return $status;
}
function r2_store_payload(PDO $db,string $payload,string $mime,string $extension): string {
    $settings=r2_settings($db);$extension=preg_replace('/[^a-z0-9]/','',strtolower($extension))?:'bin';$key='royalbeans/by-hash/'.hash('sha256',$payload).'.'.$extension;r2_request($settings,'PUT',$key,$payload,$mime);return $settings['r2_public_url'].'/'.implode('/',array_map('rawurlencode',explode('/',$key)));
}
function r2_store_video_file(PDO $db,string $filePath,string $mime,string $extension): string {
    $settings=r2_settings($db);$hash=hash_file('sha256',$filePath);$size=filesize($filePath);
    if($hash===false||$size===false)throw new RuntimeException('No se pudo leer el video temporal.');
    $key='royalbeans/videos/by-hash/'.$hash.'.'.$extension;
    $date=gmdate('Ymd');$amzDate=gmdate('Ymd\THis\Z');$host=$settings['r2_account_id'].'.r2.cloudflarestorage.com';
    $uri='/'.implode('/',array_map('rawurlencode',explode('/',$settings['r2_bucket'].'/'.$key)));
    $cache='public, max-age=31536000, immutable';
    $canonical=['cache-control'=>$cache,'content-type'=>$mime,'host'=>$host,'x-amz-content-sha256'=>$hash,'x-amz-date'=>$amzDate];ksort($canonical);
    $headers='';foreach($canonical as $name=>$value)$headers.=$name.':'.$value."\n";
    $signed=implode(';',array_keys($canonical));$request="PUT\n$uri\n\n$headers\n$signed\n$hash";
    $scope="$date/auto/s3/aws4_request";$signatureSource="AWS4-HMAC-SHA256\n$amzDate\n$scope\n".hash('sha256',$request);
    $h=static fn($secret,$data)=>hash_hmac('sha256',$data,$secret,true);
    $signing=$h($h($h($h('AWS4'.$settings['r2_secret_access_key'],$date),'auto'),'s3'),'aws4_request');
    $signature=hash_hmac('sha256',$signatureSource,$signing);
    $auth="AWS4-HMAC-SHA256 Credential={$settings['r2_access_key_id']}/$scope, SignedHeaders=$signed, Signature=$signature";
    $stream=fopen($filePath,'rb');if($stream===false)throw new RuntimeException('No se pudo abrir el video temporal.');
    try{
        $curl=curl_init('https://'.$host.$uri);
        curl_setopt_array($curl,[CURLOPT_CUSTOMREQUEST=>'PUT',CURLOPT_UPLOAD=>true,CURLOPT_INFILE=>$stream,CURLOPT_INFILESIZE=>$size,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: '.$auth,'Host: '.$host,'X-Amz-Content-Sha256: '.$hash,'X-Amz-Date: '.$amzDate,'Content-Type: '.$mime,'Cache-Control: '.$cache],CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>300,CURLOPT_NOSIGNAL=>true]);
        $result=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);
        if($result===false||$status<200||$status>=300)throw new RuntimeException('Cloudflare R2 rechazó el video (HTTP '.$status.').'.($error?' '.$error:''));
    }finally{fclose($stream);}
    return $settings['r2_public_url'].'/'.implode('/',array_map('rawurlencode',explode('/',$key)));
}
function save_video_upload(PDO $db,array $file,int $userId,?string &$creation=null): string {
    $creation='';$error=$file['error']??UPLOAD_ERR_NO_FILE;
    if($error===UPLOAD_ERR_NO_FILE)return '';
    if($error!==UPLOAD_ERR_OK)throw new RuntimeException('No se pudo recibir el video. Comprueba el límite de carga del servidor.');
    $size=(int)($file['size']??0);$path=(string)($file['tmp_name']??'');
    if($size<1||$size>120*1024*1024||!is_uploaded_file($path))throw new RuntimeException('El video debe pesar como máximo 120 MB.');
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($path);$extension=['video/mp4'=>'mp4','video/webm'=>'webm'][$mime]??'';
    if($extension==='')throw new RuntimeException('Sube un video MP4 o WebM válido.');
    $hash=hash_file('sha256',$path);if($hash===false)throw new RuntimeException('No se pudo comprobar el video.');
    $settings=r2_settings($db);$existing=$db->prepare('SELECT path FROM media WHERE content_hash=? AND mime_type=? AND path LIKE ? ORDER BY id LIMIT 1');
    $existing->execute([$hash,$mime,$settings['r2_public_url'].'/%']);$existingPath=(string)($existing->fetchColumn()?:'');
    if($existingPath!==''){try{r2_request($settings,'HEAD',r2_key_from_url($db,$existingPath));return $existingPath;}catch(Throwable){}}
    $url=r2_store_video_file($db,$path,$mime,$extension);register_media($db,$url,(string)($file['name']??'video.'.$extension),$mime,$size,$userId,$hash);$creation='catalog';return $url;
}
function r2_key_from_url(PDO $db,string $url): string {
    $public=r2_settings($db)['r2_public_url'].'/';if(!str_starts_with($url,$public))throw new RuntimeException('La imagen remota no pertenece a la URL pública de R2 configurada.');return rawurldecode(substr($url,strlen($public)));
}
function r2_delete(PDO $db,string $url): void { $settings=r2_settings($db);r2_request($settings,'DELETE',r2_key_from_url($db,$url)); }
function r2_test_connection(PDO $db): void {
    $settings=r2_settings($db);$key='royalbeans/.health/'.bin2hex(random_bytes(10)).'.txt';$payload='royalbeans-r2-ok';
    r2_request($settings,'PUT',$key,$payload,'text/plain');
    try{r2_request($settings,'HEAD',$key);}finally{r2_request($settings,'DELETE',$key);}
}
function r2_upload(PDO $db, array $file, string $mime, string $extension): string {
    $payload=file_get_contents((string)$file['tmp_name']);if($payload===false)throw new RuntimeException('No se pudo leer la imagen temporal.');return r2_store_payload($db,$payload,$mime,$extension);
}
function r2_list_objects(PDO $db,string $prefix='royalbeans/'): array {
    $settings=r2_settings($db);$objects=[];$continuation='';
    do{$query=['list-type'=>'2','prefix'=>$prefix];if($continuation!=='')$query['continuation-token']=$continuation;ksort($query);$canonicalQuery=http_build_query($query,'','&',PHP_QUERY_RFC3986);$account=trim((string)$settings['r2_account_id']);$access=(string)$settings['r2_access_key_id'];$secret=(string)$settings['r2_secret_access_key'];$bucket=trim((string)$settings['r2_bucket']);$date=gmdate('Ymd');$amzDate=gmdate('Ymd\THis\Z');$host=$account.'.r2.cloudflarestorage.com';$uri='/'.rawurlencode($bucket);$hash=hash('sha256','');$headers="host:$host\nx-amz-content-sha256:$hash\nx-amz-date:$amzDate\n";$signed='host;x-amz-content-sha256;x-amz-date';$request="GET\n$uri\n$canonicalQuery\n$headers\n$signed\n$hash";$scope="$date/auto/s3/aws4_request";$string="AWS4-HMAC-SHA256\n$amzDate\n$scope\n".hash('sha256',$request);$h=static fn($key,$data)=>hash_hmac('sha256',$data,$key,true);$signing=$h($h($h($h('AWS4'.$secret,$date),'auto'),'s3'),'aws4_request');$signature=hash_hmac('sha256',$string,$signing);$authorization="AWS4-HMAC-SHA256 Credential=$access/$scope, SignedHeaders=$signed, Signature=$signature";$curl=curl_init('https://'.$host.$uri.'?'.$canonicalQuery);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: '.$authorization,'Host: '.$host,'X-Amz-Content-Sha256: '.$hash,'X-Amz-Date: '.$amzDate],CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>90]);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);if($body===false||$status<200||$status>=300)throw new RuntimeException('No se pudo inventariar Cloudflare R2 (HTTP '.$status.').'.($error?' '.$error:''));$xml=simplexml_load_string($body);if($xml===false)throw new RuntimeException('Cloudflare R2 devolvió un inventario no válido.');foreach($xml->Contents as $item)$objects[]=['key'=>(string)$item->Key,'etag'=>strtolower(trim((string)$item->ETag,'"')),'size'=>(int)$item->Size,'modified'=>(string)$item->LastModified];$continuation=(string)$xml->NextContinuationToken;}while((string)$xml->IsTruncated==='true'&&$continuation!=='');
    return $objects;
}
function reconcile_existing_r2(PDO $db,bool $apply=false): array {
    $settings=r2_settings($db);$objects=r2_list_objects($db);$byFingerprint=[];foreach($objects as $object)if(preg_match('/^[a-f0-9]{32}$/',$object['etag']))$byFingerprint[$object['size'].':'.$object['etag']][]=$object;$rows=$db->query("SELECT path,MAX(mime_type) mime_type FROM media WHERE path LIKE '/uploads/%' AND mime_type LIKE 'image/%' GROUP BY path ORDER BY MIN(id)")->fetchAll();$result=['local'=>count($rows),'objects'=>count($objects),'matched'=>0,'restored'=>0,'unmatched'=>0,'ambiguous'=>0,'errors'=>[]];
    foreach($rows as $row){$oldPath=(string)$row['path'];try{$absolute=local_upload_path($oldPath);$fingerprint=filesize($absolute).':'.strtolower(md5_file($absolute));$matches=$byFingerprint[$fingerprint]??[];if(!$matches){$result['unmatched']++;continue;}if(count($matches)>1)$result['ambiguous']++;usort($matches,static function(array $left,array $right):int{$leftCanonical=str_contains($left['key'],'/by-hash/');$rightCanonical=str_contains($right['key'],'/by-hash/');return $leftCanonical!==$rightCanonical?($leftCanonical?-1:1):strcmp($right['modified'],$left['modified']);});$newPath=$settings['r2_public_url'].'/'.implode('/',array_map('rawurlencode',explode('/',$matches[0]['key'])));$result['matched']++;if(!$apply)continue;$db->beginTransaction();try{link_remote_media_preserving_local($db,$oldPath,$newPath);$db->commit();$result['restored']++;}catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}}catch(Throwable $error){$result['errors'][]=basename($oldPath).': '.$error->getMessage();}}
    return $result;
}
function optimize_r2_duplicates(PDO $db): array {
    $settings=r2_settings($db);$objects=r2_list_objects($db);$groups=[];foreach($objects as $object)if(preg_match('/^[a-f0-9]{32}$/',$object['etag'])&&!str_contains($object['key'],'/.health/'))$groups[$object['size'].':'.$object['etag']][]=$object;$result=['groups'=>0,'removed'=>0,'references_updated'=>0,'skipped'=>0,'errors'=>[]];$public=$settings['r2_public_url'].'/';
    foreach($groups as $matches){if(count($matches)<2)continue;$result['groups']++;$url=static fn(array $object):string=>$public.implode('/',array_map('rawurlencode',explode('/',$object['key'])));usort($matches,static function(array $left,array $right)use($db,$url):int{$leftUsed=media_content_usage_count($db,$url($left))>0;$rightUsed=media_content_usage_count($db,$url($right))>0;if($leftUsed!==$rightUsed)return $leftUsed?-1:1;$leftCanonical=str_contains($left['key'],'/by-hash/');$rightCanonical=str_contains($right['key'],'/by-hash/');return $leftCanonical!==$rightCanonical?($leftCanonical?-1:1):strcmp($right['modified'],$left['modified']);});$canonicalUrl=$url($matches[0]);
        foreach(array_slice($matches,1) as $duplicate){$duplicateUrl=$url($duplicate);$updated=0;try{$db->beginTransaction();foreach(media_reference_columns() as [$table,$column]){$statement=$db->prepare("UPDATE $table SET $column=? WHERE $column=?");$statement->execute([$canonicalUrl,$duplicateUrl]);$updated+=$statement->rowCount();}$source=$db->prepare('SELECT original_name,mime_type,size_bytes,content_hash,alt_es,alt_en,uploaded_by FROM media WHERE path IN (?,?) ORDER BY path=? DESC,id LIMIT 1');$source->execute([$canonicalUrl,$duplicateUrl,$canonicalUrl]);$media=$source->fetch();if($media){$insert=$db->prepare('INSERT INTO media(path,original_name,mime_type,size_bytes,content_hash,alt_es,alt_en,uploaded_by) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE content_hash=COALESCE(content_hash,VALUES(content_hash))');$insert->execute([$canonicalUrl,$media['original_name'],$media['mime_type'],$media['size_bytes'],$media['content_hash'],$media['alt_es'],$media['alt_en'],$media['uploaded_by']]);}$db->prepare('DELETE FROM media WHERE path=?')->execute([$duplicateUrl]);$db->commit();$result['references_updated']+=$updated;try{r2_request($settings,'DELETE',(string)$duplicate['key']);$result['removed']++;}catch(Throwable $error){$result['skipped']++;$result['errors'][]=basename((string)$duplicate['key']).': referencias consolidadas; copia remota pendiente de eliminar ('.$error->getMessage().').';}}catch(Throwable $error){if($db->inTransaction())$db->rollBack();$result['errors'][]=basename((string)$duplicate['key']).': '.$error->getMessage();}}
    }return $result;
}
function optimize_local_duplicates(PDO $db): array {
    refresh_media_hashes($db);$rows=$db->query("SELECT id,path,content_hash FROM media WHERE path NOT REGEXP '^https?://' AND content_hash IS NOT NULL AND content_hash<>'' ORDER BY id")->fetchAll();$groups=[];foreach($rows as $row)$groups[$row['content_hash']][]=$row;$result=['groups'=>0,'removed'=>0,'references_updated'=>0,'skipped'=>0,'errors'=>[]];
    foreach($groups as $matches){if(count($matches)<2)continue;$result['groups']++;usort($matches,static function(array $left,array $right)use($db):int{$leftProtected=media_protection_reason((string)$left['path'])!=='';$rightProtected=media_protection_reason((string)$right['path'])!=='';if($leftProtected!==$rightProtected)return $leftProtected?-1:1;$leftUsed=media_content_usage_count($db,(string)$left['path'])>0;$rightUsed=media_content_usage_count($db,(string)$right['path'])>0;if($leftUsed!==$rightUsed)return $leftUsed?-1:1;$leftCanonical=str_starts_with((string)$left['path'],'/uploads/by-hash/');$rightCanonical=str_starts_with((string)$right['path'],'/uploads/by-hash/');return $leftCanonical!==$rightCanonical?($leftCanonical?-1:1):((int)$left['id']<=>(int)$right['id']);});$canonical=(string)$matches[0]['path'];
        foreach(array_slice($matches,1) as $duplicate){$path=(string)$duplicate['path'];if(media_protection_reason($path)!==''){$result['skipped']++;continue;}try{foreach(media_reference_columns() as [$table,$column]){$statement=$db->prepare("UPDATE $table SET $column=? WHERE $column=?");$statement->execute([$canonical,$path]);$result['references_updated']+=$statement->rowCount();}delete_media_asset($db,(int)$duplicate['id']);$result['removed']++;}catch(Throwable $error){$result['errors'][]=basename($path).': '.$error->getMessage();}}
    }return $result;
}
function media_reference_columns(): array {
    return [['products','image_path'],['product_gallery','media_path'],['product_packages','image_path'],['content_fields','value_es'],['content_fields','value_en'],['cms_collections','logo_path'],['cms_collections','cover_path'],['cms_collection_media','media_path'],['home_announcements','image_path'],['media_aliases','remote_path']];
}
function media_reference_filter(string $table): string {
    if($table==='products')return ' AND deleted_at IS NULL';if(in_array($table,['product_gallery','product_packages'],true))return ' AND product_id IN (SELECT id FROM products WHERE deleted_at IS NULL)';return '';
}
function media_mime_from_path(string $path): string {
    $extension=strtolower((string)pathinfo((string)(parse_url($path,PHP_URL_PATH)?:$path),PATHINFO_EXTENSION));
    return ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','avif'=>'image/avif','gif'=>'image/gif','svg'=>'image/svg+xml'][$extension]??'application/octet-stream';
}
function media_hash_from_path(string $path): ?string {
    if(str_starts_with($path,'/')){try{$absolute=local_media_path($path,false);return is_file($absolute)?hash_file('sha256',$absolute):null;}catch(Throwable){return null;}}if(preg_match('#/royalbeans/by-hash/([a-f0-9]{64})\.[a-z0-9]+(?:\?.*)?$#i',$path,$match))return strtolower($match[1]);return null;
}
function register_media(PDO $db,string $path,string $name,string $mime,int $size,int $userId,?string $hash=null): void {
    $statement=$db->prepare('INSERT INTO media(path,original_name,mime_type,size_bytes,content_hash,uploaded_by) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE original_name=IF(original_name="",VALUES(original_name),original_name),mime_type=VALUES(mime_type),size_bytes=GREATEST(size_bytes,VALUES(size_bytes)),content_hash=COALESCE(content_hash,VALUES(content_hash))');$statement->execute([$path,mb_substr($name,0,255),$mime,$size,$hash,$userId>0?$userId:null]);
}
function refresh_media_hashes(PDO $db): int {
    $rows=$db->query("SELECT id,path FROM media WHERE content_hash IS NULL OR content_hash='' ORDER BY id")->fetchAll();$update=$db->prepare('UPDATE media SET content_hash=? WHERE id=?');$updated=0;foreach($rows as $row){$hash=media_hash_from_path((string)$row['path']);if($hash===null)continue;$update->execute([$hash,(int)$row['id']]);$updated++;}return $updated;
}
function scan_public_media(PDO $db,int $userId): array {
    $aliasesRestored=restore_known_media_aliases($db);$referencesRepaired=repair_known_media_references($db);
    $found=[];$add=static function(string $path,string $name='')use(&$found):void{$path=trim($path);if($path===''||(!str_starts_with($path,'/')&&!preg_match('#^https?://#i',$path)))return;$mime=media_mime_from_path($path);if(!str_starts_with($mime,'image/'))return;$found[$path]=$name!==''?$name:basename((string)(parse_url($path,PHP_URL_PATH)?:$path));};
    foreach($db->query("SELECT value_es,value_en,label FROM content_fields WHERE field_type='image'") as $row){$add((string)$row['value_es'],(string)$row['label']);$add((string)$row['value_en'],(string)$row['label']);}
    foreach([['products','image_path'],['product_gallery','media_path'],['product_packages','image_path'],['cms_collections','logo_path'],['cms_collections','cover_path'],['cms_collection_media','media_path']] as [$table,$column])foreach($db->query("SELECT $column path FROM $table WHERE $column<>''".media_reference_filter($table)) as $row)$add((string)$row['path']);
    $publicRoot=dirname(__DIR__);foreach(['images','icons','uploads'] as $folder){$directory=$publicRoot.DIRECTORY_SEPARATOR.$folder;if(!is_dir($directory))continue;$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS));foreach($iterator as $file){if(!$file->isFile())continue;$relative='/'.str_replace(DIRECTORY_SEPARATOR,'/',$file->getPathname());$prefix='/'.str_replace(DIRECTORY_SEPARATOR,'/',$publicRoot);$add(substr($relative,strlen($prefix)));}}
    foreach(glob($publicRoot.'/*.{jpg,jpeg,png,webp,avif,gif,svg}',GLOB_BRACE)?:[] as $file)$add('/'.basename($file));
    $existing=array_fill_keys($db->query("SELECT path FROM media WHERE mime_type LIKE 'image/%'")->fetchAll(PDO::FETCH_COLUMN),true);$added=0;$missing=0;
    foreach($found as $path=>$name){if(isset($existing[$path]))continue;$size=0;$hash=null;if(str_starts_with($path,'/')){$absolute=$publicRoot.str_replace('/',DIRECTORY_SEPARATOR,$path);if(is_file($absolute)){$size=(int)filesize($absolute);$hash=hash_file('sha256',$absolute);}else$missing++;}else$hash=media_hash_from_path($path);register_media($db,$path,$name,media_mime_from_path($path),$size,$userId,$hash);$added++;}
    $hashed=refresh_media_hashes($db);return ['detected'=>count($found),'added'=>$added,'existing'=>count($found)-$added,'missing'=>$missing,'hashed'=>$hashed,'aliases_restored'=>$aliasesRestored,'references_repaired'=>$referencesRepaired];
}
function media_usage_contexts(PDO $db): array {
    $contexts=[];$add=static function(string $path,string $label)use(&$contexts):void{$path=trim($path);if($path!=='')$contexts[$path][$label]=true;};
    foreach($db->query("SELECT value_es,value_en,section_key FROM content_fields WHERE field_type='image'") as $row){$label=$row['section_key']==='hero'?'Hero':'Contenido';$add((string)$row['value_es'],$label);$add((string)$row['value_en'],$label);}
    foreach([['products','image_path','Producto'],['product_gallery','media_path','Galería de producto'],['product_packages','image_path','Empaque'],['cms_collections','logo_path','Logotipo'],['cms_collections','cover_path','Galería'],['cms_collection_media','media_path','Galería'],['home_announcements','image_path','Anuncio de inicio']] as [$table,$column,$label])foreach($db->query("SELECT $column path FROM $table WHERE $column<>''".media_reference_filter($table)) as $row)$add((string)$row['path'],$label);
    foreach($db->query("SELECT remote_path path FROM media_aliases WHERE remote_path<>''") as $row)$add((string)$row['path'],'Ruta migrada');
    return array_map(static fn(array $labels):array=>array_keys($labels),$contexts);
}
function media_content_usage_count(PDO $db,string $path): int {
    // Physical deletion must also respect drafts and soft-deleted products.
    $count=0;foreach(media_reference_columns() as [$table,$column]){$statement=$db->prepare("SELECT COUNT(*) FROM $table WHERE $column=?");$statement->execute([$path]);$count+=(int)$statement->fetchColumn();}return $count;
}
function media_content_usage_map(PDO $db,array $paths,bool $includeArchived=false): array {
    $paths=array_values(array_unique(array_filter(array_map(static fn($path):string=>trim((string)$path),$paths),static fn(string $path):bool=>$path!=='')));$counts=array_fill_keys($paths,0);if(!$paths)return $counts;$wanted=array_fill_keys($paths,true);
    foreach(media_reference_columns() as [$table,$column])foreach($db->query("SELECT $column path,COUNT(*) uses FROM $table WHERE $column<>''".($includeArchived?'':media_reference_filter($table))." GROUP BY $column") as $row){$path=(string)$row['path'];if(isset($wanted[$path]))$counts[$path]+=(int)$row['uses'];}return $counts;
}
function media_static_usage_map(array $paths,bool $allowCache=true): array {
    $paths=array_values(array_unique(array_filter(array_map(static fn($path):string=>trim((string)$path),$paths),static fn(string $path):bool=>$path!=='')));sort($paths);$counts=array_fill_keys($paths,0);if(!$paths)return $counts;$publicRoot=dirname(__DIR__);$projectRoot=dirname($publicRoot);$cacheFile=sys_get_temp_dir().DIRECTORY_SEPARATOR.'royalbeans-static-media-'.sha1($publicRoot."\n".implode("\n",$paths)).'.json';if($allowCache&&is_file($cacheFile)&&filemtime($cacheFile)!==false&&filemtime($cacheFile)>=time()-300){$cached=json_decode((string)@file_get_contents($cacheFile),true);if(is_array($cached))return array_replace($counts,$cached);}$roots=array_values(array_unique(array_filter([$publicRoot,is_dir($projectRoot.DIRECTORY_SEPARATOR.'out')?$projectRoot.DIRECTORY_SEPARATOR.'out':null])));$extensions=['html'=>true,'htm'=>true,'css'=>true,'js'=>true,'json'=>true,'xml'=>true,'txt'=>true];foreach($roots as $root){$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){if(!$file->isFile()||!isset($extensions[strtolower($file->getExtension())]))continue;$relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)));if(str_starts_with($relative,'/uploads/')||str_starts_with($relative,'/admin/')||str_starts_with($relative,'/cms-config/'))continue;$content=@file_get_contents($file->getPathname());if($content===false)continue;foreach($paths as $path)if(str_contains($content,$path))$counts[$path]++;}}
    if($allowCache)@file_put_contents($cacheFile,json_encode($counts),LOCK_EX);return $counts;
}
function media_static_usage_count(string $path): int {return media_static_usage_map([$path],false)[$path]??0;}
function media_usage_count(PDO $db,string $path,int $mediaId=0,?array $staticUsage=null): int {
    $managed=str_starts_with($path,'/uploads/')||preg_match('#^https?://#i',$path);$static=$managed?0:($staticUsage!==null?($staticUsage[$path]??0):media_static_usage_count($path));return media_content_usage_count($db,$path)+$static;
}
function media_protection_reason(string $path): string {
    $urlPath=(string)(parse_url($path,PHP_URL_PATH)?:$path);
    $protected=['/apple-touch-icon.png','/favicon-64.png','/images/logo.webp','/images/mascotita-whatsapp.png'];
    if(in_array($urlPath,$protected,true)||str_starts_with($urlPath,'/icons/'))return 'Recurso estructural del sitio';
    return '';
}

function detach_media_references(PDO $db,string $path): int {
    $affected=0;
    foreach([
        ['UPDATE products SET image_path="" WHERE image_path=?','execute'],
        ['DELETE FROM product_gallery WHERE media_path=?','execute'],
        ['UPDATE product_packages SET image_path="" WHERE image_path=?','execute'],
        ['UPDATE content_fields SET value_es="" WHERE value_es=?','execute'],
        ['UPDATE content_fields SET value_en="" WHERE value_en=?','execute'],
        ['UPDATE cms_collections SET logo_path="" WHERE logo_path=?','execute'],
        ['UPDATE cms_collections SET cover_path="" WHERE cover_path=?','execute'],
        ['DELETE FROM cms_collection_media WHERE media_path=?','execute'],
        ['UPDATE home_announcements SET image_path="" WHERE image_path=?','execute'],
    ] as [$sql]){
        $statement=$db->prepare($sql);$statement->execute([$path]);$affected+=$statement->rowCount();
    }
    $aliases=$db->prepare('DELETE FROM media_aliases WHERE local_path=? OR remote_path=?');$aliases->execute([$path,$path]);$affected+=$aliases->rowCount();
    return $affected;
}

function delete_media_everywhere(PDO $db,int $mediaId): array {
    $statement=$db->prepare('SELECT * FROM media WHERE id=? LIMIT 1');$statement->execute([$mediaId]);$media=$statement->fetch();
    if(!$media)throw new RuntimeException('La imagen ya no existe en la biblioteca.');
    $path=(string)$media['path'];$reason=media_protection_reason($path);if($reason!=='')throw new RuntimeException('Este archivo está protegido: '.$reason.'.');$uses=media_usage_count($db,$path,$mediaId);if(preg_match('#^https?://#i',$path)){$settings=r2_settings($db);if(str_starts_with($path,$settings['r2_public_url'].'/'))$uses=max($uses,r2_reference_key_counts($db,$settings)[r2_key_from_url($db,$path)]??0);}if($uses>0)throw new RuntimeException('No se puede eliminar porque la imagen está publicada en '.$uses.' lugar'.($uses===1?'':'es').'. Reemplázala primero desde su editor.');
    $isRemote=preg_match('#^https?://#i',$path)===1;$detached=0;
    $db->beginTransaction();
    try{$detached=detach_media_references($db,$path);$db->prepare('DELETE FROM media WHERE path=?')->execute([$path]);$db->commit();}
    catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
    $storageDeleted=false;$storageError='';
    try{
        if($isRemote){foreach(array_reverse(managed_image_group_urls($db,$path)) as $asset)r2_delete($db,$asset);$storageDeleted=true;}
        else{$absolute=local_media_path($path,false);$storageDeleted=!is_file($absolute)||@unlink($absolute);if(!$storageDeleted)throw new RuntimeException('No se pudo eliminar el archivo local.');}
    }catch(Throwable $error){$storageError=$error->getMessage();}
    return ['path'=>$path,'detached'=>$detached,'storage_deleted'=>$storageDeleted,'storage_error'=>$storageError];
}
function release_media_if_unused(PDO $db,string $path): bool {
    $path=trim($path);if($path===''||media_content_usage_count($db,$path)>0||media_static_usage_count($path)>0)return false;
    $isLocal=str_starts_with($path,'/uploads/');$isRemote=preg_match('#^https?://#i',$path)===1;if(!$isLocal&&!$isRemote)return false;
    if($isRemote){$settings=r2_settings($db);$publicUrl=$settings['r2_public_url'].'/';if(!str_starts_with($path,$publicUrl))return false;$group=managed_image_group_urls($db,$path);$references=r2_reference_key_counts($db,$settings);foreach($group as $asset){if(media_content_usage_count($db,$asset)>0||($references[r2_key_from_url($db,$asset)]??0)>0)return false;}foreach(array_reverse($group) as $asset)r2_delete($db,$asset);}
    if($isLocal){$absolute=local_upload_path($path,false);if(is_file($absolute)&&!@unlink($absolute))throw new RuntimeException('No se pudo retirar la imagen local reemplazada.');}
    $statement=$db->prepare('DELETE FROM media WHERE path=?');$statement->execute([$path]);return true;
}
function release_replaced_media(PDO $db,array $oldPaths,array $activePaths=[],int $userId=0,string $entity='',string|int $entityId=''): array {
    if($db->inTransaction())throw new RuntimeException('La limpieza de R2 debe ejecutarse después del commit.');
    $active=array_fill_keys(array_filter(array_map(static fn($path):string=>trim((string)$path),$activePaths)),true);$result=['deleted'=>0,'kept'=>0,'errors'=>[]];
    foreach(array_unique(array_filter(array_map(static fn($path):string=>trim((string)$path),$oldPaths))) as $path){
        $references=media_content_usage_count($db,$path);$reason='';$error='';$deleted=false;
        if(isset($active[$path]))$reason='La nueva referencia sigue usando el mismo archivo.';
        elseif($references>0)$reason='El archivo tiene '.$references.' referencia(s) en la base de datos.';
        else try{$deleted=release_media_if_unused($db,$path);if(!$deleted)$reason='Archivo protegido, externo o todavía utilizado por el sitio.';}catch(Throwable $exception){$error=$exception->getMessage();$reason='Borrado pendiente en R2 o almacenamiento local.';$result['errors'][]=basename((string)(parse_url($path,PHP_URL_PATH)?:$path)).': '.$error;}
        if($deleted)$result['deleted']++;else $result['kept']++;
        if($userId>0)try{audit($db,$userId,'media-replacement',$entity,$entityId,['old_path'=>$path,'new_paths'=>array_values(array_unique(array_filter($activePaths))),'deleted'=>$deleted,'references'=>$references,'reason'=>$reason,'error'=>$error]);}catch(Throwable $auditError){$result['errors'][]='No se pudo auditar '.basename($path).': '.$auditError->getMessage();}
    }
    return $result;
}
function current_product_media_paths(PDO $db,int $id): array {
    $statement=$db->prepare("SELECT image_path path FROM products WHERE id=? UNION ALL SELECT image_path FROM product_packages WHERE product_id=? UNION ALL SELECT media_path FROM product_gallery WHERE product_id=? AND media_type='image'");
    $statement->execute([$id,$id,$id]);return $statement->fetchAll(PDO::FETCH_COLUMN);
}
function current_collection_media_paths(PDO $db,int $id): array {
    $statement=$db->prepare('SELECT logo_path path FROM cms_collections WHERE id=? UNION ALL SELECT cover_path FROM cms_collections WHERE id=? UNION ALL SELECT media_path FROM cms_collection_media WHERE collection_id=?');
    $statement->execute([$id,$id,$id]);return $statement->fetchAll(PDO::FETCH_COLUMN);
}
function r2_object_url(array $settings,string $key): string {
    return $settings['r2_public_url'].'/'.implode('/',array_map('rawurlencode',explode('/',$key)));
}
function r2_reference_key_counts(PDO $db,array $settings): array {
    $counts=[];$base=$settings['r2_public_url'].'/';
    foreach(media_reference_columns() as [$table,$column]){
        foreach($db->query("SELECT $column path,COUNT(*) uses FROM $table WHERE $column<>'' GROUP BY $column") as $row){
            $path=(string)$row['path'];
            if(str_starts_with($path,$base))$key=rawurldecode(substr($path,strlen($base)));
            else{$urlPath=(string)(parse_url($path,PHP_URL_PATH)?:'');$offset=strpos($urlPath,'/royalbeans/');if($offset===false)continue;$key=rawurldecode(substr($urlPath,$offset+1));}
            $counts[$key]=($counts[$key]??0)+(int)$row['uses'];
        }
    }
    return $counts;
}
function r2_orphan_preview(PDO $db): array {
    $settings=r2_settings($db);$objects=r2_list_objects($db,'royalbeans/');$images=[];$recent=0;
    foreach($objects as $object){
        $key=(string)$object['key'];if(!preg_match('#^royalbeans/(?:by-hash/|[^/]+/)*[^/]+\.(?:jpe?g|png|webp|avif|gif|svg)$#i',$key))continue;
        $modified=strtotime((string)$object['modified']);if($modified===false||$modified>time()-86400){$recent++;continue;}
        $object['url']=r2_object_url($settings,$key);$images[]=$object;
    }
    $paths=array_column($images,'url');$references=r2_reference_key_counts($db,$settings);$static=media_static_usage_map($paths,false);$orphans=[];$bytes=0;
    $protectedGroups=[];foreach($references as $key=>$count)if($count>0&&preg_match('~^(royalbeans/products/by-hash/[a-f0-9]{64}/)original\.(?:png|jpe?g|webp)$~i',$key,$match))$protectedGroups[$match[1]]=true;
    foreach($images as $object){$url=$object['url'];$group=preg_match('~^(royalbeans/products/by-hash/[a-f0-9]{64}/)(?:original\.(?:png|jpe?g|webp)|(?:320|640|1200)\.webp)$~i',$object['key'],$match)?$match[1]:'';if(($references[$object['key']]??0)>0||($static[$url]??0)>0||($group!==''&&isset($protectedGroups[$group])))continue;$orphans[]=$object;$bytes+=(int)$object['size'];}
    $signature=hash_hmac('sha256',json_encode(array_map(static fn($item)=>[$item['key'],$item['size'],$item['modified']],$orphans),JSON_UNESCAPED_SLASHES),admin_secret_key());
    return ['objects'=>count($objects),'recent'=>$recent,'orphans'=>$orphans,'bytes'=>$bytes,'signature'=>$signature];
}
function r2_delete_previewed_orphans(PDO $db,string $signature,int $userId): array {
    if($db->inTransaction())throw new RuntimeException('No se puede limpiar R2 dentro de una transacción.');
    $preview=r2_orphan_preview($db);if($signature===''||!hash_equals($preview['signature'],$signature))throw new RuntimeException('El inventario cambió. Previsualiza de nuevo antes de borrar.');
    $result=['deleted'=>0,'bytes'=>0,'kept'=>0,'errors'=>[]];
    foreach($preview['orphans'] as $object){$url=$object['url'];$key=$object['key'];$error='';$storageDeleted=false;
        try{
            $references=r2_reference_key_counts($db,r2_settings($db));$group=preg_match('~^(royalbeans/products/by-hash/[a-f0-9]{64}/)(?:original\.(?:png|jpe?g|webp)|(?:320|640|1200)\.webp)$~i',$key,$match)?$match[1]:'';$groupUsed=false;if($group!=='')foreach($references as $referenceKey=>$count)if($count>0&&str_starts_with($referenceKey,$group.'original.')){$groupUsed=true;break;}if(($references[$key]??0)>0||$groupUsed||media_static_usage_count($url)>0){$result['kept']++;continue;}
            r2_request(r2_settings($db),'DELETE',$key);$storageDeleted=true;$result['deleted']++;$result['bytes']+=(int)$object['size'];
            $db->prepare('DELETE FROM media WHERE path=?')->execute([$url]);
        }catch(Throwable $exception){$error=$exception->getMessage();$result['errors'][]=$key.': '.$error;}
        try{audit($db,$userId,'orphan-cleanup','media-r2',$key,['old_path'=>$url,'deleted'=>$storageDeleted,'reason'=>$error===''?'Sin referencias en BD ni sitio estático.':($storageDeleted?'Archivo R2 eliminado; registro de catálogo pendiente.':'Borrado pendiente.'),'error'=>$error]);}catch(Throwable $exception){$result['errors'][]=$key.': auditoría fallida: '.$exception->getMessage();}
    }
    return $result;
}
function replace_media_references(PDO $db,string $oldPath,string $newPath): void {
    foreach(media_reference_columns() as [$table,$column]){$statement=$db->prepare("UPDATE $table SET $column=? WHERE $column=?");$statement->execute([$newPath,$oldPath]);}$statement=$db->prepare('UPDATE media SET path=? WHERE path=?');$statement->execute([$newPath,$oldPath]);
}
function link_remote_media_preserving_local(PDO $db,string $localPath,string $remotePath): void {
    $source=$db->prepare('SELECT original_name,mime_type,size_bytes,content_hash,alt_es,alt_en,uploaded_by FROM media WHERE path=? ORDER BY id LIMIT 1');$source->execute([$localPath]);$media=$source->fetch();if(!$media)throw new RuntimeException('No se encontró el registro local que debe vincularse con R2.');$alias=$db->prepare('INSERT INTO media_aliases(local_path,remote_path,content_hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE remote_path=VALUES(remote_path),content_hash=VALUES(content_hash)');$alias->execute([$localPath,$remotePath,$media['content_hash']]);foreach(media_reference_columns() as [$table,$column]){$statement=$db->prepare("UPDATE $table SET $column=? WHERE $column=?");$statement->execute([$remotePath,$localPath]);}$exists=$db->prepare('SELECT COUNT(*) FROM media WHERE path=?');$exists->execute([$remotePath]);if((int)$exists->fetchColumn()>0){$update=$db->prepare('UPDATE media SET content_hash=COALESCE(content_hash,?) WHERE path=?');$update->execute([$media['content_hash'],$remotePath]);return;}$insert=$db->prepare('INSERT INTO media(path,original_name,mime_type,size_bytes,content_hash,alt_es,alt_en,uploaded_by) VALUES(?,?,?,?,?,?,?,?)');$insert->execute([$remotePath,$media['original_name'],$media['mime_type'],$media['size_bytes'],$media['content_hash'],$media['alt_es'],$media['alt_en'],$media['uploaded_by']]);
}
function local_upload_path(string $path,bool $mustExist=true): string {
    if(!str_starts_with($path,'/uploads/')||str_contains($path,'..'))throw new RuntimeException('La ruta local no pertenece a la biblioteca de cargas.');$absolute=dirname(__DIR__).str_replace('/',DIRECTORY_SEPARATOR,$path);if($mustExist&&!is_file($absolute))throw new RuntimeException('El archivo local ya no existe en Hostinger.');return $absolute;
}
function local_media_path(string $path,bool $mustExist=true): string {
    $urlPath=(string)(parse_url($path,PHP_URL_PATH)?:'');if($urlPath===''||!str_starts_with($urlPath,'/')||str_contains($urlPath,'..'))throw new RuntimeException('La ruta local no es válida.');$extension=strtolower((string)pathinfo($urlPath,PATHINFO_EXTENSION));if(!in_array($extension,['avif','bmp','gif','ico','jpeg','jpg','png','svg','tif','tiff','webp'],true))throw new RuntimeException('El archivo local no es una imagen compatible.');$absolute=dirname(__DIR__).str_replace('/',DIRECTORY_SEPARATOR,$urlPath);if($mustExist&&!is_file($absolute))throw new RuntimeException('El archivo local ya no existe en Hostinger.');return $absolute;
}
function remove_migrated_local_copy(PDO $db,string $localPath,string $remotePath): void {
    if(media_content_usage_count($db,$localPath)>0)throw new RuntimeException('La ruta local todavía tiene referencias activas.');$remote=$db->prepare("SELECT COUNT(*) FROM media WHERE path=? AND path REGEXP '^https?://'");$remote->execute([$remotePath]);if((int)$remote->fetchColumn()===0)throw new RuntimeException('No se confirmó el registro remoto antes de limpiar Hostinger.');$alias=$db->prepare('SELECT COUNT(*) FROM media_aliases WHERE local_path=? AND remote_path=?');$alias->execute([$localPath,$remotePath]);if((int)$alias->fetchColumn()===0)throw new RuntimeException('No se confirmó el alias remoto antes de limpiar Hostinger.');$absolute=local_media_path($localPath,false);$quarantine='';if(is_file($absolute)){$quarantine=$absolute.'.migrated-'.bin2hex(random_bytes(6));if(!@rename($absolute,$quarantine))throw new RuntimeException('No se pudo preparar la copia local para eliminarla.');}
    try{$db->beginTransaction();$statement=$db->prepare('DELETE FROM media WHERE path=?');$statement->execute([$localPath]);$db->commit();}catch(Throwable $error){if($db->inTransaction())$db->rollBack();if($quarantine!==''&&is_file($quarantine))@rename($quarantine,$absolute);throw $error;}
    $host=strtolower((string)($_SERVER['HTTP_HOST']??$_SERVER['SERVER_NAME']??''));$localEnvironment=PHP_SAPI==='cli-server'||$host==='localhost'||str_starts_with($host,'localhost:')||$host==='127.0.0.1'||str_starts_with($host,'127.0.0.1:');
    if($quarantine!==''&&is_file($quarantine)){
        if($localEnvironment){if(!@rename($quarantine,$absolute))throw new RuntimeException('R2 y MySQL están actualizados, pero no se pudo conservar la copia de desarrollo local.');}
        elseif(!@unlink($quarantine))throw new RuntimeException('R2 y MySQL están actualizados, pero quedó una copia temporal pendiente de eliminar en Hostinger.');
    }
}
function migrate_local_media_to_r2(PDO $db): array {
    r2_settings($db);refresh_media_hashes($db);$rows=$db->query("SELECT id,path,mime_type,content_hash FROM media WHERE path NOT REGEXP '^https?://' AND mime_type LIKE 'image/%' ORDER BY id")->fetchAll();$staticUsage=media_static_usage_map(array_column($rows,'path'));$result=['recovered'=>0,'migrated'=>0,'unused_deleted'=>0,'missing'=>0,'failed'=>0,'local_deleted'=>0,'errors'=>[]];
    foreach($rows as $row){
        $oldPath=(string)$row['path'];
        if(media_usage_count($db,$oldPath,(int)$row['id'],$staticUsage)===0){try{delete_media_asset($db,(int)$row['id']);$result['unused_deleted']++;}catch(Throwable $cleanupError){$result['failed']++;$result['errors'][]=basename($oldPath).': '.$cleanupError->getMessage();}continue;}
        try{
            $newPath='';
            $alias=$db->prepare("SELECT remote_path,content_hash FROM media_aliases WHERE local_path=? AND remote_path REGEXP '^https?://' LIMIT 1");
            $alias->execute([$oldPath]);
            $knownAlias=$alias->fetch();
            if($knownAlias){
                $newPath=(string)$knownAlias['remote_path'];
                $settings=r2_settings($db);
                r2_request($settings,'HEAD',r2_key_from_url($db,$newPath));
                $knownHash=trim((string)($knownAlias['content_hash']??''));
                if($knownHash!==''&&empty($row['content_hash'])){$hashUpdate=$db->prepare('UPDATE media SET content_hash=? WHERE id=?');$hashUpdate->execute([$knownHash,(int)$row['id']]);$row['content_hash']=$knownHash;}
                $result['recovered']++;
            }
            if($newPath===''){
                $absolute=local_media_path($oldPath);
                $extension=strtolower((string)pathinfo($absolute,PATHINFO_EXTENSION));
                $hash=(string)($row['content_hash']?:hash_file('sha256',$absolute));
                if($hash==='')throw new RuntimeException('No se pudo calcular la huella de la imagen.');
                if(empty($row['content_hash'])){$hashUpdate=$db->prepare('UPDATE media SET content_hash=? WHERE id=?');$hashUpdate->execute([$hash,(int)$row['id']]);}
                $remote=$db->prepare("SELECT path FROM media WHERE content_hash=? AND path REGEXP '^https?://' ORDER BY id LIMIT 1");
                $remote->execute([$hash]);
                $newPath=(string)($remote->fetchColumn()?:'');
                if($newPath!=='')$result['recovered']++;
                else{$payload=file_get_contents($absolute);if($payload===false)throw new RuntimeException('No se pudo leer el archivo local.');$newPath=r2_store_payload($db,$payload,(string)$row['mime_type'],$extension==='jpeg'?'jpg':$extension);$result['migrated']++;}
            }
            try{$db->beginTransaction();link_remote_media_preserving_local($db,$oldPath,$newPath);$db->commit();}catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
            try{remove_migrated_local_copy($db,$oldPath,$newPath);$result['local_deleted']++;}catch(Throwable $cleanupError){$result['failed']++;$result['errors'][]=basename($oldPath).': '.$cleanupError->getMessage();}
        }catch(Throwable $error){if(str_contains($error->getMessage(),'ya no existe'))$result['missing']++;else$result['failed']++;$result['errors'][]=basename($oldPath).': '.$error->getMessage();}
    }
    return $result;
}
function delete_media_asset(PDO $db,int $mediaId): string {
    $statement=$db->prepare('SELECT * FROM media WHERE id=? LIMIT 1');$statement->execute([$mediaId]);$media=$statement->fetch();if(!$media)throw new RuntimeException('La imagen ya no existe en la biblioteca.');$path=(string)$media['path'];$uses=media_usage_count($db,$path,$mediaId);if($uses>0)throw new RuntimeException('No se puede eliminar porque la imagen está en uso en '.$uses.' lugar'.($uses===1?'':'es').'.');
    $reason=media_protection_reason($path);if($reason!=='')throw new RuntimeException('Este archivo está protegido: '.$reason.'.');$isRemote=preg_match('#^https?://#i',$path)===1;$absolute='';$quarantine='';if(!$isRemote){if(!str_starts_with($path,'/')||str_contains($path,'..'))throw new RuntimeException('La ruta local no es válida.');$absolute=dirname(__DIR__).str_replace('/',DIRECTORY_SEPARATOR,$path);if(is_file($absolute)){$quarantine=$absolute.'.deleting-'.bin2hex(random_bytes(6));if(!@rename($absolute,$quarantine))throw new RuntimeException('No se pudo preparar el archivo local para eliminarlo.');}}
    try{$db->beginTransaction();$db->prepare('DELETE FROM media WHERE path=?')->execute([$path]);$db->commit();}catch(Throwable $error){if($db->inTransaction())$db->rollBack();if($quarantine!==''&&is_file($quarantine))@rename($quarantine,$absolute);throw $error;}if($isRemote)foreach(array_reverse(managed_image_group_urls($db,$path)) as $asset)r2_delete($db,$asset);if($quarantine!==''&&is_file($quarantine)&&!@unlink($quarantine))throw new RuntimeException('El medio se eliminó del catálogo, pero quedó un archivo temporal pendiente de limpieza.');return $path;
}
function delete_unused_local_media(PDO $db): array {
    $rows=$db->query("SELECT id,path FROM media WHERE path NOT REGEXP '^https?://' AND mime_type LIKE 'image/%' ORDER BY id")->fetchAll();$result=['deleted'=>0,'kept'=>0,'errors'=>[]];
    foreach($rows as $row){$path=(string)$row['path'];if(media_usage_count($db,$path,(int)$row['id'])>0||media_protection_reason($path)!==''){$result['kept']++;continue;}try{delete_media_asset($db,(int)$row['id']);$result['deleted']++;}catch(Throwable $error){$result['errors'][]=basename($path).': '.$error->getMessage();}}
    return $result;
}
function delete_unused_media(PDO $db): array {
    $rows=$db->query("SELECT id,path FROM media WHERE mime_type LIKE 'image/%' ORDER BY id")->fetchAll();$staticUsage=media_static_usage_map(array_column($rows,'path'));$result=['deleted'=>0,'kept'=>0,'local_deleted'=>0,'remote_deleted'=>0,'errors'=>[]];
    foreach($rows as $row){$path=(string)$row['path'];if(media_usage_count($db,$path,(int)$row['id'],$staticUsage)>0){$result['kept']++;continue;}try{delete_media_asset($db,(int)$row['id']);$result['deleted']++;if(preg_match('#^https?://#i',$path))$result['remote_deleted']++;else$result['local_deleted']++;}catch(Throwable $error){$result['errors'][]=basename((string)(parse_url($path,PHP_URL_PATH)?:$path)).': '.$error->getMessage();}}
    return $result;
}
function cleanup_failed_uploads(PDO $db,array $uploads): array {
    $result=['deleted'=>0,'kept'=>0,'errors'=>[]];foreach($uploads as $upload){$path=(string)($upload['path']??'');$creation=(string)($upload['creation']??'');if($path===''||$creation==='')continue;try{if(media_content_usage_count($db,$path)>0){$result['kept']++;continue;}if($creation==='local'||$creation==='r2'){if(release_media_if_unused($db,$path))$result['deleted']++;else$result['kept']++;continue;}$statement=$db->prepare('DELETE FROM media WHERE path=?');$statement->execute([$path]);$result['deleted']+=$statement->rowCount();}catch(Throwable $error){$result['errors'][]=basename((string)(parse_url($path,PHP_URL_PATH)?:$path)).': '.$error->getMessage();}}return $result;
}
function optimized_webp_payload(string $source, int $maxEdge = 1600, int $quality = 80): string {
    if(!extension_loaded('gd')||!function_exists('imagewebp'))throw new RuntimeException('El servidor necesita GD con soporte WebP para procesar imágenes.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($source);
    $types=['image/png'=>IMAGETYPE_PNG,'image/jpeg'=>IMAGETYPE_JPEG,'image/webp'=>IMAGETYPE_WEBP];
    if(!isset($types[$mime]))throw new RuntimeException('Formato permitido: PNG, JPG, JPEG o WebP.');
    $info=@getimagesize($source);
    if(!$info||($info[2]??0)!==$types[$mime])throw new RuntimeException('El archivo no contiene una imagen válida.');
    $width=(int)$info[0];$height=(int)$info[1];
    if($width<1||$height<1||$width*$height>50000000)throw new RuntimeException('Las dimensiones de la imagen no son válidas.');
    $image=match($mime){'image/png'=>@imagecreatefrompng($source),'image/jpeg'=>@imagecreatefromjpeg($source),'image/webp'=>@imagecreatefromwebp($source)};
    if(!$image)throw new RuntimeException('No se pudo procesar la imagen.');
    try{
        if($mime==='image/jpeg'&&function_exists('exif_read_data')){
            $exif=@exif_read_data($source);
            $orientation=is_array($exif)?(int)($exif['Orientation']??1):1;
            if(in_array($orientation,[2,4,5,7],true)){
                if(!imageflip($image,in_array($orientation,[2,5,7],true)?IMG_FLIP_HORIZONTAL:IMG_FLIP_VERTICAL))throw new RuntimeException('No se pudo orientar la imagen.');
            }
            $angle=match($orientation){3=>180,5=>90,6=>-90,7=>-90,8=>90,default=>0};
            if($angle!==0){
                $oriented=imagerotate($image,$angle,0);
                if(!$oriented)throw new RuntimeException('No se pudo orientar la imagen.');
                imagedestroy($image);$image=$oriented;
            }
            $width=imagesx($image);$height=imagesy($image);
        }
        $scale=min(1,$maxEdge/max($width,$height));
        $targetWidth=max(1,(int)round($width*$scale));$targetHeight=max(1,(int)round($height*$scale));
        if($scale<1){
            $resized=imagecreatetruecolor($targetWidth,$targetHeight);
            if(!$resized)throw new RuntimeException('No se pudo reducir la imagen.');
            imagealphablending($resized,false);imagesavealpha($resized,true);
            $transparent=imagecolorallocatealpha($resized,0,0,0,127);imagefilledrectangle($resized,0,0,$targetWidth,$targetHeight,$transparent);
            if(!imagecopyresampled($resized,$image,0,0,0,0,$targetWidth,$targetHeight,$width,$height)){imagedestroy($resized);throw new RuntimeException('No se pudo reducir la imagen.');}
            imagedestroy($image);$image=$resized;
        }
        ob_start();
        try{$saved=imagewebp($image,null,$quality);$payload=ob_get_contents();}finally{ob_end_clean();}
        if(!$saved||!is_string($payload)||$payload==='')throw new RuntimeException('No se pudo generar la imagen WebP.');
        return $payload;
    }finally{imagedestroy($image);}
}
function managed_image_group_urls(PDO $db,string $path): array {
    if(!preg_match('~/royalbeans/products/by-hash/[a-f0-9]{64}/original\.(?:png|jpe?g|webp)$~i',$path))return [$path];
    $base=substr($path,0,strrpos($path,'/')+1);
    return [$path,$base.'320.webp',$base.'640.webp',$base.'1200.webp'];
}
function store_managed_image_source(PDO $db,string $source,string $name,int $userId,?string &$creation=null): string {
    $creation='';
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($source);
    $extension=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??'';
    if($extension==='')throw new RuntimeException('Formato permitido: PNG, JPG, JPEG o WebP.');
    $info=@getimagesize($source);
    $expectedType=['image/png'=>IMAGETYPE_PNG,'image/jpeg'=>IMAGETYPE_JPEG,'image/webp'=>IMAGETYPE_WEBP][$mime];
    if(!$info||($info[2]??0)!==$expectedType||($info[0]??0)<1||($info[1]??0)<1||$info[0]>8000||$info[1]>8000||($info[0]*$info[1])>20000000)throw new RuntimeException('Las dimensiones de la imagen no son válidas o superan el límite permitido.');
    $payload=(string)file_get_contents($source);$hash=hash('sha256',$payload);$settings=r2_settings($db);
    $base='royalbeans/products/by-hash/'.$hash.'/';
    $originalKey=$base.'original.'.$extension;$path=r2_object_url($settings,$originalKey);
    $variants=['320.webp'=>optimized_webp_payload($source,320,74),'640.webp'=>optimized_webp_payload($source,640,76),'1200.webp'=>optimized_webp_payload($source,1200,80)];
    $existingMedia=$db->prepare('SELECT COUNT(*) FROM media WHERE path=?');$existingMedia->execute([$path]);$mediaExisted=(int)$existingMedia->fetchColumn()>0;
    $newKeys=[];
    try{
        foreach([$originalKey=>[$payload,$mime],$base.'320.webp'=>[$variants['320.webp'],'image/webp'],$base.'640.webp'=>[$variants['640.webp'],'image/webp'],$base.'1200.webp'=>[$variants['1200.webp'],'image/webp']] as $key=>$asset){
            try{r2_request($settings,'HEAD',$key);continue;}catch(Throwable $error){if(!str_contains($error->getMessage(),'HTTP 404'))throw $error;}
            r2_request($settings,'PUT',$key,$asset[0],$asset[1]);$newKeys[]=$key;
        }
        register_media($db,$path,$name,$mime,strlen($payload),$userId,$hash);
        $db->prepare("INSERT INTO settings(setting_key,value_text,is_public) VALUES('media_storage','r2',0) ON DUPLICATE KEY UPDATE value_text='r2',is_public=0")->execute();
        $creation=$newKeys?'r2':($mediaExisted?'':'catalog');
        if($userId>0)try{audit($db,$userId,'media-variants-created','media',$hash,['original'=>$path,'variants'=>array_keys($variants),'bytes'=>array_map('strlen',$variants)]);}catch(Throwable $auditError){error_log('[Royal Beans media audit] '.$auditError->getMessage());}
        return $path;
    }catch(Throwable $error){
        if(!$mediaExisted)try{$db->prepare('DELETE FROM media WHERE path=?')->execute([$path]);}catch(Throwable $cleanupError){error_log('[Royal Beans media rollback] '.$cleanupError->getMessage());}
        foreach(array_reverse($newKeys) as $key)try{if(media_content_usage_count($db,$path)===0)r2_request($settings,'DELETE',$key);}catch(Throwable $cleanupError){error_log('[Royal Beans R2 upload rollback] '.$cleanupError->getMessage());}
        throw $error;
    }
}
function save_upload(PDO $db, array $file, int $userId,?string &$creation=null): string {
    $creation='';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('La imagen no es válida o supera 8 MB.');
    $source=(string)($file['tmp_name']??'');
    if(!is_uploaded_file($source))throw new RuntimeException('La imagen subida no es válida.');
    return store_managed_image_source($db,$source,(string)($file['name']??'image'),$userId,$creation);
}
function save_document(PDO $db, array $file, int $userId): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 12 * 1024 * 1024) throw new RuntimeException('El PDF no es válido o supera 12 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime !== 'application/pdf') throw new RuntimeException('La ficha técnica debe estar en formato PDF.');
    $folder = '/uploads/' . date('Y/m');
    $absolute = dirname(__DIR__) . $folder;
    if (!is_dir($absolute) && !mkdir($absolute, 0755, true) && !is_dir($absolute)) throw new RuntimeException('No se pudo crear la carpeta de documentos.');
    $name = bin2hex(random_bytes(14)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $absolute . '/' . $name)) throw new RuntimeException('No se pudo guardar el PDF.');
    $path = $folder . '/' . $name;
    $s = $db->prepare('INSERT INTO media (path,original_name,mime_type,size_bytes,uploaded_by) VALUES (?,?,?,?,?)');
    $s->execute([$path,mb_substr((string) $file['name'],0,255),$mime,(int) $file['size'],$userId]);
    return $path;
}
