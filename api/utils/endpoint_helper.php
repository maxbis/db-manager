<?php
/**
 * Discover and display effective HTTP URLs for API scripts behind rewrites/proxies.
 *
 * Prefer endpoint_helper_collect() for programmatic use (JSON, logs, templates).
 *
 * Typical install: REQUEST_URI reflects the rewritten public path while SCRIPT_NAME
 * points at the PHP file that actually ran — both are surfaced so you can see which URL
 * to give clients versus which file handles the request on disk.
 */

/**
 * @param string|null $raw Comma-separated host list uses first token (Forwarded chains).
 */
function endpoint_helper_normalize_host_first(?string $raw): string
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return '';
    }
    $parts = explode(',', $raw, 2);
    return trim($parts[0]);
}

/**
 * @param string|null $uri Request URI — query string is discarded.
 */
function endpoint_helper_strip_query(?string $uri): string
{
    if ($uri === null || $uri === '') {
        return '';
    }
    $q = strpos($uri, '?');
    return $q === false ? $uri : substr($uri, 0, $q);
}

/**
 * @param array|null $server
 */
function endpoint_helper_is_https(?array $server = null): bool
{
    $s = $server ?? $_SERVER;
    $https = strtolower((string)($s['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }
    if (isset($s['SERVER_PORT']) && (string)$s['SERVER_PORT'] === '443') {
        return true;
    }
    $fwd = strtolower((string)($s['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($fwd === 'https') {
        return true;
    }
    $xfssl = strtolower((string)($s['HTTP_X_FORWARDED_SSL'] ?? ''));
    if ($xfssl === 'on' || $xfssl === 'https') {
        return true;
    }
    $rs = strtolower((string)($s['REQUEST_SCHEME'] ?? ''));
    return $rs === 'https';
}

/**
 * Visible authority (HOST header or SERVER_NAME/SERVER_PORT). May include host:port as sent by proxy.
 *
 * Important: Untrusted clients can spoof X-Forwarded-* unless your proxy strips/overwrites them.
 *
 * @param array|null $server
 */
function endpoint_helper_authority(?array $server = null): string
{
    $s = $server ?? $_SERVER;
    $fromFwd = isset($s['HTTP_X_FORWARDED_HOST'])
        ? endpoint_helper_normalize_host_first($s['HTTP_X_FORWARDED_HOST'])
        : '';
    if ($fromFwd !== '') {
        return $fromFwd;
    }
    $hh = trim((string)($s['HTTP_HOST'] ?? ''));
    if ($hh !== '') {
        return $hh;
    }
    $host = trim((string)($s['SERVER_NAME'] ?? 'localhost'));
    $https = endpoint_helper_is_https($s);
    $port = (int)($s['SERVER_PORT'] ?? ($https ? 443 : 80));
    if ((!$https && $port !== 80) || ($https && $port !== 443)) {
        return $host !== '' ? $host . ':' . $port : 'localhost:' . $port;
    }
    return $host !== '' ? $host : 'localhost';
}

/**
 * @param array|null $server
 */
function endpoint_helper_scheme(?array $server = null): string
{
    return endpoint_helper_is_https($server) ? 'https' : 'http';
}

/**
 * Public origin visible to browsers (scheme + authority).
 *
 * @param array|null $server
 */
function endpoint_helper_origin(?array $server = null): string
{
    $s = $server ?? $_SERVER;
    return endpoint_helper_scheme($s) . '://' . endpoint_helper_authority($s);
}

/**
 * Join absolute origin with path (path should start with /).
 */
function endpoint_helper_combine(string $origin, string $path): string
{
    $origin = rtrim($origin, '/');
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }
    return $origin . $path;
}

/**
 * All useful URL hints for diagnosing rewrites vs script entrypoints.
 *
 * @return array<string, string|null>
 */
function endpoint_helper_collect(?array $server = null): array
{
    $s = $server ?? $_SERVER;
    $origin = endpoint_helper_origin($s);
    $requestUriRaw = $s['REQUEST_URI'] ?? '';
    $requestPath = endpoint_helper_strip_query($requestUriRaw);
    $scriptName = trim((string)($s['SCRIPT_NAME'] ?? ''));
    $redirectUrlRaw = isset($s['REDIRECT_URL']) ? (string)$s['REDIRECT_URL'] : '';
    $redirectPath = endpoint_helper_strip_query($redirectUrlRaw);

    return [
        'scheme' => endpoint_helper_scheme($s),
        'authority' => endpoint_helper_authority($s),
        'origin' => $origin,
        'requested_url' => $requestPath !== '' ? endpoint_helper_combine($origin, $requestPath) : $origin . '/',
        'request_uri_raw' => $requestUriRaw !== '' ? $requestUriRaw : null,
        'script_url' => $scriptName !== '' ? endpoint_helper_combine($origin, $scriptName) : null,
        'script_name' => $scriptName !== '' ? $scriptName : null,
        'redirect_url_hint' => $redirectPath !== '' ? endpoint_helper_combine($origin, $redirectPath) : null,
        'php_self' => isset($s['PHP_SELF']) && (string)$s['PHP_SELF'] !== '' ? (string)$s['PHP_SELF'] : null,
        'document_root' => isset($s['DOCUMENT_ROOT']) && (string)$s['DOCUMENT_ROOT'] !== '' ? (string)$s['DOCUMENT_ROOT'] : null,
        'script_filename' => isset($s['SCRIPT_FILENAME']) && (string)$s['SCRIPT_FILENAME'] !== '' ? (string)$s['SCRIPT_FILENAME'] : null,
    ];
}

/**
 * JSON response for AJAX or tooling.
 *
 * @param array|null $server
 */
function endpoint_helper_collect_json(?array $server = null, int $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string
{
    return json_encode(endpoint_helper_collect($server), $options);
}

/**
 * Safe HTML snippet for dashboards/settings (caller must authenticate).
 *
 * @param array<string, string|null>|null $data Omit to use endpoint_helper_collect()
 */
function endpoint_helper_render_html_fragment(?array $data = null): string
{
    $d = $data ?? endpoint_helper_collect();
    $blocks = [];

    foreach (
        [
            'requested_url' => 'Effective request URL',
            'script_url' => 'Executing script URL (SCRIPT_NAME)',
            'redirect_url_hint' => 'Redirect target hint (REDIRECT_URL, if present)',
        ] as $key => $label
    ) {
        $value = isset($d[$key]) ? (string)$d[$key] : '';
        if ($value === '') {
            continue;
        }
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $id = htmlspecialchars('ep_' . preg_replace('/\W/', '_', (string)$key), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $blocks[] =
            '<div class="endpoint-helper-row">'
            . '<label for="' . $id . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</label>'
            . '<input id="' . $id . '" class="endpoint-helper-url" readonly type="text" value="' . $escaped . '">'
            . '</div>';
    }

    $meta = sprintf(
        'Scheme: %s · Host: %s',
        htmlspecialchars((string)$d['scheme'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        htmlspecialchars((string)$d['authority'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
    );

    $sf = isset($d['script_filename']) ? (string)$d['script_filename'] : '';
    $footnote = '';
    if ($sf !== '') {
        $footnote = '<p class="endpoint-helper-footnote"><code>'
            . htmlspecialchars($sf, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '</code></p>';
    }

    return
        '<section class="endpoint-helper" aria-labelledby="endpoint-helper-title">'
        . '<h2 id="endpoint-helper-title">Detected API URLs</h2>'
        . '<p class="endpoint-helper-meta">' . $meta . '</p>'
        . '<p class="endpoint-helper-tip">Under URL rewriting, compare <strong>Effective request URL</strong> '
        . '(what clients call) with <strong>Executing script URL</strong> (the PHP entry file). '
        . 'If HTTPS or host disagree with reality, configure your proxy to set '
        . '<code>X-Forwarded-Proto</code>/<code>X-Forwarded-Host</code>, or expose this page only behind a trusted edge.</p>'
        . implode('', $blocks)
        . $footnote
        . '</section>';
}
