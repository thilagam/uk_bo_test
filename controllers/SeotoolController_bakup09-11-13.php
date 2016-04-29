<?php
/**
 * PositiontoolController
 *
 * @author
 * @version
 */


require_once 'Zend/Controller/Action.php';

class SeotoolController extends Ep_Controller_Action
{
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
    var $gsuggest_excel_array ;
    var $cron_run_time ;
    var $cron_email ;

  	public function init()
    {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->sid = session_id();
        
        /*server details**/
        $this->ssh2_server = "50.116.62.9" ;
        $this->ssh2_user_name = "oboulo" ;
        $this->ssh2_user_pass = "3DitP1ace" ;
        $this->seo_upload_files = Zend_Registry::get('seo_upload_files');
    }

    public function posssh2uploadAction()
    {
        $pos_params=$this->_request->getParams();
        if(isset($pos_params['submit']))
        {
             // response hash
            $response = array('type'=>'', 'message'=>'','word_type'=>1);
            
            error_reporting(0);
            
            $word_type=$pos_params['word_type'];

            require_once APP_PATH_ROOT.'nlibrary/script/reader.php';
            require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';
            
            $this->type=$pos_params['type'];
            
            $this->option=$pos_params['option'];
            $this->domain=$pos_params['domain_type'];
            $this->competitor=$pos_params['comp_type'];
            
            $this->client=$pos_params['client'];
            $this->title=$pos_params['title'];
            $this->days=implode("|",$pos_params['day']);
            $this->end_date=$pos_params['enddate'];
            
            $this->frequency_option=$pos_params['frequency']; 
            $this->output_type=$pos_params['op_type'];
                
            $this->site_id=$pos_params['site'];
            $this->limit=$pos_params['limit'];
            
            if($pos_params['posSchedule'] == 1) :
                $this->cron_run_time = trim($pos_params['scheduleDate']) ;
                $this->cron_email = trim($pos_params['scheduleEmail']) ;
                
                if( empty($this->cron_run_time) || empty($this->cron_email) )
                    $response = array('type'=>'error', 'message'=>'Please enter schedule date and email') ;
                elseif( empty($this->client) || empty($this->title) )
                    $response = array('type'=>'error', 'message'=>'Client name and title are required.') ;
                
                if($response['type']=='error') :
                    print json_encode($response);
                    exit;
                endif ;
            endif ;

            if($word_type==2)
            {
                $kw_text= trim($pos_params['kw']) ;
                if( ($this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows'))
                    $kw_text=utf8_decode($kw_text);

                if( $kw_text && $this->type )
                {
                    $kw_text1=explode("\n",$kw_text) ;
                    $csv_file_name="csv_".time().".csv" ;
                    $srcFile=APP_PATH_ROOT."seo_upload/position/".$csv_file_name ;
                    $fp = fopen($srcFile, 'w') ;
                    fwrite($fp, str_replace("\'", "'", $kw_text)) ;
                    fclose($fp) ;                    
                    
                    $frequency=$this->checkFrequency() ;
                    
                    if($frequency=='process')
                    {
                        if($pos_params['posSchedule'] == 1)
                            $response   =   $this->posscheduleuploadAndProcess($srcFile,$csv_file_name) ;
                        else
                            $response   =   $this->posuploadAndProcess($srcFile,$csv_file_name) ;
                    }   
                    else
                    {
                        $response['type']='error';
                        $response['message']=$frequency;
                    }                    
                    $response['word_type']=$word_type;
                }       
                else
                {
                    if(!$kw_text)           
                         $response = array('type'=>'error', 'message'=>'Please enter URL&keywords in box (CSV Format)','word_type'=>$word_type);    
                    elseif(!$this->type)
                         $response = array('type'=>'error', 'message'=>'Please select an option','word_type'=>$word_type);
                }   
            }
            else if($word_type==1)
            {
                    if( (($_FILES['keyword_file']['type']=='text/comma-separated-values')||($_FILES['keyword_file']['type']=='text/csv') ||($_FILES['keyword_file']['type']=='application/vnd.ms-excel')||($_FILES['keyword_file']['type']=='application/x-msexcel') ||  ($_FILES['keyword_file']['type']=='application/xls' )) && $this->type )
                    {
                        $file_info=pathinfo($_FILES['keyword_file']['name']);
                        $extension=$file_info['extension'];
                        
                        if($extension=='xls')
                        {
                            $xls_array    =   $this->readInXLS($_FILES['keyword_file']['tmp_name']) ;
                            $u_file_name=str_replace(" ","_",$file_info['filename']).".csv" ;
                            $srcFile=APP_PATH_ROOT."seo_upload/position/".$u_file_name;
                            $this->writeCSV($xls_array,$srcFile);
                        }
                        else
                        {
                            $srcFile = $_FILES['keyword_file']['tmp_name'];
                            $u_file_name=str_replace(" ","_",$_FILES['keyword_file']['name']);
                        }   
                        
                        $frequency=$this->checkFrequency();
                    
                        if($frequency=='process')
                        {
                            if($pos_params['posSchedule'] == 1)
                                $response   =   $this->posscheduleuploadAndProcess($srcFile,$u_file_name) ;
                            else
                                $response=$this->posuploadAndProcess($srcFile,$u_file_name);
                        }   
                        else
                        {
                            $response['type']='error';
                            $response['message']=$frequency;
                        }                        
                        $response['word_type']=$word_type;
                    }
                    else
                    {
                        $response['type'] = 'error';
                        if(!$_FILES['keyword_file']['tmp_name'])
                            $response['message'] = 'Please upload csv or xls files.';
                        elseif(!$this->type)
                            $response['message'] = 'Please select an option.';
                        $response['word_type']=$word_type;
                    }
            }       
    
            print json_encode($response);
            exit;
        }
        
    }

    public function posssh2upload235Action()
    {
        $pos_params=$this->_request->getParams() ;
        //echo '<pre>';print_r($pos_params);exit;
        if(isset($pos_params['submit']))
        {
             // response hash
            $response = array('type'=>'', 'message'=>'') ;
            
            error_reporting(0) ;
            
            require_once APP_PATH_ROOT . 'nlibrary/script/reader.php' ;
            require_once APP_PATH_ROOT . 'nlibrary/script/Net/SFTP.php' ;
            
            $word_type=$pos_params['word_type'];
            $this->title=trim($pos_params['title']) ;
            $this->output_type=$pos_params['op_type'] ;
            $this->site_id=$pos_params['site'] ;
            $this->type = $pos_params['type'] ;
            $this->format = 1 ;
            $this->limit = $pos_params['limit'] ;

            if($word_type==2)
            {
                $url_text= trim($pos_params['url_text']) ;
                if($this->type == 4 || $this->type == 5)
                    $comp_url_text  = trim($pos_params['comp_url_text']) ;
            
                $kw_text= trim($pos_params['kw']) ;
                
                if( $this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows' ) {
                    $kw_text    =   utf8_decode($kw_text) ;
                    $url_text   =   utf8_decode($url_text) ;
                    if($this->type == 4 || $this->type == 5)
                        $comp_url_text  = utf8_decode($comp_url_text) ;
                }
    
                if( $kw_text && $url_text )
                {
                    $kws =   explode("\n",$kw_text) ;
                    $kwtext =   $url_text . ($comp_url_text ? (';' . $comp_url_text) : '') ;
                    foreach ($kws as $kw) {
                        $kwtext .= ';'. trim($kw) ;
                    }
    
                    $csv_file_name  =   "textArea_" . str_replace(' ', '_', $this->title) . time() . ".csv" ;
                    $srcFile    =   APP_PATH_ROOT."seo_upload/position/".$csv_file_name ;
                    $fp = fopen($srcFile, 'w') ;
                    fwrite($fp, $kwtext) ;
                    fclose($fp) ; 
                    
                    $frequency  =   $this->checkFrequency() ;
                    
                    if($frequency=='process')
                    {
                        $response=$this->posuploadAndProcess($srcFile,$csv_file_name) ;
                    }   
                    else
                    {
                        $response['type']='error';
                        $response['message']=$frequency;
                    }                    
                    $response['word_type']=$word_type;
                }       
                else
                {
                     $response = array('type'=>'error', 'message'=>'Please enter URL&keywords in box (CSV Format)','word_type'=>$word_type);
                }
            }
            else if($word_type==1)
            {
                if((($_FILES['keyword_file']['type']=='text/comma-separated-values')||($_FILES['keyword_file']['type']=='text/csv') ||($_FILES['keyword_file']['type']=='application/vnd.ms-excel')||($_FILES['keyword_file']['type']=='application/x-msexcel') ||  ($_FILES['keyword_file']['type']=='application/xls' )) && $this->type )
                {
                    $file_info=pathinfo($_FILES['keyword_file']['name']);
                    $extension=$file_info['extension'];
                    
                    if($extension=='xls')
                    {
                        $xls_array    =   $this->readInXLS($_FILES['keyword_file']['tmp_name']) ;
                        $u_file_name  =   str_replace(" ","_",$file_info['filename']) . ".csv" ;
                        $srcFile=APP_PATH_ROOT."seo_upload/position/".$u_file_name;
                        $this->writeCSV($xls_array,$srcFile) ;
                    }
                    else
                    {
                        $srcFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name=str_replace(" ","_",$_FILES['keyword_file']['name']);
                    }
                    
                    $csvArr =   array() ;
                    foreach( $this->getCSV($srcFile) as $key=>$val ) :
                        array_push($csvArr, $val[0]) ;
                    endforeach ;
                    
                    $url_text   = trim($csvArr[0]) ;
                    unset($csvArr[0]) ;
                    
                    if($this->type == 4 || $this->type == 5) :
                        $comp_url_text  = trim($csvArr[1]) ;
                        unset($csvArr[1]) ;
                    endif ;
                    
                    $kws =   array_unique($csvArr) ;
                    
                    $kwtext =   $url_text . ($comp_url_text ? (';' . $comp_url_text) : '') ;
                    foreach ($kws as $kw) {
                        $kwtext .= ';'. trim($kw) ;
                    }
                    
                    if( $this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows' ) {
                        $kwtext    =   utf8_decode($kwtext) ;
                        $url_text   =   utf8_decode($url_text) ;
                        if($this->type == 4 || $this->type == 5)
                            $comp_url_text  = utf8_decode($comp_url_text) ;
                    }
                    
                    $fp = fopen($srcFile, 'w') ;
                    fwrite($fp, $kwtext) ;
                    fclose($fp) ; 
                    
                    //echo '<pre>' ;   print_r($csvArr) ;  exit($srcFile) ;
                    
                    $frequency=$this->checkFrequency();
                
                    if($frequency=='process')
                    {
                        $response=$this->posuploadAndProcess($srcFile,$u_file_name);
                    }   
                    else
                    {
                        $response['type']='error';
                        $response['message']=$frequency;
                    }                        
                    $response['word_type']=$word_type;
                }
                else
                {
                    $response['type'] = 'error';
                    if(!$_FILES['keyword_file']['tmp_name'])
                        $response['message'] = 'Please upload csv or xls files.';
                    elseif(!$this->type)
                        $response['message'] = 'Please select an option.';
                    $response['word_type']=$word_type;
                }
            }                
        
            print json_encode($response) ;
            exit;
        }        
    }

    public function positionAction()
    {
        if(isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']) && isset($_GET['ext']))
            $this->posdownloadFile($_GET['file'],$_GET['ext']);
        
        if(isset($_GET['action']) && $_GET['action']=='view' && isset($_GET['file']) && isset($_GET['ext']))
        {
            $filename=$_GET['file'].".".$_GET['ext'];
            $path_file="seo_download/position/".$filename;

            if(file_exists($path_file))
            {
                $data   =   $this->getCSV($path_file);
                
                $table='<table id="mytable" cellspacing=0>';
                
                $i=0;
                foreach($data as $row)
                {                            
                    $table.='<tr>';
                    foreach($row as $td)
                    {
						
						if($this->getOS($_SERVER['HTTP_USER_AGENT']) != 'Windows' )                  
						{
							if($i==0)
								$table.='<th>'.utf8_decode($td).'</th>';
							else    
								$table.='<td>'.utf8_decode($td).'</td>';
						}
						else
						{
							if($i==0)
								$table.='<th>'.($td).'</th>';
							else    
								$table.='<td>'.($td).'</td>';
							
						}
                    }   
                    $table.='</tr>';
                    $i++;
                }
                
                $table.='<table>';
                //echo $table;
            }
            $this->_view->table =  $table ;
            $this->_view->word_type =  $_POST['word_type'] ;
            $this->render("seotool_view");
        }   else {
            
            if(@$_GET['class'])
                $this->_view->class=$_GET['class'];
                
            $_POST['word_type']=1;
            $this->_view->word_type =  $_POST['word_type'] ;
            
            if(@$msg)   $this->_view->msg = $msg ;
            $client_info_obj = new Ep_User_User();
            $client_info= $client_info_obj->GetclientList();
            $client_list=array();

            for($c=0;$c<count($client_info);$c++)
            {
                $client_list[$c]['identifier']=$client_info[$c]['identifier'];

                $name=$client_info[$c]['email'];
                $nameArr=array($client_info[$c]['company_name'],$client_info[$c]['first_name'],$client_info[$c]['last_name']);
                $nameArr=array_filter($nameArr);
    
                if(count($nameArr)>0)
                    $name.="(".implode(", ",$nameArr).")";

                $client_list[$c]['name']=strtoupper($name);
            }
            asort($client_list);
            $this->_view->client_list = $client_list;
            if($_REQUEST['debug']){echo '<pre>'; print_r($client_info); print_r($client_list);exit;}
            $this->render("seotool_position");
        }

    }

    public function position2Action()
    {
        if(isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']) && isset($_GET['ext']))
            $this->posdownloadFile($_GET['file'],$_GET['ext']) ;
        
        if(isset($_GET['action']) && $_GET['action']=='view' && isset($_GET['file']) && isset($_GET['ext']))
        {
            $filename=$_GET['file'].".".$_GET['ext'] ;
            $path_file="seo_download/position/".$filename ;

            if(file_exists($path_file))
            {
                $data   =   $this->getCSV($path_file) ;
                
                $table='<table id="mytable" cellspacing=0>' ;
                if($_REQUEST['debug']){echo '<pre>'; print_r($data);exit($data);}
                $i=0;
                foreach($data as $row)
                {
                    $table.='<tr>';
                    foreach($row as $td)
                    {
                        if($this->getOS($_SERVER['HTTP_USER_AGENT']) != 'Windows')
                            $td =   utf8_decode($td) ;
                        else
                            $td =   $td ;
                        
                        if ( $_GET['type'] ==2 || $_GET['type'] ==3) :
                            $colspan    =   ($_GET['type'] ==2) ? 'colspan="2"' : 'colspan="4"' ;
                            $table.=   (( $i==0 || $i==3 ) ? ('<th '.(($i<3) ? $colspan : '' ).'>'.($td).'</th>') : ('<td id="'.$i.'" '.(($i<3) ? $colspan : '' ).'>'.($td).'</td>')) ;
                        elseif ( $_GET['type'] ==1 ) :
                            $table.=   (( $i==0 || $i==3 ) ? ('<th '.(($i<3) ? 'colspan="3"' : '' ).'>'.($td).'</th>') : ('<td id="'.$i.'" '.(($i<3) ? 'colspan="3"' : '' ).'>'.($td).'</td>')) ;
                        else :                            
                            $table.=   (( $i==0 || $i==3 || $i==6 ) ? ('<th '.(($i<6) ? 'colspan="4"' : '' ).'>'.($td).'</th>') : ('<td id="'.$i.'" '.(($i<6) ? 'colspan="4"' : '' ).'>'.($td).'</td>')) ;
                        endif ;
                                
                    }   
                    $table.='</tr>' ;
                    $i++;
                }
                
                $table.='<table>' ;
            }
            $this->_view->table =  $table ;
            $this->_view->word_type =  $_POST['word_type'] ;
            $this->render("seotool_view") ;
        }   else {
            
            if(@$_GET['class'])
                $this->_view->class   =   $_GET['class'] ;
            
            $this->_view->type   =   $_GET['type'] ;
            $this->_view->limit   =   $_GET['limit'] ;

            if(@$msg)   $this->_view->msg = $msg ;
            $this->render("seotool_position1") ;
        }
    }

    function getCSV($file)
    {
        setlocale(LC_ALL, 'fr_FR');
        $data_array=array();
        $row = 1;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $num = count($data);
                
                    for ($c=0; $c < $num; $c++) {
                        $data_array[$row][$c]=$data[$c];
                    }
                    $row++;
            }
            fclose($handle);
        }
        return $data_array;
    }
    
    //function to check frequency
    function checkFrequency()
    {
        $error='';
        if($this->frequency_option==1)
        {
            if(!$this->client)
                $error.='Please enter client name.<br>';
            if(!$this->title)
                $error.='Please enter title.<br>';  
            if(!$this->days)
                $error.='Please select atleast one frequency day.<br>'; 
            if(!$this->end_date)
                $error.='Please select end date of frequency.<br>';     
            
            if($error)  
                return $error;  
            else
                return "process";
        }
        else
            return "process";        
    }    
    
    //function to check frequency
    function checkSearchFrequency()
    {
        $error='';
        
        if(!$this->client)
            $error.='Please Select client .<br>';
        if(!$this->contract)
            $error.='Please Select contract.<br>';  
        if(!$this->from_date)
            $error.='Please select from date.<br>'; 
        if(!$this->to_date)
            $error.='Please select end date.<br>';      
        if(!$this->days)
            $error.='Please select any one of the frequency day.<br>';      
        
        if($error)  
            return $error;  
        else
            return "process";
        
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function posuploadAndProcess($srcFile,$u_file_name)
    {
        try
        {                   
                /**creating ssh component object**/
                $sftp = new Net_SFTP($this->ssh2_server) ;
                 if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                     throw new Exception('Login Failed') ;
                 }
                    
                //Path to execute ruby command
                $file_exec_path=$sftp->exec("./ep_position.sh "); //ruby execution path   
                                    
                /**getting upload path from alias**/
                if($this->type==5)
                    $file_upload_path=$sftp->exec("./ep_uploadv12.sh "); //upload path for files with type 5
                else if($this->type==4)
                    $file_upload_path=$sftp->exec("./ep_uploadv11.sh "); //upload path for files with type 4
                else if($this->type==3)
                    $file_upload_path=$sftp->exec("./ep_uploadv10.sh "); //upload path for files with type 3
                else if($this->type==2)
                    $file_upload_path=$sftp->exec("./ep_uploadv7.sh "); //upload path for files with type 2 
                else if($this->frequency_option==1)
                    $file_upload_path=$sftp->exec("./ep_frequency_upload.sh "); //upload path for files with frequency          
                else
                    $file_upload_path=$sftp->exec("./ep_uploadv1.sh "); //upload path for files with type 1     
                
                /**getting download path from alias**/
                if($this->type==5)
                    $file_download_path=$sftp->exec("./ep_download_v12.sh "); //download path for files with type 5
                else if($this->type==4)
                    $file_download_path=$sftp->exec("./ep_download_v11.sh "); //download path for files with type 4
                else if($this->type==3)
                    $file_download_path=$sftp->exec("./ep_download_v10.sh "); //download path for files with type 3
                else if($this->type==2)
                    $file_download_path=$sftp->exec("./ep_download_v7.sh "); //download path for files with type 2  
                else if($this->frequency_option==1)
                    $file_download_path=$sftp->exec("./ep_frequency_download.sh "); //download path for files with frequency            
                else
                    $file_download_path=$sftp->exec("./ep_download_v1.sh "); //download path for files with type 1                      
                
                    $sftp->chdir(trim($file_upload_path));
                    //$dstFile=$file_upload_path."/".$u_file_name;
                    $sftp->put($u_file_name,$srcFile,NET_SFTP_LOCAL_FILE);
                    
                /**processing the file**/  
                
                    /**passing file name**/
                    $src=pathinfo($u_file_name);
                    $download_fname=$src['filename']."_".time();
                    $dstfile=$download_fname.".".$src['extension'];
                    
                    /**processing File based on Options**/
                    if($this->type==5)
                        $ruby_file="v12live.rb";
                    else if($this->type==4)   
                        $ruby_file="v11live.rb";
                    else if($this->type==3)   
                        $ruby_file="v10live.rb";
                    else if($this->type==2)   
                        $ruby_file="v7live.rb";
                    else
                        $ruby_file="v1live.rb";
                        
                    /**Encoding Parameter**/
                    $os=$this->getOS($_SERVER['HTTP_USER_AGENT']) ;
                    if($os=='Windows')
                        $encoding='WINDOWS-1252';
                    else    
                        $encoding='UTF-8';
                    
                    $limitt =   $this->limit ;
                    $clientt =   $this->client ;
                    $titlee =   $this->title ;
                    $dayss =   $this->days ;
                    $end_datee =   $this->end_date ;
                    $site_idd =   $this->site_id ;
                    $format =   $this->format ? 2 : 1 ;$loginName  =   $this->adminLogin->loginName ;
                    $userId  =   $this->adminLogin->userId ;                    
                    
                    if($this->frequency_option==1)
                        $cmd="ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$clientt\" \"$titlee\" \"$dayss\" \"$end_datee\" \"$encoding\" $userId $loginName 2>&1 ";
                    else
                        $cmd="ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$encoding\" \"$format\" $userId $loginName 2>&1 ";

                    $sftp->setTimeout(300);
                    $file_exec_path=trim($file_exec_path);
                    $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
                    $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");             
                    
                    /**Downloading the Processed File**/                                
                    
                    /**processed file path**/
                    $remoteFile=trim($file_download_path)."/".$dstfile;

                        $sftp->chdir(trim($file_download_path)) ;
                        $file_path=pathinfo($remoteFile);
                        $localFile=APP_PATH_ROOT."seo_download/position/".$file_path['basename'];
                        $serverfile=$file_path;
                        $fname=$file_path['filename'];
                        $ext=$file_path['extension'];
                        
                        //downloading the file from remote server
                        $sftp->get($dstfile,$localFile);

                        if(file_exists($localFile) && trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head')    
                        {
                            $csv_data=$this->getCSV($localFile);
                            if($this->output_type==2)
                            {
                                $ext="xls";
                                $output_file=APP_PATH_ROOT."seo_download/position/".$fname.".".$ext;
                                
                                $this->WriteXLS($csv_data,$output_file);
                            }
                            $posAction  =   $this->format ? 'position2' : 'position' ;
                            $typeParam  =   ($this->type && $this->format) ? '&type='.$this->type : '' ;
                            $response['type'] = 'success';
                            $response['message'] = "File Successfully uploaded and processed.<br>";
                            $response['message'].="<a href=\"/seotool/position?action=download&file=".$fname."&ext=".$ext."\">Download the processed file</a>";
                            $response['message'].=' / <a target="_result" href="/seotool/' . $posAction . '?action=view&file='.$fname.$typeParam.'&ext=csv">View result</a>';
                            
                        }
                        else if(trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head' && $frequency_option==1)
                        {
                            $response['type'] = 'success';
                            $response['message'] = "File has been added for frequency position tracking.";
                        }
                        else
                        {
                            throw new Exception($output);
                        }
            
        }catch(Exception $e){
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        return $response;
        
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function posscheduleuploadAndProcess($srcFile,$u_file_name)
    {
        try
        {                   
                /**creating ssh component object**/
                $sftp = new Net_SFTP($this->ssh2_server);
                 if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                     throw new Exception('Login Failed');
                 }
                    
                //Path to execute ruby command
                $file_exec_path=$sftp->exec("./ep_position.sh "); //ruby execution path   
                                    
                /**getting upload path from alias**/
                if($this->type==5)
                    $file_upload_path=$sftp->exec("./ep_uploadv12.sh "); //upload path for files with type 5
                else if($this->type==4)
                    $file_upload_path=$sftp->exec("./ep_uploadv11.sh "); //upload path for files with type 4
                else if($this->type==3)
                    $file_upload_path=$sftp->exec("./ep_uploadv10.sh "); //upload path for files with type 3
                else if($this->type==2)
                    $file_upload_path=$sftp->exec("./ep_uploadv7.sh "); //upload path for files with type 2 
                else if($this->frequency_option==1)
                    $file_upload_path=$sftp->exec("./ep_frequency_upload.sh "); //upload path for files with frequency          
                else
                    $file_upload_path=$sftp->exec("./ep_uploadv1.sh "); //upload path for files with type 1     
                
                /**getting download path from alias**/
                if($this->type==5)
                    $file_download_path=$sftp->exec("./ep_download_v12.sh "); //download path for files with type 5
                else if($this->type==4)
                    $file_download_path=$sftp->exec("./ep_download_v11.sh "); //download path for files with type 4
                else if($this->type==3)
                    $file_download_path=$sftp->exec("./ep_download_v10.sh "); //download path for files with type 3
                else if($this->type==2)
                    $file_download_path=$sftp->exec("./ep_download_v7.sh "); //download path for files with type 2  
                else if($this->frequency_option==1)
                    $file_download_path=$sftp->exec("./ep_frequency_download.sh "); //download path for files with frequency            
                else
                    $file_download_path=$sftp->exec("./ep_download_v1.sh "); //download path for files with type 1

                    $sftp->chdir(trim($file_upload_path));
                    //$dstFile=$file_upload_path."/".$u_file_name;
                    $sftp->put($u_file_name,$srcFile,NET_SFTP_LOCAL_FILE);
                    
                                    
                /**processing the file**/  
                
                    /**passing file name**/
                    $src=pathinfo($u_file_name);
                    $download_fname=$src['filename']."_".time();
                    $dstfile=$download_fname.".".$src['extension'];
                    
                    $ruby_file="position_save_file_info.rb";
                        
                    /**Encoding Parameter**/
                    $os=$this->getOS($_SERVER['HTTP_USER_AGENT']) ;
                    if($os=='Windows')
                        $encoding='WINDOWS-1252';
                    else    
                        $encoding='UTF-8';
                    
                    $limitt =   $this->limit ;
                    $clientt =   'client name' ;   //$this->client ;
                    $titlee =   $this->title ;
                    $dayss =   $this->days ;
                    $end_datee =   $this->end_date ;
                    $site_idd =   $this->site_id ;
                    $format =   $this->format ? 2 : 1 ;
                    $cron_run_time  =   str_replace('/', '-', $this->cron_run_time) ;
                    $cron_email  =   $this->cron_email ;
                    
                /**seo options **/
                if($this->type==5)
                    $option = 12; 
                elseif($this->type==4)
                    $option = 11;  
                elseif($this->type==3)
                    $option = 10;  
                elseif($this->type==2)
                    $option = 7;
                else
                    $option = 1;

                $cmd="ruby -W0 $ruby_file $site_idd $u_file_name $dstfile \"$clientt\" \"$titlee\" $option \"$encoding\" \"$cron_run_time\" \"$cron_email\"" ;
                
                //$response['type'] = 'error';
                //$response['message'] = $cmd.'<br>'.'srcFile='.$srcFile.'<br>'.'u_file_name='.$u_file_name ;
                //return $response;
                
                    $sftp->setTimeout(300);
                    $file_exec_path=trim($file_exec_path);
                    $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
                    $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");             
                    
                    /**Downloading the Processed File**/                                
                    
                    /**processed file path**/
                    $remoteFile=trim($file_download_path)."/".$dstfile;
                        
                        $sftp->chdir(trim($file_download_path));
                        $file_path=pathinfo($remoteFile);
                        $localFile=APP_PATH_ROOT."seo_download/position/".$file_path['basename'];
                        $serverfile=$file_path;
                        $fname=$file_path['filename'];
                        $ext=$file_path['extension'];
                        
                        //downloading the file from remote server
                        $sftp->get($dstfile,$localFile);

                        /*if(file_exists($localFile) && trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head')    
                        {
                            $csv_data=$this->getCSV($localFile);
                            if($this->output_type==2)
                            {
                                $ext="xls";
                                $output_file=APP_PATH_ROOT."seo_download/position/".$fname.".".$ext;
                                
                                $this->WriteXLS($csv_data,$output_file);
                            }
                            $posAction  =   $this->format ? 'position2' : 'position' ;
                            $typeParam  =   ($this->type && $this->format) ? '&type='.$this->type : '' ;
                            $response['type'] = 'success';
                            $response['message'] = "Position cron scheduled successfully.<br>";
                        }
                        else if(trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head' && $frequency_option==1)
                        {*/
                            $response['type'] = 'success';
                            $response['message'] = "File has been scheduled for position tracking.";
                        /*}
                        else
                        {
                            throw new Exception($output);
                        }*/
            
        }catch(Exception $e){
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        return $response;
        
    }


    function getOS($userAgent) {
      // Create list of operating systems with operating system name as array key 
        $oses = array (
            'iPhone' => '(iPhone)',
            'Windows' => 'Win16',
            'Windows' => '(Windows 95)|(Win95)|(Windows_95)', // Use regular expressions as value to identify operating system
            'Windows' => '(Windows 98)|(Win98)',
            'Windows' => '(Windows NT 5.0)|(Windows 2000)',
            'Windows' => '(Windows NT 5.1)|(Windows XP)',
            'Windows' => '(Windows NT 5.2)',
            'Windows' => '(Windows NT 6.0)|(Windows Vista)',
            'Windows' => '(Windows NT 6.1)|(Windows 7)',
            'Windows' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
            'Windows' => 'Windows ME',
            'Open BSD'=>'OpenBSD',
            'Sun OS'=>'SunOS',
            'Linux'=>'(Linux)|(X11)',
            'Safari' => '(Safari)',
            'Macintosh'=>'(Mac_PowerPC)|(Macintosh)',
            'QNX'=>'QNX',
            'BeOS'=>'BeOS',
            'OS/2'=>'OS/2',
            'Search Bot'=>'(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)'
        );

        foreach($oses as $os=>$pattern){ // Loop through $oses array
        
        // Use regular expressions to check operating system type
            if (strpos($userAgent, $os)) { // Check if a value in $oses array matches current user agent.
                return $os; // Operating system was matched so return $oses key
            }
        }
        return 'Unknown'; // Cannot find operating system so return Unknown
    }
    
    /**function to read XLS file and return as array**/
    function readXLS($file)
    {
        /***********Getting File1 Data**********************/
            $data = new Spreadsheet_Excel_Reader();
            
            $data->setOutputEncoding('Windows-1252');
            //$data->setOutputEncoding('UTF-8');
            $data->read($file);
                    
            //echo "<pre>"; print_r($data->sheets[0]['cells']);echo "</pre>";exit;
                    
            if($data->sheets[0]['numRows'])
            {
                $x=1;
                while($x<=$data->sheets[0]['numRows']) {
                    $y=1;
                    while($y<=$data->sheets[0]['numCols']) {
                    
                        $xls_array[$x][$y]=isset($data->sheets[0]['cells'][$x][$y]) ? $data->sheets[0]['cells'][$x][$y] : '';
                        
                                        
                        $y++;
                    }
                    $x++;
                }                
            }
            return  $xls_array;            
    }
    
    /**function to read XLS file and return as array**/
    function readInXLS($file)
    {
        /***********Getting File1 Data**********************/
        $data = new Spreadsheet_Excel_Reader();
        $data->read($file);
                
        if($data->sheets[0]['numRows'])
        {
            $x=1;
            while($x<=$data->sheets[0]['numRows']) {
                $y=1;
                while($y<=$data->sheets[0]['numCols']) {
                    $xls_array[$x][$y]  =   isset($data->sheets[0]['cells'][$x][$y]) ? iconv("ISO-8859-1","UTF-8",$data->sheets[0]['cells'][$x][$y]) : '';
                    if($this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows')
                        $xls_array[$x][$y]   =   utf8_decode($xls_array[$x][$y]) ;
                                    
                    $y++;
                }
                $x++;
            }                
        }
        return  $xls_array;            
    }
    
    /**function to create CSV file**/
    function writeCSV($list,$file)
    {
        $fp = fopen( $file, 'w' );

        foreach ($list as $fields) {
        fputcsv($fp, $fields,";");
        }
        fclose($fp);
    }    
    
    /**function to create XLS file**/
    function WriteXLS($data,$file_name)
    {    
        // include package
        include 'Spreadsheet/Excel/Writer.php';

        // create empty file        
        $excel = new Spreadsheet_Excel_Writer($file_name);

        // add worksheet
        $sheet =& $excel->addWorksheet();
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
          foreach ($row as $key => $value) {
                  
            if($this->getOS($_SERVER['HTTP_USER_AGENT']) != 'Windows' )                  
                $value=utf8_decode($value);
              
            if($rowCount==0)
                $sheet->write($rowCount, $key, $value,$firstRow);
            else
                $sheet->write($rowCount, $key, $value);
          }
          $rowCount++;
        }
        // save file to disk
        $excel->close();
    }

    function showCSV($data)
    {
        $table='<table id="mytable" cellspacing=0>';
        
        $i=0;
        foreach($data as $row)
        {
                    
            $table.='<tr>';
            foreach($row as $td)
            {
                if($i==0)
                    $table.='<th>'.utf8_encode($td).'</th>';
                else    
                    $table.='<td>'.$td.'</td>';
            }   
            $table.='</tr>';
            $i++;
        }
        
        $table.='<table>';
        return $table;
    }
    
    function posdownloadFile ( $filename,$extension )
    {        
        $filename=$filename.".".$extension;
        $path_file="seo_download/position/".$filename;
        //echo $path_file;exit;
        if(file_exists($path_file))
        {   
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file"); 
            exit;
        }
        else
        {
            $this->class="error";
            $this->msg="File not Exist";
        }
    }

    public function plagiarismAction()
    {
        if(isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']) && isset($_GET['ext']))
            $this->plagdownloadFile($_GET['file'],$_GET['ext']);

        if($_GET['class'])
            $this->class=$_GET['class'];
        $_POST['word_type']=1;
        $this->_view->word_type =  $_POST['word_type'] ;
        
        if(@$msg)   $this->_view->msg = $msg ;
    
        $this->render("seotool_plagiarism");
    }
    
    function plagdownloadFile($filename,$extension)
    {
    
        $filename=$filename.".".$extension;    
        $path_file="seo_download/plagiarism/".$filename;   
        //echo $path_file;exit;
    
        if(file_exists($path_file))    
        {       
            header("Content-type: application/octet-stream");    
            header("Content-Disposition: attachment; filename=$filename");    
            ob_clean();    
            flush();    
            readfile("$path_file");    
            exit;    
        }     
        else    
        {    
            $this->class="error";    
            $this->msg="File not Exist";    
        }    
    }
    
    public function plagssh2uploadAction()
    {

        if(isset($_POST['submit']))
        {
            // response hash
            $response = array('type'=>'', 'message'=>'','word_type'=>1,'data'=>'');
            
            error_reporting(1);
            
            $this->type=$_POST['word_type'];            
            
            require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';
            require_once APP_PATH_ROOT.'nlibrary/script/filecontent.php';
            
            $uploads_dir = 'seo_upload/plagiarism';
            
                
            if($this->type==2)
            {
                $kw_text=trim($_POST['kw']);
                $kw_text=($kw_text); //utf8_decode
                if($kw_text)
                {
                    $fname="File_".time();
                    $txt_file_name=$fname.".txt";
                    $srcFile="seo_upload/plagiarism/".$txt_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kw_text);
                    fclose($fp);            
                    
                    $response=$this->plaguploadAndProcess($srcFile,$txt_file_name);       
                    $response['word_type']=$this->type;
                }       
                else
                {       
                    $response = array('type'=>'error','message'=>'Please enter Text in box','word_type'=>$this->type);       
                }   
                
            }
            else if($this->type==1)
            {
                    if($_FILES['keyword_file']['name'])
                    {
                        $tmpFile = $_FILES['keyword_file']['tmp_name'];
                        $u_file_name=str_replace(" ","_",$_FILES['keyword_file']['name']);
                        
                        $srcFile="$uploads_dir/$u_file_name";
                        
                        move_uploaded_file($tmpFile,$srcFile);
                        
                        /**getting content of uploaded  File**/
                        
                        $content=new filecontent($srcFile);
                        $status=$content->getStatus();
                        
                        $file=pathinfo($srcFile);
                        $ext= $file['extension'];               
                    
                        if($status==1)
                        {
                            $srcFile=$uploads_dir."/".$file['filename'].".txt";
                            $u_file_name=$file['filename'].".txt";
                            $response=$this->plaguploadAndProcess($srcFile,$u_file_name);
                            
                        }   
                        else
                        {
                            $response['type']='error';
                            $response['message']='File read error.re-upload the file!!!';
                        }
                        
                        $response['word_type']=$this->type;
                    }
                    else
                    {
                        $response['type'] = 'error';
                        $response['message'] = 'Please upload file having any one of these format(doc,docx,xls,xlsx,txt).';
                        $response['word_type']=$this->type;
                    }
                    
            }
                    
            print json_encode($response);
            exit;
        }
        
                
                
        
    }
    
    function plaguploadAndProcess($srcFile,$u_file_name)
    {
        try
        {                
                /**creating ssh component object**/
                
                $sftp = new Net_SFTP($this->ssh2_server);
                 if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                     throw new Exception('Login Failed');
                 }
                    
                //Path to execute ruby command
                $file_exec_path=$sftp->exec("./ep_plag_exec.sh "); //ruby execution path    
                
                /**getting upload path from alias**/
                $file_upload_path=$sftp->exec("./ep_plag_upload.sh");                                       
                            
                /**getting download path from alias**/
                $file_download_path=$sftp->exec("./ep_plag_download.sh");
                
                    $sftp->chdir(trim($file_upload_path));
                    //$dstFile=$file_upload_path."/".$u_file_name;
                    $sftp->put($u_file_name,$srcFile,NET_SFTP_LOCAL_FILE);
                    
                                    
                /**processing the file**/   
                                        
                    /**passing file name**/
                    $src=pathinfo($u_file_name);
                    $download_fname=$src['filename']."_".time();
                    $dstfile=$download_fname.".".$src['extension'];
                    $dstfile_xml=$download_fname.".xml";
					$format =   $this->format ? 2 : 1 ;
                    $loginName  =   $this->adminLogin->loginName ;
                    $userId  =   $this->adminLogin->userId ;                    
                    
                    /**processing File based on Options**/
                    $ruby_file="check_backup.rb";
                        
                    $cmd="ruby -W0 $ruby_file $u_file_name $dstfile $dstfile_xml $userId $loginName 2>&1 ";                    
                                                            
                    $sftp->setTimeout(300);
                    
                    $file_exec_path=trim($file_exec_path);
                    $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
                    $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;"); 
                                    
                /**Downloading the Processed File**/
                                
                    
                    /**processed file path**/
                    $remoteFile=trim($file_download_path)."/".$dstfile_xml;
                        
                        $sftp->chdir(trim($file_download_path));                    
                        $file_path=pathinfo($remoteFile);   
                        $localFile="seo_download/plagiarism/".$file_path['basename'];
                        $serverfile=$file_path;
                        $fname=$file_path['filename'];
                        $ext=$file_path['extension'];
                        
                        //downloading the file from remote server
                        $sftp->get($dstfile_xml,$localFile);
                        
                        if(file_exists($localFile) && trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head')
                        {
                            $response['type'] = 'success';
                            
                            $xml_data=$this->plagXMLParser($localFile);
                            
                            $response['data']=$xml_data;
                            $response['message']= "File Successfully uploaded and processed.<br>";
                            $response['message'].="<a href=\"/seotool/plagiarism?action=download&file=".$fname."&ext=".$ext."\">Download the processed file.</a>";
                            
                        }
                        else
                        {
                            throw new Exception($output);
                        }                   
            
            
        }catch(Exception $e){
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        return $response;
        
    }

    function plagXMLParser($file)
    {
        $data = file_get_contents($file);
        /*$xml = simplexml_load_file($file);
        $data='<b>Title : </b>'.$xml->article1->Title.'</br>';
        $i=0;
        foreach($xml->article1->Result->url as $URL)
        {
            $data.='<b><u>Result'.($i+1).' : </u></b></br>';
            $data.='<b>URL : </b><a href="'.$URL.'">'.$URL.'</a></br>';
            if($xml->article1->Result->percentage[$i]>=20)
                $data.='<b>Content : </b><span style="color:red">'.$xml->article1->Result->content[$i].'</span></br>';
            else    
                $data.='<b>Content : </b><span>'.$xml->article1->Result->content[$i].'</span></br>';
            $data.='<b>Percentage : </b>'.$xml->article1->Result->percentage[$i].'</br>';           
            
            $i++;
        }   */
        return $data;
        exit;       
    }    
    
    public function googlenewsAction()
    {
        if( isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']) && isset($_GET['ext']) )
            $this->gnewsdownloadFile( $_GET['file'], $_GET['ext'] ) ;
        
        if(isset($_GET['action']) && $_GET['action']=='view' && isset($_GET['file']) && isset($_GET['ext']))
        {
            $filename=$_GET['file'].".".$_GET['ext'];
            $path_file="seo_download/gnews/".$filename;
            
            if(file_exists($path_file))
            {
                header('Content-Type: text/html; charset=utf-8');
                $data   =   $this->getCSV($path_file);
                $table='<table id="mytable" cellspacing=0>';
                //echo '<pre>'; print_r($data);
                $i=0;
                foreach($data as $row)
                {
                    $j=1;
                    foreach($row as $td)
                    {
                        if(!empty($td)) :
                            if ( !mb_check_encoding( $td, 'UTF-8' ) )
                                $td =   iconv( "ISO-8859-1", "UTF-8", $td ) ;
                            if($i==0) :
                                $table.='<tr><th colspan="2">'.($td).'</th></tr>' ;
                            else :
                                $td =   (($j==1) ? '<a href="'.$row[1].'" target="_blank">'.$td.'</a>' : $td) ;
                                $table.= (($j==1) ? '<tr>' : '') . '<td '. (($colspan | ($i==1 && $j==1)) ? ' colspan="2" class="red">' : '>') . ( strstr($td, ')') ? str_replace( '")', '', substr( $td, (strpos($td, '","' ) + 3) ) ) : $td ) .'</td>' . (($j==2) ? '</tr>' : '') ;
                            endif ;
                            unset($colspan) ;
                        else :
                            $colspan    =   1 ;
                        endif ;
                        $j++;
                    }   
                    $i++;
                }                
                $table.='<table>';

            }
            $this->_view->table =  $table ;
            $this->_view->word_type =  $_POST['word_type'] ;
            $this->render("seotool_view");
        } else {
        
            if($_GET['class'])
                $this->_view->class=$_GET['class'];
                
            $_POST['word_type']=1;
            $this->_view->word_type =  $_POST['word_type'] ;
    
            $this->render("seotool_googlenews".($_REQUEST['debug'] ? '_test' : ''));
        }
    }
    
    function gnewsdownloadFile($filename,$extension)
    {        
        $filename=$filename.".".$extension;
        $path_file="seo_download/gnews/".$filename;
        //echo $path_file;exit;
        if(file_exists($path_file))
        {   
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file"); 
            exit;
        }   
        else
        {
            $this->class="error";
            $this->msg="File not Exist";
        }   
            
    }
    
    public function gnewsssh2uploadAction()
    {   //if($_REQUEST['debug']){echo '<pre>^^'; print_r($_REQUEST) ; exit;}   //
        if(isset($_REQUEST['submit']))
        {
            // response hash
            $response = array('type'=>'', 'message'=>'', 'word_type'=>1) ;
            
            error_reporting(0);
            
            $this->type =   $_POST['word_type'] ;
            
            require_once APP_PATH_ROOT.'nlibrary/script/reader.php';
            require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';            
            
            $this->output_type=$_POST['op_type'];
            $this->site_id=$_POST['site'];
            $this->limit=$_POST['limit'];
            
            if($this->type==2)
            {
                $kw_text    =   trim($_POST['kw']) ;
                if( ($this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows') )
                    $kw_text    =   utf8_decode( $kw_text ) ;
                
                /*if ( mb_check_encoding( $kw_text, "ISO-8859-1" ) )
                    $kw_text =   iconv( "ISO-8859-1", "UTF-8", $kw_text ) ;
                elseif ( mb_check_encoding( $kw_text, "Windows-1251" ) )
                    $kw_text =   iconv( "Windows-1251", "UTF-8", $kw_text ) ;
                elseif ( mb_check_encoding( $kw_text, "Windows-1252" ) )
                    $kw_text =   iconv( "Windows-1252", "UTF-8", $kw_text ) ;*/

                if($kw_text)
                {
                    $kw_text1   =  explode("\n",$kw_text) ;
                    
                    $csv_file_name="csv_".time().".csv";
                    $srcFile="seo_upload/gnews/".$csv_file_name;
                    $fp = fopen($srcFile, 'w');
                    fwrite($fp, $kw_text);
                    /* foreach($kw_text as $line)
                    {
                        fwrite($fp, $line."\n");        
                    } */
                    fclose($fp);
                    $response=$this->gnewsuploadAndProcess($srcFile,$csv_file_name);
                                
                    $response['word_type']=$this->type;
                }       
                else
                {       
                    $response = array('type'=>'error', 'message'=>'Please enter URL&keywords in box (CSV Format)','word_type'=>$this->type);
                }   
                
            }
            else if($this->type==1)
            {
                    if(($_FILES['keyword_file']['type']=='text/comma-separated-values')||($_FILES['keyword_file']['type']=='text/csv') ||($_FILES['keyword_file']['type']=='application/vnd.ms-excel')||($_FILES['keyword_file']['type']=='application/x-msexcel') ||  ($_FILES['keyword_file']['type']=='application/xls' ))
                    {
                        $file_info=pathinfo($_FILES['keyword_file']['name']);
                        $extension=$file_info['extension'];
                        if($extension=='xls')
                        {                            
                            $xls_array=$this->readInXLS($_FILES['keyword_file']['tmp_name']);
                            $u_file_name    = str_replace( " ", "_", $file_info['filename'] ) . ".csv";
                            $srcFile    =   APP_PATH_ROOT."seo_upload/gnews/".$u_file_name;
                            $this->writeCSV($xls_array,$srcFile) ;
                        }
                        else
                        {
                            $srcFile = $_FILES['keyword_file']['tmp_name'];
                            $u_file_name=str_replace(" ","_",$_FILES['keyword_file']['name']);
                        }   
                        
                        $response=$this->gnewsuploadAndProcess($srcFile,$u_file_name);
                        $response['word_type']=$this->type;
                    }
                    else
                    {
                        $response['type'] = 'error';
                        $response['message'] = 'Please upload csv or xls files.';
                        $response['word_type']=$this->type;
                    }
            }       
            
            print json_encode($response);
            exit;
        }                
    }

    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function gnewsuploadAndProcess($srcFile,$u_file_name)
    {
        try
        {
                /**creating ssh component object**/
                
                $sftp = new Net_SFTP($this->ssh2_server);
                 if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                     throw new Exception('Login Failed');
                 }
                    
                /**passing file name**/
                $src=pathinfo($u_file_name);
                $download_fname=$src['filename']."_".time();
                $this->seo_upload_files->gnews    =   $download_fname ;
                $dstfile    =   $download_fname.".".$src['extension'];
                    
                //Path to execute ruby command
                $file_exec_path=$sftp->exec("./ep_keyword_exec.sh"); //ruby execution path  
                                    
                /**getting upload path from alias**/
                $file_upload_path=$sftp->exec("./google_news_upload.sh ");
                
                /**getting download path from alias**/
                $file_download_path=$sftp->exec("./google_news_download.sh");
                    
                //echo $file_upload_path;exit;
                
                
                /**sending uploaded file to the server**/               
                    //$file_upload_path="/home/oboulo/Programs/ptrack/rails_projects/editplace/current/script/position";
                    $sftp->chdir(trim($file_upload_path));
                    //$dstFile=$file_upload_path."/".$u_file_name;
                    $sftp->put($u_file_name,$srcFile,NET_SFTP_LOCAL_FILE);

                /**processing the file**/   
                    //$ssh_conn_process=new Components_Ssh($ssh2_server,$ssh2_user_name,$ssh2_user_pass,22,'');

                    /**processing File based on Options**/
                    $ruby_file="keyword_url_hlink.rb";
                        
                    /**Encoding Parameter**/
                    $os=$this->getOS($_SERVER['HTTP_USER_AGENT']);
                    if($os=='Windows')
                        $encoding='WINDOWS-1252';
                    else    
                        $encoding='UTF-8';
                    
                    $limitt =   $this->limit ;
                    $site_idd =   $this->site_id ;
                    $loginName  =   $this->adminLogin->loginName ;
                    $userId  =   $this->adminLogin->userId ;  
                    $cmd="ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$encoding\" 1 $userId $loginName 2>&1 ";

                    //$response['message']=$cmd;
                    //$response['message']=getOS($_SERVER['HTTP_USER_AGENT']);
                    //return $response;exit;
                    
                    $sftp->setTimeout(300);
                    //echo $ssh->exec("whoami; source ~/.rvm/scripts/rvm; rvm use 1.9.3-head; which ruby");
                    //exit(0);
                    $file_exec_path=trim($file_exec_path);
                    $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
                    $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");             
                    
                    //echo $sftp->exec($cmd);
                    //sleep($total_rows*10);
                                    
                /**Downloading the Processed File**/

                    /**processed file path**/
                    $remoteFile=trim($file_download_path)."/".$dstfile;
                        
                        $sftp->chdir(trim($file_download_path)) ;
                        $file_path=pathinfo($remoteFile);
                        $localFile="seo_download/gnews/".$file_path['basename'] ;
                        $serverfile=$file_path;
                        $fname=$file_path['filename'];
                        $ext=$file_path['extension'];
                        
                        //downloading the file from remote server
                        $sftp->get($dstfile,$localFile);

                        if(file_exists($localFile) && trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head')    
                        {
                            if($this->output_type==2)
                            {
                                $ext="xls";
                                $output_file="seo_download/gnews/".$fname.".".$ext;
                                $csv_data=$this->gnewsgetCSV($localFile);
                                $this->WriteXLS($csv_data,$output_file);
                            }
                            
                            $response['type'] = 'success';
                            $response['message'] = "File Successfully uploaded and processed.<br>";
                            $response['message'].="<a href=\"/seotool/googlenews?action=download&file=".$fname."&ext=".$ext."\">Download the processed file.</a>";
                            $response['message'].=' / <a target="_result" href="/seotool/googlenews?action=view&file='.$fname.'&ext=csv">View result</a>';
                        }
                        else if(trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head' && $option3==1)
                        {
                            $response['type'] = 'success';
                            $response['message'] = "File has been added for frequency position tracking.";
                        }
                        else
                        {
                            throw new Exception($output);
                        }                   
            
            
        }catch(Exception $e){
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        return $response;
        
    }

    function gnewsgetCSV($file)
    {        
        $data_array=array();
        $row = 1;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $num = count($data);
                
                    for ($c=0; $c < $num; $c++) {
                        $data_array[$row][$c]=$data[$c];
                    }
                    $row++;
            }
            fclose($handle);
        }
        return $data_array;
    }
    
    public function frequencyAction()
    {
        if(isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']) && isset($_GET['ext']))
            $this->frequencydownloadFile($_GET['file'],$_GET['ext']);
            
        /**getting clients and contracts details**/
        require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';
        
        /**creating ssh component object**/
        
        $sftp = new Net_SFTP($this->ssh2_server);
        if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
            throw new Exception('Login Failed');
        }
            
            //Path to execute php command
        $file_exec_path=$sftp->exec("./ep_frequency_exec.sh"); //php execution path 
        $cmd="php getContracts.php 2>&1 ";                  
                                                                    
        $sftp->setTimeout(300);                 
        $file_exec_path=trim($file_exec_path);
        $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
        $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;"); 
                                
        $output=str_replace("Using /home/oboulo/.rvm/gems/ruby-1.9.3-head","",$output);
        $output=explode("$$$#####$$$",$output);
        
        $this->_view->clients=$output[0];
        $this->_view->contracts=$output[1];
        
        if(@$_GET['class'])
            $this->_view->class=$_GET['class'];
            
        $_POST['word_type']=1;
        $this->_view->word_type =  $_POST['word_type'] ;
        
        if(@$msg)   $this->_view->msg = $msg ;
        
        $this->render("seotool_frequency");
    }                        

    function frequencydownloadFile($filename,$extension)
    {
        
        $filename=$filename.".".$extension;
        $path_file="seo_download/frequency/".$filename;
        //echo $path_file;exit;
        if(file_exists($path_file))
        {   
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file"); 
            exit;
        }   
        else
        {
            $class="error";
            $msg="File not Exist";
        }   
            
    }
    
    public function frequencyssh2uploadAction()
    {
        if(isset($_POST['submit']))
        {
             // response hash
            $response = array('type'=>'', 'message'=>'');
            
            error_reporting(0);
            
            require_once APP_PATH_ROOT.'nlibrary/script/reader.php';
            require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';            
                
            $this->client=$_POST['client'];
            $this->contract=$_POST['contract'];
            $this->from_date=$_POST['from_date'];
            $this->to_date=$_POST['to_date']; 
            $this->days=implode("|",$_POST['day']);
            
            $frequency  =   $this->checkSearchFrequency();
             //$frequency='process';
            if($frequency=='process')
            {
                $response   =   $this->frequencyuploadAndProcess();
            }   
            else
            {
                $response['type']='error';
                $response['message']=$frequency;
            } 
            
            print json_encode($response);
            exit;
        }
    }
    
    /**function to connect to the linode server, uploading the csv and processing the csv file**/
    function frequencyuploadAndProcess()
    {        
        try
        {
                /**creating ssh component object**/
                
                $sftp = new Net_SFTP($this->ssh2_server);
                 if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                     throw new Exception('Login Failed');
                 }
                    
                //Path to execute ruby command
                $file_exec_path =   $sftp->exec("./ep_frequency_exec.sh"); //ruby execution path    
                
                /**getting download path from alias**/              
                $file_download_path =   $sftp->exec("./ep_frequency_zip_download.sh");
                                
                $dstfile=str_replace(" ","_",$this->contract)."_".time().".zip";                    
                
                /**processing File based on Options**/
                $ruby_file="render_frequency.rb";  
                
                $from_datee =   $this->from_date ;  
                $to_datee =   $this->to_date ;  
                $contractt =   $this->contract ;  
                $dayss =   $this->days ;             
                
                $cmd="ruby -W0 $ruby_file \"$from_datee\" \"$to_datee\" \"$contractt\" \"$dayss\" \"$dstfile\" 2>&1 ";
                                                                            
                $sftp->setTimeout(300);

                $file_exec_path=trim($file_exec_path);
                $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
                $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");  
                                    
                /**Downloading the Processed File**/                            
                
                /**processed file path**/
                $remoteFile=trim($file_download_path)."/".$dstfile;
                        
                $sftp->chdir(trim($file_download_path));                    
                $file_path=pathinfo($remoteFile);   
                $localFile="seo_download/frequency/".$file_path['basename'];
                $serverfile=$file_path;
                $fname=$file_path['filename'];
                $ext=$file_path['extension'];
                
                //downloading the file from remote server
                $sftp->get($dstfile,$localFile);                                    
                                    
                if(file_exists($localFile) && trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head')    
                {
                    $response['type'] = 'success';
                    $response['message'].="<a href=\"/seotool/frequency?action=download&file=".$fname."&ext=".$ext."\">Download the result file.</a>";
                }
                
                else
                {
                    throw new Exception($output);
                }
            
        }catch(Exception $e){
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        return $response;
        
    }

    /* SEO Tool status to show progress bar */
    function seotoolstatusAction()    {
        //$seotool_params =   $this->_request->getParams();
        //$this->seo_upload_files->gnews ;
        
        mysql_connect('50.116.62.9', 'editplace', 'ep123') or die('cant connect to 50.116.62.9') ;
        mysql_select_db('editplace') ;
        
        $table  =   $this->seo_upload_files->gnews ;
        $sql    =   mysql_query( "select CONCAT(COUNT(*), '*', (select COUNT(*) from $table where processed = '1')) AS result from $table" ) or die("$table") ;
        $result =   mysql_fetch_object( $sql ) ;
        exit($result->result) ;
    }
    
    public function googlesuggestAction()
    {
        //$googlesuggest_params =   $this->_request->getParams();
        if(isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']))
            $this->googlesuggestdownloadXLS($_GET['file']);
        
        $this->_view->word_type =  $_POST['word_type'] ;
        $this->_view->kw =  stripslashes(trim(strip_tags($_POST['kw']))) ;
        
        if(isset($_POST['submit']))
        {
            error_reporting(E_ALL ^ E_NOTICE);           
            
            if(@$msg)   $this->_view->msg = $msg ;
            
            $type=$_POST['word_type'];
            //URL based on the language
                
                $site=$_POST['site'];
                
                switch($site)
                {
                    case 'fr' : $url='google.fr';
                                    break;
                    case 'uk' : $url='google.co.uk';
                                    break;
                    case 'com' : $url='google.com';
                                    break;  
                    case 'de' : $url='google.de';
                                    break;
                    case 'in' : $url='google.co.in';
                                    break;
                    case 'it' : $url='google.it';
                                    break;
                    case 'es' : $url='google.es';
                                    break;  
                    case 'pt' : $url='google.pt';
                                    break;
                    case 'br' : $url='google.com.br';
                                    break;
                    default: $url='google.fr';
                                    break;
                
                }
                //Only Source or Combinations
            $combination=$_POST['combination'];
                
            if($type==1)
            {
                if(($_FILES['keyword_file']['type']=='text/comma-separated-values')||($_FILES['keyword_file']['type']=='application/vnd.ms-excel')||($_FILES['keyword_file']['type']=='text/csv'))
                {
                    /***********Getting File1 Data**********************/
                    $data_array=array();
                    $row = 1;
                    if (($handle = fopen($_FILES['keyword_file']['tmp_name'], "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                            $num = count($data);
                             if($data[0])       
                             {
                                for ($c=0; $c < $num; $c++) {
                                    if($data[$c]!='')
                                        $data_array[$row][$c]=$data[$c];
                                }
                                $row++;
                            }   
                        }
                        fclose($handle);
                        
                        $rows=count($data_array);
                        $cols=$num;
                    }
                    
                    if(count($data_array)>0)
                    {
                        $words =$data_array;
                        $j=1;
                        $this->gsuggest_excel_array[0][0]= "Keyword";
                        $this->gsuggest_excel_array[0][1]= "No Results";
                        
                        foreach($words as $word)
                        {
                            $word=trim($word[0]);
                            $this->googleSuggest($url,$word);
                            
                            if($combination==2)
                            {
                                 foreach(range('a','z') as $i)
                                {
                                    $query='';
                                    $query=$word.' '.$i;
                                    $this->googleSuggest($url,$query);
                                        
                                }
                            }   
                        }
                        
                        $this->googlesuggestWriteXLS($this->gsuggest_excel_array,'suggest');
                    }
                }   
            }   
            else if($type==2)
            {
                $text = trim($_POST['kw']);
                $textAr = explode("\n", $text);
                $words = array_filter($textAr, 'trim');
                
                if(count($words)>0)
                {
                    $j=1;
                    $this->gsuggest_excel_array[0][0]= "Keyword";
                    $this->gsuggest_excel_array[0][1]= "No Results";
                    
                    foreach($words as $word)
                    {
                        $word=trim($word);
                        $this->googleSuggest($url,$word);
                        
                        if($combination==2)
                        {
                             foreach(range('a','z') as $i)
                            {
                                $query='';
                                $query=$word.' '.$i;
                                $this->googleSuggest($url,$query);
                            }
                        }   
                    }
                    //write the data into XLS
                    $this->googlesuggestWriteXLS($this->gsuggest_excel_array,'suggest');
                }
            }   
        }

        if(count($this->gsuggest_excel_array)>1)
        {
            $length=count($this->gsuggest_excel_array)-1;
            $table='<table id="mytable" cellspacing="0" summary="Google Suggest for keywords">
                    <caption>URL:'.$url.'<br>
                    "'.$length.'" Suggestions for given keyword(s).</caption>
                    <tr>
                        <th scope="col" abbr="Keyword">Keyword</th>
                        <th scope="col" abbr="Number of Results">N&deg; Results</th>
                    </tr>';
            //for($i=0;$i<$length;$i++)
            foreach($this->gsuggest_excel_array as $key=>$word)
            {
                if($key>0)
                $table.='<tr><td>'.utf8_decode($word[0]).'</td><td>'.$word[1].'</td></tr>';
            }
            $table.='</table>';
            
            $this->_view->gsuggest_excel =  $table ;
        }
        $this->_view->gurl =  $_SESSION['gurl'] ? $_SESSION['gurl'] : '0' ;
        $this->render("seotool_googlesuggest");
    }
    
    function googlesuggestWriteXLS($data,$name)
    {
        // include package
        include 'Spreadsheet/Excel/Writer.php';

        // create empty file
        $filename=uniqid()."_".str_replace(' ','_',$name);
        $excel = new Spreadsheet_Excel_Writer("seo_download/gsuggest/".$filename.".xls");

        // add worksheet
        $sheet =& $excel->addWorksheet();
        $sheet->setInputEncoding('utf-8');
        // create format for header row
        // bold, red with black lower border
        $header_f=array(
            'bold'=>'1',
            'size' => '10',
            'FgColor'=>'yellow',
            'color'=>'black',
            'border'=>'1',
            'align' => 'center'); 
        $header =& $excel->addFormat($header_f);
        $cell_f=array(
                'color'=>'black',
                'border'=>'1',
                'align' => 'left'); 
        $cell =& $excel->addFormat($cell_f);

        // add data to worksheet
        $rowCount=0;
        foreach ($data as $row) {
          foreach ($row as $key => $value) {
            if($rowCount==0)
                $sheet->write($rowCount, $key, $value,$header);
            else
                $sheet->write($rowCount, $key, utf8_decode($value),$cell);
          }
          $rowCount++;
        }
        // save file to disk
        if ($excel->close() === true) {
          $this->_view->msg='Spreadsheet successfully saved! <a href="?action=download&file='.$filename.'">Download XLS</a>';
          $this->_view->class='success';
          //header("Location:google-suggest_dom.php?msg=success&file=".$filename);
        } else {
          //echo 'ERROR: Could not save spreadsheet.';
          $this->_view->msg='ERROR: Could not save spreadsheet.';
          $this->_view->class='error';
        }

    }
    function googlesuggestdownloadXLS($filename)
    {
        $filename=$filename.".xls";
        $path_file="seo_download/gsuggest/".$filename;
        //echo $path_file;exit;
        if(file_exists($path_file))
        {   
            
            header("Content-type: application/xls");
            header("Content-Disposition: attachment; filename=$filename");
            ob_clean();
            flush();
            readfile("$path_file"); 
            exit;
        }   
        else
            header("Location:/seotol/googlesuggest");
    }
    
    function gsuggestutf8dec($s_String)
    {
        $s_String = rawurlencode(utf8_encode($s_String));
        //echo $s_String;
        return $s_String;
    }
    
    function googleSuggest($site,$query)
    {        
        switch($site)
        {
            case 'google.fr' : $lang='fr';
                            break;
            case 'google.co.uk' : $lang='en-uk';
                            break;
            case 'google.com' : $lang='en';
                            break;  
            case 'google.de' : $lang='de';
                            break;
            case 'google.co.in' : $lang='en';
                            break;
            case 'google.it' : $lang='it';
                            break;
            case 'google.es' : $lang='es';
                            break;  
            case 'google.pt' : $lang='pt';
                            break;
            case 'google.com.br' : $lang='com.br';
                            break;
            default: $lang='fr';
                            break;                      
                        
        }
        
$_SESSION['gurl']=$url='http://'.$site.'/complete/search?q='. $this->gsuggestutf8dec($query). '&output=toolbar&ie=UTF-8&oe=UTF-8&lr=lang_'.$lang.'&hl='.$lang ;

                //echo $url."<br>";
                $xml = new DOMDocument; 
                $xml->load($url);
                $thedocument = $xml->documentElement;
                $list = $thedocument->getElementsByTagName('CompleteSuggestion') ;
    
                 foreach ($list as $domElement)
                 {  
                    foreach($domElement->childNodes as $node) { 
                         if($node->getAttribute('data'))
                            $suggest=$node->getAttribute('data');
                         if($node->getAttribute('int'))
                            $num_queries=$node->getAttribute('int');
                        else    
                            $num_queries="-";
                    }
                    $this->gsuggest_excel_array[]=array($suggest,$num_queries);
                    //echo $attrValue = $domElement->getAttribute('data')
                    // echo $attrValue = $domElement->getAttribute('data')
                }
    }
    
    public function plagcontentsAction()  {
        
        error_reporting(0) ;
        
        $plgFile    =   $_REQUEST['file'] ? $_REQUEST['file'] : $_REQUEST['s0plagfile'] ;
        if( !empty($plgFile) && !empty($_REQUEST['idx']) ) :
            
            $xmldata = simplexml_load_file("http://admin-ep.edit-place.com/BO/" . ( $_REQUEST['s0plagfile'] ? "plagarism/" : "seo_download/plagiarism/" ) . $plgFile);
            $plgs   =   array() ;
            
            foreach( $xmldata->children() AS $child ){
                foreach( $child->results->children() AS $child1 ){
                    foreach( $child1->url->children() AS $child2 ){
                        if($child2->getName() == 'p')
                            $plgs['url'][]  =   (string)$child2 ;
                    }
                    foreach( $child1->content->children() AS $child2 ){
                        if($child2->getName() == 'p')
                            $plgs['content'][]  =   (string)$child2 ;
                    }
                }
            }

            $words   =   $plgs['content'][$_REQUEST['idx'] - 1] ;
            $text   =   @file_get_contents($plgs['url'][$_REQUEST['idx'] - 1]) ;
        
            $text = html_entity_decode($text, ENT_NOQUOTES, "UTF-8");
            $text=preg_replace('/\s+/',' ',$text);
            $text =str_replace("<i>","",$text);
            $text =str_replace("</i>","",$text);
            
            $words=str_replace("&rsquo;","'",$words);
            $words=str_replace("&lsquo;","'",$words);
            $words=preg_replace('/\s+/',' ',$words);
            
            $this->_view->plagText =  $this->plagsHighlight($text, $words) ;
            
        else :
            $this->_view->plagText =  'missing - plag arguments' ;
        endif ;
        
        $this->render("seotool_plags_view") ;
    }

    function plagsHighlight($text, $words) {
        
        preg_match_all('/[^\s]+\s[^\s]+\s[^\s]+\s[^\s]+\s[^\s]+/', $words, $m) ;
        if(!$m)
            return $text;
        $re = '~\\b('.implode('|',$m[0]).')\\b~';
        
        foreach ($m[0] as $m_) :
            $text   =   str_replace($m_, '<mmm>' . $m_ . '</mmm>', $text) ;
        endforeach ;
        return $text ;
        
    }
    
    public function contentserrorcheckAction()  {
        
        $this->_view->err1  =   $_REQUEST['err1'] ;
        $this->_view->err2  =   $_REQUEST['err2'] ;
        @$lang1    =   $_REQUEST['lang'] ;
        $this->_view->lang1   =   ( (!empty($lang1)) ? $lang1 : '' ) ;
        
        $this->render("textcontentserror_check") ;
    }
    
    public function validatetagAction()  {
            
        /**getting clients and contracts details**/
        require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';
        
        try
        {   
            $sftp = new Net_SFTP($this->ssh2_server) ;
                if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                    throw new Exception('Login Failed') ;
            }

            //Path to execute ruby command
            $file_exec_path = $sftp->exec("./check_script.sh ") ; //ruby execution path
            $cmd    =   "ruby -W0 checkscript.rb" ;
            $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
            $output = $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");
        
            $response['type'] = 'success';
            $response['message'] = "Command executed successfully.";
        }
        catch(Exception $e)
        {
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        print json_encode($response);
        exit;
    }

    public function longtailkwsAction()
    {
        if($_REQUEST['debug']){echo '<pre>'; print_r($_SESSION);}
        if(isset($_GET['action']) && $_GET['action']=='download' && isset($_GET['file']) && isset($_GET['ext']))
            $this->posdownloadFile($_GET['file'],$_GET['ext']);
        
        if(isset($_GET['action']) && $_GET['action']=='view' && isset($_GET['file']) && isset($_GET['ext']))
        {
            $filename=$_GET['file'].".".$_GET['ext'];
            $path_file="seo_download/longtailkws/".$filename;

            if(file_exists($path_file))
            {
                $data   =   $this->getCSV($path_file);
                
                $table='<table id="mytable" cellspacing=0>';
                
                $i=0;
                foreach($data as $row)
                {                            
                    $table.='<tr>';
                    foreach($row as $td)
                    {
                        
                        if($this->getOS($_SERVER['HTTP_USER_AGENT']) != 'Windows' )                  
                        {
                            if($i==0)
                                $table.='<th>'.utf8_decode($td).'</th>';
                            else    
                                $table.='<td>'.utf8_decode($td).'</td>';
                        }
                        else
                        {
                            if($i==0)
                                $table.='<th>'.($td).'</th>';
                            else    
                                $table.='<td>'.($td).'</td>';
                            
                        }
                    }   
                    $table.='</tr>';
                    $i++;
                }
                
                $table.='<table>';
                //echo $table;
            }
            $this->_view->table =  $table ;
            $this->_view->word_type =  $_POST['word_type'] ;
            $this->render("seotool_view");
        }   else {
            
            if(@$_GET['class'])
                $this->_view->class=$_GET['class'];
                
            $_POST['word_type']=1;
            $this->_view->word_type =  $_POST['word_type'] ;
            
            if(@$msg)   $this->_view->msg = $msg ;
            
            if($_REQUEST['debug']){echo '<pre>'; print_r($client_info); print_r($client_list);exit;}
            $this->render("seotool_longtailkws");
        }

    }

    public function longtailkwuploadAction()
    {
        $pos_params=$this->_request->getParams();
        if(isset($pos_params['submit']))
        {
             // response hash
            $response = array('type'=>'', 'message'=>'','word_type'=>1);
            
            error_reporting(0);
            
            $word_type=$pos_params['word_type'];

            require_once APP_PATH_ROOT.'nlibrary/script/reader.php';
            require_once APP_PATH_ROOT.'nlibrary/script/Net/SFTP.php';
                                    
            $this->output_type=$pos_params['op_type'];
                
            $this->site_id=$pos_params['site'];
            $this->limit=$pos_params['limit'];
            
            if($word_type==2)
            {
                $kw_text= trim($pos_params['kw']) ;
                if( ($this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows'))
                    $kw_text=utf8_decode($kw_text);

                if( $kw_text )
                {
                    $kw_text1=explode("\n",$kw_text) ;
                    $csv_file_name="csv_".time().".csv" ;
                    $srcFile=APP_PATH_ROOT."seo_upload/longtailkws/".$csv_file_name ;
                    $fp = fopen($srcFile, 'w') ;
                    fwrite($fp, str_replace("\'", "'", $kw_text)) ;
                    fclose($fp) ;                    
                    
                    $frequency=$this->checkFrequency() ;
                    
                    if($frequency=='process')
                    {
                        $response   =   $this->longtailkwuploadAndProcess($srcFile,$csv_file_name) ;
                    }   
                    else
                    {
                        $response['type']='error';
                        $response['message']=$frequency;
                    }                    
                    $response['word_type']=$word_type;
                }       
                else
                {
                    $response = array('type'=>'error', 'message'=>'Please enter keywords','word_type'=>$word_type) ;
                }   
            }
            else if($word_type==1)
            {
                    if( ($_FILES['keyword_file']['type']=='text/comma-separated-values') || ($_FILES['keyword_file']['type']=='text/csv') || ($_FILES['keyword_file']['type']=='application/vnd.ms-excel') || ($_FILES['keyword_file']['type']=='application/x-msexcel') ||  ($_FILES['keyword_file']['type']=='application/xls') )
                    {
                        $file_info=pathinfo($_FILES['keyword_file']['name']);
                        $extension=$file_info['extension'];
                        
                        if($extension=='xls')
                        {
                            $xls_array    =   $this->readInXLS($_FILES['keyword_file']['tmp_name']) ;
                            $u_file_name=str_replace(" ","_",$file_info['filename']).".csv" ;
                            $srcFile=APP_PATH_ROOT."seo_upload/longtailkws/".$u_file_name;
                            $this->writeCSV($xls_array,$srcFile);
                        }
                        else
                        {
                            $srcFile = $_FILES['keyword_file']['tmp_name'];
                            $u_file_name=str_replace(" ","_",$_FILES['keyword_file']['name']);
                        }   
                        
                        $frequency=$this->checkFrequency();
                    
                        if($frequency=='process')
                        {
                            $response=$this->longtailkwuploadAndProcess($srcFile,$u_file_name);
                        }   
                        else
                        {
                            $response['type']='error';
                            $response['message']=$frequency;
                        }                        
                        $response['word_type']=$word_type;
                    }
                    else
                    {
                        $response['type'] = 'error';
                        $response['message'] = 'Please upload csv or xls files.';
                        $response['word_type']=$word_type;
                    }
            }       
       
            print json_encode($response);
            exit;
        }
        
    }
    
    function longtailkwuploadAndProcess($srcFile,$u_file_name)
    {
        try
        {                   
                /**creating ssh component object**/
                $sftp = new Net_SFTP($this->ssh2_server) ;
                 if (!$sftp->login($this->ssh2_user_name, $this->ssh2_user_pass)) {
                     throw new Exception('Login Failed') ;
                 }
                    
                //Path to execute ruby command
                $file_exec_path=$sftp->exec("./ep_keyword_exec.sh "); //ruby execution path   
                                    
                $file_upload_path=$sftp->exec("./google_news_upload.sh ");
                
                $file_download_path=$sftp->exec("./google_news_download.sh ");                    
                
                    $sftp->chdir(trim($file_upload_path));
                    //$dstFile=$file_upload_path."/".$u_file_name;
                    $sftp->put($u_file_name,$srcFile,NET_SFTP_LOCAL_FILE);
                    
                /**processing the file**/  
                
                    /**passing file name**/
                    $src=pathinfo($u_file_name);
                    $download_fname=$src['filename']."_".time();
                    $dstfile=$download_fname.".".$src['extension'];
                    
                    $ruby_file="longkeyword.rb";
                        
                    /**Encoding Parameter**/
                    $os=$this->getOS($_SERVER['HTTP_USER_AGENT']) ;
                    if($os=='Windows')
                        $encoding='WINDOWS-1252';
                    else    
                        $encoding='UTF-8';
                    
                    $limitt =   $this->limit ;
                    $site_idd =   $this->site_id ;
                    $loginName  =   $this->adminLogin->loginName ;
                    $userId  =   $this->adminLogin->userId ;
                    
                    $cmd="ruby -W0 $ruby_file $site_idd $u_file_name $dstfile $limitt \"$encoding\" 1 $userId $loginName ";
                    
                    $sftp->setTimeout(300);
                    $file_exec_path=trim($file_exec_path);
                    $ruby_switch_prefix = "source ~/.rvm/scripts/rvm; rvm use 1.9.3-head ";
                    $output= $sftp->exec("$ruby_switch_prefix ;cd $file_exec_path;$cmd ;");             
                    
                    /**Downloading the Processed File**/                                
                    
                    /**processed file path**/
                    $remoteFile=trim($file_download_path)."/".$dstfile;
            
                        $sftp->chdir(trim($file_download_path)) ;
                        $file_path=pathinfo($remoteFile);
                        $localFile=APP_PATH_ROOT."seo_download/longtailkws/".$file_path['basename'];
                        $serverfile=$file_path;
                        $fname=$file_path['filename'];
                        $ext=$file_path['extension'];
                    
                        
                        //downloading the file from remote server
                        $sftp->get($dstfile,$localFile);
                        
                        if(file_exists($localFile) && trim($output)=='Using /home/oboulo/.rvm/gems/ruby-1.9.3-head')    
                        {
                            $csv_data=$this->getCSV($localFile);
                            if($this->output_type==2)
                            {
                                $ext="xls";
                                $output_file=APP_PATH_ROOT."seo_download/longtailkws/".$fname.".".$ext;
                                
                                $this->WriteXLS($csv_data,$output_file);
                            }

                            $response['type'] = 'success';
                            $response['message'] = "File Successfully uploaded and processed.<br>";
                            $response['message'].="<a href=\"/seotool/longtailkws?action=download&file=".$fname."&ext=".$ext."\">Download the processed file</a>";
                            $response['message'].=' / <a target="_result" href="/seotool/longtailkws?action=view&file='.$fname.$typeParam.'&ext=csv">View result</a>';
                            
                        }
                        else
                        {
            $response['type'] = 'error';
            $response['message'] = $remoteFile.'<br>'.$localFile ;
            return $response;
                            throw new Exception($output);
                        }
            
        }catch(Exception $e){
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        
        return $response;
        
    }

}