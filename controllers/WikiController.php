<?php
/**
 * CPC Controller
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once APP_PATH_ROOT.'nlibrary/script/reader.php';
require_once 'Zend/Rest/Client.php';
//Zend_Loader::loadClass('Zend_Rest_Client');

class WikiController extends Ep_Controller_Action
{
	private $text_admin;
  	public function init()
    {
        parent::init();
        $this->_view->lang = $this->_lang;
        $this->adminLogin = Zend_Registry::get('adminLogin');
        $this->sid = session_id();
		$this->wiki_lang='en';
    } 
	public function contentAction()
    {
			
    	$this->render('seo_wiki_content');

    }	
	public function wikiContentAction()
    {
		// response hash
        $response = array('type'=>'', 'message'=>'','word_type'=>1) ;
		$lang_array=array("fr","en","de","it","es","pt");
		
		$wiki_params=$this->_request->getParams();
		$word_type=$wiki_params['word_type'] ;
		$combination=$wiki_params['combination'] ;
		$site_lang=$wiki_params['site_lang'] ;
		if(in_array($site_lang,$lang_array))
			$this->wiki_lang=$site_lang;
		
		
		if($word_type==2)
        {
            if($wiki_params['keywords'])
				$keywords_array   =   explode("\n",trim($wiki_params['keywords'])) ;            
			else
			{
				$response['type'] = 'error';
                $response['message'] = 'Please ener keywords';
                $response['word_type']=$word_type;	
			}			
        }
        else if($word_type==1) {
            
            
			if( ($_FILES['keyword_file']['type']=='text/comma-separated-values') || ($_FILES['keyword_file']['type']=='text/csv') || ($_FILES['keyword_file']['type']=='application/vnd.ms-excel') || ($_FILES['keyword_file']['type']=='application/x-msexcel') ||  ($_FILES['keyword_file']['type']=='application/xls') || ($_FILES['keyword_file']['type']=='application/msword') )
            {
                               
                $file_info=pathinfo($_FILES['keyword_file']['name']);
                $extension=$file_info['extension'];
                
                if($extension=='xls')
                {
                    $keywords    =   $this->readInXLS($_FILES['keyword_file']['tmp_name']) ;
                }
                else
                {
                    $keywords    =   $this->getCSV($_FILES['keyword_file']['tmp_name']) ;
                }
                
                foreach($keywords as $row) :
                    foreach($row as $value) :
                        $keywords_array[]   =   $value ;
                    endforeach ;
                endforeach ;                
                
                //echo '<pre>'; print_r($keywords_array); exit($url);
            }
            else
            {
                $response['type'] = 'error';
                $response['message'] = 'Please upload csv or xls files.';
				$response['message'].=print_r($_FILES,true);
                $response['word_type']=$word_type;
            }
        }
		//if keywords exists get suggesstions and content
		if(count($keywords_array)>0)
		{
			//get all suggestion for given keywords if combination is 2
			if($combination==2)
				$suggestion_keywords=$this->getWikiKeywordSuggestions($keywords_array);
			else	
				$suggestion_keywords=$keywords_array;
			
			if(count($suggestion_keywords)>0)
			{
				// if combinations then get all the suggestions
				if($combination==2)
				{
					foreach($suggestion_keywords as $word => $suggestions)
					{
						//$response['message'].=print_r($suggestions,true);
						$j=0;
						 foreach($suggestions as $suggestion)
						{
							if($suggestion)
							{
								//getting wiki content for each keyword
								$Content_display=$Content=(string)$this->getWikiContent($suggestion);
								//$Content=$this->convert_smart_quotes($this->cleanString($Content)) ;
								$Content=$this->cleanWikiContent($Content);
								
								$wikiContent[$word][$j]['word']=$suggestion;
								$wikiContent[$word][$j]['content']=$Content;
								$response['data'].=$Content_display."<br>================================================================<br>";
								$j++;
							}	
						}
					}
				}	
				elseif($combination==1)
				{
					$i=1;	
					$wikiContent[0][0]='Keyword';
					$wikiContent[0][1]='Wiki Content';
					foreach($suggestion_keywords as $key=>$suggestion)
					{
						$suggestion=trim($suggestion);
						if($suggestion) 
						{
							//getting wiki content for each keyword						
							$Content_display=$Content=(string)$this->getWikiContent($suggestion);
							//$Content=$this->convert_smart_quotes($this->cleanString($Content)) ;
							$Content=$this->cleanWikiContent($Content);
							
							$wikiContent[$i][0]=$suggestion;
							$wikiContent[$i][1]=$Content;
							$response['data'].=$Content_display."<br>================================================================<br>";
							
							$i++;
						}	
					}
				}
			
			}
			//echo "<pre>";print_r($wikiContent);exit;
			
			if(count($wikiContent)>0)
			{
				//write data in to XLS file
				$op_file_name="Wiki_".date("YmdHis");
				$ext="xls";
				
				$this->wikiWriteXLS($wikiContent,$op_file_name,$combination);				
				
				$response['type'] = 'success';				
				$response['message'].="<a href=\"/BO/download_wiki.php?saction=download&file=".$op_file_name."&ext=".$ext."\">Download the result file.</a>";
				$response['message'].=' / <a target="_result" href="/wiki/view-content?combination='.$combination.'&file='.$op_file_name.'&ext='.$ext.'">View result</a>';
			
			}
			
		}
		
		
		print json_encode($response);
        exit;

    }
	
	/*wiki keyword suggestions 
		Pass Ketwords to get Suggestions*/
	public function getWikiKeywordSuggestions($keywordsArray)
	{
		$suggestions=array();
		
		
		foreach($keywordsArray as $query)
		{
		
			try {
			  // initialize REST client
			  $wikipedia = new Zend_Rest_Client('http://'.$this->wiki_lang.'.wikipedia.org/w/api.php');

			  // set query parameters
			  $wikipedia->action('opensearch');		  
			  $wikipedia->format('xml');		  
			  $wikipedia->redirects('1');
			  $wikipedia->limit('100');
			  $wikipedia->search($query);

			  // perform request
			  // get page content as XML
			  $result = $wikipedia->get();

			  foreach ($result->Section->Item as $suggestion){
				$word=(string)$suggestion->Text;
				$suggestion_array[$query][]=str_replace("\n","",$word);				
			  }

			  //echo "<pre>";print_r($suggestion_array);
			  
			  
			} catch (Exception $e) {
				return('ERROR: ' . $e->getMessage());
			}
		}	
		return $suggestion_array;
	
	}
	/*wiki Content for keyword
		Pass Keyword to get Content*/
	public function getWikiContent($query)
	{
		try {
		  // initialize REST client
		  $wikipedia = new Zend_Rest_Client('http://'.$this->wiki_lang.'.wikipedia.org/w/api.php');

		  // set query parameters
		  $wikipedia->action('query');
		  $wikipedia->prop('extracts');
		  $wikipedia->exsectionformat('plain');
		  //$wikipedia->explaintext('1');		  
		  $wikipedia->format('xml');		  
		  $wikipedia->redirects('1');
		  $wikipedia->titles($query);
		  
		  // perform request
		  // get page content as XML
		  $result = $wikipedia->get();		  
		  $content = $result->query->pages->page->extract;		
		  unset($wikipedia);
		  
		  return   $content;
		  
		} catch (Exception $e) {
		    echo('ERROR: ' . $e->getMessage());
		}

	}	
	/**function to read XLS file and return as array**/
    function readInXLS($file)
    {
        /***********Getting File1 Data**********************/
        $data = new Spreadsheet_Excel_Reader();
        $data->read($file);
		//echo $data->dumpTxt(TRUE,TRUE);exit;
                
        if($data->sheets[0]['numRows'])
        {
            $x=1;
            while($x<=$data->sheets[0]['numRows']) {
                $y=1;
                while($y<=$data->sheets[0]['numCols']) {
                    //$xls_array[$x][$y]  =   isset($data->sheets[0]['cells'][$x][$y]) ? iconv("ISO-8859-1","UTF-8",$data->sheets[0]['cells'][$x][$y]) : '';
                    //if($this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows')
						//$xls_array[$x][$y]   =   utf8_decode($xls_array[$x][$y]) ;
					$xls_array[$x][$y]  =   isset($data->sheets[0]['cells'][$x][$y]) ? $data->sheets[0]['cells'][$x][$y] : '';						
                                    
                    $y++;
                }
                $x++;
            }                
        }
        return  $xls_array;            
    }
	//get data from csv
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
	//Write content to XLS file
	function wikiwWriteXLS($data,$name,$combination)
    {
        // include package
        include 'Spreadsheet/Excel/Writer.php';

        // create empty file
        $filename=$name;//"Wiki_".date("YmdHis");
        $excel = new Spreadsheet_Excel_Writer("seo_download/wiki/".$filename.".xls");
		$excel->setVersion(8);    

        // add worksheet
        $sheet =& $excel->addWorksheet();
        $sheet->setInputEncoding('UTF-8');
		//$sheet->setInputEncoding('ISO-8859-1');
		
		$sheet->setColumn(0,15,20);
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
		$cell-> setTextWrap();
		$cell->setVAlign('top');
		
		$empty=array(
                'color'=>'black',
                'border'=>'0',
                'align' => 'left'); 
        $empty_cell =& $excel->addFormat($empty);

       
		//echo "<pre>";print_r($data);exit; 
		// add data to worksheet
		
		if($combination==2)
		{
			$rowCount=0;
			foreach ($data as $row) {
			  foreach ($row as $key => $value) {
				
					$sheet->write($rowCount, $key, $value['word'],$header);
					$sheet->write($rowCount+1, $key, ($value['content']),$cell);
			  }
			  $rowCount=$rowCount+2;
			}
		}	
		else if($combination==1)
		{
			$rowCount=0;
			foreach ($data as $row) {				
			  foreach ($row as $key => $value) {
					$sheet->write($rowCount, $key, ($value),$cell);
				}
				$rowCount++;
			}
		}
        //echo "<pre>";print_r($excel);exit; 
		// save file to disk
        $excel->close();

    }
	
	//Write content to XLS file
	function wikiWriteXLS($data,$name,$combination)
    {
        setlocale(LC_CTYPE, 'fr_FR');
		// include package
        include 'Spreadsheet/Excel/Writer.php';		
        // create empty file
        $filename=$name;//"Wiki_".date("YmdHis");
        $excel = new Spreadsheet_Excel_Writer("seo_download/wiki/".$filename.".xls");
		$excel->setVersion(8);    

        // add worksheet
        $sheet =& $excel->addWorksheet();
        $sheet->setInputEncoding('UTF-8');
		//$sheet->setInputEncoding('ISO-8859-1');
		//$sheet->setInputEncoding('Windows-1252');
		
		$sheet->setColumn(0,15,20);
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
		$cell-> setTextWrap();
		$cell->setVAlign('top');
		
		$empty=array(
                'color'=>'black',
                'border'=>'0',
                'align' => 'left'); 
        $empty_cell =& $excel->addFormat($empty);

       
		//echo "<pre>";print_r($data);exit; 
		// add data to worksheet
		
		if($combination==2)
		{
			$rowCount=0;
			foreach ($data as $row) {
			  foreach ($row as $key => $value) {
					$value['content']=substr($value['content'],0,32000);
					$value['content']=iconv("ISO-8859-1","UTF-8",$value['content']);
					$value['word']=iconv("ISO-8859-1","UTF-8",$value['word']);
					$sheet->write($rowCount, $key, utf8_decode($value['word']),$header);
					$sheet->write($rowCount+1, $key, utf8_decode($value['content']),$cell);
			  }
			  $rowCount=$rowCount+2;
			}
		}	
		else if($combination==1)
		{
			$rowCount=0;
			foreach ($data as $row) {				
			  foreach ($row as $key => $value) {		
					$value=substr($value,0,32000);
					$value=iconv("ISO-8859-1","UTF-8",$value);
					if($rowCount==0)
						$sheet->write($rowCount, $key, utf8_decode($value),$header);
					else	
						$sheet->write($rowCount, $key, utf8_decode($value),$cell);
				}
				$rowCount++;
			}
		}
        //echo "<pre>";print_r($excel);exit; 
		// save file to disk
        $excel->close();

    }
	//view XLS content
	public function viewContentAction()
	{
		header("Content-type:text/html;Charset=UTF-8");
		$view_parms=$this->_request->getParams();
		if(isset($view_parms['file']) && isset($view_parms['ext']))
        {
			$combination=$view_parms['combination'];
			
			$filename=$view_parms['file'].".".$view_parms['ext'];
			$path_file=APP_PATH_ROOT."seo_download/wiki/".$filename;
			if(file_exists($path_file))
			{
				 
				 $xlsArr =   $this->readInXLS($path_file);	
				 if(count($xlsArr)>0)		
					$this->_view->table=$this->showXLS($xlsArr,$combination);
				 else	
					echo "File not readable";
			}
		}
		$this->render("seotool_view") ;
	}	
	//show XLS Data
	function showXLS($data,$combination)
    {
        $table=SEO_TBL_TG;
        
        $i=0;
        foreach($data as $row)
        {
                    
            if($i==0)
				$table.='<thead><tr>';
			else if($i==1)
				$table.='<tbody><tr>';			
			else
				$table.='<tr>';
            foreach($row as $td)
            {
                if($i%2==0 && $combination==2) {
                    $table.='<th>'.($td).'</th>';
				}	
				elseif($i==0){				
					$table.='<th>'.($td).'</th>';
                }else{
                    if($this->getOS($_SERVER['HTTP_USER_AGENT']) == 'Windows')    
                        $table.='<td>'.html_entity_decode($td, ENT_NOQUOTES, "ISO-8859-1").'</td>';
                    else
                        $table.='<td>'.$td.'</td>';
                }
            }   
            if($i==0)
				$table.='</tr></thead>';
			$table.='</tr>';
            $i++;
        }
        
        $table.='</tbody>'.SEO_TBL_TG;
        return $table;
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
	
	function cleanWikiContent($content) {
        
        //$content=preg_replace("/\[[^]]+\]/","",$content);
		
		$content=str_replace("�","'",$content);
		$content=preg_replace("/\([^)]+[^(]+\)/","",$content);
		
		$content=str_replace("e&#769;","&eacute;",$content);
        $content=str_replace("E&#769;","&Eacute;",$content);
        $content = html_entity_decode(htmlentities($content." ", ENT_COMPAT, 'ISO-8859-1'));
        return substr($content, 0, strlen($content)-1);
        
        //return $content;
    }
	 function cleanString($string) {
        
        $find[] = '�';  // left side double smart quote
        $find[] = '�';  // right side double smart quote
        $find[] = "�";  // left side single smart quote
        $find[] = "�";  // right side single smart quote
        $find[] = '�';  // elipsis
        $find[] = '�';  // em dash
        $find[] = '�';
        
        $replace[] = '"';
        $replace[] = '"';
        $replace[] = "'";
        $replace[] = "'";
        $replace[] = '...';
        $replace[] = '-';
        $replace[] = '-';
        
        return str_replace($find, $replace, $string);
    }

    function convert_smart_quotes($string) 
    { 
        $search = array(chr(145), 
                        chr(146), 
                        chr(147), 
                        chr(148), 
                        chr(151),
                        chr(230),
                        chr(156)); 
    
        $replace = array("'", 
                         "'", 
                         '"', 
                         '"', 
                         '-',
                         'ae',
                         'oe'); 
    
        return str_replace($search, $replace, $string); 
    }   
    
}

