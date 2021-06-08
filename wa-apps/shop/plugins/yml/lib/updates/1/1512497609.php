<?php
try {    
    $path  = wa()->getAppPath('plugins/yml/lib/classes/', 'shop') . 'shopYmlDownloader.class.php';
    $path2 = wa()->getAppPath('plugins/yml/lib/config/', 'shop') . 'update_fields.php';
    
    
    if ( file_exists($path) ){
        waFiles::delete($path, true);
    }
    
    if ( file_exists($path2) ){
        waFiles::delete($path2, true);
    }
    
} catch ( waException $e){
    waLog::log($e->getMessage() , 'plugins/yml.log');
}