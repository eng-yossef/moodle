<?php
require_once('../../config.php');
require_login();

$fs = get_file_storage();

// List all files in context 24, mod_resource, content area.
$files = $fs->get_area_files(24, 'mod_resource', 'content', false, 'filename', false);
echo "<h3>Files in context 24, mod_resource, content:</h3>";
if (empty($files)) {
    echo "No files found.<br>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Filename</th><th>Filepath</th><th>Itemid</th><th>Pathnamehash</th></tr>";
    foreach ($files as $file) {
        echo "<tr>";
        echo "<td>{$file->get_id()}</td>";
        echo "<td>{$file->get_filename()}</td>";
        echo "<td>{$file->get_filepath()}</td>";
        echo "<td>{$file->get_itemid()}</td>";
        echo "<td>{$file->get_pathnamehash()}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Now try to get the specific file using the URL path.
echo "<hr>";
$original = '/pluginfile.php/24/mod_resource/content/2/GENERATIVE  AGENTIC AI SYLLABUS.pdf';
echo "Attempting to find: " . htmlspecialchars($original) . "<br>";

$path = ltrim($original, '/');
$parts = explode('/', $path);
array_shift($parts); // remove 'pluginfile.php'

$contextid = (int)array_shift($parts);
$component = array_shift($parts);
$filearea  = array_shift($parts);
$itemid    = (int)array_shift($parts);
$filename = array_pop($parts);
$filepath = empty($parts) ? '/' : '/' . implode('/', $parts) . '/';
if ($filepath !== '/') {
    $filepath = '/' . ltrim($filepath, '/') . '/';
}

echo "Parsed parameters:<br>";
echo "contextid = $contextid<br>";
echo "component = $component<br>";
echo "filearea = $filearea<br>";
echo "itemid = $itemid<br>";
echo "filepath = $filepath<br>";
echo "filename = $filename<br>";

$file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
if ($file) {
    echo "<span style='color:green'>✅ File found via get_file()! Pathnamehash: " . $file->get_pathnamehash() . "</span>";
} else {
    echo "<span style='color:red'>❌ File NOT found via get_file()</span>";
}
?>