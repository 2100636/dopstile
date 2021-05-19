<?php
/*
 * LICENSE GNU https://sourceforge.net/projects/snoopy/
 *
 * */
class shopAdvancedparamsPluginDownloadFile
{
    // Разрешенные мим типы и расширения
    protected $access_mime_types = array(
        'txt' => 'text/plain',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',

        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'mov' => 'video/quicktime',
        'qt' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
    );

    /*
        * Загружает файл по ссылке
        * return false|array data
        * */
    public function downloadFile($url) {
        $Curl = new shopAdvancedparamsPluginCurl();
        $Curl->agent = "Mozilla/5.0 (Windows; U; Windows NT 6.1; uk; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13 Some plugins";
        $domain = $Curl->cutDomain($url);
        $Curl->referer = "http://$domain/index.php";
        $Curl->rawheaders["Host"] = $domain;
        $Curl->maxredirs = 2;
        $Curl->fetch($url);
        $file_content = $Curl->results;
        // Если есть контент файла
        if(!empty($file_content)) {
            if(is_writeable(sys_get_temp_dir())) {
                $tmp_dir = sys_get_temp_dir();
            } elseif(is_writeable(ini_get("upload_tmp_dir"))) {
                $tmp_dir = ini_get("upload_tmp_dir");
            } else {
                $tmp_dir =  wa()->getDataPath('advancedparams', true, 'shop');
            }
            $tmpfname = $tmp_dir.DIRECTORY_SEPARATOR.'advancedparams.uploadfile.temp';
            $this->file_put_contents($tmpfname, '');
            if(file_exists($tmpfname)) {

                if($this->file_put_contents($tmpfname, $file_content)) {
                    $file_data = array();
                    $file_data['mime_type'] = 'none';
                    $url_arr = parse_url($url);
                    $file_data['name'] =  basename($url_arr['path']);
                    foreach($Curl->headers as $header) {
                        if(preg_match('/^content-type:[\s]+(.+)/i', $header, $match)) {
                            $file_data['mime_type'] = $match[1];
                        }
                    }
                    $file_data['tmp_filename'] = $tmpfname;
                    return $this->getFileData($file_data);
                } else {
                    throw new waException('Не удалось записать данные во временный файл ('.$tmpfname.')!');
                   
                }
            } else {
                throw new waException('Не удалось создать временный файл: '.$tmpfname.'!');
            }
        } else {
            throw new waException('Не удалось закачать файл по ссылке ('.htmlspecialchars($url).')!');
        }
    }
    // Подготавливает массив данных файла для waRequestFile
    protected function getFileData($file_data) {
        $data = array(
            'name' => '',
            'type' => 'file',
            'size' => filesize($file_data['tmp_filename']),
            'tmp_name' =>  $file_data['tmp_filename'],
            'error' => UPLOAD_ERR_OK,
        );
        // ПРоверяем имя
        $info = pathinfo($file_data['name']);
        $ext = false;
        if(isset($info['extension'])) {
            $ext = $info['extension'];
        }
        if(!array_key_exists($ext, $this->access_mime_types) ) {
            if (in_array($file_data['mime_type'], $this->access_mime_types)) {
                $ext = array_search($file_data['mime_type'], $this->access_mime_types);
                $data['name'] = pathinfo($file_data['name'], PATHINFO_FILENAME).'.'.$ext;
            } else {
                $this->errors[] = 'Файл с таким расширением не поддерживается!';
                return false;
            }
        } else {
            $data['name'] = $file_data['name'];
        }
        return $data;
    }
    /*
    * Записывает строку в файл
    * return bool
    * */
    private function file_put_contents($filename, $content) {
        $write = false;
        if(function_exists('file_put_contents')) {
            $write = file_put_contents($filename, $content);
        } else {
            $fp = fopen($filename, "w");
            if($fp) {
                $write = fwrite($fp, $content);
                fclose($fp);
            }
        }
        return $write;
    }
}