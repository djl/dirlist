<?php

//
// And Isaac begat Jacob
// http://github.com/xvzf/jacob
//

$parts = explode('/', $_SERVER['PHP_SELF']);
$count = count($parts) - 1;
$self = $parts[$count];

// ignore this stuff
$ignore = array('.', '..', '.htaccess', $self);

// known image extensions, case-insensitive
$img_extensions = array('bmp', 'gif', 'png', 'jpg', 'jpeg');

// number of columns
$columns = 4;

// display directories?
$display_directories = true;

function hfilesize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    $precision = ($pow <= 1) ? 0 : $precision;
    return round($bytes, $precision) . $units[$pow];
}

function get_files($dir) {
    global $ignore;
    global $img_extensions;
    global $display_directories;

    $files = array('images' => array(), 'other' => array(), 'directories' => array());
    $pattern = sprintf("/\.%s$/i", implode("|", $img_extensions));

    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if (in_array($file, $ignore)) {
                continue;
            }
            if (is_dir($file) && $display_directories) {
                $files['directories'][$file] = null;
                continue;
            }

            $pile = preg_match($pattern, $file) ? "images" : "other";
            $files[$pile][$file] = hfilesize(filesize($file));
            ksort($files[$pile]);
        }
    }
    return $files;
}

$files = get_files(getcwd());

$current = null;
if (isset($_GET['img'])) {
    if (array_key_exists($_GET['img'], $files['images'])) {
        $current = $_GET['img'];
    }
} else {
    if (count($files['images']) > 0) {
        reset($files['images']);
        $current = key($files['images']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browsing <?php echo dirname($_SERVER['PHP_SELF']) ?></title>
    <style type="text/css">
        *{margin:0;padding:0;}
        html{color:black;background:white;}
        body{font: normal normal normal 10px/17px monaco, 'Deja Vu Sans Mono', 'andale mono', 'courier new', monospace;padding:30px;}
        h1,h2,p,ul{font-size:inherit;font-weight:normal;margin-bottom:1em;}
        h1{background:black;color:white;margin:-30px -30px 30px -30px;padding:10px;}
        h2{background:#ddd;display:inline;padding:5px;}
        a:link,a:visited{color:black;}
        a:hover,a:active,a:focus{color:#f60;}
        ul{margin:3em 0 0 -1em;}
        ul li{line-height:2em;list-style-type: none;}
        ul li:before{content:"* ";}
        table{margin:10px 10px 30px;}
        td{vertical-align:top;}
        td.light{color:#999;}
        img{display:block;margin-bottom:50px;}
    </style>
</head>
<body>
    <h1>Browsing <?php echo dirname($_SERVER['PHP_SELF']) ?></h1>
    <?php if (!is_null($current)): ?><img src="<?php echo $current ?>"><?php endif;?>
    <?php foreach ($files as $group => $groupfiles): ?>
        <?php if (count($groupfiles) == 0) { continue; } ?>
        <?php $open = false; ?>
        <?php $pos = 1; ?>
        <?php $i = 0; ?>
        <?php $filecount = count($groupfiles); ?>
        <?php $files_per_column = ceil($filecount / $columns); ?>
        <?php if ($filecount <= $columns) { $files_per_column = $filecount; }?>
        <h2><?php echo $group; ?></h2>
        <table><tr>
        <?php foreach($groupfiles as $name => $size): ?>
            <?php if (!$open): $open = true; ?><td><table><?php endif; ?>
            <?php if ($pos > $files_per_column): $open = true; $pos = 1; ?></table></td><td><table><?php endif; ?>
            <tr><td><a href="<?php if(array_key_exists($name, $files['images'])): echo $_SERVER['PHP_SELF'] . '?img=' ?><?php endif; ?><?php echo $name ?>"><?php echo $name; ?><?php if(array_key_exists($name, $files['images'])): ?><a href="<?php echo $_SERVER['PHP_SELF'] ?>?img=<?php echo $name ?>"></a><?php endif; ?></td><td class="light"><?php if(!is_null($size)): ?>(<?php echo $size; ?>)<?php endif; ?></td></tr>
            <?php $pos++; ?>
            <?php $i++; ?>
        <?php endforeach; ?>
        <?php if ($open): ?></td></table><?php endif; ?>
        </tr>
        </table>
    <?php endforeach; ?>
</body>
</html>
