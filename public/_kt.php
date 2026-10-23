<?php
// Tep kiem tra tam thoi giam thieu CVE-2026-109618/109619 (PHP2Shell). XOA sau khi kiem xong.
header('Content-Type: text/plain; charset=utf-8');
foreach (['session.upload_progress.enabled', 'memory_limit', 'post_max_size', 'upload_max_filesize', 'display_errors', 'expose_php'] as $k) {
    echo $k, ' = ', var_export(ini_get($k), true), "\n";
}
