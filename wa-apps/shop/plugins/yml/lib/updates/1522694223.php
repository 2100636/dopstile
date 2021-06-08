<?php
try {
    $path = wa()->getAppPath('plugins/yml/lib/config/', 'shop') . 'db.php';
    if ( file_exists($path) ){
        waFiles::delete($path, true);
    }
} catch ( waException $e){
    waLog::log($e->getMessage() , 'plugins/yml.log');
}