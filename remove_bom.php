<?php
$files = glob("*.php");
foreach ($files as $file) {
    if (is_dir($file)) continue;
    $content = file_get_contents($file);
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "Found BOM in $file. Removing...\n";
        file_put_contents($file, substr($content, 3));
    }
}
echo "BOM Check Complete.\n";
