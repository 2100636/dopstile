<?php

class logsItemFile
{
    const LINES_PER_PAGE = 50;

    private $path;

    public function __construct($path)
    {
        if (!self::check($path)) {
            throw new logsInvalidDataException();
        }

        $this->path = $path;
    }

    public function get($params = array())
    {
        /**
         * lines are counted from 0
         * pages are counted from 1
         */

        $mode = isset($params['direction']) ? 'line' : 'page';
        $check = ifset($params['check'], true);
        $full_path = logsHelper::getFullPath($this->path);

        //should be approximately enough for most users
        $lines_per_page = self::LINES_PER_PAGE;
        if (wa()->getEnv() == 'frontend') {
            $lines_per_page += 5;
        }

        $contents = '';
        $error = '';

        try {
            if ($check) {
                if (!logsItemFile::check($full_path)) {
                    logsHelper::redirect();
                }
            } else {
                if (!logsItemFile::check($full_path)) {
                    throw new Exception(_w('File is not accessible.'));
                }
            }

            $file = @fopen($full_path, 'r');

            if (!$file) {
                throw new Exception(_w('File is not accessible'));
            }

            $first_line = null;
            $last_line = null;
            $page_count = null;
            $cached_contents = '';

            if ($mode == 'page') {
                //will start from line = 0
                $line_id = -1;
                $page_count = 1;
                $line = '';
                while (!feof($file)) {
                    $line = fgets($file, 4096);

                    if ($line === false) {
                        break;
                    }

                    $line_id++;
                    $current_line = $line_id;
                    $current_page = intval(floor(($line_id + 1) / $lines_per_page)) + 1;

                    if (isset($params['page'])) {
                        //show specified page

                        if ($current_page == $params['page']) {
                            //continue reading specified page

                            $contents .= $line;

                            if ($first_line === null) {
                                $first_line = $current_line;
                            }
                            $last_line = $current_line;
                        } else {
                            //cache last page contents to skip last page if it's empty

                            if ($current_page > $params['page']) {
                                if ($page_count == $current_page) {
                                    //continue caching current page
                                    $cached_contents .= $line;
                                } else {
                                    //start caching next page
                                    $cached_contents = $line;
                                }
                            }
                        }
                    } else {
                        //show last page in file

                        if ($page_count == $current_page) {
                            //continue reading current, or 1st, page

                            $contents .= $line;
                            if ($first_line === null) { //if first file page
                                $first_line = $current_line;
                            }

                            $last_line = $current_line;
                        } else {
                            //start reading next (2+) page

                            //cache previous page to be displayed if current (last) page is empty
                            $cached_contents = $contents;
                            $contents = $line;
                            $first_line = $current_line;
                        }
                    }
                    $page_count = $current_page;
                }

                //empty last page check
                //do not check empty preceding pages
                if (isset($params['page'])) { //specified page
                    /**
                     * $cached_contents: last page contents
                     */
                    if (!strlen(trim($cached_contents))) {
                        if ($page_count > 1) {
                            $page_count--;
                            $first_line = max(0, $first_line - self::LINES_PER_PAGE);
                        }
                    }
                } else { //last page
                    /**
                     * $cached_contents: last but one page contents
                     */

                    if (!strlen(trim($contents))) {
                        $contents = $cached_contents;
                        if ($page_count > 1) {
                            $page_count--;
                            $first_line = max(0, $first_line - self::LINES_PER_PAGE);
                        }
                    }
                }

                $is_file_end = empty($params['page']);
            } else {
                // 'line' mode

                if ($params['direction'] == 'previous') {
                    $first_line = max($params['first_line'] - $lines_per_page, 0);
                    $last_line = max($params['first_line'] - 1, 0);
                } else { // 'next'
                    $first_line = $params['last_line'] + 1;
                    $last_line = $params['last_line'] + $lines_per_page;
                };

                //will start from line = 0
                $line_id = -1;

                while (!feof($file)) {
                    $line_id++;
                    $line = fgets($file, 4096);
                    if ($line_id >= $first_line && $line_id <= $last_line) {
                        $contents .= $line;
                        $last_reading_position = ftell($file);
                    } else {
                        if ($line_id > $last_line) {
                            $line_id--;
                            break;
                        }
                    }
                }
                $last_line = $line_id;

                if (!isset($last_reading_position)) {
                    $last_reading_position = ftell($file);
                }

                fseek($file, -1, SEEK_END);
                fgets($file, 2);
                //have just read last byte in file
                $is_file_end = ftell($file) == $last_reading_position;
            }

            fclose($file);
        } catch (Exception $e) {
            $error = $e->getMessage();

            if ($file) {
                fclose($file);
            }
        }

        $contents = logsHelper::hideData($contents);

        //very dark magic: correctly show empty lines in different modes
        if (isset($is_file_end)) {
            $last_eol = $is_file_end ? '' : intval(!$is_file_end && substr($contents, -2, 2) === PHP_EOL.PHP_EOL);
            $file_end_eol = $is_file_end ? intval(substr($contents, -2, 1) === PHP_EOL) : '';
        }

        if (strlen($contents)) {
            if (!empty($params['page'])) {
                //show empty line at the beginning of selected page
                if (substr($contents, 0, 1) === PHP_EOL) {
                    $contents = PHP_EOL.$contents;
                }
            } elseif (!empty($params['direction'])) {
                if ($params['direction'] == 'previous') {
                    //
                } else {
                    if (strlen($params['file_end_eol'])) {
                        $contents = PHP_EOL.$contents;
                    } else {
                        if (!$params['last_eol']) {
                            $contents = PHP_EOL.$contents;
                        }

                        if (substr($contents, 0, 1) !== PHP_EOL) {
                            $contents = PHP_EOL.$contents;
                        }
                    }
                }
            } else {
                //show empty line at the beginning of last page
                if (substr($contents, 0, 1) === PHP_EOL) {
                    $contents = PHP_EOL.$contents;
                }
            }
        }

        return array(
            'contents' => $contents,
            'page_count' => ifset($page_count),
            'path' => $this->path,
            'error' => $error,
            'first_line' => ifset($first_line, 0),
            'last_line' => ifset($last_line, 0),
            'last_eol' => ifset($last_eol),
            'file_end_eol' => ifset($file_end_eol),
        );
    }

    public function download()
    {
        try {
            $full_path = logsHelper::getFullPath($this->path);

            if (!self::check($full_path)) {
                throw new Exception();
            }

            $name = basename($full_path);

            if (array_filter(logsHelper::getHideSetting(null, true))) {
                $dirname = str_replace(basename($this->path), '', $this->path);
                $temp_dir = wa()->getTempPath('download/hidedata/'.wa()->getUser()->getId().(strlen($dirname) ? DIRECTORY_SEPARATOR.$dirname: ''));
                $temp_file = $temp_dir.DIRECTORY_SEPARATOR.basename($this->path);

                $from = fopen($full_path, 'r');
                $to = fopen($temp_file, 'w');

                $stream_filter_name = 'webasyst_logs_hide_data';
                stream_filter_register($stream_filter_name, 'logsStreamFilterHideData');
                $stream_filter = stream_filter_append($from, $stream_filter_name, STREAM_FILTER_READ);
                stream_copy_to_stream($from, $to);
                stream_filter_remove($stream_filter);

                fclose($from);
                fclose($to);

                waFiles::readFile($temp_file, $name);
                waFiles::delete($temp_file);
            } else {
                waFiles::readFile($full_path, $name);
            }
        } catch (Exception $e) {
            if (wa()->getEnv() == 'backend') {
                logsHelper::redirect();
            } else {
                throw new Exception();
            }
        }
    }

    public static function check($path, $exists = true)
    {
        $success = true;

        $path = logsHelper::normalizePath($path, true);

        if (!strlen($path)) {
            $success = false;
        }

        $logs_path = wa()->getConfig()->getPath('log');
        if (strpos($path, $logs_path) !== 0) {
            $path = $logs_path.DIRECTORY_SEPARATOR.$path;
        }

        if ($exists && !file_exists($path)) {
            $success = false;
        }

        if ($exists && $success && strpos(realpath($path), wa()->getConfig()->getPath('log')) !== 0) {
            $success = false;
        }

        if ($success && basename($path) == '.htaccess') {
            $success = false;
        }

        return $success;
    }
}
