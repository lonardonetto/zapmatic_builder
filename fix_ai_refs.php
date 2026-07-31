<?php
// 1. Caption/Views/block.php - remove Ai_content_generator widget reference
$file1 = '/www/wwwroot/app_zapmatic_app/inc/core/Caption/Views/block.php';
if (file_exists($file1)) {
    $c1 = file_get_contents($file1);
    $c1 = preg_replace('/\s*<\?php echo view_cell\(\\\\Core\\\\Ai_content_generator.*?\) \?>\s*/s', '', $c1);
    file_put_contents($file1, $c1);
    echo "Fixed Caption/Views/block.php\n";
}

// 2. File_manager/Views/mini.php - remove Openai image_widget reference
$file2 = '/www/wwwroot/app_zapmatic_app/inc/core/File_manager/Views/mini.php';
if (file_exists($file2)) {
    $c2 = file_get_contents($file2);
    $c2 = preg_replace('/\s*<\?php echo view_cell\(\\\\Core\\\\Openai.*?\) \?>\s*/s', '', $c2);
    file_put_contents($file2, $c2);
    echo "Fixed File_manager/Views/mini.php\n";
}

// 3. File_manager/Views/widget.php - remove Openai image_widget reference
$file3 = '/www/wwwroot/app_zapmatic_app/inc/core/File_manager/Views/widget.php';
if (file_exists($file3)) {
    $c3 = file_get_contents($file3);
    $c3 = preg_replace('/\s*<\?php echo view_cell\(\\\\Core\\\\Openai.*?\) \?>\s*/s', '', $c3);
    file_put_contents($file3, $c3);
    echo "Fixed File_manager/Views/widget.php\n";
}
echo "All references fixed.\n";
