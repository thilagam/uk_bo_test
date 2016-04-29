<?php
/**
 * FileListingController
 * 
 * @author shiva
 * @version 1.0
 */

class TranslationsearchController extends Ep_Controller_Action
{
	private $my_obj;
	private $dynamicName;
	private $filesname;
    private $entryLang;
    private $cntry;
    private $totalLang;
    private $my_view;
    private $text_admin;
    
	public function init()
	{
		parent::init();
		$this->_view->lang = $this->_lang;
		$this->cntry = Zend_Registry::get('_country');
		$this->_view->cntry = $this->cntry;
		$this->totalLang = Zend_Registry::get('_totalLang');
		$this->_view->allLang = $this->totalLang;		
		$this->my_obj = new Ep_Db_ArrayDb2("/home/sites/site6/users/xmldb/main_meta_data.xml", "root");
		//$this->my_obj = new Ep_Db_ArrayDb2("/home/sites/site6/users/xmldb/search_test.xml", "root");		
		$this->_view->siteName = $_SERVER['HTTP_HOST'];		
		$this->xml_path = "/home/sites/site6/users/xmldb/NewDb/";		
		$this->livepath = "/home/sites/site6/users/xmldb/livedb/";		
		$this->adminLogin	= Zend_Registry::get ( 'adminLogin' );
		$this->_view->loginName = $this->adminLogin->loginName;
		$this->_view->permission = $this->adminLogin->permission;		
		$this->text_admin =  $this->_arrayDb->loadArrayv2("text_admin", $this->_lang);
		$this->_view->ADMINISTRATION_INTERFACE = $this->text_admin['ADMINISTRATION_INTERFACE'];
		$this->_view->CORRECTOR = $this->text_admin['CORRECTOR'];
		$this->_view->Disconnect = $this->text_admin['Disconnect'];
		$this->_view->Login = $this->text_admin['Login'];
		$this->_view->Password = $this->text_admin['Password'];
	    $this->_view->Country = $this->text_admin['Country'];
	    
	    $action = $this->_request->getParam("action");
	    $this->_view->urlAdd = $this->_config->www->adminurl;
		ini_set("memory_limit","100M");
	}
	
	public function searchwordAction()
	{
		$this->_view->charset = "utf8";
		$this->render("translationsearch_searchword");
	}
	
	// master of all search - it will search anything in the website and gives the data to update or modify it. 
	public function searchAction()
	{
		$this->_view->charset = "utf8";
		$find = stripslashes(utf8_decode($this->_request->getParam("find")));
		$this->_view->page = $page = $this->_request->getParam("page");
		$this->_view->temp = $temp = $this->_request->getParam("temp");
		
		if($this->_request->isPost())
		{
			$mod = $this->_request->getParam("modify");
			if(isset($mod))
			{
				$full = $this->_request->getParam("full");
				$this->_view->page = $page = $this->_request->getParam("page2");
				$this->_view->temp = $temp = $this->_request->getParam("temp2");
				foreach ($full as $file => $arrIndex)
				{
					foreach ($arrIndex as $nameArr => $lastArr)
					{
						$temparrayName = $nameArr;
						foreach ($lastArr as $lang => $final)
						$this->my_obj->updateEachIndexNodePart2($file,$temparrayName,$final,$lang);
					}
				}
			}
		}
		
		if(!isset($page))
		$this->_view->page = 40;
		
		if(!isset($temp))
		$this->_view->temp = 0;
		
		$i = $k = 0;
		
		$result = array();
		if($find != '')
		{
		$data = $this->my_obj->getAllNodes();
		$countNameArr = array();		
		foreach($data as $fileName)
		{
			$firstName = $fileName[0]->getName();
			foreach($fileName as $arrayName)
			{
				$templang = $arrayName[0]->getName();				
				foreach($arrayName as $indexName)
				{
					$tempArr = $indexName[0]->getName();
					//if(!in_array($tempArr,$countNameArr))
					//$countNameArr[$pk++] = $tempArr;
					$langlist = (string)$indexName[0]['language'];
					$indexing = (string)$indexName[0]['index'];
					$type = (string)$indexName[0]['type'];
					
					$filePathName = $this->xml_path."$tempArr.xml";
					$xml =  @simplexml_load_file($filePathName);
					$strTemp = '/root/' . $firstName .'/'.$templang.'/'.$tempArr;
					$indexName = $xml->xpath($strTemp);
					if($type == 'Array')
					foreach($indexName[0]->children() as $lastNode)
					{
						if((stristr(stripslashes(utf8_decode(urldecode($lastNode[0]))),$find) || stristr((string)$lastNode[0]->getName(),$find)) && !count($result[$tempArr][(string)$lastNode[0]->getName()]))
						{
							$langlistarr = split("\|",$langlist);							
							$result[$tempArr][(string)$lastNode[0]->getName()] = $this->my_obj->searchdetailsPart2($firstName,$tempArr, (string)$lastNode[0]->getName(),$langlistarr);
							$result[$tempArr][(string)$lastNode[0]->getName()]['all_lang'] = $langlist;
							$result[$tempArr][(string)$lastNode[0]->getName()]['filename'] = $firstName;
							$result[$tempArr][(string)$lastNode[0]->getName()]['type'] = $type;
						}
					}
					else
					{
						if((stristr(stripslashes(utf8_decode(urldecode($indexName[0]))),$find) || stristr((string)$indexName[0]->getName(),$find)))
						{
							if(!count($result[$tempArr][(string)$indexName[0]->getName()]))
							{
								$langlistarr = split("\|",$langlist);
								$result[$tempArr][(string)$indexName[0]->getName()] = $this->my_obj->searchdetails2Part2($firstName,$tempArr,$langlistarr);
								$result[$tempArr][(string)$indexName[0]->getName()]['all_lang'] = $langlist;
								$result[$tempArr][(string)$indexName[0]->getName()]['filename'] = $firstName;
								$result[$tempArr][(string)$indexName[0]->getName()]['type'] = $type;
							}							
						}
					}
				}
			}
		}
		}
		$this->_view->doclist = $result;
		$this->_view->find = $find;
		$this->render("translationsearch_search");
	}
	
	// display all the array names of the big xml file.
	public function xmloptiAction()
	{
		//$this->my_obj2 = new Ep_Db_ArrayDb2("/home/sites/site6/users/xmldb/NewDb/meta_dat.xml", "root");		
		$data = $this->my_obj->getAllNodes();
		$countNameArr = array();
		$pk = 0;
		foreach($data as $fileName)
		{
			$firstName = $fileName[0]->getName();
			//$firstName = str_replace(".php","",(string)$firstName);			
			foreach($fileName as $arrayName)
			{
				$templang = $arrayName[0]->getName();				
				foreach($arrayName as $indexName)
				{
					$tempArr = $indexName[0]->getName();
					if(!in_array($tempArr,$countNameArr))
					{
						$countNameArr[$pk++] = $tempArr;
					}
				}
			}
		}
		print_r($countNameArr);
	}
	
	// this is used to generate the small xml files from the original big xml file	
	public function xmlopti2Action()
	{
		$allfilename = array("texte.php","blackList.php","country.php","data.php","error.php","meta_values.php","price.php","sending.php","subject.php","process.php");
		
		$data = $this->my_obj->getAllNodes();
		$countNameArr = array();
		foreach($data as $fileName)
		{
			$firstName = $fileName[0]->getName();		
			foreach($fileName as $arrayName)
			{
				$templang = $arrayName[0]->getName();				
				foreach($arrayName as $indexName)
				{
					$tempArr = $indexName[0]->getName();
					if(!in_array($tempArr,$countNameArr))
					{
						$countNameArr[$pk++] = $tempArr;
						$createArray[$firstName][] = $tempArr;
					}
				}
			}
		}		
		$i = $K = 0;
		foreach ($createArray as $file=>$namearr)
		{
			$this->my_obj2 = new Ep_Db_ArrayDb("/home/sites/site6/users/xmldb/NewDb/meta_data.xml", "root");		
			$data = $this->my_obj2->getAllNodes();		
			$data2 = $this->my_obj->getAllNodes();
			
			foreach ($allfilename as $filenames)
			{
				if($filenames != $file)
				unset($data->$filenames);
			}
			foreach ($namearr as $arrr)
			{
				$attr = $data->$file->attributes();
				$totLang = split("\|",$attr['language']);
				$whtType = $attr['type'];
				foreach ($totLang as $l)
				{
					$str = "//$file/$l/$arrr";
					$some = $data2->xpath($str);					
					if(!empty($some))
					{
						$attr2 = $some[0]->attributes();
						$whtType = $attr2['type'];
						if($whtType == 'Array')
						{
							if(count($some[0]->children()))						
							foreach ($data as $loop)
							{			
								foreach ($some[0] as $an)
								{
									$nme = $an[0]->getName();
									$data[0]->$file->$l->$arrr->$nme = $an[0];
								}
							}
							if(!empty($attr2))
							foreach ($attr2 as $k => $v)
							{
								if(!empty($data[0]->$file->$l->$arrr))
								$data[0]->$file->$l->$arrr->addAttribute($k,$v);
								else
								{
									$data[0]->$file->$l->$arrr = "";
									$data[0]->$file->$l->$arrr->addAttribute($k,$v);
								}								
							}
						}
						else 
						{						
							foreach ($data as $loop)
							{			
								$data[0]->$file->$l->$arrr = $some[0];
							}
							if(!empty($attr2))
							foreach ($attr2 as $k => $v)
							{
								$data[0]->$file->$l->$arrr->addAttribute($k,$v);
							}
						}
					}			
				}
				//echo '<br/>- '.$arrr;
				//exit();
				/*$fp = @fopen("/home/sites/site6/users/xmldb/livedb/$arrr.xml", "w+");
				@fwrite($fp, $data->asXml());
				chmod("/home/sites/site6/users/xmldb/livedb/$arrr.xml",0777);
				@fclose($fp);
				foreach ($totLang as $l)
				{
					unset($data->$file->$l->$arrr);
				}*/
			}
			//if($i++ == 3) 
		}
	}
	
	//creating the main_meta_data xml optimization file.
	public function xmlopti4Action()
	{
		$allfilename = array("texte.php","blackList.php","country.php","data.php","error.php","meta_values.php","price.php","sending.php","subject.php","process.php");
		
		$data = $this->my_obj->getAllNodes();
		$countNameArr = array();
		foreach($data as $fileName)
		{
			$firstName = $fileName[0]->getName();		
			foreach($fileName as $arrayName)
			{
				$templang = $arrayName[0]->getName();				
				foreach($arrayName as $indexName)
				{
					$tempArr = $indexName[0]->getName();
					if(!in_array($tempArr,$countNameArr))
					{
						$countNameArr[$pk++] = $tempArr;
						$createArray[$firstName][] = $tempArr;
					}
				}
			}
		}
		$i = $K = 0;
		$this->my_obj2 = new Ep_Db_ArrayDb2("/home/sites/site6/users/xmldb/NewDb/meta_data.xml", "root");		
		$data = $this->my_obj2->getAllNodes();		
		$data2 = $this->my_obj->getAllNodes();
		foreach ($createArray as $file=>$namearr)
		{		
			foreach ($namearr as $arrr)
			{
				$attr = $data->$file->attributes();
				$totLang = split("\|",$attr['language']);
				$whtType = $attr['type'];
				foreach ($totLang as $l)
				{
					$str = "//$file/$l/$arrr";
					$some = $data2->xpath($str);
					
					if(!empty($some))
					{
						$attr2 = $some[0]->attributes();
						$whtType = $attr2['type'];
						if($whtType == 'Array')
						{							
							if(!empty($attr2))
							foreach ($attr2 as $k => $v)
							{
								if(!empty($data[0]->$file->$l->$arrr))
								$data[0]->$file->$l->$arrr->addAttribute($k,$v);
								else
								{
									$data[0]->$file->$l->$arrr = "";
									$data[0]->$file->$l->$arrr->addAttribute($k,$v);
								}								
							}
						}
						else 
						{
							if(!empty($attr2))
							foreach ($attr2 as $k => $v)
							{
								$data[0]->$file->$l->$arrr->addAttribute($k,$v);
							}
						}
					}			
				}
				//echo $data->asXml()."<br/><br/>";
				//unlink("/home/sites/site6/users/xmldb/NewDb/$arrr.xml");							
			}
			//if($i++ == 3)
		}
		
		$fp = @fopen("/home/sites/site6/users/xmldb/bo/main_meta_data.xml", "w+");
		@fwrite($fp, $data->asXml());
		@fclose($fp);
	}
	
	public function loadarraychckAction()
	{
		$array = $this->_request->getParam("array");
		
		if(!isset($array))
		$array = "UECountry";
		
		Zend_Loader::loadClass('Ep_Db_NewXmlDb');		
		$start_time = microtime(true);
		$newObj = new Ep_Db_NewXmlDb();
		$data1 = $newObj->newloadArray($array,$this->_lang);
		echo "newloadArray  : " . (microtime(true) - $start_time);
		//print_r($data1);
		$start_time = microtime(true);
		$this->my_obj2 = new Ep_Db_ArrayDb2("/home/sites/site6/users/xmldb/search_test.xml", "root");
		$data2 = $this->my_obj2->loadArrayv2($array,$this->_lang);
		echo "<br/>loadArrayv2  : " . (microtime(true) - $start_time);
		echo "<br/>";
	}
	
	public function updateAction()
	{
		$fr = $this->_request->getParam("fr");
		$pt = $this->_request->getParam("pt");
		$en = $this->_request->getParam("en");
		$in = $this->_request->getParam("in");
		$filename = $this->_request->getParam("filename");
		$arrName = $this->_request->getParam("arrName");
		$index = $this->_request->getParam("index");
		$type = $this->_request->getParam("type");
		$langfrView = $this->_request->getParam("langfrView");
		$langfrView = split("\|",$langfrView);
		$arr = array();
		foreach ($langfrView as $val)
		$arr[$val] = $$val;		
		
		if($type == 'Simple')
		$this->my_obj->updateSimpleIndexNode2Part2($filename, $arrName, $arr);
		if($type == 'Array')
		$this->my_obj->updateEachIndexNode2Part2($filename, $arrName, $arr, $index);
	}	
	
	public function syncxmlAction()
	{
		$fr = $this->_request->getParam("fr");
        $pt = $this->_request->getParam("pt");
        $en = $this->_request->getParam("en");
        $in = $this->_request->getParam("in");
        $filename = $this->_request->getParam("filename");
        $arrName = $this->_request->getParam("arrName");
        $index = $this->_request->getParam("index");
        $type = $this->_request->getParam("type");
        $langfrView = $this->_request->getParam("langfrView");
        $langfrView = split("\|",$langfrView);
        $arr = array();
        foreach ($langfrView as $val)
        $arr[$val] = $$val;
        //print_r($this->_request->getParams());
        //exit();
        //test xml DB       
        if($type == 'Simple')
        $this->my_obj->updateSimpleIndexNode2Part2($filename, $arrName, $arr);
        if($type == 'Array')
        $this->my_obj->updateEachIndexNode2Part2($filename, $arrName, $arr, $index);
        
        //live xml DB
        if($type == 'Simple')
        $this->my_obj->updateSimpleIndexNode2Part2($filename, $arrName, $arr,"liveup");
        if($type == 'Array')
        $this->my_obj->updateEachIndexNode2Part2($filename, $arrName, $arr, $index,"liveup");
        
		//if(!copy($this->xml_path.$arrName.".xml", $this->livepath.$arrName.".xml")) 
//		{
//    		echo "failed to copy live folder...\n";
//		}		
	}
	
	public function updatesyncxmlAction()
	{
		$fr = $this->_request->getParam("fr");
		$pt = $this->_request->getParam("pt");
		$en = $this->_request->getParam("en");
		$in = $this->_request->getParam("in");
		$filename = $this->_request->getParam("filename");
		$arrName = $this->_request->getParam("arrName");
		$index = $this->_request->getParam("index");
		$type = $this->_request->getParam("type");
		$langfrView = $this->_request->getParam("langfrView");
		$langfrView = split("\|",$langfrView);
		$arr = array();
		foreach ($langfrView as $val)
		$arr[$val] = $$val;		
		
		if($type == 'Simple')
        $this->my_obj->updateSimpleIndexNode2Part2($filename, $arrName, $arr);
        if($type == 'Array')
        $this->my_obj->updateEachIndexNode2Part2($filename, $arrName, $arr, $index);
        
        //live xml DB
        if($type == 'Simple')
        $this->my_obj->updateSimpleIndexNode2Part2($filename, $arrName, $arr,"liveup");
        if($type == 'Array')
        $this->my_obj->updateEachIndexNode2Part2($filename, $arrName, $arr, $index,"liveup");
          
          
//      $this->my_obj->updateSearchedNode($filename, $arrName, $arr);
        
//		
		//if(!copy($this->xml_path.$arrName.".xml", $this->livepath.$arrName.".xml")) 
//		{
//    		echo "failed to copy live folder...\n";
//		}
	}	
	
	public function searchnewAction()
	{
		$this->searchwordAction();
	}
	
	public function array_compare($needle, $haystack, $match_all = true)
	{
		if (!is_array($needle) || sizeof($haystack) > sizeof($needle))
		{
			return false;
		}	
		$count = 0;
		$result = false;
		foreach ($needle as $k => $v)
		{
			if (!isset($haystack[$k]))
			{
				if ($match_all)
				{
					return false;
				}
				continue;
			}		
			if (is_array($v))
			{
				$result = array_compare($v, $haystack[$k], $match_all);
			}
			$result = ($haystack[$k] === $v) && (($match_all && (!$count || $result)) ||!$match_all) || (!$match_all && $result);
			$count++;
		}
		return $result;
	} 

	public function comparearrayAction()
	{
		$this->_view->charset = "utf8";
		$this->my_obj = new Ep_Db_ArrayDb2();
		$filename = $this->_view->fileName = $this->_request->getParam("fileName");
		$arrName = $this->_view->arrayName = $this->_request->getParam("arrayName");		
		$sync = $this->_request->getParam("sync");
		if($this->_request->isPost())
		{
			if(isset($sync))
			if(!copy($this->xml_path.$arrName.".xml", $this->livepath.$arrName.".xml")) 
			{
	    		echo "failed to copy live folder...\n";
			}
		}
		
		$a1 = $test = $this->my_obj->testloadArray($arrName,$this->_lang); // Test
		$a2 = $live = $this->my_obj->liveloadArray($arrName,$this->_lang); //Live
		
		$res_a= array_keys($a1);
		$res_b= array_keys($a2);
		$result= array_unique(array_merge($res_a,$res_b));
		foreach($result as $k=>$v)
		{
			if($a1[$v]== $a2[$v])
			continue;
			$status[]=$v;
			if(isset($a1[$v]))
			unset($a1[$v] );
			if(isset($a2[$v]))
			unset($a2[$v] );
		}
		//print_r($status);
		//print_r($a2);
		//exit();
		
		
		$this->_view->doclist = $status;
		$this->_view->livearr = $live;
		$this->_view->testarr = $test;
		$this->_view->countLive = count($live);
		$this->_view->countTest = count($test);
		$this->render("translationsearch_comparearray");
	}
	
}