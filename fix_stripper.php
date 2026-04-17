<?php
$files = glob("*.php");
$log = "";
foreach ($files as $file) {
    if (is_dir($file)) continue;
    $content = file_get_contents($file);
    $original_len = strlen($content);
    
    // Remove BOM
    $content = preg_replace('/^\\xEF\\xBB\\xBF/', '', $content);
    // Remove leading whitespace before <?php
    $content = ltrim($content);
    
    if (strlen($content) !== $original_len) {
        file_put_contents($file, $content);
        $log .= "Fixed $file (removed " . ($original_len - strlen($content)) . " bytes)\n";
    }
}
if (empty($log)) $log = "All files are clean.";
file_put_contents("bominfo.txt", $log);
echo "Done";
