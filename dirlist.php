<?php
// ignore this stuff
// globbing allowed
$ignore = array('.', '..', 'robots.txt', '.htaccess', basename(__FILE__), '.dirlist.php');

// number of columns
$columns = 4;

// display directories?
$display_directories = false;

// display others?
$display_others = true;

// max image width
// set this to a non-integer value to disable
$max_image_width = 640;

// file types
$file_types = array(
    'images' => array('*.bmp', '*.gif', '*.png', '*.jpg', '*.jpeg'),
    'audio' => array('*.mp3', '*.flac'),
);

if (file_exists('.dirlist.php')) {
    include '.dirlist.php';
}

function hfilesize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    $precision = ($pow <= 1) ? 0 : $precision;
    return round($bytes, $precision) . $units[$pow];
}

function get_files($dir, $ignore, $file_types, $display_directories, $display_others) {
    $files = array();
    $others = array();

    if ($handle = opendir($dir)) {
        while (($file = readdir($handle)) !== false) {
            $ignored = false;
            // Skip ignored files
            foreach ($ignore as $i) {
                if (fnmatch($i, $file)) {
                    $ignored = true;
                    break;
                }
            }

            if ($ignored) {
                continue;
            }

            $found = false;
            foreach ($file_types as $name => $globs) {
                if (is_dir($file)) {
                    if ($display_directories) {
                        $files['directories'][$file] = null;
                    } else {
                        $found = true;
                        continue;
                    }
                }

                foreach ($globs as $glob) {
                    if (fnmatch($glob, $file)) {
                        $files[$name][$file] = hfilesize(filesize($file));
                        $found = true;
                        continue;
                    }
                }
            }

            if (!$found) {
                $others[$file] = hfilesize(filesize($file));
            }
        }
    }

    foreach ($files as $key => $value) {
        ksort($files[$key]);
    }

    if ($display_others) {
        ksort($others);
        $files['other'] = $others;
    }

    return $files;
}

$files = get_files(getcwd(), $ignore, $file_types, $display_directories, $display_others);
if (isset($_GET['img'])) {
    if (array_key_exists($_GET['img'], $files['images'])) {
        $current = $_GET['img'];
        $title = $current;
    }
} else {
    if (count($files['images']) > 0) {
        $current = key($files['images']);
        $title = dirname($_SERVER['PHP_SELF']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?php echo $title; ?></title>
        <style type="text/css">
         *{margin:0;padding:0;}
         body{background:white;color:black;font:11px/16px Verdana,"Bitstream Vera Sans",sans-serif;padding:30px;border-top:10px solid black;}
         h1,h2,h3{font-size:inherit;}
         h1,h3{background:black;color:white;margin:-30px -30px 30px -30px;padding:10px;}
         h2{font-size:14px;}
         h3{display:inline;padding:10px 10px 10px 35px;}
         a:link,a:visited{color:black;}
         a:hover,a:active,a:focus{color:#f60;}
         table{margin:10px 0 30px;}
         td{vertical-align:top;}
         td.light{color:#999;}
         img{display:block;margin:30px 0 50px;<?php if(is_int($max_image_width)): ?>max-width:<?php echo $max_image_width; ?>px;<?php endif; ?>}
        </style>
</head>
<body>
    <?php if (!is_null($current)): ?><h2><?php echo $current ?></h2><img src="<?php echo $current ?>"><?php endif;?>
    <?php foreach ($files as $group => $groupfiles): ?>
        <?php if(count($groupfiles) == 0) continue; ?>
        <?php $open = false; $pos = 1; ?>
        <?php $filecount = count($groupfiles); ?>
        <?php $files_per_column = ceil($filecount / $columns); ?>
        <?php if ($filecount <= $columns) $files_per_column = $filecount; ?>
        <h3><?php echo $group; ?></h3>
        <table><tr>
        <?php foreach($groupfiles as $name => $size): ?>
            <?php $is_img = $group == 'images'; ?>
            <?php if (!$open): $open = true; ?><td><table><?php endif; ?>
            <?php if ($pos > $files_per_column): $open = true; $pos = 1; ?></table></td><td><table><?php endif; ?>
            <tr><td><a href="<?php if($is_img): echo $_SERVER['PHP_SELF'] . '?img=' ?><?php endif; ?><?php echo $name ?>"><?php echo $name; ?><?php if($is_img): ?><a href="<?php echo $_SERVER['PHP_SELF'] ?>?img=<?php echo $name ?>"></a><?php endif; ?></td><td class="light"><?php if(!is_null($size)): ?>(<?php echo $size; ?>)<?php endif; ?></td></tr>
            <?php $pos++; ?>
        <?php endforeach; ?>
        <?php if ($open): ?></td></table><?php endif; ?>
        </tr></table>
    <?php endforeach; ?>
</body>
</html>
