<?php
class shopYmlPluginFilemngScanController extends waJsonController {
    public function execute(){
        $root_path  = wa()->getConfig()->getRootPath() . '/wa-data';
        $profile_id = waRequest::post('profile_id', 0, waRequest::TYPE_INT);

        $view = wa()->getView();

        $dir  = $root_path;
        if ($get_path = waRequest::post('root_path')){
            if ( $get_path != $root_path ){
                $back_path = realpath($get_path . '/../');
                $view->assign('back_path', $back_path);
            }

            $dir = $get_path;
        } else {
            $settings = shopYmlHelper::getProfileConfig($profile_id);

            if ( !empty($settings['server.file']) && file_exists($settings['server.file']) ){
                $pinfo = pathinfo($settings['server.file']);
                $dir   = $pinfo['dirname'];

                $view->assign('selected_file', $pinfo);
            }
        }

        if ( strlen($dir) < strlen($root_path) ){
            $dir = $root_path;
        } elseif (strlen($dir) > strlen($root_path)){
            $back_path = realpath($dir . '/../');
            $view->assign('back_path', $back_path);
        }

        $response = $this->scan($dir);

        $r = array(
            'root_path' => $root_path,
            "name" => "files",
            "type" => "folder",
            "path" => $dir,
            "items" => $response
        );

        $view->assign($r);

        $template = wa()->getAppPath('plugins/yml/templates/actions/filemng/', 'shop') . 'FileManager.html';

        $this->response['html'] = $view->fetch($template);
    }

    public function scan($dir){
        $files = array(
            'dir'  => array(),
            'file' => array()
        );

        if (file_exists($dir)) {
            foreach(scandir($dir) as $f) {

                if(!$f || $f[0] == '.') {
                    continue;
                }

                if ( is_dir($dir . '/' . $f)) {
                    $files['dir'][] = array(
                        "name" => $f,
                        "type" => "folder",
                        "path" => $dir . '/' . $f
                    );
                } else {

                    $ext = pathinfo($dir . '/' . $f, PATHINFO_EXTENSION);
                    if ( !preg_match('/[a-zA-Z]+ml/', $ext) && !($ext === 'zip') ){
                        continue;
                    }

                    $files['file'][] = array(
                        "name" => $f,
                        "type" => "file",
                        "path" => $dir . '/' . $f,
                        "size" => filesize($dir . '/' . $f)
                    );
                }
            }
        }

        return $files;
    }
}