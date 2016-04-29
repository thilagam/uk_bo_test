<?php
/**
 * PositiontoolController
 *
 * @author
 * @version
 */

require_once 'Zend/Controller/Action.php';

class SeotoolController extends Ep_Controller_Action {
    private $text_admin;
    var $type;
    var $format;
    var $option;
    var $domain;
    var $competitor;
    var $client;
    var $title;
    var $days;
    var $end_date;
    var $frequency_option;
    var $output_type;
    var $site_id;
    var $limit;
    var $contract;
    var $from_date;
    var $to_date;
    var $ssh2_server;
    var $ssh2_user_name;
    var $ssh2_user_pass;
    var $gsuggest_excel_array;
    var $cron_run_time;
    var $cron_email;
    var $os;
    var $sheet_count;

    public function init() {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->sid = session_id();

        /*server details**/
        $this->ssh2_server = SSH2_SERVER;
        $this->ssh2_user_name = SSH2_USER;
        $this->ssh2_user_pass = SSH2_PWD;
        $this->seo_upload_files = Zend_Registry::get('seo_upload_files');
        $this->os = $this->getOS($_SERVER['HTTP_USER_AGENT']);
    }

    public function posssh2uploadAction() {
        
        $pos_params = $this->_request->getParams();
        if (isset($pos_params['submit'])) {
            // response hash
            $response = $this->responseMsg('', 0, 1, '', 0);
            $word_type = $pos_params['word_type'];

            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;

            $this->type = $pos_params['type'];
            $this->option = $pos_params['option'];
            $this->domain = $pos_params['domain_type'];
            $this->competitor = $pos_params['comp_type'];
            $this->client = $pos_params['client'];
            $this->title = $pos_params['title'];
            $this->days = ((sizeof($pos_params['day'])>0) ? implode("|", $pos_params['day']) : '');
            $this->end_date = $pos_params['enddate'];
            $this->frequency_option = $pos_params['frequency'];
            $this->output_type = $pos_params['op_type'];
            $this->site_id = $pos_params['site'];
            $this->limit = $pos_params['limit'];

            if ($pos_params['posSchedule'] == 1) :
                $this->cron_run_time = trim($pos_params['scheduleDate']);
                $this->cron_email = trim($pos_params['scheduleEmail']);

                if (empty($this->cron_run_time) || empty($this->cron_email))
                    $response = $this->responseMsg(0, 21);
                elseif (empty($this->client) || empty($this->title))
                    $response = $this->responseMsg(0, 22);

                if ($response['type'] == 'error') :
                    print json_encode($response);
                    exit ;
                endif ;
            endif ;

            if ($word_type == 2) {
                $kw_text = trim($pos_params['kw']);
                if (($this->os == 'Windows'))
                    $kw_text = utf8_decode($kw_text);

                if ($kw_text && $this->type) {
                    $kw_text1 = explode("\n", $kw_text);
                    $csv_file_name = "csv_" . time() . ".csv";
                    $srcFile = SEO_UPLOAD_POS . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, str_replace("\'", "'", $kw_text));
                    fclose($fp);

                    $frequency = $this->checkFrequency();
                    if ($frequency == 'process') {
                        if ($pos_params['posSchedule'] == 1)
                            $response = $this->posscheduleuploadAndProcess($srcFile, $csv_file_name);
                        else
                            $response = $this->posuploadAndProcess($srcFile, $csv_file_name);
                    } else {
                        $response = $this->responseMsg(0, 0, $word_type, $frequency);
                    }
                    $response['word_type'] = $word_type;
                } else {
                    if (!$kw_text)
                        $response = $this->responseMsg(0, 3, $word_type);
                    elseif (!$this->type)
                        $response = $this->responseMsg(0, 2, $word_type);
                }
            } else if ($word_type == 1) {
                if ((($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) && $this->type) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];

                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_POS . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['keyword_file']['name']);
                    }

                    $frequency = $this->checkFrequency();

                    if ($frequency == 'process') {
                        if ($pos_params['posSchedule'] == 1)
                            $response = $this->posscheduleuploadAndProcess($srcFile, $u_file_name);
                        else
                            $response = $this->posuploadAndProcess($srcFile, $u_file_name);
                    } else {
                        $response = $this->responseMsg(0, 0, $word_type, $frequency);
                    }
                    $response['word_type'] = $word_type;
                } else {
                    if (!$_FILES['keyword_file']['tmp_name'])
                        $response = $this->responseMsg(0, 1, $word_type);
                    elseif (!$this->type)
                        $response = $this->responseMsg(0, 2, $word_type);
                }
            }
            print json_encode($response);
            exit ;
        }
    }

    public function posssh2upload235Action() {
        $pos_params = $this->_request->getParams();
        if (isset($pos_params['submit'])) {

            $response = array('type' => '', 'message' => '');
            
            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;

            $word_type = $pos_params['word_type'];
            $this->title = trim($pos_params['title']);
            $this->output_type = $pos_params['op_type'];
            $this->site_id = $pos_params['site'];
            $this->type = $pos_params['type'];
            $this->format = 1;
            $this->limit = $pos_params['limit'];

            if ($word_type == 2) {
                $url_text = trim($pos_params['url_text']);
                if ($this->type == 4 || $this->type == 5)
                    $comp_url_text = trim($pos_params['comp_url_text']);

                $kw_text = trim($pos_params['kw']);

                if ($this->os == 'Windows') {
                    $kw_text = utf8_decode($kw_text);
                    $url_text = utf8_decode($url_text);
                    if ($this->type == 4 || $this->type == 5)
                        $comp_url_text = utf8_decode($comp_url_text);
                }

                if ($kw_text && $url_text) {
                    $kws = explode("\n", $kw_text);
                    $kwtext = $url_text . ($comp_url_text ? (';' . $comp_url_text) : '');
                    foreach ($kws as $kw) {
                        $kwtext .= ';' . trim($kw);
                    }

                    $csv_file_name = "textArea_" . str_replace(' ', '_', $this->title) . time() . ".csv";
                    $srcFile = SEO_UPLOAD_POS . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kwtext);
                    fclose($fp);

                    $frequency = $this->checkFrequency();

                    if ($frequency == 'process') {
                        $response = $this->posuploadAndProcess($srcFile, $csv_file_name);
                    } else {
                        $response = $this->responseMsg(0, 0, $word_type, $frequency);
                    }
                    $response['word_type'] = $word_type;
                } else {
                    $response = $this->responseMsg(0, 3, $word_type);
                }
            } else if ($word_type == 1) {
                if ((($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) && $this->type) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];

                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_POS . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['keyword_file']['name']);
                    }

                    $csvArr = array();
                    foreach ($this->getCSV($srcFile) as $key => $val) :
                        array_push($csvArr, $val[0]);
                    endforeach;

                    $url_text = trim($csvArr[0]);
                    unset($csvArr[0]);

                    if ($this->type == 4 || $this->type == 5) :
                        $comp_url_text = trim($csvArr[1]);
                        unset($csvArr[1]);
                    endif ;

                    $kws = array_unique($csvArr);

                    $kwtext = $url_text . ($comp_url_text ? (';' . $comp_url_text) : '');
                    foreach ($kws as $kw) {
                        $kwtext .= ';' . trim($kw);
                    }

                    if ($this->os == 'Windows') {
                        $kwtext = utf8_decode($kwtext);
                        $url_text = utf8_decode($url_text);
                        if ($this->type == 4 || $this->type == 5)
                            $comp_url_text = utf8_decode($comp_url_text);
                    }

                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kwtext);
                    fclose($fp);

                    $frequency = $this->checkFrequency();

                    if ($frequency == 'process') {
                        $response = $this->posuploadAndProcess($srcFile, $u_file_name);
                    } else {
                        $response = $this->responseMsg(0, 0, $word_type, $frequency);
                    }
                    $response['word_type'] = $word_type;
                } else {
                    if (!$_FILES['keyword_file']['tmp_name'])
                        $response = $this->responseMsg(0, 1, $word_type);
                    elseif (!$this->type)
                        $response = $this->responseMsg(0, 2, $word_type);
                }
            }
            print json_encode($response);
            exit ;
        }
    }

    public function positionAction() {
        $pos_params = $this->_request->getParams();
        
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($pos_params['file']) && isset($pos_params['ext']))
            $this->posdownloadFile($pos_params['file'], $pos_params['ext']);

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($pos_params['file']) && isset($pos_params['ext'])) {
            $filename = $pos_params['file'] . "." . $pos_params['ext'];
            $path_file = SEO_DOWNLOAD_POS . $filename;

            if (file_exists($path_file)) {
                $data = $this->getCSV($path_file);
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'spl') ;
                /*$table = SEO_TBL_TG;

                $i = 0;
                foreach ($data as $row) {
                    $table .= '<tr>';
                    foreach ($row as $td) {
                        if ($this->os != 'Windows') {
                            if ($i == 0)
                                $table .= '<th>' . utf8_decode($td) . '</th>';
                            else
                                $table .= '<td>' . utf8_decode($td) . '</td>';
                        } else {
                            if ($i == 0)
                                $table .= '<th>' . ($td) . '</th>';
                            else
                                $table .= '<td>' . ($td) . '</td>';
                        }
                    }
                    $table .= '</tr>';
                    $i++;
                }

                $table .= SEO_TBL_TG_;*/
            }
//exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $pos_params['word_type'];
            $this->render("seotool_view");
        } else {

            if (@$pos_params['class'])
                $this->_view->class = $pos_params['class'];

            $_POST['word_type'] = 1;
            $this->_view->word_type = $pos_params['word_type'];

            if (@$msg)
                $this->_view->msg = $msg;
            $client_info_obj = new Ep_User_User();
            $client_info = $client_info_obj->GetclientList();
            $client_list = array();

            for ($c = 0; $c < count($client_info); $c++) {
                $client_list[$c]['identifier'] = $client_info[$c]['identifier'];

                $name = $client_info[$c]['email'];
                $nameArr = array($client_info[$c]['company_name'], $client_info[$c]['first_name'], $client_info[$c]['last_name']);
                $nameArr = array_filter($nameArr);
                if (count($nameArr) > 0)
                    $name .= "(" . implode(", ", $nameArr) . ")";

                $client_list[$c]['name'] = strtoupper($name);
            }
            asort($client_list);
            $this->_view->client_list = $client_list;
            $this->render("seotool_position");
        }
    }

    public function position2Action() {
        $pos_params = $this->_request->getParams();
        
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($pos_params['file']) && isset($pos_params['ext']))
            $this->posdownloadFile($pos_params['file'], $pos_params['ext']);

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($pos_params['file']) && isset($pos_params['ext'])) {
            $filename = $pos_params['file'] . "." . $pos_params['ext'];
            $path_file = SEO_DOWNLOAD_POS . $filename;

            if (file_exists($path_file)) {
                $data = $this->getCSV($path_file);
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'spl') ;
                /*$table = SEO_TBL_TG;

                $i = 0;
                foreach ($data as $row) {
                    $table .= '<tr>';
                    foreach ($row as $td) {
                        if ($this->os != 'Windows')
                            $td = utf8_decode($td);
                        else
                            $td = $td;

                        if ($pos_params['type'] == 2 || $pos_params['type'] == 3) :
                            $colspan = ($pos_params['type'] == 2) ? 'colspan="2"' : 'colspan="4"';
                            $table .= (($i == 0 || $i == 3) ? ('<th ' . (($i < 3) ? $colspan : '') . '>' . ($td) . '</th>') : ('<td id="' . $i . '" ' . (($i < 3) ? $colspan : '') . '>' . ($td) . '</td>'));
                        elseif ($pos_params['type'] == 1) :
                            $table .= (($i == 0 || $i == 3) ? ('<th ' . (($i < 3) ? 'colspan="3"' : '') . '>' . ($td) . '</th>') : ('<td id="' . $i . '" ' . (($i < 3) ? 'colspan="3"' : '') . '>' . ($td) . '</td>'));
                        else :
                            $table .= (($i == 0 || $i == 3 || $i == 6) ? ('<th ' . (($i < 6) ? 'colspan="4"' : '') . '>' . ($td) . '</th>') : ('<td id="' . $i . '" ' . (($i < 6) ? 'colspan="4"' : '') . '>' . ($td) . '</td>'));
                        endif ;
                    }
                    $table .= '</tr>';
                    $i++;
                }

                $table .= SEO_TBL_TG_;*/
            }
//exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $pos_params['word_type'];
            $this->render("seotool_view");
        } else {

            if (@$pos_params['class'])
                $this->_view->class = $pos_params['class'];

            $this->_view->type = $pos_params['type'];
            $this->_view->limit = $pos_params['limit'];

            if (@$msg)
                $this->_view->msg = $msg;
            $this->render("seotool_position1");
        }
    }

    function getCSV($file) {
        setlocale(LC_ALL, 'fr_FR');
        $data_array = array();
        $row = 1;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $num = count($data);

                for ($c = 0; $c < $num; $c++) {
                    $data_array[$row][$c] = $data[$c];
                }
                $row++;
            }
            fclose($handle);
        }
        return $data_array;
    }

    //function to check frequency
    function checkFrequency() {
        $error = '';
        if ($this->frequency_option == 1) {
            if (!$this->client)
                $error .= 'Please enter client name.<br>';
            if (!$this->title)
                $error .= 'Please enter title.<br>';
            if (!$this->days)
                $error .= 'Please select atleast one frequency day.<br>';
            if (!$this->end_date)
                $error .= 'Please select end date of frequency.<br>';

            if ($error)
                return $error;
            else
                return "process";
        } else
            return "process";
    }

    //function to check frequency
    function checkSearchFrequency() {
        $error = '';

        if (!$this->client)
            $error .= 'Please Select client .<br>';
        if (!$this->contract)
            $error .= 'Please Select contract.<br>';
        if (!$this->from_date)
            $error .= 'Please select from date.<br>';
        if (!$this->to_date)
            $error .= 'Please select end date.<br>';
        if (!$this->days)
            $error .= 'Please select any one of the frequency day.<br>';

        if ($error)
            return $error;
        else
            return "process";
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function posuploadAndProcess($srcFile, $u_file_name) {
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_POSITION_EXEC);
            
            switch($this->type) {
                case 5 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD5);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD5);
                    break;
                case 4 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD4);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD4);
                    break;
                case 3 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD3);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD3);
                    break;
                case 2 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD2);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD2);
                    break;
                default :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD);
                    break;
            }

            if($this->frequency_option==1)
            {
                $file_upload_path = $sftp->exec(SEO_FREQUENCY_UPLOAD);
                $file_download_path = $sftp->exec(SEO_FREQUENCY_DOWNLOAD);
            }

            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);

            /**passing file name**/
            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . "." . $src['extension'];

            switch($this->type)
            {
                case 5:
                    $ruby_file = SEO_POSITION_RB5;
                    break;
                case 4:
                    $ruby_file = SEO_POSITION_RB4;
                    break;
                case 3:
                    $ruby_file = SEO_POSITION_RB3;
                    break;
                case 2:
                    $ruby_file = SEO_POSITION_RB2;
                    break;
                default:
                    $ruby_file = SEO_POSITION_RB;
                    break;
            }

            $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');
            $limitt = $this->limit;
            $clientt = $this->client;
            $titlee = $this->title;
            $dayss = $this->days;
            $end_datee = $this->end_date;
            $site_idd = $this->site_id;
            $format = $this->format ? 2 : 1;
            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;

            if ($this->frequency_option == 1)
                $cmd = "ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \'$clientt\' \'$titlee\' \"$dayss\" \"$end_datee\" \"$encoding\" $userId $loginName 2>&1 ";
            else
                $cmd = "ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$encoding\" \"$format\" $userId $loginName 2>&1 ";

            $sftp->setTimeout(300);
            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
            $remoteFile = trim($file_download_path) . "/" . $dstfile;
            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_POS . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];
            $sftp->get($dstfile, $localFile);
            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                $csv_data = $this->getCSV($localFile);
                if ($this->output_type == 2) {
                    $ext = "xls";
                    $output_file = SEO_DOWNLOAD_POS . $fname . "." . $ext;

                    $this->WriteXLS($csv_data, $output_file);
                }
                $posAction = $this->format ? 'position2' : 'position';
                $typeParam = ($this->type && $this->format) ? '&type=' . $this->type : '';
                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . "/download_seo_position.php?saction=download&file=" . $fname . "&ext=" . $ext, SEO_DOWN_OP_FILE, $posAction . '?action=view&file=' . $fname . $typeParam . '&ext=csv', SEO_VIEW_RESULTS);
                
            } else if (trim($output) == SEO_RVM_NOTATION && $frequency_option == 1) {
                $response = $this->responseMsg(1, 6);
            } else {
                throw new Exception($output);
            }

        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function posscheduleuploadAndProcess($srcFile, $u_file_name) {
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_POSITION_EXEC);
            
            switch($this->type) {
                case 5 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD5);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD5);
                    break;
                case 4 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD4);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD4);
                    break;
                case 3 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD3);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD3);
                    break;
                case 2 :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD2);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD2);
                    break;
                default :
                    $file_upload_path = $sftp->exec(SEO_POSITION_UPLOAD);
                    $file_download_path = $sftp->exec(SEO_POSITION_DOWNLOAD);
                    break;
            }

            if($this->frequency_option==1)
            {
                $file_upload_path = $sftp->exec(SEO_FREQUENCY_UPLOAD);
                $file_download_path = $sftp->exec(SEO_FREQUENCY_DOWNLOAD);
            }

            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);

            /**passing file name**/
            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . "." . $src['extension'];
            $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');

            $limitt = $this->limit;
            $clientt = 'client name';
            $titlee = $this->title;
            $dayss = $this->days;
            $end_datee = $this->end_date;
            $site_idd = $this->site_id;
            $format = $this->format ? 2 : 1;
            $cron_run_time = str_replace('/', '-', $this->cron_run_time);
            $cron_email = $this->cron_email;

            switch($this->type)
            {
                case 5:
                    $option = 12;
                    break;
                case 4:
                    $option = 11;
                    break;
                case 3:
                    $option = 10;
                    break;
                case 2:
                    $option = 7;
                    break;
                default:
                    $option = 1;
                    break;
            }
            
            $ruby_file = SEO_POSITION_SCHEDULE_RB;
            
            $cmd = "ruby -W0 $ruby_file $site_idd $u_file_name $dstfile \"$clientt\" \"$titlee\" $option \"$encoding\" \"$cron_run_time\" \"$cron_email\"";

            $sftp->setTimeout(300);
            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");

            /**processed file path**/
            $remoteFile = trim($file_download_path) . "/" . $dstfile;
            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_POS . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];

            //downloading the file from remote server
            $sftp->get($dstfile, $localFile);
            $response = $this->responseMsg(1, 17);
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    function getOS($userAgent) {
        // Create list of operating systems with operating system name as array key
        $oses = array('iPhone' => '(iPhone)', 'Windows' => 'Win16', 'Windows' => '(Windows 95)|(Win95)|(Windows_95)', // Use regular expressions as value to identify operating system
        'Windows' => '(Windows 98)|(Win98)', 'Windows' => '(Windows NT 5.0)|(Windows 2000)', 'Windows' => '(Windows NT 5.1)|(Windows XP)', 'Windows' => '(Windows NT 5.2)', 'Windows' => '(Windows NT 6.0)|(Windows Vista)', 'Windows' => '(Windows NT 6.1)|(Windows 7)', 'Windows' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)', 'Windows' => 'Windows ME', 'Open BSD' => 'OpenBSD', 'Sun OS' => 'SunOS', 'Linux' => '(Linux)|(X11)', 'Safari' => '(Safari)', 'Macintosh' => '(Mac_PowerPC)|(Macintosh)', 'QNX' => 'QNX', 'BeOS' => 'BeOS', 'OS/2' => 'OS/2', 'Search Bot' => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)');

        foreach ($oses as $os => $pattern) {// Loop through $oses array

            // Use regular expressions to check operating system type
            if (strpos($userAgent, $os)) {// Check if a value in $oses array matches current user agent.
                return $os;
                // Operating system was matched so return $oses key
            }
        }
        return 'Unknown';
        // Cannot find operating system so return Unknown
    }

    /**function to read XLS file and return as array**/
    function readXLS($file) {
        /***********Getting File1 Data**********************/
        require_once SEO_XLS_READER;
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('Windows-1252');
        $data->read($file);

        $sheets = sizeof($data->sheets);

        for ($i = 0; $i < $sheets; $i++) {
            if ($data->sheets[$i]['numRows']) {
                $x = 1;
                while ($x <= $data->sheets[$i]['numRows']) {

                    $y = 1;
                    while ($y <= $data->sheets[$i]['numCols']) {

                        $xls_array[$i][$x][$y] = isset($data->sheets[0]['cells'][$x][$y]) ? iconv("ISO-8859-1", "UTF-8", $data->sheets[$i]['cells'][$x][$y]) : '';
                        if ($this->os == 'Windows')
                            $xls_array[$i][$x][$y] = utf8_decode($xls_array[$i][$x][$y]);
                        $y++;
                    }
                    $x++;
                }
            }
        }
        return $xls_array;
    }

    /**function to read XLS file and return as array**/
    function readInXLS($file) {
        /***********Getting File1 Data**********************/
        $data = new Spreadsheet_Excel_Reader();
        $data->read($file);

        if ($data->sheets[0]['numRows']) {
            $x = 1;
            while ($x <= $data->sheets[0]['numRows']) {
                $y = 1;
                while ($y <= $data->sheets[0]['numCols']) {
                    if (($this->site_id == 10 || $this->site_id == 11) && ($this->os != 'Windows')) {
                        $xls_array[$x][$y] = isset($data->sheets[0]['cells'][$x][$y]) ? (html_entity_decode($data->sheets[0]['cells'][$x][$y], ENT_QUOTES, 'iso-8859-1')) : '';
                    } else {
                        if ($this->site_id != 10 && $this->site_id != 11) {
                            $xls_array[$x][$y] = isset($data->sheets[0]['cells'][$x][$y]) ? (utf8_encode($data->sheets[0]['cells'][$x][$y])) : '';
                        } else {
                            $xls_array[$x][$y] = isset($data->sheets[0]['cells'][$x][$y]) ? iconv("ISO-8859-1", "UTF-8", $data->sheets[0]['cells'][$x][$y]) : '';
                            if ($this->os == 'Windows')
                                $xls_array[$x][$y] = utf8_decode($xls_array[$x][$y]);
                        }
                    }
                    $y++;
                }
                $x++;
            }
        }
        return $xls_array;
    }

    /**function to create CSV file**/
    function writeCSV($list, $file) {
        $fp = fopen($file, 'w');

        foreach ($list as $fields) {
            fputcsv($fp, $fields, ";");
        }
        fclose($fp);
    }

    /**function to create XLS file**/
    function WriteXLS($data, $file_name) {
        // include package
        include_once SEO_XLS_WRITER_INCLUDE;

        // create empty file
        //if(!class_exists('Spreadsheet_Excel_Writer'))
        $excel = new Spreadsheet_Excel_Writer($file_name);

        // add worksheet
        $sheet = &$excel->addWorksheet();
        //$sheet->setInputEncoding('ISO-8859-1');
        // create format for header row
        // bold, red with black lower border
        $firstRow = &$excel->addFormat();
        $firstRow->setBold();
        $firstRow->setSize(12);
        $firstRow->setBottom(1);
        $firstRow->setBottomColor('black');

        // add data to worksheet
        $rowCount = 0;
        foreach ($data as $row) {
            foreach ($row as $key => $value) {

                if ($this->os != 'Windows')
                    $value = utf8_decode($value);

                if ($rowCount == 0)
                    $sheet->write($rowCount, $key, $value, $firstRow);
                else
                    $sheet->write($rowCount, $key, $value);
            }
            $rowCount++;
        }
        // save file to disk
        $excel->close();
    }

    function showCSV($data) {
        /*$table = SEO_TBL_TG;

        $i = 0;
        foreach ($data as $row) {
            if ($i == 0){$table .= '<thead>';}
            $table .= '<tr>';
            foreach ($row as $td) {
                if ($i == 0)
                    $table .= '<th>' . utf8_encode($td) . '</th>';
                else
                    $table .= '<td>' . $td . '</td>';
            }
            $table .= '</tr>';
            if ($i == 0){$table .= '</thead><tbody>';}
            $i++;
        }
        $table .= '</tbody>' . SEO_TBL_TG_;*/
        return $this->viewResultsGrid($data, '');
    }

    function googlesuggestresults($file) {
        $data = $this->getCSV(SEO_DOWNLOAD_GSUGGEST . $file);
        //echo '<pre>';
        if (count($data) > 1) {
            $table = SEO_TBL_TG . '<thead><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th></thead><tbody>';$i=0;
            foreach ($data as $key => $word) {
                $btag = (trim($word[0]) == 'keyword' || trim($word[0]) == 'language' || trim($word[0]) == 'site') ? '<b>' : '';
                $etag = (trim($word[0]) == 'keyword' || trim($word[0]) == 'language' || trim($word[0]) == 'site') ? '</b>' : '';
                if ($this->site_id == 10) {
                    $table .= '<tr><td>' . $btag . html_entity_decode($word[1], ENT_QUOTES, 'iso-8859-1') . $etag . '</td><td>' . $btag . html_entity_decode($word[2], ENT_QUOTES, 'iso-8859-1') . $etag . '</td><td>' . $btag . html_entity_decode($word[3], ENT_QUOTES, 'iso-8859-1') . $etag . '</td><td>' . $btag . html_entity_decode($word[4], ENT_QUOTES, 'iso-8859-1') . $etag . '</td><td>' . $btag . html_entity_decode($word[5], ENT_QUOTES, 'iso-8859-1') . $etag . '</td></tr>';
                } else {
                    if (($this->os == 'Windows'))
                        $table .= '<tr><td>' . $btag . ($word[1]) . $etag . '</td><td>' . $btag . ($word[2]) . $etag . '</td><td>' . $btag . ($word[3]) . $etag . '</td><td>' . $btag . ($word[4]) . $etag . '</td><td>' . $btag . ($word[5]) . $etag . '</td></tr>';
                    else
                        $table .= '<tr><td>' . $btag . utf8_decode($word[1]) . $etag . '</td><td>' . $btag . utf8_decode($word[2]) . $etag . '</td><td>' . $btag . utf8_decode($word[3]) . $etag . '</td><td>' . $btag . utf8_decode($word[4]) . $etag . '</td><td>' . $btag . utf8_decode($word[5]) . $etag . '</td></tr>';
                }
                $i++;
            }
            $table .=  '</tbody>' . SEO_TBL_TG_;
        }
        return $table;
    }

    function posdownloadFile($filename, $extension) {
        $filename = $filename . "." . $extension;
        $path_file = SEO_DOWNLOAD_POS . $filename;
        if (file_exists($path_file)) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file");
            exit ;
        } else {
            $this->class = "error";
            $this->msg = "File not Exist";
        }
    }

    public function plagiarismAction() {        
        $plag_params = $this->_request->getParams();
        
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($plag_params['file']) && isset($plag_params['ext']))
            $this->plagdownloadFile($plag_params['file'], $plag_params['ext']);
        if ($plag_params['class'])
            $this->class = $plag_params['class'];
        $_POST['word_type'] = 1;
        $this->_view->word_type = $plag_params['word_type'];
        if (@$msg)
            $this->_view->msg = $msg;
        $this->render("seotool_plagiarism");
    }

    function plagdownloadFile($filename, $extension) {
        $filename = $filename . "." . $extension;
        $path_file = SEO_DOWNLOAD_PLAG . $filename;
        if (file_exists($path_file)) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file");
            exit ;
        } else {
            $this->class = "error";
            $this->msg = "File not Exist";
        }
    }

    public function plagssh2uploadAction() {
        $plag_params = $this->_request->getParams();
        if (isset($plag_params['submit'])) {
            $response = $this->responseMsg('', 0, 1, '', '');
            $this->type = $plag_params['word_type'];
            require_once SEO_SFTP_FILE;
            require_once SEO_FILE_CONVERTION;

            if ($this->type == 2) {
                $kw_text = trim($plag_params['kw']);
                $kw_text = ($kw_text);
                if ($kw_text) {
                    $fname = "File_" . time();
                    $txt_file_name = $fname . ".txt";
                    $srcFile = SEO_UPLOAD_PLAG . $txt_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kw_text);
                    fclose($fp);
                    $response = $this->plaguploadAndProcess($srcFile, $txt_file_name);
                    $response['word_type'] = $this->type;
                } else {
                    $response = $this->responseMsg(0, 26, $this->type);
                }
            } else if ($this->type == 1) {
                if ($_FILES['keyword_file']['name']) {
                    $tmpFile = $_FILES['keyword_file']['tmp_name'];
                    $u_file_name = str_replace(" ", "_", frenchCharsToEnglish(utf8_encode($_FILES['keyword_file']['name'])));
                    $srcFile = SEO_UPLOAD_PLAG . "$u_file_name";
                    move_uploaded_file($tmpFile, $srcFile);

                    /**getting content of uploaded  File**/
                    $file = pathinfo($srcFile);
                    $ext = $file['extension'];

                    if ($ext == 'zip' || $ext == 'rar') {
                        if($ext=='rar')
                        {
                            $zip_file=pathinfo($srcFile);
                            $zip_file['filename']   =   str_replace(" ","-",$zip_file['filename']) ;
                            $path   =   $zip_file['dirname']."/".$zip_file['filename'].".rar" ;
                            $rar_file = rar_open($path);
                            $list = rar_list($rar_file);
                            foreach($list as $file) {       
                                preg_match('/RarEntry for file "(.*)"/', $file, $matches) ;
                                if(strstr($file, 'RarEntry for file'))
                                {
                                    $entry = rar_entry_get($rar_file, $matches[1]) or die("Failed to find such entry") ;
                                    $entry->extract(false, $zip_file['dirname']."/".$zip_file['filename']."/".(str_replace(" ","-",frenchCharsToEnglish($matches[1]))));
                                    
                                    //$filepath = pathinfo($matches[1]) ;
                                    //rename(SEO_UPLOAD_PLAG . "/" . $matches[1], SEO_UPLOAD_PLAG . "/" . $filepath['dirname'] . "/" . frenchCharsToEnglish(str_replace(" ", "_", $filepath['basename']))) ;
                                }
                                
                            }
                            rar_close($rar_file);
                            $unzip_dir  =   $zip_file['dirname']."/".$zip_file['filename'] ;
                            chmod($unzip_dir,0777) ;
                        }
                        else
                        {
                            chmod($srcFile, 0777);
                            $unzip_dir = $this->unzip($srcFile);
                            //$unzip_dir = APP_PATH_ROOT . $unzip_dir ;
                        }
                        //exit('unzip_dir='.$unzip_dir);
                        if ($handle = opendir($unzip_dir)) {

                            $zip = new ZipArchive();
                            // Load zip library
                            $zip_name = "$unzip_dir/zip_" . time() . ".zip";
                            // Zip name
                            if ($zip->open($zip_name, ZIPARCHIVE::CREATE) !== TRUE) {
                                // Opening zip file to load files
                                $error .= "* Sorry ZIP creation failed at this time";
                            }

                            while (false !== ($entry = readdir($handle))) {

                                if ($entry != "." && $entry != "..") {

                                    unset($content);
                                    unset($status);
                                    $unzip_file = pathinfo("$unzip_dir/$entry");
                                    $unzip_ext = $unzip_file['extension'];
                                    $content = new filecontent($unzip_dir . "/" . $entry);
                                    $status = $content->getStatus();

                                    if ($status == 1) {
                                        $srcFile = $unzip_dir . "/" . $unzip_file['filename'] . ".txt";
                                        $u_file_name = $unzip_file['filename'] . ".txt";
                                        $u_file_name = frenchCharsToEnglish($u_file_name);
                                        $zip->addFile($srcFile, $u_file_name);
                                    }
                                }
                            }
                            closedir($handle);
                            $zip->close();
                            $response = $this->plaguploadAndProcess($zip_name, 'many');
                        }
                    } else {
                        $content = new filecontent($srcFile);
                        $status = $content->getStatus();

                        if ($status == 1) {
                            $srcFile = SEO_UPLOAD_PLAG . $file['filename'] . ".txt";
                            $u_file_name = $file['filename'] . ".txt";
                            $response = $this->plaguploadAndProcess($srcFile, $u_file_name);
                        } else {
                            $response = $this->responseMsg(0, 27);
                        }
                    }
                } else {
                    $response = $this->responseMsg(0, 28);
                }
                $response['word_type'] = $this->type;
            }
            print json_encode($response);
            exit ;
        }
    }

    function plaguploadAndProcess($srcFile, $u_filename) {
        
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_PLAG_EXEC);
            $file_upload_path = $sftp->exec(SEO_PLAG_UPLOAD);
            $file_download_path = $sftp->exec(SEO_PLAG_DOWNLOAD);

            $sftp->chdir(trim($file_upload_path));

            if ($u_filename == 'many')
                $u_file_name = strrev(substr(strrev($srcFile), 0, strpos(strrev($srcFile), '/')));
            else
                $u_file_name = $u_filename;

            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);
            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . "." . $src['extension'];
            $dstfile_xml = $download_fname . ".xml";
            $format = $this->format ? 2 : 1;
            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;
            $ruby_file = SEO_PLAG_RB;

            if ($u_filename == 'many')
                $cmd = "ruby -W0 $ruby_file '$u_file_name' 'many' '$dstfile_xml' $userId $loginName 2>&1 ";
            else
                $cmd = "ruby -W0 $ruby_file '$u_file_name' '$dstfile' '$dstfile_xml' $userId $loginName 2>&1 ";

            $sftp->setTimeout(300);

            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");

            /**processed file path**/
            $remoteFile = trim($file_download_path) . "/" . $dstfile_xml;

            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_PLAG . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];

            //downloading the file from remote server
            $sftp->get($dstfile_xml, $localFile);

            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                $xml_data = file_get_contents($localFile);
                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, "plagiarism?action=download&file=" . $fname . "&ext=" . $ext, SEO_DOWN_OP_FILE, 0, 0, '<div id="plagresults" class="alert alert-block alert-info" style="height:auto; position:relative;top:20px;float:left;"><h3>Plagiarism Results</h3>' . $xml_data.'</div>');
            } else {
                if(strlen(trim(strip_tags(nl2br($output))))==strlen('Using /home/oboulo/.rvm/gems/ruby-1.9.3-head File Size is more Than 800kb'))
                {
                    $response['type'] = 'error';
                    $response['message'] = 'File Size is more Than 800kb';
                }
                else {
                    throw new Exception($output);
                }
            }

        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    public function googleNewsAction() {
        $gnews_params = $this->_request->getParams();
        /*if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($gnews_params['file']) && isset($gnews_params['ext']))
            $this->_redirect(BO_PATH_ . 'download_seoresult.php?ext=' . $gnews_params['ext'] . '&filename=' . $gnews_params['file'] . '&tool=gnews');*/

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($gnews_params['file']) && isset($gnews_params['ext'])) {
            $filename = $gnews_params['file'] . "." . $gnews_params['ext'];
            $path_file = SEO_DOWNLOAD_GNEWS . $filename;

            if (file_exists($path_file)) {
                header('Content-Type: text/html; charset=utf-8');
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'gnews') ;
            }
            //exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $gnews_params['word_type'];
            $this->render("seotool_view");
        } else {
            if ($gnews_params['class'])
                $this->_view->class = $gnews_params['class'];

            $_POST['word_type'] = 1;
            $this->_view->word_type = $gnews_params['word_type'];

            $this->render("seotool_googlenews" . ($gnews_params['debug'] ? '_test' : ''));
        }
    }

    function gnewsdownloadFile($filename, $extension) {
        $filename = $filename . "." . $extension;
        $path_file = SEO_DOWNLOAD_GNEWS . $filename;
        if (file_exists($path_file)) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file");
            exit ;
        } else {
            $this->class = "error";
            $this->msg = "File not Exist";
        }
    }

    public function gnewsssh2uploadAction() {
        $gnews_params = $this->_request->getParams();
        if (isset($gnews_params['submit'])) {
            // response hash
            $response = $this->responseMsg('', 0, 1, '', '');
            $this->type = $gnews_params['word_type'];

            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;

            $this->output_type = $gnews_params['op_type'];
            $this->site_id = $gnews_params['site'];
            $this->limit = $gnews_params['limit'];

            if ($this->type == 2) {
                $kw_text = trim($gnews_params['kw']);
                if (($this->os == 'Windows'))
                    $kw_text = utf8_decode($kw_text);

                if ($kw_text) {
                    $kw_text1 = explode("\n", $kw_text);

                    $csv_file_name = "csv_" . time() . ".csv";
                    $srcFile = SEO_UPLOAD_GNEWS . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kw_text);
                    fclose($fp);
                    $response = $this->gnewsuploadAndProcess($srcFile, $csv_file_name);
                    $response['word_type'] = $this->type;
                } else {
                    $response = array('type' => 'error', 'message' => 'Please enter URL&keywords in box (CSV Format)', 'word_type' => $this->type);
                }

            } else if ($this->type == 1) {
                if (($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];
                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_GNEWS . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['keyword_file']['name']);
                    }

                    $response = $this->gnewsuploadAndProcess($srcFile, $u_file_name);
                } else {
                    $response = $this->responseMsg(0, 1);
                }
                $response['word_type'] = $this->type;
            }
            print json_encode($response);
            exit ;
        }
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function gnewsuploadAndProcess($srcFile, $u_file_name) {
        try {
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $this->seo_upload_files->gnews = $download_fname;
            $dstfile = $download_fname . "." . $src['extension'];

            $file_exec_path = $sftp->exec(SEO_GNEWS_EXEC);
            $file_upload_path = $sftp->exec(SEO_GNEWS_UPLOAD);
            $file_download_path = $sftp->exec(SEO_GNEWS_DOWNLOAD);
            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);
            $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');

            $limitt = $this->limit;
            $site_idd = $this->site_id;
            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;
            $ruby_file = SEO_GNEWS_RB;
            
            $cmd = "ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$encoding\" 1 $userId $loginName 2>&1 ";
            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
            $remoteFile = trim($file_download_path) . "/" . $dstfile;

            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_GNEWS . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];
            $sftp->get($dstfile, $localFile);

            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                if ($this->output_type == 2) {
                    $ext = "xls";
                    $output_file = SEO_DOWNLOAD_GNEWS . $fname . "." . $ext;
                    $csv_data = $this->gnewsgetCSV($localFile);
                    $this->WriteXLS($csv_data, $output_file);
                }

                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . $ext . '&filename=' . $fname . '&tool=gnews', SEO_DOWN_OP_FILE, 'google-news?action=view&file=' . $fname . '&ext=csv', SEO_VIEW_RESULTS);
            } else if (trim($output) == SEO_RVM_NOTATION && $option3 == 1) {
                $response = $this->responseMsg(1, 6);
            } else {
                throw new Exception($output);
            }
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    /*function gnewsgetCSV1($file) {
        $data_array = array();
        $row = 1;
        $file_handle = fopen($file, "r");

        while (!feof($file_handle)) {

            $data = fgetcsv($file_handle, 1024);
            $num = count($data);
            for ($c = 0; $c < $num; $c++) {
                $data[$c] = trim($data[$c]);
                $data_array[$row][$c] = iconv("ISO-8859-1", "UTF-8", $data[$c]);
                $data_array[$row][$c] = utf8_decode($data_array[$row][$c]);
            }
            $row++;
        }
        fclose($file_handle);
        return $data_array;
    }*/

    function gnewsgetCSV($file) {
        $data_array = array();
        $row = 1;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $num = count($data);

                for ($c = 0; $c < $num; $c++) {
                    $data_array[$row][$c] = $data[$c];
                }
                $row++;
            }
            fclose($handle);
        }
        return $data_array;
    }

    public function frequencyAction() {
        $frequency_params = $this->_request->getParams();
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($frequency_params['file']) && isset($frequency_params['ext']))
            $this->frequencydownloadFile($frequency_params['file'], $frequency_params['ext']);

        require_once SEO_SFTP_FILE;
        $sftp = new Net_SFTP($this->ssh2_server);
        if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
            throw new Exception('Login Failed');
        }

        //Path to execute php command
        $file_exec_path = $sftp->exec(SEO_FREQUENCY_EXEC);
        $cmd = "php getContracts.php 2>&1 ";

        $sftp->setTimeout(300);
        $file_exec_path = trim($file_exec_path);
        $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
        $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");

        $output = str_replace(SEO_RVM_NOTATION, "", $output);
        $output = explode("$$$#####$$$", $output);

        $this->_view->clients = $output[0];
        $this->_view->contracts = $output[1];
        if (@$frequency_params['class'])
            $this->_view->class = $frequency_params['class'];
        $_POST['word_type'] = 1;
        $this->_view->word_type = $frequency_params['word_type'];

        if (@$msg)
            $this->_view->msg = $msg;

        $this->render("seotool_frequency");
    }

    function frequencydownloadFile($filename, $extension) {

        $filename = $filename . "." . $extension;
        $path_file = SEO_DOWNLOAD_FREQUENCY . $filename;
        if (file_exists($path_file)) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file");
            exit ;
        } else {
            $class = "error";
            $msg = "File not Exist";
        }
    }

    function linksdownloadFile($filename, $extension) {
        $filename = $filename . "." . $extension;
        $path_file = SEO_DOWNLOAD_LINKS . $filename;
        if (file_exists($path_file)) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file");
            exit ;
        } else {
            $class = "error";
            $msg = "File not Exist";
        }
    }

    public function frequencyssh2uploadAction() {
        $frequency_params = $this->_request->getParams();
        if (isset($frequency_params['submit'])) {
            $response = array('type' => '', 'message' => '');

            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;

            $this->client = $frequency_params['client'];
            $this->contract = $frequency_params['contract'];
            $this->from_date = $frequency_params['from_date'];
            $this->to_date = $frequency_params['to_date'];
            $this->days = implode("|", $frequency_params['day']);

            $frequency = $this->checkSearchFrequency();
            if ($frequency == 'process') {
                $response = $this->frequencyuploadAndProcess();
            } else {
                $response = $this->responseMsg(0, 0, 0, $frequency);
            }
            print json_encode($response);
            exit ;
        }
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function frequencyuploadAndProcess() {
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            $file_exec_path = $sftp->exec(SEO_FREQUENCY_EXEC);
            $file_download_path = $sftp->exec(SEO_FREQUENCY_DOWNLOAD);
            $dstfile = str_replace(" ", "_", $this->contract) . "_" . time() . ".zip";
            $from_datee = $this->from_date;
            $to_datee = $this->to_date;
            $contractt = $this->contract;
            $dayss = $this->days;
            $ruby_file = SEO_FREQUENCY_RB;
            $cmd = "ruby -W0 $ruby_file \"$from_datee\" \"$to_datee\" \"$contractt\" \"$dayss\" \"$dstfile\" 2>&1 ";
            $sftp->setTimeout(300);
            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
            $remoteFile = trim($file_download_path) . "/" . $dstfile;
            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_FREQUENCY . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];
            $sftp->get($dstfile, $localFile);
            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                $response = $this->displaySuccessMsg('', "/seotool/frequency?action=download&file=" . $fname . "&ext=" . $ext, SEO_DOWN_RESULT_FILE);
            } else {
                throw new Exception($output);
            }
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    /* SEO Tool status to show progress bar */
    function seotoolstatusAction() {

        mysql_connect('50.116.62.9', 'editplace', 'ep123') or die('cant connect to 50.116.62.9');
        mysql_select_db('editplace');

        $table = $this->seo_upload_files->gnews;
        //"test";
        $sql = mysql_query("select CONCAT(COUNT(*), '*', (select COUNT(*) from $table where processed = '1')) AS result from $table") or die("$table");
        $result = mysql_fetch_object($sql);
        exit($result->result);
    }

    public function googleSuggestAction() {
        $gsuggest_params = $this->_request->getParams();
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($gsuggest_params['file']))
            $this->googlesuggestdownloadXLS($gsuggest_params['file']);

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($gsuggest_params['file']) && isset($gsuggest_params['ext'])) {
            $this->site_id = $gsuggest_params['siteid'];
            //exit($this->googlesuggestresults($gsuggest_params['file'] . "." . $gsuggest_params['ext']));
            $this->_view->table = $this->googlesuggestresults($gsuggest_params['file'] . "." . $gsuggest_params['ext']);
            $this->render($gsuggest_params['siteid'] ? "seotool_utfview" : "seotool_view");
            exit ;
        }

        $this->_view->word_type = $gsuggest_params['word_type'];
        $this->_view->kw = stripslashes(trim(strip_tags($gsuggest_params['kw'])));

        if (isset($gsuggest_params['submit'])) {
            if (@$msg)
                $this->_view->msg = $msg;
            $type = $gsuggest_params['word_type'];
            $site = $gsuggest_params['site'];

            switch($site) {
                case 'fr' :
                    $url = 'google.fr';
                    break;
                case 'uk' :
                    $url = 'google.co.uk';
                    break;
                case 'com' :
                    $url = 'google.com';
                    break;
                case 'de' :
                    $url = 'google.de';
                    break;
                case 'in' :
                    $url = 'google.co.in';
                    break;
                case 'it' :
                    $url = 'google.it';
                    break;
                case 'es' :
                    $url = 'google.es';
                    break;
                case 'pt' :
                    $url = 'google.pt';
                    break;
                case 'br' :
                    $url = 'google.com.br';
                    break;
                default :
                    $url = 'google.fr';
                    break;
            }
            //Only Source or Combinations
            $combination = $gsuggest_params['combination'];
            if ($type == 1) {
                if (($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];

                    if ($extension == 'xls') {
                        $data_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                    } else {
                        $data_array = array();
                        $row = 1;
                        if (($handle = fopen($_FILES['keyword_file']['tmp_name'], "r")) !== FALSE) {
                            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                                $num = count($data);
                                if ($data[0]) {
                                    for ($c = 0; $c < $num; $c++) {
                                        if ($data[$c] != '')
                                            $data_array[$row][$c] = $data[$c];
                                    }
                                    $row++;
                                }
                            }
                            fclose($handle);

                            $rows = count($data_array);
                            $cols = $num;
                        }
                    }
                    if (count($data_array) > 0) {
                        $words = $data_array;
                        $j = 1;
                        $this->gsuggest_excel_array[0][0] = "Keyword";
                        $this->gsuggest_excel_array[0][1] = "No Results";

                        foreach ($words as $word) {
                            $word = trim($word[0]);
                            $this->googleSuggest($url, $word);

                            if ($combination == 2) {
                                foreach (range('a','z') as $i) {
                                    $query = '';
                                    $query = $word . ' ' . $i;
                                    $this->googleSuggest($url, $query);
                                }
                            }
                        }
                        $this->googlesuggestWriteXLS($this->gsuggest_excel_array, 'suggest');
                    }
                }
            } else if ($type == 2) {
                $text = trim($gsuggest_params['kw']);
                $textAr = explode("\n", $text);
                $words = array_filter($textAr, 'trim');
                if (count($words) > 0) {
                    $j = 1;
                    $this->gsuggest_excel_array[0][0] = "Keyword";
                    $this->gsuggest_excel_array[0][1] = "No Results";

                    foreach ($words as $word) {
                        $word = trim($word);
                        $this->googleSuggest($url, $word);

                        if ($combination == 2) {
                            foreach (range('a','z') as $i) {
                                $query = '';
                                $query = $word . ' ' . $i;
                                $this->googleSuggest($url, $query);
                            }
                        }
                    }
                    //write the data into XLS
                    $this->googlesuggestWriteXLS($this->gsuggest_excel_array, 'suggest');
                }
            }
        }

        if (count($this->gsuggest_excel_array) > 1) {
            $length = count($this->gsuggest_excel_array) - 1;
            $table = SEO_TBL_TG . '
                    <caption>URL:' . $url . '<br>
                    "' . $length . '" Suggestions for given keyword(s).</caption>
                    <tr>
                        <th scope="col" abbr="Keyword">Keyword</th>
                        <th scope="col" abbr="Number of Results">N&deg; Results</th>
                    </tr>';
            //for($i=0;$i<$length;$i++)
            foreach ($this->gsuggest_excel_array as $key => $word) {
                if ($key > 0)
                    $table .= '<tr><td>' . utf8_decode($word[0]) . '</td><td>' . $word[1] . '</td></tr>';
            }
            $table .= SEO_TBL_TG_;

            $this->_view->gsuggest_excel = $table;
        }
        $this->_view->gurl = $_SESSION['gurl'] ? $_SESSION['gurl'] : '0';
        $this->render($gsuggest_params['linode'] ? 'seotool_googlesuggest' : 'seotool_googlesuggest_tor');
    }

    public function googlesuggesttorAction() {
        $gsuggestParams = $this->_request->getParams();
        $type = $gsuggestParams['word_type'];
        $site = implode('|', $gsuggestParams['site']);
        $site_ext = $gsuggestParams['site_ext'];
        $this->site_id = $site_ext;
        $combination = $gsuggestParams['combination'];
        $loginName = $this->adminLogin->loginName;
        $userId = $this->adminLogin->userId;
        $outputfilename = 'results_' . time();
        $outputcsvfile = 'results_' . time() . '.csv';
        $outputxlsfile = 'results_' . time() . '.xls';
        $srcFile = SEO_UPLOAD_GSUGGEST . $outputcsvfile;

        if (isset($gsuggestParams['submit'])) {
            if ($site == '|' || $site == '') {
                $response = $this->responseMsg(0, 29);
            } elseif ($type == 2) {
                $kw_text = trim($gsuggestParams['kw']);

                if (!empty($kw_text)) {
                    if ($this->os == 'Windows' && ($this->site_id == 10) && ($this->site_id == 11))
                        $kw_text = utf8_decode($kw_text);

                    $kw_text1 = explode("\n", $kw_text);
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, str_replace("\'", "'", $kw_text));
                    fclose($fp);
                } else {
                    $response = $this->responseMsg(0, 16);
                }
            } else if ($type == 1) {
                $file_info = pathinfo($_FILES['keyword_file']['name']);
                $extension = $file_info['extension'];

                if ($extension == 'xls') {
                    require_once SEO_XLS_READER;
                    move_uploaded_file($_FILES['keyword_file']['tmp_name'], SEO_UPLOAD_GSUGGEST . $outputfilename . ".xls");
                    $data = $this->readInXLS(SEO_UPLOAD_GSUGGEST . $outputfilename . ".xls");

                    $this->writeCSV($data, $srcFile);
                } elseif ($extension == 'csv') {
                    move_uploaded_file($_FILES['keyword_file']['tmp_name'], $srcFile);
                } else {
                    $response = $this->responseMsg(0, 1);
                }
            }
            if (file_exists($srcFile)) {
                require_once SEO_SFTP_FILE;

                $sftp = new Net_SFTP($this->ssh2_server);
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed');
                }
                
                $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');
                $file_exec_path = $sftp->exec(SEO_GSUGGEST_EXEC);
                $file_upload_path = $sftp->exec(SEO_GSUGGEST_UPLOAD);
                $file_download_path = $sftp->exec(SEO_GSUGGEST_DOWNLOAD);
                $file_exec_path = trim($file_exec_path);
                $file_upload_path = trim($file_upload_path);
                $file_download_path = trim($file_download_path);
                $ruby_file = SEO_GSUGGEST_RB;

                $sftp->setTimeout(300);
                $cmd = "ruby -W0 $ruby_file '$site_ext' $outputcsvfile $outputcsvfile '$site' \"$encoding\" $combination $userId $loginName";
                $sftp->chdir(trim($file_upload_path));
                $sftp->put($outputcsvfile, SEO_UPLOAD_GSUGGEST . $outputcsvfile, NET_SFTP_LOCAL_FILE);

                $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                $out_put = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd;");
                $sftp->chdir(trim($file_download_path));

                //downloading the files from remote server
                $sftp->get($outputcsvfile, SEO_DOWNLOAD_GSUGGEST . $outputcsvfile);
                $sftp->get($outputxlsfile, SEO_DOWNLOAD_GSUGGEST . $outputxlsfile);
            } else {
                $response = $this->responseMsg(0, 30);
            }

            if (trim($out_put) == SEO_RVM_NOTATION) :
                $response = $this->displaySuccessMsg('', BO_PATH . "/download_seoresult.php?filename=" . $outputfilename . "&tool=gsuggest&ext=xls", SEO_DOWN_OP_FILE, 'google-suggest?action=view&file=' . $outputfilename . 
                '&ext=csv&siteid=' . $this->site_id, SEO_VIEW_RESULTS);
            else :
                $response = $this->responseMsg(0, 0, 0, "cmd=" . $cmd . "output=" . trim($out_put));
            endif ;
        }
        print json_encode($response);
        exit ;
    }

    function googlesuggestWriteXLS($data, $name) {
        // include package
        include SEO_XLS_WRITER_INCLUDE;

        // create empty file
        $filename = uniqid() . "_" . str_replace(' ', '_', $name);
        $excel = new Spreadsheet_Excel_Writer(SEO_DOWNLOAD_GSUGGEST . $filename . ".xls");

        // add worksheet
        $sheet = &$excel->addWorksheet();
        $sheet->setInputEncoding('utf-8');
        // create format for header row
        // bold, red with black lower border
        $header_f = array('bold' => '1', 'size' => '10', 'FgColor' => 'yellow', 'color' => 'black', 'border' => '1', 'align' => 'center');
        $header = &$excel->addFormat($header_f);
        $cell_f = array('color' => 'black', 'border' => '1', 'align' => 'left');
        $cell = &$excel->addFormat($cell_f);

        // add data to worksheet
        $rowCount = 0;
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                if ($rowCount == 0)
                    $sheet->write($rowCount, $key, $value, $header);
                else
                    $sheet->write($rowCount, $key, utf8_decode($value), $cell);
            }
            $rowCount++;
        }
        // save file to disk
        if ($excel->close() === true) {
            $this->_view->msg = 'Spreadsheet successfully saved! <a href="/download_seo.php?saction=download&tool=gsuggest&file=' . $filename . '&ext=xls">Download XLS</a>';
            $this->_view->class = 'success';
        } else {
            $this->_view->msg = 'ERROR: Could not save spreadsheet.';
            $this->_view->class = 'error';
        }

    }

    function googlesuggestdownloadXLS($filename) {
        $filename = $filename . ".xls";
        $path_file = SEO_DOWNLOAD_GSUGGEST . $filename;
        if (file_exists($path_file)) {
            header("Content-type: application/xls");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file");
            exit ;
        }
    }

    function gsuggestutf8dec($s_String) {
        $s_String = rawurlencode(utf8_encode($s_String));
        return $s_String;
    }

    function googleSuggest($site, $query) {
        switch($site) {
            case 'google.fr' :
                $lang = 'fr';
                break;
            case 'google.co.uk' :
                $lang = 'en-uk';
                break;
            case 'google.com' :
                $lang = 'en';
                break;
            case 'google.de' :
                $lang = 'de';
                break;
            case 'google.co.in' :
                $lang = 'en';
                break;
            case 'google.it' :
                $lang = 'it';
                break;
            case 'google.es' :
                $lang = 'es';
                break;
            case 'google.pt' :
                $lang = 'pt';
                break;
            case 'google.com.br' :
                $lang = 'com.br';
                break;
            default :
                $lang = 'fr';
                break;
        }

        $_SESSION['gurl'] = $url = 'http://' . $site . '/complete/search?q=' . $this->gsuggestutf8dec($query) . '&output=toolbar&ie=UTF-8&oe=UTF-8&lr=lang_' . $lang . '&hl=' . $lang;

        //echo $url."<br>";
        $xml = new DOMDocument;
        $xml->load($url);
        $thedocument = $xml->documentElement;
        $list = $thedocument->getElementsByTagName('CompleteSuggestion');

        foreach ($list as $domElement) {
            foreach ($domElement->childNodes as $node) {
                if ($node->getAttribute('data'))
                    $suggest = $node->getAttribute('data');
                if ($node->getAttribute('int'))
                    $num_queries = $node->getAttribute('int');
                else
                    $num_queries = "-";
            }
            $this->gsuggest_excel_array[] = array($suggest, $num_queries);
        }
    }

    public function plagcontentsAction() {
        
        $plag_params = $this->_request->getParams();

        $plgFile = $plag_params['file'] ? $plag_params['file'] : $plag_params['s0plagfile'];

        //Added for compare URL date with txt file
        $txtFile = $plag_params['file'] ? $plag_params['file'] : $plag_params['s0plagfile'];
        $txtFile = explode("_", $txtFile);
        $artdetails = explode("_", $plag_params['s0plagfile']);
        $artId = $artdetails[0];
        array_pop($txtFile);
        $txtFile = implode("_", $txtFile) . ".txt";
        $plagTxtFile_path = SEO_UPLOAD_PLAG . $txtFile;

        if (!empty($plgFile) && !empty($plag_params['idx'])) :

             $xmldata = simplexml_load_file(BO_DOMAIN_ . BO_PATH_  . ($plag_params['s0plagfile'] ? SEO_PLAG_ : SEO_DOWNLOAD_PLAG_) . $plgFile);
            $plgs = array();

            foreach ($xmldata->children() AS $child) {
                foreach ($child->results->children() AS $child1) {
                    foreach ($child1->url->children() AS $child2) {
                        if ($child2->getName() == 'p')
                            $plgs['url'][] = (string)$child2;
                    }
                    foreach ($child1->content->children() AS $child2) {
                        if ($child2->getName() == 'p')
                            $plgs['content'][] = (string)$child2;
                    }
                    foreach ($child1->percentage->children() AS $child2) {
                        if ($child2->getName() == 'p')
                            $plgs['percentage'][] = (string)$child2;
                    }
                }
            }
            if ($plag_params['s0plagfile']) {
               // echo BO_DOMAIN_ . FO_PATH_ . SEO_ARTICLES .substr($plag_params['s0plagfile'], 0, strpos($plag_params['s0plagfile'], '_')) . '/' . str_replace('.xml', '.txt', $plag_params['s0plagfile'])  ;
                $pActualContent = file_get_contents(BO_DOMAIN_ . FO_PATH_ . SEO_ARTICLES . substr($plag_params['s0plagfile'], 0, strpos($plag_params['s0plagfile'], '_')) . '/' . str_replace('.xml', '.txt', $plag_params['s0plagfile']));
               $words = ($plgs['content'][$plag_params['idx'] - 1]);
              // echo  $words = " L'argent [ liquide reste l'un des moyens de transactions préféré des Français, 86% d'entre eux ne souhaitant pas que le cash disparaisse selon un sondage réalisé par l'Ifop en partenariat avec la société de transport de fonds Brinks. Une étude commandée alors que les paiements sans contacts et électroniques se développe de plus en plus, quoi qu'à un rythme assez lent en France.";
                 // $words = $pActualContent;
               // $file_content = $pActualContent;
                $file_content = $plgs['content'][$plag_params['idx'] - 1];
            } else {
                if (file_exists($plagTxtFile_path)) {
                    $words = @file_get_contents($plagTxtFile_path);
                    $file_content = $words;
                } else
                    $words = $plgs['content'][$plag_params['idx'] - 1];
            }
          //  echo $words; exit;
            $escapechars = array("&rsquo;"=>"'", "&lsquo;"=>"'", "à"=>"&agrave;",
                ","=>"","é"=>"&eacute;","è"=>"&egrave;",""=>"",""=>"","("=>"",")"=>"","?"=>"",
                "#"=>"","$"=>"","^"=>"","*"=>"","~"=>"","`"=>"","~"=>"","ù"=>'',""=>'',"."=>"",);
            $text = file_get_contents($plgs['url'][$plag_params['idx'] - 1]);
            $text = $this->cleanString(html_entity_decode($text, ENT_QUOTES, "UTF-8")); // Remove unwanted quotes from string
            $words = $this->cleanString(html_entity_decode($words, ENT_QUOTES, "UTF-8"));
            $text=preg_replace('/\s+/',' ',$text);
            $words=preg_replace('/\s+/',' ',$words);
            foreach($escapechars as $key => $val)
            {
                $text =str_replace($key,$val,$text);
                $words =str_replace($key,$val, $words);
            }
           /* $text=preg_replace('/\s+/',' ',$text);
            $text =str_replace("’","'",$text);
            $text=str_replace("&rsquo;","'",$text);
            $text=str_replace("&lsquo;","'",$text);
            $text =str_replace("<i>","",$text);
            $text =str_replace("</i>","",$text);
            $text =str_replace("à","&agrave;",$text);
            $text =str_replace(",","",$text);
            $text =str_replace("é","&eacute;",$text);
            $words = $this->cleanString(html_entity_decode($words, ENT_QUOTES, "UTF-8"));
            $words =str_replace("é","&eacute;",$words);
            $words =str_replace("è","&egrave;",$words);
            $words =str_replace("à","&agrave;",$words);
            $words =str_replace(",","",$words);
            $words=preg_replace('/\s+/',' ',$words);
            $words =str_replace("’","'", $words);
            $words=str_replace("&rsquo;","'",$words);
            $words=str_replace("&lsquo;","'",$words);
            $words=str_replace("["," ",$words);
            $words=str_replace("]"," ",$words);
            $words=str_replace("("," ",$words);
            $words=str_replace(")"," ",$words);
            $words=str_replace("<a>"," ",$words);
            $words=str_replace("</a>"," ",$words);
            $words=str_replace("?"," ",$words);*/

            $words = str_replace("&rsquo;", "'", $this->cleanString($words)); // Remove unwanted quotes from string
            //added
            $words = stripslashes($words);

            $this->_view->pUrl = $plgs['url'][$plag_params['idx'] - 1];

            //added
            if ($file_content)
                $this->_view->pContent = $file_content;
            else
                $this->_view->pContent = $plgs['content'][$plag_params['idx'] - 1];

            $this->_view->pPercentage = $plgs['percentage'][$plag_params['idx'] - 1];
             $this->_view->plagText = $this->plagsHighlight($text, $words);
        else :
            $this->_view->plagText = 'missing - plag arguments';
        endif ;

        // Processes a view script and returns the output.
        $this->render("seotool_plags_view");
    }

    /* Words highlight in plagiarsm results view*/
    function plagsHighlight($text, $words)
    {  // echo $words;exit;
        // preg_match_all('/[^\s]+\s[^\s]+\s[^\s]+\s[^\s]+\s[^\s]+/i', $words, $m);
        preg_match_all('/[^\s]+\s+[^\s]+\s+[^\s]+/i', $words, $m); //[^\s]+\s[^\s]+\s
       // print_r($m); exit;
        if(!$m)
            return $text;
        $re = '~\\b('.implode('|',$m[0]).')\\b~';
        return  preg_replace($re, '<b style="background:#FFFF00">$0</b>', $text);
    }
    // Words highlight in plagiarsm results view
    /*function plagsHighlight($text, $words) {

        preg_match_all('/[^\s]+\s[^\s]+\s[^\s]+\s[^\s]+\s[^\s]+/', $words, $m);
        //+\s[^\s]+\s[^\s]
        if (!$m)
            return $text;
        $re = '~\\b(' . implode('|', $m[0]) . ')\\b~';
        foreach ($m[0] as $m_) :
            $text =preg_replace($re, '<b style="background:#FFFF00">$0</b>', $text);
        endforeach;
        return $text;
    }*/

    function cleanString($string) {

        $find[] = '“';
        // left side double smart quote
        $find[] = '”';
        // right side double smart quote
        $find[] = "‘";
        // left side single smart quote
        $find[] = "’";
        // right side single smart quote
        $find[] = '…';
        // elipsis
        $find[] = '—';
        // em dash
        $find[] = '–';

        $replace[] = '"';
        $replace[] = '"';
        $replace[] = "'";
        $replace[] = "'";
        $replace[] = '...';
        $replace[] = '-';
        $replace[] = '-';

        return str_replace($find, $replace, $string);
    }

    function convert_smart_quotes($string) {
        $search = array(chr(145), chr(146), chr(147), chr(148), chr(151), chr(230), chr(156));

        $replace = array("'", "'", '"', '"', '-', 'ae', 'oe');
        return str_replace($search, $replace, $string);
    }

    public function contentserrorcheckAction() {
        $err_params = $this->_request->getParams();
        $this->_view->err1 = $err_params['err1'];
        $this->_view->err2 = $err_params['err2'];
        @$lang1 = $err_params['lang'];
        $this->_view->lang1 = ((!empty($lang1)) ? $lang1 : '');

        $this->render("textcontentserror_check");
    }

    public function validatetagAction() {

        /**getting clients and contracts details**/
        require_once SEO_SFTP_FILE;

        try {
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }
            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_TAG_EXEC);
            $ruby_file = SEO_TAG_RB;
            //ruby execution path
            $cmd = "ruby -W0 $ruby_file";
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");

            $response = $this->responseMsg(1, 7);
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }

        print json_encode($response);
        exit ;
    }

    public function savetaginfoAction() {
        $tag_params = $this->_request->getParams();
        $client_info_obj = new Ep_User_User();
        $client_info = $client_info_obj->GetclientList();
        $client_list = array();
        $ao_obj = new Ep_Ao_EditAO();

        foreach ($client_info as $key => $value) {
            $name = $value['email'];
            $nameArr = array($value['company_name'], $value['first_name'], $value['last_name']);
            $nameArr = array_filter($nameArr);

            if (count($nameArr) > 0)
                $name .= "(" . implode(", ", $nameArr) . ")";

            $client_list[$value['identifier']] = strtoupper($name);
        }
        asort($client_list);
        array_unshift($client_list, "S&eacute;lectionner");
        $this->_view->show_ao = "display:none;";

        if ($tag_params['client'] != "") {
            $this->_view->show_ao = "";
            $ao_list = $ao_obj->getAOlist($tag_params['client'], 1);
            $this->_view->ao_list = $ao_list;
            $this->_view->def_user = $tag_params['client'];
        }

        $this->render("seotool_savetaginfo");
    }

    public function savetagAction() {

        $tag_params = $this->_request->getParams();
        $articleId = $tag_params['ao_list'];
        $clientId = $tag_params['client_list'];
        $clientUrl = $tag_params['url'];
        $clientTag = addslashes($tag_params['tag']);
        $expiry = $tag_params['validate_till'];
        $incharge_email = $this->adminLogin->loginEmail;

        require_once SEO_SFTP_FILE;
        try {
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_TAG_EXEC);
            $ruby_file = SEO_TAG_SAVE_RB;
            
            $cmd = "ruby -W0 $ruby_file $articleId $clientId $clientUrl \"$clientTag\" \"$expiry\" $incharge_email";
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ; cd $file_exec_path; $cmd ;");
            $response = $this->responseMsg(1, 8);
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        print json_encode($response);
        exit ;
    }

    public function longtailkwsAction() {
        $longtailkw_params = $this->_request->getParams();
        /*if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($longtailkw_params['file']) && isset($longtailkw_params['ext']))
            $this->_redirect(BO_PATH_ . 'download_seoresult.php?ext=' . $longtailkw_params['ext'] . '&filename=' . $longtailkw_params['file'] . '&tool=longtailkws');*/

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($longtailkw_params['file']) && isset($longtailkw_params['ext'])) {
            $filename = $longtailkw_params['file'] . "." . $longtailkw_params['ext'];
            $path_file = SEO_DOWNLOAD_LKWS . $filename;

            if (file_exists($path_file)) {
                $data = $this->getCSV($path_file);
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'spl') ;
                /*$table = SEO_TBL_TG;
                $i = 0;
                foreach ($data as $row) {
                    if ($i == 0){$table .= '<thead>';}
                    $table .= '<tr>';
                    foreach ($row as $td) {
                        if ($this->os != 'Windows') {
                            if ($i == 0)
                                $table .= '<th>' . utf8_decode($td) . '</th>';
                            else
                                $table .= '<td>' . utf8_decode($td) . '</td>';
                        } else {
                            if ($i == 0)
                                $table .= '<th>' . ($td) . '</th>';
                            else
                                $table .= '<td>' . ($td) . '</td>';
                        }
                    }
                    $table .= '</tr>';
                    if ($i == 0){$table .= '</thead><tbody>';}
                    $i++;
                }
                $table .= '</tbody>' . SEO_TBL_TG_;*/
            }
            //exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $longtailkw_params['word_type'];
            $this->render("seotool_view");
        } else {

            if (@$longtailkw_params['class'])
                $this->_view->class = $longtailkw_params['class'];

            $_POST['word_type'] = 1;
            $this->_view->word_type = $longtailkw_params['word_type'];

            if (@$msg)
                $this->_view->msg = $msg;
            $this->render("seotool_longtailkws");
        }
    }

    public function longtailkwuploadAction() {
        $pos_params = $this->_request->getParams();
        if (isset($pos_params['submit'])) {
            
            $response = $this->responseMsg('', 0, 1, '', '');
            $word_type = $pos_params['word_type'];
            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;
            $this->output_type = $pos_params['op_type'];
            $this->site_id = $pos_params['site'];
            $this->limit = $pos_params['limit'];

            if ($word_type == 2) {
                $kw_text = trim($pos_params['kw']);
                if (($this->os == 'Windows'))
                    $kw_text = utf8_decode($kw_text);

                if ($kw_text) {
                    $kw_text1 = explode("\n", $kw_text);
                    $csv_file_name = "csv_" . time() . ".csv";
                    $srcFile = SEO_UPLOAD_LKWS . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, str_replace("\'", "'", $kw_text));
                    fclose($fp);

                    $frequency = $this->checkFrequency();
                    if ($frequency == 'process') {
                        $response = $this->longtailkwuploadAndProcess($srcFile, $csv_file_name);
                    } else {
                        $response = $this->responseMsg(0, 0, 0, $frequency);
                    }
                    $response['word_type'] = $word_type;
                } else {
                    $response = $this->responseMsg(0, 16, $word_type);
                }
            } else if ($word_type == 1) {
                if (($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];

                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_LKWS . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['keyword_file']['name']);
                    }

                    $frequency = $this->checkFrequency();
                    if ($frequency == 'process') {
                        $response = $this->longtailkwuploadAndProcess($srcFile, $u_file_name);
                    } else {
                        $response = $this->responseMsg(0, 0, 0, $frequency);
                    }
                } else {
                    $response = $this->responseMsg(0, 1);
                }
                $response['word_type'] = $word_type;
            }
            print json_encode($response);
            exit ;
        }

    }

    function longtailkwuploadAndProcess($srcFile, $u_file_name) {
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_LONG_KW_EXEC);
            $file_upload_path = $sftp->exec(SEO_GNEWS_UPLOAD);
            $file_download_path = $sftp->exec(SEO_GNEWS_DOWNLOAD);
            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);
            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . "." . $src['extension'];
            $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');
            $limitt = $this->limit;
            $site_idd = $this->site_id;
            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;
            $ruby_file = SEO_LONG_KW_RB;
            
            $cmd = "ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$encoding\" 1 $userId $loginName ";
            //$sftp->setTimeout(300);
            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
            $remoteFile = trim($file_download_path) . "/" . $dstfile;
            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_LKWS . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];
            $sftp->get($dstfile, $localFile);

            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                $csv_data = $this->getCSV($localFile);
                if ($this->output_type == 2) {
                    $ext = "xls";
                    $output_file = SEO_DOWNLOAD_LKWS . $fname . "." . $ext;

                    $this->WriteXLS($csv_data, $output_file);
                }
                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . $ext . '&filename=' . $fname . '&tool=longtailkws', SEO_DOWN_OP_FILE, 'longtailkws?action=view&file=' . $fname . $typeParam . '&ext=csv', SEO_VIEW_RESULTS);//.' cmd='.$cmd

            } else {
                throw new Exception($output);
            }
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    //web keyworb scraper
    public function scraperAction() {
        ini_set("display_errors", 0);
        $scrape_params = $this->_request->getParams();

        if (@$scrape_params['class'])
            $this->_view->class = $scrape_params['class'];
        if (@$msg)
            $this->_view->msg = $msg;

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($scrape_params['file']) && isset($scrape_params['ext'])) {
            $filename = $scrape_params['file'] . "." . $scrape_params['ext'];
            $path_file = SEO_DOWNLOAD_SCRAPER . $filename;

            if (file_exists($path_file)) {
                $data = $this->readXLS($path_file);
                
                $sheetcount = 1;
                //$table = $this->viewResultsGrid($this->readXLS($path_file), 'spl') ;
                foreach ($data as $sheets) {
                    $i = 0;if($_REQUEST['debug']){echo '<pre>'; print_r($sheets);}
                    $cols = sizeof(max($sheets)) ;

                    foreach ($sheets as $key=>$row) {
                        if(sizeof($row)<$cols){
                            $sheets[$key] = array_merge($sheets[$key], array_fill((sizeof($row)-1), ($cols-(sizeof($row))), ''));
                        }
                    }if($_REQUEST['debug']){echo '<pre>'; print_r($sheets); exit;}
                    $table .= '<table class="table table-striped table-bordered dTableR" id="smpl_tbl'.$sheetcount.'">';
                    //$table .= SEO_TBL_TG;
                    foreach ($sheets as $row) {
                        $table .= ($i==0 ? '<thead>' : ($i==1 ? '<tbody>' : '')).($i==0 ? '' : '<tr>');
                        foreach ($row as $td) {
                            if ($this->os != 'Windows') {
                                if ($i == 0)
                                    $table .= '<th>' . utf8_decode($td) . '</th>';
                                else
                                    $table .= '<td>' . utf8_decode($td) . '</td>';
                            } else {
                                if ($i == 0)
                                    $table .= '<th>' . ($td) . '</th>';
                                else
                                    $table .= '<td>' . ($td) . '</td>';
                            }
                        }
                        $table .= ($i==0 ? '</thead>' : ($i==0 ? '' : '</tr>'));//exit($table);
                        $i++;
                    }
                    $table .= '</tbody>';
                    $table .= SEO_TBL_TG_;
                    $sheetcount++;
                }
                //$table .= "<br><br>";
            }
//exit($table);
            $this->_view->sheet_count = range(1,$sheetcount-1);
            $this->_view->table = $table;
            $this->_view->word_type = $scrape_params['word_type'];
            $this->render("seotool_view");

        } elseif ($this->_request->isPost()) {

            $url = $scrape_params['url'];
            if ($scrape_params['crawl_type'])
                $crawl_type = $scrape_params['crawl_type'];
            else
                $crawl_type = 1;
            $scrape = new Ep_Scraper_Scraper($url, $crawl_type);
            $result = $scrape->getResult();
            $brokenURLs = $scrape->brokenURLs();
            $this->_view->result = $result;
            $this->_view->url = $url;
            $this->_view->crawl_type = $crawl_type;
            $this->render('keyword_scraper');
        } else
            $this->render(!$scrape_params['linode'] ? 'linode_keyword_scraper' : 'keyword_scraper');
    }

    //web keyworb scraper
    public function keywordscraperAction() {
        $response = $this->responseMsg(0, 0, 1, '');

        $scrape_params = $this->_request->getParams();
        $loginName = $this->adminLogin->loginName;
        $userId = $this->adminLogin->userId;
        $word_type = $scrape_params['word_type'];
        $this->output_type = $scrape_params['op_type'];

        if ($word_type == 2) {
            $urls = explode("\n", trim($scrape_params['url']));
            $urls = array_map('trim', $urls);
            $url = implode("|", $urls);
        } else if ($word_type == 1) {

            if (($_FILES['url_file']['type'] == 'text/comma-separated-values') || ($_FILES['url_file']['type'] == 'text/csv') || ($_FILES['url_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['url_file']['type'] == 'application/x-msexcel') || ($_FILES['url_file']['type'] == 'application/xls')) {
                require_once SEO_XLS_READER;

                $file_info = pathinfo($_FILES['url_file']['name']);
                $extension = $file_info['extension'];

                if ($extension == 'xls') {
                    $urls_array = $this->readInXLS($_FILES['url_file']['tmp_name']);
                } else {
                    $urls_array = $this->getCSV($_FILES['url_file']['tmp_name']);
                }

                foreach ($urls_array as $row) :
                    foreach ($row as $value) :
                        $urlArr[] = $value;
                    endforeach;
                endforeach;

                $urlArr = array_map('trim', $urlArr);
                $url = implode("|", array_filter($urlArr));
            } else {
                $response = $this->responseMsg(0, 1, $word_type);
            }
        }

        $crawltype = $scrape_params['crawl_type'];
        $contentType = $scrape_params['content_type'];
        $exectime = (int)trim($scrape_params['exectime']);
        $exectimelimit = $scrape_params['exectimelimit'];
        $crawlcount = (int)trim($scrape_params['crawlcount']);

        if ($contentType[0] && $contentType[1]) :
            $crawlContentType = 'both';
        else :
            $crawlContentType = $contentType[0];
        endif ;

        if (empty($url)) {
            $response = $this->responseMsg(0, 9);
        } elseif (!$crawltype) {
            $response = $this->responseMsg(0, 10);
        } elseif (!$crawlContentType) {
            $response = $this->responseMsg(0, 11);
        } else {
            try {
                require_once SEO_XLS_READER;
                require_once SEO_SFTP_FILE;

                $sftp = new Net_SFTP($this->ssh2_server);
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed');
                }

                //Path to execute ruby command
                $file_exec_path = trim($sftp->exec(SEO_SCRAPER_EXEC));
                $csv_file = "results_" . time();
                $csv_file_name = $csv_file . ".xls";
                $ruby_file = SEO_SCRAPER_RB;
                
                $cmd = "ruby -W0 $ruby_file $userId $loginName $csv_file_name '$url' $crawltype '$crawlContentType' $crawlcount $exectime '$exectimelimit'";

                $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                $file_download_path = $sftp->exec(SEO_SCRAPER_DOWNLOAD);

                $remoteFile = trim($file_download_path) . "/" . $csv_file_name;
                $sftp->chdir(trim($file_download_path));
                $localFile = SEO_DOWNLOAD_SCRAPER . $csv_file_name;
                $sftp->get($remoteFile, $localFile);

                if (file_exists($localFile)) {
                    $xls_array = $this->readInXLS($localFile);
                    $this->writeCSV($xls_array, SEO_DOWNLOAD_SCRAPER . $csv_file . ".csv");

                    $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG2, BO_DOMAIN_ . BO_PATH_ . "download_seo.php?saction=download&amp;file=" . $csv_file . "&ext=" . (($this->output_type != 2) ? 'csv' : 'xls'), SEO_DOWN_OP_FILE, 'scraper?action=view&file=' . $csv_file . '&ext=xls', SEO_VIEW_RESULTS);
                    
                } else {
                    throw new Exception($output);
                }

            } catch(Exception $e) {
                $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
            }
        }
        print json_encode($response);
        exit ;
    }

    public function downloadScraperAction() {
        $scraper_params = $this->_request->getParams();
        if (isset($scraper_params['saction']) && $scraper_params['saction'] == 'download' && isset($scraper_params['file']))
            $this->scraperdownloadFile($scraper_params['file'], "xls");
        exit ;
    }

    function scraperdownloadFile($filename, $extension) {

        $filename = $filename . "." . $extension;
        $path_file = SEO_DOWNLOAD_SCRAPER . $filename;
        if (file_exists($path_file)) {
            $attachment = new Ep_Message_Attachment();
            $attachment->downloadAttachment($path_file, 'attachment', $filename);
            exit ;
        } else {
            $class = "error";
            $msg = "File not Exist";
        }
        exit ;
    }

    public function stopWordsAction() {
        $blackList = new Ep_Scraper_Blacklist();
        $stopwords = $blackList->stopwords();

        $stopwords_params = $this->_request->getParams();

        if ($stopwords_params['saction'] == 'add' && $stopwords_params['filter']) {
            $blackList->addstopword($stopwords_params['filter']);
            exit ;
        } else if ($stopwords_params['saction'] == 'remove' && $stopwords_params['filter']) {

            $blackList->removestopword();
            exit ;
        } else {
            $stopwords = $blackList->stopwords();
        }

        $this->_view->stopwords = $stopwords;
        $this->render('stopwords_scraper');
    }

    public function brokenUrlAction() {
        ini_set("display_errors", 1);
        $broken_params = $this->_request->getParams();

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($broken_params['file']) && isset($broken_params['ext'])) {
            $filename = $broken_params['file'] . "." . $broken_params['ext'];
            $path_file = SEO_DOWNLOAD_SCRAPER . $filename;

            if (file_exists($path_file)) {
                $data = $this->getCSV($path_file);
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'spl') ;
                /*$table = SEO_TBL_TG;

                $i = 0;
                foreach ($data as $row) {
                    if ($i == 0){$table .= '<thead>';}
                    $table .= '<tr>';
                    foreach ($row as $td) {

                        if ($this->os != 'Windows') {
                            if ($i == 0)
                                $table .= '<th>' . utf8_decode($td) . '</th>';
                            else
                                $table .= '<td>' . utf8_decode($td) . '</td>';
                        } else {
                            if ($i == 0)
                                $table .= '<th>' . ($td) . '</th>';
                            else
                                $table .= '<td>' . ($td) . '</td>';

                        }
                    }
                    $table .= '</tr>';
                    if ($i == 0){$table .= '</thead><tbody>';}
                    $i++;
                }

                $table .= '</tbody>' . SEO_TBL_TG_;*/
            }
//exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $broken_params['word_type'];
            $this->render("seotool_view");
        } elseif ($this->_request->isPost()) {
            $response = $this->responseMsg('', 0, 0, '', '');
            $url = $broken_params['url'];
            
            if ($url) {
                if ($broken_params['crawl_type'])
                    $crawl_type = $broken_params['crawl_type'];
                else
                    $crawl_type = 1;
                $scrape = new Ep_Scraper_Broken($url, $crawl_type);
                $result = $scrape->getResult();
                $message = $scrape->getMessage();
                $response = $this->responseMsg(1, 0, 0, $message, $result);
            } else {
                $response = $this->responseMsg(0, 23);
            }
            print json_encode($response);
            exit ;
        } else
            $this->render($broken_params['linode'] ? 'linode_broken_url_finder' : 'broken_url_finder');
    }

    //broken url
    public function findbrokenurlAction() {
        $broken_params = $this->_request->getParams();
        $loginName = $this->adminLogin->loginName;
        $userId = $this->adminLogin->userId;

        $url = trim($broken_params['url']);
        $crawltype = $broken_params['crawl_type'];

        if (empty($url)) {
            $response = $this->responseMsg(0, 9);
        } elseif (!$crawltype) {
            $response = $this->responseMsg(0, 10);
        } else {
            try {
                require_once SEO_XLS_READER;
                require_once SEO_SFTP_FILE;

                $sftp = new Net_SFTP($this->ssh2_server);
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed');
                }

                //Path to execute ruby command
                $file_exec_path = trim($sftp->exec(SEO_BROKEN_URLS_EXEC));
                $csv_file = "results_broken_" . time();
                $csv_file_name = $csv_file . ".csv";
                $ruby_file = SEO_BROKEN_URLS_RB;

                $cmd = "ruby -W0 $ruby_file $loginName $userId $csv_file_name '$url' $crawltype ";

                $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                $file_download_path = $sftp->exec(SEO_BROKEN_URLS_DOWNLOAD);
                $remoteFile = trim($file_download_path) . "/" . $csv_file_name;
                $sftp->chdir(trim($file_download_path));
                $localFile = SEO_DOWNLOAD_SCRAPER . $csv_file_name;
                $sftp->get($remoteFile, $localFile);
                $csv_data = $this->getCSV($localFile);
                $output_file = SEO_DOWNLOAD_SCRAPER . $csv_file . ".xls";
                $this->WriteXLS($csv_data, $output_file);

                if (file_exists($localFile)) {
                    $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG2, "download_seo.php?saction=download&amp;file=" . $csv_file . "&ext=xls", SEO_DOWN_OP_FILE, 'broken-url?action=view&file=' . $csv_file . '&ext=csv', SEO_VIEW_RESULTS);
                } else {
                    throw new Exception($output);
                }

            } catch(Exception $e) {
                $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
            }
        }
        print json_encode($response);
        exit ;
    }

    public function orphanUrlAction() {
        $orphan_params = $this->_request->getParams();
        $loginName = $this->adminLogin->loginName;
        $userId = $this->adminLogin->userId;

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($orphan_params['file']) && isset($orphan_params['ext'])) {
            $filename = $orphan_params['file'] . "." . $orphan_params['ext'];
            $path_file = SEO_DOWNLOAD_SCRAPER . $filename;

            if (file_exists($path_file)) {
                $data = $this->getCSV($path_file);
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'spl') ;
                /*$table = SEO_TBL_TG;

                $i = 0;
                foreach ($data as $row) {
                    $table .= '<tr>';
                    foreach ($row as $td) {

                        if ($this->os != 'Windows') {
                            if ($i == 0)
                                $table .= '<th>' . utf8_decode($td) . '</th>';
                            else
                                $table .= '<td>' . utf8_decode($td) . '</td>';
                        } else {
                            if ($i == 0)
                                $table .= '<th>' . ($td) . '</th>';
                            else
                                $table .= '<td>' . ($td) . '</td>';

                        }
                    }
                    $table .= '</tr>';
                    $i++;
                }

                $table .= SEO_TBL_TG_;*/
            }
//exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $orphan_params['word_type'];
            $this->render("seotool_view");
        } elseif ($this->_request->isPost()) {
            $response = $this->responseMsg('', 0, 0, '', '');

            $url = $orphan_params['url'];

            if ($url) {
                if ($orphan_params['crawl_type'])
                    $crawl_type = $orphan_params['crawl_type'];
                else
                    $crawl_type = 1;

                $url_array = explode("\n", ($orphan_params['url']));

                $orphan = new Ep_Scraper_Orphan($url_array, $crawl_type);
                $result = $orphan->getResult();
                $message = $orphan->getMessage();
                $response = $this->responseMsg(1, 0, 0, $message, $result);
            } else {
                $response = $this->responseMsg(0, 23);
            }//echo '<pre>';print_r($response);exit;
            print json_encode($response);
            exit ;
        } else
            $this->render($orphan_params['linode'] ? 'linode_orphan_url_finder' : 'orphan_url_finder');
    }

    //orphan url
    public function findorphanurlAction() {
        //ini_set("display_errors",0) ;
        $orphan_params = $this->_request->getParams();

        $url = str_replace("\n", "' '", trim($orphan_params['url']));
        if ($orphan_params['crawl_type'])
            $crawltype = $orphan_params['crawl_type'];
        else
            $crawltype = 1;

        if (empty($url)) {
            $response = $this->responseMsg(0, 9);
        } else {
            try {
                require_once SEO_XLS_READER;
                require_once SEO_SFTP_FILE;

                $sftp = new Net_SFTP($this->ssh2_server);
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed');
                }

                //Path to execute ruby command
                $file_exec_path = trim($sftp->exec(SEO_ORPHAN_URLS_EXEC));
                $csv_file = "results_orphan_" . time();
                $csv_file_name = $csv_file . ".csv";
                $ruby_file = SEO_ORPHAN_URLS_RB;

                $cmd = "ruby -W0 $ruby_file $loginName $userId $csv_file_name '$url' ";
                
                $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                $file_download_path = $sftp->exec(SEO_ORPHAN_URLS_DOWNLOAD);
                $remoteFile = trim($file_download_path) . "/" . $csv_file_name;
                $sftp->chdir(trim($file_download_path));
                $localFile = SEO_DOWNLOAD_SCRAPER . $csv_file_name;
                $sftp->get($remoteFile, $localFile);
                $csv_data = $this->getCSV($localFile);
                $output_file = SEO_DOWNLOAD_SCRAPER . $csv_file . ".xls";
                $this->WriteXLS($csv_data, $output_file);

                if (file_exists($localFile)) {
                    $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG2, "download_seo.php?saction=download&file=" . $csv_file . "&ext=xls", SEO_DOWN_OP_FILE, 'orphan-url?action=view&file=' . $csv_file . '&ext=csv', SEO_VIEW_RESULTS);
                } else {
                    throw new Exception($output);
                }

            } catch(Exception $e) {
                $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
            }
        }
        print json_encode($response);
        exit ;
    }

    public function compareLinksAction() {
        $link_params = $this->_request->getParams();
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($link_params['file']) && isset($link_params['ext']))
            $this->linksdownloadFile($link_params['file'], $link_params['ext']);

        if (@$link_params['class'])
            $this->_view->class = $link_params['class'];
        if (@$msg)
            $this->_view->msg = $msg;
        $this->render('comparelinks');
    }

    public function validatelinksAction() {
        $pos_params = $this->_request->getParams();
        if (isset($pos_params['submit'])) {

            $response = $this->responseMsg('', 0, 0, '', '');
            require_once SEO_SFTP_FILE;

            $this->url_text = trim($pos_params['url_text']);
            $this->comp_url_text = trim($pos_params['comp_url_text']);

            if ($this->url_text && $this->comp_url_text) {
                $url_text = "'" . $pos_params['url_text'] . "'";
                $comp_urls = explode("\n", $this->comp_url_text);
		$comp_urls = array_map('trim', $comp_urls);
                $comp_url_cmd = "'" . implode("' '", $comp_urls) . "'";
                $csv_file_name = "links_" . time() . ".xls";

                try {
                    $sftp = new Net_SFTP($this->ssh2_server);
                    if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                        throw new Exception('Login Failed');
                    }

                    //Path to execute ruby command
                    $file_exec_path = trim($sftp->exec(SEO_LINKS_EXEC));
                    $ruby_file = SEO_LINKS_RB;
                    $cmd = "ruby -W0 $ruby_file $csv_file_name $url_text $comp_url_cmd ";
		    $cmd = str_replace('\/', '/', $cmd);//exit($cmd);
                    $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                    $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                    $file_download_path = $sftp->exec(SEO_LINKS_DOWNLOAD);
                    $remoteFile = trim($file_download_path) . "/" . $csv_file_name;
                    $sftp->chdir(trim($file_download_path));
                    $file_path = pathinfo($remoteFile);
                    $localFile = SEO_DOWNLOAD_LINKS . $csv_file_name;
                    $serverfile = $file_path;
                    $fname = $file_path['filename'];
                    $ext = $file_path['extension'];
                    $sftp->get($remoteFile, $localFile);

                    if (file_exists($localFile)) {
                        $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . $ext . '&filename=' . $fname . '&tool=links', SEO_DOWN_OP_FILE);
                        
                    } else {
                        //throw new Exception($output);
$response = $this->responseMsg(0, 0, 0, 'cmd=' . $cmd);
                    }

                } catch(Exception $e) {
                    $response = $this->responseMsg(0, 0, 0, ($e->getMessage() . $cmd));
                }
            } else {
                $response = $this->responseMsg(0, 24, $word_type);
            }
            print json_encode($response);
            exit ;
        }
    }

    public function googleurlAction() {
        $googleurl_params = $this->_request->getParams();
        if (@$googleurl_params['class'])
            $this->_view->class = $googleurl_params['class'];
        if (@$msg)
            $this->_view->msg = $msg;
        $this->render('updategoogleurl');
    }

    public function updategoogleurlAction() {
        $googleurl_params = $this->_request->getParams();
        if (isset($googleurl_params['submit'])) {
            $response = $this->responseMsg('', 0, 0, '', '');
            require_once SEO_SFTP_FILE;
            $url = trim($googleurl_params['url_text']);

            if (!empty($url)) {
                try {
                    /**creating ssh component object**/
                    $sftp = new Net_SFTP($this->ssh2_server);
                    if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                        throw new Exception('Login Failed');
                    }

                    //Path to execute ruby command$ruby_switch_prefix ;
                    $file_exec_path = $sftp->exec(SEO_UPDATE_GOOGLE_URL_EXEC);
                    $country = array('1' => 'france', '2' => 'general', '3' => 'portuguese', '4' => 'india', '5' => 'united kingdom');
                    $tool = $googleurl_params['tool'];
                    $site = $googleurl_params['site'];
                    $country_name = $country[$site];
                    $ruby_file = SEO_UPDATE_GOOGLE_URL_RB;
                    $cmd = "ruby -W0 $ruby_file $tool $site $country_name \"$url\"";
                    $sftp->setTimeout(300);
                    $file_exec_path = trim($file_exec_path);
                    $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                    $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                    $response = $this->responseMsg(1, 12);

                } catch(Exception $e) {
                    $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
                }
            } else {
                $response = $this->responseMsg(0, 25);
            }
        }

        print json_encode($response);
        exit ;
    }

    public function validateclientspageAction() {
        $tag_params = $this->_request->getParams();
        if (@$tag_params['class'])
            $this->_view->class = $tag_params['class'];
        if (@$msg)
            $this->_view->msg = $msg;
        $this->render('validateclientspage');
    }

    public function tagscriptAction() {
        $tag_params = $this->_request->getParams();

        if (isset($tag_params['submit'])) {
            require_once SEO_SFTP_FILE;

            $url = trim($tag_params['url_text']);
            $tag = $tag_params['tag_text'];
            $tag = trim(str_replace('\"', '"', $tag));
            $email = trim($tag_params['email']);

            if (!empty($url) && !empty($tag)) {
                /**creating ssh component object**/
                $sftp = new Net_SFTP($this->ssh2_server);
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed');
                }

                //Path to execute ruby command
                $file_exec_path = $sftp->exec(SEO_TAG_EXEC);
                $file_exec_path = trim($file_exec_path);
                $sftp->setTimeout(300);
                $ruby_file = SEO_TAG_RB;
                $cmd = "ruby -W0 $ruby_file '$url' '$tag' '$email'";
                $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                $out_put = trim(str_replace(SEO_RVM_NOTATION, '', $sftp->exec("$ruby_switch_prefix;cd $file_exec_path;$cmd;")));

                if ($out_put) :
                    $response = $this->responseMsg(1, 13);
                else :
                    $response = $this->responseMsg(0, 14);
                endif ;
            } else {
                $response = $this->responseMsg(0, 15);
            }
        }
        print json_encode($response);
        exit ;
    }

    public function fbTwitterLikeShareCountAction() {
        $fbtwitter_params = $this->_request->getParams();
        
        /*if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($fbtwitter_params['file']) && isset($fbtwitter_params['ext']))
            $this->_redirect(BO_PATH_ . 'download_seoresult.php?ext=' . $fbtwitter_params['ext'] . '&filename=' . $fbtwitter_params['file'] . '&tool=fbtwitter');*/

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($fbtwitter_params['file']) && isset($fbtwitter_params['ext'])) {
            $filename = $fbtwitter_params['file'] . "." . $fbtwitter_params['ext'];
            $path_file = SEO_DOWNLOAD_FBTWITTER . $filename;

            if (file_exists($path_file)) {
                $data = $this->getCSV($path_file);
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'spl') ;
                /*$table = SEO_TBL_TG;
                $i = 0;
                foreach ($data as $row) {
                    $table .= '<tr>';
                    foreach ($row as $td) {

                        if ($this->os != 'Windows') {
                            if ($i == 0)
                                $table .= '<th>' . utf8_decode($td) . '</th>';
                            else
                                $table .= '<td>' . utf8_decode($td) . '</td>';
                        } else {
                            if ($i == 0)
                                $table .= '<th>' . ($td) . '</th>';
                            else
                                $table .= '<td>' . ($td) . '</td>';
                        }
                    }
                    $table .= '</tr>';
                    $i++;
                }

                $table .= SEO_TBL_TG_;*/
            }
//exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $fbtwitter_params['word_type'];
            $this->render("seotool_view");
        } else {

            if (@$fbtwitter_params['class'])
                $this->_view->class = $fbtwitter_params['class'];

            $_POST['word_type'] = 1;
            $this->_view->word_type = $fbtwitter_params['word_type'];

            if (@$msg)
                $this->_view->msg = $msg;

            $this->render("fbtwitterlikesharecount");
        }
    }

    public function fbTwitterAction() {
        $pos_params = $this->_request->getParams();
        if (isset($pos_params['submit'])) {
            $response = $this->responseMsg('', 0, 1, '', '');

            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;
            
            $word_type = $pos_params['word_type'];
            $this->output_type = $pos_params['op_type'];
            if ($word_type == 2) {
                $kw_text = trim($pos_params['kw']);
                if (($this->os == 'Windows'))
                    $kw_text = utf8_decode($kw_text);

                if ($kw_text) {
                    $kw_text1 = explode("\n", $kw_text);
                    $csv_file_name = "fb" . time() . ".csv";
                    $srcFile = SEO_UPLOAD_FBTWITTER . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, str_replace("\'", "'", $kw_text));
                    fclose($fp);

                    $response = $this->fbTwitterProcess($srcFile, $csv_file_name);
                    $response['word_type'] = $word_type;
                } else {
                    $response = $this->responseMsg(0, 16, $word_type);
                }
            } else if ($word_type == 1) {
                if (($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];

                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_FBTWITTER . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['keyword_file']['name']);
                    }

                    $response = $this->fbTwitterProcess($srcFile, $u_file_name);
                    $response['word_type'] = $word_type;
                } else {
                    $response = $this->responseMsg(0, 1, $word_type);
                }
            }

            print json_encode($response);
            exit ;
        }
    }

    function fbTwitterProcess($srcFile, $u_file_name) {
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec(SEO_FB_TWITTER_EXEC);
            $file_upload_path = $sftp->exec(SEO_FB_TWITTER_UPLOAD);
            $file_download_path = $sftp->exec(SEO_FB_TWITTER_DOWNLOAD);

            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);
            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . "." . $src['extension'];
            $encoding = 'UTF-8';
            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;
            $ruby_file = SEO_FB_TWITTER_RB;

            $cmd = trim("ruby -W0 $ruby_file $u_file_name $dstfile '$encoding' $userId $loginName");

            $sftp->setTimeout(300);
            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");

            $remoteFile = trim($file_download_path) . "/" . $dstfile;

            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_FBTWITTER . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];

            //downloading the file from remote server
            $sftp->get($dstfile, $localFile);

            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                $csv_data = $this->getCSV($localFile);
                if ($this->output_type == 2) {
                    $ext = "xls";
                    $output_file = SEO_DOWNLOAD_FBTWITTER . $fname . "." . $ext;
                    $this->WriteXLS($csv_data, $output_file);
                }
                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . $ext . '&filename=' . $fname . '&tool=fbtwitter', SEO_DOWN_OP_FILE, 'fb-twitter-like-share-count?action=view&file=' . $fname . $typeParam . '&ext=csv', SEO_VIEW_RESULTS);

            } else {
                throw new Exception($output);
            }
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        return $response;
    }

    function unzip($file) {
        $zip_file = pathinfo($file);
        $zip_file['filename'] = str_replace(" ", "-", $zip_file['filename']);
        $path = $zip_file['dirname'] . "/" . $zip_file['filename'];
        if (!is_dir($path))
            mkdir($path, 0777, TRUE);

        chmod($path, 0777);

        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            // extract it to the path we determined above
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if ((substr($entry, -1) == '/') || strstr($entry, '__MACOSX'))
                    continue;

                $entry1 = frenchCharsToEnglish(str_replace(' ', '_', $entry));
                $fp = $zip->getStream($entry);
                $ofp = fopen($path . '/' . basename($entry1), 'w');
                if ($fp) {
                    while (!feof($fp))
                        fwrite($ofp, fread($fp, 8192));
                }
                fclose($fp);
                fclose($ofp);
            }
            return $path;
        } else {
            echo "Doh! I couldn't open $file";
        }
    }

    function is_empty_dir($dir) {
        if (($files = @scandir($dir)) && count($files) <= 2) {
            return true;
        }
        return false;
    }

    public function seoCompareAction() {
        $seocompare_params = $this->_request->getParams();
        
        /*if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($seocompare_params['file']) && isset($seocompare_params['ext']))
            $this->_redirect(BO_PATH_ . 'download_seoresult.php?ext=' . $seocompare_params['ext'] . '&filename=' . $seocompare_params['file'] . '&tool=seocompare');*/

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($seocompare_params['file']) && isset($seocompare_params['ext'])) {
            $filename = $seocompare_params['file'] . "." . $seocompare_params['ext'];

            $path_file = SEO_DOWNLOAD_COMP . $filename;

            if (file_exists($path_file)) {
                require_once SEO_XLS_READER;
                $data = $this->readInXLS($path_file);
                $table = $this->viewResultsGrid($this->readInXLS($path_file), 'scompare') ;
                /*$table = SEO_TBL_TG;
                $i = 0;
                foreach ($data as $row) {
                    $j = 1;
                    $table .= '<tr>';
                    foreach ($row as $td) {
                        if ($this->os != 'Windows')
                            $table .= '<td>' . htmlentities(utf8_decode($td)) . '</td>';
                        else
                            $table .= '<td>' . htmlentities(($td)) . '</td>';
                        $j++;
                    }
                    $table .= '</tr>';
                    $i++;
                }
                $table .= SEO_TBL_TG_;*/
            }
            //exit($table);
            $this->_view->table = $table;
            $this->render("seotool_view");
        } else {
            $this->render("seocompare");
        }
    }

    public function seoPositionCompareAction() {
        $seopos_params = $this->_request->getParams();
        
        /*if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'download' && isset($seopos_params['file']) && isset($seopos_params['ext']))
            $this->_redirect(BO_PATH_ . 'download_seoresult.php?ext=' . $seopos_params['ext'] . '&filename=' . $seopos_params['file'] . '&tool=seopositioncompare');*/

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($seopos_params['file']) && isset($seopos_params['ext'])) {
            $filename = $seopos_params['file'] . "." . $seopos_params['ext'];

            if (file_exists(SEO_DOWNLOAD_POS_COMP . $filename)) {
                require_once SEO_XLS_READER;
                $data = $this->readInXLS(SEO_DOWNLOAD_POS_COMP . $filename);
                //$table = $this->viewResultsGrid($this->readInXLS(SEO_DOWNLOAD_POS_COMP . $filename), 'scompare') ;
                //$table = $this->viewResultsGrid($this->readInXLS(SEO_DOWNLOAD_POS_COMP . $filename), 'scompare') ;
                /*$table = SEO_TBL_TG;
                $i = 0;
                foreach ($data as $row) {
                    $j = 1;
                    $table .= '<tr>';
                    foreach ($row as $td) {
                        if ($this->os != 'Windows')
                            $table .= '<td>' . htmlentities(utf8_decode($td)) . '</td>';
                        else
                            $table .= '<td>' . htmlentities(($td)) . '</td>';
                        $j++;
                    }
                    $table .= '</tr>';
                    $i++;
                }
                $table .= SEO_TBL_TG_;*/

            }
            //exit($table);
            $this->_view->table = $table;
            $this->render("seotool_view");
        } else {
            $this->render("seopositioncompare");
        }
    }

    public function seoCompareProcessAction() {
        $seo_compare_params = $this->_request->getParams();
        require_once SEO_SFTP_FILE;
        try {
            /**creating ssh component object**/
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            $file_exec_path = trim($sftp->exec(SEO_COMPARE_EXEC));
            $file_download_path = trim($sftp->exec(SEO_COMPARE_DOWNLOAD));
            $urls = explode("\n", $seo_compare_params['urls']);
            $urls = array_values(array_filter(array_map('trim', $urls)));
            $url_text = implode('|', $urls);

            if ((sizeof($urls) > 4) || (sizeof($urls) < 2)) {
                $response = $this->responseMsg(0, 18);
            } elseif (!empty($url_text) && (sizeof($seo_compare_params['options']) > 0)) {
                $options = implode('|', $seo_compare_params['options']);
                $op_file_name = "results_" . time() . ".xls";
                $file_path = pathinfo($op_file_name);
                if (($this->os == 'Windows'))
                    $url_text = utf8_decode($url_text);
                $loginName = $this->adminLogin->loginName;
                $userId = $this->adminLogin->userId;
                $ruby_file = SEO_COMPARE_RB;

                $cmd = trim("ruby -W0 $ruby_file $userId $loginName $op_file_name '$url_text' '$options'");
                $sftp->setTimeout(300);
                $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                $sftp->chdir(trim($file_download_path));
                $localFile = SEO_DOWNLOAD_COMP . $op_file_name;
                $sftp->get($op_file_name, $localFile);

                if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                    $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG3, BO_PATH . '/download_seoresult.php?ext=' . $file_path['extension'] . '&filename=' . $file_path['filename'] . '&tool=seocompare', SEO_DOWN_OP_FILE, 'seo-compare?action=view&file=' . $file_path['filename'] . '&ext=' . $file_path['extension'], SEO_VIEW_RESULTS);

                } else {
                    throw new Exception($output);
                }
            } else {
                $response = $this->responseMsg(0, 19);
            }

        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
        }
        print json_encode($response);
        exit ;
    }

    public function seoPositionCompareProcessAction() {
        $seo_compare_params = $this->_request->getParams();
        $word_type = $seo_compare_params['word_type'];
        $op_file = "results_" . time();
        $op_file_csv = $op_file . ".csv";
        $op_file_xls = $op_file . ".xls";
        $output_csv = SEO_UPLOAD_POS_COMP . $op_file_csv;
        $output_xls = SEO_UPLOAD_POS_COMP . $op_file_xls;
        $csv_file_path = pathinfo($output_csv);
        $xls_file_path = pathinfo($xls_file_path);

        if ($word_type == 2) {
            $kw_text = trim($seo_compare_params['kw']);

            if (!empty($kw_text)) {
                if ($this->os == 'Windows')
                    $kw_text = utf8_decode($kw_text);
                $kw_text1 = explode("\n", $kw_text);
                $fp = fopen($output_csv, 'w');
                fwrite($fp, str_replace("\'", "'", $kw_text));
                fclose($fp);
            } else {
                $response = $this->responseMsg(0, 16);
            }
        } else if ($word_type == 1) {
            $file_info = pathinfo($_FILES['keyword_file']['name']);
            $extension = $file_info['extension'];

            if ($extension == 'xls') {
                require_once SEO_XLS_READER;
                move_uploaded_file($_FILES['keyword_file']['tmp_name'], $output_xls);
                $data = $this->readInXLS($output_xls);
                $this->writeCSV($data, $output_csv);
            } elseif ($extension == 'csv') {
                move_uploaded_file($_FILES['keyword_file']['tmp_name'], $output_csv);
            } else {
                $response = $this->responseMsg(0, 1);
            }
        }

        if ($response['type'] != 'error') {
            require_once SEO_SFTP_FILE;
            try {
                /**creating ssh component object**/
                $sftp = new Net_SFTP($this->ssh2_server);
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed');
                }

                $file_exec_path = trim($sftp->exec(SEO_POSITION_COMPARE_EXEC));
                $file_download_path = trim($sftp->exec(SEO_POSITION_COMPARE_DOWNLOAD));
                $file_upload_path = trim($sftp->exec(SEO_POSITION_COMPARE_UPLOAD));
                $site = implode('|', $seo_compare_params['site']);

                if (sizeof($seo_compare_params['site']) > 0) {
                    if (($this->os == 'Windows'))
                        $url_text = utf8_decode($url_text);
                    $sftp->chdir(trim($file_upload_path));
                    $sftp->put($op_file_csv, $output_csv, NET_SFTP_LOCAL_FILE);
                    $loginName = $this->adminLogin->loginName;
                    $userId = $this->adminLogin->userId;
                    $ruby_file = SEO_POSITION_COMPARE_RB;
                    $cmd = trim("ruby -W0 $ruby_file '$site' $op_file_csv $op_file_csv 100 'UTF-8' 1 $userId $loginName");
                    $sftp->setTimeout(300);
                    $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
                    $out_put = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
                    $sftp->chdir(trim($file_download_path));
                    $sftp->get($op_file_xls, SEO_DOWNLOAD_POS_COMP . $op_file_xls);

                    if (file_exists($output_csv) && trim($out_put) == SEO_RVM_NOTATION) {
                        $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG3, BO_PATH . '/download_seoresult.php?ext=xls&filename=' . $op_file . '&tool=seopositioncompare', SEO_DOWN_OP_FILE, 'seo-position-compare?action=view&file=' . $op_file . '&ext=xls', SEO_VIEW_RESULTS);
                    } else {
                        //$response = $this->responseMsg(0, 0, 0, ($out_put . '--' . SEO_DOWNLOAD_POS_COMP . $output_csv));
                        throw new Exception($output);
                    }
                } else {
                    $response = $this->responseMsg(0, 20);
                }
            } catch(Exception $e) {
                $response = $this->responseMsg(0, 0, 0, ($e->getMessage()));
            }
        }
        print json_encode($response);
        exit ;
    }

    public function keywordXlsAction()
    {
        $kw_params = $this->_request->getParams();
        if(isset($_REQUEST['action']) && $_REQUEST['action']=='view' && isset($kw_params['file']) && isset($kw_params['ext']))
        {
            error_reporting(0);
            if($kw_params['ext'] == 'xls')
            {
                require_once SEO_XLS_READER;
                $filename=$kw_params['file'].".".$kw_params['ext'];
                $path_file=SEO_DOWNLOAD_SCRAPER . $filename;

                if(file_exists($path_file))
                {
                    $data = new Spreadsheet_Excel_Reader();
                    $data->read($path_file);
                    if($data->sheets[0]['numRows'])
                    {
                        $x=1;
                        while($x<=$data->sheets[0]['numRows']) {
                            $y=1;
                            while($y<=$data->sheets[0]['numCols']) {
                                $xls_array[$x][$y]   =   $data->sheets[0]['cells'][$x][$y] ;
                                $y++;
                            }
                            $x++;
                        }
                    }
                    $table=SEO_TBL_TG;
                    $i=0;
                    foreach($xls_array as $row)
                    {
                        $table .= ($i==0 ? '<thead>' : ($i==1 ? '<tbody>' : '<tr>')) ;
                        foreach($row as $td)
                        {
                            $table.='<td>'.utf8_decode($td).'</td>';
                        }
                        $table.= ($i==0 ? '</thead>' : '</tr>');
                        $i++;
                    }
                    $table.='</tbody>' . SEO_TBL_TG_;
                }
            }
//exit($table);
            $this->_view->table =  $table ;
            $this->_view->word_type =  $kw_params['word_type'] ;
            $this->render("seotool_view");
        }
        else
        {
            if($_REQUEST['msg']=='success' && $kw_params['file'])
            {
                /*if($kw_params['ext'] == 'xls')
                    $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seo.php?saction=download&ext=' . $kw_params['ext'] . '&file=' . $kw_params['file'], SEO_DOWN_OP_FILE, 'keyword-xls?action=view&file=' . $kw_params['file'] . '&ext=' . $kw_params['ext'], SEO_VIEW_RESULTS);
                else
                    $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seo.php?saction=download&ext=' . $kw_params['ext'] . '&file=' . $kw_params['file'] . '&tool=seopositioncompare', SEO_DOWN_OP_FILE, '', '', '');*/
                
                $this->_view->msg = "File Successfully uploaded and processed.<br>" ;
                $this->_view->msg.='<a href="/' . BO_PATH_ . 'download_seo.php?saction=download&file='.$_REQUEST['file'].'&ext='.$_GET['ext'].'">Cick here to download</a>' ;
                if($_GET['ext'] == 'xls')
                    $this->_view->msg.=' / <a target="_result" href="/seotool/keyword-xls?action=view&file='.$_REQUEST['file'].'&ext=xls">View result</a>' ;
                $this->_view->class = 'success' ;
            }
            else
            {
                $this->_view->class = '' ;
                $this->_view->msg = '' ;
            }
            $this->render('keyword_count_xls');
        }   
    }

    public function keywordXlsUploadAction()
    {
        $kw_params = $this->_request->getParams();
        if(isset($kw_params['submit']))
        {
            $response = $this->responseMsg('', 0, 0, '', '');
            require_once SEO_XLS_READER;
            $kw_text=trim($kw_params['kw']);
            if($_FILES['keyword_file']['name'])
            {
                if($kw_text)
                {
                    $reference=$kw_params['index_reference'] ;
                    $kw_text=explode("\n",($kw_text)) ;
                    $file_info=pathinfo($_FILES['keyword_file']['name']) ;
                    $extension=$file_info['extension'] ;
                    if($extension == 'xls')
                    {
                        $data = new Spreadsheet_Excel_Reader();
                        $data->read($_FILES['keyword_file']['tmp_name']);
                        if($data->sheets[0]['numRows'])
                        {
                            $x=1;
                            while($x<=$data->sheets[0]['numRows']) {
                                $y=1;
                                while($y<=$data->sheets[0]['numCols']) {
                                    $xls_array[$x][$y]   =   $data->sheets[0]['cells'][$x][$y] ;
                                    $y++;
                                }
                                $x++;
                            }
                        }
                        $xls_array=array_filter($xls_array) ;
                        $final_array=$this->processKeywordXLS($xls_array,$kw_text,$reference) ;
                        $filename="results_".time() ;
                        $filename_result = SEO_DOWNLOAD_SCRAPER . $filename . ".xls" ;
                        $this->WriteKeywordXLS(array_values($final_array),$filename_result,$filename) ;
                    }
                    elseif($extension == 'docx' || $extension == 'doc')
                    {
                        $filename  =   "results_".time() ;
                        $file = SEO_DOWNLOAD_SCRAPER.$filename.".".$extension ;
                        $txtFile = SEO_DOWNLOAD_SCRAPER.$filename.".txt" ;
                        $docFile = SEO_DOWNLOAD_SCRAPER.$filename.".doc" ;
                        $htmlFile = SEO_DOWNLOAD_SCRAPER.$filename.".html" ;
                        copy($_FILES['keyword_file']['tmp_name'], $file) ;
                        
                        if($extension == 'doc')
                            $this->o_docToTxt($file,$txtFile) ;
                        elseif($extension == 'docx')
                            $this->o_docxToTxt($file,$txtFile) ;

                        $results   =   $this->processKwWord( $txtFile, $kw_text, $extension ) ;
                        $txtFileContents = file_get_contents($txtFile);
                        if($extension == 'docx')
                            $txtFileContents = utf8_decode($txtFileContents) ;

                        $fp = fopen($txtFile, 'w+');
                        fwrite($fp, $this->convert_smart_quotes($txtFileContents.($results)));
                        fclose($fp);

                        $fp = fopen($htmlFile, 'w+');
                        fwrite($fp, HTML_UTF_HEADER);
                        fwrite($fp, utf8_encode(file_get_contents($txtFile)));
                        fclose($fp);

                        require_once SEO_HTML_TO_DOC ;

                        chmod("$htmlFile",0777);
                        $htmltodoc= new HTML_TO_DOC();
                        $htmltodoc->createDoc("$htmlFile",str_replace('.doc', '', $docFile));
                        chmod($docFile,0777);
                        header("Location:/seotool/keyword-xls?msg=success&file=".$filename."&ext=".(($extension == 'doc' || $extension == 'docx') ? 'doc' : $extension)."&submenuId=ML8-SL12");
                    }
                    elseif($extension == 'xlsx')
                    {
                        include SEO_XLSX_READER ;
                        $xlsx = new SimpleXLSX($_FILES['keyword_file']['tmp_name']);
                        //echo "<pre>";print_r($xlsx);exit;
                        for($j=1;$j <= $xlsx->sheetsCount();$j++){
                            list($cols) = $xlsx->dimension($j);
                            if(count($xlsx->rows($j))>0)
                            {
                                $row=0;
                                foreach( $xlsx->rows($j) as $k => $r) {
                                    for( $i = 0; $i < $cols; $i++) {
                                        $xlsArr[$row+1][$i+1] = ( isset($r[$i]) ? ($this->convert_smart_quotes(utf8_decode($r[$i]))) : '' ) ;
                                    }
                                    $row++;
                                }
                            }
                        }
                        $xls_array=array_filter($xlsArr) ;
                        $final_array=$this->processKeywordXLS($xls_array,$kw_text,$reference) ;
                        $filename="results_".time() ;
                        $filename_result = SEO_DOWNLOAD_SCRAPER.$filename.".xls" ;
                        $this->WriteKeywordXLS(array_values($final_array),$filename_result,$filename) ;
                    }

                    if($extension == 'xls' || $extension == 'xlsx')
                        $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seo.php?saction=download&ext=xls&file=' . $filename, SEO_DOWN_OP_FILE, 'keyword-xls?action=view&file=' . $filename . '&ext=xls', SEO_VIEW_RESULTS, '');
                    else
                        $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seo.php?saction=download&ext=doc&file=' . $filename, SEO_DOWN_OP_FILE, '', '', '');

                    print json_encode($response);
                    exit ;
                }
            }
        }
    }

    public function processKeywordXLS($xls_array,$keyword_array,$reference)
    {
        $cnt=0;
        $final_array=array();
        foreach($xls_array as $key=>$karray)
        {
            $content_words=array();
            $next_column='';
            $karray=array_values(($karray));
            $column_count=count($karray);
            //getting all keywords from content
            $final_array[$key]=$karray ;
            $ref_content=$final_array[$key][$reference-1];
            $ref_content    =   strtolower($ref_content);

            if($ref_content && $cnt>0)
            {
                foreach($keyword_array as $kword)
                {
                    $kword=($kword);
                    $kword=strtolower(trim($kword));
                    $count  =   0 ;
                    if(strstr($ref_content, " ".$kword." ") || (strstr($ref_content, substr($content, 0, strlen($ref_content))) && (substr($ref_content, 0, strlen($kword)) == $kword)) || (strstr($ref_content, substr($ref_content, (strlen($ref_content) - strlen($kword)), strlen($ref_content))) && (substr($ref_content, (strlen($ref_content) - strlen($kword)), strlen($ref_content)) == $kword)))
                    {
                        $next_column.=$kword." - ".substr_count($ref_content, $kword)."\n" ;
                    }
                    $kword1 = $kword.'s' ;
                    if(strstr($ref_content, " ".$kword1." ") || (strstr($ref_content, substr($content, 0, strlen($ref_content))) && (substr($ref_content, 0, strlen($kword1)) == $kword1)) || (strstr($ref_content, substr($ref_content, (strlen($ref_content) - strlen($kword1)), strlen($ref_content))) && (substr($ref_content, (strlen($ref_content) - strlen($kword1)), strlen($ref_content)) == $kword1)))
                    {
                            $next_column.=$kword." - ".substr_count($ref_content, $kword)."\n" ;
                    }
                }
                array_unshift($final_array[$key],$next_column);
            }
            else{
                if($cnt == 0)
                    array_unshift($final_array[$key],'Black list kw count');
            }
            $cnt++ ;
        }
        return $final_array;
    }

    public function processKwWord($txtFile, $keyword_array, $extension)
    {
        $results='<br>' ;        
        $ref_content    =   (strtolower(file_get_contents($txtFile))) ;
        if($extension== 'docx')
            $ref_content    =   utf8_decode($ref_content) ;
        
        if($ref_content)
        {
            foreach($keyword_array as $kword)
            {
                $kword=(strtolower(trim($kword)));
                if(strstr($ref_content, " ".$kword." ") || (strstr($ref_content, substr($content, 0, strlen($ref_content))) && (substr($ref_content, 0, strlen($kword)) == $kword)) || (strstr($ref_content, substr($ref_content, (strlen($ref_content) - strlen($kword)), strlen($ref_content))) && (substr($ref_content, (strlen($ref_content) - strlen($kword)), strlen($ref_content)) == $kword)))
                {
                    $results.="<br>Number of <u>".($kword)."</u> Words :: ".substr_count($ref_content, $kword)."<br>" ;
                }
                $kword1 = $kword.'s' ;
                if(strstr($ref_content, " ".$kword1." ") || (strstr($ref_content, substr($content, 0, strlen($ref_content))) && (substr($ref_content, 0, strlen($kword1)) == $kword1)) || (strstr($ref_content, substr($ref_content, (strlen($ref_content) - strlen($kword1)), strlen($ref_content))) && (substr($ref_content, (strlen($ref_content) - strlen($kword1)), strlen($ref_content)) == $kword1)))
                {
                    $results.="<br>Number of <u>".($kword)."</u> Words :: ".substr_count($ref_content, $kword)."<br>" ;
                }
            }
        }
        return $results;
    }


    /**function to create XLS file**/
    function WriteKeywordXLS($data,$file_name,$filename)
    {    
        // include package
        include SEO_XLS_WRITER_INCLUDE;

        // create empty file        
        $excel = new Spreadsheet_Excel_Writer($file_name);
        $excel->setVersion(8); 

        // add worksheet
        $sheet =& $excel->addWorksheet();
        $sheet->setInputEncoding('UTF-8');
        
        //$sheet->setInputEncoding('ISO-8859-1');
        // create format for header row
        // bold, red with black lower border
        $firstRow =& $excel->addFormat();
        $firstRow->setBold();
        $firstRow->setSize(12);
        $firstRow->setBottom(1);
        $firstRow->setBottomColor('black');

        // add data to worksheet
        $rowCount=0;
        foreach ($data as $row) {
        $sheet->setColumn(0,count($row),20);
          foreach ($row as $key => $value) {
            $sheet->write($rowCount, $key, utf8_encode($value)) ;
          }
          $rowCount++;
        }
        // save file to disk
        if ($excel->close() === true) {
            //exit("/seotool/keyword-xls?msg=success&file=".$filename."&submenuId=ML8-SL12");
          header("Location:/seotool/keyword-xls?msg=success&file=".$filename."&ext=xls&submenuId=ML8-SL15");
        }
    }

    //loading black list kws per client
    public function loadblkwsAction() 
    {   
        exit(file_get_contents(ROOT_PATH . BO_PATH_ . BLKWS_PATH . ($_REQUEST['client']).".txt")) ;
    }

    function o_docToTxt($filein, $fileout)
    {
        $doc2txt = "/usr/bin/antiword ";
        $cmd = $doc2txt." ".$filein." > ".$fileout."";
        
        $ret = 0;
        if(file_exists($filein))
        {
            $output = array();
            shell_exec($cmd);
        } 
        else 
        {
          $ret = -1;
        }
        return $ret;
    }

    function o_docxToTxt($path, $outpath)
    {
        if (!file_exists($path))
            return -1;
        $zh = zip_open($path);
        $content = "";
        while (($entry = zip_read($zh))){
            $entry_name = zip_entry_name($entry);
            if (preg_match('/word\/document\.xml/im', $entry_name)){
                $content = zip_entry_read($entry, zip_entry_filesize($entry));
                break;
            }
        }
        $text_content = "";
        if ($content){
            $xml = new XMLReader();
            $xml->XML($content);
            while($xml->read()){
                if ($xml->name == "w:t" && $xml->nodeType == XMLReader::ELEMENT){
                    $text_content .= $xml->readInnerXML();
                    $space = $xml->getAttribute("xml:space");
                    if ($space && $space == "preserve")
                        $text_content .= " ";
                }
                if (($xml->name == "w:p" || $xml->name == "w:br" || $xml->name == "w:cr") && $xml->nodeType == XMLReader::ELEMENT)
                    $text_content .= "\n";
                if (($xml->name == "w:tab") && $xml->nodeType == XMLReader::ELEMENT)
                    $text_content .= "\t";
            }
            file_put_contents($outpath, $text_content);
            return 0;
        }
        return -1;
    }

    public function pageRankAnalysisAction()
    {
        $pr_params=$this->_request->getParams();
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($pr_params['file']) && isset($pr_params['ext'])) {
            $filename = $pr_params['file'] . "." . $pr_params['ext'];
            $path_file = SEO_DOWNLOAD_PR . $filename;

            if (file_exists($path_file)) {
                header('Content-Type: text/html; charset=utf-8');
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'pagerank') ;
            }
            //exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $pr_params['word_type'];
            $this->render("seotool_view");
        } else {
            if ($pr_params['class'])
                $this->_view->class = $pr_params['class'];

            $_POST['word_type'] = 1;
            $this->_view->word_type = $pr_params['word_type'];

            $this->render('page_rank_analysis');
        }
    }

    public function keywordGeneratorAction()
    {
        $key_gen_params=$this->_request->getParams();
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($key_gen_params['file']) && isset($key_gen_params['ext'])) {
            $filename = $key_gen_params['file'] . "." . $key_gen_params['ext'];
            $path_file = SEO_DOWNLOAD_KW_GENERATOR . $filename;

            if (file_exists($path_file)) {
                header('Content-Type: text/html; charset=utf-8');
                $table = $this->viewResultsGrid($this->getCSV($path_file), 'kw_generator') ;
            }
            //exit($table);
            $this->_view->table = $table;
            $this->_view->word_type = $key_gen_params['word_type'];
            $this->render("seotool_view");
        } else {
            if ($pr_params['class'])
                $this->_view->class = $key_gen_params['class'];

            $_POST['word_type'] = 1;
            $this->_view->word_type = $key_gen_params['word_type'];

            $this->render('kw_generator');
        }
    }

    public function keywordGenerationProcessAction()
    {
        $key_gen_params=$this->_request->getParams();
        if (isset($key_gen_params['submit'])) {
            // response hash
            $response = $this->responseMsg('', 0, 1, '', '');

            $this->type = $key_gen_params['word_type'];
            $this->output_type = $key_gen_params['op_type'];
            $this->site_id = $key_gen_params['site'];
            $this->limit = $key_gen_params['limit'];

            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;

            if ($this->type == 2) {
                $kw_text = trim($key_gen_params['kw']);
                if (($this->os == 'Windows'))
                    $kw_text = utf8_decode($kw_text);

                if ($kw_text) {
                    $kw_text1 = explode("\n", $kw_text);

                    $csv_file_name = time() . ".csv";
                    $srcFile = SEO_UPLOAD_KW_GENERATOR . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kw_text);
                    fclose($fp);
                    $response = $this->keyGenUploadAndProcess($srcFile, $csv_file_name);
                    $response['word_type'] = $this->type;
                } else {
                    $response = array('type' => 'error', 'message' => 'Please enter URL(s)', 'word_type' => $this->type);
                }
            } else if ($this->type == 1) {
                if (($_FILES['keyword_file']['type'] == 'text/comma-separated-values') || ($_FILES['keyword_file']['type'] == 'text/csv') || ($_FILES['keyword_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['keyword_file']['type'] == 'application/x-msexcel') || ($_FILES['keyword_file']['type'] == 'application/xls')) {
                    $file_info = pathinfo($_FILES['keyword_file']['name']);
                    $extension = $file_info['extension'];
                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['keyword_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_KW_GENERATOR . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['keyword_file']['name']);
                    }
                    $response = $this->keyGenUploadAndProcess($srcFile, $u_file_name);
                } else {
                    $response = $this->responseMsg(0, 1);
                }
                $response['word_type'] = $this->type;
            }
            print json_encode($response);
            exit ;
        }
    }

    public function pageRankAnalysisProcessAction()
    {
        error_reporting(0);
        $pr_params=$this->_request->getParams();
        if (isset($pr_params['submit'])) {
            // response hash
            $response = $this->responseMsg('', 0, 1, '', '');
            
            $this->type = $pr_params['word_type'];
            $this->title = $pr_params['title'];
            $this->output_type = $pr_params['op_type'];
            $this->frequency_option = $pr_params['frequency'];
            $this->end_date = $pr_params['enddate'];
            $this->creation_date = date('Y-m-d H:i:s');
            $this->cron_email = $pr_params['email'];
            $days = array_flip(array_values($pr_params['day'])) ;
            foreach($days as $key=>$val) $days[$key] = 1;
            $this->days = (sizeof($pr_params['day'])>0 ? $days : '') ;

            require_once SEO_XLS_READER;
            require_once SEO_SFTP_FILE;

            if ($this->type == 2) {
                $url_text = trim($pr_params['urls']);
                if ($this->os == 'Windows')
                    $url_text = utf8_decode($url_text);

                if ($url_text) {
                    $url_text1 = explode("\n", $url_text);

                    $csv_file_name = time() . ".csv";
                    $srcFile = SEO_UPLOAD_PR . $csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $url_text);
                    fclose($fp);
                    $response = $this->prUploadAndProcess($srcFile, $csv_file_name);
                    $response['word_type'] = $this->type;
                } else {
                    $response = array('type' => 'error', 'message' => 'Please enter URL(s)', 'word_type' => $this->type);
                }
            } else if ($this->type == 1) {
                if (($_FILES['url_file']['type'] == 'text/comma-separated-values') || ($_FILES['url_file']['type'] == 'text/csv') || ($_FILES['url_file']['type'] == 'application/vnd.ms-excel') || ($_FILES['url_file']['type'] == 'application/x-msexcel') || ($_FILES['url_file']['type'] == 'application/xls')) {
                    $file_info = pathinfo($_FILES['url_file']['name']);
                    $extension = $file_info['extension'];
                    if ($extension == 'xls') {
                        $xls_array = $this->readInXLS($_FILES['url_file']['tmp_name']);
                        $u_file_name = str_replace(" ", "_", $file_info['filename']) . ".csv";
                        $srcFile = SEO_UPLOAD_PR . $u_file_name;
                        $this->writeCSV($xls_array, $srcFile);
                    } else {
                        $srcFile = $_FILES['url_file']['tmp_name'];
                        $u_file_name = str_replace(" ", "_", $_FILES['url_file']['name']);
                    }
                    $response = $this->prUploadAndProcess($srcFile, $u_file_name);
                } else {
                    $response = $this->responseMsg(0, 1);
                }
                $response['word_type'] = $this->type;
            }
            print json_encode($response);
            exit ;
        }
    }

    function prUploadAndProcess($srcFile, $u_file_name) {
        try {
            $csv_data = $this->getCSV($srcFile) ;
            $file_path = pathinfo($u_file_name);
                        
            if($this->frequency_option==1)
            {
                $frequency['frequency_file'] = $u_file_name;
                $frequency['user_id'] = $this->adminLogin->userId;
                $frequency['title'] =   $this->title ;
                $frequency['days'] =  $days  =  $this->days ;
                $frequency['end_date'] =   $this->end_date ;
                $frequency['creation_date'] =   $this->creation_date ;
                $frequency['email'] =   $this->cron_email ;
                
                if($days[strtolower(date('l'))])
                {
                    $i = 1 ;
                    foreach($csv_data as $url)
                    {
                        $data[$i][0]   =   $csv_data[$i][0];
                        $data[$i][1]   =   $this->get_google_pagerank($url[0]) ;
                        $data[$i][2]   =   date('Y-m-d H:i:s');
                        $i++ ;
                    }
                }
                
                $frequencyObj = new Ep_Seo_Frequency() ;
                $frequencyObj->insertSchedule($frequency, $data) ;
                $response = $this->responseMsg(1, 31) ;
            }
            else
            {
                $data[1][0] =   'Url';   $data[1][1] =   'Page Rank';   $i = 1 ;
                foreach($csv_data as $url)
                {
                    $data[$i+1][0]   =   $csv_data[$i][0];
                    $data[$i+1][1]   =   $this->get_google_pagerank($url[0]) ;
                    $i++ ;
                }
                $this->writeCSV($data, SEO_DOWNLOAD_PR . $u_file_name) ;
                if($this->output_type==2)
                {
                    $this->WriteXLS($data, SEO_DOWNLOAD_PR . $file_path['filename'] . ".xls") ;
                }
                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . ($this->output_type==2 ? 'xls' : 'csv') . '&filename=' . $file_path['filename'] . '&tool=pagerank', SEO_DOWN_OP_FILE, 'page-rank-analysis?action=view&file=' . $file_path['filename'] . '&ext=csv', SEO_VIEW_RESULTS) ;
            }
            
            //echo '<pre>';print_r($data);print_r($this->getCSV($srcFile));
            //exit('srcFile='.$srcFile.' -- u_file_name='.$u_file_name);
            /*$sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . ".xls" ;

            $file_exec_path = $sftp->exec(SEO_PR_EXEC);
            $file_upload_path = $sftp->exec(SEO_PR_UPLOAD);
            $file_download_path = $sftp->exec(SEO_PR_DOWNLOAD);
            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);
            $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');

            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;
            $title =   $this->title ;
            $days =   $this->days ;
            $end_date =   $this->end_date ;
            $email =   $this->cron_email ;
            $ruby_file = SEO_PR_RB;

            if($this->frequency_option==1)
                $cmd = "ruby -W0 $ruby_file $userId $loginName $u_file_name $dstfile $days 'editplace' 'test' $email $end_date \"$encoding\" 2>&1 ";
            else
                $cmd = "ruby -W0 $ruby_file $userId $loginName $u_file_name $dstfile \"$encoding\" 2>&1 ";

            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
            
            if($this->frequency_option==1)
            {
                return $this->responseMsg(1, 31);
            }
            
            $remoteFile = trim($file_download_path) . "/" . $dstfile;
            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_PR . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];
            $sftp->get($dstfile, $localFile);

            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                //if ($this->output_type == 2) {                    
                    $xls_array = $this->readInXLS(SEO_DOWNLOAD_PR . $fname . ".xls");
                    $this->writeCSV($xls_array, SEO_DOWNLOAD_PR . $fname . ".csv");
                //}

                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . ($this->output_type==2 ? 'xls' : 'csv') . '&filename=' . $fname . '&tool=pagerank', SEO_DOWN_OP_FILE, 'page-rank-analysis?action=view&file=' . $fname . '&ext=csv', SEO_VIEW_RESULTS);
            } else {
                throw new Exception($output);
            }*/
            
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage().$cmd));
        }
        return $response;
    }

    public function pageRankFrequencyProcessAction()
    {
        $pr_params=$this->_request->getParams();
        $frequencyObj = new Ep_Seo_Frequency() ;
        $schedules   =   $frequencyObj->getSchedules() ;

        foreach($schedules['e'] as $fid=>$schedules_)
        {
            $i = 0 ;
            foreach($schedules_ as $url)
            {
                $pagerank[$fid][$i]['url'] = $url->url ;
                $pagerank[$fid][$i]['pagerank'] = $this->get_google_pagerank($url->url) ;
                $pagerank[$fid][$i]['created_at'] = date('Y-m-d H:i:s');
                $i++ ;
            }
            $frequencies[] = $fid ;
        }

        $frequencyObj->updatePRstatus($pagerank) ;

        foreach($schedules['n'] as $newSchedule)
        {
            $csv_data = $this->getCSV(SEO_UPLOAD_PR . $newSchedule->frequency_file) ;
            
            $i = 1 ;
            foreach($csv_data as $url)
            {
                $data[$newSchedule->id][$i]['url'] = $csv_data[$i][0] ;
                $data[$newSchedule->id][$i]['pagerank'] = $this->get_google_pagerank($url[0]) ;
                $data[$newSchedule->id][$i]['created_at'] = date('Y-m-d H:i:s');
                $i++ ;
            }
            $frequencies[] = $newSchedule->id ;
        }
        $frequencies = array_unique($frequencies) ;
        $frequencyObj->updateNewPRstatus($data) ;
        
        //echo '<pre>#';print_r($pagerank);exit;

        foreach($frequencyObj->getSchedulesToMail($frequencies) as $schedule)
            $urls[$schedule->frequency_id][$schedule->url][date('Y-m-d', strtotime($schedule->created_at))]  =   $schedule->pagerank ;
        
        //echo '<pre>'; print_r($frequencies); print_r($urls);
        
        foreach($urls as $process_id=>$urls_)
        {
            $frequency   =   $frequencyObj->getFrequencyData($process_id) ;
            $frequencyDates   =   $frequencyObj->getFrequencyDates($process_id) ;
            $frequency_filename =   $process_id . '_' . $frequency[0]->user_id . '_' . date('Ymd', strtotime($frequency[0]->end_date)) ;
            $data[1][0] =   'Url';   $data[1][1] =   'Page Rank'; $i = 1 ;
            foreach($frequencyDates as $key=>$val)  $data[1][$key+1] =   $val;
            
            //print_r($frequencyDates);
            foreach($urls_ as $url_=>$urls_val)
            {
                $data[$i+1][0]   =   $url_;
                foreach($frequencyDates as $key1=>$val1)
                {
                    $data[$i+1][$key1+1]   =   $urls_val[$val1] ;
                }
                $i++ ;
            }
            
            //print_r($data);exit($frequency_filename);
            $this->writeCSV($data, SEO_DOWNLOAD_PR . $frequency_filename . '.csv');
            $this->WriteXLS($data, SEO_DOWNLOAD_PR . $frequency_filename . '.xls');
            
            $zip = new ZipArchive() ;
            $zip->open(SEO_DOWNLOAD_PR . $frequency_filename . '.zip',  ZIPARCHIVE::CREATE) ;
            $zip->addFile(SEO_DOWNLOAD_PR . $frequency_filename . '.csv', $frequency_filename . '.csv') ;
            $zip->addFile(SEO_DOWNLOAD_PR . $frequency_filename . '.xls', $frequency_filename . '.xls') ;
            $zip->close();
            
            $mail = new Zend_Mail();
            $mail->addHeader('Reply-To','support@edit-place.com');
            $mail->setBodyHtml('testing zend mail attachment..')
                ->setFrom('support@edit-place.com')
                ->addTo($frequency[0]->email)
                ->setSubject('PR checker results (' . $frequency[0]->title . ') on ' . date('m/d/Y'));

        //'anoopchandanathope@gmail.com'

            $at = new Zend_Mime_Part(file_get_contents(SEO_DOWNLOAD_PR . $frequency_filename . '.zip'));
            $at->type        =  'application/zip'; //Zend_Mime::MULTIPART_RELATED
            $at->filename = basename(SEO_DOWNLOAD_PR . $frequency_filename . '.zip');
            $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $at->encoding = Zend_Mime::ENCODING_BASE64;
            $mail->addAttachment($at);
            $mail->send();
        }
        
        
        //echo '<pre>'; print_r($schedules); exit(strtolower(date('l')));
    }
    
    function keyGenUploadAndProcess($srcFile, $u_file_name) {
        try {
            $sftp = new Net_SFTP($this->ssh2_server);
            if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                throw new Exception('Login Failed');
            }

            $src = pathinfo($u_file_name);
            $download_fname = $src['filename'] . "_" . time();
            $dstfile = $download_fname . ".xls" ;

            $file_exec_path = $sftp->exec(SEO_KW_GENERATOR_EXEC);
            $file_upload_path = $sftp->exec(SEO_KW_GENERATOR_UPLOAD);
            $file_download_path = $sftp->exec(SEO_KW_GENERATOR_DOWNLOAD);
            $sftp->chdir(trim($file_upload_path));
            $sftp->put($u_file_name, $srcFile, NET_SFTP_LOCAL_FILE);
            $encoding = (($this->os=='Windows') ? 'WINDOWS-1252' : 'UTF-8');

            $loginName = $this->adminLogin->loginName;
            $userId = $this->adminLogin->userId;
            $limit =   $this->limit ;
            $site_id =   $this->site_id ;
            $ruby_file = SEO_KW_GENERATOR_RB;

            $cmd = "ruby -W0 $ruby_file $loginName $userId $site_id $u_file_name $dstfile $limit \"$encoding\" 2>&1 ";

            $file_exec_path = trim($file_exec_path);
            $ruby_switch_prefix = SEO_RB_SWITCH_PREFIX;
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
            
            if($this->frequency_option==1)
            {
                return $this->responseMsg(1, 31);
            }
            
            $remoteFile = trim($file_download_path) . "/" . $dstfile;
            $sftp->chdir(trim($file_download_path));
            $file_path = pathinfo($remoteFile);
            $localFile = SEO_DOWNLOAD_KW_GENERATOR . $file_path['basename'];
            $serverfile = $file_path;
            $fname = $file_path['filename'];
            $ext = $file_path['extension'];
            $sftp->get($dstfile, $localFile);

            if (file_exists($localFile) && trim($output) == SEO_RVM_NOTATION) {
                //if ($this->output_type == 2) {                    
                    $xls_array = $this->readInXLS(SEO_DOWNLOAD_KW_GENERATOR . $fname . ".xls");
                    $this->writeCSV($xls_array, SEO_DOWNLOAD_KW_GENERATOR . $fname . ".csv");
                //}

                $response = $this->displaySuccessMsg(SEO_SUCCESS_MSG1, BO_PATH . '/download_seoresult.php?ext=' . ($this->output_type==2 ? 'xls' : 'csv') . '&filename=' . $fname . '&tool=kwgenerator', SEO_DOWN_OP_FILE, 'keyword-generator?action=view&file=' . $fname . '&ext=csv', SEO_VIEW_RESULTS);
            } else {
                throw new Exception($output);
            }
        } catch(Exception $e) {
            $response = $this->responseMsg(0, 0, 0, ($e->getMessage().$cmd));
        }
        return $response;
    }

    function responseMsg($type, $code, $word_type=0, $msg='', $data=0) {
        
        $response['type'] = (!empty($type) ? ($type ? 'success' : 'error') : '');
        if($word_type)
            $response['word_type'] = $word_type;
        if($data)
            $response['data'] = $data;
        switch($code) {
            case 1 :
                $response['message'] = 'Please upload csv or xls files.';
                break;
            case 2 :
                $response['message'] = 'Please select an option.';
                break;
            case 3 :
                $response['message'] = 'Please enter URL & keywords in box (CSV Format)';
                break;
            case 4 :
                $response['message'] = 'File read error.re-upload the file!!!';
                break;
            case 5 :
                $response['message'] = 'Please upload file having any one of these format(doc,docx,xls,xlsx,txt).';
                break;
            case 6 :
                $response['message'] = "File has been added for frequency position tracking.";
                break;
            case 7 :
                $response['message'] = "Command executed successfully.";
                break;
            case 8 :
                $response['message'] = "Validate script saved successfully.";
                break;
            case 9 :
                $response['message'] = 'Please enter url.';
                break;
            case 10 :
                $response['message'] = 'Please select crawl option.';
                break;
            case 11 :
                $response['message'] = 'Please select content type.';
                break;
            case 12 :
                $response['message'] = "Search url updated successfully.";
                break;
            case 13 :
                $response['message'] = 'Client page validated successfully.';
                break;
            case 14 :
                $response['message'] = 'Validation failed.';
                break;
            case 15 :
                $response['message'] = 'Please enter URL and tag.';
                break;
            case 16 :
                $response['message'] = 'Please enter keywords.';
                break;
            case 17 :
                $response['message'] = "File has been scheduled for position tracking.";
                break;
            case 18 :
                $response['message'] = 'Please enter a minimum of 2 and maximum of 4 urls.';
                break;
            case 19 :
                $response['message'] = 'Please enter url and select option(s).';
                break;
            case 20 :
                $response['message'] = 'Please select site(s).';
                break;
            case 21 :
                $response['message'] = 'Please enter schedule date and email';
                break;
            case 22 :
                $response['message'] = 'Client name and title are required.';
                break;
            case 23 :
                $response['message'] = 'Please enter URL(s)';
                break;
            case 24 :
                $response['message'] = 'Please enter URL & Competitor URLs';
                break;
            case 25 :
                $response['message'] = 'Please enter URL';
                break;
            case 26 :
                $response['message'] = 'Please enter Text in box';
                break;
            case 27 :
                $response['message'] = 'File read error.re-upload the file!!!';
                break;
            case 28 :
                $response['message'] = 'Please upload file having any one of these format(doc,docx,xls,xlsx,txt).';
                break;
            case 29 :
                $response['message'] = 'Please select site.';
                break;
            case 30 :
                $response['message'] = "Please select keyword csv/xls";
                break;
            case 31 :
                $response['message'] = "Frequency scheduled.";
                break;
            default :
                $response['message'] = $msg;
                break;
        }
        return $response ;
    }

    function displaySuccessMsg($msg, $downUrl, $downLabel, $viewUrl='', $viewLabel='', $data='') {
        $response['type'] = 'success';
        if($data)
            $response['data'] = $data;
        $response['message'] = "";
        if($msg)
            $response['message'] = $msg . "<br>";
        if($downUrl)
            $response['message'] .= "<a href=\"" . $downUrl . "\">" . $downLabel . "</a>";
        if($viewUrl)
            $response['message'] .= " / <a href=\"javascript:void(0);\" onclick=\"window.open('" . BO_DOMAIN_ . "seotool/".$viewUrl."', '_blank');\">" . $viewLabel . '</a>';
        
        return $response ;
    }

    public function testGoogleNewsAction() {
        $gnews_params = $this->_request->getParams();

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && isset($gnews_params['file']) && isset($gnews_params['ext'])) {
            $filename = $gnews_params['file'] . "." . $gnews_params['ext'];
            $path_file = SEO_DOWNLOAD_GNEWS . $filename;

            if (file_exists($path_file)) {
                header('Content-Type: text/html; charset=utf-8');
                $data = $this->getCSV($path_file);
                /*$table = SEO_TBL_TG. '<thead>';
                $cols = sizeof(max($data)) ;
                for($idx=0; $idx < $cols; $idx++) $table .= '<th>&nbsp;</th>' ;
                $table .= '</thead><tbody>' ;

                foreach ($data as $key=>$row) {
                    if(sizeof($row)<$cols){
                        $arr = array_fill((sizeof($row)-1), ($cols-(sizeof($row))), '');
                        $data[$key] = array_merge($data[$key], $arr);
                    }
                }
if($_REQUEST['debug']){echo '<pre>'; $max=max($data); $count=sizeof(max($data)); print_r($data);echo(sizeof(max($data)));exit($count);}
                
                $i = 0;
                foreach ($data as $row) {
                    $j = 1;
                    foreach ($row as $td) {

                        if (!mb_check_encoding($td, 'UTF-8'))
                            $td = iconv("ISO-8859-1", "UTF-8", $td);
                        
                        $td = (($j == 1) ? '<a href="' . $row[1] . '" target="_blank">' . $td . '</a>' : $td);
                        
                        $table .= (($j == 1) ? '<tr>' : '') . '<td>' . (strstr($td, ')') ? str_replace('")', '', substr($td, (strpos($td, '","') + 3))) : $td) . '</td>' . (($j == $cols) ? '</tr>' : '');

                        $j++;
                    }
                    $i++;
                }
                $table .= '</tbody>' . SEO_TBL_TG_;*/
            }
            $this->_view->table = $table;
            $this->_view->word_type = $gnews_params['word_type'];
            $this->render("seotool_view");
        }
    }

    function viewResultsGrid($data, $tool) {
        if($_REQUEST['debug']){echo '<pre>';echo(sizeof(max($data)));}
        $table = SEO_TBL_TG. '<thead>';
        $cols = sizeof(max($data)) ;

        foreach ($data as $key=>$row) {
            if(sizeof($row)<$cols){
                $data[$key] = array_merge($data[$key], array_fill((sizeof($row)-1), ($cols-(sizeof($row))), ''));
            }
        }
        
        for($idx=0; $idx < $cols; $idx++)
        {
            $td=$data[1][$idx];
            if($tool == 'gnews') {
                if (!mb_check_encoding($td, 'UTF-8'))
                    $td = iconv("ISO-8859-1", "UTF-8", $td);
                $td = (($j == 1) ? '<a href="' . $row[1] . '" target="_blank">' . $td . '</a>' : $td);
            } elseif($tool == 'spl') {
                if ($this->os != 'Windows') {
                    $td = utf8_decode($td);
                }
            } elseif($tool == 'scompare') {
                if ($this->os != 'Windows')
                    $td = htmlentities(utf8_decode($td));
                else
                    $td = htmlentities(($td));
            }
            $table .= '<th>' . $td . '</th>' ;
        }
        $table .= '</thead><tbody>' ;
        
        if($_REQUEST['debug']){ print_r($data); print_r($data[0]);echo(sizeof(max($data)));exit;}
        $i = 0;
        foreach ($data as $row) {
            $j = 1;
            foreach ($row as $td) {
                if($i>0)
                {
                    if($tool == 'gnews') {
                        if (!mb_check_encoding($td, 'UTF-8'))
                            $td = iconv("ISO-8859-1", "UTF-8", $td);
                        $td = (($j == 1) ? '<a href="' . $row[1] . '" target="_blank">' . $td . '</a>' : $td);
                    } elseif($tool == 'spl') {
                        if ($this->os != 'Windows') {
                            $td = utf8_decode($td);
                        }
                    } elseif($tool == 'scompare') {
                        if ($this->os != 'Windows')
                            $td = htmlentities(utf8_decode($td));
                        else
                            $td = htmlentities(($td));
                    }
                    $table .= (($j == 1) ? '<tr>' : '') . '<td>' . $td . '</td>' . (($j == $cols) ? '</tr>' : '');
                    //$table .= (($j == 1) ? '<tr>' : '') . '<td>' . (strstr($td, ')') ? str_replace('")', '', substr($td, (strpos($td, '","') + 3))) : $td) . '</td>' . (($j == $cols) ? '</tr>' : '');
                }
                $j++;
            }
            $i++;
        }
        
        $table .= '</tbody>' . SEO_TBL_TG_;//exit($table);
        
        return $table ;
    }

    public function get_google_pagerank($url) {
        $query="http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=".$this->CheckHash($this->HashURL($url)). "&features=Rank&q=info:".$url."&num=100&filter=0";
        @$data=file_get_contents($query);
        $pos = strpos($data, "Rank_");
        if($pos === false){} else{
            $pagerank = substr($data, $pos + 9);
            return $pagerank;
        }
    }
    
    public function StrToNum($Str, $Check, $Magic)
    {
        $Int32Unit = 4294967296; // 2^32
        $length = strlen($Str);
        for ($i = 0; $i < $length; $i++) {
            $Check *= $Magic;
            if ($Check >= $Int32Unit) {
                $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
                $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
            }
            $Check += ord($Str{$i});
        }
        return $Check;
    }
    
    public function HashURL($String)
    {
        $Check1 = $this->StrToNum($String, 0x1505, 0x21);
        $Check2 = $this->StrToNum($String, 0, 0x1003F);
        $Check1 >>= 2;
        $Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
        $Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
        $Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);
        $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
        $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
        return ($T1 | $T2);
    }
    
    public function CheckHash($Hashnum)
    {
        $CheckByte = 0;
        $Flag = 0;
        $HashStr = sprintf('%u', $Hashnum) ;
        $length = strlen($HashStr);
        for ($i = $length - 1; $i >= 0; $i --) {
            $Re = $HashStr{$i};
            if (1 === ($Flag % 2)) {
                $Re += $Re;
                $Re = (int)($Re / 10) + ($Re % 10);
            }
            $CheckByte += $Re;
            $Flag ++;
        }
        $CheckByte %= 10;
        if (0 !== $CheckByte) {
            $CheckByte = 10 - $CheckByte;
            if (1 === ($Flag % 2) ) {
                if (1 === ($CheckByte % 2)) {
                    $CheckByte += 9;
                }
                $CheckByte >>= 1;
            }
        }
        return '7'.$CheckByte.$HashStr;
    }

}
