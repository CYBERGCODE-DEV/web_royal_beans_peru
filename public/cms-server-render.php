<?php
declare(strict_types=1);

require_once __DIR__ . '/api/_bootstrap.php';

function cms_server_safe(string $value): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', strtolower($value)) ?: $value;
    return substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-'), 0, 46);
}

function cms_server_attributes(string $tag): array {
    preg_match_all('/([\w:-]+)\s*=\s*(["\'])(.*?)\2/s', $tag, $matches, PREG_SET_ORDER);
    $attributes = [];
    foreach ($matches as $match) $attributes[strtolower($match[1])] = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $attributes;
}

/** Scan the exported HTML without reserializing it (which would alter SVG and Next scripts). */
function cms_server_nodes(string $html): array {
    $start = stripos($html, '<main');
    $end = $start === false ? false : stripos($html, '</main>', $start);
    if ($start === false || $end === false) return [];
    $fragment = substr($html, $start, $end + 7 - $start);
    preg_match_all('/<!--.*?-->|<script\b[^>]*>.*?<\/script\s*>|<[^>]+>|[^<]+/is', $fragment, $tokens, PREG_OFFSET_CAPTURE);
    $nodes = []; $stack = [];
    $void = ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'];
    foreach ($tokens[0] as [$token, $relative]) {
        $offset = $start + $relative;
        if ($token[0] !== '<') {
            if ($stack) $nodes[end($stack)]['direct'] .= html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            continue;
        }
        if (str_starts_with($token, '<!--') || preg_match('/^<script\b/i', $token)) continue;
        if (preg_match('/^<\/([a-z][\w:-]*)/i', $token, $closing)) {
            $tag = strtolower($closing[1]);
            for ($i = count($stack) - 1; $i >= 0; $i--) {
                $index = $stack[$i];
                if ($nodes[$index]['tag'] !== $tag) continue;
                $nodes[$index]['closeStart'] = $offset;
                $nodes[$index]['closeEnd'] = $offset + strlen($token);
                $stack = array_slice($stack, 0, $i);
                break;
            }
            continue;
        }
        if (!preg_match('/^<([a-z][\w:-]*)\b/i', $token, $opening)) continue;
        $tag = strtolower($opening[1]);
        $parent = $stack ? end($stack) : null;
        $nodes[] = ['tag'=>$tag, 'attributes'=>cms_server_attributes($token), 'parent'=>$parent,
            'start'=>$offset, 'openEnd'=>$offset + strlen($token), 'closeStart'=>null, 'closeEnd'=>null, 'direct'=>'', 'key'=>''];
        $index = count($nodes) - 1;
        if (!in_array($tag, $void, true) && !str_ends_with(rtrim($token), '/>')) $stack[] = $index;
    }
    $main = null; $sections = [];
    foreach ($nodes as $index => $node) {
        if ($node['tag'] === 'main' && $main === null) $main = $index;
        if ($node['tag'] === 'section' && $node['parent'] === $main) $sections[] = $index;
    }
    if ($main === null) return [];
    if (!$sections) $sections = [$main];
    preg_match_all('/\bdata-cms="([^"]+)"/', $html, $declared);
    $used = array_fill_keys($declared[1], true);
    foreach ($sections as $sectionIndex => $section) {
        $attrs = $nodes[$section]['attributes'];
        $classes = preg_split('/\s+/', $attrs['class'] ?? '') ?: [];
        $class = '';
        foreach ($classes as $candidate) if ($candidate !== '' && !in_array($candidate, ['section-pad','container'], true)) { $class = $candidate; break; }
        $name = cms_server_safe($attrs['id'] ?? ($class ?: ($nodes[$section]['tag'] . '-0-' . $sectionIndex))) ?: 'section-' . $sectionIndex;
        $nodes[$section]['sectionName'] = $name;
        $counts = [];
        foreach ($nodes as $index => $node) {
            if ($index === $section || $node['start'] < $nodes[$section]['openEnd'] || ($nodes[$section]['closeStart'] !== null && $node['start'] >= $nodes[$section]['closeStart'])) continue;
            $ancestor = $node['parent']; $inside = false; $excluded = false;
            while ($ancestor !== null) {
                if ($ancestor === $section) { $inside = true; break; }
                if (array_key_exists('data-cms-managed', $nodes[$ancestor]['attributes']) || array_key_exists('data-product-id', $nodes[$ancestor]['attributes'])) $excluded = true;
                $ancestor = $nodes[$ancestor]['parent'];
            }
            if (!$inside || $excluded || array_key_exists('data-cms-managed', $node['attributes']) || array_key_exists('data-product-id', $node['attributes'])) continue;
            $tag = $node['tag'];
            if (!in_array($tag, ['h1','h2','h3','h4','h5','h6','p','li','blockquote','figcaption','dt','dd','a','button','span','strong','small','label','img'], true)) continue;
            if ($tag === 'img' ? (($node['attributes']['alt'] ?? null) === '') : trim($node['direct']) === '') continue;
            if (isset($node['attributes']['data-cms'])) { $nodes[$index]['key'] = $node['attributes']['data-cms']; continue; }
            $kind = $tag === 'img' ? 'image' : cms_server_safe($tag . '-' . ($node['attributes']['class'] ?? 'text'));
            $counts[$kind] = ($counts[$kind] ?? 0) + 1;
            $key = $name . '.' . $kind . '-' . $counts[$kind];
            while (isset($used[$key])) { $counts[$kind]++; $key = $name . '.' . $kind . '-' . $counts[$kind]; }
            $used[$key] = true;
            $nodes[$index]['key'] = $key;
        }
    }
    // Explicit CMS fields can be nested outside the editor's auto-enumerated sections.
    foreach ($nodes as &$node) if ($node['key'] === '' && isset($node['attributes']['data-cms'])) $node['key'] = $node['attributes']['data-cms'];
    unset($node);
    return $nodes;
}

function cms_server_field_style(array $field): array {
    $style = $field['style'] ?? [];
    $out = [];
    $fonts = ['display'=>'var(--font-display-active,var(--font-display))', 'body'=>'var(--font-body-active,var(--font-body))', 'editorial'=>"Georgia, 'Times New Roman', serif"];
    if (isset($fonts[$style['font'] ?? ''])) $out['fontFamily'] = $fonts[$style['font']];
    if (preg_match('/^#[0-9a-f]{6}$/i', (string) ($style['color'] ?? ''))) $out['color'] = $style['color'];
    if (($style['size'] ?? 0) >= 10 && ($style['size'] ?? 0) <= 120) $out['fontSize'] = (int) $style['size'] . 'px';
    return $out;
}

function cms_server_html_style(array $styles): string {
    $names = ['fontFamily'=>'font-family', 'color'=>'color', 'fontSize'=>'font-size'];
    $declarations = [];
    foreach ($styles as $key => $value) $declarations[] = ($names[$key] ?? $key) . ':' . $value;
    return implode(';', $declarations);
}

function cms_server_add_attributes(string $opening, string $attributes): string {
    $end = str_ends_with($opening, '/>') ? strlen($opening) - 2 : strlen($opening) - 1;
    return substr_replace($opening, ' ' . $attributes, $end, 0);
}

function cms_server_text_html(array $field, string $lang): string {
    $value = (string) $field['value'];
    $runs = $field['style']['richText'][$lang] ?? null;
    if (!is_array($runs) || implode('', array_map(static fn($run) => is_array($run) ? (string) ($run['text'] ?? '') : '', $runs)) !== $value) return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '';
    foreach ($runs as $run) {
        $text = htmlspecialchars((string) ($run['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $color = (string) ($run['color'] ?? '');
        $html .= preg_match('/^#[0-9a-f]{6}$/i', $color) ? '<span data-cms-run="color" style="color:' . $color . '">' . $text . '</span>' : $text;
    }
    return $html;
}

function cms_server_replace_html(string $html, array $nodes, array $fields, string $lang): string {
    $edits = []; $covered = [];
    $heroField = $fields['hero.image'] ?? null;
    if ($heroField) {
        $heroStyle = $heroField['style'];
        $image = (string) $heroField['value'];
        foreach ($nodes as $node) {
            if (array_key_exists('data-hero', $node['attributes'])) {
                $opening = substr($html, $node['start'], $node['openEnd'] - $node['start']);
                $declarations = [];
                if (($heroStyle['heroHeight'] ?? 0) >= 360 && ($heroStyle['heroHeight'] ?? 0) <= 950) $declarations[] = '--hero-height:' . (int) $heroStyle['heroHeight'] . 'px';
                if (isset($heroStyle['heroOverlay']) && is_numeric($heroStyle['heroOverlay'])) $declarations[] = '--hero-overlay:' . max(0, min(90, (float) $heroStyle['heroOverlay'])) / 100;
                if ($declarations) {
                    $css = implode(';', $declarations);
                    $opening = preg_match('/\sstyle="([^"]*)"/', $opening)
                        ? preg_replace_callback('/\sstyle="([^"]*)"/', static fn($m) => ' style="' . $m[1] . ';' . $css . '"', $opening, 1)
                        : cms_server_add_attributes($opening, 'style="' . $css . '"');
                }
                if (($heroStyle['heroShowText'] ?? true) === false) $opening = cms_server_add_attributes($opening, 'data-hero-text-hidden=""');
                $edits[] = [$node['start'], $node['openEnd'], $opening];
            }
            if ($node['tag'] !== 'source') continue;
            $parent = $node['parent'];
            if ($parent === null || $nodes[$parent]['tag'] !== 'picture') continue;
            $isHeroPicture = false;
            foreach ($nodes as $sibling) if ($sibling['parent'] === $parent && $sibling['key'] === 'hero.image') { $isHeroPicture = true; break; }
            if (!$isHeroPicture) continue;
            $opening = substr($html, $node['start'], $node['openEnd'] - $node['start']);
            $opening = preg_replace('/\s(?:srcset|type)="[^"]*"/i', '', $opening);
            if ($image !== '') $opening = cms_server_add_attributes($opening, 'srcSet="' . htmlspecialchars($image, ENT_QUOTES) . '"');
            $edits[] = [$node['start'], $node['openEnd'], $opening];
        }
    }
    foreach ($nodes as $index => $node) {
        $key = $node['key'];
        if ($key === '' || !isset($fields[$key])) continue;
        $field = $fields[$key]; $value = (string) $field['value'];
        if ($node['tag'] === 'img' && $field['type'] !== 'image') continue;
        if ($field['type'] !== 'image' && $node['closeStart'] === null) continue;
        $ancestor = $node['parent']; $skip = false;
        while ($ancestor !== null) {
            if (isset($covered[$ancestor])) { $skip = true; break; }
            $ancestor = $nodes[$ancestor]['parent'];
        }
        if ($skip) continue;
        $opening = substr($html, $node['start'], $node['openEnd'] - $node['start']);
        $styles = cms_server_field_style($field);
        if ($styles) {
            $css = cms_server_html_style($styles);
            $opening = preg_match('/\sstyle="([^"]*)"/', $opening)
                ? preg_replace_callback('/\sstyle="([^"]*)"/', static fn($m) => ' style="' . $m[1] . ';' . htmlspecialchars($css, ENT_QUOTES) . '"', $opening, 1)
                : cms_server_add_attributes($opening, 'style="' . htmlspecialchars($css, ENT_QUOTES) . '"');
        }
        // Keep the exported element tree intact when the DB already has its text.
        // Flattening <br> or inline markup here would differ from React's RSC tree.
        $richRuns = $field['style']['richText'][$lang] ?? null;
        if ($field['type'] !== 'image' && $node['direct'] === $value && !is_array($richRuns)) {
            if ($styles) $edits[] = [$node['start'], $node['openEnd'], $opening];
            continue;
        }
        if ($field['type'] === 'image') {
            $opening = preg_replace('/\ssrc="[^"]*"/', '', $opening, 1);
            if ($value !== '') $opening = cms_server_add_attributes($opening, 'src="' . htmlspecialchars($value, ENT_QUOTES) . '"');
            if ($value === '') $opening = cms_server_add_attributes($opening, 'hidden data-cms-image-empty=""');
            $edits[] = [$node['start'], $node['openEnd'], $opening];
            continue;
        }
        $inner = substr($html, $node['openEnd'], $node['closeStart'] - $node['openEnd']);
        $preserve = false;
        foreach ($nodes as $child) if ($child['parent'] === $index && in_array($child['tag'], ['svg','img','span'], true) && ($child['tag'] !== 'span' || !isset($child['attributes']['data-cms-run']))) { $preserve = true; break; }
        if ($preserve) {
            $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $inner = preg_replace('/(^|>)([^<>]*\S[^<>]*)(?=<|$)/u', '$1' . str_replace('$', '\\$', $escaped), $inner, 1) ?? $inner;
        } else {
            $inner = cms_server_text_html($field, $lang);
            $covered[$index] = true;
        }
        $edits[] = [$node['start'], $node['closeEnd'], $opening . $inner . substr($html, $node['closeStart'], $node['closeEnd'] - $node['closeStart'])];
    }
    usort($edits, static fn($a,$b) => $b[0] <=> $a[0]);
    $last = PHP_INT_MAX;
    foreach ($edits as [$start,$end,$replacement]) {
        if ($end > $last) continue;
        $html = substr_replace($html, $replacement, $start, $end - $start);
        $last = $start;
    }
    return $html;
}

function cms_server_rsc_node(mixed &$node, array $fields, array $replacements, string $lang): void {
    if (!is_array($node)) {
        if (is_string($node) && isset($replacements[$node])) $node = $replacements[$node];
        return;
    }
    if (array_is_list($node) && ($node[0] ?? null) === '$' && isset($node[3]) && is_array($node[3])) {
        $props =& $node[3];
        if (array_key_exists('data-hero', $props) && isset($fields['hero.image'])) {
            $heroStyle = $fields['hero.image']['style'];
            $heroCss = [];
            if (($heroStyle['heroHeight'] ?? 0) >= 360 && ($heroStyle['heroHeight'] ?? 0) <= 950) $heroCss['--hero-height'] = (int) $heroStyle['heroHeight'] . 'px';
            if (isset($heroStyle['heroOverlay']) && is_numeric($heroStyle['heroOverlay'])) $heroCss['--hero-overlay'] = (string) (max(0, min(90, (float) $heroStyle['heroOverlay'])) / 100);
            if ($heroCss) $props['style'] = array_merge(is_array($props['style'] ?? null) ? $props['style'] : [], $heroCss);
            if (($heroStyle['heroShowText'] ?? true) === false) $props['data-hero-text-hidden'] = true;
        }
        $key = (string) ($props['data-cms'] ?? '');
        if ($key !== '' && isset($fields[$key])) {
            $field = $fields[$key]; $value = (string) $field['value'];
            if ($field['type'] === 'image' && ($node[1] ?? '') === 'img') {
                if ($value === '') { unset($props['src']); $props['hidden'] = true; $props['data-cms-image-empty'] = true; }
                else $props['src'] = $value;
            } elseif ($field['type'] === 'url' && ($node[1] ?? '') === 'a') $props['href'] = $value;
            elseif ($field['type'] !== 'image') {
                $runs = $field['style']['richText'][$lang] ?? null;
                if (is_array($runs) && implode('', array_map(static fn($run) => is_array($run) ? (string) ($run['text'] ?? '') : '', $runs)) === $value) {
                    $children = [];
                    foreach ($runs as $run) {
                        if (preg_match('/^#[0-9a-f]{6}$/i', (string) ($run['color'] ?? ''))) $children[] = ['$', 'span', null, ['data-cms-run'=>'color','style'=>['color'=>$run['color']],'children'=>(string) $run['text']]];
                        else $children[] = (string) ($run['text'] ?? '');
                    }
                    $props['children'] = $children;
                } else $props['children'] = $value;
            }
            $style = cms_server_field_style($field);
            if ($style) $props['style'] = array_merge(is_array($props['style'] ?? null) ? $props['style'] : [], $style);
        }
        // A CMS node's children were just built from the current DB value.
        // Applying old-text replacements again would corrupt richText runs.
        foreach ($props as $propName => &$part) cms_server_rsc_node($part, $fields, $key !== '' && isset($fields[$key]) && $propName === 'children' ? [] : $replacements, $lang);
        unset($part);
        if (($node[1] ?? '') === 'picture' && isset($fields['hero.image'])) {
            $image = (string) $fields['hero.image']['value'];
            $children =& $props['children'];
            if (is_array($children)) {
                $hasHero = false;
                foreach ($children as $child) if (is_array($child) && ($child[1] ?? '') === 'img' && ($child[3]['data-cms'] ?? '') === 'hero.image') $hasHero = true;
                if ($hasHero) foreach ($children as &$child) if (is_array($child) && ($child[1] ?? '') === 'source') {
                    unset($child[3]['type']);
                    if ($image === '') unset($child[3]['srcSet']); else $child[3]['srcSet'] = $image;
                }
                unset($child);
            }
        }
        return;
    }
    foreach ($node as &$part) cms_server_rsc_node($part, $fields, $replacements, $lang);
    unset($part);
}

function cms_server_rsc(string $rsc, array $fields, array $replacements, string $lang): string {
    return preg_replace_callback('/(^|\n)([0-9a-f]+:)([^\n]*)/i', static function($match) use ($fields,$replacements,$lang) {
        $value = json_decode($match[3], true);
        if (json_last_error() !== JSON_ERROR_NONE) return $match[0];
        cms_server_rsc_node($value, $fields, $replacements, $lang);
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return $match[1] . $match[2] . ($json === false ? $match[3] : $json);
    }, $rsc) ?? $rsc;
}

function cms_server_render(string $html, string $page, string $lang, bool $rsc, ?PDO $db = null): string {
    // Older exported shells may still be deployed for pages unaffected by this
    // release. Keep every shared navigation link on the new canonical route.
    $html = str_replace(['/productos/linea-convencional/', '/en/products/conventional-line/'], ['/productos/linea-a-granel/', '/en/products/bulk-line/'], $html);
    $db ??= rb_pdo();
    $column = $lang === 'en' ? 'value_en' : 'value_es';
    $statement = $db->prepare("SELECT section_key,field_key,field_type,$column AS value,style_json FROM content_fields WHERE page_key=?");
    $statement->execute([$page]);
    $fields = [];
    foreach ($statement as $row) $fields[$row['section_key'] . '.' . $row['field_key']] = ['type'=>$row['field_type'],'value'=>(string) $row['value'],'style'=>json_decode((string) ($row['style_json'] ?? ''), true) ?: []];
    // Contact's heading is essential content: a stale editor flag must not hide it.
    // Keep the database values untouched and make HTML/RSC agree with the API.
    if ($page === 'contacto' && isset($fields['hero.image'])) $fields['hero.image']['style']['heroShowText'] = true;
    $reference = $rsc ? file_get_contents(__DIR__ . '/' . cms_server_static_path($page, $lang) . '/index.html') : $html;
    $nodes = cms_server_nodes(is_string($reference) ? $reference : '');
    $replacements = [];
    $imageReplacements = [];
    foreach ($nodes as $node) {
        $key = $node['key'];
        if ($key === '' || !isset($fields[$key])) continue;
        $old = trim($node['direct']);
        if ($old !== '' && $old !== $fields[$key]['value'] && !isset($replacements[$old])) $replacements[$old] = $fields[$key]['value'];
        if ($node['tag'] === 'img' && isset($node['attributes']['src'])) {
            $oldImage = $node['attributes']['src'];
            $newImage = $fields[$key]['value'];
            $replacements[$oldImage] = $newImage;
            if ($oldImage !== '' && $newImage !== '') $imageReplacements[$oldImage] = $newImage;
        }
    }
    if ($rsc) {
        $html = cms_server_rsc($html, $fields, $replacements, $lang);
        return str_replace(array_keys($imageReplacements), array_values($imageReplacements), $html);
    }
    $html = preg_replace_callback('~<script>self\.__next_f\.push\((\[.*?\])\)</script>~s', static function($match) use ($fields,$replacements,$lang) {
        $payload = json_decode($match[1], true);
        if (!is_array($payload) || !isset($payload[1]) || !is_string($payload[1])) return $match[0];
        $payload[1] = cms_server_rsc($payload[1], $fields, $replacements, $lang);
        return '<script>self.__next_f.push(' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) . ')</script>';
    }, $html) ?? $html;
    $html = cms_server_replace_html($html, $nodes, $fields, $lang);
    // Next emits additional image preload hints outside the rendered main tree.
    $html = str_replace(array_keys($imageReplacements), array_values($imageReplacements), $html);
    $route = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    $html = preg_replace('/(<div class="public-page-stage"[^>]*)(>)/', '$1 data-cms-server-rendered="' . htmlspecialchars($route, ENT_QUOTES) . '"$2', $html, 1) ?? $html;
    $html = str_replace('data-cms-state="loading"', 'data-cms-state="ready"', $html);
    $html = preg_replace('/<div class="cms-content-status".*?<\/div>/s', '', $html) ?? $html;
    $settings = $db->query("SELECT setting_key,value_text FROM settings WHERE is_public=1 AND setting_key IN ('theme_palette','theme_font','theme_background')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $palettes = [
        'royal'=>['--forest'=>'#052f26','--green'=>'#0a493c','--lime'=>'#c5e59b','--paper'=>'#f4f5ec'],
        'harvest'=>['--forest'=>'#263c2c','--green'=>'#496044','--lime'=>'#d8e7a5','--paper'=>'#f7f3e8'],
        'pacific'=>['--forest'=>'#123b3a','--green'=>'#1f5d58','--lime'=>'#b9e2bd','--paper'=>'#f1f5f0'],
    ];
    $backgrounds = ['paper'=>'#f4f5ec','white'=>'#ffffff','mist'=>'#edf3e9'];
    $fonts = ['bricolage-dm'=>['var(--font-display)','var(--font-body)'],'editorial'=>["Georgia, 'Times New Roman', serif",'var(--font-body)'],'modern'=>['var(--font-body)','var(--font-body)']];
    $css = ($palettes[$settings['theme_palette'] ?? ''] ?? $palettes['royal']);
    $css['--paper'] = $backgrounds[$settings['theme_background'] ?? ''] ?? $backgrounds['paper'];
    [$css['--font-display-active'],$css['--font-body-active']] = $fonts[$settings['theme_font'] ?? ''] ?? $fonts['bricolage-dm'];
    $style = '.public-page-stage{' . cms_server_html_style($css) . '}';
    $heroOverlay = $fields['hero.image']['style']['heroOverlay'] ?? null;
    if (is_numeric($heroOverlay)) $style .= ':root{--hero-overlay:' . max(0, min(90, (float) $heroOverlay)) / 100 . '}';
    $routeJson = json_encode($route, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    $html = str_replace('</head>', '<style id="cms-server-theme">' . $style . '</style><script>window.__ROYALBEANS_SERVER_CMS_PATH__=' . $routeJson . ';</script></head>', $html);
    return $html;
}

function cms_server_static_path(string $page, string $lang): string {
    $prefix = $lang === 'en' ? 'en/' : '';
    return $prefix . match ($page) {
        'inicio' => $lang === 'en' ? '' : '.',
        'nosotros' => $lang === 'en' ? 'about-us' : 'nosotros',
        'participacion' => $lang === 'en' ? 'events' : 'participacion',
        'impacto' => $lang === 'en' ? 'impact' : 'impacto',
        'contacto' => $lang === 'en' ? 'contact' : 'contacto',
        'productos-conventional' => $lang === 'en' ? 'products/bulk-line' : 'productos/linea-a-granel',
        'productos-retail' => $lang === 'en' ? 'products/retail' : 'productos/linea-retail',
        'privacidad' => $lang === 'en' ? 'privacy-policy' : 'politica-de-privacidad',
        'terminos' => $lang === 'en' ? 'terms-and-conditions' : 'terminos-y-condiciones',
        default => throw new InvalidArgumentException('Unknown CMS page'),
    };
}
